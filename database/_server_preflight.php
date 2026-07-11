<?php
/**
 * Auditoria rapida para despliegue en Ubuntu Server 20.04.
 * Ejecutar desde la raiz del proyecto:
 *   php database/_server_preflight.php
 */

$projectRoot = dirname(__DIR__);

$checks = [];

function add_check(&$checks, $name, $ok, $detail) {
    $checks[] = [
        'name' => $name,
        'ok' => (bool) $ok,
        'detail' => $detail,
    ];
}

function check_extension($name) {
    return extension_loaded($name);
}

function check_writable_dir($path) {
    if (!is_dir($path)) {
        return @mkdir($path, 0775, true);
    }
    return is_writable($path);
}

add_check(
    $checks,
    'PHP >= 7.4',
    version_compare(PHP_VERSION, '7.4.0', '>='),
    'Version detectada: ' . PHP_VERSION
);

$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json'];
foreach ($requiredExtensions as $ext) {
    add_check(
        $checks,
        'Extension ' . $ext,
        check_extension($ext),
        check_extension($ext) ? 'OK' : 'Falta instalar: php-' . $ext
    );
}

$directories = [
    $projectRoot . '/storage',
    $projectRoot . '/storage/logs',
    $projectRoot . '/storage/cache',
    $projectRoot . '/public/assets/uploads',
];

foreach ($directories as $dir) {
    $ok = check_writable_dir($dir);
    add_check(
        $checks,
        'Directorio escribible: ' . str_replace('\\', '/', substr($dir, strlen($projectRoot) + 1)),
        $ok,
        $ok ? 'OK' : 'Sin permisos de escritura para el usuario de PHP/Apache'
    );
}

$autoloadFile = $projectRoot . '/vendor/autoload.php';
add_check(
    $checks,
    'Dependencias Composer',
    file_exists($autoloadFile),
    file_exists($autoloadFile) ? 'OK' : 'Ejecuta: composer install --no-dev --classmap-authoritative'
);

$configEnv = $projectRoot . '/config/env.php';
add_check(
    $checks,
    'Archivo config/env.php',
    file_exists($configEnv),
    file_exists($configEnv)
        ? 'OK (verifica credenciales reales en servidor)'
        : 'Crea config/env.php a partir de config/env.example.php'
);

$hasErrors = false;
echo "\n=== PRE-FLIGHT UBUNTU 20.04 (AppEgresados) ===\n\n";

foreach ($checks as $check) {
    $status = $check['ok'] ? '[OK]' : '[FAIL]';
    echo $status . ' ' . $check['name'] . "\n";
    echo '       ' . $check['detail'] . "\n";
    if (!$check['ok']) {
        $hasErrors = true;
    }
}

echo "\nResultado final: " . ($hasErrors ? 'CON ERRORES' : 'LISTO PARA DESPLIEGUE') . "\n\n";
exit($hasErrors ? 1 : 0);
