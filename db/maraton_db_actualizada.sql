-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-12-2025 a las 00:33:06
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
-- Estructura de tabla para la tabla `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$nUjYPUxGTBSb.YWsEu.jUeNPo65JBzlj7.QfKhfFtr7py7ShOOQy.', 1, '2025-12-16 01:46:43');

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
  `categoria` varchar(50) DEFAULT NULL,
  `kit_retirado` tinyint(1) NOT NULL DEFAULT 0,
  `kit_retirado_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `nombre`, `dni`, `email`, `carrera`, `fecha_inscripcion`, `fecha_nacimiento`, `talle_remera`, `cobertura_medica`, `numero_afiliado`, `telefono_emergencia`, `numero_corredor`, `categoria`, `kit_retirado`, `kit_retirado_at`) VALUES
(61, 'joa ugarte', '36598163', 'joa@gmail.com', '3km', '2025-12-16 00:44:36', '1991-11-11', 'Adulto M', 'swiss medical', '112321-4324324', '1224354332', NULL, NULL, 0, NULL),
(63, 'mateo german', '55473212', 'mateo123456@gmail.com', 'Kids', '2025-12-19 12:59:21', '2016-11-08', 'Niño 8', 'swiss medical', '112321-432111', '321375214', NULL, NULL, 0, NULL),
(64, 'nico rew', '7362685', 'prueba@gmail.com', '10km', '2025-12-19 13:15:15', '1998-11-11', 'Adulto M', 'swiss medical', '232132131', '3232132', NULL, NULL, 1, '2025-12-28 19:15:58'),
(65, 'aye ret', '32453243', 'aye4323@gmail.com', '10km', '2025-12-21 13:57:59', '1997-11-11', 'M', 'mediucas', '13213214324', '1134632874', NULL, NULL, 1, '2025-12-26 21:04:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones_historicas`
--

CREATE TABLE `inscripciones_historicas` (
  `id` int(11) NOT NULL,
  `edicion` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `dni` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `carrera` varchar(20) NOT NULL,
  `fecha_inscripcion` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripciones_historicas`
--

INSERT INTO `inscripciones_historicas` (`id`, `edicion`, `nombre`, `dni`, `email`, `carrera`, `fecha_inscripcion`, `created_at`) VALUES
(1, 2024, 'Luis Perez', '28987654', 'luis.perez@mail.com', '3km', '2024-02-11 10:30:00', '2025-12-18 01:25:07'),
(2, 2024, 'Carla Ramirez', '32555111', 'carla.ramirez@mail.com', 'Kids', '2024-02-12 15:45:00', '2025-12-18 01:25:07'),
(3, 2024, 'Marcos Sosa', '27888999', 'marcos.sosa@mail.com', '10km', '2024-02-13 18:20:00', '2025-12-18 01:25:07'),
(4, 2024, 'Julieta Fernández', '31222333', 'julieta.fernandez@mail.com', '3km', '2024-02-14 11:05:00', '2025-12-18 01:25:07'),
(5, 2024, 'Luis Perez', '28987654', 'luis.perez@mail.com', '3km', '2024-02-11 10:30:00', '2025-12-19 14:05:35'),
(6, 2024, 'Carla Ramirez', '32555111', 'carla.ramirez@mail.com', 'Kids', '2024-02-12 15:45:00', '2025-12-19 14:05:35'),
(7, 2024, 'Marcos Sosa', '27888999', 'marcos.sosa@mail.com', '10km', '2024-02-13 18:20:00', '2025-12-19 14:05:35'),
(8, 2024, 'Julieta Fernández', '31222333', 'julieta.fernandez@mail.com', '3km', '2024-02-14 11:05:00', '2025-12-19 14:05:35');

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
-- Indices de la tabla `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

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
-- Indices de la tabla `inscripciones_historicas`
--
ALTER TABLE `inscripciones_historicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_edicion` (`edicion`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_email` (`email`);

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
-- AUTO_INCREMENT de la tabla `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `inscripciones_historicas`
--
ALTER TABLE `inscripciones_historicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `limites_inscripciones`
--
ALTER TABLE `limites_inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
