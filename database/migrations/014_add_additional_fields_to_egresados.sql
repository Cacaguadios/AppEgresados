-- ============================================================
-- Migración 014: Campos adicionales en seguimiento de egresados
-- BD: bolsa_trabajo_utp
-- Fecha: 2026-04-22
-- ============================================================

ALTER TABLE egresados
  ADD COLUMN campo_adicional_1 VARCHAR(255) NULL AFTER descripcion_experiencia,
  ADD COLUMN campo_adicional_2 VARCHAR(255) NULL AFTER campo_adicional_1;

-- Verificar
-- SHOW COLUMNS FROM egresados;
