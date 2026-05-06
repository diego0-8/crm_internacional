-- Base de datos para CRM Internacional
-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS crm_internacional;
USE crm_internacional;

-- Tabla de roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar roles por defecto
INSERT INTO roles (nombre, descripcion) VALUES 
('admin', 'Administrador - Gestión de usuarios y asesores'),
('coordinador', 'Coordinador - Gestión de asesores asignados'),
('asesor', 'Asesor - Acceso limitado a sus funciones');

-- Tabla de usuarios
CREATE TABLE usuarios (
    cedula VARCHAR(20) PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    sip_extension VARCHAR(40) NULL COMMENT 'Extension SIP/WebRTC (Issabel)',
    sip_secret VARCHAR(128) NULL COMMENT 'Secreto SIP en texto plano (PBX)',
    rol_id INT NOT NULL,
    coordinador_cedula VARCHAR(20) NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    FOREIGN KEY (coordinador_cedula) REFERENCES usuarios(cedula)
);

-- Tabla de sesiones (para control de sesiones activas)
CREATE TABLE sesiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_cedula VARCHAR(20) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 1 HOUR),
    FOREIGN KEY (usuario_cedula) REFERENCES usuarios(cedula) ON DELETE CASCADE
);

-- Tabla de logs de actividad
CREATE TABLE logs_actividad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_cedula VARCHAR(20) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_cedula) REFERENCES usuarios(cedula)
);

-- Insertar usuario administrador por defecto
-- Contraseña: 'password' (hasheada con password_hash())
INSERT INTO usuarios (cedula, usuario, nombre, apellido, email, password, rol_id) VALUES 
('1234567890', 'admin', 'Super', 'Admin', 'admin@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Tabla de archivos CSV subidos
CREATE TABLE archivos_csv (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    coordinador_cedula VARCHAR(20) NOT NULL,
    total_registros INT DEFAULT 0,
    registros_procesados INT DEFAULT 0,
    estado ENUM('procesando', 'completado', 'error') DEFAULT 'procesando',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (coordinador_cedula) REFERENCES usuarios(cedula)
);

-- Tabla de clientes
CREATE TABLE clientes (
    cedula VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(20),
    empresa VARCHAR(150),
    cargo VARCHAR(100),
    direccion TEXT,
    ciudad VARCHAR(100),
    pais VARCHAR(100),
    codigo_postal VARCHAR(20),
    asesor_cedula VARCHAR(20),
    coordinador_cedula VARCHAR(20) NOT NULL,
    archivo_csv_id INT,
    estado ENUM('nuevo', 'contactado', 'interesado', 'prospecto', 'cliente', 'inactivo') DEFAULT 'nuevo',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula),
    FOREIGN KEY (coordinador_cedula) REFERENCES usuarios(cedula),
    FOREIGN KEY (archivo_csv_id) REFERENCES archivos_csv(id)
);

-- Tabla de tareas/actividades
CREATE TABLE tareas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    cliente_cedula VARCHAR(20),
    asesor_cedula VARCHAR(20) NOT NULL,
    coordinador_cedula VARCHAR(20) NOT NULL,
    tipo ENUM('llamada', 'email', 'reunion', 'seguimiento', 'otro') NOT NULL,
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    estado ENUM('pendiente', 'en_progreso', 'completada', 'cancelada') DEFAULT 'pendiente',
    fecha_vencimiento DATETIME,
    fecha_completada DATETIME,
    resultado TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula),
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula),
    FOREIGN KEY (coordinador_cedula) REFERENCES usuarios(cedula)
);

-- Tabla de métricas de asesores
CREATE TABLE metricas_asesores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asesor_cedula VARCHAR(20) NOT NULL,
    coordinador_cedula VARCHAR(20) NOT NULL,
    fecha_reporte DATE NOT NULL,
    clientes_asignados INT DEFAULT 0,
    clientes_contactados INT DEFAULT 0,
    llamadas_realizadas INT DEFAULT 0,
    emails_enviados INT DEFAULT 0,
    reuniones_realizadas INT DEFAULT 0,
    tareas_completadas INT DEFAULT 0,
    clientes_convertidos INT DEFAULT 0,
    ingresos_generados DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula),
    FOREIGN KEY (coordinador_cedula) REFERENCES usuarios(cedula),
    UNIQUE KEY unique_asesor_fecha (asesor_cedula, fecha_reporte)
);

-- Tabla de tipificaciones de llamadas
CREATE TABLE tipificaciones_llamadas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    categoria VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    es_positivo BOOLEAN DEFAULT FALSE,
    requiere_observacion BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de historial de llamadas
CREATE TABLE historial_llamadas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_cedula VARCHAR(20) NOT NULL,
    asesor_cedula VARCHAR(20) NOT NULL,
    tipificacion_id INT NOT NULL,
    fecha_llamada DATETIME NOT NULL,
    duracion_minutos INT DEFAULT 0,
    observacion TEXT NOT NULL,
    proxima_accion TEXT,
    fecha_proxima_accion DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula),
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula),
    FOREIGN KEY (tipificacion_id) REFERENCES tipificaciones_llamadas(id)
);

-- Insertar tipificaciones de llamadas
INSERT INTO tipificaciones_llamadas (codigo, categoria, descripcion, es_positivo, requiere_observacion) VALUES
-- A. Llamadas Efectivas (Resultado Positivo)
('A1', 'Llamadas Efectivas', 'Interesado - Envío de Información: El contacto mostró interés, solicitó más detalles. Se le envió el brochure, un correo con información adicional o un enlace al sitio web.', TRUE, TRUE),
('A2', 'Llamadas Efectivas', 'Interesado - Calificado (Lead): El contacto no solo mostró interés, sino que también cumple con el perfil de cliente (posiblemente un propietario extranjero o local con una propiedad que fue a subasta). Está dispuesto a agendar una reunión o enviar la información de su caso.', TRUE, TRUE),
('A3', 'Llamadas Efectivas', 'Cita Agendada: Se concretó una fecha y hora para una reunión con el equipo comercial o un especialista para discutir el caso en detalle.', TRUE, TRUE),
('A4', 'Llamadas Efectivas', 'Caso en Proceso: El cliente potencial ya envió la documentación necesaria para que el equipo legal inicie el análisis de su caso.', TRUE, TRUE),

-- B. Llamadas Fallidas (Resultado Negativo)
('B1', 'Llamadas Fallidas', 'No Contesta: Se realizó la llamada, pero no hubo respuesta. Se debe programar un reintento.', FALSE, TRUE),
('B2', 'Llamadas Fallidas', 'Buzón de Voz: La llamada fue desviada al buzón de voz. Se puede dejar un mensaje breve y programar un reintento.', FALSE, TRUE),
('B3', 'Llamadas Fallidas', 'Número Erróneo/Inexistente: El número de teléfono no está en servicio o pertenece a una persona equivocada. Se debe marcar como "malo" y no volver a llamar.', FALSE, TRUE),
('B4', 'Llamadas Fallidas', 'No Interesado: El contacto escuchó la propuesta pero declinó cualquier tipo de interés. Puede ser por falta de necesidad, desconfianza o por estar ocupado.', FALSE, TRUE),
('B5', 'Llamadas Fallidas', 'Bloqueo/No Molestar: El contacto solicitó explícitamente no volver a ser contactado. Se debe respetar su petición y marcar su número para no hacer más llamadas.', FALSE, TRUE),

-- C. Llamadas de Seguimiento/Otras
('C1', 'Llamadas de Seguimiento', 'Mensaje Dejado (WhatsApp/Correo): No se logró el contacto telefónico, pero se envió un mensaje por otra vía, como WhatsApp o correo electrónico.', FALSE, TRUE),
('C2', 'Llamadas de Seguimiento', 'Reintento Programado: Se registró un reintento de llamada para un día y hora específicos.', FALSE, TRUE),
('C3', 'Llamadas de Seguimiento', 'Ya es Cliente: La persona contactada ya tiene un contrato con ClaimTrust o ya está trabajando en su caso.', FALSE, TRUE);

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_usuarios_usuario ON usuarios(usuario);
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_rol ON usuarios(rol_id);
CREATE INDEX idx_usuarios_coordinador ON usuarios(coordinador_cedula);
CREATE INDEX idx_usuarios_activo ON usuarios(activo);
CREATE INDEX idx_sesiones_token ON sesiones(token);
CREATE INDEX idx_sesiones_usuario ON sesiones(usuario_cedula);
CREATE INDEX idx_logs_usuario ON logs_actividad(usuario_cedula);
CREATE INDEX idx_logs_fecha ON logs_actividad(created_at);
CREATE INDEX idx_archivos_coordinador ON archivos_csv(coordinador_cedula);
CREATE INDEX idx_clientes_asesor ON clientes(asesor_cedula);
CREATE INDEX idx_clientes_coordinador ON clientes(coordinador_cedula);
CREATE INDEX idx_clientes_estado ON clientes(estado);
CREATE INDEX idx_tareas_asesor ON tareas(asesor_cedula);
CREATE INDEX idx_tareas_estado ON tareas(estado);
CREATE INDEX idx_metricas_asesor ON metricas_asesores(asesor_cedula);
CREATE INDEX idx_metricas_fecha ON metricas_asesores(fecha_reporte);
CREATE INDEX idx_tipificaciones_categoria ON tipificaciones_llamadas(categoria);
CREATE INDEX idx_tipificaciones_activo ON tipificaciones_llamadas(activo);
CREATE INDEX idx_historial_cliente ON historial_llamadas(cliente_cedula);
CREATE INDEX idx_historial_asesor ON historial_llamadas(asesor_cedula);
CREATE INDEX idx_historial_fecha ON historial_llamadas(fecha_llamada);

-- Tabla de tokens "Remember Me"
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_cedula VARCHAR(20) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_cedula) REFERENCES usuarios(cedula) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_user (user_cedula)
);

-- Tiketera v2: categorías, SLA, importación CSV
CREATE TABLE ticket_categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO ticket_categorias (codigo, nombre) VALUES
('GEN', 'General'),
('SOP', 'Soporte técnico'),
('FAC', 'Facturación'),
('COM', 'Comercial');

CREATE TABLE sla_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    prioridad ENUM('baja', 'media', 'alta', 'urgente') NOT NULL,
    tiempo_respuesta_minutos INT NOT NULL,
    tiempo_resolucion_horas INT NOT NULL,
    horario_atencion_inicio TIME DEFAULT '08:00:00',
    horario_atencion_fin TIME DEFAULT '18:00:00',
    dias_laborales JSON,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO sla_policies (nombre, descripcion, prioridad, tiempo_respuesta_minutos, tiempo_resolucion_horas) VALUES
('SLA Urgente', 'Problemas críticos que afectan operaciones', 'urgente', 15, 2),
('SLA Alta', 'Problemas importantes que requieren atención rápida', 'alta', 60, 8),
('SLA Media', 'Problemas estándar', 'media', 240, 24),
('SLA Baja', 'Consultas y solicitudes generales', 'baja', 480, 72);

CREATE TABLE escalation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    condicion JSON NOT NULL,
    accion JSON NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ticket_import_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coordinador_cedula VARCHAR(20) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    total_filas INT DEFAULT 0,
    filas_ok INT DEFAULT 0,
    filas_error INT DEFAULT 0,
    estado ENUM('procesando','completado','error') DEFAULT 'procesando',
    crear_clientes_modo TINYINT(1) DEFAULT 0,
    mensaje_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (coordinador_cedula) REFERENCES usuarios(cedula),
    INDEX idx_ticket_import_coord (coordinador_cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de tiketera (sistema de tickets)
CREATE TABLE tiketera (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_ticket VARCHAR(40) NULL UNIQUE,
    cliente_cedula VARCHAR(20) NOT NULL,
    asesor_cedula VARCHAR(20) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    estado ENUM('comunicacion','validacion','proceso_judicial','remate','recuperacion','cierre') NOT NULL DEFAULT 'comunicacion',
    observaciones TEXT,
    pdf_archivo VARCHAR(500),
    categoria_id INT NULL,
    origen ENUM('asesor','csv') DEFAULT 'asesor',
    import_batch_id INT NULL,
    sla_politica_id INT NULL,
    sla_respuesta_limite DATETIME NULL,
    sla_resolucion_limite DATETIME NULL,
    primera_respuesta_en DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula),
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula),
    FOREIGN KEY (categoria_id) REFERENCES ticket_categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (import_batch_id) REFERENCES ticket_import_batches(id) ON DELETE SET NULL,
    FOREIGN KEY (sla_politica_id) REFERENCES sla_policies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE sla_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sla_policy_id INT NOT NULL,
    tiempo_respuesta_objetivo TIMESTAMP NULL,
    tiempo_resolucion_objetivo TIMESTAMP NULL,
    tiempo_respuesta_real TIMESTAMP NULL,
    tiempo_resolucion_real TIMESTAMP NULL,
    cumplio_respuesta BOOLEAN DEFAULT FALSE,
    cumplio_resolucion BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tiketera(id) ON DELETE CASCADE,
    FOREIGN KEY (sla_policy_id) REFERENCES sla_policies(id),
    INDEX idx_sla_tracking_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ticket_import_filas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    numero_linea INT NOT NULL,
    estado ENUM('ok','error') NOT NULL,
    mensaje_error TEXT NULL,
    ticket_id INT NULL,
    raw_line TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES ticket_import_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tiketera(id) ON DELETE SET NULL,
    INDEX idx_import_filas_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índices para tiketera
CREATE INDEX idx_tiketera_cliente ON tiketera(cliente_cedula);
CREATE INDEX idx_tiketera_asesor ON tiketera(asesor_cedula);
CREATE INDEX idx_tiketera_estado ON tiketera(estado);
CREATE INDEX idx_tiketera_fecha_creacion ON tiketera(fecha_creacion);
CREATE INDEX idx_tiketera_import_batch ON tiketera(import_batch_id);
CREATE INDEX idx_tiketera_sla_resp_lim ON tiketera(sla_respuesta_limite);

-- Tabla para archivos PDF de tickets
CREATE TABLE IF NOT EXISTS ticket_archivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    asesor_cedula VARCHAR(20) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tiketera(id) ON DELETE CASCADE,
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_asesor_cedula (asesor_cedula),
    INDEX idx_fecha_subida (fecha_subida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para historial / timeline de cambios de estado de tickets
CREATE TABLE IF NOT EXISTS ticket_estado_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    estado_anterior VARCHAR(40) NULL,
    estado_nuevo VARCHAR(40) NOT NULL,
    asesor_cedula VARCHAR(20) NULL,
    observacion TEXT NULL,
    fecha_cambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tiketera(id) ON DELETE CASCADE,
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula) ON DELETE SET NULL,
    INDEX idx_teh_ticket (ticket_id),
    INDEX idx_teh_fecha (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para notas de tickets
CREATE TABLE IF NOT EXISTS ticket_notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    asesor_cedula VARCHAR(20) NOT NULL,
    contenido TEXT NOT NULL,
    proxima_accion TEXT,
    fecha_proxima_accion DATETIME,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tiketera(id) ON DELETE CASCADE,
    FOREIGN KEY (asesor_cedula) REFERENCES usuarios(cedula) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_asesor_cedula (asesor_cedula),
    INDEX idx_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;