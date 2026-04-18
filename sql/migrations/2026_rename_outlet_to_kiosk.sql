-- ============================================================
-- Migration: Rename Outlet_ID -> Kiosk_ID across all tables
-- Date    : 2026-04-18
-- Reason  : "Outlet" and "Kiosk" were being used interchangeably.
--           Standardising on "Kiosk" everywhere to remove confusion.
-- Run on  : MASTER ONLY (port 3306). Slave replicates automatically.
-- Affects : 6 tables (Audit_Log has no Outlet_ID column, so 7 from
--           the original task spec is actually 6 in the live schema).
-- Notes   : MariaDB requires drop FK / change column / add FK as
--           separate ALTER statements when the FK name is reused.
-- ============================================================

USE chicken_deluxe;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------
-- 1. User.Outlet_ID  ->  User.Kiosk_ID  (NULLABLE — owner has none)
-- ----------------------------------------------------------------
ALTER TABLE User DROP FOREIGN KEY user_ibfk_2;
ALTER TABLE User CHANGE COLUMN Outlet_ID Kiosk_ID INT NULL;
ALTER TABLE User ADD CONSTRAINT user_ibfk_2 FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID);

-- ----------------------------------------------------------------
-- 2. Inventory_Snapshot — also rebuild the unique key that included it
-- ----------------------------------------------------------------
ALTER TABLE Inventory_Snapshot DROP FOREIGN KEY inventory_snapshot_ibfk_1;
ALTER TABLE Inventory_Snapshot DROP INDEX uq_snapshot;
ALTER TABLE Inventory_Snapshot CHANGE COLUMN Outlet_ID Kiosk_ID INT NOT NULL;
ALTER TABLE Inventory_Snapshot ADD CONSTRAINT inventory_snapshot_ibfk_1 FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID);
ALTER TABLE Inventory_Snapshot ADD UNIQUE KEY uq_snapshot (Kiosk_ID, Product_ID, Snapshot_date, Snapshot_type);

-- ----------------------------------------------------------------
-- 3. Delivery
-- ----------------------------------------------------------------
ALTER TABLE Delivery DROP FOREIGN KEY delivery_ibfk_1;
ALTER TABLE Delivery CHANGE COLUMN Outlet_ID Kiosk_ID INT NOT NULL;
ALTER TABLE Delivery ADD CONSTRAINT delivery_ibfk_1 FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID);

-- ----------------------------------------------------------------
-- 4. Sales
-- ----------------------------------------------------------------
ALTER TABLE Sales DROP FOREIGN KEY sales_ibfk_1;
ALTER TABLE Sales CHANGE COLUMN Outlet_ID Kiosk_ID INT NOT NULL;
ALTER TABLE Sales ADD CONSTRAINT sales_ibfk_1 FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID);

-- ----------------------------------------------------------------
-- 5. Expenses
-- ----------------------------------------------------------------
ALTER TABLE Expenses DROP FOREIGN KEY expenses_ibfk_1;
ALTER TABLE Expenses CHANGE COLUMN Outlet_ID Kiosk_ID INT NOT NULL;
ALTER TABLE Expenses ADD CONSTRAINT expenses_ibfk_1 FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID);

-- ----------------------------------------------------------------
-- 6. Time_in
-- ----------------------------------------------------------------
ALTER TABLE Time_in DROP FOREIGN KEY time_in_ibfk_2;
ALTER TABLE Time_in CHANGE COLUMN Outlet_ID Kiosk_ID INT NOT NULL;
ALTER TABLE Time_in ADD CONSTRAINT time_in_ibfk_2 FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- End of migration
-- ============================================================
