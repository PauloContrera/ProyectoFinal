-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2025 at 11:11 PM
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
-- Table structure for table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `unblock_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `blocked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_ips`
--

INSERT INTO `blocked_ips` (`id`, `ip_address`, `unblock_at`, `reason`, `blocked_at`) VALUES
(1, '::1', '2025-07-01 18:05:32', 'IP bloqueada automáticamente por 18 intentos fallidos', '2025-07-01 17:59:00');

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
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `email`, `token`, `expires_at`, `verified`, `created_at`, `verified_at`, `ip_address`) VALUES
(3, 31, 'paulocontrera97@gmail.com', 'eb6afebe39f3214de4d7bc1599dd18b6', '2025-07-01 19:32:43', -1, '2025-07-01 13:32:43', NULL, '::1'),
(4, 31, 'paulocontrera97@gmail.com', '5365e12a5f8a5d2603b7c42d12ddbdde', '2025-07-01 19:37:45', -1, '2025-07-01 13:37:45', NULL, '::1'),
(5, 31, 'paulocontrera97@gmail.com', '6c3c595d60961fcd402cac493f2c984f', '2025-07-01 19:38:17', -1, '2025-07-01 13:38:17', NULL, '::1'),
(6, 31, 'paulocontrera97@gmail.com', '1527314c1c99f5c25f3b73e5c35d8a05', '2025-07-01 20:51:16', 1, '2025-07-01 14:51:16', '2025-07-01 14:51:30', '::1'),
(7, 32, 'paulocontrera927@gmail.com', '8eebee33628005b995c45679e47c5cc8', '2025-07-01 20:53:20', 0, '2025-07-01 14:53:20', NULL, '::1'),
(8, 33, '1@1.com', '85bbb3bb8b35a28aedc62139568455f3', '2025-07-01 20:53:55', -1, '2025-07-01 14:53:55', NULL, '::1'),
(9, 34, '1231231@gmail.com', '0af5139ad911ae9ccf89e683c7039afb', '2025-07-01 20:54:36', -1, '2025-07-01 14:54:36', NULL, '::1'),
(10, 34, '1231231@gmail.com', '24f2c4ad3769027a7c5160e31cf34a68', '2025-07-01 20:57:00', 1, '2025-07-01 14:57:00', '2025-07-01 14:59:03', '::1'),
(11, 33, '1@1.com', '921cf15fd7f9ec00c60541953e9f8063', '2025-07-01 20:57:54', -1, '2025-07-01 14:57:54', NULL, '::1'),
(12, 33, '1@1.com', '392650e65aa1687eb946173800141d19', '2025-07-01 23:33:07', 0, '2025-07-01 17:33:07', NULL, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `event_logs`
--

CREATE TABLE `event_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_logs`
--

INSERT INTO `event_logs` (`id`, `user_id`, `event_type`, `event_message`, `ip_address`, `created_at`) VALUES
(1, 30, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-01 12:49:30'),
(2, 31, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-01 13:32:43'),
(3, 31, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-01 13:37:48'),
(4, 31, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-01 13:38:20'),
(5, 31, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-01 14:50:48'),
(6, 31, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-01 14:51:12'),
(7, 31, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-01 14:51:19'),
(8, 31, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-01 14:51:30'),
(9, 32, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-01 14:53:20'),
(10, 32, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-01 14:53:23'),
(11, 33, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-01 14:53:55'),
(12, 33, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-01 14:53:58'),
(13, 34, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-01 14:54:36'),
(14, 34, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-01 14:54:40'),
(15, 34, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-01 14:57:04'),
(16, 33, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-01 14:57:57'),
(17, 34, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-01 14:59:03'),
(18, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 15:10:44'),
(19, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 15:10:59'),
(20, NULL, 'login_failed', 'Intento de inicio de sesión fallido, Usuario incorrecto', '::1', '2025-07-01 15:11:41'),
(21, NULL, 'login_failed', 'Intento de inicio de sesión fallido, Usuario incorrecto', '::1', '2025-07-01 15:14:49'),
(22, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 15:16:31'),
(23, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:17:50'),
(24, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:18:11'),
(25, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:18:12'),
(26, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:18:13'),
(27, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:18:23'),
(28, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 15:18:25'),
(29, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 15:19:43'),
(30, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 15:20:02'),
(31, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 15:20:19'),
(32, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 15:21:53'),
(33, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:21:59'),
(34, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:22:01'),
(35, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:22:02'),
(36, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:22:03'),
(37, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 15:22:04'),
(38, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 15:22:05'),
(39, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 15:22:10'),
(40, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 16:00:29'),
(41, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 16:18:55'),
(42, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 16:25:22'),
(43, NULL, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña, Usuario no encontrado', '::1', '2025-07-01 16:25:25'),
(44, NULL, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 16:32:42'),
(45, NULL, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 16:41:52'),
(46, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 16:46:04'),
(47, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 16:46:10'),
(48, NULL, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 16:46:35'),
(49, NULL, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 16:48:51'),
(50, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 16:53:06'),
(51, 31, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 16:54:00'),
(52, 31, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 16:55:40'),
(53, 31, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Token inválido o expirado', '::1', '2025-07-01 16:58:17'),
(54, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrectas', '::1', '2025-07-01 17:22:28'),
(55, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 17:22:37'),
(56, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 17:30:36'),
(57, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-01 17:31:14'),
(58, 31, 'password_reset', 'Contraseña restablecida', '::1', '2025-07-01 17:31:53'),
(59, 31, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Token inválido o expirado', '::1', '2025-07-01 17:31:56'),
(60, 33, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-01 17:33:12'),
(61, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:57:43'),
(62, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 17:57:49'),
(63, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:13'),
(64, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:14'),
(65, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:16'),
(66, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:19'),
(67, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:21'),
(68, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 17:58:23'),
(69, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:55'),
(70, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:58:58'),
(71, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 17:59:00'),
(72, NULL, 'ip_blocked', 'IP bloqueada automáticamente por 10 intentos fallidos', '::1', '2025-07-01 17:59:00'),
(73, NULL, 'login_failed', 'Intento de inicio de sesión fallido, IP Bloqueado', '::1', '2025-07-01 17:59:02'),
(74, NULL, 'login_failed', 'Intento de inicio de sesión fallido, IP Bloqueado', '::1', '2025-07-01 17:59:34'),
(75, 31, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-01 18:00:52'),
(76, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 18:01:10'),
(77, NULL, 'ip_blocked', 'IP bloqueada automáticamente por 14 intentos fallidos', '::1', '2025-07-01 18:01:10'),
(78, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 18:01:14'),
(79, NULL, 'login_failed', 'Intento de inicio de sesión fallido, IP Bloqueado', '::1', '2025-07-01 18:01:31'),
(80, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 18:01:50'),
(81, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 18:04:03'),
(82, NULL, 'ip_blocked', 'IP bloqueada automáticamente por 16 intentos fallidos', '::1', '2025-07-01 18:04:03'),
(83, NULL, 'login_failed', 'Intento de inicio de sesión fallido, IP Bloqueado', '::1', '2025-07-01 18:04:05'),
(84, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-01 18:05:32'),
(85, NULL, 'ip_blocked', 'IP bloqueada automáticamente por 18 intentos fallidos', '::1', '2025-07-01 18:05:32'),
(86, NULL, 'login_failed', 'Intento de inicio de sesión fallido, IP Bloqueado', '::1', '2025-07-01 18:05:36'),
(87, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 18:05:49');

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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `token`, `expires_at`, `used`, `ip_address`, `created_at`) VALUES
(1, 31, 'paulocontrera97@gmail.com', '3e1e472ead902dee6186494a3cc516f725e056861ae0578608c51f0c47a0fed8', '2025-07-01 22:01:14', 1, '::1', '2025-07-01 16:01:14'),
(2, 31, 'paulocontrera97@gmail.com', 'bb43e9a248745c0a965f41334619b8c8f333686b2069ed0e7a1141a3146578f7', '2025-07-01 22:18:52', 1, '::1', '2025-07-01 16:18:52'),
(3, 31, 'paulocontrera97@gmail.com', '4298612980b6eb2a1ff19e5aa258b88eaf8e52ada62fb25ad9d1749738477c96', '2025-07-01 22:25:19', 1, '::1', '2025-07-01 16:25:19'),
(4, 31, 'paulocontrera97@gmail.com', '8cef613e35a1ea51d274c6486ae83f9b9826d165941110d32c46f825bb11136c', '2025-07-01 22:45:59', 1, '::1', '2025-07-01 16:45:59'),
(5, 31, 'paulocontrera97@gmail.com', '2252e70dc10609b17c87a2bbead81bf90ae71ef918480f0f5688478f9aa25fe8', '2025-07-01 22:46:06', 1, '::1', '2025-07-01 16:46:06'),
(6, 31, 'paulocontrera97@gmail.com', '967fbe484f8da53e3d4229ec41bcc8682239c2677b8be59ec5c3aa9acd784999', '2025-07-01 22:53:04', 1, '::1', '2025-07-01 16:53:04'),
(7, 31, 'paulocontrera97@gmail.com', '602c72bbc84fc824e22e8687cffd568daf6259469f12bec82d21c81962410f97', '2025-07-01 23:30:31', 1, '::1', '2025-07-01 17:30:31'),
(8, 31, 'paulocontrera97@gmail.com', 'd73bbf6d1b87fd8315104b137f7c401190ca9d175bcf10a465e8f55a3a5fb101', '2025-07-01 23:31:10', 1, '::1', '2025-07-01 17:31:10');

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
  `is_email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `email`, `phone`, `role`, `is_email_verified`, `phone_verified`, `failed_login_attempts`, `last_login_at`, `registered_at`, `updated_at`) VALUES
(1, 'Admin One', 'admin1', '12345678', 'admin1@mail.com', '1111111111', 'admin', 0, 0, 1, NULL, '2025-06-05 21:02:13', '2025-06-12 17:27:23'),
(2, 'Cliente Uno', 'cliente1', '123456', 'cliente1@mail.com', '2222222222', 'client', 0, 0, 0, NULL, '2025-06-05 21:02:13', '2025-06-12 17:27:23'),
(4, 'Visitante Uno', 'visit1', '123456', 'visit1@mail.com', '4444444444', 'visitor', 0, 0, 0, NULL, '2025-06-05 21:02:13', '2025-06-12 17:27:23'),
(13, 'admin', 'admin', '$2y$10$J9PF9moAVwcyou3C4IQHQ.KW5BxLdE.k6rI5aen7l3CrFE6jlKqJ6', 'admin@example.com', '2611234567', 'admin', 0, 0, 0, '2025-06-13 11:22:32', '2025-06-10 21:19:09', '2025-06-13 11:22:32'),
(28, 'cliente', 'visiasasd', '$2y$10$VmMMnTWKTdhVd/xsXtXoGORVVHQGyIpEFqB0qV4lHv.drn5U4lZT6', 'clientee@mail.com', '111', 'client', 0, 0, 0, NULL, '2025-06-18 17:10:31', '2025-07-01 17:41:00'),
(31, 'PauloAdmin', 'PauloAdmin', '$2y$10$w3OJOFs9QBizMFLbqdibg.tcy5pPBrdbel5c/M4gJPbvwQIYIHUUK', 'paulocontrera97@gmail.com', '2611234567', 'client', 1, 0, 0, '2025-07-01 18:05:49', '2025-07-01 13:32:43', '2025-07-01 18:05:49'),
(32, 'PauloAdmin', 'PauloAdmin2', '$2y$10$L643jqFYiEzKnJ22XUqhr.oh2fvJVb7y8ktDF6r/KZpI09EpQuu0.', 'paulocontrera927@gmail.com', '2611234567', 'client', 0, 0, 0, NULL, '2025-07-01 14:53:20', '2025-07-01 14:53:20'),
(33, 'PauloAdmin', 'PauloAdmin21', '$2y$10$6Rd5Rz7bdfTj6XndmXjw6eoNMLX1kxSaRANkkxPS09cmE16XUt.IC', '1@1.com', '2611234567', 'client', 0, 0, 0, NULL, '2025-07-01 14:53:55', '2025-07-01 14:53:55'),
(34, 'PauloAdmin', 'PauloAdmin211', '$2y$10$Sq064g1fNshWdT244CW7qOm5fd2ATSjaEZnaNoFb.eiqkhaYv5mjG', '1231231@gmail.com', '2611234567', 'client', 1, 0, 0, NULL, '2025-07-01 14:54:36', '2025-07-01 14:59:03');

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
(42, 28, 'username', 'visi', 'visiasasd', '2025-06-18 17:31:12', 13),
(43, 28, 'name', 'clientee', 'cliente', '2025-07-01 17:41:00', 13),
(44, 28, 'email', 'cliente@mail.com', 'clientee@mail.com', '2025-07-01 17:41:00', 13),
(45, 28, 'phone', '261123456bbgvggtv7', '111', '2025-07-01 17:41:00', 13);

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
-- Indexes for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

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
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_logs`
--
ALTER TABLE `event_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event_logs`
--
ALTER TABLE `event_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `user_change_log`
--
ALTER TABLE `user_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
