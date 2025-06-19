-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2025 at 01:32 PM
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
-- Database: `sistemas_heladeras`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp(),
  `type` enum('TEMP_LOW','TEMP_HIGH','PAYMENT_DUE','NO_DATA') NOT NULL DEFAULT 'TEMP_HIGH',
  `notified` tinyint(1) DEFAULT 0,
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `device_id`, `temperature`, `recorded_at`, `type`, `notified`, `resolved`, `resolved_at`) VALUES
(1, 1, 9.1, '2025-06-05 21:02:13', 'TEMP_HIGH', 0, 0, NULL),
(2, 2, 0.8, '2025-06-05 21:02:13', 'TEMP_LOW', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `alert_suppression`
--

CREATE TABLE `alert_suppression` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `min_temp` float DEFAULT 0,
  `max_temp` float DEFAULT 10,
  `firmware_version` varchar(50) DEFAULT NULL,
  `last_reported_at` datetime DEFAULT NULL,
  `device_time` datetime DEFAULT NULL,
  `time_discrepancy` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `name`, `location`, `user_id`, `group_id`, `min_temp`, `max_temp`, `firmware_version`, `last_reported_at`, `device_time`, `time_discrepancy`, `created_at`) VALUES
(1, 'Heladera A1', 'Depósito 1', 2, 1, 2, 8, NULL, NULL, NULL, NULL, '2025-06-05 21:02:13'),
(2, 'Heladera A2', 'Cámara 2', 2, 1, 1, 7, NULL, NULL, NULL, NULL, '2025-06-05 21:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `device_groups`
--

CREATE TABLE `device_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_groups`
--

INSERT INTO `device_groups` (`id`, `name`, `description`, `user_id`, `created_at`) VALUES
(1, 'Grupo A', 'Grupo de prueba A', 2, '2025-06-05 21:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `device_inactivity_log`
--

CREATE TABLE `device_inactivity_log` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `detected_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `status` enum('active','resolved') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `inactive_fridges`
-- (See below for the actual view)
--
CREATE TABLE `inactive_fridges` (
`id` int(11)
,`name` varchar(100)
,`last_reported_at` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `severity` enum('info','warning','error','critical') DEFAULT 'info',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temperatures`
--

CREATE TABLE `temperatures` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `temperatures`
--

INSERT INTO `temperatures` (`id`, `device_id`, `temperature`, `recorded_at`) VALUES
(1, 1, 3.5, '2025-06-05 20:47:13'),
(2, 1, 9.1, '2025-06-05 21:02:13'),
(3, 2, 0.8, '2025-06-05 21:02:13');

-- --------------------------------------------------------

--
-- Stand-in structure for view `top_fridges_by_alerts`
-- (See below for the actual view)
--
CREATE TABLE `top_fridges_by_alerts` (
`fridge_id` int(11)
,`name` varchar(100)
,`alert_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','client','visitor') DEFAULT 'client',
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `email`, `phone`, `role`, `email_verified`, `phone_verified`, `failed_login_attempts`, `last_login_at`, `registered_at`, `updated_at`) VALUES
(1, 'Admin One', 'admin1', '12345678', 'admin1@mail.com', '1111111111', 'admin', 0, 0, 1, NULL, '2025-06-05 21:02:13', '2025-06-12 17:27:23'),
(2, 'Cliente Uno', 'cliente1', '123456', 'cliente1@mail.com', '2222222222', 'client', 0, 0, 0, NULL, '2025-06-05 21:02:13', '2025-06-12 17:27:23'),
(4, 'Visitante Uno', 'visit1', '123456', 'visit1@mail.com', '4444444444', 'visitor', 0, 0, 0, NULL, '2025-06-05 21:02:13', '2025-06-12 17:27:23'),
(13, 'admin', 'admin', '$2y$10$J9PF9moAVwcyou3C4IQHQ.KW5BxLdE.k6rI5aen7l3CrFE6jlKqJ6', 'admin@example.com', '2611234567', 'admin', 0, 0, 0, '2025-06-13 11:22:32', '2025-06-10 21:19:09', '2025-06-13 11:22:32'),
(28, 'clientee', 'visiasasd', '$2y$10$VmMMnTWKTdhVd/xsXtXoGORVVHQGyIpEFqB0qV4lHv.drn5U4lZT6', 'cliente@mail.com', '261123456bbgvggtv7', 'client', 0, 0, 0, NULL, '2025-06-18 17:10:31', '2025-06-18 17:31:12');

-- --------------------------------------------------------

--
-- Stand-in structure for view `users_with_most_alerts`
-- (See below for the actual view)
--
CREATE TABLE `users_with_most_alerts` (
`user_id` int(11)
,`name` varchar(100)
,`alert_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_change_log`
--

CREATE TABLE `user_change_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp(),
  `changed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_change_log`
--

INSERT INTO `user_change_log` (`id`, `user_id`, `field_changed`, `old_value`, `new_value`, `changed_at`, `changed_by`) VALUES
(7, 13, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 17:56:43', 13),
(9, 13, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:00:29', 13),
(10, 13, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:00:36', 13),
(12, 13, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:00:56', 13),
(17, 13, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:08:55', 13),
(18, 13, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:09:29', 13),
(19, NULL, 'deleted', '{\"id\":26,\"name\":\"visit\",\"username\":\"visit\",\"password\":\"$2y$10$0Euy\\/5L5J0kgCXiWtgcJpuw5YqgW7IUMOdLVfXaHuBD25CTETissK\",\"email\":\"visit@example.com\",\"phone\":\"2611234567\",\"role\":\"client\",\"email_verified\":0,\"phone_verified\":0,\"failed_login_attempts\":0,\"last_login_at\":null,\"registered_at\":\"2025-06-12 18:10:01\",\"updated_at\":\"2025-06-12 18:10:01\"}', NULL, '2025-06-12 18:10:08', 13),
(20, NULL, 'deleted', '{\"id\":26,\"name\":\"visit\",\"username\":\"visit\",\"password\":\"$2y$10$0Euy\\/5L5J0kgCXiWtgcJpuw5YqgW7IUMOdLVfXaHuBD25CTETissK\",\"email\":\"visit@example.com\",\"phone\":\"2611234567\",\"role\":\"client\",\"email_verified\":0,\"phone_verified\":0,\"failed_login_attempts\":0,\"last_login_at\":\"2025-06-13 11:26:36\",\"registered_at\":\"2025-06-12 18:10:01\",\"updated_at\":\"2025-06-13 11:26:36\"}', NULL, '2025-06-18 16:40:25', 13),
(21, NULL, 'deleted', '{\"id\":26,\"name\":\"visit\",\"username\":\"visit\",\"password\":\"$2y$10$0Euy\\/5L5J0kgCXiWtgcJpuw5YqgW7IUMOdLVfXaHuBD25CTETissK\",\"email\":\"visit@example.com\",\"phone\":\"2611234567\",\"role\":\"client\",\"email_verified\":0,\"phone_verified\":0,\"failed_login_attempts\":0,\"last_login_at\":\"2025-06-13 11:26:36\",\"registered_at\":\"2025-06-12 18:10:01\",\"updated_at\":\"2025-06-13 11:26:36\"}', NULL, '2025-06-18 16:59:05', 13),
(22, NULL, 'deleted', '{\"id\":26,\"0\":26,\"name\":\"visit\",\"1\":\"visit\",\"username\":\"visit\",\"2\":\"visit\",\"email\":\"visit@example.com\",\"3\":\"visit@example.com\",\"phone\":\"2611234567\",\"4\":\"2611234567\",\"role\":\"client\",\"5\":\"client\"}', NULL, '2025-06-18 17:00:50', 13),
(23, NULL, 'deleted', '{\"id\":27,\"0\":27,\"name\":\"visit\",\"1\":\"visit\",\"username\":\"visit\",\"2\":\"visit\",\"email\":\"visit@example.com\",\"3\":\"visit@example.com\",\"phone\":\"2611234567\",\"4\":\"2611234567\",\"role\":\"client\",\"5\":\"client\"}', NULL, '2025-06-18 17:02:04', 13),
(24, 28, 'name', 'visit', 'cliente', '2025-06-18 17:10:48', 13),
(25, 28, 'email', 'visit@example.com', 'cliente@example.com', '2025-06-18 17:10:48', 13),
(26, 28, 'email', 'cliente@example.com', 'clientee@example.com', '2025-06-18 17:11:21', 13),
(27, 28, 'name', 'cliente', 'clientee', '2025-06-18 17:11:32', 13),
(28, 28, 'email', 'clientee@example.com', 'cliente1@example.com', '2025-06-18 17:12:29', 13),
(29, 28, 'email', 'cliente1@example.com', 'client1@mail.com', '2025-06-18 17:13:17', 13),
(30, 28, 'phone', '2611234567', '', '2025-06-18 17:13:18', 13),
(31, 28, 'name', 'clientee', 'clientee22', '2025-06-18 17:16:35', 13),
(32, 28, 'email', 'client1@mail.com', 'client122@mail.com', '2025-06-18 17:16:35', 13),
(33, 28, 'name', 'clientee22', 'clientee', '2025-06-18 17:17:58', 13),
(34, 28, 'email', 'client122@mail.com', 'cliente@mail.com', '2025-06-18 17:17:58', 13),
(35, 28, 'phone', '', '2611234567', '2025-06-18 17:17:58', 13),
(36, 28, 'phone', '2611234567', '261123456bbgvggtv7', '2025-06-18 17:19:08', 13),
(37, 28, 'username', 'visit', 'visit2', '2025-06-18 17:29:44', 13),
(38, 28, 'username', 'visit2', 'visi', '2025-06-18 17:30:32', 13),
(39, 28, 'username', 'visi', 'visi', '2025-06-18 17:30:33', 13),
(40, 28, 'username', 'visi', 'visi', '2025-06-18 17:30:35', 13),
(41, 28, 'username', 'visi', 'visi', '2025-06-18 17:30:46', 13),
(42, 28, 'username', 'visi', 'visiasasd', '2025-06-18 17:31:12', 13);

-- --------------------------------------------------------

--
-- Structure for view `inactive_fridges`
--
DROP TABLE IF EXISTS `inactive_fridges`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inactive_fridges`  AS SELECT `d`.`id` AS `id`, `d`.`name` AS `name`, `d`.`last_reported_at` AS `last_reported_at` FROM `devices` AS `d` WHERE `d`.`last_reported_at` < current_timestamp() - interval 1 day ;

-- --------------------------------------------------------

--
-- Structure for view `top_fridges_by_alerts`
--
DROP TABLE IF EXISTS `top_fridges_by_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `top_fridges_by_alerts`  AS SELECT `d`.`id` AS `fridge_id`, `d`.`name` AS `name`, count(`a`.`id`) AS `alert_count` FROM (`devices` `d` join `alerts` `a` on(`a`.`device_id` = `d`.`id`)) GROUP BY `d`.`id` ORDER BY count(`a`.`id`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `users_with_most_alerts`
--
DROP TABLE IF EXISTS `users_with_most_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users_with_most_alerts`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `name`, count(`a`.`id`) AS `alert_count` FROM ((`users` `u` join `devices` `d` on(`d`.`user_id` = `u`.`id`)) join `alerts` `a` on(`a`.`device_id` = `d`.`id`)) GROUP BY `u`.`id` ORDER BY count(`a`.`id`) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `alert_suppression`
--
ALTER TABLE `alert_suppression`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `device_groups`
--
ALTER TABLE `device_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `device_inactivity_log`
--
ALTER TABLE `device_inactivity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temperatures`
--
ALTER TABLE `temperatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_change_log`
--
ALTER TABLE `user_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `alert_suppression`
--
ALTER TABLE `alert_suppression`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `device_groups`
--
ALTER TABLE `device_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `device_inactivity_log`
--
ALTER TABLE `device_inactivity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temperatures`
--
ALTER TABLE `temperatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `user_change_log`
--
ALTER TABLE `user_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `alert_suppression`
--
ALTER TABLE `alert_suppression`
  ADD CONSTRAINT `alert_suppression_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alert_suppression_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devices_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `device_groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `device_groups`
--
ALTER TABLE `device_groups`
  ADD CONSTRAINT `device_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `device_inactivity_log`
--
ALTER TABLE `device_inactivity_log`
  ADD CONSTRAINT `device_inactivity_log_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `temperatures`
--
ALTER TABLE `temperatures`
  ADD CONSTRAINT `temperatures_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_change_log`
--
ALTER TABLE `user_change_log`
  ADD CONSTRAINT `user_change_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_change_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
