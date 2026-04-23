<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';

$ofertaModel = new Oferta();
$egresadoModel = new Egresado();
$postulacionModel = new Postulacion();

$offerSummary = $ofertaModel->getReportSummary();
$approvedByMonth = $ofertaModel->getApprovedByMonth(6);
$egresadoSummary = $egresadoModel->getAdminReportSummary();
$topCompanies = $egresadoModel->getTopEmpresasEmpleadoras(8);
$postulacionSummary = $postulacionModel->getAdminStats();

echo json_encode([
    'ok' => true,
    'data' => [
        'offersSummary' => [
            'liberadas' => (int)($offerSummary['liberadas'] ?? 0),
            'activas' => (int)($offerSummary['activas'] ?? 0),
            'pendientes' => (int)($offerSummary['pendientes'] ?? 0),
            'rechazadas' => (int)($offerSummary['rechazadas'] ?? 0),
            'cerradas' => (int)($offerSummary['cerradas'] ?? 0),
        ],
        'approvedByMonth' => array_map(static function ($item) {
            return [
                'label' => $item['etiqueta'] ?? $item['periodo'],
                'value' => (int)($item['total'] ?? 0),
            ];
        }, $approvedByMonth),
        'employmentSummary' => [
            'empleados' => (int)($egresadoSummary['empleados'] ?? 0),
            'no_empleados' => (int)($egresadoSummary['no_empleados'] ?? 0),
            'en_ti' => (int)($egresadoSummary['en_ti'] ?? 0),
            'fuera_ti' => max(0, (int)($egresadoSummary['empleados'] ?? 0) - (int)($egresadoSummary['en_ti'] ?? 0)),
        ],
        'laborMetrics' => [
            'salario_promedio_estimado' => (float)($egresadoSummary['salario_promedio_estimado'] ?? 0),
            'promedio_meses_laborando' => (float)($egresadoSummary['promedio_meses_laborando'] ?? 0),
        ],
        'postulaciones' => [
            'pendientes' => (int)($postulacionSummary['pendientes'] ?? 0),
            'preseleccionadas' => (int)($postulacionSummary['preseleccionadas'] ?? 0),
            'contactadas' => (int)($postulacionSummary['contactadas'] ?? 0),
            'rechazadas' => (int)($postulacionSummary['rechazadas'] ?? 0),
            'retiradas' => (int)($postulacionSummary['retiradas'] ?? 0),
        ],
        'topCompanies' => array_map(static function ($item) {
            return [
                'label' => $item['empresa'] ?? 'Sin empresa',
                'value' => (int)($item['total'] ?? 0),
                'en_ti' => (int)($item['en_ti'] ?? 0),
            ];
        }, $topCompanies),
    ],
], JSON_UNESCAPED_UNICODE);