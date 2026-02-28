<?php
/**
 * Modelo Notificacion
 * Gestiona las notificaciones del sistema para todos los roles
 */

require_once __DIR__ . '/Database.php';

class Notificacion extends Database {

    /**
     * Crear una notificación para un usuario específico
     */
    public function crear($idUsuario, $tipo, $titulo, $mensaje = '', $url = null) {
        return $this->insert('notificaciones', [
            'id_usuario'     => $idUsuario,
            'tipo'           => $tipo,
            'titulo'         => $titulo,
            'mensaje'        => $mensaje,
            'url'            => $url,
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Crear notificación masiva para todos los usuarios de un rol
     */
    public function crearParaRol($tipo, $titulo, $mensaje, $url, $rol) {
        $sql = "SELECT id FROM usuarios WHERE tipo_usuario = ? AND activo = 1";
        $usuarios = $this->fetchAll($sql, [$rol]);
        foreach ($usuarios as $u) {
            $this->crear($u['id'], $tipo, $titulo, $mensaje, $url);
        }
    }

    /**
     * Crear notificación para todos los admins
     */
    public function notificarAdmins($tipo, $titulo, $mensaje, $url = null) {
        $this->crearParaRol($tipo, $titulo, $mensaje, $url, 'admin');
    }

    /**
     * Crear notificación para todos los egresados
     */
    public function notificarEgresados($tipo, $titulo, $mensaje, $url = null) {
        $this->crearParaRol($tipo, $titulo, $mensaje, $url, 'egresado');
    }

    /**
     * Obtener notificaciones de un usuario (más recientes primero)
     */
    public function getByUsuario($idUsuario, $limit = 20) {
        $sql = "SELECT * FROM notificaciones 
                WHERE id_usuario = ? 
                ORDER BY fecha_creacion DESC 
                LIMIT ?";
        $stmt = $this->query($sql, [$idUsuario, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Contar notificaciones no leídas
     */
    public function contarNoLeidas($idUsuario) {
        $sql = "SELECT COUNT(*) as total FROM notificaciones WHERE id_usuario = ? AND leida = 0";
        $row = $this->fetchOne($sql, [$idUsuario]);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Marcar una notificación como leída
     */
    public function marcarLeida($id, $idUsuario) {
        $sql = "UPDATE notificaciones SET leida = 1 WHERE id = ? AND id_usuario = ?";
        $this->query($sql, [$id, $idUsuario]);
    }

    /**
     * Marcar todas las notificaciones de un usuario como leídas
     */
    public function marcarTodasLeidas($idUsuario) {
        $sql = "UPDATE notificaciones SET leida = 1 WHERE id_usuario = ? AND leida = 0";
        $this->query($sql, [$idUsuario]);
    }

    /**
     * Eliminar notificaciones antiguas (más de 30 días)
     */
    public function limpiarAntiguas() {
        $sql = "DELETE FROM notificaciones WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY) AND leida = 1";
        $this->query($sql);
    }

    /* ================================================================
     *  Métodos de conveniencia para crear notificaciones específicas
     * ================================================================ */

    /**
     * Docente/TI publica oferta → notifica a admins
     */
    public function onOfertaCreada($ofertaTitulo, $creadorNombre) {
        $this->notificarAdmins(
            'nueva_postulacion',
            'Nueva oferta por moderar',
            "{$creadorNombre} publicó la oferta \"{$ofertaTitulo}\". Revísala para aprobarla.",
            '../../views/admin/moderacion/list.php'
        );
    }

    /**
     * Admin aprueba oferta → notifica al creador + todos los egresados
     */
    public function onOfertaAprobada($ofertaId, $ofertaTitulo, $idCreador) {
        // Notificar al docente/ti creador
        $this->crear(
            $idCreador,
            'oferta_aprobada',
            'Tu oferta fue aprobada',
            "La oferta \"{$ofertaTitulo}\" ha sido aprobada y ya es visible para los egresados.",
            '../../views/docente/mis-ofertas.php'
        );

        // Notificar a todos los egresados
        $this->notificarEgresados(
            'oferta_nueva',
            '¡Nueva oferta disponible!',
            "Se publicó la oferta \"{$ofertaTitulo}\". ¡Revísala y postúlate!",
            "../../views/egresado/oferta-detalle.php?id={$ofertaId}"
        );
    }

    /**
     * Admin rechaza oferta → notifica al creador
     */
    public function onOfertaRechazada($ofertaTitulo, $idCreador, $razon) {
        $this->crear(
            $idCreador,
            'oferta_rechazada',
            'Oferta rechazada',
            "La oferta \"{$ofertaTitulo}\" fue rechazada. Motivo: {$razon}",
            '../../views/docente/mis-ofertas.php'
        );
    }

    /**
     * Egresado se postula → notifica al docente/ti creador de la oferta
     */
    public function onPostulacion($ofertaTitulo, $idCreadorOferta, $egresadoNombre) {
        $this->crear(
            $idCreadorOferta,
            'nueva_postulacion',
            'Nuevo postulante',
            "{$egresadoNombre} se postuló a tu oferta \"{$ofertaTitulo}\".",
            '../../views/docente/postulantes.php'
        );
    }

    /**
     * Docente selecciona postulante → notifica al egresado
     */
    public function onPostulanteSeleccionado($ofertaTitulo, $idUsuarioEgresado) {
        $this->crear(
            $idUsuarioEgresado,
            'postulacion_seleccionada',
            '¡Has sido seleccionado!',
            "¡Felicidades! Fuiste seleccionado para la oferta \"{$ofertaTitulo}\".",
            '../../views/egresado/postulaciones.php'
        );
    }

    /**
     * Docente rechaza postulante → notifica al egresado
     */
    public function onPostulanteRechazado($ofertaTitulo, $idUsuarioEgresado) {
        $this->crear(
            $idUsuarioEgresado,
            'postulacion_rechazada',
            'Postulación no seleccionada',
            "Tu postulación para \"{$ofertaTitulo}\" no fue seleccionada en esta ocasión.",
            '../../views/egresado/postulaciones.php'
        );
    }
}
?>
