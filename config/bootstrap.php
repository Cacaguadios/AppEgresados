<?php
/**
 * Configuración global de Bootstrap y estilos
 * Incluir al inicio de cada página
 */

$appBasePath = getenv('APP_BASE_PATH') ?: '/AppEgresados';
$appBasePath = trim($appBasePath);

if ($appBasePath === '' || $appBasePath === '/') {
    $appBasePath = '';
} else {
    $appBasePath = '/' . trim($appBasePath, '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $appBasePath);
    define('ASSETS_URL', $appBasePath . '/public/assets');
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
    return <<<HTML
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--utp-red);">
        <div class="container-fluid">
            <a class="navbar-brand" href="{BASE_URL}/">
                <i class="bi bi-mortarboard-fill"></i> $title
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{BASE_URL}/">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{BASE_URL}/views/auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    HTML;
}
