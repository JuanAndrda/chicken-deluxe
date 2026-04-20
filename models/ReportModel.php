<?php
/**
 * ReportModel — handles all reporting queries (read-only from Slave)
 */
class ReportModel extends Model
{
    /** Daily summary for a single kiosk on a specific date */
    public function getDailySummary(string $date, int $kiosk_id): array
    {
        // Beginning stock
        $beginning = $this->db->read(
            "SELECT i.Product_ID, p.Name AS Product_Name, c.Name AS Category_Name, p.Unit,
                    i.Quantity AS Beginning_Qty
             FROM Inventory_Snapshot i
             JOIN Product p ON i.Product_ID = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE i.Kiosk_ID = ? AND i.Snapshot_date = ? AND i.Snapshot_type = 'beginning'
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $date]
        );

        // Ending stock
        $ending = $this->db->read(
            "SELECT Product_ID, Quantity AS Ending_Qty
             FROM Inventory_Snapshot
             WHERE Kiosk_ID = ? AND Snapshot_date = ? AND Snapshot_type = 'ending'",
            [$kiosk_id, $date]
        );
        $ending_map = [];
        foreach ($ending as $e) {
            $ending_map[$e['Product_ID']] = $e['Ending_Qty'];
        }

        // Deliveries
        $deliveries = $this->db->read(
            "SELECT Product_ID, SUM(Quantity) AS Delivered_Qty
             FROM Delivery
             WHERE Kiosk_ID = ? AND Delivery_Date = ?
             GROUP BY Product_ID",
            [$kiosk_id, $date]
        );
        $delivery_map = [];
        foreach ($deliveries as $d) {
            $delivery_map[$d['Product_ID']] = $d['Delivered_Qty'];
        }

        // Sales
        $sales = $this->db->read(
            "SELECT Product_ID, SUM(Quantity_sold) AS Sold_Qty, SUM(Line_total) AS Sales_Total
             FROM Sales
             WHERE Kiosk_ID = ? AND Sales_date = ?
             GROUP BY Product_ID",
            [$kiosk_id, $date]
        );
        $sales_map = [];
        foreach ($sales as $s) {
            $sales_map[$s['Product_ID']] = $s;
        }

        // Combine into a single report
        $report = [];
        foreach ($beginning as $row) {
            $pid = $row['Product_ID'];
            $beg = (int) $row['Beginning_Qty'];
            $del = (int) ($delivery_map[$pid] ?? 0);
            $sold = (int) ($sales_map[$pid]['Sold_Qty'] ?? 0);
            $end = (int) ($ending_map[$pid] ?? 0);
            $expected = $beg + $del - $sold;
            $discrepancy = $end - $expected;

            $report[] = [
                'Product_Name'  => $row['Product_Name'],
                'Category_Name' => $row['Category_Name'],
                'Unit'          => $row['Unit'],
                'Beginning_Qty' => $beg,
                'Delivered_Qty' => $del,
                'Sold_Qty'      => $sold,
                'Sales_Total'   => (float) ($sales_map[$pid]['Sales_Total'] ?? 0),
                'Ending_Qty'    => $end,
                'Expected_Qty'  => $expected,
                'Discrepancy'   => $discrepancy,
            ];
        }

        return $report;
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

    /** Get delivery summary for a date range and optional kiosk */
    public function getDeliverySummary(string $from_date, string $to_date, ?int $kiosk_id = null): array
    {
        $sql = "SELECT d.Delivery_Date, k.Name AS Kiosk_Name,
                       p.Name AS Product_Name, c.Name AS Category_Name,
                       SUM(d.Quantity) AS Total_Qty
                FROM Delivery d
                JOIN Kiosk k ON d.Kiosk_ID = k.Kiosk_ID
                JOIN Product p ON d.Product_ID = p.Product_ID
                JOIN Category c ON p.Category_ID = c.Category_ID
                WHERE d.Delivery_Date BETWEEN ? AND ?";
        $params = [$from_date, $to_date];

        if ($kiosk_id !== null) {
            $sql .= " AND d.Kiosk_ID = ?";
            $params[] = $kiosk_id;
        }

        $sql .= " GROUP BY d.Delivery_Date, d.Kiosk_ID, d.Product_ID
                   ORDER BY d.Delivery_Date DESC, k.Name, c.Name, p.Name";

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
