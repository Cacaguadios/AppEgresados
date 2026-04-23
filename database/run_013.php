<?php
/**
 * Script para ejecutar la Migración 013
 * Crea el checklist de habilidades blandas por postulación.
 *
 * Ejecutar con: php database/run_013.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sql = file_get_contents(__DIR__ . '/migrations/013_add_postulacion_soft_skills_checklist.sql');
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
    echo 'OK: ' . substr(preg_replace('/\s+/', ' ', $cleanSql), 0, 70) . PHP_EOL;
} catch (Exception $e) {
    echo 'SKIP: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Migration 013 complete." . PHP_EOL;