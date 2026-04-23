<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../app/models/Oferta.php';
require_once __DIR__ . '/../../../app/models/Notificacion.php';
require_once __DIR__ . '/../../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$ofertaModel = new Oferta();
$msgExito = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $ofertaId = (int)($_POST['oferta_id'] ?? 0);

    if ($action === 'aprobar' && $ofertaId) {
        $oferta = $ofertaModel->getById($ofertaId);
        $ofertaModel->approve($ofertaId, $_SESSION['usuario_id']);
        $msgExito = 'Oferta aprobada y publicada correctamente.';

        // Notificar al creador + todos los egresados
        if ($oferta) {
            $notifModel = new Notificacion();
            $notifModel->onOfertaAprobada($ofertaId, $oferta['titulo'], $oferta['id_usuario_creador']);
        }
    }

    if ($action === 'rechazar' && $ofertaId) {
        $oferta = $ofertaModel->getById($ofertaId);
        $razon = trim($_POST['motivo_rechazo'] ?? 'Sin especificar');
        $ofertaModel->reject($ofertaId, $razon);
        $msgExito = 'Oferta rechazada.';

        // Notificar al creador
        if ($oferta) {
            $notifModel = new Notificacion();
            $notifModel->onOfertaRechazada($oferta['titulo'], $oferta['id_usuario_creador'], $razon);
        }
    }
}

// Load data
$stats   = $ofertaModel->getModeracionStats();
$ofertas = $ofertaModel->getAllForModeration();

// Badge mapping
$estadoBadge = [
    'pendiente_aprobacion' => ['label' => 'Pendiente',  'class' => 'bg-warning bg-opacity-10 text-warning'],
    'aprobada'             => ['label' => 'Aprobada',   'class' => 'bg-success bg-opacity-10 text-success'],
    'rechazada'            => ['label' => 'Rechazada',  'class' => 'bg-danger bg-opacity-10 text-danger'],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moderación de Ofertas - Admin UTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'admin', roleLabel: 'Administrador',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'moderacion',
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
            <h1 class="utp-h1 mb-2">Moderación de Ofertas</h1>
            <p class="utp-subtitle mb-0">Revisa, aprueba o rechaza las ofertas laborales publicadas</p>
          </section>

          <?php if ($msgExito): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
              <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Stats -->
          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-12 col-sm-4">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)($stats['pendientes'] ?? 0) ?></div>
                <div class="text-muted small">Pendientes de revisión</div>
              </article>
            </div>
            <div class="col-12 col-sm-4">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-check-circle"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)($stats['aprobadas'] ?? 0) ?></div>
                <div class="text-muted small">Aprobadas</div>
              </article>
            </div>
            <div class="col-12 col-sm-4">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon red"><i class="bi bi-x-circle"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)($stats['rechazadas'] ?? 0) ?></div>
                <div class="text-muted small">Rechazadas</div>
              </article>
            </div>
          </section>

          <!-- Filtros -->
          <div class="utp-card mb-3">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label utp-label">Buscar</label>
                <div class="position-relative">
                  <i class="bi bi-search utp-search-icon"></i>
                  <input type="text" class="form-control utp-input utp-search-input" id="searchInput" placeholder="Título, empresa o creador...">
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label utp-label">Estado</label>
                <select class="form-select utp-select" id="filterEstado">
                  <option value="">Todos los estados</option>
                  <option value="pendiente_aprobacion">Pendientes</option>
                  <option value="aprobada">Aprobadas</option>
                  <option value="rechazada">Rechazadas</option>
                </select>
              </div>
            </div>
          </div>

          <p class="text-muted small mb-3" id="counterText">Mostrando <?= count($ofertas) ?> oferta<?= count($ofertas) !== 1 ? 's' : '' ?></p>

          <?php if (empty($ofertas)): ?>
            <div class="utp-card text-center py-5">
              <div class="utp-miniicon green mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;">
                <i class="bi bi-check-all" style="font-size:28px;"></i>
              </div>
              <h3 style="font-size:20px; font-weight:600;">Sin ofertas</h3>
              <p class="text-muted">No hay ofertas registradas en el sistema.</p>
            </div>
          <?php else: ?>

          <!-- Desktop Table -->
          <div class="utp-card d-none d-lg-block mb-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr class="text-muted small">
                    <th>ID</th>
                    <th>Título</th>
                    <th>Empresa</th>
                    <th>Publicó</th>
                    <th>Fecha</th>
                    <th>Expira</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ofertas as $o):
                    $eb = $estadoBadge[$o['estado']] ?? $estadoBadge['pendiente_aprobacion'];
                    $fechaCreacion = $o['fecha_creacion'] ? date('d/m/Y', strtotime($o['fecha_creacion'])) : '—';
                    $fechaExpira   = $o['fecha_expiracion'] ? date('d/m/Y', strtotime($o['fecha_expiracion'])) : '—';
                    $creador       = htmlspecialchars(trim($o['creador'] ?? ''));
                    $rolCreador    = $o['creador_rol'] ?? '';
                  ?>
                  <tr class="oferta-row"
                      data-titulo="<?= strtolower(htmlspecialchars($o['titulo'] ?? '')) ?>"
                      data-empresa="<?= strtolower(htmlspecialchars($o['empresa'] ?? '')) ?>"
                      data-creador="<?= strtolower($creador) ?>"
                      data-estado="<?= htmlspecialchars($o['estado']) ?>">
                    <td class="text-muted">#<?= (int)$o['id'] ?></td>
                    <td class="fw-medium"><?= htmlspecialchars($o['titulo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($o['empresa'] ?? '—') ?></td>
                    <td>
                      <?= $creador ?>
                      <span class="text-muted small">(<?= $rolCreador ?>)</span>
                    </td>
                    <td class="text-muted small"><?= $fechaCreacion ?></td>
                    <td class="text-muted small"><?= $fechaExpira ?></td>
                    <td><span class="badge <?= $eb['class'] ?>"><?= $eb['label'] ?></span></td>
                    <td class="text-end">
                      <?php if ($o['estado'] === 'pendiente_aprobacion'): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick='abrirModalRevisar(<?= json_encode($o, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                          <i class="bi bi-eye me-1"></i>Revisar
                        </button>
                      <?php elseif ($o['estado'] === 'aprobada'): ?>
                        <button class="btn btn-sm btn-outline-success" onclick='abrirModalAprobada(<?= json_encode($o, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                          <i class="bi bi-info-circle me-1"></i>Ver
                        </button>
                      <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger" onclick='abrirModalRechazada(<?= json_encode($o, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                          <i class="bi bi-info-circle me-1"></i>Ver
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile Cards -->
          <div class="d-lg-none">
            <?php foreach ($ofertas as $o):
              $eb = $estadoBadge[$o['estado']] ?? $estadoBadge['pendiente_aprobacion'];
              $fechaCreacion = $o['fecha_creacion'] ? date('d/m/Y', strtotime($o['fecha_creacion'])) : '—';
              $creador = htmlspecialchars(trim($o['creador'] ?? ''));
            ?>
            <div class="utp-card mb-3 oferta-card"
                 data-titulo="<?= strtolower(htmlspecialchars($o['titulo'] ?? '')) ?>"
                 data-empresa="<?= strtolower(htmlspecialchars($o['empresa'] ?? '')) ?>"
                 data-creador="<?= strtolower($creador) ?>"
                 data-estado="<?= htmlspecialchars($o['estado']) ?>">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="fw-semibold mb-0"><?= htmlspecialchars($o['titulo'] ?? '') ?></h6>
                <span class="badge <?= $eb['class'] ?>"><?= $eb['label'] ?></span>
              </div>
              <div class="text-muted small mb-1"><i class="bi bi-building me-1"></i><?= htmlspecialchars($o['empresa'] ?? '—') ?></div>
              <div class="text-muted small mb-2"><i class="bi bi-person me-1"></i><?= $creador ?> · <?= $fechaCreacion ?></div>
              <div>
                <?php if ($o['estado'] === 'pendiente_aprobacion'): ?>
                  <button class="btn btn-sm btn-outline-primary w-100" onclick='abrirModalRevisar(<?= json_encode($o, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                    <i class="bi bi-eye me-1"></i>Revisar
                  </button>
                <?php elseif ($o['estado'] === 'aprobada'): ?>
                  <button class="btn btn-sm btn-outline-success w-100" onclick='abrirModalAprobada(<?= json_encode($o, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                    <i class="bi bi-info-circle me-1"></i>Ver detalles
                  </button>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-danger w-100" onclick='abrirModalRechazada(<?= json_encode($o, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                    <i class="bi bi-info-circle me-1"></i>Ver detalles
                  </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>

  <!-- Modal: Revisar oferta pendiente -->
  <div class="modal fade" id="modalRevisar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Revisar oferta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <h6 class="fw-bold" id="revTitulo"></h6>
            <div class="text-muted small" id="revEmpresa"></div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <span class="text-muted small d-block">Ubicación</span>
              <span id="revUbicacion">—</span>
            </div>
            <div class="col-sm-6">
              <span class="text-muted small d-block">Modalidad</span>
              <span id="revModalidad">—</span>
            </div>
            <div class="col-sm-6">
              <span class="text-muted small d-block">Tipo contrato</span>
              <span id="revContrato">—</span>
            </div>
            <div class="col-sm-6">
              <span class="text-muted small d-block">Salario</span>
              <span id="revSalario">—</span>
            </div>
          </div>
          <div class="mb-3">
            <span class="text-muted small d-block">Descripción</span>
            <p id="revDescripcion"></p>
          </div>
          <div class="mb-3">
            <span class="text-muted small d-block">Requisitos</span>
            <p id="revRequisitos"></p>
          </div>
          <div class="mb-3">
            <span class="text-muted small d-block">Publicado por</span>
            <span id="revCreador"></span>
          </div>
          <hr>
          <div class="mb-3">
            <label class="form-label fw-medium">Motivo de rechazo (si aplica)</label>
            <select class="form-select utp-select" id="motivoRechazo">
              <option value="">Selecciona un motivo...</option>
              <option value="Información incompleta">Información incompleta</option>
              <option value="Contenido inapropiado">Contenido inapropiado</option>
              <option value="Oferta duplicada">Oferta duplicada</option>
              <option value="No cumple políticas">No cumple políticas de la UTP</option>
              <option value="Datos incorrectos">Datos incorrectos</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <form method="POST" class="d-inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="rechazar">
            <input type="hidden" name="oferta_id" id="revRejectId">
            <input type="hidden" name="motivo_rechazo" id="revRejectMotivo">
            <button type="submit" class="btn btn-danger" onclick="document.getElementById('revRejectMotivo').value = document.getElementById('motivoRechazo').value;">
              <i class="bi bi-x-circle me-1"></i>Rechazar
            </button>
          </form>
          <form method="POST" class="d-inline">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="aprobar">
            <input type="hidden" name="oferta_id" id="revApproveId">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-check-circle me-1"></i>Aprobar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Ver oferta aprobada -->
  <div class="modal fade" id="modalAprobada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-success bg-opacity-10">
          <h5 class="modal-title text-success"><i class="bi bi-check-circle me-2"></i>Oferta aprobada</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <h6 class="fw-bold" id="aprTitulo"></h6>
          <div class="text-muted small mb-3" id="aprEmpresa"></div>
          <div class="row g-3 mb-3">
            <div class="col-sm-6"><span class="text-muted small d-block">Ubicación</span><span id="aprUbicacion">—</span></div>
            <div class="col-sm-6"><span class="text-muted small d-block">Modalidad</span><span id="aprModalidad">—</span></div>
          </div>
          <div class="mb-3"><span class="text-muted small d-block">Descripción</span><p id="aprDescripcion"></p></div>
          <div class="mb-3"><span class="text-muted small d-block">Publicado por</span><span id="aprCreador"></span></div>
          <div class="mb-3"><span class="text-muted small d-block">Postulantes</span><span id="aprPostulantes"></span></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Ver oferta rechazada -->
  <div class="modal fade" id="modalRechazada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-danger bg-opacity-10">
          <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Oferta rechazada</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <h6 class="fw-bold" id="rejTitulo"></h6>
          <div class="text-muted small mb-3" id="rejEmpresa"></div>
          <div class="mb-3"><span class="text-muted small d-block">Descripción</span><p id="rejDescripcion"></p></div>
          <div class="mb-3"><span class="text-muted small d-block">Publicado por</span><span id="rejCreador"></span></div>
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Motivo de rechazo:</strong> <span id="rejMotivo"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <script>
    // --- Modal: Revisar pendiente ---
    function abrirModalRevisar(o) {
      document.getElementById('revTitulo').textContent = o.titulo || '';
      document.getElementById('revEmpresa').textContent = o.empresa || '—';
      document.getElementById('revUbicacion').textContent = o.ubicacion || '—';
      document.getElementById('revModalidad').textContent = o.modalidad || '—';
      document.getElementById('revContrato').textContent = o.tipo_contrato || '—';
      document.getElementById('revSalario').textContent = o.salario_minimo || o.salario_maximo ?
        ('$' + (o.salario_minimo || '?') + ' - $' + (o.salario_maximo || '?')) : 'No especificado';
      document.getElementById('revDescripcion').textContent = o.descripcion || '—';
      document.getElementById('revRequisitos').textContent = o.requisitos || '—';
      document.getElementById('revCreador').textContent = (o.creador || '') + ' (' + (o.creador_rol || '') + ')';
      document.getElementById('revApproveId').value = o.id;
      document.getElementById('revRejectId').value = o.id;
      document.getElementById('motivoRechazo').value = '';
      new bootstrap.Modal(document.getElementById('modalRevisar')).show();
    }

    // --- Modal: Ver aprobada ---
    function abrirModalAprobada(o) {
      document.getElementById('aprTitulo').textContent = o.titulo || '';
      document.getElementById('aprEmpresa').textContent = o.empresa || '—';
      document.getElementById('aprUbicacion').textContent = o.ubicacion || '—';
      document.getElementById('aprModalidad').textContent = o.modalidad || '—';
      document.getElementById('aprDescripcion').textContent = o.descripcion || '—';
      document.getElementById('aprCreador').textContent = (o.creador || '') + ' (' + (o.creador_rol || '') + ')';
      document.getElementById('aprPostulantes').textContent = (o.postulantes_count || 0) + ' postulante(s)';
      new bootstrap.Modal(document.getElementById('modalAprobada')).show();
    }

    // --- Modal: Ver rechazada ---
    function abrirModalRechazada(o) {
      document.getElementById('rejTitulo').textContent = o.titulo || '';
      document.getElementById('rejEmpresa').textContent = o.empresa || '—';
      document.getElementById('rejDescripcion').textContent = o.descripcion || '—';
      document.getElementById('rejCreador').textContent = (o.creador || '') + ' (' + (o.creador_rol || '') + ')';
      document.getElementById('rejMotivo').textContent = o.razon_rechazo || 'No especificado';
      new bootstrap.Modal(document.getElementById('modalRechazada')).show();
    }

    // --- Filtering ---
    function applyFilters() {
      var q = document.getElementById('searchInput').value.toLowerCase();
      var estado = document.getElementById('filterEstado').value;
      var visible = 0;

      document.querySelectorAll('.oferta-row').forEach(function(row) {
        var match = filterMatch(row, q, estado);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      document.querySelectorAll('.oferta-card').forEach(function(card) {
        card.style.display = filterMatch(card, q, estado) ? '' : 'none';
      });
      document.getElementById('counterText').textContent = 'Mostrando ' + visible + ' oferta' + (visible !== 1 ? 's' : '');
    }

    function filterMatch(el, q, estado) {
      var titulo  = el.getAttribute('data-titulo') || '';
      var empresa = el.getAttribute('data-empresa') || '';
      var creador = el.getAttribute('data-creador') || '';
      var elEst   = el.getAttribute('data-estado') || '';
      var matchSearch = !q || titulo.includes(q) || empresa.includes(q) || creador.includes(q);
      var matchEstado = !estado || elEst === estado;
      return matchSearch && matchEstado;
    }

    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterEstado').addEventListener('change', applyFilters);
  </script>
</body>
</html>
