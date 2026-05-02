# DATABASE — FULL DOCUMENTATION
## Chicken Deluxe Inventory & Sales Monitoring System

> **Project:** Chicken Deluxe Inventory & Sales Monitoring System
> **Group:** Group 7 — BSIT 2-B
> **Database:** `chicken_deluxe` (MySQL / MariaDB, master-slave replication)
> **Source files:** [`sql/schema.sql`](sql/schema.sql), [`sql/triggers.sql`](sql/triggers.sql)
> **Last updated:** May 2026

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

The database has **13 tables**, listed in the order they appear in `schema.sql`.

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
- **Used By:** Process **E** (Reference Data) — and indirectly **every** other process via `Kiosk_ID`.
- **ERD Status:** ⚠️ **MODIFIED** — the ERD listed wrong columns (`Role_ID`, `Kiosk_ID`, `Password` — a copy-paste error from the User table). Replaced with the correct kiosk columns.
- **Why it was changed:** A kiosk is a physical place. It needs a name and an address — not a role or a password. The Owner can also temporarily disable a kiosk (`Active = 0`) without deleting it.
- **Seed data:** Tagbak, Atrium, City Proper, Supermart, Aldeguer.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Kiosk_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each kiosk | Used as the foreign key (`Kiosk_ID`) on every operational table |
| `Name` | VARCHAR(100) | NOT NULL | Short kiosk name shown in headers and dropdowns (e.g. "Tagbak Branch") | Lets the UI show a human-readable label instead of an ID number |
| `Location` | VARCHAR(255) | NOT NULL | Full address of the kiosk | Used in reports and on the Manage Kiosks admin page |
| `Active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Tells the system whether this kiosk is currently operating. 1 = open, 0 = temporarily disabled | Lets the Owner pause a kiosk without deleting historical records (deleting would orphan past sales) |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time this kiosk record was first added | — |

---

## TABLE 3 — `User`

- **Purpose:** Stores all the people who can log in — the Owner, kiosk staff, and any auditors. Their assigned role and (for staff) their assigned kiosk live here.
- **Used By:** Process **A** primarily; every other process needs `User_ID` for record-tracking and audit trails.
- **ERD Status:** ⚠️ **MODIFIED** — added `Full_name` column for display purposes; clarified `Kiosk_ID` as nullable.
- **Why it was changed:** The ERD listed Password but not `Full_name`. We need a real name to show in headers ("Cherryll Laud") instead of a username ("owner1"), and to make audit log entries readable. `Kiosk_ID` is nullable because Owner and Auditor are not tied to a single kiosk.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `User_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each user | Foreign key on every operational table for record-tracking |
| `Role_ID` | INT | NOT NULL, FK → `Role(Role_ID)` | Tells the system whether this user is Owner, Staff, or Auditor | Drives every role check — what menu appears, what they can edit, etc. |
| `Kiosk_ID` | INT | **NULL allowed**, FK → `Kiosk(Kiosk_ID)` | The kiosk this user belongs to | NULL for Owner/Auditor so they can see all kiosks. For Staff this enforces "you can only see your own kiosk" |
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

## TABLE 5A — `Part` *(added April 2026 — parts-based inventory)*

- **Purpose:** Stores raw ingredient parts (Burger Bun, Patty, Cheese Slice, etc.) used to make finished Products. Inventory, Delivery, and the recipe system all track Parts instead of Products.
- **Used By:** Process **B** (Inventory), **C** (Delivery), **E** (Reference Data).
- **ERD Status:** 🆕 **NEW TABLE** — added April 2026 when the system migrated from product-based to parts-based inventory tracking.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Part_ID` | INT | PK, AUTO_INCREMENT | Unique number identifying each part | FK on Inventory_Snapshot, Delivery, Product_Part |
| `Name` | VARCHAR(100) | NOT NULL, UNIQUE | Part name (e.g. "Burger Bun") | UNIQUE prevents duplicate part definitions |
| `Unit` | VARCHAR(30) | NOT NULL | Unit of measurement (pcs / cup / pack / bottle / kg / g / ml) | Makes inventory counts meaningful ("50 pcs" vs "50 kg") |
| `Active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Soft-delete flag. 1 = in use, 0 = retired | Lets the Owner retire a part without losing historical records |
| `Created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time this part was first added | — |

---

## TABLE 5B — `Product_Part` *(added April 2026 — recipe junction)*

- **Purpose:** Defines how many of each Part are needed to make one unit of a Product. This is the "recipe" junction table that allows sales to auto-deduct parts from inventory.
- **Used By:** Process **B** (Inventory — running stock calculation), **D** (Sales — parts deduction).
- **ERD Status:** 🆕 **NEW TABLE** — added April 2026.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Product_Part_ID` | INT | PK, AUTO_INCREMENT | Unique number for each recipe row | — |
| `Product_ID` | INT | NOT NULL, FK → `Product(Product_ID)` ON DELETE CASCADE | The product this recipe belongs to | Cascade delete means removing a product cleans up its recipe |
| `Part_ID` | INT | NOT NULL, FK → `Part(Part_ID)` ON DELETE RESTRICT | The part used in the recipe | RESTRICT prevents deleting a part that is still used in a recipe |
| `Quantity_needed` | INT | NOT NULL | How many units of this part are needed to make 1 unit of the product | Used by `getRunningPartsInventory()` to compute parts consumed by sales |

**Special key:** `UNIQUE (Product_ID, Part_ID)` — one row per part per product; prevents duplicate recipe entries.

---

## TABLE 6 — `Inventory_Snapshot`

- **Purpose:** Stores the daily stock count for every **part** at every kiosk (new rows) and — for historical data only — every product (legacy rows). Each business day produces two rounds per part: a 'beginning' snapshot (morning) and an 'ending' snapshot (close of day).
- **Used By:** Process **B** (Inventory) and Process **F** (Daily Summary report).
- **ERD Status:** ⚠️ **MODIFIED** — same columns as the ERD, but with a new UNIQUE KEY constraint added.
- **Why it was changed:** Without `uq_snapshot`, a staff member could accidentally record "beginning stock" twice for the same product on the same day, which would silently corrupt the daily report. The unique key makes the database itself reject the duplicate.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Inventory_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each snapshot row | — |
| `Kiosk_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk this stock count is for | Lets staff see only their kiosk; lets reports filter by kiosk |
| `Product_ID` | INT | **NULL allowed**, FK → `Product(Product_ID)` | Legacy column — kept for historical product-based rows only. NULL for all new parts-based rows | **NULLable since April 2026.** New rows use Part_ID instead |
| `Part_ID` | INT | **NULL allowed**, FK → `Part(Part_ID)` | The part being counted — **primary column for all new rows** | NULL for historical product-based rows only. One of Product_ID or Part_ID must be non-NULL |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The user who recorded this count | Audit trail — we always know who entered the number |
| `Locked_status` | TINYINT(1) | NOT NULL, DEFAULT 0 | Whether this snapshot is locked. 1 = locked, 0 = still editable | **Core lock-out mechanism.** Once 1, only the Owner can flip it back to 0, and the trigger logs that unlock to the audit log |
| `Snapshot_date` | DATE | NOT NULL | The business day this snapshot is for | Used by the daily report query |
| `Snapshot_type` | ENUM(`beginning`, `ending`) | NOT NULL | Whether this row is the morning's beginning stock or end-of-day closing stock | **ENUM was chosen** so typos like "begining" are rejected by the DB itself |
| `Quantity` | INT | NOT NULL, DEFAULT 0 | The actual quantity on hand | The reason the row exists |
| `Recorded_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact timestamp when this row was saved | — |

**Special keys:** `UNIQUE KEY uq_snapshot (Kiosk_ID, Product_ID, Snapshot_date, Snapshot_type)` for legacy rows; `UNIQUE KEY uq_snapshot_part (Kiosk_ID, Part_ID, Snapshot_date, Snapshot_type)` for parts-based rows.

---

## TABLE 7 — `Delivery`

- **Purpose:** Records every stock delivery or pullout at a kiosk. New rows are parts-based (Part_ID); legacy rows used Product_ID. Feeds the daily inventory reconciliation.
- **Used By:** Process **C** (Delivery) and Process **F** (Reports).
- **ERD Status:** ⚠️ **MODIFIED** — added `Locked_status` column.
- **Why it was changed:** The ERD didn't include a lock flag for deliveries, but the same business rule that protects past sales should protect past deliveries — once locked, they cannot be silently rewritten.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Delivery_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each delivery row | — |
| `Kiosk_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk that received the delivery | — |
| `User_ID` | INT | NOT NULL, FK → `User(User_ID)` | The user who recorded the delivery | Audit trail |
| `Product_ID` | INT | **NULL allowed**, FK → `Product(Product_ID)` | Legacy column — kept for historical product-based rows only. NULL for all new parts-based rows | **NULLable since April 2026.** |
| `Part_ID` | INT | **NULL allowed**, FK → `Part(Part_ID)` | The part delivered or pulled out — **primary column for new rows** | NULL for legacy rows only |
| `Delivery_Date` | DATE | NOT NULL | The day the delivery arrived | Used by the daily report query |
| `Quantity` | INT | NOT NULL, DEFAULT 0 | How many units were delivered | The reason the row exists |
| `Type` | VARCHAR(20) | NOT NULL, DEFAULT 'Delivery' | Whether this row is incoming stock ('Delivery') or stock removed ('Pullout') | 'Pullout' rows subtract from running inventory (expired, returned to supplier, etc.) |
| `Notes` | VARCHAR(255) | NULL allowed | Reason or description for a pullout | Free text — e.g. "Expired", "Returned to supplier" |
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
| `Kiosk_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk where the sale happened | — |
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
| `Kiosk_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk that incurred the expense | — |
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
- **ERD Status:** ⚠️ **MODIFIED** — added `Details` column plus four change-logging columns (`Operation`, `Table_name`, `Old_values`, `New_values`) in the 2026-04-20 migration.
- **Why it was changed:** The ERD only had `Action` (a code like `UPDATE`). Without context, an audit log of "UPDATE", "UPDATE", "UPDATE" is useless — you can't tell what was updated or what the old vs new data looked like. The extra columns let the SQL triggers in §Part 2 record the affected table, the operation type, and a full JSON snapshot of OLD and NEW row values for every INSERT / UPDATE / DELETE.

| Column Name | Data Type | Constraints | Description | Why It Matters |
|-------------|-----------|-------------|-------------|----------------|
| `Log_ID` | INT | PK, AUTO_INCREMENT | Unique number that identifies each log entry | — |
| `User_ID` | INT | **NULL allowed**, FK → `User(User_ID)` | The user who performed the action | NULL for failed logins, and for changes to tables that have no `User_ID` column (Product / User / Kiosk) |
| `Action` | VARCHAR(100) | NOT NULL | Short code for the action (`LOGIN`, `INSERT`, `UPDATE`, `DELETE`, `RECORD_UNLOCKED`, etc.) | Lets reports filter by action type quickly |
| `Operation` | VARCHAR(10) | NULL allowed | `INSERT` / `UPDATE` / `DELETE` — populated by change-logging triggers only | **NEW.** Rubric §1.3 requires operation-type logging |
| `Table_name` | VARCHAR(100) | NULL allowed | Name of the table the change hit (e.g. `Sales`, `Product`) | **NEW.** Lets the audit trail be filtered by table without parsing `Details` |
| `Old_values` | TEXT | NULL allowed | JSON snapshot of the OLD row (UPDATE / DELETE only) | **NEW.** Satisfies the "record data changes" rubric item — full before-state |
| `New_values` | TEXT | NULL allowed | JSON snapshot of the NEW row (INSERT / UPDATE only) | **NEW.** Full after-state; `User.Password` is deliberately omitted |
| `Details` | VARCHAR(255) | NULL allowed | Human-readable description (e.g. "Updated product ID:14") | Turns the audit log from a list of codes into a readable history |
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
| `Kiosk_ID` | INT | NOT NULL, FK → `Kiosk(Kiosk_ID)` | The kiosk where they clocked in | Lets the Owner see which kiosk each staff member worked at |
| `Timestamp` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Exact moment they clocked in | The reason the row exists |

---
---

# PART 2 — SQL TRIGGERS

> All triggers live in [`sql/triggers.sql`](sql/triggers.sql). They are created on the **Master** node only and replicate to the **Slave** automatically. Triggers were a coursework requirement and are used here for things that **must** always happen, no matter which page or developer wrote the surrounding code.

We have **28 active triggers** (7 business-rule triggers documented below as Triggers 1–7, plus 21 change-logging triggers documented as Trigger Group 9) and **1 trigger that was removed** (because of a MySQL limitation, see Trigger 8).

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
         WHERE Kiosk_ID = ? AND Snapshot_date = ? AND Locked_status = 0",
        [$kiosk_id, $snapshot_date]
    );
}
```

`triggers.sql` keeps a `DROP TRIGGER IF EXISTS trg_auto_lock_inventory;` line so a stale trigger on an old database is safely cleaned up.

---

### Trigger Group 9 — Change-Logging Triggers (`trg_log_<table>_<event>`)

These 21 triggers were added on 2026-04-20 to satisfy rubric §1.3 ("record change logging with old/new values"). They are mechanical — same shape for every table — so they are documented once as a group instead of repeating 21 near-identical listings. The full source is in [`sql/triggers.sql`](sql/triggers.sql).

**Coverage matrix — 7 tables × 3 events = 21 triggers:**

| Table | INSERT | UPDATE | DELETE |
|---|---|---|---|
| `Sales` | `trg_log_sales_insert` | `trg_log_sales_update` | `trg_log_sales_delete` |
| `Inventory_Snapshot` | `trg_log_inventory_snapshot_insert` | `trg_log_inventory_snapshot_update` | `trg_log_inventory_snapshot_delete` |
| `Delivery` | `trg_log_delivery_insert` | `trg_log_delivery_update` | `trg_log_delivery_delete` |
| `Expenses` | `trg_log_expenses_insert` | `trg_log_expenses_update` | `trg_log_expenses_delete` |
| `Product` | `trg_log_product_insert` | `trg_log_product_update` | `trg_log_product_delete` |
| `User` | `trg_log_user_insert` | `trg_log_user_update` | `trg_log_user_delete` |
| `Kiosk` | `trg_log_kiosk_insert` | `trg_log_kiosk_update` | `trg_log_kiosk_delete` |

**What every trigger does (uniform pattern):**

1. Fires `AFTER` the DML event — so the row has its auto-increment PK and any default-valued columns already filled in.
2. Builds `Old_values` (for UPDATE / DELETE) and `New_values` (for INSERT / UPDATE) as `JSON_OBJECT(...)` snapshots of every non-sensitive column.
3. Inserts one row into `Audit_Log` populating `User_ID`, `Action`, `Operation`, `Table_name`, `Old_values`, `New_values`, `Details`, `Timestamp`.
4. For tables that carry a `User_ID` column (Sales, Inventory_Snapshot, Delivery, Expenses), the trigger uses `NEW.User_ID` (INSERT/UPDATE) or `OLD.User_ID` (DELETE). For Product / User / Kiosk the row has no `User_ID` column, so the audit `User_ID` is stored as `NULL` and the identity is captured in `Details` + `New_values`/`Old_values` instead.

**Security exclusion:** `User.Password` is deliberately **not** included in the `JSON_OBJECT` snapshots for the User triggers. Hashes should never end up in the audit trail.

**Canonical example — `trg_log_product_update`:**

```sql
CREATE TRIGGER trg_log_product_update
AFTER UPDATE ON Product
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log
        (User_ID, Action, Operation, Table_name,
         Old_values, New_values, Details, Timestamp)
    VALUES (NULL, 'UPDATE', 'UPDATE', 'Product',
        JSON_OBJECT('Product_ID', OLD.Product_ID, 'Category_ID', OLD.Category_ID,
                    'Name', OLD.Name, 'Unit', OLD.Unit,
                    'Price', OLD.Price, 'Active', OLD.Active),
        JSON_OBJECT('Product_ID', NEW.Product_ID, 'Category_ID', NEW.Category_ID,
                    'Name', NEW.Name, 'Unit', NEW.Unit,
                    'Price', NEW.Price, 'Active', NEW.Active),
        CONCAT('Record updated in Product ID:', NEW.Product_ID),
        NOW());
END$$
```

**Live verification (2026-04-20):** inserting a test Product, updating its price, then deleting it produced `Audit_Log` rows 99, 100, 101 with the expected `Operation='INSERT'/'UPDATE'/'DELETE'`, `Table_name='Product'`, and fully populated JSON snapshots.

**Note on `Audit_Log` duplication with `trg_audit_inventory_unlock`:** an Inventory_Snapshot unlock now produces **two** rows — one from the existing `trg_audit_inventory_unlock` (`Action='RECORD_UNLOCKED'`) and one from the new `trg_log_inventory_snapshot_update` (`Action='UPDATE'`). This is intentional — the two rows carry different semantic meaning (unlock event vs generic change) and the Reports module can filter either independently.

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
| `Inventory_Snapshot` | `uq_snapshot` (UNIQUE) | (Kiosk_ID, Product_ID, Snapshot_date, Snapshot_type) | Prevents duplicate beginning/ending snapshots for the same kiosk + product + date |
| `Inventory_Snapshot` | `uq_snapshot_part` (UNIQUE) | (Kiosk_ID, Part_ID, Snapshot_date, Snapshot_type) | Prevents duplicate parts-based beginning/ending snapshots |
| `Product_Part` | (UNIQUE) | (Product_ID, Part_ID) | One row per part per product in the recipe |
| `User` | (UNIQUE on Username) | Username | Two users can't share a login name |
| `Role` | (UNIQUE on Name) | Name | Two roles can't share a name |
| `Category` | (UNIQUE on Name) | Name | Two categories can't share a name |

Primary keys also act as automatic indexes.

## 3.3 Foreign Key Constraints

The ERD only showed relationships as lines. The actual database enforces them with `FOREIGN KEY` constraints:

| Child Table | Foreign Key | References |
|-------------|------------|------------|
| `User` | `Role_ID` | `Role(Role_ID)` |
| `User` | `Kiosk_ID` | `Kiosk(Kiosk_ID)` |
| `Product` | `Category_ID` | `Category(Category_ID)` |
| `Inventory_Snapshot` | `Kiosk_ID` | `Kiosk(Kiosk_ID)` |
| `Inventory_Snapshot` | `Product_ID` | `Product(Product_ID)` |
| `Inventory_Snapshot` | `User_ID` | `User(User_ID)` |
| `Delivery` | `Kiosk_ID` | `Kiosk(Kiosk_ID)` |
| `Delivery` | `Product_ID` | `Product(Product_ID)` |
| `Delivery` | `User_ID` | `User(User_ID)` |
| `Sales` | `Kiosk_ID` | `Kiosk(Kiosk_ID)` |
| `Sales` | `Product_ID` | `Product(Product_ID)` |
| `Sales` | `User_ID` | `User(User_ID)` |
| `Expenses` | `Kiosk_ID` | `Kiosk(Kiosk_ID)` |
| `Expenses` | `User_ID` | `User(User_ID)` |
| `Audit_Log` | `User_ID` | `User(User_ID)` |
| `Time_in` | `User_ID` | `User(User_ID)` |
| `Time_in` | `Kiosk_ID` | `Kiosk(Kiosk_ID)` |
| `Product_Part` | `Product_ID` | `Product(Product_ID)` |
| `Product_Part` | `Part_ID` | `Part(Part_ID)` |
| `Inventory_Snapshot` | `Part_ID` | `Part(Part_ID)` |
| `Delivery` | `Part_ID` | `Part(Part_ID)` |

## 3.4 Stored Procedures and Views

**None.** The team deliberately kept the database simple — all cross-table logic that isn't a trigger lives in PHP models, where the team can read and modify it more easily.

## 3.5 Seed Data

The schema seeds the data we know is fixed at install time:

- **3 roles:** Owner, Staff, Auditor
- **5 kiosks:** Tagbak, Atrium, City Proper, Supermart, Aldeguer
- **5 categories:** Burgers, Drinks, Hotdogs, Ricebowl, Snacks

All seeds use `ON DUPLICATE KEY UPDATE` so re-running `schema.sql` is safe — it won't fail or overwrite valid edits.

## 3.6 Demo / Reset Scripts

Two convenience scripts live alongside `schema.sql` for demo prep — neither changes the schema, both are idempotent:

| File | What it does | Touches |
|---|---|---|
| `sql/demo_reset.sql` | TRUNCATEs every transactional table (Sales, Delivery, Expenses, Inventory_Snapshot, Time_in, Audit_Log), resets their AUTO_INCREMENT counters, sets realistic Philippine kiosk prices on every product (₱45–₱85 burgers, ₱35–₱45 hotdogs, ₱65–₱85 ricebowls, ₱18–₱35 drinks, ₱20–₱35 snacks), and ensures every product is `Active = 1`. Final step: a second `TRUNCATE Audit_Log` to drop the noise rows that the price-update triggers just generated. Result: clean transactional state, intact reference data. | Sales / Delivery / Expenses / Inventory_Snapshot / Time_in / Audit_Log / Product (price + active) |
| `sql/demo_seed.sql` | Inserts 5 staff users (one per kiosk: `staff_k1` … `staff_k5`, all with bcrypt-hashed password `staff1234`), then populates **yesterday** with a fully-locked operational day (10 popular products × 5 kiosks × beginning + ending = 100 inventory rows, ~12 deliveries, ~54 sales, ~12 expenses, 5 time-in punches) and **today** with an open mid-day partial state (50 carry-forward beginning inventory rows = yesterday's ending values, 5 morning deliveries, ~27 sales, 2 expenses, 5 time-in punches). Wraps up by truncating the trigger-generated Audit_Log rows and inserting ~13 manual-looking entries (logins, lock-all events). | User / Sales / Delivery / Expenses / Inventory_Snapshot / Time_in / Audit_Log |

**Recommended workflow** to start a clean demo:

```bash
mysql -u root chicken_deluxe < sql/demo_reset.sql
mysql -u root chicken_deluxe < sql/demo_seed.sql
```

**Why both scripts truncate Audit_Log at the end:** every INSERT into the seven main tables fires a `trg_log_<table>_insert` trigger, which writes a row into `Audit_Log`. After a fresh seed, that's ~270 rows of trigger noise. Both scripts wipe Audit_Log as the final step so the audit log starts clean (or, in `demo_seed.sql`, contains only the 13 hand-crafted login/lock entries).

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
- **Two subquery-based reports** in `ReportModel.php`:
  - `getProductsAboveAverageSales($from, $to)` — products whose total quantity sold in the range exceeds the average per-product total (uses nested derived tables in a `HAVING` clause)
  - `getKiosksWithoutEndingSnapshot($date)` — active kiosks that haven't yet recorded an ending snapshot on the given date (uses a `NOT IN` subquery)

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
| `Part` | New table | (its own) | Raw ingredient parts used to build Products; the unit of measurement for all new inventory and delivery rows |
| `Product_Part` | New table | (its own) | Recipe junction — how many of each Part are needed to make 1 unit of a Product; drives parts-consumed calculation in `getRunningPartsInventory()` |
| `Kiosk.Name` / `Location` / `Active` | Modified columns | `Kiosk` | Replaced wrong ERD columns with proper kiosk fields |
| `Category_ID` | New column | `Product` | Link each product to its category |
| `Price` | New column | `Product` | Official selling price; used by server on every sale (anti-tampering) |
| `Full_name` | New column | `User` | Display name for UI and audit log |
| `Locked_status` | New column | `Delivery` | Allow Owner to lock past delivery rows |
| `Part_ID` | New column | `Delivery` | Parts-based delivery tracking (April 2026); `Product_ID` is now NULLable for legacy rows |
| `Type` | New column | `Delivery` | Distinguishes 'Delivery' (incoming) from 'Pullout' (removed) rows |
| `Notes` | New column | `Delivery` | Free-text reason for a pullout |
| `Part_ID` | New column | `Inventory_Snapshot` | Parts-based snapshot tracking (April 2026); `Product_ID` is now NULLable for legacy rows |
| `uq_snapshot_part` | Unique key | `Inventory_Snapshot` | Prevent duplicate parts-based beginning/ending snapshots |
| `Locked_status` | New column | `Expenses` | Allow Owner to lock past expense rows |
| `Details` | New column | `Audit_Log` | Human-readable description of audited action |
| `Operation` | New column | `Audit_Log` | INSERT / UPDATE / DELETE — populated by change-logging triggers |
| `Table_name` | New column | `Audit_Log` | Source table name — populated by change-logging triggers |
| `Old_values` | New column | `Audit_Log` | JSON snapshot of OLD row (UPDATE/DELETE) |
| `New_values` | New column | `Audit_Log` | JSON snapshot of NEW row (INSERT/UPDATE) |
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
| `trg_log_*_insert` (×7) | Trigger group | Sales/Inventory_Snapshot/Delivery/Expenses/Product/User/Kiosk | Change-logging: capture NEW row as JSON in Audit_Log |
| `trg_log_*_update` (×7) | Trigger group | same 7 tables | Change-logging: capture OLD and NEW rows as JSON in Audit_Log |
| `trg_log_*_delete` (×7) | Trigger group | same 7 tables | Change-logging: capture OLD row as JSON in Audit_Log |
| Seed: 3 roles | Seed data | `Role` | Owner / Staff / Auditor |
| Seed: 5 kiosks | Seed data | `Kiosk` | All 5 kiosks pre-loaded |
| Seed: 5 categories | Seed data | `Category` | Burgers / Drinks / Hotdogs / Ricebowl / Snacks |

---

*End of DATABASE_FULL_DOCUMENTATION.md — Group 7, BSIT 2-B, May 2026*
