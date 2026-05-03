# CLAUDE.md — Developer Instructions for Claude Code
> This file is automatically read by Claude Code at the start of every session.
> Follow ALL instructions here before writing any code.

---

## 🔴 FIRST THINGS FIRST — DO THIS BEFORE ANYTHING ELSE

1. **Read `PROJECT_CONTEXT.md`** — full system context, database schema, business rules, master-slave setup
2. **Check existing files** — always scan the current project structure before creating anything new
3. **Check XAMPP config** — before any DB work, read `C:/xampp/mysql/bin/my.ini` and slave config to confirm ports and setup
4. **Run `SHOW TRIGGERS FROM chicken_deluxe;`** — before writing any SQL, check what triggers already exist
5. **Ask if unclear** — if something in the existing code is ambiguous, ask Juan before assuming

---

## 👤 ABOUT THE DEVELOPER

- **Name:** Juan Miguel Rashley M. Andrada — BSIT 2-B
- **IDE:** VS Code
- **Experience:** Java Swing POS system, Java Banking System, SQL triggers, MySQL master-slave replication
- **Preferred style:** Clean, readable, well-commented, easy to modify later
- **Goal:** Build a functional, professional-grade web system for a real client

---

## 🏗️ PROJECT STRUCTURE — ALWAYS FOLLOW THIS

```
chicken-deluxe/
│
├── CLAUDE.md                        ← You are here
├── PROJECT_CONTEXT.md               ← Read this every session
├── index.php                        ← Entry point / front controller
│
├── config/
│   ├── database.php                 ← Master & Slave DB connections
│   └── constants.php                ← App-wide constants (roles, statuses, etc.)
│
├── core/
│   ├── Database.php                 ← DB class — routes reads to Slave, writes to Master
│   ├── Model.php                    ← Base model (all models extend this)
│   ├── Controller.php               ← Base controller (all controllers extend this)
│   ├── Auth.php                     ← Session management & role-based access checks
│   └── Router.php                   ← URL routing
│
├── models/
│   ├── UserModel.php
│   ├── RoleModel.php
│   ├── KioskModel.php
│   ├── ProductModel.php
│   ├── InventoryModel.php
│   ├── DeliveryModel.php
│   ├── SalesModel.php
│   ├── ExpenseModel.php
│   ├── AuditLogModel.php
│   └── TimeInModel.php
│
├── controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── InventoryController.php
│   ├── DeliveryController.php
│   ├── SalesController.php
│   ├── ExpenseController.php
│   ├── ReportController.php
│   └── AdminController.php
│
├── views/
│   ├── layouts/
│   │   ├── main.php                 ← Main HTML wrapper (head, body, footer)
│   │   ├── navbar.php               ← Top navigation bar
│   │   └── sidebar.php              ← Sidebar menu (role-aware)
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   └── index.php
│   ├── inventory/
│   ├── delivery/
│   ├── sales/
│   ├── expenses/
│   ├── reports/
│   └── admin/
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js
│   └── img/
│
└── sql/
    ├── schema.sql                   ← Full DB creation script (run on Master)
    ├── triggers.sql                 ← All SQL triggers (run on Master)
    ├── demo_reset.sql               ← Wipe transactional tables + reset prices
    ├── demo_seed.sql                ← Seed 5 staff users + yesterday/today demo data
    ├── migrations/                  ← Dated change-scripts (e.g. outlet→kiosk rename)
    └── backups/                     ← Pre-risky-operation snapshots
```

**Note on the `views/admin/` folder:** also contains `edit-user.php` (added in the April 2026 UI/UX pass) and `audit-log.php` (filterable + paginated viewer with diff panels).

### Rules about structure
- **Never dump files in the root** — every file has a folder it belongs to
- **New module?** → Create a Model + Controller + views subfolder for it
- **New helper/utility?** → Put it in `core/` if reusable, or in the relevant controller/model if specific
- **New SQL?** → Add to `sql/schema.sql` or `sql/triggers.sql`, never inline ad-hoc
- **Never mix PHP logic inside view files** — views only display, controllers handle logic

---

## 🎨 CODE STYLE RULES — ALWAYS FOLLOW THESE

### PHP
```php
<?php
// ✅ DO: Use classes for everything — OOP all the way
class InventoryModel extends Model {
    // Properties at the top
    private int $kiosk_id;

    // Constructor next
    public function __construct(int $kiosk_id) {
        parent::__construct();
        $this->kiosk_id = $kiosk_id;
    }

    // Public methods first, private/protected last
    public function getTodaySnapshot(): array {
        // Short, descriptive variable names
        $query = "SELECT * FROM Inventory_Snapshot WHERE Kiosk_ID = ?";
        return $this->db->readQuery($query, [$this->kiosk_id]);
    }
}
```

- **Always use classes** — no raw procedural PHP files (except `index.php` entry point)
- **All models extend `Model`**, all controllers extend `Controller`
- **Type hints everywhere** — `int`, `string`, `array`, `bool`, `?string` etc.
- **No inline SQL in controllers** — SQL belongs in models only
- **Use `?` prepared statements** — never concatenate user input into queries (SQL injection prevention)
- **Comment every method** with a one-line description of what it does
- **Consistent naming:**
  - Classes → `PascalCase` (e.g. `InventoryModel`)
  - Methods/functions → `camelCase` (e.g. `getByKiosk()`)
  - Variables → `snake_case` (e.g. `$kiosk_id`)
  - Constants → `UPPER_SNAKE_CASE` (e.g. `ROLE_OWNER`)
  - DB columns → match the schema exactly (e.g. `Kiosk_ID`, `Locked_status`)

### HTML/CSS
- Use semantic HTML (`<section>`, `<article>`, `<nav>`, `<main>`, etc.)
- CSS classes use `kebab-case` (e.g. `btn-primary`, `form-input`)
- Keep styles in `assets/css/style.css` — no inline styles except dynamic values
- Mobile-friendly — use responsive layouts

### JavaScript
- Use `const` and `let` — never `var`
- Keep all JS in `assets/js/app.js` or module-specific files in `assets/js/`
- Use `fetch()` for AJAX calls — no jQuery unless already in the project

---

## 🗄️ DATABASE RULES — CRITICAL

### Master-Slave Routing — NEVER BREAK THIS
```php
// ALL write operations → Master
$this->db->write("INSERT INTO Sales ...", [...]);
$this->db->write("UPDATE Inventory_Snapshot ...", [...]);
$this->db->write("DELETE FROM ...", [...]);

// ALL read operations → Slave
$result = $this->db->read("SELECT * FROM Sales WHERE ...", [...]);
```

- **Master** = INSERT, UPDATE, DELETE, CREATE, ALTER, DROP
- **Slave** = SELECT only
- The `Database.php` core class handles routing — always use its `read()` and `write()` methods
- **Never call PDO directly** in models — always go through `$this->db`

### Business Rules in DB Layer
These must be enforced at the DB/model level, not just the UI:
1. Check `Locked_status = 1` before any UPDATE/DELETE on `Inventory_Snapshot`, `Sales`, `Expenses`, `Delivery`
2. Only `Role_ID` matching Owner can set `Locked_status = 0` (unlock)
3. Every write operation must insert a row into `Audit_Log`
4. `Line_total` is always calculated as `Quantity_sold * Unit_Price` — never accept it from user input

### SQL Triggers
- All triggers live in `sql/triggers.sql`
- Before adding a new trigger, run `SHOW TRIGGERS FROM chicken_deluxe;` to avoid duplicates
- Triggers are only created on the **Master** — they replicate automatically
- **33 triggers currently live** (see `DATABASE_FULL_DOCUMENTATION.md` Part 2 for the full list):
  - **Business-rule (6):** `trg_calc_line_total_insert/update`, `trg_prevent_locked_sales/inventory/delivery/expense_edit`
  - **Change-logging (27):** `trg_log_<table>_insert/update/delete` for Sales, Inventory_Snapshot, Delivery, Expenses, Product, User, Kiosk, Part, Product_Part
  - The Inventory_Snapshot and Delivery change-logging triggers include `Part_ID` (and Delivery also includes `Type` and `Notes`) in the JSON snapshot — fixed 2026-05 so audit rows are accurate for the parts-based system.
  - Previously had `trg_audit_inventory_unlock` (dedicated unlock-event trigger). Removed 2026-05 — the generic `trg_log_inventory_snapshot_update` already captures every unlock with full Operation + Old_values + New_values.
- ⚠️ **The change-logging triggers fire on every INSERT/UPDATE/DELETE** to those 9 tables and write a row to `Audit_Log`. Bulk seed/reset scripts should TRUNCATE Audit_Log at the end to avoid hundreds of trigger-noise rows — see `sql/demo_reset.sql` / `sql/demo_seed.sql` for the pattern.
- TRUNCATE does **not** fire DML triggers in MySQL — useful for clean wipes.

---

## 🔐 SECURITY RULES — ALWAYS

- **Passwords** → Always hash with `password_hash($pass, PASSWORD_BCRYPT)`, verify with `password_verify()`
- **Sessions** → Start session in `Auth.php`, never in individual controllers or views
- **Role checks** → Every controller method must call `Auth::requireRole([ROLE_OWNER])` or equivalent before executing
- **Prepared statements** → All queries use `?` placeholders — never string concatenation
- **CSRF** → Add CSRF token validation on all POST forms
- **Input sanitization** → Always sanitize and validate input in controllers before passing to models

---

## ♻️ FLEXIBILITY RULES — MAKE CODE EASY TO CHANGE

These rules ensure Juan can easily add features, change logic, or update requirements later:

1. **Use constants, not magic numbers/strings**
   ```php
   // ✅ Good
   if ($user['Role_ID'] === ROLE_OWNER) { ... }

   // ❌ Bad
   if ($user['Role_ID'] === 1) { ... }
   ```

2. **Config values go in `config/constants.php`** — not hardcoded in logic files
   ```php
   define('ROLE_OWNER',   1);
   define('ROLE_STAFF',   2);
   define('ROLE_AUDITOR', 3);
   define('DB_MASTER_PORT', 3306);
   define('DB_SLAVE_PORT',  3307);
   ```

3. **Small, single-purpose methods** — one method does one thing
   ```php
   // ✅ Good: easy to reuse and test
   public function getByKiosk(int $kiosk_id): array { ... }
   public function getByDate(string $date): array { ... }
   public function lockRecord(int $id): bool { ... }

   // ❌ Bad: does too much, hard to change
   public function getAndLockAndLog(int $id, string $date): mixed { ... }
   ```

4. **No hardcoded SQL table/column names in controllers** — SQL only in models

5. **Views receive data as arrays** — controllers prepare data, views just render it
   ```php
   // Controller
   $data['inventory'] = $inventoryModel->getTodaySnapshot($kiosk_id);
   $this->render('inventory/index', $data);

   // View
   foreach ($data['inventory'] as $row) { ... }
   ```

6. **If a feature might grow** — make it a class, not a function

---

## ✅ CHECKLIST BEFORE WRITING ANY FILE

- [ ] Did I read `PROJECT_CONTEXT.md` this session?
- [ ] Does this file belong in the right folder?
- [ ] Am I extending the right base class (`Model` or `Controller`)?
- [ ] Are all DB reads going to Slave and writes to Master?
- [ ] Am I using prepared statements (no string concat in SQL)?
- [ ] Did I add a role/auth check at the top of every controller method?
- [ ] Are passwords hashed (never plain text)?
- [ ] Does this feature have an Audit Log entry?
- [ ] Am I using constants instead of magic values?
- [ ] Did I comment the class and its public methods?

---

## 🚫 THINGS TO NEVER DO

- ❌ Never put SQL in a view or controller — SQL belongs in models
- ❌ Never echo user input without sanitizing — XSS risk
- ❌ Never send a write query to the Slave
- ❌ Never hardcode passwords, ports, or DB credentials — use `config/database.php`
- ❌ Never create a file outside of its proper folder
- ❌ Never skip the role/auth check on a controller method
- ❌ Never store plain-text passwords
- ❌ Never allow a staff user to access another kiosk's data
- ❌ Never let anyone (except Owner) modify a locked record
- ❌ Never add a trigger without checking if it already exists first
- ❌ Never record inventory for products — always use parts (`Inventory_Snapshot.Part_ID`)
- ❌ Never record deliveries for products — always use parts (`Delivery.Part_ID`); the legacy `Product_ID` columns exist only for historical rows

---

## 💬 HOW TO COMMUNICATE WITH JUAN

- Be direct and concise — explain what you're doing and why
- If you're about to create a new file, say what folder it goes in and why
- If something in the existing code looks wrong or inconsistent, flag it before proceeding
- If a requirement is unclear, ask before assuming
- When done with a task, summarize what was created/changed and what to test next

---

*CLAUDE.md — Chicken Deluxe Inventory & Sales Monitoring System — Group 7 — 2026*
