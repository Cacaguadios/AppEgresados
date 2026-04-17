<?php
/**
 * API Handler para actualizaciones de ofertas
 * Acciones: editar, baja, activar
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Validar sesión
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No está autenticado']);
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$ofertaId = (int)($_GET['oferta_id'] ?? $_POST['oferta_id'] ?? 0);

if (!$ofertaId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de oferta faltante']);
    exit;
}

$ofertaModel = new Oferta();
$oferta = $ofertaModel->getById($ofertaId);

if (!$oferta) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Oferta no encontrada']);
    exit;
}

// Validar permisos
$esCreador = $oferta['id_usuario_creador'] == $_SESSION['usuario_id'];
$esAdmin = $_SESSION['usuario_rol'] === 'admin';

if (!$esCreador && !$esAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tienes permisos para esta acción']);
    exit;
}

switch ($action) {
    case 'baja':
        $motivo = $_POST['motivo'] ?? 'Dada de baja por el creador';
        $ofertaModel->setBaja($ofertaId, $motivo);
        echo json_encode(['success' => true, 'message' => 'Oferta dada de baja correctamente']);
        break;

    case 'activar':
        $ofertaModel->setActiva($ofertaId);
        echo json_encode(['success' => true, 'message' => 'Oferta reactivada correctamente']);
        break;

    case 'editar':
        $data = [];
        
        // Campos editables
        $campos = ['titulo', 'empresa', 'ubicacion', 'modalidad', 'jornada', 
                   'salario_min', 'salario_max', 'descripcion', 'contacto',
                   'nombre_contacto', 'puesto_contacto', 'telefono_contacto', 
                   'vacantes', 'fecha_expiracion'];
        
        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                $data[$campo] = trim($_POST[$campo]);
            }
        }
        
        // Requisitos, beneficios, habilidades (arrays JSON)
        if (isset($_POST['requisitos'])) {
            $data['requisitos'] = is_array($_POST['requisitos']) 
                ? json_encode($_POST['requisitos']) 
                : $_POST['requisitos'];
        }
        if (isset($_POST['beneficios'])) {
            $data['beneficios'] = is_array($_POST['beneficios']) 
                ? json_encode($_POST['beneficios']) 
                : $_POST['beneficios'];
        }
        if (isset($_POST['habilidades'])) {
            $data['habilidades'] = is_array($_POST['habilidades']) 
                ? json_encode($_POST['habilidades']) 
                : $_POST['habilidades'];
        }

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No hay datos para actualizar']);
            exit;
        }

        $ofertaModel->edit($ofertaId, $data);
        echo json_encode(['success' => true, 'message' => 'Oferta actualizada correctamente']);
        break;

    case 'eliminar':
        // Solo admin puede eliminar ofertas permanentemente
        if (!$esAdmin && $_SESSION['usuario_rol'] !== 'ti') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo administradores pueden eliminar ofertas']);
            exit;
        }
        
        // Aquí se podría implementar eliminación física si es necesario
        $ofertaModel->setBaja($ofertaId, 'Eliminada por ' . ($_SESSION['usuario_rol'] === 'admin' ? 'administrador' : 'staff'));
        echo json_encode(['success' => true, 'message' => 'Oferta eliminada']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
?>
