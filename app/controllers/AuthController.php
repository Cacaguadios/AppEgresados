<?php
/**
 * Controlador de Autenticación
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Egresado.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';

class AuthController {
    private $usuarioModel;
    private const DUMMY_PASSWORD_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    
    public function __construct() {
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * PROCESAR LOGIN
     * Acepta usuario (username) O correo electrónico como identificador.
     */
    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        // Obtener datos del formulario
        $identifier = Security::sanitize($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        // Validar CSRF token
        if (!Security::validateCsrfToken($csrf_token)) {
            $_SESSION['error'] = '⚠️ Token de seguridad inválido. Intenta de nuevo.';
            return false;
        }
        
        // Validaciones básicas
        if (empty($identifier)) {
            $_SESSION['error'] = '❌ El usuario o correo es requerido';
            return false;
        }
        
        if (empty($password)) {
            $_SESSION['error'] = '❌ La contraseña es requerida';
            return false;
        }

        $identityKey = strtolower(trim((string) $identifier)) . '|' . Security::clientIp();
        $ipKey = Security::clientIp();
        if (RateLimiter::tooManyAttempts('login_identity', $identityKey, 5, 900)
            || RateLimiter::tooManyAttempts('login_ip', $ipKey, 20, 900)) {
            http_response_code(429);
            $_SESSION['error'] = 'Demasiados intentos. Espera 15 minutos antes de volver a intentar.';
            return false;
        }
        
        // Buscar usuario: primero por email, luego por username
        $usuario = null;
        
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $usuario = $this->usuarioModel->getByEmail($identifier);
        }
        
        if (!$usuario) {
            $usuario = $this->usuarioModel->getByUsuario($identifier);
        }
        
        // Si todavía no se encontró, intentar la otra vía
        if (!$usuario && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $usuario = $this->usuarioModel->getByEmail($identifier);
        }
        
        if (!$usuario) {
            password_verify($password, self::DUMMY_PASSWORD_HASH);
            return $this->invalidCredentials($identityKey, $ipKey);
        }
        
        // Verificar contraseña
        if (!Security::verifyPassword($password, $usuario['contraseña'])) {
            return $this->invalidCredentials($identityKey, $ipKey);
        }
        
        // Verificar si la cuenta está activa
        if (!$usuario['activo']) {
            return $this->invalidCredentials($identityKey, $ipKey);
        }

        $rol = $usuario['tipo_usuario'] ?? '';
        
        // Actualizar último login
        $this->usuarioModel->updateLastLogin($usuario['id']);
        
        // Evitar fijacion de sesion al elevar una sesion anonima a autenticada.
        session_regenerate_id(true);
        RateLimiter::clear('login_identity', $identityKey);

        // Crear variables de sesión
        $_SESSION['usuario_id']       = $usuario['id'];
        $_SESSION['usuario_email']    = $usuario['email'];
        $_SESSION['usuario_nombre']   = $usuario['nombre'];
        $_SESSION['usuario_apellidos'] = $usuario['apellidos'] ?? '';
        $_SESSION['usuario_usuario']  = $usuario['usuario'] ?? '';
        $_SESSION['usuario_rol']      = $rol;
        $_SESSION['usuario_verificacion_estado'] = $usuario['verificacion_estado'] ?? 'pendiente';
        $_SESSION['logged_in']        = true;
        $_SESSION['requiere_cambio_pass'] = !empty($usuario['requiere_cambio_pass']);
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_created_at'] = time();

        if ($rol === 'egresado') {
            try {
                $egresadoModel = new Egresado();
                $perfilEgresado = $egresadoModel->getByUsuarioId($usuario['id']);
                $_SESSION['usuario_telefono'] = $perfilEgresado['telefono'] ?? '';
            } catch (\Throwable $e) {
                $_SESSION['usuario_telefono'] = '';
            }
        }
        
        $_SESSION['success'] = '✅ ¡Bienvenido ' . $usuario['nombre'] . '!';
        
        return true;
    }

    private function invalidCredentials($identityKey, $ipKey) {
        RateLimiter::hit('login_identity', $identityKey, 900);
        RateLimiter::hit('login_ip', $ipKey, 900);
        $_SESSION['error'] = 'Usuario, correo o contraseña incorrectos.';
        return false;
    }
}
?>
