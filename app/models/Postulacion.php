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
                       p.id_oferta AS oferta_id,
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
     * Estadísticas globales para reportes admin.
     */
    public function getAdminStats() {
        $sql = "SELECT
                    SUM(estado = 'pendiente') AS pendientes,
                    SUM(estado = 'preseleccionado') AS preseleccionadas,
                    SUM(estado = 'contactado') AS contactadas,
                    SUM(estado = 'rechazado') AS rechazadas,
                    SUM(estado = 'retirada') AS retiradas,
                    COUNT(*) AS total
                FROM postulaciones";
        return $this->fetchOne($sql);
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
     * Actualizar mensaje de postulación
     */
    public function updateMensaje($id, $mensaje) {
        $this->update('postulaciones', ['mensaje' => $mensaje], ['id' => $id]);
    }

    /**
     * Obtener postulación por ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                       o.titulo AS oferta_titulo, o.id_usuario_creador,
                       e.id_usuario AS egresado_usuario_id,
                       u.email AS egresado_email,
                       CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS egresado_nombre,
                       uc.email AS creador_oferta_email
                FROM postulaciones p
                JOIN ofertas o ON p.id_oferta = o.id
                JOIN egresados e ON p.id_egresado = e.id
                JOIN usuarios u ON e.id_usuario = u.id
                JOIN usuarios uc ON o.id_usuario_creador = uc.id
                WHERE p.id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    /**
     * Guardar feedback del ofertador sobre el resultado del contacto
     */
    public function guardarFeedback($id, $resultado, $quedo_en_trabajo, $comentario = '') {
        $this->update('postulaciones', [
            'feedback_resultado'   => $resultado,
            'feedback_trabajo'     => $quedo_en_trabajo,
            'feedback_comentario'  => $comentario,
            'fecha_feedback'       => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /**
     * Retirar postulación (marcar como retirada por el egresado)
     */
    public function retirar($id) {
        $this->update('postulaciones',
            [
                'retirada' => 1,
                'fecha_retiro' => date('Y-m-d H:i:s')
            ],
            ['id' => $id]
        );
    }

    /**
     * Restaurar postulación retirada
     */
    public function restaurar($id) {
        $this->update('postulaciones',
            [
                'retirada' => 0,
                'fecha_retiro' => null
            ],
            ['id' => $id]
        );
    }

    /**
     * Eliminar postulación de forma permanente
     */
    public function eliminar($id) {
        $this->delete('postulacion_habilidades_blandas', ['id_postulacion' => $id]);
        $this->delete('postulaciones', ['id' => $id]);
    }

    /**
     * Inicializar checklist de habilidades blandas para una postulación
     */
    public function inicializarChecklistHabilidadesBlandas($postulacionId, array $habilidades) {
        $habilidades = array_values(array_filter(array_map('trim', $habilidades)));
        $habilidades = array_values(array_unique($habilidades));

        if (empty($habilidades)) {
            return;
        }

        $existentes = $this->fetchAll(
            "SELECT habilidad FROM postulacion_habilidades_blandas WHERE id_postulacion = ?",
            [$postulacionId]
        );
        $setExistentes = array_map(fn($r) => mb_strtolower((string)$r['habilidad']), $existentes);

        foreach ($habilidades as $habilidad) {
            if (in_array(mb_strtolower($habilidad), $setExistentes, true)) {
                continue;
            }

            $this->insert('postulacion_habilidades_blandas', [
                'id_postulacion' => $postulacionId,
                'habilidad' => $habilidad,
                'cumple' => null,
                'fecha_evaluacion' => null,
                'evaluado_por' => null,
            ]);
        }
    }

    /**
     * Obtener evaluación de habilidades blandas de una postulación
     */
    public function getEvaluacionHabilidadesBlandas($postulacionId) {
        $sql = "SELECT *
                FROM postulacion_habilidades_blandas
                WHERE id_postulacion = ?
                ORDER BY habilidad ASC";
        return $this->fetchAll($sql, [$postulacionId]);
    }

    /**
     * Guardar evaluación cumple/no cumple de una habilidad blanda
     */
    public function evaluarHabilidadBlanda($postulacionId, $habilidad, $cumple, $evaluadoPor) {
        $sql = "UPDATE postulacion_habilidades_blandas
                SET cumple = ?, evaluado_por = ?, fecha_evaluacion = NOW()
                WHERE id_postulacion = ? AND habilidad = ?";
        $this->query($sql, [$cumple, $evaluadoPor, $postulacionId, $habilidad]);
    }

    /**
     * Obtener postulaciones activas de un egresado
     */
    public function getByEgresadoIdActivas($egresadoId) {
        $sql = "SELECT p.*, 
                       o.titulo, o.empresa, o.ubicacion, o.modalidad, o.habilidades AS oferta_habilidades,
                       o.estado_vacante, o.vacantes, o.fecha_expiracion,
                       o.salario_min, o.salario_max
                FROM postulaciones p
                JOIN ofertas o ON p.id_oferta = o.id
                WHERE p.id_egresado = ? AND p.retirada = 0
                ORDER BY p.fecha_postulacion DESC";
        return $this->fetchAll($sql, [$egresadoId]);
    }
}
