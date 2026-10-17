-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: fleet_db
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
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(50) NOT NULL,
  `department_name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `head_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_code` (`department_code`),
  UNIQUE KEY `uq_departments_department_name` (`department_name`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'DG-OFFICE','Director-General\'s Office','Office of the Director-General','Dir. Jose A. Torres Jr.','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(2,'ADM-DIV','Administrative Division','General administration, logistics and motorpool coordination','Atty. Julius S. De Peralta','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(3,'FIN-DIV','Finance and Accounting Division','Budget utilization, disbursement and COA liquidation','Ms. Cristina V. Bautista','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(4,'HRM-DIV','Human Resource Management Division','Personnel, travel authorities and official business clearances','Mr. Emmanuel R. Dizon','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(5,'PLN-DIV','Planning and Operations Division','Program planning, monitoring and field operations','Engr. Ricardo M. Salazar','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(6,'NID','News and Information Division','News release, press briefing and public information campaigns','Ms. Patricia L. Gomez','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(7,'PMD','Production and Media Division','Audio-visual production, multimedia and content development','Mr. Adrian C. Mendoza','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(8,'PAO-DIV','Public Assistance and Complaints Desk','Citizen feedback, complaints and public assistance coordination','Ms. Rosalinda P. Aguilar','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(9,'ITSD','Information Technology Services Division','Network, systems administration and digital services','Mr. Kevin B. Rocero','active','2026-10-09 09:30:51','2026-10-09 09:30:51'),(10,'ROPS-DIV','Regional Operations Division','Field operations and regional office coordination','Ms. Angelica T. Ramos','active','2026-10-09 09:30:51','2026-10-09 09:30:51');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drivers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `driver_code` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `license_type` varchar(50) NOT NULL DEFAULT 'Class A Commercial',
  `license_expiry` date DEFAULT NULL,
  `status` enum('available','on_trip','off_duty','on_leave','suspended') NOT NULL DEFAULT 'available',
  `safety_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `total_trips` int(11) NOT NULL DEFAULT 0,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `driver_code` (`driver_code`),
  UNIQUE KEY `license_number` (`license_number`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `drivers`
--

LOCK TABLES `drivers` WRITE;
/*!40000 ALTER TABLE `drivers` DISABLE KEYS */;
INSERT INTO `drivers` VALUES (1,12,'DRV-101','Rodrigo','Santos','rodrigo.santos@fleet.com','+63 917 111 2233','N01-19-082914','Professional Driver (Heavy Articulated)','2028-09-30','available',100.00,0,'12 Sampaguita St, Pasig City','Elena Santos (+63 917 888 1234)','2026-09-30 02:03:50','2026-10-17 08:45:47'),(2,13,'DRV-102','Eduardo','Reyes','eduardo.reyes@fleet.com','+63 918 222 3344','N02-20-044812','Professional Driver (Heavy Articulated)','2027-09-30','available',100.00,0,'45 Dahlia Rd, Quezon City','Maria Reyes (+63 918 999 5678)','2026-09-30 02:03:50','2026-10-17 08:45:47'),(3,25,'DRV-103','Carlos','Mendoza','carlos.mendoza@fleet.com','+63 919 333 4455','N03-21-099231','Professional Driver (Light & Medium)','2029-09-30','available',100.00,0,'88 Acacia Blvd, Mandaluyong City','Lourdes Mendoza (+63 919 111 9876)','2026-09-30 02:03:50','2026-10-17 08:45:47'),(4,26,'DRV-104','Mark','Villanueva','mark.villanueva@fleet.com','+63 920 444 5566','N04-18-011243','Professional Driver (Commercial Van)','2028-03-30','available',100.00,0,'23 Malakas St, Makati City','Joy Villanueva (+63 920 222 3456)','2026-09-30 02:03:50','2026-10-17 08:45:47'),(5,27,'DRV-105','Arnel','Bautista','arnel.bautista@fleet.com','+63 921 555 6677','N05-22-076123','Professional Driver (Heavy Articulated)','2027-03-30','available',100.00,0,'101 Bayani Ave, Taguig City','Grace Bautista (+63 921 333 7890)','2026-09-30 02:03:50','2026-10-17 08:45:47');
/*!40000 ALTER TABLE `drivers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_outbox`
--

DROP TABLE IF EXISTS `email_outbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_outbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `to_email` varchar(150) DEFAULT NULL,
  `to_user_id` int(11) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('sent','logged','failed') NOT NULL DEFAULT 'logged',
  `error` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_outbox`
--

LOCK TABLES `email_outbox` WRITE;
/*!40000 ALTER TABLE `email_outbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_outbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_logs`
--

DROP TABLE IF EXISTS `fuel_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) DEFAULT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `driver_id` int(11) unsigned DEFAULT NULL,
  `fuel_date` date NOT NULL,
  `odometer_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `liters` decimal(8,2) NOT NULL,
  `cost_per_liter` decimal(8,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `fuel_station` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_logs`
--

LOCK TABLES `fuel_logs` WRITE;
/*!40000 ALTER TABLE `fuel_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gate_logs`
--

DROP TABLE IF EXISTS `gate_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gate_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trip_ticket_id` int(11) unsigned NOT NULL,
  `event_type` enum('egress','ingress') NOT NULL,
  `guard_user_id` int(11) unsigned NOT NULL,
  `guard_name` varchar(100) NOT NULL,
  `scanned_at` datetime NOT NULL,
  `odometer_reading` decimal(10,2) NOT NULL,
  `security_status` enum('cleared','flagged','rejected') NOT NULL DEFAULT 'cleared',
  `odometer_verified` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_ticket_id` (`trip_ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_logs`
--

LOCK TABLES `gate_logs` WRITE;
/*!40000 ALTER TABLE `gate_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `gate_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gps_pings`
--

DROP TABLE IF EXISTS `gps_pings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gps_pings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `speed_kmh` decimal(6,2) DEFAULT 0.00,
  `heading` decimal(5,2) DEFAULT 0.00,
  `fuel_level` decimal(5,2) DEFAULT 100.00,
  `ignition` tinyint(1) DEFAULT 1,
  `engine_code` varchar(50) DEFAULT NULL,
  `recorded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_veh_rec` (`vehicle_id`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gps_pings`
--

LOCK TABLES `gps_pings` WRITE;
/*!40000 ALTER TABLE `gps_pings` DISABLE KEYS */;
/*!40000 ALTER TABLE `gps_pings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incidents`
--

DROP TABLE IF EXISTS `incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `incident_no` varchar(50) NOT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `driver_id` int(11) unsigned DEFAULT NULL,
  `trip_id` int(11) unsigned DEFAULT NULL,
  `incident_date` datetime NOT NULL,
  `severity` enum('minor','moderate','severe','critical') NOT NULL DEFAULT 'minor',
  `type` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `damage_estimate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('reported','investigating','resolved','closed') NOT NULL DEFAULT 'reported',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incident_no` (`incident_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incidents`
--

LOCK TABLES `incidents` WRITE;
/*!40000 ALTER TABLE `incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_records`
--

DROP TABLE IF EXISTS `maintenance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(50) NOT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `scheduled_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `odometer_at_service` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_center` varchar(150) DEFAULT NULL,
  `technician_name` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_no` (`reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_records`
--

LOCK TABLES `maintenance_records` WRITE;
/*!40000 ALTER TABLE `maintenance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-09-30-020043','App\\Database\\Migrations\\CreateFleetTables','default','App',1790733795,1),(2,'2026-10-01-070000','App\\Database\\Migrations\\CreatePiaFleetTables','default','App',1790837640,2),(3,'2026-10-08-090000','App\\Database\\Migrations\\AddBrdComplianceTables','default','App',1791441693,3),(4,'2026-10-09-090000','App\\Database\\Migrations\\AddDepartmentToTripRequests','default','App',1791538251,4),(5,'2026-10-13-100000','App\\Database\\Migrations\\CreateSessionsTable','default','App',1791868904,5),(6,'2026-10-13-120000','App\\Database\\Migrations\\AddReferenceNameUniqueIndexes','default','App',1791876284,6),(7,'2026-10-16-180000','App\\Database\\Migrations\\AddMergeAndDriverAvailabilityFields','default','App',1792145224,7),(8,'2026-10-16-183000','App\\Database\\Migrations\\AddOnLeaveToDriversStatusEnum','default','App',1792146174,8);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `non_usage_certificates`
--

DROP TABLE IF EXISTS `non_usage_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `non_usage_certificates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_no` varchar(50) NOT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `period_month` varchar(7) NOT NULL,
  `trip_count` int(5) NOT NULL DEFAULT 0,
  `odometer_start` decimal(10,2) NOT NULL DEFAULT 0.00,
  `odometer_end` decimal(10,2) NOT NULL DEFAULT 0.00,
  `generated_by` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_no` (`certificate_no`),
  UNIQUE KEY `vehicle_id_period_month` (`vehicle_id`,`period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `non_usage_certificates`
--

LOCK TABLES `non_usage_certificates` WRITE;
/*!40000 ALTER TABLE `non_usage_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `non_usage_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offices`
--

DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `office_code` varchar(50) NOT NULL,
  `office_name` varchar(150) NOT NULL,
  `office_type` enum('central','regional','satellite') NOT NULL DEFAULT 'central',
  `address` varchar(255) DEFAULT NULL,
  `division_head_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `office_code` (`office_code`),
  UNIQUE KEY `uq_offices_office_name` (`office_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offices`
--

LOCK TABLES `offices` WRITE;
/*!40000 ALTER TABLE `offices` DISABLE KEYS */;
INSERT INTO `offices` VALUES (1,'CO-ODG','Central Office - Office of the Director-General','central','PIA Bldg., Visayas Avenue, Diliman, Quezon City','Director-General Jose A. Torres Jr.','2026-10-01 06:55:42','2026-10-01 06:55:42'),(2,'CO-ADMIN','Central Office - Administrative Division','central','PIA Bldg., Visayas Avenue, Diliman, Quezon City','Atty. Julius S. De Peralta','2026-10-01 06:55:42','2026-10-01 06:55:42'),(3,'CO-NID','Central Office - News & Information Division','central','PIA Bldg., Visayas Avenue, Diliman, Quezon City','Dir. Maria Elena Rodriguez','2026-10-01 06:55:42','2026-10-01 06:55:42'),(4,'CO-PMD','Central Office - Production & Media Division','central','PIA Bldg., Visayas Avenue, Diliman, Quezon City','Dir. Ferdinand C. Rivera','2026-10-01 06:55:42','2026-10-01 06:55:42'),(5,'RO-NCR','Regional Office - National Capital Region','regional','PIA NCR, Diliman, Quezon City','Reg. Dir. Emigdio A. Gomez','2026-10-01 06:55:42','2026-10-01 06:55:42'),(6,'RO-CAR','Regional Office - Cordillera Administrative Region','regional','PIA CAR, Baguio City','Reg. Dir. Helen R. Tibaldo','2026-10-01 06:55:42','2026-10-01 06:55:42'),(7,'RO-01','Regional Office I - Ilocos Region','regional','PIA Region 1, San Fernando City, La Union','Reg. Dir. Jennilyne C. Role','2026-10-01 06:55:42','2026-10-01 06:55:42'),(8,'RO-03','Regional Office III - Central Luzon','regional','PIA Region 3, City of San Fernando, Pampanga','Reg. Dir. William L. Beltran','2026-10-01 06:55:42','2026-10-01 06:55:42'),(9,'RO-04A','Regional Office IV-A - CALABARZON','regional','PIA CALABARZON, Calamba City, Laguna','Reg. Dir. Cristina C. Arzadon','2026-10-01 06:55:42','2026-10-01 06:55:42'),(10,'RO-07','Regional Office VII - Central Visayas','regional','PIA Region 7, Cebu City','Reg. Dir. Fayette C. Riñen','2026-10-01 06:55:42','2026-10-01 06:55:42');
/*!40000 ALTER TABLE `offices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passenger_delay_logs`
--

DROP TABLE IF EXISTS `passenger_delay_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passenger_delay_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `trip_ticket_id` int(11) unsigned NOT NULL,
  `delay_minutes` int(5) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `dispatcher_notified` tinyint(1) NOT NULL DEFAULT 0,
  `logged_by` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_ticket_id` (`trip_ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passenger_delay_logs`
--

LOCK TABLES `passenger_delay_logs` WRITE;
/*!40000 ALTER TABLE `passenger_delay_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `passenger_delay_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pir_reports`
--

DROP TABLE IF EXISTS `pir_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pir_reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pir_number` varchar(50) NOT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `trip_ticket_id` int(11) unsigned DEFAULT NULL,
  `incident_id` int(11) unsigned DEFAULT NULL,
  `source` enum('blowbagets','breakdown','incident','manual') NOT NULL DEFAULT 'manual',
  `defect_description` text NOT NULL,
  `status` enum('pending','in_inspection','in_repair','ready_for_release','released') NOT NULL DEFAULT 'pending',
  `pre_inspection_findings` text DEFAULT NULL,
  `post_repair_evaluation` text DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL,
  `repair_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `mechanic_id` int(11) unsigned DEFAULT NULL,
  `reported_by` int(11) unsigned DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pir_number` (`pir_number`),
  KEY `vehicle_id_status` (`vehicle_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pir_reports`
--

LOCK TABLES `pir_reports` WRITE;
/*!40000 ALTER TABLE `pir_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `pir_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pms_records`
--

DROP TABLE IF EXISTS `pms_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pms_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) unsigned NOT NULL,
  `last_pms_odometer` decimal(10,2) NOT NULL DEFAULT 0.00,
  `next_pms_odometer` decimal(10,2) NOT NULL DEFAULT 5000.00,
  `pms_interval_km` decimal(10,2) NOT NULL DEFAULT 5000.00,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('ok','due_soon','overdue','in_service') NOT NULL DEFAULT 'ok',
  `last_pms_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pms_records`
--

LOCK TABLES `pms_records` WRITE;
/*!40000 ALTER TABLE `pms_records` DISABLE KEYS */;
INSERT INTO `pms_records` VALUES (1,1,60000.00,65000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 65000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(2,2,30000.00,35000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 35000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(3,3,40000.00,45000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 45000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(4,4,85000.00,90000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 90000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(5,5,15000.00,20000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 20000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(6,6,30000.00,35000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 35000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(7,7,15000.00,20000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 20000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(8,8,45000.00,50000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 50000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47'),(9,9,25000.00,30000.00,5000.00,0,'ok','2026-10-02','Routine 5,000 KM PMS Cycle. Next inspection at 30000 km.','2026-10-01 06:55:42','2026-10-17 08:45:47');
/*!40000 ALTER TABLE `pms_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfid_cards`
--

DROP TABLE IF EXISTS `rfid_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfid_cards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) unsigned NOT NULL,
  `provider` enum('autosweep','easytrip') NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `low_balance_threshold` decimal(10,2) NOT NULL DEFAULT 500.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_id_provider` (`vehicle_id`,`provider`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfid_cards`
--

LOCK TABLES `rfid_cards` WRITE;
/*!40000 ALTER TABLE `rfid_cards` DISABLE KEYS */;
INSERT INTO `rfid_cards` VALUES (4,6,'autosweep','4502-1188-3390-7741',500.00,500.00,'active','2026-09-27 09:15:00','2026-10-17 08:45:47');
/*!40000 ALTER TABLE `rfid_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfid_transactions`
--

DROP TABLE IF EXISTS `rfid_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfid_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfid_card_id` int(11) unsigned NOT NULL,
  `trip_ticket_id` int(11) unsigned DEFAULT NULL,
  `type` enum('reload','toll','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfid_card_id` (`rfid_card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfid_transactions`
--

LOCK TABLES `rfid_transactions` WRITE;
/*!40000 ALTER TABLE `rfid_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `rfid_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `safety_checks`
--

DROP TABLE IF EXISTS `safety_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_checks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `trip_ticket_id` int(11) unsigned NOT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `driver_id` int(11) unsigned NOT NULL,
  `items` text NOT NULL,
  `failed_items` text DEFAULT NULL,
  `result` enum('pass','fail') NOT NULL,
  `remarks` text DEFAULT NULL,
  `checked_by` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_ticket_id_result` (`trip_ticket_id`,`result`),
  KEY `vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `safety_checks`
--

LOCK TABLES `safety_checks` WRITE;
/*!40000 ALTER TABLE `safety_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `safety_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_audit_logs`
--

DROP TABLE IF EXISTS `system_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_role` varchar(30) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `context` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type_created_at` (`event_type`,`created_at`),
  KEY `entity_type_entity_id` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_audit_logs`
--

LOCK TABLES `system_audit_logs` WRITE;
/*!40000 ALTER TABLE `system_audit_logs` DISABLE KEYS */;
INSERT INTO `system_audit_logs` VALUES (1,NULL,'SYSTEM (Scheduled Task)','system','status_change','system',NULL,'System operational database purged of test data and reset to clean production baseline.','{\"purged_tables\":[\"trip_requests\",\"trip_tickets\",\"gate_logs\",\"trips\",\"fuel_logs\",\"incidents\",\"maintenance_records\",\"passenger_delay_logs\",\"pir_reports\",\"safety_checks\",\"non_usage_certificates\",\"rfid_transactions\",\"telemetry_logs\",\"gps_pings\",\"system_notifications\",\"system_audit_logs\",\"email_outbox\",\"sessions\"]}',NULL,NULL,'2026-10-17 08:45:48'),(2,5,'Director-General Jose A. Torres Jr.','admin','logout','user',5,'User signed out.',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 09:12:59'),(3,5,'Director-General Jose A. Torres Jr.','admin','login_success','user',5,'Successful login for admin@pia.gov.ph (admin)',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 09:13:02'),(4,5,'Director-General Jose A. Torres Jr.','admin','logout','user',5,'User signed out.',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 09:22:36'),(5,5,'Director-General Jose A. Torres Jr.','admin','login_success','user',5,'Successful login for admin@pia.gov.ph (admin)',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 09:22:41'),(6,5,'Director-General Jose A. Torres Jr.','admin','logout','user',5,'User signed out.',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 09:22:50');
/*!40000 ALTER TABLE `system_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_notifications`
--

DROP TABLE IF EXISTS `system_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','danger','success') NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_is_read` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_notifications`
--

LOCK TABLES `system_notifications` WRITE;
/*!40000 ALTER TABLE `system_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telemetry_logs`
--

DROP TABLE IF EXISTS `telemetry_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telemetry_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) unsigned NOT NULL,
  `trip_id` int(11) unsigned DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `speed_kmh` decimal(6,2) NOT NULL DEFAULT 0.00,
  `heading` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fuel_level` decimal(5,2) NOT NULL DEFAULT 100.00,
  `engine_status` varchar(20) NOT NULL DEFAULT 'running',
  `battery_voltage` decimal(4,2) DEFAULT NULL,
  `engine_temp_c` decimal(5,2) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telemetry_logs`
--

LOCK TABLES `telemetry_logs` WRITE;
/*!40000 ALTER TABLE `telemetry_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `telemetry_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_requests`
--

DROP TABLE IF EXISTS `trip_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_requests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `requestor_id` int(11) unsigned NOT NULL,
  `requestor_name` varchar(100) NOT NULL,
  `office_id` int(11) unsigned DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `office_scope` enum('central','regional','satellite') NOT NULL DEFAULT 'central',
  `destination` varchar(255) NOT NULL,
  `purpose` text NOT NULL,
  `passenger_names` text DEFAULT NULL,
  `passenger_count` int(5) NOT NULL DEFAULT 1,
  `departure_time` datetime NOT NULL,
  `return_time` datetime NOT NULL,
  `requested_driver_id` int(11) unsigned DEFAULT NULL,
  `requested_vehicle_type` varchar(50) DEFAULT NULL,
  `is_rush_request` tinyint(1) NOT NULL DEFAULT 0,
  `justification_file` varchar(255) DEFAULT NULL,
  `justification_notes` text DEFAULT NULL,
  `status` enum('pending_dispatcher','pending_admin','approved','dispatched','completed','rejected','expired') DEFAULT 'pending_dispatcher',
  `ticket_id` int(10) unsigned DEFAULT NULL,
  `is_merged` tinyint(1) NOT NULL DEFAULT 0,
  `merged_with_request_id` int(10) unsigned DEFAULT NULL,
  `dispatcher_approver_id` int(11) unsigned DEFAULT NULL,
  `dispatcher_action` enum('pending','approved','rejected') DEFAULT 'pending',
  `dispatcher_action_at` datetime DEFAULT NULL,
  `dispatcher_remarks` text DEFAULT NULL,
  `admin_approver_id` int(11) unsigned DEFAULT NULL,
  `admin_action` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_action_at` datetime DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `sla_deadline` datetime DEFAULT NULL,
  `is_sla_breached` tinyint(1) NOT NULL DEFAULT 0,
  `is_emergency_override` tinyint(1) NOT NULL DEFAULT 0,
  `emergency_override_by` int(10) unsigned DEFAULT NULL,
  `emergency_override_at` datetime DEFAULT NULL,
  `emergency_override_remarks` text DEFAULT NULL,
  `escalated_at` datetime DEFAULT NULL,
  `post_trip_doc_due` datetime DEFAULT NULL,
  `post_trip_doc_status` enum('pending','submitted','overdue') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_number` (`request_number`),
  KEY `requestor_id` (`requestor_id`),
  KEY `status` (`status`),
  KEY `ticket_id` (`ticket_id`),
  KEY `merged_with_request_id` (`merged_with_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_requests`
--

LOCK TABLES `trip_requests` WRITE;
/*!40000 ALTER TABLE `trip_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `trip_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_tickets`
--

DROP TABLE IF EXISTS `trip_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_tickets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_serial_no` varchar(50) NOT NULL,
  `request_id` int(11) unsigned NOT NULL,
  `merged_request_ids` text DEFAULT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `driver_id` int(11) unsigned NOT NULL,
  `qr_crypt_token` varchar(255) NOT NULL,
  `status` enum('issued','departed','arrived_dest','departed_dest','returned','completed','cancelled') NOT NULL DEFAULT 'issued',
  `authorized_departure` datetime NOT NULL,
  `authorized_return` datetime NOT NULL,
  `authorized_passengers` text NOT NULL,
  `authorized_destination` varchar(255) NOT NULL,
  `authorized_purpose` text NOT NULL,
  `admin_approver_name` varchar(100) NOT NULL DEFAULT 'Atty. Julius S. De Peralta',
  `departure_time` datetime DEFAULT NULL,
  `arrival_dest_time` datetime DEFAULT NULL,
  `departure_dest_time` datetime DEFAULT NULL,
  `arrival_back_time` datetime DEFAULT NULL,
  `start_odometer` decimal(10,2) DEFAULT NULL,
  `dest_odometer` decimal(10,2) DEFAULT NULL,
  `return_odometer` decimal(10,2) DEFAULT NULL,
  `total_distance_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fuel_balance_start_liters` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fuel_issued_stock_liters` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fuel_purchased_liters` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fuel_purchased_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fuel_used_liters` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fuel_balance_end_liters` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fuel_efficiency_kml` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fuel_anomaly_flag` tinyint(1) NOT NULL DEFAULT 0,
  `toll_expense` decimal(10,2) NOT NULL DEFAULT 0.00,
  `toll_provider` enum('autosweep','easytrip','cash') DEFAULT NULL,
  `gear_oil_liters` decimal(6,2) NOT NULL DEFAULT 0.00,
  `lube_oil_liters` decimal(6,2) NOT NULL DEFAULT 0.00,
  `grease_units` decimal(6,2) NOT NULL DEFAULT 0.00,
  `driver_certified` tinyint(1) NOT NULL DEFAULT 0,
  `driver_certified_at` datetime DEFAULT NULL,
  `passenger_certified` tinyint(1) NOT NULL DEFAULT 0,
  `passenger_certifier_name` varchar(100) DEFAULT NULL,
  `passenger_certified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_serial_no` (`ticket_serial_no`),
  UNIQUE KEY `qr_crypt_token` (`qr_crypt_token`),
  KEY `request_id` (`request_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `driver_id` (`driver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_tickets`
--

LOCK TABLES `trip_tickets` WRITE;
/*!40000 ALTER TABLE `trip_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `trip_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trips`
--

DROP TABLE IF EXISTS `trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trips` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `trip_number` varchar(50) NOT NULL,
  `vehicle_id` int(11) unsigned NOT NULL,
  `driver_id` int(11) unsigned NOT NULL,
  `origin_address` varchar(255) NOT NULL,
  `origin_lat` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `origin_lng` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `destination_address` varchar(255) NOT NULL,
  `destination_lat` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `destination_lng` decimal(10,7) NOT NULL DEFAULT 0.0000000,
  `cargo_type` varchar(150) NOT NULL DEFAULT 'General Merchandise',
  `cargo_weight_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `distance_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `scheduled_departure` datetime DEFAULT NULL,
  `scheduled_arrival` datetime DEFAULT NULL,
  `actual_departure` datetime DEFAULT NULL,
  `actual_arrival` datetime DEFAULT NULL,
  `start_odometer` decimal(10,2) DEFAULT NULL,
  `end_odometer` decimal(10,2) DEFAULT NULL,
  `status` enum('scheduled','dispatched','in_transit','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `priority` enum('normal','urgent','critical') NOT NULL DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trip_number` (`trip_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trips`
--

LOCK TABLES `trips` WRITE;
/*!40000 ALTER TABLE `trips` DISABLE KEYS */;
/*!40000 ALTER TABLE `trips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('admin','dispatcher','maintenance','driver','requestor','approver_admin','guard','auditor') DEFAULT 'requestor',
  `office_id` int(10) unsigned DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Chief Fleet Admin',NULL,'admin@fleet.com','$2y$12$lZaqyxa4rBuXgtVlqKOUWOlRQ51vI6W2LoiOOlmm..VP9aN0vlygq',NULL,0,'admin',NULL,'+63 917 555 0100','inactive','2026-09-30 02:03:50','2026-10-13 15:20:01'),(2,'Lead Dispatcher',NULL,'dispatcher@fleet.com','$argon2id$v=19$m=65536,t=4,p=1$c1dseGl5Y1RYakpJZnJlaA$+764Z0vvKdAuXyBHyBd2ytZOBPKevV50R/xpHchylhg',NULL,0,'dispatcher',NULL,'+63 918 555 0200','active','2026-09-30 02:03:50','2026-10-15 04:27:00'),(3,'Safety & Maintenance Lead',NULL,'maintenance@fleet.com','$argon2id$v=19$m=65536,t=4,p=1$LkJrODZZdWxpOTRIdWltUw$zjcJccvT+0Ofg+9HZ6o1M9xmM839cMioXhMhRMp6vvc',NULL,0,'maintenance',NULL,'+63 919 555 0300','active','2026-09-30 02:03:50','2026-10-15 05:33:18'),(4,'Rodrigo Santos',NULL,'driver@fleet.com','$2y$12$ynjzd657E/DTQ6vcEoI1GezSQ5ST8POcdwBgee3zks70CagCJCzaC',NULL,0,'driver',NULL,'+63 917 111 2233','inactive','2026-09-30 15:01:05','2026-10-13 15:20:01'),(5,'Director-General Jose A. Torres Jr.','Director-General / Super Administrator','admin@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$Ui5TM08xcnIzZWNOSnJJeg$hdE1Lgw2KVbF9+zmWl7nVi86rJSyzCODJi8kQx1z5Ec','OKRC2664KTKNCWSN3HRQEEI6UGO42MGV',0,'admin',2,'+63 917 555 0100','active','2026-10-01 06:55:42','2026-10-13 00:47:54'),(7,'Atty. Julius S. De Peralta','Head, Administrative Division','admin.head@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$cDNFT0FXLjRVc0R5MFRGWQ$Im+ISj6QCBeugOwWVXE+r2devRrCOG9Z9DedmLjc7V0',NULL,0,'approver_admin',2,'+63 917 555 0102','active','2026-10-01 06:55:42','2026-10-08 06:50:21'),(8,'Engr. Roberto D. Tan','Motorpool Dispatch Officer','dispatcher@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$L1NPVTVhem9vN04ybGZ3cw$bz36eS/Y7/ENzItmRVjC8W39He6LDsBM5O9ReB1w34c',NULL,0,'dispatcher',2,'+63 918 555 0200','active','2026-10-01 06:55:42','2026-10-08 06:50:14'),(9,'Sgt. Danilo Ramos','Senior Compound Security Officer','guard@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$Y2F4QnJuNlFhZ1lzYlVkSw$a+j0FfjcUcD/qqyJX/Cj9QEvUEDD8dC5AsrlR6i8HIw',NULL,0,'guard',2,'+63 918 555 0400','active','2026-10-01 06:55:42','2026-10-08 06:50:26'),(10,'State Auditor IV Teresa Santos','COA Resident Fleet Auditor','auditor@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$bGxYRzZ0WFl5UVZiSGM4TQ$+ELoFQzZAfjYaQk1F6P/5AX+2ZiE88+Aj2AUlX2LZW0',NULL,0,'auditor',2,'+63 919 555 0500','active','2026-10-01 06:55:42','2026-10-08 06:50:18'),(11,'Juan Dela Cruz','Senior Information Officer, Coverage Unit','requestor@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$dW03TnBoVVFpM2VYa0xZcQ$p7KjfmqftWG+Q72B5cM9qnwCxY7POp/m4oJtJ+/kTD0',NULL,0,'requestor',3,'+63 919 555 0600','active','2026-10-01 06:55:42','2026-10-08 06:50:31'),(12,'Rodrigo Santos','Senior Official Driver (Central Pool)','driver.santos@pia.gov.ph','$argon2id$v=19$m=65536,t=4,p=1$NjNhZXVheE4xZlRsZ1VOLw$CGVmy1QEGKkf1lEeaVuS+7uRQwGjeQrtso3s0Y3IQGc',NULL,0,'driver',2,'+63 917 111 2233','active','2026-10-01 06:55:42','2026-10-08 06:50:28'),(13,'Eduardo Reyes','Official Driver (Regional Pool)','driver.reyes@pia.gov.ph','$2y$12$3LwX6hgfqZtuA74vWuiVLekrh2Mgw.1YfxPdbNprwiqvTNanM16ku',NULL,0,'driver',2,'+63 918 222 3344','active','2026-10-01 06:55:42','2026-10-01 06:55:42'),(23,'Valdez, Jairus John D.','MISD - OJT','valdez.jairusjohn.deleste@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$TkExamQwNElrRWQuUUV6Tg$plQ+O3U8XuMYTX5t0TxinMxs4woucN5nqyj4NPCHq8A',NULL,0,'approver_admin',5,'09497844287','active','2026-10-15 03:10:48','2026-10-15 03:10:48'),(24,'De Leon, Job O.','Motorpool Systems & Dispatch Specialist','valdezjairusjohn01@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$OFovRTVTMjA1bWNuRndlaw$DmEpqpL9mv4DlhnidSySeAF0si0z1XLD7fKlAe3BX18',NULL,0,'dispatcher',5,'09062126799','active','2026-10-15 03:15:08','2026-10-15 03:15:08'),(25,'Carlos Mendoza',NULL,'carlos.mendoza@fleet.com','$2y$12$ORKxbs/FIWEh7U522ep2FepmKsNFerg7Fbw0POPX.f16k6af589e2',NULL,0,'driver',2,'+63 919 333 4455','active','2026-10-15 13:17:03','2026-10-15 13:17:03'),(26,'Mark Villanueva',NULL,'mark.villanueva@fleet.com','$2y$12$ORKxbs/FIWEh7U522ep2FepmKsNFerg7Fbw0POPX.f16k6af589e2',NULL,0,'driver',2,'+63 920 444 5566','active','2026-10-15 13:17:03','2026-10-15 13:17:03'),(27,'Arnel Bautista',NULL,'arnel.bautista@fleet.com','$2y$12$ORKxbs/FIWEh7U522ep2FepmKsNFerg7Fbw0POPX.f16k6af589e2',NULL,0,'driver',2,'+63 921 555 6677','active','2026-10-15 13:17:03','2026-10-15 13:17:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_code` varchar(50) NOT NULL,
  `plate_number` varchar(30) NOT NULL,
  `vin` varchar(50) NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `type` enum('semi_truck','box_truck','delivery_van','pickup','trailer','suv') NOT NULL DEFAULT 'box_truck',
  `fuel_type` enum('diesel','gasoline','electric','hybrid') NOT NULL DEFAULT 'diesel',
  `max_payload_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fuel_capacity_liters` decimal(8,2) NOT NULL DEFAULT 100.00,
  `odometer_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_latitude` decimal(10,7) NOT NULL DEFAULT 14.5995120,
  `current_longitude` decimal(10,7) NOT NULL DEFAULT 120.9842220,
  `current_speed` decimal(6,2) NOT NULL DEFAULT 0.00,
  `current_fuel_level` decimal(5,2) NOT NULL DEFAULT 100.00,
  `engine_status` enum('off','idling','running') NOT NULL DEFAULT 'off',
  `status` enum('active','in_transit','maintenance','under_maintenance','disabled_breakdown','out_of_service') DEFAULT 'active',
  `fleet_category` enum('dedicated','pool') NOT NULL DEFAULT 'pool',
  `assigned_official` varchar(100) DEFAULT NULL,
  `lto_registration_expiry` date DEFAULT NULL,
  `gsis_insurance_expiry` date DEFAULT NULL,
  `current_driver_id` int(11) unsigned DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_km` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_code` (`vehicle_code`),
  UNIQUE KEY `plate_number` (`plate_number`),
  UNIQUE KEY `vin` (`vin`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,'FLT-01','NAA-8120','1HTMMAAL4KH123401','Freightliner','Cascadia 126',2023,'semi_truck','diesel',24000.00,450.00,64220.00,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,NULL,NULL,NULL,'2026-10-17',82020.64,'2026-09-30 02:03:50','2026-10-17 08:45:47'),(2,'FLT-02','NCB-4519','4V4NC9EH1MN234502','Volvo','VNL 860',2024,'semi_truck','diesel',26000.00,500.00,32110.00,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,NULL,NULL,NULL,'2026-09-10',40000.00,'2026-09-30 02:03:50','2026-10-17 08:45:47'),(3,'FLT-03','NFE-3392','1FTNE3Y82PKA34503','Ford','Transit 350 High Roof',2023,'delivery_van','diesel',2100.00,95.00,41500.00,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,NULL,NULL,NULL,'2026-09-15',45000.00,'2026-09-30 02:03:50','2026-10-17 08:45:47'),(4,'FLT-04','NKG-9014','JALC4B165R7456704','Isuzu','NPR-HD 16ft Box',2022,'box_truck','diesel',5500.00,140.00,89230.10,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,NULL,NULL,NULL,'2026-06-02',90000.00,'2026-09-30 02:03:50','2026-10-17 08:45:47'),(5,'FLT-05','NPH-7621','WD3PE8CD8NP567805','Mercedes-Benz','Sprinter 2500 Cargo',2024,'delivery_van','diesel',1950.00,90.00,18426.60,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,NULL,NULL,NULL,'2026-08-31',25000.00,'2026-09-30 02:03:50','2026-10-17 08:45:47'),(6,'PIA-V01','SJL-4192','JTFSS22P10023401','Toyota','HiAce Grandia 3.0D Tourer',2023,'delivery_van','diesel',1200.00,70.00,34250.00,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,'2027-04-06','2026-11-22',NULL,'2026-09-01',35000.00,'2026-10-01 06:55:42','2026-10-17 08:45:47'),(7,'PIA-V02','SAB-8821','MHFGB42G50067802','Toyota','Fortuner 2.8L 4x4 LTD',2024,'suv','diesel',750.00,80.00,18920.00,14.6508000,121.0504000,0.00,100.00,'off','active','dedicated','Regional Director Maria L. Santos','2026-10-20','2027-06-05',NULL,'2026-08-17',20000.00,'2026-10-01 06:55:42','2026-10-17 08:45:47'),(8,'PIA-V03','SKE-5510','JN8AS52B40091203','Nissan','NV350 Urvan Premium',2022,'delivery_van','diesel',1400.00,65.00,48800.00,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,'2027-01-11','2026-10-27',NULL,'2026-09-16',50000.00,'2026-10-01 06:55:42','2026-10-17 08:45:47'),(9,'PIA-V04','SKZ-7734','MPB4FR5460012304','Isuzu','D-Max 3.0 4x4 Boondock',2023,'pickup','diesel',1050.00,76.00,29400.00,14.6508000,121.0504000,0.00,100.00,'off','active','pool',NULL,'2027-08-04','2027-08-04',NULL,'2026-09-06',30000.00,'2026-10-01 06:55:42','2026-10-17 08:45:47');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-17 17:25:22
