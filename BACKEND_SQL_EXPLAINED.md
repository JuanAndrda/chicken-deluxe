# Chicken Deluxe — Backend SQL Explained

### Master-Slave Replication · SQL Queries · Triggers

> A teaching-style walkthrough of how the back end actually works in this
> codebase. Everything below uses the **real** file paths, class names,
> method names, column names, and trigger names that live in this project
> — not generic textbook examples.
>
> **Read order:** top to bottom. Each section explains *what* something is,
> *why* we need it, *where* it lives in the code, and *how* it actually
> runs end-to-end.

---

## Table of Contents

1. [Master-Slave Replication](#1-master-slave-replication)
2. [How PHP Connects to Master vs Slave](#2-how-php-connects-to-master-vs-slave)
3. [SQL Queries Used in the System](#3-sql-queries-used-in-the-system) (3.1–3.8)
4. [SQL Triggers](#4-sql-triggers)
5. [Putting It All Together — One Real Request](#5-putting-it-all-together--one-real-request)

---

## 1. Master-Slave Replication

### What it is

Master-Slave replication runs **two MySQL servers at the same time** on the same machine, with one acting as the writer and the other as the reader.

| Server | Role | Port | Job |
|---|---|---|---|
| **Master** | Writer | **3306** | Receives every `INSERT`, `UPDATE`, `DELETE` |
| **Slave** | Reader | **3307** | Gets an automatic real-time copy of all data |

**Plain-English analogy:** the **Master** is the original notebook where every record is written. The **Slave** is a photocopier glued to the Master — every line written into the notebook is automatically photocopied into a second notebook within milliseconds. The web app reads from the photocopy so the original isn't slowed down.

### Why we need it (in this project specifically)

1. **Performance** — the POS pages re-load the full product menu on every page render (~34 products × multiple JOINs). If reads and writes both hit the same DB, busy kiosks would slow each other down.
2. **Rubric requirement** — the school project rubric (§1.2 Master-Slave Architecture) explicitly requires it.
3. **Disaster-recovery shape** — if the Master ever dies, the Slave already has a near-real-time copy of every byte. Promoting the Slave to Master is a one-command move.
4. **Read-only enforcement at DB level** — the Slave is configured with `read_only=1`, so even a buggy controller or a curious staff member with phpMyAdmin can't accidentally write to the Slave node. Writes physically can only happen on the Master.

### How replication actually works (step by step)

```
[ Staff records a sale in the web app ]
              │
              ▼
     [ Master MySQL : 3306 ]
     INSERT INTO Sales ...
              │
              │ Master writes the event to its Binary Log (binlog)
              ▼
     [ Binary Log file: mysql-bin.000001 ]
       # event timestamp + SQL data
       INSERT INTO Sales (Kiosk_ID=1, …)
              │
              │ Slave's I/O Thread is constantly watching the Master
              ▼
     [ Slave I/O Thread ] ── connects to Master, reads new events
              │
              │ Copies each event into the Slave's Relay Log
              ▼
     [ Relay Log on Slave ]
              │
              │ Slave SQL Thread replays (re-executes) each event
              ▼
     [ Slave MySQL : 3307 ]
     Now has the exact same data as Master
```

**Three moving parts to remember:**

- **Binary Log** — Master's diary of every write
- **I/O Thread** — Slave's reader that watches the Master's diary
- **Relay Log + SQL Thread** — Slave's local copy of the diary, which it re-executes

### Where the configuration lives

We don't run two XAMPP installations — we run one normal XAMPP plus a second `mysqld` process pointed at a separate data directory.

| File | What's in it |
|---|---|
| `C:/xampp/mysql/bin/my.ini` | Master config — `server-id=1`, `log_bin=mysql-bin`, port `3306` |
| `C:/xampp/mysql_slave/bin/my.ini` | Slave config — `server-id=2`, `relay-log=mysql-relay-bin`, `read_only=1`, port `3307` |
| `C:/xampp/start_replication.bat` | Starts both `mysqld` processes + runs `START SLAVE;` to wake the I/O thread |
| `C:/xampp/stop_replication.bat` | Stops both processes cleanly |

**Critical Slave settings:**

```ini
[mysqld]
server-id=2                 # Must differ from Master's server-id
relay-log=mysql-relay-bin
read_only=1                 # Reject writes from non-SUPER users
                            # (SUPER users like root bypass this; the
                            # application enforces routing for them)
port=3307
```

### Verifying replication is alive

Run this on the Slave (port 3307):

```sql
SHOW SLAVE STATUS\G
```

Look for these three fields:

```
Slave_IO_Running:        Yes
Slave_SQL_Running:       Yes
Seconds_Behind_Master:   0
```

If `Seconds_Behind_Master` shows a number greater than 0, the Slave is lagging. If `Slave_SQL_Running` is `No`, replication is broken — usually because of a duplicate-key conflict on the Slave (something was written directly to the Slave that conflicts with what's coming from the Master).

---

## 2. How PHP Connects to Master vs Slave

### The two connection targets

**File: `config/constants.php`** — port numbers as named constants so both files agree:

```php
define('DB_MASTER_PORT', 3306);
define('DB_SLAVE_PORT',  3307);
```

**File: `config/database.php`** — returns one array describing both connections:

```php
return [
    'master' => [
        'host'     => '127.0.0.1',
        'port'     => DB_MASTER_PORT,
        'dbname'   => 'chicken_deluxe',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'slave' => [
        'host'     => '127.0.0.1',
        'port'     => DB_SLAVE_PORT,
        'dbname'   => 'chicken_deluxe',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
];
```

### The Database class — the one place that knows about Master vs Slave

**File: `core/Database.php`** — singleton wrapper around two PDO connections:

```php
class Database
{
    private static ?Database $instance = null;
    private PDO $master;
    private PDO $slave;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        // Build a DSN for each side, then open one PDO connection per side
        $this->master = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['master']['host'], $config['master']['port'],
                $config['master']['dbname'], $config['master']['charset']),
            $config['master']['username'], $config['master']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );

        $this->slave = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['slave']['host'], $config['slave']['port'],
                $config['slave']['dbname'], $config['slave']['charset']),
            $config['slave']['username'], $config['slave']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** SELECT — goes to the Slave */
    public function read(string $sql, array $params = []): array
    {
        $stmt = $this->slave->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** SELECT one row — goes to the Slave */
    public function readOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->slave->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** INSERT/UPDATE/DELETE — goes to the Master, returns affected row count */
    public function write(string $sql, array $params = []): int
    {
        $stmt = $this->master->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT — goes to the Master, returns the new auto-increment ID */
    public function insert(string $sql, array $params = []): int
    {
        $stmt = $this->master->prepare($sql);
        $stmt->execute($params);
        return (int) $this->master->lastInsertId();
    }

    /** Transactions always run on the Master */
    public function beginTransaction(): void { $this->master->beginTransaction(); }
    public function commit():           void { $this->master->commit(); }
    public function rollback():         void { $this->master->rollBack(); }
}
```

**The rule every model follows:**

| You call… | The query goes to… |
|---|---|
| `$this->db->read(...)` | **Slave** (3307) |
| `$this->db->readOne(...)` | **Slave** (3307) |
| `$this->db->write(...)` | **Master** (3306) |
| `$this->db->insert(...)` | **Master** (3306) |
| `$this->db->beginTransaction()` / `commit()` / `rollback()` | **Master** (3306) |

### How models get the Database instance — the Model base class

**File: `core/Model.php`** — every model extends this and gets `$this->db` for free:

```php
class Model
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
```

So every model just does `class SalesModel extends Model { ... }` and it can use `$this->db->read(...)` etc. without importing or wiring anything.

### Real example — `SalesModel`

**File: `models/SalesModel.php`** — three actual methods showing all three styles:

```php
class SalesModel extends Model
{
    /** READ — goes to the Slave */
    public function getByDateAndKiosk(string $date, int $kiosk_id): array
    {
        return $this->db->read(
            "SELECT s.*, p.Name AS Product_Name, p.Unit, c.Name AS Category_Name,
                    u.Full_name AS Recorded_by
             FROM Sales s
             JOIN Product  p ON s.Product_ID  = p.Product_ID
             JOIN Category c ON p.Category_ID = c.Category_ID
             JOIN User     u ON s.User_ID     = u.User_ID
             WHERE s.Kiosk_ID = ? AND s.Sales_date = ?
             ORDER BY c.Name, p.Name",
            [$kiosk_id, $date]
        );
    }

    /** WRITE one row — goes to the Master.
     *  Notice we never insert Line_total — a trigger fills it in (Section 4). */
    public function create(int $kiosk_id, int $user_id, int $product_id,
                           string $date, int $quantity_sold, float $unit_price): int
    {
        return $this->db->insert(
            "INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date,
                                Quantity_sold, Unit_Price)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$kiosk_id, $user_id, $product_id, $date, $quantity_sold, $unit_price]
        );
    }

    /** BATCH WRITE — goes to the Master inside a transaction so the whole
     *  POS cart either fully saves or fully rolls back. */
    public function createBatch(int $kiosk_id, int $user_id,
                                string $date, array $items): int
    {
        if (empty($items)) return 0;

        $this->db->beginTransaction();
        $count = 0;
        try {
            foreach ($items as $item) {
                $this->db->insert(
                    "INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date,
                                        Quantity_sold, Unit_Price)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$kiosk_id, $user_id, (int) $item['product_id'],
                     $date, (int) $item['quantity_sold'], (float) $item['unit_price']]
                );
                $count++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        return $count;
    }
}
```

**Key insight:** after `commit()` finishes on the Master, the Master writes the change to its binary log. The Slave's I/O thread sees the new event within milliseconds and applies it. So by the time the page redirects and re-reads from the Slave, the new data is already there.

### What about the `?` placeholders?

We use **prepared statements** — the SQL has `?` for each value, and PHP passes the actual values as a separate array:

```php
$this->db->read("... WHERE Kiosk_ID = ? AND Sales_date = ?", [$kiosk_id, $date]);
```

PDO substitutes the values safely before sending the query to MySQL. This is what blocks **SQL injection** — even if `$kiosk_id` came from a malicious user as `1; DROP TABLE Sales; --`, PDO treats it as a single string value, not as SQL.

---

## 3. SQL Queries Used in the System

This section shows the real SQL patterns this project uses, with the actual file each one lives in.

### 3.1 SELECT with JOINs — the workhorse

Every "list" page joins the operational table with `Product`, `Category`, and `User` so the view shows readable names instead of just IDs.

**Where:** `models/DeliveryModel.php` → `getByDateAndKiosk()`

```sql
SELECT d.*,
       p.Name      AS Product_Name,
       p.Unit,
       c.Name      AS Category_Name,
       u.Full_name AS Recorded_by
FROM   Delivery d
JOIN   Product  p ON d.Product_ID  = p.Product_ID
JOIN   Category c ON p.Category_ID = c.Category_ID
JOIN   User     u ON d.User_ID     = u.User_ID
WHERE  d.Kiosk_ID      = ?
  AND  d.Delivery_Date = ?
ORDER  BY d.Created_at DESC;
```

**Why JOINs?** The `Delivery` row only stores `Product_ID = 7` and `User_ID = 12`. To show "Burger Patty delivered by Maria Santos" the query has to follow those IDs to the `Product`, `Category`, and `User` tables.

### 3.2 INSERT — recording a new row

**Where:** `models/ExpenseModel.php` → `create()`

```sql
INSERT INTO Expenses (Kiosk_ID, User_ID, Expense_date, Amount, Description)
VALUES (?, ?, ?, ?, ?);
```

PHP wraps it with `$this->db->insert(...)` which returns the new `Expense_ID`.

### 3.3 UPDATE with built-in safety check

**Where:** `models/DeliveryModel.php` → `updateQuantity()`

```sql
UPDATE Delivery
SET    Quantity      = ?
WHERE  Delivery_ID   = ?
  AND  Locked_status = 0;   -- safety: locked rows are silently skipped
```

If the row is already locked, the `WHERE` clause won't match, so MySQL updates 0 rows — no error, just no change. The controller checks `rowCount()` and shows the user a friendly "Cannot update a locked record" message. (And the trigger in §4.3 is a second wall — even if this WHERE somehow let the update through, the trigger would still raise an error.)

### 3.4 Bulk UPDATE — "Lock All for the day"

**Where:** `models/DeliveryModel.php` → `lockByDate()`

```sql
UPDATE Delivery
SET    Locked_status = 1
WHERE  Kiosk_ID      = ?
  AND  Delivery_Date = ?
  AND  Locked_status = 0;   -- only flip rows that are currently open
```

### 3.5 Aggregate queries — totals and breakdowns

**Where:** `models/SalesModel.php` → `getDailyTotalByKiosk()`

```sql
SELECT k.Name        AS Kiosk_Name,
       k.Kiosk_ID,
       COALESCE(SUM(s.Line_total), 0) AS Day_Total
FROM   Kiosk k
LEFT JOIN Sales s ON k.Kiosk_ID = s.Kiosk_ID AND s.Sales_date = ?
WHERE  k.Active = 1
GROUP  BY k.Kiosk_ID, k.Name
ORDER  BY k.Kiosk_ID;
```

**Why `LEFT JOIN`?** A regular `JOIN` would drop kiosks that had zero sales for the day. `LEFT JOIN` keeps every active kiosk in the result and uses `NULL` for sales totals where there are none — `COALESCE(..., 0)` then turns those `NULL`s into `0` so the dashboard shows `₱0.00` instead of an awkward blank cell.

**`SUM`** adds up all the `Line_total` values for that kiosk on that date. **`GROUP BY`** says "give me one row per Kiosk_ID + Name combination" so the result is ready to render as a per-kiosk strip.

### 3.6 The big one — `getRunningInventory` (live perpetual-inventory math)

**Where:** `models/InventoryModel.php` → `getRunningInventory()`

This computes, in one query, the formula
**`running stock = beginning + delivered − sold`** for every product the kiosk stocked today:

```sql
SELECT i.Product_ID,
       p.Name AS Product_Name,
       p.Unit,
       c.Name AS Category_Name,
       i.Quantity AS Beginning_Qty,
       COALESCE(d_sum.delivered, 0) AS Delivered_Qty,
       COALESCE(s_sum.sold, 0)      AS Sold_Qty,
       (i.Quantity + COALESCE(d_sum.delivered, 0)
                   - COALESCE(s_sum.sold, 0)) AS Running_Qty
FROM   Inventory_Snapshot i
JOIN   Product  p ON i.Product_ID  = p.Product_ID
JOIN   Category c ON p.Category_ID = c.Category_ID
LEFT JOIN (
    SELECT Product_ID, SUM(Quantity) AS delivered
    FROM   Delivery
    WHERE  Kiosk_ID = ? AND Delivery_Date = ?
    GROUP  BY Product_ID
) d_sum ON d_sum.Product_ID = i.Product_ID
LEFT JOIN (
    SELECT Product_ID, SUM(Quantity_sold) AS sold
    FROM   Sales
    WHERE  Kiosk_ID = ? AND Sales_date = ?
    GROUP  BY Product_ID
) s_sum ON s_sum.Product_ID = i.Product_ID
WHERE  i.Kiosk_ID      = ?
  AND  i.Snapshot_date = ?
  AND  i.Snapshot_type = 'beginning'
ORDER  BY c.Name, p.Name;
```

**Why this query is interesting:**

1. **Two derived tables** (`d_sum` and `s_sum`) pre-compute the totals from `Delivery` and `Sales` per product. Without them, joining directly to `Delivery` and `Sales` would multiply rows (one beginning row × N deliveries × N sales = a Cartesian explosion).
2. **`COALESCE(..., 0)`** handles products that have a beginning snapshot but no deliveries or no sales yet — without it the math would be `15 + NULL − NULL = NULL`.
3. **`Snapshot_type = 'beginning'`** — note the lowercase enum value. Our column uses MySQL's `ENUM('beginning','ending')` so the database itself rejects typos like `'Begining'`.

### 3.7 Parts-based running inventory — `getRunningPartsInventory`

**Where:** `models/InventoryModel.php` → `getRunningPartsInventory()`

This is the **active inventory formula** used since April 2026. It is the parts-based equivalent of `getRunningInventory()` and now also powers the **Stock column on the Delivery page**:

```sql
SELECT
    pt.Part_ID,
    pt.Name                              AS Part_Name,
    pt.Unit,
    COALESCE(beg.Quantity, 0)            AS Beginning_Qty,
    COALESCE(d.delivered, 0)             AS Delivered_Qty,
    COALESCE(po.pulled_out, 0)           AS Pullout_Qty,
    COALESCE(consumed.used_qty, 0)       AS Used_Qty,
    (COALESCE(beg.Quantity, 0)
     + COALESCE(d.delivered, 0)
     - COALESCE(po.pulled_out, 0)
     - COALESCE(consumed.used_qty, 0))   AS Running_Qty
FROM Part pt
INNER JOIN Inventory_Snapshot beg
       ON beg.Part_ID = pt.Part_ID AND beg.Kiosk_ID = ?
      AND beg.Snapshot_date = ? AND beg.Snapshot_type = 'beginning'
LEFT JOIN (SELECT Part_ID, SUM(Quantity) AS delivered
           FROM Delivery WHERE Kiosk_ID=? AND Delivery_Date=?
             AND Part_ID IS NOT NULL AND Type='Delivery'
           GROUP BY Part_ID) d ON d.Part_ID = pt.Part_ID
LEFT JOIN (SELECT Part_ID, SUM(Quantity) AS pulled_out
           FROM Delivery WHERE Kiosk_ID=? AND Delivery_Date=?
             AND Part_ID IS NOT NULL AND Type='Pullout'
           GROUP BY Part_ID) po ON po.Part_ID = pt.Part_ID
LEFT JOIN (SELECT pp.Part_ID,
                  SUM(s.Quantity_sold * pp.Quantity_needed) AS used_qty
           FROM Sales s JOIN Product_Part pp ON s.Product_ID = pp.Product_ID
           WHERE s.Kiosk_ID=? AND s.Sales_date=?
           GROUP BY pp.Part_ID) consumed ON consumed.Part_ID = pt.Part_ID
WHERE pt.Active = 1
ORDER BY pt.Name
```

**Key differences from `getRunningInventory()`:**
- Joins `Part` not `Product` — tracks ingredients, not finished menu items
- Splits Delivery by `Type`: `'Delivery'` adds stock, `'Pullout'` removes it
- Consumption uses `Product_Part.Quantity_needed` to convert sales into parts used
- `DeliveryController` calls this to build `part_stock` (Part_ID → Running_Qty map), passed to the view so the Select Part table shows live stock

---

### 3.8 Subqueries — the rubric §1.4 highlights

**Where:** `models/ReportModel.php` → `getProductsAboveAverageSales()`

This finds products whose total quantity sold is **above the per-product average** for the date range. It uses **derived tables in a `HAVING` clause**:

```sql
SELECT p.Product_ID, p.Name, c.Name AS Category_Name,
       SUM(s.Quantity_sold) AS Total_Sold,
       (SELECT AVG(per_product_total)
        FROM (SELECT SUM(s2.Quantity_sold) AS per_product_total
              FROM   Sales s2
              WHERE  s2.Sales_date BETWEEN ? AND ?
              GROUP  BY s2.Product_ID) avg_src) AS Avg_Sold
FROM   Sales s
JOIN   Product  p ON s.Product_ID  = p.Product_ID
JOIN   Category c ON p.Category_ID = c.Category_ID
WHERE  s.Sales_date BETWEEN ? AND ?
GROUP  BY p.Product_ID, p.Name, c.Name
HAVING Total_Sold > (
    SELECT AVG(per_product_total)
    FROM (SELECT SUM(s3.Quantity_sold) AS per_product_total
          FROM   Sales s3
          WHERE  s3.Sales_date BETWEEN ? AND ?
          GROUP  BY s3.Product_ID) avg_src2
)
ORDER  BY Total_Sold DESC;
```

**Where:** `models/ReportModel.php` → `getKiosksWithoutEndingSnapshot()`

This finds kiosks that haven't recorded their ending snapshot yet for a given date — uses a **`NOT IN` subquery**:

```sql
SELECT k.Kiosk_ID, k.Name, k.Location
FROM   Kiosk k
WHERE  k.Active = 1
  AND  k.Kiosk_ID NOT IN (
      SELECT i.Kiosk_ID
      FROM   Inventory_Snapshot i
      WHERE  i.Snapshot_date = ?
        AND  i.Snapshot_type = 'ending'
  );
```

**The pattern:** the inner `SELECT` runs first and produces a list of kiosk IDs that *do* have an ending snapshot. The outer query then keeps the active kiosks whose ID is **not** in that list. Both subquery results power the **⭐ Top Performers** and **Anomalies** tabs on `/reports/consolidated`.

---

## 4. SQL Triggers

### What a trigger is

A trigger is a piece of SQL that **MySQL runs automatically** whenever a specific event happens on a table. PHP doesn't call it — MySQL fires it on its own.

```
[ PHP sends INSERT INTO Sales ... ]
              │
              ▼
   [ MySQL receives the INSERT ]
              │
              │ MySQL automatically fires every trigger registered on Sales
              ▼
   [ trg_calc_line_total_insert runs ]
       SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price
              │
              ▼
   [ Row gets saved with Line_total already filled in ]
              │
              ▼
   [ trg_log_sales_insert runs ]
       INSERT INTO Audit_Log (...)
```

**Trigger anatomy:**

```sql
CREATE TRIGGER trigger_name
    BEFORE INSERT          -- when:  BEFORE or AFTER, then INSERT/UPDATE/DELETE
    ON Sales               -- which table
    FOR EACH ROW           -- runs once per affected row
BEGIN
    -- SQL to run automatically
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END;
```

- **`NEW`** — the row being inserted or the new version of an updated row
- **`OLD`** — the row before it was updated or deleted
- **`BEFORE`** — runs *before* the row is saved, so you can modify `NEW.*`
- **`AFTER`** — runs *after* the row is saved (used for logging, since by then the row already has its auto-increment PK)

**All triggers in this project live in `sql/triggers.sql` and are installed on the Master only.** They replicate to the Slave automatically through the binary log, so both servers have identical triggers.

### 4.1 Trigger — auto-calculate `Line_total` on Sales

**Name:** `trg_calc_line_total_insert` (and a sibling `trg_calc_line_total_update` for edits)
**Why we need it:** staff should never type the line total. Letting the database compute it removes a whole class of fraud and typos.

```sql
DROP TRIGGER IF EXISTS trg_calc_line_total_insert;
DELIMITER $$
CREATE TRIGGER trg_calc_line_total_insert
BEFORE INSERT ON Sales
FOR EACH ROW
BEGIN
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END$$
DELIMITER ;
```

**Why `BEFORE INSERT`?** Because we set `NEW.Line_total` *before* the row is written to disk. If we used `AFTER INSERT`, we'd have to issue a separate `UPDATE` to fix the row, which is messier and slower.

**Notice in `SalesModel::create()`** that we never include `Line_total` in the column list — the trigger fills it in:

```php
"INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date,
                    Quantity_sold, Unit_Price)  -- ← no Line_total here
 VALUES (?, ?, ?, ?, ?, ?)"
```

### 4.2 Triggers — auto-log every change to Audit_Log (the "change-logging" set)

**Names:** `trg_log_sales_insert`, `trg_log_sales_update`, `trg_log_sales_delete`, plus the same `_insert/_update/_delete` for **Inventory_Snapshot, Delivery, Expenses, Product, User, Kiosk** = **21 triggers total**.

**Why we need them:** the rubric §1.3 demands change logging with old + new values. More importantly, even if a future developer forgets to call `auditLog->log(...)` in a controller, the database itself still records the change. Defense in depth.

**Example — `trg_log_sales_update`** (this is exactly what's in `sql/triggers.sql`):

```sql
DELIMITER $$
CREATE TRIGGER trg_log_sales_update
AFTER UPDATE ON Sales
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log
        (User_ID, Action, Operation, Table_name,
         Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'UPDATE', 'UPDATE', 'Sales',
        JSON_OBJECT('Sales_ID', OLD.Sales_ID, 'Quantity_sold', OLD.Quantity_sold,
                    'Unit_Price', OLD.Unit_Price, 'Line_total', OLD.Line_total,
                    'Locked_status', OLD.Locked_status),
        JSON_OBJECT('Sales_ID', NEW.Sales_ID, 'Quantity_sold', NEW.Quantity_sold,
                    'Unit_Price', NEW.Unit_Price, 'Line_total', NEW.Line_total,
                    'Locked_status', NEW.Locked_status),
        CONCAT('Record updated in Sales ID:', NEW.Sales_ID),
        NOW());
END$$
DELIMITER ;
```

**`JSON_OBJECT(...)`** packages all the relevant column values into a JSON string. That's exactly what the **expand button on the Audit Log page** opens up — the OLD vs NEW JSON diff comes straight from these columns.

**Important security exclusion:** the User triggers deliberately leave the `Password` column out of the `JSON_OBJECT` calls — bcrypt hashes should never end up in the audit trail.

### 4.3 Triggers — block edits on locked records

**Names:** `trg_prevent_locked_sales_edit`, `trg_prevent_locked_inventory_edit`, `trg_prevent_locked_delivery_edit`, `trg_prevent_locked_expense_edit` = **4 triggers**.

**Why we need them:** the locking rule is the heart of the audit story — once a day is closed, those rows must never change. PHP already enforces this in its `WHERE Locked_status = 0` clauses, but a buggy controller, a phpMyAdmin session, or a future developer could bypass that. The trigger makes the rule absolute at the database level.

```sql
DELIMITER $$
CREATE TRIGGER trg_prevent_locked_sales_edit
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
    -- Block the update only if the row was locked AND the update keeps it locked.
    -- (Unlocks 1 → 0 are explicitly allowed so the Owner can still unlock.)
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked sales record.';
    END IF;
END$$
DELIMITER ;
```

**`SIGNAL SQLSTATE '45000'`** is how a trigger says "throw an error and cancel this query." `45000` is MySQL's generic user-defined error code. The whole UPDATE is rolled back and the error message bubbles up to PHP as a `PDOException`.

### 4.4 Trigger removed — `trg_audit_inventory_unlock`

This used to be an `AFTER UPDATE` trigger on `Inventory_Snapshot` that wrote a `RECORD_UNLOCKED` row to `Audit_Log` whenever `Locked_status` flipped from `1` to `0`.

**Removed 2026-05** because it wrote a half-empty row (Operation/Table_name/Old_values/New_values were left NULL even though `Audit_Log` has those columns) AND duplicated with `trg_log_inventory_snapshot_update` which already captures every unlock — including the `Locked_status: 1 → 0` transition — with full JSON payload.

Net effect: every unlock used to generate **3** Audit_Log rows (this trigger + the change-logging trigger + the controller's `auditLog->log()` call). After removal, it's **2** rows, both with full data.

### All 33 triggers at a glance

| # | Trigger | Table | Event | Job |
|---|---|---|---|---|
| 1 | `trg_calc_line_total_insert` | Sales | BEFORE INSERT | Auto-compute `Line_total` |
| 2 | `trg_calc_line_total_update` | Sales | BEFORE UPDATE | Recompute `Line_total` if qty/price edited |
| 3 | `trg_prevent_locked_sales_edit` | Sales | BEFORE UPDATE | Block edits on locked Sales |
| 4 | `trg_prevent_locked_inventory_edit` | Inventory_Snapshot | BEFORE UPDATE | Block edits on locked Inventory |
| 5 | `trg_prevent_locked_delivery_edit` | Delivery | BEFORE UPDATE | Block edits on locked Delivery |
| 6 | `trg_prevent_locked_expense_edit` | Expenses | BEFORE UPDATE | Block edits on locked Expenses |
| 7 – 9 | `trg_log_sales_{insert,update,delete}` | Sales | AFTER INSERT/UPDATE/DELETE | Change-log to Audit_Log |
| 10 – 12 | `trg_log_inventory_snapshot_{insert,update,delete}` | Inventory_Snapshot | AFTER ... | Change-log |
| 13 – 15 | `trg_log_delivery_{insert,update,delete}` | Delivery | AFTER ... | Change-log |
| 16 – 18 | `trg_log_expenses_{insert,update,delete}` | Expenses | AFTER ... | Change-log |
| 19 – 21 | `trg_log_product_{insert,update,delete}` | Product | AFTER ... | Change-log |
| 22 – 24 | `trg_log_user_{insert,update,delete}` | User | AFTER ... | Change-log (Password excluded) |
| 25 – 27 | `trg_log_kiosk_{insert,update,delete}` | Kiosk | AFTER ... | Change-log |
| 28 – 30 | `trg_log_part_{insert,update,delete}` | Part | AFTER ... | Change-log |
| 31 – 33 | `trg_log_product_part_{insert,update,delete}` | Product_Part | AFTER ... | Change-log (UPDATE added 2026-05) |

**Categories:** 6 business-rule triggers (1–6) + 27 change-logging triggers = **33 total**. (Adds Part [3] and Product_Part [3] to the original 7-table change-logging set.)

### How to inspect triggers in MySQL yourself

```sql
-- All triggers in the database
SHOW TRIGGERS FROM chicken_deluxe;

-- Full source of one specific trigger
SHOW CREATE TRIGGER trg_calc_line_total_insert;

-- Cleaner detail via information_schema
SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING
FROM   INFORMATION_SCHEMA.TRIGGERS
WHERE  TRIGGER_SCHEMA = 'chicken_deluxe'
ORDER  BY EVENT_OBJECT_TABLE, ACTION_TIMING;
```

### Caveat about bulk operations

Because the change-logging triggers fire on **every** INSERT/UPDATE/DELETE, a bulk seed or bulk reset would generate hundreds of `Audit_Log` noise rows. That's why `sql/demo_reset.sql` and `sql/demo_seed.sql` truncate `Audit_Log` at the very end of their execution — to wipe the trigger noise and leave a clean slate.

`TRUNCATE TABLE` does **not** fire DML triggers in MySQL, which is why those scripts can wipe the transactional tables freely without producing log entries from the truncates themselves.

---

## 5. Putting It All Together — One Real Request

Here's the full end-to-end flow when a staff member submits a POS cart:

```
 1. Staff taps products → JS cart object builds up the order
        (views/sales/index.php — inline cart logic)

 2. Staff clicks "Confirm Order ✓"
    → showConfirmModal() shows a confirmation dialog

 3. Staff confirms
    → submitCart() builds a hidden form and POSTs:
       POST /sales/store-batch
       body: csrf_token=…&date=2026-04-26&kiosk_id=1
             &product_ids[]=1&quantities[]=2
             &product_ids[]=3&quantities[]=1
             …

 4. Router (index.php) dispatches to:
    SalesController::storeBatch()
    → Auth::requireRole([ROLE_OWNER, ROLE_STAFF])
    → Auth::validateCsrf()
    → isFutureDate($date) check
    → resolveKiosk() to confirm staff's kiosk

 5. For each product_id, the controller looks up the unit price:
    $product = $this->productModel->findById($id);
    → $this->db->readOne(...) → SLAVE 3307
    (price never trusted from the form)

 6. SalesModel::createBatch() runs:
    → $this->db->beginTransaction()  → MASTER 3306
    → for each item:
         INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID,
                            Sales_date, Quantity_sold, Unit_Price)
         VALUES (?, ?, ?, ?, ?, ?)
       — note: no Line_total in the columns

 7. MASTER fires triggers for EACH inserted row:
    → trg_calc_line_total_insert (BEFORE INSERT)
        sets NEW.Line_total = qty * price
    → trg_log_sales_insert (AFTER INSERT)
        inserts a JSON snapshot into Audit_Log

 8. $this->db->commit() finalizes the transaction on MASTER.

 9. MASTER writes the committed events to its binary log.

10. SLAVE I/O thread reads the new events from MASTER's binlog
    → copies them to the SLAVE's relay log
    → SLAVE SQL thread re-executes the INSERTs
       (entire propagation completes in milliseconds)

11. PHP redirects:
    /sales?date=2026-04-26&kiosk_id=1&success=Order+confirmed+(N+items+saved)

12. Browser reloads the sales page:
    → SalesModel::getByDateAndKiosk()
    → $this->db->read(...) → SLAVE 3307
    → the new rows are already there
    → Records tab shows the confirmed order with computed line totals
```

Notice how **every layer enforces the same business rule**:
- Application: `isFutureDate()` check, role check, CSRF check
- Model: prepared statements, `Locked_status = 0` in WHERE clauses
- Trigger: `SIGNAL '45000'` for locked-row edits, `JSON_OBJECT` for change logs
- Database: `read_only=1` on the Slave, `ENUM` on `Snapshot_type`, foreign keys, unique key on `(Kiosk_ID, Product_ID, Snapshot_date, Snapshot_type)`

That's defense in depth — bypass any one of those layers and there are still others standing in the way.

---

## Summary Cheat Sheet

| Concept | What it is | Where to look in the code |
|---|---|---|
| **Master** | MySQL on port 3306, all writes | `config/database.php` → `'master'` |
| **Slave** | MySQL on port 3307, all reads | `config/database.php` → `'slave'` |
| **Binary Log** | Master's diary of every write | Master's `my.ini` `log_bin=mysql-bin` |
| **Replication** | Slave auto-copies Master | `SHOW SLAVE STATUS\G` to verify |
| **`$this->db->read()`** | SELECT routed to Slave | `core/Database.php` |
| **`$this->db->write()`** | UPDATE/DELETE routed to Master | `core/Database.php` |
| **`$this->db->insert()`** | INSERT + last ID, routed to Master | `core/Database.php` |
| **Transaction** | All-or-nothing block of writes | `beginTransaction/commit/rollback` |
| **`?` placeholders** | Prepared statements (anti-injection) | Every model query |
| **BEFORE trigger** | Modifies the row before save | `trg_calc_line_total_*` |
| **AFTER trigger** | Runs after save (used for logging) | All `trg_log_*` |
| **`NEW`** | New row data inside a trigger | `NEW.Line_total`, `NEW.Quantity_sold` |
| **`OLD`** | Pre-update / pre-delete row data | `OLD.Locked_status` |
| **`SIGNAL '45000'`** | Throw an error and cancel the query | `trg_prevent_locked_*_edit` |
| **`JSON_OBJECT`** | Pack columns into a JSON string for logging | All `trg_log_*` |
| **`COALESCE(x, 0)`** | Use 0 when `x` is NULL | Running-inventory query |
| **`ENUM`** | Restrict a column to a fixed set of values | `Inventory_Snapshot.Snapshot_type` |

---

*Chicken Deluxe Inventory & Sales Monitoring System — Group 7 — BSIT 2-B*
*Systems Analysis & Design + Information Management — May 2026*
