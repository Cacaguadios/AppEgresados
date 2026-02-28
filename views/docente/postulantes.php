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
                $notifModel->onPostulanteSeleccionado($post['oferta_titulo'], $post['egresado_usuario_id']);
            } elseif ($nuevoEstado === 'rechazado') {
                $notifModel->onPostulanteRechazado($post['oferta_titulo'], $post['egresado_usuario_id']);
            }
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
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
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
              </div>
            </article>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>
      </main>
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
            <span class="utp-contact-value" id="modalEmail"></span>
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
    // Modal contacto via data-attributes (XSS-safe)
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-contactar');
      if (btn) {
        document.getElementById('modalAvatar').textContent = btn.dataset.initials;
        document.getElementById('modalNombre').textContent = btn.dataset.nombre;
        document.getElementById('modalMatricula').textContent = btn.dataset.matricula;
        document.getElementById('modalEmail').textContent = btn.dataset.email;
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
  </script>
</body>
</html>
