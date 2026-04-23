<?php
/**
 * Modelo Invitacion
 */

require_once __DIR__ . '/Database.php';

class Invitacion extends Database {

    /**
     * Obtener invitaciones de un egresado
     */
    public function getByEgresadoId($egresadoId) {
        $sql = "SELECT i.*,
                       o.titulo, o.empresa, o.descripcion, o.ubicacion, o.modalidad,
                       o.salario_min, o.salario_max, o.vacantes,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS docente_nombre,
                       u.email AS docente_email
                FROM invitaciones i
                JOIN ofertas o ON i.id_oferta = o.id
                JOIN usuarios u ON i.id_docente = u.id
                WHERE i.id_egresado = ?
                ORDER BY i.fecha_invitacion DESC";
        return $this->fetchAll($sql, [$egresadoId]);
    }

    /**
     * Obtener invitaciones de una oferta (por docente)
     */
    public function getByOfertaId($ofertaId) {
        $sql = "SELECT i.*,
                       e.id_usuario AS egresado_usuario_id,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS egresado_nombre,
                       u.email AS egresado_email
                FROM invitaciones i
                JOIN egresados e ON i.id_egresado = e.id
                JOIN usuarios u ON e.id_usuario = u.id
                WHERE i.id_oferta = ?
                ORDER BY i.fecha_invitacion DESC";
        return $this->fetchAll($sql, [$ofertaId]);
    }

    /**
     * Obtener una invitación por ID
     */
    public function getById($id) {
        $sql = "SELECT i.*,
                       o.titulo, o.empresa, o.id_usuario_creador,
                       e.id_usuario AS egresado_usuario_id,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS docente_nombre
                FROM invitaciones i
                JOIN ofertas o ON i.id_oferta = o.id
                JOIN egresados e ON i.id_egresado = e.id
                JOIN usuarios u ON o.id_usuario_creador = u.id
                WHERE i.id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    /**
     * Verificar si una invitación ya existe
     */
    public function exists($ofertaId, $egresadoId) {
        $sql = "SELECT id FROM invitaciones WHERE id_oferta = ? AND id_egresado = ?";
        return $this->fetchOne($sql, [$ofertaId, $egresadoId]);
    }

    /**
     * Crear invitación
     */
    public function create($data) {
        $data['fecha_invitacion'] = date('Y-m-d H:i:s');
        return $this->insert('invitaciones', $data);
    }

    /**
     * Actualizar estado de invitación
     */
    public function updateEstado($id, $estado) {
        $update = [
            'estado' => $estado,
            'fecha_respuesta' => date('Y-m-d H:i:s')
        ];
        $this->update('invitaciones', $update, ['id' => $id]);
    }

    /**
     * Contar invitaciones pendientes de un egresado
     */
    public function countPendingByEgresado($egresadoId) {
        $sql = "SELECT COUNT(*) as total FROM invitaciones WHERE id_egresado = ? AND estado = 'pendiente'";
        $result = $this->fetchOne($sql, [$egresadoId]);
        return $result['total'] ?? 0;
    }

    /**
     * Contar invitaciones enviadas de una oferta
     */
    public function countByOferta($ofertaId) {
        return $this->count('invitaciones', ['id_oferta' => $ofertaId]);
    }

    /**
     * Marcar invitación como vista
     */
    public function markAsViewed($id) {
        $this->updateEstado($id, 'visto');
    }
}
?>
