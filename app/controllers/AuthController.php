<?php
/**
 * Controlador de Autenticación
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Security.php';

class AuthController {
    private $usuarioModel;
    
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
            $_SESSION['error'] = '❌ Usuario no encontrado';
            return false;
        }
        
        // Verificar contraseña
        if (!Security::verifyPassword($password, $usuario['contraseña'])) {
            $_SESSION['error'] = '❌ Contraseña incorrecta';
            return false;
        }
        
        // Verificar si la cuenta está activa
        if (!$usuario['activo']) {
            $_SESSION['error'] = '❌ Tu cuenta ha sido desactivada';
            return false;
        }
        
        // Actualizar último login
        $this->usuarioModel->updateLastLogin($usuario['id']);
        
        // Crear variables de sesión
        $_SESSION['usuario_id']       = $usuario['id'];
        $_SESSION['usuario_email']    = $usuario['email'];
        $_SESSION['usuario_nombre']   = $usuario['nombre'];
        $_SESSION['usuario_apellidos'] = $usuario['apellidos'] ?? '';
        $_SESSION['usuario_usuario']  = $usuario['usuario'] ?? '';
        $_SESSION['usuario_rol']      = $usuario['tipo_usuario'];
        $_SESSION['logged_in']        = true;
        $_SESSION['requiere_cambio_pass'] = !empty($usuario['requiere_cambio_pass']);
        
        $_SESSION['success'] = '✅ ¡Bienvenido ' . $usuario['nombre'] . '!';
        
        return true;
    }
}
?>