<?php
/**
 * SalesController — record daily sales per product
 */
require_once __DIR__ . '/../models/SalesModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class SalesController extends Controller
{
    private SalesModel $salesModel;
    private ProductModel $productModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;

    public function __construct()
    {
        $this->salesModel   = new SalesModel();
        $this->productModel = new ProductModel();
        $this->kioskModel   = new KioskModel();
        $this->auditLog     = new AuditLogModel();
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

        $data = [
            'page_title'   => 'Sales',
            'kiosk'        => $kiosk,
            'kiosk_id'    => $kiosk_id,
            'date'         => $date,
            'sales'        => $sales,
            'day_total'    => $day_total,
            'is_today'     => $is_today,
            'any_locked'   => $any_locked,
            'products'     => $this->productModel->getActiveGrouped(),
            'product_map'  => $this->productModel->getActiveAsMap(),
            'kiosks'       => Auth::isOwner() ? $this->kioskModel->getActive() : [],
            'history'      => $this->salesModel->getRecordedDates($kiosk_id),
            'success'      => $_GET['success'] ?? null,
            'error'        => $_GET['error'] ?? null,
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
