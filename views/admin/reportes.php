<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';

$nombre    = $_SESSION['usuario_nombre'] ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellidos, 0, 1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$ofertaModel = new Oferta();
$egresadoModel = new Egresado();
$postulacionModel = new Postulacion();

$offerSummary = $ofertaModel->getReportSummary();
$egresadoSummary = $egresadoModel->getAdminReportSummary();
$postulacionSummary = $postulacionModel->getAdminStats();
$egresados = $egresadoModel->getExportRows();
$empleadores = $egresadoModel->getEmployerRows();

$ofertasLiberadas = (int)($offerSummary['liberadas'] ?? 0);
$ofertasActivas = (int)($offerSummary['activas'] ?? 0);
$postulacionesTotales = (int)($postulacionSummary['total'] ?? 0);
$egresadosTotales = (int)($egresadoSummary['total'] ?? 0);
$egresadosEmpleados = (int)($egresadoSummary['empleados'] ?? 0);
$tasaColocacion = $egresadosTotales > 0 ? round(($egresadosEmpleados / $egresadosTotales) * 100) : 0;
$salarioPromedio = (float)($egresadoSummary['salario_promedio_estimado'] ?? 0);
$promedioMesesLaborando = (float)($egresadoSummary['promedio_meses_laborando'] ?? 0);
$promedioAniosLaborando = $promedioMesesLaborando > 0 ? round($promedioMesesLaborando / 12, 1) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes Admin - UTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
  <style>
    .utp-report-card {
      min-height: 420px;
    }

    .utp-chart-wrap {
      position: relative;
      height: 280px;
      width: 100%;
    }

    .utp-chart-wrap canvas {
      display: block;
      width: 100% !important;
      height: 100% !important;
    }

    .utp-chart-empty {
      display: none;
      height: 100%;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: #757575;
      font-size: 14px;
      border: 1px dashed rgba(0, 0, 0, 0.08);
      border-radius: 16px;
      background: rgba(250, 250, 250, 0.6);
      padding: 20px;
    }

    .utp-chart-empty.is-visible {
      display: flex;
    }
  </style>
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'admin', roleLabel: 'Administrador',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'reportes',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
    window.UTP_REPORTES = {
      apiUrl: <?= json_encode('../../public/api/reportes.php') ?>
    };
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="container-fluid px-0">
    <div class="row g-0">
      <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

      <main class="col utp-content">
        <div class="container-fluid px-3 px-md-4 py-4 py-md-5">

          <section class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
            <div>
              <h1 class="utp-h1 mb-2">Reportes y Analítica</h1>
              <p class="utp-subtitle mb-0">Consulta gráficas, listas exportables y métricas del sistema</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-outline-success" href="../../public/api/exportar-egresados.php?dataset=egresados&format=csv">
                <i class="bi bi-filetype-csv me-1"></i>CSV egresados
              </a>
              <a class="btn btn-success" href="../../public/api/exportar-egresados.php?dataset=egresados&format=excel">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel egresados
              </a>
              <a class="btn btn-outline-primary" href="../../public/api/exportar-egresados.php?dataset=empleadores&format=csv">
                <i class="bi bi-download me-1"></i>CSV empresas
              </a>
              <a class="btn btn-primary" href="../../public/api/exportar-egresados.php?dataset=empleadores&format=excel">
                <i class="bi bi-building-check me-1"></i>Excel empresas
              </a>
            </div>
          </section>

          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-check-circle"></i></div>
                  <div class="text-muted small">Liberadas</div>
                </div>
                <div class="utp-kpi mt-3"><?= $ofertasLiberadas ?></div>
                <div class="text-muted small">Ofertas aprobadas</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-briefcase"></i></div>
                  <div class="text-muted small">Activas</div>
                </div>
                <div class="utp-kpi mt-3"><?= $ofertasActivas ?></div>
                <div class="text-muted small">Publicadas y vigentes</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-send"></i></div>
                  <div class="text-muted small">Postulaciones</div>
                </div>
                <div class="utp-kpi mt-3"><?= $postulacionesTotales ?></div>
                <div class="text-muted small">Histórico total</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon red"><i class="bi bi-graph-up-arrow"></i></div>
                  <div class="text-muted small">Colocación</div>
                </div>
                <div class="utp-kpi mt-3"><?= $tasaColocacion ?>%</div>
                <div class="text-muted small">Egresados con empleo</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-cash-coin"></i></div>
                  <div class="text-muted small">Promedio salarial</div>
                </div>
                <div class="utp-kpi mt-3">$<?= number_format($salarioPromedio, 0, '.', ',') ?></div>
                <div class="text-muted small">Estimado mensual (MXN)</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                  <div class="text-muted small">Antigüedad</div>
                </div>
                <div class="utp-kpi mt-3"><?= number_format($promedioAniosLaborando, 1) ?> años</div>
                <div class="text-muted small">Promedio laborando</div>
              </article>
            </div>
          </section>

          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-12 col-xl-6">
              <article class="utp-card utp-report-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                    <h2 class="utp-h2 mb-1">Ofertas liberadas</h2>
                    <p class="text-muted small mb-0">Tendencia mensual de publicaciones aprobadas</p>
                  </div>
                </div>
                <div class="utp-chart-wrap">
                  <canvas id="chartOfertasLiberadas"></canvas>
                  <div class="utp-chart-empty" id="emptyChartOfertasLiberadas">No hay datos suficientes para mostrar esta gráfica.</div>
                </div>
              </article>
            </div>
            <div class="col-12 col-xl-6">
              <article class="utp-card utp-report-card h-100">
                <div>
                  <h2 class="utp-h2 mb-1">Estado de postulaciones</h2>
                  <p class="text-muted small mb-3">Distribución actual del flujo de aplicación</p>
                </div>
                <div class="utp-chart-wrap">
                  <canvas id="chartPostulaciones"></canvas>
                  <div class="utp-chart-empty" id="emptyChartPostulaciones">No hay datos suficientes para mostrar esta gráfica.</div>
                </div>
              </article>
            </div>
            <div class="col-12 col-xl-6">
              <article class="utp-card utp-report-card h-100">
                <div>
                  <h2 class="utp-h2 mb-1">Situación laboral</h2>
                  <p class="text-muted small mb-3">Empleo y presencia en TI</p>
                </div>
                <div class="utp-chart-wrap">
                  <canvas id="chartSeguimiento"></canvas>
                  <div class="utp-chart-empty" id="emptyChartSeguimiento">No hay datos suficientes para mostrar esta gráfica.</div>
                </div>
              </article>
            </div>
            <div class="col-12 col-xl-6">
              <article class="utp-card utp-report-card h-100">
                <div>
                  <h2 class="utp-h2 mb-1">Dónde trabajan</h2>
                  <p class="text-muted small mb-3">Principales empresas empleadoras</p>
                </div>
                <div class="utp-chart-wrap">
                  <canvas id="chartEmpresas"></canvas>
                  <div class="utp-chart-empty" id="emptyChartEmpresas">No hay datos suficientes para mostrar esta gráfica.</div>
                </div>
              </article>
            </div>
            <div class="col-12">
              <article class="utp-card utp-report-card h-100">
                <div>
                  <h2 class="utp-h2 mb-1">Indicadores promedio</h2>
                  <p class="text-muted small mb-3">Salario mensual estimado y tiempo promedio laborando</p>
                </div>
                <div class="utp-chart-wrap">
                  <canvas id="chartIndicadores"></canvas>
                  <div class="utp-chart-empty" id="emptyChartIndicadores">No hay datos suficientes para mostrar esta gráfica.</div>
                </div>
              </article>
            </div>
          </section>

          <section class="row g-3 g-lg-4">
            <div class="col-12 xl col-xl-7">
              <article class="utp-card h-100">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                  <div>
                    <h2 class="utp-h2 mb-1">Lista de egresados</h2>
                    <p class="text-muted small mb-0">Datos clave para seguimiento y validación</p>
                  </div>
                  <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill"><?= count($egresados) ?> registros</span>
                </div>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
                    <thead>
                      <tr class="text-muted small">
                        <th>Matrícula</th>
                        <th>Nombre</th>
                        <th>Correo personal</th>
                        <th>CURP</th>
                        <th>Empresa</th>
                        <th>Trabaja en TI</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (array_slice($egresados, 0, 12) as $egresado): ?>
                        <tr>
                          <td><?= htmlspecialchars($egresado['matricula'] ?? '—') ?></td>
                          <td><?= htmlspecialchars($egresado['nombre'] ?? '—') ?></td>
                          <td class="text-muted small"><?= htmlspecialchars($egresado['correo_personal'] ?: ($egresado['email'] ?? '—')) ?></td>
                          <td class="text-muted small"><?= htmlspecialchars($egresado['curp'] ?? '—') ?></td>
                          <td class="text-muted small"><?= htmlspecialchars($egresado['empresa_actual'] ?? '—') ?></td>
                          <td>
                            <span class="badge <?= ($egresado['trabaja_en_ti'] ?? 'No') === 'Si' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?>">
                              <?= htmlspecialchars($egresado['trabaja_en_ti'] ?? 'No') ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </article>
            </div>
            <div class="col-12 col-xl-5">
              <article class="utp-card h-100">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                  <div>
                    <h2 class="utp-h2 mb-1">Lista de dónde trabajan</h2>
                    <p class="text-muted small mb-0">Relación rápida de empresas y puestos</p>
                  </div>
                  <span class="badge bg-success-subtle text-success-emphasis rounded-pill"><?= count($empleadores) ?> colocaciones</span>
                </div>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
                    <thead>
                      <tr class="text-muted small">
                        <th>Egresado</th>
                        <th>Empresa</th>
                        <th>Puesto</th>
                        <th>TI</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (array_slice($empleadores, 0, 12) as $empleador): ?>
                        <tr>
                          <td><?= htmlspecialchars($empleador['egresado'] ?? '—') ?></td>
                          <td><?= htmlspecialchars($empleador['empresa_actual'] ?? '—') ?></td>
                          <td class="text-muted small"><?= htmlspecialchars($empleador['puesto_actual'] ?? '—') ?></td>
                          <td>
                            <span class="badge <?= ($empleador['trabaja_en_ti'] ?? 'No') === 'Si' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-secondary bg-opacity-10 text-secondary' ?>">
                              <?= htmlspecialchars($empleador['trabaja_en_ti'] ?? 'No') ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </article>
            </div>
          </section>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <script src="<?= ASSETS_URL ?>/js/reportes.js"></script>
</body>
</html>