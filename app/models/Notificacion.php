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
     * Registrar un correo simulado en el log local.
     */
    private function registrarCorreoSimulado($to, $subject, $message, $tipo) {
        if (empty($to)) {
            return;
        }

        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/emails.log';
        $timestamp = date('Y-m-d H:i:s');
        $body = str_replace(["\r", "\n"], [' ', ' '], trim($message));
        $log = "[{$timestamp}] TO: {$to} | SUBJECT: {$subject} | TYPE: {$tipo} | MESSAGE: {$body}\n";
        file_put_contents($logFile, $log, FILE_APPEND);
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
    public function onPostulacion($ofertaTitulo, $idCreadorOferta, $egresadoNombre, $correoCreador = null) {
        $mensaje = "{$egresadoNombre} se postuló a tu oferta \"{$ofertaTitulo}\".";
        $this->crear(
            $idCreadorOferta,
            'nueva_postulacion',
            'Nuevo postulante',
            $mensaje,
            '../../views/docente/postulantes.php'
        );

        $this->registrarCorreoSimulado(
            $correoCreador,
            'Nueva postulación recibida - UTP',
            $mensaje,
            'nueva_postulacion'
        );
    }

    /**
     * Docente/TI invita a un egresado a revisar una vacante.
     */
    public function onOfertaInvitada($ofertaTitulo, $idUsuarioEgresado, $egresadoNombre, $correoEgresado = null, $urlOferta = null) {
        $mensaje = "{$egresadoNombre}, te invitamos a revisar la oferta \"{$ofertaTitulo}\" y postularte si te interesa.";

        $this->crear(
            $idUsuarioEgresado,
            'general',
            'Invitación a vacante',
            $mensaje,
            $urlOferta ?: '../../views/egresado/oferta-detalle.php'
        );

        $this->registrarCorreoSimulado(
            $correoEgresado,
            'Invitación a postularse - UTP',
            $mensaje,
            'invitacion_vacante'
        );
    }

    /**
     * Docente selecciona postulante → notifica al egresado
     */
    public function onPostulanteSeleccionado($ofertaTitulo, $idUsuarioEgresado, $correoEgresado = null) {
        $mensaje = "¡Felicidades! Fuiste seleccionado para la oferta \"{$ofertaTitulo}\".";
        $this->crear(
            $idUsuarioEgresado,
            'postulacion_seleccionada',
            '¡Has sido seleccionado!',
            $mensaje,
            '../../views/egresado/postulaciones.php'
        );

        $this->registrarCorreoSimulado(
            $correoEgresado,
            'Seleccionado en vacante - UTP',
            $mensaje,
            'postulacion_seleccionada'
        );
    }

    /**
     * Docente rechaza postulante → notifica al egresado
     */
    public function onPostulanteRechazado($ofertaTitulo, $idUsuarioEgresado, $correoEgresado = null) {
        $mensaje = "Tu postulación para \"{$ofertaTitulo}\" no fue seleccionada en esta ocasión.";
        $this->crear(
            $idUsuarioEgresado,
            'postulacion_rechazada',
            'Postulación no seleccionada',
            $mensaje,
            '../../views/egresado/postulaciones.php'
        );

        $this->registrarCorreoSimulado(
            $correoEgresado,
            'Estado de postulación - UTP',
            $mensaje,
            'postulacion_rechazada'
        );
    }

    /**
     * Docente invita a egresado a postularse → notifica al egresado
     */
    public function onInvitacionOferta($ofertaTitulo, $empresa, $idUsuarioEgresado, $ofertaId, $correoEgresado = null) {
        $mensaje = "Fuiste invitado a postularte para la vacante \"{$ofertaTitulo}\" en {$empresa}. ¡No pierdas esta oportunidad!";
        $this->crear(
            $idUsuarioEgresado,
            'invitacion_oferta',
            '¡Invitación a postularte!',
            $mensaje,
            "../../views/egresado/invitaciones.php"
        );

        $this->registrarCorreoSimulado(
            $correoEgresado,
            'Invitación a vacante - UTP',
            $mensaje,
            'invitacion_oferta'
        );
    }

    /**
     * Egresado acepta invitación y se postula → notifica al docente
     */
    public function onPostulacionRecibida($ofertaTitulo, $egresadoNombre, $idDocente, $correoDocente = null) {
        $mensaje = "{$egresadoNombre} aceptó tu invitación y se postuló para \"{$ofertaTitulo}\".";
        $this->crear(
            $idDocente,
            'nueva_postulacion',
            'Invitado aceptó y se postuló',
            $mensaje,
            '../../views/docente/postulantes.php'
        );

        $this->registrarCorreoSimulado(
            $correoDocente,
            'Postulación recibida - UTP',
            $mensaje,
            'nueva_postulacion'
        );
    }

    /**
     * El perfil del egresado no cumple requisitos → notifica al mismo egresado
     */
    public function onPerfilNoCumple($ofertaTitulo, $idUsuarioEgresado, $correoEgresado = null) {
        $mensaje = "Tu perfil no cumple completamente con los requisitos de la oferta \"{$ofertaTitulo}\". Te recomendamos actualizar tus habilidades y experiencia antes de postularte.";
        $this->crear(
            $idUsuarioEgresado,
            'general',
            'Perfil no coincide con la oferta',
            $mensaje,
            '../../views/egresado/perfil.php'
        );

        $this->registrarCorreoSimulado(
            $correoEgresado,
            'Tu perfil y la oferta - UTP',
            $mensaje,
            'perfil_no_cumple'
        );
    }

    /**
     * Postulante marcado como contactado → pregunta al ofertador sobre el resultado
     */
    public function onFeedbackSolicitado($ofertaTitulo, $egresadoNombre, $idCreadorOferta, $postulacionId, $correoCreador = null) {
        $mensaje = "Ya lograste el contacto con {$egresadoNombre} para la oferta \"{$ofertaTitulo}\". ¿Quedaste satisfecho? ¿El candidato obtuvo el empleo? Cuéntanos el resultado.";
        $this->crear(
            $idCreadorOferta,
            'feedback_pendiente',
            '¿Cómo resultó el contacto?',
            $mensaje,
            "../../views/docente/postulantes.php?feedback={$postulacionId}"
        );

        $this->registrarCorreoSimulado(
            $correoCreador,
            'Resultado del contacto - UTP',
            $mensaje,
            'feedback_pendiente'
        );
    }

    /**
     * Egresado retira su postulación → notifica al docente
     */
    public function onPostulacionRetirada($ofertaTitulo, $egresadoNombre, $idDocente, $correoDocente = null) {
        $mensaje = "{$egresadoNombre} retiró su postulación para \"{$ofertaTitulo}\".";
        $this->crear(
            $idDocente,
            'postulacion_retirada',
            'Postulación retirada',
            $mensaje,
            '../../views/docente/postulantes.php'
        );

        $this->registrarCorreoSimulado(
            $correoDocente,
            'Postulación retirada - UTP',
            $mensaje,
            'postulacion_retirada'
        );
    }
}
?>
