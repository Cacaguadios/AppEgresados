<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sql = file_get_contents(__DIR__ . '/migrations/003_add_ofertas_and_seguimiento_fields.sql');

foreach (explode(';', $sql) as $stmt) {
    $stmt = trim($stmt);
    // Skip comments and empty statements
    if (empty($stmt) || strpos($stmt, '--') === 0 || strlen($stmt) < 10) continue;
    // Remove leading comment lines
    $lines = explode("\n", $stmt);
    $clean = [];
    foreach ($lines as $line) {
        $l = trim($line);
        if ($l !== '' && strpos($l, '--') !== 0) $clean[] = $line;
    }
    $stmt = implode("\n", $clean);
    if (strlen(trim($stmt)) < 10) continue;

    try {
        $pdo->exec($stmt);
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 70) . PHP_EOL;
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "Migration 003 complete." . PHP_EOL;
