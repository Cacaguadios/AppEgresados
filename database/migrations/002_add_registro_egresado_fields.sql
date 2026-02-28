-- ============================================================
-- Migración: Soporte de registro de egresados
-- BD: bolsa_trabajo_utp
-- Fecha: 2026-02-17
-- ============================================================

-- 1. Agregar columna 'usuario' a tabla usuarios (login username)
ALTER TABLE usuarios
  ADD COLUMN usuario VARCHAR(100) NULL AFTER id;

-- 2. Agregar columna 'apellidos' a tabla usuarios
ALTER TABLE usuarios
  ADD COLUMN apellidos VARCHAR(255) NULL AFTER nombre;

-- 3. Agregar índice único a usuario (una vez que tenga datos)
ALTER TABLE usuarios
  ADD UNIQUE INDEX idx_usuario (usuario);

-- 4. Agregar columna para marcar si requiere cambio de contraseña
ALTER TABLE usuarios
  ADD COLUMN requiere_cambio_pass TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

-- ============================================================
-- Verificar que la migración se aplicó correctamente
-- ============================================================
-- SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'bolsa_trabajo_utp'
--   AND TABLE_NAME = 'usuarios'
-- ORDER BY ORDINAL_POSITION;
