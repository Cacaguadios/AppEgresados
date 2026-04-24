<?php
/**
 * Modelo Usuario
 */

require_once __DIR__ . '/Database.php';

class Usuario extends Database {
    
    /**
     * Obtener usuario por email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        return $this->fetchOne($sql, [$email]);
    }
    
    /**
     * Obtener usuario por nombre de usuario
     */
    public function getByUsuario($usuario) {
        $sql = "SELECT * FROM usuarios WHERE usuario = ?";
        return $this->fetchOne($sql, [$usuario]);
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        return $this->fetchOne($sql, [$id]);
    }
    
    /**
     * Crear nuevo usuario (registro completo)
     * Devuelve el ID insertado o false en caso de error
     */
    public function create($email, $password, $nombre, $tipo_usuario) {
        $data = [
            'email' => $email,
            'contraseña' => password_hash($password, PASSWORD_BCRYPT),
            'nombre' => $nombre,
            'tipo_usuario' => $tipo_usuario,
            'activo' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert('usuarios', $data);
    }
    
    /**
     * Crear usuario con datos completos de registro
     */
    public function createFull($data) {
        return $this->insert('usuarios', $data);
    }
    
    /**
     * Crear registro en tabla egresados vinculado al usuario
     */
    public function createEgresado($idUsuario, $matricula, $curp, $correoPersonal = null, $telefono = null) {
        $data = [
            'id_usuario' => $idUsuario,
            'matricula'  => $matricula,
            'curp'       => $curp,
            'correo_personal' => $correoPersonal,
            'telefono'   => $telefono,
        ];
        return $this->insert('egresados', $data);
    }
    
    /**
     * Verificar contraseña
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Actualizar último login
     */
    public function updateLastLogin($id) {
        $this->update('usuarios', 
            ['fecha_ultima_login' => date('Y-m-d H:i:s')],
            ['id' => $id]
        );
    }
    
    /**
     * Actualizar contraseña y desactivar flag de cambio obligatorio
     */
    public function updatePassword($id, $newPlainPassword) {
        $this->update('usuarios', [
            'contraseña'           => password_hash($newPlainPassword, PASSWORD_BCRYPT),
            'requiere_cambio_pass' => 0,
        ], ['id' => $id]);
    }

    /**
     * Marcar email como verificado (registro o recuperación)
     */
    public function markEmailAsVerified($id) {
        return $this->update('usuarios', [
            'email_verificado' => 1,
            'email_verificado_registro' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /**
     * Email existe
     */
    public function emailExists($email) {
        return $this->count('usuarios', ['email' => $email]) > 0;
    }
    
    /**
     * Usuario (username) existe
     */
    public function usuarioExists($usuario) {
        return $this->count('usuarios', ['usuario' => $usuario]) > 0;
    }
    
    /**
     * Matrícula ya registrada en egresados
     */
    public function matriculaExists($matricula) {
        return $this->count('egresados', ['matricula' => $matricula]) > 0;
    }
    
    /**
     * CURP ya registrado en egresados
     */
    public function curpExists($curp) {
        return $this->count('egresados', ['curp' => $curp]) > 0;
    }
    
    /**
     * Actualizar perfil de usuario (campos editables)
     */
    public function updateProfile($id, $data) {
        return $this->update('usuarios', $data, ['id' => $id]);
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAll($tipo = null) {
        if ($tipo) {
            $sql = "SELECT * FROM usuarios WHERE tipo_usuario = ? ORDER BY fecha_creacion DESC";
            return $this->fetchAll($sql, [$tipo]);
        }
        
        $sql = "SELECT * FROM usuarios ORDER BY fecha_creacion DESC";
        return $this->fetchAll($sql);
    }

    // ─── Admin: Stats ───

    /**
     * Contar usuarios por estado de actividad
     */
    public function getAdminStats() {
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(activo = 1) as activos,
            SUM(activo = 0 OR activo IS NULL) as bloqueados,
            SUM(verificacion_estado = 'pendiente') as verif_pendientes,
            SUM(verificacion_estado = 'verificado') as verificados,
            SUM(tipo_usuario = 'egresado') as egresados,
            SUM(tipo_usuario = 'docente') as docentes,
            SUM(tipo_usuario = 'ti') as ti_users,
            SUM(tipo_usuario = 'admin') as admins
        FROM usuarios";
        return $this->fetchOne($sql);
    }

    /**
     * Obtener todos los usuarios con datos extra para admin
     */
    public function getAllForAdmin() {
        $sql = "SELECT u.*, 
            CASE WHEN e.matricula IS NOT NULL THEN e.matricula ELSE u.usuario END as identificador,
            e.matricula, e.curp
        FROM usuarios u
        LEFT JOIN egresados e ON u.id = e.id_usuario
        ORDER BY u.fecha_creacion DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Bloquear/Desbloquear usuario
     */
    public function toggleBlock($id, $block = true) {
        return $this->update('usuarios', ['activo' => $block ? 0 : 1], ['id' => $id]);
    }

    /**
     * Resetear contraseña a valor temporal
     */
    public function resetPassword($id, $tempPassword) {
        return $this->update('usuarios', [
            'contraseña' => password_hash($tempPassword, PASSWORD_BCRYPT),
            'requiere_cambio_pass' => 1,
        ], ['id' => $id]);
    }

    /**
     * Actualizar rol y estado de usuario
     */
    public function updateUserAdmin($id, $data) {
        return $this->update('usuarios', $data, ['id' => $id]);
    }

    // ─── Admin: Verificación ───

    /**
     * Obtener usuarios pendientes de verificación por tipo
     */
    public function getPendingVerification($tipo = null) {
        $sql = "SELECT u.*, e.matricula, e.curp 
            FROM usuarios u 
            LEFT JOIN egresados e ON u.id = e.id_usuario 
            WHERE u.verificacion_estado = 'pendiente'";
        $params = [];
        if ($tipo) {
            $sql .= " AND u.tipo_usuario = ?";
            $params[] = $tipo;
        }
        $sql .= " ORDER BY u.fecha_creacion ASC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Obtener todos los usuarios con datos de verificación (para tabla con tabs)
     */
    public function getAllVerification($tipo = null) {
        $sql = "SELECT u.*, e.matricula, e.curp 
            FROM usuarios u 
            LEFT JOIN egresados e ON u.id = e.id_usuario
            WHERE u.tipo_usuario != 'admin'";
        $params = [];
        if ($tipo) {
            $sql .= " AND u.tipo_usuario = ?";
            $params[] = $tipo;
        }
        $sql .= " ORDER BY FIELD(u.verificacion_estado, 'pendiente', 'rechazado', 'verificado'), u.fecha_creacion DESC";
        return $this->fetchAll($sql, $params);
    }

    /**
     * Verificar un usuario
     */
    public function verifyUser($id) {
        return $this->update('usuarios', [
            'verificacion_estado' => 'verificado',
            'verificacion_fecha' => date('Y-m-d H:i:s'),
            'verificacion_motivo_rechazo' => null,
        ], ['id' => $id]);
    }

    /**
     * Rechazar verificación de usuario
     */
    public function rejectVerification($id, $motivo) {
        return $this->update('usuarios', [
            'verificacion_estado' => 'rechazado',
            'verificacion_fecha' => date('Y-m-d H:i:s'),
            'verificacion_motivo_rechazo' => $motivo,
        ], ['id' => $id]);
    }

    /**
     * Contar verificaciones pendientes por tipo
     */
    public function countPendingVerification() {
        $sql = "SELECT 
            SUM(tipo_usuario = 'egresado' AND verificacion_estado = 'pendiente') as egresados,
            SUM(tipo_usuario = 'docente' AND verificacion_estado = 'pendiente') as docentes,
            SUM(tipo_usuario = 'ti' AND verificacion_estado = 'pendiente') as ti_users,
            SUM(verificacion_estado = 'pendiente') as total
        FROM usuarios WHERE tipo_usuario != 'admin'";
        return $this->fetchOne($sql);
    }

    /* ================================================================
     *  Métodos de Verificación por Email
     * ================================================================ */

    /**
     * Obtener usuario por email de verificación (institucional para docentes, personal para egresados)
     */
    public function getByInstitutionalEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE email_institucional = ?";
        return $this->fetchOne($sql, [$email]);
    }

    /**
     * Actualizar email de verificación y marcar como verificado
     */
    public function updateInstitutionalEmail($userId, $email) {
        return $this->update('usuarios', [
            'email_institucional' => $email,
            'email_verificado'    => 1,
        ], ['id' => $userId]);
    }

    /**
     * Crear código de verificación
     */
    public function createVerificationCode($email, $code, $tipo, $expiration) {
        return $this->insert('codigos_verificacion', [
            'email'             => $email,
            'codigo'            => $code,
            'tipo'              => $tipo,
            'fecha_expiracion'  => $expiration,
        ]);
    }

    /**
     * Obtener código de verificación activo más reciente
     */
    public function getVerificationCode($email, $tipo) {
        $sql = "SELECT * FROM codigos_verificacion 
                WHERE email = ? AND tipo = ? AND usado = 0 
                ORDER BY fecha_creacion DESC LIMIT 1";
        return $this->fetchOne($sql, [$email, $tipo]);
    }

    /**
     * Invalidar todos los códigos anteriores de un email y tipo
     */
    public function invalidateVerificationCodes($email, $tipo) {
        $sql = "UPDATE codigos_verificacion SET usado = 1 WHERE email = ? AND tipo = ? AND usado = 0";
        $this->query($sql, [$email, $tipo]);
    }

    /**
     * Incrementar intentos de verificación
     */
    public function incrementVerificationAttempts($codeId) {
        $sql = "UPDATE codigos_verificacion SET intentos = intentos + 1 WHERE id = ?";
        $this->query($sql, [$codeId]);
    }

    /**
     * Marcar código como usado
     */
    public function markVerificationCodeUsed($codeId) {
        $sql = "UPDATE codigos_verificacion SET usado = 1 WHERE id = ?";
        $this->query($sql, [$codeId]);
    }
}
?>