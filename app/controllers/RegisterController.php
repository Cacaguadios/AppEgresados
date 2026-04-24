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
     *  PASO 2 – Validar datos de verificación por rol
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

    /* ── Egresado: correo personal, CURP y teléfono ── */
    private function validateEgresado($data) {
        $curp = strtoupper(trim($data['curp'] ?? ''));
        $telefono = preg_replace('/\D+/', '', (string)($data['telefono'] ?? ''));
        $email = strtolower(trim($data['email'] ?? ''));
        $errors = [];

        // CURP: 18 caracteres
        if (empty($curp)) {
            $errors[] = 'El CURP es requerido.';
        } elseif (!preg_match('/^[A-Z]{4}\d{6}[MH][A-Z]{5}[0-9A-Z]\d$/', $curp)) {
            $errors[] = 'El CURP debe tener 18 caracteres válidos.';
        } elseif ($this->usuarioModel->curpExists($curp)) {
            $errors[] = 'Este CURP ya está registrado.';
        }

        // Teléfono: 10 dígitos
        if (empty($telefono)) {
            $errors[] = 'El teléfono de contacto es requerido.';
        } elseif (!preg_match('/^\d{10}$/', $telefono)) {
            $errors[] = 'El teléfono debe tener exactamente 10 dígitos.';
        }

        // Email
        if (empty($email)) {
            $errors[] = 'El correo electrónico es requerido.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo electrónico no es válido.';
        } elseif ($this->usuarioModel->emailExists($email)) {
            $errors[] = 'Este correo electrónico ya fue registrado.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        return [
            'success' => true,
            'data'    => ['email' => $email, 'curp' => $curp, 'telefono' => $telefono]
        ];
    }

    /* ── Docente: correo institucional ── */
    private function validateDocente($data) {
        $email = strtolower(trim($data['email_docente'] ?? ''));
        $errors = [];

        if (empty($email)) {
            $errors[] = 'El correo institucional de docente es requerido.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo institucional no es válido.';
        } elseif (!str_ends_with($email, '@utpuebla.edu.mx') && !str_ends_with($email, '@utp.edu.mx')) {
            $errors[] = 'El correo docente debe terminar en @utpuebla.edu.mx o @utp.edu.mx.';
        }

        if (!empty($email) && $this->usuarioModel->emailExists($email)) {
            $errors[] = 'Este correo ya está registrado en el sistema.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        return ['success' => true, 'data' => ['email_institucional' => $email]];
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
        
        // Priorizar email validado en Step 2 (egresado/docente)
        if (!empty($verificacionData['email'])) {
            $email = strtolower(trim($verificacionData['email']));
        } elseif (!empty($verificacionData['email_institucional'])) {
            $email = strtolower(trim($verificacionData['email_institucional']));
        } else {
            // Si no se proporcionó email verificable, generar uno técnico
            $email = $usuario . '@egresados.utp.edu.mx';

            // Verificar que no exista duplicado de email
            if ($this->usuarioModel->emailExists($email)) {
                $suffix = rand(10, 999);
                $usuario = $usuario . $suffix;
                $email   = $usuario . '@egresados.utp.edu.mx';
            }
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

            if ($role === 'egresado') {
                $curp = $verificacionData['curp'] ?? null;
                $correoPersonal = $verificacionData['email'] ?? null;
                $telefono = $verificacionData['telefono'] ?? null;
                $this->usuarioModel->createEgresado($idUsuario, null, $curp, $correoPersonal, $telefono);
            }
        } catch (\Exception $e) {
            if (!empty($idUsuario)) {
                try {
                    $this->usuarioModel->delete('usuarios', ['id' => $idUsuario]);
                } catch (\Exception $ignored) {
                }
            }
            return [
                'success' => false,
                'message' => 'Error al crear el usuario. Intenta de nuevo.'
            ];
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
