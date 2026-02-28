-- ============================================================
-- Migración 003: Columnas para ofertas completas y seguimiento
-- BD: bolsa_trabajo_utp
-- Fecha: 2026-02-17
-- ============================================================

-- ==========================================
-- 1. Columnas adicionales en tabla OFERTAS
-- ==========================================
ALTER TABLE ofertas
  ADD COLUMN empresa VARCHAR(255) NULL AFTER titulo,
  ADD COLUMN ubicacion VARCHAR(255) NULL AFTER empresa,
  ADD COLUMN modalidad ENUM('presencial','remoto','hibrido') NULL DEFAULT 'presencial' AFTER ubicacion,
  ADD COLUMN jornada ENUM('completo','parcial','freelance') NULL DEFAULT 'completo' AFTER modalidad,
  ADD COLUMN salario_min DECIMAL(10,2) NULL AFTER jornada,
  ADD COLUMN salario_max DECIMAL(10,2) NULL AFTER salario_min,
  ADD COLUMN beneficios TEXT NULL AFTER salario_max,
  ADD COLUMN habilidades TEXT NULL AFTER beneficios;

-- ==========================================
-- 2. Columnas de seguimiento en EGRESADOS
-- ==========================================
ALTER TABLE egresados
  ADD COLUMN correo_personal VARCHAR(255) NULL AFTER curp,
  ADD COLUMN trabaja_actualmente TINYINT(1) NOT NULL DEFAULT 0 AFTER correo_personal,
  ADD COLUMN trabaja_en_ti TINYINT(1) NOT NULL DEFAULT 0 AFTER trabaja_actualmente,
  ADD COLUMN empresa_actual VARCHAR(255) NULL AFTER trabaja_en_ti,
  ADD COLUMN puesto_actual VARCHAR(255) NULL AFTER empresa_actual,
  ADD COLUMN modalidad_trabajo ENUM('presencial','hibrido','remoto') NULL AFTER puesto_actual,
  ADD COLUMN jornada_trabajo ENUM('completo','parcial','freelance') NULL AFTER modalidad_trabajo,
  ADD COLUMN ubicacion_trabajo VARCHAR(255) NULL AFTER jornada_trabajo,
  ADD COLUMN tipo_contrato ENUM('indefinido','temporal','proyecto','honorarios') NULL AFTER ubicacion_trabajo,
  ADD COLUMN fecha_inicio_empleo DATE NULL AFTER tipo_contrato,
  ADD COLUMN rango_salarial VARCHAR(50) NULL AFTER fecha_inicio_empleo,
  ADD COLUMN prestaciones TEXT NULL AFTER rango_salarial,
  ADD COLUMN anos_experiencia_ti VARCHAR(20) NULL AFTER prestaciones,
  ADD COLUMN descripcion_experiencia TEXT NULL AFTER anos_experiencia_ti,
  ADD COLUMN habilidades TEXT NULL AFTER descripcion_experiencia,
  ADD COLUMN fecha_actualizacion_seguimiento TIMESTAMP NULL AFTER habilidades;

-- ============================================================
-- Verificar
-- ============================================================
-- SHOW COLUMNS FROM ofertas;
-- SHOW COLUMNS FROM egresados;
