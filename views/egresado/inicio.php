<?php
session_start();

// ─── Guard: requiere autenticación + rol egresado ───
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}
if (($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}

// ─── Datos del usuario para inyectar en JS ───
$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);

$initials = '';
if ($nombre)    $initials .= mb_substr($nombre, 0, 1);
if ($apellidos) $initials .= mb_substr($apellidos, 0, 1);
$initials = mb_strtoupper($initials);

$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// ─── Cargar datos reales del dashboard ───
require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';

$ofertaModel = new Oferta();
$egresadoModel = new Egresado();
$postulacionModel = new Postulacion();

// Ofertas disponibles (aprobadas y vigentes)
$ofertasDisponibles = count($ofertaModel->getApprovedAndActive());

// Datos del egresado
$egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);

$misPostulaciones = 0;
$enRevision = 0;
if ($egresado) {
    $stats = $postulacionModel->getStatsByEgresado($egresado['id']);
    $misPostulaciones = (int)($stats['total'] ?? 0);
    $enRevision = (int)($stats['en_revision'] ?? 0);
}

// Perfil completo (% basado en campos llenados)
$perfilCompleto = 0;
if ($egresado) {
  $camposPerfil = ['matricula', 'especialidad', 'generacion', 'habilidades'];
    $llenos = 0;
    foreach ($camposPerfil as $campo) {
        if (!empty($egresado[$campo])) $llenos++;
    }
    $perfilCompleto = round(($llenos / count($camposPerfil)) * 100);
}

// Obtener estado del recordatorio de actualización (cada 3 meses)
$estadoRecordatorio = null;
if ($egresado) {
    $estadoRecordatorio = $egresadoModel->obtenerEstadoRecordatorio($_SESSION['usuario_id']);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio - Egresados UTP</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <!-- App CSS -->
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
</head>

<body>
  <!-- Datos para JS -->
  <script>
    window.UTP_DATA = {
      role: 'egresado',
      roleLabel: 'Egresado',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'inicio',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>,
      estadoRecordatorio: <?= $estadoRecordatorio ? json_encode($estadoRecordatorio) : 'null' ?>
    };
  </script>

  <!-- ===== Notice (inyectado por JS si requirePasswordChange) ===== -->
  <div id="utp-notice-container"></div>

  <!-- ===== Topbar ===== -->
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <!-- ===== Layout principal ===== -->
  <div class="utp-layout">
    <div class="container-fluid px-3 px-md-4">
      <div class="row gx-4">

        <!-- Sidebar -->
        <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

        <!-- Contenido principal -->
        <div class="col">
          <div class="utp-content">

            <!-- Encabezado -->
            <div class="mb-4">
              <h1 class="utp-h1">
                Bienvenido de vuelta
              </h1>
              <p class="utp-subtitle mt-2 mb-0">
                Aquí está un resumen de tu actividad
              </p>
            </div>

            <!-- KPIs -->
            <div class="row g-3 mb-4">
              <div class="col-6 col-lg-3">
                <div class="utp-card h-100">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="utp-miniicon green">
                      <i class="bi bi-briefcase"></i>
                    </div>
                  </div>
                  <div class="utp-kpi"><?= $ofertasDisponibles ?></div>
                  <div class="utp-kpi-label">Activas</div>
                  <div class="utp-kpi-sub">Ofertas disponibles</div>
                </div>
              </div>
              <div class="col-6 col-lg-3">
                <div class="utp-card h-100">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="utp-miniicon blue">
                      <i class="bi bi-file-earmark-text"></i>
                    </div>
                  </div>
                  <div class="utp-kpi"><?= $misPostulaciones ?></div>
                  <div class="utp-kpi-label">Total</div>
                  <div class="utp-kpi-sub">Mis aplicaciones</div>
                </div>
              </div>
              <div class="col-6 col-lg-3">
                <div class="utp-card h-100">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="utp-miniicon yellow">
                      <i class="bi bi-clock-history"></i>
                    </div>
                  </div>
                  <div class="utp-kpi"><?= $enRevision ?></div>
                  <div class="utp-kpi-label">En revisión</div>
                </div>
              </div>
              <div class="col-6 col-lg-3">
                <div class="utp-card h-100">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="utp-miniicon orange">
                      <i class="bi bi-person-badge"></i>
                    </div>
                  </div>
                  <div class="utp-kpi"><?= $perfilCompleto ?>%</div>
                  <div class="utp-kpi-label">Perfil</div>
                  <div class="utp-kpi-sub">Completado</div>
                </div>
              </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="mb-4">
              <h2 class="utp-h2 mb-3">
                Acciones rápidas
              </h2>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <a class="utp-actioncard h-100" href="ofertas.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon green">
                        <i class="bi bi-briefcase"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Explorar ofertas</div>
                    <div class="utp-actiondesc mt-1">Descubre nuevas oportunidades laborales</div>
                  </a>
                </div>
                <div class="col-12 col-md-6">
                  <a class="utp-actioncard h-100" href="postulaciones.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon blue">
                        <i class="bi bi-file-earmark"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Mis aplicaciones</div>
                    <div class="utp-actiondesc mt-1">Revisa el estado de tus postulaciones</div>
                  </a>
                </div>
                <div class="col-12 col-md-6">
                  <a class="utp-actioncard h-100" href="perfil.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon yellow">
                        <i class="bi bi-person"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Actualizar perfil</div>
                    <div class="utp-actiondesc mt-1">Mantén tu CV y habilidades al día</div>
                  </a>
                </div>
                <div class="col-12 col-md-6">
                  <a class="utp-actioncard h-100" href="seguimiento.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon orange">
                        <i class="bi bi-graph-up"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Formulario de seguimiento</div>
                    <div class="utp-actiondesc mt-1">Comparte tu situación laboral actual (privado)</div>
                  </a>
                </div>
              </div>
            </div>

            <!-- Completa tu perfil -->
            <?php if ($perfilCompleto < 100): ?>
            <div class="utp-card utp-complete-profile-card">
              <div class="d-flex align-items-start gap-3">
                <div class="utp-miniicon orange utp-shrink-0">
                  <i class="bi bi-person-badge"></i>
                </div>
                <div class="utp-flex-1">
                  <h3 class="utp-profile-tip-title">Completa tu perfil</h3>
                  <p class="utp-profile-tip-text">
                    Un perfil completo aumenta tus posibilidades de selección. Le falta <?= (100 - $perfilCompleto) ?>% para completarlo.
                  </p>
                </div>
                <a href="perfil.php" class="btn btn-utp-red utp-shrink-0 utp-nowrap">
                  Completar ahora
                </a>
              </div>
            </div>
            <?php endif; ?>

          </div><!-- /utp-content -->
        </div><!-- /col -->
      </div><!-- /row -->
    </div><!-- /container -->
  </div><!-- /utp-layout -->

  <!-- ===== Modal: Recordatorio actualizar información ===== -->
  <?php require_once __DIR__ . '/../../views/components/modal-recordatorio-actualizacion.php'; ?>

  <!-- ===== Modal: Recordatorio de seguridad ===== -->
  <?php if ($requirePasswordChange): ?>
  <div class="modal fade" id="securityReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content utp-modal utp-security-modal-content">
        <div class="text-center mb-4">
          <div class="utp-miniicon utp-empty-icon yellow mx-auto mb-3">
            <i class="bi bi-shield-lock"></i>
          </div>
          <h3 class="utp-security-modal-title">Cambia tu contraseña</h3>
          <p class="utp-security-modal-text">
            Estás usando una contraseña temporal generada durante tu registro.
            Te recomendamos cambiarla para proteger tu cuenta.
          </p>
        </div>
        <div class="d-flex flex-column gap-2">
          <a href="seguridad.php" class="btn btn-utp-red btn-utp-lg w-100">
            Cambiar contraseña ahora
          </a>
          <button type="button" class="btn btn-utp-outline-red btn-utp-lg w-100" data-bs-dismiss="modal">
            Recordar más tarde
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Shared -->
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <!-- Page -->
  <script src="<?= ASSETS_URL ?>/js/egresado/inicio.js"></script>
  
  <!-- Inicializar recordatorio de actualización -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (window.UTP_DATA && window.UTP_DATA.estadoRecordatorio) {
        inicializarRecordatorio(window.UTP_DATA.estadoRecordatorio);
      }
    });
  </script>
</body>
</html>
