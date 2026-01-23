-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: findr
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `budgetcategory`
--

DROP TABLE IF EXISTS `budgetcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgetcategory` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `MonthlyLimit` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `uq_budgetcat_user_name` (`UserID`,`Name`),
  KEY `idx_budgetcat_user` (`UserID`),
  CONSTRAINT `fk_budgetcat_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgetcategory`
--

LOCK TABLES `budgetcategory` WRITE;
/*!40000 ALTER TABLE `budgetcategory` DISABLE KEYS */;
INSERT INTO `budgetcategory` VALUES (1,1,'Rent',15000.00),(2,1,'Food',8000.00),(11,5,'Salary',NULL),(12,5,'rent',NULL);
/*!40000 ALTER TABLE `budgetcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budgettransaction`
--

DROP TABLE IF EXISTS `budgettransaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgettransaction` (
  `TransactionID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Type` enum('Expense','Income') NOT NULL,
  `Note` varchar(255) DEFAULT NULL,
  `Date` date NOT NULL,
  PRIMARY KEY (`TransactionID`),
  KEY `idx_tx_user_date` (`UserID`,`Date`),
  KEY `idx_tx_category_date` (`CategoryID`,`Date`),
  CONSTRAINT `fk_tx_category` FOREIGN KEY (`CategoryID`) REFERENCES `budgetcategory` (`CategoryID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgettransaction`
--

LOCK TABLES `budgettransaction` WRITE;
/*!40000 ALTER TABLE `budgettransaction` DISABLE KEYS */;
INSERT INTO `budgettransaction` VALUES (1,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(2,1,2,300.00,'Expense','Lunch at canteen','2026-01-02'),(3,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(4,1,2,300.00,'Expense','Lunch at canteen','2026-01-02'),(5,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(6,1,2,300.00,'Expense','Lunch at canteen','2026-01-02'),(7,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(8,1,2,300.00,'Expense','Lunch at canteen','2026-01-02'),(9,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(10,1,2,300.00,'Expense','Lunch at canteen','2026-01-02'),(11,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(12,1,2,300.00,'Expense','Lunch at canteen','2026-01-02'),(13,5,11,12000.00,'Income','I got the money form my salary','2026-01-23'),(14,5,12,3250.00,'Expense','my monthly home rent','2026-01-23'),(15,1,1,15000.00,'Expense','Monthly room rent','2026-01-01'),(16,1,2,300.00,'Expense','Lunch at canteen','2026-01-02');
/*!40000 ALTER TABLE `budgettransaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `busroute`
--

DROP TABLE IF EXISTS `busroute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `busroute` (
  `RouteID` int(11) NOT NULL AUTO_INCREMENT,
  `RouteName` varchar(100) NOT NULL,
  `StartPoint` varchar(100) NOT NULL,
  `EndPoint` varchar(100) NOT NULL,
  `Fare` decimal(6,2) NOT NULL,
  `FirstBusTime` time NOT NULL,
  `LastBusTime` time NOT NULL,
  PRIMARY KEY (`RouteID`),
  KEY `idx_route_name` (`RouteName`),
  KEY `idx_route_points` (`StartPoint`,`EndPoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `busroute`
--

LOCK TABLES `busroute` WRITE;
/*!40000 ALTER TABLE `busroute` DISABLE KEYS */;
/*!40000 ALTER TABLE `busroute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groceryprice`
--

DROP TABLE IF EXISTS `groceryprice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groceryprice` (
  `GroceryID` int(11) NOT NULL AUTO_INCREMENT,
  `ItemName` varchar(100) NOT NULL,
  `Unit` varchar(20) NOT NULL,
  `Price` decimal(8,2) NOT NULL,
  `MarketName` varchar(100) NOT NULL,
  `Date` date NOT NULL,
  PRIMARY KEY (`GroceryID`),
  KEY `idx_grocery_item_date` (`ItemName`,`Date`),
  KEY `idx_grocery_market_date` (`MarketName`,`Date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groceryprice`
--

LOCK TABLES `groceryprice` WRITE;
/*!40000 ALTER TABLE `groceryprice` DISABLE KEYS */;
/*!40000 ALTER TABLE `groceryprice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lostfound`
--

DROP TABLE IF EXISTS `lostfound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lostfound` (
  `LFID` int(11) NOT NULL AUTO_INCREMENT,
  `ReporterID` int(11) NOT NULL,
  `PostType` enum('Lost','Found') NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Description` text NOT NULL,
  `Location` varchar(150) NOT NULL,
  `ContactInfo` varchar(100) NOT NULL,
  `Status` enum('Open','Resolved','Expired') NOT NULL DEFAULT 'Open',
  `Date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`LFID`),
  KEY `idx_lf_reporter` (`ReporterID`),
  KEY `idx_lf_status` (`Status`),
  CONSTRAINT `fk_lf_reporter` FOREIGN KEY (`ReporterID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lostfound`
--

LOCK TABLES `lostfound` WRITE;
/*!40000 ALTER TABLE `lostfound` DISABLE KEYS */;
INSERT INTO `lostfound` VALUES (1,5,'Lost','ddcdd','ddd','Bangladesh','+8801882359241','Open','2026-01-22 02:52:34'),(2,7,'Lost','kjj','ojj','mmm','mlmlmm','Open','2026-01-22 03:18:44'),(3,7,'Found','mm','mmn','mmm','mmm','Open','2026-01-22 03:19:07'),(4,6,'Lost','mmm','nmm','mk','mmm','Open','2026-01-22 03:20:09'),(5,7,'Found','ghj','bbbbbb','Bangladesh','+8801882359241','Open','2026-01-22 11:53:42');
/*!40000 ALTER TABLE `lostfound` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketitem`
--

DROP TABLE IF EXISTS `marketitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketitem` (
  `ItemID` int(11) NOT NULL AUTO_INCREMENT,
  `SellerID` int(11) NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Description` text NOT NULL,
  `Category` enum('Electronics','Furniture','Books','Clothing','Sports','Others') NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Condition` enum('New','LikeNew','Used','VeryUsed') NOT NULL,
  `Status` enum('Available','Reserved','Sold','Inactive') NOT NULL DEFAULT 'Available',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ItemID`),
  KEY `idx_market_seller` (`SellerID`),
  KEY `idx_market_cat_status` (`Category`,`Status`),
  CONSTRAINT `fk_market_seller` FOREIGN KEY (`SellerID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketitem`
--

LOCK TABLES `marketitem` WRITE;
/*!40000 ALTER TABLE `marketitem` DISABLE KEYS */;
INSERT INTO `marketitem` VALUES (1,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-22 02:44:44'),(2,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-22 03:45:57'),(3,12,'iPhone X Used','Black color, 64GB, 80% battery health.','Electronics',25000.00,'Used','Available','2026-01-21 03:45:57'),(4,12,'Study Table Wooden','Solid wood table, perfect condition.','Furniture',3000.00,'','Available','2026-01-20 03:45:57'),(5,21,'Physics H.C. Verma Vol 1','Essential for physics students.','Books',400.00,'Used','Available','2026-01-21 22:45:57'),(6,19,'Gaming Mouse Logitech','G502 Hero, barely used.','Electronics',3500.00,'','Available','2026-01-19 03:45:57'),(7,12,'Plastic Chairs (Set of 2)','Red color, RFL plastic.','Furniture',1200.00,'Used','Available','2026-01-12 03:45:57'),(8,17,'Bicycle - Veloce','Needs some brake work but rides fine.','Sports',8000.00,'Used','Available','2026-01-21 03:45:57'),(9,19,'Monitor 24 inch HP','IPS Display, no dead pixels.','Electronics',10500.00,'Used','Available','2026-01-18 03:45:57'),(10,21,'Introduction to Algorithms','CLRS 3rd Edition. Hardcover.','Books',1500.00,'','Available','2026-01-21 15:45:57'),(11,17,'Curtains (Blue)','Set of 4 window curtains.','Furniture',800.00,'New','Available','2026-01-16 03:45:57'),(12,12,'Sony Headphones','Wireless noise cancelling.','Electronics',4500.00,'Used','Available','2026-01-15 03:45:57'),(13,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-22 03:47:09'),(14,12,'iPhone X Used','Black color, 64GB, 80% battery health.','Electronics',25000.00,'Used','Available','2026-01-21 03:47:09'),(15,12,'Study Table Wooden','Solid wood table, perfect condition.','Furniture',3000.00,'','Available','2026-01-20 03:47:09'),(16,21,'Physics H.C. Verma Vol 1','Essential for physics students.','Books',400.00,'Used','Available','2026-01-21 22:47:09'),(17,19,'Gaming Mouse Logitech','G502 Hero, barely used.','Electronics',3500.00,'','Available','2026-01-19 03:47:09'),(18,12,'Plastic Chairs (Set of 2)','Red color, RFL plastic.','Furniture',1200.00,'Used','Available','2026-01-12 03:47:09'),(19,17,'Bicycle - Veloce','Needs some brake work but rides fine.','Sports',8000.00,'Used','Available','2026-01-21 03:47:09'),(20,19,'Monitor 24 inch HP','IPS Display, no dead pixels.','Electronics',10500.00,'Used','Available','2026-01-18 03:47:09'),(21,21,'Introduction to Algorithms','CLRS 3rd Edition. Hardcover.','Books',1500.00,'','Available','2026-01-21 15:47:09'),(22,17,'Curtains (Blue)','Set of 4 window curtains.','Furniture',800.00,'New','Available','2026-01-16 03:47:09'),(23,12,'Sony Headphones','Wireless noise cancelling.','Electronics',4500.00,'Used','Available','2026-01-15 03:47:09'),(24,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-22 03:49:40'),(25,12,'iPhone X Used','Black color, 64GB, 80% battery health.','Electronics',25000.00,'Used','Available','2026-01-21 03:49:40'),(26,12,'Study Table Wooden','Solid wood table, perfect condition.','Furniture',3000.00,'','Available','2026-01-20 03:49:40'),(27,21,'Physics H.C. Verma Vol 1','Essential for physics students.','Books',400.00,'Used','Available','2026-01-21 22:49:40'),(28,19,'Gaming Mouse Logitech','G502 Hero, barely used.','Electronics',3500.00,'','Available','2026-01-19 03:49:40'),(29,12,'Plastic Chairs (Set of 2)','Red color, RFL plastic.','Furniture',1200.00,'Used','Available','2026-01-12 03:49:40'),(30,17,'Bicycle - Veloce','Needs some brake work but rides fine.','Sports',8000.00,'Used','Available','2026-01-21 03:49:40'),(31,19,'Monitor 24 inch HP','IPS Display, no dead pixels.','Electronics',10500.00,'Used','Available','2026-01-18 03:49:40'),(32,21,'Introduction to Algorithms','CLRS 3rd Edition. Hardcover.','Books',1500.00,'','Available','2026-01-21 15:49:40'),(33,17,'Curtains (Blue)','Set of 4 window curtains.','Furniture',800.00,'New','Available','2026-01-16 03:49:40'),(34,12,'Sony Headphones','Wireless noise cancelling.','Electronics',4500.00,'Used','Available','2026-01-15 03:49:40'),(35,7,'ccc','dfef','Others',1555.00,'LikeNew','Available','2026-01-22 03:51:19'),(36,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-22 04:04:08'),(37,12,'iPhone X Used','Black color, 64GB, 80% battery health.','Electronics',25000.00,'Used','Available','2026-01-21 04:04:08'),(38,12,'Study Table Wooden','Solid wood table, perfect condition.','Furniture',3000.00,'','Available','2026-01-20 04:04:08'),(39,21,'Physics H.C. Verma Vol 1','Essential for physics students.','Books',400.00,'Used','Available','2026-01-21 23:04:08'),(40,19,'Gaming Mouse Logitech','G502 Hero, barely used.','Electronics',3500.00,'','Available','2026-01-19 04:04:08'),(41,12,'Plastic Chairs (Set of 2)','Red color, RFL plastic.','Furniture',1200.00,'Used','Available','2026-01-12 04:04:08'),(42,17,'Bicycle - Veloce','Needs some brake work but rides fine.','Sports',8000.00,'Used','Available','2026-01-21 04:04:08'),(43,19,'Monitor 24 inch HP','IPS Display, no dead pixels.','Electronics',10500.00,'Used','Available','2026-01-18 04:04:08'),(44,21,'Introduction to Algorithms','CLRS 3rd Edition. Hardcover.','Books',1500.00,'','Available','2026-01-21 16:04:08'),(45,17,'Curtains (Blue)','Set of 4 window curtains.','Furniture',800.00,'New','Available','2026-01-16 04:04:08'),(46,12,'Sony Headphones','Wireless noise cancelling.','Electronics',4500.00,'Used','Available','2026-01-15 04:04:08'),(47,7,'bnhj','bh','Electronics',2255.00,'New','Available','2026-01-22 11:54:45'),(48,55,'bn','vb','Electronics',444.00,'New','Available','2026-01-22 11:59:26'),(49,7,'kglg','jgjgj','Others',3366.00,'New','','2026-01-22 12:06:34'),(50,5,'table','new','Furniture',1200.00,'Used','','2026-01-22 12:12:48'),(51,5,'lamp','new','Electronics',1000.00,'LikeNew','','2026-01-22 12:15:45'),(52,5,'ottt','otttt','Electronics',123456.00,'New','','2026-01-22 12:21:31'),(53,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-22 12:25:32'),(54,12,'iPhone X Used','Black color, 64GB, 80% battery health.','Electronics',25000.00,'Used','Available','2026-01-21 12:25:32'),(55,12,'Study Table Wooden','Solid wood table, perfect condition.','Furniture',3000.00,'','Available','2026-01-20 12:25:32'),(56,21,'Physics H.C. Verma Vol 1','Essential for physics students.','Books',400.00,'Used','Available','2026-01-22 07:25:32'),(57,19,'Gaming Mouse Logitech','G502 Hero, barely used.','Electronics',3500.00,'','Available','2026-01-19 12:25:32'),(58,12,'Plastic Chairs (Set of 2)','Red color, RFL plastic.','Furniture',1200.00,'Used','Available','2026-01-12 12:25:32'),(59,17,'Bicycle - Veloce','Needs some brake work but rides fine.','Sports',8000.00,'Used','Available','2026-01-21 12:25:32'),(60,19,'Monitor 24 inch HP','IPS Display, no dead pixels.','Electronics',10500.00,'Used','Available','2026-01-18 12:25:32'),(61,21,'Introduction to Algorithms','CLRS 3rd Edition. Hardcover.','Books',1500.00,'','Available','2026-01-22 00:25:32'),(62,17,'Curtains (Blue)','Set of 4 window curtains.','Furniture',800.00,'New','Available','2026-01-16 12:25:32'),(63,12,'Sony Headphones','Wireless noise cancelling.','Electronics',4500.00,'Used','Available','2026-01-15 12:25:32'),(64,6,'jkl','mkl','Electronics',1234.00,'LikeNew','','2026-01-22 12:26:04'),(65,5,'2356','klj','Electronics',1234.00,'Used','','2026-01-22 12:26:37'),(66,5,'pen','pen','Books',5.00,'New','Available','2026-01-22 12:40:38'),(67,5,'pen','pen','Books',5.00,'New','','2026-01-22 12:41:22'),(68,5,'klk','kl','Electronics',23.00,'New','Available','2026-01-22 12:47:13'),(69,70,'g','gg','Electronics',1.00,'New','','2026-01-22 12:58:20'),(70,1,'Used Study Table','Wooden table, good condition.','Furniture',2500.00,'Used','Available','2026-01-23 17:23:35'),(71,12,'iPhone X Used','Black color, 64GB, 80% battery health.','Electronics',25000.00,'Used','Available','2026-01-22 17:23:35'),(72,12,'Study Table Wooden','Solid wood table, perfect condition.','Furniture',3000.00,'','Available','2026-01-21 17:23:35'),(73,21,'Physics H.C. Verma Vol 1','Essential for physics students.','Books',400.00,'Used','Available','2026-01-23 12:23:35'),(74,19,'Gaming Mouse Logitech','G502 Hero, barely used.','Electronics',3500.00,'','Available','2026-01-20 17:23:35'),(75,12,'Plastic Chairs (Set of 2)','Red color, RFL plastic.','Furniture',1200.00,'Used','Available','2026-01-13 17:23:35'),(76,17,'Bicycle - Veloce','Needs some brake work but rides fine.','Sports',8000.00,'Used','Available','2026-01-22 17:23:35'),(77,19,'Monitor 24 inch HP','IPS Display, no dead pixels.','Electronics',10500.00,'Used','Available','2026-01-19 17:23:35'),(78,21,'Introduction to Algorithms','CLRS 3rd Edition. Hardcover.','Books',1500.00,'','Available','2026-01-23 05:23:35'),(79,17,'Curtains (Blue)','Set of 4 window curtains.','Furniture',800.00,'New','Available','2026-01-17 17:23:35'),(80,12,'Sony Headphones','Wireless noise cancelling.','Electronics',4500.00,'Used','Available','2026-01-16 17:23:35');
/*!40000 ALTER TABLE `marketitem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mealplan`
--

DROP TABLE IF EXISTS `mealplan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mealplan` (
  `MealPlanID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `MonthlyPrice` decimal(10,2) NOT NULL,
  `Details` text DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`MealPlanID`),
  KEY `idx_mealplan_provider` (`ProviderID`),
  KEY `idx_mealplan_active` (`IsActive`),
  CONSTRAINT `fk_mealplan_provider` FOREIGN KEY (`ProviderID`) REFERENCES `serviceprovider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mealplan`
--

LOCK TABLES `mealplan` WRITE;
/*!40000 ALTER TABLE `mealplan` DISABLE KEYS */;
INSERT INTO `mealplan` VALUES (1,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1),(2,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1),(3,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1),(4,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1),(5,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1),(6,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1),(7,3,'30-Day Full Board',5500.00,'Breakfast, lunch, dinner, 7 days a week.',1);
/*!40000 ALTER TABLE `mealplan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `MessageID` int(11) NOT NULL AUTO_INCREMENT,
  `SenderID` int(11) NOT NULL,
  `ReceiverID` int(11) NOT NULL,
  `Subject` varchar(200) DEFAULT NULL COMMENT 'Optional subject line',
  `MessageText` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`MessageID`),
  KEY `idx_message_sender` (`SenderID`,`CreatedAt`),
  KEY `idx_message_receiver` (`ReceiverID`,`IsRead`,`CreatedAt`),
  KEY `idx_message_conversation` (`SenderID`,`ReceiverID`,`CreatedAt`),
  CONSTRAINT `fk_message_receiver` FOREIGN KEY (`ReceiverID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_message_sender` FOREIGN KEY (`SenderID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message`
--

LOCK TABLES `message` WRITE;
/*!40000 ALTER TABLE `message` DISABLE KEYS */;
INSERT INTO `message` VALUES (1,5,5,NULL,'hey',1,'2026-01-22 13:00:15'),(2,71,5,NULL,'hey',1,'2026-01-22 13:07:02'),(3,5,5,NULL,'i am done',1,'2026-01-22 13:07:52'),(4,71,5,NULL,'are you oky',1,'2026-01-22 13:09:55'),(5,71,1,NULL,'hey',0,'2026-01-22 13:15:48'),(6,71,5,NULL,'swern',1,'2026-01-22 13:16:13'),(7,70,5,NULL,'heey how are you\r\n\r\nI want to tell you you are doing good',1,'2026-01-23 16:52:03'),(8,5,70,NULL,'yes thank you for your appritiations',1,'2026-01-23 16:52:52'),(9,6,5,NULL,'বাংলা',1,'2026-01-23 16:53:43'),(10,6,5,NULL,'ভাই দাম বেশী কিনমু না, দাম কমান',1,'2026-01-23 16:54:04'),(11,70,5,NULL,'you are wellcome',1,'2026-01-23 16:54:42'),(12,5,71,NULL,'hey I want to book',0,'2026-01-23 17:25:33'),(13,5,55,NULL,'yoo bro',1,'2026-01-23 17:42:57'),(14,55,5,NULL,'ji bro',1,'2026-01-23 17:43:14');
/*!40000 ALTER TABLE `message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `moodentry`
--

DROP TABLE IF EXISTS `moodentry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moodentry` (
  `MoodID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `MoodLevel` tinyint(4) NOT NULL,
  `Note` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `MoodLabel` varchar(50) DEFAULT NULL,
  `EnergyLevel` tinyint(4) DEFAULT NULL,
  `StressLevel` tinyint(4) DEFAULT NULL,
  `Activities` text DEFAULT NULL,
  `MedicationTaken` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`MoodID`),
  KEY `idx_mood_user_date` (`UserID`,`CreatedAt`),
  CONSTRAINT `fk_mood_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_mood_level` CHECK (`MoodLevel` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moodentry`
--

LOCK TABLES `moodentry` WRITE;
/*!40000 ALTER TABLE `moodentry` DISABLE KEYS */;
INSERT INTO `moodentry` VALUES (1,1,4,'Feeling positive about studies today.','2026-01-22 02:44:44','Okay',NULL,NULL,NULL,0),(2,7,2,'jk','2026-01-22 03:34:38','Okay',NULL,NULL,NULL,0),(3,1,4,'Feeling positive about studies today.','2026-01-22 03:45:57','Okay',NULL,NULL,NULL,0),(4,1,4,'Feeling positive about studies today.','2026-01-22 03:47:09','Okay',NULL,NULL,NULL,0),(5,1,4,'Feeling positive about studies today.','2026-01-22 03:49:40','Okay',NULL,NULL,NULL,0),(6,1,4,'Feeling positive about studies today.','2026-01-22 04:04:08','Okay',NULL,NULL,NULL,0),(7,7,5,'','2026-01-22 04:08:23','Okay',3,10,NULL,0),(8,7,3,'','2026-01-22 04:19:08','Poor',2,5,NULL,0),(9,1,4,'Feeling positive about studies today.','2026-01-22 12:25:32',NULL,NULL,NULL,NULL,0),(10,1,4,'Feeling positive about studies today.','2026-01-23 17:23:35',NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `moodentry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Type` enum('General','Message','Order','System') NOT NULL DEFAULT 'General',
  `Title` varchar(100) NOT NULL,
  `Message` text NOT NULL,
  `Link` varchar(255) DEFAULT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `idx_notif_user` (`UserID`,`IsRead`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
INSERT INTO `notification` VALUES (1,1,'General','Test','This is a test notification',NULL,0,'2026-01-22 04:24:34'),(2,1,'General','Test','This is a test notification',NULL,0,'2026-01-22 04:24:34'),(3,4,'General','New Marketplace Item','A new item \'kglg\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:06:34'),(4,4,'General','New Marketplace Item','A new item \'table\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:12:48'),(5,4,'General','New Marketplace Item','A new item \'lamp\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:15:45'),(6,4,'General','New Marketplace Item','A new item \'ottt\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:21:31'),(7,4,'General','New Marketplace Item','A new item \'jkl\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:26:04'),(8,4,'General','New Marketplace Item','A new item \'2356\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:26:37'),(9,4,'General','New Marketplace Item','A new item \'pen\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:40:38'),(10,70,'General','New Marketplace Item','A new item \'pen\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:40:38'),(11,4,'General','New Marketplace Item','A new item \'pen\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:41:22'),(12,70,'General','New Marketplace Item','A new item \'pen\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:41:22'),(13,14,'General','New Booking Request','You have a new booking request for Tuition on 2026-11-12 at 9.00-10.00.',NULL,0,'2026-01-22 12:45:51'),(14,4,'General','New Marketplace Item','A new item \'klk\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:47:13'),(15,70,'General','New Marketplace Item','A new item \'klk\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:47:13'),(16,4,'General','New Marketplace Item','A new item \'g\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:58:20'),(17,70,'General','New Marketplace Item','A new item \'g\' has been posted and is awaiting approval.',NULL,0,'2026-01-22 12:58:20'),(18,5,'General','New Message','You have a new message from MD. MAHAMUDUL HASAN',NULL,1,'2026-01-22 13:00:15'),(19,71,'General','New Booking Request','You have a new booking request for Cleaner on 2026-12-12 at 9.00-10.00.',NULL,1,'2026-01-22 13:05:15'),(20,5,'General','Booking Confirmed','Your booking for \'Cleaner\' has been Confirmed.',NULL,1,'2026-01-22 13:06:17'),(21,5,'General','New Message','You have a new message from abc',NULL,1,'2026-01-22 13:07:03'),(22,5,'General','New Message','You have a new message from MD. MAHAMUDUL HASAN',NULL,1,'2026-01-22 13:07:52'),(23,2,'General','New Room Application','You have a new application for your room \'2BHK near Dhanmondi 27\'.',NULL,0,'2026-01-22 13:08:24'),(24,5,'General','New Message','You have a new message from abc',NULL,1,'2026-01-22 13:09:55'),(25,71,'General','New Booking Request','You have a new booking request for Van on 2026-12-12 at 9.00-10.00.',NULL,0,'2026-01-22 13:13:42'),(26,5,'General','Booking Confirmed','Your booking for \'Van\' has been Confirmed.',NULL,1,'2026-01-22 13:14:29'),(27,1,'General','New Message','You have a new message from abc',NULL,0,'2026-01-22 13:15:48'),(28,5,'General','New Message','You have a new message from abc',NULL,1,'2026-01-22 13:16:13'),(29,6,'General','New Room Application','You have a new application for your room \'In vatara I need a room\'.',NULL,1,'2026-01-23 16:31:15'),(30,5,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=70',1,'2026-01-23 16:52:03'),(31,70,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=5',1,'2026-01-23 16:52:52'),(32,5,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=6',1,'2026-01-23 16:53:43'),(33,5,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=6',1,'2026-01-23 16:54:05'),(34,5,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=70',1,'2026-01-23 16:54:42'),(35,71,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=5',0,'2026-01-23 17:25:33'),(36,3,'General','Service Approved','Your service \'Cleaner\' is now live.',NULL,0,'2026-01-23 17:41:54'),(37,14,'General','Service Approved','Your service \'Tuition\' is now live.',NULL,0,'2026-01-23 17:41:56'),(38,15,'General','Service Approved','Your service \'Cleaner\' is now live.',NULL,0,'2026-01-23 17:41:57'),(39,16,'General','Service Approved','Your service \'Van\' is now live.',NULL,0,'2026-01-23 17:41:58'),(40,20,'General','Service Approved','Your service \'Mess\' is now live.',NULL,0,'2026-01-23 17:41:59'),(41,55,'General','Service Approved','Your service \'Cleaner\' is now live.',NULL,1,'2026-01-23 17:42:00'),(42,71,'General','Service Approved','Your service \'Van\' is now live.',NULL,0,'2026-01-23 17:42:00'),(43,55,'General','Service Approved','Your service \'Mess\' is now live.',NULL,1,'2026-01-23 17:42:01'),(44,55,'General','Service Approved','Your service \'Van\' is now live.',NULL,1,'2026-01-23 17:42:01'),(45,55,'General','Service Approved','Your service \'Cleaner\' is now live.',NULL,1,'2026-01-23 17:42:02'),(46,55,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=5',1,'2026-01-23 17:42:57'),(47,5,'Message','New Message','You have a new message from MD. MAHAMUDUL HASAN','chat.php?user_id=55',1,'2026-01-23 17:43:14'),(48,55,'Order','New Booking Request','New booking: Cleaner on 2026-01-23 at 9.00-10.00.','provider_bookings.php',1,'2026-01-23 18:06:01'),(49,5,'General','Booking Confirmed','Your booking for \'Cleaner\' has been Confirmed.',NULL,1,'2026-01-23 18:06:28'),(50,5,'General','Booking Completed','Your booking for \'Cleaner\' has been Completed.',NULL,1,'2026-01-23 18:06:32'),(51,55,'Order','New Booking Request','New booking: Van on 2026-01-30 at 9.00-10.00.','provider_bookings.php',1,'2026-01-23 18:07:09'),(52,5,'General','Booking Confirmed','Your booking for \'Van\' has been Confirmed.',NULL,1,'2026-01-23 18:07:24'),(53,5,'General','Booking Completed','Your booking for \'Van\' has been Completed.',NULL,1,'2026-01-23 18:07:27'),(54,55,'Order','New Booking Request','New booking: Van on 2026-01-24 at 9.00-10.00.','provider_bookings.php',1,'2026-01-23 18:08:29'),(55,5,'General','Booking Confirmed','Your booking for \'Van\' has been Confirmed.',NULL,1,'2026-01-23 18:08:49'),(56,5,'General','Booking Completed','Your booking for \'Van\' has been Completed.',NULL,1,'2026-01-23 18:08:52'),(57,16,'Order','New Booking Request','New booking: Van on 2026-01-25 at 10:00 AM.','provider_bookings.php',0,'2026-01-23 18:16:56'),(58,86,'Order','New Booking Request','New booking: Van on 2026-01-25 at 10:00 AM.','provider_bookings.php',0,'2026-01-23 18:27:29'),(59,87,'General','Booking Confirmed','Your booking for \'Van\' has been Confirmed.',NULL,0,'2026-01-23 18:28:26'),(60,87,'General','Booking Completed','Your booking for \'Van\' has been Completed.','rate_service.php?booking_id=17',1,'2026-01-23 18:28:40'),(61,55,'Order','New Booking Request','New booking: Mess on 2026-01-23 at 9.00-10.00.','provider_bookings.php',1,'2026-01-23 18:35:40'),(62,5,'General','Booking Confirmed','Your booking for \'\' has been Confirmed.',NULL,0,'2026-01-23 18:36:00'),(63,5,'General','Booking Completed','Your booking for \'\' has been Completed.','rate_service.php?booking_id=18',1,'2026-01-23 18:36:01'),(64,55,'System','New Review Received','You received a 5-star review for ',NULL,1,'2026-01-23 18:36:35'),(65,5,'General','Application Accepted','Your application for \'In vatara I need a room\' has been Accepted.',NULL,0,'2026-01-23 18:39:58');
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_services`
--

DROP TABLE IF EXISTS `provider_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provider_services` (
  `ServiceID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) NOT NULL,
  `ServiceType` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `IsApproved` tinyint(1) DEFAULT 0,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ServiceID`),
  KEY `idx_ps_provider` (`ProviderID`),
  KEY `idx_ps_type` (`ServiceType`),
  CONSTRAINT `fk_ps_provider` FOREIGN KEY (`ProviderID`) REFERENCES `serviceprovider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_services`
--

LOCK TABLES `provider_services` WRITE;
/*!40000 ALTER TABLE `provider_services` DISABLE KEYS */;
INSERT INTO `provider_services` VALUES (1,3,'Cleaner',NULL,NULL,1,1,'2026-01-23 17:07:08'),(2,14,'Tuition',NULL,NULL,1,1,'2026-01-23 17:07:08'),(3,15,'Cleaner',NULL,NULL,1,1,'2026-01-23 17:07:08'),(4,16,'Van',NULL,NULL,1,1,'2026-01-23 17:07:08'),(5,20,'Mess',NULL,NULL,1,1,'2026-01-23 17:07:08'),(6,55,'Cleaner',NULL,NULL,1,1,'2026-01-23 17:07:08'),(7,71,'Van',NULL,NULL,1,1,'2026-01-23 17:07:08'),(8,55,'Mess','you can order meals here.',2500.00,1,1,'2026-01-23 17:09:27'),(9,55,'Van','you book here a van',200.00,1,1,'2026-01-23 17:24:55'),(10,55,'Cleaner','get first',500.00,1,1,'2026-01-23 17:41:05'),(11,86,'Van','Testing van service',2000.00,1,0,'2026-01-23 18:17:49');
/*!40000 ALTER TABLE `provider_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `ReviewID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` int(11) NOT NULL,
  `ProviderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` between 1 and 5),
  `Comment` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ReviewID`),
  UNIQUE KEY `BookingID` (`BookingID`),
  KEY `fk_review_user` (`UserID`),
  KEY `idx_review_provider` (`ProviderID`),
  CONSTRAINT `fk_review_booking` FOREIGN KEY (`BookingID`) REFERENCES `servicebooking` (`BookingID`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_provider` FOREIGN KEY (`ProviderID`) REFERENCES `serviceprovider` (`ProviderID`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,18,55,5,5,'the service was good.','2026-01-23 18:36:35');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_after_review_insert
AFTER INSERT ON REVIEWS
FOR EACH ROW
BEGIN
    UPDATE SERVICEPROVIDER sp
    SET 
        TotalReviews = (SELECT COUNT(*) FROM REVIEWS WHERE ProviderID = NEW.ProviderID),
        AverageRating = (SELECT IFNULL(AVG(Rating), 0) FROM REVIEWS WHERE ProviderID = NEW.ProviderID)
    WHERE ProviderID = NEW.ProviderID;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER update_provider_rating_after_review
AFTER INSERT ON REVIEWS
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE total_reviews INT;
    
    SELECT AVG(Rating), COUNT(*) INTO avg_rating, total_reviews
    FROM REVIEWS
    WHERE ProviderID = NEW.ProviderID;
    
    UPDATE SERVICEPROVIDER
    SET AverageRating = avg_rating,
        TotalReviews = total_reviews
    WHERE ProviderID = NEW.ProviderID;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `roomapplication`
--

DROP TABLE IF EXISTS `roomapplication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roomapplication` (
  `ApplicationID` int(11) NOT NULL AUTO_INCREMENT,
  `RoomID` int(11) NOT NULL,
  `ApplicantID` int(11) NOT NULL,
  `Message` text DEFAULT NULL,
  `Status` enum('Pending','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  `AppliedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ApplicationID`),
  UNIQUE KEY `uq_room_applicant` (`RoomID`,`ApplicantID`),
  KEY `idx_app_room` (`RoomID`),
  KEY `idx_app_applicant` (`ApplicantID`),
  CONSTRAINT `fk_app_room` FOREIGN KEY (`RoomID`) REFERENCES `roomlisting` (`RoomID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_user` FOREIGN KEY (`ApplicantID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roomapplication`
--

LOCK TABLES `roomapplication` WRITE;
/*!40000 ALTER TABLE `roomapplication` DISABLE KEYS */;
INSERT INTO `roomapplication` VALUES (1,1,1,'I am a student at DU, looking for room from next month.','Pending','2026-01-22 02:44:44'),(2,1,5,'njk','Pending','2026-01-22 03:10:43'),(3,2,7,'jkk','Accepted','2026-01-22 03:17:36'),(8,21,7,'kloi','Pending','2026-01-22 04:08:51'),(9,9,7,'klop','Pending','2026-01-22 04:19:23'),(10,26,7,'need','Pending','2026-01-22 04:19:34'),(12,5,7,'xcvbnm','Pending','2026-01-22 11:54:18'),(14,27,5,'jkl','Pending','2026-01-22 13:08:24'),(15,33,5,'I need this room for my sister. so can you book this room for me.','Accepted','2026-01-23 16:31:15');
/*!40000 ALTER TABLE `roomapplication` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_room_app_not_owner
BEFORE INSERT ON ROOMAPPLICATION
FOR EACH ROW
BEGIN
  DECLARE vOwner INT;
  SELECT OwnerID INTO vOwner FROM ROOMLISTING WHERE RoomID = NEW.RoomID;
  IF vOwner = NEW.ApplicantID THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Owner cannot apply to own room';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `roomlisting`
--

DROP TABLE IF EXISTS `roomlisting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roomlisting` (
  `RoomID` int(11) NOT NULL AUTO_INCREMENT,
  `OwnerID` int(11) NOT NULL,
  `ListingType` enum('Room','HostelBed','RoommateWanted') NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Description` text NOT NULL,
  `LocationArea` varchar(100) NOT NULL,
  `Latitude` decimal(10,8) DEFAULT NULL,
  `Longitude` decimal(11,8) DEFAULT NULL,
  `RentAmount` decimal(10,2) NOT NULL,
  `UtilitiesIncluded` tinyint(1) NOT NULL DEFAULT 0,
  `GenderPreference` enum('Any','Male','Female') NOT NULL DEFAULT 'Any',
  `IsVerified` tinyint(1) NOT NULL DEFAULT 0,
  `Status` enum('Available','Booked','Closed') NOT NULL DEFAULT 'Available',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`RoomID`),
  KEY `idx_room_owner` (`OwnerID`),
  KEY `idx_room_area` (`LocationArea`),
  KEY `idx_room_type_status` (`ListingType`,`Status`),
  CONSTRAINT `fk_room_owner` FOREIGN KEY (`OwnerID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roomlisting`
--

LOCK TABLES `roomlisting` WRITE;
/*!40000 ALTER TABLE `roomlisting` DISABLE KEYS */;
INSERT INTO `roomlisting` VALUES (1,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-22 02:44:44'),(2,6,'HostelBed','jk','mmk','k',NULL,NULL,588.00,1,'Male',1,'Available','2026-01-22 03:16:34'),(3,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-22 03:45:57'),(4,13,'Room','Single Room in Bashundhara','Attached bath, south facing, 5th floor. Lift available.','Bashundhara R/A',NULL,NULL,8500.00,0,'Male',1,'Available','2026-01-20 03:45:57'),(5,18,'Room','Shared Room for Female','Near NSU, quiet environment. 2 person room.','Bashundhara R/A',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-21 03:45:57'),(6,13,'HostelBed','Seat in Master Bedroom','Nice view, balcony attached. Meal system active.','Baridhara',NULL,NULL,5000.00,1,'Male',1,'Available','2026-01-17 03:45:57'),(7,18,'Room','Family Flat for Rent','2 Bedrooms, 2 Bath. Gas line available.','Uttara Sector 7',NULL,NULL,18000.00,0,'Any',1,'Available','2026-01-15 03:45:57'),(8,12,'RoommateWanted','Looking for Roommate','I have a 3 room flat, need 1 person for small room.','Mirpur DOHS',NULL,NULL,6000.00,1,'Male',1,'Available','2026-01-22 00:45:57'),(9,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-22 03:47:09'),(10,13,'Room','Single Room in Bashundhara','Attached bath, south facing, 5th floor. Lift available.','Bashundhara R/A',NULL,NULL,8500.00,0,'Male',1,'Available','2026-01-20 03:47:09'),(11,18,'Room','Shared Room for Female','Near NSU, quiet environment. 2 person room.','Bashundhara R/A',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-21 03:47:09'),(12,13,'HostelBed','Seat in Master Bedroom','Nice view, balcony attached. Meal system active.','Baridhara',NULL,NULL,5000.00,1,'Male',1,'Available','2026-01-17 03:47:09'),(13,18,'Room','Family Flat for Rent','2 Bedrooms, 2 Bath. Gas line available.','Uttara Sector 7',NULL,NULL,18000.00,0,'Any',1,'Available','2026-01-15 03:47:09'),(14,12,'RoommateWanted','Looking for Roommate','I have a 3 room flat, need 1 person for small room.','Mirpur DOHS',NULL,NULL,6000.00,1,'Male',1,'Available','2026-01-22 00:47:09'),(15,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-22 03:49:40'),(16,13,'Room','Single Room in Bashundhara','Attached bath, south facing, 5th floor. Lift available.','Bashundhara R/A',NULL,NULL,8500.00,0,'Male',1,'Available','2026-01-20 03:49:40'),(17,18,'Room','Shared Room for Female','Near NSU, quiet environment. 2 person room.','Bashundhara R/A',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-21 03:49:40'),(18,13,'HostelBed','Seat in Master Bedroom','Nice view, balcony attached. Meal system active.','Baridhara',NULL,NULL,5000.00,1,'Male',1,'Available','2026-01-17 03:49:40'),(19,18,'Room','Family Flat for Rent','2 Bedrooms, 2 Bath. Gas line available.','Uttara Sector 7',NULL,NULL,18000.00,0,'Any',1,'Available','2026-01-15 03:49:40'),(20,12,'RoommateWanted','Looking for Roommate','I have a 3 room flat, need 1 person for small room.','Mirpur DOHS',NULL,NULL,6000.00,1,'Male',1,'Available','2026-01-22 00:49:40'),(21,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-22 04:04:08'),(22,13,'Room','Single Room in Bashundhara','Attached bath, south facing, 5th floor. Lift available.','Bashundhara R/A',NULL,NULL,8500.00,0,'Male',1,'Available','2026-01-20 04:04:08'),(23,18,'Room','Shared Room for Female','Near NSU, quiet environment. 2 person room.','Bashundhara R/A',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-21 04:04:08'),(24,13,'HostelBed','Seat in Master Bedroom','Nice view, balcony attached. Meal system active.','Baridhara',NULL,NULL,5000.00,1,'Male',1,'Available','2026-01-17 04:04:08'),(25,18,'Room','Family Flat for Rent','2 Bedrooms, 2 Bath. Gas line available.','Uttara Sector 7',NULL,NULL,18000.00,0,'Any',1,'Available','2026-01-15 04:04:08'),(26,12,'RoommateWanted','Looking for Roommate','I have a 3 room flat, need 1 person for small room.','Mirpur DOHS',NULL,NULL,6000.00,1,'Male',1,'Available','2026-01-22 01:04:08'),(27,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-22 12:25:32'),(28,13,'Room','Single Room in Bashundhara','Attached bath, south facing, 5th floor. Lift available.','Bashundhara R/A',NULL,NULL,8500.00,0,'Male',1,'Available','2026-01-20 12:25:32'),(29,18,'Room','Shared Room for Female','Near NSU, quiet environment. 2 person room.','Bashundhara R/A',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-21 12:25:32'),(30,13,'HostelBed','Seat in Master Bedroom','Nice view, balcony attached. Meal system active.','Baridhara',NULL,NULL,5000.00,1,'Male',1,'Available','2026-01-17 12:25:32'),(31,18,'Room','Family Flat for Rent','2 Bedrooms, 2 Bath. Gas line available.','Uttara Sector 7',NULL,NULL,18000.00,0,'Any',1,'Available','2026-01-15 12:25:32'),(32,12,'RoommateWanted','Looking for Roommate','I have a 3 room flat, need 1 person for small room.','Mirpur DOHS',NULL,NULL,6000.00,1,'Male',1,'Available','2026-01-22 09:25:32'),(33,6,'Room','In vatara I need a room','hhey do you need this room, fell free to contact with me.','Vatara, Dhaka.',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-23 16:29:37'),(34,2,'Room','2BHK near Dhanmondi 27','Spacious room with balcony, close to bus stop.','Dhanmondi',23.74646600,90.37601500,15000.00,1,'Male',1,'Available','2026-01-23 17:23:35'),(35,13,'Room','Single Room in Bashundhara','Attached bath, south facing, 5th floor. Lift available.','Bashundhara R/A',NULL,NULL,8500.00,0,'Male',1,'Available','2026-01-21 17:23:35'),(36,18,'Room','Shared Room for Female','Near NSU, quiet environment. 2 person room.','Bashundhara R/A',NULL,NULL,4500.00,1,'Female',1,'Available','2026-01-22 17:23:35'),(37,13,'HostelBed','Seat in Master Bedroom','Nice view, balcony attached. Meal system active.','Baridhara',NULL,NULL,5000.00,1,'Male',1,'Available','2026-01-18 17:23:35'),(38,18,'Room','Family Flat for Rent','2 Bedrooms, 2 Bath. Gas line available.','Uttara Sector 7',NULL,NULL,18000.00,0,'Any',1,'Available','2026-01-16 17:23:35'),(39,12,'RoommateWanted','Looking for Roommate','I have a 3 room flat, need 1 person for small room.','Mirpur DOHS',NULL,NULL,6000.00,1,'Male',1,'Available','2026-01-23 14:23:35'),(40,6,'Room','new room','hhhh','dhaka',NULL,NULL,122.00,0,'Any',1,'Available','2026-01-23 18:40:42');
/*!40000 ALTER TABLE `roomlisting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicebooking`
--

DROP TABLE IF EXISTS `servicebooking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicebooking` (
  `BookingID` int(11) NOT NULL AUTO_INCREMENT,
  `ProviderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ServiceType` enum('Cleaner','Van','Tuition','Meal','Other') NOT NULL,
  `Date` date NOT NULL,
  `TimeSlot` varchar(50) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `PaymentStatus` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `BookingStatus` enum('Requested','Confirmed','InProgress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`BookingID`),
  KEY `idx_booking_provider_date` (`ProviderID`,`Date`),
  KEY `idx_booking_user_date` (`UserID`,`Date`),
  CONSTRAINT `fk_booking_provider` FOREIGN KEY (`ProviderID`) REFERENCES `serviceprovider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicebooking`
--

LOCK TABLES `servicebooking` WRITE;
/*!40000 ALTER TABLE `servicebooking` DISABLE KEYS */;
INSERT INTO `servicebooking` VALUES (1,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-22 02:44:44'),(2,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-22 03:45:57'),(3,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-22 03:47:09'),(4,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-22 03:49:40'),(5,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-22 04:04:08'),(6,20,7,'','2026-10-02','12','Notun Bazar, Badda, Dhaka',12305.00,'Pending','Requested','2026-01-22 04:10:01'),(7,14,5,'Tuition','2026-11-12','9.00-10.00','Notun Bazar, Badda, Dhaka',2355.00,'Pending','Requested','2026-01-22 11:57:13'),(8,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-22 12:25:32'),(9,14,5,'Tuition','2026-11-12','9.00-10.00','Notun Bazar, Badda, Dhaka',5000.00,'Pending','Requested','2026-01-22 12:45:51'),(10,71,5,'Cleaner','2026-12-12','9.00-10.00','Notun Bazar, Badda, Dhaka',5000.00,'Pending','Confirmed','2026-01-22 13:05:15'),(11,71,5,'Van','2026-12-12','9.00-10.00','Notun Baz',2000.00,'Pending','Confirmed','2026-01-22 13:13:42'),(12,3,1,'Cleaner','2026-01-15','09:00-11:00','House 10, Road 5, Dhanmondi',600.00,'Pending','Requested','2026-01-23 17:23:35'),(13,55,5,'Cleaner','2026-01-23','9.00-10.00','Notun Bazar, Badda, Dhaka',5000.00,'Pending','Completed','2026-01-23 18:06:01'),(14,55,5,'Van','2026-01-30','9.00-10.00','Notun Bazar, Badda, Dhaka',200.00,'Pending','Completed','2026-01-23 18:07:09'),(15,55,5,'Van','2026-01-24','9.00-10.00','Notun Bazar, Badda, Dhaka',200.00,'Pending','Completed','2026-01-23 18:08:29'),(16,16,87,'Van','2026-01-25','10:00 AM','Dhaka',5000.00,'Pending','Requested','2026-01-23 18:16:56'),(17,86,87,'Van','2026-01-25','10:00 AM','Dhaka',2000.00,'Pending','Completed','2026-01-23 18:27:29'),(18,55,5,'','2026-01-23','9.00-10.00','Notun Bazar, Badda, Dhaka',2500.00,'Pending','Completed','2026-01-23 18:35:40');
/*!40000 ALTER TABLE `servicebooking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `serviceprovider`
--

DROP TABLE IF EXISTS `serviceprovider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `serviceprovider` (
  `ProviderID` int(11) NOT NULL,
  `ServiceType` enum('Cleaner','Van','Mess','Tuition','Other') NOT NULL,
  `BusinessName` varchar(100) NOT NULL,
  `Area` varchar(100) NOT NULL,
  `AverageRating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `TotalReviews` int(11) NOT NULL DEFAULT 0,
  `IsApproved` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin must approve before visible to students',
  `Description` text DEFAULT NULL,
  `StartingPrice` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`ProviderID`),
  CONSTRAINT `fk_sp_user` FOREIGN KEY (`ProviderID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `serviceprovider`
--

LOCK TABLES `serviceprovider` WRITE;
/*!40000 ALTER TABLE `serviceprovider` DISABLE KEYS */;
INSERT INTO `serviceprovider` VALUES (3,'Cleaner','CleanPro Services','Dhanmondi',0.00,0,0,NULL,0.00),(14,'Tuition','Math & Physics Care','Bashundhara R/A',4.90,25,1,NULL,0.00),(15,'Cleaner','Daily Home Clean','Dhanmondi',4.20,40,1,NULL,0.00),(16,'Van','Easy Shift Movers','All Dhaka',4.70,15,1,NULL,0.00),(20,'Mess','Mayer Doa Meal System','Kuril',3.80,8,1,NULL,0.00),(55,'Cleaner','Zerone','dhaka',5.00,1,1,NULL,0.00),(71,'Van','zerone','dhaka',0.00,0,1,'lkloo',200.00),(86,'Cleaner','Test Business','Test Area',0.00,0,1,NULL,0.00);
/*!40000 ALTER TABLE `serviceprovider` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sp_role_check
BEFORE INSERT ON SERVICEPROVIDER
FOR EACH ROW
BEGIN
  DECLARE r ENUM('Student','Owner','ServiceProvider','Admin');
  SELECT Role INTO r FROM USER WHERE UserID = NEW.ProviderID;
  IF r <> 'ServiceProvider' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Provider must have Role = ServiceProvider';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('Student','Owner','ServiceProvider','Admin') NOT NULL DEFAULT 'Student',
  `AgeGroup` enum('18-21','22-25','26-30','Over30') NOT NULL,
  `LivingStatus` enum('Alone','Roommates','Family','Hostel') NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Active','Blocked','Suspended') NOT NULL DEFAULT 'Active',
  `Bio` text DEFAULT NULL,
  `Country` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'Rahim Uddin','rahim@example.com','01711111111','hash1','Student','18-21','Roommates','2026-01-22 02:44:43','Active',NULL,NULL),(2,'Karim Hossain','karim.owner@example.com','01722222222','hash2','Owner','26-30','Family','2026-01-22 02:44:43','Active',NULL,NULL),(3,'CleanPro Services','cleanpro@example.com','01733333333','hash3','ServiceProvider','22-25','Family','2026-01-22 02:44:43','Active',NULL,NULL),(4,'Admin User','admin@example.com','01744444444','hash4','Admin','Over30','Family','2026-01-22 02:44:43','Active',NULL,NULL),(5,'MD. MAHAMUDUL HASAN','ssiam8474@gmail.com','01882359241','$2y$10$vyMLKY.fhbvfsLxlESM.POB2X2.Js8dNwnJnAR0nq.RzUoa588HeO','Student','18-21','Roommates','2026-01-22 02:46:30','Active',NULL,NULL),(6,'MD. MAHAMUDUL HASAN','iam8474@gmail.com','iam8474@gmail.com','$2y$10$dH.XYUUKggarIKzZ9Vql3OzEq9ufmVnnzqDBx9DTeZyq3EtXdCMYC','Owner','18-21','Family','2026-01-22 03:11:33','Active',NULL,NULL),(7,'MD. MAHAMUDUL HASAN','siam8474@gmail.com','ssiam8474@gmail.com','$2y$10$Fqcz2WGDOJ3eX3kK9qZvR.jD8dEXp/9EHZWczyiY7WYTxYdvc5iJ.','Student','18-21','Roommates','2026-01-22 03:17:20','Active',NULL,NULL),(12,'Rahim Seller','rahim@test.com','01710000001','$2y$10$YourHashHere','Student','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(13,'Karim Owner','karim@test.com','01710000002','$2y$10$YourHashHere','Owner','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(14,'Ayesha Tutor','ayesha@test.com','01710000003','$2y$10$YourHashHere','ServiceProvider','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(15,'Clean Pro','clean@test.com','01710000004','$2y$10$YourHashHere','ServiceProvider','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(16,'Fast Movers','move@test.com','01710000005','$2y$10$YourHashHere','ServiceProvider','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(17,'Jamal Student','jamal@test.com','01710000006','$2y$10$YourHashHere','Student','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(18,'Owner Salma','salma@test.com','01710000007','$2y$10$YourHashHere','Owner','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(19,'Tech Guy','tech@test.com','01710000008','$2y$10$YourHashHere','Student','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(20,'Mess Manager','mess@test.com','01710000009','$2y$10$YourHashHere','ServiceProvider','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(21,'Book Worm','books@test.com','01710000010','$2y$10$YourHashHere','Student','18-21','Alone','2026-01-22 03:45:57','Active',NULL,NULL),(55,'MD. MAHAMUDUL HASAN','mohammedmiraz203@gmai.com','01882359241','$2y$10$fYlMiVfTznnxXFVwR2EDFOzlbr0v3JR5lkwsj7SvXcYMcezWidF1a','ServiceProvider','','','2026-01-22 11:58:40','Active',NULL,NULL),(70,'MD. MAHAMUDUL HASAN','mm@gmail.com','01882359241','$2y$10$nIOIswtKbCJYqPADbsMu2.AVgTJ2hCCBSGCHace.H.ubd/zERBaxq','Admin','','','2026-01-22 12:39:32','Active',NULL,NULL),(71,'abc','abc@gmail.com','01882359241','$2y$10$VOJ.Nw2nhtUbmM6Yg5XKKeNawo8LjnXty0/pCWt204pX4rne8AT6W','ServiceProvider','','','2026-01-22 12:53:37','Active',NULL,NULL),(86,'Test Provider','testprovider@example.com','01712345678','$2y$10$hZ6eIz5YeY3xc0/M8E2A7.Qbv9LE/9rt0/jzBnMcBOasoaDaq6k.O','ServiceProvider','','','2026-01-23 17:59:13','Active',NULL,NULL),(87,'Test User','testuser@example.com','01712345678','$2y$10$XvHqfysaD4p1TkXUrsRfde8WFilw9iDCPaPH5Kwxrh.4qr6Pmjy66','Student','','','2026-01-23 18:00:59','Active',NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_badges`
--

DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_badges` (
  `BadgeID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `BadgeName` varchar(100) NOT NULL,
  `BadgeDescription` varchar(255) DEFAULT NULL,
  `EarnedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`BadgeID`),
  KEY `fk_badge_user` (`UserID`),
  CONSTRAINT `fk_badge_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_badges`
--

LOCK TABLES `user_badges` WRITE;
/*!40000 ALTER TABLE `user_badges` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_points`
--

DROP TABLE IF EXISTS `user_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_points` (
  `PointID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `TotalPoints` int(11) NOT NULL DEFAULT 0,
  `TierLevel` enum('Bronze','Silver','Gold','Platinum') NOT NULL DEFAULT 'Bronze',
  `StreakDays` int(11) NOT NULL DEFAULT 0,
  `LastActivityDate` date DEFAULT NULL,
  PRIMARY KEY (`PointID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `fk_points_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points`
--

LOCK TABLES `user_points` WRITE;
/*!40000 ALTER TABLE `user_points` DISABLE KEYS */;
INSERT INTO `user_points` VALUES (1,7,10,'Bronze',1,'2026-01-22');
/*!40000 ALTER TABLE `user_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'findr'
--

--
-- Dumping routines for database 'findr'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-23 18:41:51
