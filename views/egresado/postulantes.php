<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre = $_SESSION['usuario_nombre'] ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName = trim($nombre . ' ' . $apellidos);
$initials = mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellidos, 0, 1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$ofertaModel = new Oferta();
$filtroOferta = isset($_GET['oferta']) ? (int)$_GET['oferta'] : null;
$filtroEstado = $_GET['estado'] ?? null;

$misOfertas = $ofertaModel->getByUserId($_SESSION['usuario_id']);
$postulantes = $ofertaModel->getPostulantesByUser(
    $_SESSION['usuario_id'],
    $filtroOferta ?: null,
    $filtroEstado ?: null
);

$estadoPostBadge = [
    'pendiente'       => ['label' => 'Postulado',    'class' => 'utp-badge-postulado'],
    'preseleccionado' => ['label' => 'En revisión',  'class' => 'utp-badge-validado'],
    'contactado'      => ['label' => 'Seleccionado', 'class' => 'utp-badge-seleccionado'],
    'rechazado'       => ['label' => 'Rechazado',    'class' => 'utp-badge-rechazado'],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Postulantes - Egresados UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'egresado', roleLabel: 'Egresado',
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
        <div class="p-4 p-lg-5">
          <header class="mb-4">
            <h1 class="utp-h1 mb-2">Postulantes de Mis Ofertas</h1>
            <p class="text-muted mb-0">Selecciona candidatos y consulta sus datos de contacto.</p>
          </header>

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
                  <option value="preseleccionado" <?= $filtroEstado === 'preseleccionado' ? 'selected' : '' ?>>En revisión</option>
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
              <div class="utp-miniicon utp-empty-icon blue mx-auto mb-3">
                <i class="bi bi-people"></i>
              </div>
              <h3 class="utp-empty-title">Sin postulantes</h3>
              <p class="utp-empty-text">Aún no hay postulaciones para tus ofertas con estos filtros.</p>
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

              <div class="utp-postulante-actions">
                <?php if ($estado !== 'contactado'): ?>
                <button type="button" class="btn utp-btn-green" onclick="actualizarEstado(<?= (int)$p['id'] ?>, 'contactado', '¿Seleccionar a este postulante?')">
                  <i class="bi bi-check-circle"></i> Seleccionar
                </button>
                <?php endif; ?>
                <?php if ($estado !== 'rechazado'): ?>
                <button type="button" class="btn utp-btn-outline" onclick="actualizarEstado(<?= (int)$p['id'] ?>, 'rechazado', '¿Rechazar a este postulante?')">
                  <i class="bi bi-x-circle"></i> Rechazar
                </button>
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
                <span class="badge <?= $p['feedback_resultado'] === 'satisfecho' ? 'bg-success' : 'bg-secondary' ?>">
                  <i class="bi bi-chat-check"></i> <?= $p['feedback_resultado'] === 'satisfecho' ? 'Satisfecho' : 'Insatisfecho' ?>
                  <?php if ($p['feedback_trabajo'] !== null): ?>
                    &bull; <?= $p['feedback_trabajo'] ? 'Obtuvo el empleo' : 'No obtuvo el empleo' ?>
                  <?php endif; ?>
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

  <!-- Modal Feedback -->
  <div class="utp-modal-overlay d-none" id="modalFeedback">
    <div class="utp-modal-contact">
      <button type="button" class="utp-modal-close" aria-label="Cerrar" onclick="cerrarModalFeedback()">
        <i class="bi bi-x-lg"></i>
      </button>
      <div class="utp-modal-header">
        <h2 class="utp-modal-title">Feedback del contacto</h2>
        <p class="utp-modal-subtitle" id="feedbackSubtitle">¿Cuál fue el resultado del contacto?</p>
      </div>
      <div class="utp-modal-body">
        <input type="hidden" id="feedbackPostId" value="">
        <div class="mb-3">
          <label class="form-label fw-semibold">¿Quedaste satisfecho con el proceso?</label>
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
          <label class="form-label fw-semibold">Comentario (opcional)</label>
          <textarea id="feedbackComentario" class="form-control" rows="3" placeholder="Cuéntanos más..."></textarea>
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

  <div class="utp-modal-overlay d-none" id="modalContactar">
    <div class="utp-modal-contact">
      <button type="button" class="utp-modal-close" aria-label="Cerrar" onclick="cerrarModalContacto()">
        <i class="bi bi-x-lg"></i>
      </button>
      <div class="utp-modal-header">
        <h2 class="utp-modal-title">Información de Contacto</h2>
        <p class="utp-modal-subtitle">Datos del postulante para comunicarte directamente</p>
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
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
  <script>
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
        document.getElementById('modalContactar').classList.remove('d-none');
      }
    });

    function cerrarModalContacto() {
      document.getElementById('modalContactar').classList.add('d-none');
    }

    document.getElementById('modalContactar').addEventListener('click', function(e) {
      if (e.target === this) cerrarModalContacto();
    });

    document.getElementById('searchInput').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.utp-postulante-card').forEach(function(card) {
        const name = card.getAttribute('data-name') || '';
        const mat = card.getAttribute('data-matricula') || '';
        card.style.display = (name.includes(q) || mat.includes(q)) ? '' : 'none';
      });
    });

    // Modal Feedback
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-feedback');
      if (btn) {
        document.getElementById('feedbackPostId').value = btn.dataset.id;
        document.getElementById('feedbackSubtitle').textContent = '¿Cómo resultó el contacto con ' + btn.dataset.nombre + '?';
        document.querySelectorAll('input[name="fb_resultado"], input[name="fb_trabajo"]').forEach(function(r){ r.checked = false; });
        document.getElementById('feedbackComentario').value = '';
        document.getElementById('modalFeedback').classList.remove('d-none');
      }
    });
    function cerrarModalFeedback() {
      document.getElementById('modalFeedback').classList.add('d-none');
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

    function actualizarEstado(postulacionId, estado, mensajeConfirmacion) {
      if (!confirm(mensajeConfirmacion)) {
        return;
      }

      fetch('../../public/api/postulaciones-update.php?action=actualizar_estado&postulacion_id=' + postulacionId, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
        },
        body: JSON.stringify({
          postulacion_id: postulacionId,
          estado: estado,
          csrf_token: window.UTP_CSRF_TOKEN || ''
        })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
          return;
        }
        alert('Error: ' + (data.error || 'No se pudo actualizar el estado'));
      })
      .catch(function() {
        alert('Error de conexión');
      });
    }
  </script>
</body>
</html>
