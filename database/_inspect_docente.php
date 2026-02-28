<?php
require_once __DIR__ . '/../app/models/Database.php';
$db = new Database();

echo "=== OFERTAS COLUMNS ===\n";
$cols = $db->fetchAll('SHOW COLUMNS FROM ofertas');
foreach($cols as $c) echo $c['Field'].' ('.$c['Type'].') '.$c['Null']."\n";

echo "\n=== USUARIOS COLUMNS ===\n";
$cols = $db->fetchAll('SHOW COLUMNS FROM usuarios');
foreach($cols as $c) echo $c['Field'].' ('.$c['Type'].') '.$c['Null']."\n";

echo "\n=== POSTULACIONES COLUMNS ===\n";
$cols = $db->fetchAll('SHOW COLUMNS FROM postulaciones');
foreach($cols as $c) echo $c['Field'].' ('.$c['Type'].') '.$c['Null']."\n";

echo "\n=== EGRESADOS COLUMNS ===\n";
$cols = $db->fetchAll('SHOW COLUMNS FROM egresados');
foreach($cols as $c) echo $c['Field'].' ('.$c['Type'].') '.$c['Null']."\n";

echo "\n=== ALL USERS ===\n";
$users = $db->fetchAll('SELECT id,usuario,nombre,apellidos,tipo_usuario,email FROM usuarios');
foreach($users as $u) echo $u['id'].' '.$u['usuario'].' '.$u['nombre'].' '.$u['apellidos'].' ('.$u['tipo_usuario'].') '.$u['email']."\n";

echo "\n=== OFERTAS ===\n";
$offers = $db->fetchAll('SELECT id,titulo,empresa,id_usuario_creador,estado,estado_vacante,vacantes FROM ofertas');
foreach($offers as $o) echo $o['id'].' "'.$o['titulo'].'" by user#'.$o['id_usuario_creador'].' estado='.$o['estado'].' vacante='.$o['estado_vacante'].' vacantes='.$o['vacantes']."\n";

echo "\n=== POSTULACIONES ===\n";
$posts = $db->fetchAll('SELECT p.*, e.id_usuario FROM postulaciones p JOIN egresados e ON p.id_egresado = e.id');
foreach($posts as $p) echo 'postulacion#'.$p['id'].' egresado#'.$p['id_egresado'].' (user#'.$p['id_usuario'].') oferta#'.$p['id_oferta'].' estado='.$p['estado']."\n";
