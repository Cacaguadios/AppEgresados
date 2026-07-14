<?php
/**
 * Bootstrap unico de la aplicacion para web, API y CLI.
 */

require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__) . '/app/helpers/ErrorHandler.php';
ErrorHandler::register();
app_validate_environment();
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/app/helpers/Session.php';

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    app_session_start();
}
