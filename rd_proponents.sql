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
-- Table structure for table `rd_proponents`
--

CREATE TABLE `rd_proponents` (
  `id` int(11) NOT NULL,
  `rd_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_role` enum('Main Author','Co-Author','Adviser','Panel') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rd_proponents`
--
ALTER TABLE `rd_proponents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rd_id` (`rd_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rd_proponents`
--
ALTER TABLE `rd_proponents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rd_proponents`
--
ALTER TABLE `rd_proponents`
  ADD CONSTRAINT `rd_proponents_ibfk_1` FOREIGN KEY (`rd_id`) REFERENCES `rd_projects` (`rd_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rd_proponents_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
