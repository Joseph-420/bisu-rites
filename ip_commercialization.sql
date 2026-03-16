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
-- Table structure for table `ip_commercialization`
--

CREATE TABLE `ip_commercialization` (
  `comm_id` int(11) NOT NULL,
  `ip_id` int(11) NOT NULL,
  `request_type` enum('Technology Adopter Search','IP Valuation','Licensing Advice','Online Promotion','Other') NOT NULL,
  `status` enum('Pending','Processing','Completed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `request_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ip_commercialization`
--
ALTER TABLE `ip_commercialization`
  ADD PRIMARY KEY (`comm_id`),
  ADD KEY `ip_id` (`ip_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ip_commercialization`
--
ALTER TABLE `ip_commercialization`
  MODIFY `comm_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ip_commercialization`
--
ALTER TABLE `ip_commercialization`
  ADD CONSTRAINT `ip_commercialization_ibfk_1` FOREIGN KEY (`ip_id`) REFERENCES `ip_assets` (`ip_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
