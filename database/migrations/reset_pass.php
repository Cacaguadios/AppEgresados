<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '');

$passwordHash = '$2y$10$V4302t/gbb82vC9Fj6/eEujX9Xo4IA7TjUGTT2zI9x4uuF6qoYKiK';

$stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ? WHERE tipo_usuario IN ('docente', 'ti')");
$stmt->execute([$passwordHash]);

echo "✓ Contraseñas actualizadas para docentes y TI\n";
echo "✓ Nueva contraseña: Test1234!\n";
