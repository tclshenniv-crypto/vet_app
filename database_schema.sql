-- ========================================================
-- Estructura de la Base de Datos: vet_clinica
-- Sistema de Gestión Veterinaria VetApp
-- ========================================================

CREATE DATABASE IF NOT EXISTS `vet_clinica` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `vet_clinica`;

-- Tabla: clientes
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula_doc` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono_principal` varchar(20) NOT NULL,
  `telefono_secundario` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `notas_contacto` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: pacientes
DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `raza` varchar(50) DEFAULT NULL,
  `sexo` enum('Macho','Hembra') NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `peso_actual` decimal(5,2) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `padecimientos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: consultas
DROP TABLE IF EXISTS `consultas`;
CREATE TABLE `consultas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `motivo` text NOT NULL,
  `diagnostico` text NOT NULL,
  `tratamiento` text NOT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `archivo_nombre_original` varchar(255) DEFAULT NULL,
  `archivo_tipo` varchar(100) DEFAULT NULL,
  `peso_en_consulta` decimal(5,2) DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `frecuencia_cardiaca` varchar(50) DEFAULT NULL,
  `mucosas` varchar(50) DEFAULT NULL,
  `veterinario` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: vacunas_recordatorios
DROP TABLE IF EXISTS `vacunas_recordatorios`;
CREATE TABLE `vacunas_recordatorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `tipo` enum('Vacuna','Desparasitación','Tratamiento','Revisión') NOT NULL DEFAULT 'Vacuna',
  `titulo` varchar(100) NOT NULL,
  `fecha_aplicacion` date NOT NULL,
  `proxima_dosis` date NOT NULL,
  `estado` enum('Pendiente','Notificado','Completado') DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `vacunas_recordatorios_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

