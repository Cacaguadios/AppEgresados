<?php
require __DIR__ . '/app/models/Database.php';
$db = new Database();

// Check admin user
$admin = $db->fetchOne('SELECT id, usuario, nombre, apellidos, email, tipo_usuario, activo FROM usuarios WHERE tipo_usuario = ?', ['admin']);
echo "=== ADMIN USER ===\n";
print_r($admin);

// Check columns in usuarios table
$cols = $db->fetchAll("SHOW COLUMNS FROM usuarios");
echo "\n=== USUARIOS COLUMNS ===\n";
foreach ($cols as $c) echo $c['Field'] . ' (' . $c['Type'] . ') ' . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";

// Check egresados columns for seguimiento
$ecols = $db->fetchAll("SHOW COLUMNS FROM egresados");
echo "\n=== EGRESADOS COLUMNS ===\n";
foreach ($ecols as $c) echo $c['Field'] . ' (' . $c['Type'] . ') ' . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
