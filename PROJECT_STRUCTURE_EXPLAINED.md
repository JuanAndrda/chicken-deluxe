# 📚 PROJECT STRUCTURE EXPLAINED

> A guided tour of the **Chicken Deluxe Inventory & Sales Monitoring System** — written for anyone, even people who have never touched a line of code.

If you can read a recipe, you can read this document. We use everyday comparisons (restaurants, offices, filing cabinets) to explain what each piece of the project does and why it exists.

---

## 🗺️ THE BIG PICTURE FIRST

Imagine the project as a small **office building**:

- The **front door** is `index.php` — every visitor enters through it
- The **receptionist** is `core/Router.php` — she figures out which department you want
- The **departments** are `controllers/` — each handles a specific kind of request (inventory, sales, etc.)
- The **kitchen / back office** is `models/` — where the actual work happens (talking to the database, doing math)
- The **office decor** is `views/` — what visitors actually see on their screen
- The **filing cabinets** are `sql/` and the database itself — where everything is stored
- The **rule book** for the building is `core/` and `config/` — shared services and settings

Every page load follows this pattern. We'll come back to a step-by-step walkthrough at the end.

---

## 🚪 1. `index.php` — The Front Door

**What it is:** A single PHP file that sits at the very top of the project. It's the *only* file the web browser is ever allowed to call directly.

**Why it exists:** Without it, every page would have to be its own separate file with its own setup code. By forcing every visitor through one door, we can do security checks, load shared settings, and route traffic from a single place — like an office that has one entrance with a guard, instead of 50 unguarded side doors.

**Real-world analogy:** A hotel lobby. No matter what room you're going to, you walk through the lobby first so the front desk knows you're here.

**How it works:** When somebody types `http://localhost/chicken-deluxe/inventory` into their browser, the web server doesn't actually look for a file called `inventory`. Thanks to a small rule file (`.htaccess`), it says "send everything to `index.php` and let it sort it out." `index.php` then loads the configuration, starts the session, and hands control to the Router.

---

## ⚙️ 2. `config/` — The Settings Folder

**What it is:** A folder of small files that store **settings** — values that almost never change but are needed everywhere.

**Why it exists:** Settings like database passwords, port numbers, and role IDs should live in *one* place. If we ever change the database password, we update one file instead of hunting through 200.

**Real-world analogy:** The thermostat panel of an office. Settings are gathered in one spot so anyone managing the building can see and adjust them quickly.

**How it works:** Other parts of the project load these files at startup and use the values they find inside.

### Files inside

- **`database.php`** — Holds the address, port, username, and password for both databases (Master on port 3306, Slave on port 3307). When any other file needs to connect to the database, it asks this file "what are the credentials?" instead of writing them down 50 times.

- **`constants.php`** — Defines fixed values that the rest of the project refers to by name. For example, instead of using the number `1` to mean "Owner" everywhere (which is confusing — what does `1` mean?), we say `ROLE_OWNER` and the system knows that means `1`. Easier to read, harder to break.

---

## 🧱 3. `core/` — The Shared Toolbox

**What it is:** The handful of *foundational* classes every other part of the project depends on. These are the engine parts under the hood.

**Why it exists:** If every model and controller had to figure out by itself how to talk to the database, how to check if someone is logged in, and how to read URLs, we'd repeat the same code endlessly. The `core/` folder gives them shared tools so they can focus on their actual job.

**Real-world analogy:** The shared utilities of an office building — the elevators, the electrical wiring, the plumbing. None of the individual offices have to install their own; they just plug in.

**How it works:** Every model and controller automatically inherits from these core classes, which means they get all the shared functions for free.

### Files inside

- **`Database.php`** — The database expert. It knows how to connect to both the Master (for saving things) and the Slave (for reading things) and automatically picks the right one. If you ask it "save this sale" it sends it to the Master. If you ask it "show me today's sales" it sends it to the Slave. Other files never have to think about which database to use.

- **`Model.php`** — The "base" model class. Every specific model (like `SalesModel`, `InventoryModel`) extends this one, which means they all start with a built-in connection to `Database.php` and a few helper methods. It's like a starter kit.

- **`Controller.php`** — The "base" controller class. Every specific controller extends it. It comes with helper methods like "render this view file with this data" so individual controllers don't reinvent the wheel.

- **`Auth.php`** — The security guard. It manages who's logged in, what role they have, what kiosk they're assigned to, and whether they're allowed to do a particular thing. Every controller checks with `Auth` at the door: "is this person allowed in here?"

- **`Router.php`** — The receptionist. It looks at the URL the visitor typed (like `/sales/create`) and figures out which controller and which method should handle it. Without it, `index.php` wouldn't know where to send anyone.

---

## 🧠 4. `models/` — The Brain of Each Topic

### What models are (in plain English)

A **model** is a class that knows everything about *one specific topic* in the system — and nothing else. There's a model for sales, one for inventory, one for users, etc. Models are the *only* files allowed to talk to the database.

**Why this matters:** If we ever change how sales are stored, we only edit `SalesModel.php`. The rest of the project doesn't care how the data is stored — it just asks the model.

**Real-world analogy:** Specialists in an office. The "sales specialist" is the only one who touches the sales filing cabinet. If a manager wants sales numbers, they ask the specialist; they don't dig through the cabinet themselves.

**How it works:** A controller asks the model a question (like "give me today's sales for Kiosk 3"). The model writes the database query, runs it, and hands back the results — usually as a clean list the rest of the system can easily use.

### Files inside

- **`UserModel.php`** — Handles everything about user accounts: looking someone up by username (for login), creating new staff accounts, **editing profile fields (`update`)**, **resetting passwords (`resetPassword`)**, deactivating/reactivating accounts, and listing all users (active-only or `getAll()` including deactivated).

- **`RoleModel.php`** — Manages the three roles: Owner, Staff, and Auditor. It mostly just provides a list of available roles when an admin is creating a new user.

- **`KioskModel.php`** — Manages the 5 kiosk locations: their names, addresses, and whether they're active or not. Needed when an admin assigns a staff member to a kiosk, or when a report needs to filter by kiosk.

- **`CategoryModel.php`** — Manages the 5 product categories (Burgers, Drinks, Hotdogs, Ricebowl, Snacks). Used so products can be grouped on screen.

- **`ProductModel.php`** — Manages the menu items the kiosks sell — names, units, prices, photos, and active status. The point-of-sale screens lean heavily on this model to display what's available.

- **`InventoryModel.php`** — The most important model in the system. It records the daily snapshots of stock — what was on hand at the start of the day (`beginning`) and what was left at the end (`ending`). It also locks records when the day ends and unlocks them when the owner says so. Now also supports **perpetual inventory**: `getPreviousDayEnding()` reads the prior day's ending stock, `autoGenerateBeginning()` creates today's beginning rows in one click using yesterday's ending values (carry-forward), `getRunningInventory()` computes live "beginning + delivered − sold" per product so staff can see what should be on hand right now, and `getKioskInventoryStatus()` powers the dashboard's per-kiosk readiness widget. Parts-based equivalents added in April 2026: `getRunningPartsInventory()` computes `beginning + delivered − pulled_out − used_by_sales` per **part** (not product) — this is now the active inventory formula and also powers the Stock column on the Delivery page.

- **`DeliveryModel.php`** — Records when fresh stock arrives at a kiosk. Each delivery is tied to a kiosk, a staff member, a product, and a date. `updateQuantity()` lets the Owner edit an unlocked delivery's quantity (rejected at the DB level if the row is already locked). `getPartsByDateAndKiosk()` fetches part-based delivery rows. `createPartDelivery()` and `createPartPullout()` are the active insert paths for new rows. `updateQuantity()` lets the Owner edit an unlocked delivery inline.

- **`SalesModel.php`** — Records every sale. Each row is "this kiosk sold this product, this many units, at this price, on this date." The total amount is calculated automatically by a database trigger (more on that later). `createBatch()` writes a whole POS cart in one transaction so a 6-item order is either fully saved or fully rolled back. `getDailyTotalByKiosk()` powers the Owner dashboard's per-kiosk day totals.

- **`ExpenseModel.php`** — Tracks daily operating expenses per kiosk (transport, utilities, supplies, etc.). `update()` lets the Owner edit an unlocked expense's amount or description inline.

- **`AuditLogModel.php`** — The system's diary. Every important action (login, unlock, edit, delete) writes a line here so the owner can always see *who did what, and when*. The `getAll()` method now accepts filters (date range, action type) plus pagination, and a companion `countAll()` returns the total row count so the Audit Log page can show "Showing 1–20 of 286" with proper Next/Prev buttons.

- **`TimeInModel.php`** — Records when staff members start their shift. Helps the owner check attendance.

---

## 🎯 5. `controllers/` — The Traffic Cops

### What controllers are (in plain English)

A **controller** is the middleman between what the user wants and what the system does. When a button is clicked, the controller decides what happens next: it checks who you are, asks the right model for data, and tells the right view to display itself.

**Why this matters:** Controllers keep the messy logic out of the views (which should just *display* things) and out of the models (which should just *manage data*). They're the glue.

**Real-world analogy:** A waiter at a restaurant. The waiter doesn't cook (that's the kitchen / model) and isn't the menu (that's the view), but they take your order, pass it to the kitchen, and bring back the food.

**How it works:** When the Router sees a URL, it picks a controller and calls one of its methods (e.g. "the user wants to create a sale → call `SalesController::create`"). The method runs, gathers data, and either renders a page or redirects somewhere.

### Files inside

- **`AuthController.php`** — Handles login and logout. When someone submits the login form, this controller checks the password, starts the session, and writes "user X logged in" to the Audit Log.

- **`DashboardController.php`** — Builds the home page after login. Now **fully role-aware**: `buildOwnerData()` assembles per-kiosk stat cards and a kiosk-status grid showing today's progress; `buildStaffData()` gives the kiosk staff a focused "your kiosk today" view with progress meters and a contextual "what's the next thing you should do" callout (record beginning stock, end the day, etc.); `buildAuditorData()` shows a minimal read-only landing.

- **`InventoryController.php`** — Handles the daily inventory pages: showing today's snapshot, accepting beginning and ending stock entries, and (for the Owner) unlocking past records. Adds `autoGenerateBeginning()` for the perpetual-inventory carry-forward — staff click one button and today's beginning rows are created from yesterday's ending values, audit-logged.

- **`DeliveryController.php`** — Handles delivery entry pages: showing what was delivered today, accepting new delivery records, and locking past delivery days. Adds `update()` so the Owner can correct an unlocked delivery's quantity without deleting and re-creating the row. Now also depends on `InventoryModel` to load the current running stock per part, which it passes to the view as `part_stock` so the "Select Part" table shows live pcs.

- **`SalesController.php`** — The point-of-sale screen. Lists all products with photos and prices, accepts quantities sold, and saves everything to the Sales table. The original `store()` method handles single-row writes; the new `storeBatch()` handles the cart-based POS flow — a whole order (multiple products + quantities) is submitted in one POST and saved atomically through `SalesModel::createBatch()`.

- **`ExpenseController.php`** — Handles daily expense entries: showing the day's expenses and accepting new ones. Adds `update()` for inline-editing an unlocked expense's amount or description.

- **`ReportController.php`** — Builds the reports the Owner cares about: daily summaries per kiosk, consolidated weekly/monthly reports, and the staff time-in log.

- **`AdminController.php`** — The Owner's admin panel. Manages users, products, kiosks, and the audit log viewer. This is the controller with the most "power" — only the Owner role is allowed to use it. New methods: `editUser()` loads a user-edit form, `updateUser()` handles profile-field changes (role, kiosk, full name, username) with username-uniqueness validation, and `resetUserPassword()` lets the Owner force a new password without knowing the old one. `users()` and `kiosks()` now honor a `?show_all=1` flag so deactivated rows can be viewed and reactivated. `auditLog()` now supports date-range and action-type filtering plus server-side pagination (20 per page).

---

## 👀 6. `views/` — What the User Actually Sees

### What views are (in plain English)

**Views** are the screens — the actual HTML pages that get sent to the browser and shown to the user. They contain almost no logic; they just display data that the controller already prepared.

**Why this matters:** Keeping the screen layout separate from the data logic means a designer can change the look of the page without breaking how the system works.

**Real-world analogy:** The menu and tablecloth at a restaurant. They're what the customer sees and touches, but the actual food preparation happens out of sight.

**How it works:** The controller calls a view file and hands it a tray of data (an array). The view file is mostly HTML with small placeholders that fill in that data — names, totals, lists of products, etc.

### Subfolders inside

- **`layouts/`** — The shared shell every page uses, so we don't repeat the navigation bar and header on every screen.
  - **`main.php`** — The HTML wrapper (the `<head>`, `<body>`, footer). Every other view is "wrapped" by this file.
  - **`navbar.php`** — The top bar with the logo and the user's full name (now uses `Auth::fullName()` instead of just the username).
  - **`sidebar.php`** — The left-side menu that changes depending on the user's role (Owner sees more options, Staff sees less). Displays the **Logo.png** brand image in the header instead of the plain app name text (`.sidebar-logo` fills the container width). Staff users also see a 📍 **kiosk badge** at the bottom showing which branch they're assigned to (uses `$_SESSION['kiosk_name']` cached at login).
  - **`modal.php`** — A single hidden confirm-dialog overlay that gets included once by `main.php` and is then driven by the `showConfirmModal()` helper in `assets/js/app.js`. Every Lock / Unlock / Delete / Save button across the app calls into it instead of using the browser's native `confirm()` — keeps the styling consistent and lets the modal show colour-coded confirm buttons (red for delete, blue for unlock, green for lock).

- **`auth/`** — Login screen.
  - **`login.php`** — The username/password form. The only page accessible without being logged in. Displays the **Logo.png** brand image at the top of the card (`.login-logo` fills the card width).

- **`dashboard/`** — Home page after login.
  - **`index.php`** — Now **role-aware**. Owner sees a multi-kiosk overview with stat cards (today's sales, units sold, etc.) and a kiosk-status grid showing which branches have started/closed their day. Staff sees a focused "your kiosk today" view with progress meters (beginning stock recorded? deliveries logged? etc.) and a contextual action callout that tells them the next thing to do. Auditor sees a minimal read-only landing.

- **`inventory/`** — Inventory module screens.
  - **`index.php`** — Tablet-friendly daily entry screen. Products are split into **category sub-tabs** (Burgers, Drinks, Hotdogs, Ricebowl, Snacks) so staff don't scroll a flat list. Each row has finger-sized **+/− buttons**, a live **progress bar** ("X of 33 filled"), and a **sticky Save bar** anchored to the bottom of the screen. Now also shows the **perpetual inventory** features: a green ✓ "carry-forward" badge when today's beginning stock came from yesterday's ending, an "Auto-generate beginning" card that copies the previous day's ending values in one click, and a live **Running Inventory** widget computing `beginning + delivered − sold` per product so staff can see what's left right now.

- **`delivery/`** — Delivery module screens.
  - **`index.php`** — **Tab-based layout** (`Add Delivery` + `Delivery Records (N)`). Add tab shows the full-width product grid with category pills; tapping a card slides a compact entry panel in **above** the grid (no horizontal scrolling). Records tab shows the records table with a Total Units indicator, Lock All button (Owner), and inline Edit rows for unlocked records. Auto-jumps to the Records tab right after a successful add. A **Recent Deliveries** quick-link card appears above the tabs. The Select Part table has a **Stock** column showing the current running inventory in pcs for each part (pulled from `part_stock` computed by `DeliveryController`). Shows `—` when no beginning snapshot has been recorded yet.

- **`sales/`** — Point-of-sale screen.
  - **`index.php`** — **Tab-based POS** (`New Order` + `Sales Records (N)`). New Order tab is a 65/35 split: full-width product grid on the left, persistent order cart on the right. Tapping a product adds it to the cart with a green qty badge on the card; cart shows live unit count and grand total, with +/-/× line controls. Confirm Order submits the whole cart in one transaction via `/sales/store-batch`. Records tab shows the day's sales with a Day Total strip, totals row at the bottom, Lock All (Owner), Delete/Unlock actions, and a 🧾 empty-state card with a "New Order" shortcut.

- **`expenses/`** — Expense entry screen.
  - **`index.php`** — Two-row form (description full width, amount + button below), record-count + total summary in the header, 🧾 empty-state card when no entries, and inline Edit rows for unlocked expenses (Owner can change description and amount without deleting).

- **`reports/`** — Report viewers.
  - **`tabs.php`** — Shared row of tabs (Daily / Consolidated / Time-in) that appears on top of every report page.
  - **`daily.php`** — Daily summary per kiosk (stock, deliveries, sales, expenses). Uses **category sub-tabs** to slice the 33-product list, an **amber discrepancy banner** when stock counts don't reconcile, a live-updating **totals row**, and **client-side pagination** for big result sets.
  - **`consolidated.php`** — Weekly / monthly roll-up across all 5 kiosks. The 3 overview cards (Total Sales / Total Expenses / Net Income) stay pinned at the top while **6 pill-shaped inner tabs** swap between Sales-by-Kiosk, Expenses-by-Kiosk, Daily Sales, Daily Expenses, Deliveries, and an Anomalies tab that only appears when there's something wrong. Per-kiosk rows include a **horizontal share-bar** for at-a-glance comparison.
  - **`timein.php`** — Staff attendance / time-in log. Topped with **3 summary stat cards** (total check-ins, unique staff, kiosks with check-ins). Records are **grouped by date into collapsible sections** with an "Expand All / Collapse All" toggle, paginated 7 days at a time.
  - **`_empty_state.php`** — Tiny shared partial (📊 icon + "No data" message) reused across the consolidated report's panels so the empty-state styling stays consistent.

- **`admin/`** — Admin panel screens (Owner only).
  - **`users.php`** — Add user form, list of users, per-row **Edit** button, and a "Show Deactivated" toggle so the Owner can reactivate accounts that were turned off. Deactivated rows are dimmed.
  - **`edit-user.php`** — *(new this iteration)* Three-card edit page reached from the users list: **Profile** (role, kiosk, full name, username), **Reset Password** (Owner can force a new password without knowing the old one), and **Account Info** (read-only ID, status, role, kiosk, created date).
  - **`products.php`** — Manage the menu (create, edit, upload photo, deactivate). Now flags **active products priced at ₱0.00** with a yellow ⚠ icon next to the price and a banner at the top of the page so the Owner can see at a glance which menu items still need a real price.
  - **`kiosks.php`** — Manage the 5 kiosks. Each row has an **Edit** button that toggles an inline edit row (name + location); Activate/Deactivate stays as a separate action. Includes a "Show Inactive" toggle.
  - **`audit-log.php`** — Viewer for the audit trail. Filterable by **date range**, **action type** (LOGIN / CREATE / UPDATE / DELETE / LOCK / UNLOCK / RECORD_UNLOCKED), with quick-filter pills (Today / This Week / Last 30 Days). Each row has an **expand button** that opens a side-by-side **OLD vs NEW JSON diff panel** populated by the change-logging triggers. Server-side paginated 20 per page with windowed `…` page navigation. Sort order is `Timestamp DESC, Log_ID DESC` so rows that share the same second still appear in deterministic insertion order.

---

## 🎨 7. `assets/` — Static Files

**What it is:** All the static stuff the browser needs — stylesheets, scripts, images. Things that don't change based on who's logged in.

**Why it exists:** Browsers download these once and cache them, which makes pages load faster and keeps the project organized.

**Real-world analogy:** The decor and signage of a shop. The wallpaper, the logo on the door, the price tags — they're separate from the products themselves but make the whole experience work.

### Files inside

- **`css/style.css`** — The single stylesheet that controls how every page looks: colors, fonts, spacing, button shapes, table layouts. Want to change the theme? Edit this one file.

- **`js/app.js`** — The JavaScript that adds interactivity: form validation hints, table pagination on the products page, confirmation dialogs.

- **`img/products/`** — The actual product photos shown in the point-of-sale grid. Photos are organized by category subfolder (`Burgers/`, `Drinks/`, `Hotdogs/`, `Ricebowl/`, `Snacks/`) and the system automatically figures out which file matches each product name.

- **`img/graphics/`** — brand graphics. Currently contains `Logo.png`, the brand logo displayed on the login page header and the sidebar header.

---

## 🗄️ 8. `sql/` — Database Scripts

**What it is:** Plain text files containing instructions for the database — how to build tables, what triggers to install, and a record of every change ever made.

**Why it exists:** The database structure isn't created by hand. It's built by running these scripts. That way anyone (even a future Juan in 5 years) can rebuild the entire database from scratch by re-running the scripts.

**Real-world analogy:** The blueprints of the office building. You don't keep the blueprints inside the building — you keep them safely on the side so you can rebuild if needed.

### Files inside

- **`schema.sql`** — The "blueprint" file. Running this on an empty database creates all 11 tables with the right columns, types, and relationships. Also seeds the default rows (the 3 roles, the 5 kiosks, the 5 categories).

- **`triggers.sql`** — Installs the 7 automatic database rules (called *triggers*). More on triggers in Section E below.

- **`migrations/`** — Folder of small "change scripts" that record specific updates to the database after the initial schema. Right now it contains `2026_rename_outlet_to_kiosk.sql`, which renamed the `Outlet_ID` column to `Kiosk_ID` everywhere. Each migration is dated and run once.

- **`backups/`** — Folder of full database snapshots taken before risky operations, so we can restore if something breaks. Currently holds `backup_before_outlet_rename.sql`, the pre-rename safety net.

- **`demo_reset.sql`** — Demo-prep script. Wipes every transactional table (Sales, Delivery, Expenses, Inventory_Snapshot, Time_in, Audit_Log) without touching users, kiosks, or products, then sets realistic Philippine kiosk prices on every product and reactivates them all. Re-runnable anytime to start a clean demo. The Audit_Log noise from the price UPDATEs is wiped at the end so the log starts empty.

- **`demo_seed.sql`** — Companion to the reset script. Creates **5 staff users** (one per kiosk: `staff_k1` … `staff_k5`, all with password `staff1234`), then populates **yesterday** with a fully-locked operational day (inventory beginning + ending, deliveries, sales, expenses, time-in punches) and **today** with an open mid-day partial state (beginning inventory carried-forward from yesterday's ending, a few morning sales/deliveries/expenses, today's time-in). Wraps up with ~13 realistic-looking Audit_Log entries (logins, lock-all events) so the Audit Log page looks used. Workflow: `mysql -u root chicken_deluxe < sql/demo_reset.sql && mysql -u root chicken_deluxe < sql/demo_seed.sql`.

---

## 📖 9. Documentation Files (project root)

- **`CLAUDE.md`** — Instructions for the AI coding assistant (Claude Code). Lays out coding rules, folder structure, and security guidelines so the AI's contributions match Juan's preferred style.

- **`PROJECT_CONTEXT.md`** — The full project briefing: what the system does, who it's for, the tech stack, the database schema, and the business rules. Read by anyone (human or AI) joining the project.

- **`PROJECT_STRUCTURE_EXPLAINED.md`** — *This file.* The plain-English tour you're reading right now.

- **`DATABASE_FULL_DOCUMENTATION.md`** — The deep technical reference for every table, column, trigger, and design decision.

---

# 🎬 SECTION A — How a Page Load Works (Step by Step)

Let's follow what happens when a staff member at the Tagbak kiosk opens the Sales page, picks 2 burgers and 1 drink, and clicks **Submit Sales**.

1. The staff types the URL or clicks the **Sales** link in the sidebar. The browser sends a request to the server: "I want `/chicken-deluxe/sales`."

2. The web server doesn't find a file literally called `sales`. Thanks to the `.htaccess` rule, it forwards the request to `index.php`.

3. `index.php` wakes up, loads the configuration files, starts the session (so it knows who's logged in), and hands the request to the **Router**.

4. The **Router** looks at the URL `/sales` and decides: "this is for the **SalesController**, method `index`."

5. **SalesController** first checks with **Auth**: "is this person logged in? are they allowed to use the sales page?" Auth says yes, this is James, a Staff member assigned to Kiosk 1.

6. SalesController asks the **ProductModel**: "give me all active products grouped by category." ProductModel asks the **Database** class for the data, and Database routes the request to the **Slave** (because it's just reading).

7. SalesController hands the product list to the **Sales view** (`views/sales/index.php`). The view fills in the HTML — product names, photos, prices, quantity input boxes — and sends it back to the browser.

8. The staff sees the screen, types `2` next to "Classic Burger" and `1` next to "Cola," and clicks **Submit Sales**.

9. The browser sends the form data back to the server (still going through `index.php`). The Router this time sees `/sales/create` and calls `SalesController::create`.

10. SalesController checks Auth again ("are you still logged in and still Staff?"), validates the input ("are these positive numbers? are these real product IDs?"), and asks the **SalesModel** to save each line.

11. SalesModel asks the Database to write to the **Master** (because writing). For each product sold, it runs an `INSERT` into the Sales table.

12. As each row is inserted, a **database trigger** (`trg_calc_line_total_insert`) automatically calculates `Line_total = Quantity_sold × Unit_Price` so the staff doesn't have to.

13. The Master immediately replicates the new rows to the Slave so reads will see them.

14. SalesController writes a row to the **Audit_Log** table: "James created sales records on this date."

15. SalesController redirects the browser back to the Sales page with a success message. The staff sees "Sales recorded successfully!" and is ready for the next sale.

That's the full lifecycle of one click. Every page in the system follows this same pattern.

---

# 🎭 SECTION B — How the MVC Pattern Works (Restaurant Analogy)

The project uses a design called **MVC** — short for **Model, View, Controller**. Imagine a restaurant:

- 📋 **The View is the menu** — it's what the customer (the user) sees and picks from. The menu doesn't cook anything; it just lists what's available and shows pretty pictures.

- 🤵 **The Controller is the waiter** — they take your order from the menu, ask the kitchen to cook it, and bring back the plate. The waiter doesn't cook (they don't know how!) and they don't print menus. They're the messenger and gatekeeper.

- 👨‍🍳 **The Model is the kitchen** — they do the actual work. They know the recipes, where the ingredients are stored, and how to assemble a plate. They never talk to the customer directly.

**A real example from this project:**

A staff member opens the Inventory page (📋 the View) and submits the day's beginning stock. The form is sent to the **InventoryController** (🤵 the waiter), which checks the staff is allowed to do this, then asks the **InventoryModel** (👨‍🍳 the kitchen) to save the stock numbers. The model writes to the database, returns success, and the controller tells the view to display "Saved!" back to the staff.

**Why split things up like this?**

- If we want to change how the page *looks*, we only edit the View. The kitchen and waiter don't change.
- If we want to change how data is *stored*, we only edit the Model. The menu and waiter don't change.
- If we want to add a new check (like "auditors can't enter sales"), we only edit the Controller.

It's the same reason a restaurant has separate roles: each person can become really good at their one job.

---

# 🏢 SECTION C — How Master-Slave DB Works (Head Office vs Branch Office)

This system has **two databases**, not one. Both contain exactly the same information, but they have different jobs.

- **Master Database (port 3306)** — the head office. *All changes happen here.* Every new sale, every inventory entry, every new user — they're written to the Master.

- **Slave Database (port 3307)** — the branch office. *Only reading happens here.* Every list, report, and lookup is read from the Slave.

**How they stay in sync:** Every time the Master writes something, it whispers the change to the Slave, which copies it within milliseconds. So both databases always have the same data — the Slave just lags by a tiny fraction of a second.

**Why bother having two?**

1. **Performance** — Reading is much more common than writing in this system (think how often the Sales page loads vs. how often a sale is actually submitted). Splitting reads onto a separate database keeps the Master free to handle writes quickly.

2. **Safety** — If the Master ever crashes, the Slave still has all the data. We could fail over to it as a backup.

3. **Coursework requirement** — Master-slave replication is required for the school project, and it's a real-world setup big companies use.

**Analogy:** Think of a chain of bookstores. The **head office (Master)** decides which books to stock and updates the official catalog. Each **branch (Slave)** receives a copy of the catalog every morning so customers can browse without disturbing the head office. Customers can only buy / order from a branch (read), but the catalog itself is only ever updated at head office (write). The system in this project does this automatically and instantly, not once a day.

The `core/Database.php` class handles all of this for you — when a model says "write this," it picks Master; when it says "read this," it picks Slave. No model ever has to think about it.

---

# 🔒 SECTION D — How the Lock System Works

The lock system is the main reason this project exists. The owner, Cherryll, was tired of staff editing past entries in Google Sheets and changing numbers. So we built a digital lock.

**What "locked" means in plain English:**
A locked record is one that **cannot be changed by anyone except the Owner**. The data is still there, still readable, still showing up in reports — it's just frozen. Like a notarized document: anyone can read it, but no one can scribble on it.

**Where it shows up in the database:**
Most important tables (Inventory_Snapshot, Sales, Delivery, Expenses) have an extra column called `Locked_status`. It's either:
- `0` = unlocked (editable)
- `1` = locked (frozen)

That's the entire mechanism. A single number per row.

**Who can lock and unlock:**
- **Locking** happens *automatically*. When the staff finishes the day's inventory by entering ending stock, the system locks all records for that day. Sales and expenses get locked at end of day too. No human has to click anything — it just happens.
- **Unlocking** can *only* be done by the **Owner role**. If a staff member needs to fix a typo from yesterday, they have to ask the owner to unlock it. The owner clicks "unlock" in the admin panel; the system flips `Locked_status` from `1` back to `0`, *and* writes a row in the Audit Log saying "owner unlocked record X on this date." So nothing happens in secret.

**What stops staff from cheating:**
Even if a staff member somehow tried to send an edit request to the database directly (skipping the buttons on the screen), a **database trigger** stops them. The trigger fires *before* the database accepts the change, sees that `Locked_status = 1`, and refuses with an error. So the lock isn't just a UI hint — it's enforced at the deepest level.

This means the "no editing past records" rule cannot be bypassed without owner approval and an audit trail entry. That's the whole point.

---

# ⚡ SECTION E — How Triggers Work in Plain English

**What a trigger is:**
A **trigger** is an automatic rule the database runs on its own whenever a specific event happens. Think of it like a **smoke detector**: nobody tells it to go off — it watches for smoke (the event) and reacts instantly (the alarm). You don't have to remember to set it off; it just does its job.

**Why use triggers instead of doing it in PHP code?**
Because triggers can't be skipped. Even if a future programmer forgets to add the right calculation in the controller, or a developer connects directly to the database with a desktop tool and tries to make a change, the trigger still fires. It's the database's own immune system.

The project has **33 active triggers** (6 business-rule + 27 change-logging). Here are the 6 business-rule ones in plain English; the change-logging triggers are mechanical and documented in `DATABASE_FULL_DOCUMENTATION.md` Part 2:

1. **`trg_calc_line_total_insert`** — Whenever a new sale row is inserted, this trigger automatically calculates `Quantity Sold × Unit Price` and stores the result in `Line_total`. The staff never has to do math, and they can't accidentally enter a wrong total.

2. **`trg_calc_line_total_update`** — Same as above, but fires when an existing sale is *edited*. If the owner unlocks a sale and changes the quantity, the line total recalculates automatically — it can never go out of sync.

3. **`trg_prevent_locked_sales_edit`** — The bouncer for the Sales table. Before any update, it checks: "is this row locked?" If yes, it slams the door and refuses the edit with an error message. This is what makes the lock unbreakable.

4. **`trg_prevent_locked_inventory_edit`** — Same bouncer, but for the Inventory_Snapshot table. Frozen inventory records cannot be touched.

5. **`trg_prevent_locked_delivery_edit`** — Same bouncer, but for the Delivery table. Frozen delivery records cannot be touched.

6. **`trg_prevent_locked_expense_edit`** — Same bouncer, but for the Expenses table. Frozen expense records cannot be touched.

**Notes on removed triggers:**

- `trg_auto_lock_inventory` (originally planned) — was supposed to fire `AFTER INSERT ON Inventory_Snapshot` and lock all snapshots for that day when an `ending` snapshot was added. MySQL doesn't allow a trigger to update the same table that fired it (it would create an infinite loop), so we moved that logic into PHP code (specifically `InventoryModel::createBatchSnapshots`). Same end result, just done in a different layer.

- `trg_audit_inventory_unlock` — used to log every inventory unlock as a `RECORD_UNLOCKED` row. Removed 2026-05 because the generic `trg_log_inventory_snapshot_update` (one of the change-logging triggers) already captures every unlock with full before/after JSON, making the dedicated trigger redundant noise.

---

# 🎁 WRAPPING UP

You've now seen every folder, every key file, and every architectural pattern in the project. The big takeaways:

- **One front door** (`index.php`) protects everything.
- **Settings live in one place** (`config/`).
- **Shared engine parts** sit in `core/`.
- **Models** know the data, **Views** know the look, **Controllers** glue them together.
- **Static files** (CSS, JS, images) live in `assets/`.
- **The database** is built and changed via scripts in `sql/`.
- **Two databases** (Master + Slave) split writes from reads.
- **Locks and triggers** make the rules unbreakable, even by future programmers.

If you ever get lost, come back to this document and find the right folder for the question you're trying to answer. The structure is consistent on purpose — once you understand one module (say, sales), you understand them all, because deliveries, expenses, and inventory follow the exact same pattern.

---

*PROJECT_STRUCTURE_EXPLAINED.md — Chicken Deluxe Inventory & Sales Monitoring System — Group 7 — BSIT 2-B — 2026; May 2026 — delivery stock column, logo image; May 2026 — trigger audit fix*
