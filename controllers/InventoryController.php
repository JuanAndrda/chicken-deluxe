<?php
/**
 * InventoryController — daily stock recording (beginning & ending)
 */
require_once __DIR__ . '/../models/InventoryModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../models/PartModel.php';

class InventoryController extends Controller
{
    private InventoryModel $inventoryModel;
    private ProductModel $productModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;
    private PartModel $partModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->productModel   = new ProductModel();
        $this->kioskModel     = new KioskModel();
        $this->auditLog       = new AuditLogModel();
        $this->partModel      = new PartModel();
    }

    /** Show the main inventory page (parts-based) for the chosen date */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        $kiosk_id = $this->resolveKiosk();
        $kiosk    = $this->kioskModel->findById($kiosk_id);
        $date     = $this->get('date', date('Y-m-d'));
        $is_today = ($date === date('Y-m-d'));

        // Parts-based snapshots
        $beginning = $this->inventoryModel->getPartsByDateKioskType($date, $kiosk_id, 'beginning');
        $ending    = $this->inventoryModel->getPartsByDateKioskType($date, $kiosk_id, 'ending');

        $has_beginning = !empty($beginning);
        $has_ending    = !empty($ending);

        // Carry-forward: most recent locked PART ending before $date
        $previous_ending     = $this->inventoryModel->getPreviousDayEndingParts($kiosk_id, $date);
        $has_previous_ending = !empty($previous_ending);
        $previous_date       = $has_previous_ending ? $previous_ending[0]['Snapshot_date'] : null;

        // Running PART inventory (beginning + delivered − used by sales)
        $running_inventory = $has_beginning
            ? $this->inventoryModel->getRunningPartsInventory($date, $kiosk_id)
            : [];

        // Active parts list for the entry form (flat — no categories)
        $parts = $this->partModel->getActive();

        $data = [
            'page_title'          => 'Inventory',
            'kiosk'               => $kiosk,
            'kiosk_id'            => $kiosk_id,
            'date'                => $date,
            'beginning'           => $beginning,
            'ending'              => $ending,
            'has_beginning'       => $has_beginning,
            'has_ending'          => $has_ending,
            'is_today'            => $is_today,
            'parts'               => $parts,
            'kiosks'              => Auth::isOwner() ? $this->kioskModel->getActive() : [],
            'history'             => $this->inventoryModel->getRecordedDates($kiosk_id),
            'previous_ending'     => $previous_ending,
            'has_previous_ending' => $has_previous_ending,
            'previous_date'       => $previous_date,
            'running_inventory'   => $running_inventory,
            'success'             => $_GET['success'] ?? null,
            'error'               => $_GET['error'] ?? null,
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

        if ($this->isFutureDate($date)) {
            $this->redirect('/inventory?error=Cannot+record+inventory+for+future+dates');
            return;
        }

        if (!in_array($type, ['beginning', 'ending'], true)) {
            $this->redirect('/inventory?error=Invalid+snapshot+type');
            return;
        }

        // Prevent duplicate submissions (parts-based checks)
        if ($type === 'beginning' && $this->inventoryModel->hasPartsBeginningToday($kiosk_id) && $date === date('Y-m-d')) {
            $this->redirect('/inventory?error=Beginning+stock+already+recorded+today');
            return;
        }
        if ($type === 'ending' && $this->inventoryModel->hasPartsEndingToday($kiosk_id) && $date === date('Y-m-d')) {
            $this->redirect('/inventory?error=Ending+stock+already+recorded+today');
            return;
        }

        // Collect quantities from POST (format: qty[part_id] = value)
        $quantities = $this->post('qty', []);
        if (empty($quantities)) {
            $this->redirect('/inventory?error=No+quantities+provided');
            return;
        }

        // Sanitize: clamp to >= 0 and key by Part_ID (int)
        $clean_quantities = [];
        foreach ($quantities as $part_id => $qty) {
            $clean_quantities[(int) $part_id] = max(0, (int) $qty);
        }

        try {
            $count = $this->inventoryModel->createPartsSnapshots(
                $kiosk_id, Auth::userId(), $type, $clean_quantities, $date
            );

            $label = ucfirst($type);
            $this->auditLog->log(
                Auth::userId(), ACTION_CREATE,
                "{$label} stock recorded: {$count} parts for kiosk {$kiosk_id} on {$date}"
            );

            $this->redirect("/inventory?date={$date}&success={$label}+stock+recorded+successfully+({$count}+parts)");
        } catch (\Exception $e) {
            $this->redirect('/inventory?error=Failed+to+save:+' . urlencode($e->getMessage()));
        }
    }

    /**
     * Auto-generate today's beginning stock from yesterday's locked ending.
     * Falls back to zeros if no previous locked ending exists.
     */
    public function autoGenerateBeginning(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/inventory?error=Invalid+request');
            return;
        }

        $kiosk_id = $this->resolveKiosk();
        $date     = $this->post('date', date('Y-m-d'));

        if ($this->isFutureDate($date)) {
            $this->redirect('/inventory?error=Cannot+record+inventory+for+future+dates');
            return;
        }

        // Block if a parts beginning already exists for that day
        if ($this->inventoryModel->hasPartsBeginningToday($kiosk_id) && $date === date('Y-m-d')) {
            $this->redirect('/inventory?error=Beginning+stock+already+recorded+today');
            return;
        }

        try {
            // Look up previous PART ending up-front so we can include it in the audit detail
            $previous = $this->inventoryModel->getPreviousDayEndingParts($kiosk_id, $date);
            $prev_date = !empty($previous) ? $previous[0]['Snapshot_date'] : null;

            $count = $this->inventoryModel->autoGeneratePartsBeginning(
                $kiosk_id, Auth::userId(), $date
            );

            if ($count === 0) {
                // Either no parts to fill, or every target row already
                // existed (INSERT IGNORE silently skipped them). Either way,
                // there's nothing to do — show a calm informational message.
                $this->redirect("/inventory?date={$date}&success=Beginning+stock+is+already+complete+for+today");
                return;
            }

            if ($prev_date) {
                $detail = "Beginning stock auto-generated from {$prev_date} ending: "
                        . "{$count} parts for kiosk {$kiosk_id} on {$date}";
            } else {
                $detail = "Beginning stock auto-generated (no previous ending found, seeded zeros): "
                        . "{$count} parts for kiosk {$kiosk_id} on {$date}";
            }
            $this->auditLog->log(Auth::userId(), ACTION_CREATE, $detail);

            $msg = $prev_date
                ? "Beginning+stock+auto-generated+from+{$prev_date}+({$count}+parts)"
                : "Beginning+stock+initialized+with+zeros+({$count}+parts)";
            $this->redirect("/inventory?date={$date}&success={$msg}");
        } catch (\Exception $e) {
            $this->redirect('/inventory?error=Auto-generate+failed:+' . urlencode($e->getMessage()));
        }
    }

    /** Lock a single inventory snapshot record (re-lock after edit) — Owner only */
    public function lock(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/inventory?error=Invalid+request');
            return;
        }

        $inventory_id = (int) $this->post('inventory_id');
        $date         = $this->post('date', date('Y-m-d'));

        if ($inventory_id <= 0) {
            $this->redirect("/inventory?date={$date}&error=Missing+record+ID");
            return;
        }

        $rows = $this->inventoryModel->lockOne($inventory_id);
        $this->auditLog->log(Auth::userId(), ACTION_LOCK, "Locked Inventory_Snapshot ID:{$inventory_id}");
        $msg = $rows > 0 ? 'Record+locked' : 'Record+already+locked';
        $this->redirect("/inventory?date={$date}&success={$msg}");
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

    /** Update an unlocked snapshot's quantity (Owner only) */
    public function update(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/inventory?error=Invalid+request');
            return;
        }

        $inventory_id = (int) $this->post('inventory_id');
        $quantity     = (int) $this->post('quantity');
        $date         = $this->post('date', date('Y-m-d'));

        if ($inventory_id <= 0) {
            $this->redirect("/inventory?date={$date}&error=Missing+record+ID");
            return;
        }
        if ($quantity < 0) {
            $this->redirect("/inventory?date={$date}&error=Quantity+cannot+be+negative");
            return;
        }

        $rows = $this->inventoryModel->updateQuantity($inventory_id, $quantity);
        if ($rows > 0) {
            $this->auditLog->log(Auth::userId(), ACTION_UPDATE,
                "Updated Inventory_Snapshot ID:{$inventory_id} quantity to {$quantity}");
            $this->redirect("/inventory?date={$date}&success=Quantity+updated");
        } else {
            $this->redirect("/inventory?date={$date}&error=Cannot+update+a+locked+record");
        }
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
