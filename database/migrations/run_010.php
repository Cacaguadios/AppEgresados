<?php
/**
 * Ejecutar Migración 010: Sistema de recordatorio de actualización
 * Agrega campos para rastrear la completitud del perfil y recordatorios
 */

// Conexión a base de datos
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "=== Ejecutando Migración 010 ===\n";
echo "Agregando campos para sistema de recordatorio...\n\n";

try {
    // Agregar columnas a tabla egresados
    $sqlEgresados = [
        "ALTER TABLE egresados ADD COLUMN IF NOT EXISTS fecha_proximo_recordatorio DATETIME NULL",
        "ALTER TABLE egresados ADD COLUMN IF NOT EXISTS recordatorio_visto TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE egresados ADD COLUMN IF NOT EXISTS porcentaje_completitud INT NOT NULL DEFAULT 0",
    ];

    foreach ($sqlEgresados as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ " . substr($sql, 0, 70) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ SKIP (existe): " . substr($sql, 0, 70) . "\n";
            } else {
                throw $e;
            }
        }
    }

    // Crear índices
    $sqlIndexes = [
        "CREATE INDEX IF NOT EXISTS idx_recordatorio_visto ON egresados(recordatorio_visto, fecha_proximo_recordatorio)",
        "CREATE INDEX IF NOT EXISTS idx_completitud ON egresados(porcentaje_completitud)",
    ];

    foreach ($sqlIndexes as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ " . substr($sql, 0, 70) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ SKIP (existe): " . substr($sql, 0, 70) . "\n";
            } else {
                throw $e;
            }
        }
    }

    // Agregar columna en usuarios
    $sqlUsers = [
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS fecha_ultima_actualizacion_perfil DATETIME NULL",
    ];

    foreach ($sqlUsers as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ " . substr($sql, 0, 70) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ SKIP (existe): " . substr($sql, 0, 70) . "\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Migración 010 completada exitosamente\n";
    exit(0);

} catch (PDOException $e) {
    echo "\n❌ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
