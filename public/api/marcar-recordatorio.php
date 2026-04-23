<?php
/**
 * API: Marcar recordatorio como visto y obtener estado
 * GET: Obtener estado actual del recordatorio
 * POST: Marcar recordatorio como visto
 */

session_start();

// Guard: solo para egresados autenticados
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if (($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$egresadoModel = new Egresado();
$id_usuario = $_SESSION['usuario_id'];

// Obtener estado del recordatorio
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $estado = $egresadoModel->obtenerEstadoRecordatorio($id_usuario);
    
    if ($estado) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'estado' => $estado
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Egresado no encontrado'
        ]);
    }
    exit;
}

// Marcar recordatorio como visto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $csrfToken = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!Security::validateCsrfToken($csrfToken)) {
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    $accion = $data['accion'] ?? '';

    if ($accion === 'marcar_visto') {
        $resultado = $egresadoModel->marcarRecordatorioVisto($id_usuario);
        
        if ($resultado) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Recordatorio marcado como visto',
                'proximo_recordatorio' => date('Y-m-d', strtotime('+3 months'))
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al marcar recordatorio'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
