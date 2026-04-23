<?php
/**
 * Limpia la base de datos de pruebas y deja solo usuarios tipo admin.
 * Uso: php database/cleanup_keep_only_admin.php
 */

$pdo = new PDO(
    'mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

try {
    $admins = $pdo->query("SELECT id, usuario, email FROM usuarios WHERE tipo_usuario = 'admin' ORDER BY id")
        ->fetchAll(PDO::FETCH_ASSOC);

    if (empty($admins)) {
        throw new RuntimeException('No se encontró ningún usuario admin. Operación cancelada.');
    }

    echo "Admins a conservar:" . PHP_EOL;
    foreach ($admins as $admin) {
        echo "- ID={$admin['id']} | user={$admin['usuario']} | email={$admin['email']}" . PHP_EOL;
    }
    echo PHP_EOL;

    $tablesToClear = [
        'postulaciones',
        'notificaciones',
        'ofertas',
        'egresados',
        'email_verification_log',
        'codigos_verificacion',
        'audit_logs',
    ];

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->beginTransaction();

    foreach ($tablesToClear as $table) {
        $pdo->exec("DELETE FROM {$table}");
    }

    $deletedUsers = $pdo->exec("DELETE FROM usuarios WHERE tipo_usuario <> 'admin'");

    $pdo->commit();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "Limpieza completada." . PHP_EOL;
    echo "Usuarios no-admin eliminados: {$deletedUsers}" . PHP_EOL;
    echo PHP_EOL;

    $counts = [
        'usuarios' => "SELECT COUNT(*) FROM usuarios",
        'usuarios_admin' => "SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'admin'",
        'egresados' => "SELECT COUNT(*) FROM egresados",
        'ofertas' => "SELECT COUNT(*) FROM ofertas",
        'postulaciones' => "SELECT COUNT(*) FROM postulaciones",
        'notificaciones' => "SELECT COUNT(*) FROM notificaciones",
        'codigos_verificacion' => "SELECT COUNT(*) FROM codigos_verificacion",
        'email_verification_log' => "SELECT COUNT(*) FROM email_verification_log",
        'audit_logs' => "SELECT COUNT(*) FROM audit_logs",
    ];

    echo "Conteos finales:" . PHP_EOL;
    foreach ($counts as $label => $sql) {
        $value = (int) $pdo->query($sql)->fetchColumn();
        echo "- {$label}: {$value}" . PHP_EOL;
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Throwable $ignored) {
    }

    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
