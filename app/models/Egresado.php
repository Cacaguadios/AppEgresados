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
     * - Información personal: correo_personal, telefono, especialidad
     * - Información laboral: trabaja_actualmente, empresa_actual, puesto_actual
     * - Detalles de empleo: modalidad_trabajo, jornada_trabajo, tipo_contrato
     * - Desarrollo: habilidades, anos_experiencia_ti, descripcion_experiencia
     */
    public function calcularCompletudinformacion($egresado_data) {
        // Campos que consideramos para la completitud
        $campos_perfil = [
            'nombre',                    // del usuario
            'correo_personal',           // contacto
            'telefono',                  // contacto
            'especialidad',              // info académica
        ];
        
        $campos_laborales = [
            'empresa_actual',            // empleo actual
            'puesto_actual',             // rol
            'modalidad_trabajo',         // presencial/híbrido/remoto
            'jornada_trabajo',          // completo/parcial
            'tipo_contrato',            // tipo de contrato
            'habilidades',              // competencias
        ];

        $campos_total = array_merge($campos_perfil, $campos_laborales);
        $campos_llenos = 0;

        foreach ($campos_total as $campo) {
            if (!empty($egresado_data[$campo])) {
                $campos_llenos++;
            }
        }

        $porcentaje = round(($campos_llenos / count($campos_total)) * 100);
        
        return [
            'porcentaje' => $porcentaje,
            'campos_llenos' => $campos_llenos,
            'campos_totales' => count($campos_total),
            'campos_faltantes' => count($campos_total) - $campos_llenos
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

        // Mostrar si:
        // 1. Tiene menos del 60% de info Y no ha visto el recordatorio en 30 días
        if ($completitud['porcentaje'] < 60) {
            if (empty($fecha_proximo) || strtotime($fecha_proximo) <= time()) {
                $debe_mostrar = true;
                $razon = 'completitud_baja';
            }
        }

        // 2. Información laboral no actualizada hace 3 meses
        if ($necesita_actualizacion && !$recordatorio_visto) {
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
            'necesita_actualizacion' => $necesita_actualizacion,
            'recordatorio_visto' => (bool)$recordatorio_visto
        ];
    }

    /**
     * Marcar recordatorio como visto (el usuario lo cerró)
     */
    public function marcarRecordatorioVisto($id_usuario) {
        // Establecer próximo recordatorio para 30 días después
        $fecha_proximo = date('Y-m-d H:i:s', strtotime('+30 days'));
        
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
