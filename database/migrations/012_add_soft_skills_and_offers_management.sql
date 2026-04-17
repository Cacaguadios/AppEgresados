-- ============================================================
-- Migración 012: Habilidades Blandas y Gestión de Ofertas
-- BD: bolsa_trabajo_utp
-- Fecha: 2026-03-24
-- ============================================================

-- ==========================================
-- 1. Agregar habilidades blandas a EGRESADOS
-- ==========================================
ALTER TABLE egresados
  ADD COLUMN habilidades_blandas TEXT NULL AFTER habilidades;

-- ==========================================
-- 2. Agregar columnas de baja a OFERTAS
-- ==========================================
ALTER TABLE ofertas
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado,
  ADD COLUMN fecha_baja DATETIME NULL AFTER activo,
  ADD COLUMN motivo_baja VARCHAR(255) NULL AFTER fecha_baja;

-- ==========================================
-- 3. Agregar columna para estado en POSTULACIONES
-- ==========================================
ALTER TABLE postulaciones
  ADD COLUMN retirada TINYINT(1) NOT NULL DEFAULT 0 AFTER estado,
  ADD COLUMN fecha_retiro DATETIME NULL AFTER retirada;

-- ============================================================
-- Verificar
-- ============================================================
-- SHOW COLUMNS FROM egresados WHERE Field LIKE '%blanda%';
-- SHOW COLUMNS FROM ofertas WHERE Field IN ('activo', 'fecha_baja', 'motivo_baja');
-- SHOW COLUMNS FROM postulaciones WHERE Field IN ('retirada', 'fecha_retiro');
