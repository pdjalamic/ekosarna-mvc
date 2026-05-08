-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 23, 2026 at 11:34 PM
-- Server version: 10.6.24-MariaDB-cll-lve
-- PHP Version: 8.4.19

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
-- Table structure for table `admin_korisnici`
--

CREATE TABLE `admin_korisnici` (
  `id` int(10) UNSIGNED NOT NULL,
  `ime` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL DEFAULT '',
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `uloga` enum('Administrator','Operator') NOT NULL DEFAULT 'Operator',
  `aktivan` tinyint(1) NOT NULL DEFAULT 1,
  `datum_kreiranja` datetime NOT NULL DEFAULT current_timestamp(),
  `vidi_imenik` tinyint(1) NOT NULL DEFAULT 0,
  `telefon` varchar(50) NOT NULL DEFAULT '',
  `mail_pass` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_korisnici`
--

INSERT INTO `admin_korisnici` (`id`, `ime`, `email`, `username`, `password_hash`, `uloga`, `aktivan`, `datum_kreiranja`, `vidi_imenik`, `telefon`, `mail_pass`) VALUES
(1, 'Predrag Đalamić', 'predrag.djalamic@ekosarna.com', 'ekosarna', '$2y$10$UOFmWEo3IUhfB.1u6DqRXu35lxm/gRINziCqzr4ZOk0nKi2Camqmm', 'Administrator', 1, '2026-04-16 11:14:56', 0, '061 317 99 88', 'P3dj4123!'),
(2, 'Milan Jovanović', 'milan.jovanovic@ekosarna.com', 'milan', '$2y$10$9bFWgBxS.a/rWnC4umviQueziX7mPe1jAprWOsjTVWWiqXPZo8gi.', 'Administrator', 1, '2026-04-16 14:28:57', 0, '060 56 56 718', 'mikajoka123!'),
(3, 'Jelena Gojković', 'info@ekosarna.com', 'jelena', '$2y$10$QMxDzW8sWvlFA4OC6XD7K.nZpXNe0EGMGAcf3snVH4sulYNX2SBnW', 'Administrator', 1, '2026-04-16 14:30:45', 0, '069 37 31 413', 'EKOSARNA123*'),
(4, 'Aleksandar Jakimov', 'aleksandar.jakimov@ekosarna.com', 'Acko', '$2y$10$zX.djzHbb5zMbHwrmJPhDu11BsVL8i6lrp6DsM9V4flaSnK/ZMzYi', 'Operator', 1, '2026-04-20 09:26:07', 0, '0613047446', 'bilosta');

-- --------------------------------------------------------

--
-- Table structure for table `gradilista_robe`
--

CREATE TABLE `gradilista_robe` (
  `id` int(11) NOT NULL,
  `item_key` varchar(100) NOT NULL,
  `gradiliste` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gradilista_robe`
--

INSERT INTO `gradilista_robe` (`id`, `item_key`, `gradiliste`, `updated_at`) VALUES
(1, 'P-0792/26M1_1', 'Stublina kancelarija', '2026-04-23 14:31:06'),
(5, 'P-0792/26M1_2', 'Isto kao 1.', '2026-04-23 14:31:17'),
(13, 'P-0792/26M1_3', 'Stublina ako zapne moze profa da nisi na fortunu. Dorucicemo koliko treba', '2026-04-23 14:31:46'),
(24, 'P-0792/26M1_4', 'Srublina kanc', '2026-04-23 14:31:58'),
(26, 'P-0792/26M1_5', 'Stublina kanc', '2026-04-23 14:32:03'),
(27, 'R-2295/26M1_1', 'Muzej', '2026-04-23 14:34:46'),
(28, 'R-2295/26M1_2', 'Muzej', '2026-04-23 14:34:48'),
(30, 'R-2295/26M1_3', 'Muzej', '2026-04-23 14:34:52'),
(31, 'R-2295/26M1_4', 'Muzej ali treba da je doslo 5kom-Peđa: DOŠLO JE 10 komada. I na otpremnici je 10. Ovo je greška koju sam primetio i ispravio na papiru a ne i ovde', '2026-04-23 16:36:04'),
(34, 'R-2295/26M1_5', 'Muzej', '2026-04-23 14:35:40'),
(36, 'R-2295/26M1_6', 'Stublina kanc', '2026-04-23 14:35:58'),
(38, 'R-2295/26M1_7', 'Stublina kanc', '2026-04-23 14:36:08'),
(40, 'R-2295/26M1_8', 'Stublina kanc', '2026-04-23 14:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `imenik_firme`
--

CREATE TABLE `imenik_firme` (
  `id` int(10) UNSIGNED NOT NULL,
  `naziv` varchar(200) NOT NULL,
  `adresa` varchar(300) NOT NULL DEFAULT '',
  `drzava` varchar(100) NOT NULL DEFAULT 'Srbija',
  `komentar` text NOT NULL DEFAULT '',
  `aktivan` tinyint(1) NOT NULL DEFAULT 1,
  `datum_kreiranja` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `imenik_firme`
--

INSERT INTO `imenik_firme` (`id`, `naziv`, `adresa`, `drzava`, `komentar`, `aktivan`, `datum_kreiranja`) VALUES
(1, 'Promont Komerc d.o.o.', 'Beograd/Niš', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(2, 'Totem Tim DOO', 'Veselina Masleše 86A, Novi Sad', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(3, 'XENON LIGHT', 'Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(4, 'IBF', 'N. Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(5, 'ELMAT', 'Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(6, 'Ultra Light', 'Matice Srpske 4, 21000 Novi Sad', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(7, 'KANDELA', 'BORSKA BB, 18000, Niš', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(8, 'KOLVEL HELIOS', 'Nikodima Milaša 1 (ugao Cvijićeva 125a), 11000 Beograd. Srbija', 'Srbija', 'PROIZVODNJA- NE SLATI MAIL ZA PONUDU', 1, '2026-04-16 14:22:08'),
(9, 'SYSTEH', '', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(10, 'TESTA SISTEMI', 'Dr Ivana Ribara 181a, Novi Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(11, 'Sectron D.O.O.', 'Jurija Gagarina 11b, Zgrada Sectron/SOT', 'Srbija', 'Telefon:  +381 11 4422 702, Faks: +381 11 4422 701', 1, '2026-04-16 14:22:08'),
(12, 'GENESEC', '', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(13, 'ALARM AUTOMATIKA', 'Zarija Vujoševića 8, 11 070 Novi Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(14, 'DEKADAS D.O.O', 'Bul. Arsenija Čarnojevića 99a, Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(15, 'ISC CONTACT DOO', 'POREČKA 13, 11060, Beograd (Palilula)', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(16, 'Technosector d.o.o.', 'Kralja A. I Karađorđevića 88/I-2, 34000 Kragujevac, Srbija', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(17, 'ANTENALL', 'Ljiljane Krstić 24, 11283 Zemun (Altina)', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(18, 'Dahua direktno', '', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(19, 'NETIX', '', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(20, 'ŠRAK', '', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(21, 'TEHNOUNION', 'Majora Tepića 26a, 21208 Sremska Kamenica', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(22, 'ELTON LIGHTING d.o.o', 'Milutina Milankovića 1c, 11070 Novi Beograd, Srbija', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(23, 'DELTA ELEKTRO', 'Aranđelovac', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(24, 'LED-LIGHT DOO', 'Aranđelovac', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(25, 'BLUE LINE', 'Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(26, 'ENERGYHUB', 'PM. Požarevac/Kraljevo', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(27, 'VELTEH DOO', 'Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(28, 'AMIGA D.O.O.', 'Aerodromska 1G, Kraljevo', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(29, 'Vesimpex d.o.o.', 'Patrijarha Dimitrija 24,Beograd 11090, Srbija', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(30, 'STOJIĆ ELEKTRIK', 'Svetozara Babovića 13, ČAČAK', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(31, 'Elektrometal Plus d.o.o', 'Autoput za Novi Sad 244b, Beograd', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(32, 'ELDON PLUS DOO', 'Azbresnica bb, 18258 Azbresnica', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(33, 'TJ MAXIM DOO', 'Grmeč 1, Nova 18b, 11080 Zemun', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(34, 'Servis Bogdanović', 'Stefana Nemanje 97b, Aranđelovac', 'Srbija', '', 1, '2026-04-16 14:22:08'),
(35, 'RECA D.O.O.', 'Pancevacki Put 36/V, 11210 Beograd', 'Srbija', '', 1, '2026-04-16 14:23:34'),
(36, 'Sova Auto Električar', 'Zanatlijska, Aranđelovac', 'Srbija', 'Radi od 08 do 17', 1, '2026-04-23 08:20:48'),
(37, 'MAESTRO PROFI PR', 'Janka Katića 31, 34300 Aranđelovac', 'Srbija', '', 1, '2026-04-23 11:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `imenik_kontakti`
--

CREATE TABLE `imenik_kontakti` (
  `id` int(10) UNSIGNED NOT NULL,
  `firma_id` int(10) UNSIGNED NOT NULL,
  `ime` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL DEFAULT '',
  `telefon` varchar(100) NOT NULL DEFAULT '',
  `komentar` varchar(500) NOT NULL DEFAULT '',
  `datum_kreiranja` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `imenik_kontakti`
--

INSERT INTO `imenik_kontakti` (`id`, `firma_id`, `ime`, `email`, `telefon`, `komentar`, `datum_kreiranja`) VALUES
(1, 1, 'Vlada', 'vladimir@promont.rs', '063 10 434 68', '', '2026-04-16 14:22:08'),
(2, 2, 'Rakić Srđan', 'rasveta.totemtim@gmail.com', '066 65 45 456', '', '2026-04-16 14:22:08'),
(3, 3, 'Nemanja', 'nemanja@xenonlight.rs', '011 27 55 371', '', '2026-04-16 14:22:08'),
(4, 4, 'Lazović', 'milos.lazovic@ibf.rs', '060 704 6421', '', '2026-04-16 14:22:08'),
(5, 5, 'Aleksandar Dimčić', 'aleksandar.dimcic@elmat.rs', '063 400 752', '', '2026-04-16 14:22:08'),
(6, 6, '', 'rasveta@ultralight.rs', '021 553 713, 069 510 6927', '', '2026-04-16 14:22:08'),
(7, 7, 'Saša Nikolić', 'kandela.nis@gmail.com', '062 211 385', 'Sijalice', '2026-04-16 14:22:08'),
(8, 9, '', 'office@systeh.com', '', '', '2026-04-16 14:22:08'),
(9, 10, 'Zlatan Pešić', 'z.pesic@tesla.rs', '011 711 4535', '', '2026-04-16 14:22:08'),
(10, 10, 'Aleksandar Dimčić', '', '064 644 2078', '', '2026-04-16 14:22:08'),
(11, 11, 'Ivan Lelić', 'ivan.lelic@sectron.co.rs', '062 259 762', '', '2026-04-16 14:22:08'),
(12, 11, '', 'Sasa.djordjevic@sectron.co.rs', '', 'Glavni za projekte', '2026-04-16 14:22:08'),
(13, 11, '', 'Branislav.cavic@sectron.co.rs', '', '', '2026-04-16 14:22:08'),
(14, 11, '', 'Marko.maksimovic@sectron.co.rs', '', '', '2026-04-16 14:22:08'),
(15, 11, 'Tadija Popović', 'Tadija.popovic@sectron.co.rs', '062 239 873', 'Pravio ponudu za ozvučenje za Šumadiju', '2026-04-16 14:22:08'),
(16, 11, '', 'Bojan.glisic@sectron.co.rs', '', '', '2026-04-16 14:22:08'),
(17, 11, '', 'Aleksandar.jakovljevic@sectron.co.rs', '', '', '2026-04-16 14:22:08'),
(18, 11, '', 'Marko@sectron.co.rs', '', '', '2026-04-16 14:22:08'),
(19, 12, 'Njegoš', 'njegos@genesec.rs', '066 36 36 88, 011 304 0404', '', '2026-04-16 14:22:08'),
(20, 12, 'Marija Babić', 'marijab@genesec.rs', '066 866 7669, 011 304 0404', '', '2026-04-16 14:22:08'),
(21, 13, 'Strahinja Lazović', 'strahinja.lazovic@alarmautomatika.com', '069 349 9157', '', '2026-04-16 14:22:08'),
(22, 14, 'Vojislav Karović', 'v.karovic@dekadas.rs', '064 614 7 444', '', '2026-04-16 14:22:08'),
(23, 14, 'Dragan Milenković', 'd.milenkovic@dekadas.rs', '062295683', '', '2026-04-16 14:22:08'),
(24, 15, 'Davor Milošević', 'davor.milosevic@iscc.rs', '065 3293 737', '', '2026-04-16 14:22:08'),
(25, 15, '', 'projekti@iscc.rs', '0113293737', '', '2026-04-16 14:22:08'),
(26, 16, '', 'office@technosector.rs', '065 50 70 101', '', '2026-04-16 14:22:08'),
(27, 16, 'Dušan', 'office@technosector.rs', '066 8205056', '', '2026-04-16 14:22:08'),
(28, 17, 'Milosav Guščević', 'milosav.gluscevic@antenall.rs', '066 80 43 100', '', '2026-04-16 14:22:08'),
(29, 17, 'Jovana Avramović', 'jovana.avramovic@antenall.rs', '069 111 42 40', '', '2026-04-16 14:22:08'),
(30, 17, 'Igor Drljača', 'igor.drljaca@antenall.rs', '060 481 11 07', '', '2026-04-16 14:22:08'),
(31, 18, 'Aleksandar Marinković', 'aleksandar.marinkovic@dahuatech.com', '069 1078572', '', '2026-04-16 14:22:08'),
(32, 19, '', 'info@netix.rs', '', '', '2026-04-16 14:22:08'),
(33, 19, '', 'netiks@gmail.com', '', '', '2026-04-16 14:22:08'),
(34, 20, 'Stojan Vojnović', 's.vojnovic@schrack.rs', '0628033319', '', '2026-04-16 14:22:08'),
(35, 21, 'Jelena Milutinović', 'jelena.milutinović@tehnounion.rs', '062 616 221', '', '2026-04-16 14:22:08'),
(36, 22, '', 'office@elton.rs', '381 65 33 11 011', '', '2026-04-16 14:22:08'),
(37, 23, 'Peđa', 'deltaelektro@gmail.com', '065 522 0781', '', '2026-04-16 14:22:08'),
(38, 24, '', 'elferum@mts.rs', '034 710 129', '', '2026-04-16 14:22:08'),
(39, 25, 'Nikola', 'nikola.bozic@blueline.rs', '063 326 133', '', '2026-04-16 14:22:08'),
(40, 26, 'Lazar Jevtić', 'l.jevtic@energyhub.rs', '064 6466 313', '', '2026-04-16 14:22:08'),
(41, 27, 'Anita', 'anita@velteh.rs', '066 250 300', '', '2026-04-16 14:22:08'),
(42, 28, 'Željko', 'zeljko.stasevic@beamforall.com', '064 8636 060', '', '2026-04-16 14:22:08'),
(43, 28, 'Marjan', 'marjan.bogdanovic@beamforall.com', '', '', '2026-04-16 14:22:08'),
(44, 29, 'Dejan Dojčin', 'dejan.dojcin@vesimpex.rs', '381 63 33 56 41', '', '2026-04-16 14:22:08'),
(45, 30, 'Radmilo', 'radmilo.ristanovic@stojic.rs', '065 3706 303', '', '2026-04-16 14:22:08'),
(46, 31, 'Elizabeta', 'office@elektrometal.rs', '069 608 826 , 011/41-55-121 , 013/805-393', '', '2026-04-16 14:22:08'),
(47, 32, '', 'eldonplus@gmail.com', '018 897 993, 063 414 783', '', '2026-04-16 14:22:08'),
(48, 33, 'Miloš', 'office@tjmaxim.com', '0113149786', '', '2026-04-16 14:22:08'),
(49, 34, '', 'bogdanovicelektro@gmail.com', '063 432 168, 061 684 1773', '', '2026-04-16 14:22:08'),
(50, 35, 'Nemanja', 'nemanja.nikolic@reca.rs', '063243984', 'Terenski komercijalista', '2026-04-16 14:24:54'),
(52, 35, 'test korisnik', 'pedja@yopmail.com', '061 317 99 88', 'Test user', '2026-04-17 07:19:49'),
(53, 11, 'Marko Vučković', 'marko.vuckovic@sectron.co.rs', '062259037', 'Pravio ponudu za SKS za Šumadiju', '2026-04-20 10:59:00'),
(54, 15, 'Neven Kunovac', 'projekti@iscc.rs', '065 329 37 38', '', '2026-04-22 12:14:00'),
(55, 36, 'Sova', '', '064 204 35 78', '', '2026-04-23 08:21:18'),
(56, 37, 'Nikola Krsmanović', 'nikola.krsmanovic@yahoo.com', '062 473 933', '', '2026-04-23 11:06:53'),
(57, 1, 'Bojan', '', '063 454 084', 'vozač', '2026-04-23 13:34:20');

-- --------------------------------------------------------

--
-- Table structure for table `kontakt_forme`
--

CREATE TABLE `kontakt_forme` (
  `id` int(10) UNSIGNED NOT NULL,
  `datum` datetime NOT NULL DEFAULT current_timestamp(),
  `ime_prezime` varchar(200) NOT NULL,
  `firma` varchar(200) NOT NULL DEFAULT '',
  `telefon` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL DEFAULT '',
  `vrsta_usluge` varchar(200) NOT NULL DEFAULT '',
  `opis_projekta` text NOT NULL,
  `grad` varchar(100) NOT NULL DEFAULT '',
  `komentar` text NOT NULL DEFAULT '',
  `procitano` tinyint(1) NOT NULL DEFAULT 0,
  `ip_adresa` varchar(45) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kontakt_forme`
--

INSERT INTO `kontakt_forme` (`id`, `datum`, `ime_prezime`, `firma`, `telefon`, `email`, `vrsta_usluge`, `opis_projekta`, `grad`, `komentar`, `procitano`, `ip_adresa`) VALUES
(1, '2026-04-14 08:10:23', 'Peđa', 'Firma neka d.o.o.', '0613179988', 'pedja@yopmail.com', 'Instalacija jake struje', 'Test da vidim', 'Novi Sad', 'Čili smo se. šalje projekat.22.04.2026 poslao projekat. javiću mu se. 25.04.2025', 1, '109.93.52.252');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_korisnici`
--
ALTER TABLE `admin_korisnici`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `gradilista_robe`
--
ALTER TABLE `gradilista_robe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_key` (`item_key`);

--
-- Indexes for table `imenik_firme`
--
ALTER TABLE `imenik_firme`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `imenik_kontakti`
--
ALTER TABLE `imenik_kontakti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `firma_id` (`firma_id`);

--
-- Indexes for table `kontakt_forme`
--
ALTER TABLE `kontakt_forme`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_datum` (`datum`),
  ADD KEY `idx_procitano` (`procitano`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_korisnici`
--
ALTER TABLE `admin_korisnici`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gradilista_robe`
--
ALTER TABLE `gradilista_robe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `imenik_firme`
--
ALTER TABLE `imenik_firme`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `imenik_kontakti`
--
ALTER TABLE `imenik_kontakti`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `kontakt_forme`
--
ALTER TABLE `kontakt_forme`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `imenik_kontakti`
--
ALTER TABLE `imenik_kontakti`
  ADD CONSTRAINT `imenik_kontakti_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `imenik_firme` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
