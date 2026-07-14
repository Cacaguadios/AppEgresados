<?php

require_once __DIR__ . '/../../config/environment.php';

function app_request_is_https() {
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }

    if (app_env_bool('TRUST_PROXY_HEADERS', false)) {
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    return false;
}

function app_session_cookie_options() {
    $basePath = trim((string) app_env('APP_BASE_PATH', ''), '/');
    $path = $basePath === '' ? '/' : '/' . $basePath . '/';

    return [
        'lifetime' => 0,
        'path' => $path,
        'domain' => '',
        'secure' => app_is_production() || app_request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function app_clear_session_cookie() {
    if (!ini_get('session.use_cookies')) {
        return;
    }

    $options = app_session_cookie_options();
    $options['expires'] = time() - 42000;
    unset($options['lifetime']);
    setcookie(session_name(), '', $options);
}

function app_logout() {
    if (session_status() === PHP_SESSION_NONE) {
        return;
    }

    $_SESSION = [];
    app_clear_session_cookie();
    session_destroy();
    session_id('');
}

function app_session_start() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('APPEGRESADOSSESSID');
    session_set_cookie_params(app_session_cookie_options());
    session_start();

    if (empty($_SESSION['logged_in'])) {
        return;
    }

    $now = time();
    $idleTimeout = max(300, (int) app_env('SESSION_TIMEOUT', '3600'));
    $absoluteTimeout = max($idleTimeout, (int) app_env('SESSION_ABSOLUTE_TIMEOUT', '43200'));
    $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);
    $createdAt = (int) ($_SESSION['session_created_at'] ?? $now);

    if (($now - $lastActivity) > $idleTimeout || ($now - $createdAt) > $absoluteTimeout) {
        app_logout();
        session_start();
        $_SESSION['session_expired'] = true;
        return;
    }

    $_SESSION['last_activity'] = $now;
    $_SESSION['session_created_at'] = $createdAt;
}
