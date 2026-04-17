-- ============================================================
-- Migration 009: Tabla para rastrear verificaciones por email
-- Fecha: 2026-03-17
-- Descripción: Agregar tabla para registrar cuándo se verificó
--              el email personal durante el registro
-- ============================================================

-- 1. Crear tabla de email_verification_log para auditoría
CREATE TABLE IF NOT EXISTS email_verification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    email_verificado VARCHAR(255) NOT NULL,
    tipo_verificacion ENUM('registro', 'recuperacion', 'cambio_email') NOT NULL DEFAULT 'registro',
    codigo_usado VARCHAR(6) NOT NULL,
    ip_direccion VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_verificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_fecha (fecha_verificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Agregar columna para rastrear si el email fue verificado en el registro
ALTER TABLE usuarios ADD COLUMN email_verificado_registro DATETIME DEFAULT NULL AFTER email_verificado;

-- ============================================================
-- Notes:
-- - email_verificado (0/1) indica estado actual
-- - email_verificado_registro almacena CUÁNDO se verificó
-- - email_verification_log es auditoría de todas las verificaciones
-- ============================================================
