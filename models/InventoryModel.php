<?php
/**
 * InventoryModel — handles Inventory_Snapshot table operations
 */
class InventoryModel extends Model
{
    /** Get snapshots for a specific date and kiosk */
    public function getByDateAndKiosk(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT i.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name
             FROM Inventory_Snapshot i
             JOIN Product p ON i.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Kiosk_ID = ? AND i.Snapshot_date = ?
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $date]
        );
    }

    /** Get snapshots for a specific date, kiosk, and type */
    public function getByDateKioskType(string $date, int $kiosk_id, string $type): array
    {
        return $this->db->read(
            "SELECT i.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name
             FROM Inventory_Snapshot i
             JOIN Product p ON i.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Kiosk_ID = ? AND i.Snapshot_date = ? AND i.Snapshot_type = ?
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $date, $type]
        );
    }

    /** Check if beginning snapshots exist for today at an kiosk */
    public function hasBeginningToday(int $kiosk_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt FROM Inventory_Snapshot
             WHERE Kiosk_ID = ? AND Snapshot_date = CURDATE() AND Snapshot_type = 'beginning'",
            [$kiosk_id]
        );
        return ($row['cnt'] ?? 0) > 0;
    }

    /** Check if ending snapshots exist for today at an kiosk */
    public function hasEndingToday(int $kiosk_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt FROM Inventory_Snapshot
             WHERE Kiosk_ID = ? AND Snapshot_date = CURDATE() AND Snapshot_type = 'ending'",
            [$kiosk_id]
        );
        return ($row['cnt'] ?? 0) > 0;
    }

    /** Batch-create beginning or ending snapshots for all active products */
    public function createBatchSnapshots(int $kiosk_id, int $user_id, string $type, array $quantities, ?string $date = null): int
    {
        $snapshot_date = $date ?? date('Y-m-d');
        $count = 0;

        $this->db->beginTransaction();
        try {
            foreach ($quantities as $product_id => $qty) {
                $this->db->insert(
                    "INSERT INTO Inventory_Snapshot (Kiosk_ID, Product_ID, User_ID, Snapshot_type, Quantity, Snapshot_date)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$kiosk_id, (int) $product_id, $user_id, $type, (int) $qty, $snapshot_date]
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
                 WHERE Kiosk_ID = ? AND Snapshot_date = ? AND Locked_status = 0",
                [$kiosk_id, $snapshot_date]
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

    /** Update quantity on an unlocked record */
    public function updateQuantity(int $inventory_id, int $quantity): int
    {
        return $this->db->write(
            "UPDATE Inventory_Snapshot SET Quantity = ? WHERE Inventory_ID = ? AND Locked_status = 0",
            [$quantity, $inventory_id]
        );
    }

    /** Get all dates with inventory records for an kiosk (for history) */
    public function getRecordedDates(int $kiosk_id, int $limit = 30): array
    {
        return $this->db->read(
            "SELECT DISTINCT Snapshot_date,
                    MAX(Locked_status) AS is_locked
             FROM Inventory_Snapshot
             WHERE Kiosk_ID = ?
             GROUP BY Snapshot_date
             ORDER BY Snapshot_date DESC
             LIMIT ?",
            [$kiosk_id, $limit]
        );
    }

    /** Get beginning/ending snapshot status for all active kiosks on a given date */
    public function getKioskInventoryStatus(string $date): array
    {
        return $this->db->read(
            "SELECT k.Kiosk_ID, k.Name AS Kiosk_Name,
                    MAX(CASE WHEN i.Snapshot_type = 'beginning' THEN 1 ELSE 0 END) AS has_beginning,
                    MAX(CASE WHEN i.Snapshot_type = 'ending'    THEN 1 ELSE 0 END) AS has_ending
             FROM Kiosk k
             LEFT JOIN Inventory_Snapshot i
                ON k.Kiosk_ID = i.Kiosk_ID
               AND i.Snapshot_date = ?
             WHERE k.Active = 1
             GROUP BY k.Kiosk_ID, k.Name
             ORDER BY k.Kiosk_ID",
            [$date]
        );
    }

    // ============================================================
    // PERPETUAL INVENTORY — carry-forward & running-stock helpers
    // ============================================================

    /**
     * Get the most recent LOCKED ending snapshot for a kiosk strictly before the given date.
     * Only locked rows are used as carry-forward source so unfinalized counts can't propagate.
     * Returns rows with Product_ID, Quantity, Snapshot_date, Product_Name, Unit, Category_Name.
     */
    public function getPreviousDayEnding(int $kiosk_id, string $date): array
    {
        // Find the most recent date with at least one locked ending row before $date
        $row = $this->db->readOne(
            "SELECT MAX(Snapshot_date) AS prev_date
             FROM Inventory_Snapshot
             WHERE Kiosk_ID = ?
               AND Snapshot_date < ?
               AND Snapshot_type = 'ending'
               AND Locked_status = 1",
            [$kiosk_id, $date]
        );
        $prev_date = $row['prev_date'] ?? null;
        if (!$prev_date) {
            return [];
        }

        return $this->db->read(
            "SELECT i.Product_ID, i.Quantity, i.Snapshot_date,
                    p.Name AS Product_Name, p.Unit, c.Name AS Category_Name
             FROM Inventory_Snapshot i
             JOIN Product  p ON i.Product_ID  = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Kiosk_ID = ?
               AND i.Snapshot_date = ?
               AND i.Snapshot_type = 'ending'
               AND i.Locked_status = 1
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $prev_date]
        );
    }

    /**
     * Auto-generate today's (or $date's) beginning stock by copying yesterday's locked ending.
     * If no previous locked ending is found, seeds zeros for all active products.
     * Returns the number of beginning rows inserted (0 means a duplicate existed and nothing was written).
     */
    public function autoGenerateBeginning(int $kiosk_id, int $user_id, string $date): int
    {
        // Build quantities map from previous locked ending
        $previous   = $this->getPreviousDayEnding($kiosk_id, $date);
        $quantities = [];

        if (!empty($previous)) {
            foreach ($previous as $row) {
                $quantities[(int) $row['Product_ID']] = (int) $row['Quantity'];
            }
        } else {
            // Fallback: seed zeros for every active product so the day is initialized
            $products = $this->db->read(
                "SELECT Product_ID FROM Product WHERE Active = 1"
            );
            foreach ($products as $p) {
                $quantities[(int) $p['Product_ID']] = 0;
            }
        }

        if (empty($quantities)) {
            return 0;
        }

        // Use INSERT IGNORE so any product that already has a beginning row
        // for this kiosk+date is silently skipped instead of raising a
        // duplicate-key error. This makes the operation idempotent and safe
        // against stale-slave reads or repeated clicks.
        $inserted = 0;
        $this->db->beginTransaction();
        try {
            foreach ($quantities as $product_id => $qty) {
                $rows = $this->db->write(
                    "INSERT IGNORE INTO Inventory_Snapshot
                        (Kiosk_ID, Product_ID, User_ID, Snapshot_type, Quantity, Snapshot_date)
                     VALUES (?, ?, ?, 'beginning', ?, ?)",
                    [$kiosk_id, (int) $product_id, $user_id, (int) $qty, $date]
                );
                $inserted += $rows;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        return $inserted;
    }

    /**
     * Compute running inventory per product for a given date and kiosk:
     *   running = beginning + delivered_today - sold_today
     * Returns one row per product that has a beginning snapshot for $date.
     */
    public function getRunningInventory(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT
                p.Product_ID,
                p.Name           AS Product_Name,
                p.Unit,
                c.Name           AS Category_Name,
                COALESCE(beg.Quantity, 0)         AS Beginning_Qty,
                COALESCE(d.delivered, 0)          AS Delivered_Qty,
                COALESCE(s.sold, 0)               AS Sold_Qty,
                (COALESCE(beg.Quantity, 0)
                 + COALESCE(d.delivered, 0)
                 - COALESCE(s.sold, 0))           AS Running_Qty
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             INNER JOIN Inventory_Snapshot beg
                    ON beg.Product_ID    = p.Product_ID
                   AND beg.Kiosk_ID     = ?
                   AND beg.Snapshot_date = ?
                   AND beg.Snapshot_type = 'beginning'
             LEFT JOIN (
                    SELECT Product_ID, SUM(Quantity) AS delivered
                    FROM Delivery
                    WHERE Kiosk_ID = ? AND Delivery_Date = ?
                    GROUP BY Product_ID
                ) d ON d.Product_ID = p.Product_ID
             LEFT JOIN (
                    SELECT Product_ID, SUM(Quantity_sold) AS sold
                    FROM Sales
                    WHERE Kiosk_ID = ? AND Sales_date = ?
                    GROUP BY Product_ID
                ) s ON s.Product_ID = p.Product_ID
             WHERE p.Active = 1
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $date, $kiosk_id, $date, $kiosk_id, $date]
        );
    }

    // ============================================================
    // PARTS-BASED INVENTORY (added 2026-04-27)
    // The new system tracks raw PARTS (Burger Bun, Patty, Cheese, ...).
    // Existing product-based methods above remain in place for
    // historical data; new operational paths use the methods below.
    // ============================================================

    /**
     * Get parts inventory snapshot for a kiosk, date, and type.
     * Filters out historical product-based rows (Part_ID NULL).
     */
    public function getPartsByDateKioskType(string $date, int $kiosk_id, string $type): array
    {
        return $this->db->read(
            "SELECT i.*, pt.Name AS Part_Name, pt.Unit
             FROM   Inventory_Snapshot i
             JOIN   Part pt ON i.Part_ID = pt.Part_ID
             WHERE  i.Kiosk_ID      = ?
               AND  i.Snapshot_date = ?
               AND  i.Snapshot_type = ?
               AND  i.Part_ID       IS NOT NULL
             ORDER  BY pt.Name",
            [$kiosk_id, $date, $type]
        );
    }

    /** True if a parts-based beginning snapshot exists today */
    public function hasPartsBeginningToday(int $kiosk_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt
             FROM   Inventory_Snapshot
             WHERE  Kiosk_ID      = ?
               AND  Snapshot_date = CURDATE()
               AND  Snapshot_type = 'beginning'
               AND  Part_ID       IS NOT NULL",
            [$kiosk_id]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Per-kiosk parts inventory status for a given date.
     * Mirrors getKioskInventoryStatus() but filters on Part_ID IS NOT NULL
     * so historical product-based rows don't pollute the dashboard counts.
     */
    public function getKioskPartsInventoryStatus(string $date): array
    {
        return $this->db->read(
            "SELECT k.Kiosk_ID, k.Name AS Kiosk_Name,
                    MAX(CASE WHEN i.Snapshot_type = 'beginning' THEN 1 ELSE 0 END) AS has_beginning,
                    MAX(CASE WHEN i.Snapshot_type = 'ending'    THEN 1 ELSE 0 END) AS has_ending
             FROM Kiosk k
             LEFT JOIN Inventory_Snapshot i
                ON k.Kiosk_ID = i.Kiosk_ID
               AND i.Snapshot_date = ?
               AND i.Part_ID IS NOT NULL
             WHERE k.Active = 1
             GROUP BY k.Kiosk_ID, k.Name
             ORDER BY k.Kiosk_ID",
            [$date]
        );
    }

    /** True if a parts-based ending snapshot exists today */
    public function hasPartsEndingToday(int $kiosk_id): bool
    {
        $row = $this->db->readOne(
            "SELECT COUNT(*) AS cnt
             FROM   Inventory_Snapshot
             WHERE  Kiosk_ID      = ?
               AND  Snapshot_date = CURDATE()
               AND  Snapshot_type = 'ending'
               AND  Part_ID       IS NOT NULL",
            [$kiosk_id]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Batch-create beginning or ending PART snapshots.
     * $quantities = [Part_ID => qty, ...]
     * On 'ending' submission, all parts-based rows for that day are auto-locked.
     */
    public function createPartsSnapshots(int $kiosk_id, int $user_id, string $type, array $quantities, string $date): int
    {
        $count = 0;
        $this->db->beginTransaction();
        try {
            foreach ($quantities as $part_id => $qty) {
                $this->db->insert(
                    "INSERT INTO Inventory_Snapshot
                        (Kiosk_ID, Part_ID, User_ID, Snapshot_type, Quantity, Snapshot_date)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$kiosk_id, (int) $part_id, $user_id, $type, max(0, (int) $qty), $date]
                );
                $count++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        if ($type === 'ending') {
            $this->db->write(
                "UPDATE Inventory_Snapshot
                 SET    Locked_status = 1
                 WHERE  Kiosk_ID      = ?
                   AND  Snapshot_date = ?
                   AND  Part_ID       IS NOT NULL
                   AND  Locked_status = 0",
                [$kiosk_id, $date]
            );
        }
        return $count;
    }

    /**
     * Get the most recent locked PART ending snapshot before $date,
     * for carry-forward. Returns [] if none exists.
     */
    public function getPreviousDayEndingParts(int $kiosk_id, string $date): array
    {
        $row = $this->db->readOne(
            "SELECT MAX(Snapshot_date) AS prev_date
             FROM   Inventory_Snapshot
             WHERE  Kiosk_ID      = ?
               AND  Snapshot_date < ?
               AND  Snapshot_type = 'ending'
               AND  Locked_status = 1
               AND  Part_ID       IS NOT NULL",
            [$kiosk_id, $date]
        );
        $prev = $row['prev_date'] ?? null;
        if (!$prev) {
            return [];
        }

        return $this->db->read(
            "SELECT i.Part_ID, i.Quantity, i.Snapshot_date,
                    pt.Name AS Part_Name, pt.Unit
             FROM   Inventory_Snapshot i
             JOIN   Part pt ON i.Part_ID = pt.Part_ID
             WHERE  i.Kiosk_ID      = ?
               AND  i.Snapshot_date = ?
               AND  i.Snapshot_type = 'ending'
               AND  i.Locked_status = 1
               AND  i.Part_ID       IS NOT NULL
             ORDER  BY pt.Name",
            [$kiosk_id, $prev]
        );
    }

    /**
     * Auto-generate today's parts beginning by carry-forward from the
     * previous day's locked ending. Falls back to zero rows for every
     * active part if no previous ending exists.
     * Idempotent — uses INSERT IGNORE to skip already-seeded rows.
     */
    public function autoGeneratePartsBeginning(int $kiosk_id, int $user_id, string $date): int
    {
        $previous   = $this->getPreviousDayEndingParts($kiosk_id, $date);
        $quantities = [];

        if (!empty($previous)) {
            foreach ($previous as $row) {
                $quantities[(int) $row['Part_ID']] = (int) $row['Quantity'];
            }
        } else {
            $parts = $this->db->read(
                "SELECT Part_ID FROM Part WHERE Active = 1"
            );
            foreach ($parts as $p) {
                $quantities[(int) $p['Part_ID']] = 0;
            }
        }
        if (empty($quantities)) {
            return 0;
        }

        $inserted = 0;
        $this->db->beginTransaction();
        try {
            foreach ($quantities as $part_id => $qty) {
                $rows = $this->db->write(
                    "INSERT IGNORE INTO Inventory_Snapshot
                        (Kiosk_ID, Part_ID, User_ID, Snapshot_type, Quantity, Snapshot_date)
                     VALUES (?, ?, ?, 'beginning', ?, ?)",
                    [$kiosk_id, $part_id, $user_id, $qty, $date]
                );
                $inserted += $rows;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        return $inserted;
    }

    /**
     * Running PARTS inventory for a kiosk and date.
     *   Running_Qty = Beginning + Delivered − ParticipatedInSales
     * Sales consumption is computed by joining Product_Part:
     *   for every Sales row, multiply Quantity_sold × parts_per_unit
     *   for each part used by the product, then sum per Part_ID.
     */
    public function getRunningPartsInventory(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT
                pt.Part_ID,
                pt.Name                            AS Part_Name,
                pt.Unit,
                COALESCE(beg.Quantity, 0)          AS Beginning_Qty,
                COALESCE(d.delivered, 0)           AS Delivered_Qty,
                COALESCE(consumed.used_qty, 0)     AS Used_Qty,
                (COALESCE(beg.Quantity, 0)
                 + COALESCE(d.delivered, 0)
                 - COALESCE(consumed.used_qty, 0)) AS Running_Qty
             FROM Part pt
             INNER JOIN Inventory_Snapshot beg
                    ON beg.Part_ID       = pt.Part_ID
                   AND beg.Kiosk_ID      = ?
                   AND beg.Snapshot_date = ?
                   AND beg.Snapshot_type = 'beginning'
             LEFT JOIN (
                    SELECT Part_ID, SUM(Quantity) AS delivered
                    FROM   Delivery
                    WHERE  Kiosk_ID      = ?
                      AND  Delivery_Date = ?
                      AND  Part_ID       IS NOT NULL
                    GROUP  BY Part_ID
                ) d ON d.Part_ID = pt.Part_ID
             LEFT JOIN (
                    SELECT pp.Part_ID,
                           SUM(s.Quantity_sold * pp.Quantity_needed) AS used_qty
                    FROM   Sales s
                    JOIN   Product_Part pp ON s.Product_ID = pp.Product_ID
                    WHERE  s.Kiosk_ID   = ?
                      AND  s.Sales_date = ?
                    GROUP  BY pp.Part_ID
                ) consumed ON consumed.Part_ID = pt.Part_ID
             WHERE pt.Active = 1
             ORDER BY pt.Name",
            [$kiosk_id, $date, $kiosk_id, $date, $kiosk_id, $date]
        );
    }

    /**
     * Check if all parts needed to make $quantity of $product_id are in stock.
     * Returns: ['available' => bool, 'parts' => [{name, needed, available, ok}, ...]]
     * Empty recipe → product treated as unlimited (returns available=true).
     */
    public function checkProductAvailability(int $product_id, int $kiosk_id, string $date, int $quantity = 1): array
    {
        $recipe = $this->db->read(
            "SELECT pp.Part_ID, pp.Quantity_needed, pt.Name
             FROM   Product_Part pp
             JOIN   Part pt ON pp.Part_ID = pt.Part_ID
             WHERE  pp.Product_ID = ?",
            [$product_id]
        );
        if (empty($recipe)) {
            return ['available' => true, 'parts' => []];
        }

        $running = $this->getRunningPartsInventory($date, $kiosk_id);
        $stock   = [];
        foreach ($running as $r) {
            $stock[(int) $r['Part_ID']] = (int) $r['Running_Qty'];
        }

        $available  = true;
        $parts_info = [];
        foreach ($recipe as $r) {
            $pid    = (int) $r['Part_ID'];
            $needed = (int) $r['Quantity_needed'] * $quantity;
            $have   = $stock[$pid] ?? 0;
            $ok     = $have >= $needed;
            if (!$ok) $available = false;
            $parts_info[] = [
                'name'      => $r['Name'],
                'needed'    => $needed,
                'available' => $have,
                'ok'        => $ok,
            ];
        }
        return ['available' => $available, 'parts' => $parts_info];
    }
}
