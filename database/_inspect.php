<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "=== TABLES ===" . PHP_EOL;
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo PHP_EOL . ">> $t" . PHP_EOL;
    $cols = $pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} | {$c['Type']} | " . ($c['Null']==='YES'?'NULL':'NOT NULL') . " | {$c['Key']} | " . ($c['Default'] ?? '') . PHP_EOL;
    }
}
