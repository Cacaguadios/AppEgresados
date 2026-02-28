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
}
