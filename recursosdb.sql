-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-08-2025 a las 01:19:43
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `recursosdb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--
DROP TABLE IF EXISTS `asistencia`;
CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `id_empleados` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `horaEntrada` time NOT NULL,
  `horaSalida` time NOT NULL,
  `llego_tarde` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id_asistencia`, `id_empleados`, `fecha`, `horaEntrada`, `horaSalida`, `llego_tarde`) VALUES
(1, 12, '2025-06-30', '16:34:15', '00:00:00', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--
DROP TABLE IF EXISTS `empleados`;
CREATE TABLE `empleados` (
  `id_empleados` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `dni` varchar(15) NOT NULL,
  `nro_legajo` int(11) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` enum('empleado','admin') NOT NULL DEFAULT 'empleado',
  `codigo_barras` varchar(100) DEFAULT NULL,
  `clave` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id_empleados`, `nombre`, `apellido`, `dni`, `nro_legajo`, `telefono`, `email`, `rol`, `codigo_barras`, `clave`) VALUES
(21, 'Silvina', 'Pereyra', '00000000', 1, NULL, NULL, 'admin', NULL, '$2y$10$5Dvc4APZ2Gf9YBUDEsPEYe/3mjuomnSJZPJOSjIX9kiOrxsqvt0l.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--
DROP TABLE IF EXISTS `horario`;
CREATE TABLE `horario` (
  `id` int(11) NOT NULL,
  `hora_limite_entrada` time NOT NULL,
  `hora_limite_salida` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`id`, `hora_limite_entrada`, `hora_limite_salida`) VALUES
(1, '18:30:00', '20:00:00');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id_asistencia`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleados`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleados` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `horario`
--
ALTER TABLE `horario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
