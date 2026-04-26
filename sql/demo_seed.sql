-- ============================================================
-- demo_seed.sql — Realistic demo data for all 5 kiosks
--
-- Adds:
--   * 5 staff users (one per kiosk)
--   * Yesterday: full operational day, ALL records LOCKED
--   * Today:     mid-day partial data, ALL records OPEN
--   * Sample audit-log entries
--
-- Run after demo_reset.sql.  Run on Master only.
-- Idempotency: this script INSERTs duplicates if run twice.
--              Re-run demo_reset.sql first to start clean.
-- ============================================================

USE chicken_deluxe;

-- ---------- date / id helpers ----------
SET @owner_id = (SELECT User_ID FROM User WHERE Username = 'owner' LIMIT 1);
SET @y        = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
SET @t        = CURDATE();

-- ============================================================
-- PART 1 — STAFF USERS (one per kiosk)
-- bcrypt('staff1234', cost=10) generated via php -r
-- ============================================================
INSERT INTO User (Role_ID, Kiosk_ID, Username, Password, Full_name, Active_status) VALUES
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
-- PART 2 — YESTERDAY  (Locked_status = 1)
-- ============================================================

-- ---------- 2A. Inventory Snapshots (Beginning + Ending) ----------
-- 10 popular products per kiosk × 2 types × 5 kiosks = 100 rows
-- Product mix: classic burger, all around, patty, hungarian, classic
-- hotdog, lumpia bowl, siomai bowl, cup of rice, coke swakto, siomai
INSERT INTO Inventory_Snapshot
    (Kiosk_ID, Product_ID, User_ID, Snapshot_date, Snapshot_type, Quantity, Locked_status)
VALUES
    -- Kiosk 1 — Tagbak (high traffic): begin 20, end ~10
    (1,  1, @u1, @y, 'beginning', 20, 1), (1,  1, @u1, @y, 'ending', 10, 1),
    (1,  3, @u1, @y, 'beginning', 18, 1), (1,  3, @u1, @y, 'ending',  8, 1),
    (1,  8, @u1, @y, 'beginning', 15, 1), (1,  8, @u1, @y, 'ending',  6, 1),
    (1, 22, @u1, @y, 'beginning', 12, 1), (1, 22, @u1, @y, 'ending',  4, 1),
    (1,  2, @u1, @y, 'beginning', 16, 1), (1,  2, @u1, @y, 'ending',  9, 1),
    (1, 25, @u1, @y, 'beginning', 10, 1), (1, 25, @u1, @y, 'ending',  3, 1),
    (1, 26, @u1, @y, 'beginning', 12, 1), (1, 26, @u1, @y, 'ending',  5, 1),
    (1, 23, @u1, @y, 'beginning', 30, 1), (1, 23, @u1, @y, 'ending', 15, 1),
    (1, 13, @u1, @y, 'beginning', 24, 1), (1, 13, @u1, @y, 'ending', 12, 1),
    (1, 32, @u1, @y, 'beginning', 25, 1), (1, 32, @u1, @y, 'ending', 11, 1),

    -- Kiosk 2 — Atrium (medium): begin 15, end ~7
    (2,  1, @u2, @y, 'beginning', 15, 1), (2,  1, @u2, @y, 'ending',  7, 1),
    (2,  3, @u2, @y, 'beginning', 14, 1), (2,  3, @u2, @y, 'ending',  6, 1),
    (2,  8, @u2, @y, 'beginning', 12, 1), (2,  8, @u2, @y, 'ending',  4, 1),
    (2, 22, @u2, @y, 'beginning', 10, 1), (2, 22, @u2, @y, 'ending',  3, 1),
    (2,  2, @u2, @y, 'beginning', 14, 1), (2,  2, @u2, @y, 'ending',  7, 1),
    (2, 25, @u2, @y, 'beginning',  8, 1), (2, 25, @u2, @y, 'ending',  2, 1),
    (2, 26, @u2, @y, 'beginning',  9, 1), (2, 26, @u2, @y, 'ending',  4, 1),
    (2, 23, @u2, @y, 'beginning', 25, 1), (2, 23, @u2, @y, 'ending', 12, 1),
    (2, 13, @u2, @y, 'beginning', 20, 1), (2, 13, @u2, @y, 'ending', 10, 1),
    (2, 32, @u2, @y, 'beginning', 20, 1), (2, 32, @u2, @y, 'ending',  9, 1),

    -- Kiosk 3 — City Proper (lower traffic): begin 12, end ~5
    (3,  1, @u3, @y, 'beginning', 12, 1), (3,  1, @u3, @y, 'ending',  5, 1),
    (3,  3, @u3, @y, 'beginning', 11, 1), (3,  3, @u3, @y, 'ending',  4, 1),
    (3,  8, @u3, @y, 'beginning', 10, 1), (3,  8, @u3, @y, 'ending',  3, 1),
    (3, 22, @u3, @y, 'beginning',  8, 1), (3, 22, @u3, @y, 'ending',  2, 1),
    (3,  2, @u3, @y, 'beginning', 10, 1), (3,  2, @u3, @y, 'ending',  4, 1),
    (3, 25, @u3, @y, 'beginning',  8, 1), (3, 25, @u3, @y, 'ending',  3, 1),
    (3, 26, @u3, @y, 'beginning',  8, 1), (3, 26, @u3, @y, 'ending',  3, 1),
    (3, 23, @u3, @y, 'beginning', 20, 1), (3, 23, @u3, @y, 'ending',  9, 1),
    (3, 13, @u3, @y, 'beginning', 18, 1), (3, 13, @u3, @y, 'ending',  9, 1),
    (3, 32, @u3, @y, 'beginning', 15, 1), (3, 32, @u3, @y, 'ending',  7, 1),

    -- Kiosk 4 — Supermart (high traffic): begin 18, end ~9
    (4,  1, @u4, @y, 'beginning', 18, 1), (4,  1, @u4, @y, 'ending',  9, 1),
    (4,  3, @u4, @y, 'beginning', 16, 1), (4,  3, @u4, @y, 'ending',  7, 1),
    (4,  8, @u4, @y, 'beginning', 14, 1), (4,  8, @u4, @y, 'ending',  5, 1),
    (4, 22, @u4, @y, 'beginning', 12, 1), (4, 22, @u4, @y, 'ending',  4, 1),
    (4,  2, @u4, @y, 'beginning', 15, 1), (4,  2, @u4, @y, 'ending',  6, 1),
    (4, 25, @u4, @y, 'beginning', 10, 1), (4, 25, @u4, @y, 'ending',  4, 1),
    (4, 26, @u4, @y, 'beginning', 11, 1), (4, 26, @u4, @y, 'ending',  4, 1),
    (4, 23, @u4, @y, 'beginning', 28, 1), (4, 23, @u4, @y, 'ending', 13, 1),
    (4, 13, @u4, @y, 'beginning', 22, 1), (4, 13, @u4, @y, 'ending', 11, 1),
    (4, 32, @u4, @y, 'beginning', 22, 1), (4, 32, @u4, @y, 'ending', 10, 1),

    -- Kiosk 5 — Aldeguer (small): begin 10, end ~4
    (5,  1, @u5, @y, 'beginning', 10, 1), (5,  1, @u5, @y, 'ending',  4, 1),
    (5,  3, @u5, @y, 'beginning',  9, 1), (5,  3, @u5, @y, 'ending',  3, 1),
    (5,  8, @u5, @y, 'beginning',  8, 1), (5,  8, @u5, @y, 'ending',  3, 1),
    (5, 22, @u5, @y, 'beginning',  8, 1), (5, 22, @u5, @y, 'ending',  2, 1),
    (5,  2, @u5, @y, 'beginning',  9, 1), (5,  2, @u5, @y, 'ending',  4, 1),
    (5, 25, @u5, @y, 'beginning',  6, 1), (5, 25, @u5, @y, 'ending',  1, 1),
    (5, 26, @u5, @y, 'beginning',  7, 1), (5, 26, @u5, @y, 'ending',  3, 1),
    (5, 23, @u5, @y, 'beginning', 18, 1), (5, 23, @u5, @y, 'ending',  8, 1),
    (5, 13, @u5, @y, 'beginning', 15, 1), (5, 13, @u5, @y, 'ending',  7, 1),
    (5, 32, @u5, @y, 'beginning', 14, 1), (5, 32, @u5, @y, 'ending',  6, 1);

-- ---------- 2B. Deliveries ----------
INSERT INTO Delivery (Kiosk_ID, User_ID, Product_ID, Delivery_Date, Quantity, Locked_status) VALUES
    -- Kiosk 1 — 4 deliveries
    (1, @u1,  1, @y, 25, 1), (1, @u1,  8, @y, 20, 1),
    (1, @u1, 23, @y, 30, 1), (1, @u1, 13, @y, 24, 1),
    -- Kiosk 2 — 3 deliveries
    (2, @u2,  3, @y, 20, 1), (2, @u2, 25, @y, 15, 1), (2, @u2, 32, @y, 18, 1),
    -- Kiosk 3 — 2 deliveries
    (3, @u3,  1, @y, 15, 1), (3, @u3, 23, @y, 25, 1),
    -- Kiosk 4 — 4 deliveries
    (4, @u4,  3, @y, 22, 1), (4, @u4, 22, @y, 18, 1),
    (4, @u4, 26, @y, 15, 1), (4, @u4, 32, @y, 22, 1),
    -- Kiosk 5 — 2 deliveries
    (5, @u5,  2, @y, 12, 1), (5, @u5, 13, @y, 18, 1);

-- ---------- 2C. Sales (yesterday — 10–13 transactions per kiosk) ----------
-- Spread across the day; Unit_Price taken from Product table
INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date, Quantity_sold, Unit_Price, Locked_status) VALUES
    -- KIOSK 1 (12 sales)
    (1, @u1,  1, @y, 2, 65.00, 1),
    (1, @u1,  3, @y, 1, 75.00, 1),
    (1, @u1,  8, @y, 3, 45.00, 1),
    (1, @u1, 22, @y, 2, 45.00, 1),
    (1, @u1, 13, @y, 5, 18.00, 1),
    (1, @u1, 23, @y, 4, 20.00, 1),
    (1, @u1, 25, @y, 1, 65.00, 1),
    (1, @u1, 26, @y, 2, 70.00, 1),
    (1, @u1, 32, @y, 3, 30.00, 1),
    (1, @u1,  1, @y, 1, 65.00, 1),
    (1, @u1,  2, @y, 4, 35.00, 1),
    (1, @u1, 13, @y, 3, 18.00, 1),

    -- KIOSK 2 (11 sales)
    (2, @u2,  3, @y, 2, 75.00, 1),
    (2, @u2,  8, @y, 2, 45.00, 1),
    (2, @u2, 22, @y, 1, 45.00, 1),
    (2, @u2,  2, @y, 3, 35.00, 1),
    (2, @u2, 13, @y, 4, 18.00, 1),
    (2, @u2, 23, @y, 3, 20.00, 1),
    (2, @u2, 26, @y, 2, 70.00, 1),
    (2, @u2, 32, @y, 4, 30.00, 1),
    (2, @u2,  1, @y, 1, 65.00, 1),
    (2, @u2, 25, @y, 1, 65.00, 1),
    (2, @u2, 13, @y, 2, 18.00, 1),

    -- KIOSK 3 (10 sales)
    (3, @u3,  1, @y, 1, 65.00, 1),
    (3, @u3,  8, @y, 2, 45.00, 1),
    (3, @u3,  3, @y, 1, 75.00, 1),
    (3, @u3, 22, @y, 1, 45.00, 1),
    (3, @u3,  2, @y, 2, 35.00, 1),
    (3, @u3, 13, @y, 3, 18.00, 1),
    (3, @u3, 23, @y, 2, 20.00, 1),
    (3, @u3, 25, @y, 1, 65.00, 1),
    (3, @u3, 26, @y, 1, 70.00, 1),
    (3, @u3, 32, @y, 2, 30.00, 1),

    -- KIOSK 4 (13 sales — busiest)
    (4, @u4,  1, @y, 3, 65.00, 1),
    (4, @u4,  3, @y, 2, 75.00, 1),
    (4, @u4,  8, @y, 4, 45.00, 1),
    (4, @u4, 22, @y, 2, 45.00, 1),
    (4, @u4,  2, @y, 3, 35.00, 1),
    (4, @u4, 25, @y, 2, 65.00, 1),
    (4, @u4, 26, @y, 2, 70.00, 1),
    (4, @u4, 23, @y, 5, 20.00, 1),
    (4, @u4, 13, @y, 4, 18.00, 1),
    (4, @u4, 32, @y, 3, 30.00, 1),
    (4, @u4,  1, @y, 2, 65.00, 1),
    (4, @u4,  8, @y, 2, 45.00, 1),
    (4, @u4, 13, @y, 3, 18.00, 1),

    -- KIOSK 5 (8 sales — small kiosk)
    (5, @u5,  1, @y, 2, 65.00, 1),
    (5, @u5,  8, @y, 2, 45.00, 1),
    (5, @u5, 22, @y, 1, 45.00, 1),
    (5, @u5,  2, @y, 2, 35.00, 1),
    (5, @u5, 13, @y, 3, 18.00, 1),
    (5, @u5, 23, @y, 2, 20.00, 1),
    (5, @u5, 32, @y, 2, 30.00, 1),
    (5, @u5, 26, @y, 1, 70.00, 1);

-- ---------- 2D. Expenses ----------
INSERT INTO Expenses (Kiosk_ID, User_ID, Expense_date, Amount, Description, Locked_status) VALUES
    (1, @u1, @y, 920.00, 'LPG refill',                  1),
    (1, @u1, @y, 180.00, 'Paper cups and containers',   1),
    (1, @u1, @y,  65.00, 'Ice purchase',                1),
    (2, @u2, @y, 870.00, 'LPG refill',                  1),
    (2, @u2, @y, 130.00, 'Condiments restock',          1),
    (3, @u3, @y, 110.00, 'Transport / delivery fee',    1),
    (3, @u3, @y,  85.00, 'Cleaning supplies',           1),
    (4, @u4, @y, 940.00, 'LPG refill',                  1),
    (4, @u4, @y, 220.00, 'Paper cups and containers',   1),
    (4, @u4, @y, 145.00, 'Condiments restock',          1),
    (5, @u5, @y,  95.00, 'Transport / delivery fee',    1),
    (5, @u5, @y,  70.00, 'Ice purchase',                1);

-- ---------- 2E. Time-In (yesterday morning clock-in) ----------
INSERT INTO Time_in (User_ID, Kiosk_ID, Timestamp) VALUES
    (@u1, 1, CONCAT(@y, ' 07:05:00')),
    (@u2, 2, CONCAT(@y, ' 07:30:00')),
    (@u3, 3, CONCAT(@y, ' 08:10:00')),
    (@u4, 4, CONCAT(@y, ' 06:55:00')),
    (@u5, 5, CONCAT(@y, ' 07:45:00'));

-- ============================================================
-- PART 3 — TODAY (open, Locked_status = 0)
-- ============================================================

-- ---------- 3A. Inventory Beginning (carry-forward from yesterday's ending) ----------
INSERT INTO Inventory_Snapshot
    (Kiosk_ID, Product_ID, User_ID, Snapshot_date, Snapshot_type, Quantity, Locked_status)
VALUES
    -- Kiosk 1 — qty matches yesterday's ending
    (1,  1, @u1, @t, 'beginning', 10, 0), (1,  3, @u1, @t, 'beginning',  8, 0),
    (1,  8, @u1, @t, 'beginning',  6, 0), (1, 22, @u1, @t, 'beginning',  4, 0),
    (1,  2, @u1, @t, 'beginning',  9, 0), (1, 25, @u1, @t, 'beginning',  3, 0),
    (1, 26, @u1, @t, 'beginning',  5, 0), (1, 23, @u1, @t, 'beginning', 15, 0),
    (1, 13, @u1, @t, 'beginning', 12, 0), (1, 32, @u1, @t, 'beginning', 11, 0),
    -- Kiosk 2
    (2,  1, @u2, @t, 'beginning',  7, 0), (2,  3, @u2, @t, 'beginning',  6, 0),
    (2,  8, @u2, @t, 'beginning',  4, 0), (2, 22, @u2, @t, 'beginning',  3, 0),
    (2,  2, @u2, @t, 'beginning',  7, 0), (2, 25, @u2, @t, 'beginning',  2, 0),
    (2, 26, @u2, @t, 'beginning',  4, 0), (2, 23, @u2, @t, 'beginning', 12, 0),
    (2, 13, @u2, @t, 'beginning', 10, 0), (2, 32, @u2, @t, 'beginning',  9, 0),
    -- Kiosk 3
    (3,  1, @u3, @t, 'beginning',  5, 0), (3,  3, @u3, @t, 'beginning',  4, 0),
    (3,  8, @u3, @t, 'beginning',  3, 0), (3, 22, @u3, @t, 'beginning',  2, 0),
    (3,  2, @u3, @t, 'beginning',  4, 0), (3, 25, @u3, @t, 'beginning',  3, 0),
    (3, 26, @u3, @t, 'beginning',  3, 0), (3, 23, @u3, @t, 'beginning',  9, 0),
    (3, 13, @u3, @t, 'beginning',  9, 0), (3, 32, @u3, @t, 'beginning',  7, 0),
    -- Kiosk 4
    (4,  1, @u4, @t, 'beginning',  9, 0), (4,  3, @u4, @t, 'beginning',  7, 0),
    (4,  8, @u4, @t, 'beginning',  5, 0), (4, 22, @u4, @t, 'beginning',  4, 0),
    (4,  2, @u4, @t, 'beginning',  6, 0), (4, 25, @u4, @t, 'beginning',  4, 0),
    (4, 26, @u4, @t, 'beginning',  4, 0), (4, 23, @u4, @t, 'beginning', 13, 0),
    (4, 13, @u4, @t, 'beginning', 11, 0), (4, 32, @u4, @t, 'beginning', 10, 0),
    -- Kiosk 5
    (5,  1, @u5, @t, 'beginning',  4, 0), (5,  3, @u5, @t, 'beginning',  3, 0),
    (5,  8, @u5, @t, 'beginning',  3, 0), (5, 22, @u5, @t, 'beginning',  2, 0),
    (5,  2, @u5, @t, 'beginning',  4, 0), (5, 25, @u5, @t, 'beginning',  1, 0),
    (5, 26, @u5, @t, 'beginning',  3, 0), (5, 23, @u5, @t, 'beginning',  8, 0),
    (5, 13, @u5, @t, 'beginning',  7, 0), (5, 32, @u5, @t, 'beginning',  6, 0);

-- ---------- 3B. Today's Deliveries (only some kiosks) ----------
INSERT INTO Delivery (Kiosk_ID, User_ID, Product_ID, Delivery_Date, Quantity, Locked_status) VALUES
    (1, @u1, 22, @t, 15, 0),
    (1, @u1, 25, @t, 12, 0),
    (4, @u4,  8, @t, 20, 0),
    (4, @u4, 13, @t, 24, 0),
    (2, @u2, 23, @t, 20, 0);

-- ---------- 3C. Today's Sales (morning rush, 4–8 per kiosk) ----------
INSERT INTO Sales (Kiosk_ID, User_ID, Product_ID, Sales_date, Quantity_sold, Unit_Price, Locked_status) VALUES
    -- Kiosk 1 (6 sales)
    (1, @u1,  1, @t, 1, 65.00, 0),
    (1, @u1,  3, @t, 2, 75.00, 0),
    (1, @u1, 13, @t, 3, 18.00, 0),
    (1, @u1, 23, @t, 2, 20.00, 0),
    (1, @u1,  8, @t, 1, 45.00, 0),
    (1, @u1, 32, @t, 2, 30.00, 0),
    -- Kiosk 2 (5)
    (2, @u2,  1, @t, 2, 65.00, 0),
    (2, @u2,  8, @t, 1, 45.00, 0),
    (2, @u2, 13, @t, 2, 18.00, 0),
    (2, @u2,  2, @t, 2, 35.00, 0),
    (2, @u2, 26, @t, 1, 70.00, 0),
    -- Kiosk 3 (4)
    (3, @u3,  1, @t, 1, 65.00, 0),
    (3, @u3, 22, @t, 1, 45.00, 0),
    (3, @u3, 13, @t, 2, 18.00, 0),
    (3, @u3, 23, @t, 1, 20.00, 0),
    -- Kiosk 4 (8)
    (4, @u4,  1, @t, 2, 65.00, 0),
    (4, @u4,  3, @t, 1, 75.00, 0),
    (4, @u4,  8, @t, 3, 45.00, 0),
    (4, @u4, 22, @t, 1, 45.00, 0),
    (4, @u4, 13, @t, 4, 18.00, 0),
    (4, @u4, 23, @t, 3, 20.00, 0),
    (4, @u4, 25, @t, 1, 65.00, 0),
    (4, @u4, 32, @t, 2, 30.00, 0),
    -- Kiosk 5 (4)
    (5, @u5,  1, @t, 1, 65.00, 0),
    (5, @u5,  8, @t, 1, 45.00, 0),
    (5, @u5, 13, @t, 2, 18.00, 0),
    (5, @u5, 23, @t, 1, 20.00, 0);

-- ---------- 3D. Today's Expenses (only 2 kiosks) ----------
INSERT INTO Expenses (Kiosk_ID, User_ID, Expense_date, Amount, Description, Locked_status) VALUES
    (1, @u1, @t,  75.00, 'Ice purchase',          0),
    (4, @u4, @t, 130.00, 'Condiments restock',    0);

-- ---------- 3E. Today's Time-In ----------
INSERT INTO Time_in (User_ID, Kiosk_ID, Timestamp) VALUES
    (@u1, 1, CONCAT(@t, ' 07:00:00')),
    (@u2, 2, CONCAT(@t, ' 07:25:00')),
    (@u3, 3, CONCAT(@t, ' 08:05:00')),
    (@u4, 4, CONCAT(@t, ' 06:50:00')),
    (@u5, 5, CONCAT(@t, ' 07:40:00'));

-- ============================================================
-- PART 4 — Sample Audit Log entries
-- The change-logging triggers fired on every INSERT above and
-- created hundreds of OPERATION='INSERT' rows.  Wipe them and
-- replace with a small, realistic set of user-action entries.
-- ============================================================
TRUNCATE TABLE Audit_Log;
ALTER TABLE Audit_Log AUTO_INCREMENT = 1;

INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp) VALUES
    (@owner_id, 'LOGIN',  NULL, NULL, NULL, NULL, 'Owner logged in',                     CONCAT(@y, ' 07:30:00')),
    (@u1,       'LOGIN',  NULL, NULL, NULL, NULL, 'Staff Maria Santos logged in',        CONCAT(@y, ' 07:05:00')),
    (@u2,       'LOGIN',  NULL, NULL, NULL, NULL, 'Staff Jose Reyes logged in',          CONCAT(@y, ' 07:30:00')),
    (@u3,       'LOGIN',  NULL, NULL, NULL, NULL, 'Staff Ana Flores logged in',          CONCAT(@y, ' 08:10:00')),
    (@u4,       'LOGIN',  NULL, NULL, NULL, NULL, 'Staff Rico Mendoza logged in',        CONCAT(@y, ' 06:55:00')),
    (@u5,       'LOGIN',  NULL, NULL, NULL, NULL, 'Staff Liza Cruz logged in',           CONCAT(@y, ' 07:45:00')),
    (@u1,       'LOCK',   NULL, 'Sales', NULL, NULL, CONCAT('Locked all sales for ', @y, ' (kiosk 1)'),   CONCAT(@y, ' 18:05:00')),
    (@u2,       'LOCK',   NULL, 'Sales', NULL, NULL, CONCAT('Locked all sales for ', @y, ' (kiosk 2)'),   CONCAT(@y, ' 18:15:00')),
    (@u3,       'LOCK',   NULL, 'Sales', NULL, NULL, CONCAT('Locked all sales for ', @y, ' (kiosk 3)'),   CONCAT(@y, ' 18:25:00')),
    (@u4,       'LOCK',   NULL, 'Sales', NULL, NULL, CONCAT('Locked all sales for ', @y, ' (kiosk 4)'),   CONCAT(@y, ' 18:00:00')),
    (@u5,       'LOCK',   NULL, 'Sales', NULL, NULL, CONCAT('Locked all sales for ', @y, ' (kiosk 5)'),   CONCAT(@y, ' 18:30:00')),
    (@owner_id, 'LOGIN',  NULL, NULL, NULL, NULL, 'Owner reviewed daily reports',        CONCAT(@y, ' 19:00:00')),
    (@owner_id, 'LOGIN',  NULL, NULL, NULL, NULL, 'Owner logged in (today)',             CONCAT(@t, ' 08:00:00'));
