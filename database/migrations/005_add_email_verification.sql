-- ============================================================
-- Migration 005: Email Verification (Registration + Password Reset)
-- ============================================================

-- Tabla para códigos de verificación por email
CREATE TABLE IF NOT EXISTS codigos_verificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    tipo ENUM('registro', 'recuperacion') NOT NULL DEFAULT 'registro',
    usado TINYINT(1) NOT NULL DEFAULT 0,
    intentos INT NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    INDEX idx_email_tipo (email, tipo),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columna de email institucional a usuarios
ALTER TABLE usuarios ADD COLUMN email_institucional VARCHAR(255) DEFAULT NULL AFTER email;

-- Agregar columna de verificación de email
ALTER TABLE usuarios ADD COLUMN email_verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER email_institucional;
