<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '');

// Hash de "Test1234!"
$passwordHash = password_hash('Test1234!', PASSWORD_BCRYPT);

// Actualizar docentes y TI
$pdo->exec("UPDATE usuarios SET contraseña = '$passwordHash' WHERE tipo_usuario IN ('docente', 'ti')");

echo "Contraseñas actualizadas para docentes y TI\n";
echo "Nueva contraseña: Test1234!\n";
