<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Usuario.php';
require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Stats
$usuarioModel  = new Usuario();
$ofertaModel   = new Oferta();
$egresadoModel = new Egresado();

$adminStats    = $usuarioModel->getAdminStats();
$modStats      = $ofertaModel->getModeracionStats();
$egresadoStats = $egresadoModel->getStats();
$verifPending  = $usuarioModel->countPendingVerification();

$ofertasPendientes = (int)($modStats['pendientes'] ?? 0);
$ofertasActivas    = (int)($modStats['aprobadas'] ?? 0);
$totalEgresados    = (int)($egresadoStats['total'] ?? 0);
$totalVerificados  = (int)($adminStats['verificados'] ?? 0);
$totalUsuarios     = (int)($adminStats['total'] ?? 0);
$usuariosActivos   = (int)($adminStats['activos'] ?? 0);
$pendVerif         = (int)($verifPending['total'] ?? 0);
$tasaVerif         = $totalUsuarios > 0 ? round(($totalVerificados / $totalUsuarios) * 100) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - UTP</title>
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
      currentPage: 'inicio',
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
            <h1 class="utp-h1 mb-2">Panel de Administración</h1>
            <p class="utp-subtitle mb-0">Vista general del sistema y acciones prioritarias</p>
          </section>

          <!-- KPIs -->
          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                  <div class="text-muted small">Pendientes</div>
                </div>
                <div class="utp-kpi mt-3"><?= $ofertasPendientes ?></div>
                <div class="text-muted small">Ofertas por moderar</div>
              </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-check-circle"></i></div>
                  <div class="text-muted small">Activas</div>
                </div>
                <div class="utp-kpi mt-3"><?= $ofertasActivas ?></div>
                <div class="text-muted small">Ofertas aprobadas</div>
              </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-people"></i></div>
                  <div class="text-muted small">Total</div>
                </div>
                <div class="utp-kpi mt-3"><?= $totalEgresados ?></div>
                <div class="text-muted small">Egresados registrados</div>
              </article>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-patch-check"></i></div>
                  <div class="text-muted small">Verificados</div>
                </div>
                <div class="utp-kpi mt-3"><?= $totalVerificados ?></div>
                <div class="text-muted small">Usuarios verificados</div>
              </article>
            </div>
          </section>

          <!-- Acciones Prioritarias -->
          <section class="mb-4">
            <h2 class="utp-h2 mb-3">Acciones prioritarias</h2>
            <div class="row g-3">
              <div class="col-12 col-lg-6">
                <a class="utp-actioncard" href="moderacion/list.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                    <span class="badge bg-warning text-dark rounded-pill"><?= $ofertasPendientes ?></span>
                  </div>
                  <div class="utp-actiontitle mt-3">Moderar ofertas</div>
                  <div class="utp-actiondesc">Revisa y aprueba las ofertas pendientes de publicación</div>
                </a>
              </div>
              <div class="col-12 col-lg-6">
                <a class="utp-actioncard" href="verificacion/list.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon blue"><i class="bi bi-patch-check"></i></div>
                    <span class="badge bg-primary rounded-pill"><?= $pendVerif ?></span>
                  </div>
                  <div class="utp-actiontitle mt-3">Verificar usuarios</div>
                  <div class="utp-actiondesc">Valida la identidad de los usuarios registrados</div>
                </a>
              </div>
            </div>
          </section>

          <!-- Gestión del sistema -->
          <section class="mb-4">
            <h2 class="utp-h2 mb-3">Gestión del sistema</h2>
            <div class="row g-3">
              <div class="col-12 col-lg-4">
                <a class="utp-actioncard" href="seguimiento/list.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon green"><i class="bi bi-graph-up"></i></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                  </div>
                  <div class="utp-actiontitle mt-3">Seguimiento de egresados</div>
                  <div class="utp-actiondesc">Consulta la situación laboral de los egresados</div>
                </a>
              </div>
              <div class="col-12 col-lg-4">
                <a class="utp-actioncard" href="reportes.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon yellow"><i class="bi bi-bar-chart-line"></i></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                  </div>
                  <div class="utp-actiontitle mt-3">Reportes y exportes</div>
                  <div class="utp-actiondesc">Genera gráficas, reportes y descargas en Excel o CSV</div>
                </a>
              </div>
              <div class="col-12 col-lg-4">
                <a class="utp-actioncard" href="users.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon blue"><i class="bi bi-people-fill"></i></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                  </div>
                  <div class="utp-actiontitle mt-3">Gestión de usuarios</div>
                  <div class="utp-actiondesc">Administra cuentas, roles y permisos</div>
                </a>
              </div>
              <div class="col-12 col-lg-4">
                <div class="utp-actioncard" style="opacity:0.6; cursor:default;">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon red"><i class="bi bi-shield-lock"></i></div>
                    <span class="badge bg-secondary">Próximamente</span>
                  </div>
                  <div class="utp-actiontitle mt-3">Auditoría y logs</div>
                  <div class="utp-actiondesc">Revisa la actividad y seguridad del sistema</div>
                </div>
              </div>
            </div>
          </section>

          <!-- Estado del Sistema -->
          <section>
            <h2 class="utp-h2 mb-3">Estado del sistema</h2>
            <div class="utp-card">
              <div class="row g-4">
                <div class="col-12 col-md-4 text-center">
                  <div class="utp-kpi"><?= $ofertasActivas ?></div>
                  <div class="text-muted small mt-1">Ofertas activas</div>
                </div>
                <div class="col-12 col-md-4 text-center">
                  <div class="utp-kpi"><?= $tasaVerif ?>%</div>
                  <div class="text-muted small mt-1">Tasa de verificación</div>
                </div>
                <div class="col-12 col-md-4 text-center">
                  <div class="utp-kpi"><?= $usuariosActivos ?></div>
                  <div class="text-muted small mt-1">Usuarios activos</div>
                </div>
              </div>
            </div>
          </section>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
</body>
</html>
