<?php
/**
 * InventoryController — daily stock recording (beginning & ending)
 */
require_once __DIR__ . '/../models/InventoryModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class InventoryController extends Controller
{
    private InventoryModel $inventoryModel;
    private ProductModel $productModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->productModel   = new ProductModel();
        $this->kioskModel     = new KioskModel();
        $this->auditLog       = new AuditLogModel();
    }

    /** Show the main inventory page for today */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        $kiosk_id = $this->resolveKiosk();
        $kiosk     = $this->kioskModel->findById($kiosk_id);
        $date      = $this->get('date', date('Y-m-d'));

        $beginning = $this->inventoryModel->getByDateKioskType($date, $kiosk_id, 'beginning');
        $ending    = $this->inventoryModel->getByDateKioskType($date, $kiosk_id, 'ending');

        $has_beginning = !empty($beginning);
        $has_ending    = !empty($ending);
        $is_today      = ($date === date('Y-m-d'));

        $data = [
            'page_title'    => 'Inventory',
            'kiosk'         => $kiosk,
            'kiosk_id'     => $kiosk_id,
            'date'          => $date,
            'beginning'     => $beginning,
            'ending'        => $ending,
            'has_beginning' => $has_beginning,
            'has_ending'    => $has_ending,
            'is_today'      => $is_today,
            'products'      => $this->productModel->getActiveGrouped(),
            'kiosks'        => Auth::isOwner() ? $this->kioskModel->getActive() : [],
            'history'       => $this->inventoryModel->getRecordedDates($kiosk_id),
            'success'       => $_GET['success'] ?? null,
            'error'         => $_GET['error'] ?? null,
        ];

        $this->render('inventory/index', $data);
    }

    /** Handle batch submission of beginning or ending stock */
    public function store(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/inventory?error=Invalid+request');
            return;
        }

        $kiosk_id = $this->resolveKiosk();
        $type      = $this->post('snapshot_type');
        $date      = $this->post('date', date('Y-m-d'));

        if (!in_array($type, ['beginning', 'ending'], true)) {
            $this->redirect('/inventory?error=Invalid+snapshot+type');
            return;
        }

        // Prevent duplicate submissions
        if ($type === 'beginning' && $this->inventoryModel->hasBeginningToday($kiosk_id) && $date === date('Y-m-d')) {
            $this->redirect('/inventory?error=Beginning+stock+already+recorded+today');
            return;
        }
        if ($type === 'ending' && $this->inventoryModel->hasEndingToday($kiosk_id) && $date === date('Y-m-d')) {
            $this->redirect('/inventory?error=Ending+stock+already+recorded+today');
            return;
        }

        // Collect quantities from POST (format: qty[product_id] = value)
        $quantities = $this->post('qty', []);
        if (empty($quantities)) {
            $this->redirect('/inventory?error=No+quantities+provided');
            return;
        }

        // Filter out empty values, default to 0
        $clean_quantities = [];
        foreach ($quantities as $product_id => $qty) {
            $clean_quantities[(int) $product_id] = max(0, (int) $qty);
        }

        try {
            $count = $this->inventoryModel->createBatchSnapshots(
                $kiosk_id, Auth::userId(), $type, $clean_quantities, $date
            );

            $label = ucfirst($type);
            $this->auditLog->log(
                Auth::userId(), ACTION_CREATE,
                "{$label} stock recorded: {$count} products for kiosk {$kiosk_id} on {$date}"
            );

            $this->redirect("/inventory?date={$date}&success={$label}+stock+recorded+successfully+({$count}+products)");
        } catch (\Exception $e) {
            $this->redirect('/inventory?error=Failed+to+save:+' . urlencode($e->getMessage()));
        }
    }

    /** Unlock a record (owner only) */
    public function unlock(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/inventory?error=Invalid+request');
            return;
        }

        $inventory_id = (int) $this->post('inventory_id');
        $date         = $this->post('date', date('Y-m-d'));

        $this->inventoryModel->unlock($inventory_id, Auth::userId());
        $this->auditLog->log(Auth::userId(), ACTION_UNLOCK, "Unlocked Inventory_Snapshot ID:{$inventory_id}");

        $this->redirect("/inventory?date={$date}&success=Record+unlocked");
    }

    /** Determine which kiosk to use (staff=assigned, owner=selected or first) */
    private function resolveKiosk(): int
    {
        if (Auth::isStaff()) {
            return Auth::kioskId();
        }

        // Owner can select a kiosk
        $selected = $this->get('kiosk_id', $this->post('kiosk_id'));
        if ($selected) {
            return (int) $selected;
        }

        // Default to first active kiosk
        $kiosks = $this->kioskModel->getActive();
        return $kiosks[0]['Kiosk_ID'] ?? 1;
    }
}
