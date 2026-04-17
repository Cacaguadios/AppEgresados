-- ============================================================
-- Migración 011: Agregar información de contacto detallada
-- BD: bolsa_trabajo_utp
-- Fecha: 2026-03-24
-- ============================================================

-- ==========================================
-- 1. Agregar columnas de contacto a OFERTAS
-- ==========================================
ALTER TABLE ofertas
  ADD COLUMN nombre_contacto VARCHAR(255) NULL AFTER contacto,
  ADD COLUMN puesto_contacto VARCHAR(255) NULL AFTER nombre_contacto,
  ADD COLUMN telefono_contacto VARCHAR(20) NULL AFTER puesto_contacto;

-- ============================================================
-- Verificar
-- ============================================================
-- SHOW COLUMNS FROM ofertas;
