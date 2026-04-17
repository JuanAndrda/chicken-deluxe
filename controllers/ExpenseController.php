<?php
/**
 * ExpenseController — track daily operational expenses
 */
require_once __DIR__ . '/../models/ExpenseModel.php';
require_once __DIR__ . '/../models/KioskModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class ExpenseController extends Controller
{
    private ExpenseModel $expenseModel;
    private KioskModel $kioskModel;
    private AuditLogModel $auditLog;

    public function __construct()
    {
        $this->expenseModel = new ExpenseModel();
        $this->kioskModel   = new KioskModel();
        $this->auditLog     = new AuditLogModel();
    }

    /** Show expenses page */
    public function index(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        $outlet_id = $this->resolveOutlet();
        $kiosk     = $this->kioskModel->findById($outlet_id);
        $date      = $this->get('date', date('Y-m-d'));

        $expenses  = $this->expenseModel->getByDateAndOutlet($date, $outlet_id);
        $day_total = $this->expenseModel->getDailyTotal($date, $outlet_id);
        $is_today  = ($date === date('Y-m-d'));
        $any_locked = false;
        foreach ($expenses as $e) {
            if ($e['Locked_status']) { $any_locked = true; break; }
        }

        $data = [
            'page_title'  => 'Expenses',
            'kiosk'       => $kiosk,
            'outlet_id'   => $outlet_id,
            'date'        => $date,
            'expenses'    => $expenses,
            'day_total'   => $day_total,
            'is_today'    => $is_today,
            'any_locked'  => $any_locked,
            'kiosks'      => Auth::isOwner() ? $this->kioskModel->getActive() : [],
            'history'     => $this->expenseModel->getRecordedDates($outlet_id),
            'success'     => $_GET['success'] ?? null,
            'error'       => $_GET['error'] ?? null,
        ];

        $this->render('expenses/index', $data);
    }

    /** Handle adding an expense */
    public function store(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/expenses?error=Invalid+request');
            return;
        }

        $outlet_id   = $this->resolveOutlet();
        $amount      = (float) $this->post('amount');
        $description = trim($this->post('description', ''));
        $date        = $this->post('date', date('Y-m-d'));

        if ($amount <= 0 || $description === '') {
            $this->redirect("/expenses?date={$date}&error=Amount+and+description+are+required");
            return;
        }

        $expense_id = $this->expenseModel->create($outlet_id, Auth::userId(), $date, $amount, $description);
        $this->auditLog->log(Auth::userId(), ACTION_CREATE, "Expense ID:{$expense_id} — amount:{$amount}, desc:{$description}");

        $this->redirect("/expenses?date={$date}&success=Expense+recorded");
    }

    /** Lock all expenses for a date */
    public function lock(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/expenses?error=Invalid+request');
            return;
        }

        $outlet_id = $this->resolveOutlet();
        $date      = $this->post('date', date('Y-m-d'));

        $count = $this->expenseModel->lockByDate($outlet_id, $date);
        $this->auditLog->log(Auth::userId(), ACTION_LOCK, "Locked {$count} expenses for outlet:{$outlet_id} on {$date}");

        $this->redirect("/expenses?date={$date}&success=Expenses+locked+({$count}+records)");
    }

    /** Unlock an expense record (owner only) */
    public function unlock(): void
    {
        Auth::requireRole([ROLE_OWNER]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/expenses?error=Invalid+request');
            return;
        }

        $expense_id = (int) $this->post('expense_id');
        $date       = $this->post('date', date('Y-m-d'));

        $this->expenseModel->unlock($expense_id);
        $this->auditLog->log(Auth::userId(), ACTION_UNLOCK, "Unlocked Expense ID:{$expense_id}");

        $this->redirect("/expenses?date={$date}&success=Record+unlocked");
    }

    /** Delete an expense (only if unlocked) */
    public function delete(): void
    {
        Auth::requireRole([ROLE_OWNER, ROLE_STAFF]);

        if (!Auth::validateCsrf()) {
            $this->redirect('/expenses?error=Invalid+request');
            return;
        }

        $expense_id = (int) $this->post('expense_id');
        $date       = $this->post('date', date('Y-m-d'));

        $deleted = $this->expenseModel->delete($expense_id);
        if ($deleted) {
            $this->auditLog->log(Auth::userId(), ACTION_DELETE, "Deleted Expense ID:{$expense_id}");
            $this->redirect("/expenses?date={$date}&success=Expense+deleted");
        } else {
            $this->redirect("/expenses?date={$date}&error=Cannot+delete+a+locked+record");
        }
    }

    /** Determine which outlet to use */
    private function resolveOutlet(): int
    {
        if (Auth::isStaff()) {
            return Auth::outletId();
        }
        $selected = $this->get('outlet_id', $this->post('outlet_id'));
        if ($selected) {
            return (int) $selected;
        }
        $kiosks = $this->kioskModel->getActive();
        return $kiosks[0]['Kiosk_ID'] ?? 1;
    }
}
