<?php
/**
 * Modelo Oferta
 */

require_once __DIR__ . '/Database.php';

class Oferta extends Database {
    
    /**
     * Obtener oferta por ID (con datos del creador y conteo de postulantes)
     */
    public function getById($id) {
        $sql = "SELECT o.*, 
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS creador,
                       u.email AS creador_email,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                JOIN usuarios u ON o.id_usuario_creador = u.id
                WHERE o.id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    /**
     * Obtener todas las ofertas aprobadas (incluidas expiradas, con badge)
     */
    public function getAllApproved() {
        $sql = "SELECT o.*,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS creador,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                JOIN usuarios u ON o.id_usuario_creador = u.id
                WHERE o.estado = 'aprobada'
                ORDER BY o.fecha_creacion DESC";
        return $this->fetchAll($sql);
    }
    
    /**
     * Obtener ofertas aprobadas y vigentes
     */
    public function getApprovedAndActive() {
        $sql = "SELECT o.*,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS creador,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                JOIN usuarios u ON o.id_usuario_creador = u.id
                WHERE o.estado = 'aprobada' 
                AND o.fecha_expiracion > NOW()
                ORDER BY o.fecha_creacion DESC";
        return $this->fetchAll($sql);
    }
    
    /**
     * Crear oferta
     */
    public function create($data) {
        if (is_array($data['requisitos'] ?? null)) {
            $data['requisitos'] = json_encode($data['requisitos']);
        }
        $data['fecha_creacion'] = date('Y-m-d H:i:s');
        
        return $this->insert('ofertas', $data);
    }
    
    /**
     * Obtener ofertas del usuario (con conteo de postulantes)
     */
    public function getByUserId($id_usuario) {
        $sql = "SELECT o.*,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                WHERE o.id_usuario_creador = ?
                ORDER BY o.fecha_creacion DESC";
        return $this->fetchAll($sql, [$id_usuario]);
    }

    /**
     * Estadísticas de ofertas de un usuario
     */
    public function getStatsByUser($id_usuario) {
        $sql = "SELECT 
                    COUNT(*)                                    AS total,
                    SUM(estado = 'pendiente_aprobacion')        AS pendientes,
                    SUM(estado = 'aprobada' AND fecha_expiracion > NOW()) AS activas,
                    SUM(estado = 'rechazada')                   AS rechazadas
                FROM ofertas
                WHERE id_usuario_creador = ?";
        return $this->fetchOne($sql, [$id_usuario]);
    }

    /**
     * Total de postulantes en todas las ofertas de un usuario
     */
    public function getTotalPostulantesByUser($id_usuario) {
        $sql = "SELECT COUNT(*) AS total
                FROM postulaciones p
                JOIN ofertas o ON p.id_oferta = o.id
                WHERE o.id_usuario_creador = ?";
        $row = $this->fetchOne($sql, [$id_usuario]);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Obtener postulantes de las ofertas de un usuario (docente)
     */
    public function getPostulantesByUser($id_usuario, $ofertaId = null, $estado = null) {
        $sql = "SELECT p.*, 
                       o.titulo AS oferta_titulo, o.empresa AS oferta_empresa,
                       e.matricula, e.correo_personal, e.telefono, e.habilidades AS egresado_habilidades,
                       e.cv_path, e.especialidad, e.generacion,
                       u.nombre, u.apellidos, u.email
                FROM postulaciones p
                JOIN ofertas o ON p.id_oferta = o.id
                JOIN egresados e ON p.id_egresado = e.id
                JOIN usuarios u ON e.id_usuario = u.id
                WHERE o.id_usuario_creador = ?";
        $params = [$id_usuario];

        if ($ofertaId) {
            $sql .= " AND p.id_oferta = ?";
            $params[] = $ofertaId;
        }
        if ($estado) {
            $sql .= " AND p.estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY p.fecha_postulacion DESC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Actualizar oferta
     */
    public function updateOferta($id, $data) {
        $this->update('ofertas', $data, ['id' => $id]);
    }
    
    /**
     * Obtener ofertas pendientes
     */
    public function getPending() {
        $sql = "SELECT o.*, u.nombre as creador, u.apellidos as creador_apellidos, u.tipo_usuario as creador_rol
                FROM ofertas o
                JOIN usuarios u ON o.id_usuario_creador = u.id
                WHERE o.estado = 'pendiente_aprobacion'
                ORDER BY o.fecha_creacion ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Obtener TODAS las ofertas para panel de moderación (con creador y rol)
     */
    public function getAllForModeration() {
        $sql = "SELECT o.*, 
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS creador,
                       u.tipo_usuario AS creador_rol,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                JOIN usuarios u ON o.id_usuario_creador = u.id
                ORDER BY FIELD(o.estado, 'pendiente_aprobacion', 'rechazada', 'aprobada'), o.fecha_creacion DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Stats de moderación
     */
    public function getModeracionStats() {
        $sql = "SELECT 
            SUM(estado = 'pendiente_aprobacion') as pendientes,
            SUM(estado = 'aprobada') as aprobadas,
            SUM(estado = 'rechazada') as rechazadas,
            COUNT(*) as total
        FROM ofertas";
        return $this->fetchOne($sql);
    }

    /**
     * Aprobar oferta
     */
    public function approve($id, $id_admin) {
        $this->update('ofertas', 
            [
                'estado' => 'aprobada',
                'id_admin_aprobador' => $id_admin,
                'fecha_aprobacion' => date('Y-m-d H:i:s')
            ],
            ['id' => $id]
        );
    }
    
    /**
     * Rechazar oferta
     */
    public function reject($id, $razon) {
        $this->update('ofertas',
            [
                'estado' => 'rechazada',
                'razon_rechazo' => $razon
            ],
            ['id' => $id]
        );
    }
    
    /**
     * Actualizar estado de vacantes
     */
    public function updateVacancyStatus($id) {
        $sql = "SELECT COUNT(*) as total FROM postulaciones WHERE id_oferta = ? AND estado = 'pendiente'";
        $postulantes = $this->fetchOne($sql, [$id])['total'] ?? 0;
        
        $oferta = $this->getById($id);
        $estado = 'verde';
        
        if ($postulantes > 0) $estado = 'amarillo';
        if ($oferta['vacantes'] == 0) $estado = 'rojo';
        
        $this->update('ofertas', ['estado_vacante' => $estado], ['id' => $id]);
    }
}
?>