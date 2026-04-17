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
        $outlet_id = (int) ($this->get('outlet_id') ?? ($kiosks[0]['Kiosk_ID'] ?? 1));
        $date      = $this->get('date', date('Y-m-d'));
        $kiosk     = $this->kioskModel->findById($outlet_id);

        $report = $this->reportModel->getDailySummary($date, $outlet_id);

        // Calculate totals
        $total_sales = 0;
        $has_discrepancy = false;
        foreach ($report as $row) {
            $total_sales += $row['Sales_Total'];
            if ($row['Discrepancy'] !== 0) {
                $has_discrepancy = true;
            }
        }

        $data = [
            'page_title'      => 'Daily Report',
            'active_tab'      => 'daily',
            'kiosks'          => $kiosks,
            'kiosk'           => $kiosk,
            'outlet_id'       => $outlet_id,
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
        $outlet_id = $this->get('outlet_id') ? (int) $this->get('outlet_id') : null;

        // Default to current week
        $from_date = $this->get('from_date', date('Y-m-d', strtotime('monday this week')));
        $to_date   = $this->get('to_date', date('Y-m-d'));

        $sales_by_kiosk   = $this->reportModel->getSalesTotalsByKiosk($from_date, $to_date);
        $expense_by_kiosk = $this->reportModel->getExpenseTotalsByKiosk($from_date, $to_date);
        $daily_sales      = $this->reportModel->getDailySalesBreakdown($from_date, $to_date, $outlet_id);
        $daily_expenses   = $this->reportModel->getDailyExpenseBreakdown($from_date, $to_date, $outlet_id);
        $deliveries       = $this->reportModel->getDeliverySummary($from_date, $to_date, $outlet_id);
        $anomalies        = $this->reportModel->getMissingSnapshots($from_date, $to_date);

        // Grand totals
        $grand_sales = 0;
        $grand_expenses = 0;
        foreach ($sales_by_kiosk as $s) { $grand_sales += $s['Total_Sales']; }
        foreach ($expense_by_kiosk as $e) { $grand_expenses += $e['Total_Expenses']; }

        $data = [
            'page_title'      => 'Consolidated Report',
            'active_tab'      => 'consolidated',
            'kiosks'          => $kiosks,
            'outlet_id'       => $outlet_id,
            'from_date'       => $from_date,
            'to_date'         => $to_date,
            'sales_by_kiosk'  => $sales_by_kiosk,
            'expense_by_kiosk'=> $expense_by_kiosk,
            'daily_sales'     => $daily_sales,
            'daily_expenses'  => $daily_expenses,
            'deliveries'      => $deliveries,
            'anomalies'       => $anomalies,
            'grand_sales'     => $grand_sales,
            'grand_expenses'  => $grand_expenses,
            'net_income'      => $grand_sales - $grand_expenses,
        ];

        $this->render('reports/consolidated', $data);
    }

    /** Time-in report — staff attendance */
    public function timeIn(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_AUDITOR]);

        $kiosks    = $this->kioskModel->getActive();
        $outlet_id = $this->get('outlet_id') ? (int) $this->get('outlet_id') : null;
        $from_date = $this->get('from_date', date('Y-m-d'));
        $to_date   = $this->get('to_date', date('Y-m-d'));

        $records = $this->timeInModel->getByDateRange($from_date, $to_date, $outlet_id);

        $data = [
            'page_title'  => 'Staff Time-In',
            'active_tab'  => 'timein',
            'kiosks'      => $kiosks,
            'outlet_id'   => $outlet_id,
            'from_date'   => $from_date,
            'to_date'     => $to_date,
            'records'     => $records,
        ];

        $this->render('reports/timein', $data);
    }
}
