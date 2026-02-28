<?php
/**
 * Router Principal - Bolsa de Trabajo UTP
 * Gestiona todas las rutas de la aplicación
 */

session_start();

// ============================================
// Variables de sesión
// ============================================
$user_logged = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_id = $_SESSION['usuario_id'] ?? null;
$user_role = $_SESSION['usuario_rol'] ?? null;
$user_name = $_SESSION['usuario_nombre'] ?? null;

// ============================================
// Obtener ruta solicitada
// ============================================
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = str_replace('/AppEgresados', '', $request);
$request = rtrim($request, '/');

if (empty($request)) {
    $request = '/';
}

// ============================================
// Rutas públicas (sin login)
// ============================================
$public_routes = [
    '/',
    '/login',
    '/register',
    '/inicio',
    '/acerca',
    '/contacto'
];

// ============================================
// Verificar acceso a rutas protegidas
// ============================================
if (!$user_logged && !in_array($request, $public_routes) && 
    !str_contains($request, '/login') && 
    !str_contains($request, '/register') &&
    !str_contains($request, '/forgot') &&
    !str_contains($request, '/verify-code') &&
    !str_contains($request, '/reset-password') &&
    !str_contains($request, '/password-updated')) {
    header('Location: /AppEgresados/login');
    exit;
}

// ============================================
// Helper: redirect según rol
// ============================================
function getDashboardUrl($role) {
    return match($role) {
        'admin' => '/AppEgresados/views/admin/inicio.php',
        'docente', 'ti' => '/AppEgresados/views/docente/inicio.php',
        default => '/AppEgresados/views/egresado/inicio.php',
    };
}

// ============================================
// RUTEO PRINCIPAL
// ============================================

switch ($request) {
    
    // ============================================
    // RUTAS PÚBLICAS
    // ============================================
    
    case '/':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        header('Location: /AppEgresados/login');
        exit;
        
    case '/login':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/login.php';
        break;
        
    case '/logout':
        session_destroy();
        header('Location: /AppEgresados/');
        exit;
        
    // ============================================
    // DASHBOARD GENERAL
    // ============================================
    
    case '/dashboard':
        if (!$user_logged) {
            header('Location: /AppEgresados/login');
            exit;
        }
        
        // Redirigir según rol
        switch($user_role) {
            case 'egresado':
                header('Location: /AppEgresados/egresado/dashboard');
                exit;
            case 'docente':
            case 'ti':
                header('Location: /AppEgresados/docente/dashboard');
                exit;
            case 'admin':
                header('Location: /AppEgresados/admin/dashboard');
                exit;
            default:
                header('Location: /AppEgresados/');
                exit;
        }
        break;
        
    // ============================================
    // RUTAS EGRESADO
    // ============================================
    
    case '/egresado/dashboard':
    case '/egresado/inicio':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/inicio.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/egresado/perfil':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/perfil.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/egresado/ofertas':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/ofertas.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/egresado/postulaciones':
    case '/egresado/mis-postulaciones':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/postulaciones.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/egresado/seguimiento':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/seguimiento.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/egresado/seguridad':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/seguridad.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    // ============================================
    // RUTAS DOCENTE/TI
    // ============================================
    
    case '/docente/dashboard':
    case '/docente/inicio':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/inicio.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/docente/publicar-oferta':
    case '/docente/crear-oferta':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/publicar-oferta.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/docente/mis-ofertas':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/mis-ofertas.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/docente/postulantes':
    case '/docente/ver-postulantes':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/postulantes.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/docente/directorio':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/directorio.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/docente/perfil':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/perfil.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/docente/seguridad':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/seguridad.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    // ============================================
    // RUTAS ADMIN
    // ============================================
    
    case '/admin/dashboard':
    case '/admin/inicio':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/inicio.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/admin/moderacion':
    case '/admin/ofertas-pendientes':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/moderacion/list.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/admin/verificacion':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/verificacion/list.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/admin/seguimiento':
    case '/admin/egresados':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/seguimiento/list.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    case '/admin/usuarios':
    case '/admin/estadisticas':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/users.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;

    case '/admin/seguridad':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/seguridad.php';
        } else {
            header('Location: /AppEgresados/login'); exit;
        }
        break;
        
    // ============================================
    // RUTAS COMPARTIDAS
    // ============================================
    
    case '/inicio':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
        } else {
            header('Location: /AppEgresados/login');
        }
        exit;
        
    // ============================================
    // API DE NOTIFICACIONES
    // ============================================

    case '/api/notificaciones':
        if ($user_logged) {
            require __DIR__ . '/../app/controllers/NotificacionController.php';
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
        }
        break;
        
    // ============================================
    // ERROR 404 - Ruta no encontrada
    // ============================================
    
    default:
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1><p><a href="/AppEgresados/login">Volver al inicio</a></p>';
        break;
}
?>