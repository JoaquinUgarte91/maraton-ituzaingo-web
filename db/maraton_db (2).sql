-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-10-2025 a las 22:37:56
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
-- Base de datos: `maraton_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_evento`
--

CREATE TABLE `configuracion_evento` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_evento`
--

INSERT INTO `configuracion_evento` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1, 'fecha_inscripcion_inicio', '2026-02-23', 'Fecha de inicio de inscripciones'),
(2, 'fecha_maraton', '2026-03-08', 'Fecha de la maratón'),
(3, 'lugar_maraton', 'Plaza 20 de febrero, Las Heras y Zufriategui, Ituzaingó', 'Lugar del evento'),
(4, 'horario_entrada_calor', '7:30hs', 'Horario de entrada en calor'),
(5, 'email_contacto', 'mujeres_mimaraton@miituzaingo.gob.ar', 'Email de contacto'),
(6, 'instagram_url', 'https://www.instagram.com/consejogeneros.ituzaingo', 'URL de Instagram');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_requeridos`
--

CREATE TABLE `documentos_requeridos` (
  `id` int(11) NOT NULL,
  `documento` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `obligatorio` tinyint(1) DEFAULT 1,
  `para_menores` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos_requeridos`
--

INSERT INTO `documentos_requeridos` (`id`, `documento`, `descripcion`, `obligatorio`, `para_menores`) VALUES
(1, 'Deslinde de responsabilidad', 'Impreso y firmado de puño y letra', 1, 0),
(2, 'Fotocopia del DNI', 'Documento de identidad', 1, 0),
(3, 'Autorización para menores', 'Autorización impresa y firmada por el adulto responsable', 1, 1),
(4, 'Fotocopia DNI menor', 'Fotocopia del DNI del niño/a o adolescente', 1, 1),
(5, 'Autorización retiro kit', 'Para retirar kit de otra persona', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `carrera` varchar(15) NOT NULL,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_nacimiento` date DEFAULT NULL,
  `talle_remera` varchar(10) DEFAULT NULL,
  `cobertura_medica` varchar(100) DEFAULT NULL,
  `numero_afiliado` varchar(50) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `numero_corredor` int(11) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `limites_inscripciones`
--

CREATE TABLE `limites_inscripciones` (
  `id` int(11) NOT NULL,
  `categoria` varchar(15) NOT NULL,
  `limite` int(11) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `limites_inscripciones`
--

INSERT INTO `limites_inscripciones` (`id`, `categoria`, `limite`, `descripcion`) VALUES
(1, '10km', 4000, 'Carrera de 10km - Límite 4,000 participantes'),
(2, '3km', 5000, 'Carrera de 3km - Límite 5,000 participantes'),
(3, 'Kids', 1000, 'Carrera Kids - Límite 1,000 participantes'),
(4, 'total', 15000, 'Límite total de inscripciones');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `configuracion_evento`
--
ALTER TABLE `configuracion_evento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave_unique` (`clave`);

--
-- Indices de la tabla `documentos_requeridos`
--
ALTER TABLE `documentos_requeridos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni_unique` (`dni`),
  ADD KEY `idx_carrera` (`carrera`),
  ADD KEY `idx_fecha_inscripcion` (`fecha_inscripcion`);

--
-- Indices de la tabla `limites_inscripciones`
--
ALTER TABLE `limites_inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categoria_unique` (`categoria`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `configuracion_evento`
--
ALTER TABLE `configuracion_evento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `documentos_requeridos`
--
ALTER TABLE `documentos_requeridos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla `limites_inscripciones`
--
ALTER TABLE `limites_inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
