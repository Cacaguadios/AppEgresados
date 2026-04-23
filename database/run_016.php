<?php
/**
 * Migración 016: columnas de feedback en postulaciones
 * Ejecutar: php database/run_016.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sqls = [
    "ALTER TABLE postulaciones ADD COLUMN feedback_resultado  VARCHAR(20) NULL",
    "ALTER TABLE postulaciones ADD COLUMN feedback_trabajo    TINYINT(1)  NULL",
    "ALTER TABLE postulaciones ADD COLUMN feedback_comentario TEXT        NULL",
    "ALTER TABLE postulaciones ADD COLUMN fecha_feedback      DATETIME    NULL",

    "ALTER TABLE notificaciones
        MODIFY COLUMN tipo ENUM(
            'oferta_nueva','oferta_aprobada','oferta_rechazada',
            'nueva_postulacion','postulacion_seleccionada','postulacion_rechazada',
            'nuevo_usuario','general',
            'invitacion_oferta','postulacion_retirada',
            'feedback_pendiente'
        ) NOT NULL DEFAULT 'general'",
];

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr(trim($sql), 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "SKIP (ya existe): " . substr(trim($sql), 0, 60) . "...\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nMigración 016 completada.\n";
