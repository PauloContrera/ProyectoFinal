-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2025 at 10:12 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `device_access`
--

CREATE TABLE `device_access` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_modify` tinyint(1) DEFAULT 0,
  `granted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_change_log`
--

CREATE TABLE `device_change_log` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp(),
  `changed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(16, 37, 'paulocontrera97@gmail.com', '0b598946f4d4bb04ec01f08f14291273', '2025-07-03 18:14:39', 1, '2025-07-03 12:14:39', '2025-07-03 12:16:08', '::1'),
(17, 38, 'Cliente@gmail.com', 'fa91d27c3eb529e0786b9810a42df0f8', '2025-07-03 18:15:02', 1, '2025-07-03 12:15:02', '2025-07-03 12:16:18', '::1'),
(18, 39, 'Visitante@gmail.com', 'f0b6abe1ab499ff29c2d249f422e1da9', '2025-07-03 18:15:46', 1, '2025-07-03 12:15:46', '2025-07-03 12:16:29', '::1'),
(19, 40, 'SuperAdmin@gmail.com', '2e16f65e7812a7c3d98ac194e14ada94', '2025-07-03 22:18:57', 1, '2025-07-03 16:18:57', '2025-07-03 16:20:15', '::1');

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
(87, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-01 18:05:49'),
(88, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-03 12:03:29'),
(89, 33, 'verification_email_resent', 'Correo de verificación reenviado', '::1', '2025-07-03 12:03:46'),
(90, 31, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', '::1', '2025-07-03 12:03:53'),
(91, NULL, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Token inválido o expirado', '::1', '2025-07-03 12:04:05'),
(92, NULL, 'login_failed', 'Intento de inicio de sesión fallido, Usuario incorrecto', '::1', '2025-07-03 12:12:44'),
(93, 35, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-03 12:12:52'),
(94, 35, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-03 12:12:55'),
(95, 36, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-03 12:13:37'),
(96, 36, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-03 12:13:41'),
(97, 37, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-03 12:14:39'),
(98, 37, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-03 12:14:43'),
(99, 38, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-03 12:15:02'),
(100, 38, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-03 12:15:05'),
(101, 39, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-03 12:15:46'),
(102, 39, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-03 12:15:49'),
(103, 37, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-03 12:16:08'),
(104, 38, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-03 12:16:18'),
(105, 39, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-03 12:16:29'),
(106, 40, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-03 16:18:57'),
(107, 40, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-03 16:19:03'),
(108, 40, 'login_failed', 'Intento de inicio de sesión fallido, Correo no verificado', '::1', '2025-07-03 16:19:21'),
(109, 40, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-03 16:20:16'),
(110, 40, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-03 16:20:19'),
(111, 40, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-03 16:20:23'),
(112, 40, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-03 16:20:27'),
(113, 40, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-03 16:21:14'),
(114, 37, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-03 16:36:46'),
(115, 38, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-03 16:37:05'),
(116, 39, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-03 16:37:28');

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
  `role` enum('superadmin','admin','client','visitor') DEFAULT 'client',
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
(37, 'Admin', 'Admin', '$2y$10$.3wDmPCymRBx4z1pjK8dIuktNRXrDbsnOuGqs1TWJ.SUjhe2NSIMa', 'paulocontrera97@gmail.com', '2611234567', 'admin', 1, 0, 0, '2025-07-03 16:36:46', '2025-07-03 12:14:39', '2025-07-03 16:36:46'),
(38, 'Cliente', 'Cliente', '$2y$10$LgAGQUaXd5CRFqGyozSYc.mc2wmkDFhZ4dt3Zj5Vhwmw6/mo2/ARO', 'Cliente@gmail.com', '2611234567', 'client', 1, 0, 0, '2025-07-03 16:37:05', '2025-07-03 12:15:02', '2025-07-03 16:37:05'),
(39, 'Visitante', 'Visitante', '$2y$10$QgssjkGMd4oEvBG96uEniOBSgk9ivpvLFIWOLZJBkSImX9rxINrXa', 'Visitante@gmail.com', '2611234567', 'visitor', 1, 0, 0, '2025-07-03 16:37:28', '2025-07-03 12:15:46', '2025-07-03 16:37:28'),
(40, 'SuperAdmin', 'SuperAdmin', '$2y$10$o5a3j/FBaz.Msgmb2MrJYODTYEMh8zvp.q08I9YmcVuER0Qu6y86W', 'SuperAdmin@gmail.com', '2611234567', 'superadmin', 1, 0, 0, '2025-07-03 16:21:14', '2025-07-03 16:18:57', '2025-07-03 16:21:14');

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
-- Indexes for table `device_access`
--
ALTER TABLE `device_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_user_unique` (`device_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `device_change_log`
--
ALTER TABLE `device_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `changed_by` (`changed_by`);

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
-- AUTO_INCREMENT for table `device_access`
--
ALTER TABLE `device_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device_change_log`
--
ALTER TABLE `device_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `event_logs`
--
ALTER TABLE `event_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
-- Constraints for table `device_access`
--
ALTER TABLE `device_access`
  ADD CONSTRAINT `device_access_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_access_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `device_change_log`
--
ALTER TABLE `device_change_log`
  ADD CONSTRAINT `device_change_log_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_change_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
