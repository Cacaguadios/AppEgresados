<?php
/**
 * API Handler para actualizaciones de postulaciones
 * Acciones: retirar, restaurar, actualizar_estado, editar_mensaje, eliminar
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
require_once __DIR__ . '/../../app/models/Notificacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$csrfToken = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (!Security::validateCsrfToken($csrfToken)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);
$postulacionId = (int)($_GET['postulacion_id'] ?? $_POST['postulacion_id'] ?? ($input['postulacion_id'] ?? 0));

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

// Validar actor y permisos
$egresadoModel = new Egresado();
$egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
$rol = $_SESSION['usuario_rol'] ?? '';

$esAdmin = $rol === 'admin';
$esCreadorOferta = ((int)$postulacion['id_usuario_creador'] === (int)$_SESSION['usuario_id']);
$esEgresadoPropietario = ($rol === 'egresado') && $egresado && ((int)$postulacion['id_egresado'] === (int)$egresado['id']);

if (!$esAdmin && !$esCreadorOferta && !$esEgresadoPropietario) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tienes permisos para esta acción']);
    exit;
}

switch ($action) {
    case 'retirar':
        if (!$esEgresadoPropietario && !$esAdmin && !$esCreadorOferta) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para retirar esta postulación']);
            exit;
        }

        $postulacionModel->retirar($postulacionId);

        // Notificar al docente si es el egresado quien retira
        if ($esEgresadoPropietario && !$esAdmin && !$esCreadorOferta) {
            $notifModel = new Notificacion();
            $notifModel->onPostulacionRetirada(
                $postulacion['oferta_titulo'],
                trim(($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellidos'] ?? '')),
                (int)$postulacion['id_usuario_creador'],
                $postulacion['creador_email'] ?? null
            );
        }

        echo json_encode(['success' => true, 'message' => 'Postulación retirada correctamente']);
        break;

    case 'restaurar':
        if (!$esEgresadoPropietario && !$esAdmin && !$esCreadorOferta) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para restaurar esta postulación']);
            exit;
        }

        $postulacionModel->restaurar($postulacionId);
        echo json_encode(['success' => true, 'message' => 'Postulación restaurada correctamente']);
        break;

    case 'editar_mensaje':
        $mensaje = trim($_POST['mensaje'] ?? ($input['mensaje'] ?? ''));
        $postulacionModel->updateMensaje($postulacionId, $mensaje);
        echo json_encode(['success' => true, 'message' => 'Mensaje de postulación actualizado']);
        break;

    case 'actualizar_estado':
        if (!$esAdmin && !$esCreadorOferta) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo quien publicó la oferta o administración puede actualizar estado']);
            exit;
        }

        $nuevoEstado = $_POST['estado'] ?? ($input['estado'] ?? null);
        $estadosValidos = ['pendiente', 'preseleccionado', 'contactado', 'rechazado'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Estado no válido']);
            exit;
        }

        $postulacionModel->updateEstado($postulacionId, $nuevoEstado);

        // Notificar al egresado en estados clave
        $notifModel = new Notificacion();
        if ($nuevoEstado === 'contactado') {
            $notifModel->onPostulanteSeleccionado(
                $postulacion['oferta_titulo'],
                $postulacion['egresado_usuario_id'],
                $postulacion['egresado_email'] ?? null
            );
            // Req.9: pedir feedback al ofertador sobre el resultado del contacto
            $notifModel->onFeedbackSolicitado(
                $postulacion['oferta_titulo'],
                $postulacion['egresado_nombre'] ?? 'el candidato',
                (int)$postulacion['id_usuario_creador'],
                $postulacionId,
                $postulacion['creador_oferta_email'] ?? null
            );
        } elseif ($nuevoEstado === 'rechazado') {
            $notifModel->onPostulanteRechazado(
                $postulacion['oferta_titulo'],
                $postulacion['egresado_usuario_id'],
                $postulacion['egresado_email'] ?? null
            );
        }

        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        break;

    case 'eliminar':
        if (!$esAdmin && !$esCreadorOferta && !$esEgresadoPropietario) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para eliminar esta postulación']);
            exit;
        }

        $postulacionModel->eliminar($postulacionId);
        echo json_encode(['success' => true, 'message' => 'Postulación eliminada correctamente']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
?>
