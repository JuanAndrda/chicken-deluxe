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
-- Current Database: `chicken_deluxe`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `chicken_deluxe` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `chicken_deluxe`;

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
  `Operation` varchar(10) DEFAULT NULL,
  `Table_name` varchar(100) DEFAULT NULL,
  `Old_values` text DEFAULT NULL,
  `New_values` text DEFAULT NULL,
  `Details` varchar(255) DEFAULT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Log_ID`),
  KEY `User_ID` (`User_ID`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 11:31:19'),(2,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 11:31:27'),(3,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 11:32:09'),(4,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 11:33:03'),(5,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 11:33:23'),(6,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 11:36:57'),(7,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 11:55:38'),(8,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 12:24:05'),(9,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 12:24:32'),(10,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 12:26:05'),(11,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 12:28:00'),(12,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 12:28:10'),(13,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 12:33:10'),(14,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 12:35:01'),(15,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 13:02:00'),(16,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 15:06:43'),(17,1,'CREATE',NULL,NULL,NULL,NULL,'Created product: Classic Burger (ID:1)','2026-04-16 15:07:09'),(18,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 15:08:03'),(19,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 15:12:36'),(20,1,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:1 — product:1, qty:5, price:65','2026-04-16 15:12:49'),(21,1,'CREATE',NULL,NULL,NULL,NULL,'Delivery ID:1 — product:1, qty:20','2026-04-16 15:13:01'),(22,1,'CREATE',NULL,NULL,NULL,NULL,'Expense ID:1 — amount:150, desc:LPG refill','2026-04-16 15:13:02'),(23,1,'CREATE',NULL,NULL,NULL,NULL,'Delivery ID:2 — product:1, qty:2','2026-04-16 15:15:46'),(24,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 2 deliveries for outlet:1 on 2026-04-16','2026-04-16 15:15:58'),(25,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:2','2026-04-16 15:16:11'),(26,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:1','2026-04-16 15:16:14'),(27,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 15:22:29'),(28,1,'CREATE',NULL,NULL,NULL,NULL,'Created user: james (ID:2)','2026-04-16 15:23:34'),(29,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 15:23:39'),(30,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 15:23:46'),(31,2,'CREATE',NULL,NULL,NULL,NULL,'Beginning stock recorded: 1 products for outlet 1 on 2026-04-16','2026-04-16 15:24:41'),(32,2,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:2 — product:1, qty:2, price:49.99','2026-04-16 15:26:39'),(33,2,'LOCK',NULL,NULL,NULL,NULL,'Locked 2 sales for outlet:1 on 2026-04-16','2026-04-16 15:26:45'),(34,2,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 15:28:38'),(35,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 15:28:58'),(36,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Sales ID:1','2026-04-16 15:55:04'),(37,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Sales ID:2','2026-04-16 15:55:10'),(38,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 22:30:27'),(39,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 22:30:38'),(40,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 2 deliveries for outlet:1 on 2026-04-16','2026-04-16 22:31:55'),(41,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:2','2026-04-16 22:31:58'),(42,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:1','2026-04-16 22:32:00'),(43,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 22:32:12'),(44,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 22:32:19'),(45,2,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 22:36:35'),(46,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 22:36:43'),(47,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 2 deliveries for outlet:1 on 2026-04-16','2026-04-16 22:43:12'),(48,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:2','2026-04-16 22:43:16'),(49,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:1','2026-04-16 22:43:18'),(50,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-16 22:43:22'),(51,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-16 22:45:01'),(52,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-17 09:20:43'),(53,1,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:3 — product:1, qty:1, price:0','2026-04-17 09:24:15'),(54,1,'CREATE',NULL,NULL,NULL,NULL,'Delivery ID:3 — product:1, qty:1','2026-04-17 09:24:50'),(55,1,'UPDATE',NULL,NULL,NULL,NULL,'Updated product ID:1','2026-04-17 09:32:14'),(56,1,'UPDATE',NULL,NULL,NULL,NULL,'Updated product ID:1','2026-04-17 09:32:22'),(57,1,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:4 — product:1, qty:1, price:95','2026-04-17 09:32:38'),(58,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-17 09:33:46'),(59,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-17 09:33:53'),(60,2,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:5 — product:1, qty:1, price:95','2026-04-17 09:33:58'),(61,2,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-17 09:34:04'),(62,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-17 09:34:16'),(63,1,'CREATE',NULL,NULL,NULL,NULL,'Beginning stock recorded: 1 products for outlet 1 on 2026-04-17','2026-04-17 09:35:20'),(64,1,'CREATE',NULL,NULL,NULL,NULL,'Ending stock recorded: 1 products for outlet 1 on 2026-04-17','2026-04-17 09:38:17'),(65,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-17 09:45:08'),(66,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-17 19:28:13'),(67,1,'CREATE',NULL,NULL,NULL,NULL,'Delivery ID:4 — product:1, qty:1','2026-04-17 19:32:40'),(68,1,'DELETE',NULL,NULL,NULL,NULL,'Deleted Delivery ID:4','2026-04-17 19:32:45'),(69,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-17 23:27:54'),(70,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-18 00:02:22'),(71,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 1 deliveries for outlet:1 on 2026-04-17','2026-04-18 00:28:12'),(72,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 3 sales for outlet:1 on 2026-04-17','2026-04-18 00:28:15'),(75,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-18 19:18:31'),(76,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-18 19:19:03'),(77,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-18 19:20:32'),(78,1,'CREATE',NULL,NULL,NULL,NULL,'Beginning stock recorded: 33 products for kiosk 1 on 2026-04-18','2026-04-18 19:21:26'),(79,1,'CREATE',NULL,NULL,NULL,NULL,'Delivery ID:5 — product:3, qty:2','2026-04-18 19:21:38'),(80,1,'DELETE',NULL,NULL,NULL,NULL,'Deleted Delivery ID:5','2026-04-18 19:21:43'),(81,1,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:6 — product:3, qty:3, price:0','2026-04-18 19:21:49'),(82,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 1 sales for kiosk:1 on 2026-04-18','2026-04-18 19:21:55'),(83,1,'CREATE',NULL,NULL,NULL,NULL,'Ending stock recorded: 33 products for kiosk 1 on 2026-04-18','2026-04-18 19:22:40'),(84,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-18 19:23:16'),(85,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-18 19:23:28'),(86,2,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-18 19:23:47'),(87,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-20 14:19:10'),(88,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-20 15:55:23'),(89,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-20 15:55:25'),(90,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-20 16:38:01'),(91,1,'CREATE',NULL,NULL,NULL,NULL,'Delivery ID:6 — product:14, qty:3','2026-04-20 16:38:45'),(92,1,'LOCK',NULL,NULL,NULL,NULL,'Locked 1 deliveries for kiosk:1 on 2026-04-20','2026-04-20 16:38:52'),(93,1,'UNLOCK',NULL,NULL,NULL,NULL,'Unlocked Delivery ID:6','2026-04-20 16:38:57'),(94,1,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-20 16:40:31'),(95,2,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-20 16:40:39'),(96,2,'CREATE',NULL,NULL,NULL,NULL,'Sales ID:7 — product:9, qty:1, price:0','2026-04-20 16:40:54'),(97,2,'LOGOUT',NULL,NULL,NULL,NULL,'User logged out','2026-04-20 16:42:03'),(98,1,'LOGIN',NULL,NULL,NULL,NULL,'User logged in','2026-04-20 16:42:22'),(99,NULL,'INSERT','INSERT','Product',NULL,'{\"Product_ID\": 34, \"Category_ID\": 5, \"Name\": \"TEST_TRIGGER_PRODUCT\", \"Unit\": \"pcs\", \"Price\": 50.00, \"Active\": 1}','New record inserted into Product ID:34','2026-04-20 22:12:19'),(100,NULL,'UPDATE','UPDATE','Product','{\"Product_ID\": 34, \"Category_ID\": 5, \"Name\": \"TEST_TRIGGER_PRODUCT\", \"Unit\": \"pcs\", \"Price\": 50.00, \"Active\": 1}','{\"Product_ID\": 34, \"Category_ID\": 5, \"Name\": \"TEST_TRIGGER_PRODUCT\", \"Unit\": \"pcs\", \"Price\": 75.00, \"Active\": 1}','Record updated in Product ID:34','2026-04-20 22:12:19'),(101,NULL,'DELETE','DELETE','Product','{\"Product_ID\": 34, \"Category_ID\": 5, \"Name\": \"TEST_TRIGGER_PRODUCT\", \"Unit\": \"pcs\", \"Price\": 75.00, \"Active\": 1}',NULL,'Record deleted from Product ID:34','2026-04-20 22:12:19');
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
  `Kiosk_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Delivery_Date` date NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Delivery_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `Product_ID` (`Product_ID`),
  KEY `delivery_ibfk_1` (`Kiosk_ID`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`Kiosk_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  CONSTRAINT `delivery_ibfk_3` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,1,1,'2026-04-16',20,0,'2026-04-16 15:13:01'),(2,1,1,1,'2026-04-16',2,0,'2026-04-16 15:15:46'),(3,1,1,1,'2026-04-17',1,1,'2026-04-17 09:24:50'),(6,1,1,14,'2026-04-20',3,0,'2026-04-20 16:38:45');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_delivery_insert
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_delivery_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_delivery_delete
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
  `Kiosk_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Expense_date` date NOT NULL,
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Description` varchar(255) NOT NULL,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Expense_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `expenses_ibfk_1` (`Kiosk_ID`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`Kiosk_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_expenses_insert
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_expenses_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_expenses_delete
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
  `Kiosk_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Snapshot_date` date NOT NULL,
  `Snapshot_type` enum('beginning','ending') NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `Recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Inventory_ID`),
  UNIQUE KEY `uq_snapshot` (`Kiosk_ID`,`Product_ID`,`Snapshot_date`,`Snapshot_type`),
  KEY `Product_ID` (`Product_ID`),
  KEY `User_ID` (`User_ID`),
  CONSTRAINT `inventory_snapshot_ibfk_1` FOREIGN KEY (`Kiosk_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `inventory_snapshot_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`),
  CONSTRAINT `inventory_snapshot_ibfk_3` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_snapshot`
--

LOCK TABLES `inventory_snapshot` WRITE;
/*!40000 ALTER TABLE `inventory_snapshot` DISABLE KEYS */;
INSERT INTO `inventory_snapshot` VALUES (1,1,1,2,0,'2026-04-16','beginning',10,'2026-04-16 15:24:41'),(6,1,1,1,1,'2026-04-17','beginning',10,'2026-04-17 09:35:19'),(8,1,1,1,1,'2026-04-17','ending',2,'2026-04-17 09:38:17'),(10,1,3,1,1,'2026-04-18','beginning',5,'2026-04-18 19:21:26'),(11,1,8,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(12,1,10,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(13,1,11,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(14,1,9,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(15,1,4,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(16,1,5,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(17,1,6,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(18,1,7,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(19,1,1,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(20,1,12,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(21,1,19,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(22,1,13,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(23,1,14,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(24,1,15,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(25,1,20,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(26,1,16,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(27,1,21,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(28,1,17,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(29,1,18,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(30,1,2,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(31,1,22,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(32,1,23,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(33,1,24,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(34,1,25,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(35,1,26,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(36,1,27,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(37,1,28,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(38,1,29,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(39,1,30,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(40,1,31,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(41,1,32,1,1,'2026-04-18','beginning',0,'2026-04-18 19:21:26'),(42,1,33,1,1,'2026-04-18','beginning',5,'2026-04-18 19:21:26'),(43,1,3,1,1,'2026-04-18','ending',2,'2026-04-18 19:22:40'),(44,1,8,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(45,1,10,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(46,1,11,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(47,1,9,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(48,1,4,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(49,1,5,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(50,1,6,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(51,1,7,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(52,1,1,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(53,1,12,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(54,1,19,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(55,1,13,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(56,1,14,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(57,1,15,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(58,1,20,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(59,1,16,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(60,1,21,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(61,1,17,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(62,1,18,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(63,1,2,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(64,1,22,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(65,1,23,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(66,1,24,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(67,1,25,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(68,1,26,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(69,1,27,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(70,1,28,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(71,1,29,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(72,1,30,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(73,1,31,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(74,1,32,1,1,'2026-04-18','ending',0,'2026-04-18 19:22:40'),(75,1,33,1,1,'2026-04-18','ending',5,'2026-04-18 19:22:40');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_inventory_snapshot_insert
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_inventory_snapshot_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_inventory_snapshot_delete
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_kiosk_insert
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_kiosk_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_kiosk_delete
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,1,'Classic Burger','pcs',95.00,1,'2026-04-16 15:07:09'),(2,3,'Classic Hotdog','pcs',45.00,1,'2026-04-17 19:31:11'),(3,1,'All Around Burger','pcs',0.00,1,'2026-04-18 00:01:13'),(4,1,'Burger with Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(5,1,'Burger with Egg & Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(6,1,'Burger with Ham & Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(7,1,'Burger with Ham & Egg','pcs',0.00,1,'2026-04-18 00:01:13'),(8,1,'Burger Patty','pcs',0.00,1,'2026-04-18 00:01:13'),(9,1,'Burger Patty with Egg & Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(10,1,'Burger Patty with Cheese','pcs',0.00,1,'2026-04-18 00:01:13'),(11,1,'Burger Patty with Egg','pcs',0.00,1,'2026-04-18 00:01:13'),(12,2,'Caramel Coffee','pcs',0.00,1,'2026-04-18 00:01:13'),(13,2,'Coke Swakto','pcs',0.00,1,'2026-04-18 00:01:13'),(14,2,'Iced Coffee','pcs',0.00,1,'2026-04-18 00:01:13'),(15,2,'Iced Matcha','pcs',0.00,1,'2026-04-18 00:01:13'),(16,2,'Royal Swakto','pcs',0.00,1,'2026-04-18 00:01:13'),(17,2,'Sprite Swakto','pcs',0.00,1,'2026-04-18 00:01:13'),(18,2,'Sting','pcs',0.00,1,'2026-04-18 00:01:13'),(19,2,'Coke 500ml','pcs',0.00,1,'2026-04-18 00:01:13'),(20,2,'Royal 500ml','pcs',0.00,1,'2026-04-18 00:01:13'),(21,2,'Sprite 500ml','pcs',0.00,1,'2026-04-18 00:01:13'),(22,3,'Hungarian Hotdog','pcs',0.00,1,'2026-04-18 00:01:13'),(23,4,'Cup of Rice','pcs',0.00,1,'2026-04-18 00:01:13'),(24,4,'Egg','pcs',0.00,1,'2026-04-18 00:01:13'),(25,4,'Lumpia Bowl','pcs',0.00,1,'2026-04-18 00:01:13'),(26,4,'Siomai Bowl','pcs',0.00,1,'2026-04-18 00:01:13'),(27,4,'Sisig Bowl','pcs',0.00,1,'2026-04-18 00:01:13'),(28,5,'Canton','pcs',0.00,1,'2026-04-18 00:01:13'),(29,5,'Fish Balls','pcs',0.00,1,'2026-04-18 00:01:13'),(30,5,'Fries','pcs',0.00,1,'2026-04-18 00:01:13'),(31,5,'Kikiam','pcs',0.00,1,'2026-04-18 00:01:13'),(32,5,'Siomai','pcs',0.00,1,'2026-04-18 00:01:13'),(33,5,'Siopao','pcs',0.00,1,'2026-04-18 00:01:13');
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_product_insert
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_product_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_product_delete
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `Kiosk_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Sales_date` date NOT NULL,
  `Quantity_sold` int(11) NOT NULL DEFAULT 0,
  `Unit_Price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Locked_status` tinyint(1) NOT NULL DEFAULT 0,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Sales_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `Product_ID` (`Product_ID`),
  KEY `sales_ibfk_1` (`Kiosk_ID`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`Kiosk_ID`) REFERENCES `kiosk` (`Kiosk_ID`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,1,1,1,'2026-04-16',5,65.00,325.00,0,'2026-04-16 15:12:49'),(2,1,2,1,'2026-04-16',2,49.99,99.98,0,'2026-04-16 15:26:39'),(3,1,1,1,'2026-04-17',1,0.00,0.00,1,'2026-04-17 09:24:15'),(4,1,1,1,'2026-04-17',1,95.00,95.00,1,'2026-04-17 09:32:38'),(5,1,2,1,'2026-04-17',1,95.00,95.00,1,'2026-04-17 09:33:58'),(6,1,1,3,'2026-04-18',3,0.00,0.00,1,'2026-04-18 19:21:49'),(7,1,2,9,'2026-04-20',1,0.00,0.00,0,'2026-04-20 16:40:54');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_sales_insert
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_sales_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_sales_delete
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
  `Kiosk_ID` int(11) NOT NULL,
  `Timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Timein_ID`),
  KEY `User_ID` (`User_ID`),
  KEY `time_in_ibfk_2` (`Kiosk_ID`),
  CONSTRAINT `time_in_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`),
  CONSTRAINT `time_in_ibfk_2` FOREIGN KEY (`Kiosk_ID`) REFERENCES `kiosk` (`Kiosk_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_in`
--

LOCK TABLES `time_in` WRITE;
/*!40000 ALTER TABLE `time_in` DISABLE KEYS */;
INSERT INTO `time_in` VALUES (1,2,1,'2026-04-16 15:23:46'),(2,2,1,'2026-04-17 09:33:53'),(3,2,1,'2026-04-18 19:19:03'),(4,2,1,'2026-04-20 16:40:39');
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
  `Kiosk_ID` int(11) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Full_name` varchar(100) NOT NULL,
  `Active_status` tinyint(1) NOT NULL DEFAULT 1,
  `Created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `Username` (`Username`),
  KEY `Role_ID` (`Role_ID`),
  KEY `user_ibfk_2` (`Kiosk_ID`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`Role_ID`) REFERENCES `role` (`Role_ID`),
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`Kiosk_ID`) REFERENCES `kiosk` (`Kiosk_ID`)
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_user_insert
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_user_update
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_log_user_delete
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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

-- Dump completed on 2026-04-20 22:15:22
