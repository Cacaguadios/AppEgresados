-- ============================================================
-- Migration 006: Sistema de Notificaciones
-- ============================================================

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('oferta_nueva', 'oferta_aprobada', 'oferta_rechazada', 'nueva_postulacion', 'postulacion_seleccionada', 'postulacion_rechazada', 'nuevo_usuario', 'general') NOT NULL DEFAULT 'general',
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT,
    url VARCHAR(500) DEFAULT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (id_usuario),
    INDEX idx_usuario_leida (id_usuario, leida),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
