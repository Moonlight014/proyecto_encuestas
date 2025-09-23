-- ===============================================
-- CONSULTAS SQL COMPLETAS PARA DAS HUALPÉN (Actualizado)
-- Esquema y datos alineados con das_encuestas.sql (MariaDB 10.4)
-- Usa CREATE DATABASE/TABLE IF NOT EXISTS e inserts idempotentes
-- ===============================================

-- Crear la base de datos y usarla
CREATE DATABASE IF NOT EXISTS `das_encuestas` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `das_encuestas`;

-- ===============================================
-- ESTRUCTURA DE TABLAS (con claves y FKs inline)
-- ===============================================

-- categorias
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `color` varchar(7) DEFAULT '#007bff',
    `activo` tinyint(1) DEFAULT 1,
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- tipos_pregunta
CREATE TABLE IF NOT EXISTS `tipos_pregunta` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(50) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `activo` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- departamentos
CREATE TABLE IF NOT EXISTS `departamentos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `codigo` varchar(50) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `activo` tinyint(1) DEFAULT 1,
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `apellido` varchar(100) NOT NULL,
    `email` varchar(150) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `departamento_id` int(11) DEFAULT NULL,
    `cargo` varchar(100) DEFAULT NULL,
    `rol` enum('super_admin','admin_departamental','visualizador') DEFAULT 'visualizador',
    `activo` tinyint(1) DEFAULT 0,
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    `ultimo_acceso` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `departamento_id` (`departamento_id`),
    CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- banco_preguntas
CREATE TABLE IF NOT EXISTS `banco_preguntas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `categoria_id` int(11) NOT NULL,
    `tipo_pregunta_id` int(11) NOT NULL,
    `texto` text NOT NULL,
    `opciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opciones`)),
    `orden` int(11) DEFAULT 0,
    `departamento` varchar(100) DEFAULT 'general',
    `obligatoria` tinyint(1) DEFAULT 0,
    `activa` tinyint(1) DEFAULT 1,
    `created_by` varchar(50) DEFAULT 'admin',
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_banco_categoria` (`categoria_id`),
    KEY `idx_banco_tipo` (`tipo_pregunta_id`),
    KEY `idx_banco_departamento` (`departamento`),
    CONSTRAINT `banco_preguntas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
    CONSTRAINT `banco_preguntas_ibfk_2` FOREIGN KEY (`tipo_pregunta_id`) REFERENCES `tipos_pregunta` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- encuestas
CREATE TABLE IF NOT EXISTS `encuestas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `titulo` varchar(255) NOT NULL,
    `descripcion` text NOT NULL,
    `departamento_id` int(11) NOT NULL,
    `creado_por` int(11) NOT NULL,
    `estado` enum('borrador','activa','pausada','finalizada') DEFAULT 'borrador',
    `fecha_inicio` datetime DEFAULT NULL,
    `fecha_fin` datetime DEFAULT NULL,
    `enlace_publico` varchar(100) NOT NULL,
    `codigo_qr` text DEFAULT NULL,
    `configuracion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuracion`)),
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `enlace_publico` (`enlace_publico`),
    KEY `creado_por` (`creado_por`),
    KEY `idx_encuestas_estado` (`estado`),
    KEY `idx_encuestas_departamento` (`departamento_id`),
    CONSTRAINT `encuestas_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`),
    CONSTRAINT `encuestas_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- encuesta_preguntas
CREATE TABLE IF NOT EXISTS `encuesta_preguntas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `encuesta_id` int(11) NOT NULL,
    `pregunta_id` int(11) NOT NULL,
    `orden` int(11) NOT NULL,
    `seccion` varchar(100) DEFAULT NULL,
    `obligatoria_encuesta` tinyint(1) DEFAULT 0,
    `configuracion_especifica` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuracion_especifica`)),
    `activa` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_encuesta_pregunta_orden` (`encuesta_id`,`orden`),
    KEY `pregunta_id` (`pregunta_id`),
    CONSTRAINT `encuesta_preguntas_ibfk_1` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `encuesta_preguntas_ibfk_2` FOREIGN KEY (`pregunta_id`) REFERENCES `banco_preguntas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- respuestas_encuesta
CREATE TABLE IF NOT EXISTS `respuestas_encuesta` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `encuesta_id` int(11) NOT NULL,
    `ip_hash` varchar(255) DEFAULT NULL,
    `sesion_token` varchar(100) NOT NULL,
    `estado` enum('iniciada','completada','abandonada') DEFAULT 'iniciada',
    `progreso_porcentaje` decimal(5,2) DEFAULT 0.00,
    `fecha_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
    `fecha_completada` timestamp NULL DEFAULT NULL,
    `fecha_ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `sesion_token` (`sesion_token`),
    KEY `idx_respuestas_encuesta` (`encuesta_id`,`estado`),
    CONSTRAINT `respuestas_encuesta_ibfk_1` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- respuestas_detalle
CREATE TABLE IF NOT EXISTS `respuestas_detalle` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `respuesta_encuesta_id` int(11) NOT NULL,
    `pregunta_id` int(11) NOT NULL,
    `valor_respuesta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`valor_respuesta`)),
    `fecha_respuesta` timestamp NOT NULL DEFAULT current_timestamp(),
    `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_respuesta_pregunta` (`respuesta_encuesta_id`,`pregunta_id`),
    KEY `pregunta_id` (`pregunta_id`),
    CONSTRAINT `respuestas_detalle_ibfk_1` FOREIGN KEY (`respuesta_encuesta_id`) REFERENCES `respuestas_encuesta` (`id`) ON DELETE CASCADE,
    CONSTRAINT `respuestas_detalle_ibfk_2` FOREIGN KEY (`pregunta_id`) REFERENCES `banco_preguntas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- sesiones_usuario
CREATE TABLE IF NOT EXISTS `sesiones_usuario` (
    `id` varchar(128) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `datos_sesion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_sesion`)),
    `fecha_expiracion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `usuario_id` (`usuario_id`),
    CONSTRAINT `sesiones_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===============================================
-- DATOS BASE (INSERT IGNORE para idempotencia)
-- ===============================================

-- categorias
INSERT IGNORE INTO `categorias` (`id`, `nombre`, `descripcion`, `color`, `activo`, `fecha_creacion`) VALUES
(1, 'Satisfacción', 'Preguntas sobre satisfacción general', '#28a745', 1, '2025-09-22 13:00:09'),
(2, 'Atención Primaria', 'Preguntas específicas de atención primaria', '#007bff', 1, '2025-09-22 13:00:09'),
(3, 'Atención Secundaria', 'Preguntas sobre derivaciones y especialistas', '#17a2b8', 1, '2025-09-22 13:00:09'),
(4, 'Atención Terciaria', 'Preguntas sobre hospitalizaciones y urgencias', '#dc3545', 1, '2025-09-22 13:00:09'),
(5, 'CESFAM', 'Preguntas específicas del Centro de Salud Familiar', '#ffc107', 1, '2025-09-22 13:00:09'),
(6, 'CECOSF', 'Preguntas del Centro Comunitario de Salud Familiar', '#6f42c1', 1, '2025-09-22 13:00:09'),
(7, 'SAPU', 'Preguntas del Servicio de Atención Primaria de Urgencia', '#fd7e14', 1, '2025-09-22 13:00:09'),
(8, 'Postas Rurales', 'Preguntas sobre atención en postas rurales', '#198754', 1, '2025-09-22 13:00:09'),
(9, 'Servicios', 'Preguntas sobre servicios específicos de salud', '#0d6efd', 1, '2025-09-22 13:00:09'),
(10, 'General', 'Preguntas demográficas y generales', '#6c757d', 1, '2025-09-22 13:00:09');

-- tipos_pregunta (incluye tipos extendidos)
INSERT IGNORE INTO `tipos_pregunta` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'texto_corto', 'Campo de texto de una línea', 1),
(2, 'texto_largo', 'Área de texto multilínea', 1),
(3, 'opcion_multiple', 'Selección única entre varias opciones', 1),
(4, 'seleccion_multiple', 'Seleccion múltiple entre varias opciones', 1),
(5, 'escala_likert', 'Escala de 1 a 5 (Likert)', 1),
(6, 'escala_numerica', 'Escala numérica con rango personalizable', 1),
(7, 'numero', 'Campo numérico simple', 1),
(8, 'fecha', 'Selector de fecha', 1),
(9, 'email', 'Campo de correo electrónico con validación', 1),
(10, 'clasificacion', 'Clasificación por orden de preferencia (ranking)', 1),
(11, 'matriz_seleccion', 'Matriz de opciones múltiples (grid de radio buttons)', 1),
(12, 'matriz_escala', 'Matriz de escalas (grid de escalas Likert)', 1),
(13, 'slider', 'Control deslizante para valores numéricos', 1),
(14, 'calificacion_estrellas', 'Calificación con estrellas (1-5 estrellas)', 1),
(18, 'net_promoter_score', 'Net Promoter Score (NPS) 0-10', 1),
(19, 'contacto', 'Información de contacto (nombre, email, teléfono)', 1);

-- departamentos
INSERT IGNORE INTO `departamentos` (`id`, `codigo`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'general', 'General', 'Departamento General', 1, '2025-09-22 13:00:09'),
(2, 'atencion-primaria', 'Atención Primaria', 'Departamento de Atención Primaria de Salud', 1, '2025-09-22 13:00:09'),
(3, 'atencion-secundaria', 'Atención Secundaria', 'Departamento de Atención Secundaria', 1, '2025-09-22 13:00:09'),
(4, 'atencion-terciaria', 'Atención Terciaria', 'Departamento de Atención Terciaria', 1, '2025-09-22 13:00:09'),
(5, 'cesfam', 'CESFAM', 'Centro de Salud Familiar', 1, '2025-09-22 13:00:09'),
(6, 'cecosf', 'CECOSF', 'Centro Comunitario de Salud Familiar', 1, '2025-09-22 13:00:09'),
(7, 'sapu', 'SAPU', 'Servicio de Atención Primaria de Urgencia', 1, '2025-09-22 13:00:09'),
(8, 'postas-rurales', 'Postas Rurales', 'Postas de Salud Rural', 1, '2025-09-22 13:00:09'),
(9, 'servicios', 'Servicios', 'Servicios Específicos de Salud', 1, '2025-09-22 13:00:09'),
(10, 'administracion', 'Administración', 'Departamento de Administración', 1, '2025-09-22 13:00:09');

-- usuarios (admin)
INSERT IGNORE INTO `usuarios` (`id`, `username`, `nombre`, `apellido`, `email`, `password_hash`, `departamento_id`, `cargo`, `rol`, `activo`, `fecha_creacion`, `ultimo_acceso`) VALUES
(1, 'admin', 'Administrador', 'Sistema', 'admin@dashualpen.cl', '$2y$10$F57NBSMstAnlWmnobR3ywOTF1xHOBDQFLTRxM0Se7y/RbX5j0zBfy', 1, 'Administrador del Sistema', 'super_admin', 1, '2025-09-22 13:00:09', '2025-09-22 15:50:39');

-- banco_preguntas (fragmento principal del dump)
INSERT IGNORE INTO `banco_preguntas` (`id`, `categoria_id`, `tipo_pregunta_id`, `texto`, `opciones`, `orden`, `departamento`, `obligatoria`, `activa`, `created_by`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 5, '¿Con qué frecuencia utiliza los servicios de salud municipales en Hualpén?', NULL, 1, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(2, 1, 5, '¿Cómo evalúa la calidad general de la atención en los servicios de salud de Hualpén?', NULL, 2, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(3, 1, 5, '¿Considera que el tiempo de espera para ser atendido es adecuado?', NULL, 3, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(4, 1, 5, '¿Se siente informado sobre los servicios de salud disponibles en su comuna?', NULL, 4, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(5, 1, 5, '¿Qué tan fácil es acceder a información sobre horarios y servicios?', NULL, 5, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(6, 1, 5, '¿Qué tan satisfecho está con la atención recibida en su última consulta?', NULL, 6, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(7, 1, 5, '¿Considera que las instalaciones de salud en Hualpén están bien mantenidas?', NULL, 7, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(8, 1, 5, '¿Ha tenido dificultades para obtener horas médicas en el sistema de salud municipal?', NULL, 8, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(9, 1, 5, '¿Cómo evalúa la amabilidad del personal de salud en general?', NULL, 9, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(10, 1, 5, '¿Recomendaría los servicios de salud municipales a familiares o amigos?', NULL, 10, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(11, 1, 5, '¿Considera que los servicios de salud de Hualpén cubren sus necesidades básicas?', NULL, 11, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(12, 1, 5, '¿Cree que existe suficiente personal médico para la demanda actual?', NULL, 12, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(13, 1, 5, '¿Está conforme con la atención de urgencia en la comuna?', NULL, 13, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(14, 1, 5, '¿Cómo calificaría la limpieza e higiene en los recintos de salud?', NULL, 14, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(15, 1, 5, '¿Cree que la comuna invierte lo suficiente en salud?', NULL, 15, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(16, 1, 5, '¿Se siente seguro al acudir a los recintos de salud de Hualpén?', NULL, 16, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(17, 1, 5, '¿Qué tan fácil le resulta acceder a medicamentos en la farmacia municipal?', NULL, 17, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(18, 1, 5, '¿Ha participado en actividades de promoción de la salud organizadas por el municipio?', NULL, 18, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(19, 1, 5, '¿Qué tan satisfecho está con el trato recibido por el personal administrativo?', NULL, 19, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(20, 1, 5, '¿Qué mejoras le gustaría ver en el sistema de salud municipal de Hualpén?', NULL, 20, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(21, 5, 5, '¿Cómo evalúa la atención en su CESFAM de referencia?', NULL, 1, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(22, 5, 5, '¿Recibe atención oportuna cuando solicita una hora en el CESFAM?', NULL, 2, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(23, 5, 5, '¿Cómo calificaría la atención del médico en su CESFAM?', NULL, 3, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(24, 5, 5, '¿Cómo calificaría la atención del equipo de enfermería?', NULL, 4, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(25, 5, 5, '¿Recibe información clara sobre diagnósticos y tratamientos?', NULL, 5, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(26, 5, 5, '¿Ha tenido problemas con la disponibilidad de medicamentos en la farmacia del CESFAM?', NULL, 6, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(27, 5, 5, '¿Qué tan satisfecho está con la atención de salud preventiva?', NULL, 7, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(28, 5, 5, '¿Le resulta fácil acceder a controles de enfermedades crónicas en el CESFAM?', NULL, 8, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(29, 5, 5, '¿Considera que el CESFAM tiene la infraestructura adecuada?', NULL, 9, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(30, 5, 5, '¿Cómo calificaría la atención recibida en el SOME del CESFAM?', NULL, 10, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(31, 5, 5, '¿Cree que el CESFAM responde a las necesidades de su comunidad?', NULL, 11, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(32, 5, 5, '¿Qué tan fácil le resulta solicitar una hora médica?', NULL, 12, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(33, 5, 5, '¿Está satisfecho con los tiempos de espera en sala?', NULL, 13, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(34, 5, 5, '¿Recibe recordatorios de sus controles de salud?', NULL, 14, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(35, 5, 5, '¿Ha tenido acceso a programas de salud mental en el CESFAM?', NULL, 15, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(36, 5, 5, '¿Está conforme con los servicios odontológicos en el CESFAM?', NULL, 16, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(37, 5, 5, '¿Ha participado en talleres o charlas organizadas por el CESFAM?', NULL, 17, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(38, 5, 5, '¿Cómo evalúa la coordinación entre los distintos profesionales del CESFAM?', NULL, 18, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(39, 5, 5, '¿Siente que el equipo de salud escucha sus inquietudes?', NULL, 19, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(40, 5, 5, '¿Qué tan satisfecho está con el tiempo de atención durante sus consultas?', NULL, 20, 'cesfam', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(41, 6, 5, '¿Conoce los servicios que entrega su CECOSF más cercano?', NULL, 1, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(42, 6, 5, '¿Le resulta accesible acudir a un CECOSF en su barrio?', NULL, 2, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(43, 6, 5, '¿Está satisfecho con la atención recibida en el CECOSF?', NULL, 3, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(44, 6, 5, '¿Qué tan bien lo orientan en el CECOSF sobre derivaciones al CESFAM?', NULL, 4, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(45, 6, 5, '¿Ha participado en actividades comunitarias organizadas por el CECOSF?', NULL, 5, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(46, 6, 5, '¿Considera que los horarios de atención del CECOSF son adecuados?', NULL, 6, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(47, 6, 5, '¿Está conforme con la atención de enfermería en el CECOSF?', NULL, 7, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(48, 6, 5, '¿Ha recibido atención preventiva en su CECOSF?', NULL, 8, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(49, 6, 5, '¿Qué tan fácil es obtener una hora en el CECOSF?', NULL, 9, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(50, 6, 5, '¿El personal del CECOSF le entrega información clara?', NULL, 10, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(51, 6, 5, '¿Considera que el CECOSF contribuye a mejorar la salud en su barrio?', NULL, 11, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(52, 6, 5, '¿Se siente escuchado por el equipo de salud del CECOSF?', NULL, 12, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(53, 6, 5, '¿Ha accedido a programas de salud mental en el CECOSF?', NULL, 13, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(54, 6, 5, '¿Qué tan satisfecho está con las campañas de vacunación?', NULL, 14, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(55, 6, 5, '¿Ha tenido acceso a controles de niño sano en el CECOSF?', NULL, 15, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(56, 6, 5, '¿Cómo evalúa la atención de salud de la mujer en el CECOSF?', NULL, 16, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(57, 6, 5, '¿Ha recibido visitas domiciliarias desde el CECOSF?', NULL, 17, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(58, 6, 5, '¿Está conforme con la infraestructura del CECOSF?', NULL, 18, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(59, 6, 5, '¿Cree que el CECOSF responde a las necesidades de su comunidad?', NULL, 19, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(60, 6, 5, '¿Recomendaría el CECOSF a sus vecinos?', NULL, 20, 'cecosf', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(61, 7, 5, '¿Ha utilizado el Servicio de Atención Primaria de Urgencia (SAPU) en Hualpén?', NULL, 1, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(62, 7, 5, '¿Cómo calificaría la rapidez de la atención en el SAPU?', NULL, 2, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(63, 7, 5, '¿Considera adecuado el tiempo de espera en el SAPU?', NULL, 3, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(64, 7, 5, '¿Está conforme con la atención del personal médico en el SAPU?', NULL, 4, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(65, 7, 5, '¿Cómo evalúa la atención del equipo de enfermería en el SAPU?', NULL, 5, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(66, 7, 5, '¿Se siente seguro al acudir al SAPU por una urgencia?', NULL, 6, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(67, 7, 5, '¿Ha recibido información clara sobre su diagnóstico en el SAPU?', NULL, 7, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(68, 7, 5, '¿El personal del SAPU le brinda un trato amable y respetuoso?', NULL, 8, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(69, 7, 5, '¿Está conforme con la infraestructura del SAPU?', NULL, 9, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(70, 7, 5, '¿Considera que el SAPU está bien equipado?', NULL, 10, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(71, 7, 5, '¿Qué tan fácil le resulta acceder al SAPU desde su hogar?', NULL, 11, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(72, 7, 5, '¿Se siente satisfecho con la resolución de su problema de salud en el SAPU?', NULL, 12, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(73, 7, 5, '¿Recibe derivaciones oportunas a hospitales u otros servicios?', NULL, 13, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(74, 7, 5, '¿Cómo evalúa la limpieza en las instalaciones del SAPU?', NULL, 14, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(75, 7, 5, '¿Ha tenido acceso oportuno a medicamentos en el SAPU?', NULL, 15, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(76, 7, 5, '¿Considera que el SAPU ayuda a descongestionar hospitales?', NULL, 16, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(77, 7, 5, '¿Cómo calificaría la atención en situaciones de urgencia nocturna?', NULL, 17, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(78, 7, 5, '¿Está conforme con la atención de niños en el SAPU?', NULL, 18, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(79, 7, 5, '¿Recomendaría el SAPU a familiares o vecinos?', NULL, 19, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(80, 7, 5, '¿Qué mejoras propondría para el SAPU de Hualpén?', NULL, 20, 'sapu', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(81, 4, 5, '¿Ha acudido a un servicio de urgencias en Hualpén en el último año?', NULL, 1, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(82, 4, 5, '¿Cómo evalúa el tiempo de espera en la urgencia?', NULL, 2, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(83, 4, 5, '¿Está conforme con la atención del personal médico en urgencias?', NULL, 3, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(84, 4, 5, '¿Cómo calificaría la atención del personal de enfermería en urgencias?', NULL, 4, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(85, 4, 5, '¿Recibió un diagnóstico claro y oportuno en la urgencia?', NULL, 5, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(86, 4, 5, '¿Considera que el servicio de urgencias está bien equipado?', NULL, 6, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(87, 4, 5, '¿Se siente seguro en la sala de espera de urgencias?', NULL, 7, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(88, 4, 5, '¿Cómo calificaría la limpieza e higiene en el área de urgencias?', NULL, 8, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(89, 4, 5, '¿Está satisfecho con el trato recibido por el personal de urgencias?', NULL, 9, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(90, 4, 5, '¿Recibió la medicación necesaria durante su atención en urgencias?', NULL, 10, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(91, 4, 5, '¿Considera que los tiempos de espera son razonables en urgencias?', NULL, 11, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(92, 4, 5, '¿Ha tenido que acudir varias veces a urgencias por la misma condición?', NULL, 12, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(93, 4, 5, '¿Cómo evalúa la atención en urgencias pediátricas?', NULL, 13, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(94, 4, 5, '¿Está conforme con la atención recibida en horarios nocturnos?', NULL, 14, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(95, 4, 5, '¿Recibió derivación oportuna a otro centro de mayor complejidad?', NULL, 15, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(96, 4, 5, '¿Qué tan fácil le resultó llegar al servicio de urgencias?', NULL, 16, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(97, 4, 5, '¿Recibió orientación clara al momento del alta médica?', NULL, 17, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(98, 4, 5, '¿Cómo evalúa la coordinación entre urgencias y otros servicios?', NULL, 18, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(99, 4, 5, '¿Recomendaría el servicio de urgencias a sus vecinos?', NULL, 19, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(100, 4, 5, '¿Qué mejoras propondría para el servicio de urgencias en Hualpén?', NULL, 20, 'urgencias', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(101, 10, 3, '¿Cuál es su género?', NULL, 1, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(102, 10, 3, '¿En qué rango de edad se encuentra?', NULL, 2, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(103, 10, 3, '¿Cuál es su nivel de educación?', NULL, 3, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(104, 10, 3, '¿Cuál es su situación laboral actual?', NULL, 4, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(105, 10, 3, '¿En qué sector de Hualpén reside?', NULL, 5, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(106, 10, 3, '¿Cuánto tiempo lleva viviendo en Hualpén?', NULL, 6, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(107, 10, 3, '¿Con qué frecuencia visita los servicios de salud?', NULL, 7, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(108, 10, 3, '¿Qué medio de transporte utiliza para llegar a los servicios de salud?', NULL, 8, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(109, 10, 3, '¿Ha utilizado el servicio de telemedicina?', NULL, 9, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(110, 10, 3, '¿Cómo se enteró de los servicios de salud disponibles?', NULL, 10, 'general', 0, 1, 'admin', '2025-09-22 13:00:09', '2025-09-22 13:00:09'),
(111, 10, 2, 'multilinea', NULL, 11, 'general', 0, 1, 'admin', '2025-09-22 14:07:18', '2025-09-22 14:07:18'),
(112, 10, 5, 'awsfas', NULL, 12, 'general', 0, 1, 'admin', '2025-09-22 14:11:51', '2025-09-22 14:11:51'),
(113, 1, 1, '¿Cuál es su nombre completo?', NULL, 1, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(114, 1, 2, '¿Qué mejoras sugiere para los servicios de salud?', NULL, 2, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(115, 1, 3, '¿Cómo calificaría la atención recibida?', '{"excelente":"Excelente","muy_buena":"Muy buena","buena":"Buena","regular":"Regular","mala":"Mala"}', 3, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(116, 2, 4, '¿Qué servicios ha utilizado? (Seleccione todos los que aplican)', '{"consulta_medica":"Consulta m\u00e9dica","odontologia":"Odontolog\u00eda","enfermeria":"Enfermer\u00eda","farmacia":"Farmacia","laboratorio":"Laboratorio","rayos_x":"Rayos X"}', 4, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(117, 1, 5, '¿Está satisfecho con el tiempo de espera?', NULL, 5, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(118, 1, 6, 'Del 1 al 5, ¿qué tan probable es que recomiende nuestros servicios?', '{"min":1,"max":5,"step":1,"etiqueta_min":"Nada probable","etiqueta_max":"Muy probable"}', 6, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:18:43'),
(119, 1, 7, '¿Cuántas veces ha visitado nuestro centro en el último año?', NULL, 7, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(120, 1, 8, '¿Cuándo fue su última visita?', NULL, 8, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(121, 1, 9, '¿Cuál es su correo electrónico de contacto?', NULL, 9, 'prueba', 0, 1, 'admin', '2025-09-22 16:05:43', '2025-09-22 16:05:43'),
(122, 1, 10, '¿Cómo clasificarías estos aspectos por orden de importancia para ti?', '["Precio","Calidad","Servicio al cliente","Rapidez","Confiabilidad"]', 20, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47'),
(123, 1, 11, 'Evalúa los siguientes productos en diferentes aspectos:', '{"filas":["Producto A","Producto B","Producto C"],"columnas":["Excelente","Bueno","Regular","Malo"]}', 21, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47'),
(124, 1, 12, '¿Qué tan satisfecho estás con los siguientes aspectos de nuestro servicio?', '{"filas":["Atenci\u00f3n al cliente","Tiempo de respuesta","Calidad del producto","Precio"],"escala":["Muy insatisfecho","Insatisfecho","Neutral","Satisfecho","Muy satisfecho"]}', 22, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47'),
(125, 1, 13, '¿Cuál es tu nivel de experiencia con tecnología? (0 = Principiante, 100 = Experto)', '{"min":0,"max":100,"step":5,"label_min":"Principiante","label_max":"Experto"}', 23, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47'),
(126, 1, 14, '¿Cómo calificarías tu experiencia general con nuestro servicio?', NULL, 24, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47'),
(127, 1, 18, 'Pregunta NPS estándar', NULL, 25, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47'),
(128, 1, 19, 'Por favor, proporciona tu información de contacto para seguimiento:', NULL, 26, 'TI', 0, 1, 'Sistema', '2025-09-22 16:41:47', '2025-09-22 16:41:47');

-- encuestas
INSERT IGNORE INTO `encuestas` (`id`, `titulo`, `descripcion`, `departamento_id`, `creado_por`, `estado`, `fecha_inicio`, `fecha_fin`, `enlace_publico`, `codigo_qr`, `configuracion`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'sdfaf', 'asfafas', 7, 1, 'activa', '2025-09-22 11:11:00', '2025-09-24 11:11:00', 'enc_68d155a5562a4', NULL, NULL, '2025-09-22 13:56:53', '2025-09-22 16:34:16'),
(2, 'Encuesta de Prueba - Tipos de Pregunta', 'Encuesta para probar todos los tipos de pregunta disponibles', 1, 1, 'activa', NULL, NULL, 'prueba_tipos_68d173d7e7328', NULL, NULL, '2025-09-22 16:05:43', '2025-09-22 17:02:08');

-- encuesta_preguntas
INSERT IGNORE INTO `encuesta_preguntas` (`id`, `encuesta_id`, `pregunta_id`, `orden`, `seccion`, `obligatoria_encuesta`, `configuracion_especifica`, `activa`) VALUES
(1, 1, 81, 1, NULL, 0, NULL, 1),
(2, 1, 41, 2, NULL, 0, NULL, 1),
(3, 1, 102, 3, NULL, 0, NULL, 1),
(4, 1, 111, 4, NULL, 0, NULL, 1),
(5, 1, 112, 5, NULL, 0, NULL, 1),
(6, 2, 113, 1, NULL, 1, NULL, 1),
(7, 2, 114, 2, NULL, 1, NULL, 1),
(8, 2, 115, 3, NULL, 1, NULL, 1),
(9, 2, 116, 4, NULL, 0, NULL, 1),
(10, 2, 117, 5, NULL, 0, NULL, 1),
(11, 2, 118, 6, NULL, 0, NULL, 1),
(12, 2, 119, 7, NULL, 0, NULL, 1),
(13, 2, 120, 8, NULL, 0, NULL, 1),
(14, 2, 121, 9, NULL, 0, NULL, 1),
(15, 2, 122, 20, NULL, 0, NULL, 1),
(16, 2, 123, 21, NULL, 0, NULL, 1),
(17, 2, 124, 22, NULL, 0, NULL, 1),
(18, 2, 125, 23, NULL, 0, NULL, 1),
(19, 2, 126, 24, NULL, 0, NULL, 1),
(20, 2, 127, 25, NULL, 0, NULL, 1),
(21, 2, 128, 26, NULL, 0, NULL, 1);

-- respuestas_encuesta
INSERT IGNORE INTO `respuestas_encuesta` (`id`, `encuesta_id`, `ip_hash`, `sesion_token`, `estado`, `progreso_porcentaje`, `fecha_inicio`, `fecha_completada`, `fecha_ultima_actividad`) VALUES
(1, 1, '8ef992c3ec2b20903ea311e656447b2db11080bd242f3a3a74c8d5ce578b403a', 'resp_68d15bc2d74fa5.48987843', 'completada', 100.00, '2025-09-22 14:22:58', '2025-09-22 14:22:58', '2025-09-22 14:22:58'),
(2, 1, '8ef992c3ec2b20903ea311e656447b2db11080bd242f3a3a74c8d5ce578b403a', 'resp_68d15bd49e1507.55753355', 'completada', 100.00, '2025-09-22 14:23:16', '2025-09-22 14:23:16', '2025-09-22 14:23:16'),
(3, 1, '8ef992c3ec2b20903ea311e656447b2db11080bd242f3a3a74c8d5ce578b403a', 'resp_68d15bee9ce0a6.33143789', 'completada', 100.00, '2025-09-22 14:23:42', '2025-09-22 14:23:42', '2025-09-22 14:23:42'),
(4, 1, '8ef992c3ec2b20903ea311e656447b2db11080bd242f3a3a74c8d5ce578b403a', 'resp_68d15c0a0c4232.53456023', 'completada', 100.00, '2025-09-22 14:24:10', '2025-09-22 14:24:10', '2025-09-22 14:24:10'),
(5, 1, '8ef992c3ec2b20903ea311e656447b2db11080bd242f3a3a74c8d5ce578b403a', 'resp_68d15c95c227c5.50745775', 'completada', 100.00, '2025-09-22 14:26:29', '2025-09-22 14:26:29', '2025-09-22 14:26:29');

-- respuestas_detalle
INSERT IGNORE INTO `respuestas_detalle` (`id`, `respuesta_encuesta_id`, `pregunta_id`, `valor_respuesta`, `fecha_respuesta`, `fecha_actualizacion`) VALUES
(1, 1, 81, '"2"', '2025-09-22 14:22:58', '2025-09-22 14:22:58'),
(2, 1, 41, '"3"', '2025-09-22 14:22:58', '2025-09-22 14:22:58'),
(3, 1, 102, '"10"', '2025-09-22 14:22:58', '2025-09-22 14:22:58'),
(4, 1, 111, '"MHBJH"', '2025-09-22 14:22:58', '2025-09-22 14:22:58'),
(5, 1, 112, '"4"', '2025-09-22 14:22:58', '2025-09-22 14:22:58'),
(6, 2, 81, '"2"', '2025-09-22 14:23:16', '2025-09-22 14:23:16'),
(7, 2, 41, '"3"', '2025-09-22 14:23:16', '2025-09-22 14:23:16'),
(8, 2, 102, '"10"', '2025-09-22 14:23:16', '2025-09-22 14:23:16'),
(9, 2, 111, '"MHBJH"', '2025-09-22 14:23:16', '2025-09-22 14:23:16'),
(10, 2, 112, '"4"', '2025-09-22 14:23:16', '2025-09-22 14:23:16'),
(11, 3, 81, '"5"', '2025-09-22 14:23:42', '2025-09-22 14:23:42'),
(12, 3, 41, '"5"', '2025-09-22 14:23:42', '2025-09-22 14:23:42'),
(13, 3, 102, '"42"', '2025-09-22 14:23:42', '2025-09-22 14:23:42'),
(14, 3, 111, '"QWEQWE"', '2025-09-22 14:23:42', '2025-09-22 14:23:42'),
(15, 3, 112, '"2"', '2025-09-22 14:23:42', '2025-09-22 14:23:42'),
(16, 4, 81, '"5"', '2025-09-22 14:24:10', '2025-09-22 14:24:10'),
(17, 4, 41, '"5"', '2025-09-22 14:24:10', '2025-09-22 14:24:10'),
(18, 4, 102, '"42"', '2025-09-22 14:24:10', '2025-09-22 14:24:10'),
(19, 4, 111, '"QWEQWE"', '2025-09-22 14:24:10', '2025-09-22 14:24:10'),
(20, 4, 112, '"2"', '2025-09-22 14:24:10', '2025-09-22 14:24:10'),
(21, 5, 81, '"5"', '2025-09-22 14:26:29', '2025-09-22 14:26:29'),
(22, 5, 41, '"5"', '2025-09-22 14:26:29', '2025-09-22 14:26:29'),
(23, 5, 102, '"42"', '2025-09-22 14:26:29', '2025-09-22 14:26:29'),
(24, 5, 111, '"QWEQWE"', '2025-09-22 14:26:29', '2025-09-22 14:26:29'),
(25, 5, 112, '"2"', '2025-09-22 14:26:29', '2025-09-22 14:26:29');

-- ===============================================
-- FIN
-- ===============================================
