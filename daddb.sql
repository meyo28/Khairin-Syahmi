-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3301
-- Generation Time: Jun 26, 2025 at 10:39 AM
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
-- Database: `daddb`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `move_in_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `property_id`, `student_id`, `message`, `move_in_date`, `status`, `created_at`, `updated_at`) VALUES
(8, 12, 4, 'okay boleh', '2025-07-30', 'approved', '2025-06-26 07:26:46', '2025-06-26 07:27:41'),
(9, 7, 1, 'saya berminat untuk sewa rumah', '2025-07-08', 'pending', '2025-06-26 08:25:59', '2025-06-26 08:25:59'),
(10, 7, 5, 'saya berminat untuk menyewa sampai bulan 12', '2025-07-11', 'pending', '2025-06-26 08:27:53', '2025-06-26 08:27:53');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `property_type` enum('apartment','house','condo','townhouse') NOT NULL,
  `bedrooms` int(11) NOT NULL,
  `bathrooms` decimal(3,1) NOT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `available_date` date NOT NULL,
  `property_image` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `landlord_id`, `title`, `address`, `city`, `property_type`, `bedrooms`, `bathrooms`, `rent_amount`, `available_date`, `property_image`, `status`, `latitude`, `longitude`) VALUES
(6, 6, 'Taman Teknologi Apartment', 'No. 1, Jalan Teknologi, Taman Teknologi, Durian Tunggal', 'Melaka', 'apartment', 3, 2.0, 900.00, '2025-07-01', 'images/utem1.png', 'available', 2.31234400, 102.31884900),
(7, 6, 'Rumah Sewa Bukit Beruang', 'No. 88, Jalan Beruang 3, Taman Bukit Beruang', 'Melaka', 'house', 4, 2.0, 1200.00, '2025-07-05', 'images/utem2.jpg', 'available', 2.24570300, 102.28770600),
(8, 7, 'Kondominium Ixora Heights', 'Ixora Heights, Jalan Tun Hamzah, Bukit Katil', 'Melaka', 'condo', 2, 1.0, 750.00, '2025-07-10', 'images/utem3.jpg', 'available', 2.25939500, 102.28989100),
(9, 8, 'Student House Taman Tasik Utama', 'No. 12, Jalan TU 3, Taman Tasik Utama, Ayer Keroh', 'Melaka', 'house', 3, 2.0, 950.00, '2025-07-08', 'images/utem4.jpeg', 'available', 2.29908000, 102.31854200),
(10, 9, 'Apartment Near UTeM', 'No. 5, Jalan UTeM, Durian Tunggal', 'Melaka', 'apartment', 2, 1.0, 800.00, '2025-07-12', 'images/utem5.jpg', 'available', 2.31152600, 102.32066100),
(11, 6, 'Homestay Seri UTeM', 'Jalan Seri UTeM, Taman Seri UTeM, Durian Tunggal', 'Melaka', 'house', 3, 2.0, 850.00, '2025-07-15', 'images/utem6.jpg', 'available', 2.30952100, 102.31915200),
(12, 6, 'Budget Room Taman Teknologi', 'Lorong Teknologi 2, Taman Teknologi, Durian Tunggal', 'Melaka', 'apartment', 1, 1.0, 450.00, '2025-07-18', 'images/utem7.jpg', 'unavailable', 2.31042100, 102.31812100),
(13, 7, 'Single Room Bukit Beruang', 'Jalan BB 5, Taman Bukit Beruang Permai', 'Melaka', 'apartment', 1, 1.0, 500.00, '2025-07-20', 'images/utem8.jpeg', 'available', 2.24999800, 102.28880100),
(14, 7, 'Semi-D Near MITC', 'Jalan MITC 3, Ayer Keroh', 'Melaka', 'house', 4, 3.0, 1300.00, '2025-07-22', 'images/utem9.jpg', 'available', 2.27420000, 102.28600000),
(15, 8, 'Ixora Heights Student Condo', 'Block C, Ixora Heights, Bukit Katil', 'Melaka', 'condo', 3, 2.0, 980.00, '2025-07-25', 'images/utem10.jpg', 'available', 2.25870000, 102.28830000),
(16, 8, 'Taman Saujana Indah House', 'No. 22, Jalan Saujana 1, Taman Saujana Indah', 'Melaka', 'house', 3, 2.0, 880.00, '2025-07-28', 'images/utem11.jpg', 'available', 2.26548000, 102.32149000),
(17, 9, 'Shared Unit Taman Tasik Utama', 'Jalan TU 5, Taman Tasik Utama, Ayer Keroh', 'Melaka', 'apartment', 2, 1.0, 700.00, '2025-07-30', 'images/utem12.jpeg', 'available', 2.29880000, 102.31990000),
(18, 9, 'Katil Bujang Dekat UTeM', 'No. 7, Jalan UTeM 1, Durian Tunggal', 'Melaka', 'apartment', 1, 1.0, 400.00, '2025-08-01', 'images/utem13.jpg', 'available', 2.31188800, 102.32001200),
(19, 10, 'Unit Bajet Durian Tunggal', 'Lorong Belimbing, Taman Belimbing Murni', 'Melaka', 'apartment', 2, 1.0, 650.00, '2025-08-03', 'images/utem14.jpg', 'available', 2.31851200, 102.31691200),
(20, 10, 'Rumah Sewa Pelajar UTeM', 'Jalan Pelajar 2, Taman UTeM Jaya', 'Melaka', 'house', 3, 2.0, 900.00, '2025-08-05', 'images/utem15.jpg', 'available', 2.31331200, 102.31709100);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `user_type` enum('student','landlord') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'john.smith@example.com', 'student123', 'student', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(2, 'Emily Johnson', 'emily.j@example.com', 'emily456', 'student', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(3, 'Michael Brown', 'michael.b@example.com', 'michael789', 'student', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(4, 'Sarah Davis', 'sarah.d@example.com', 'sarah101', 'student', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(5, 'David Wilson', 'david.w@example.com', 'david202', 'student', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(6, 'Robert Taylor', 'robert.t@example.com', 'landlord123', 'landlord', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(7, 'Jennifer Lee', 'jennifer.l@example.com', 'jennifer456', 'landlord', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(8, 'Thomas Clark', 'thomas.c@example.com', 'thomas789', 'landlord', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(9, 'Jessica Hall', 'jessica.h@example.com', 'jessica101', 'landlord', '2025-06-19 12:39:34', '2025-06-19 12:39:34'),
(10, 'Daniel Young', 'daniel.y@example.com', 'daniel202', 'landlord', '2025-06-19 12:39:34', '2025-06-19 12:39:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
