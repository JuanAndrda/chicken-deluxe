-- ============================================================
-- demo_reset.sql — Wipe transactional data + set demo prices
-- Run on Master only (replicates to Slave automatically).
--
-- Keeps:   Role, User, Kiosk, Category, Product (price/active updated)
-- Wipes:   Audit_Log, Time_in, Sales, Delivery, Expenses,
--          Inventory_Snapshot
--
-- Note: TRUNCATE does NOT fire DML triggers in MySQL, so the
-- change-logging triggers on transactional tables stay quiet.
-- The Product UPDATEs DO fire trg_log_product_update — those
-- rows are wiped at the very end with a final Audit_Log TRUNCATE.
-- ============================================================

USE chicken_deluxe;

-- ----- 1. Wipe transactional tables -----
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE Audit_Log;
TRUNCATE TABLE Time_in;
TRUNCATE TABLE Sales;
TRUNCATE TABLE Delivery;
TRUNCATE TABLE Expenses;
TRUNCATE TABLE Inventory_Snapshot;

-- ----- 2. Reset auto-increments -----
ALTER TABLE Audit_Log          AUTO_INCREMENT = 1;
ALTER TABLE Time_in            AUTO_INCREMENT = 1;
ALTER TABLE Sales              AUTO_INCREMENT = 1;
ALTER TABLE Delivery           AUTO_INCREMENT = 1;
ALTER TABLE Expenses           AUTO_INCREMENT = 1;
ALTER TABLE Inventory_Snapshot AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ----- 3. Set realistic Philippine kiosk prices -----
-- Burgers (P45 - P89)
UPDATE Product SET Price = 75.00 WHERE Name = 'All Around Burger';
UPDATE Product SET Price = 45.00 WHERE Name = 'Burger Patty';
UPDATE Product SET Price = 55.00 WHERE Name = 'Burger Patty with Cheese';
UPDATE Product SET Price = 55.00 WHERE Name = 'Burger Patty with Egg';
UPDATE Product SET Price = 65.00 WHERE Name = 'Burger Patty with Egg & Cheese';
UPDATE Product SET Price = 50.00 WHERE Name = 'Burger with Cheese';
UPDATE Product SET Price = 65.00 WHERE Name = 'Burger with Egg & Cheese';
UPDATE Product SET Price = 70.00 WHERE Name = 'Burger with Ham & Cheese';
UPDATE Product SET Price = 70.00 WHERE Name = 'Burger with Ham & Egg';
UPDATE Product SET Price = 65.00 WHERE Name = 'Classic Burger';

-- Hotdogs (P25 - P45)
UPDATE Product SET Price = 35.00 WHERE Name = 'Classic Hotdog';
UPDATE Product SET Price = 45.00 WHERE Name = 'Hungarian Hotdog';

-- Ricebowls / meals (P55 - P85)
UPDATE Product SET Price = 65.00 WHERE Name = 'Lumpia Bowl';
UPDATE Product SET Price = 70.00 WHERE Name = 'Siomai Bowl';
UPDATE Product SET Price = 85.00 WHERE Name = 'Sisig Bowl';
-- Cup of Rice + Egg are sides, priced accordingly
UPDATE Product SET Price = 20.00 WHERE Name = 'Cup of Rice';
UPDATE Product SET Price = 15.00 WHERE Name = 'Egg';

-- Drinks (P15 - P35)
UPDATE Product SET Price = 25.00 WHERE Name = 'Buko';
UPDATE Product SET Price = 35.00 WHERE Name = 'Caramel Coffee';
UPDATE Product SET Price = 30.00 WHERE Name = 'Coke 500ml';
UPDATE Product SET Price = 18.00 WHERE Name = 'Coke Swakto';
UPDATE Product SET Price = 30.00 WHERE Name = 'Iced Coffee';
UPDATE Product SET Price = 35.00 WHERE Name = 'Iced Matcha';
UPDATE Product SET Price = 30.00 WHERE Name = 'Royal 500ml';
UPDATE Product SET Price = 18.00 WHERE Name = 'Royal Swakto';
UPDATE Product SET Price = 30.00 WHERE Name = 'Sprite 500ml';
UPDATE Product SET Price = 18.00 WHERE Name = 'Sprite Swakto';
UPDATE Product SET Price = 25.00 WHERE Name = 'Sting';

-- Snacks (P20 - P45)
UPDATE Product SET Price = 35.00 WHERE Name = 'Canton';
UPDATE Product SET Price = 20.00 WHERE Name = 'Fish Balls';
UPDATE Product SET Price = 35.00 WHERE Name = 'Fries';
UPDATE Product SET Price = 25.00 WHERE Name = 'Kikiam';
UPDATE Product SET Price = 30.00 WHERE Name = 'Siomai';
UPDATE Product SET Price = 30.00 WHERE Name = 'Siopao';

-- Reactivate every product
UPDATE Product SET Active = 1;

-- ----- 4. Final cleanup: drop the audit rows that the price
--        UPDATEs above just produced, so we end with a clean log -----
TRUNCATE TABLE Audit_Log;
ALTER TABLE Audit_Log AUTO_INCREMENT = 1;
