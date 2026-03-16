-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2026 at 01:54 AM
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
-- Database: `bisu_rites`
--

-- --------------------------------------------------------

--
-- Table structure for table `rd_projects`
--

CREATE TABLE `rd_projects` (
  `rd_id` int(11) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `abstract` text DEFAULT NULL,
  `status` enum('Proposed','Ongoing','Completed','Published','Deferred') DEFAULT 'Proposed',
  `budget` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `college_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rd_projects`
--

INSERT INTO `rd_projects` (`rd_id`, `project_title`, `abstract`, `status`, `budget`, `start_date`, `end_date`, `college_id`) VALUES
(1, 'Nutriwatch', 'An AI-Assisted Nutrition Monitoring for Bohol Public Primary Schools', 'Ongoing', 500000.00, '2025-10-20', '2027-03-20', 3),
(2, 'Nutriwatch', 'abstract', 'Proposed', 0.01, '2026-02-02', '2026-02-23', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rd_projects`
--
ALTER TABLE `rd_projects`
  ADD PRIMARY KEY (`rd_id`),
  ADD KEY `college_id` (`college_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rd_projects`
--
ALTER TABLE `rd_projects`
  MODIFY `rd_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rd_projects`
--
ALTER TABLE `rd_projects`
  ADD CONSTRAINT `rd_projects_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`college_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
