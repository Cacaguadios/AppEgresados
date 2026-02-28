<?php
/**
 * Helper de seguridad: CSRF, sanitización, password
 */
class Security {
    // Generar token CSRF (solo el string)
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Generar y mostrar campo CSRF completo (input hidden)
    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">';
    }

    // Validar token CSRF
    public static function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Sanitizar input
    public static function sanitize($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // Verificar contraseña
    public static function verifyPassword($input, $hash) {
        return password_verify($input, $hash);
    }
}
