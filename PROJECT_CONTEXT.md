# PROJECT CONTEXT — Chicken Deluxe Inventory & Sales Monitoring System
> For use with Claude Code. Read this file first before doing anything in this project.

---

## 1. PROJECT OVERVIEW

**System Name:** Chicken Deluxe Inventory & Sales Monitoring System
**Client / Business Owner:** Cherryll Laud — Food Kiosk Retailer
**Group:** Group 7 — BSIT 2-B
**Developer (You):** Juan Miguel Rashley M. Andrada
**Course:** Systems Analysis & Design
**Year:** 2026

**What this system does:**
Replaces the current manual paper + Google Sheets process used across 5 food kiosks. It automates daily inventory recording, sales and expense tracking, delivery logging, automatic record locking, and centralized reporting — all with role-based access so staff can only do what they're supposed to.

**Why it was needed:**
- Staff accidentally edit or delete past records in Google Sheets
- Owner has to manually lock sheets every day
- Paper forms and spreadsheet data constantly mismatch
- No centralized view across all 5 kiosks

---

## 2. KIOSK LOCATIONS

The system covers 5 kiosk kiosks:
1. Tagbak, Jaro, Iloilo
2. The Atrium Mall, Iloilo
3. City Proper, Iloilo
4. Iloilo Supermart, Mandurriao, Iloilo
5. Aldeguer St., Iloilo

---

## 3. USERS / ROLES

| Role | Description | Key Permissions |
|------|-------------|-----------------|
| **Business Owner** | Cherryll Laud — primary user | Full access: view all kiosks, unlock/edit archived records, view all reports, manage users/products/kiosks |
| **Food Handler / Staff** | Kiosk-level users | Encode daily stocks, deliveries, sales, expenses for their assigned kiosk only. Cannot edit locked records. |
| **Auditor** | Optional reviewer | Read-only access to all records. Cannot modify anything. |
| **System Developer** | Juan (you) | Development, testing, deployment, maintenance |

---

## 4. SYSTEM MODULES (6 Processes)

### A — User Access & Role Management
- Authenticate users (username + password)
- Assign roles and kiosk access per user
- Restrict UI and data by role
- Log all login/logout events to Audit Log
- Deactivate/update accounts

### B — Inventory Management
- Record beginning stock per product at start of day
- Record ending stock per product at end of day
- Auto-generate a new daily record each business day
- Auto-lock and archive records at end of day
- Only Business Owner can unlock past records
- Flag discrepancies between beginning stock, deliveries, sales, and ending stock

### C — Delivery Management
- Staff encodes incoming deliveries (product, quantity, date, kiosk)
- Records linked to user and kiosk
- Validate before saving
- Past delivery records are locked after submission
- Delivery data feeds into Reporting (Process F)

### D — Sales & Expense Recording
- Record daily sales per product (product, qty, unit price)
- Auto-calculate line totals and overall sales total
- Record daily operational expenses (description, amount)
- Auto-lock completed sales and expense records
- Feeds into Reporting (Process F)

### E — Reference Data Management
- Manage product catalog (name, unit, active status)
- Manage kiosk/kiosk list
- Manage user roles and permissions
- Provides master reference data to all other processes
- Only admin/owner can modify reference data

### F — Reporting & Monitoring
- Daily summary per kiosk (beginning stock, ending stock, deliveries, sales, expenses)
- Weekly and monthly consolidated reports across all kiosks
- Filter by date range and kiosk
- Audit trail reports (all user actions)
- Time-in records included for staff monitoring
- Flag missing or anomalous entries

---

## 5. DATABASE SCHEMA

> Based on the draw.io ERD. Note: Delivery_Sales and Delivery are merged; Sales_Item and Sales are merged (as confirmed by the team).

### Tables

#### `Role`
| Column | Type | Notes |
|--------|------|-------|
| Role_ID | PK | |
| Name | VARCHAR | Owner / Staff / Auditor |

#### `User`
| Column | Type | Notes |
|--------|------|-------|
| User_ID | PK | |
| Role_ID | FK → Role | |
| Kiosk_ID | FK → Kiosk | |
| Username | VARCHAR | |
| Password | VARCHAR | Encrypted |
| Active_status | BOOLEAN | |
| Created_at | DATETIME | |

#### `Kiosk` (Kiosk)
| Column | Type | Notes |
|--------|------|-------|
| Kiosk_ID | PK | |
| Role_ID | FK → Role | |
| Kiosk_ID | FK | |
| Password | VARCHAR | |
| Created_at | DATETIME | |

#### `Product`
| Column | Type | Notes |
|--------|------|-------|
| Product_ID | PK | |
| Name | VARCHAR | |
| Unit | VARCHAR | e.g. pcs, kg |
| Active | BOOLEAN | |
| Created_at | DATETIME | |

#### `Inventory_Snapshot`
| Column | Type | Notes |
|--------|------|-------|
| Inventory_ID | PK | |
| Kiosk_ID | FK → Kiosk | |
| Product_ID | FK → Product | |
| User_ID | FK → User | |
| Locked_status | BOOLEAN | Auto-locked end of day |
| Snapshot_date | DATE | |
| Snapshot_type | ENUM | 'beginning' / 'ending' |
| Quantity | INT | |
| Recorded_at | DATETIME | |

#### `Delivery` (merged with Delivery_Item)
| Column | Type | Notes |
|--------|------|-------|
| Delivery_ID | PK | |
| Kiosk_ID | FK → Kiosk | |
| User_ID | FK → User | |
| Product_ID | FK → Product | |
| Delivery_Date | DATE | |
| Quantity | INT | |
| Created_at | DATETIME | |

#### `Sales` (merged with Sales_Item)
| Column | Type | Notes |
|--------|------|-------|
| Sales_ID | PK | |
| Kiosk_ID | FK → Kiosk | |
| User_ID | FK → User | |
| Product_ID | FK → Product | |
| Sales_date | DATE | |
| Quantity_sold | INT | |
| Unit_Price | DECIMAL | |
| Line_total | DECIMAL | Auto-calculated |
| Total_Amount | DECIMAL | Overall total |
| Locked_status | BOOLEAN | |
| Created_at | DATETIME | |

#### `Expenses`
| Column | Type | Notes |
|--------|------|-------|
| Expense_ID | PK | |
| Kiosk_ID | FK → Kiosk | |
| User_ID | FK → User | |
| Expense_date | DATE | |
| Amount | DECIMAL | |
| Description | VARCHAR | |
| Created_at | DATETIME | |

#### `Audit_Log`
| Column | Type | Notes |
|--------|------|-------|
| Log_ID | PK | |
| User_ID | FK → User | |
| Action | VARCHAR | e.g. LOGIN, LOCK, EDIT |
| Timestamp | DATETIME | |

#### `Time_in` *(newly added)*
| Column | Type | Notes |
|--------|------|-------|
| Timein_ID | PK | |
| User_ID | FK → User | |
| Kiosk_ID | FK → Kiosk | |
| Timestamp | DATETIME | |

---

## 6. FUNCTIONAL REQUIREMENTS SUMMARY

### Must Have (Mandatory)
- Auto-generate new daily record each business day
- Auto-lock previous day records at end of day
- Role-based access control (RBAC)
- Staff can only access their assigned kiosk
- Only owner can unlock/modify archived records
- Encrypt all passwords
- Record all user actions in Audit Log
- Daily/weekly/monthly reports
- Auto-calculate sales totals
- Validate all entries before saving

### Should Have (Desirable)
- Auto-logout inactive sessions
- Export/print reports
- Delivery reconciliation report
- Flag anomalies in reports
- Time-in records for staff attendance

---

## 7. NON-FUNCTIONAL REQUIREMENTS SUMMARY

| Category | Key Requirements |
|----------|-----------------|
| **Performance** | Fast response on tablet devices; handle concurrent entries from 5 kiosks |
| **Usability** | Clean minimal UI; max 2 clicks to reach core functions; error messages on invalid input |
| **Security** | RBAC enforced at all levels; encrypted passwords; atomic DB transactions |
| **Reliability** | Daily auto-backup; no partial record saves; auto-lock reliability |
| **Maintainability** | Well-documented code; modular architecture; version numbering |
| **Portability** | Deployable across all 5 kiosks simultaneously; migratable to new server |

---

## 8. TEAM ROLES

| Name | Role |
|------|------|
| Juan Miguel Rashley M. Andrada | **System Developer** (you) |
| Mikhael Jeff B. Gedorio | Business Analyst |
| Roland Shem J. Alera | Documentation |
| Chelson Clyde Khalil B. Laud | Deployment / IS Liaison |
| Winbealle T. Baylon | Training |

---

## 9. PROJECT TIMELINE (WBS)

| Task | Duration | Dates |
|------|----------|-------|
| A — Project Initiation | 5 days | Mar 1–5 |
| B — Requirements Analysis | 10 days | Mar 6–15 |
| C — System Design | 10 days | Mar 16–25 |
| D — Development | 20 days | Mar 26–Apr 14 |
| E — Testing | 5 days | Apr 15–19 |
| F — Deployment | 5 days | Apr 20–24 |
| G — Training | 3 days | Apr 25–27 |
| H — Monitoring & Buffer | 2 days | Apr 28–29 |
| **Total** | **60 days** | **Mar 1 – Apr 29** |

---

## 10. TECH STACK

- **Language:** Java (preferred based on developer background)
- **UI:** Java Swing or Web (TBD)
- **Database:** MySQL / MariaDB via XAMPP (master-slave replication setup)
- **IDE:** IntelliJ IDEA or VS Code
- **Version Control:** Git

---

## 11. DATABASE SETUP — XAMPP MASTER-SLAVE REPLICATION

> ⚠️ CRITICAL: Claude Code must check the actual XAMPP files/config before making any DB changes. Always look at the existing setup first to understand what progress has already been made.

### Overview
The project uses **MySQL master-slave replication** configured inside XAMPP on a single machine (simulated for school demo purposes).

### How It Works

| Node | Role | Handles |
|------|------|---------|
| **Master** | Primary DB | ALL write operations — INSERT, UPDATE, DELETE, DDL changes |
| **Slave** | Replica DB | ALL read/SELECT operations — data retrieval only |

### Rules for Claude Code
- **NEVER send write queries (INSERT/UPDATE/DELETE) to the Slave** — always route to Master
- **NEVER send SELECT/read queries to the Master** when they can go to the Slave
- **The Slave automatically replicates** everything from the Master — do not manually sync
- If the Slave is behind (replication lag), reads should fall back to Master temporarily
- All schema changes (CREATE TABLE, ALTER TABLE, etc.) go to **Master only**

### Connection Config Pattern
```java
// Master connection — for all writes
Connection masterConn = DriverManager.getConnection(
    "jdbc:mysql://localhost:3306/chicken_deluxe",  // Master port
    "master_user", "password"
);

// Slave connection — for all reads
Connection slaveConn = DriverManager.getConnection(
    "jdbc:mysql://localhost:3307/chicken_deluxe",  // Slave port (different port on same machine)
    "slave_user", "password"
);
```
> ⚠️ Check the actual ports in the XAMPP config files — they may differ from the example above. Always read the existing config before assuming port numbers.

### Where to Find XAMPP Config Files
Before writing any DB connection code, Claude Code should check:
- `C:/xampp/mysql/bin/my.ini` — main MySQL config (master settings, server-id, log-bin)
- `C:/xampp/mysql/bin/my-slave.ini` or equivalent — slave config (server-id, relay-log)
- `C:/xampp/mysql/data/` — data directory
- Any existing `.sql` setup scripts in the project folder

### What to Look For in Config
```ini
# Master (my.ini)
[mysqld]
server-id = 1
log_bin = mysql-bin
binlog_do_db = chicken_deluxe

# Slave (separate config)
[mysqld]
server-id = 2
relay-log = relay-log
read_only = 1
```

### Query Routing Logic
```java
// Route based on operation type
public Connection getConnection(String operationType) {
    if (operationType.equals("READ")) {
        return slaveConn;   // SELECT queries → Slave
    } else {
        return masterConn;  // INSERT/UPDATE/DELETE → Master
    }
}
```

---

## 12. SQL TRIGGERS

> This project uses MySQL SQL triggers as part of the coursework requirement. Triggers are implemented on the Master database and replicate to the Slave automatically.

### What Triggers Are Used For
Triggers automate backend logic that must happen every time a specific database event occurs — without relying on application code to remember to do it.

### Planned / Implemented Triggers

#### 1. Auto-lock records at end of day
```sql
-- Example: After inventory snapshot is inserted for 'ending' type,
-- lock all records for that date and kiosk
DELIMITER $$
CREATE TRIGGER trg_auto_lock_inventory
AFTER INSERT ON Inventory_Snapshot
FOR EACH ROW
BEGIN
  IF NEW.Snapshot_type = 'ending' THEN
    UPDATE Inventory_Snapshot
    SET Locked_status = 1
    WHERE Snapshot_date = NEW.Snapshot_date
      AND Kiosk_ID = NEW.Kiosk_ID;
  END IF;
END$$
DELIMITER ;
```

#### 2. Auto-calculate Sales line total
```sql
-- Before inserting a sales record, auto-calculate Line_total
DELIMITER $$
CREATE TRIGGER trg_calc_line_total
BEFORE INSERT ON Sales
FOR EACH ROW
BEGIN
  SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END$$
DELIMITER ;
```

#### 3. Auto-write to Audit Log on sensitive actions
```sql
-- After any UPDATE on Inventory_Snapshot (e.g. unlock by owner),
-- write to Audit_Log
DELIMITER $$
CREATE TRIGGER trg_audit_inventory_update
AFTER UPDATE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
  INSERT INTO Audit_Log (User_ID, Action, Timestamp)
  VALUES (NEW.User_ID, 'RECORD_UPDATED', NOW());
END$$
DELIMITER ;
```

#### 4. Prevent edits on locked records
```sql
-- Before any UPDATE on a locked Sales record, raise an error
DELIMITER $$
CREATE TRIGGER trg_prevent_locked_sales_edit
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
  IF OLD.Locked_status = 1 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Cannot modify a locked sales record.';
  END IF;
END$$
DELIMITER ;
```

### Important Rules for Triggers
- All triggers are created on the **Master** — they replicate to Slave automatically
- Never create duplicate trigger logic in both application code AND triggers — pick one
- Always check existing trigger definitions before adding new ones to avoid conflicts
- Use `SHOW TRIGGERS FROM chicken_deluxe;` to see what's already been created
- Trigger errors will rollback the entire transaction — handle them gracefully in app code

### How to Check Existing Triggers
```sql
-- Run this on Master to see all existing triggers
SHOW TRIGGERS FROM chicken_deluxe;

-- Or check the information schema
SELECT * FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = 'chicken_deluxe';
```

---

## 13. KEY BUSINESS RULES TO ENFORCE IN CODE

1. **Records auto-lock at end of each business day** — no staff edits after lock
2. **Only Business Owner can unlock a locked record** — and this must be logged in Audit_Log
3. **Staff can only see/enter data for their assigned Kiosk** — enforce via Kiosk_ID on User
4. **Auditor is read-only** — no INSERT, UPDATE, or DELETE allowed
5. **Every user action must create an Audit_Log entry** — especially login, logout, create, edit, delete, lock, unlock
6. **Sales line totals = Quantity_sold × Unit_Price** — always auto-calculated, never manually entered
7. **All passwords must be stored encrypted** — never plain text
8. **A new daily record must be auto-generated each business day** — not created manually by staff
9. **Delivery and Sales records are merged tables** — no separate Sales_Item or Delivery_Item tables

---

*Last updated: April 2026 — Generated from full project documentation, Group 7, BSIT 2-B*

---

> 💡 **Reminder for Claude Code:** Before writing any code or SQL, always:
> 1. Read this file fully
> 2. Check XAMPP config files to understand the current master-slave setup
> 3. Run `SHOW TRIGGERS FROM chicken_deluxe;` to see existing triggers
> 4. Look at existing project files to understand what's already been built
> 5. Route ALL writes → Master, ALL reads → Slave
