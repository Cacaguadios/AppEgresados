<?php

$env = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_BASE_PATH' => '/bttiutp',
    'APP_URL' => 'https://ti.utpuebla.edu.mx/bttiutp',

    'APP_DB_HOST' => 'localhost',
    'APP_DB_PORT' => '3306',
    'APP_DB_NAME' => 'bttiutp',
    'APP_DB_USER' => 'bttiutp',
    'APP_DB_PASS' => 'CAMBIAR_EN_SERVIDOR',

    'MAIL_DRIVER' => 'log',
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => '587',
    'MAIL_ENCRYPTION' => 'tls',
    'MAIL_USER' => 'CAMBIAR_EN_SERVIDOR',
    'MAIL_PASS' => 'CAMBIAR_EN_SERVIDOR',
    'MAIL_FROM' => 'CAMBIAR_EN_SERVIDOR',
    'MAIL_FROM_NAME' => 'Bolsa de Trabajo UTP',
];

foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv($key . '=' . $value);
}
