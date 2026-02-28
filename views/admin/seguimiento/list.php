<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../app/models/Egresado.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$egresadoModel = new Egresado();
$stats     = $egresadoModel->getSeguimientoStats();
$egresados = $egresadoModel->getAllSeguimiento();

// Collect unique values for filters
$generaciones = [];
$especialidades = [];
foreach ($egresados as $e) {
    if (!empty($e['generacion'])) $generaciones[$e['generacion']] = true;
    if (!empty($e['especialidad'])) $especialidades[$e['especialidad']] = true;
}
krsort($generaciones);
ksort($especialidades);

$modalidadLabel = ['presencial'=>'Presencial','hibrido'=>'Híbrido','remoto'=>'Remoto'];
$contratoLabel  = ['indefinido'=>'Indefinido','temporal'=>'Temporal','proyecto'=>'Por proyecto','honorarios'=>'Honorarios'];
$jornadaLabel   = ['completo'=>'Tiempo completo','parcial'=>'Medio tiempo','freelance'=>'Freelance'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguimiento de Egresados - Admin UTP</title>
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
      currentPage: 'seguimiento',
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
            <h1 class="utp-h1 mb-2">Seguimiento de Egresados</h1>
            <p class="utp-subtitle mb-0">Consulta la situación laboral de los egresados de TI</p>
          </section>

          <!-- Privacy alert -->
          <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
            <i class="bi bi-shield-lock flex-shrink-0 mt-1"></i>
            <div>
              <strong>Información confidencial.</strong> Los datos de seguimiento (salarios, contratos, situación laboral) son exclusivos para administradores. No compartir ni exportar sin autorización.
            </div>
          </div>

          <!-- Stats -->
          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-people"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['total'] ?></div>
                <div class="text-muted small">Total egresados</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-briefcase"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['empleados'] ?></div>
                <div class="text-muted small">Empleados</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-laptop"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['en_ti'] ?></div>
                <div class="text-muted small">Trabajan en TI</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon red"><i class="bi bi-graph-up"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= $stats['tasa_ti'] ?>%</div>
                <div class="text-muted small">Tasa en TI</div>
              </article>
            </div>
          </section>

          <!-- Filters -->
          <div class="utp-card mb-3">
            <div class="row g-3">
              <div class="col-12 col-md-3">
                <label class="form-label utp-label">Buscar</label>
                <div class="position-relative">
                  <i class="bi bi-search utp-search-icon"></i>
                  <input type="text" class="form-control utp-input utp-search-input" id="searchInput" placeholder="Nombre, matrícula...">
                </div>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label utp-label">Generación</label>
                <select class="form-select utp-select" id="filterGen">
                  <option value="">Todas</option>
                  <?php foreach ($generaciones as $g => $_): ?>
                    <option value="<?= (int)$g ?>"><?= (int)$g ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label utp-label">Especialidad</label>
                <select class="form-select utp-select" id="filterEsp">
                  <option value="">Todas</option>
                  <?php foreach ($especialidades as $esp => $_): ?>
                    <option value="<?= htmlspecialchars($esp) ?>"><?= htmlspecialchars($esp) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label utp-label">¿Trabaja?</label>
                <select class="form-select utp-select" id="filterTrabaja">
                  <option value="">Todos</option>
                  <option value="1">Sí</option>
                  <option value="0">No</option>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label utp-label">¿En TI?</label>
                <select class="form-select utp-select" id="filterTI">
                  <option value="">Todos</option>
                  <option value="1">Sí</option>
                  <option value="0">No</option>
                </select>
              </div>
            </div>
          </div>

          <p class="text-muted small mb-3" id="counterText">Mostrando <?= count($egresados) ?> egresado<?= count($egresados) !== 1 ? 's' : '' ?></p>

          <?php if (empty($egresados)): ?>
            <div class="utp-card text-center py-5">
              <p class="text-muted">No hay egresados registrados.</p>
            </div>
          <?php else: ?>

          <!-- Desktop Table -->
          <div class="utp-card d-none d-lg-block mb-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                <thead>
                  <tr class="text-muted small">
                    <th>Nombre</th>
                    <th>Matrícula</th>
                    <th>Generación</th>
                    <th>Especialidad</th>
                    <th>¿Trabaja?</th>
                    <th>¿En TI?</th>
                    <th>Empresa</th>
                    <th>Puesto</th>
                    <th>Modalidad</th>
                    <th>Contrato</th>
                    <th>Salario</th>
                    <th class="text-end">Detalle</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($egresados as $e):
                    $eName = trim(($e['nombre_usuario'] ?? '') . ' ' . ($e['apellidos'] ?? ''));
                    $eInit = mb_strtoupper(mb_substr($e['nombre_usuario']??'',0,1) . mb_substr($e['apellidos']??'',0,1));
                    $trabaja = (int)($e['trabaja_actualmente'] ?? 0);
                    $enTI = (int)($e['trabaja_en_ti'] ?? 0);
                  ?>
                  <tr class="egresado-row"
                      data-name="<?= strtolower(htmlspecialchars($eName)) ?>"
                      data-matricula="<?= strtolower(htmlspecialchars($e['matricula'] ?? '')) ?>"
                      data-gen="<?= htmlspecialchars($e['generacion'] ?? '') ?>"
                      data-esp="<?= strtolower(htmlspecialchars($e['especialidad'] ?? '')) ?>"
                      data-trabaja="<?= $trabaja ?>"
                      data-ti="<?= $enTI ?>">
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="utp-useravatar" style="width:32px;height:32px;font-size:12px;"><?= $eInit ?></div>
                        <span class="fw-medium"><?= htmlspecialchars($eName) ?></span>
                      </div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($e['matricula'] ?? '—') ?></td>
                    <td class="text-muted"><?= htmlspecialchars($e['generacion'] ?? '—') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($e['especialidad'] ?? '—') ?></td>
                    <td>
                      <?php if ($trabaja): ?>
                        <span class="badge bg-success bg-opacity-10 text-success">Sí</span>
                      <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger">No</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($enTI): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Sí</span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">No</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($e['empresa_actual'] ?? '—') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($e['puesto_actual'] ?? '—') ?></td>
                    <td class="text-muted small"><?= $modalidadLabel[$e['modalidad_trabajo'] ?? ''] ?? '—' ?></td>
                    <td class="text-muted small"><?= $contratoLabel[$e['tipo_contrato'] ?? ''] ?? '—' ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($e['rango_salarial'] ?? '—') ?></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary" onclick='abrirDetalle(<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-eye"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile Cards -->
          <div class="d-lg-none">
            <?php foreach ($egresados as $e):
              $eName = trim(($e['nombre_usuario'] ?? '') . ' ' . ($e['apellidos'] ?? ''));
              $eInit = mb_strtoupper(mb_substr($e['nombre_usuario']??'',0,1) . mb_substr($e['apellidos']??'',0,1));
              $trabaja = (int)($e['trabaja_actualmente'] ?? 0);
              $enTI = (int)($e['trabaja_en_ti'] ?? 0);
            ?>
            <div class="utp-card mb-3 egresado-card"
                 data-name="<?= strtolower(htmlspecialchars($eName)) ?>"
                 data-matricula="<?= strtolower(htmlspecialchars($e['matricula'] ?? '')) ?>"
                 data-gen="<?= htmlspecialchars($e['generacion'] ?? '') ?>"
                 data-esp="<?= strtolower(htmlspecialchars($e['especialidad'] ?? '')) ?>"
                 data-trabaja="<?= $trabaja ?>"
                 data-ti="<?= $enTI ?>">
              <div class="d-flex align-items-center gap-3 mb-2">
                <div class="utp-useravatar"><?= $eInit ?></div>
                <div class="flex-grow-1">
                  <div class="fw-semibold"><?= htmlspecialchars($eName) ?></div>
                  <div class="text-muted small">Mat: <?= htmlspecialchars($e['matricula'] ?? '—') ?> · Gen: <?= htmlspecialchars($e['generacion'] ?? '—') ?></div>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 mb-2">
                <?php if ($trabaja): ?>
                  <span class="badge bg-success bg-opacity-10 text-success">Trabaja</span>
                <?php else: ?>
                  <span class="badge bg-danger bg-opacity-10 text-danger">No trabaja</span>
                <?php endif; ?>
                <?php if ($enTI): ?>
                  <span class="badge bg-primary bg-opacity-10 text-primary">En TI</span>
                <?php endif; ?>
              </div>
              <?php if ($trabaja && !empty($e['empresa_actual'])): ?>
                <div class="text-muted small mb-2">
                  <i class="bi bi-building me-1"></i><?= htmlspecialchars($e['empresa_actual']) ?>
                  <?php if (!empty($e['puesto_actual'])): ?> · <?= htmlspecialchars($e['puesto_actual']) ?><?php endif; ?>
                </div>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-primary w-100" onclick='abrirDetalle(<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                <i class="bi bi-eye me-1"></i>Ver detalle
              </button>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>

  <!-- Modal: Detalle de egresado -->
  <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle de seguimiento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Personal -->
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="utp-useravatar utp-useravatar-lg" id="detInit"></div>
            <div>
              <div class="fw-bold fs-5" id="detNombre"></div>
              <div class="text-muted" id="detUsuario"></div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-sm-4"><span class="text-muted small d-block">Matrícula</span><span id="detMatricula">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Generación</span><span id="detGen">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Especialidad</span><span id="detEsp">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Email</span><span id="detEmail">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Teléfono</span><span id="detTelefono">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">CURP</span><span id="detCurp">—</span></div>
          </div>

          <hr>

          <!-- Laboral -->
          <h6 class="fw-bold mb-3"><i class="bi bi-briefcase me-2"></i>Situación Laboral</h6>
          <div class="row g-3 mb-4">
            <div class="col-sm-4"><span class="text-muted small d-block">¿Trabaja actualmente?</span><span id="detTrabaja">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">¿En área de TI?</span><span id="detTI">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Empresa</span><span id="detEmpresa">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Puesto</span><span id="detPuesto">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Ubicación</span><span id="detUbicacion">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Fecha inicio</span><span id="detFechaInicio">—</span></div>
          </div>

          <hr>

          <!-- Contrato -->
          <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-2"></i>Contrato e Ingresos</h6>
          <div class="row g-3 mb-4">
            <div class="col-sm-4"><span class="text-muted small d-block">Modalidad</span><span id="detModalidad">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Jornada</span><span id="detJornada">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Tipo contrato</span><span id="detContrato">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Rango salarial</span><span id="detSalario">—</span></div>
            <div class="col-sm-4"><span class="text-muted small d-block">Prestaciones</span><span id="detPrestaciones">—</span></div>
          </div>

          <hr>

          <!-- Experiencia -->
          <h6 class="fw-bold mb-3"><i class="bi bi-award me-2"></i>Experiencia</h6>
          <div class="row g-3 mb-3">
            <div class="col-sm-4"><span class="text-muted small d-block">Años en TI</span><span id="detAnosExp">—</span></div>
            <div class="col-12"><span class="text-muted small d-block">Descripción de experiencia</span><p id="detDescExp" class="mb-0">—</p></div>
          </div>

          <div class="text-muted small mt-3" id="detFechaActualizacion"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../../public/assets/js/shared/app.js"></script>
  <script>
    var modalidadMap = <?= json_encode($modalidadLabel) ?>;
    var contratoMap  = <?= json_encode($contratoLabel) ?>;
    var jornadaMap   = <?= json_encode($jornadaLabel) ?>;

    function formatDate(d) {
      if (!d) return '—';
      return new Date(d).toLocaleDateString('es-MX');
    }

    function abrirDetalle(e) {
      var nom = (e.nombre_usuario || '') + ' ' + (e.apellidos || '');
      var ini = ((e.nombre_usuario||'').charAt(0) + (e.apellidos||'').charAt(0)).toUpperCase();

      document.getElementById('detInit').textContent = ini;
      document.getElementById('detNombre').textContent = nom.trim();
      document.getElementById('detUsuario').textContent = e.usuario || '';
      document.getElementById('detMatricula').textContent = e.matricula || '—';
      document.getElementById('detGen').textContent = e.generacion || '—';
      document.getElementById('detEsp').textContent = e.especialidad || '—';
      document.getElementById('detEmail').textContent = e.email || '—';
      document.getElementById('detTelefono').textContent = e.telefono || '—';
      document.getElementById('detCurp').textContent = e.curp || '—';

      document.getElementById('detTrabaja').textContent = e.trabaja_actualmente == 1 ? 'Sí' : 'No';
      document.getElementById('detTI').textContent = e.trabaja_en_ti == 1 ? 'Sí' : 'No';
      document.getElementById('detEmpresa').textContent = e.empresa_actual || '—';
      document.getElementById('detPuesto').textContent = e.puesto_actual || '—';
      document.getElementById('detUbicacion').textContent = e.ubicacion_trabajo || '—';
      document.getElementById('detFechaInicio').textContent = formatDate(e.fecha_inicio_empleo);

      document.getElementById('detModalidad').textContent = modalidadMap[e.modalidad_trabajo] || '—';
      document.getElementById('detJornada').textContent = jornadaMap[e.jornada_trabajo] || '—';
      document.getElementById('detContrato').textContent = contratoMap[e.tipo_contrato] || '—';
      document.getElementById('detSalario').textContent = e.rango_salarial || '—';
      document.getElementById('detPrestaciones').textContent = e.prestaciones || '—';

      document.getElementById('detAnosExp').textContent = e.anos_experiencia_ti || '—';
      document.getElementById('detDescExp').textContent = e.descripcion_experiencia || '—';

      var fechaAct = e.fecha_actualizacion_seguimiento ? 'Última actualización: ' + formatDate(e.fecha_actualizacion_seguimiento) : '';
      document.getElementById('detFechaActualizacion').textContent = fechaAct;

      new bootstrap.Modal(document.getElementById('modalDetalle')).show();
    }

    // --- Filtering ---
    function applyFilters() {
      var q = document.getElementById('searchInput').value.toLowerCase();
      var gen = document.getElementById('filterGen').value;
      var esp = document.getElementById('filterEsp').value.toLowerCase();
      var trabaja = document.getElementById('filterTrabaja').value;
      var ti = document.getElementById('filterTI').value;
      var visible = 0;

      document.querySelectorAll('.egresado-row').forEach(function(row) {
        var match = filterMatch(row, q, gen, esp, trabaja, ti);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      document.querySelectorAll('.egresado-card').forEach(function(card) {
        card.style.display = filterMatch(card, q, gen, esp, trabaja, ti) ? '' : 'none';
      });

      document.getElementById('counterText').textContent = 'Mostrando ' + visible + ' egresado' + (visible !== 1 ? 's' : '');
    }

    function filterMatch(el, q, gen, esp, trabaja, ti) {
      var name = el.getAttribute('data-name') || '';
      var mat  = el.getAttribute('data-matricula') || '';
      var eGen = el.getAttribute('data-gen') || '';
      var eEsp = el.getAttribute('data-esp') || '';
      var eTr  = el.getAttribute('data-trabaja') || '';
      var eTi  = el.getAttribute('data-ti') || '';

      return (!q || name.includes(q) || mat.includes(q)) &&
             (!gen || eGen === gen) &&
             (!esp || eEsp === esp) &&
             (trabaja === '' || eTr === trabaja) &&
             (ti === '' || eTi === ti);
    }

    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterGen').addEventListener('change', applyFilters);
    document.getElementById('filterEsp').addEventListener('change', applyFilters);
    document.getElementById('filterTrabaja').addEventListener('change', applyFilters);
    document.getElementById('filterTI').addEventListener('change', applyFilters);
  </script>
</body>
</html>
