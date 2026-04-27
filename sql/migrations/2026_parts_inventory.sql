-- =====================================================================
--  Migration: Parts-based Inventory
--  File:      sql/migrations/2026_parts_inventory.sql
--  Date:      2026-04-27
--  Author:    Juan Miguel Rashley M. Andrada (Group 7)
--
--  PURPOSE
--  -------
--  Shifts the system from "finished-product inventory" to "parts-based
--  inventory". Inventory and Delivery now track raw PARTS (Burger Bun,
--  Patty, Cheese, etc.) instead of finished Products. Sales still records
--  Products sold; the application layer (Phase 2) will auto-deduct parts
--  from inventory using the Product_Part recipe junction.
--
--  IDEMPOTENCY
--  -----------
--  All DDL uses IF NOT EXISTS / IF EXISTS clauses.
--  All seed data uses ON DUPLICATE KEY UPDATE so this script is safe
--  to re-run without erroring or creating duplicates.
--
--  RUN ON: Master only (replicates to slave automatically)
--  COMMAND:
--    mysql -u root -P 3306 chicken_deluxe < sql/migrations/2026_parts_inventory.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. NEW TABLE — Part
--    Each row is a raw ingredient / component used to build products.
--    Soft-deleted via Active=0 to preserve history of past recipes.
--
--    NOTE: Spec did not require UNIQUE on Name; added here as a safety
--    constraint so re-running the seed cannot create duplicate parts.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Part (
    Part_ID    INT AUTO_INCREMENT PRIMARY KEY,
    Name       VARCHAR(100) NOT NULL,
    Unit       VARCHAR(30)  NOT NULL DEFAULT 'pcs',
    Active     TINYINT(1)   NOT NULL DEFAULT 1,
    Created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_part_name (Name)
) ENGINE=InnoDB CHARACTER SET utf8mb4;

-- ---------------------------------------------------------------------
-- 2. NEW TABLE — Product_Part  (RECIPE JUNCTION)
--    One row per (Product, Part) pair — defines how many of each part
--    are needed to build one unit of the product.
--
--    UNIQUE (Product_ID, Part_ID) prevents duplicate part entries.
--    ON DELETE CASCADE on Product_ID — deleting a product cleans up its
--    recipe automatically.
--    ON DELETE RESTRICT on Part_ID — cannot delete a part still used
--    in a recipe (forces explicit recipe edit first).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Product_Part (
    Product_Part_ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID      INT NOT NULL,
    Part_ID         INT NOT NULL,
    Quantity_needed INT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_product_part (Product_ID, Part_ID),
    CONSTRAINT pp_product_fk FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID) ON DELETE CASCADE,
    CONSTRAINT pp_part_fk    FOREIGN KEY (Part_ID)    REFERENCES Part(Part_ID)       ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4;

-- ---------------------------------------------------------------------
-- 3. ALTER Inventory_Snapshot — add Part_ID, swap unique key
--    Existing rows keep their Product_ID values (historical data).
--    New rows will populate Part_ID instead. Both columns become
--    nullable so old + new schemas coexist.
--
--    The new uq_snapshot_part index allows multiple historical rows
--    with Part_ID NULL on the same kiosk/date/type because MySQL
--    treats NULLs as distinct in unique indexes.
-- ---------------------------------------------------------------------
ALTER TABLE Inventory_Snapshot
    ADD COLUMN IF NOT EXISTS Part_ID INT NULL AFTER Product_ID;

-- Add the FK only if not already present (re-run safety)
SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Inventory_Snapshot'
      AND CONSTRAINT_NAME = 'inv_snap_part_fk'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE Inventory_Snapshot ADD CONSTRAINT inv_snap_part_fk FOREIGN KEY (Part_ID) REFERENCES Part(Part_ID)',
    'SELECT "FK inv_snap_part_fk already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE Inventory_Snapshot
    MODIFY COLUMN Product_ID INT NULL;

-- The old uq_snapshot index implicitly supported the Kiosk_ID foreign key
-- (because uq_snapshot starts with Kiosk_ID). Before dropping it we must
-- create a standalone index on Kiosk_ID so the FK still has support.
ALTER TABLE Inventory_Snapshot
    ADD KEY IF NOT EXISTS ix_inv_snap_kiosk (Kiosk_ID);

ALTER TABLE Inventory_Snapshot
    DROP INDEX IF EXISTS uq_snapshot;

ALTER TABLE Inventory_Snapshot
    ADD UNIQUE KEY IF NOT EXISTS uq_snapshot_part
        (Kiosk_ID, Part_ID, Snapshot_date, Snapshot_type);

-- ---------------------------------------------------------------------
-- 4. ALTER Delivery — add Part_ID
--    Same pattern: new rows use Part_ID, historical rows keep Product_ID.
-- ---------------------------------------------------------------------
ALTER TABLE Delivery
    ADD COLUMN IF NOT EXISTS Part_ID INT NULL AFTER Product_ID;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Delivery'
      AND CONSTRAINT_NAME = 'delivery_part_fk'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE Delivery ADD CONSTRAINT delivery_part_fk FOREIGN KEY (Part_ID) REFERENCES Part(Part_ID)',
    'SELECT "FK delivery_part_fk already exists" AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE Delivery
    MODIFY COLUMN Product_ID INT NULL;

-- ---------------------------------------------------------------------
-- 5. SEED — Parts master list  (idempotent via ON DUPLICATE KEY UPDATE)
--    These mirror the actual menu's raw ingredients.
-- ---------------------------------------------------------------------
INSERT INTO Part (Name, Unit) VALUES
    ('Burger Bun',        'pcs'),
    ('Burger Patty',      'pcs'),
    ('Cheese Slice',      'pcs'),
    ('Lettuce',           'pcs'),
    ('Tomato',            'pcs'),
    ('Onion',             'pcs'),
    ('Ham Slice',         'pcs'),
    ('Egg',               'pcs'),
    ('Hotdog',            'pcs'),
    ('Hungarian Sausage', 'pcs'),
    ('Lumpia',            'pcs'),
    ('Rice',              'cup'),
    ('Rice Cup',          'pcs'),
    ('Sisig',             'pcs'),
    ('Siomai',            'pcs'),
    ('Kikiam',            'pcs'),
    ('Fish Balls',        'pcs'),
    ('Canton',            'pack'),
    ('Siopao',            'pcs'),
    ('Fries',             'pack'),
    ('Coke Bottle',       'bottle'),
    ('Royal Bottle',      'bottle'),
    ('Sprite Bottle',     'bottle'),
    ('Coke Swakto',       'can'),
    ('Royal Swakto',      'can'),
    ('Sprite Swakto',     'can'),
    ('Sting',             'bottle'),
    ('Coffee Base',       'cup'),
    ('Matcha Base',       'cup'),
    ('Caramel Syrup',     'tsp'),
    ('Iced Coffee Base',  'cup')
ON DUPLICATE KEY UPDATE Unit = VALUES(Unit), Active = 1;

-- ---------------------------------------------------------------------
-- 6. SEED — Product_Part recipes
--    Each block uses INSERT...SELECT to look up real Product_IDs and
--    Part_IDs from the live tables (never hardcoded numeric IDs).
--    ON DUPLICATE KEY UPDATE makes re-running safe.
--
--    Convention used here (matches Juan's spec examples):
--      • "Burger Patty" / "Burger Patty with X" = patty-style products,
--        NO bun (a patty served on its own / with toppings).
--      • "Burger with X" = full sandwich including Bun + Patty + extras.
--      • "Hungarian Hotdog" = sausage only (no bun) per spec example.
-- ---------------------------------------------------------------------

-- ===== BURGERS =====

-- All Around Burger: Bun + Patty + Cheese + Lettuce + Tomato + Onion (1 each)
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'All Around Burger'
  AND pt.Name IN ('Burger Bun','Burger Patty','Cheese Slice','Lettuce','Tomato','Onion')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger Patty (plain — patty only, per spec)
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger Patty'
  AND pt.Name IN ('Burger Patty')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger Patty with Cheese
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger Patty with Cheese'
  AND pt.Name IN ('Burger Patty','Cheese Slice')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger Patty with Egg
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger Patty with Egg'
  AND pt.Name IN ('Burger Patty','Egg')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger Patty with Egg & Cheese
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger Patty with Egg & Cheese'
  AND pt.Name IN ('Burger Patty','Egg','Cheese Slice')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger with Cheese (full sandwich)
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger with Cheese'
  AND pt.Name IN ('Burger Bun','Burger Patty','Cheese Slice')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger with Egg & Cheese
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger with Egg & Cheese'
  AND pt.Name IN ('Burger Bun','Burger Patty','Egg','Cheese Slice')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger with Ham & Cheese
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger with Ham & Cheese'
  AND pt.Name IN ('Burger Bun','Burger Patty','Ham Slice','Cheese Slice')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Burger with Ham & Egg
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Burger with Ham & Egg'
  AND pt.Name IN ('Burger Bun','Burger Patty','Ham Slice','Egg')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- ===== HOTDOGS =====

-- Hungarian Hotdog: just the sausage (per spec)
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Hungarian Hotdog'
  AND pt.Name IN ('Hungarian Sausage')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- ===== RICEBOWL =====

-- Cup of Rice (Rice Cup container only — per spec)
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Cup of Rice'
  AND pt.Name IN ('Rice Cup')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Egg (single egg as a side)
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Egg'
  AND pt.Name = 'Egg'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Lumpia Bowl: 4 Lumpia + 1 Rice + 1 Rice Cup
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID,
       CASE WHEN pt.Name = 'Lumpia' THEN 4 ELSE 1 END
FROM Product p, Part pt
WHERE p.Name = 'Lumpia Bowl'
  AND pt.Name IN ('Lumpia','Rice','Rice Cup')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Siomai Bowl: 3 Siomai + 1 Rice + 1 Rice Cup
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID,
       CASE WHEN pt.Name = 'Siomai' THEN 3 ELSE 1 END
FROM Product p, Part pt
WHERE p.Name = 'Siomai Bowl'
  AND pt.Name IN ('Siomai','Rice','Rice Cup')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Sisig Bowl: 1 Sisig + 1 Rice + 1 Rice Cup
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Sisig Bowl'
  AND pt.Name IN ('Sisig','Rice','Rice Cup')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- ===== SNACKS (1:1 — product name maps to its same-named part) =====

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Canton' AND pt.Name = 'Canton'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Fish Balls' AND pt.Name = 'Fish Balls'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Fries' AND pt.Name = 'Fries'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Kikiam' AND pt.Name = 'Kikiam'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Siomai' AND pt.Name = 'Siomai'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Siopao' AND pt.Name = 'Siopao'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- ===== DRINKS =====

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Coke 500ml' AND pt.Name = 'Coke Bottle'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Royal 500ml' AND pt.Name = 'Royal Bottle'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Sprite 500ml' AND pt.Name = 'Sprite Bottle'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Coke Swakto' AND pt.Name = 'Coke Swakto'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Royal Swakto' AND pt.Name = 'Royal Swakto'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Sprite Swakto' AND pt.Name = 'Sprite Swakto'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Sting' AND pt.Name = 'Sting'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Iced Coffee: 1 Iced Coffee Base
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Iced Coffee' AND pt.Name = 'Iced Coffee Base'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Iced Matcha: 1 Matcha Base
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Iced Matcha' AND pt.Name = 'Matcha Base'
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- Caramel Coffee: 1 Coffee Base + 1 Caramel Syrup
INSERT INTO Product_Part (Product_ID, Part_ID, Quantity_needed)
SELECT p.Product_ID, pt.Part_ID, 1
FROM Product p, Part pt
WHERE p.Name = 'Caramel Coffee'
  AND pt.Name IN ('Coffee Base','Caramel Syrup')
ON DUPLICATE KEY UPDATE Quantity_needed = VALUES(Quantity_needed);

-- ---------------------------------------------------------------------
-- 7. VERIFICATION
-- ---------------------------------------------------------------------
SELECT 'Part'         AS table_name, COUNT(*) AS row_count FROM Part
UNION ALL
SELECT 'Product_Part', COUNT(*) FROM Product_Part;

-- Count of parts per product (every active product should have ≥1 part)
SELECT p.Name AS Product, COUNT(pp.Part_ID) AS parts_in_recipe
FROM Product p
LEFT JOIN Product_Part pp ON p.Product_ID = pp.Product_ID
WHERE p.Active = 1
GROUP BY p.Product_ID, p.Name
ORDER BY parts_in_recipe ASC, p.Name;

-- Recipe spot-check: All Around Burger should have 6 parts
SELECT p.Name AS Product, pt.Name AS Part, pp.Quantity_needed
FROM Product_Part pp
JOIN Product p  ON pp.Product_ID = p.Product_ID
JOIN Part    pt ON pp.Part_ID    = pt.Part_ID
WHERE p.Name = 'All Around Burger'
ORDER BY pt.Name;

-- =====================================================================
--  END OF MIGRATION
-- =====================================================================
