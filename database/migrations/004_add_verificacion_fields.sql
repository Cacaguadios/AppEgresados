-- ============================================
-- Migración 004: Campos de verificación de usuarios + admin credentials
-- Fecha: 2026-02-22
-- ============================================

-- 1. Add verification columns to usuarios
ALTER TABLE usuarios
    ADD COLUMN verificacion_estado ENUM('pendiente','verificado','rechazado') NOT NULL DEFAULT 'pendiente' AFTER fecha_ultima_login,
    ADD COLUMN verificacion_motivo_rechazo TEXT NULL AFTER verificacion_estado,
    ADD COLUMN verificacion_fecha DATETIME NULL AFTER verificacion_motivo_rechazo;

-- 2. Set admin user credentials (usuario='admin', password='Admin1234!')
UPDATE usuarios SET
    usuario = 'admin',
    contraseña = '$2y$10$Ch5pxuazWGP8vI0JgQ.sZechi72G.B6XmtFjRWoO6UlpHSmY3QYyu',
    verificacion_estado = 'verificado',
    verificacion_fecha = NOW()
WHERE id = 1 AND tipo_usuario = 'admin';

-- 3. Auto-verify existing active users
UPDATE usuarios SET verificacion_estado = 'verificado', verificacion_fecha = NOW()
WHERE activo = 1 AND verificacion_estado = 'pendiente' AND id != 1;
