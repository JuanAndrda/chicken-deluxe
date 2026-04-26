<?php
/**
 * DeliveryController — log incoming deliveries per kiosk
 */
require_once __DIR__ . '/../models/DeliveryModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class DeliveryController extends Controller
{
    private DeliveryModel $deliveryModel;
    private ProductModel $productModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;

    public function __construct()
    {
        $this->deliveryModel = new DeliveryModel();
        $this->productModel  = new ProductModel();
        $this->kioskModel    = new KioskModel();
        $this->auditLog      = new AuditLogModel();
    }

    /** Show delivery page */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        $kiosk_id = $this->resolveKiosk();
        $kiosk     = $this->kioskModel->findById($kiosk_id);
        $date      = $this->get('date', date('Y-m-d'));

        $deliveries = $this->deliveryModel->getByDateAndKiosk($date, $kiosk_id);
        $is_today   = ($date === date('Y-m-d'));
        $any_locked = false;
        foreach ($deliveries as $d) {
            if ($d['Locked_status']) { $any_locked = true; break; }
        }

        $data = [
            'page_title'  => 'Deliveries',
            'kiosk'       => $kiosk,
            'kiosk_id'   => $kiosk_id,
            'date'        => $date,
            'deliveries'  => $deliveries,
            'is_today'    => $is_today,
            'any_locked'  => $any_locked,
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
        $product_id = (int) $this->post('product_id');
        $quantity   = (int) $this->post('quantity');
        $date       = $this->post('date', date('Y-m-d'));

        if ($product_id <= 0 || $quantity <= 0) {
            $this->redirect("/delivery?date={$date}&error=Product+and+quantity+are+required");
            return;
        }

        $delivery_id = $this->deliveryModel->create($kiosk_id, Auth::userId(), $product_id, $date, $quantity);
        $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Delivery ID:{$delivery_id} — product:{$product_id}, qty:{$quantity}");

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
