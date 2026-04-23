<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/models/Notificacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$ofertaModel = new Oferta();
$postulacionModel = new Postulacion();

// Handle status change via POST
$msgExito = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $postId = (int)$_POST['postulacion_id'];
        $nuevoEstado = $_POST['nuevo_estado'] ?? '';
        // Verify ownership
        $post = $postulacionModel->getById($postId);
        if ($post && (int)$post['id_usuario_creador'] === (int)$_SESSION['usuario_id'] && in_array($nuevoEstado, ['preseleccionado', 'contactado', 'rechazado'])) {
            $postulacionModel->updateEstado($postId, $nuevoEstado);
            $msgExito = 'Estado actualizado correctamente.';

            // Notificar al egresado
            $notifModel = new Notificacion();
            if ($nuevoEstado === 'contactado') {
              $notifModel->onPostulanteSeleccionado(
                $post['oferta_titulo'],
                $post['egresado_usuario_id'],
                $post['egresado_email'] ?? null
              );
              // Req.9: solicitar feedback al docente sobre el resultado
              $notifModel->onFeedbackSolicitado(
                $post['oferta_titulo'],
                $post['egresado_nombre'] ?? 'el candidato',
                (int)$_SESSION['usuario_id'],
                $postId,
                $_SESSION['usuario_email'] ?? null
              );
            } elseif ($nuevoEstado === 'rechazado') {
              $notifModel->onPostulanteRechazado(
                $post['oferta_titulo'],
                $post['egresado_usuario_id'],
                $post['egresado_email'] ?? null
              );
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_habilidades_blandas'])) {
  if (Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $postId = (int)($_POST['postulacion_id'] ?? 0);
    $habilidades = $_POST['habilidad'] ?? [];
    $cumpleVals = $_POST['cumple'] ?? [];
    $post = $postulacionModel->getById($postId);

    if ($post && (int)$post['id_usuario_creador'] === (int)$_SESSION['usuario_id']) {
      foreach ($habilidades as $idx => $habilidad) {
        $habilidad = trim((string)$habilidad);
        $cumple = $cumpleVals[$idx] ?? '';
        if ($habilidad === '' || ($cumple !== '1' && $cumple !== '0')) {
          continue;
        }
        $postulacionModel->evaluarHabilidadBlanda($postId, $habilidad, (int)$cumple, (int)$_SESSION['usuario_id']);
      }
      $msgExito = 'Evaluación de habilidades blandas guardada correctamente.';
    }
  }
}

// Filters
$filtroOferta = isset($_GET['oferta']) ? (int)$_GET['oferta'] : null;
$filtroEstado = $_GET['estado'] ?? null;

// Get data
$misOfertas = $ofertaModel->getByUserId($_SESSION['usuario_id']);
$postulantes = $ofertaModel->getPostulantesByUser(
    $_SESSION['usuario_id'],
    $filtroOferta ?: null,
    $filtroEstado ?: null
);

// Status mapping
$estadoPostBadge = [
    'pendiente'       => ['label' => 'Postulado',      'class' => 'utp-badge-postulado'],
    'preseleccionado' => ['label' => 'Validado',       'class' => 'utp-badge-validado'],
    'contactado'      => ['label' => 'Seleccionado',   'class' => 'utp-badge-seleccionado'],
    'rechazado'       => ['label' => 'Rechazado',      'class' => 'utp-badge-rechazado'],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alumnos / Postulantes - Docente UTP</title>

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
      currentPage: 'postulantes',
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
        <div class="p-4 p-lg-5">

          <header class="mb-4">
            <h1 class="utp-h1 mb-2">Alumnos / Postulantes</h1>
            <p class="text-muted mb-0">Visualiza y gestiona los egresados que han aplicado a tus ofertas</p>
          </header>

          <?php if ($msgExito): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
              <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Filtros -->
          <div class="utp-card mb-3">
            <form method="GET" class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Oferta</label>
                <select name="oferta" class="form-select utp-select" onchange="this.form.submit()">
                  <option value="">Todas las ofertas</option>
                  <?php foreach ($misOfertas as $of): ?>
                    <option value="<?= (int)$of['id'] ?>" <?= $filtroOferta == $of['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($of['titulo']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Estado</label>
                <select name="estado" class="form-select utp-select" onchange="this.form.submit()">
                  <option value="">Todos los estados</option>
                  <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Postulado</option>
                  <option value="preseleccionado" <?= $filtroEstado === 'preseleccionado' ? 'selected' : '' ?>>Validado</option>
                  <option value="contactado" <?= $filtroEstado === 'contactado' ? 'selected' : '' ?>>Seleccionado</option>
                  <option value="rechazado" <?= $filtroEstado === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Buscar</label>
                <div class="position-relative">
                  <i class="bi bi-search utp-search-icon"></i>
                  <input type="text" class="form-control utp-input utp-search-input" id="searchInput" placeholder="Nombre o matrícula...">
                </div>
              </div>
            </form>
          </div>

          <p class="text-muted small mb-3">Mostrando <?= count($postulantes) ?> postulante<?= count($postulantes) !== 1 ? 's' : '' ?></p>

          <?php if (empty($postulantes)): ?>
            <div class="utp-card text-center py-5">
              <div class="utp-miniicon blue mx-auto mb-3" style="width:64px;height:64px;border-radius:50%;">
                <i class="bi bi-people" style="font-size:28px;"></i>
              </div>
              <h3 style="font-size:20px; font-weight:600; color:#121212;">Sin postulantes</h3>
              <p style="color:#757575; font-size:16px; margin-top:8px;">
                Aún no hay egresados que hayan aplicado a tus ofertas<?= $filtroOferta || $filtroEstado ? ' con estos filtros' : '' ?>.
              </p>
            </div>
          <?php else: ?>
          <div class="utp-postulantes-list">
            <?php foreach ($postulantes as $p):
              $pNombre = htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellidos'] ?? '')));
              $pInitials = mb_strtoupper(mb_substr($p['nombre'] ?? '', 0, 1) . mb_substr($p['apellidos'] ?? '', 0, 1));
              $pMatricula = htmlspecialchars($p['matricula'] ?? '—');
              $pEmail = htmlspecialchars($p['email'] ?? $p['correo_personal'] ?? '—');
              $pTelefono = htmlspecialchars($p['telefono'] ?? '—');
              $estado = $p['estado'] ?? 'pendiente';
              $badge = $estadoPostBadge[$estado] ?? $estadoPostBadge['pendiente'];
              $skills = json_decode($p['egresado_habilidades'] ?? '[]', true) ?: [];
              $skillsBlandas = json_decode($p['habilidades_blandas'] ?? '[]', true) ?: [];
              if (!empty($skillsBlandas)) {
                $postulacionModel->inicializarChecklistHabilidadesBlandas((int)$p['id'], $skillsBlandas);
              }
              $evaluacionesBlandas = $postulacionModel->getEvaluacionHabilidadesBlandas((int)$p['id']);
              $mapEvaluaciones = [];
              foreach ($evaluacionesBlandas as $ev) {
                $mapEvaluaciones[mb_strtolower((string)$ev['habilidad'])] = $ev;
              }
              $fechaPost = $p['fecha_postulacion'] ? date('d/m/Y', strtotime($p['fecha_postulacion'])) : '—';
            ?>
            <article class="utp-postulante-card" data-name="<?= strtolower($pNombre) ?>" data-matricula="<?= strtolower($pMatricula) ?>">
              <div class="utp-postulante-header">
                <div class="utp-useravatar utp-useravatar-lg"><?= $pInitials ?></div>
                <div class="utp-postulante-info">
                  <h3 class="utp-postulante-name"><?= $pNombre ?></h3>
                  <p class="utp-postulante-matricula">Matrícula: <?= $pMatricula ?></p>
                  <div class="utp-postulante-badges">
                    <span class="utp-badge-status <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                  </div>
                  <p class="utp-postulante-oferta">
                    Oferta: <?= htmlspecialchars($p['oferta_titulo'] ?? '') ?>
                    <span class="text-muted small ms-2"><?= $fechaPost ?></span>
                  </p>
                </div>
              </div>

              <?php if (!empty($skills)): ?>
              <div class="utp-postulante-skills">
                <span class="utp-skill-label">Habilidades:</span>
                <div class="utp-postulante-chips">
                  <?php foreach (array_slice($skills, 0, 6) as $s): ?>
                    <span class="utp-chip-sm"><?= htmlspecialchars($s) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <?php if (!empty($skillsBlandas)): ?>
              <div class="utp-postulante-skills mt-2">
                <span class="utp-skill-label">Habilidades blandas (cumple/no cumple):</span>
                <form method="POST" class="mt-2">
                  <?= Security::csrfField() ?>
                  <input type="hidden" name="guardar_habilidades_blandas" value="1">
                  <input type="hidden" name="postulacion_id" value="<?= (int)$p['id'] ?>">
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-2">
                      <thead>
                        <tr>
                          <th>Habilidad</th>
                          <th style="width: 180px;">Evaluación</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($skillsBlandas as $hb):
                          $evRow = $mapEvaluaciones[mb_strtolower((string)$hb)] ?? null;
                          $evVal = isset($evRow['cumple']) ? (string)$evRow['cumple'] : '';
                        ?>
                        <tr>
                          <td>
                            <?= htmlspecialchars($hb) ?>
                            <input type="hidden" name="habilidad[]" value="<?= htmlspecialchars($hb) ?>">
                          </td>
                          <td>
                            <select name="cumple[]" class="form-select form-select-sm">
                              <option value="" <?= $evVal === '' ? 'selected' : '' ?>>Pendiente</option>
                              <option value="1" <?= $evVal === '1' ? 'selected' : '' ?>>Cumple</option>
                              <option value="0" <?= $evVal === '0' ? 'selected' : '' ?>>No cumple</option>
                            </select>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-save me-1"></i> Guardar evaluación
                  </button>
                </form>
              </div>
              <?php endif; ?>

              <div class="utp-postulante-actions">
                <?php if ($estado !== 'contactado'): ?>
                <form method="POST" class="d-inline">
                  <?= Security::csrfField() ?>
                  <input type="hidden" name="cambiar_estado" value="1">
                  <input type="hidden" name="postulacion_id" value="<?= (int)$p['id'] ?>">
                  <input type="hidden" name="nuevo_estado" value="contactado">
                  <button type="submit" class="btn utp-btn-green" onclick="return confirm('¿Seleccionar a este postulante?')">
                    <i class="bi bi-check-circle"></i> Seleccionar
                  </button>
                </form>
                <?php endif; ?>
                <?php if ($estado !== 'rechazado'): ?>
                <form method="POST" class="d-inline">
                  <?= Security::csrfField() ?>
                  <input type="hidden" name="cambiar_estado" value="1">
                  <input type="hidden" name="postulacion_id" value="<?= (int)$p['id'] ?>">
                  <input type="hidden" name="nuevo_estado" value="rechazado">
                  <button type="submit" class="btn utp-btn-outline" onclick="return confirm('¿Rechazar a este postulante?')">
                    <i class="bi bi-x-circle"></i> Rechazar
                  </button>
                </form>
                <?php endif; ?>
                <button type="button" class="btn utp-btn-outline-blue btn-contactar"
                  data-initials="<?= htmlspecialchars($pInitials) ?>"
                  data-nombre="<?= htmlspecialchars($pNombre) ?>"
                  data-matricula="<?= htmlspecialchars($pMatricula) ?>"
                  data-email="<?= htmlspecialchars($pEmail) ?>"
                  data-telefono="<?= htmlspecialchars($pTelefono) ?>">
                  <i class="bi bi-envelope"></i> Contactar
                </button>
                <?php if ($estado === 'contactado' && empty($p['feedback_resultado'])): ?>
                <button type="button" class="btn btn-sm btn-outline-warning btn-feedback"
                  data-id="<?= (int)$p['id'] ?>"
                  data-nombre="<?= htmlspecialchars($pNombre) ?>">
                  <i class="bi bi-chat-dots"></i> Dar feedback
                </button>
                <?php elseif ($estado === 'contactado' && !empty($p['feedback_resultado'])): ?>
                <span class="badge <?= $p['feedback_resultado'] === 'satisfecho' ? 'bg-success' : 'bg-secondary' ?>" title="<?= htmlspecialchars($p['feedback_comentario'] ?? '') ?>">
                  <i class="bi bi-chat-check"></i> <?= $p['feedback_resultado'] === 'satisfecho' ? 'Satisfecho' : 'Insatisfecho' ?>
                  <?php if ($p['feedback_trabajo'] !== null): ?>
                    &bull; <?= $p['feedback_trabajo'] ? 'Obtuvo el empleo' : 'No obtuvo el empleo' ?>
                  <?php endif; ?>
                </span>
                <?php endif; ?>
                <?php if (empty($p['retirada'])): ?>
                <button type="button" class="btn utp-btn-outline" onclick="darDeBajaPostulacion(<?= (int)$p['id'] ?>)">
                  <i class="bi bi-archive"></i> Dar de baja
                </button>
                <?php endif; ?>
                <button type="button" class="btn utp-btn-outline" onclick="borrarPostulacion(<?= (int)$p['id'] ?>)">
                  <i class="bi bi-trash"></i> Borrar
                </button>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>

  <!-- Modal Feedback -->
  <div class="utp-modal-overlay" id="modalFeedback" style="display:none;">
    <div class="utp-modal-contact">
      <button type="button" class="utp-modal-close" aria-label="Cerrar" onclick="cerrarModalFeedback()">
        <i class="bi bi-x-lg"></i>
      </button>
      <div class="utp-modal-header">
        <h2 class="utp-modal-title">Feedback del contacto</h2>
        <p class="utp-modal-subtitle" id="feedbackSubtitle">¿Quedaste satisfecho con el candidato?</p>
      </div>
      <div class="utp-modal-body">
        <input type="hidden" id="feedbackPostId" value="">
        <div class="mb-3">
          <label class="form-label fw-semibold">¿Quedaste satisfecho con las propuestas?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="fb_resultado" id="fbSatisfecho" value="satisfecho">
              <label class="form-check-label" for="fbSatisfecho">Sí, satisfecho</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="fb_resultado" id="fbInsatisfecho" value="insatisfecho">
              <label class="form-check-label" for="fbInsatisfecho">No, insatisfecho</label>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">¿El candidato obtuvo el empleo?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="fb_trabajo" id="fbTrabSi" value="1">
              <label class="form-check-label" for="fbTrabSi">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="fb_trabajo" id="fbTrabNo" value="0">
              <label class="form-check-label" for="fbTrabNo">No</label>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">¿Por qué? (opcional)</label>
          <textarea id="feedbackComentario" class="form-control" rows="3" placeholder="Cuéntanos más sobre el resultado..."></textarea>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-utp-red w-100" onclick="enviarFeedback()">
            <i class="bi bi-send me-1"></i> Enviar feedback
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="cerrarModalFeedback()">Cancelar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Contactar -->
  <div class="utp-modal-overlay" id="modalContactar" style="display:none;">
    <div class="utp-modal-contact">
      <button type="button" class="utp-modal-close" aria-label="Cerrar" onclick="cerrarModalContacto()">
        <i class="bi bi-x-lg"></i>
      </button>
      <div class="utp-modal-header">
        <h2 class="utp-modal-title">Información de Contacto</h2>
        <p class="utp-modal-subtitle">Datos del egresado para comunicarte directamente</p>
      </div>
      <div class="utp-modal-body">
        <div class="utp-contact-user">
          <div class="utp-useravatar utp-useravatar-md" id="modalAvatar"></div>
          <div class="utp-contact-user-info">
            <div class="utp-contact-name" id="modalNombre"></div>
            <div class="utp-contact-matricula" id="modalMatricula"></div>
          </div>
        </div>
        <div class="utp-contact-fields">
          <div class="utp-contact-field">
            <span class="utp-contact-label">Correo electrónico</span>
            <a class="utp-contact-value" id="modalEmail" href="#" target="_blank" rel="noopener"></a>
          </div>
          <div class="utp-contact-field">
            <span class="utp-contact-label">Teléfono</span>
            <span class="utp-contact-value" id="modalTelefono"></span>
          </div>
        </div>
        <button type="button" class="btn btn-utp-red w-100" onclick="cerrarModalContacto()">Cerrar</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <script>
    // Modal contacto via data-attributes (XSS-safe)
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-contactar');
      if (btn) {
        document.getElementById('modalAvatar').textContent = btn.dataset.initials;
        document.getElementById('modalNombre').textContent = btn.dataset.nombre;
        document.getElementById('modalMatricula').textContent = btn.dataset.matricula;
        var emailEl = document.getElementById('modalEmail');
        emailEl.textContent = btn.dataset.email;
        emailEl.href = 'mailto:' + btn.dataset.email;
        document.getElementById('modalTelefono').textContent = btn.dataset.telefono;
        document.getElementById('modalContactar').style.display = 'flex';
      }
    });
    function cerrarModalContacto() {
      document.getElementById('modalContactar').style.display = 'none';
    }
    // Close on overlay click
    document.getElementById('modalContactar').addEventListener('click', function(e) {
      if (e.target === this) cerrarModalContacto();
    });

    // Client-side search
    document.getElementById('searchInput').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.utp-postulante-card').forEach(function(card) {
        const name = card.getAttribute('data-name') || '';
        const mat = card.getAttribute('data-matricula') || '';
        card.style.display = (name.includes(q) || mat.includes(q)) ? '' : 'none';
      });
    });

    function postAction(url, payload, okMsg) {
      fetch(url, {
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
          alert(okMsg);
          location.reload();
          return;
        }
        alert('Error: ' + (data.error || 'No se pudo completar la acción'));
      })
      .catch(() => alert('Error de conexión'));
    }

    function darDeBajaPostulacion(postulacionId) {
      if (confirm('¿Dar de baja esta postulación?')) {
        postAction('../../public/api/postulaciones-update.php?action=retirar&postulacion_id=' + postulacionId,
          {postulacion_id: postulacionId},
          'Postulación dada de baja correctamente');
      }
    }

    function borrarPostulacion(postulacionId) {
      if (confirm('¿Eliminar permanentemente esta postulación? Esta acción no se puede deshacer.')) {
        postAction('../../public/api/postulaciones-update.php?action=eliminar&postulacion_id=' + postulacionId,
          {postulacion_id: postulacionId},
          'Postulación eliminada correctamente');
      }
    }

    // Modal feedback
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-feedback');
      if (btn) {
        document.getElementById('feedbackPostId').value  = btn.dataset.id;
        document.getElementById('feedbackSubtitle').textContent = '¿Cómo resultó el contacto con ' + btn.dataset.nombre + '?';
        document.querySelectorAll('input[name="fb_resultado"], input[name="fb_trabajo"]').forEach(function(r){ r.checked = false; });
        document.getElementById('feedbackComentario').value = '';
        document.getElementById('modalFeedback').style.display = 'flex';
      }
    });
    function cerrarModalFeedback() {
      document.getElementById('modalFeedback').style.display = 'none';
    }
    document.getElementById('modalFeedback').addEventListener('click', function(e) {
      if (e.target === this) cerrarModalFeedback();
    });
    function enviarFeedback() {
      var resultado = document.querySelector('input[name="fb_resultado"]:checked')?.value;
      if (!resultado) { alert('Selecciona si quedaste satisfecho o no.'); return; }
      var trabajo   = document.querySelector('input[name="fb_trabajo"]:checked')?.value ?? null;
      var postId    = document.getElementById('feedbackPostId').value;
      var comentario= document.getElementById('feedbackComentario').value;
      fetch('../../public/api/feedback-postulacion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.UTP_CSRF_TOKEN || '' },
        body: JSON.stringify({
          postulacion_id: parseInt(postId),
          resultado: resultado,
          quedo_en_trabajo: trabajo !== null ? parseInt(trabajo) : null,
          comentario: comentario,
          csrf_token: window.UTP_CSRF_TOKEN || ''
        })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) { cerrarModalFeedback(); location.reload(); }
        else alert('Error: ' + (data.error || 'No se pudo guardar'));
      })
      .catch(() => alert('Error de conexión'));
    }
  </script>
</body>
</html>
