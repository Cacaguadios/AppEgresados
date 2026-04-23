-- ============================================================
-- Migration 015: Sistema de Invitaciones para Docentes
-- ============================================================

-- Agregar campos a postulaciones para soporte de retiro
ALTER TABLE postulaciones
ADD COLUMN IF NOT EXISTS retirada TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS fecha_retiro DATETIME NULL;

-- Tabla para invitaciones de docentes a egresados
CREATE TABLE IF NOT EXISTS invitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_oferta INT NOT NULL,
    id_docente INT NOT NULL,
    id_egresado INT NOT NULL,
    estado ENUM('pendiente', 'visto', 'rechazado', 'aceptado') NOT NULL DEFAULT 'pendiente',
    fecha_invitacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME NULL,
    INDEX idx_oferta (id_oferta),
    INDEX idx_docente (id_docente),
    INDEX idx_egresado (id_egresado),
    INDEX idx_estado (estado),
    INDEX idx_egresado_estado (id_egresado, estado),
    FOREIGN KEY (id_oferta) REFERENCES ofertas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_docente) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_egresado) REFERENCES egresados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar tipo de notificación para invitaciones
ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM('oferta_nueva', 'oferta_aprobada', 'oferta_rechazada', 'nueva_postulacion', 'postulacion_seleccionada', 'postulacion_rechazada', 'nuevo_usuario', 'general', 'invitacion_oferta', 'postulacion_retirada') NOT NULL DEFAULT 'general';

-- Agregar estado "retirada" a postulaciones si no existe
ALTER TABLE postulaciones MODIFY COLUMN estado ENUM('pendiente', 'preseleccionado', 'contactado', 'rechazado', 'retirada') NOT NULL DEFAULT 'pendiente';
