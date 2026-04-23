<?php
/**
 * Router Principal - Bolsa de Trabajo UTP
 * Gestiona todas las rutas de la aplicación
 */

// Cargar variables de entorno desde archivo PHP (para Hostinger u otros hosts sin SetEnv)
$envFile = dirname(__DIR__) . '/config/env.php';
if (file_exists($envFile)) {
    require_once $envFile;
}

// Cargar configuración global (define BASE_URL, ASSETS_URL, etc.)
require_once dirname(__DIR__) . '/config/bootstrap.php';

session_start();

// ============================================
// Configurar ruta base (dominio raiz o subcarpeta)
// ============================================
$appBasePath = defined('BASE_URL') ? BASE_URL : '/AppEgresados';

function appUrl($path = '/') {
    global $appBasePath;

    $path = '/' . ltrim((string) $path, '/');
    if ($path === '//') {
        $path = '/';
    }

    if ($appBasePath === '') {
        return $path;
    }

    if ($path === '/') {
        return $appBasePath . '/';
    }

    return $appBasePath . $path;
}

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
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($appBasePath !== '' && str_starts_with($request, $appBasePath)) {
    $request = substr($request, strlen($appBasePath));
    if ($request === '' || $request === false) {
        $request = '/';
    }
}

$request = rtrim($request, '/');

if (empty($request)) {
    $request = '/';
}

// Compatibilidad general para URLs legacy con .php
if (str_ends_with($request, '.php')) {
    $normalized = preg_replace('/\.php$/', '', $request);

    // Compatibilidad con rutas antiguas /views/*
    if (str_starts_with($normalized, '/views/')) {
        if (preg_match('#^/views/(egresado|docente|admin)/(.+)$#', $normalized, $m)) {
            $normalized = '/' . $m[1] . '/' . $m[2];
        } elseif (preg_match('#^/views/auth/(.+)$#', $normalized, $m)) {
            $authMap = [
                'login' => '/login',
                'logout' => '/logout',
                'forgot' => '/forgot',
                'verify-code' => '/verify-code',
                'reset-password' => '/reset-password',
                'password-updated' => '/password-updated',
                'register-step-1' => '/register-step-1',
                'register-step-2' => '/register-step-2',
                'register-step-3' => '/register-step-3',
                'register-step-4' => '/register-step-4',
                'credentials-success' => '/credentials-success',
            ];
            $normalized = $authMap[$m[1]] ?? '/login';
        } elseif ($normalized === '/views/notificaciones/index') {
            $normalized = '/notificaciones';
        }
    }

    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $target = appUrl($normalized);
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }

    header('Location: ' . $target, true, 301);
    exit;
}

// Compatibilidad con URLs antiguas terminadas en .php (sin /views/auth/)
$legacyAuthMap = [
    '/login.php' => '/login',
    '/register-step-1.php' => '/register-step-1',
    '/register-step-2.php' => '/register-step-2',
    '/register-step-3.php' => '/register-step-3',
    '/register-step-4.php' => '/register-step-4',
    '/credentials-success.php' => '/credentials-success',
    '/forgot.php' => '/forgot',
    '/verify-code.php' => '/verify-code',
    '/reset-password.php' => '/reset-password',
    '/password-updated.php' => '/password-updated',
    '/logout.php' => '/logout',
];
if (isset($legacyAuthMap[$request])) {
    header('Location: ' . appUrl($legacyAuthMap[$request]), true, 301);
    exit;
}

// ============================================
// Rutas públicas (sin login)
// ============================================
$public_routes = [
    '/',
    '/login',
    '/register',
    '/register-step-1',
    '/register-step-2',
    '/register-step-3',
    '/register-step-4',
    '/credentials-success',
    '/forgot',
    '/verify-code',
    '/reset-password',
    '/password-updated',
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
    header('Location: ' . appUrl('/login'));
    exit;
}

// ============================================
// Helper: redirect según rol
// ============================================
function getDashboardUrl($role) {
    return match($role) {
        'admin' => appUrl('/admin/inicio'),
        'docente', 'ti' => appUrl('/docente/inicio'),
        default => appUrl('/egresado/inicio'),
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
        header('Location: ' . appUrl('/login'));
        exit;
        
    case '/login':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/login.php';
        break;

    case '/register':
        header('Location: ' . appUrl('/register-step-1'));
        exit;

    case '/register-step-1':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/register-step-1.php';
        break;

    case '/register-step-2':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/register-step-2.php';
        break;

    case '/register-step-3':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/register-step-3.php';
        break;

    case '/register-step-4':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/register-step-4.php';
        break;

    case '/credentials-success':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/credentials-success.php';
        break;

    case '/forgot':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/forgot.php';
        break;

    case '/verify-code':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/verify-code.php';
        break;

    case '/reset-password':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/reset-password.php';
        break;

    case '/password-updated':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
            exit;
        }
        require __DIR__ . '/../views/auth/password-updated.php';
        break;
        
    case '/logout':
        session_destroy();
        header('Location: ' . appUrl('/'));
        exit;
        
    // ============================================
    // DASHBOARD GENERAL
    // ============================================
    
    case '/dashboard':
        if (!$user_logged) {
            header('Location: ' . appUrl('/login'));
            exit;
        }
        
        // Redirigir según rol
        switch($user_role) {
            case 'egresado':
                header('Location: ' . appUrl('/egresado/dashboard'));
                exit;
            case 'docente':
            case 'ti':
                header('Location: ' . appUrl('/docente/dashboard'));
                exit;
            case 'admin':
                header('Location: ' . appUrl('/admin/dashboard'));
                exit;
            default:
                header('Location: ' . appUrl('/'));
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
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/egresado/perfil':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/perfil.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/egresado/ofertas':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/ofertas.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/publicar-oferta':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/publicar-oferta.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/mis-ofertas':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/mis-ofertas.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/postulantes':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/postulantes.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/oferta-detalle':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/oferta-detalle.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/editar-oferta':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/editar-oferta.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/invitaciones':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/invitaciones.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/egresado/postulaciones':
    case '/egresado/mis-postulaciones':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/postulaciones.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/seguimiento':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/seguimiento.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/egresado/seguridad':
        if ($user_logged && $user_role === 'egresado') {
            require __DIR__ . '/../views/egresado/seguridad.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
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
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/docente/publicar-oferta':
    case '/docente/crear-oferta':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/publicar-oferta.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/docente/mis-ofertas':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/mis-ofertas.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/docente/editar-oferta':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/editar-oferta.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/docente/invitar-egresados':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/invitar-egresados.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/docente/postulantes':
    case '/docente/ver-postulantes':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/postulantes.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/docente/directorio':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/directorio.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/docente/perfil':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/perfil.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/docente/seguridad':
        if ($user_logged && ($user_role === 'docente' || $user_role === 'ti')) {
            require __DIR__ . '/../views/docente/seguridad.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
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
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/admin/moderacion':
    case '/admin/ofertas-pendientes':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/moderacion/list.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/admin/verificacion':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/verificacion/list.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/admin/seguimiento':
    case '/admin/egresados':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/seguimiento/list.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    case '/admin/usuarios':
    case '/admin/estadisticas':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/users.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/admin/seguridad':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/seguridad.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;

    case '/admin/reportes':
        if ($user_logged && $user_role === 'admin') {
            require __DIR__ . '/../views/admin/reportes.php';
        } else {
            header('Location: ' . appUrl('/login')); exit;
        }
        break;
        
    // ============================================
    // RUTAS COMPARTIDAS
    // ============================================
    
    case '/inicio':
        if ($user_logged) {
            header('Location: ' . getDashboardUrl($user_role));
        } else {
            header('Location: ' . appUrl('/login'));
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

    case '/notificaciones':
        if ($user_logged) {
            require __DIR__ . '/../views/notificaciones/index.php';
        } else {
            header('Location: ' . appUrl('/login'));
            exit;
        }
        break;
        
    // ============================================
    // ERROR 404 - Ruta no encontrada
    // ============================================
    
    default:
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1><p><a href="' . htmlspecialchars(appUrl('/login'), ENT_QUOTES, 'UTF-8') . '">Volver al inicio</a></p>';
        break;
}
?>