<?php
/**
 * API: Guardar feedback del ofertador sobre una postulación contactada
 * POST { postulacion_id, resultado, quedo_en_trabajo, comentario, csrf_token }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$csrfToken = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (!Security::validateCsrfToken($csrfToken)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$postulacionId = (int)($_POST['postulacion_id'] ?? ($input['postulacion_id'] ?? 0));
$resultado     = trim($_POST['resultado']       ?? ($input['resultado']       ?? ''));
$quedoTrabajo  = isset($_POST['quedo_en_trabajo'])  ? (int)$_POST['quedo_en_trabajo']
               : (isset($input['quedo_en_trabajo'])  ? (int)$input['quedo_en_trabajo'] : null);
$comentario    = trim($_POST['comentario']      ?? ($input['comentario']      ?? ''));

if (!$postulacionId || !in_array($resultado, ['satisfecho', 'insatisfecho'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos']);
    exit;
}

$postulacionModel = new Postulacion();
$postulacion = $postulacionModel->getById($postulacionId);

if (!$postulacion) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Postulación no encontrada']);
    exit;
}

// Solo el creador de la oferta o admin puede dar feedback
$esCreador = (int)$postulacion['id_usuario_creador'] === (int)$_SESSION['usuario_id'];
$esAdmin   = ($_SESSION['usuario_rol'] ?? '') === 'admin';
if (!$esCreador && !$esAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sin permisos para esta acción']);
    exit;
}

if ($postulacion['estado'] !== 'contactado') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Solo se puede dar feedback a postulaciones en estado contactado']);
    exit;
}

$postulacionModel->guardarFeedback($postulacionId, $resultado, $quedoTrabajo, $comentario);

echo json_encode(['success' => true, 'message' => 'Feedback registrado correctamente']);
