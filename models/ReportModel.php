<?php
/**
 * ReportModel — handles all reporting queries (read-only from Slave)
 */
class ReportModel extends Model
{
    /**
     * Daily summary for a single kiosk on a specific date.
     *
     * REWRITTEN 2026-05 — was joining Inventory_Snapshot.Product_ID and
     * Delivery.Product_ID, both of which are always NULL in the parts-based
     * system, so the report always returned an empty array. Now driven by
     * Part_ID and Sales x Product_Part recipe consumption — same formula
     * as InventoryModel::getRunningPartsInventory.
     *
     * Output keys are kept compatible with the existing daily.php view
     * (`Product_Name` actually carries the part name, `Category_Name` is a
     * fixed 'Parts' label since parts have no category). `Sales_Total` is
     * 0 per row — sales totals are per-product, not per-part; the kiosk's
     * total sales for the day is shown in the page header via
     * getDailyTotalSales().
     */
    public function getDailySummary(string $date, int $kiosk_id): array
    {
        $rows = $this->db->read(
            "SELECT
                pt.Part_ID,
                pt.Name                              AS Part_Name,
                pt.Unit,
                COALESCE(beg.Quantity, 0)            AS Beginning_Qty,
                COALESCE(end_s.Quantity, 0)          AS Ending_Qty,
                COALESCE(d.delivered, 0)             AS Delivered_Qty,
                COALESCE(po.pulled_out, 0)           AS Pullout_Qty,
                COALESCE(consumed.used_qty, 0)       AS Used_Qty
             FROM Part pt
             LEFT JOIN Inventory_Snapshot beg
                    ON beg.Part_ID = pt.Part_ID AND beg.Kiosk_ID = ?
                   AND beg.Snapshot_date = ? AND beg.Snapshot_type = 'beginning'
             LEFT JOIN Inventory_Snapshot end_s
                    ON end_s.Part_ID = pt.Part_ID AND end_s.Kiosk_ID = ?
                   AND end_s.Snapshot_date = ? AND end_s.Snapshot_type = 'ending'
             LEFT JOIN (
                    SELECT Part_ID, SUM(Quantity) AS delivered
                    FROM   Delivery
                    WHERE  Kiosk_ID = ? AND Delivery_Date = ?
                      AND  Part_ID IS NOT NULL AND Type = 'Delivery'
                    GROUP  BY Part_ID
                ) d ON d.Part_ID = pt.Part_ID
             LEFT JOIN (
                    SELECT Part_ID, SUM(Quantity) AS pulled_out
                    FROM   Delivery
                    WHERE  Kiosk_ID = ? AND Delivery_Date = ?
                      AND  Part_ID IS NOT NULL AND Type = 'Pullout'
                    GROUP  BY Part_ID
                ) po ON po.Part_ID = pt.Part_ID
             LEFT JOIN (
                    SELECT pp.Part_ID,
                           SUM(s.Quantity_sold * pp.Quantity_needed) AS used_qty
                    FROM   Sales s
                    JOIN   Product_Part pp ON s.Product_ID = pp.Product_ID
                    WHERE  s.Kiosk_ID = ? AND s.Sales_date = ?
                    GROUP  BY pp.Part_ID
                ) consumed ON consumed.Part_ID = pt.Part_ID
             WHERE pt.Active = 1
               AND (beg.Quantity IS NOT NULL
                    OR end_s.Quantity IS NOT NULL
                    OR d.delivered IS NOT NULL
                    OR po.pulled_out IS NOT NULL
                    OR consumed.used_qty IS NOT NULL)
             ORDER BY pt.Name",
            [$kiosk_id, $date, $kiosk_id, $date,
             $kiosk_id, $date, $kiosk_id, $date, $kiosk_id, $date]
        );

        $report = [];
        foreach ($rows as $r) {
            $beg      = (int) $r['Beginning_Qty'];
            $del      = (int) $r['Delivered_Qty'];
            $pull     = (int) $r['Pullout_Qty'];
            $used     = (int) $r['Used_Qty'];
            $end      = (int) $r['Ending_Qty'];
            $expected = $beg + $del - $pull - $used;
            // Discrepancy is only meaningful once an ending snapshot exists.
            $disc = $end > 0 ? ($end - $expected) : 0;

            $report[] = [
                // View-compat keys (Product_Name carries the part name)
                'Product_Name'  => $r['Part_Name'],
                'Category_Name' => 'Parts',
                'Unit'          => $r['Unit'],
                'Beginning_Qty' => $beg,
                'Delivered_Qty' => $del,
                'Pullout_Qty'   => $pull,
                'Used_Qty'      => $used,
                'Sold_Qty'      => $used, // alias: parts "sold" == parts consumed by sales
                'Sales_Total'   => 0.0,   // per-part has no peso total; see getDailyTotalSales()
                'Ending_Qty'    => $end,
                'Expected_Qty'  => $expected,
                'Discrepancy'   => $disc,
            ];
        }
        return $report;
    }

    /** Kiosk's total peso sales for a given day (used by daily report header) */
    public function getDailyTotalSales(int $kiosk_id, string $date): float
    {
        $row = $this->db->readOne(
            "SELECT COALESCE(SUM(Line_total), 0) AS Total
             FROM   Sales
             WHERE  Kiosk_ID = ? AND Sales_date = ?",
            [$kiosk_id, $date]
        );
        return (float) ($row['Total'] ?? 0);
    }

    /** Get sales totals per kiosk for a date range */
    public function getSalesTotalsByKiosk(string $from_date, string $to_date): array
    {
        return $this->db->read(
            "SELECT k.Name AS Kiosk_Name, k.Kiosk_ID,
                    COUNT(s.Sales_ID) AS Total_Transactions,
                    COALESCE(SUM(s.Line_total), 0) AS Total_Sales
             FROM Kiosk k
             LEFT JOIN Sales s ON k.Kiosk_ID = s.Kiosk_ID
                AND s.Sales_date BETWEEN ? AND ?
             WHERE k.Active = 1
             GROUP BY k.Kiosk_ID, k.Name
             ORDER BY k.Kiosk_ID",
            [$from_date, $to_date]
        );
    }

    /** Get expense totals per kiosk for a date range */
    public function getExpenseTotalsByKiosk(string $from_date, string $to_date): array
    {
        return $this->db->read(
            "SELECT k.Name AS Kiosk_Name, k.Kiosk_ID,
                    COUNT(e.Expense_ID) AS Total_Entries,
                    COALESCE(SUM(e.Amount), 0) AS Total_Expenses
             FROM Kiosk k
             LEFT JOIN Expenses e ON k.Kiosk_ID = e.Kiosk_ID
                AND e.Expense_date BETWEEN ? AND ?
             WHERE k.Active = 1
             GROUP BY k.Kiosk_ID, k.Name
             ORDER BY k.Kiosk_ID",
            [$from_date, $to_date]
        );
    }

    /** Get daily sales breakdown for a date range and optional kiosk */
    public function getDailySalesBreakdown(string $from_date, string $to_date, ?int $kiosk_id = null): array
    {
        $sql = "SELECT s.Sales_date, k.Name AS Kiosk_Name,
                       SUM(s.Line_total) AS Day_Total,
                       COUNT(s.Sales_ID) AS Transactions
                FROM Sales s
                JOIN Kiosk k ON s.Kiosk_ID = k.Kiosk_ID
                WHERE s.Sales_date BETWEEN ? AND ?";
        $params = [$from_date, $to_date];

        if ($kiosk_id !== null) {
            $sql .= " AND s.Kiosk_ID = ?";
            $params[] = $kiosk_id;
        }

        $sql .= " GROUP BY s.Sales_date, s.Kiosk_ID
                   ORDER BY s.Sales_date DESC, k.Name";

        return $this->db->read($sql, $params);
    }

    /** Get daily expense breakdown for a date range and optional kiosk */
    public function getDailyExpenseBreakdown(string $from_date, string $to_date, ?int $kiosk_id = null): array
    {
        $sql = "SELECT e.Expense_date, k.Name AS Kiosk_Name,
                       SUM(e.Amount) AS Day_Total,
                       COUNT(e.Expense_ID) AS Entries
                FROM Expenses e
                JOIN Kiosk k ON e.Kiosk_ID = k.Kiosk_ID
                WHERE e.Expense_date BETWEEN ? AND ?";
        $params = [$from_date, $to_date];

        if ($kiosk_id !== null) {
            $sql .= " AND e.Kiosk_ID = ?";
            $params[] = $kiosk_id;
        }

        $sql .= " GROUP BY e.Expense_date, e.Kiosk_ID
                   ORDER BY e.Expense_date DESC, k.Name";

        return $this->db->read($sql, $params);
    }

    /**
     * Get delivery summary for a date range and optional kiosk.
     *
     * REWRITTEN 2026-05 — was joining Delivery.Product_ID = Product.Product_ID,
     * which is always NULL post-parts-migration, so this returned 0 rows. Now
     * joins Delivery.Part_ID = Part.Part_ID and groups by part.
     *
     * View-compat note: result keeps the column name `Product_Name` (carrying
     * the part name) and a fixed 'Parts' Category_Name so consolidated.php
     * doesn't need a rewrite. `Type` ('Delivery' or 'Pullout') is included so
     * the report can distinguish incoming stock from pullouts.
     */
    public function getDeliverySummary(string $from_date, string $to_date, ?int $kiosk_id = null): array
    {
        $sql = "SELECT d.Delivery_Date, k.Name AS Kiosk_Name,
                       pt.Name AS Product_Name, 'Parts' AS Category_Name,
                       d.Type,
                       SUM(d.Quantity) AS Total_Qty
                FROM Delivery d
                JOIN Kiosk k ON d.Kiosk_ID = k.Kiosk_ID
                JOIN Part  pt ON d.Part_ID  = pt.Part_ID
                WHERE d.Delivery_Date BETWEEN ? AND ?
                  AND d.Part_ID IS NOT NULL";
        $params = [$from_date, $to_date];

        if ($kiosk_id !== null) {
            $sql .= " AND d.Kiosk_ID = ?";
            $params[] = $kiosk_id;
        }

        $sql .= " GROUP BY d.Delivery_Date, d.Kiosk_ID, d.Part_ID, d.Type
                   ORDER BY d.Delivery_Date DESC, k.Name, pt.Name, d.Type";

        return $this->db->read($sql, $params);
    }

    /** Get dates with missing beginning or ending snapshots (anomalies) */
    public function getMissingSnapshots(string $from_date, string $to_date): array
    {
        return $this->db->read(
            "SELECT k.Name AS Kiosk_Name, i.Snapshot_date,
                    SUM(CASE WHEN i.Snapshot_type = 'beginning' THEN 1 ELSE 0 END) AS has_beginning,
                    SUM(CASE WHEN i.Snapshot_type = 'ending' THEN 1 ELSE 0 END) AS has_ending
             FROM Kiosk k
             CROSS JOIN (
                 SELECT DISTINCT Snapshot_date FROM Inventory_Snapshot
                 WHERE Snapshot_date BETWEEN ? AND ?
             ) dates
             LEFT JOIN Inventory_Snapshot i ON k.Kiosk_ID = i.Kiosk_ID
                AND i.Snapshot_date = dates.Snapshot_date
             WHERE k.Active = 1
             GROUP BY k.Kiosk_ID, dates.Snapshot_date
             HAVING has_beginning = 0 OR has_ending = 0
             ORDER BY dates.Snapshot_date DESC, k.Name",
            [$from_date, $to_date]
        );
    }

    /**
     * SUBQUERY EXAMPLE #1
     * Return products whose total quantity sold in the given date range
     * is strictly above the system-wide average of per-product totals
     * within that same range. Uses a correlated-free scalar subquery in
     * the HAVING clause against a derived table of per-product sums.
     *
     * Output columns: Product_ID, Name, Category_Name, Total_Sold, Avg_Sold
     */
    public function getProductsAboveAverageSales(string $from_date, string $to_date): array
    {
        $sql = "SELECT p.Product_ID,
                       p.Name,
                       c.Name AS Category_Name,
                       SUM(s.Quantity_sold) AS Total_Sold,
                       (
                           SELECT AVG(per_product_total)
                           FROM (
                               SELECT SUM(s2.Quantity_sold) AS per_product_total
                               FROM Sales s2
                               WHERE s2.Sales_date BETWEEN ? AND ?
                               GROUP BY s2.Product_ID
                           ) avg_src
                       ) AS Avg_Sold
                FROM Sales s
                JOIN Product  p ON s.Product_ID = p.Product_ID
                JOIN Category c ON p.Category_ID = c.Category_ID
                WHERE s.Sales_date BETWEEN ? AND ?
                GROUP BY p.Product_ID, p.Name, c.Name
                HAVING Total_Sold > (
                    SELECT AVG(per_product_total)
                    FROM (
                        SELECT SUM(s3.Quantity_sold) AS per_product_total
                        FROM Sales s3
                        WHERE s3.Sales_date BETWEEN ? AND ?
                        GROUP BY s3.Product_ID
                    ) avg_src2
                )
                ORDER BY Total_Sold DESC";

        return $this->db->read($sql, [
            $from_date, $to_date,
            $from_date, $to_date,
            $from_date, $to_date,
        ]);
    }

    /**
     * SUBQUERY EXAMPLE #2
     * Return active kiosks that have NO ending snapshot recorded for the
     * given date. Uses a NOT IN subquery to exclude kiosks that already
     * closed the day's inventory.
     */
    public function getKiosksWithoutEndingSnapshot(string $date): array
    {
        $sql = "SELECT k.Kiosk_ID, k.Name, k.Location
                FROM Kiosk k
                WHERE k.Active = 1
                  AND k.Kiosk_ID NOT IN (
                      SELECT i.Kiosk_ID
                      FROM Inventory_Snapshot i
                      WHERE i.Snapshot_date = ?
                        AND i.Snapshot_type = 'ending'
                  )
                ORDER BY k.Name";

        return $this->db->read($sql, [$date]);
    }
}
