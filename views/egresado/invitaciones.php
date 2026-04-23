<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Invitacion.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Get egresado ID
$egresadoModel = new Egresado();
$perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
$egresadoId = $perfil['id'] ?? 0;

// Get invitations
$invitacionModel = new Invitacion();
$invitaciones = $invitacionModel->getByEgresadoId($egresadoId);

// Filter by status
$filtroEstado = $_GET['estado'] ?? 'pendiente';
$invitacionesFiltradas = array_filter($invitaciones, function($inv) use ($filtroEstado) {
    if ($filtroEstado === 'todas') return true;
    return $inv['estado'] === $filtroEstado;
});

// Stats
$estadoCounts = [
    'pendiente' => count(array_filter($invitaciones, fn($i) => $i['estado'] === 'pendiente')),
    'visto' => count(array_filter($invitaciones, fn($i) => $i['estado'] === 'visto')),
    'aceptado' => count(array_filter($invitaciones, fn($i) => $i['estado'] === 'aceptado')),
    'rechazado' => count(array_filter($invitaciones, fn($i) => $i['estado'] === 'rechazado')),
];

$statusLabels = [
  'pendiente' => ['label' => 'Pendiente', 'color' => 'yellow', 'icon' => 'bi-clock-history'],
  'visto' => ['label' => 'Visto', 'color' => 'blue', 'icon' => 'bi-eye'],
  'aceptado' => ['label' => 'Aceptado', 'color' => 'green', 'icon' => 'bi-check-circle'],
  'rechazado' => ['label' => 'Rechazado', 'color' => 'gray', 'icon' => 'bi-x-circle'],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitaciones - Egresados UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'egresado', roleLabel: 'Egresado',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'invitaciones',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
    window.UTP_CSRF_TOKEN = <?= json_encode(Security::generateCsrfToken()) ?>;
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="utp-layout">
    <div class="container-fluid px-3 px-md-4">
      <div class="row gx-4">
        <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

        <main class="col">
          <div class="utp-content">
            <div class="container-fluid px-0 py-4 py-md-5">

          <!-- Header -->
          <section class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
              <h1 class="utp-h1 mb-2">Invitaciones</h1>
              <p class="utp-subtitle mb-0"><?= count($invitaciones) ?> invitación<?= count($invitaciones) !== 1 ? 'es' : '' ?> recibida<?= count($invitaciones) !== 1 ? 's' : '' ?></p>
            </div>
          </section>

          <!-- Stats Cards -->
          <div class="row g-3 mb-4">
            <div class="col-6 col-sm-6 col-lg-3">
              <a href="?estado=todas" class="utp-card-link">
                <div class="utp-card p-3 text-center h-100">
                  <div class="utp-stat-icon blue"><i class="bi bi-inbox"></i></div>
                  <span class="d-block text-muted small mt-2">Total</span>
                  <span class="d-block h4 mb-0"><?= count($invitaciones) ?></span>
                </div>
              </a>
            </div>
            <div class="col-6 col-sm-6 col-lg-3">
              <a href="?estado=pendiente" class="utp-card-link">
                <div class="utp-card p-3 text-center h-100">
                  <div class="utp-stat-icon yellow"><i class="bi bi-clock-history"></i></div>
                  <span class="d-block text-muted small mt-2">Pendientes</span>
                  <span class="d-block h4 mb-0"><?= $estadoCounts['pendiente'] ?></span>
                </div>
              </a>
            </div>
            <div class="col-6 col-sm-6 col-lg-3">
              <a href="?estado=aceptado" class="utp-card-link">
                <div class="utp-card p-3 text-center h-100">
                  <div class="utp-stat-icon green"><i class="bi bi-check-circle"></i></div>
                  <span class="d-block text-muted small mt-2">Aceptadas</span>
                  <span class="d-block h4 mb-0"><?= $estadoCounts['aceptado'] ?></span>
                </div>
              </a>
            </div>
            <div class="col-6 col-sm-6 col-lg-3">
              <a href="?estado=rechazado" class="utp-card-link">
                <div class="utp-card p-3 text-center h-100">
                  <div class="utp-stat-icon gray"><i class="bi bi-x-circle"></i></div>
                  <span class="d-block text-muted small mt-2">Rechazadas</span>
                  <span class="d-block h4 mb-0"><?= $estadoCounts['rechazado'] ?></span>
                </div>
              </a>
            </div>
          </div>

          <!-- Filter buttons -->
          <div class="utp-inv-filter mb-4">
            <a href="?estado=pendiente" class="btn <?= $filtroEstado === 'pendiente' ? 'btn-utp-green' : 'btn-utp-outline' ?>">
              Pendientes (<?= $estadoCounts['pendiente'] ?>)
            </a>
            <a href="?estado=todas" class="btn <?= $filtroEstado === 'todas' ? 'btn-utp-green' : 'btn-utp-outline' ?>">
              Todas (<?= count($invitaciones) ?>)
            </a>
          </div>

          <!-- Invitations List -->
          <?php if (empty($invitacionesFiltradas)): ?>
            <div class="utp-card text-center py-5">
              <div class="utp-miniicon utp-empty-icon blue mx-auto mb-3">
                <i class="bi bi-inbox"></i>
              </div>
              <h3 class="utp-empty-title">Sin invitaciones</h3>
              <p class="utp-empty-text">
                <?= $filtroEstado === 'pendiente' ? 'No tienes invitaciones pendientes.' : 'No hay invitaciones con este estado.' ?>
              </p>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($invitacionesFiltradas as $inv): 
                $status = $statusLabels[$inv['estado']] ?? $statusLabels['pendiente'];
                $isPendiente = $inv['estado'] === 'pendiente';
              ?>
                <div class="col-12 col-md-6">
                  <div class="utp-card utp-inv-card p-4 h-100">
                    <!-- Header with status -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <div>
                        <h5 class="mb-0"><?= htmlspecialchars($inv['titulo']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($inv['empresa']) ?></small>
                      </div>
                      <span class="utp-badge-<?= $status['color'] ?>">
                        <i class="bi <?= $status['icon'] ?> me-1"></i><?= $status['label'] ?>
                      </span>
                    </div>

                    <!-- Offer details -->
                    <div class="row g-2 mb-3">
                      <?php if (!empty($inv['ubicacion'])): ?>
                        <div class="col-6">
                          <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($inv['ubicacion']) ?></small>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($inv['modalidad'])): ?>
                        <div class="col-6">
                          <small class="text-muted"><i class="bi bi-laptop me-1"></i><?= htmlspecialchars($inv['modalidad']) ?></small>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Salary info -->
                    <?php if (!empty($inv['salario_min']) || !empty($inv['salario_max'])): ?>
                      <div class="utp-info-notice mb-3">
                        <small>
                          <strong>Rango salarial:</strong>
                          <?php if ($inv['salario_min'] && $inv['salario_max']): ?>
                            $<?= number_format($inv['salario_min'], 0, ',', '.') ?> - $<?= number_format($inv['salario_max'], 0, ',', '.') ?>
                          <?php elseif ($inv['salario_min']): ?>
                            Desde $<?= number_format($inv['salario_min'], 0, ',', '.') ?>
                          <?php endif; ?>
                        </small>
                      </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <p class="small text-muted mb-3"><?= htmlspecialchars(substr($inv['descripcion'], 0, 150)) ?>...</p>

                    <!-- Docente info -->
                    <div class="utp-info-notice mb-3">
                      <small>
                        <strong>Invitado por:</strong> <?= htmlspecialchars($inv['docente_nombre'] ?? 'Un docente') ?>
                      </small>
                    </div>

                    <!-- Dates -->
                    <small class="text-muted d-block mb-3">
                      <i class="bi bi-calendar me-1"></i>Invitado: <?= date('d/m/Y', strtotime($inv['fecha_invitacion'])) ?>
                    </small>

                    <!-- Actions -->
                    <?php if ($isPendiente): ?>
                      <div class="d-grid gap-2">
                        <button class="btn btn-utp-green" onclick="aceptarInvitacion(<?= (int)$inv['id'] ?>)">
                          <i class="bi bi-check-lg me-2"></i>Aceptar y Postularme
                        </button>
                        <button class="btn btn-utp-outline-danger" onclick="rechazarInvitacion(<?= (int)$inv['id'] ?>)">
                          <i class="bi bi-x-lg me-2"></i>Rechazar
                        </button>
                      </div>
                    <?php elseif ($inv['estado'] === 'aceptado'): ?>
                      <div class="alert alert-success mb-0">
                        <small><i class="bi bi-check-circle me-1"></i>Ya has aceptado esta invitación y te postulaste.</small>
                      </div>
                    <?php elseif ($inv['estado'] === 'rechazado'): ?>
                      <div class="alert alert-danger mb-0">
                        <small><i class="bi bi-x-circle me-1"></i>Rechazaste esta invitación.</small>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

            </div>
          </div>
        </main>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
  <script>
    async function aceptarInvitacion(invitacionId) {
      if (!confirm('¿Deseas aceptar esta invitación y postularte a la vacante?')) return;
      
      try {
        const response = await fetch('../../public/api/invitaciones.php?action=aceptar', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
          },
          body: JSON.stringify({
            invitacion_id: invitacionId,
            csrf_token: window.UTP_CSRF_TOKEN || ''
          })
        });
        const data = await response.json();
        
        if (data.success) {
          alert('¡Excelente! Has aceptado la invitación y te postulaste a la vacante.');
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'No se pudo aceptar la invitación'));
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }

    async function rechazarInvitacion(invitacionId) {
      if (!confirm('¿Deseas rechazar esta invitación?')) return;
      
      try {
        const response = await fetch('../../public/api/invitaciones.php?action=rechazar', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
          },
          body: JSON.stringify({
            invitacion_id: invitacionId,
            csrf_token: window.UTP_CSRF_TOKEN || ''
          })
        });
        const data = await response.json();
        
        if (data.success) {
          alert('Invitación rechazada.');
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'No se pudo rechazar la invitación'));
        }
      } catch (error) {
        alert('Error: ' + error.message);
      }
    }
  </script>
</body>
</html>
