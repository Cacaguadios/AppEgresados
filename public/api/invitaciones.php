<?php
/**
 * API Handler para invitaciones de docentes a egresados
 * Acciones: crear, aceptar, rechazar, marcar_visto
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Validar sesión
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No está autenticado']);
    exit;
}

require_once __DIR__ . '/../../app/models/Invitacion.php';
require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/models/Notificacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);

$csrfToken = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (!Security::validateCsrfToken($csrfToken)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$invitacionModel = new Invitacion();
$ofertaModel = new Oferta();
$egresadoModel = new Egresado();
$postulacionModel = new Postulacion();
$notifModel = new Notificacion();

$rol = $_SESSION['usuario_rol'] ?? '';

switch ($action) {
    // Docente invita a egresados a postularse
    case 'crear':
        if (!in_array($rol, ['docente', 'ti'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo docentes pueden invitar']);
            exit;
        }

        $ofertaId = (int)($input['oferta_id'] ?? $_POST['oferta_id'] ?? 0);
        $egresadoIds = $_POST['egresados'] ?? $input['egresados'] ?? [];

        if (!$ofertaId || empty($egresadoIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Oferta e ID de egresados requeridos']);
            exit;
        }

        $oferta = $ofertaModel->getById($ofertaId);
        if (!$oferta || (int)$oferta['id_usuario_creador'] !== (int)$_SESSION['usuario_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para invitar en esta oferta']);
            exit;
        }

        $invitacionesCreadas = 0;
        $egresadosInvitados = [];

        foreach ((array)$egresadoIds as $egresadoId) {
            $egresadoId = (int)$egresadoId;
            if (!$egresadoId) continue;

            // Verificar que no sea una invitación duplicada
            $existente = $invitacionModel->exists($ofertaId, $egresadoId);
            if ($existente) {
                continue;
            }

            // Crear invitación
            $invData = [
                'id_oferta' => $ofertaId,
                'id_docente' => (int)$_SESSION['usuario_id'],
                'id_egresado' => $egresadoId,
                'estado' => 'pendiente'
            ];

            $newId = $invitacionModel->create($invData);
            if ($newId) {
                $invitacionesCreadas++;

                // Obtener datos del egresado para notificación
                $egresado = $egresadoModel->getById($egresadoId);
                if ($egresado) {
                    $egresadosInvitados[] = [
                        'id_egresado' => $egresadoId,
                        'id_usuario' => $egresado['id_usuario'],
                        'email' => $egresado['correo_personal'] ?? null
                    ];

                    // Crear notificación
                    $notifModel->onInvitacionOferta(
                        $oferta['titulo'],
                        $oferta['empresa'],
                        $egresado['id_usuario'],
                        $ofertaId,
                        $egresado['correo_personal'] ?? null
                    );
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Se invitaron $invitacionesCreadas egresado(s) exitosamente",
            'invitaciones_creadas' => $invitacionesCreadas
        ]);
        break;

    // Egresado acepta invitación
    case 'aceptar':
        if ($rol !== 'egresado') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo egresados pueden aceptar invitaciones']);
            exit;
        }

        $invitacionId = (int)($input['invitacion_id'] ?? $_POST['invitacion_id'] ?? 0);
        if (!$invitacionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de invitación requerido']);
            exit;
        }

        $invitacion = $invitacionModel->getById($invitacionId);
        if (!$invitacion) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invitación no encontrada']);
            exit;
        }

        $egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
        if (!$egresado || (int)$invitacion['id_egresado'] !== (int)$egresado['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Esta invitación no es para ti']);
            exit;
        }

        // Verificar que no haya postulado ya
        $yaPostuló = $postulacionModel->hasApplied((int)$egresado['id'], (int)$invitacion['id_oferta']);
        if ($yaPostuló) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ya has postulado a esta oferta']);
            exit;
        }

        // Marcar invitación como aceptada y crear postulación
        $invitacionModel->updateEstado($invitacionId, 'aceptado');

        $postData = [
            'id_egresado' => (int)$egresado['id'],
            'id_oferta' => (int)$invitacion['id_oferta'],
            'estado' => 'pendiente',
            'fecha_postulacion' => date('Y-m-d H:i:s'),
            'mensaje' => 'Postulación por invitación del docente'
        ];

        $postId = $postulacionModel->create($postData);
        if ($postId) {
            // Inicializar checklist de habilidades blandas
            $oferta = $ofertaModel->getById((int)$invitacion['id_oferta']);
            if ($oferta && !empty($oferta['habilidades'])) {
                $habilidades = json_decode($oferta['habilidades'], true) ?: [];
                $postulacionModel->inicializarChecklistHabilidadesBlandas($postId, $habilidades);
            }

            // Notificar al docente
            $egresadoNombre = trim(($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellidos'] ?? ''));
            $notifModel->onPostulacionRecibida(
                $oferta['titulo'],
                $egresadoNombre !== '' ? $egresadoNombre : 'un egresado',
                (int)$invitacion['id_docente'],
                $invitacion['docente_email'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Has aceptado la invitación y te has postulado a la oferta'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al crear la postulación']);
        }
        break;

    // Egresado rechaza invitación
    case 'rechazar':
        if ($rol !== 'egresado') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo egresados pueden rechazar invitaciones']);
            exit;
        }

        $invitacionId = (int)($input['invitacion_id'] ?? $_POST['invitacion_id'] ?? 0);
        if (!$invitacionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de invitación requerido']);
            exit;
        }

        $invitacion = $invitacionModel->getById($invitacionId);
        if (!$invitacion) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invitación no encontrada']);
            exit;
        }

        $egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
        if (!$egresado || (int)$invitacion['id_egresado'] !== (int)$egresado['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Esta invitación no es para ti']);
            exit;
        }

        $invitacionModel->updateEstado($invitacionId, 'rechazado');
        echo json_encode([
            'success' => true,
            'message' => 'Invitación rechazada'
        ]);
        break;

    // Marcar invitación como vista
    case 'marcar_visto':
        if ($rol !== 'egresado') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo egresados pueden marcar invitaciones como vistas']);
            exit;
        }

        $invitacionId = (int)($input['invitacion_id'] ?? $_POST['invitacion_id'] ?? 0);
        if (!$invitacionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de invitación requerido']);
            exit;
        }

        $invitacion = $invitacionModel->getById($invitacionId);
        if (!$invitacion) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invitación no encontrada']);
            exit;
        }

        $egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
        if (!$egresado || (int)$invitacion['id_egresado'] !== (int)$egresado['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Esta invitación no es para ti']);
            exit;
        }

        if ($invitacion['estado'] === 'pendiente') {
            $invitacionModel->markAsViewed($invitacionId);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Invitación marcada como vista'
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
?>
