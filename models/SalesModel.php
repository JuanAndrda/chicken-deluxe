<?php
/**
 * SalesModel — handles Sales table operations
 */
class SalesModel extends Model
{
    /** Get sales for a specific date and kiosk */
    public function getByDateAndKiosk(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT s.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name,
                    u.Full_name AS Recorded_by
             FROM Sales s
             JOIN Product p ON s.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             JOIN User u ON s.User_ID = u.User_ID
             WHERE s.Kiosk_ID = ? AND s.Sales_date = ?
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $date]
        );
    }

    /** Get daily totals for a date and kiosk */
    public function getDailyTotal(string $date, int $kiosk_id): float
    {
        $row = $this->db->readOne(
            "SELECT COALESCE(SUM(Line_total), 0) AS total
             FROM Sales
             WHERE Kiosk_ID = ? AND Sales_date = ?",
            [$kiosk_id, $date]
        );
        return (float) ($row['total'] ?? 0);
    }

    /** Get recent sales dates for an kiosk */
    public function getRecordedDates(int $kiosk_id, int $limit = 30): array
    {
        return $this->db->read(
            "SELECT Sales_date,
                    COUNT(*) AS item_count,
                    SUM(Line_total) AS day_total,
                    MAX(Locked_status) AS is_locked
             FROM Sales
             WHERE Kiosk_ID = ?
             GROUP BY Sales_date
             ORDER BY Sales_date DESC
             LIMIT ?",
            [$kiosk_id, $limit]
        );
    }

    /** Create a sales record (Line_total auto-calculated by trigger) */
    public function create(int $kiosk_id, int $user_id, int $product_id, string $date, int $quantity_sold, float $unit_price): int
    {
        return $this->db->insert(
            "INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date, Quantity_sold, Unit_Price)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$kiosk_id, $user_id, $product_id, $date, $quantity_sold, $unit_price]
        );
    }

    /** Lock all sales for a specific date and kiosk */
    public function lockByDate(int $kiosk_id, string $date): int
    {
        return $this->db->write(
            "UPDATE Sales SET Locked_status = 1
             WHERE Kiosk_ID = ? AND Sales_date = ? AND Locked_status = 0",
            [$kiosk_id, $date]
        );
    }

    /** Unlock a sales record (owner only) */
    public function unlock(int $sales_id): int
    {
        return $this->db->write(
            "UPDATE Sales SET Locked_status = 0 WHERE Sales_ID = ?",
            [$sales_id]
        );
    }

    /** Delete a sales record (only if unlocked) */
    public function delete(int $sales_id): int
    {
        return $this->db->write(
            "DELETE FROM Sales WHERE Sales_ID = ? AND Locked_status = 0",
            [$sales_id]
        );
    }
}
