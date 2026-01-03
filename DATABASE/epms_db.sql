-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2025 at 08:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `epms_db`
--

-- --------------------------------------------------------

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist to avoid errors
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `idp_entries`;
DROP TABLE IF EXISTS `ipcr_entries`;
DROP TABLE IF EXISTS `dpcr_entries`;
DROP TABLE IF EXISTS `programs`;
DROP TABLE IF EXISTS `records`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `departments`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `head_id`, `created_at`, `updated_at`) VALUES
(1, 'ICSLIS - Institute of Computing Studies and Library Information Sciences', 'Department for computing and library science programs', NULL, '2025-04-18 04:51:59', '2025-04-18 04:53:17'),
(2, 'Human Resources', 'Manages employee relations and workforce planning', 5, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'Finance', 'Handles financial operations and budgeting', NULL, '2025-04-18 04:51:59', '2025-04-18 04:52:48'),
(4, 'Academic Affairs', 'Oversees academic programs and policies', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(5, 'Student Services', 'Provides support services for students', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(6, 'Academic Affairs', 'Oversees academic programs, faculty, and educational policies', 7, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'Institute of Business and Management', 'Department for business programs and management education', 8, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'Institute of Computing Studies and Library Information Science', 'Department for computing and library science programs', 9, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(9, 'Institute of Education, Arts and Sciences', 'Department for education, arts and sciences programs', 10, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(10, 'Student Affairs and Services Office', 'Provides support services for students', 11, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(11, 'Admissions and Registrar\'s Office', 'Manages student admissions and registration', 12, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(12, 'College Library', 'Provides library resources and services', 13, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(13, 'College Guidance and Formation Office', 'Provides guidance and counseling services', 14, '2025-04-18 04:51:59', '2025-04-18 04:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `dpcr_entries`
--

CREATE TABLE `dpcr_entries` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `major_output` text NOT NULL,
  `success_indicators` text NOT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `accountable` text DEFAULT NULL,
  `accomplishments` text DEFAULT NULL,
  `category` enum('Strategic','Core','Support') NOT NULL DEFAULT 'Core',
  `q1_rating` decimal(5,2) DEFAULT NULL,
  `q2_rating` decimal(5,2) DEFAULT NULL,
  `q3_rating` decimal(5,2) DEFAULT NULL,
  `q4_rating` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dpcr_entries`
--

INSERT INTO `dpcr_entries` (`id`, `record_id`, `major_output`, `success_indicators`, `budget`, `accountable`, `accomplishments`, `category`, `q1_rating`, `q2_rating`, `q3_rating`, `q4_rating`, `weight`, `created_at`, `updated_at`) VALUES
(1, 1, 'Curriculum Development', 'Update CS curriculum by Q2', 50000.00, 'ICSLIS Department', 'Curriculum updated ahead of schedule', 'Strategic', 5.00, 0.00, 0.00, 0.00, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 1, 'Faculty Training Program', 'Train faculty on new technologies', 75000.00, 'ICSLIS Department', 'Training completed with 95% attendance', 'Core', 4.00, 0.00, 0.00, 0.00, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(4, 7, 'Sample', 'wa', 10000.00, '32', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-04-18 04:55:16', '2025-04-18 04:55:16'),
(5, 7, 'same', 'oo', 505005.00, '3123', NULL, 'Core', NULL, NULL, NULL, NULL, NULL, '2025-04-18 04:55:16', '2025-04-18 04:55:16'),
(6, 11, 'EWAN', 'WALA PA', 50000.00, '5000', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-04-18 06:16:16', '2025-04-18 06:16:16'),
(7, 11, 'HI MAYOR!', 'DIKO ALAM', 100000.00, '3000', NULL, 'Core', NULL, NULL, NULL, NULL, NULL, '2025-04-18 06:16:16', '2025-04-18 06:16:16');

-- --------------------------------------------------------

--
-- Table structure for table `idp_entries`
--

CREATE TABLE `idp_entries` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `development_needs` text NOT NULL,
  `development_interventions` text NOT NULL,
  `target_competency_level` int(11) DEFAULT NULL,
  `success_indicators` text NOT NULL,
  `timeline_start` date DEFAULT NULL,
  `timeline_end` date DEFAULT NULL,
  `resources_needed` text DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ipcr_entries`
--

CREATE TABLE `ipcr_entries` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `major_output` text NOT NULL,
  `success_indicators` text NOT NULL,
  `actual_accomplishments` text DEFAULT NULL,
  `q_rating` decimal(5,2) DEFAULT NULL,
  `e_rating` decimal(5,2) DEFAULT NULL,
  `t_rating` decimal(5,2) DEFAULT NULL,
  `final_rating` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `category` enum('Strategic','Core','Support') NOT NULL DEFAULT 'Core',
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ipcr_entries`
--

INSERT INTO `ipcr_entries` (`id`, `record_id`, `major_output`, `success_indicators`, `actual_accomplishments`, `q_rating`, `e_rating`, `t_rating`, `final_rating`, `remarks`, `category`, `weight`, `created_at`, `updated_at`) VALUES
(1, 2, 'Course Materials Development', 'Develop 3 new lab exercises', 'Developed 4 new lab exercises with comprehensive guides', 4.00, 5.00, 4.00, 4.00, NULL, 'Core', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 2, 'Student Mentorship', 'Mentor at least 5 students', 'Mentored 7 students, with 2 winning in competitions', 5.00, 5.00, 5.00, 5.00, NULL, 'Strategic', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 5, 'Technology Workshop', 'Conduct 2 workshops for students', 'Conducted 1 workshop with 30 attendees', 3.00, 4.00, 3.00, 3.00, NULL, 'Support', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(4, 8, 'HAHAHAHAHA', 'HAHAHAH', 'OK NA TO', 4.00, 3.00, 5.00, 4.00, 'OK', 'Core', NULL, '2025-04-18 04:55:58', '2025-04-18 04:55:58'),
(5, 8, 'AHHAHAHAH', '2', 'OK NA TO', 5.00, 5.00, 5.00, 5.00, 'OK', 'Core', NULL, '2025-04-18 04:55:58', '2025-04-18 04:55:58'),
(6, 8, 'sample', '2', 'OK NA TO', 1.00, 2.00, 1.00, 1.00, 'GG', 'Core', NULL, '2025-04-18 04:55:58', '2025-04-18 04:55:58'),
(7, 9, 'HAHAHAHAHA', 'HAHAHAH', 'OK NA TO', 4.00, 3.00, 5.00, 4.00, 'OK', 'Core', NULL, '2025-04-18 04:56:10', '2025-04-18 04:56:10'),
(8, 9, 'AHHAHAHAH', '2', 'OK NA TO', 5.00, 5.00, 5.00, 5.00, 'OK', 'Core', NULL, '2025-04-18 04:56:10', '2025-04-18 04:56:10'),
(9, 9, 'sample', '2', 'OK NA TO', 1.00, 2.00, 1.00, 1.00, 'GG', 'Core', NULL, '2025-04-18 04:56:10', '2025-04-18 04:56:10'),
(10, 10, 'HAHAHAHAHA', 'HAHAHAH', 'OK NA TO', 4.00, 3.00, 5.00, 4.00, 'OK', 'Core', NULL, '2025-04-18 06:10:13', '2025-04-18 06:10:13'),
(11, 10, 'AHHAHAHAH', '2', 'OK NA TO', 5.00, 5.00, 5.00, 5.00, 'OK', 'Core', NULL, '2025-04-18 06:10:13', '2025-04-18 06:10:13'),
(12, 10, 'sample', '2', 'OK NA TO', 1.00, 2.00, 1.00, 1.00, 'GG', 'Core', NULL, '2025-04-18 06:10:13', '2025-04-18 06:10:13'),
(13, 10, 'HAHAHAHHAHA', 'OKOKOK', 'OK NA TO', 1.00, 4.00, 2.00, 2.00, 'NO', 'Support', NULL, '2025-04-18 06:10:13', '2025-04-18 06:10:13'),
(14, 10, 'OK NA TO', 'OK NA TO', 'OK NA TO', 1.00, 1.00, 3.00, 1.00, 'OK NA TO', 'Support', NULL, '2025-04-18 06:10:13', '2025-04-18 06:10:13');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'BSCS - Bachelor of Science in Computer Science', 1, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 'BSIS - Bachelor of Science in Information Systems', 1, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'BLIS - Bachelor of Library and Information Science', 1, '2025-04-18 04:51:59', '2025-04-18 04:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `form_type` enum('DPCR','IPCR','IDP') NOT NULL,
  `period` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `date_submitted` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `computation_type` enum('Type1','Type2') DEFAULT 'Type1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `user_id`, `form_type`, `period`, `content`, `status`, `date_submitted`, `reviewed_by`, `date_reviewed`, `comments`, `computation_type`) VALUES
(1, 2, 'DPCR', 'Q1 2023', NULL, 'Approved', '2023-03-15 02:00:00', NULL, NULL, NULL, 'Type1'),
(2, 2, 'IPCR', 'Q1 2023', NULL, 'Approved', '2023-03-20 03:30:00', NULL, NULL, NULL, 'Type1'),
(5, 6, 'IPCR', 'Q2 2023', NULL, 'Pending', '2023-06-10 08:45:00', NULL, NULL, NULL, 'Type1'),
(6, 2, 'IPCR', 'Q3 2023', NULL, 'Draft', NULL, NULL, NULL, NULL, 'Type1'),
(7, 9, 'DPCR', 'Q1 2025', NULL, 'Pending', '2025-04-17 22:55:16', NULL, NULL, NULL, 'Type1'),
(8, 16, 'IPCR', 'Q1 2025', '{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"},{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1'),
(9, 16, 'IPCR', 'Q1 2025', '{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"},{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1'),
(10, 16, 'IPCR', 'Q1 2025', '{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"HAHAHAHHAHA\",\"indicators\":\"OKOKOK\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"4\",\"t\":\"2\",\"a\":\"2.33\",\"remarks\":\"NO\"},{\"mfo\":\"OK NA TO\",\"indicators\":\"OK NA TO\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"1\",\"t\":\"3\",\"a\":\"1.67\",\"remarks\":\"OK NA TO\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1'),
(11, 9, 'DPCR', 'Q1 2025', NULL, 'Approved', '2025-04-18 00:16:16', 17, '2025-04-18 06:17:02', 'pwede na nak', 'Type1');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'strategic_weight_type1', '45', 'Strategic category weight for Type1 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 'core_weight_type1', '55', 'Core category weight for Type1 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'strategic_weight_type2', '45', 'Strategic category weight for Type2 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(4, 'core_weight_type2', '45', 'Core category weight for Type2 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(5, 'support_weight_type2', '10', 'Support category weight for Type2 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(6, 'quality_weight', '35', 'Quality criteria weight for IPCR (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'efficiency_weight', '35', 'Efficiency criteria weight for IPCR (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'timeliness_weight', '30', 'Timeliness criteria weight for IPCR (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(9, 'dpcr_computation_type', 'Type1', 'DPCR computation type: Type1 = Strategic (45%) and Core (55%), Type2 = Strategic (45%), Core (45%), and Support (10%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','president','department_head','regular_employee','user') NOT NULL DEFAULT 'user',
  `department_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department_id`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Santos', 'asantos@gmail.com', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'admin', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 'Arnie Santos', 'arniesantos@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'regular_employee', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(5, 'HR Manager', 'hrmanager@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 2, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(6, 'Faculty Member', 'faculty@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'regular_employee', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'DR. CAROLINA A. SARMIENTO', 'carolinasarmiento@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'president', 6, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'MS. AMOR L. BARBA', 'amorbarba@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 7, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(9, 'MS. MAIKA V. GARBES', 'maikagarbes@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 8, NULL, 'ea0a7298bf7433bf30e7ab2390577223cbad5f7f73a51342de01782b1128d7b6', '2025-04-18 04:51:59', '2025-04-18 04:54:30'),
(10, 'DR. LEVITA DE GUZMAN', 'levitaguzman@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 9, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(11, 'MS. MARIA TERESSA G. LAPUZ', 'mariateressalapuz@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 10, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(12, 'MR. LESSANDRO YUCON', 'lessandroyucon@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 11, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(13, 'MS. JASMINE ANGELICA MARIE CANLAS', 'jasmineangelicacanlas@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 12, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(14, 'DR. RHENAN ESTACIO', 'rhenanestacio@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 13, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(15, 'ADMIN NGA KOOO', 'admin@cca.edu.ph', 'admin123', 'admin', 1, 'uploads/avatars/7_6801c5d913a3f.jpg', '', '2025-04-18 02:34:36', '2025-04-18 03:24:09'),
(16, 'Benedict Ortiz', 'jortiz@cca.edu.ph', '$2y$10$ucBn/VaiNqycSyxaxSYGrOGkFSWuaXicSVT/Ftcmu8XQQG9pjHxWm', 'regular_employee', 1, 'uploads/avatars/9_6801cd09df7d1.jpg', '', '2025-04-18 02:43:16', '2025-04-18 03:54:49'),
(69, 'test', 'test@test.com', 'qweqwe', 'regular_employee', 1, 'uploads/avatars/9_6801cd09df7d1.jpg', '', '2025-04-18 02:43:16', '2025-04-18 03:54:49'),
(17, 'Antonio Luna', 'henlun@cca.edu.ph', '$2y$10$MSvv96EDreka7VH.O6wQqe.Sdu/FE2aLA2idI59r6qzUpocgQroLG', 'president', 2, 'uploads/avatars/12_6801c17a2cd64.jpg', '', '2025-04-18 03:04:29', '2025-04-18 03:05:30');

--
-- Table structure for table `pds_records`
--

CREATE TABLE `pds_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `pds_data` JSON NOT NULL COMMENT 'Stores the complete PDS data as a JSON object',
  `submitted_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `head_id` (`head_id`);

--
-- Indexes for table `dpcr_entries`
--
ALTER TABLE `dpcr_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `idp_entries`
--
ALTER TABLE `idp_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `dpcr_entries`
--
ALTER TABLE `dpcr_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `idp_entries`
--
ALTER TABLE `idp_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dpcr_entries`
--
ALTER TABLE `dpcr_entries`
  ADD CONSTRAINT `dpcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idp_entries`
--
ALTER TABLE `idp_entries`
  ADD CONSTRAINT `idp_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  ADD CONSTRAINT `ipcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `records`
--
ALTER TABLE `records`
  ADD CONSTRAINT `records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `records_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
