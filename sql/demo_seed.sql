-- ============================================================
-- demo_seed.sql — Parts-based realistic demo data for all 5 kiosks
--
-- Seeds:
--   * 5 staff users (one per kiosk) — INSERT IGNORE so re-runnable
--   * Yesterday: full operational day with parts-based inventory,
--                deliveries, sales, expenses, time-in punches.
--                ALL records LOCKED.
--   * Today:     mid-day partial state — carry-forward parts beginning,
--                some morning deliveries, sales, expenses, active
--                time-in punches (no time_out yet). ALL records OPEN.
--   * ~12 sample audit-log entries
--
-- Run AFTER demo_reset.sql.  Run on Master only (replicates to slave).
-- Idempotency: INSERTs duplicate if run twice — re-run demo_reset.sql
-- first to start clean.
-- ============================================================

USE chicken_deluxe;

-- ---------- date / id helpers ----------
SET @y = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
SET @t = CURDATE();
SET @owner_id = (SELECT User_ID FROM User WHERE Username = 'owner' LIMIT 1);

-- ============================================================
-- PART 1 — STAFF USERS (one per kiosk)
-- bcrypt('staff1234', cost=10)
-- INSERT IGNORE prevents duplicates if seed is re-run on existing users
-- ============================================================
INSERT IGNORE INTO User (Role_ID, Kiosk_ID, Username, Password, Full_name, Active_status) VALUES
    (2, 1, 'staff_k1', '$2y$10$CHpV8JW1S0X3aAvjDIJtu.HgapDl9CJk/b0uNi/rce4suE7FmVN1i', 'Maria Santos',  1),
    (2, 2, 'staff_k2', '$2y$10$CHpV8JW1S0X3aAvjDIJtu.HgapDl9CJk/b0uNi/rce4suE7FmVN1i', 'Jose Reyes',    1),
    (2, 3, 'staff_k3', '$2y$10$CHpV8JW1S0X3aAvjDIJtu.HgapDl9CJk/b0uNi/rce4suE7FmVN1i', 'Ana Flores',    1),
    (2, 4, 'staff_k4', '$2y$10$CHpV8JW1S0X3aAvjDIJtu.HgapDl9CJk/b0uNi/rce4suE7FmVN1i', 'Rico Mendoza',  1),
    (2, 5, 'staff_k5', '$2y$10$CHpV8JW1S0X3aAvjDIJtu.HgapDl9CJk/b0uNi/rce4suE7FmVN1i', 'Liza Cruz',     1);

SET @u1 = (SELECT User_ID FROM User WHERE Username = 'staff_k1');
SET @u2 = (SELECT User_ID FROM User WHERE Username = 'staff_k2');
SET @u3 = (SELECT User_ID FROM User WHERE Username = 'staff_k3');
SET @u4 = (SELECT User_ID FROM User WHERE Username = 'staff_k4');
SET @u5 = (SELECT User_ID FROM User WHERE Username = 'staff_k5');

-- ============================================================
-- PART 2 — YESTERDAY (Locked_status = 1, full operational day)
-- ============================================================

-- ---------- 2A. Yesterday — Beginning parts inventory ----------
-- Each kiosk has a "size factor": Tagbak (1) and Supermart (4) are high
-- traffic, Atrium (2) is medium, City Proper (3) is lower, Aldeguer (5)
-- is small.  Beginning quantities scale by part class × kiosk size.
INSERT INTO Inventory_Snapshot
    (Kiosk_ID, Part_ID, User_ID, Snapshot_date, Snapshot_type, Quantity, Locked_status)
SELECT
    k.Kiosk_ID,
    pt.Part_ID,
    k.user_id,
    @y,
    'beginning',
    -- Quantity model: base × kiosk_factor, rounded
    CAST(ROUND(
        CASE
            -- Heavy-use parts (in many recipes)
            WHEN pt.Name IN ('Burger Bun','Burger Patty','Rice','Rice Cup') THEN 80
            WHEN pt.Name IN ('Cheese Slice','Lettuce','Tomato','Onion','Egg') THEN 60
            WHEN pt.Name IN ('Hotdog','Hungarian Sausage','Lumpia','Siomai','Sisig') THEN 40
            -- Snack / per-stick items
            WHEN pt.Name IN ('Kikiam','Fish Balls','Canton','Siopao','Fries','Ham Slice') THEN 30
            -- Drinks (per bottle / can)
            WHEN pt.Name LIKE '%Bottle' OR pt.Name LIKE '%Swakto' OR pt.Name = 'Sting' THEN 24
            -- Coffee / matcha bases / syrup
            WHEN pt.Name LIKE '%Base' OR pt.Name LIKE '%Syrup' THEN 20
            ELSE 15
        END * k.size_factor
    ) AS UNSIGNED),
    1
FROM (
    SELECT 1 AS Kiosk_ID, @u1 AS user_id, 1.0  AS size_factor UNION ALL
    SELECT 2, @u2, 0.8 UNION ALL
    SELECT 3, @u3, 0.6 UNION ALL
    SELECT 4, @u4, 1.0 UNION ALL
    SELECT 5, @u5, 0.5
) k
CROSS JOIN Part pt
WHERE pt.Active = 1;

-- ---------- 2B. Yesterday — Deliveries (parts replenishment) ----------
-- 3 deliveries per kiosk yesterday morning — top-up of high-volume parts
INSERT INTO Delivery (Kiosk_ID, User_ID, Part_ID, Delivery_Date, Quantity, Type, Locked_status, Created_at)
SELECT k.Kiosk_ID, k.user_id, pt.Part_ID, @y, q.qty, 'Delivery', 1,
       CONCAT(@y, ' 07:30:00')
FROM (
    SELECT 1 AS Kiosk_ID, @u1 AS user_id UNION ALL
    SELECT 2, @u2 UNION ALL
    SELECT 3, @u3 UNION ALL
    SELECT 4, @u4 UNION ALL
    SELECT 5, @u5
) k
CROSS JOIN (
    SELECT 'Burger Bun'   AS pname, 30 AS qty UNION ALL
    SELECT 'Burger Patty', 30 UNION ALL
    SELECT 'Rice',         20
) q
JOIN Part pt ON pt.Name = q.pname;

-- ---------- 2C. Yesterday — Sales (varied product mix) ----------
-- 8-12 transactions per kiosk, scaled by size_factor — these auto-deduct
-- parts from running inventory at view time via Sales × Product_Part.
INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date, Quantity_sold, Unit_Price, Locked_status, Created_at)
SELECT k.Kiosk_ID, k.user_id, p.Product_ID, @y, s.qty, p.Price, 1,
       CONCAT(@y, ' ', s.hhmm)
FROM (
    SELECT 1 AS Kiosk_ID, @u1 AS user_id UNION ALL
    SELECT 2, @u2 UNION ALL
    SELECT 3, @u3 UNION ALL
    SELECT 4, @u4 UNION ALL
    SELECT 5, @u5
) k
CROSS JOIN (
    -- 10 sample transactions per kiosk — common menu items
    SELECT 'All Around Burger'   AS pname, 3 AS qty, '09:15:00' AS hhmm UNION ALL
    SELECT 'Burger with Cheese',   2, '09:45:00' UNION ALL
    SELECT 'Hungarian Hotdog',     4, '10:20:00' UNION ALL
    SELECT 'Cup of Rice',          5, '10:30:00' UNION ALL
    SELECT 'Lumpia Bowl',          2, '11:00:00' UNION ALL
    SELECT 'Siomai Bowl',          3, '11:30:00' UNION ALL
    SELECT 'Coke 500ml',           4, '12:00:00' UNION ALL
    SELECT 'Iced Coffee',          3, '13:15:00' UNION ALL
    SELECT 'Siomai',               6, '14:00:00' UNION ALL
    SELECT 'Fries',                3, '15:30:00'
) s
JOIN Product p ON p.Name = s.pname
WHERE p.Active = 1;

-- ---------- 2D. Yesterday — Expenses ----------
INSERT INTO Expenses (Kiosk_ID, User_ID, Expense_date, Amount, Description, Locked_status, Created_at)
SELECT k.Kiosk_ID, k.user_id, @y, e.amt, e.descr, 1, CONCAT(@y, ' ', e.hhmm)
FROM (
    SELECT 1 AS Kiosk_ID, @u1 AS user_id UNION ALL
    SELECT 2, @u2 UNION ALL
    SELECT 3, @u3 UNION ALL
    SELECT 4, @u4 UNION ALL
    SELECT 5, @u5
) k
CROSS JOIN (
    SELECT 250.00 AS amt, 'LPG refill'           AS descr, '08:00:00' AS hhmm UNION ALL
    SELECT 120.00,        'Paper cups (200pc)',           '08:30:00' UNION ALL
    SELECT 60.00,         'Tricycle / transport',         '17:00:00'
) e;

-- ---------- 2E. Yesterday — Ending parts inventory ----------
-- Approximately 40-60% of beginning quantity — realistic end-of-day stock
INSERT INTO Inventory_Snapshot
    (Kiosk_ID, Part_ID, User_ID, Snapshot_date, Snapshot_type, Quantity, Locked_status)
SELECT b.Kiosk_ID, b.Part_ID, b.User_ID, @y, 'ending',
       CAST(ROUND(b.Quantity * 0.5) AS UNSIGNED),
       1
FROM Inventory_Snapshot b
WHERE b.Snapshot_date = @y AND b.Snapshot_type = 'beginning' AND b.Part_ID IS NOT NULL;

-- ---------- 2F. Yesterday — Time-in punches (with time-out) ----------
INSERT INTO Time_in (User_ID, Kiosk_ID, Timestamp, Time_out) VALUES
    (@u1, 1, CONCAT(@y, ' 07:00:00'), CONCAT(@y, ' 17:30:00')),
    (@u2, 2, CONCAT(@y, ' 07:15:00'), CONCAT(@y, ' 17:45:00')),
    (@u3, 3, CONCAT(@y, ' 07:00:00'), CONCAT(@y, ' 17:00:00')),
    (@u4, 4, CONCAT(@y, ' 06:45:00'), CONCAT(@y, ' 17:30:00')),
    (@u5, 5, CONCAT(@y, ' 07:30:00'), CONCAT(@y, ' 16:30:00'));


-- ============================================================
-- PART 3 — TODAY (Locked_status = 0, mid-day partial state)
-- ============================================================

-- ---------- 3A. Today — Beginning parts inventory (carry-forward) ----------
-- Use yesterday's ending as today's beginning (perpetual inventory pattern)
INSERT INTO Inventory_Snapshot
    (Kiosk_ID, Part_ID, User_ID, Snapshot_date, Snapshot_type, Quantity, Locked_status)
SELECT e.Kiosk_ID, e.Part_ID, e.User_ID, @t, 'beginning',
       e.Quantity, 0
FROM Inventory_Snapshot e
WHERE e.Snapshot_date = @y AND e.Snapshot_type = 'ending' AND e.Part_ID IS NOT NULL;

-- ---------- 3B. Today — Morning deliveries (parts top-up) ----------
INSERT INTO Delivery (Kiosk_ID, User_ID, Part_ID, Delivery_Date, Quantity, Type, Locked_status, Created_at)
SELECT k.Kiosk_ID, k.user_id, pt.Part_ID, @t, q.qty, 'Delivery', 0,
       CONCAT(@t, ' 07:30:00')
FROM (
    SELECT 1 AS Kiosk_ID, @u1 AS user_id UNION ALL
    SELECT 2, @u2 UNION ALL
    SELECT 4, @u4
) k
CROSS JOIN (
    SELECT 'Burger Bun' AS pname, 20 AS qty UNION ALL
    SELECT 'Burger Patty', 20
) q
JOIN Part pt ON pt.Name = q.pname;

-- ---------- 3C. Today — Morning sales (open) ----------
INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date, Quantity_sold, Unit_Price, Locked_status, Created_at)
SELECT k.Kiosk_ID, k.user_id, p.Product_ID, @t, s.qty, p.Price, 0,
       CONCAT(@t, ' ', s.hhmm)
FROM (
    SELECT 1 AS Kiosk_ID, @u1 AS user_id UNION ALL
    SELECT 2, @u2 UNION ALL
    SELECT 3, @u3 UNION ALL
    SELECT 4, @u4 UNION ALL
    SELECT 5, @u5
) k
CROSS JOIN (
    SELECT 'All Around Burger' AS pname, 2 AS qty, '09:00:00' AS hhmm UNION ALL
    SELECT 'Hungarian Hotdog',  3, '09:30:00' UNION ALL
    SELECT 'Cup of Rice',       3, '10:00:00' UNION ALL
    SELECT 'Coke 500ml',        2, '10:30:00' UNION ALL
    SELECT 'Siomai',            4, '11:00:00'
) s
JOIN Product p ON p.Name = s.pname
WHERE p.Active = 1;

-- ---------- 3D. Today — Expenses (open) ----------
INSERT INTO Expenses (Kiosk_ID, User_ID, Expense_date, Amount, Description, Locked_status, Created_at) VALUES
    (1, @u1, @t, 180.00, 'Ice for drinks',         0, CONCAT(@t, ' 08:00:00')),
    (2, @u2, @t, 220.00, 'Plastic bags + napkins', 0, CONCAT(@t, ' 08:15:00')),
    (4, @u4, @t,  90.00, 'Tricycle (supplier)',    0, CONCAT(@t, ' 09:30:00'));

-- ---------- 3E. Today — Time-in punches (active, no time-out yet) ----------
INSERT INTO Time_in (User_ID, Kiosk_ID, Timestamp, Time_out) VALUES
    (@u1, 1, CONCAT(@t, ' 07:00:00'), NULL),
    (@u2, 2, CONCAT(@t, ' 07:15:00'), NULL),
    (@u3, 3, CONCAT(@t, ' 07:00:00'), NULL),
    (@u4, 4, CONCAT(@t, ' 06:45:00'), NULL),
    (@u5, 5, CONCAT(@t, ' 07:30:00'), NULL);

-- ============================================================
-- PART 4 — TRUNCATE the trigger-noise audit log, then add a clean
--          set of human-readable demo audit entries
-- ============================================================
TRUNCATE TABLE Audit_Log;
ALTER TABLE Audit_Log AUTO_INCREMENT = 1;

INSERT INTO Audit_Log (User_ID, Action, Details, Timestamp) VALUES
    (@u1, 'LOGIN',  'Login from Tagbak Branch',     CONCAT(@y, ' 06:55:00')),
    (@u1, 'CREATE', 'Beginning stock recorded (31 parts)', CONCAT(@y, ' 07:05:00')),
    (@u1, 'CREATE', 'Delivery batch (3 parts: Bun, Patty, Rice)', CONCAT(@y, ' 07:35:00')),
    (@u1, 'CREATE', 'Batch sale: 10 products | Parts consumed: Bun×3, Patty×3, Rice×8', CONCAT(@y, ' 12:05:00')),
    (@u1, 'CREATE', 'Ending stock recorded (31 parts)',   CONCAT(@y, ' 17:00:00')),
    (@u1, 'LOCK',   'Day closed — all records auto-locked', CONCAT(@y, ' 17:05:00')),
    (@u2, 'LOGIN',  'Login from Atrium Branch',     CONCAT(@y, ' 07:10:00')),
    (@owner_id, 'LOGIN', 'Owner login (review)', CONCAT(@y, ' 18:00:00')),
    (@u1, 'LOGIN',  'Login from Tagbak Branch',     CONCAT(@t, ' 06:58:00')),
    (@u1, 'CREATE', 'Beginning stock auto-generated from yesterday (31 parts)', CONCAT(@t, ' 07:05:00')),
    (@u1, 'CREATE', 'Batch sale: 5 products', CONCAT(@t, ' 09:05:00')),
    (@owner_id, 'LOGIN', 'Owner login (today review)', CONCAT(@t, ' 09:30:00'));

-- ============================================================
-- VERIFICATION
-- ============================================================
SELECT 'Yesterday begin parts'   AS what, COUNT(*) AS n FROM Inventory_Snapshot WHERE Snapshot_date=@y AND Snapshot_type='beginning' AND Part_ID IS NOT NULL
UNION ALL SELECT 'Yesterday end parts',   COUNT(*) FROM Inventory_Snapshot WHERE Snapshot_date=@y AND Snapshot_type='ending'    AND Part_ID IS NOT NULL
UNION ALL SELECT 'Today begin parts',     COUNT(*) FROM Inventory_Snapshot WHERE Snapshot_date=@t AND Snapshot_type='beginning' AND Part_ID IS NOT NULL
UNION ALL SELECT 'Yesterday deliveries',  COUNT(*) FROM Delivery   WHERE Delivery_Date=@y
UNION ALL SELECT 'Today deliveries',      COUNT(*) FROM Delivery   WHERE Delivery_Date=@t
UNION ALL SELECT 'Yesterday sales',       COUNT(*) FROM Sales      WHERE Sales_date=@y
UNION ALL SELECT 'Today sales',           COUNT(*) FROM Sales      WHERE Sales_date=@t
UNION ALL SELECT 'Yesterday expenses',    COUNT(*) FROM Expenses   WHERE Expense_date=@y
UNION ALL SELECT 'Today expenses',        COUNT(*) FROM Expenses   WHERE Expense_date=@t
UNION ALL SELECT 'Time-in (yesterday)',   COUNT(*) FROM Time_in    WHERE DATE(Timestamp)=@y
UNION ALL SELECT 'Time-in (today)',       COUNT(*) FROM Time_in    WHERE DATE(Timestamp)=@t
UNION ALL SELECT 'Audit_Log demo rows',   COUNT(*) FROM Audit_Log;
