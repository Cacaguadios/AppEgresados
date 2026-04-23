-- ============================================================
-- Migration 016: Feedback post-contacto en postulaciones
-- ============================================================

-- Columnas de feedback en postulaciones
ALTER TABLE postulaciones
    ADD COLUMN IF NOT EXISTS feedback_resultado  VARCHAR(20)  NULL COMMENT 'pendiente|satisfecho|insatisfecho',
    ADD COLUMN IF NOT EXISTS feedback_trabajo    TINYINT(1)   NULL COMMENT '1=quedó en el trabajo, 0=no quedó',
    ADD COLUMN IF NOT EXISTS feedback_comentario TEXT         NULL,
    ADD COLUMN IF NOT EXISTS fecha_feedback      DATETIME     NULL;

-- Tipo de notificación para feedback y perfil no cumple
ALTER TABLE notificaciones
    MODIFY COLUMN tipo ENUM(
        'oferta_nueva','oferta_aprobada','oferta_rechazada',
        'nueva_postulacion','postulacion_seleccionada','postulacion_rechazada',
        'nuevo_usuario','general',
        'invitacion_oferta','postulacion_retirada',
        'feedback_pendiente'
    ) NOT NULL DEFAULT 'general';
