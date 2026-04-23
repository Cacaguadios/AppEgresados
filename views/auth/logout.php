<?php
/**
 * Logout – Cierra la sesión y redirige al login
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = '/AppEgresados';

// Limpiar todas las variables de sesión
$_SESSION = [];

// Destruir la cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: ' . $baseUrl . '/login');
exit;
