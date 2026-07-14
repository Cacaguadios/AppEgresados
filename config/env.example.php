<?php
/**
 * Configuracion exclusiva para desarrollo local.
 * Copia este archivo como config/env.php y cambia los valores locales.
 * Produccion ignora config/env.php y usa variables del proceso de Apache.
 */

if (!function_exists('set_env_if_missing')) {
    function set_env_if_missing($key, $value) {
        $current = getenv($key);
        if ($current !== false && $current !== '') {
            return;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

set_env_if_missing('APP_NAME', 'Bolsa de Trabajo UTP');
set_env_if_missing('APP_ENV', 'development');
set_env_if_missing('APP_DEBUG', 'true');
set_env_if_missing('APP_URL', 'http://localhost/AppEgresados');
set_env_if_missing('APP_BASE_PATH', '/AppEgresados');
set_env_if_missing('APP_KEY', 'replace-with-a-random-local-value-of-32-characters');

set_env_if_missing('APP_DB_HOST', '127.0.0.1');
set_env_if_missing('APP_DB_PORT', '3306');
set_env_if_missing('APP_DB_NAME', 'bolsa_trabajo_utp');
set_env_if_missing('APP_DB_USER', 'appegresados_local');
set_env_if_missing('APP_DB_PASS', 'change-this-local-password');

set_env_if_missing('MAIL_DRIVER', 'log');
set_env_if_missing('MAIL_HOST', '');
set_env_if_missing('MAIL_PORT', '587');
set_env_if_missing('MAIL_ENCRYPTION', 'tls');
set_env_if_missing('MAIL_USER', '');
set_env_if_missing('MAIL_PASS', '');
set_env_if_missing('MAIL_FROM', 'no-reply@example.test');
set_env_if_missing('MAIL_FROM_NAME', 'Bolsa de Trabajo UTP');
set_env_if_missing('MAIL_ALLOW_SELF_SIGNED', 'false');
