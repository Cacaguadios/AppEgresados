<?php

require_once __DIR__ . '/../../config/application.php';
require_once __DIR__ . '/Security.php';

function api_json_input() {
    if (array_key_exists('app_api_json_input', $GLOBALS)) {
        return $GLOBALS['app_api_json_input'];
    }

    $raw = file_get_contents('php://input');
    $decoded = $raw !== '' ? json_decode($raw, true) : [];
    $GLOBALS['app_api_json_input'] = is_array($decoded) ? $decoded : [];
    return $GLOBALS['app_api_json_input'];
}

function api_error($status, $code, $message) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'code' => (string) $code,
        'error' => (string) $message,
        'request_id' => class_exists('ErrorHandler') ? ErrorHandler::requestId() : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_csrf_token_from_request() {
    $input = api_json_input();
    return $_POST['csrf_token']
        ?? $input['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';
}

function api_bootstrap($entryFile) {
    header('Content-Type: application/json; charset=utf-8');

    $policies = require __DIR__ . '/../../config/api_routes.php';
    $endpoint = basename($entryFile);
    if (!isset($policies[$endpoint])) {
        api_error(500, 'route_policy_missing', 'La ruta no tiene una politica de acceso configurada.');
    }

    $policy = $policies[$endpoint];
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $allowedMethods = $policy['methods'] ?? ['GET'];

    if (!in_array($method, $allowedMethods, true)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        api_error(405, 'method_not_allowed', 'Metodo no permitido.');
    }

    if (empty($_SESSION['logged_in']) || empty($_SESSION['usuario_id'])) {
        api_error(401, 'authentication_required', 'Autenticacion requerida.');
    }

    $role = (string) ($_SESSION['usuario_rol'] ?? '');
    $allowedRoles = $policy['roles'] ?? [];
    if ($allowedRoles && !in_array($role, $allowedRoles, true)) {
        api_error(403, 'role_forbidden', 'El rol actual no tiene acceso a esta ruta.');
    }

    $requiresCsrf = !empty($policy['csrf']) && !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    if ($requiresCsrf && !Security::validateCsrfToken(api_csrf_token_from_request())) {
        api_error(419, 'csrf_invalid', 'Token CSRF invalido.');
    }

    return $policy;
}
