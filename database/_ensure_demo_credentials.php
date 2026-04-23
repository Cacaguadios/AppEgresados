<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$egresadoPass = 'Egresado123!';
$docentePass = 'Docente123!';
$egresadoHash = password_hash($egresadoPass, PASSWORD_BCRYPT);
$docenteHash = password_hash($docentePass, PASSWORD_BCRYPT);

$createUser = $pdo->prepare(
    "INSERT INTO usuarios (usuario, email, `contraseña`, nombre, apellidos, tipo_usuario, activo, requiere_cambio_pass, verificacion_estado, email_verificado)
     VALUES (?, ?, ?, ?, ?, ?, 1, 0, 'verificado', 1)"
);

$findUser = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
$updatePass = $pdo->prepare("UPDATE usuarios SET `contraseña` = ? WHERE id = ?");
$ensureEgresado = $pdo->prepare("INSERT INTO egresados (id_usuario) SELECT ? WHERE NOT EXISTS (SELECT 1 FROM egresados WHERE id_usuario = ?)");

for ($i = 1; $i <= 5; $i++) {
    $username = sprintf('egresado.demo%02d', $i);
    $email = sprintf('egresado.demo%02d@egresados.utp.edu.mx', $i);

    $findUser->execute([$username]);
    $row = $findUser->fetch();

    if (!$row) {
        $createUser->execute([
            $username,
            $email,
            $egresadoHash,
            'Egresado',
            'Demo ' . $i,
            'egresado',
        ]);
        $userId = (int) $pdo->lastInsertId();
    } else {
        $userId = (int) $row['id'];
        $updatePass->execute([$egresadoHash, $userId]);
    }

    $ensureEgresado->execute([$userId, $userId]);
}

for ($i = 1; $i <= 5; $i++) {
    $username = sprintf('docente.demo%02d', $i);
    $email = sprintf('docente.demo%02d@utp.edu.mx', $i);

    $findUser->execute([$username]);
    $row = $findUser->fetch();

    if (!$row) {
        $createUser->execute([
            $username,
            $email,
            $docenteHash,
            'Docente',
            'Demo ' . $i,
            'docente',
        ]);
    } else {
        $updatePass->execute([$docenteHash, (int) $row['id']]);
    }
}

echo "Credenciales listas." . PHP_EOL;
echo "Egresados: egresado.demo01..05 / {$egresadoPass}" . PHP_EOL;
echo "Docentes: docente.demo01..05 / {$docentePass}" . PHP_EOL;
