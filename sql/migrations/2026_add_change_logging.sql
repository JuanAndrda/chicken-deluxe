-- ============================================================
-- Migration: Expand Audit_Log + add AFTER INSERT/UPDATE/DELETE
--            change-logging triggers for all major tables
-- Date    : 2026-04-18
-- Reason  : IM Final Project rubric §1.3 — requires full change
--           logging with operation type, table name, OLD and NEW
--           values, timestamp, and user.
-- Run on  : MASTER ONLY (port 3306). Slave replicates automatically.
-- ============================================================

USE chicken_deluxe;

-- ------------------------------------------------------------
-- Step 1: Add new columns to Audit_Log (kept existing columns
--         Log_ID, User_ID, Action, Details, Timestamp intact).
-- ------------------------------------------------------------
ALTER TABLE Audit_Log
    ADD COLUMN IF NOT EXISTS Operation  VARCHAR(10)  NULL AFTER Action,
    ADD COLUMN IF NOT EXISTS Table_name VARCHAR(100) NULL AFTER Operation,
    ADD COLUMN IF NOT EXISTS Old_values TEXT         NULL AFTER Table_name,
    ADD COLUMN IF NOT EXISTS New_values TEXT         NULL AFTER Old_values;

-- ============================================================
-- Step 2: Change-logging triggers.
--
-- Naming convention:  trg_log_<table>_<event>
-- Pattern:            AFTER INSERT / UPDATE / DELETE per table
-- Columns captured:   all non-sensitive columns as JSON_OBJECT
--                     (Password is deliberately excluded from
--                     User triggers for security)
--
-- User_ID semantics in Audit_Log:
--   - For tables that carry a User_ID column, we use NEW.User_ID
--     (INSERT/UPDATE) or OLD.User_ID (DELETE).
--   - For Product / Kiosk / User (no User_ID column in the row),
--     we store NULL — the Details text still identifies the record.
-- ============================================================


-- ============================================================
--   SALES  (has User_ID)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_sales_insert;
DELIMITER $$
CREATE TRIGGER trg_log_sales_insert
AFTER INSERT ON Sales
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'INSERT', 'INSERT', 'Sales', NULL,
        JSON_OBJECT(
            'Sales_ID',      NEW.Sales_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'User_ID',       NEW.User_ID,
            'Product_ID',    NEW.Product_ID,
            'Sales_date',    NEW.Sales_date,
            'Quantity_sold', NEW.Quantity_sold,
            'Unit_Price',    NEW.Unit_Price,
            'Line_total',    NEW.Line_total,
            'Locked_status', NEW.Locked_status
        ),
        CONCAT('New record inserted into Sales ID:', NEW.Sales_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_sales_update;
DELIMITER $$
CREATE TRIGGER trg_log_sales_update
AFTER UPDATE ON Sales
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'UPDATE', 'UPDATE', 'Sales',
        JSON_OBJECT(
            'Sales_ID',      OLD.Sales_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'User_ID',       OLD.User_ID,
            'Product_ID',    OLD.Product_ID,
            'Sales_date',    OLD.Sales_date,
            'Quantity_sold', OLD.Quantity_sold,
            'Unit_Price',    OLD.Unit_Price,
            'Line_total',    OLD.Line_total,
            'Locked_status', OLD.Locked_status
        ),
        JSON_OBJECT(
            'Sales_ID',      NEW.Sales_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'User_ID',       NEW.User_ID,
            'Product_ID',    NEW.Product_ID,
            'Sales_date',    NEW.Sales_date,
            'Quantity_sold', NEW.Quantity_sold,
            'Unit_Price',    NEW.Unit_Price,
            'Line_total',    NEW.Line_total,
            'Locked_status', NEW.Locked_status
        ),
        CONCAT('Record updated in Sales ID:', NEW.Sales_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_sales_delete;
DELIMITER $$
CREATE TRIGGER trg_log_sales_delete
AFTER DELETE ON Sales
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (OLD.User_ID, 'DELETE', 'DELETE', 'Sales',
        JSON_OBJECT(
            'Sales_ID',      OLD.Sales_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'User_ID',       OLD.User_ID,
            'Product_ID',    OLD.Product_ID,
            'Sales_date',    OLD.Sales_date,
            'Quantity_sold', OLD.Quantity_sold,
            'Unit_Price',    OLD.Unit_Price,
            'Line_total',    OLD.Line_total,
            'Locked_status', OLD.Locked_status
        ),
        NULL,
        CONCAT('Record deleted from Sales ID:', OLD.Sales_ID),
        NOW());
END$$
DELIMITER ;


-- ============================================================
--   INVENTORY_SNAPSHOT  (has User_ID)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_inventory_snapshot_insert;
DELIMITER $$
CREATE TRIGGER trg_log_inventory_snapshot_insert
AFTER INSERT ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'INSERT', 'INSERT', 'Inventory_Snapshot', NULL,
        JSON_OBJECT(
            'Inventory_ID',  NEW.Inventory_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'Product_ID',    NEW.Product_ID,
            'User_ID',       NEW.User_ID,
            'Locked_status', NEW.Locked_status,
            'Snapshot_date', NEW.Snapshot_date,
            'Snapshot_type', NEW.Snapshot_type,
            'Quantity',      NEW.Quantity
        ),
        CONCAT('New record inserted into Inventory_Snapshot ID:', NEW.Inventory_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_inventory_snapshot_update;
DELIMITER $$
CREATE TRIGGER trg_log_inventory_snapshot_update
AFTER UPDATE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'UPDATE', 'UPDATE', 'Inventory_Snapshot',
        JSON_OBJECT(
            'Inventory_ID',  OLD.Inventory_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'Product_ID',    OLD.Product_ID,
            'User_ID',       OLD.User_ID,
            'Locked_status', OLD.Locked_status,
            'Snapshot_date', OLD.Snapshot_date,
            'Snapshot_type', OLD.Snapshot_type,
            'Quantity',      OLD.Quantity
        ),
        JSON_OBJECT(
            'Inventory_ID',  NEW.Inventory_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'Product_ID',    NEW.Product_ID,
            'User_ID',       NEW.User_ID,
            'Locked_status', NEW.Locked_status,
            'Snapshot_date', NEW.Snapshot_date,
            'Snapshot_type', NEW.Snapshot_type,
            'Quantity',      NEW.Quantity
        ),
        CONCAT('Record updated in Inventory_Snapshot ID:', NEW.Inventory_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_inventory_snapshot_delete;
DELIMITER $$
CREATE TRIGGER trg_log_inventory_snapshot_delete
AFTER DELETE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (OLD.User_ID, 'DELETE', 'DELETE', 'Inventory_Snapshot',
        JSON_OBJECT(
            'Inventory_ID',  OLD.Inventory_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'Product_ID',    OLD.Product_ID,
            'User_ID',       OLD.User_ID,
            'Locked_status', OLD.Locked_status,
            'Snapshot_date', OLD.Snapshot_date,
            'Snapshot_type', OLD.Snapshot_type,
            'Quantity',      OLD.Quantity
        ),
        NULL,
        CONCAT('Record deleted from Inventory_Snapshot ID:', OLD.Inventory_ID),
        NOW());
END$$
DELIMITER ;


-- ============================================================
--   DELIVERY  (has User_ID)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_delivery_insert;
DELIMITER $$
CREATE TRIGGER trg_log_delivery_insert
AFTER INSERT ON Delivery
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'INSERT', 'INSERT', 'Delivery', NULL,
        JSON_OBJECT(
            'Delivery_ID',   NEW.Delivery_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'User_ID',       NEW.User_ID,
            'Product_ID',    NEW.Product_ID,
            'Delivery_Date', NEW.Delivery_Date,
            'Quantity',      NEW.Quantity,
            'Locked_status', NEW.Locked_status
        ),
        CONCAT('New record inserted into Delivery ID:', NEW.Delivery_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_delivery_update;
DELIMITER $$
CREATE TRIGGER trg_log_delivery_update
AFTER UPDATE ON Delivery
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'UPDATE', 'UPDATE', 'Delivery',
        JSON_OBJECT(
            'Delivery_ID',   OLD.Delivery_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'User_ID',       OLD.User_ID,
            'Product_ID',    OLD.Product_ID,
            'Delivery_Date', OLD.Delivery_Date,
            'Quantity',      OLD.Quantity,
            'Locked_status', OLD.Locked_status
        ),
        JSON_OBJECT(
            'Delivery_ID',   NEW.Delivery_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'User_ID',       NEW.User_ID,
            'Product_ID',    NEW.Product_ID,
            'Delivery_Date', NEW.Delivery_Date,
            'Quantity',      NEW.Quantity,
            'Locked_status', NEW.Locked_status
        ),
        CONCAT('Record updated in Delivery ID:', NEW.Delivery_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_delivery_delete;
DELIMITER $$
CREATE TRIGGER trg_log_delivery_delete
AFTER DELETE ON Delivery
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (OLD.User_ID, 'DELETE', 'DELETE', 'Delivery',
        JSON_OBJECT(
            'Delivery_ID',   OLD.Delivery_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'User_ID',       OLD.User_ID,
            'Product_ID',    OLD.Product_ID,
            'Delivery_Date', OLD.Delivery_Date,
            'Quantity',      OLD.Quantity,
            'Locked_status', OLD.Locked_status
        ),
        NULL,
        CONCAT('Record deleted from Delivery ID:', OLD.Delivery_ID),
        NOW());
END$$
DELIMITER ;


-- ============================================================
--   EXPENSES  (has User_ID)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_expenses_insert;
DELIMITER $$
CREATE TRIGGER trg_log_expenses_insert
AFTER INSERT ON Expenses
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'INSERT', 'INSERT', 'Expenses', NULL,
        JSON_OBJECT(
            'Expense_ID',    NEW.Expense_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'User_ID',       NEW.User_ID,
            'Expense_date',  NEW.Expense_date,
            'Amount',        NEW.Amount,
            'Description',   NEW.Description,
            'Locked_status', NEW.Locked_status
        ),
        CONCAT('New record inserted into Expenses ID:', NEW.Expense_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_expenses_update;
DELIMITER $$
CREATE TRIGGER trg_log_expenses_update
AFTER UPDATE ON Expenses
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'UPDATE', 'UPDATE', 'Expenses',
        JSON_OBJECT(
            'Expense_ID',    OLD.Expense_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'User_ID',       OLD.User_ID,
            'Expense_date',  OLD.Expense_date,
            'Amount',        OLD.Amount,
            'Description',   OLD.Description,
            'Locked_status', OLD.Locked_status
        ),
        JSON_OBJECT(
            'Expense_ID',    NEW.Expense_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'User_ID',       NEW.User_ID,
            'Expense_date',  NEW.Expense_date,
            'Amount',        NEW.Amount,
            'Description',   NEW.Description,
            'Locked_status', NEW.Locked_status
        ),
        CONCAT('Record updated in Expenses ID:', NEW.Expense_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_expenses_delete;
DELIMITER $$
CREATE TRIGGER trg_log_expenses_delete
AFTER DELETE ON Expenses
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (OLD.User_ID, 'DELETE', 'DELETE', 'Expenses',
        JSON_OBJECT(
            'Expense_ID',    OLD.Expense_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'User_ID',       OLD.User_ID,
            'Expense_date',  OLD.Expense_date,
            'Amount',        OLD.Amount,
            'Description',   OLD.Description,
            'Locked_status', OLD.Locked_status
        ),
        NULL,
        CONCAT('Record deleted from Expenses ID:', OLD.Expense_ID),
        NOW());
END$$
DELIMITER ;


-- ============================================================
--   PRODUCT  (no User_ID column -> audit User_ID = NULL)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_product_insert;
DELIMITER $$
CREATE TRIGGER trg_log_product_insert
AFTER INSERT ON Product
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'INSERT', 'INSERT', 'Product', NULL,
        JSON_OBJECT(
            'Product_ID',  NEW.Product_ID,
            'Category_ID', NEW.Category_ID,
            'Name',        NEW.Name,
            'Unit',        NEW.Unit,
            'Price',       NEW.Price,
            'Active',      NEW.Active
        ),
        CONCAT('New record inserted into Product ID:', NEW.Product_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_product_update;
DELIMITER $$
CREATE TRIGGER trg_log_product_update
AFTER UPDATE ON Product
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'UPDATE', 'UPDATE', 'Product',
        JSON_OBJECT(
            'Product_ID',  OLD.Product_ID,
            'Category_ID', OLD.Category_ID,
            'Name',        OLD.Name,
            'Unit',        OLD.Unit,
            'Price',       OLD.Price,
            'Active',      OLD.Active
        ),
        JSON_OBJECT(
            'Product_ID',  NEW.Product_ID,
            'Category_ID', NEW.Category_ID,
            'Name',        NEW.Name,
            'Unit',        NEW.Unit,
            'Price',       NEW.Price,
            'Active',      NEW.Active
        ),
        CONCAT('Record updated in Product ID:', NEW.Product_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_product_delete;
DELIMITER $$
CREATE TRIGGER trg_log_product_delete
AFTER DELETE ON Product
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'DELETE', 'DELETE', 'Product',
        JSON_OBJECT(
            'Product_ID',  OLD.Product_ID,
            'Category_ID', OLD.Category_ID,
            'Name',        OLD.Name,
            'Unit',        OLD.Unit,
            'Price',       OLD.Price,
            'Active',      OLD.Active
        ),
        NULL,
        CONCAT('Record deleted from Product ID:', OLD.Product_ID),
        NOW());
END$$
DELIMITER ;


-- ============================================================
--   USER  (audit User_ID = NULL; Password intentionally excluded)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_user_insert;
DELIMITER $$
CREATE TRIGGER trg_log_user_insert
AFTER INSERT ON User
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'INSERT', 'INSERT', 'User', NULL,
        JSON_OBJECT(
            'User_ID',       NEW.User_ID,
            'Role_ID',       NEW.Role_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'Username',      NEW.Username,
            'Full_name',     NEW.Full_name,
            'Active_status', NEW.Active_status
        ),
        CONCAT('New record inserted into User ID:', NEW.User_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_user_update;
DELIMITER $$
CREATE TRIGGER trg_log_user_update
AFTER UPDATE ON User
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'UPDATE', 'UPDATE', 'User',
        JSON_OBJECT(
            'User_ID',       OLD.User_ID,
            'Role_ID',       OLD.Role_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'Username',      OLD.Username,
            'Full_name',     OLD.Full_name,
            'Active_status', OLD.Active_status
        ),
        JSON_OBJECT(
            'User_ID',       NEW.User_ID,
            'Role_ID',       NEW.Role_ID,
            'Kiosk_ID',      NEW.Kiosk_ID,
            'Username',      NEW.Username,
            'Full_name',     NEW.Full_name,
            'Active_status', NEW.Active_status
        ),
        CONCAT('Record updated in User ID:', NEW.User_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_user_delete;
DELIMITER $$
CREATE TRIGGER trg_log_user_delete
AFTER DELETE ON User
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'DELETE', 'DELETE', 'User',
        JSON_OBJECT(
            'User_ID',       OLD.User_ID,
            'Role_ID',       OLD.Role_ID,
            'Kiosk_ID',      OLD.Kiosk_ID,
            'Username',      OLD.Username,
            'Full_name',     OLD.Full_name,
            'Active_status', OLD.Active_status
        ),
        NULL,
        CONCAT('Record deleted from User ID:', OLD.User_ID),
        NOW());
END$$
DELIMITER ;


-- ============================================================
--   KIOSK  (audit User_ID = NULL)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_kiosk_insert;
DELIMITER $$
CREATE TRIGGER trg_log_kiosk_insert
AFTER INSERT ON Kiosk
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'INSERT', 'INSERT', 'Kiosk', NULL,
        JSON_OBJECT(
            'Kiosk_ID', NEW.Kiosk_ID,
            'Name',     NEW.Name,
            'Location', NEW.Location,
            'Active',   NEW.Active
        ),
        CONCAT('New record inserted into Kiosk ID:', NEW.Kiosk_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_kiosk_update;
DELIMITER $$
CREATE TRIGGER trg_log_kiosk_update
AFTER UPDATE ON Kiosk
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'UPDATE', 'UPDATE', 'Kiosk',
        JSON_OBJECT(
            'Kiosk_ID', OLD.Kiosk_ID,
            'Name',     OLD.Name,
            'Location', OLD.Location,
            'Active',   OLD.Active
        ),
        JSON_OBJECT(
            'Kiosk_ID', NEW.Kiosk_ID,
            'Name',     NEW.Name,
            'Location', NEW.Location,
            'Active',   NEW.Active
        ),
        CONCAT('Record updated in Kiosk ID:', NEW.Kiosk_ID),
        NOW());
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_log_kiosk_delete;
DELIMITER $$
CREATE TRIGGER trg_log_kiosk_delete
AFTER DELETE ON Kiosk
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'DELETE', 'DELETE', 'Kiosk',
        JSON_OBJECT(
            'Kiosk_ID', OLD.Kiosk_ID,
            'Name',     OLD.Name,
            'Location', OLD.Location,
            'Active',   OLD.Active
        ),
        NULL,
        CONCAT('Record deleted from Kiosk ID:', OLD.Kiosk_ID),
        NOW());
END$$
DELIMITER ;

-- ============================================================
-- End of migration
-- ============================================================
