<?php
/**
 * Controlador de Cambio de Contraseña
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Security.php';

class PasswordController {

    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Procesar cambio de contraseña.
     * Requiere sesión activa. Retorna true si se cambió correctamente.
     */
    public function changePassword(): bool {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $csrf            = $_POST['csrf_token']       ?? '';
        $currentPassword = $_POST['current_password']  ?? '';
        $newPassword     = $_POST['new_password']      ?? '';
        $confirmPassword = $_POST['confirm_password']  ?? '';

        // CSRF
        if (!Security::validateCsrfToken($csrf)) {
            $_SESSION['pwd_error'] = '⚠️ Token de seguridad inválido. Intenta de nuevo.';
            return false;
        }

        // Campos vacíos
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['pwd_error'] = '❌ Todos los campos son obligatorios.';
            return false;
        }

        // Confirmación
        if ($newPassword !== $confirmPassword) {
            $_SESSION['pwd_error'] = '❌ Las contraseñas no coinciden.';
            return false;
        }

        // Fortaleza mínima
        if (!$this->validarFortaleza($newPassword)) {
            $_SESSION['pwd_error'] = '❌ La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.';
            return false;
        }

        // No se puede reutilizar la misma contraseña
        $userId  = $_SESSION['usuario_id'] ?? null;
        if (!$userId) {
            $_SESSION['pwd_error'] = '❌ Sesión inválida.';
            return false;
        }

        $usuario = $this->usuarioModel->getById($userId);
        if (!$usuario) {
            $_SESSION['pwd_error'] = '❌ Usuario no encontrado.';
            return false;
        }

        // Verificar contraseña actual
        if (!Security::verifyPassword($currentPassword, $usuario['contraseña'])) {
            $_SESSION['pwd_error'] = '❌ La contraseña actual es incorrecta.';
            return false;
        }

        // Evitar reutilizar la misma contraseña
        if (Security::verifyPassword($newPassword, $usuario['contraseña'])) {
            $_SESSION['pwd_error'] = '❌ La nueva contraseña no puede ser igual a la actual.';
            return false;
        }

        // Actualizar contraseña
        $this->usuarioModel->updatePassword($userId, $newPassword);

        // Quitar flag de cambio obligatorio
        $_SESSION['requiere_cambio_pass'] = false;

        $_SESSION['pwd_success'] = '✅ ¡Contraseña actualizada correctamente!';
        return true;
    }

    /**
     * Valida que la contraseña cumpla los requisitos mínimos.
     */
    private function validarFortaleza(string $password): bool {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
        return true;
    }
}
