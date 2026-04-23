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

    /**
     * Limpiar valor para almacenamiento interno (sin HTML).
     */
    public static function sanitizeForStorage($value) {
        if (!is_string($value)) {
            return $value;
        }

        $clean = trim($value);
        $clean = strip_tags($clean);
        // Eliminar caracteres de control no imprimibles.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);

        if (self::containsSqlInjectionPattern($clean)) {
            throw new InvalidArgumentException('Entrada rechazada por patron potencial de inyeccion SQL.');
        }

        return $clean;
    }

    /**
     * Sanitizar recursivamente arrays/objetos de entrada.
     */
    public static function sanitizeRecursive($input) {
        if (is_array($input)) {
            $clean = [];
            foreach ($input as $k => $v) {
                $clean[$k] = self::sanitizeRecursive($v);
            }
            return $clean;
        }

        return self::sanitizeForStorage($input);
    }

    /**
     * Detectar patrones comunes de inyeccion SQL.
     */
    public static function containsSqlInjectionPattern($value) {
        if (!is_string($value)) {
            return false;
        }

        $v = strtolower(trim($value));
        if ($v === '') {
            return false;
        }

        $patterns = [
            '/\bunion\s+select\b/i',
            '/\bdrop\s+table\b/i',
            '/\binsert\s+into\b/i',
            '/\bdelete\s+from\b/i',
            '/\bupdate\s+\w+\s+set\b/i',
            '/\bor\s+1\s*=\s*1\b/i',
            '/\band\s+1\s*=\s*1\b/i',
            '/--/',
            '/\/\*/',
            '/\*\//',
            '/;\s*(drop|delete|update|insert|alter|truncate|create)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $v)) {
                return true;
            }
        }

        return false;
    }

    // Verificar contraseña
    public static function verifyPassword($input, $hash) {
        return password_verify($input, $hash);
    }
}
