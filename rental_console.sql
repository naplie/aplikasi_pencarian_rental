-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2026 at 06:13 PM
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
-- Database: `rental_console`
--

-- --------------------------------------------------------

--
-- Table structure for table `consoles`
--

CREATE TABLE `consoles` (
  `console_id` int(11) NOT NULL,
  `console_name` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `generation` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consoles`
--

INSERT INTO `consoles` (`console_id`, `console_name`, `brand`, `generation`) VALUES
(1, 'PS 3', 'Sony', '7th Gen'),
(2, 'PS 4', 'Sony', '8th Gen'),
(3, 'PS 5', 'Sony', '9th Gen');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(150) NOT NULL,
  `genre` varchar(80) DEFAULT NULL,
  `cover_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`game_id`, `game_name`, `genre`, `cover_url`) VALUES
(1, 'GTA V', NULL, NULL),
(2, 'tekken 7', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `rental_id`, `address`, `latitude`, `longitude`) VALUES
(8, 8, 'tataaran patar', 1.2789155, 124.8772436);

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_id`, `name`, `email`, `password`, `phone`, `created_at`) VALUES
(10, 'admin', 'nepli6477@gmail.com', '$2y$10$QzIFnWtxvyQczgqKAiPz0uNlZD.ZKRYoAjy0tHFU.Ynl/j2nxb5eO', NULL, '2026-11-21 06:39:08');

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `rental_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status_toko` enum('buka','tutup','libur') NOT NULL DEFAULT 'buka',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `owner_id`, `rental_name`, `description`, `phone`, `status_toko`, `created_at`, `catatan`) VALUES
(8, 10, 'rental PS', NULL, NULL, 'buka', '2026-11-21 06:41:01', 'akun ini hanya di buat untuk testing. lokasi yang ada di map tidak benar-benar ada');

-- --------------------------------------------------------

--
-- Table structure for table `rental_consoles`
--

CREATE TABLE `rental_consoles` (
  `rental_console_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `console_id` int(11) NOT NULL,
  `total_units` int(11) NOT NULL DEFAULT 1,
  `available_units` int(11) NOT NULL DEFAULT 1,
  `status` enum('tersedia','penuh','maintenance') NOT NULL DEFAULT 'tersedia'
) ;

--
-- Dumping data for table `rental_consoles`
--

INSERT INTO `rental_consoles` (`rental_console_id`, `rental_id`, `console_id`, `total_units`, `available_units`, `status`) VALUES
(2, 8, 1, 1, 1, 'tersedia'),
(3, 8, 2, 2, 2, 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `rental_games`
--

CREATE TABLE `rental_games` (
  `rental_game_id` int(11) NOT NULL,
  `rental_console_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `status` enum('tersedia','tidak tersedia') NOT NULL DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_games`
--

INSERT INTO `rental_games` (`rental_game_id`, `rental_console_id`, `game_id`, `status`) VALUES
(1, 2, 1, 'tersedia'),
(2, 3, 2, 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `rental_photos`
--

CREATE TABLE `rental_photos` (
  `photo_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `photo_url` varchar(255) NOT NULL,
  `caption` varchar(150) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_photos`
--

INSERT INTO `rental_photos` (`photo_id`, `rental_id`, `photo_url`, `caption`, `is_primary`) VALUES
(1, 8, 'uploads/1795255929_Modern Purple Game Rental Shop.png', 'tampak dalam toko', 0),
(2, 8, 'uploads/1795256169_Modern Purple Game Rental Shop.png', 'tampak dalam toko', 0),
(3, 8, 'uploads/1795256174_Modern Purple Game Rental Shop.png', 'tampak dalam toko', 0);

-- --------------------------------------------------------

--
-- Table structure for table `rental_rates`
--

CREATE TABLE `rental_rates` (
  `rate_id` int(11) NOT NULL,
  `rental_console_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ;

--
-- Dumping data for table `rental_rates`
--

INSERT INTO `rental_rates` (`rate_id`, `rental_console_id`, `package_name`, `duration_hours`, `price`) VALUES
(5, 2, 'Paket 1 Jam', 1, 10000.00),
(6, 3, 'Paket 1 Jam', 1, 15000.00);

-- --------------------------------------------------------

--
-- Table structure for table `rental_schedules`
--

CREATE TABLE `rental_schedules` (
  `schedule_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `day_of_week` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_schedules`
--

INSERT INTO `rental_schedules` (`schedule_id`, `rental_id`, `day_of_week`, `opening_time`, `closing_time`, `is_closed`) VALUES
(1, 8, 'Senin', '09:00:00', '22:00:00', 0),
(2, 8, 'Selasa', '09:00:00', '22:00:00', 0),
(3, 8, 'Rabu', '09:00:00', '22:00:00', 0),
(4, 8, 'Kamis', '09:00:00', '22:00:00', 0),
(5, 8, 'Jumat', '09:00:00', '22:00:00', 0),
(6, 8, 'Sabtu', NULL, NULL, 1),
(7, 8, 'Minggu', NULL, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consoles`
--
`consoles`
  ADD PRIMARY KEY (`console_id`);

--
-- Indexes for table `games`
--
`games`
  ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `locations`
--
`locations`
  ADD PRIMARY KEY (`location_id`),
  ADD UNIQUE KEY `rental_id` (`rental_id`);

--
-- Indexes for table `owners`
--
`owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `rentals`
--
`rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `fk_rentals_owner` (`owner_id`);

--
-- Indexes for table `rental_consoles`
--
`rental_consoles`
  ADD PRIMARY KEY (`rental_console_id`),
  ADD UNIQUE KEY `uq_rental_console` (`rental_id`,`console_id`),
  ADD KEY `fk_rc_console` (`console_id`);

--
-- Indexes for table `rental_games`
--
`rental_games`
  ADD PRIMARY KEY (`rental_game_id`),
  ADD UNIQUE KEY `uq_console_game` (`rental_console_id`,`game_id`),
  ADD KEY `fk_rg_game` (`game_id`);

--
-- Indexes for table `rental_photos`
--
`rental_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `fk_photos_rental` (`rental_id`);

--
-- Indexes for table `rental_rates`
--
`rental_rates`
  ADD PRIMARY KEY (`rate_id`),
  ADD KEY `fk_rates_rc` (`rental_console_id`);

--
-- Indexes for table `rental_schedules`
--
`rental_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `uq_rental_day` (`rental_id`,`day_of_week`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consoles`
--
`consoles`
  MODIFY `console_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `games`
--
`games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
`locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `owners`
--
`owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rentals`
--
`rentals`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rental_consoles`
--
`rental_consoles`
  MODIFY `rental_console_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_games`
--
`rental_games`
  MODIFY `rental_game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rental_photos`
--
`rental_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rental_rates`
--
`rental_rates`
  MODIFY `rate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_schedules`
--
`rental_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `locations`
--
`locations`
`fk_locations_rental` (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rentals`
--
`rentals`
`fk_rentals_owner` (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rental_consoles`
--
`rental_consoles`
`fk_rc_console` (`console_id`) REFERENCES `consoles` (`console_id`) ON DELETE CASCADE ON UPDATE CASCADE,
`fk_rc_rental` (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rental_games`
--
`rental_games`
`fk_rg_console` (`rental_console_id`) REFERENCES `rental_consoles` (`rental_console_id`) ON DELETE CASCADE,
`fk_rg_game` (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_photos`
--
`rental_photos`
`fk_photos_rental` (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rental_rates`
--
`rental_rates`
`fk_rates_rc` (`rental_console_id`) REFERENCES `rental_consoles` (`rental_console_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rental_schedules`
--
`rental_schedules`
`fk_schedules_rental` (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
