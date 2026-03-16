-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2026 at 01:55 AM
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
-- Table structure for table `ip_assets`
--

CREATE TABLE `ip_assets` (
  `ip_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `ip_type` enum('Patent','Utility Model','Industrial Design','Trademark','Copyright') NOT NULL,
  `application_number` varchar(100) DEFAULT NULL,
  `status` enum('Disclosure Submitted','Drafting','Filed','Registered','Refused','Expired') DEFAULT 'Disclosure Submitted',
  `filing_date` date DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `is_externally_funded` tinyint(1) DEFAULT 0,
  `funding_agency` varchar(255) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ip_assets`
--
ALTER TABLE `ip_assets`
  ADD PRIMARY KEY (`ip_id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ip_assets`
--
ALTER TABLE `ip_assets`
  MODIFY `ip_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ip_assets`
--
ALTER TABLE `ip_assets`
  ADD CONSTRAINT `ip_assets_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
