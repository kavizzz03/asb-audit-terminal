-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2026 at 06:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `asb_file_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_audit_logs`
--

CREATE TABLE `access_audit_logs` (
  `id` int(11) NOT NULL,
  `operator_name` varchar(100) NOT NULL,
  `event_time` datetime NOT NULL,
  `status` enum('AUTHORIZED','DENIED') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_audit_logs`
--

INSERT INTO `access_audit_logs` (`id`, `operator_name`, `event_time`, `status`, `ip_address`, `user_agent`) VALUES
(1, 'kavindu_dev', '2026-05-06 08:46:14', 'AUTHORIZED', '::1', NULL),
(2, 'kavindu_dev', '2026-05-06 08:57:26', 'AUTHORIZED', '::1', NULL),
(3, 'kavindu_dev', '2026-05-06 09:01:36', 'AUTHORIZED', '::1', NULL),
(4, 'Test', '2026-05-06 09:41:34', 'AUTHORIZED', '::1', NULL),
(5, 'test3', '2026-05-06 09:42:29', 'AUTHORIZED', '::1', NULL),
(6, 'kavindu_dev', '2026-05-06 10:17:21', 'AUTHORIZED', '::1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `branch_code`) VALUES
(1, 'Head office', '001'),
(2, 'panadura', '002'),
(3, 'all', '0000'),
(4, 'Glamour Gate', '004'),
(5, 'ambalangoda', '003');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`) VALUES
(1, 'Financial Reports', 'Monthly and annual financial statements 2026'),
(2, 'HR Documents', 'Employee contracts and policy updates'),
(3, 'Sale Report 2026', '2026 April Season report'),
(4, 'Standing Orders', 'Gm Orders');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `doc_date` date DEFAULT NULL,
  `doc_number` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `doc_date`, `doc_number`, `file_path`, `category_id`, `branch_id`) VALUES
(1, 'Annual Budget 2026', '2026-01-15', 'DOC-FIN-001', 'uploads/docs/flood.pdf', 1, 1),
(2, 'Staff Code of Conduct', '2026-03-10', 'DOC-HR-045', '/uploads/docs/conduct_v2.pdf', 1, 3),
(3, 'Project proposal', '2026-05-05', '134/2026/22343/df', 'uploads/docs/1778005212_Research Project Proposal 2541047.pdf', 3, 3),
(4, 'Standing oder 1', '2026-05-06', '134/2026/2233/df', 'uploads/docs/1778040516_Interview Questions for Company.pdf', 4, 2),
(5, 'standing order 2', '2026-05-05', '134/2025/2233/df', 'uploads/docs/1778040568_Community Survey Questionnaire Flood dection system 2541047.pdf', 4, 4),
(6, 'standing order 3', '2026-05-03', '134/2026/2243/df', 'uploads/docs/1778040607_BSc Project - Ethics Form 2541047.pdf', 4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Super Admin'),
(2, 'Editor'),
(3, 'BRANCH MANAGER');

-- --------------------------------------------------------

--
-- Table structure for table `role_category_access`
--

CREATE TABLE `role_category_access` (
  `role_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_category_access`
--

INSERT INTO `role_category_access` (`role_id`, `category_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 2),
(2, 3),
(3, 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `password`, `contact_number`, `email`, `role_id`, `branch_id`) VALUES
(1, 'kavindu_dev', 'Kavindu', 'admin123', '0771234567', 'kavindu@example.com', 1, 1),
(2, 'saman_staff', 'Saman Perera', 'staff456', '94719876543', 'saman@example.com', 2, 2),
(3, 'asbit', 'ASb It department', 'asb@it', '94747189893', 'it@asbfashion.com', 2, 1),
(4, 'Test', 'test Manger', '123456', '94771234567', 'test@email.com', 3, 2),
(5, 'test3', 'Test manager 2', '123456', '94711550225', 'colombomc09@gmail.com', 3, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_audit_logs`
--
ALTER TABLE `access_audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_number` (`doc_number`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `doc_date` (`doc_date`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_category_access`
--
ALTER TABLE `role_category_access`
  ADD PRIMARY KEY (`role_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_audit_logs`
--
ALTER TABLE `access_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `role_category_access`
--
ALTER TABLE `role_category_access`
  ADD CONSTRAINT `role_category_access_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `role_category_access_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
