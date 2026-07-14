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

require_once __DIR__ . '/../models/Notificacion.php';

$notifModel = new Notificacion();
$userId = $_SESSION['usuario_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    case 'count':
        $count = $notifModel->contarNoLeidas($userId);
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'list':
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $notificaciones = $notifModel->getByUsuario($userId, $limit);
        $count = $notifModel->contarNoLeidas($userId);
        echo json_encode([
            'success'        => true,
            'count'          => $count,
            'notificaciones' => $notificaciones,
        ]);
        break;

    case 'read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_error(405, 'method_not_allowed', 'Metodo no permitido.');
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $notifModel->marcarLeida($id, $userId);
            echo json_encode(['success' => true]);
        } else {
            api_error(400, 'notification_id_invalid', 'ID de notificacion invalido.');
        }
        break;

    case 'read_all':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_error(405, 'method_not_allowed', 'Metodo no permitido.');
        }
        $notifModel->marcarTodasLeidas($userId);
        echo json_encode(['success' => true]);
        break;

    default:
        api_error(400, 'notification_action_invalid', 'Accion no valida.');
        break;
}
