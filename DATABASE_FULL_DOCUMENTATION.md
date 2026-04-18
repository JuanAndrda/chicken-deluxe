# DATABASE — FULL DOCUMENTATION
## Chicken Deluxe Inventory & Sales Monitoring System

> **Project:** Chicken Deluxe Inventory & Sales Monitoring System
> **Group:** Group 7 — BSIT 2-B
> **Database:** `chicken_deluxe` (MySQL / MariaDB, master-slave replication)
> **Source files:** [`sql/schema.sql`](sql/schema.sql), [`sql/triggers.sql`](sql/triggers.sql)
> **Last updated:** April 2026

---

### How to read this document

This is the **single combined database reference** for the project. For every table you will see:

1. **Purpose** — what the table is for, in one or two sentences
2. **Used by** — which of the 6 processes (A–F) reads/writes it
3. **ERD Status** — whether the table is unchanged from the draw.io ERD, modified, or brand new
4. **Why it was added / changed** — if it's new or modified, this explains the reasoning
5. **Attributes** — every column listed with:
   - **Column Name** — exact column name in the schema
   - **Data Type** — MySQL type
   - **Constraints** — PK / FK / NULL rules / defaults
   - **Description** — what it stores, in plain English
   - **Why It Matters** — extra explanation for important or newly-added columns (left as `—` for routine columns like `Created_at`)

After all the tables, the document covers SQL triggers, indexes, foreign keys, replication notes, and a one-page summary at the end.

**Process codes:**

| Code | Process |
|------|---------|
| **A** | User Access & Role Management |
| **B** | Inventory Management |
| **C** | Delivery Management |
| **D** | Sales & Expense Recording |
| **E** | Reference Data Management |
| **F** | Reporting & Monitoring |

---
---

# PART 1 — TABLE REFERENCE

The database has **11 tables**, listed in the order they appear in `schema.sql`.

---

## TABLE 1 — `Role`

- **Purpose:** Stores the three user roles available in the system. Every user belongs to exactly one role, which controls what they can see and do.
- **Used By:** Process **A** (User Access & Role Management).
- **ERD Status:** ✅ Unchanged from the original ERD.
- **Seed data:** `(1, 'Owner')`, `(2, 'Staff')`, `(3, 'Auditor')` — inserted automatically.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Role_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each role | Used everywhere as a foreign key — keeping it as a number (not a text label) means the Owner can rename a role without breaking links |
| `Name` | VARCHAR(50) | NOT NULL, UNIQUE | The role's display name (`Owner`, `Staff`, `Auditor`) | UNIQUE prevents creating two roles with the same name |

---

## TABLE 2 — `Kiosk`

- **Purpose:** Stores the 5 physical kiosk locations where the business operates. Every sale, delivery, expense, and inventory record links back to a kiosk.
- **Used By:** Process **E** (Reference Data) — and indirectly **every** other process via `Outlet_ID`.
- **ERD Status:** ⚠️ **MODIFIED** — the ERD listed wrong columns (`Role_ID`, `Outlet_ID`, `Password` — a copy-paste error from the User table). Replaced with the correct kiosk columns.
- **Why it was changed:** A kiosk is a physical place. It needs a name and an address — not a role or a password. The Owner can also temporarily disable a kiosk (`Active = 0`) without deleting it.
- **Seed data:** Tagbak, Atrium, City Proper, Supermart, Aldeguer.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Kiosk_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each kiosk | Used as the foreign key (`Outlet_ID`) on every operational table |
| `Name` | VARCHAR(100) | NOT NULL | Short kiosk name shown in headers and dropdowns (e.g. "Tagbak Branch") | Lets the UI show a human-readable label instead of an ID number |
| `Location` | VARCHAR(255) | NOT NULL | Full address of the kiosk | Used in reports and on the Manage Kiosks admin page |
| `Active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Tells the system whether this kiosk is currently operating. 1 = open, 0 = temporarily disabled | Lets the Owner pause a kiosk without deleting historical records (deleting would orphan past sales) |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time this kiosk record was first added | — |

---

## TABLE 3 — `User`

- **Purpose:** Stores all the people who can log in — the Owner, kiosk staff, and any auditors. Their assigned role and (for staff) their assigned kiosk live here.
- **Used By:** Process **A** primarily; every other process needs `User_ID` for record-tracking and audit trails.
- **ERD Status:** ⚠️ **MODIFIED** — added `Full_name` column for display purposes; clarified `Outlet_ID` as nullable.
- **Why it was changed:** The ERD listed Password but not `Full_name`. We need a real name to show in headers ("Cherryll Laud") instead of a username ("owner1"), and to make audit log entries readable. `Outlet_ID` is nullable because Owner and Auditor are not tied to a single kiosk.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `User_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each user | Foreign key on every operational table for record-tracking |
| `Role_ID` | INT | NOT NULL, FK → `Role(Role_ID)` | Tells the system whether this user is Owner, Staff, or Auditor | Drives every role check — what menu appears, what they can edit, etc. |
| `Outlet_ID` | INT | **NULL allowed**, FK → `Kiosk(Kiosk_ID)` | The kiosk this user belongs to | NULL for Owner/Auditor so they can see all kiosks. For Staff this enforces "you can only see your own kiosk" |
| `Username` | VARCHAR(50) | NOT NULL, UNIQUE | The name typed at the login screen | UNIQUE prevents two users sharing a login — defense-in-depth at the DB level |
| `Password` | VARCHAR(255) | NOT NULL | Encrypted password (bcrypt hash) | 255 characters because bcrypt produces 60-character hashes today, and the column is sized to allow stronger algorithms later |
| `Full_name` | VARCHAR(100) | NOT NULL | Real name shown in the header bar and audit log entries | **NEW vs ERD.** Without it, audit logs and headers would only show usernames — Cherryll wanted to see real names |
| `Active_status` | TINYINT(1) | NOT NULL, DEFAULT 1 | Tells the system whether this account can still log in. 1 = active, 0 = deactivated | Lets the Owner disable a user (e.g. resigned staff) without deleting their past records |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time this user account was created | — |

---

## TABLE 4 — `Category`

- **Purpose:** Stores the 5 menu categories so the POS can show category tabs and reports can break sales down by category.
- **Used By:** Process **E** (Reference Data) and Process **D** (Sales POS).
- **ERD Status:** 🆕 **NEW TABLE** — not in the original ERD.
- **Why it was added:** The ERD treated `Product` as a flat list. Without a Category table the POS couldn't group products into tabs (Burgers / Drinks / etc.), reports couldn't show category subtotals, and adding a new category required hardcoding it somewhere in the code.
- **Seed data:** Burgers, Drinks, Hotdogs, Ricebowl, Snacks.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Category_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each category | Used by `Product.Category_ID` to link products to their category |
| `Name` | VARCHAR(50) | NOT NULL, UNIQUE | The category's display name (e.g. "Burgers") | Shown on POS tabs and report subtotals |
| `Active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Whether this category is still in use. 1 = active, 0 = hidden | Lets the Owner retire a category without deleting old products linked to it |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time this category was first added | — |

---

## TABLE 5 — `Product`

- **Purpose:** Stores the master menu — every item the kiosks can sell. Each product belongs to one category and has an official price the system uses on every sale.
- **Used By:** Process **E** (Reference Data) primarily; Processes **B**, **C**, **D**, **F** all reference `Product_ID`.
- **ERD Status:** ⚠️ **MODIFIED** — added two columns: `Category_ID` and `Price`.
- **Why it was changed:**
  - `Category_ID` is required for the new `Category` table.
  - `Price` was originally only on the Sales row, which meant staff had to type the price for every transaction (where typos and abuse can happen). Putting the official price on `Product` lets the server look it up automatically and ignore whatever the form sends.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Product_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each product | Foreign key on Sales, Delivery, Inventory_Snapshot |
| `Category_ID` | INT | NOT NULL, FK → `Category(Category_ID)` | The category this product belongs to | **NEW vs ERD.** Drives POS tabs and report grouping |
| `Name` | VARCHAR(100) | NOT NULL | The product's display name | Shown on POS cards, sales table, reports |
| `Unit` | VARCHAR(30) | NOT NULL, DEFAULT `'pcs'` | Unit this product is counted in (pcs, cup, bottle, etc.) | So inventory and delivery tables make sense ("50 pcs" vs "50 cups") |
| `Price` | DECIMAL(10,2) | NOT NULL, DEFAULT `0.00` | Official selling price in pesos | **NEW vs ERD. Anti-tampering.** Server reads this on every sale; staff can't override it from the form |
| `Active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Whether this product is currently sold. 1 = active, 0 = removed from menu | Soft-delete — past sales of this product still work, but it stops appearing on POS |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time this product was first added | — |

---

## TABLE 6 — `Inventory_Snapshot`

- **Purpose:** Stores the daily stock count for every product at every kiosk. Each business day produces two rounds per product: a `beginning` snapshot (morning) and an `ending` snapshot (close of day). This is the heart of the inventory module.
- **Used By:** Process **B** (Inventory) and Process **F** (Daily Summary report).
- **ERD Status:** ⚠️ **MODIFIED** — same columns as the ERD, but with a new UNIQUE KEY constraint added.
- **Why it was changed:** Without `uq_snapshot`, a staff member could accidentally record "beginning stock" twice for the same product on the same day, which would silently corrupt the daily report. The unique key makes the database itself reject the duplicate.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Inventory_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each snapshot row | — |
| `Outlet_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk this stock count is for | Lets staff see only their kiosk; lets reports filter by kiosk |
| `Product_ID` | INT | NOT NULL, FK → `Product(Product_ID)` | The product being counted | — |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The user who recorded this count | Audit trail — we always know who entered the number |
| `Locked_status` | TINYINT(1) | NOT NULL, DEFAULT 0 | Whether this snapshot is locked. 1 = locked, 0 = still editable | **Core lock-out mechanism.** Once 1, only the Owner can flip it back to 0, and the trigger logs that unlock to the audit log |
| `Snapshot_date` | DATE | NOT NULL | The business day this snapshot is for | Used by the daily report query |
| `Snapshot_type` | ENUM(`beginning`, `ending`) | NOT NULL | Whether this row is the morning's beginning stock or end-of-day closing stock | **ENUM was chosen** so typos like "begining" are rejected by the DB itself |
| `Quantity` | INT | NOT NULL, DEFAULT 0 | The actual quantity on hand | The reason the row exists |
| `Recorded_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact timestamp when this row was saved | — |

**Special key:** `UNIQUE KEY uq_snapshot (Outlet_ID, Product_ID, Snapshot_date, Snapshot_type)` — prevents duplicate snapshots.

---

## TABLE 7 — `Delivery`

- **Purpose:** Records every stock delivery that arrives at a kiosk (e.g. supplier dropping off 50 burger patties). Feeds the daily inventory reconciliation report.
- **Used By:** Process **C** (Delivery) and Process **F** (Reports).
- **ERD Status:** ⚠️ **MODIFIED** — added `Locked_status` column.
- **Why it was changed:** The ERD didn't include a lock flag for deliveries, but the same business rule that protects past sales should protect past deliveries — once locked, they cannot be silently rewritten.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Delivery_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each delivery row | — |
| `Outlet_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk that received the delivery | — |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The user who recorded the delivery | Audit trail |
| `Product_ID` | INT | NOT NULL, FK → `Product(Product_ID)` | Which product was delivered | — |
| `Delivery_Date` | DATE | NOT NULL | The day the delivery arrived | Used by the daily report query |
| `Quantity` | INT | NOT NULL, DEFAULT 0 | How many units were delivered | The reason the row exists |
| `Locked_status` | TINYINT(1) | NOT NULL, DEFAULT 0 | Whether this delivery is locked. 1 = locked, 0 = still editable | **NEW vs ERD.** Same lock model as Sales — protects past data from accidental edits |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact timestamp when this row was saved | — |

---

## TABLE 8 — `Sales`

- **Purpose:** Records every sale transaction. One row = one product line on a sale. Used by the POS, daily total display, and all sales reports.
- **Used By:** Process **D** (Sales) and Process **F** (Reports).
- **ERD Status:** ⚠️ **MODIFIED** — removed the `Total_Amount` column from the ERD.
- **Why it was changed:** The ERD had both `Line_total` (per row) and `Total_Amount` (the day's total). Storing the daily total on every row duplicates information and goes out of sync the moment one row is edited or deleted. Daily totals are now computed at runtime with `SUM(Line_total)` — one source of truth.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Sales_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each sale line | — |
| `Outlet_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk where the sale happened | — |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The staff member who recorded the sale | Audit trail |
| `Product_ID` | INT | NOT NULL, FK → `Product(Product_ID)` | Which product was sold | — |
| `Sales_date` | DATE | NOT NULL | The day the sale was made | Used by the daily report query |
| `Quantity_sold` | INT | NOT NULL, DEFAULT 0 | How many units were sold | — |
| `Unit_Price` | DECIMAL(10,2) | NOT NULL, DEFAULT `0.00` | Price per unit at the time of sale | The server copies this from `Product.Price` so it's never trusted from the form |
| `Line_total` | DECIMAL(10,2) | NOT NULL, DEFAULT `0.00` | Total for this line — `Quantity_sold × Unit_Price` | **Set automatically by trigger.** Never trusted from user input — eliminates a whole class of fraud |
| `Locked_status` | TINYINT(1) | NOT NULL, DEFAULT 0 | Whether this sale is locked. 1 = locked, 0 = still editable | Same lock model as inventory — once locked, only the Owner can unlock |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact timestamp when this sale was saved | — |

---

## TABLE 9 — `Expenses`

- **Purpose:** Records every operational expense per kiosk per day (utilities, supplies, repairs, etc.). Feeds into the daily report so the Owner can see net profit.
- **Used By:** Process **D** (Expense Recording) and Process **F** (Reports).
- **ERD Status:** ⚠️ **MODIFIED** — added `Locked_status` column.
- **Why it was changed:** Same reasoning as Delivery — once an expense day is closed, the row should not be quietly edited.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Expense_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each expense row | — |
| `Outlet_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk that incurred the expense | — |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The user who recorded the expense | Audit trail |
| `Expense_date` | DATE | NOT NULL | The day the expense happened | Used by the daily report query |
| `Amount` | DECIMAL(10,2) | NOT NULL, DEFAULT `0.00` | The peso amount of the expense | — |
| `Description` | VARCHAR(255) | NOT NULL | Short note explaining what the expense was for | Lets the Owner read the expense list and understand each entry |
| `Locked_status` | TINYINT(1) | NOT NULL, DEFAULT 0 | Whether this expense is locked. 1 = locked, 0 = still editable | **NEW vs ERD.** Same lock model as Sales/Delivery |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact timestamp when this row was saved | — |

---

## TABLE 10 — `Audit_Log`

- **Purpose:** Permanent record of every important action — logins, creates, edits, deletes, locks, unlocks. The Owner uses this to investigate suspicious activity or track changes over time.
- **Used By:** **Every** process writes to it; Process **F** (Reports) reads from it for the audit trail report.
- **ERD Status:** ⚠️ **MODIFIED** — added `Details` column.
- **Why it was changed:** The ERD only had `Action` (a code like `UPDATE`). Without context, an audit log of "UPDATE", "UPDATE", "UPDATE" is useless — you can't tell what was updated. The `Details` column adds a human-readable description of each action.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Log_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each log entry | — |
| `User_ID` | INT | **NULL allowed**, FK → `User(User_ID)` | The user who performed the action | NULL is allowed for failed logins where the user is unknown |
| `Action` | VARCHAR(100) | NOT NULL | Short code for the action (`LOGIN`, `CREATE`, `UPDATE`, `RECORD_UNLOCKED`, etc.) | Lets reports filter by action type quickly |
| `Details` | VARCHAR(255) | NULL allowed | Human-readable description (e.g. "Updated product ID:14") | **NEW vs ERD.** Turns the audit log from a list of codes into a readable history |
| `Timestamp` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact date and time the action occurred | — |

---

## TABLE 11 — `Time_in`

- **Purpose:** Records every time-in punch by a staff member at a kiosk. Used for staff attendance monitoring.
- **Used By:** Process **A** (User Access) and Process **F** (Staff Monitoring report).
- **ERD Status:** 🆕 **NEW TABLE** — flagged in the ERD itself as `*(newly added)*` after the original draw.io diagram.
- **Why it was added:** The Owner asked for staff attendance tracking — who showed up, at which kiosk, when. Putting it in its own table (instead of stuffing more columns into `User`) means a single staff member can have many punches over time, which is the natural shape for attendance data.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Timein_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each time-in entry | — |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The staff member who clocked in | Lets the report show attendance by staff |
| `Outlet_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk where they clocked in | Lets the Owner see which kiosk each staff member worked at |
| `Timestamp` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact moment they clocked in | The reason the row exists |

---
---

# PART 2 — SQL TRIGGERS

> All triggers live in [`sql/triggers.sql`](sql/triggers.sql). They are created on the **Master** node only and replicate to the **Slave** automatically. Triggers were a coursework requirement and are used here for things that **must** always happen, no matter which page or developer wrote the surrounding code.

We have **7 active triggers** and **1 trigger that was removed** (because of a MySQL limitation, see Trigger 8).

---

### Trigger 1 — `trg_calc_line_total_insert`

- **Table:** `Sales`
- **Event:** `BEFORE INSERT`

**What it does (step by step):**
1. A new row is about to be inserted into `Sales`.
2. The trigger fires before the row is written.
3. It overwrites `NEW.Line_total` with `NEW.Quantity_sold × NEW.Unit_Price`.
4. The corrected row is then saved.

**Why it is needed:** Without this trigger, the application would have to remember to compute the line total. A bug or a tampered request could save a wrong total. The trigger guarantees the math is correct, every time.

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

---

### Trigger 2 — `trg_calc_line_total_update`

- **Table:** `Sales`
- **Event:** `BEFORE UPDATE`

**What it does (step by step):**
1. A `Sales` row is about to be updated (e.g. Owner edits the quantity).
2. The trigger fires before the update is committed.
3. It recalculates `NEW.Line_total` using the new quantity and price.
4. The corrected row is then saved.

**Why it is needed:** Without this, updating a sale row would leave `Line_total` stale (still showing the old amount), which would corrupt the daily report.

```sql
DROP TRIGGER IF EXISTS trg_calc_line_total_update;
DELIMITER $$
CREATE TRIGGER trg_calc_line_total_update
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END$$
DELIMITER ;
```

---

### Trigger 3 — `trg_prevent_locked_sales_edit`

- **Table:** `Sales`
- **Event:** `BEFORE UPDATE`

**What it does (step by step):**
1. A `Sales` row is about to be updated.
2. The trigger checks: was the row already locked (`OLD.Locked_status = 1`) AND is the update keeping it locked (`NEW.Locked_status = 1`)?
3. If both are true, the trigger raises SQL error `45000` with message *"Cannot modify a locked sales record."* and the update is aborted.
4. Unlocks (`1` → `0`) are explicitly allowed so the Owner can still unlock through the UI.

**Why it is needed:** The whole point of locking is that staff can't go back and rewrite history. If only the UI enforced this, anyone with direct DB access (phpMyAdmin, a buggy controller) could still edit a locked row. The trigger makes the lock real at the database level.

```sql
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
```

---

### Trigger 4 — `trg_prevent_locked_inventory_edit`

- **Table:** `Inventory_Snapshot`
- **Event:** `BEFORE UPDATE`

**What it does:** Same logic as Trigger 3 but for inventory snapshots. Once an inventory row is locked, no field can change unless the Owner unlocks it first.

**Why it is needed:** Same reasoning — locks must be enforced at the DB level so they can't be bypassed.

```sql
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
```

---

### Trigger 5 — `trg_prevent_locked_delivery_edit`

- **Table:** `Delivery`
- **Event:** `BEFORE UPDATE`

**What it does:** Same protection for delivery rows. Once locked, the quantity, product, or date cannot be changed.

```sql
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
```

---

### Trigger 6 — `trg_prevent_locked_expense_edit`

- **Table:** `Expenses`
- **Event:** `BEFORE UPDATE`

**What it does:** Same protection for expense rows. Once locked, the amount or description cannot be changed.

```sql
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
```

---

### Trigger 7 — `trg_audit_inventory_unlock`

- **Table:** `Inventory_Snapshot`
- **Event:** `AFTER UPDATE`

**What it does (step by step):**
1. An `Inventory_Snapshot` row was just updated.
2. The trigger checks: was this an unlock action (`OLD.Locked_status = 1` AND `NEW.Locked_status = 0`)?
3. If yes, it inserts a new row into `Audit_Log` with action `RECORD_UNLOCKED`, the user who did it, the snapshot ID, and the snapshot date.

**Why it is needed:** Unlocking past records is the most sensitive thing the Owner can do — it lets historical numbers be edited. The audit trail must show every unlock, every time, no exceptions. The trigger guarantees this even if a future developer forgets to write the audit log call.

```sql
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
```

---

### Trigger 8 (REMOVED) — `trg_auto_lock_inventory`

This trigger was originally planned in `PROJECT_CONTEXT.md`. It was supposed to fire `AFTER INSERT ON Inventory_Snapshot` and lock all snapshots for that day when an `ending` snapshot was added.

**Problem:** MySQL forbids a trigger from updating the same table that fired it — it raises **error 1442**.

**Solution:** The same logic now lives in PHP, inside `InventoryModel::createBatchSnapshots()`. Right after the ending batch is inserted, the model runs:

```php
if ($type === 'ending') {
    $this->db->write(
        "UPDATE Inventory_Snapshot SET Locked_status = 1
         WHERE Outlet_ID = ? AND Snapshot_date = ? AND Locked_status = 0",
        [$outlet_id, $snapshot_date]
    );
}
```

`triggers.sql` keeps a `DROP TRIGGER IF EXISTS trg_auto_lock_inventory;` line so a stale trigger on an old database is safely cleaned up.

---
---

# PART 3 — OTHER SQL ADDITIONS

## 3.1 ENUM Data Types

| Table | Column | Allowed Values | Why |
|-------|--------|----------------|-----|
| `Inventory_Snapshot` | `Snapshot_type` | `'beginning'`, `'ending'` | A snapshot can only be one of two things. ENUM means typos like `"begining"` are rejected by the database itself. |

## 3.2 Indexes / Unique Keys

| Table | Key Name | Columns | Purpose |
|-------|----------|---------|---------|
| `Inventory_Snapshot` | `uq_snapshot` (UNIQUE) | (Outlet_ID, Product_ID, Snapshot_date, Snapshot_type) | Prevents duplicate beginning/ending snapshots for the same kiosk + product + date |
| `User` | (UNIQUE on Username) | Username | Two users can't share a login name |
| `Role` | (UNIQUE on Name) | Name | Two roles can't share a name |
| `Category` | (UNIQUE on Name) | Name | Two categories can't share a name |

Primary keys also act as automatic indexes.

## 3.3 Foreign Key Constraints

The ERD only showed relationships as lines. The actual database enforces them with `FOREIGN KEY` constraints:

| Child Table | Foreign Key | References |
|-------------|------------|------------|
| `User` | `Role_ID` | `Role(Role_ID)` |
| `User` | `Outlet_ID` | `Kiosk(Kiosk_ID)` |
| `Product` | `Category_ID` | `Category(Category_ID)` |
| `Inventory_Snapshot` | `Outlet_ID` | `Kiosk(Kiosk_ID)` |
| `Inventory_Snapshot` | `Product_ID` | `Product(Product_ID)` |
| `Inventory_Snapshot` | `User_ID` | `User(User_ID)` |
| `Delivery` | `Outlet_ID` | `Kiosk(Kiosk_ID)` |
| `Delivery` | `Product_ID` | `Product(Product_ID)` |
| `Delivery` | `User_ID` | `User(User_ID)` |
| `Sales` | `Outlet_ID` | `Kiosk(Kiosk_ID)` |
| `Sales` | `Product_ID` | `Product(Product_ID)` |
| `Sales` | `User_ID` | `User(User_ID)` |
| `Expenses` | `Outlet_ID` | `Kiosk(Kiosk_ID)` |
| `Expenses` | `User_ID` | `User(User_ID)` |
| `Audit_Log` | `User_ID` | `User(User_ID)` |
| `Time_in` | `User_ID` | `User(User_ID)` |
| `Time_in` | `Outlet_ID` | `Kiosk(Kiosk_ID)` |

## 3.4 Stored Procedures and Views

**None.** The team deliberately kept the database simple — all cross-table logic that isn't a trigger lives in PHP models, where the team can read and modify it more easily.

## 3.5 Seed Data

The schema seeds the data we know is fixed at install time:

- **3 roles:** Owner, Staff, Auditor
- **5 kiosks:** Tagbak, Atrium, City Proper, Supermart, Aldeguer
- **5 categories:** Burgers, Drinks, Hotdogs, Ricebowl, Snacks

All seeds use `ON DUPLICATE KEY UPDATE` so re-running `schema.sql` is safe — it won't fail or overwrite valid edits.

---
---

# PART 4 — MASTER-SLAVE REPLICATION NOTES

## 4.1 Tables That Are Write-Heavy (Master)

These tables get inserted/updated constantly during a business day and must live on the Master node (port 3306):

| Table | Why It's Write-Heavy |
|-------|----------------------|
| `Sales` | Every kiosk transaction inserts a row |
| `Inventory_Snapshot` | Two rounds per product per day per kiosk |
| `Delivery` | Every supplier delivery is logged |
| `Expenses` | Every operational expense is logged |
| `Audit_Log` | Every user action inserts a row |
| `Time_in` | Every staff time-in punches a new row |

## 4.2 Queries Routed to the Slave

All `SELECT` queries go through `Database::read()` to the Slave node (port 3307). This includes:

- Loading the product menu on every POS page render (heavy — 33 rows × every page load)
- Loading kiosk dropdowns and category tabs
- The reporting page (joins Sales × Product × Category × Kiosk over a date range — the heaviest read consumer)
- Listing past deliveries, sales, expenses for the records table
- Auth lookups (find user by username on login)

## 4.3 Schema Decisions Driven by Replication

- **Triggers are created on the Master only.** They replicate to the Slave automatically as part of the binary log.
- **`AUTO_INCREMENT` IDs are generated on the Master.** The Slave just copies them. This is why no model is allowed to insert through the Slave connection.
- **`ENGINE=InnoDB` everywhere.** InnoDB supports row-based replication AND foreign keys, both of which we depend on. MyISAM would have broken both.
- **`utf8mb4` charset is set on the Master**, replicated to the Slave, so encoding can never disagree across nodes (important for product names with special characters).
- **`SIGNAL SQLSTATE '45000'` errors from triggers also replicate.** If the Slave is ever promoted to Master in a disaster scenario, the locking rules still apply — no business rule is lost.

---
---

# PART 5 — SUMMARY TABLE

Every new or different item from the original ERD, in one place:

| Item Name | Type | Affected Table | Purpose |
|-----------|------|----------------|---------|
| `Category` | New table | (its own) | Group products into menu categories |
| `Time_in` | New table | (its own) | Track staff attendance per kiosk |
| `Kiosk.Name` / `Location` / `Active` | Modified columns | `Kiosk` | Replaced wrong ERD columns with proper kiosk fields |
| `Category_ID` | New column | `Product` | Link each product to its category |
| `Price` | New column | `Product` | Official selling price; used by server on every sale (anti-tampering) |
| `Full_name` | New column | `User` | Display name for UI and audit log |
| `Locked_status` | New column | `Delivery` | Allow Owner to lock past delivery rows |
| `Locked_status` | New column | `Expenses` | Allow Owner to lock past expense rows |
| `Details` | New column | `Audit_Log` | Human-readable description of audited action |
| `Total_Amount` | Removed column | `Sales` | Daily totals computed at runtime — no duplicate storage |
| `uq_snapshot` | Unique key | `Inventory_Snapshot` | Prevent duplicate beginning/ending snapshots |
| `Snapshot_type` | ENUM | `Inventory_Snapshot` | Restrict value to `'beginning'` or `'ending'` |
| `trg_calc_line_total_insert` | Trigger | `Sales` | Auto-compute `Line_total` on INSERT |
| `trg_calc_line_total_update` | Trigger | `Sales` | Auto-recompute `Line_total` on UPDATE |
| `trg_prevent_locked_sales_edit` | Trigger | `Sales` | Block edits on locked Sales rows |
| `trg_prevent_locked_inventory_edit` | Trigger | `Inventory_Snapshot` | Block edits on locked Inventory rows |
| `trg_prevent_locked_delivery_edit` | Trigger | `Delivery` | Block edits on locked Delivery rows |
| `trg_prevent_locked_expense_edit` | Trigger | `Expenses` | Block edits on locked Expenses rows |
| `trg_audit_inventory_unlock` | Trigger | `Inventory_Snapshot` | Auto-log a `RECORD_UNLOCKED` entry on every unlock |
| `trg_auto_lock_inventory` | Removed trigger | `Inventory_Snapshot` | Replaced by PHP logic (MySQL error 1442) |
| Seed: 3 roles | Seed data | `Role` | Owner / Staff / Auditor |
| Seed: 5 kiosks | Seed data | `Kiosk` | All 5 outlets pre-loaded |
| Seed: 5 categories | Seed data | `Category` | Burgers / Drinks / Hotdogs / Ricebowl / Snacks |

---

*End of DATABASE_FULL_DOCUMENTATION.md — Group 7, BSIT 2-B, April 2026*
