<?php
/**
 * Entorno y validacion de configuracion.
 *
 * En produccion, todos los valores deben llegar desde el entorno del proceso.
 * config/env.php se conserva unicamente para desarrollo local y nunca se carga
 * cuando APP_ENV=production.
 */

if (!function_exists('app_env')) {
    function app_env($key, $default = null) {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }
}

if (!function_exists('app_env_bool')) {
    function app_env_bool($key, $default = false) {
        $value = app_env($key, $default ? 'true' : 'false');
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('app_is_production')) {
    function app_is_production() {
        return strtolower(trim((string) app_env('APP_ENV', 'development'))) === 'production';
    }
}

$externalAppEnvironment = strtolower(trim((string) app_env('APP_ENV', 'development')));
$localEnvironmentFile = __DIR__ . '/env.php';

if ($externalAppEnvironment !== 'production' && file_exists($localEnvironmentFile)) {
    require_once $localEnvironmentFile;
}

if (!function_exists('app_configuration_issues')) {
    function app_configuration_issues() {
        if (!app_is_production()) {
            return [];
        }

        $issues = [];
        $required = [
            'APP_URL',
            'APP_KEY',
            'APP_DB_HOST',
            'APP_DB_PORT',
            'APP_DB_NAME',
            'APP_DB_USER',
            'APP_DB_PASS',
            'MAIL_DRIVER',
        ];

        foreach ($required as $key) {
            if (trim((string) app_env($key, '')) === '') {
                $issues[] = $key . ' es obligatorio en produccion';
            }
        }

        $placeholderValues = [
            'change-me',
            'change-this-password',
            'change-this-local-password',
            'password',
            'secret',
            'replace-me',
            'contraseña_app',
        ];

        foreach (['APP_KEY', 'APP_DB_PASS', 'MAIL_PASS'] as $key) {
            $value = strtolower(trim((string) app_env($key, '')));
            if ($value !== '' && in_array($value, $placeholderValues, true)) {
                $issues[] = $key . ' contiene un valor de ejemplo inseguro';
            }
        }

        $appUrl = strtolower(trim((string) app_env('APP_URL', '')));
        if ($appUrl !== '' && strpos($appUrl, 'https://') !== 0) {
            $issues[] = 'APP_URL debe usar HTTPS en produccion';
        }

        if (app_env_bool('APP_DEBUG', false)) {
            $issues[] = 'APP_DEBUG debe estar desactivado en produccion';
        }

        $appKey = (string) app_env('APP_KEY', '');
        if ($appKey !== '' && strlen($appKey) < 32) {
            $issues[] = 'APP_KEY debe contener al menos 32 caracteres';
        }

        $dbPass = (string) app_env('APP_DB_PASS', '');
        if ($dbPass !== '' && strlen($dbPass) < 12) {
            $issues[] = 'APP_DB_PASS debe contener al menos 12 caracteres';
        }

        $dbUser = strtolower(trim((string) app_env('APP_DB_USER', '')));
        if ($dbUser === 'root') {
            $issues[] = 'APP_DB_USER no puede ser root en produccion';
        }

        $mailDriver = strtolower(trim((string) app_env('MAIL_DRIVER', '')));
        if ($mailDriver === 'log') {
            $issues[] = 'MAIL_DRIVER=log no esta permitido en produccion';
        } elseif ($mailDriver === 'smtp') {
            foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USER', 'MAIL_PASS', 'MAIL_FROM'] as $key) {
                if (trim((string) app_env($key, '')) === '') {
                    $issues[] = $key . ' es obligatorio cuando MAIL_DRIVER=smtp';
                }
            }

            if (app_env_bool('MAIL_ALLOW_SELF_SIGNED', false)) {
                $issues[] = 'MAIL_ALLOW_SELF_SIGNED no esta permitido en produccion';
            }
        } elseif ($mailDriver !== '') {
            $issues[] = 'MAIL_DRIVER debe ser smtp en produccion';
        }

        return array_values(array_unique($issues));
    }
}

if (!function_exists('app_validate_environment')) {
    function app_validate_environment() {
        $issues = app_configuration_issues();
        if ($issues) {
            throw new RuntimeException(
                'Configuracion de produccion invalida: ' . implode('; ', $issues)
            );
        }
    }
}

app_validate_environment();
