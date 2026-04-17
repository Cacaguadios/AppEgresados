<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Get egresado id
$egresadoModel = new Egresado();
$perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
$egresadoId = $perfil['id'] ?? 0;

// Load postulaciones + stats
$postulacionModel = new Postulacion();
$postulaciones = $postulacionModel->getByEgresadoId($egresadoId);
$stats = $postulacionModel->getStatsByEgresado($egresadoId);

// Decode egresado skills for match calculation
$misHabilidades = json_decode($perfil['habilidades'] ?? '[]', true) ?: [];

// Status mapping
$statusMap = [
    'pendiente'      => ['label' => 'Enviada',         'color' => 'blue',   'icon' => 'bi-clock-history'],
    'preseleccionado'=> ['label' => 'En revisión',     'color' => 'yellow', 'icon' => 'bi-eye'],
    'contactado'     => ['label' => 'Seleccionado',    'color' => 'green',  'icon' => 'bi-check-circle'],
    'rechazado'      => ['label' => 'No seleccionado', 'color' => 'gray',   'icon' => 'bi-x-circle'],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Postulaciones - Egresados UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body>
  <script>
    window.UTP_DATA = {
      role: 'egresado', roleLabel: 'Egresado',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'postulaciones',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="utp-layout">
    <div class="container-fluid px-3 px-md-4">
      <div class="row gx-4">
        <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

        <div class="col">
          <div class="utp-content">
            <div class="px-0 py-3 py-md-4" style="min-height: calc(100vh - 65px);">

              <!-- Page Header -->
              <div class="mb-4">
                <h1 style="font-size:36px; font-weight:700; line-height:40px; color:#121212;">Mis Postulaciones</h1>
                <p style="color:#757575; font-size:18px; line-height:28px;" class="mb-0">Revisa el estado de tus postulaciones</p>
              </div>

              <!-- Stats Cards Row -->
              <div class="row g-3 mb-4">
                <div class="col-6 col-sm-6 col-lg-3">
                  <div class="utp-stat-card">
                    <div class="utp-stat-icon blue"><i class="bi bi-clock-history"></i></div>
                    <div class="utp-stat-info">
                      <span class="utp-stat-number"><?= (int)($stats['enviadas'] ?? 0) ?></span>
                      <span class="utp-stat-label">Enviadas</span>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-sm-6 col-lg-3">
                  <div class="utp-stat-card">
                    <div class="utp-stat-icon yellow"><i class="bi bi-eye"></i></div>
                    <div class="utp-stat-info">
                      <span class="utp-stat-number"><?= (int)($stats['en_revision'] ?? 0) ?></span>
                      <span class="utp-stat-label">En revisión</span>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-sm-6 col-lg-3">
                  <div class="utp-stat-card">
                    <div class="utp-stat-icon green"><i class="bi bi-check-circle"></i></div>
                    <div class="utp-stat-info">
                      <span class="utp-stat-number"><?= (int)($stats['seleccionado'] ?? 0) ?></span>
                      <span class="utp-stat-label">Seleccionado</span>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-sm-6 col-lg-3">
                  <div class="utp-stat-card">
                    <div class="utp-stat-icon gray"><i class="bi bi-x-circle"></i></div>
                    <div class="utp-stat-info">
                      <span class="utp-stat-number"><?= (int)($stats['no_seleccionado'] ?? 0) ?></span>
                      <span class="utp-stat-label">No seleccionado</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Application Cards -->
              <div class="row g-4">

                <?php if (empty($postulaciones)): ?>
                <!-- Empty state -->
                <div class="col-12">
                  <div class="utp-card text-center py-5">
                    <div class="utp-miniicon blue mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;">
                      <i class="bi bi-file-earmark-text" style="font-size:28px;"></i>
                    </div>
                    <h3 style="font-size:20px; font-weight:600; color:#121212;">Aún no tienes postulaciones</h3>
                    <p style="color:#757575; font-size:16px; margin-top:8px;">
                      Explora las ofertas disponibles y postúlate a las que te interesen.
                    </p>
                    <a href="ofertas.php" class="btn btn-utp-red btn-utp-rounded mt-2">Explorar ofertas</a>
                  </div>
                </div>
                <?php else: ?>
                  <?php foreach ($postulaciones as $p):
                    $estado = $p['estado'] ?? 'pendiente';
                    $st = $statusMap[$estado] ?? $statusMap['pendiente'];
                    $ofertaSkills = json_decode($p['oferta_habilidades'] ?? '[]', true) ?: [];
                    // Skill match
                    $matchCount = 0;
                    if (!empty($ofertaSkills) && !empty($misHabilidades)) {
                        $ofertaLower = array_map('mb_strtolower', $ofertaSkills);
                        $misLower = array_map('mb_strtolower', $misHabilidades);
                        $matchCount = count(array_intersect($ofertaLower, $misLower));
                    }
                    $matchPct = !empty($ofertaSkills) ? round(($matchCount / count($ofertaSkills)) * 100) : 0;
                    // Format date
                    $fechaPost = $p['fecha_postulacion'] ? date('d/m/Y', strtotime($p['fecha_postulacion'])) : '—';
                    // Salary
                    $salarioMin = $p['salario_min'] ?? null;
                    $salarioMax = $p['salario_max'] ?? null;
                    $salarioTxt = '';
                    if ($salarioMin && $salarioMax) {
                        $salarioTxt = '$' . number_format($salarioMin,0,',',',') . ' - $' . number_format($salarioMax,0,',',',') . ' MXN';
                    } elseif ($salarioMin) {
                        $salarioTxt = 'Desde $' . number_format($salarioMin,0,',',',') . ' MXN';
                    }
                  ?>
                  <div class="col-12">
                    <div class="utp-card">
                      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                      <div>
                        <h3 style="font-size:18px; font-weight:600; color:#121212; margin-bottom:4px;">
                          <?= htmlspecialchars($p['titulo']) ?>
                        </h3>
                        <p style="color:#757575; font-size:14px; margin-bottom:0;">
                          <?= htmlspecialchars($p['empresa'] ?? '') ?>
                          <?php if ($p['ubicacion'] ?? ''): ?> · <?= htmlspecialchars($p['ubicacion']) ?><?php endif; ?>
                          <?php if ($p['modalidad'] ?? ''): ?> · <?= ucfirst(htmlspecialchars($p['modalidad'])) ?><?php endif; ?>
                        </p>
                      </div>
                      <span class="utp-badge-<?= $st['color'] ?>">
                        <i class="bi <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
                      </span>
                    </div>

                    <?php if (!empty($ofertaSkills)): ?>
                      <div class="d-flex flex-wrap gap-1 mb-3">
                        <?php foreach (array_slice($ofertaSkills, 0, 6) as $skill): ?>
                          <span class="utp-skill-chip-sm"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($ofertaSkills) > 6): ?>
                          <span class="utp-skill-chip-sm">+<?= count($ofertaSkills) - 6 ?></span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <!-- Barra de compatibilidad -->
                    <?php 
                      // Determinar color según compatibilidad
                      $compatColor = '#dc3545'; // rojo por defecto
                      $compatBg = '#f8d7da';
                      $compatLabel = 'Baja compatibilidad';
                      if ($matchPct >= 75) {
                        $compatColor = '#28a745'; // verde
                        $compatBg = '#d4edda';
                        $compatLabel = 'Excelente compatibilidad';
                      } elseif ($matchPct >= 50) {
                        $compatColor = '#ffc107'; // amarillo
                        $compatBg = '#fff3cd';
                        $compatLabel = 'Buena compatibilidad';
                      } elseif ($matchPct >= 25) {
                        $compatColor = '#fd7e14'; // naranja
                        $compatBg = '#ffe5cc';
                        $compatLabel = 'Compatible';
                      }
                    ?>
                    <div class="mb-3" style="padding: 12px; background-color: <?= $compatBg ?>; border-radius: 8px;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:13px; font-weight:600; color:#121212;">
                          <i class="bi bi-lightning-fill me-1" style="color:<?= $compatColor ?>;"></i>Coincidencia
                        </span>
                        <span style="font-size:13px; font-weight:600; color:<?= $compatColor ?>;"><?= $matchPct ?>%</span>
                      </div>
                      <div style="width:100%; height:6px; background-color:rgba(0,0,0,0.1); border-radius:4px; overflow:hidden;">
                        <div style="width:<?= $matchPct ?>%; height:100%; background-color:<?= $compatColor ?>; transition:width 0.3s ease;"></div>
                      </div>
                      <div style="font-size:12px; color:#666; margin-top:6px;"><?= $compatLabel ?></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                      <?php if ($salarioTxt): ?>
                      <span style="font-size:13px; color:#757575;"><i class="bi bi-cash-stack me-1"></i><?= $salarioTxt ?></span>
                      <?php endif; ?>
                      <span style="font-size:13px; color:#757575;"><i class="bi bi-calendar3 me-1"></i>Postulado: <?= $fechaPost ?></span>
                      <?php $ofertaUrl = 'oferta-detalle.php?id=' . (int)$p['oferta_id']; ?>
                      <a href="<?= htmlspecialchars($ofertaUrl) ?>" class="ms-auto btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                        Ver oferta <i class="bi bi-arrow-right ms-1"></i>
                      </a>
                      <?php if ($estado !== 'rechazado'): ?>
                        <?php $retiroId = (int)$p['id']; $retiroTitulo = htmlspecialchars($p['titulo'], ENT_QUOTES); ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" onclick="confirmarRetiro(<?= $retiroId ?>, <?= json_encode($retiroTitulo) ?>)">
                          <i class="bi bi-trash me-1"></i> Retirar
                        </button>
                      <?php endif; ?>
                    </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>

              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>

  <script>
    function confirmarRetiro(postulacionId, titulo) {
      if (confirm('Confirmas que deseas retirar tu postulación a: ' + titulo + '?\n\nNo podrás deshacer esta acción.')) {
        fetch('../../public/api/postulaciones-update.php?action=retirar&postulacion_id=' + postulacionId, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({postulacion_id: postulacionId})
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            alert('Postulación retirada correctamente');
            location.reload();
          } else {
            alert('Error: ' + (data.error || 'No se pudo retirar la postulación'));
          }
        })
        .catch(() => alert('Error de conexión'));
      }
    }
  </script>
</body>
</html>
