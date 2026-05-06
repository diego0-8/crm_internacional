-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-05-2026 a las 21:52:43
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
-- Base de datos: `crm_internacional`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_csv`
--

CREATE TABLE `archivos_csv` (
  `id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `coordinador_cedula` varchar(20) NOT NULL,
  `total_registros` int(11) DEFAULT 0,
  `registros_procesados` int(11) DEFAULT 0,
  `estado` enum('procesando','completado','error') DEFAULT 'procesando',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `channel_interactions`
--

CREATE TABLE `channel_interactions` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `tipo_interaccion` enum('entrada','salida','interno') NOT NULL,
  `contenido` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `fecha_interaccion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `cedula` varchar(20) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `empresa` varchar(150) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `asesor_cedula` varchar(20) DEFAULT NULL,
  `coordinador_cedula` varchar(20) NOT NULL,
  `archivo_csv_id` int(11) DEFAULT NULL,
  `estado` enum('nuevo','contactado','interesado','prospecto','cliente','inactivo') DEFAULT 'nuevo',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `communication_channels`
--

CREATE TABLE `communication_channels` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('email','telefono','chat','redes_sociales','portal_web') NOT NULL,
  `configuracion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuracion`)),
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escalation_rules`
--

CREATE TABLE `escalation_rules` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `condicion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`condicion`)),
  `accion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`accion`)),
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_llamadas`
--

CREATE TABLE `historial_llamadas` (
  `id` int(11) NOT NULL,
  `cliente_cedula` varchar(20) NOT NULL,
  `asesor_cedula` varchar(20) NOT NULL,
  `tipificacion_id` int(11) NOT NULL,
  `fecha_llamada` datetime NOT NULL,
  `duracion_minutos` int(11) DEFAULT 0,
  `observacion` text NOT NULL,
  `proxima_accion` text DEFAULT NULL,
  `fecha_proxima_accion` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `etiquetas` text DEFAULT NULL,
  `autor_cedula` varchar(20) NOT NULL,
  `estado` enum('borrador','publicado','archivado') DEFAULT 'borrador',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visualizaciones` int(11) DEFAULT 0,
  `utilidad` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `knowledge_categories`
--

CREATE TABLE `knowledge_categories` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `knowledge_searches`
--

CREATE TABLE `knowledge_searches` (
  `id` int(11) NOT NULL,
  `termino_busqueda` varchar(255) NOT NULL,
  `resultados_encontrados` int(11) DEFAULT 0,
  `articulo_seleccionado` int(11) DEFAULT NULL,
  `usuario_cedula` varchar(20) DEFAULT NULL,
  `fecha_busqueda` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id` int(11) NOT NULL,
  `usuario_cedula` varchar(20) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metricas_asesores`
--

CREATE TABLE `metricas_asesores` (
  `id` int(11) NOT NULL,
  `asesor_cedula` varchar(20) NOT NULL,
  `coordinador_cedula` varchar(20) NOT NULL,
  `fecha_reporte` date NOT NULL,
  `clientes_asignados` int(11) DEFAULT 0,
  `clientes_contactados` int(11) DEFAULT 0,
  `llamadas_realizadas` int(11) DEFAULT 0,
  `emails_enviados` int(11) DEFAULT 0,
  `reuniones_realizadas` int(11) DEFAULT 0,
  `tareas_completadas` int(11) DEFAULT 0,
  `clientes_convertidos` int(11) DEFAULT 0,
  `ingresos_generados` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `predefined_responses`
--

CREATE TABLE `predefined_responses` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `contenido` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `activa` tinyint(1) DEFAULT 1,
  `uso_count` int(11) DEFAULT 0,
  `autor_cedula` varchar(20) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_cedula` varchar(20) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL,
  `usuario_cedula` varchar(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sla_policies`
--

CREATE TABLE `sla_policies` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `prioridad` enum('baja','media','alta','urgente') NOT NULL,
  `tiempo_respuesta_minutos` int(11) NOT NULL,
  `tiempo_resolucion_horas` int(11) NOT NULL,
  `horario_atencion_inicio` time DEFAULT '08:00:00',
  `horario_atencion_fin` time DEFAULT '18:00:00',
  `dias_laborales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dias_laborales`)),
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sla_tracking`
--

CREATE TABLE `sla_tracking` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sla_policy_id` int(11) NOT NULL,
  `tiempo_respuesta_objetivo` timestamp NULL DEFAULT NULL,
  `tiempo_resolucion_objetivo` timestamp NULL DEFAULT NULL,
  `tiempo_respuesta_real` timestamp NULL DEFAULT NULL,
  `tiempo_resolucion_real` timestamp NULL DEFAULT NULL,
  `cumplio_respuesta` tinyint(1) DEFAULT 0,
  `cumplio_resolucion` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cliente_cedula` varchar(20) DEFAULT NULL,
  `asesor_cedula` varchar(20) NOT NULL,
  `coordinador_cedula` varchar(20) NOT NULL,
  `tipo` enum('llamada','email','reunion','seguimiento','otro') NOT NULL,
  `prioridad` enum('baja','media','alta','urgente') DEFAULT 'media',
  `estado` enum('pendiente','en_progreso','completada','cancelada') DEFAULT 'pendiente',
  `fecha_vencimiento` datetime DEFAULT NULL,
  `fecha_completada` datetime DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_archivos`
--

CREATE TABLE `ticket_archivos` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `asesor_cedula` varchar(20) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_categorias`
--

CREATE TABLE `ticket_categorias` (
  `id` int(11) NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_estado_historial`
--

CREATE TABLE `ticket_estado_historial` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `estado_anterior` varchar(40) DEFAULT NULL,
  `estado_nuevo` varchar(40) NOT NULL,
  `asesor_cedula` varchar(20) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_import_batches`
--

CREATE TABLE `ticket_import_batches` (
  `id` int(11) NOT NULL,
  `coordinador_cedula` varchar(20) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `total_filas` int(11) DEFAULT 0,
  `filas_ok` int(11) DEFAULT 0,
  `filas_error` int(11) DEFAULT 0,
  `estado` enum('procesando','completado','error') DEFAULT 'procesando',
  `crear_clientes_modo` tinyint(1) DEFAULT 0,
  `mensaje_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_import_filas`
--

CREATE TABLE `ticket_import_filas` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `numero_linea` int(11) NOT NULL,
  `estado` enum('ok','error') NOT NULL,
  `mensaje_error` text DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `raw_line` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_notas`
--

CREATE TABLE `ticket_notas` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `asesor_cedula` varchar(20) NOT NULL,
  `contenido` text NOT NULL,
  `proxima_accion` text DEFAULT NULL,
  `fecha_proxima_accion` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiketera`
--

CREATE TABLE `tiketera` (
  `id` int(11) NOT NULL,
  `cliente_cedula` varchar(20) NOT NULL,
  `asesor_cedula` varchar(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('comunicacion','validacion','proceso_judicial','remate','recuperacion','cierre') NOT NULL DEFAULT 'comunicacion',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `pdf_archivo` varchar(255) DEFAULT NULL,
  `numero_ticket` varchar(40) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `origen` enum('asesor','csv') DEFAULT 'asesor',
  `import_batch_id` int(11) DEFAULT NULL,
  `sla_politica_id` int(11) DEFAULT NULL,
  `sla_respuesta_limite` datetime DEFAULT NULL,
  `sla_resolucion_limite` datetime DEFAULT NULL,
  `primera_respuesta_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipificaciones_llamadas`
--

CREATE TABLE `tipificaciones_llamadas` (
  `id` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `es_positivo` tinyint(1) DEFAULT 0,
  `requiere_observacion` tinyint(1) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nivel` int(11) DEFAULT 1,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `cedula` varchar(20) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `sip_extension` varchar(40) DEFAULT NULL COMMENT 'Extensión SIP/WebRTC (Issabel)',
  `sip_secret` varchar(128) DEFAULT NULL COMMENT 'Secreto SIP (guardar con políticas de seguridad)',
  `rol_id` int(11) NOT NULL,
  `coordinador_cedula` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pdf_documento` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo PDF del usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_csv`
--
ALTER TABLE `archivos_csv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archivos_coordinador` (`coordinador_cedula`);

--
-- Indices de la tabla `channel_interactions`
--
ALTER TABLE `channel_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `channel_id` (`channel_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`cedula`),
  ADD KEY `archivo_csv_id` (`archivo_csv_id`),
  ADD KEY `idx_clientes_asesor` (`asesor_cedula`),
  ADD KEY `idx_clientes_coordinador` (`coordinador_cedula`),
  ADD KEY `idx_clientes_estado` (`estado`);

--
-- Indices de la tabla `communication_channels`
--
ALTER TABLE `communication_channels`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `escalation_rules`
--
ALTER TABLE `escalation_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_llamadas`
--
ALTER TABLE `historial_llamadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipificacion_id` (`tipificacion_id`),
  ADD KEY `idx_historial_cliente` (`cliente_cedula`),
  ADD KEY `idx_historial_asesor` (`asesor_cedula`),
  ADD KEY `idx_historial_fecha` (`fecha_llamada`);

--
-- Indices de la tabla `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`),
  ADD KEY `autor_cedula` (`autor_cedula`);

--
-- Indices de la tabla `knowledge_categories`
--
ALTER TABLE `knowledge_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `knowledge_searches`
--
ALTER TABLE `knowledge_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `articulo_seleccionado` (`articulo_seleccionado`),
  ADD KEY `usuario_cedula` (`usuario_cedula`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_usuario` (`usuario_cedula`),
  ADD KEY `idx_logs_fecha` (`created_at`);

--
-- Indices de la tabla `metricas_asesores`
--
ALTER TABLE `metricas_asesores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_asesor_fecha` (`asesor_cedula`,`fecha_reporte`),
  ADD KEY `coordinador_cedula` (`coordinador_cedula`),
  ADD KEY `idx_metricas_asesor` (`asesor_cedula`),
  ADD KEY `idx_metricas_fecha` (`fecha_reporte`);

--
-- Indices de la tabla `predefined_responses`
--
ALTER TABLE `predefined_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `autor_cedula` (`autor_cedula`);

--
-- Indices de la tabla `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_user` (`user_cedula`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_sesiones_token` (`token`),
  ADD KEY `idx_sesiones_usuario` (`usuario_cedula`);

--
-- Indices de la tabla `sla_policies`
--
ALTER TABLE `sla_policies`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sla_tracking`
--
ALTER TABLE `sla_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `sla_policy_id` (`sla_policy_id`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_cedula` (`cliente_cedula`),
  ADD KEY `coordinador_cedula` (`coordinador_cedula`),
  ADD KEY `idx_tareas_asesor` (`asesor_cedula`),
  ADD KEY `idx_tareas_estado` (`estado`);

--
-- Indices de la tabla `ticket_archivos`
--
ALTER TABLE `ticket_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_asesor_cedula` (`asesor_cedula`),
  ADD KEY `idx_fecha_subida` (`fecha_subida`);

--
-- Indices de la tabla `ticket_categorias`
--
ALTER TABLE `ticket_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `ticket_estado_historial`
--
ALTER TABLE `ticket_estado_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teh_asesor` (`asesor_cedula`),
  ADD KEY `idx_teh_ticket` (`ticket_id`),
  ADD KEY `idx_teh_fecha` (`fecha_cambio`);

--
-- Indices de la tabla `ticket_import_batches`
--
ALTER TABLE `ticket_import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_import_coord` (`coordinador_cedula`);

--
-- Indices de la tabla `ticket_import_filas`
--
ALTER TABLE `ticket_import_filas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `idx_import_filas_batch` (`batch_id`);

--
-- Indices de la tabla `ticket_notas`
--
ALTER TABLE `ticket_notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_asesor_cedula` (`asesor_cedula`),
  ADD KEY `idx_fecha_creacion` (`fecha_creacion`);

--
-- Indices de la tabla `tiketera`
--
ALTER TABLE `tiketera`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tiketera_numero_ticket` (`numero_ticket`),
  ADD KEY `idx_tiketera_cliente` (`cliente_cedula`),
  ADD KEY `idx_tiketera_asesor` (`asesor_cedula`),
  ADD KEY `idx_tiketera_estado` (`estado`),
  ADD KEY `idx_tiketera_fecha_creacion` (`fecha_creacion`),
  ADD KEY `idx_tiketera_pdf` (`pdf_archivo`),
  ADD KEY `idx_tiketera_import_batch` (`import_batch_id`),
  ADD KEY `idx_tiketera_sla_resp_lim` (`sla_respuesta_limite`),
  ADD KEY `fk_tiketera_categoria` (`categoria_id`),
  ADD KEY `fk_tiketera_sla_pol` (`sla_politica_id`);

--
-- Indices de la tabla `tipificaciones_llamadas`
--
ALTER TABLE `tipificaciones_llamadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_tipificaciones_categoria` (`categoria`),
  ADD KEY `idx_tipificaciones_activo` (`activo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`cedula`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usuarios_usuario` (`usuario`),
  ADD KEY `idx_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_rol` (`rol_id`),
  ADD KEY `idx_usuarios_coordinador` (`coordinador_cedula`),
  ADD KEY `idx_usuarios_activo` (`activo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_csv`
--
ALTER TABLE `archivos_csv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `channel_interactions`
--
ALTER TABLE `channel_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `communication_channels`
--
ALTER TABLE `communication_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `escalation_rules`
--
ALTER TABLE `escalation_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_llamadas`
--
ALTER TABLE `historial_llamadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `knowledge_categories`
--
ALTER TABLE `knowledge_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `knowledge_searches`
--
ALTER TABLE `knowledge_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metricas_asesores`
--
ALTER TABLE `metricas_asesores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `predefined_responses`
--
ALTER TABLE `predefined_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sla_policies`
--
ALTER TABLE `sla_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sla_tracking`
--
ALTER TABLE `sla_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_archivos`
--
ALTER TABLE `ticket_archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_categorias`
--
ALTER TABLE `ticket_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_estado_historial`
--
ALTER TABLE `ticket_estado_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_import_batches`
--
ALTER TABLE `ticket_import_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_import_filas`
--
ALTER TABLE `ticket_import_filas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_notas`
--
ALTER TABLE `ticket_notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tiketera`
--
ALTER TABLE `tiketera`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipificaciones_llamadas`
--
ALTER TABLE `tipificaciones_llamadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_csv`
--
ALTER TABLE `archivos_csv`
  ADD CONSTRAINT `archivos_csv_ibfk_1` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `channel_interactions`
--
ALTER TABLE `channel_interactions`
  ADD CONSTRAINT `channel_interactions_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tiketera` (`id`),
  ADD CONSTRAINT `channel_interactions_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `communication_channels` (`id`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `clientes_ibfk_2` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `clientes_ibfk_3` FOREIGN KEY (`archivo_csv_id`) REFERENCES `archivos_csv` (`id`);

--
-- Filtros para la tabla `historial_llamadas`
--
ALTER TABLE `historial_llamadas`
  ADD CONSTRAINT `historial_llamadas_ibfk_1` FOREIGN KEY (`cliente_cedula`) REFERENCES `clientes` (`cedula`),
  ADD CONSTRAINT `historial_llamadas_ibfk_2` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `historial_llamadas_ibfk_3` FOREIGN KEY (`tipificacion_id`) REFERENCES `tipificaciones_llamadas` (`id`);

--
-- Filtros para la tabla `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD CONSTRAINT `knowledge_base_ibfk_1` FOREIGN KEY (`autor_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `knowledge_searches`
--
ALTER TABLE `knowledge_searches`
  ADD CONSTRAINT `knowledge_searches_ibfk_1` FOREIGN KEY (`articulo_seleccionado`) REFERENCES `knowledge_base` (`id`),
  ADD CONSTRAINT `knowledge_searches_ibfk_2` FOREIGN KEY (`usuario_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD CONSTRAINT `logs_actividad_ibfk_1` FOREIGN KEY (`usuario_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `metricas_asesores`
--
ALTER TABLE `metricas_asesores`
  ADD CONSTRAINT `metricas_asesores_ibfk_1` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `metricas_asesores_ibfk_2` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `predefined_responses`
--
ALTER TABLE `predefined_responses`
  ADD CONSTRAINT `predefined_responses_ibfk_1` FOREIGN KEY (`autor_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_cedula`) REFERENCES `usuarios` (`cedula`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_cedula`) REFERENCES `usuarios` (`cedula`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sla_tracking`
--
ALTER TABLE `sla_tracking`
  ADD CONSTRAINT `sla_tracking_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tiketera` (`id`),
  ADD CONSTRAINT `sla_tracking_ibfk_2` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`);

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `tareas_ibfk_1` FOREIGN KEY (`cliente_cedula`) REFERENCES `clientes` (`cedula`),
  ADD CONSTRAINT `tareas_ibfk_2` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`),
  ADD CONSTRAINT `tareas_ibfk_3` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `ticket_archivos`
--
ALTER TABLE `ticket_archivos`
  ADD CONSTRAINT `ticket_archivos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tiketera` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_archivos_ibfk_2` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket_estado_historial`
--
ALTER TABLE `ticket_estado_historial`
  ADD CONSTRAINT `fk_teh_asesor` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teh_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tiketera` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket_import_batches`
--
ALTER TABLE `ticket_import_batches`
  ADD CONSTRAINT `ticket_import_batches_ibfk_1` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `ticket_import_filas`
--
ALTER TABLE `ticket_import_filas`
  ADD CONSTRAINT `ticket_import_filas_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `ticket_import_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_import_filas_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tiketera` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tiketera`
--
ALTER TABLE `tiketera`
  ADD CONSTRAINT `fk_tiketera_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `ticket_categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tiketera_import_batch` FOREIGN KEY (`import_batch_id`) REFERENCES `ticket_import_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tiketera_sla_pol` FOREIGN KEY (`sla_politica_id`) REFERENCES `sla_policies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tiketera_ibfk_1` FOREIGN KEY (`cliente_cedula`) REFERENCES `clientes` (`cedula`),
  ADD CONSTRAINT `tiketera_ibfk_2` FOREIGN KEY (`asesor_cedula`) REFERENCES `usuarios` (`cedula`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`coordinador_cedula`) REFERENCES `usuarios` (`cedula`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
