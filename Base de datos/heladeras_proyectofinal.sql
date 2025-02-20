-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-02-2025 a las 02:48:44
-- Versión del servidor: 10.4.25-MariaDB
-- Versión de PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `heladeras_proyectofinal`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_temp_records` ()   BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE temp DECIMAL(5, 2);


    WHILE i < 6 DO  -- Insertar solo 6 registros para simplificar
        -- Generar temperatura aleatoria entre 2 y 8 grados, con algunos valores fuera de rango
        SET temp = 2 + (RAND() * 6);
        IF RAND() < 0.1 THEN
            SET temp = temp + (RAND() * 2 - 1); -- Añadir o restar hasta 1 grado para algunos registros
        END IF;

        -- Insertar registro para Heladera 1
        INSERT INTO temperature_records (fridge_id, temperature, recorded_at)
        VALUES (1, temp, NOW());

        -- Generar temperatura para Heladera 2
        SET temp = 2 + (RAND() * 6);
        IF RAND() < 0.1 THEN
            SET temp = temp + (RAND() * 2 - 1);
        END IF;

        -- Insertar registro para Heladera 2
        INSERT INTO temperature_records (fridge_id, temperature, recorded_at)
        VALUES (2, temp, NOW());

        -- Incrementar el contador
        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `access_permissions`
--

CREATE TABLE `access_permissions` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) DEFAULT NULL,
  `visitor_id` bigint(20) DEFAULT NULL,
  `fridge_id` bigint(20) DEFAULT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_receive_alerts` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `access_permissions`
--

INSERT INTO `access_permissions` (`id`, `client_id`, `visitor_id`, `fridge_id`, `can_view`, `can_receive_alerts`) VALUES
(1, 9, 10, 1, 1, 1),
(2, 9, 10, 2, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerts`
--

CREATE TABLE `alerts` (
  `id` bigint(20) NOT NULL,
  `fridge_id` bigint(20) DEFAULT NULL,
  `temperature_record_id` bigint(20) DEFAULT NULL,
  `alert_type` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `alerts`
--

INSERT INTO `alerts` (`id`, `fridge_id`, `temperature_record_id`, `alert_type`, `created_at`) VALUES
(1, 1, 28, 'Excede el límite', '2024-08-15 16:49:48'),
(2, 1, 30, 'Excede el límite', '2024-08-15 16:54:00'),
(3, 1, 31, 'Excede el límite', '2024-08-19 11:32:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fridges`
--

CREATE TABLE `fridges` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `min_temp` decimal(5,2) NOT NULL,
  `max_temp` decimal(5,2) NOT NULL,
  `client_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `fridges`
--

INSERT INTO `fridges` (`id`, `name`, `location`, `min_temp`, `max_temp`, `client_id`) VALUES
(1, 'Heladera 1', 'Ubicación 1', '2.00', '8.00', 9),
(2, 'Heladera 2', 'Ubicación 2', '1.00', '7.00', 9),
(3, 'Heladera de otro cliente', 'tu casa', '0.00', '20.00', 12),
(4, 'Heladera de otro cliente otra ves', 'tu casa', '0.00', '20.00', 12),
(5, 'Heladera de', 'tu casa', '-5.00', '10.00', 12),
(6, 'Heladera de', 'tu casa', '-5.00', '10.00', 12),
(7, 'Heladera de', 'tu casa', '-5.00', '10.00', 12),
(8, 'Heladera de', 'tu casa', '-5.00', '10.00', 12),
(9, 'Heladera de', 'tu casa', '-5.00', '10.00', 12),
(10, 'Heladera de', 'tu casa', '-5.00', '10.00', 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fridge_groups`
--

CREATE TABLE `fridge_groups` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `client_id` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `fridge_groups`
--

INSERT INTO `fridge_groups` (`id`, `name`, `client_id`, `description`) VALUES
(1, 'Grupo 1', 9, 'Descripción del Grupo 1'),
(2, 'Grupete', 12, 'Porque hay que probar la wea'),
(5, 'Default', 23, NULL),
(6, 'Default', 24, NULL),
(7, 'Default', 9, NULL),
(8, 'Default', 12, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fridge_group_members`
--

CREATE TABLE `fridge_group_members` (
  `fridge_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `fridge_group_members`
--

INSERT INTO `fridge_group_members` (`fridge_id`, `group_id`) VALUES
(1, 1),
(2, 1),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temperature_records`
--

CREATE TABLE `temperature_records` (
  `id` bigint(20) NOT NULL,
  `fridge_id` bigint(20) DEFAULT NULL,
  `temperature` decimal(5,2) NOT NULL,
  `recorded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `temperature_records`
--

INSERT INTO `temperature_records` (`id`, `fridge_id`, `temperature`, `recorded_at`) VALUES
(1, 1, '4.10', '2024-08-15 11:42:37'),
(2, 2, '2.83', '2024-08-15 11:42:37'),
(3, 1, '4.89', '2024-08-15 11:42:37'),
(4, 2, '7.41', '2024-08-15 11:42:37'),
(5, 1, '6.86', '2024-08-15 11:42:37'),
(6, 2, '3.58', '2024-08-15 11:42:37'),
(7, 1, '6.36', '2024-08-15 11:42:37'),
(8, 2, '4.60', '2024-08-15 11:42:37'),
(9, 1, '6.08', '2024-08-15 11:42:37'),
(10, 2, '2.11', '2024-08-15 11:42:37'),
(11, 1, '3.39', '2024-08-15 11:42:37'),
(12, 2, '3.42', '2024-08-15 11:42:37'),
(13, 1, '5.18', '2024-08-15 11:42:47'),
(14, 2, '3.78', '2024-08-15 11:42:47'),
(15, 1, '7.29', '2024-08-15 11:42:48'),
(16, 2, '2.41', '2024-08-15 11:42:48'),
(17, 1, '4.17', '2024-08-15 11:42:48'),
(18, 2, '3.52', '2024-08-15 11:42:48'),
(19, 1, '5.32', '2024-08-15 11:42:48'),
(20, 2, '3.58', '2024-08-15 11:42:49'),
(21, 1, '6.11', '2024-08-15 11:42:49'),
(22, 2, '5.01', '2024-08-15 11:42:49'),
(23, 1, '4.21', '2024-08-15 11:42:49'),
(24, 2, '4.94', '2024-08-15 11:42:49'),
(25, 1, '6.50', '2024-08-15 16:34:40'),
(26, 1, '6.50', '2024-08-15 16:36:55'),
(27, 1, '6.50', '2024-08-15 16:49:29'),
(28, 1, '10.50', '2024-08-15 16:49:48'),
(29, 1, '8.00', '2024-08-15 16:53:48'),
(30, 1, '22.00', '2024-08-15 16:54:00'),
(31, 1, '22.00', '2024-08-19 11:32:26'),
(32, 1, '8.00', '2024-08-19 11:32:39'),
(33, 1, '8.00', '2024-08-20 18:27:10'),
(34, 1, '8.00', '2024-08-29 18:51:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`, `phone_number`, `role`) VALUES
(1, 'SuperAdmin', '$2y$10$UDsVsPOdNyMAAtavBRhNCuh67ewe.icSF7jstCCm7k4zZJW.Xy0.u', 'SuperAdmin@pf.com', '2024-08-16 19:19:13', '1234567890', 'SuperAdmin'),
(2, 'Admin', '$2y$10$RD55G0jYf0yTfp9tacjxuuggimuYyehXWGCBVTVpDVEhO8QwtFQzO', 'Admin@pf.com', '2024-08-16 19:18:33', '1234567890', 'Admin'),
(9, 'Cliente', '$2y$10$EZnWs0HrDMf5jTMJ5tzfWuIqJZmSkD1nMPVZw4/RdkWUYX3RGf2kC', 'Cliente@pf.com', '2024-08-16 19:18:45', '1234567890', 'Cliente'),
(10, 'Visitante', '$2y$10$zfmJRQ31nNv0rh3GTu.Uku19/JR5HKcn1lsJqP7epgBliBr3q4DT.', 'Visitante@pf.com', '2024-08-16 19:18:55', '1234567890', 'Visitante'),
(12, 'Cliente2', '$2y$10$f3WGm6KBh1UCICdIzdblAuNujKfJFoOEKHmz2hAyIcRNwWdT.GQfK', 'Cliente2@pf.com', '2024-08-19 17:44:48', '1234567890', 'Cliente2'),
(23, 'Cliente1', '$2y$10$vQaeSGbi00Gb50L9rOdGneCDW2J1dOKrddnQOTtcoKLQ2KqyqxP5e', 'Cliente1@pf.com', '2024-08-20 12:33:01', '1234567890', 'Cliente'),
(24, 'Cliente3', '$2y$10$clbOm0q1qOZlnnrXZJE1VObdLFP1B8L5FobL5OQzrQjVBbbVHeTzq', 'Cliente3@pf.com', '2024-08-20 12:35:01', '1234567890', 'Cliente');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `access_permissions`
--
ALTER TABLE `access_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `visitor_id` (`visitor_id`),
  ADD KEY `fridge_id` (`fridge_id`);

--
-- Indices de la tabla `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fridge_id` (`fridge_id`),
  ADD KEY `temperature_record_id` (`temperature_record_id`);

--
-- Indices de la tabla `fridges`
--
ALTER TABLE `fridges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indices de la tabla `fridge_groups`
--
ALTER TABLE `fridge_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indices de la tabla `fridge_group_members`
--
ALTER TABLE `fridge_group_members`
  ADD PRIMARY KEY (`fridge_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indices de la tabla `temperature_records`
--
ALTER TABLE `temperature_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fridge_id` (`fridge_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `access_permissions`
--
ALTER TABLE `access_permissions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `fridges`
--
ALTER TABLE `fridges`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `fridge_groups`
--
ALTER TABLE `fridge_groups`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `temperature_records`
--
ALTER TABLE `temperature_records`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `access_permissions`
--
ALTER TABLE `access_permissions`
  ADD CONSTRAINT `access_permissions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `access_permissions_ibfk_2` FOREIGN KEY (`visitor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `access_permissions_ibfk_3` FOREIGN KEY (`fridge_id`) REFERENCES `fridges` (`id`);

--
-- Filtros para la tabla `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`fridge_id`) REFERENCES `fridges` (`id`),
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`temperature_record_id`) REFERENCES `temperature_records` (`id`);

--
-- Filtros para la tabla `fridges`
--
ALTER TABLE `fridges`
  ADD CONSTRAINT `fridges_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `fridge_groups`
--
ALTER TABLE `fridge_groups`
  ADD CONSTRAINT `fridge_groups_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `fridge_group_members`
--
ALTER TABLE `fridge_group_members`
  ADD CONSTRAINT `fridge_group_members_ibfk_1` FOREIGN KEY (`fridge_id`) REFERENCES `fridges` (`id`),
  ADD CONSTRAINT `fridge_group_members_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `fridge_groups` (`id`);

--
-- Filtros para la tabla `temperature_records`
--
ALTER TABLE `temperature_records`
  ADD CONSTRAINT `temperature_records_ibfk_1` FOREIGN KEY (`fridge_id`) REFERENCES `fridges` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
