<?php
/**
 * Controlador de Registro – Egresados
 * Maneja las 3 etapas del registro + inserción real en BD
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Security.php';

class RegisterController {

    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /* ================================================================
     *  PASO 1 – Validar selección de rol
     * ================================================================ */
    public function validateRoleSelection($role) {
        $rolesValidos = ['egresado', 'docente', 'ti'];

        if (empty($role) || !in_array($role, $rolesValidos)) {
            return [
                'success' => false,
                'message' => 'Debes seleccionar un tipo de usuario válido.'
            ];
        }

        return ['success' => true, 'role' => $role];
    }

    /* ================================================================
     *  PASO 2 – Validar datos de verificación (solo egresado por ahora)
     * ================================================================ */
    public function validateVerification($role, $data) {

        // Sanitizar todo lo que entra
        $data = array_map(function ($v) {
            return Security::sanitize(trim($v));
        }, $data);

        switch ($role) {
            case 'egresado':
                return $this->validateEgresado($data);
            case 'docente':
                return $this->validateDocente($data);
            case 'ti':
                return $this->validateTI($data);
            default:
                return ['success' => false, 'message' => 'Rol no reconocido.'];
        }
    }

    /* ── Egresado: matrícula (10 dígitos) + CURP (18 caracteres) ── */
    private function validateEgresado($data) {
        $matricula = strtoupper($data['matricula'] ?? '');
        $curp      = strtoupper($data['curp'] ?? '');
        $errors    = [];

        // Matrícula
        if (empty($matricula)) {
            $errors[] = 'La matrícula es requerida.';
        } elseif (!preg_match('/^\d{10}$/', $matricula)) {
            $errors[] = 'La matrícula debe tener exactamente 10 dígitos.';
        } elseif ($this->usuarioModel->matriculaExists($matricula)) {
            $errors[] = 'Esta matrícula ya fue registrada.';
        }

        // CURP
        if (empty($curp)) {
            $errors[] = 'El CURP es requerido.';
        } elseif (!preg_match('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/', $curp)) {
            $errors[] = 'El CURP no tiene un formato válido.';
        } elseif ($this->usuarioModel->curpExists($curp)) {
            $errors[] = 'Este CURP ya fue registrado.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        return [
            'success' => true,
            'data'    => ['matricula' => $matricula, 'curp' => $curp]
        ];
    }

    /* ── Docente: ID 6-8 alfanuméricos ── */
    private function validateDocente($data) {
        $id = strtoupper(trim($data['id_docente'] ?? ''));

        if (empty($id)) {
            return ['success' => false, 'message' => 'El ID de docente es requerido.'];
        }
        if (!preg_match('/^[A-Z0-9]{6,8}$/', $id)) {
            return ['success' => false, 'message' => 'El ID de docente debe tener 6-8 caracteres alfanuméricos.'];
        }

        return ['success' => true, 'data' => ['id_docente' => $id]];
    }

    /* ── TI: 5-6 dígitos ── */
    private function validateTI($data) {
        $id = trim($data['id_ti'] ?? '');

        if (empty($id)) {
            return ['success' => false, 'message' => 'El ID de Personal TI es requerido.'];
        }
        if (!preg_match('/^\d{5,6}$/', $id)) {
            return ['success' => false, 'message' => 'El ID de TI debe tener 5-6 dígitos.'];
        }

        return ['success' => true, 'data' => ['id_ti' => $id]];
    }

    /* ================================================================
     *  PASO 3 – Crear usuario en BD (egresado)
     * ================================================================ */
    public function createUser($nombre, $apellidos, $role, $verificacionData) {
        // Sanitizar
        $nombre    = Security::sanitize(trim($nombre));
        $apellidos = Security::sanitize(trim($apellidos));
        $errors    = [];

        if (empty($nombre))    $errors[] = 'El nombre es requerido.';
        if (empty($apellidos)) $errors[] = 'Los apellidos son requeridos.';

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        // Generar credenciales
        $usuario  = $this->generateUsername($nombre, $apellidos);
        $password = $this->generatePassword();
        $email    = $usuario . '@egresados.utp.edu.mx';

        // Verificar que no exista duplicado de email
        if ($this->usuarioModel->emailExists($email)) {
            $suffix = rand(10, 999);
            $usuario = $usuario . $suffix;
            $email   = $usuario . '@egresados.utp.edu.mx';
        }

        // ── Insertar en tabla usuarios ──
        try {
            $idUsuario = $this->usuarioModel->createFull([
                'usuario'              => $usuario,
                'email'                => $email,
                'contraseña'           => password_hash($password, PASSWORD_BCRYPT),
                'nombre'               => $nombre,
                'apellidos'            => $apellidos,
                'tipo_usuario'         => $role,
                'activo'               => 1,
                'requiere_cambio_pass' => 1,
                'fecha_creacion'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear el usuario. Intenta de nuevo.'
            ];
        }

        // ── Insertar en tabla egresados (solo si rol = egresado) ──
        if ($role === 'egresado' && !empty($verificacionData)) {
            try {
                $this->usuarioModel->createEgresado(
                    $idUsuario,
                    $verificacionData['matricula'] ?? '',
                    $verificacionData['curp']      ?? ''
                );
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Error al vincular datos de egresado. Intenta de nuevo.'
                ];
            }
        }

        return [
            'success'   => true,
            'usuario'   => $usuario,
            'password'  => $password,
            'email'     => $email,
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'role'      => $role,
        ];
    }

    /* ================================================================
     *  Utilidades
     * ================================================================ */

    /**
     * Generar username: primer-nombre.primer-apellido  (sin acentos)
     * Si ya existe agrega un sufijo numérico
     */
    private function generateUsername($nombre, $apellidos) {
        $primer   = explode(' ', trim($nombre))[0];
        $apellido = explode(' ', trim($apellidos))[0];

        $base = strtolower($this->removeAccents($primer . '.' . $apellido));
        $base = preg_replace('/[^a-z0-9.]/', '', $base);

        $usuario = $base;
        $i = 1;
        while ($this->usuarioModel->usuarioExists($usuario)) {
            $usuario = $base . $i;
            $i++;
        }

        return $usuario;
    }

    /**
     * Generar contraseña temporal segura (12 caracteres)
     * Garantiza 1 mayúscula, 1 minúscula, 1 dígito, 1 símbolo
     */
    private function generatePassword($length = 12) {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $special = '!@#$%&*';

        $pwd  = $upper[random_int(0, strlen($upper) - 1)];
        $pwd .= $lower[random_int(0, strlen($lower) - 1)];
        $pwd .= $digits[random_int(0, strlen($digits) - 1)];
        $pwd .= $special[random_int(0, strlen($special) - 1)];

        $all = $upper . $lower . $digits . $special;
        for ($i = 4; $i < $length; $i++) {
            $pwd .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($pwd);
    }

    /** Reemplazar acentos y ñ */
    private function removeAccents($str) {
        $map = [
            'Á'=>'A','á'=>'a','É'=>'E','é'=>'e','Í'=>'I','í'=>'i',
            'Ó'=>'O','ó'=>'o','Ú'=>'U','ú'=>'u','Ü'=>'U','ü'=>'u',
            'Ñ'=>'N','ñ'=>'n',
        ];
        return strtr($str, $map);
    }
}
?>
