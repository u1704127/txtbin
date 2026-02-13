-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 13, 2026 at 11:25 PM
-- Server version: 10.11.15-MariaDB-cll-lve
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ferosite_paste_url`
--

-- --------------------------------------------------------

--
-- Table structure for table `passkey_table`
--

CREATE TABLE `passkey_table` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `passkey` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `passkey_table`
--

INSERT INTO `passkey_table` (`id`, `username`, `passkey`) VALUES
(1, 'Admin@123', '0e7517141fb53f21ee439b355b5a1d0a');

-- --------------------------------------------------------

--
-- Table structure for table `value_table`
--

CREATE TABLE `value_table` (
  `id` int(11) NOT NULL,
  `passkey_id` int(11) NOT NULL,
  `key_value` varchar(650) DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `passkey_table`
--
ALTER TABLE `passkey_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `value_table`
--
ALTER TABLE `value_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_passkey_text` (`passkey_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `passkey_table`
--
ALTER TABLE `passkey_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `value_table`
--
ALTER TABLE `value_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `value_table`
--
ALTER TABLE `value_table`
  ADD CONSTRAINT `fk_passkey_text` FOREIGN KEY (`passkey_id`) REFERENCES `passkey_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
