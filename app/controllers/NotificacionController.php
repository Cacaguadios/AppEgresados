<?php
/**
 * NotificacionController
 * API endpoint para notificaciones (usado por AJAX del topbar)
 * 
 * Acciones via GET ?action=:
 *   - list: obtener notificaciones del usuario (JSON)
 *   - count: contar no leídas (JSON)
 * 
 * Acciones via POST:
 *   - read: marcar una notificación como leída
 *   - read_all: marcar todas como leídas
 */

// Session should already be started by the router
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../models/Notificacion.php';

$notifModel = new Notificacion();
$userId = $_SESSION['usuario_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    case 'count':
        $count = $notifModel->contarNoLeidas($userId);
        echo json_encode(['count' => $count]);
        break;

    case 'list':
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $notificaciones = $notifModel->getByUsuario($userId, $limit);
        $count = $notifModel->contarNoLeidas($userId);
        echo json_encode([
            'count'          => $count,
            'notificaciones' => $notificaciones,
        ]);
        break;

    case 'read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $notifModel->marcarLeida($id, $userId);
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido']);
        }
        break;

    case 'read_all':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
        }
        $notifModel->marcarTodasLeidas($userId);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
