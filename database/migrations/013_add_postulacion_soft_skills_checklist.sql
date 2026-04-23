-- ============================================================
-- Migracion 013: Checklist de habilidades blandas por postulación
-- BD: bolsa_trabajo_utp
-- Fecha: 2026-04-22
-- ============================================================

CREATE TABLE IF NOT EXISTS postulacion_habilidades_blandas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_postulacion INT NOT NULL,
  habilidad VARCHAR(120) NOT NULL,
  cumple TINYINT(1) NULL,
  evaluado_por INT NULL,
  fecha_evaluacion DATETIME NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_postulacion_habilidad (id_postulacion, habilidad),
  KEY idx_postulacion_habilidad_postulacion (id_postulacion),
  KEY idx_postulacion_habilidad_evaluador (evaluado_por),
  CONSTRAINT fk_post_hb_postulacion
    FOREIGN KEY (id_postulacion) REFERENCES postulaciones(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_post_hb_evaluador
    FOREIGN KEY (evaluado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verificacion
-- SHOW TABLES LIKE 'postulacion_habilidades_blandas';
-- SHOW COLUMNS FROM postulacion_habilidades_blandas;
