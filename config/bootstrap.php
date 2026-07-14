<?php
/**
 * Configuración global de Bootstrap y estilos
 * Incluir al inicio de cada página
 */

require_once __DIR__ . '/environment.php';

$appBasePath = app_env('APP_BASE_PATH', '/AppEgresados');
$appBasePath = trim($appBasePath);

if ($appBasePath === '' || $appBasePath === '/') {
    $appBasePath = '';
} else {
    $appBasePath = '/' . trim($appBasePath, '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $appBasePath);
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', $appBasePath . '/assets');
}
if (!defined('API_URL')) {
    define('API_URL', $appBasePath . '/api');
}

if (!function_exists('appUrl')) {
    function appUrl($path = '/') {
        $path = '/' . ltrim((string) $path, '/');

        if (BASE_URL === '') {
            return $path;
        }

        if ($path === '/') {
            return BASE_URL . '/';
        }

        return BASE_URL . $path;
    }
}

if (!function_exists('getDashboardUrl')) {
    function getDashboardUrl($role) {
        switch ($role) {
            case 'admin':
                return appUrl('/admin/inicio');
            case 'docente':
            case 'ti':
                return appUrl('/docente/inicio');
            default:
                return appUrl('/egresado/inicio');
        }
    }
}

// Polyfills para las funciones de cadenas incorporadas en PHP 8.
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        if ($needle === '') {
            return true;
        }
        return strpos((string) $haystack, (string) $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        if ($needle === '') {
            return true;
        }
        $needleLength = strlen($needle);
        if ($needleLength > strlen($haystack)) {
            return false;
        }
        return substr($haystack, -$needleLength) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        if ($needle === '') {
            return true;
        }
        return strpos((string) $haystack, (string) $needle) !== false;
    }
}

// URLs de CDN Bootstrap
define('BOOTSTRAP_CSS', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
define('BOOTSTRAP_ICONS', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css');
define('BOOTSTRAP_JS', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js');

// URLs locales de estilos
define('AUTH_CSS', ASSETS_URL . '/css/auth.css');
define('APP_JS', ASSETS_URL . '/js/app.js');

// Función para incluir CSS
function include_css($url) {
    echo '<link href="' . htmlspecialchars($url) . '" rel="stylesheet">' . PHP_EOL;
}

// Función para incluir JS
function include_js($url) {
    echo '<script src="' . htmlspecialchars($url) . '"></script>' . PHP_EOL;
}

// Función para crear Nav Bar de Bootstrap
function render_navbar($title = 'AppEgresados') {
    $homeUrl = appUrl('/');
    $loginUrl = appUrl('/login');

    return <<<HTML
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--utp-red);">
        <div class="container-fluid">
            <a class="navbar-brand" href="$homeUrl">
                <i class="bi bi-mortarboard-fill"></i> $title
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="$homeUrl">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="$loginUrl">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    HTML;
}
