-- ============================================================
-- Migration 010: Sistema de recordatorio actualizar información
-- ============================================================

-- Agregar campos para recordar cuándo actualizar la información
ALTER TABLE egresados
  ADD COLUMN fecha_proximo_recordatorio DATETIME NULL AFTER fecha_actualizacion_seguimiento,
  ADD COLUMN recordatorio_visto TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_proximo_recordatorio,
  ADD COLUMN porcentaje_completitud INT NOT NULL DEFAULT 0 AFTER recordatorio_visto;

-- Índices para optimizar búsquedas
CREATE INDEX idx_recordatorio_visto ON egresados(recordatorio_visto, fecha_proximo_recordatorio);
CREATE INDEX idx_completitud ON egresados(porcentaje_completitud);

-- ============================================================
-- Actualizar tabla usuarios para rastrear último acceso
-- ============================================================
ALTER TABLE usuarios
  ADD COLUMN fecha_ultima_actualizacion_perfil DATETIME NULL;

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW COLUMNS FROM egresados;
-- SELECT id, porcentaje_completitud, fecha_proximo_recordatorio FROM egresados LIMIT 5;
