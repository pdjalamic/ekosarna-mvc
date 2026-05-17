-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 16, 2026 at 01:09 PM
-- Server version: 10.6.25-MariaDB-cll-lve
-- PHP Version: 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ekosarna_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `raspored_stavke`
--

CREATE TABLE `raspored_stavke` (
  `id` int(10) UNSIGNED NOT NULL,
  `dan_id` int(10) UNSIGNED NOT NULL,
  `gradiliste_id` int(10) UNSIGNED DEFAULT NULL,
  `opis` text DEFAULT NULL,
  `redosled` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `raspored_stavke`
--

INSERT INTO `raspored_stavke` (`id`, `dan_id`, `gradiliste_id`, `opis`, `redosled`) VALUES
(32, 43, NULL, 'Tim building (čitaj alkoholisanje)', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `raspored_stavke`
--
ALTER TABLE `raspored_stavke`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dan_id` (`dan_id`),
  ADD KEY `gradiliste_id` (`gradiliste_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `raspored_stavke`
--
ALTER TABLE `raspored_stavke`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `raspored_stavke`
--
ALTER TABLE `raspored_stavke`
  ADD CONSTRAINT `raspored_stavke_ibfk_1` FOREIGN KEY (`dan_id`) REFERENCES `raspored_dani` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raspored_stavke_ibfk_2` FOREIGN KEY (`gradiliste_id`) REFERENCES `gradilista` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
