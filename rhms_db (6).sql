-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2025 at 05:20 AM
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
-- Database: `rhms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `username`, `password_hash`, `created_at`, `updated_at`, `is_active`, `last_login`, `failed_login_attempts`, `locked_until`) VALUES
(1, 'System Administrator', 'admin@richardshotel.com', 'admin', '$2y$10$kt2XzOULT/PcEfMaKgHwFOW4e.xUXq1xdeXx8ugHZf8IbXYqZhYwK', '2025-09-07 09:25:41', '2025-09-14 01:30:36', 1, '2025-09-14 01:30:36', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_address` text NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `duration_hours` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `check_in_datetime` datetime NOT NULL,
  `check_out_datetime` datetime NOT NULL,
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `booking_status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `customer_name`, `customer_address`, `customer_phone`, `customer_email`, `room_type_id`, `room_id`, `duration_hours`, `total_price`, `check_in_datetime`, `check_out_datetime`, `proof_of_payment`, `special_requests`, `booking_status`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Josh McDowell Trapal', 'Ogbot', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 2, NULL, 24, 2000.00, '2025-09-07 15:22:00', '2025-09-08 15:22:00', 'payment_68bd32727dea0.jpg', '', 'pending', 'pending', '2025-09-07 07:21:22', '2025-09-07 07:21:22'),
(2, NULL, 'John Doe', '123 Test Street, Test City', '09123456789', 'john.doe@example.com', 1, NULL, 3, 500.00, '2024-12-25 14:00:00', '2024-12-25 17:00:00', NULL, 'Test booking request', 'pending', 'pending', '2025-09-07 07:21:55', '2025-09-07 07:21:55'),
(3, NULL, 'Josh McDowell Trapal', 'Sitio Highway Roxas, 5212 Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 1, NULL, 12, 1200.00, '2025-09-07 15:24:00', '2025-09-08 03:24:00', 'payment_68bd32d391f24.jpg', '', 'pending', 'pending', '2025-09-07 07:22:59', '2025-09-07 07:22:59'),
(4, NULL, 'Josh McDowell Trapal', 'Sitio Highway Roxas, 5212 Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 1, 2, 12, 1200.00, '2025-09-10 05:32:00', '2025-09-10 17:32:00', 'payment_68bd35138b824.png', '', 'pending', 'pending', '2025-09-07 07:32:35', '2025-09-07 07:32:35'),
(5, NULL, 'Josh McDowell Trapal', 'Ogbot', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 2, 10, 24, 2000.00, '2025-09-09 18:00:00', '2025-09-10 18:00:00', 'payment_68bd5b8202f66.png', '', 'confirmed', 'paid', '2025-09-07 10:16:34', '2025-09-13 14:40:49'),
(6, NULL, 'josh', 'ogbot', '09958714112', 'josh@gmaill.com', 1, 3, 12, 1200.00, '2025-09-11 18:22:00', '2025-09-12 06:22:00', 'payment_68bd5cfc58a9e.jpg', '', 'confirmed', 'paid', '2025-09-07 10:22:52', '2025-09-13 14:40:30'),
(7, NULL, 'Josh McDowell Trapal', 'Sitio Highway Roxas, 5212 Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 2, NULL, 24, 2000.00, '2025-09-13 15:39:00', '2025-09-14 15:39:00', NULL, '', 'pending', 'pending', '2025-09-13 07:39:41', '2025-09-13 07:39:41'),
(8, NULL, 'Josh McDowell Trapal', 'Sitio Highway Roxas, 5212 Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 2, NULL, 24, 2000.00, '2025-09-14 16:00:00', '2025-09-15 16:00:00', NULL, '', 'pending', 'pending', '2025-09-13 08:07:57', '2025-09-13 08:07:57'),
(9, NULL, 'AJ Nicole', 'San Rafael', '09452592763', 'ajnicole@gmail.com', 2, NULL, 24, 2000.00, '2025-09-16 16:33:00', '2025-09-17 16:33:00', NULL, '', 'confirmed', 'paid', '2025-09-13 08:33:17', '2025-09-13 14:32:50'),
(10, NULL, 'AJ Nicole', 'San Rafael', '09452592763', 'ajnicole@gmail.com', 2, NULL, 24, 2000.00, '2025-09-16 16:33:00', '2025-09-17 16:33:00', NULL, '', 'confirmed', 'paid', '2025-09-13 08:33:17', '2025-09-13 14:40:22'),
(11, NULL, 'AJ Nicole Salamente', 'San Rafael', '09958714112', 'ajnicolesalamente@gmail.com', 1, NULL, 3, 500.00, '2025-09-19 22:35:00', '2025-09-20 01:35:00', NULL, '', 'confirmed', 'paid', '2025-09-13 14:36:02', '2025-09-13 14:40:16'),
(12, NULL, 'AJ Nicole Salamente', 'San Rafael', '09958714112', 'ajnicolesalamente@gmail.com', 1, NULL, 3, 500.00, '2025-09-19 22:35:00', '2025-09-20 01:35:00', NULL, '', 'confirmed', 'paid', '2025-09-13 14:36:02', '2025-09-13 14:40:19'),
(13, NULL, 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'joshmcdowelltrapal@gmail.com', 2, NULL, 24, 2000.00, '2025-09-24 10:43:00', '2025-09-25 10:43:00', NULL, '', 'pending', 'pending', '2025-09-13 14:43:26', '2025-09-13 14:43:26'),
(14, NULL, 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'joshmcdowelltrapal@gmail.com', 2, NULL, 24, 2000.00, '2025-09-24 10:43:00', '2025-09-25 10:43:00', NULL, '', 'pending', 'pending', '2025-09-13 14:43:26', '2025-09-13 14:43:26'),
(15, NULL, 'Josh McDowell Trapal', 'Ogbot', '09452592763', 'joshmcdowelltrapal@gmail.com', 1, 1, 12, 1200.00, '2025-09-19 22:51:00', '2025-09-20 10:51:00', NULL, '', 'confirmed', 'paid', '2025-09-13 14:51:56', '2025-09-13 14:52:51'),
(16, NULL, 'Josh McDowell Trapal', 'Ogbot', '09452592763', 'joshmcdowelltrapal@gmail.com', 1, 1, 12, 1200.00, '2025-09-19 22:51:00', '2025-09-20 10:51:00', NULL, '', 'pending', 'pending', '2025-09-13 14:51:56', '2025-09-13 14:51:56'),
(17, NULL, 'Josh McDowell Trapal', 'ogbot', '0995872166', 'joshmcdowelltrapal@gmail.com', 2, 10, 24, 2000.00, '2025-09-30 22:49:00', '2025-10-01 22:49:00', NULL, '', 'pending', 'pending', '2025-09-13 14:53:58', '2025-09-13 14:53:58'),
(18, NULL, 'Josh McDowell Trapal', 'ogbot', '0995872166', 'joshmcdowelltrapal@gmail.com', 2, 10, 24, 2000.00, '2025-09-30 22:49:00', '2025-10-01 22:49:00', NULL, '', 'pending', 'pending', '2025-09-13 14:53:58', '2025-09-13 14:53:58'),
(19, NULL, 'Josh McDowell Trapal', 'Ogbot', '0995872166', 'joshmcdowelltrapal@gmail.com', 1, 2, 3, 500.00, '2025-09-19 23:00:00', '2025-09-20 02:00:00', NULL, '', 'pending', 'pending', '2025-09-13 15:00:48', '2025-09-13 15:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `booking_rates`
--

CREATE TABLE `booking_rates` (
  `id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_rates`
--

INSERT INTO `booking_rates` (`id`, `room_type_id`, `duration_hours`, `price`, `created_at`) VALUES
(1, 1, 3, 500.00, '2025-09-07 05:37:03'),
(2, 1, 12, 1200.00, '2025-09-07 05:37:03'),
(3, 1, 24, 1000.00, '2025-09-07 05:37:03'),
(4, 2, 24, 2000.00, '2025-09-07 05:37:03');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('signup','password_reset','email_verification') DEFAULT 'signup',
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `email`, `otp_code`, `purpose`, `expires_at`, `is_used`, `created_at`) VALUES
(4, 'dhodstrapal12@gmail.com', '461655', 'signup', '2025-09-14 01:37:52', 0, '2025-09-13 17:27:52'),
(7, 'joshmcdowelltrapal@gmail.com', '271828', 'signup', '2025-09-14 01:41:23', 1, '2025-09-13 17:31:23'),
(8, 'saanncuya2023@gmail.com', '864614', 'signup', '2025-09-14 01:43:07', 1, '2025-09-13 17:33:07'),
(11, 'student.joshmcdowelltrapal@gmail.com', '494864', 'signup', '2025-09-14 12:20:12', 1, '2025-09-14 04:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `status` enum('available','occupied','maintenance','out_of_order') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `status`, `created_at`, `updated_at`) VALUES
(1, '101', 1, 'occupied', '2025-09-07 05:37:02', '2025-09-13 16:21:41'),
(2, '102', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(3, '103', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(4, '201', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(5, '202', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(6, '203', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(7, '301', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(8, '302', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(9, '303', 1, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(10, '401', 2, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02'),
(11, '402', 2, 'available', '2025-09-07 05:37:02', '2025-09-07 05:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `type_name`, `description`, `created_at`) VALUES
(1, 'Normal Room', 'Standard room with basic amenities', '2025-09-07 05:37:02'),
(2, 'Family Room', 'Spacious room designed for families', '2025-09-07 05:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `created_at`, `updated_at`, `is_active`, `email_verified`, `last_login`, `failed_login_attempts`, `locked_until`) VALUES
(20, 'Josh McDowell', 'Trapal', 'student.joshmcdowelltrapal@gmail.com', '09958714112', '$2y$10$uns0tGOHHvpUwGN9ZKeF.e.0pOshGbWpYtyNufuIwNH1EUphJewlW', '2025-09-14 12:10:33', '2025-09-14 12:10:41', 1, 0, '2025-09-14 12:10:41', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 hour),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_admin_email` (`email`),
  ADD KEY `idx_admin_username` (`username`),
  ADD KEY `idx_admin_active` (`is_active`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_type_id` (`room_type_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_booking_status` (`booking_status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_check_in` (`check_in_datetime`),
  ADD KEY `idx_customer_email` (`customer_email`);

--
-- Indexes for table `booking_rates`
--
ALTER TABLE `booking_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rate` (`room_type_id`,`duration_hours`),
  ADD KEY `idx_room_type_duration` (`room_type_id`,`duration_hours`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_purpose` (`email`,`purpose`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_room_type` (`room_type_id`),
  ADD KEY `idx_room_status` (`status`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_users_email_verified` (`email_verified`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_sessions_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `booking_rates`
--
ALTER TABLE `booking_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_rates`
--
ALTER TABLE `booking_rates`
  ADD CONSTRAINT `booking_rates_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
