-- ============================================================================
-- Migración: importar CSV foreclosure (cliente + predio + referencias)
-- Archivo: database/migration_foreclosure_v1.sql
--
-- Objetivo:
--   Permitir guardar en BD toda la informacion del archivo basecsv#1.csv:
--     * Datos extra del cliente (mailing address, age, deceased, source).
--     * Predios (datos del listado de subasta/foreclosure).
--     * Telefonos y emails del cliente (hasta 5 c/u, con tipo y DNC).
--     * Referencias personales (RELATIVE 1..5) y sus telefonos/emails (hasta 5 c/u).
--
-- Importante:
--   Solo agrega columnas opcionales y tablas nuevas; no rompe codigo existente.
--   Hacer backup antes de ejecutar. Los pasos son idempotentes en lo posible.
-- ============================================================================

USE crm_internacional;

-- ----------------------------------------------------------------------------
-- 1) Extender la tabla clientes con campos del titular del listado
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS __add_col_if_missing__;
DELIMITER //
CREATE PROCEDURE __add_col_if_missing__(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = tbl
          AND column_name = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL __add_col_if_missing__('clientes', 'age',            "`age` TINYINT UNSIGNED NULL AFTER `notas`");
CALL __add_col_if_missing__('clientes', 'deceased',       "`deceased` ENUM('Y','N') NULL AFTER `age`");
CALL __add_col_if_missing__('clientes', 'source',         "`source` VARCHAR(500) NULL AFTER `deceased`");
CALL __add_col_if_missing__('clientes', 'mailing_street', "`mailing_street` VARCHAR(255) NULL AFTER `source`");
CALL __add_col_if_missing__('clientes', 'mailing_city',   "`mailing_city` VARCHAR(120) NULL AFTER `mailing_street`");
CALL __add_col_if_missing__('clientes', 'mailing_state',  "`mailing_state` VARCHAR(10) NULL AFTER `mailing_city`");
CALL __add_col_if_missing__('clientes', 'mailing_zip',    "`mailing_zip` VARCHAR(20) NULL AFTER `mailing_state`");

DROP PROCEDURE __add_col_if_missing__;

-- ----------------------------------------------------------------------------
-- 2) Tabla predios (datos del listado/subasta/foreclosure)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS predios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_cedula VARCHAR(20) NOT NULL,
    ticket_id INT NULL,

    case_number VARCHAR(80) NULL,
    parcel_number VARCHAR(80) NULL,
    type_of_foreclosure VARCHAR(120) NULL,

    property_street VARCHAR(255) NULL,
    property_city VARCHAR(120) NULL,
    property_state VARCHAR(10) NULL,
    property_zip VARCHAR(20) NULL,
    county VARCHAR(120) NULL,

    source VARCHAR(500) NULL,

    valor_a_devolver DECIMAL(12,2) NULL,
    valor_vendido DECIMAL(12,2) NULL,
    valor_inicial_subasta DECIMAL(12,2) NULL,
    date_sold DATE NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_predios_cliente FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula) ON DELETE CASCADE,
    CONSTRAINT fk_predios_ticket FOREIGN KEY (ticket_id) REFERENCES tiketera(id) ON DELETE SET NULL,

    INDEX idx_predios_cliente (cliente_cedula),
    INDEX idx_predios_case (case_number),
    INDEX idx_predios_parcel (parcel_number),
    INDEX idx_predios_date (date_sold),
    INDEX idx_predios_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 3) Telefonos del cliente (hasta 5)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cliente_telefonos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_cedula VARCHAR(20) NOT NULL,
    numero VARCHAR(40) NOT NULL,
    numero_normalizado VARCHAR(20) NULL,
    tipo ENUM('landline','wireless','voip','other') NOT NULL DEFAULT 'other',
    dnc_litigator ENUM('Y','N') NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cltel_cliente FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula) ON DELETE CASCADE,
    INDEX idx_cltel_cliente (cliente_cedula),
    INDEX idx_cltel_norm (numero_normalizado),
    UNIQUE KEY uq_cltel_cliente_orden (cliente_cedula, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 4) Emails del cliente (hasta 5)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cliente_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_cedula VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_clmail_cliente FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula) ON DELETE CASCADE,
    INDEX idx_clmail_cliente (cliente_cedula),
    INDEX idx_clmail_email (email),
    UNIQUE KEY uq_clmail_cliente_orden (cliente_cedula, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 5) Referencias personales del cliente (RELATIVE 1..5)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referencias_personales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_cedula VARCHAR(20) NOT NULL,
    nombre VARCHAR(120) NULL,
    apellido VARCHAR(120) NULL,
    possible_type VARCHAR(60) NULL,
    age TINYINT UNSIGNED NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ref_cliente FOREIGN KEY (cliente_cedula) REFERENCES clientes(cedula) ON DELETE CASCADE,
    INDEX idx_ref_cliente (cliente_cedula),
    UNIQUE KEY uq_ref_cliente_orden (cliente_cedula, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 6) Telefonos de cada referencia (hasta 5)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referencia_telefonos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referencia_id INT NOT NULL,
    numero VARCHAR(40) NOT NULL,
    numero_normalizado VARCHAR(20) NULL,
    tipo ENUM('landline','wireless','voip','other') NOT NULL DEFAULT 'other',
    dnc_litigator ENUM('Y','N') NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reftel_ref FOREIGN KEY (referencia_id) REFERENCES referencias_personales(id) ON DELETE CASCADE,
    INDEX idx_reftel_ref (referencia_id),
    INDEX idx_reftel_norm (numero_normalizado),
    UNIQUE KEY uq_reftel_ref_orden (referencia_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 7) Emails de cada referencia (hasta 5)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referencia_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referencia_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_refmail_ref FOREIGN KEY (referencia_id) REFERENCES referencias_personales(id) ON DELETE CASCADE,
    INDEX idx_refmail_ref (referencia_id),
    INDEX idx_refmail_email (email),
    UNIQUE KEY uq_refmail_ref_orden (referencia_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- ROLLBACK (referencia, NO se ejecuta automaticamente):
-- ============================================================================
-- DROP TABLE IF EXISTS referencia_emails;
-- DROP TABLE IF EXISTS referencia_telefonos;
-- DROP TABLE IF EXISTS referencias_personales;
-- DROP TABLE IF EXISTS cliente_emails;
-- DROP TABLE IF EXISTS cliente_telefonos;
-- DROP TABLE IF EXISTS predios;
-- ALTER TABLE clientes
--     DROP COLUMN mailing_zip,
--     DROP COLUMN mailing_state,
--     DROP COLUMN mailing_city,
--     DROP COLUMN mailing_street,
--     DROP COLUMN source,
--     DROP COLUMN deceased,
--     DROP COLUMN age;
