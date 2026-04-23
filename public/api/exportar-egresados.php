<?php
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo 'Acceso no autorizado';
    exit;
}

require_once __DIR__ . '/../../app/models/Egresado.php';

$dataset = $_GET['dataset'] ?? 'egresados';
$format = $_GET['format'] ?? 'csv';

$egresadoModel = new Egresado();

if ($dataset === 'empleadores') {
    $rows = $egresadoModel->getEmployerRows();
    $filenameBase = 'empleadores-egresados';
    $columns = [
        'egresado' => 'Egresado',
        'matricula' => 'Matricula',
        'empresa_actual' => 'Empresa',
        'puesto_actual' => 'Puesto',
        'especialidad' => 'Especialidad',
        'trabaja_en_ti' => 'Trabaja en TI',
        'modalidad_trabajo' => 'Modalidad',
        'tipo_contrato' => 'Tipo de contrato',
        'rango_salarial' => 'Rango salarial',
        'fecha_actualizacion_seguimiento' => 'Ultima actualizacion',
    ];
} else {
    $rows = $egresadoModel->getExportRows();
    $filenameBase = 'reporte-egresados';
    $columns = [
        'matricula' => 'Matricula',
        'nombre' => 'Nombre',
        'correo_personal' => 'Correo personal',
        'email' => 'Correo acceso',
        'curp' => 'CURP',
        'generacion' => 'Generacion',
        'especialidad' => 'Especialidad',
        'trabaja_actualmente' => 'Trabaja actualmente',
        'trabaja_en_ti' => 'Trabaja en TI',
        'empresa_actual' => 'Empresa',
        'puesto_actual' => 'Puesto',
        'modalidad_trabajo' => 'Modalidad',
        'tipo_contrato' => 'Tipo de contrato',
        'rango_salarial' => 'Rango salarial',
        'telefono' => 'Telefono',
        'fecha_actualizacion_seguimiento' => 'Ultima actualizacion',
        'estado_usuario' => 'Estado usuario',
    ];
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filenameBase . '.xls');

    echo "<table border='1'><thead><tr>";
    foreach ($columns as $label) {
        echo '<th>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($columns as $key => $_label) {
            $value = $row[$key] ?? '';
            echo '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filenameBase . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($output, array_values($columns));

foreach ($rows as $row) {
    $record = [];
    foreach ($columns as $key => $_label) {
        $record[] = $row[$key] ?? '';
    }
    fputcsv($output, $record);
}

fclose($output);