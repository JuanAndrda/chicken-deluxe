-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: chicken_deluxe
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `Log_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID` int(11) DEFAULT NULL,
  `Action` varchar(100) NOT NULL,
  `Details` varchar(255) DEFAULT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Log_ID`),
  KEY `User_ID` (`User_ID`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,1,'LOGIN','User logged in','2026-04-16 11:31:19'),(2,1,'LOGIN','User logged in','2026-04-16 11:31:27'),(3,1,'LOGIN','User logged in','2026-04-16 11:32:09'),(4,1,'LOGOUT','User logged out','2026-04-16 11:33:03'),(5,1,'LOGIN','User logged in','2026-04-16 11:33:23'),(6,1,'LOGOUT','User logged out','2026-04-16 11:36:57'),(7,1,'LOGIN','User logged in','2026-04-16 11:55:38'),(8,1,'LOGOUT','User logged out','2026-04-16 12:24:05'),(9,1,'LOGIN','User logged in','2026-04-16 12:24:32'),(10,1,'LOGIN','User logged in','2026-04-16 12:26:05'),(11,1,'LOGOUT','User logged out','2026-04-16 12:28:00'),(12,1,'LOGIN','User logged in','2026-04-16 12:28:10'),(13,1,'LOGIN','User logged in','2026-04-16 12:33:10'),(14,1,'LOGIN','User logged in','2026-04-16 12:35:01'),(15,1,'LOGIN','User logged in','2026-04-16 13:02:00'),(16,1,'LOGIN','User logged in','2026-04-16 15:06:43'),(17,1,'CREATE','Created product: Classic Burger (ID:1)','2026-04-16 15:07:09'),(18,1,'LOGIN','User logged in','2026-04-16 15:08:03'),(19,1,'LOGIN','User logged in','2026-04-16 15:12:36'),(20,1,'CREATE','Sales ID:1 — product:1, qty:5, price:65','2026-04-16 15:12:49'),(21,1,'CREATE','Delivery ID:1 — product:1, qty:20','2026-04-16 15:13:01'),(22,1,'CREATE','Expense ID:1 — amount:150, desc:LPG refill','2026-04-16 15:13:02'),(23,1,'CREATE','Delivery ID:2 — product:1, qty:2','2026-04-16 15:15:46'),(24,1,'LOCK','Locked 2 deliveries for outlet:1 on 2026-04-16','2026-04-16 15:15:58'),(25,1,'UNLOCK','Unlocked Delivery ID:2','2026-04-16 15:16:11'),(26,1,'UNLOCK','Unlocked Delivery ID:1','2026-04-16 15:16:14'),(27,1,'LOGIN','User logged in','2026-04-16 15:22:29'),(28,1,'CREATE','Created user: james (ID:2)','2026-04-16 15:23:34'),(29,1,'LOGOUT','User logged out','2026-04-16 15:23:39'),(30,2,'LOGIN','User logged in','2026-04-16 15:23:46'),(31,2,'CREATE','Beginning stock recorded: 1 products for outlet 1 on 2026-04-16','2026-04-16 15:24:41'),(32,2,'CREATE','Sales ID:2 — product:1, qty:2, price:49.99','2026-04-16 15:26:39'),(33,2,'LOCK','Locked 2 sales for outlet:1 on 2026-04-16','2026-04-16 15:26:45'),(34,2,'LOGOUT','User logged out','2026-04-16 15:28:38'),(35,1,'LOGIN','User logged in','2026-04-16 15:28:58'),(36,1,'UNLOCK','Unlocked Sales ID:1','2026-04-16 15:55:04'),(37,1,'UNLOCK','Unlocked Sales ID:2','2026-04-16 15:55:10'),(38,1,'LOGIN','User logged in','2026-04-16 22:30:27'),(39,1,'LOGIN','User logged in','2026-04-16 22:30:38'),(40,1,'LOCK','Locked 2 deliveries for outlet:1 on 2026-04-16','2026-04-16 22:31:55'),(41,1,'UNLOCK','Unlocked Delivery ID:2','2026-04-16 22:31:58'),(42,1,'UNLOCK','Unlocked Delivery ID:1','2026-04-16 22:32:00'),(43,1,'LOGOUT','User logged out','2026-04-16 22:32:12'),(44,2,'LOGIN','User logged in','2026-04-16 22:32:19'),(45,2,'LOGOUT','User logged out','2026-04-16 22:36:35'),(46,1,'LOGIN','User logged in','2026-04-16 22:36:43'),(47,1,'LOCK','Locked 2 deliveries for outlet:1 on 2026-04-16','2026-04-16 22:43:12'),(48,1,'UNLOCK','Unlocked Delivery ID:2','2026-04-16 22:43:16'),(49,1,'UNLOCK','Unlocked Delivery ID:1','2026-04-16 22:43:18'),(50,1,'LOGOUT','User logged out','2026-04-16 22:43:22'),(51,2,'LOGIN','User logged in','2026-04-16 22:45:01'),(52,1,'LOGIN','User logged in','2026-04-17 09:20:43'),(53,1,'CREATE','Sales ID:3 — product:1, qty:1, price:0','2026-04-17 09:24:15'),(54,1,'CREATE','Delivery ID:3 — product:1, qty:1','2026-04-17 09:24:50'),(55,1,'UPDATE','Updated product ID:1','2026-04-17 09:32:14'),(56,1,'UPDATE','Updated product ID:1','2026-04-17 09:32:22'),(57,1,'CREATE','Sales ID:4 — product:1, qty:1, price:95','2026-04-17 09:32:38'),(58,1,'LOGOUT','User logged out','2026-04-17 09:33:46'),(59,2,'LOGIN','User logged in','2026-04-17 09:33:53'),(60,2,'CREATE','Sales ID:5 — product:1, qty:1, price:95','2026-04-17 09:33:58'),(61,2,'LOGOUT','User logged out','2026-04-17 09:34:04'),(62,1,'LOGIN','User logged in','2026-04-17 09:34:16'),(63,1,'CREATE','Beginning stock recorded: 1 products for outlet 1 on 2026-04-17','2026-04-17 09:35:20'),(64,1,'CREATE','Ending stock recorded: 1 products for outlet 1 on 2026-04-17','2026-04-17 09:38:17'),(65,1,'LOGOUT','User logged out','2026-04-17 09:45:08'),(66,1,'LOGIN','User logged in','2026-04-17 19:28:13'),(67,1,'CREATE','Delivery ID:4 — product:1, qty:1','2026-04-17 19:32:40'),(68,1,'DELETE','Deleted Delivery ID:4','2026-04-17 19:32:45'),(69,1,'LOGIN','User logged in','2026-04-17 23:27:54'),(70,1,'LOGIN','User logged in','2026-04-18 00:02:22'),(71,1,'LOCK','Locked 1 deliveries for outlet:1 on 2026-04-17','2026-04-18 00:28:12'),(72,1,'LOCK','Locked 3 sales for outlet:1 on 2026-04-17','2026-04-18 00:28:15');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `Category_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Category_ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Burgers',1,'2026-04-16 11:27:30'),(2,'Drinks',1,'2026-04-16 11:27:30'),(3,'Hotdogs',1,'2026-04-16 11:27:30'),(4,'Ricebowl',1,'2026-04-16 11:27:30'),(5,'Snacks',1,'2026-04-16 11:27:30');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery` (
  `Delivery_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Outlet_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Delivery_Date` date NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Delivery_ID`),
  KEY `Outlet_ID` (`Outlet_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `Product_ID` (`Product_ID`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`Outlet_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  CONSTRAINT `delivery_ibfk_3` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,1,1,'2026-04-16',20,0,'2026-04-16 15:13:01'),(2,1,1,1,'2026-04-16',2,0,'2026-04-16 15:15:46'),(3,1,1,1,'2026-04-17',1,1,'2026-04-17 09:24:50');
/*!40000 ALTER TABLE `delivery` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_prevent_locked_delivery_edit
BEFORE UPDATE ON Delivery
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked delivery record.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `Expense_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Outlet_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Expense_date` date NOT NULL,
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Description` varchar(255) NOT NULL,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Expense_ID`),
  KEY `Outlet_ID` (`Outlet_ID`),
  KEY `User_ID` (`User_ID`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`Outlet_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,1,1,'2026-04-16',150.00,'LPG refill',0,'2026-04-16 15:13:02');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_prevent_locked_expense_edit
BEFORE UPDATE ON Expenses
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked expense record.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `inventory_snapshot`
--

DROP TABLE IF EXISTS `inventory_snapshot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_snapshot` (
  `Inventory_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Outlet_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Snapshot_date` date NOT NULL,
  `Snapshot_type` enum('beginning','ending') NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `Recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Inventory_ID`),
  UNIQUE KEY `uq_snapshot` (`Outlet_ID`,`Product_ID`,`Snapshot_date`,`Snapshot_type`),
  KEY `Product_ID` (`Product_ID`),
  KEY `User_ID` (`User_ID`),
  CONSTRAINT `inventory_snapshot_ibfk_1` FOREIGN KEY (`Outlet_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `inventory_snapshot_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`),
  CONSTRAINT `inventory_snapshot_ibfk_3` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_snapshot`
--

LOCK TABLES `inventory_snapshot` WRITE;
/*!40000 ALTER TABLE `inventory_snapshot` DISABLE KEYS */;
INSERT INTO `inventory_snapshot` VALUES (1,1,1,2,0,'2026-04-16','beginning',10,'2026-04-16 15:24:41'),(6,1,1,1,1,'2026-04-17','beginning',10,'2026-04-17 09:35:19'),(8,1,1,1,1,'2026-04-17','ending',2,'2026-04-17 09:38:17');
/*!40000 ALTER TABLE `inventory_snapshot` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_prevent_locked_inventory_edit
BEFORE UPDATE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked inventory record.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_audit_inventory_unlock
AFTER UPDATE ON Inventory_Snapshot
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 0 THEN
        INSERT INTO Audit_Log (User_ID, Action, Details, Timestamp)
        VALUES (NEW.User_ID, 'RECORD_UNLOCKED',
            CONCAT('Inventory_Snapshot ID:', NEW.Inventory_ID, ' unlocked for ', NEW.Snapshot_date),
            NOW());
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `kiosk`
--

DROP TABLE IF EXISTS `kiosk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kiosk` (
  `Kiosk_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Location` varchar(255) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Kiosk_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kiosk`
--

LOCK TABLES `kiosk` WRITE;
/*!40000 ALTER TABLE `kiosk` DISABLE KEYS */;
INSERT INTO `kiosk` VALUES (1,'Tagbak Branch','Tagbak, Jaro, Iloilo',1,'2026-04-16 11:27:30'),(2,'Atrium Branch','The Atrium Mall, Iloilo',1,'2026-04-16 11:27:30'),(3,'City Proper Branch','City Proper, Iloilo',1,'2026-04-16 11:27:30'),(4,'Supermart Branch','Iloilo Supermart, Mandurriao, Iloilo',1,'2026-04-16 11:27:30'),(5,'Aldeguer Branch','Aldeguer St., Iloilo',1,'2026-04-16 11:27:30');
/*!40000 ALTER TABLE `kiosk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `Product_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Category_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Unit` varchar(30) NOT NULL DEFAULT 'pcs',
  `Price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Product_ID`),
  KEY `Category_ID` (`Category_ID`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`Category_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,1,'Classic Burger','pcs',95.00,1,'2026-04-16 15:07:09'),(2,3,'Classic Hotdog','pcs',45.00,1,'2026-04-17 19:31:11'),(3,1,'All Around Burger','pcs',0.00,1,'2026-04-18 00:01:13'),(4,1,'Burger with Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(5,1,'Burger with Egg & Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(6,1,'Burger with Ham & Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(7,1,'Burger with Ham & Egg','pcs',0.00,1,'2026-04-18 00:01:13'),(8,1,'Burger Patty','pcs',0.00,1,'2026-04-18 00:01:13'),(9,1,'Burger Patty with Egg & Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(10,1,'Burger Patty with Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(11,1,'Burger Patty with Egg','pcs',0.00,1,'2026-04-18 00:01:13'),(12,2,'Caramel Coffee','pcs',0.00,1,'2026-04-18 00:01:13'),(13,2,'Coke Swakto','pcs',0.00,1,'2026-04-18 00:01:13'),(14,2,'Iced Coffee','pcs',0.00,1,'2026-04-18 00:01:13'),(15,2,'Iced Matcha','pcs',0.00,1,'2026-04-18 00:01:13'),(16,2,'Royal Swakto','pcs',0.00,1,'2026-04-18 00:01:13'),(17,2,'Sprite Swakto','pcs',0.00,1,'2026-04-18 00:01:13'),(18,2,'Sting','pcs',0.00,1,'2026-04-18 00:01:13'),(19,2,'Coke 500ml','pcs',0.00,1,'2026-04-18 00:01:13'),(20,2,'Royal 500ml','pcs',0.00,1,'2026-04-18 00:01:13'),(21,2,'Sprite 500ml','pcs',0.00,1,'2026-04-18 00:01:13'),(22,3,'Hungarian Hotdog','pcs',0.00,1,'2026-04-18 00:01:13'),(23,4,'Cup of Rice','pcs',0.00,1,'2026-04-18 00:01:13'),(24,4,'Egg','pcs',0.00,1,'2026-04-18 00:01:13'),(25,4,'Lumpia Bowl','pcs',0.00,1,'2026-04-18 00:01:13'),(26,4,'Siomai Bowl','pcs',0.00,1,'2026-04-18 00:01:13'),(27,4,'Sisig Bowl','pcs',0.00,1,'2026-04-18 00:01:13'),(28,5,'Canton','pcs',0.00,1,'2026-04-18 00:01:13'),(29,5,'Fish Balls','pcs',0.00,1,'2026-04-18 00:01:13'),(30,5,'Fries','pcs',0.00,1,'2026-04-18 00:01:13'),(31,5,'Kikiam','pcs',0.00,1,'2026-04-18 00:01:13'),(32,5,'Siomai','pcs',0.00,1,'2026-04-18 00:01:13'),(33,5,'Siopao','pcs',0.00,1,'2026-04-18 00:01:13');
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `Role_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`Role_ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (3,'Auditor'),(1,'Owner'),(2,'Staff');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `Sales_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Outlet_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Sales_date` date NOT NULL,
  `Quantity_sold` int(11) NOT NULL DEFAULT 0,
  `Unit_Price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Sales_ID`),
  KEY `Outlet_ID` (`Outlet_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `Product_ID` (`Product_ID`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`Outlet_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,1,1,1,'2026-04-16',5,65.00,325.00,0,'2026-04-16 15:12:49'),(2,1,2,1,'2026-04-16',2,49.99,99.98,0,'2026-04-16 15:26:39'),(3,1,1,1,'2026-04-17',1,0.00,0.00,1,'2026-04-17 09:24:15'),(4,1,1,1,'2026-04-17',1,95.00,95.00,1,'2026-04-17 09:32:38'),(5,1,2,1,'2026-04-17',1,95.00,95.00,1,'2026-04-17 09:33:58');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_calc_line_total_insert
BEFORE INSERT ON Sales
FOR EACH ROW
BEGIN
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_calc_line_total_update
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
    SET NEW.Line_total = NEW.Quantity_sold * NEW.Unit_Price;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_prevent_locked_sales_edit
BEFORE UPDATE ON Sales
FOR EACH ROW
BEGIN
    IF OLD.Locked_status = 1 AND NEW.Locked_status = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a locked sales record.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `time_in`
--

DROP TABLE IF EXISTS `time_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_in` (
  `Timein_ID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID` int(11) NOT NULL,
  `Outlet_ID` int(11) NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Timein_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `Outlet_ID` (`Outlet_ID`),
  CONSTRAINT `time_in_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  CONSTRAINT `time_in_ibfk_2` FOREIGN KEY (`Outlet_ID`) REFERENCES `kiosk` (`Kiosk_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_in`
--

LOCK TABLES `time_in` WRITE;
/*!40000 ALTER TABLE `time_in` DISABLE KEYS */;
INSERT INTO `time_in` VALUES (1,2,1,'2026-04-16 15:23:46'),(2,2,1,'2026-04-17 09:33:53');
/*!40000 ALTER TABLE `time_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `User_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Role_ID` int(11) NOT NULL,
  `Outlet_ID` int(11) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Full_name` varchar(100) NOT NULL,
  `Active_status` tinyint(1) NOT NULL DEFAULT 1,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `Username` (`Username`),
  KEY `Role_ID` (`Role_ID`),
  KEY `Outlet_ID` (`Outlet_ID`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`Role_ID`) REFERENCES `role` (`Role_ID`),
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`Outlet_ID`) REFERENCES `kiosk` (`Kiosk_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,1,NULL,'owner','$2y$10$N5OKOH3tsBPvqtDraQ/EZ.kuG3X/rdgtR2SBkfSDtVGEvGFU5hxFS','Cherryll Laud',1,'2026-04-16 11:28:10'),(2,2,1,'james','$2y$10$6mmNv1O5tK1uTZpV8gljROU0wfYxWs0XQgsN7UWMEq1HeconquvSW','James',1,'2026-04-16 15:23:34');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'chicken_deluxe'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-18 19:07:03
