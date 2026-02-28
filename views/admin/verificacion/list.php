<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../app/models/Usuario.php';
require_once __DIR__ . '/../../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$usuarioModel = new Usuario();
$msgExito = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);

    if ($action === 'verificar' && $uid) {
        $usuarioModel->verifyUser($uid);
        $msgExito = 'Usuario verificado correctamente.';
    }

    if ($action === 'rechazar' && $uid) {
        $motivo = trim($_POST['motivo_rechazo'] ?? 'Sin especificar');
        $usuarioModel->rejectVerification($uid, $motivo);
        $msgExito = 'Verificación rechazada.';
    }
}

// Load data
$pendingStats   = $usuarioModel->countPendingVerification();
$egresadosVerif = $usuarioModel->getAllVerification('egresado');
$docentesVerif  = $usuarioModel->getAllVerification('docente');

// Badge mapping
$verifBadge = [
    'pendiente'  => ['label' => 'Pendiente',  'class' => 'bg-warning bg-opacity-10 text-warning'],
    'verificado' => ['label' => 'Verificado', 'class' => 'bg-success bg-opacity-10 text-success'],
    'rechazado'  => ['label' => 'Rechazado',  'class' => 'bg-danger bg-opacity-10 text-danger'],
];

$totalPending    = (int)($pendingStats['total'] ?? 0);
$pendEgresados   = (int)($pendingStats['egresados'] ?? 0);
$pendDocentes    = (int)($pendingStats['docentes'] ?? 0);
$totalVerificados = 0;
foreach (array_merge($egresadosVerif, $docentesVerif) as $u) {
    if (($u['verificacion_estado'] ?? '') === 'verificado') $totalVerificados++;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificación de Usuarios - Admin UTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'admin', roleLabel: 'Administrador',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'verificacion',
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
            <h1 class="utp-h1 mb-2">Verificación de Usuarios</h1>
            <p class="utp-subtitle mb-0">Valida la identidad de los usuarios registrados en el sistema</p>
          </section>

          <?php if ($msgExito): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
              <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Stats -->
          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= $totalPending ?></div>
                <div class="text-muted small">Pendientes totales</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-patch-check"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= $totalVerificados ?></div>
                <div class="text-muted small">Verificados</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-mortarboard"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= $pendEgresados ?></div>
                <div class="text-muted small">Egresados pendientes</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon red"><i class="bi bi-person-badge"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= $pendDocentes ?></div>
                <div class="text-muted small">Docentes pendientes</div>
              </article>
            </div>
          </section>

          <!-- Tabs -->
          <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
              <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabEgresados" type="button">
                <i class="bi bi-mortarboard me-1"></i>Egresados
                <?php if ($pendEgresados > 0): ?>
                  <span class="badge bg-warning text-dark ms-1"><?= $pendEgresados ?></span>
                <?php endif; ?>
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocentes" type="button">
                <i class="bi bi-person-badge me-1"></i>Docentes
                <?php if ($pendDocentes > 0): ?>
                  <span class="badge bg-warning text-dark ms-1"><?= $pendDocentes ?></span>
                <?php endif; ?>
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Tab: Egresados -->
            <div class="tab-pane fade show active" id="tabEgresados">
              <?php if (empty($egresadosVerif)): ?>
                <div class="utp-card text-center py-5">
                  <p class="text-muted">No hay egresados registrados.</p>
                </div>
              <?php else: ?>
              <!-- Desktop -->
              <div class="utp-card d-none d-lg-block mb-4">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr class="text-muted small">
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Fecha registro</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($egresadosVerif as $u):
                        $uNom = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
                        $uInit = mb_strtoupper(mb_substr($u['nombre']??'',0,1) . mb_substr($u['apellidos']??'',0,1));
                        $vb = $verifBadge[$u['verificacion_estado'] ?? 'pendiente'] ?? $verifBadge['pendiente'];
                        $fecha = $u['fecha_creacion'] ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—';
                      ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="utp-useravatar"><?= $uInit ?></div>
                            <span class="fw-medium"><?= htmlspecialchars($u['usuario'] ?? '—') ?></span>
                          </div>
                        </td>
                        <td><?= htmlspecialchars($uNom) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                        <td class="text-muted small"><?= $fecha ?></td>
                        <td><span class="badge <?= $vb['class'] ?>"><?= $vb['label'] ?></span></td>
                        <td class="text-end">
                          <?php if (($u['verificacion_estado'] ?? '') === 'pendiente'): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick='abrirModalValidar(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                              <i class="bi bi-search me-1"></i>Validar
                            </button>
                          <?php elseif (($u['verificacion_estado'] ?? '') === 'verificado'): ?>
                            <button class="btn btn-sm btn-outline-success" onclick='abrirModalVerificado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                              <i class="bi bi-check-circle me-1"></i>Ver
                            </button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-outline-danger" onclick='abrirModalRechazado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                              <i class="bi bi-x-circle me-1"></i>Ver
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Mobile -->
              <div class="d-lg-none">
                <?php foreach ($egresadosVerif as $u):
                  $uNom = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
                  $uInit = mb_strtoupper(mb_substr($u['nombre']??'',0,1) . mb_substr($u['apellidos']??'',0,1));
                  $vb = $verifBadge[$u['verificacion_estado'] ?? 'pendiente'] ?? $verifBadge['pendiente'];
                  $fecha = $u['fecha_creacion'] ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—';
                ?>
                <div class="utp-card mb-3">
                  <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="utp-useravatar"><?= $uInit ?></div>
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($uNom) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($u['usuario'] ?? '') ?> · <?= $fecha ?></div>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge <?= $vb['class'] ?>"><?= $vb['label'] ?></span>
                    <?php if (($u['verificacion_estado'] ?? '') === 'pendiente'): ?>
                      <button class="btn btn-sm btn-outline-primary" onclick='abrirModalValidar(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Validar</button>
                    <?php elseif (($u['verificacion_estado'] ?? '') === 'verificado'): ?>
                      <button class="btn btn-sm btn-outline-success" onclick='abrirModalVerificado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Ver</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-danger" onclick='abrirModalRechazado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Ver</button>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>

            <!-- Tab: Docentes -->
            <div class="tab-pane fade" id="tabDocentes">
              <?php if (empty($docentesVerif)): ?>
                <div class="utp-card text-center py-5">
                  <p class="text-muted">No hay docentes registrados.</p>
                </div>
              <?php else: ?>
              <div class="utp-card d-none d-lg-block mb-4">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr class="text-muted small">
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Fecha registro</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($docentesVerif as $u):
                        $uNom = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
                        $uInit = mb_strtoupper(mb_substr($u['nombre']??'',0,1) . mb_substr($u['apellidos']??'',0,1));
                        $vb = $verifBadge[$u['verificacion_estado'] ?? 'pendiente'] ?? $verifBadge['pendiente'];
                        $fecha = $u['fecha_creacion'] ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—';
                      ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="utp-useravatar"><?= $uInit ?></div>
                            <span class="fw-medium"><?= htmlspecialchars($u['usuario'] ?? '—') ?></span>
                          </div>
                        </td>
                        <td><?= htmlspecialchars($uNom) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                        <td class="text-muted small"><?= $fecha ?></td>
                        <td><span class="badge <?= $vb['class'] ?>"><?= $vb['label'] ?></span></td>
                        <td class="text-end">
                          <?php if (($u['verificacion_estado'] ?? '') === 'pendiente'): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick='abrirModalValidar(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                              <i class="bi bi-search me-1"></i>Validar
                            </button>
                          <?php elseif (($u['verificacion_estado'] ?? '') === 'verificado'): ?>
                            <button class="btn btn-sm btn-outline-success" onclick='abrirModalVerificado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                              <i class="bi bi-check-circle me-1"></i>Ver
                            </button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-outline-danger" onclick='abrirModalRechazado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                              <i class="bi bi-x-circle me-1"></i>Ver
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- Mobile -->
              <div class="d-lg-none">
                <?php foreach ($docentesVerif as $u):
                  $uNom = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
                  $uInit = mb_strtoupper(mb_substr($u['nombre']??'',0,1) . mb_substr($u['apellidos']??'',0,1));
                  $vb = $verifBadge[$u['verificacion_estado'] ?? 'pendiente'] ?? $verifBadge['pendiente'];
                  $fecha = $u['fecha_creacion'] ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—';
                ?>
                <div class="utp-card mb-3">
                  <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="utp-useravatar"><?= $uInit ?></div>
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($uNom) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($u['usuario'] ?? '') ?> · <?= $fecha ?></div>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge <?= $vb['class'] ?>"><?= $vb['label'] ?></span>
                    <?php if (($u['verificacion_estado'] ?? '') === 'pendiente'): ?>
                      <button class="btn btn-sm btn-outline-primary" onclick='abrirModalValidar(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Validar</button>
                    <?php elseif (($u['verificacion_estado'] ?? '') === 'verificado'): ?>
                      <button class="btn btn-sm btn-outline-success" onclick='abrirModalVerificado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Ver</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-danger" onclick='abrirModalRechazado(<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Ver</button>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

  <!-- Modal: Validar usuario pendiente -->
  <div class="modal fade" id="modalValidar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Validar usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="utp-useravatar utp-useravatar-lg" id="valInitials"></div>
            <div>
              <div class="fw-semibold" id="valNombre"></div>
              <div class="text-muted small" id="valUsuario"></div>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <span class="text-muted small d-block">Email</span>
              <span id="valEmail"></span>
            </div>
            <div class="col-6">
              <span class="text-muted small d-block">Tipo</span>
              <span id="valTipo"></span>
            </div>
            <div class="col-6">
              <span class="text-muted small d-block">Registrado</span>
              <span id="valFecha"></span>
            </div>
            <div class="col-6">
              <span class="text-muted small d-block">Matrícula/ID</span>
              <span id="valMatricula"></span>
            </div>
          </div>
          <hr>
          <div class="mb-3">
            <label class="form-label fw-medium">Motivo de rechazo (si aplica)</label>
            <textarea class="form-control utp-input" id="valMotivo" rows="3" placeholder="Escribe el motivo de rechazo..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <form method="POST" class="d-inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="rechazar">
            <input type="hidden" name="user_id" id="valRejectId">
            <input type="hidden" name="motivo_rechazo" id="valRejectMotivo">
            <button type="submit" class="btn btn-danger" onclick="document.getElementById('valRejectMotivo').value = document.getElementById('valMotivo').value;">
              <i class="bi bi-x-circle me-1"></i>Rechazar
            </button>
          </form>
          <form method="POST" class="d-inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="verificar">
            <input type="hidden" name="user_id" id="valVerifyId">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-patch-check me-1"></i>Verificar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Ver usuario verificado -->
  <div class="modal fade" id="modalVerificado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success bg-opacity-10">
          <h5 class="modal-title text-success"><i class="bi bi-patch-check me-2"></i>Usuario verificado</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="utp-useravatar utp-useravatar-lg" id="verInitials"></div>
            <div>
              <div class="fw-semibold" id="verNombre"></div>
              <div class="text-muted small" id="verUsuario"></div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-6"><span class="text-muted small d-block">Email</span><span id="verEmail"></span></div>
            <div class="col-6"><span class="text-muted small d-block">Tipo</span><span id="verTipo"></span></div>
            <div class="col-6"><span class="text-muted small d-block">Verificado el</span><span id="verFechaVerif"></span></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Ver usuario rechazado -->
  <div class="modal fade" id="modalRechazado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger bg-opacity-10">
          <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Verificación rechazada</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="utp-useravatar utp-useravatar-lg" id="rejInitials"></div>
            <div>
              <div class="fw-semibold" id="rejNombre"></div>
              <div class="text-muted small" id="rejUsuario"></div>
            </div>
          </div>
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Motivo de rechazo:</strong> <span id="rejMotivo"></span>
          </div>
        </div>
        <div class="modal-footer">
          <form method="POST" class="d-inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="verificar">
            <input type="hidden" name="user_id" id="rejVerifyId">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-patch-check me-1"></i>Verificar ahora
            </button>
          </form>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../../public/assets/js/shared/app.js"></script>
  <script>
    function formatDate(d) {
      if (!d) return '—';
      var dt = new Date(d);
      return dt.toLocaleDateString('es-MX');
    }

    // --- Modal: Validar pendiente ---
    function abrirModalValidar(u) {
      var nom = (u.nombre || '') + ' ' + (u.apellidos || '');
      var ini = ((u.nombre||'').charAt(0) + (u.apellidos||'').charAt(0)).toUpperCase();
      document.getElementById('valInitials').textContent = ini;
      document.getElementById('valNombre').textContent = nom.trim();
      document.getElementById('valUsuario').textContent = u.usuario || '—';
      document.getElementById('valEmail').textContent = u.email || '—';
      document.getElementById('valTipo').textContent = u.tipo_usuario || '—';
      document.getElementById('valFecha').textContent = formatDate(u.fecha_creacion);
      document.getElementById('valMatricula').textContent = u.matricula || u.identificador || '—';
      document.getElementById('valVerifyId').value = u.id;
      document.getElementById('valRejectId').value = u.id;
      document.getElementById('valMotivo').value = '';
      new bootstrap.Modal(document.getElementById('modalValidar')).show();
    }

    // --- Modal: Ver verificado ---
    function abrirModalVerificado(u) {
      var nom = (u.nombre || '') + ' ' + (u.apellidos || '');
      var ini = ((u.nombre||'').charAt(0) + (u.apellidos||'').charAt(0)).toUpperCase();
      document.getElementById('verInitials').textContent = ini;
      document.getElementById('verNombre').textContent = nom.trim();
      document.getElementById('verUsuario').textContent = u.usuario || '—';
      document.getElementById('verEmail').textContent = u.email || '—';
      document.getElementById('verTipo').textContent = u.tipo_usuario || '—';
      document.getElementById('verFechaVerif').textContent = formatDate(u.verificacion_fecha);
      new bootstrap.Modal(document.getElementById('modalVerificado')).show();
    }

    // --- Modal: Ver rechazado ---
    function abrirModalRechazado(u) {
      var nom = (u.nombre || '') + ' ' + (u.apellidos || '');
      var ini = ((u.nombre||'').charAt(0) + (u.apellidos||'').charAt(0)).toUpperCase();
      document.getElementById('rejInitials').textContent = ini;
      document.getElementById('rejNombre').textContent = nom.trim();
      document.getElementById('rejUsuario').textContent = u.usuario || '—';
      document.getElementById('rejMotivo').textContent = u.verificacion_motivo_rechazo || 'No especificado';
      document.getElementById('rejVerifyId').value = u.id;
      new bootstrap.Modal(document.getElementById('modalRechazado')).show();
    }
  </script>
</body>
</html>
