<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$tables = ['usuarios', 'egresados', 'ofertas', 'postulaciones', 'invitaciones'];
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    echo $table . ': ' . $count . PHP_EOL;
}

$demoUsers = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE usuario LIKE 'egresado.demo%'")->fetchColumn();
echo 'egresado.demo*: ' . $demoUsers . PHP_EOL;
