<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Invitacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Get offer ID
$ofertaId = (int)($_GET['oferta'] ?? $_POST['oferta'] ?? 0);
if (!$ofertaId) {
    header('Location: mis-ofertas.php');
    exit;
}

$ofertaModel = new Oferta();
$oferta = $ofertaModel->getById($ofertaId);

// Verify ownership
if (!$oferta || (int)$oferta['id_usuario_creador'] !== (int)$_SESSION['usuario_id']) {
    header('Location: mis-ofertas.php');
    exit;
}

// Get all egresados for invitation
$egresadoModel = new Egresado();
$todosEgresados = $egresadoModel->getAll();

// Get existing invitations for this offer
$invitacionModel = new Invitacion();
$invitacionesExistentes = $invitacionModel->getByOfertaId($ofertaId);
$idEgresadosInvitados = array_map(fn($i) => (int)$i['id_egresado'], $invitacionesExistentes);

// Filter egresados: exclude those already invited
$egresadosDisponibles = array_filter($todosEgresados, function($e) use ($idEgresadosInvitados) {
    return !in_array((int)$e['id'], $idEgresadosInvitados);
});

$msgExito = '';
$msgError = '';

// Handle invitation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_invitaciones'])) {
  if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $msgError = 'Token de seguridad inválido. Recarga la página.';
  } else {
    $egresadosSeleccionados = $_POST['egresados'] ?? [];
    $egresadosSeleccionados = array_map('intval', array_filter($egresadosSeleccionados));

    if (empty($egresadosSeleccionados)) {
      $msgError = 'Debes seleccionar al menos un egresado.';
    } else {
      $invitacionesCreadas = 0;
      foreach ($egresadosSeleccionados as $egresadoId) {
        // Verify egresado exists and not already invited
        if (!in_array($egresadoId, $idEgresadosInvitados)) {
          $invData = [
            'id_oferta' => $ofertaId,
            'id_docente' => (int)$_SESSION['usuario_id'],
            'id_egresado' => $egresadoId,
            'estado' => 'pendiente'
          ];
          if ($invitacionModel->create($invData)) {
            $invitacionesCreadas++;
          }
                }
            }

      if ($invitacionesCreadas > 0) {
        $msgExito = "Se enviaron $invitacionesCreadas invitación(es) exitosamente.";
        // Reload to refresh available egresados
        header("Location: invitar-egresados.php?oferta=$ofertaId&enviadas=1");
        exit;
      } else {
        $msgError = 'No se pudieron enviar invitaciones.';
      }
        }
    }
}

$msgEnviadas = isset($_GET['enviadas']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitar Egresados - Docente UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'docente', roleLabel: 'Docente',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'invitar-egresados',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="container-fluid px-0">
    <div class="row g-0">
      <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

      <main class="col utp-content">
        <div class="container-fluid px-3 px-md-4 py-4 py-md-5">

          <!-- Header -->
          <section class="mb-4">
            <a href="mis-ofertas.php" class="btn btn-link text-dark text-decoration-none p-0 mb-3 d-inline-flex align-items-center gap-2">
              <i class="bi bi-chevron-left"></i> Volver
            </a>
            <h1 class="utp-h1 mb-2">Invitar Egresados</h1>
            <p class="utp-subtitle mb-0">
              Oferta: <strong><?= htmlspecialchars($oferta['titulo']) ?></strong> - <?= htmlspecialchars($oferta['empresa']) ?>
            </p>
          </section>

          <?php if ($msgEnviadas): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-check-circle me-2"></i>Invitaciones enviadas exitosamente. Los egresados recibirán una notificación.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if ($msgExito): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if ($msgError): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msgError) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Form -->
          <form method="POST" class="utp-card p-4">
            <?= Security::csrfField() ?>
            <h5 class="mb-3">Selecciona los egresados a invitar</h5>

            <?php if (empty($egresadosDisponibles) && empty($todosEgresados)): ?>
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No hay egresados registrados en el sistema.
              </div>
            <?php elseif (empty($egresadosDisponibles)): ?>
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Todos los egresados han sido invitados a esta oferta.
              </div>
            <?php else: ?>
              <div class="row g-3 mb-3">
                <div class="col-12">
                  <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="seleccionarTodos()">
                      Seleccionar todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodos()">
                      Deseleccionar todos
                    </button>
                  </div>

                  <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($egresadosDisponibles as $egresado):
                      $nombreEgresado = htmlspecialchars(trim(($egresado['nombre'] ?? '') . ' ' . ($egresado['apellidos'] ?? '')));
                      $email = htmlspecialchars($egresado['correo_personal'] ?? $egresado['email'] ?? '—');
                      $matricula = htmlspecialchars($egresado['matricula'] ?? '—');
                    ?>
                      <label class="list-group-item">
                        <input class="form-check-input me-2 egresado-checkbox" type="checkbox" name="egresados[]" value="<?= (int)$egresado['id'] ?>">
                        <div>
                          <strong><?= $nombreEgresado ?></strong>
                          <br>
                          <small class="text-muted">
                            Matrícula: <?= $matricula ?> | <?= $email ?>
                          </small>
                        </div>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <div class="row g-2">
                <div class="col-auto">
                  <button type="submit" name="enviar_invitaciones" class="btn btn-utp-green">
                    <i class="bi bi-send me-2"></i>Enviar Invitaciones
                  </button>
                </div>
                <div class="col-auto">
                  <a href="mis-ofertas.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-2"></i>Cancelar
                  </a>
                </div>
              </div>
            <?php endif; ?>
          </form>

          <!-- Info section -->
          <div class="utp-card mt-4 p-4 bg-light">
            <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>¿Cómo funciona?</h6>
            <ul class="small mb-0">
              <li>Los egresados invitados recibirán una notificación</li>
              <li>Podrán aceptar la invitación y postularse automáticamente</li>
              <li>O pueden rechazarla si no les interesa</li>
              <li>Verás sus postulaciones en la sección de "Alumnos / Postulantes"</li>
            </ul>
          </div>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function seleccionarTodos() {
      document.querySelectorAll('.egresado-checkbox').forEach(cb => cb.checked = true);
    }

    function deseleccionarTodos() {
      document.querySelectorAll('.egresado-checkbox').forEach(cb => cb.checked = false);
    }
  </script>
</body>
</html>
