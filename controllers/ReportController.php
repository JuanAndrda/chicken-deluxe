<?php
/**
 * ReportController — reporting and monitoring dashboards
 * Owner + Auditor access
 */
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/TimeInModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class ReportController extends Controller
{
    private ReportModel $reportModel;
    private KioskModel $kioskModel;
    private TimeInModel $timeInModel;
    private AuditLogModel $auditLog;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->kioskModel  = new KioskModel();
        $this->timeInModel = new TimeInModel();
        $this->auditLog    = new AuditLogModel();
    }

    /** Reports landing — show daily summary */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_AUDITOR]);
        $this->daily();
    }

    /** Daily report — per-kiosk product breakdown */
    public function daily(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_AUDITOR]);

        $kiosks    = $this->kioskModel->getActive();
        $kiosk_id = (int) ($this->get('kiosk_id') ?? ($kiosks[0]['Kiosk_ID'] ?? 1));
        $date      = $this->get('date', date('Y-m-d'));
        $kiosk     = $this->kioskModel->findById($kiosk_id);

        $report = $this->reportModel->getDailySummary($date, $kiosk_id);

        // Total sales come from a separate query (per-part rows have no peso total).
        $total_sales = $this->reportModel->getDailyTotalSales($kiosk_id, $date);
        $has_discrepancy = false;
        foreach ($report as $row) {
            if ($row['Discrepancy'] !== 0) {
                $has_discrepancy = true;
                break;
            }
        }

        $data = [
            'page_title'      => 'Daily Report',
            'active_tab'      => 'daily',
            'kiosks'          => $kiosks,
            'kiosk'           => $kiosk,
            'kiosk_id'       => $kiosk_id,
            'date'            => $date,
            'report'          => $report,
            'total_sales'     => $total_sales,
            'has_discrepancy' => $has_discrepancy,
        ];

        $this->render('reports/daily', $data);
    }

    /** Consolidated report — all kiosks for a date range */
    public function consolidated(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_AUDITOR]);

        $kiosks    = $this->kioskModel->getActive();
        $kiosk_id = $this->get('kiosk_id') ? (int) $this->get('kiosk_id') : null;

        // Default to current week
        $from_date = $this->get('from_date', date('Y-m-d', strtotime('monday this week')));
        $to_date   = $this->get('to_date', date('Y-m-d'));

        $sales_by_kiosk   = $this->reportModel->getSalesTotalsByKiosk($from_date, $to_date);
        $expense_by_kiosk = $this->reportModel->getExpenseTotalsByKiosk($from_date, $to_date);
        $daily_sales      = $this->reportModel->getDailySalesBreakdown($from_date, $to_date, $kiosk_id);
        $daily_expenses   = $this->reportModel->getDailyExpenseBreakdown($from_date, $to_date, $kiosk_id);
        $deliveries       = $this->reportModel->getDeliverySummary($from_date, $to_date, $kiosk_id);
        $anomalies        = $this->reportModel->getMissingSnapshots($from_date, $to_date);

        // Subquery-driven analytics:
        //   getProductsAboveAverageSales — derived-table HAVING subquery
        //   getKiosksWithoutEndingSnapshot — NOT IN subquery (for the report's end date)
        $top_products     = $this->reportModel->getProductsAboveAverageSales($from_date, $to_date);
        $kiosks_no_ending = $this->reportModel->getKiosksWithoutEndingSnapshot($to_date);

        // Grand totals
        $grand_sales = 0;
        $grand_expenses = 0;
        foreach ($sales_by_kiosk as $s) { $grand_sales += $s['Total_Sales']; }
        foreach ($expense_by_kiosk as $e) { $grand_expenses += $e['Total_Expenses']; }

        $data = [
            'page_title'       => 'Consolidated Report',
            'active_tab'       => 'consolidated',
            'kiosks'           => $kiosks,
            'kiosk_id'         => $kiosk_id,
            'from_date'        => $from_date,
            'to_date'          => $to_date,
            'sales_by_kiosk'   => $sales_by_kiosk,
            'expense_by_kiosk' => $expense_by_kiosk,
            'daily_sales'      => $daily_sales,
            'daily_expenses'   => $daily_expenses,
            'deliveries'       => $deliveries,
            'anomalies'        => $anomalies,
            'top_products'     => $top_products,
            'kiosks_no_ending' => $kiosks_no_ending,
            'grand_sales'      => $grand_sales,
            'grand_expenses'   => $grand_expenses,
            'net_income'       => $grand_sales - $grand_expenses,
        ];

        $this->render('reports/consolidated', $data);
    }

    /** Time-in report — staff attendance */
    public function timeIn(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_AUDITOR]);

        $kiosks    = $this->kioskModel->getActive();
        $kiosk_id = $this->get('kiosk_id') ? (int) $this->get('kiosk_id') : null;
        $from_date = $this->get('from_date', date('Y-m-d'));
        $to_date   = $this->get('to_date', date('Y-m-d'));

        // Use the full model method so the Time_out column comes through
        $records = $this->timeInModel->getFullByDateRange($from_date, $to_date, $kiosk_id);

        $data = [
            'page_title'  => 'Staff Time-In',
            'active_tab'  => 'timein',
            'kiosks'      => $kiosks,
            'kiosk_id'   => $kiosk_id,
            'from_date'   => $from_date,
            'to_date'     => $to_date,
            'records'     => $records,
        ];

        $this->render('reports/timein', $data);
    }
}
