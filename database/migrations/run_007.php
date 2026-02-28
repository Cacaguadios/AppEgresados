<?php
/**
 * Ejecutar seed 007 desde PHP para evitar problemas de encoding
 */
require_once __DIR__ . '/../../app/models/Database.php';

$db = new Database();
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents(__DIR__ . '/007_seed_data.sql');

// Remove comments
$sql = preg_replace('/--[^\n]*/', '', $sql);

// Split by semicolons (respecting strings)
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = [];

foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (PDOException $e) {
        $errors[] = substr($stmt, 0, 80) . '... => ' . $e->getMessage();
    }
}

echo "Ejecutados: {$success} statements\n";
if (!empty($errors)) {
    echo "Errores (" . count($errors) . "):\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
}
echo "Done.\n";
