<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Stats
$ofertaModel = new Oferta();
$stats = $ofertaModel->getStatsByUser($_SESSION['usuario_id']);
$totalPostulantes = $ofertaModel->getTotalPostulantesByUser($_SESSION['usuario_id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Ofertas - Docente UTP</title>

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
          <section class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
              <h1 class="utp-h1 mb-2">Panel de ofertas</h1>
              <p class="utp-subtitle mb-0">Gestiona tus ofertas laborales y candidatos</p>
            </div>
            <a href="publicar-oferta.php" class="btn btn-utp-green d-inline-flex align-items-center gap-2">
              <i class="bi bi-plus-lg"></i>
              Nueva oferta
            </a>
          </section>

          <!-- Stats -->
          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon red"><i class="bi bi-briefcase"></i></div>
                  <div class="text-muted small">Total</div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="text-muted small">Mis ofertas</div>
              </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                  <div class="text-muted small">Estado</div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)($stats['pendientes'] ?? 0) ?></div>
                <div class="text-muted small">Pendiente aprobación</div>
              </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-check-circle"></i></div>
                  <div class="text-muted small">Activas</div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)($stats['activas'] ?? 0) ?></div>
                <div class="text-muted small">Publicadas</div>
              </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-people"></i></div>
                  <div class="text-muted small">Total</div>
                </div>
                <div class="utp-kpi mt-3"><?= $totalPostulantes ?></div>
                <div class="text-muted small">Postulados</div>
              </article>
            </div>
          </section>

          <!-- Quick actions -->
          <section class="mb-4">
            <h2 class="utp-h2 mb-3">Acciones rápidas</h2>
            <div class="row g-3">
              <div class="col-12 col-lg-6">
                <a class="utp-actioncard" href="publicar-oferta.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon green"><i class="bi bi-plus-circle"></i></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                  </div>
                  <div class="utp-actiontitle mt-3">Crear nueva oferta</div>
                  <div class="utp-actiondesc">Publica una nueva vacante para egresados UTP</div>
                </a>
              </div>
              <div class="col-12 col-lg-6">
                <a class="utp-actioncard" href="mis-ofertas.php">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="utp-miniicon red"><i class="bi bi-briefcase"></i></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                  </div>
                  <div class="utp-actiontitle mt-3">Gestionar ofertas</div>
                  <div class="utp-actiondesc">Revisa y edita tus ofertas publicadas</div>
                </a>
              </div>
            </div>
          </section>

          <!-- Tutorial CTA -->
          <section>
            <article class="utp-infobox blue">
              <div class="d-flex gap-3 align-items-start">
                <div class="utp-miniicon blue flex-shrink-0"><i class="bi bi-play-circle"></i></div>
                <div class="flex-grow-1">
                  <div class="utp-infobox-title">¿Primera vez publicando?</div>
                  <p class="utp-infobox-desc mb-3">
                    Aprende cómo crear ofertas efectivas y gestionar candidatos en nuestro tutorial rápido.
                  </p>
                  <button class="btn btn-utp-outline-blue" disabled style="opacity:0.6;">
                    <i class="bi bi-play-circle me-1"></i> Próximamente
                  </button>
                </div>
              </div>
            </article>
          </section>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
</body>
</html>
