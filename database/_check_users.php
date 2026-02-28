<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$rows = $pdo->query("SELECT id, usuario, email, nombre, apellidos, tipo_usuario FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID={$r['id']} | user={$r['usuario']} | email={$r['email']} | {$r['nombre']} {$r['apellidos']} | rol={$r['tipo_usuario']}" . PHP_EOL;
}
echo PHP_EOL . "--- EGRESADOS ---" . PHP_EOL;
$rows2 = $pdo->query("SELECT * FROM egresados")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows2 as $r) {
    echo "ID={$r['id']} | usuario_id={$r['id_usuario']} | mat={$r['matricula']} | curp={$r['curp']} | esp={$r['especialidad']} | gen={$r['generacion']}" . PHP_EOL;
}
