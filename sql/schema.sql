-- ============================================
-- Chicken Deluxe Inventory & Sales Monitoring
-- Database Schema — Run on MASTER (port 3306)
-- ============================================

CREATE DATABASE IF NOT EXISTS chicken_deluxe
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE chicken_deluxe;

-- ==================
-- 1. Role
-- ==================
CREATE TABLE IF NOT EXISTS Role (
    Role_ID   INT AUTO_INCREMENT PRIMARY KEY,
    Name      VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Seed default roles
INSERT INTO Role (Role_ID, Name) VALUES
    (1, 'Owner'),
    (2, 'Staff'),
    (3, 'Auditor')
ON DUPLICATE KEY UPDATE Name = VALUES(Name);

-- ==================
-- 2. Kiosk (Kiosk)
-- ==================
CREATE TABLE IF NOT EXISTS Kiosk (
    Kiosk_ID    INT AUTO_INCREMENT PRIMARY KEY,
    Name        VARCHAR(100) NOT NULL,
    Location    VARCHAR(255) NOT NULL,
    Active      TINYINT(1) NOT NULL DEFAULT 1,
    Created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed the 5 kiosk locations
INSERT INTO Kiosk (Kiosk_ID, Name, Location) VALUES
    (1, 'Tagbak Branch',       'Tagbak, Jaro, Iloilo'),
    (2, 'Atrium Branch',       'The Atrium Mall, Iloilo'),
    (3, 'City Proper Branch',  'City Proper, Iloilo'),
    (4, 'Supermart Branch',    'Iloilo Supermart, Mandurriao, Iloilo'),
    (5, 'Aldeguer Branch',     'Aldeguer St., Iloilo')
ON DUPLICATE KEY UPDATE Name = VALUES(Name), Location = VALUES(Location);

-- ==================
-- 3. User
-- ==================
CREATE TABLE IF NOT EXISTS User (
    User_ID       INT AUTO_INCREMENT PRIMARY KEY,
    Role_ID       INT NOT NULL,
    Kiosk_ID     INT NULL,
    Username      VARCHAR(50) NOT NULL UNIQUE,
    Password      VARCHAR(255) NOT NULL,
    Full_name     VARCHAR(100) NOT NULL,
    Active_status TINYINT(1) NOT NULL DEFAULT 1,
    Created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Role_ID)   REFERENCES Role(Role_ID),
    FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID)
) ENGINE=InnoDB;

-- ==================
-- 4. Category
-- ==================
CREATE TABLE IF NOT EXISTS Category (
    Category_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name        VARCHAR(50) NOT NULL UNIQUE,
    Active      TINYINT(1) NOT NULL DEFAULT 1,
    Created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed the 5 product categories
INSERT INTO Category (Category_ID, Name) VALUES
    (1, 'Burgers'),
    (2, 'Drinks'),
    (3, 'Hotdogs'),
    (4, 'Ricebowl'),
    (5, 'Snacks')
ON DUPLICATE KEY UPDATE Name = VALUES(Name);

-- ==================
-- 5. Product
-- ==================
CREATE TABLE IF NOT EXISTS Product (
    Product_ID  INT AUTO_INCREMENT PRIMARY KEY,
    Category_ID INT NOT NULL,
    Name        VARCHAR(100) NOT NULL,
    Unit        VARCHAR(30) NOT NULL DEFAULT 'pcs',
    Price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Active      TINYINT(1) NOT NULL DEFAULT 1,
    Created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Category_ID) REFERENCES Category(Category_ID)
) ENGINE=InnoDB;

-- ==================
-- 6. Inventory_Snapshot
-- ==================
CREATE TABLE IF NOT EXISTS Inventory_Snapshot (
    Inventory_ID   INT AUTO_INCREMENT PRIMARY KEY,
    Kiosk_ID      INT NOT NULL,
    Product_ID     INT NOT NULL,
    User_ID        INT NOT NULL,
    Locked_status  TINYINT(1) NOT NULL DEFAULT 0,
    Snapshot_date  DATE NOT NULL,
    Snapshot_type  ENUM('beginning', 'ending') NOT NULL,
    Quantity       INT NOT NULL DEFAULT 0,
    Recorded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Kiosk_ID)  REFERENCES Kiosk(Kiosk_ID),
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID),
    FOREIGN KEY (User_ID)    REFERENCES User(User_ID),
    UNIQUE KEY uq_snapshot (Kiosk_ID, Product_ID, Snapshot_date, Snapshot_type)
) ENGINE=InnoDB;

-- ==================
-- 7. Delivery
-- ==================
CREATE TABLE IF NOT EXISTS Delivery (
    Delivery_ID   INT AUTO_INCREMENT PRIMARY KEY,
    Kiosk_ID     INT NOT NULL,
    User_ID       INT NOT NULL,
    Product_ID    INT NOT NULL,
    Delivery_Date DATE NOT NULL,
    Quantity      INT NOT NULL DEFAULT 0,
    Locked_status TINYINT(1) NOT NULL DEFAULT 0,
    Created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Kiosk_ID)  REFERENCES Kiosk(Kiosk_ID),
    FOREIGN KEY (User_ID)    REFERENCES User(User_ID),
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID)
) ENGINE=InnoDB;

-- ==================
-- 8. Sales
-- ==================
CREATE TABLE IF NOT EXISTS Sales (
    Sales_ID      INT AUTO_INCREMENT PRIMARY KEY,
    Kiosk_ID     INT NOT NULL,
    User_ID       INT NOT NULL,
    Product_ID    INT NOT NULL,
    Sales_date    DATE NOT NULL,
    Quantity_sold INT NOT NULL DEFAULT 0,
    Unit_Price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Line_total    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Locked_status TINYINT(1) NOT NULL DEFAULT 0,
    Created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Kiosk_ID)  REFERENCES Kiosk(Kiosk_ID),
    FOREIGN KEY (User_ID)    REFERENCES User(User_ID),
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID)
) ENGINE=InnoDB;

-- ==================
-- 9. Expenses
-- ==================
CREATE TABLE IF NOT EXISTS Expenses (
    Expense_ID   INT AUTO_INCREMENT PRIMARY KEY,
    Kiosk_ID    INT NOT NULL,
    User_ID      INT NOT NULL,
    Expense_date DATE NOT NULL,
    Amount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Description  VARCHAR(255) NOT NULL,
    Locked_status TINYINT(1) NOT NULL DEFAULT 0,
    Created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID),
    FOREIGN KEY (User_ID)   REFERENCES User(User_ID)
) ENGINE=InnoDB;

-- ==================
-- 10. Audit_Log
-- ==================
CREATE TABLE IF NOT EXISTS Audit_Log (
    Log_ID      INT AUTO_INCREMENT PRIMARY KEY,
    User_ID     INT NULL,
    Action      VARCHAR(100) NOT NULL,
    Operation   VARCHAR(10) NULL,          -- INSERT / UPDATE / DELETE (for change-logging triggers)
    Table_name  VARCHAR(100) NULL,         -- source table name (for change-logging triggers)
    Old_values  TEXT NULL,                 -- JSON snapshot of OLD row (UPDATE/DELETE only)
    New_values  TEXT NULL,                 -- JSON snapshot of NEW row (INSERT/UPDATE only)
    Details     VARCHAR(255) NULL,
    Timestamp   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID) REFERENCES User(User_ID)
) ENGINE=InnoDB;

-- ==================
-- 11. Time_in
-- ==================
CREATE TABLE IF NOT EXISTS Time_in (
    Timein_ID  INT AUTO_INCREMENT PRIMARY KEY,
    User_ID    INT NOT NULL,
    Kiosk_ID  INT NOT NULL,
    Timestamp  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID)   REFERENCES User(User_ID),
    FOREIGN KEY (Kiosk_ID) REFERENCES Kiosk(Kiosk_ID)
) ENGINE=InnoDB;

-- ============================================
-- End of schema
-- ============================================
