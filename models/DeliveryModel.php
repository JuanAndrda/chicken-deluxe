<?php
/**
 * DeliveryModel — handles Delivery table operations
 */
class DeliveryModel extends Model
{
    /** Get deliveries for a specific date and kiosk */
    public function getByDateAndKiosk(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT d.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name,
                    u.Full_name AS Recorded_by
             FROM Delivery d
             JOIN Product p ON d.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             JOIN User u ON d.User_ID = u.User_ID
             WHERE d.Kiosk_ID = ? AND d.Delivery_Date = ?
             ORDER BY d.Created_at DESC",
            [$kiosk_id, $date]
        );
    }

    /** Get recent delivery dates for an kiosk */
    public function getRecordedDates(int $kiosk_id, int $limit = 30): array
    {
        return $this->db->read(
            "SELECT DISTINCT Delivery_Date,
                    COUNT(*) AS item_count,
                    MAX(Locked_status) AS is_locked
             FROM Delivery
             WHERE Kiosk_ID = ?
             GROUP BY Delivery_Date
             ORDER BY Delivery_Date DESC
             LIMIT ?",
            [$kiosk_id, $limit]
        );
    }

    /** Create a delivery record */
    public function create(int $kiosk_id, int $user_id, int $product_id, string $date, int $quantity): int
    {
        return $this->db->insert(
            "INSERT INTO Delivery (Kiosk_ID, User_ID, Product_ID, Delivery_Date, Quantity)
             VALUES (?, ?, ?, ?, ?)",
            [$kiosk_id, $user_id, $product_id, $date, $quantity]
        );
    }

    /** Lock all deliveries for a specific date and kiosk */
    public function lockByDate(int $kiosk_id, string $date): int
    {
        return $this->db->write(
            "UPDATE Delivery SET Locked_status = 1
             WHERE Kiosk_ID = ? AND Delivery_Date = ? AND Locked_status = 0",
            [$kiosk_id, $date]
        );
    }

    /** Unlock a delivery record (owner only) */
    public function unlock(int $delivery_id): int
    {
        return $this->db->write(
            "UPDATE Delivery SET Locked_status = 0 WHERE Delivery_ID = ?",
            [$delivery_id]
        );
    }

    /** Delete a delivery record (only if unlocked) */
    public function delete(int $delivery_id): int
    {
        return $this->db->write(
            "DELETE FROM Delivery WHERE Delivery_ID = ? AND Locked_status = 0",
            [$delivery_id]
        );
    }

    /** Update delivery quantity (only if unlocked) */
    public function updateQuantity(int $delivery_id, int $quantity): int
    {
        return $this->db->write(
            "UPDATE Delivery
             SET Quantity = ?
             WHERE Delivery_ID = ? AND Locked_status = 0",
            [$quantity, $delivery_id]
        );
    }
}
