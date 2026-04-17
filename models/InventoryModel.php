<?php
/**
 * InventoryModel — handles Inventory_Snapshot table operations
 */
class InventoryModel extends Model
{
    /** Get today's snapshots for an outlet */
    public function getTodayByOutlet(int $outlet_id): array
    {
        return $this->db->read(
            "SELECT i.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name
             FROM Inventory_Snapshot i
             JOIN Product p ON i.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Outlet_ID = ? AND i.Snapshot_date = CURDATE()
             ORDER BY c.Name, p.Name",
            [$outlet_id]
        );
    }

    /** Get snapshots for a specific date and outlet */
    public function getByDateAndOutlet(string $date, int $outlet_id): array
    {
        return $this->db->read(
            "SELECT i.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name
             FROM Inventory_Snapshot i
             JOIN Product p ON i.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Outlet_ID = ? AND i.Snapshot_date = ?
             ORDER BY c.Name, p.Name",
            [$outlet_id, $date]
        );
    }

    /** Get snapshots for a specific date, outlet, and type */
    public function getByDateOutletType(string $date, int $outlet_id, string $type): array
    {
        return $this->db->read(
            "SELECT i.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name
             FROM Inventory_Snapshot i
             JOIN Product p ON i.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Outlet_ID = ? AND i.Snapshot_date = ? AND i.Snapshot_type = ?
             ORDER BY c.Name, p.Name",
            [$outlet_id, $date, $type]
        );
    }

    /** Check if beginning snapshots exist for today at an outlet */
    public function hasBeginningToday(int $outlet_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt FROM Inventory_Snapshot
             WHERE Outlet_ID = ? AND Snapshot_date = CURDATE() AND Snapshot_type = 'beginning'",
            [$outlet_id]
        );
        return ($row['cnt'] ?? 0) > 0;
    }

    /** Check if ending snapshots exist for today at an outlet */
    public function hasEndingToday(int $outlet_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt FROM Inventory_Snapshot
             WHERE Outlet_ID = ? AND Snapshot_date = CURDATE() AND Snapshot_type = 'ending'",
            [$outlet_id]
        );
        return ($row['cnt'] ?? 0) > 0;
    }

    /** Create a snapshot record */
    public function createSnapshot(int $outlet_id, int $product_id, int $user_id, string $type, int $quantity, ?string $date = null): int
    {
        $snapshot_date = $date ?? date('Y-m-d');
        return $this->db->insert(
            "INSERT INTO Inventory_Snapshot (Outlet_ID, Product_ID, User_ID, Snapshot_type, Quantity, Snapshot_date)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$outlet_id, $product_id, $user_id, $type, $quantity, $snapshot_date]
        );
    }

    /** Batch-create beginning or ending snapshots for all active products */
    public function createBatchSnapshots(int $outlet_id, int $user_id, string $type, array $quantities, ?string $date = null): int
    {
        $snapshot_date = $date ?? date('Y-m-d');
        $count = 0;

        $this->db->beginTransaction();
        try {
            foreach ($quantities as $product_id => $qty) {
                $this->db->insert(
                    "INSERT INTO Inventory_Snapshot (Outlet_ID, Product_ID, User_ID, Snapshot_type, Quantity, Snapshot_date)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$outlet_id, (int) $product_id, $user_id, $type, (int) $qty, $snapshot_date]
                );
                $count++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        // After ending stock is recorded, lock all snapshots for that date
        // (was previously a DB trigger but MySQL forbids self-table updates inside triggers)
        if ($type === 'ending') {
            $this->db->write(
                "UPDATE Inventory_Snapshot SET Locked_status = 1
                 WHERE Outlet_ID = ? AND Snapshot_date = ? AND Locked_status = 0",
                [$outlet_id, $snapshot_date]
            );
        }

        return $count;
    }

    /** Unlock a snapshot record (owner only) */
    public function unlock(int $inventory_id, int $user_id): int
    {
        return $this->db->write(
            "UPDATE Inventory_Snapshot SET Locked_status = 0, User_ID = ? WHERE Inventory_ID = ?",
            [$user_id, $inventory_id]
        );
    }

    /** Bulk-unlock all locked snapshots for a date and optional outlet (owner only) */
    public function unlockAllByDate(string $date, ?int $outlet_id = null): int
    {
        if ($outlet_id !== null) {
            return $this->db->write(
                "UPDATE Inventory_Snapshot SET Locked_status = 0
                 WHERE Snapshot_date = ? AND Outlet_ID = ? AND Locked_status = 1",
                [$date, $outlet_id]
            );
        }
        return $this->db->write(
            "UPDATE Inventory_Snapshot SET Locked_status = 0
             WHERE Snapshot_date = ? AND Locked_status = 1",
            [$date]
        );
    }

    /** Update quantity on an unlocked record */
    public function updateQuantity(int $inventory_id, int $quantity): int
    {
        return $this->db->write(
            "UPDATE Inventory_Snapshot SET Quantity = ? WHERE Inventory_ID = ? AND Locked_status = 0",
            [$quantity, $inventory_id]
        );
    }

    /** Get all dates with inventory records for an outlet (for history) */
    public function getRecordedDates(int $outlet_id, int $limit = 30): array
    {
        return $this->db->read(
            "SELECT DISTINCT Snapshot_date,
                    MAX(Locked_status) AS is_locked
             FROM Inventory_Snapshot
             WHERE Outlet_ID = ?
             GROUP BY Snapshot_date
             ORDER BY Snapshot_date DESC
             LIMIT ?",
            [$outlet_id, $limit]
        );
    }
}
