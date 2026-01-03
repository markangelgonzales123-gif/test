-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: epms_db
-- ------------------------------------------------------
-- Server version	8.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `head_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `head_id` (`head_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'ICSLIS - Institute of Computing Studies and Library Information Sciences','Department for computing and library science programs',1,'2025-04-18 04:51:59','2025-10-13 17:43:26'),(2,'Human Resources','Manages employee relations and workforce planning',5,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,'Finance','Handles financial operations and budgeting',NULL,'2025-04-18 04:51:59','2025-04-18 04:52:48'),(4,'Academic Affairs','Oversees academic programs and policies',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(5,'Student Services','Provides support services for students',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(6,'Academic Affairs','Oversees academic programs, faculty, and educational policies',7,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(7,'Institute of Business and Management','Department for business programs and management education',8,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(8,'Institute of Computing Studies and Library Information Science','Department for computing and library science programs',9,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(9,'Institute of Education, Arts and Sciences','Department for education, arts and sciences programs',10,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(10,'Student Affairs and Services Office','Provides support services for students',11,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(11,'Admissions and Registrar\'s Office','Manages student admissions and registration',12,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(12,'College Library','Provides library resources and services',13,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(13,'College Guidance and Formation Office','Provides guidance and counseling services',14,'2025-04-18 04:51:59','2025-04-18 04:51:59');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dpcr_entries`
--

DROP TABLE IF EXISTS `dpcr_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dpcr_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL,
  `major_output` text COLLATE utf8mb4_general_ci NOT NULL,
  `success_indicators` text COLLATE utf8mb4_general_ci NOT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `accountable` text COLLATE utf8mb4_general_ci,
  `accomplishments` text COLLATE utf8mb4_general_ci,
  `category` enum('Strategic','Core','Support') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Core',
  `q1_rating` decimal(5,2) DEFAULT NULL,
  `q2_rating` decimal(5,2) DEFAULT NULL,
  `q3_rating` decimal(5,2) DEFAULT NULL,
  `q4_rating` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `dpcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dpcr_entries`
--

LOCK TABLES `dpcr_entries` WRITE;
/*!40000 ALTER TABLE `dpcr_entries` DISABLE KEYS */;
INSERT INTO `dpcr_entries` VALUES (1,1,'Curriculum Development','Update CS curriculum by Q2',50000.00,'ICSLIS Department','Curriculum updated ahead of schedule','Strategic',5.00,0.00,0.00,0.00,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,1,'Faculty Training Program','Train faculty on new technologies',75000.00,'ICSLIS Department','Training completed with 95% attendance','Core',4.00,0.00,0.00,0.00,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(4,7,'Sample','wa',10000.00,'32',NULL,'Strategic',NULL,NULL,NULL,NULL,NULL,'2025-04-18 04:55:16','2025-04-18 04:55:16'),(5,7,'same','oo',505005.00,'3123',NULL,'Core',NULL,NULL,NULL,NULL,NULL,'2025-04-18 04:55:16','2025-04-18 04:55:16'),(6,11,'EWAN','WALA PA',50000.00,'5000',NULL,'Strategic',NULL,NULL,NULL,NULL,NULL,'2025-04-18 06:16:16','2025-04-18 06:16:16'),(7,11,'HI MAYOR!','DIKO ALAM',100000.00,'3000',NULL,'Core',NULL,NULL,NULL,NULL,NULL,'2025-04-18 06:16:16','2025-04-18 06:16:16');
/*!40000 ALTER TABLE `dpcr_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idp_entries`
--

DROP TABLE IF EXISTS `idp_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `idp_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL,
  `development_needs` text COLLATE utf8mb4_general_ci NOT NULL,
  `development_interventions` text COLLATE utf8mb4_general_ci NOT NULL,
  `target_competency_level` int DEFAULT NULL,
  `success_indicators` text COLLATE utf8mb4_general_ci NOT NULL,
  `timeline_start` date DEFAULT NULL,
  `timeline_end` date DEFAULT NULL,
  `resources_needed` text COLLATE utf8mb4_general_ci,
  `status` enum('Not Started','In Progress','Completed') COLLATE utf8mb4_general_ci DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `idp_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idp_entries`
--

LOCK TABLES `idp_entries` WRITE;
/*!40000 ALTER TABLE `idp_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `idp_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ipcr_entries`
--

DROP TABLE IF EXISTS `ipcr_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipcr_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL,
  `major_output` text COLLATE utf8mb4_general_ci NOT NULL,
  `success_indicators` text COLLATE utf8mb4_general_ci NOT NULL,
  `actual_accomplishments` text COLLATE utf8mb4_general_ci,
  `q_rating` decimal(5,2) DEFAULT NULL,
  `e_rating` decimal(5,2) DEFAULT NULL,
  `t_rating` decimal(5,2) DEFAULT NULL,
  `final_rating` decimal(5,2) DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_general_ci,
  `category` enum('Strategic','Core','Support') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Core',
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `ipcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipcr_entries`
--

LOCK TABLES `ipcr_entries` WRITE;
/*!40000 ALTER TABLE `ipcr_entries` DISABLE KEYS */;
INSERT INTO `ipcr_entries` VALUES (1,2,'Course Materials Development','Develop 3 new lab exercises','Developed 4 new lab exercises with comprehensive guides',4.00,5.00,4.00,4.00,NULL,'Core',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,2,'Student Mentorship','Mentor at least 5 students','Mentored 7 students, with 2 winning in competitions',5.00,5.00,5.00,5.00,NULL,'Strategic',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,5,'Technology Workshop','Conduct 2 workshops for students','Conducted 1 workshop with 30 attendees',3.00,4.00,3.00,3.00,NULL,'Support',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(4,8,'HAHAHAHAHA','HAHAHAH','OK NA TO',4.00,3.00,5.00,4.00,'OK','Core',NULL,'2025-04-18 04:55:58','2025-04-18 04:55:58'),(5,8,'AHHAHAHAH','2','OK NA TO',5.00,5.00,5.00,5.00,'OK','Core',NULL,'2025-04-18 04:55:58','2025-04-18 04:55:58'),(6,8,'sample','2','OK NA TO',1.00,2.00,1.00,1.00,'GG','Core',NULL,'2025-04-18 04:55:58','2025-04-18 04:55:58'),(7,9,'HAHAHAHAHA','HAHAHAH','OK NA TO',4.00,3.00,5.00,4.00,'OK','Core',NULL,'2025-04-18 04:56:10','2025-04-18 04:56:10'),(8,9,'AHHAHAHAH','2','OK NA TO',5.00,5.00,5.00,5.00,'OK','Core',NULL,'2025-04-18 04:56:10','2025-04-18 04:56:10'),(9,9,'sample','2','OK NA TO',1.00,2.00,1.00,1.00,'GG','Core',NULL,'2025-04-18 04:56:10','2025-04-18 04:56:10'),(10,10,'HAHAHAHAHA','HAHAHAH','OK NA TO',4.00,3.00,5.00,4.00,'OK','Core',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(11,10,'AHHAHAHAH','2','OK NA TO',5.00,5.00,5.00,5.00,'OK','Core',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(12,10,'sample','2','OK NA TO',1.00,2.00,1.00,1.00,'GG','Core',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(13,10,'HAHAHAHHAHA','OKOKOK','OK NA TO',1.00,4.00,2.00,2.00,'NO','Support',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(14,10,'OK NA TO','OK NA TO','OK NA TO',1.00,1.00,3.00,1.00,'OK NA TO','Support',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13');
/*!40000 ALTER TABLE `ipcr_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'New form submission requires your review','view_record.php?id=13',0,'2025-10-13 17:44:40'),(2,10,'New form submission requires your review','view_record.php?id=14',0,'2025-10-13 17:46:49'),(3,69,'Your IPCR for Q4 2025 has been APPROVED','view_record.php?id=14',0,'2025-10-13 18:00:27'),(4,69,'Your IDP for Annual 2025 has been APPROVED','view_record.php?id=15',0,'2025-10-13 18:07:25');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pds_records`
--

DROP TABLE IF EXISTS `pds_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pds_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `pds_data` json NOT NULL COMMENT 'Stores the complete PDS data as a JSON object',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pds_records`
--

LOCK TABLES `pds_records` WRITE;
/*!40000 ALTER TABLE `pds_records` DISABLE KEYS */;
INSERT INTO `pds_records` VALUES (1,69,'{\"children\": [], \"references\": {\"1\": {\"tel\": \"1231\", \"name\": \"asd\", \"address\": \"zxczxczxczxc\"}, \"2\": {\"tel\": \"1231\", \"name\": \"qwe\", \"address\": \"zxczxczxc\"}, \"3\": {\"tel\": \"123\", \"name\": \"zxc\", \"address\": \"zxcz\"}}, \"conditional\": {\"q34\": \"No\", \"q36\": \"Yes\", \"q37\": \"Yes\", \"q38\": \"No\", \"q39\": \"Yes\", \"q40\": \"Yes\", \"q41\": \"Yes\", \"q35a\": \"Yes\", \"q35b\": \"Yes\", \"q42a\": \"Yes\", \"q42b\": \"No\", \"q42c\": \"No\", \"q34_details\": \"\", \"q36_details\": \"asdasd\", \"q37_details\": \"blind\", \"q38_details\": \"\", \"q39_details\": \"idk\", \"q40_details\": \"olsoidk\", \"q41_details\": \"asd\", \"q35a_details\": \"By Birth\", \"q35b_details\": \"qweq\", \"q42a_details\": \"asd2312\", \"q42b_details\": \"\", \"q42c_details\": \"\"}, \"eligibility\": [], \"learning_dev\": [], \"other_skills\": [], \"personal_info\": {\"dob\": \"2025-10-08\", \"pob\": \"qwe\", \"sex\": \"Male\", \"height\": \"123\", \"sss_no\": \"asd\", \"tin_no\": \"asd\", \"weight\": \"213\", \"gsis_id\": \"asd\", \"surname\": \"qwe\", \"mobile_no\": \"1223123123123123\", \"blood_type\": \"asadasd\", \"first_name\": \"qwe\", \"pagibig_id\": \"\", \"tel_no_res\": \"\", \"middle_name\": \"qwe\", \"civil_status\": \"Married\", \"res_zip_code\": \"9090\", \"email_address\": \"weqwdaw@asgasd.com\", \"perm_zip_code\": \"0909\", \"philhealth_no\": \"asd\", \"permanent_address\": \"qwe\", \"agency_employee_no\": \"\", \"residential_address\": \"qwe\"}, \"voluntary_work\": [], \"work_experience\": [], \"family_background\": {\"father_surname\": \"qweqwe\", \"mother_surname\": \"eqweqwe\", \"spouse_surname\": \"\", \"spouse_employer\": \"\", \"father_first_name\": \"qweqeq\", \"mother_first_name\": \"eqweqeq\", \"spouse_first_name\": \"\", \"spouse_occupation\": \"\", \"father_middle_name\": \"e\", \"mother_middle_name\": \"q\", \"spouse_middle_name\": \"\"}, \"membership_in_assoc\": [], \"educational_background\": [], \"non_academic_distinctions\": []}','2025-10-12 15:36:07','2025-10-13 20:03:49');
/*!40000 ALTER TABLE `pds_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `department_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'BSCS - Bachelor of Science in Computer Science',1,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,'BSIS - Bachelor of Science in Information Systems',1,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,'BLIS - Bachelor of Library and Information Science',1,'2025-04-18 04:51:59','2025-04-18 04:51:59');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `records`
--

DROP TABLE IF EXISTS `records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `form_type` enum('DPCR','IPCR','IDP') COLLATE utf8mb4_general_ci NOT NULL,
  `period` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_general_ci,
  `status` enum('Draft','Pending','Approved','Rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Draft',
  `date_submitted` timestamp NULL DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `computation_type` enum('Type1','Type2') COLLATE utf8mb4_general_ci DEFAULT 'Type1',
  `feedback` text COLLATE utf8mb4_general_ci,
  `remarks` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `records_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `records`
--

LOCK TABLES `records` WRITE;
/*!40000 ALTER TABLE `records` DISABLE KEYS */;
INSERT INTO `records` VALUES (1,2,'DPCR','Q1 2023',NULL,'Approved','2023-03-15 02:00:00',NULL,NULL,NULL,'Type1',NULL,NULL),(2,2,'IPCR','Q1 2023',NULL,'Approved','2023-03-20 03:30:00',NULL,NULL,NULL,'Type1',NULL,NULL),(5,6,'IPCR','Q2 2023',NULL,'Pending','2023-06-10 08:45:00',NULL,NULL,NULL,'Type1',NULL,NULL),(6,2,'IPCR','Q3 2023',NULL,'Draft',NULL,NULL,NULL,NULL,'Type1',NULL,NULL),(7,9,'DPCR','Q1 2025',NULL,'Pending','2025-04-17 22:55:16',NULL,NULL,NULL,'Type1',NULL,NULL),(8,16,'IPCR','Q1 2025','{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"},{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1',NULL,NULL),(9,16,'IPCR','Q1 2025','{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"},{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1',NULL,NULL),(10,16,'IPCR','Q1 2025','{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"HAHAHAHHAHA\",\"indicators\":\"OKOKOK\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"4\",\"t\":\"2\",\"a\":\"2.33\",\"remarks\":\"NO\"},{\"mfo\":\"OK NA TO\",\"indicators\":\"OK NA TO\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"1\",\"t\":\"3\",\"a\":\"1.67\",\"remarks\":\"OK NA TO\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1',NULL,NULL),(11,9,'DPCR','Q1 2025',NULL,'Approved','2025-04-18 00:16:16',17,'2025-04-18 06:17:02','pwede na nak','Type1',NULL,NULL),(13,69,'IPCR','Q4 2025','{\"period\":\"Q4 2025\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"indicators\":\"qwe\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"},{\"mfo\":\"qwe\",\"indicators\":\"qwe\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"},{\"mfo\":\"qwe\",\"indicators\":\"qwe\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"eqwe\",\"indicators\":\"weq\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"},{\"mfo\":\"eqwe\",\"indicators\":\"weq\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"},{\"mfo\":\"eqwe\",\"indicators\":\"weq\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"qwe\",\"indicators\":\"qwe\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"5\",\"t\":\"1\",\"a\":\"2.33\",\"remarks\":\"\"},{\"mfo\":\"qwe\",\"indicators\":\"qwe\",\"accomplishments\":\"qwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"5\",\"a\":\"2.33\",\"remarks\":\"\"}],\"final_rating\":\"1.00\",\"strategic_average\":\"1.00\",\"core_average\":\"1.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Poor\"}','Pending','2025-10-13 17:44:40',NULL,NULL,NULL,'Type1',NULL,NULL),(14,69,'IPCR','Q4 2025','{\"period\":\"Q4 2025\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\"},{\"mfo\":\"qwe\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"2\",\"supervisor_a\":\"1.33\"},{\"mfo\":\"q\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"2\",\"supervisor_a\":\"1.33\"}],\"core_functions\":[{\"mfo\":\"q\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\"},{\"mfo\":\"q\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\"},{\"mfo\":\"q\",\"indicators\":\"w\",\"accomplishments\":\"ee\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\"}],\"support_functions\":[{\"mfo\":\"q\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\"},{\"mfo\":\"q\",\"indicators\":\"w\",\"accomplishments\":\"e\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\"}],\"final_rating\":\"1.00\",\"strategic_average\":\"1.00\",\"core_average\":\"1.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Poor\",\"supervisor_strategic_average\":\"1.66\",\"supervisor_core_average\":\"2.33\",\"supervisor_support_average\":\"2.33\",\"supervisor_final_rating\":\"2.03\",\"supervisor_rating_interpretation\":\"Unsatisfactory\"}','Approved','2025-10-13 17:46:49',10,'2025-10-13 18:00:27',NULL,'Type1','qwe','wqe'),(15,69,'IDP','Annual 2025','{\"period\":\"Annual 2025\",\"professional_development\":[{\"goals\":\"asd\",\"competencies\":\"asd\",\"actions\":\"sad\",\"timeline\":\"wqe\",\"status\":\"Completed\"},{\"goals\":\"dsa\",\"competencies\":\"das\",\"actions\":\"sda\",\"timeline\":\"wqe\",\"status\":\"Completed\"}],\"personal_development\":[{\"goals\":\"dsa\",\"competencies\":\"dsadas\",\"actions\":\"asd\",\"timeline\":\"qwe\",\"status\":\"Completed\"}]}','Approved',NULL,10,'2025-10-13 18:07:25',NULL,'Type1','asd','asd');
/*!40000 ALTER TABLE `records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'strategic_weight_type1','45','Strategic category weight for Type1 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,'core_weight_type1','55','Core category weight for Type1 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,'strategic_weight_type2','45','Strategic category weight for Type2 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(4,'core_weight_type2','45','Core category weight for Type2 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(5,'support_weight_type2','10','Support category weight for Type2 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(6,'quality_weight','35','Quality criteria weight for IPCR (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(7,'efficiency_weight','35','Efficiency criteria weight for IPCR (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(8,'timeliness_weight','30','Timeliness criteria weight for IPCR (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(9,'dpcr_computation_type','Type1','DPCR computation type: Type1 = Strategic (45%) and Core (55%), Type2 = Strategic (45%), Core (45%), and Support (10%)','2025-04-18 04:51:59','2025-04-18 04:51:59');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','president','department_head','regular_employee','user') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `department_id` int DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_department` (`department_id`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Santos','asantos@gmail.com','qwe','admin',1,NULL,NULL,'2025-04-18 04:51:59','2025-10-13 17:26:28'),(2,'Arnie Santos','arniesantos@cca.edu.ph','qwe','regular_employee',1,NULL,NULL,'2025-04-18 04:51:59','2025-10-13 17:27:44'),(5,'HR Manager','hrmanager@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',2,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(6,'Faculty Member','faculty@cca.edu.ph','$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea','regular_employee',1,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(7,'DR. CAROLINA A. SARMIENTO','carolinasarmiento@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','president',6,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(8,'MS. AMOR L. BARBA','amorbarba@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',7,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(9,'MS. MAIKA V. GARBES','maikagarbes@cca.edu.ph','qwe','department_head',8,NULL,'ea0a7298bf7433bf30e7ab2390577223cbad5f7f73a51342de01782b1128d7b6','2025-04-18 04:51:59','2025-10-13 17:40:28'),(10,'DR. LEVITA DE GUZMAN','levitaguzman@cca.edu.ph','qwe','department_head',9,NULL,NULL,'2025-04-18 04:51:59','2025-10-13 17:47:02'),(11,'MS. MARIA TERESSA G. LAPUZ','mariateressalapuz@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',10,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(12,'MR. LESSANDRO YUCON','lessandroyucon@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',11,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(13,'MS. JASMINE ANGELICA MARIE CANLAS','jasmineangelicacanlas@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',12,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(14,'DR. RHENAN ESTACIO','rhenanestacio@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',13,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(15,'ADMIN NGA KOOO','admin@cca.edu.ph','admin123','admin',1,'uploads/avatars/7_6801c5d913a3f.jpg','','2025-04-18 02:34:36','2025-04-18 03:24:09'),(16,'Benedict Ortiz','jortiz@cca.edu.ph','$2y$10$ucBn/VaiNqycSyxaxSYGrOGkFSWuaXicSVT/Ftcmu8XQQG9pjHxWm','regular_employee',1,'uploads/avatars/9_6801cd09df7d1.jpg','','2025-04-18 02:43:16','2025-04-18 03:54:49'),(17,'Antonio Luna','henlun@cca.edu.ph','$2y$10$MSvv96EDreka7VH.O6wQqe.Sdu/FE2aLA2idI59r6qzUpocgQroLG','president',2,'uploads/avatars/12_6801c17a2cd64.jpg','','2025-04-18 03:04:29','2025-04-18 03:05:30'),(69,'test','test@test.com','qweqwe','regular_employee',9,'uploads/avatars/9_6801cd09df7d1.jpg','','2025-04-18 02:43:16','2025-10-13 17:45:49');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-14  4:17:13
