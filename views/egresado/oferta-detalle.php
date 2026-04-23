<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}
$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// ─── Load offer from DB by ?id= ───
require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/models/Notificacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$ofertaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ofertaId) { header('Location: ofertas.php'); exit; }

$ofertaModel = new Oferta();
$oferta = $ofertaModel->getById($ofertaId);
if (!$oferta) { header('Location: ofertas.php'); exit; }

// Check if current user already applied
$egresadoModel = new Egresado();
$egresado = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
$postulacionModel = new Postulacion();
$aplicacion = $egresado ? $postulacionModel->hasApplied($egresado['id'], $ofertaId) : null;

// ─── Handle POST: Postularse ───
$msgExito = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postularse'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido. Recarga la página.';
    } elseif ($aplicacion) {
        $msgError = 'Ya te postulaste a esta oferta.';
    } elseif (!$egresado) {
        $msgError = 'Completa tu perfil de egresado primero.';
    } elseif ($oferta['estado'] !== 'aprobada' || (int)($oferta['activo'] ?? 1) !== 1 || (int)($oferta['vacantes'] ?? 0) <= 0) {
        $msgError = 'Esta oferta no está disponible.';
    } else {
      // ── Validación automática de perfil ──
      $ofertaHabs  = array_map('mb_strtolower', json_decode($oferta['habilidades'] ?? '[]', true) ?: []);
      $egresadoHabsRaw = $egresado['habilidades'] ?? '';
      // Soporta JSON array o texto separado por comas/saltos
      $egresadoHabs = json_decode($egresadoHabsRaw, true);
      if (!is_array($egresadoHabs)) {
          $egresadoHabs = array_filter(array_map('trim', preg_split('/[,;\n]+/', $egresadoHabsRaw)));
      }
      $egresadoHabs = array_map('mb_strtolower', $egresadoHabs);

      $match = 0;
      foreach ($ofertaHabs as $req) {
          foreach ($egresadoHabs as $eHab) {
              if ($eHab !== '' && (str_contains($eHab, $req) || str_contains($req, $eHab))) {
                  $match++;
                  break;
              }
          }
      }
      $totalReq = count($ofertaHabs);
      $cumpleHabs = $totalReq === 0 || ($match / $totalReq) >= 0.3; // ≥30% de habilidades coinciden

      // Experiencia mínima (si aplica)
      $expMin      = (int)($oferta['experiencia_minima'] ?? 0);
      $expEgresado = (int)preg_replace('/\D.*/', '', $egresado['anos_experiencia_ti'] ?? '0');
      $cumpleExp   = $expMin === 0 || $expEgresado >= $expMin;

      $validacion = ($cumpleHabs && $cumpleExp) ? 'cumple' : 'no_cumple';

      $postulacionId = $postulacionModel->create([
            'id_egresado'          => $egresado['id'],
            'id_oferta'            => $ofertaId,
            'estado'               => 'pendiente',
            'fecha_postulacion'    => date('Y-m-d H:i:s'),
            'mensaje'              => trim($_POST['mensaje_postulacion'] ?? ''),
            'validacion_automatica'=> $validacion,
        ]);

      // Inicializar checklist de habilidades blandas para evaluación de docente/TI.
      $habilidadesBlandas = $egresadoModel->getHabilidadesBlandas($_SESSION['usuario_id']);
      if ($postulacionId) {
        $postulacionModel->inicializarChecklistHabilidadesBlandas((int)$postulacionId, $habilidadesBlandas);
      }

        // Decrementar vacantes (se elimina automáticamente si llega a 0)
        $ofertaEliminada = !$ofertaModel->decrementVacancies($ofertaId);

        // Notificar al creador de la oferta
        $notifModel = new Notificacion();
        $notifModel->onPostulacion(
            $oferta['titulo'],
            $oferta['id_usuario_creador'],
          $fullName,
          $oferta['creador_email'] ?? null
        );

        // Si el perfil no cumple, avisar al egresado
        if ($validacion === 'no_cumple') {
            $notifModel->onPerfilNoCumple(
                $oferta['titulo'],
                $_SESSION['usuario_id'],
                $_SESSION['usuario_email'] ?? null
            );
        }

        if ($ofertaEliminada) {
            header('Location: ofertas.php?cupo_lleno=1');
        } else {
            // Actualizar estado de vacante
            $ofertaModel->updateVacancyStatus($ofertaId);
            $redir = 'oferta-detalle.php?id=' . $ofertaId . '&postulado=1';
            if ($validacion === 'no_cumple') {
                $redir .= '&aviso=perfil';
            }
            header('Location: ' . $redir);
        }
        exit;
    }
}

// Refresh application status after potential POST
if (!$aplicacion && $egresado) {
    $aplicacion = $postulacionModel->hasApplied($egresado['id'], $ofertaId);
}
$postulantesCount = (int)($oferta['postulantes_count'] ?? 0);

// Decode JSON fields
$requisitos  = json_decode($oferta['requisitos'] ?? '[]', true) ?: [];
$beneficios  = json_decode($oferta['beneficios'] ?? '[]', true) ?: [];
$habilidades = json_decode($oferta['habilidades'] ?? '[]', true) ?: [];

// Badge
$badgeColor = 'green'; $badgeLabel = 'Disponible';
if ($oferta['estado_vacante'] === 'amarillo') { $badgeColor = 'yellow'; $badgeLabel = 'Con postulados'; }
if ($oferta['estado_vacante'] === 'rojo')     { $badgeColor = 'red';    $badgeLabel = 'Vacante cubierta'; }

$modalidadLabel = ['presencial'=>'Presencial','remoto'=>'Remoto','hibrido'=>'Híbrido'][$oferta['modalidad']] ?? $oferta['modalidad'];
$fechaPub = date('d/m/Y', strtotime($oferta['fecha_aprobacion'] ?? $oferta['fecha_creacion']));
$fechaExp = date('d/m/Y', strtotime($oferta['fecha_expiracion']));

// Salary
$salario = '—';
if ($oferta['salario_min'] && $oferta['salario_max']) {
    $salario = '$' . number_format($oferta['salario_min'], 0, '.', ',') . ' - $' . number_format($oferta['salario_max'], 0, '.', ',') . ' MXN';
}

// Check success message
$postulado = isset($_GET['postulado']);

// Check skill match
$mySkills = json_decode($egresado['habilidades'] ?? '[]', true) ?: [];
$missingSkills = array_diff(array_map('mb_strtolower', $habilidades), array_map('mb_strtolower', $mySkills));
$matchPercent = count($habilidades) > 0 ? round((1 - count($missingSkills)/count($habilidades)) * 100) : 100;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalle de Oferta - Egresados UTP</title>

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
      currentPage: 'ofertas',
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
            <div class="px-0 py-3 py-md-4 utp-content-wrap">

              <!-- Volver a ofertas -->
              <a href="ofertas.php" class="utp-back-link mb-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-chevron-left"></i>
                <span>Volver a ofertas</span>
              </a>

              <!-- Two Column Layout -->
              <div class="row g-4">
                <!-- Left Column: Main Info -->
                <div class="col-12 col-lg-8">
                  <!-- Job Header Card -->
                  <div class="utp-detail-card mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                      <div class="flex-grow-1">
                        <h1 class="utp-detail-title"><?= htmlspecialchars($oferta['titulo']) ?></h1>
                        <div class="d-flex align-items-center gap-2 mt-2">
                          <i class="bi bi-building text-muted"></i>
                          <span class="utp-detail-company"><?= htmlspecialchars($oferta['empresa'] ?? '—') ?></span>
                        </div>
                      </div>
                      <span class="utp-status-badge <?= $badgeColor ?>">
                        <span class="utp-status-dot"></span>
                        <?= $badgeLabel ?>
                      </span>
                    </div>

                    <div class="utp-detail-meta mb-4">
                      <div class="utp-meta-item"><i class="bi bi-geo-alt"></i><span><?= htmlspecialchars($oferta['ubicacion'] ?? '—') ?></span></div>
                      <div class="utp-meta-item"><i class="bi bi-laptop"></i><span><?= $modalidadLabel ?></span></div>
                      <div class="utp-meta-item"><i class="bi bi-calendar"></i><span><?= $fechaPub ?></span></div>
                      <div class="utp-meta-item"><i class="bi bi-people"></i><span><?= (int)$oferta['vacantes'] ?> vacante<?= $oferta['vacantes'] != 1 ? 's' : '' ?></span></div>
                    </div>

                    <?php if ($salario !== '—'): ?>
                    <div class="utp-detail-salary mb-4">
                      <i class="bi bi-cash-stack text-success"></i>
                      <span><?= $salario ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2">
                      <?php foreach ($habilidades as $skill): ?>
                        <span class="utp-tech-tag-green"><?= htmlspecialchars($skill) ?></span>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <!-- Description Card -->
                  <div class="utp-detail-card mb-4">
                    <h2 class="utp-section-title mb-4">Descripción</h2>
                    <p class="utp-detail-text"><?= nl2br(htmlspecialchars($oferta['descripcion'])) ?></p>
                  </div>

                  <!-- Requirements Card -->
                  <?php if (!empty($requisitos)): ?>
                  <div class="utp-detail-card mb-4">
                    <h2 class="utp-section-title mb-4">Requisitos</h2>
                    <ul class="utp-check-list green">
                      <?php foreach ($requisitos as $req): ?>
                        <li><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($req) ?></span></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php endif; ?>

                  <!-- Benefits Card -->
                  <?php if (!empty($beneficios)): ?>
                  <div class="utp-detail-card">
                    <h2 class="utp-section-title mb-4">Beneficios</h2>
                    <ul class="utp-check-list blue">
                      <?php foreach ($beneficios as $ben): ?>
                        <li><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($ben) ?></span></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php endif; ?>
                </div>

                <!-- Right Column: Sidebar Cards -->
                <div class="col-12 col-lg-4">
                  <?php if ($postulado): ?>
                  <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle me-2"></i>¡Te has postulado exitosamente!
                  </div>
                  <?php if (isset($_GET['aviso']) && $_GET['aviso'] === 'perfil'): ?>
                  <div class="alert alert-warning mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Aviso:</strong> Tu perfil no cumple completamente con los requisitos de esta oferta. Te recomendamos actualizar tu perfil para mejorar tus oportunidades.
                  </div>
                  <?php endif; ?>
                  <?php endif; ?>

                  <?php if ($msgError): ?>
                  <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($msgError) ?>
                  </div>
                  <?php endif; ?>

                  <?php if ($matchPercent < 100 && !empty($missingSkills)): ?>
                  <!-- Warning Card: missing skills -->
                  <div class="utp-warning-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i class="bi bi-exclamation-circle"></i>
                      <span class="utp-warning-title">Compatibilidad de habilidades: <?= $matchPercent ?>%</span>
                    </div>
                    <p class="utp-warning-subtitle mb-1">Te faltan estas habilidades:</p>
                    <?php foreach ($missingSkills as $ms): ?>
                      <p class="utp-warning-text mb-0">• <?= htmlspecialchars($ms) ?></p>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>

                  <?php if ($aplicacion): ?>
                  <!-- Already Applied Card -->
                  <div class="utp-applied-card mb-4">
                    <div class="text-center">
                      <div class="utp-applied-icon mb-3">
                        <i class="bi bi-check2-circle"></i>
                      </div>
                      <h3 class="utp-applied-title">Ya aplicaste a esta oferta</h3>
                      <p class="utp-applied-text">Estado: <strong><?= htmlspecialchars(ucfirst($aplicacion['estado'])) ?></strong></p>
                      <p class="utp-applied-text">Revisa el estado en <a href="postulaciones.php">"Mis Postulaciones"</a></p>
                    </div>
                  </div>
                  <?php elseif ($oferta['estado'] === 'aprobada' && (int)($oferta['activo'] ?? 1) === 1 && (int)($oferta['vacantes'] ?? 0) > 0 && $oferta['estado_vacante'] !== 'rojo'): ?>
                  <!-- Apply Card -->
                  <div class="utp-detail-card mb-4">
                    <div class="text-center">
                      <div class="utp-miniicon utp-empty-icon-sm green mx-auto mb-3">
                        <i class="bi bi-send"></i>
                      </div>
                      <h3 class="utp-apply-title">¿Te interesa esta oferta?</h3>
                      <p class="utp-apply-text">Postúlate y el reclutador revisará tu perfil</p>
                      <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                        <input type="hidden" name="postularse" value="1">
                        <textarea name="mensaje_postulacion" class="form-control mb-3 utp-apply-textarea" rows="3" 
                                  placeholder="Mensaje opcional para el reclutador..."></textarea>
                        <button type="submit" class="btn btn-utp-red btn-utp-lg w-100">
                          <i class="bi bi-send me-2"></i>Postularme
                        </button>
                      </form>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Additional Info Card -->
                  <div class="utp-detail-card">
                    <h3 class="utp-info-card-title mb-4">Información adicional</h3>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Publicado por</span>
                      <span class="utp-info-value"><?= htmlspecialchars($oferta['creador'] ?? '—') ?></span>
                    </div>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Fecha de publicación</span>
                      <span class="utp-info-value"><?= $fechaPub ?></span>
                    </div>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Fecha de expiración</span>
                      <span class="utp-info-value"><?= $fechaExp ?></span>
                    </div>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Postulados</span>
                      <span class="utp-info-value"><?= $postulantesCount ?> candidato<?= $postulantesCount != 1 ? 's' : '' ?></span>
                    </div>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Email de contacto</span>
                      <?php if (!empty($oferta['contacto'])): ?>
                        <a class="utp-info-value" href="mailto:<?= htmlspecialchars($oferta['contacto']) ?>"><?= htmlspecialchars($oferta['contacto']) ?></a>
                      <?php else: ?>
                        <span class="utp-info-value">—</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($oferta['nombre_contacto'])): ?>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Nombre del contacto</span>
                      <span class="utp-info-value"><?= htmlspecialchars($oferta['nombre_contacto']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($oferta['puesto_contacto'])): ?>
                    <div class="utp-info-item mb-3">
                      <span class="utp-info-label">Puesto del contacto</span>
                      <span class="utp-info-value"><?= htmlspecialchars($oferta['puesto_contacto']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($oferta['telefono_contacto'])): ?>
                    <div class="utp-info-item">
                      <span class="utp-info-label">Teléfono de contacto</span>
                      <span class="utp-info-value"><?= htmlspecialchars($oferta['telefono_contacto']) ?></span>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
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
</body>
</html>
