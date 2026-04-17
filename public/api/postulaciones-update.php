<?php
/**
 * API Handler para actualizaciones de postulaciones
 * Acciones: retirar
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Validar sesión
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No está autenticado']);
    exit;
}

require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/models/Egresado.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$postulacionId = (int)($_GET['postulacion_id'] ?? $_POST['postulacion_id'] ?? 0);

if (!$postulacionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de postulación faltante']);
    exit;
}

$postulacionModel = new Postulacion();
$postulacion = $postulacionModel->getById($postulacionId);

if (!$postulacion) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Postulación no encontrada']);
    exit;
}

// Validar que es el egresado propietario
$egresadoModel = new Egresado();
$egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);

if (!$egresado || $postulacion['id_egresado'] != $egresado['id']) {
    // Si no es el egresado, verificar si es admin/docente
    $esAdmin = $_SESSION['usuario_rol'] === 'admin';
    $esDocente = $_SESSION['usuario_rol'] === 'docente' && $postulacion['id_usuario_creador'] == $_SESSION['usuario_id'];
    
    if (!$esAdmin && !$esDocente) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permisos para esta acción']);
        exit;
    }
}

switch ($action) {
    case 'retirar':
        // Solo el egresado puede retirar su postulación
        if ($_SESSION['usuario_rol'] !== 'egresado' && $_SESSION['usuario_rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para retirar esta postulación']);
            exit;
        }
        
        $postulacionModel->retirar($postulacionId);
        echo json_encode(['success' => true, 'message' => 'Postulación retirada correctamente']);
        break;

    case 'actualizar_estado':
        // Docente/Admin puede actualizar estado
        $nuevoEstado = $_POST['estado'] ?? null;
        $estadosValidos = ['pendiente', 'preseleccionado', 'contactado', 'rechazado'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Estado no válido']);
            exit;
        }

        $postulacionModel->updateEstado($postulacionId, $nuevoEstado);
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
?>
