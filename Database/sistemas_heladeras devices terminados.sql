-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-08-2025 a las 22:53:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistemas_heladeras`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerts`
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
-- Estructura de tabla para la tabla `alert_suppression`
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
-- Estructura de tabla para la tabla `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `unblock_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `blocked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `blocked_ips`
--

INSERT INTO `blocked_ips` (`id`, `ip_address`, `unblock_at`, `reason`, `blocked_at`) VALUES
(1, '::1', '2025-07-01 18:05:32', 'IP bloqueada automáticamente por 18 intentos fallidos', '2025-07-01 17:59:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `device_code` varchar(100) DEFAULT NULL,
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
-- Estructura de tabla para la tabla `device_access`
--

CREATE TABLE `device_access` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_modify` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_access_log`
--

CREATE TABLE `device_access_log` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `target_user` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `action` enum('grant','revoke') NOT NULL,
  `can_modify` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_change_log`
--

CREATE TABLE `device_change_log` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `action` enum('create','update','delete') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `device_change_log`
--

INSERT INTO `device_change_log` (`id`, `device_id`, `user_id`, `field_changed`, `old_value`, `new_value`, `action`, `created_at`) VALUES
(1, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-07-28 12:53:33'),
(2, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-07-28 12:53:33'),
(3, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-07-28 12:53:33'),
(4, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-07-28 12:53:33'),
(5, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-28 12:53:33'),
(6, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-07-28 12:53:34'),
(7, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-07-28 15:01:40'),
(8, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-07-28 15:01:40'),
(9, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-07-28 15:01:40'),
(10, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-07-28 15:01:40'),
(11, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-28 15:01:40'),
(12, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-07-28 15:01:40'),
(13, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-07-28 15:10:19'),
(14, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-07-28 15:10:19'),
(15, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-07-28 15:10:19'),
(16, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-07-28 15:10:19'),
(17, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-28 15:10:19'),
(18, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-07-28 15:10:19'),
(19, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-07-28 15:10:43'),
(20, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-07-28 15:10:43'),
(21, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-07-28 15:10:43'),
(22, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-07-28 15:10:43'),
(23, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-28 15:10:43'),
(24, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-07-28 15:10:43'),
(25, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-07-28 15:10:51'),
(26, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-07-28 15:10:51'),
(27, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-07-28 15:10:51'),
(28, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-07-28 15:10:51'),
(29, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-28 15:10:51'),
(30, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-07-28 15:10:51'),
(31, NULL, 36, 'group_id', NULL, '18', 'update', '2025-07-28 16:24:00'),
(32, NULL, 36, 'group_id', '18', '19', 'update', '2025-07-28 16:25:00'),
(33, NULL, 36, 'group_id', NULL, '21', 'update', '2025-07-28 16:30:42'),
(34, NULL, 36, 'group_id', NULL, '21', 'update', '2025-07-28 16:31:17'),
(35, NULL, 36, 'group_id', '21', '19', 'update', '2025-07-28 16:34:38'),
(36, NULL, 36, 'group_id', NULL, '17', 'update', '2025-07-28 16:36:39'),
(37, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:18:35'),
(38, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:18:35'),
(39, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-07-30 10:18:35'),
(40, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-07-30 10:18:35'),
(41, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:18:35'),
(42, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-07-30 10:18:35'),
(43, NULL, 39, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:21:09'),
(44, NULL, 39, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:21:09'),
(45, NULL, 39, 'min_temp', NULL, '2', 'create', '2025-07-30 10:21:09'),
(46, NULL, 39, 'max_temp', NULL, '8', 'create', '2025-07-30 10:21:09'),
(47, NULL, 39, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:21:09'),
(48, NULL, 39, 'group_id', NULL, '25', 'create', '2025-07-30 10:21:09'),
(49, NULL, 39, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:21:27'),
(50, NULL, 39, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:21:27'),
(51, NULL, 39, 'min_temp', NULL, '2', 'create', '2025-07-30 10:21:27'),
(52, NULL, 39, 'max_temp', NULL, '8', 'create', '2025-07-30 10:21:27'),
(53, NULL, 39, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:21:27'),
(54, NULL, 39, 'group_id', NULL, '24', 'create', '2025-07-30 10:21:27'),
(55, NULL, 39, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:42:06'),
(56, NULL, 39, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:42:06'),
(57, NULL, 39, 'min_temp', NULL, '2', 'create', '2025-07-30 10:42:06'),
(58, NULL, 39, 'max_temp', NULL, '8', 'create', '2025-07-30 10:42:06'),
(59, NULL, 39, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:42:06'),
(60, NULL, 39, 'group_id', NULL, '25', 'create', '2025-07-30 10:42:06'),
(61, NULL, 36, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:52:34'),
(62, NULL, 36, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:52:34'),
(63, NULL, 36, 'min_temp', NULL, '2', 'create', '2025-07-30 10:52:34'),
(64, NULL, 36, 'max_temp', NULL, '8', 'create', '2025-07-30 10:52:34'),
(65, NULL, 36, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:52:34'),
(66, NULL, 36, 'group_id', NULL, '22', 'create', '2025-07-30 10:52:34'),
(67, NULL, 37, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:52:46'),
(68, NULL, 37, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:52:46'),
(69, NULL, 37, 'min_temp', NULL, '2', 'create', '2025-07-30 10:52:46'),
(70, NULL, 37, 'max_temp', NULL, '8', 'create', '2025-07-30 10:52:46'),
(71, NULL, 37, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:52:46'),
(72, NULL, 37, 'group_id', NULL, '23', 'create', '2025-07-30 10:52:46'),
(73, NULL, 38, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:53:03'),
(74, NULL, 38, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:53:03'),
(75, NULL, 38, 'min_temp', NULL, '2', 'create', '2025-07-30 10:53:03'),
(76, NULL, 38, 'max_temp', NULL, '8', 'create', '2025-07-30 10:53:03'),
(77, NULL, 38, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:53:03'),
(78, NULL, 38, 'group_id', NULL, '24', 'create', '2025-07-30 10:53:03'),
(79, NULL, 39, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 10:53:34'),
(80, NULL, 39, 'location', NULL, 'Depósito A', 'create', '2025-07-30 10:53:34'),
(81, NULL, 39, 'min_temp', NULL, '2', 'create', '2025-07-30 10:53:34'),
(82, NULL, 39, 'max_temp', NULL, '8', 'create', '2025-07-30 10:53:34'),
(83, NULL, 39, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 10:53:34'),
(84, NULL, 39, 'group_id', NULL, '25', 'create', '2025-07-30 10:53:34'),
(85, NULL, 38, 'name', NULL, 'Heladera 1', 'create', '2025-07-30 11:35:15'),
(86, NULL, 38, 'location', NULL, 'Depósito A', 'create', '2025-07-30 11:35:15'),
(87, NULL, 38, 'min_temp', NULL, '2', 'create', '2025-07-30 11:35:15'),
(88, NULL, 38, 'max_temp', NULL, '8', 'create', '2025-07-30 11:35:15'),
(89, NULL, 38, 'firmware_version', NULL, '1.0.1', 'create', '2025-07-30 11:35:15'),
(90, NULL, 38, 'group_id', NULL, '24', 'create', '2025-07-30 11:35:15'),
(91, NULL, 40, 'user_id', '38', '40', 'update', '2025-07-30 12:52:05'),
(92, NULL, 39, 'user_id', '38', '39', 'update', '2025-07-30 12:55:34'),
(93, NULL, 38, 'name', NULL, 'Heladera 1', 'create', '2025-08-07 16:34:53'),
(94, NULL, 38, 'location', NULL, 'Depósito A', 'create', '2025-08-07 16:34:53'),
(95, NULL, 38, 'min_temp', NULL, '2', 'create', '2025-08-07 16:34:53'),
(96, NULL, 38, 'max_temp', NULL, '8', 'create', '2025-08-07 16:34:53'),
(97, NULL, 38, 'firmware_version', NULL, '1.0.1', 'create', '2025-08-07 16:34:53'),
(98, NULL, 38, 'group_id', NULL, NULL, 'create', '2025-08-07 16:34:53'),
(99, NULL, 38, 'name', NULL, 'Heladera 1', 'create', '2025-08-07 16:35:38'),
(100, NULL, 38, 'location', NULL, 'Depósito A', 'create', '2025-08-07 16:35:38'),
(101, NULL, 38, 'min_temp', NULL, '2', 'create', '2025-08-07 16:35:38'),
(102, NULL, 38, 'max_temp', NULL, '8', 'create', '2025-08-07 16:35:38'),
(103, NULL, 38, 'firmware_version', NULL, '1.0.1', 'create', '2025-08-07 16:35:38'),
(104, NULL, 38, 'group_id', NULL, '24', 'create', '2025-08-07 16:35:38'),
(105, NULL, 36, 'name', 'Heladera 1', 'Heladera 5', 'update', '2025-08-07 16:40:20'),
(106, NULL, 36, 'location', 'Depósito A', 'Depósito 5', 'update', '2025-08-07 16:40:20'),
(107, NULL, 36, 'min_temp', '2', '15', 'update', '2025-08-07 16:40:20'),
(108, NULL, 36, 'max_temp', '8', '52', 'update', '2025-08-07 16:40:20'),
(109, NULL, 36, 'firmware_version', '1.0.1', '15', 'update', '2025-08-07 16:40:20'),
(110, NULL, 36, 'name', 'Heladera 1', 'Heladera 5', 'update', '2025-08-07 16:40:45'),
(111, NULL, 36, 'location', 'Depósito A', 'Depósito 5', 'update', '2025-08-07 16:40:45'),
(112, NULL, 36, 'min_temp', '2', '15', 'update', '2025-08-07 16:40:45'),
(113, NULL, 36, 'max_temp', '8', '52', 'update', '2025-08-07 16:40:45'),
(114, NULL, 36, 'firmware_version', '1.0.1', '15', 'update', '2025-08-07 16:40:45'),
(115, NULL, 37, 'name', 'Heladera 5', 'Heladera 1', 'update', '2025-08-07 16:41:24'),
(116, NULL, 37, 'location', 'Depósito 5', 'Depósito A', 'update', '2025-08-07 16:41:24'),
(117, NULL, 37, 'min_temp', '15', '2', 'update', '2025-08-07 16:41:24'),
(118, NULL, 37, 'max_temp', '52', '8', 'update', '2025-08-07 16:41:24'),
(119, NULL, 37, 'firmware_version', '15', '1.0.1', 'update', '2025-08-07 16:41:24'),
(120, NULL, 38, 'name', 'Heladera 1', 'Heladera 1110', 'update', '2025-08-07 16:43:25'),
(121, NULL, 38, 'location', 'Depósito A', 'Depósito A000', 'update', '2025-08-07 16:43:25'),
(122, NULL, 38, 'min_temp', '2', '20', 'update', '2025-08-07 16:43:25'),
(123, NULL, 38, 'max_temp', '8', '80', 'update', '2025-08-07 16:43:25'),
(124, NULL, 38, 'firmware_version', '1.0.1', '10.0.1', 'update', '2025-08-07 16:43:25'),
(125, NULL, 39, 'name', 'Heladera 1110', 'Heladera 1', 'update', '2025-08-07 16:44:37'),
(126, NULL, 39, 'location', 'Depósito A000', 'Depósito A', 'update', '2025-08-07 16:44:37'),
(127, NULL, 39, 'min_temp', '20', '2', 'update', '2025-08-07 16:44:37'),
(128, NULL, 39, 'max_temp', '80', '8', 'update', '2025-08-07 16:44:37'),
(129, NULL, 39, 'firmware_version', '10.0.1', '1.0.1', 'update', '2025-08-07 16:44:37'),
(130, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-08-07 17:07:30'),
(131, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-08-07 17:07:30'),
(132, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-08-07 17:07:30'),
(133, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-08-07 17:07:30'),
(134, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-08-07 17:07:30'),
(135, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-08-07 17:07:30'),
(136, NULL, 39, 'user_id', NULL, '39', 'update', '2025-08-07 17:12:31'),
(137, NULL, NULL, 'name', NULL, 'Heladera 1', 'create', '2025-08-07 17:15:20'),
(138, NULL, NULL, 'location', NULL, 'Depósito A', 'create', '2025-08-07 17:15:20'),
(139, NULL, NULL, 'min_temp', NULL, '2', 'create', '2025-08-07 17:15:20'),
(140, NULL, NULL, 'max_temp', NULL, '8', 'create', '2025-08-07 17:15:20'),
(141, NULL, NULL, 'firmware_version', NULL, '1.0.1', 'create', '2025-08-07 17:15:20'),
(142, NULL, NULL, 'group_id', NULL, NULL, 'create', '2025-08-07 17:15:20'),
(143, NULL, 40, 'user_id', NULL, '40', 'update', '2025-08-07 17:16:24'),
(144, NULL, 38, 'name', 'Heladera 1', NULL, 'delete', '2025-08-07 17:31:59'),
(145, NULL, 38, 'location', 'Depósito A', NULL, 'delete', '2025-08-07 17:31:59'),
(146, NULL, 38, 'min_temp', '2', NULL, 'delete', '2025-08-07 17:31:59'),
(147, NULL, 38, 'max_temp', '8', NULL, 'delete', '2025-08-07 17:31:59'),
(148, NULL, 38, 'firmware_version', '1.0.1', NULL, 'delete', '2025-08-07 17:31:59'),
(149, NULL, 38, 'group_id', '24', NULL, 'delete', '2025-08-07 17:31:59'),
(150, NULL, 38, 'name', 'Heladera 1', NULL, 'delete', '2025-08-07 17:32:11'),
(151, NULL, 38, 'location', 'Depósito A', NULL, 'delete', '2025-08-07 17:32:11'),
(152, NULL, 38, 'min_temp', '2', NULL, 'delete', '2025-08-07 17:32:11'),
(153, NULL, 38, 'max_temp', '8', NULL, 'delete', '2025-08-07 17:32:11'),
(154, NULL, 38, 'firmware_version', '1.0.1', NULL, 'delete', '2025-08-07 17:32:11'),
(155, NULL, 38, 'group_id', '24', NULL, 'delete', '2025-08-07 17:32:11'),
(156, NULL, 36, 'name', 'Heladera 1', NULL, 'delete', '2025-08-07 17:32:46'),
(157, NULL, 36, 'location', 'Depósito A', NULL, 'delete', '2025-08-07 17:32:46'),
(158, NULL, 36, 'min_temp', '2', NULL, 'delete', '2025-08-07 17:32:46'),
(159, NULL, 36, 'max_temp', '8', NULL, 'delete', '2025-08-07 17:32:46'),
(160, NULL, 36, 'firmware_version', '1.0.1', NULL, 'delete', '2025-08-07 17:32:46'),
(161, NULL, 36, 'group_id', '24', NULL, 'delete', '2025-08-07 17:32:46'),
(162, NULL, 37, 'name', 'Heladera 1', NULL, 'delete', '2025-08-07 17:33:21'),
(163, NULL, 37, 'location', 'Depósito A', NULL, 'delete', '2025-08-07 17:33:21'),
(164, NULL, 37, 'min_temp', '2', NULL, 'delete', '2025-08-07 17:33:21'),
(165, NULL, 37, 'max_temp', '8', NULL, 'delete', '2025-08-07 17:33:21'),
(166, NULL, 37, 'firmware_version', '1.0.1', NULL, 'delete', '2025-08-07 17:33:21'),
(167, NULL, 37, 'group_id', NULL, NULL, 'delete', '2025-08-07 17:33:21'),
(168, NULL, 37, 'name', 'Heladera 1', NULL, 'delete', '2025-08-07 17:33:28'),
(169, NULL, 37, 'location', 'Depósito A', NULL, 'delete', '2025-08-07 17:33:28'),
(170, NULL, 37, 'min_temp', '2', NULL, 'delete', '2025-08-07 17:33:28'),
(171, NULL, 37, 'max_temp', '8', NULL, 'delete', '2025-08-07 17:33:28'),
(172, NULL, 37, 'firmware_version', '1.0.1', NULL, 'delete', '2025-08-07 17:33:28'),
(173, NULL, 37, 'group_id', '23', NULL, 'delete', '2025-08-07 17:33:28'),
(174, NULL, 36, 'name', 'Heladera 5', NULL, 'delete', '2025-08-07 17:33:47'),
(175, NULL, 36, 'location', 'Depósito 5', NULL, 'delete', '2025-08-07 17:33:47'),
(176, NULL, 36, 'min_temp', '15', NULL, 'delete', '2025-08-07 17:33:47'),
(177, NULL, 36, 'max_temp', '52', NULL, 'delete', '2025-08-07 17:33:47'),
(178, NULL, 36, 'firmware_version', '15', NULL, 'delete', '2025-08-07 17:33:47'),
(179, NULL, 36, 'group_id', '22', NULL, 'delete', '2025-08-07 17:33:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_groups`
--

CREATE TABLE `device_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `device_groups`
--

INSERT INTO `device_groups` (`id`, `name`, `description`, `user_id`, `created_at`) VALUES
(22, 'Grupo Frío', 'Heladeras de mostrador', 36, '2025-07-28 17:42:33'),
(23, 'Grupo Frío', 'Heladeras de mostrador', 37, '2025-07-28 17:42:42'),
(24, 'Grupo Frío', 'Heladeras de mostrador', 38, '2025-07-28 17:42:47'),
(25, 'Grupo Frío', 'Heladeras de mostrador', 39, '2025-07-28 17:42:54'),
(26, 'Grupo Frío', 'Heladeras de mostrador', 39, '2025-07-30 10:46:01'),
(27, 'Grupo Frío', 'Heladeras de mostrador', 39, '2025-07-30 10:50:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_group_change_log`
--

CREATE TABLE `device_group_change_log` (
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` enum('create','update','delete') NOT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `device_group_change_log`
--

INSERT INTO `device_group_change_log` (`id`, `group_id`, `user_id`, `action`, `field_changed`, `old_value`, `new_value`, `created_at`) VALUES
(64, 22, 36, 'create', 'name', NULL, 'Grupo Frío', '2025-07-28 17:42:33'),
(65, 22, 36, 'create', 'description', NULL, 'Heladeras de mostrador', '2025-07-28 17:42:33'),
(66, 23, 37, 'create', 'name', NULL, 'Grupo Frío', '2025-07-28 17:42:42'),
(67, 23, 37, 'create', 'description', NULL, 'Heladeras de mostrador', '2025-07-28 17:42:42'),
(68, 24, 38, 'create', 'name', NULL, 'Grupo Frío', '2025-07-28 17:42:47'),
(69, 24, 38, 'create', 'description', NULL, 'Heladeras de mostrador', '2025-07-28 17:42:47'),
(70, 25, 39, 'create', 'name', NULL, 'Grupo Frío', '2025-07-28 17:42:54'),
(71, 25, 39, 'create', 'description', NULL, 'Heladeras de mostrador', '2025-07-28 17:42:54'),
(74, 26, 39, 'create', 'name', NULL, 'Grupo Frío', '2025-07-30 10:46:01'),
(75, 26, 39, 'create', 'description', NULL, 'Heladeras de mostrador', '2025-07-30 10:46:01'),
(76, 27, 39, 'create', 'name', NULL, 'Grupo Frío', '2025-07-30 10:50:23'),
(77, 27, 39, 'create', 'description', NULL, 'Heladeras de mostrador', '2025-07-30 10:50:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_inactivity_log`
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
-- Estructura de tabla para la tabla `email_verifications`
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
-- Volcado de datos para la tabla `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `email`, `token`, `expires_at`, `verified`, `created_at`, `verified_at`, `ip_address`) VALUES
(14, 36, 'PauloSuperAdmin@gmail.com', '9c4f9f5a5e4a02e5ea914541f5d7ab86', '2025-07-09 04:36:11', 1, '2025-07-08 22:36:11', '2025-07-08 22:37:35', '::1'),
(15, 37, 'PauloAdmin@gmail.com', 'd49309a7197de899381da02a4924575e', '2025-07-09 04:36:21', 1, '2025-07-08 22:36:21', '2025-07-08 22:38:08', '::1'),
(16, 38, 'PauloCliente@gmail.com', 'ded543e199b7474ac2e2c50d60c83425', '2025-07-09 04:36:36', 1, '2025-07-08 22:36:36', '2025-07-08 22:38:15', '::1'),
(17, 39, 'PauloVisitante@gmail.com', 'fe58ec7e2efcd42ba1bf6320f2b5f23f', '2025-07-09 04:36:50', 1, '2025-07-08 22:36:50', '2025-07-08 22:38:23', '::1'),
(18, 40, 'paulocontrera97@gmail.com', '8df98da68827df5e0a26358fadec6449', '2025-07-09 23:11:44', 0, '2025-07-09 17:11:44', NULL, '::1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `event_logs`
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
-- Volcado de datos para la tabla `event_logs`
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
(88, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-08 21:57:06'),
(89, 31, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-08 22:00:27'),
(90, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:00:38'),
(91, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:01:15'),
(92, 31, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:34:44'),
(93, 35, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-08 22:35:39'),
(94, 35, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-08 22:35:43'),
(95, 36, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-08 22:36:11'),
(96, 36, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-08 22:36:15'),
(97, 37, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-08 22:36:21'),
(98, 37, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-08 22:36:25'),
(99, 38, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-08 22:36:36'),
(100, 38, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-08 22:36:39'),
(101, 39, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-08 22:36:50'),
(102, 39, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-08 22:36:54'),
(103, 36, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-08 22:37:35'),
(104, 37, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-08 22:38:08'),
(105, 38, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-08 22:38:15'),
(106, 39, 'email_verified', 'Correo verificado correctamente', '::1', '2025-07-08 22:38:23'),
(107, 37, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-08 22:41:58'),
(108, 37, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-08 22:42:10'),
(109, 37, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:42:21'),
(110, 37, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:45:48'),
(111, 36, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:49:00'),
(112, 38, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:49:39'),
(113, 39, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-08 22:50:04'),
(114, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 16:26:54'),
(115, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:02:50'),
(116, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:02:52'),
(117, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:02:53'),
(118, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:02:55'),
(119, 39, 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', '::1', '2025-07-09 17:02:57'),
(120, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:03:44'),
(121, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:03:46'),
(122, 39, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:03:51'),
(123, 37, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-09 17:04:39'),
(124, 37, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-09 17:04:46'),
(125, 40, 'register', 'Usuario registrado exitosamente', '::1', '2025-07-09 17:11:44'),
(126, 40, 'verification_email_sent', 'Correo de verificación enviado', '::1', '2025-07-09 17:11:49'),
(127, 36, 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', '::1', '2025-07-28 12:57:57'),
(128, 36, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-28 12:58:15'),
(129, 36, 'login_success', 'Inicio de sesión exitoso', '::1', '2025-07-28 12:59:10');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `inactive_fridges`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `inactive_fridges` (
`id` int(11)
,`name` varchar(100)
,`last_reported_at` datetime
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
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
-- Estructura de tabla para la tabla `system_logs`
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
-- Estructura de tabla para la tabla `temperatures`
--

CREATE TABLE `temperatures` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `recorded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `top_fridges_by_alerts`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `top_fridges_by_alerts` (
`fridge_id` int(11)
,`name` varchar(100)
,`alert_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
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
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `email`, `phone`, `role`, `is_email_verified`, `phone_verified`, `failed_login_attempts`, `last_login_at`, `registered_at`, `updated_at`) VALUES
(36, 'PauloSuperAdmin', 'PauloSuperAdmin', '$2y$10$crI/EPgYYPZvlzDXOU8RA.igR9MyzXZNuQ7qtX2/x.CKsUoNws6ZW', 'PauloSuperAdmin@gmail.com', '2611234567', 'superadmin', 1, 0, 0, '2025-07-28 12:59:10', '2025-07-08 22:36:11', '2025-07-28 12:59:10'),
(37, 'PauloAdmin', 'PauloAdmin', '$2y$10$8fzQG036kBesaodO29LWkundpdqszNywUez7zogBSiQ9Vsio5ke.O', 'PauloAdmin@gmail.com', '2611234567', 'admin', 1, 0, 0, '2025-07-09 17:04:46', '2025-07-08 22:36:21', '2025-07-09 17:04:46'),
(38, 'PauloCliente', 'PauloCliente', '$2y$10$M5IGykk1cOegJQhbu5H7AeYEuDdgfs0S0r6qi4gs.rm37jzlkF48e', 'PauloCliente@gmail.com', '2611234567', 'client', 1, 0, 0, '2025-07-08 22:49:39', '2025-07-08 22:36:35', '2025-07-08 22:49:39'),
(39, 'PauloVisitante', 'PauloVisitante', '$2y$10$gkSkfz7DJ93w7XC5bGCHjO6UJqQxSsBmqV5oEeh82kKHLLFIzcB1W', 'PauloVisitante@gmail.com', '2611234567', 'visitor', 1, 0, 3, '2025-07-08 22:50:03', '2025-07-08 22:36:50', '2025-07-09 17:03:51'),
(40, 'PauloVisitante2', 'PauloVisitante2', '$2y$10$TC0DSvpib5R3D8y3eBNjZ.3oLI/oCl1W749ry8CN/xEmIV8TbAHi6', 'paulocontrera97@gmail.com', '2611234567', 'client', 0, 0, 0, NULL, '2025-07-09 17:11:44', '2025-07-09 17:11:44');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `users_with_most_alerts`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `users_with_most_alerts` (
`user_id` int(11)
,`name` varchar(100)
,`alert_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_change_log`
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
-- Volcado de datos para la tabla `user_change_log`
--

INSERT INTO `user_change_log` (`id`, `user_id`, `field_changed`, `old_value`, `new_value`, `changed_at`, `changed_by`) VALUES
(7, NULL, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 17:56:43', NULL),
(9, NULL, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:00:29', NULL),
(10, NULL, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:00:36', NULL),
(12, NULL, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:00:56', NULL),
(17, NULL, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:08:55', NULL),
(18, NULL, 'test_log', 'valor antiguo', 'valor nuevo', '2025-06-12 18:09:29', NULL),
(19, NULL, 'deleted', '{\"id\":26,\"name\":\"visit\",\"username\":\"visit\",\"password\":\"$2y$10$0Euy\\/5L5J0kgCXiWtgcJpuw5YqgW7IUMOdLVfXaHuBD25CTETissK\",\"email\":\"visit@example.com\",\"phone\":\"2611234567\",\"role\":\"client\",\"email_verified\":0,\"phone_verified\":0,\"failed_login_attempts\":0,\"last_login_at\":null,\"registered_at\":\"2025-06-12 18:10:01\",\"updated_at\":\"2025-06-12 18:10:01\"}', NULL, '2025-06-12 18:10:08', NULL),
(20, NULL, 'deleted', '{\"id\":26,\"name\":\"visit\",\"username\":\"visit\",\"password\":\"$2y$10$0Euy\\/5L5J0kgCXiWtgcJpuw5YqgW7IUMOdLVfXaHuBD25CTETissK\",\"email\":\"visit@example.com\",\"phone\":\"2611234567\",\"role\":\"client\",\"email_verified\":0,\"phone_verified\":0,\"failed_login_attempts\":0,\"last_login_at\":\"2025-06-13 11:26:36\",\"registered_at\":\"2025-06-12 18:10:01\",\"updated_at\":\"2025-06-13 11:26:36\"}', NULL, '2025-06-18 16:40:25', NULL),
(21, NULL, 'deleted', '{\"id\":26,\"name\":\"visit\",\"username\":\"visit\",\"password\":\"$2y$10$0Euy\\/5L5J0kgCXiWtgcJpuw5YqgW7IUMOdLVfXaHuBD25CTETissK\",\"email\":\"visit@example.com\",\"phone\":\"2611234567\",\"role\":\"client\",\"email_verified\":0,\"phone_verified\":0,\"failed_login_attempts\":0,\"last_login_at\":\"2025-06-13 11:26:36\",\"registered_at\":\"2025-06-12 18:10:01\",\"updated_at\":\"2025-06-13 11:26:36\"}', NULL, '2025-06-18 16:59:05', NULL),
(22, NULL, 'deleted', '{\"id\":26,\"0\":26,\"name\":\"visit\",\"1\":\"visit\",\"username\":\"visit\",\"2\":\"visit\",\"email\":\"visit@example.com\",\"3\":\"visit@example.com\",\"phone\":\"2611234567\",\"4\":\"2611234567\",\"role\":\"client\",\"5\":\"client\"}', NULL, '2025-06-18 17:00:50', NULL),
(23, NULL, 'deleted', '{\"id\":27,\"0\":27,\"name\":\"visit\",\"1\":\"visit\",\"username\":\"visit\",\"2\":\"visit\",\"email\":\"visit@example.com\",\"3\":\"visit@example.com\",\"phone\":\"2611234567\",\"4\":\"2611234567\",\"role\":\"client\",\"5\":\"client\"}', NULL, '2025-06-18 17:02:04', NULL),
(24, NULL, 'name', 'visit', 'cliente', '2025-06-18 17:10:48', NULL),
(25, NULL, 'email', 'visit@example.com', 'cliente@example.com', '2025-06-18 17:10:48', NULL),
(26, NULL, 'email', 'cliente@example.com', 'clientee@example.com', '2025-06-18 17:11:21', NULL),
(27, NULL, 'name', 'cliente', 'clientee', '2025-06-18 17:11:32', NULL),
(28, NULL, 'email', 'clientee@example.com', 'cliente1@example.com', '2025-06-18 17:12:29', NULL),
(29, NULL, 'email', 'cliente1@example.com', 'client1@mail.com', '2025-06-18 17:13:17', NULL),
(30, NULL, 'phone', '2611234567', '', '2025-06-18 17:13:18', NULL),
(31, NULL, 'name', 'clientee', 'clientee22', '2025-06-18 17:16:35', NULL),
(32, NULL, 'email', 'client1@mail.com', 'client122@mail.com', '2025-06-18 17:16:35', NULL),
(33, NULL, 'name', 'clientee22', 'clientee', '2025-06-18 17:17:58', NULL),
(34, NULL, 'email', 'client122@mail.com', 'cliente@mail.com', '2025-06-18 17:17:58', NULL),
(35, NULL, 'phone', '', '2611234567', '2025-06-18 17:17:58', NULL),
(36, NULL, 'phone', '2611234567', '261123456bbgvggtv7', '2025-06-18 17:19:08', NULL),
(37, NULL, 'username', 'visit', 'visit2', '2025-06-18 17:29:44', NULL),
(38, NULL, 'username', 'visit2', 'visi', '2025-06-18 17:30:32', NULL),
(39, NULL, 'username', 'visi', 'visi', '2025-06-18 17:30:33', NULL),
(40, NULL, 'username', 'visi', 'visi', '2025-06-18 17:30:35', NULL),
(41, NULL, 'username', 'visi', 'visi', '2025-06-18 17:30:46', NULL),
(42, NULL, 'username', 'visi', 'visiasasd', '2025-06-18 17:31:12', NULL),
(43, NULL, 'name', 'clientee', 'cliente', '2025-07-01 17:41:00', NULL),
(44, NULL, 'email', 'cliente@mail.com', 'clientee@mail.com', '2025-07-01 17:41:00', NULL),
(45, NULL, 'phone', '261123456bbgvggtv7', '111', '2025-07-01 17:41:00', NULL),
(46, 39, 'username', 'PauloVisitante', 'avisiot', '2025-07-08 22:55:09', 37),
(47, 39, 'username', 'avisiot', 'PauloVisitante', '2025-07-08 22:55:29', 37),
(48, 39, 'password', '***', '***', '2025-07-08 22:56:51', 37),
(49, 39, 'password', '***', '***', '2025-07-08 22:57:06', 37);

-- --------------------------------------------------------

--
-- Estructura para la vista `inactive_fridges`
--
DROP TABLE IF EXISTS `inactive_fridges`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inactive_fridges`  AS SELECT `d`.`id` AS `id`, `d`.`name` AS `name`, `d`.`last_reported_at` AS `last_reported_at` FROM `devices` AS `d` WHERE `d`.`last_reported_at` < current_timestamp() - interval 1 day ;

-- --------------------------------------------------------

--
-- Estructura para la vista `top_fridges_by_alerts`
--
DROP TABLE IF EXISTS `top_fridges_by_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `top_fridges_by_alerts`  AS SELECT `d`.`id` AS `fridge_id`, `d`.`name` AS `name`, count(`a`.`id`) AS `alert_count` FROM (`devices` `d` join `alerts` `a` on(`a`.`device_id` = `d`.`id`)) GROUP BY `d`.`id` ORDER BY count(`a`.`id`) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `users_with_most_alerts`
--
DROP TABLE IF EXISTS `users_with_most_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users_with_most_alerts`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `name`, count(`a`.`id`) AS `alert_count` FROM ((`users` `u` join `devices` `d` on(`d`.`user_id` = `u`.`id`)) join `alerts` `a` on(`a`.`device_id` = `d`.`id`)) GROUP BY `u`.`id` ORDER BY count(`a`.`id`) DESC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indices de la tabla `alert_suppression`
--
ALTER TABLE `alert_suppression`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Indices de la tabla `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_code` (`device_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indices de la tabla `device_access`
--
ALTER TABLE `device_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_access` (`device_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `device_access_log`
--
ALTER TABLE `device_access_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `target_user` (`target_user`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indices de la tabla `device_change_log`
--
ALTER TABLE `device_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_change_log_fk_device` (`device_id`),
  ADD KEY `device_change_log_fk_user` (`user_id`);

--
-- Indices de la tabla `device_groups`
--
ALTER TABLE `device_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `device_group_change_log`
--
ALTER TABLE `device_group_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `device_inactivity_log`
--
ALTER TABLE `device_inactivity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indices de la tabla `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `event_logs`
--
ALTER TABLE `event_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `temperatures`
--
ALTER TABLE `temperatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_change_log`
--
ALTER TABLE `user_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alert_suppression`
--
ALTER TABLE `alert_suppression`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `device_access`
--
ALTER TABLE `device_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `device_access_log`
--
ALTER TABLE `device_access_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `device_change_log`
--
ALTER TABLE `device_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT de la tabla `device_groups`
--
ALTER TABLE `device_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `device_group_change_log`
--
ALTER TABLE `device_group_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `device_inactivity_log`
--
ALTER TABLE `device_inactivity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `event_logs`
--
ALTER TABLE `event_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `temperatures`
--
ALTER TABLE `temperatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `user_change_log`
--
ALTER TABLE `user_change_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `alert_suppression`
--
ALTER TABLE `alert_suppression`
  ADD CONSTRAINT `alert_suppression_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alert_suppression_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devices_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `device_groups` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `device_access`
--
ALTER TABLE `device_access`
  ADD CONSTRAINT `device_access_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_access_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `device_access_log`
--
ALTER TABLE `device_access_log`
  ADD CONSTRAINT `device_access_log_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `device_access_log_ibfk_2` FOREIGN KEY (`target_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `device_access_log_ibfk_3` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `device_change_log`
--
ALTER TABLE `device_change_log`
  ADD CONSTRAINT `device_change_log_fk_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `device_change_log_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `device_groups`
--
ALTER TABLE `device_groups`
  ADD CONSTRAINT `device_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `device_group_change_log`
--
ALTER TABLE `device_group_change_log`
  ADD CONSTRAINT `device_group_change_log_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `device_groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `device_group_change_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `device_inactivity_log`
--
ALTER TABLE `device_inactivity_log`
  ADD CONSTRAINT `device_inactivity_log_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `temperatures`
--
ALTER TABLE `temperatures`
  ADD CONSTRAINT `temperatures_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_change_log`
--
ALTER TABLE `user_change_log`
  ADD CONSTRAINT `user_change_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_change_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
