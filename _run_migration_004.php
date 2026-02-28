<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "Running migration 004...\n";

// 1. Add verification columns
try {
    $pdo->exec("ALTER TABLE usuarios
        ADD COLUMN verificacion_estado ENUM('pendiente','verificado','rechazado') NOT NULL DEFAULT 'pendiente' AFTER fecha_ultima_login,
        ADD COLUMN verificacion_motivo_rechazo TEXT NULL AFTER verificacion_estado,
        ADD COLUMN verificacion_fecha DATETIME NULL AFTER verificacion_motivo_rechazo");
    echo "[OK] Verification columns added\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "[SKIP] Columns already exist\n";
    } else {
        echo "[ERROR] " . $e->getMessage() . "\n";
    }
}

// 2. Set admin credentials
$hash = password_hash('Admin1234!', PASSWORD_BCRYPT);
$pdo->prepare("UPDATE usuarios SET usuario = 'admin', `contraseña` = ?, verificacion_estado = 'verificado', verificacion_fecha = NOW() WHERE id = 1 AND tipo_usuario = 'admin'")->execute([$hash]);
echo "[OK] Admin credentials set (admin / Admin1234!)\n";

// 3. Auto-verify active users
$stmt = $pdo->prepare("UPDATE usuarios SET verificacion_estado = 'verificado', verificacion_fecha = NOW() WHERE activo = 1 AND verificacion_estado = 'pendiente' AND id != 1");
$stmt->execute();
echo "[OK] Active users verified (" . $stmt->rowCount() . " updated)\n";

echo "\nMigration 004 complete!\n";
