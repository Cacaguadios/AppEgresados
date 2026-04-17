-- ============================================================
-- Migration 008: Actualizar flujo de verificación por email
-- Fecha: 2026-03-17
-- Descripción: Adaptar BD para registro sin matrícula/CURP
--              Solo usando correo personal verificable
-- ============================================================

-- 1. Hacer матrícula y CURP opcionales en egresados
ALTER TABLE egresados
  MODIFY COLUMN matricula VARCHAR(20) DEFAULT NULL,
  MODIFY COLUMN curp VARCHAR(18) DEFAULT NULL;

-- 2. Crear índices para buscar egresados por email si se necesita
ALTER TABLE usuarios ADD INDEX idx_email (email);

-- 3. Ya no necesitamos email_institucional para egresados
-- Este campo se mantiene para docentes/ti si es necesario

-- ============================================================
-- Verificar cambios (comentado, usar para debugging)
-- ============================================================
-- SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'bolsa_trabajo_utp'
--   AND TABLE_NAME = 'egresados'
--   AND COLUMN_NAME IN ('matricula', 'curp');
