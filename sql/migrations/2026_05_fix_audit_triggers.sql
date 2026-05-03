-- ============================================================
-- Migration: Fix audit-trail triggers for parts-based system
-- Date:      2026-05-03
-- ============================================================
-- Problems this fixes (verified live against the audit_log table):
--
--   1. The 3 Inventory_Snapshot change-logging triggers logged
--      `Product_ID` (always NULL post-migration) and never logged
--      `Part_ID` -> audit rows showed "Product_ID: null" forever
--      and you could not tell which part was changed.
--
--   2. The 3 Delivery change-logging triggers had the same gap
--      AND also omitted `Type` (Delivery vs Pullout) and `Notes`
--      (the pullout reason).  An audit row could not even tell you
--      whether the operation was a delivery or a pullout.
--
--   3. There was no `trg_log_product_part_update` -> changes to
--      a recipe's Quantity_needed were silent.
--
--   4. `trg_audit_inventory_unlock` wrote a half-empty Audit_Log
--      row (Operation/Table_name/Old_values/New_values all NULL)
--      AND fired alongside `trg_log_inventory_snapshot_update`,
--      so every inventory unlock generated 2 trigger rows + the
--      app-level row = 3 audit entries for one user action.
--      Dropped: the generic update trigger already captures the
--      unlock with full Operation+Old/New values.
--
-- After this migration: 33 -> 31 active triggers (7 business-rule +
-- 24 change-logging) and Audit_Log rows finally carry Part_ID/Type/
-- Notes for the parts-based operational tables.
-- ============================================================

-- ----------------------------------------------------------------
-- 1. Inventory_Snapshot change-logging triggers — add Part_ID
-- ----------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_log_inventory_snapshot_insert;
DELIMITER $$
CREATE TRIGGER trg_log_inventory_snapshot_insert
AFTER INSERT ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'INSERT', 'INSERT', 'Inventory_Snapshot', NULL,
        JSON_OBJECT('Inventory_ID', NEW.Inventory_ID, 'Kiosk_ID', NEW.Kiosk_ID,
            'Product_ID', NEW.Product_ID, 'Part_ID', NEW.Part_ID,
            'User_ID', NEW.User_ID, 'Locked_status', NEW.Locked_status,
            'Snapshot_date', NEW.Snapshot_date, 'Snapshot_type', NEW.Snapshot_type,
            'Quantity', NEW.Quantity),
        CONCAT('New record inserted into Inventory_Snapshot ID:', NEW.Inventory_ID), NOW());
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
        JSON_OBJECT('Inventory_ID', OLD.Inventory_ID, 'Kiosk_ID', OLD.Kiosk_ID,
            'Product_ID', OLD.Product_ID, 'Part_ID', OLD.Part_ID,
            'User_ID', OLD.User_ID, 'Locked_status', OLD.Locked_status,
            'Snapshot_date', OLD.Snapshot_date, 'Snapshot_type', OLD.Snapshot_type,
            'Quantity', OLD.Quantity),
        JSON_OBJECT('Inventory_ID', NEW.Inventory_ID, 'Kiosk_ID', NEW.Kiosk_ID,
            'Product_ID', NEW.Product_ID, 'Part_ID', NEW.Part_ID,
            'User_ID', NEW.User_ID, 'Locked_status', NEW.Locked_status,
            'Snapshot_date', NEW.Snapshot_date, 'Snapshot_type', NEW.Snapshot_type,
            'Quantity', NEW.Quantity),
        CONCAT('Record updated in Inventory_Snapshot ID:', NEW.Inventory_ID), NOW());
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
        JSON_OBJECT('Inventory_ID', OLD.Inventory_ID, 'Kiosk_ID', OLD.Kiosk_ID,
            'Product_ID', OLD.Product_ID, 'Part_ID', OLD.Part_ID,
            'User_ID', OLD.User_ID, 'Locked_status', OLD.Locked_status,
            'Snapshot_date', OLD.Snapshot_date, 'Snapshot_type', OLD.Snapshot_type,
            'Quantity', OLD.Quantity),
        NULL, CONCAT('Record deleted from Inventory_Snapshot ID:', OLD.Inventory_ID), NOW());
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 2. Delivery change-logging triggers — add Part_ID, Type, Notes
-- ----------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_log_delivery_insert;
DELIMITER $$
CREATE TRIGGER trg_log_delivery_insert
AFTER INSERT ON Delivery
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NEW.User_ID, 'INSERT', 'INSERT', 'Delivery', NULL,
        JSON_OBJECT('Delivery_ID', NEW.Delivery_ID, 'Kiosk_ID', NEW.Kiosk_ID,
            'User_ID', NEW.User_ID, 'Product_ID', NEW.Product_ID, 'Part_ID', NEW.Part_ID,
            'Delivery_Date', NEW.Delivery_Date, 'Quantity', NEW.Quantity,
            'Type', NEW.Type, 'Notes', NEW.Notes, 'Locked_status', NEW.Locked_status),
        CONCAT('New record inserted into Delivery ID:', NEW.Delivery_ID), NOW());
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
        JSON_OBJECT('Delivery_ID', OLD.Delivery_ID, 'Kiosk_ID', OLD.Kiosk_ID,
            'User_ID', OLD.User_ID, 'Product_ID', OLD.Product_ID, 'Part_ID', OLD.Part_ID,
            'Delivery_Date', OLD.Delivery_Date, 'Quantity', OLD.Quantity,
            'Type', OLD.Type, 'Notes', OLD.Notes, 'Locked_status', OLD.Locked_status),
        JSON_OBJECT('Delivery_ID', NEW.Delivery_ID, 'Kiosk_ID', NEW.Kiosk_ID,
            'User_ID', NEW.User_ID, 'Product_ID', NEW.Product_ID, 'Part_ID', NEW.Part_ID,
            'Delivery_Date', NEW.Delivery_Date, 'Quantity', NEW.Quantity,
            'Type', NEW.Type, 'Notes', NEW.Notes, 'Locked_status', NEW.Locked_status),
        CONCAT('Record updated in Delivery ID:', NEW.Delivery_ID), NOW());
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
        JSON_OBJECT('Delivery_ID', OLD.Delivery_ID, 'Kiosk_ID', OLD.Kiosk_ID,
            'User_ID', OLD.User_ID, 'Product_ID', OLD.Product_ID, 'Part_ID', OLD.Part_ID,
            'Delivery_Date', OLD.Delivery_Date, 'Quantity', OLD.Quantity,
            'Type', OLD.Type, 'Notes', OLD.Notes, 'Locked_status', OLD.Locked_status),
        NULL, CONCAT('Record deleted from Delivery ID:', OLD.Delivery_ID), NOW());
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 3. Product_Part — add the missing UPDATE trigger
-- ----------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_log_product_part_update;
DELIMITER $$
CREATE TRIGGER trg_log_product_part_update
AFTER UPDATE ON Product_Part
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (User_ID, Action, Operation, Table_name, Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'UPDATE', 'UPDATE', 'Product_Part',
        JSON_OBJECT('Product_Part_ID', OLD.Product_Part_ID, 'Product_ID', OLD.Product_ID,
            'Part_ID', OLD.Part_ID, 'Quantity_needed', OLD.Quantity_needed),
        JSON_OBJECT('Product_Part_ID', NEW.Product_Part_ID, 'Product_ID', NEW.Product_ID,
            'Part_ID', NEW.Part_ID, 'Quantity_needed', NEW.Quantity_needed),
        CONCAT('Recipe row updated: Product_Part ID:', NEW.Product_Part_ID), NOW());
END$$
DELIMITER ;

-- ----------------------------------------------------------------
-- 4. Drop trg_audit_inventory_unlock (redundant + half-empty)
-- ----------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_audit_inventory_unlock;

-- ============================================================
-- Verification queries (run manually after this migration):
--
--   -- Should return 31:
--   SELECT COUNT(*) FROM information_schema.TRIGGERS
--    WHERE TRIGGER_SCHEMA = 'chicken_deluxe';
--
--   -- Should return 0 (trigger is gone):
--   SELECT COUNT(*) FROM information_schema.TRIGGERS
--    WHERE TRIGGER_NAME = 'trg_audit_inventory_unlock';
--
--   -- Make a parts-based delivery + check the resulting audit row
--   -- now contains Part_ID, Type, and Notes:
--   INSERT INTO Delivery (Kiosk_ID, User_ID, Part_ID, Delivery_Date,
--                         Quantity, Type, Notes)
--   VALUES (1, 1, 1, CURDATE(), 5, 'Delivery', 'test');
--   SELECT New_values FROM Audit_Log
--    ORDER BY Log_ID DESC LIMIT 1;
-- ============================================================
