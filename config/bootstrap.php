<?php
/**
 * Configuración global de Bootstrap y estilos
 * Incluir al inicio de cada página
 */

$envFile = __DIR__ . '/env.php';
if (file_exists($envFile)) {
    require_once $envFile;
}

$appBasePath = getenv('APP_BASE_PATH');
$appBasePath = $appBasePath === false ? '/' : trim($appBasePath);

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        if ($needle === '') {
            return true;
        }
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if ($appBasePath === '' || $appBasePath === '/') {
    $appBasePath = '';
} else {
    $appBasePath = '/' . trim($appBasePath, '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $appBasePath);
    define('ASSETS_URL', $appBasePath . '/public/assets');
}

if (!function_exists('app_url')) {
    function app_url(string $path = '/'): string {
        $base = defined('BASE_URL') ? BASE_URL : '';
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string {
        return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dashboard_url')) {
    function dashboard_url(?string $role): string {
        switch ($role) {
            case 'admin':
                return app_url('/admin/inicio');
            case 'docente':
            case 'ti':
                return app_url('/docente/inicio');
            default:
                return app_url('/egresado/inicio');
        }
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
    $homeUrl = e(app_url('/'));
    $loginUrl = e(app_url('/login'));

    return <<<HTML
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--utp-red);">
        <div class="container-fluid">
            <a class="navbar-brand" href="{$homeUrl}">
                <i class="bi bi-mortarboard-fill"></i> $title
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{$homeUrl}">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{$loginUrl}">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    HTML;
}
