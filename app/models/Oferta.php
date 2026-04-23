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
     * Desactivar automáticamente las ofertas cuya fecha de expiración ya pasó
     */
    public function desactivarExpiradas() {
        $sql = "UPDATE ofertas
                SET activo = 0, motivo_baja = 'Expirada', fecha_baja = NOW(), estado_vacante = 'rojo'
                WHERE estado = 'aprobada' AND activo = 1
                  AND fecha_expiracion IS NOT NULL AND fecha_expiracion < NOW()";
        $this->query($sql);
    }

    /**
     * Obtener ofertas aprobadas y vigentes
     */
    public function getApprovedAndActive() {
        $this->desactivarExpiradas();
        $sql = "SELECT o.*,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS creador,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                JOIN usuarios u ON o.id_usuario_creador = u.id
                WHERE o.estado = 'aprobada' 
                AND o.activo = 1
                AND o.vacantes > 0
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
              e.matricula, e.correo_personal, e.telefono, e.habilidades AS egresado_habilidades, e.habilidades_blandas,
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
     * Resumen de ofertas para reportes admin.
     */
    public function getReportSummary() {
        $sql = "SELECT
            COUNT(*) AS total,
            SUM(estado = 'aprobada') AS liberadas,
            SUM(estado = 'aprobada' AND activo = 1 AND fecha_expiracion > NOW()) AS activas,
            SUM(estado = 'pendiente_aprobacion') AS pendientes,
            SUM(estado = 'rechazada') AS rechazadas,
            SUM(activo = 0 OR vacantes = 0 OR fecha_expiracion <= NOW()) AS cerradas,
            COALESCE(SUM((SELECT COUNT(*) FROM postulaciones p WHERE p.id_oferta = ofertas.id)), 0) AS postulaciones_totales
        FROM ofertas";
        return $this->fetchOne($sql);
    }

    /**
     * Ofertas aprobadas por mes.
     */
    public function getApprovedByMonth($limit = 6) {
        $limit = max(1, (int)$limit);

        $sql = "SELECT
            DATE_FORMAT(fecha_aprobacion, '%Y-%m') AS periodo,
            DATE_FORMAT(fecha_aprobacion, '%b %Y') AS etiqueta,
            COUNT(*) AS total
        FROM ofertas
        WHERE estado = 'aprobada' AND fecha_aprobacion IS NOT NULL
        GROUP BY DATE_FORMAT(fecha_aprobacion, '%Y-%m'), DATE_FORMAT(fecha_aprobacion, '%b %Y')
        ORDER BY periodo DESC
        LIMIT {$limit}";

        return array_reverse($this->fetchAll($sql));
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
     * Decrementar vacantes de una oferta
     * Si llega a 0, elimina la oferta automáticamente
     */
    public function decrementVacancies($id) {
        $oferta = $this->getById($id);
        if (!$oferta) return false;

        $nuevasVacantes = max(0, ((int)$oferta['vacantes']) - 1);

        if ($nuevasVacantes <= 0) {
            // Dar de baja cuando se llena el cupo para conservar historial.
            $this->update('ofertas', [
                'vacantes' => 0,
                'activo' => 0,
                'estado_vacante' => 'rojo',
                'fecha_baja' => date('Y-m-d H:i:s'),
                'motivo_baja' => 'Cupo lleno'
            ], ['id' => $id]);
            return true;
        } else {
            // Actualizar vacantes
            $this->update('ofertas', ['vacantes' => $nuevasVacantes], ['id' => $id]);
            return true;
        }
    }

    /**
     * Actualizar estado de vacantes
     */
    public function updateVacancyStatus($id) {
        $sql = "SELECT COUNT(*) as total FROM postulaciones WHERE id_oferta = ? AND estado = 'pendiente'";
        $postulantes = $this->fetchOne($sql, [$id])['total'] ?? 0;
        
        $oferta = $this->getById($id);
        if (!$oferta) return;

        if ((int)($oferta['activo'] ?? 1) === 0) {
            $this->update('ofertas', ['estado_vacante' => 'rojo'], ['id' => $id]);
            return;
        }
        
        $estado = 'verde';
        
        if ($postulantes > 0) $estado = 'amarillo';
        if ($oferta['vacantes'] == 0) $estado = 'rojo';
        
        $this->update('ofertas', ['estado_vacante' => $estado], ['id' => $id]);
    }

    /**
     * Dar de baja una oferta
     */
    public function setBaja($id, $motivo = null) {
        $this->update('ofertas', 
            [
                'activo' => 0,
                'fecha_baja' => date('Y-m-d H:i:s'),
                'motivo_baja' => $motivo ?? null
            ],
            ['id' => $id]
        );
    }

    /**
     * Reactivar una oferta
     */
    public function setActiva($id) {
        $this->update('ofertas', 
            [
                'activo' => 1,
                'fecha_baja' => null,
                'motivo_baja' => null
            ],
            ['id' => $id]
        );
    }

    /**
     * Editar oferta existente
     */
    public function edit($id, $data) {
        $editables = ['titulo', 'empresa', 'ubicacion', 'modalidad', 'jornada', 
                      'salario_min', 'salario_max', 'beneficios', 'habilidades', 
                      'descripcion', 'requisitos', 'contacto', 'nombre_contacto', 
                      'puesto_contacto', 'telefono_contacto', 'vacantes', 'fecha_expiracion'];
        
        $dataFiltrada = array_intersect_key($data, array_flip($editables));
        
        if (is_array($dataFiltrada['requisitos'] ?? null)) {
            $dataFiltrada['requisitos'] = json_encode($dataFiltrada['requisitos']);
        }
        if (is_array($dataFiltrada['beneficios'] ?? null)) {
            $dataFiltrada['beneficios'] = json_encode($dataFiltrada['beneficios']);
        }
        if (is_array($dataFiltrada['habilidades'] ?? null)) {
            $dataFiltrada['habilidades'] = json_encode($dataFiltrada['habilidades']);
        }
        
        $this->update('ofertas', $dataFiltrada, ['id' => $id]);
    }

    /**
     * Obtener ofertas activas de un usuario
     */
    public function getByUserIdActive($id_usuario) {
        $sql = "SELECT o.*,
                       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
                FROM ofertas o
                WHERE o.id_usuario_creador = ? AND o.activo = 1
                ORDER BY o.fecha_creacion DESC";
        return $this->fetchAll($sql, [$id_usuario]);
    }
}
?>