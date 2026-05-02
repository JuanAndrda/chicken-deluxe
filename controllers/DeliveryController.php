<?php
/**
 * DeliveryController — log incoming deliveries per kiosk
 */
require_once __DIR__ . '/../models/DeliveryModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../models/PartModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';

class DeliveryController extends Controller
{
    private DeliveryModel $deliveryModel;
    private ProductModel $productModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;
    private PartModel $partModel;
    private InventoryModel $inventoryModel;

    public function __construct()
    {
        $this->deliveryModel  = new DeliveryModel();
        $this->productModel   = new ProductModel();
        $this->kioskModel     = new KioskModel();
        $this->auditLog       = new AuditLogModel();
        $this->partModel      = new PartModel();
        $this->inventoryModel = new InventoryModel();
    }

    /** Show delivery page */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        $kiosk_id = $this->resolveKiosk();
        $kiosk     = $this->kioskModel->findById($kiosk_id);
        $date      = $this->get('date', date('Y-m-d'));

        $is_today = ($date === date('Y-m-d'));

        // Build a unified delivery list: new part-based rows + historical product rows.
        // Each row carries Source ('Part'|'Product') and Display_Name so the view loops once.
        $part_deliveries    = $this->deliveryModel->getPartsByDateAndKiosk($date, $kiosk_id);
        $product_deliveries = $this->deliveryModel->getByDateAndKiosk($date, $kiosk_id);

        $deliveries = [];
        foreach ($part_deliveries as $r) {
            $r['Source']        = 'Part';
            $r['Display_Name']  = $r['Part_Name'];
            $r['Display_Cat']   = 'Parts';
            $deliveries[] = $r;
        }
        foreach ($product_deliveries as $r) {
            $r['Source']        = 'Product';
            $r['Display_Name']  = $r['Product_Name'];
            $r['Display_Cat']   = $r['Category_Name'] ?? '—';
            $deliveries[] = $r;
        }
        // Newest first (Created_at DESC) — both source queries already sort that way; merge keeps it close enough
        usort($deliveries, fn($a, $b) => strcmp($b['Created_at'] ?? '', $a['Created_at'] ?? ''));

        $any_locked = false;
        foreach ($deliveries as $d) {
            if ($d['Locked_status']) { $any_locked = true; break; }
        }

        // Build a Part_ID → Running_Qty map so the Select Part table can show current stock
        $running_parts = $this->inventoryModel->getRunningPartsInventory($date, $kiosk_id);
        $part_stock = [];
        foreach ($running_parts as $r) {
            $part_stock[(int) $r['Part_ID']] = (int) $r['Running_Qty'];
        }

        $data = [
            'page_title'  => 'Deliveries',
            'kiosk'       => $kiosk,
            'kiosk_id'    => $kiosk_id,
            'date'        => $date,
            'deliveries'  => $deliveries,
            'is_today'    => $is_today,
            'any_locked'  => $any_locked,
            'parts'       => $this->partModel->getActive(),
            'part_stock'  => $part_stock,
            'products'    => $this->productModel->getActiveGrouped(),
            'product_map' => $this->productModel->getActiveAsMap(),
            'kiosks'      => Auth::isOwner() ? $this->kioskModel->getActive() : [],
            'history'     => $this->deliveryModel->getRecordedDates($kiosk_id),
            'success'     => $_GET['success'] ?? null,
            'error'       => $_GET['error'] ?? null,
        ];

        $this->render('delivery/index', $data);
    }

    /** Handle adding a delivery */
    public function store(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/delivery?error=Invalid+request');
            return;
        }

        $kiosk_id  = $this->resolveKiosk();
        // Accept part_id (new parts-based path); fall back to product_id for safety
        $part_id    = (int) $this->post('part_id');
        $product_id = (int) $this->post('product_id');
        $quantity   = (int) $this->post('quantity');
        $date       = $this->post('date', date('Y-m-d'));

        if ($this->isFutureDate($date)) {
            $this->redirect('/delivery?error=Cannot+record+deliveries+for+future+dates');
            return;
        }

        if (($part_id <= 0 && $product_id <= 0) || $quantity <= 0) {
            $this->redirect("/delivery?date={$date}&error=Item+and+quantity+are+required");
            return;
        }

        if ($part_id > 0) {
            $delivery_id = $this->deliveryModel->createPartDelivery($kiosk_id, Auth::userId(), $part_id, $date, $quantity);
            $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Delivery ID:{$delivery_id} — part:{$part_id}, qty:{$quantity}");
        } else {
            // Legacy product-based path — kept for safety / backwards compat
            $delivery_id = $this->deliveryModel->create($kiosk_id, Auth::userId(), $product_id, $date, $quantity);
            $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Delivery ID:{$delivery_id} — product:{$product_id}, qty:{$quantity}");
        }

        $this->redirect("/delivery?date={$date}&success=Delivery+recorded");
    }

    /** Lock all deliveries for a date */
    public function lock(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/delivery?error=Invalid+request');
            return;
        }

        $kiosk_id = $this->resolveKiosk();
        $date      = $this->post('date', date('Y-m-d'));

        $count = $this->deliveryModel->lockByDate($kiosk_id, $date);
        $this->auditLog->log(Auth::userId(), ACTION_LOCK, "Locked {$count} deliveries for kiosk:{$kiosk_id} on {$date}");

        $this->redirect("/delivery?date={$date}&success=Deliveries+locked+({$count}+records)");
    }

    /** Unlock a delivery record (owner only) */
    public function unlock(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/delivery?error=Invalid+request');
            return;
        }

        $delivery_id = (int) $this->post('delivery_id');
        $date        = $this->post('date', date('Y-m-d'));

        $this->deliveryModel->unlock($delivery_id);
        $this->auditLog->log(Auth::userId(), ACTION_UNLOCK, "Unlocked Delivery ID:{$delivery_id}");

        $this->redirect("/delivery?date={$date}&success=Record+unlocked");
    }

    /** Delete a delivery (only if unlocked) */
    public function delete(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/delivery?error=Invalid+request');
            return;
        }

        $delivery_id = (int) $this->post('delivery_id');
        $date        = $this->post('date', date('Y-m-d'));

        $deleted = $this->deliveryModel->delete($delivery_id);
        if ($deleted) {
            $this->auditLog->log(Auth::userId(), ACTION_DELETE, "Deleted Delivery ID:{$delivery_id}");
            $this->redirect("/delivery?date={$date}&success=Delivery+deleted");
        } else {
            $this->redirect("/delivery?date={$date}&error=Cannot+delete+a+locked+record");
        }
    }

    /** Update delivery quantity (owner only, unlocked records) */
    public function update(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/delivery?error=Invalid+request');
            return;
        }

        $delivery_id = (int) $this->post('delivery_id');
        $quantity    = (int) $this->post('quantity');
        $date        = $this->post('date', date('Y-m-d'));

        if ($this->isFutureDate($date)) {
            $this->redirect('/delivery?error=Cannot+edit+deliveries+for+future+dates');
            return;
        }

        if ($quantity <= 0) {
            $this->redirect("/delivery?date={$date}&error=Quantity+must+be+greater+than+zero");
            return;
        }

        $updated = $this->deliveryModel->updateQuantity($delivery_id, $quantity);
        if ($updated) {
            $this->auditLog->log(
                Auth::userId(),
                ACTION_UPDATE,
                "Updated Delivery ID:{$delivery_id} quantity to {$quantity}"
            );
            $this->redirect("/delivery?date={$date}&success=Delivery+updated");
        } else {
            $this->redirect("/delivery?date={$date}&error=Cannot+update+a+locked+record");
        }
    }

    /** Record a pullout (stock removed) */
    public function pullout(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/delivery?error=Invalid+request');
            return;
        }

        $kiosk_id   = $this->resolveKiosk();
        // Prefer part_id (new parts-based path); fall back to product_id for legacy support
        $part_id    = (int) $this->post('part_id');
        $product_id = (int) $this->post('product_id');
        $quantity   = (int) $this->post('quantity');
        $notes      = trim($this->post('notes', ''));
        $date       = $this->post('date', date('Y-m-d'));

        if ($this->isFutureDate($date)) {
            $this->redirect('/delivery?error=Cannot+record+pullouts+for+future+dates');
            return;
        }

        if (($part_id <= 0 && $product_id <= 0) || $quantity <= 0) {
            $this->redirect("/delivery?date={$date}&error=Item+and+quantity+are+required");
            return;
        }

        if ($part_id > 0) {
            $delivery_id = $this->deliveryModel->createPartPullout($kiosk_id, Auth::userId(), $part_id, $date, $quantity, $notes);
            $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Pullout ID:{$delivery_id} — part:{$part_id}, qty:{$quantity}");
        } else {
            // Legacy product-based pullout path — preserved
            $delivery_id = $this->deliveryModel->createPullout($kiosk_id, Auth::userId(), $product_id, $date, $quantity, $notes);
            $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Pullout ID:{$delivery_id} — product:{$product_id}, qty:{$quantity}");
        }

        $this->redirect("/delivery?date={$date}&kiosk_id={$kiosk_id}&success=Pullout+recorded");
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
