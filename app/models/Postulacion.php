<?php
/**
 * Modelo Postulacion
 */

require_once __DIR__ . '/Database.php';

class Postulacion extends Database {

    /**
     * Obtener postulaciones de un egresado (con datos de oferta)
     */
    public function getByEgresadoId($egresadoId) {
        $sql = "SELECT p.*, 
                       o.titulo, o.empresa, o.ubicacion, o.modalidad, o.habilidades AS oferta_habilidades,
                       o.estado_vacante, o.vacantes, o.fecha_expiracion,
                       o.salario_min, o.salario_max
                FROM postulaciones p
                JOIN ofertas o ON p.id_oferta = o.id
                WHERE p.id_egresado = ?
                ORDER BY p.fecha_postulacion DESC";
        return $this->fetchAll($sql, [$egresadoId]);
    }

    /**
     * Contar postulaciones por estado para un egresado
     */
    public function getStatsByEgresado($egresadoId) {
        $sql = "SELECT 
                    SUM(estado = 'pendiente')        AS enviadas,
                    SUM(estado = 'preseleccionado')  AS en_revision,
                    SUM(estado = 'contactado')       AS seleccionado,
                    SUM(estado = 'rechazado')        AS no_seleccionado,
                    COUNT(*)                         AS total
                FROM postulaciones
                WHERE id_egresado = ?";
        $row = $this->fetchOne($sql, [$egresadoId]);
        return [
            'enviadas'         => (int)($row['enviadas'] ?? 0),
            'en_revision'      => (int)($row['en_revision'] ?? 0),
            'seleccionado'     => (int)($row['seleccionado'] ?? 0),
            'no_seleccionado'  => (int)($row['no_seleccionado'] ?? 0),
            'total'            => (int)($row['total'] ?? 0),
        ];
    }

    /**
     * Verificar si un egresado ya postuló a una oferta
     */
    public function hasApplied($egresadoId, $ofertaId) {
        $sql = "SELECT id, estado, fecha_postulacion FROM postulaciones WHERE id_egresado = ? AND id_oferta = ?";
        return $this->fetchOne($sql, [$egresadoId, $ofertaId]);
    }

    /**
     * Contar postulantes de una oferta
     */
    public function countByOferta($ofertaId) {
        return $this->count('postulaciones', ['id_oferta' => $ofertaId]);
    }

    /**
     * Crear postulación
     */
    public function create($data) {
        return $this->insert('postulaciones', $data);
    }

    /**
     * Actualizar estado de una postulación
     */
    public function updateEstado($id, $estado) {
        $this->update('postulaciones', ['estado' => $estado], ['id' => $id]);
    }

    /**
     * Obtener postulación por ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       o.titulo AS oferta_titulo, o.id_usuario_creador,
                       e.id_usuario AS egresado_usuario_id
                FROM postulaciones p
                JOIN ofertas o ON p.id_oferta = o.id
                JOIN egresados e ON p.id_egresado = e.id
                WHERE p.id = ?";
        return $this->fetchOne($sql, [$id]);
    }
}
