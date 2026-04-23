<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

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
  'retirada'       => ['label' => 'Retirada',        'color' => 'gray',   'icon' => 'bi-archive'],
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
    window.UTP_CSRF_TOKEN = <?= json_encode(Security::generateCsrfToken()) ?>;
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="utp-layout">
    <div class="container-fluid px-3 px-md-4">
      <div class="row gx-4">
        <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

        <div class="col">
          <div class="utp-content">
            <div class="px-0 py-3 py-md-4">

              <!-- Page Header -->
              <div class="mb-4">
                <h1 class="utp-h1">Mis Postulaciones</h1>
                <p class="utp-subtitle mb-0">Revisa el estado de tus postulaciones</p>
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
                    <div class="utp-miniicon utp-empty-icon blue mx-auto mb-3">
                      <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3 class="utp-empty-title">Aún no tienes postulaciones</h3>
                    <p class="utp-empty-text">
                      Explora las ofertas disponibles y postúlate a las que te interesen.
                    </p>
                    <a href="ofertas.php" class="btn btn-utp-red btn-utp-rounded mt-2">Explorar ofertas</a>
                  </div>
                </div>
                <?php else: ?>
                  <?php foreach ($postulaciones as $p):
                    $estado = !empty($p['retirada']) ? 'retirada' : ($p['estado'] ?? 'pendiente');
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
                    <div class="utp-card utp-application-card">
                      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                      <div>
                        <h3 class="utp-app-title mb-1">
                          <?= htmlspecialchars($p['titulo']) ?>
                        </h3>
                        <p class="utp-app-company mb-0">
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

                    <?php if (!empty($p['mensaje'])): ?>
                    <div class="utp-app-message mb-3">
                      <span class="utp-app-message-label">Tu mensaje:</span>
                      <p class="utp-app-message-text mb-0"><?= nl2br(htmlspecialchars($p['mensaje'])) ?></p>
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
                    <div class="utp-compat-box mb-3" style="--compat-bg: <?= htmlspecialchars($compatBg, ENT_QUOTES) ?>; --compat-color: <?= htmlspecialchars($compatColor, ENT_QUOTES) ?>; --compat-pct: <?= (int)$matchPct ?>%;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="utp-compat-title">
                          <i class="bi bi-lightning-fill me-1 utp-compat-icon"></i>Coincidencia
                        </span>
                        <span class="utp-compat-pct"><?= $matchPct ?>%</span>
                      </div>
                      <div class="utp-compat-track">
                        <div class="utp-compat-fill"></div>
                      </div>
                      <div class="utp-compat-label"><?= $compatLabel ?></div>
                    </div>

                    <div class="utp-app-footer d-flex flex-wrap align-items-center gap-3">
                      <?php if ($salarioTxt): ?>
                      <span class="utp-app-meta"><i class="bi bi-cash-stack me-1"></i><?= $salarioTxt ?></span>
                      <?php endif; ?>
                      <span class="utp-app-meta"><i class="bi bi-calendar3 me-1"></i>Postulado: <?= $fechaPost ?></span>
                      <?php $ofertaUrl = 'oferta-detalle.php?id=' . (int)$p['oferta_id']; ?>
                      <a href="<?= htmlspecialchars($ofertaUrl) ?>" class="ms-auto btn btn-sm btn-outline-secondary utp-btn-compact">
                        Ver oferta <i class="bi bi-arrow-right ms-1"></i>
                      </a>
                      <?php $postId = (int)$p['id']; $postTitulo = htmlspecialchars($p['titulo'], ENT_QUOTES); ?>
                      <?php if ($estado !== 'retirada' && $estado !== 'rechazado'): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary utp-btn-compact" onclick="editarMensaje(<?= $postId ?>, <?= json_encode((string)($p['mensaje'] ?? '')) ?>)">
                          <i class="bi bi-pencil me-1"></i> Editar mensaje
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger utp-btn-compact" onclick="confirmarRetiro(<?= $postId ?>, <?= json_encode($postTitulo) ?>)">
                          <i class="bi bi-x-circle me-1"></i> Dar de baja
                        </button>
                      <?php elseif ($estado === 'retirada'): ?>
                        <button type="button" class="btn btn-sm btn-outline-success utp-btn-compact" onclick="restaurarPostulacion(<?= $postId ?>)">
                          <i class="bi bi-arrow-repeat me-1"></i> Restaurar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger utp-btn-compact" onclick="eliminarPostulacion(<?= $postId ?>, <?= json_encode($postTitulo) ?>)">
                          <i class="bi bi-trash me-1"></i> Borrar
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
    function postAction(url, payload, okMessage) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
        },
        body: JSON.stringify(Object.assign({}, payload || {}, {
          csrf_token: window.UTP_CSRF_TOKEN || ''
        }))
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (okMessage) alert(okMessage);
          location.reload();
          return;
        }
        alert('Error: ' + (data.error || 'No se pudo completar la acción'));
      })
      .catch(() => alert('Error de conexión'));
    }

    function confirmarRetiro(postulacionId, titulo) {
      if (confirm('Confirmas que deseas retirar tu postulación a: ' + titulo + '?\n\nNo podrás deshacer esta acción.')) {
        postAction('../../public/api/postulaciones-update.php?action=retirar&postulacion_id=' + postulacionId,
          {postulacion_id: postulacionId},
          'Postulación dada de baja correctamente');
      }
    }

    function restaurarPostulacion(postulacionId) {
      if (confirm('¿Deseas restaurar esta postulación?')) {
        postAction('../../public/api/postulaciones-update.php?action=restaurar&postulacion_id=' + postulacionId,
          {postulacion_id: postulacionId},
          'Postulación restaurada correctamente');
      }
    }

    function eliminarPostulacion(postulacionId, titulo) {
      if (confirm('¿Eliminar permanentemente la postulación a ' + titulo + '?\n\nEsta acción no se puede deshacer.')) {
        postAction('../../public/api/postulaciones-update.php?action=eliminar&postulacion_id=' + postulacionId,
          {postulacion_id: postulacionId},
          'Postulación eliminada correctamente');
      }
    }

    function editarMensaje(postulacionId, mensajeActual) {
      var nuevoMensaje = prompt('Edita tu mensaje de postulación:', mensajeActual || '');
      if (nuevoMensaje === null) return;

      postAction('../../public/api/postulaciones-update.php?action=editar_mensaje&postulacion_id=' + postulacionId,
        {postulacion_id: postulacionId, mensaje: nuevoMensaje},
        'Mensaje actualizado correctamente');
    }
  </script>
</body>
</html>
