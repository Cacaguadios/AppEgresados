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
    $camposPerfil = ['matricula', 'correo_personal', 'telefono', 'especialidad', 'generacion', 'habilidades'];
    $llenos = 0;
    foreach ($camposPerfil as $campo) {
        if (!empty($egresado[$campo])) $llenos++;
    }
    $perfilCompleto = round(($llenos / count($camposPerfil)) * 100);
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
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
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
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
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
              <h1 style="font-size:36px; font-weight:700; line-height:40px; color:#121212;">
                ¡Hola, <?= htmlspecialchars($nombre) ?>!
              </h1>
              <p style="color:#757575; font-size:18px; line-height:28px; margin-top:8px;">
                Bienvenido al Sistema de Egresados de la UTP
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
                  <div style="color:#757575; font-size:14px;">Ofertas disponibles</div>
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
                  <div style="color:#757575; font-size:14px;">Mis postulaciones</div>
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
                  <div style="color:#757575; font-size:14px;">En revisión</div>
                </div>
              </div>
              <div class="col-6 col-lg-3">
                <div class="utp-card h-100">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="utp-miniicon red">
                      <i class="bi bi-person-badge"></i>
                    </div>
                  </div>
                  <div class="utp-kpi"><?= $perfilCompleto ?>%</div>
                  <div style="color:#757575; font-size:14px;">Perfil completo</div>
                </div>
              </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="mb-4">
              <h2 style="font-size:24px; font-weight:600; line-height:32px; color:#121212; margin-bottom:16px;">
                Acciones rápidas
              </h2>
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <a class="utp-actioncard h-100" href="ofertas.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon green">
                        <i class="bi bi-search"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Explorar ofertas</div>
                    <div class="utp-actiondesc mt-1">Descubre nuevas oportunidades laborales</div>
                  </a>
                </div>
                <div class="col-12 col-md-4">
                  <a class="utp-actioncard h-100" href="perfil.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon blue">
                        <i class="bi bi-pencil-square"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Completar perfil</div>
                    <div class="utp-actiondesc mt-1">Mejora tu visibilidad ante empleadores</div>
                  </a>
                </div>
                <div class="col-12 col-md-4">
                  <a class="utp-actioncard h-100" href="seguimiento.php">
                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="utp-miniicon yellow">
                        <i class="bi bi-graph-up"></i>
                      </div>
                    </div>
                    <div class="utp-actiontitle">Mi seguimiento</div>
                    <div class="utp-actiondesc mt-1">Actualiza tu situación laboral</div>
                  </a>
                </div>
              </div>
            </div>

          </div><!-- /utp-content -->
        </div><!-- /col -->
      </div><!-- /row -->
    </div><!-- /container -->
  </div><!-- /utp-layout -->

  <!-- ===== Modal: Recordatorio de seguridad ===== -->
  <?php if ($requirePasswordChange): ?>
  <div class="modal fade" id="securityReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content utp-modal" style="border-radius:20px; padding:32px;">
        <div class="text-center mb-4">
          <div class="utp-miniicon yellow mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;">
            <i class="bi bi-shield-lock" style="font-size:28px;"></i>
          </div>
          <h3 style="font-size:24px; font-weight:600; color:#121212;">Cambia tu contraseña</h3>
          <p style="color:#757575; font-size:16px; margin-top:8px;">
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
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
  <!-- Page -->
  <script src="../../public/assets/js/egresado/inicio.js"></script>
</body>
</html>
