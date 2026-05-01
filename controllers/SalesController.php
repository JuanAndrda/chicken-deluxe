<?php
/**
 * SalesController — record daily sales per product
 */
require_once __DIR__ . '/../models/SalesModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class SalesController extends Controller
{
    private SalesModel $salesModel;
    private ProductModel $productModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;
    private InventoryModel $inventoryModel;

    public function __construct()
    {
        $this->salesModel     = new SalesModel();
        $this->productModel   = new ProductModel();
        $this->kioskModel     = new KioskModel();
        $this->auditLog       = new AuditLogModel();
        $this->inventoryModel = new InventoryModel();
    }

    /** Show sales page (POS-style) */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        $kiosk_id = $this->resolveKiosk();
        $kiosk     = $this->kioskModel->findById($kiosk_id);
        $date      = $this->get('date', date('Y-m-d'));

        $sales     = $this->salesModel->getByDateAndKiosk($date, $kiosk_id);
        $day_total = $this->salesModel->getDailyTotal($date, $kiosk_id);
        $is_today  = ($date === date('Y-m-d'));
        $any_locked = false;
        foreach ($sales as $s) {
            if ($s['Locked_status']) { $any_locked = true; break; }
        }

        // Build availability map: Product_ID => ['available' => bool, 'parts' => [...]]
        // Computed locally from ONE running-parts query + ONE products+recipe query
        // (avoids the N+1 cost of calling checkProductAvailability() per product).
        $availability_map = [];
        $product_map      = $this->productModel->getActiveAsMap();
        if ($is_today) {
            $running = $this->inventoryModel->getRunningPartsInventory($date, $kiosk_id);
            $stock_by_part = [];
            foreach ($running as $r) {
                $stock_by_part[(int) $r['Part_ID']] = (int) $r['Running_Qty'];
            }
            foreach ($product_map as $pid => $p) {
                $recipe = $p['Recipe'] ?? [];
                if (empty($recipe)) {
                    // No recipe defined → treat as unlimited (don't block sales)
                    $availability_map[$pid] = ['available' => true, 'parts' => []];
                    continue;
                }
                $ok_overall = true;
                $parts_info = [];
                foreach ($recipe as $r) {
                    $part_id = (int) $r['Part_ID'];
                    $needed  = (int) $r['Quantity_needed'];
                    $have    = $stock_by_part[$part_id] ?? 0;
                    $ok      = $have >= $needed;
                    if (!$ok) $ok_overall = false;
                    $parts_info[] = [
                        'name'      => $r['Part_Name'],
                        'needed'    => $needed,
                        'available' => $have,
                        'ok'        => $ok,
                    ];
                }
                $availability_map[$pid] = ['available' => $ok_overall, 'parts' => $parts_info];
            }
        }

        $data = [
            'page_title'      => 'Point of Sales',
            'kiosk'           => $kiosk,
            'kiosk_id'        => $kiosk_id,
            'date'            => $date,
            'sales'           => $sales,
            'day_total'       => $day_total,
            'is_today'        => $is_today,
            'any_locked'      => $any_locked,
            'products'        => $this->productModel->getActiveGrouped(),
            'product_map'     => $product_map,
            'availability_map'=> $availability_map,
            'stock_map'       => [],   // legacy compat — no longer populated
            'kiosks'          => Auth::isOwner() ? $this->kioskModel->getActive() : [],
            'history'         => $this->salesModel->getRecordedDates($kiosk_id),
            'success'         => $_GET['success'] ?? null,
            'error'           => $_GET['error'] ?? null,
        ];

        $this->render('sales/index', $data);
    }

    /** Handle adding a sale — price is fetched from the Product table, never from staff input */
    public function store(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/sales?error=Invalid+request');
            return;
        }

        $kiosk_id  = $this->resolveKiosk();
        $product_id = (int) $this->post('product_id');
        $qty_sold   = (int) $this->post('quantity_sold');
        $date       = $this->post('date', date('Y-m-d'));

        if ($this->isFutureDate($date)) {
            $this->redirect('/sales?error=Cannot+record+sales+for+future+dates');
            return;
        }

        if ($product_id <= 0 || $qty_sold <= 0) {
            $this->redirect("/sales?date={$date}&error=Product+and+quantity+are+required");
            return;
        }

        // Look up the unit price from the Product table — staff never types the price
        $product = $this->productModel->findById($product_id);
        if (!$product || !$product['Active']) {
            $this->redirect("/sales?date={$date}&error=Invalid+or+inactive+product");
            return;
        }
        $unit_price = (float) $product['Price'];

        $sales_id = $this->salesModel->create($kiosk_id, Auth::userId(), $product_id, $date, $qty_sold, $unit_price);
        $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Sales ID:{$sales_id} — product:{$product_id}, qty:{$qty_sold}, price:{$unit_price}");

        $this->redirect("/sales?date={$date}&kiosk_id={$kiosk_id}&success=Sale+recorded");
    }

    /** Handle batch sale submission from the POS cart */
    public function storeBatch(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/sales?error=Invalid+request');
            return;
        }

        $kiosk_id = $this->resolveKiosk();
        $date     = $this->post('date', date('Y-m-d'));

        if ($this->isFutureDate($date)) {
            $this->redirect('/sales?error=Cannot+record+sales+for+future+dates');
            return;
        }

        // Items come as parallel arrays: product_ids[] + quantities[]
        $product_ids = $this->post('product_ids', []);
        $quantities  = $this->post('quantities', []);

        if (!is_array($product_ids) || !is_array($quantities)
            || empty($product_ids) || count($product_ids) !== count($quantities)) {
            $this->redirect("/sales?date={$date}&error=No+items+in+order");
            return;
        }

        // Build items — look up unit price from Product table; never trust the form
        $items     = [];
        $total_qty = 0;
        foreach ($product_ids as $idx => $pid) {
            $product_id = (int) $pid;
            $qty        = max(1, (int) $quantities[$idx]);
            $product    = $this->productModel->findById($product_id);

            if (!$product || !$product['Active']) continue;

            $items[] = [
                'product_id'    => $product_id,
                'quantity_sold' => $qty,
                'unit_price'    => (float) $product['Price'],
            ];
            $total_qty += $qty;
        }

        if (empty($items)) {
            $this->redirect("/sales?date={$date}&error=No+valid+products+in+order");
            return;
        }

        try {
            $count = $this->salesModel->createBatch($kiosk_id, Auth::userId(), $date, $items);

            // Calculate which PARTS were consumed by this batch (for audit log only —
            // no separate INSERT needed; the running inventory derives consumption
            // live from Sales × Product_Part).
            $parts_consumed = [];
            foreach ($items as $item) {
                foreach ($this->productModel->getRecipe($item['product_id']) as $r) {
                    $part_id = (int) $r['Part_ID'];
                    $parts_consumed[$part_id] =
                        ($parts_consumed[$part_id] ?? 0)
                        + (int) $r['Quantity_needed'] * (int) $item['quantity_sold'];
                }
            }
            $consumed_summary = '';
            if (!empty($parts_consumed)) {
                $bits = [];
                foreach ($parts_consumed as $pid => $qty) {
                    $bits[] = "Part#{$pid}:x{$qty}";
                }
                $consumed_summary = ' | Parts consumed: ' . implode(', ', $bits);
            }

            $this->auditLog->log(
                Auth::userId(),
                ACTION_CREATE,
                "Batch sale: {$count} product(s), {$total_qty} total units for kiosk {$kiosk_id} on {$date}{$consumed_summary}"
            );

            $this->redirect("/sales?date={$date}&kiosk_id={$kiosk_id}&success=Order+confirmed+({$count}+items+saved)");
        } catch (\Exception $e) {
            $this->redirect("/sales?date={$date}&error=Failed+to+save+order:+" . urlencode($e->getMessage()));
        }
    }

    /** Lock all sales for a date */
    public function lock(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/sales?error=Invalid+request');
            return;
        }

        $kiosk_id = $this->resolveKiosk();
        $date      = $this->post('date', date('Y-m-d'));

        $count = $this->salesModel->lockByDate($kiosk_id, $date);
        $this->auditLog->log(Auth::userId(), ACTION_LOCK, "Locked {$count} sales for kiosk:{$kiosk_id} on {$date}");

        $this->redirect("/sales?date={$date}&success=Sales+locked+({$count}+records)");
    }

    /** Unlock a sales record (owner only) */
    public function unlock(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/sales?error=Invalid+request');
            return;
        }

        $sales_id = (int) $this->post('sales_id');
        $date     = $this->post('date', date('Y-m-d'));

        $this->salesModel->unlock($sales_id);
        $this->auditLog->log(Auth::userId(), ACTION_UNLOCK, "Unlocked Sales ID:{$sales_id}");

        $this->redirect("/sales?date={$date}&success=Record+unlocked");
    }

    /** Delete a sale (only if unlocked) */
    public function delete(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/sales?error=Invalid+request');
            return;
        }

        $sales_id = (int) $this->post('sales_id');
        $date     = $this->post('date', date('Y-m-d'));

        $deleted = $this->salesModel->delete($sales_id);
        if ($deleted) {
            $this->auditLog->log(Auth::userId(), ACTION_DELETE, "Deleted Sales ID:{$sales_id}");
            $this->redirect("/sales?date={$date}&success=Sale+deleted");
        } else {
            $this->redirect("/sales?date={$date}&error=Cannot+delete+a+locked+record");
        }
    }

    /** Determine which kiosk to use */
    private function resolveKiosk(): int
    {
        if (Auth::isStaff()) {
            return Auth::kioskId();
        }
        $selected = $this->get('kiosk_id', $this->post('kiosk_id'));
        if ($selected) {
            return (int) $selected;
        }
        $kiosks = $this->kioskModel->getActive();
        return $kiosks[0]['Kiosk_ID'] ?? 1;
    }
}
