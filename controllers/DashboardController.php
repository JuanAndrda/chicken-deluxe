<?php
/**
 * DashboardController — main landing page after login.
 * Role-aware: Owner gets a multi-kiosk overview, Staff sees their own kiosk's
 * progress for the day, Auditor sees a minimal landing card.
 */
require_once __DIR__ . '/../models/SalesModel.php';
require_once __DIR__ . '/../models/InventoryModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/ExpenseModel.php';

class DashboardController extends Controller
{
    private SalesModel $salesModel;
    private InventoryModel $inventoryModel;
    private KioskModel $kioskModel;
    private ExpenseModel $expenseModel;

    public function __construct()
    {
        $this->salesModel     = new SalesModel();
        $this->inventoryModel = new InventoryModel();
        $this->kioskModel     = new KioskModel();
        $this->expenseModel   = new ExpenseModel();
    }

    /** Show the dashboard */
    public function index(): void
    {
        Auth::requireLogin();

        $today          = date('Y-m-d');
        $today_pretty   = date('l, F j, Y');

        $data = [
            'page_title'   => 'Dashboard',
            'today'        => $today,
            'today_pretty' => $today_pretty,
        ];

        if (Auth::isOwner()) {
            $data = array_merge($data, $this->buildOwnerData($today));
        } elseif (Auth::isStaff()) {
            $data = array_merge($data, $this->buildStaffData($today));
        } elseif (Auth::isAuditor()) {
            $data = array_merge($data, $this->buildAuditorData());
        }

        $this->render('dashboard/index', $data);
    }

    /** Owner: aggregate sales + per-kiosk inventory status */
    private function buildOwnerData(string $today): array
    {
        $today_sales_by_kiosk    = $this->salesModel->getDailyTotalByKiosk($today);
        $kiosk_inventory_status  = $this->inventoryModel->getKioskInventoryStatus($today);
        $total_kiosks            = count($kiosk_inventory_status);

        $missing_beginning = 0;
        $missing_ending    = 0;
        $has_beginning_ct  = 0;
        $has_ending_ct     = 0;
        foreach ($kiosk_inventory_status as $row) {
            if ((int) $row['has_beginning'] === 0) {
                $missing_beginning++;
            } else {
                $has_beginning_ct++;
            }
            if ((int) $row['has_ending'] === 0) {
                $missing_ending++;
            } else {
                $has_ending_ct++;
            }
        }

        return [
            'today_sales_by_kiosk'     => $today_sales_by_kiosk,
            'grand_total_today'        => array_sum(array_column($today_sales_by_kiosk, 'Day_Total')),
            'total_expenses_today'     => $this->expenseModel->getDailyTotalAllKiosks($today),
            'kiosk_inventory_status'   => $kiosk_inventory_status,
            'kiosks_missing_beginning' => $missing_beginning,
            'kiosks_missing_ending'    => $missing_ending,
            'kiosks_have_beginning'    => $has_beginning_ct,
            'kiosks_have_ending'       => $has_ending_ct,
            'total_kiosks'             => $total_kiosks,
        ];
    }

    /** Staff: own-kiosk progress + carry-forward availability */
    private function buildStaffData(string $today): array
    {
        $kiosk_id = Auth::kioskId();
        $kiosk    = $kiosk_id ? $this->kioskModel->findById($kiosk_id) : null;

        $today_sales_total          = $kiosk_id ? $this->salesModel->getDailyTotal($today, $kiosk_id) : 0.0;
        $has_beginning_today        = $kiosk_id ? $this->inventoryModel->hasBeginningToday($kiosk_id) : false;
        $has_ending_today           = $kiosk_id ? $this->inventoryModel->hasEndingToday($kiosk_id) : false;
        $previous_ending_available  = $kiosk_id
            ? !empty($this->inventoryModel->getPreviousDayEnding($kiosk_id, $today))
            : false;

        return [
            'kiosk'                     => $kiosk,
            'kiosk_name'                => $kiosk['Name'] ?? null,
            'today_sales_total'         => $today_sales_total,
            'today_expenses_total'      => $kiosk_id ? $this->expenseModel->getDailyTotal($today, $kiosk_id) : 0.0,
            'has_beginning_today'       => $has_beginning_today,
            'has_ending_today'          => $has_ending_today,
            'previous_ending_available' => $previous_ending_available,
        ];
    }

    /** Auditor: minimal data — kiosk count for context */
    private function buildAuditorData(): array
    {
        return [
            'total_kiosks' => count($this->kioskModel->getActive()),
        ];
    }
}
