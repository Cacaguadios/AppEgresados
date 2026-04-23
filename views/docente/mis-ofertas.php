<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$ofertaModel = new Oferta();
$ofertas = $ofertaModel->getByUserId($_SESSION['usuario_id']);

// Status badges
$estadoBadge = [
    'pendiente_aprobacion' => ['label' => 'Pendiente',  'color' => 'yellow', 'icon' => 'bi-hourglass-split'],
    'aprobada'             => ['label' => 'Aprobada',   'color' => 'green',  'icon' => 'bi-check-circle'],
    'rechazada'            => ['label' => 'Rechazada',  'color' => 'red',    'icon' => 'bi-x-circle'],
];
$vacanteBadge = [
    'verde'    => ['label' => 'Disponible',  'color' => 'green'],
    'amarillo' => ['label' => 'En proceso',  'color' => 'yellow'],
    'rojo'     => ['label' => 'Cubierta',    'color' => 'red'],
];

$msgCreada = isset($_GET['creada']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Ofertas - Docente UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'docente', roleLabel: 'Docente',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'mis-ofertas',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
    window.UTP_CSRF_TOKEN = <?= json_encode(Security::generateCsrfToken()) ?>;
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
              <h1 class="utp-h1 mb-1">Mis Ofertas</h1>
              <p class="text-muted mb-0"><?= count($ofertas) ?> oferta<?= count($ofertas) !== 1 ? 's' : '' ?> publicada<?= count($ofertas) !== 1 ? 's' : '' ?></p>
            </div>
            <a href="publicar-oferta.php" class="btn btn-utp-green d-inline-flex align-items-center gap-2">
              <i class="bi bi-plus-lg"></i> Nueva oferta
            </a>
          </section>

          <?php if ($msgCreada): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-check-circle me-2"></i>Oferta creada exitosamente. Será revisada por un administrador.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (empty($ofertas)): ?>
            <div class="utp-card text-center py-5">
              <div class="utp-miniicon green mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;">
                <i class="bi bi-briefcase" style="font-size:28px;"></i>
              </div>
              <h3 style="font-size:20px; font-weight:600; color:#121212;">Aún no tienes ofertas</h3>
              <p style="color:#757575; font-size:16px; margin-top:8px;">Publica tu primera oferta para comenzar a recibir postulaciones.</p>
              <a href="publicar-oferta.php" class="btn btn-utp-green mt-2">
                <i class="bi bi-plus-lg me-2"></i> Crear oferta
              </a>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($ofertas as $o):
                $est = $estadoBadge[$o['estado']] ?? $estadoBadge['pendiente_aprobacion'];
                $vac = $vacanteBadge[$o['estado_vacante'] ?? 'verde'] ?? $vacanteBadge['verde'];
                $skills = json_decode($o['habilidades'] ?? '[]', true) ?: [];
                $salarioTxt = '';
                if ($o['salario_min'] && $o['salario_max']) {
                    $salarioTxt = '$' . number_format($o['salario_min'],0,',',',') . ' - $' . number_format($o['salario_max'],0,',',',') . ' MXN';
                }
                $fechaCreacion = date('d/m/Y', strtotime($o['fecha_creacion']));
                $fechaExp = $o['fecha_expiracion'] ? date('d/m/Y', strtotime($o['fecha_expiracion'])) : '—';
                $expirada = $o['fecha_expiracion'] && strtotime($o['fecha_expiracion']) < time();
              ?>
              <article class="utp-card">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <h3 style="font-size:18px; font-weight:600; color:#121212; margin-bottom:4px;">
                      <?= htmlspecialchars($o['titulo']) ?>
                    </h3>
                    <p style="color:#757575; font-size:14px; margin-bottom:0;">
                      <?= htmlspecialchars($o['empresa'] ?? '') ?>
                      <?php if ($o['ubicacion']): ?> · <?= htmlspecialchars($o['ubicacion']) ?><?php endif; ?>
                      <?php if ($o['modalidad']): ?> · <?= ucfirst(htmlspecialchars($o['modalidad'])) ?><?php endif; ?>
                    </p>
                  </div>
                  <div class="d-flex gap-2">
                    <span class="utp-badge-<?= $est['color'] ?>">
                      <i class="bi <?= $est['icon'] ?> me-1"></i><?= $est['label'] ?>
                    </span>
                    <?php if ($o['estado'] === 'aprobada'): ?>
                      <span class="utp-badge-<?= $vac['color'] ?>">
                        <?= $vac['label'] ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($expirada): ?>
                      <span class="utp-badge-gray">Expirada</span>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if (!empty($skills)): ?>
                <div class="d-flex flex-wrap gap-1 mb-3">
                  <?php foreach (array_slice($skills, 0, 5) as $skill): ?>
                    <span class="utp-skill-chip-sm"><?= htmlspecialchars($skill) ?></span>
                  <?php endforeach; ?>
                  <?php if (count($skills) > 5): ?>
                    <span class="utp-skill-chip-sm">+<?= count($skills) - 5 ?></span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap align-items-center gap-3">
                  <?php if ($salarioTxt): ?>
                    <span style="font-size:13px; color:#757575;"><i class="bi bi-cash-stack me-1"></i><?= $salarioTxt ?></span>
                  <?php endif; ?>
                  <span style="font-size:13px; color:#757575;"><i class="bi bi-people me-1"></i><?= (int)($o['postulantes_count'] ?? 0) ?> postulantes</span>
                  <span style="font-size:13px; color:#757575;"><i class="bi bi-calendar3 me-1"></i>Creada: <?= $fechaCreacion ?></span>
                  <span style="font-size:13px; color:#757575;"><i class="bi bi-calendar-x me-1"></i>Expira: <?= $fechaExp ?></span>
                  <span style="font-size:13px; color:#757575;"><i class="bi bi-door-open me-1"></i><?= (int)$o['vacantes'] ?> vacante<?= $o['vacantes'] != 1 ? 's' : '' ?></span>
                </div>

                <?php if ($o['estado'] === 'rechazada' && !empty($o['razon_rechazo'])): ?>
                <div class="alert alert-warning mt-3 mb-0 py-2 px-3" style="font-size:13px;">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  <strong>Razón de rechazo:</strong> <?= htmlspecialchars($o['razon_rechazo']) ?>
                </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                  <?php if ($o['estado'] === 'aprobada' && !$expirada && $o['activo'] == 1): ?>
                    <a href="publicar-oferta.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                      <i class="bi bi-eye me-1"></i> Ver
                    </a>
                    <a href="editar-oferta.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                      <i class="bi bi-pencil me-1"></i> Editar
                    </a>
                    <a href="invitar-egresados.php?oferta=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-success" style="border-radius:8px;">
                      <i class="bi bi-hand-thumbs-up me-1"></i> Invitar
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" onclick="confirmarBaja(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($o['titulo']), ENT_QUOTES) ?>')">
                      <i class="bi bi-x-circle me-1"></i> Dar de baja
                    </button>
                    <a href="postulantes.php?oferta=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-info ms-auto" style="border-radius:8px;">
                      <i class="bi bi-people me-1"></i> <?= (int)($o['postulantes_count'] ?? 0) ?> postulante<?= ($o['postulantes_count'] ?? 0) != 1 ? 's' : '' ?>
                    </a>
                  <?php elseif ($o['activo'] == 0): ?>
                    <span class="badge bg-secondary" style="align-self:center;">
                      <i class="bi bi-archive me-1"></i> Dada de baja
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-success ms-auto" style="border-radius:8px;" onclick="confirmarActivacion(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($o['titulo']), ENT_QUOTES) ?>')">
                      <i class="bi bi-arrow-repeat me-1"></i> Reactivar
                    </button>
                  <?php else: ?>
                    <span class="badge bg-info" style="align-self:center;">
                      <i class="bi bi-clock-history me-1"></i> En revisión
                    </span>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>

  <script>
    function confirmarBaja(ofertaId, titulo) {
      if (confirm('Confirmas que deseas dar de baja la oferta: ' + titulo + '?\n\nEsto ocultará la oferta pero podrás reactivarla después.')) {
        fetch('../../public/api/ofertas-update.php?action=baja&oferta_id=' + ofertaId, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
          },
          body: JSON.stringify({
            oferta_id: ofertaId,
            csrf_token: window.UTP_CSRF_TOKEN || ''
          })
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            alert('Error: ' + (data.error || 'No se pudo dar de baja la oferta'));
          }
        })
        .catch(() => alert('Error de conexión'));
      }
    }

    function confirmarActivacion(ofertaId, titulo) {
      if (confirm('Confirmas que deseas reactivar la oferta: ' + titulo + '?')) {
        fetch('../../public/api/ofertas-update.php?action=activar&oferta_id=' + ofertaId, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
          },
          body: JSON.stringify({
            oferta_id: ofertaId,
            csrf_token: window.UTP_CSRF_TOKEN || ''
          })
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            alert('Error: ' + (data.error || 'No se pudo reactivar la oferta'));
          }
        })
        .catch(() => alert('Error de conexión'));
      }
    }
  </script>
</body>
</html>
