<?php
/**
 * API: Guardar feedback del ofertador sobre una postulación contactada
 * POST { postulacion_id, resultado, quedo_en_trabajo, comentario, csrf_token }
 */

require_once __DIR__ . '/../../app/helpers/Http.php';
api_bootstrap(__FILE__);

require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$input = api_json_input();

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
