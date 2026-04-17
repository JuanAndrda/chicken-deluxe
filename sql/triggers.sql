-- ============================================
-- Chicken Deluxe — SQL Triggers
-- Run on MASTER (port 3306) after schema.sql
-- ============================================

USE chicken_deluxe;

-- ==================
-- 1. Auto-calculate Sales line total before insert
-- ==================
DROP TRIGGER IF EXISTS trg_calc_line_total_insert;
DELIMITER $$
CREATE TRIGGER trg_calc_line_total_insert
BEFORE INSERT ON Sales
FOR EACH ROW
BEGIN
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END$$
DELIMITER ;

-- ==================
-- 2. Auto-calculate Sales line total before update
-- ==================
DROP TRIGGER IF EXISTS trg_calc_line_total_update;
DELIMITER $$
CREATE TRIGGER trg_calc_line_total_update
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END$$
DELIMITER ;

-- ==================
-- 3. Prevent edits on locked Sales records
-- ==================
DROP TRIGGER IF EXISTS trg_prevent_locked_sales_edit;
DELIMITER $$
CREATE TRIGGER trg_prevent_locked_sales_edit
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked sales record.';
    END IF;
END$$
DELIMITER ;

-- ==================
-- 4. Prevent edits on locked Inventory records
-- ==================
DROP TRIGGER IF EXISTS trg_prevent_locked_inventory_edit;
DELIMITER $$
CREATE TRIGGER trg_prevent_locked_inventory_edit
BEFORE UPDATE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked inventory record.';
    END IF;
END$$
DELIMITER ;

-- ==================
-- 5. Prevent edits on locked Delivery records
-- ==================
DROP TRIGGER IF EXISTS trg_prevent_locked_delivery_edit;
DELIMITER $$
CREATE TRIGGER trg_prevent_locked_delivery_edit
BEFORE UPDATE ON Delivery
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked delivery record.';
    END IF;
END$$
DELIMITER ;

-- ==================
-- 6. Prevent edits on locked Expense records
-- ==================
DROP TRIGGER IF EXISTS trg_prevent_locked_expense_edit;
DELIMITER $$
CREATE TRIGGER trg_prevent_locked_expense_edit
BEFORE UPDATE ON Expenses
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked expense record.';
    END IF;
END$$
DELIMITER ;

-- ==================
-- 7. Auto-lock inventory when ending snapshot is inserted
-- ==================
-- NOTE: Originally implemented as an AFTER INSERT trigger here, but MySQL forbids
-- a trigger from updating its own table (error 1442). Logic moved into
-- InventoryModel::createBatchSnapshots() which locks the day's snapshots after
-- the ending batch insert commits.
DROP TRIGGER IF EXISTS trg_auto_lock_inventory;

-- ==================
-- 8. Audit log on inventory unlock (status change from 1 to 0)
-- ==================
DROP TRIGGER IF EXISTS trg_audit_inventory_unlock;
DELIMITER $$
CREATE TRIGGER trg_audit_inventory_unlock
AFTER UPDATE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 0 THEN
        INSERT INTO Audit_Log (User_ID, Action, Details, Timestamp)
        VALUES (NEW.User_ID, 'RECORD_UNLOCKED',
            CONCAT('Inventory_Snapshot ID:', NEW.Inventory_ID, ' unlocked for ', NEW.Snapshot_date),
            NOW());
    END IF;
END$$
DELIMITER ;

-- ============================================
-- End of triggers
-- ============================================
