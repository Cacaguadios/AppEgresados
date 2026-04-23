<?php
/**
 * Script para ejecutar la Migración 014
 * Agrega campos adicionales al seguimiento de egresados.
 *
 * Ejecutar con: php database/run_014.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sql = file_get_contents(__DIR__ . '/migrations/014_add_additional_fields_to_egresados.sql');
$sqlLines = [];

foreach (preg_split('/\R/', $sql) as $line) {
    $lineTrim = trim($line);
    if ($lineTrim === '' || strpos($lineTrim, '--') === 0) {
        continue;
    }
    $sqlLines[] = $line;
}

$cleanSql = trim(implode("\n", $sqlLines));

try {
    $pdo->exec($cleanSql);
    echo 'OK: ' . substr(preg_replace('/\s+/', ' ', $cleanSql), 0, 90) . PHP_EOL;
} catch (Exception $e) {
    echo 'SKIP: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Migration 014 complete." . PHP_EOL;
