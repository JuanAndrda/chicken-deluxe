<?php
/**
 * ExpenseModel — handles Expenses table operations
 */
class ExpenseModel extends Model
{
    /** Get expenses for a specific date and kiosk */
    public function getByDateAndKiosk(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT e.*, u.Full_name AS Recorded_by
             FROM Expenses e
             JOIN User u ON e.User_ID = u.User_ID
             WHERE e.Kiosk_ID = ? AND e.Expense_date = ?
             ORDER BY e.Created_at DESC",
            [$kiosk_id, $date]
        );
    }

    /** Get daily expense total for a date and kiosk */
    public function getDailyTotal(string $date, int $kiosk_id): float
    {
        $row = $this->db->readOne(
            "SELECT COALESCE(SUM(Amount), 0) AS total
             FROM Expenses
             WHERE Kiosk_ID = ? AND Expense_date = ?",
            [$kiosk_id, $date]
        );
        return (float) ($row['total'] ?? 0);
    }

    /** Get recent expense dates for an kiosk */
    public function getRecordedDates(int $kiosk_id, int $limit = 30): array
    {
        return $this->db->read(
            "SELECT Expense_date,
                    COUNT(*) AS item_count,
                    SUM(Amount) AS day_total,
                    MAX(Locked_status) AS is_locked
             FROM Expenses
             WHERE Kiosk_ID = ?
             GROUP BY Expense_date
             ORDER BY Expense_date DESC
             LIMIT ?",
            [$kiosk_id, $limit]
        );
    }

    /** Create an expense record */
    public function create(int $kiosk_id, int $user_id, string $date, float $amount, string $description): int
    {
        return $this->db->insert(
            "INSERT INTO Expenses (Kiosk_ID, User_ID, Expense_date, Amount, Description)
             VALUES (?, ?, ?, ?, ?)",
            [$kiosk_id, $user_id, $date, $amount, $description]
        );
    }

    /** Lock all expenses for a specific date and kiosk */
    public function lockByDate(int $kiosk_id, string $date): int
    {
        return $this->db->write(
            "UPDATE Expenses SET Locked_status = 1
             WHERE Kiosk_ID = ? AND Expense_date = ? AND Locked_status = 0",
            [$kiosk_id, $date]
        );
    }

    /** Unlock an expense record (owner only) */
    public function unlock(int $expense_id): int
    {
        return $this->db->write(
            "UPDATE Expenses SET Locked_status = 0 WHERE Expense_ID = ?",
            [$expense_id]
        );
    }

    /** Delete an expense record (only if unlocked) */
    public function delete(int $expense_id): int
    {
        return $this->db->write(
            "DELETE FROM Expenses WHERE Expense_ID = ? AND Locked_status = 0",
            [$expense_id]
        );
    }

    /** Update an expense record (only if unlocked) */
    public function update(
        int    $expense_id,
        float  $amount,
        string $description
    ): int {
        return $this->db->write(
            "UPDATE Expenses
             SET Amount = ?, Description = ?
             WHERE Expense_ID = ? AND Locked_status = 0",
            [$amount, $description, $expense_id]
        );
    }
}
