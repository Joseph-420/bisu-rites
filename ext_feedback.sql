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
-- Table structure for table `ext_feedback`
--

CREATE TABLE `ext_feedback` (
  `feedback_id` int(11) NOT NULL,
  `ext_id` int(11) NOT NULL,
  `participant_name` varchar(100) DEFAULT NULL,
  `rating_quality` int(11) DEFAULT NULL CHECK (`rating_quality` between 1 and 5),
  `rating_relevance` int(11) DEFAULT NULL CHECK (`rating_relevance` between 1 and 5),
  `comments` text DEFAULT NULL,
  `date_evaluated` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ext_feedback`
--
ALTER TABLE `ext_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `ext_id` (`ext_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ext_feedback`
--
ALTER TABLE `ext_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ext_feedback`
--
ALTER TABLE `ext_feedback`
  ADD CONSTRAINT `ext_feedback_ibfk_1` FOREIGN KEY (`ext_id`) REFERENCES `ext_projects` (`ext_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
