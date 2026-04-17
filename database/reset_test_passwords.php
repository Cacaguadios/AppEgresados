<?php
/**
 * Reset all test user passwords to fixed values
 */
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "=== RESETTING TEST PASSWORDS ===" . PHP_EOL;

$usuarios = [
    'test.egresado'  => 'Test1234!',
    'juan.perez'     => 'Juan1234!',
    'maria.lopez'    => 'Maria1234!',
    'admin'          => 'Admin1234!',
    'carlos.anzurez' => 'Carlos1234!',
];

foreach ($usuarios as $user => $pass) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE usuarios SET contraseña = ? WHERE usuario = ?');
    $stmt->execute([$hash, $user]);
    echo "✓ $user / $pass" . PHP_EOL;
}

echo PHP_EOL . "=== TESTING CREDENTIALS ===" . PHP_EOL;
foreach ($usuarios as $user => $pass) {
    echo "$user | $pass" . PHP_EOL;
}
