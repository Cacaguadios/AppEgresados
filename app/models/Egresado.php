<?php
/**
 * Modelo Egresado
 */

require_once __DIR__ . '/Database.php';

class Egresado extends Database {
    
    /**
     * Obtener egresado por ID usuario (con datos de usuario)
     */
    public function getByUsuarioId($id_usuario) {
        $sql = "SELECT e.*, u.email, u.nombre, u.apellidos, u.usuario
                FROM egresados e
                JOIN usuarios u ON e.id_usuario = u.id
                WHERE e.id_usuario = ?";
        return $this->fetchOne($sql, [$id_usuario]);
    }
    
    /**
     * Obtener por matrícula
     */
    public function getByMatricula($matricula) {
        $sql = "SELECT * FROM egresados WHERE matricula = ?";
        return $this->fetchOne($sql, [$matricula]);
    }
    
    /**
     * Crear perfil egresado
     */
    public function create($data) {
        return $this->insert('egresados', $data);
    }
    
    /**
     * Actualizar perfil (datos personales / contacto)
     */
    public function updatePerfil($id_usuario, $data) {
        $this->update('egresados', $data, ['id_usuario' => $id_usuario]);
    }

    /**
     * Guardar habilidades blandas (soft skills)
     */
    public function updateHabilidadesBlandas($id_usuario, $habilidades) {
        $data = ['habilidades_blandas' => is_array($habilidades) ? json_encode($habilidades) : $habilidades];
        $this->update('egresados', $data, ['id_usuario' => $id_usuario]);
    }

    /**
     * Obtener habilidades blandas
     */
    public function getHabilidadesBlandas($id_usuario) {
        $egresado = $this->getByUsuarioId($id_usuario);
        if (!$egresado) return [];
        return json_decode($egresado['habilidades_blandas'] ?? '[]', true) ?: [];
    }

    /**
     * Actualizar datos de seguimiento laboral
     */
    public function updateSeguimiento($id_usuario, $data) {
        $data['fecha_actualizacion_seguimiento'] = date('Y-m-d H:i:s');
        $this->update('egresados', $data, ['id_usuario' => $id_usuario]);
    }
    
    /**
     * Subir CV
     */
    public function uploadCV($id_usuario, $cv_path) {
        $this->update('egresados', ['cv_path' => $cv_path], ['id_usuario' => $id_usuario]);
    }
    
    /**
     * Obtener egresados con datos completos
     */
    public function getAllWithUser() {
        $sql = "SELECT e.*, u.email, u.nombre AS nombre_usuario, u.apellidos
                FROM egresados e
                JOIN usuarios u ON e.id_usuario = u.id
                ORDER BY u.fecha_creacion DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Obtener todos los egresados (simple)
     */
    public function getAll() {
        $sql = "SELECT e.*, u.id AS id_usuario, u.email, u.nombre, u.apellidos
                FROM egresados e
                JOIN usuarios u ON e.id_usuario = u.id
                WHERE u.activo = 1
                ORDER BY u.nombre ASC, u.apellidos ASC";
        return $this->fetchAll($sql);
    }
    
    /**
     * Estadísticas de egresados
     */
    public function getStats() {
        $total = $this->count('egresados');
        
        $sql = "SELECT COUNT(*) as total FROM egresados WHERE trabaja_actualmente = 1";
        $empleados = $this->fetchOne($sql)['total'] ?? 0;
        
        return [
            'total' => $total,
            'total_egresados' => $total,
            'empleados' => $empleados,
            'desempleados' => $total - $empleados
        ];
    }

    /**
     * Estadísticas de seguimiento para admin
     */
    public function getSeguimientoStats() {
        $total = $this->count('egresados');
        
        $sql = "SELECT 
            SUM(trabaja_actualmente = 1) as empleados,
            SUM(trabaja_en_ti = 1) as en_ti
        FROM egresados";
        $r = $this->fetchOne($sql);
        $empleados = (int)($r['empleados'] ?? 0);
        $enTI = (int)($r['en_ti'] ?? 0);
        $tasaTI = $total > 0 ? round(($enTI / $total) * 100) : 0;

        return [
            'total' => $total,
            'empleados' => $empleados,
            'en_ti' => $enTI,
            'tasa_ti' => $tasaTI
        ];
    }

    /**
     * Resumen ampliado para reportes admin.
     */
    public function getAdminReportSummary() {
        $sql = "SELECT
            COUNT(*) AS total,
            SUM(COALESCE(trabaja_actualmente, 0) = 1) AS empleados,
            SUM(COALESCE(trabaja_actualmente, 0) = 0) AS no_empleados,
            SUM(COALESCE(trabaja_en_ti, 0) = 1) AS en_ti,
            SUM(COALESCE(trabaja_actualmente, 0) = 1 AND (empresa_actual IS NOT NULL AND empresa_actual <> '')) AS con_empresa,
            SUM(COALESCE(trabaja_actualmente, 0) = 1 AND (puesto_actual IS NOT NULL AND puesto_actual <> '')) AS con_puesto,
            SUM(COALESCE(u.activo, 0) = 1) AS usuarios_activos,
            ROUND(AVG(
                CASE rango_salarial
                    WHEN '0-8000' THEN 4000
                    WHEN '8001-12000' THEN 10000
                    WHEN '12001-18000' THEN 15000
                    WHEN '18001-25000' THEN 21500
                    WHEN '25001-35000' THEN 30000
                    WHEN '35001+' THEN 40000
                    ELSE NULL
                END
            ), 2) AS salario_promedio_estimado,
            ROUND(AVG(
                CASE
                    WHEN fecha_inicio_empleo IS NOT NULL AND fecha_inicio_empleo <= CURDATE()
                        THEN TIMESTAMPDIFF(MONTH, fecha_inicio_empleo, CURDATE())
                    ELSE NULL
                END
            ), 2) AS promedio_meses_laborando
        FROM egresados e
        JOIN usuarios u ON e.id_usuario = u.id";

        return $this->fetchOne($sql);
    }

    /**
     * Top de empresas donde trabajan egresados.
     */
    public function getTopEmpresasEmpleadoras($limit = 10) {
        $limit = max(1, (int)$limit);

        $sql = "SELECT
            empresa_actual AS empresa,
            COUNT(*) AS total,
            SUM(COALESCE(trabaja_en_ti, 0) = 1) AS en_ti
        FROM egresados
        WHERE COALESCE(trabaja_actualmente, 0) = 1
          AND empresa_actual IS NOT NULL
          AND TRIM(empresa_actual) <> ''
        GROUP BY empresa_actual
        ORDER BY total DESC, empresa_actual ASC
        LIMIT {$limit}";

        return $this->fetchAll($sql);
    }

    /**
     * Filas normalizadas para exporte de egresados.
     */
    public function getExportRows() {
        $sql = "SELECT
            e.matricula,
            CONCAT(u.nombre, ' ', IFNULL(u.apellidos, '')) AS nombre,
            u.email,
            e.correo_personal,
            e.curp,
            e.generacion,
            e.especialidad,
            CASE WHEN COALESCE(e.trabaja_actualmente, 0) = 1 THEN 'Si' ELSE 'No' END AS trabaja_actualmente,
            CASE WHEN COALESCE(e.trabaja_en_ti, 0) = 1 THEN 'Si' ELSE 'No' END AS trabaja_en_ti,
            e.empresa_actual,
            e.puesto_actual,
            e.modalidad_trabajo,
            e.tipo_contrato,
            e.rango_salarial,
            e.telefono,
            e.fecha_actualizacion_seguimiento,
            CASE WHEN COALESCE(u.activo, 0) = 1 THEN 'Activo' ELSE 'Bloqueado' END AS estado_usuario
        FROM egresados e
        JOIN usuarios u ON e.id_usuario = u.id
        ORDER BY u.nombre ASC, u.apellidos ASC";

        return $this->fetchAll($sql);
    }

    /**
     * Filas de empleadores para exporte.
     */
    public function getEmployerRows() {
        $sql = "SELECT
            CONCAT(u.nombre, ' ', IFNULL(u.apellidos, '')) AS egresado,
            e.matricula,
            e.empresa_actual,
            e.puesto_actual,
            e.especialidad,
            CASE WHEN COALESCE(e.trabaja_en_ti, 0) = 1 THEN 'Si' ELSE 'No' END AS trabaja_en_ti,
            e.modalidad_trabajo,
            e.tipo_contrato,
            e.rango_salarial,
            e.fecha_actualizacion_seguimiento
        FROM egresados e
        JOIN usuarios u ON e.id_usuario = u.id
        WHERE COALESCE(e.trabaja_actualmente, 0) = 1
          AND e.empresa_actual IS NOT NULL
          AND TRIM(e.empresa_actual) <> ''
        ORDER BY e.empresa_actual ASC, u.nombre ASC, u.apellidos ASC";

        return $this->fetchAll($sql);
    }

    /**
     * Obtener todos los egresados con datos completos para seguimiento admin
     */
    public function getAllSeguimiento() {
        $sql = "SELECT e.*, u.email, u.nombre AS nombre_usuario, u.apellidos, u.usuario, u.activo
                FROM egresados e
                JOIN usuarios u ON e.id_usuario = u.id
                ORDER BY u.nombre ASC";
        return $this->fetchAll($sql);
    }

    /**
     * ================================================================
     * SISTEMA DE RECORDATORIO DE ACTUALIZACIÓN (3 meses)
     * ================================================================
     */

    /**
     * Calcular porcentaje de completitud de información laboral
     * Campos considerados:
     * - Información base del perfil: especialidad
     * - Información laboral: trabaja_actualmente, empresa_actual, puesto_actual
     * - Detalles de empleo: modalidad_trabajo, jornada_trabajo, tipo_contrato
     * - Desarrollo: habilidades, anos_experiencia_ti, descripcion_experiencia
     */
    public function calcularCompletudinformacion($egresado_data) {
        $campos_info = [
            'especialidad' => 'Especialidad',
            'empresa_actual' => 'Empresa actual',
            'puesto_actual' => 'Puesto actual',
            'modalidad_trabajo' => 'Modalidad de trabajo',
            'jornada_trabajo' => 'Jornada de trabajo',
            'tipo_contrato' => 'Tipo de contrato',
            'habilidades' => 'Habilidades tecnicas',
            'anos_experiencia_ti' => 'Experiencia en TI',
        ];

        $campos_total = array_keys($campos_info);
        $campos_llenos = 0;
        $campos_faltantes_detalle = [];

        foreach ($campos_total as $campo) {
            if (!empty($egresado_data[$campo])) {
                $campos_llenos++;
            } else {
                $campos_faltantes_detalle[] = $campos_info[$campo] ?? $campo;
            }
        }

        $porcentaje = round(($campos_llenos / count($campos_total)) * 100);
        
        return [
            'porcentaje' => $porcentaje,
            'campos_llenos' => $campos_llenos,
            'campos_totales' => count($campos_total),
            'campos_faltantes' => count($campos_total) - $campos_llenos,
            'campos_faltantes_detalle' => $campos_faltantes_detalle,
        ];
    }

    /**
     * Verificar si el egresado necesita actualizar información
     * Retorna true si:
     * - La última actualización fue hace más de 3 meses, O
     * - Nunca ha actualizado (fecha_actualizacion_seguimiento es NULL)
     */
    public function necesitaActualizacion($egresado_data) {
        $fecha_actualizacion = $egresado_data['fecha_actualizacion_seguimiento'] ?? null;
        
        // Si nunca ha actualizado o si pasaron más de 3 meses
        if (empty($fecha_actualizacion)) {
            return true;
        }

        $fecha_actualizacion = strtotime($fecha_actualizacion);
        $hace_3_meses = strtotime('-3 months');
        
        return $fecha_actualizacion < $hace_3_meses;
    }

    /**
     * Obtener estado del recordatorio para mostrar en el dashboard
     */
    public function obtenerEstadoRecordatorio($id_usuario) {
        $egresado = $this->getByUsuarioId($id_usuario);
        
        if (!$egresado) {
            return null;
        }

        $completitud = $this->calcularCompletudinformacion($egresado);
        $necesita_actualizacion = $this->necesitaActualizacion($egresado);

        // Verificar si debe mostrar el recordatorio
        $fecha_proximo = $egresado['fecha_proximo_recordatorio'] ?? null;
        $recordatorio_visto = $egresado['recordatorio_visto'] ?? 0;
        
        $debe_mostrar = false;
        $razon = '';

        $ya_corresponde_recordatorio = empty($fecha_proximo) || strtotime($fecha_proximo) <= time();

        // Mostrar únicamente cuando ya corresponde una nueva ventana trimestral.
        if ($ya_corresponde_recordatorio && $completitud['porcentaje'] < 60) {
            $debe_mostrar = true;
            $razon = 'completitud_baja';
        }

        if ($ya_corresponde_recordatorio && $necesita_actualizacion) {
            $debe_mostrar = true;
            $razon = $razon ?: 'actualizacion_vencida';
        }

        return [
            'debe_mostrar' => $debe_mostrar,
            'razon' => $razon,
            'porcentaje_completitud' => $completitud['porcentaje'],
            'campos_llenos' => $completitud['campos_llenos'],
            'campos_totales' => $completitud['campos_totales'],
            'campos_faltantes' => $completitud['campos_faltantes'],
            'campos_faltantes_detalle' => $completitud['campos_faltantes_detalle'] ?? [],
            'necesita_actualizacion' => $necesita_actualizacion,
            'recordatorio_visto' => (bool)$recordatorio_visto
        ];
    }

    /**
     * Marcar recordatorio como visto (el usuario lo cerró)
     */
    public function marcarRecordatorioVisto($id_usuario) {
        // Establecer próximo recordatorio para 3 meses después
        $fecha_proximo = date('Y-m-d H:i:s', strtotime('+3 months'));
        
        $this->update('egresados', [
            'recordatorio_visto' => 1,
            'fecha_proximo_recordatorio' => $fecha_proximo
        ], ['id_usuario' => $id_usuario]);

        return true;
    }

    /**
     * Actualizar próximo recordatorio después de actualizar información
     */
    public function setProximoRecordatorio($id_usuario) {
        // El próximo recordatorio será en 3 meses
        $fecha_proximo = date('Y-m-d H:i:s', strtotime('+3 months'));
        
        $this->update('egresados', [
            'recordatorio_visto' => 0,
            'fecha_proximo_recordatorio' => $fecha_proximo,
            'fecha_actualizacion_seguimiento' => date('Y-m-d H:i:s')
        ], ['id_usuario' => $id_usuario]);

        return true;
    }

    /**
     * Actualizar porcentaje de completitud
     */
    public function actualizarCompletudinformacion($id_usuario) {
        $egresado = $this->getByUsuarioId($id_usuario);
        
        if (!$egresado) {
            return false;
        }

        $completitud = $this->calcularCompletudinformacion($egresado);
        
        $this->update('egresados', [
            'porcentaje_completitud' => $completitud['porcentaje']
        ], ['id_usuario' => $id_usuario]);

        return $completitud['porcentaje'];
    }
}
