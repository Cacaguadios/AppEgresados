<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Egresado.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Load all egresados
$egresadoModel = new Egresado();
$egresados = $egresadoModel->getAllWithUser();

// Collect unique generations and specialties for filters
$generaciones = [];
$especialidades = [];
foreach ($egresados as $e) {
    if (!empty($e['generacion'])) $generaciones[$e['generacion']] = true;
    if (!empty($e['especialidad'])) $especialidades[$e['especialidad']] = true;
}
krsort($generaciones);
ksort($especialidades);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Directorio de Egresados - Docente UTP</title>

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
      currentPage: 'directorio',
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
            <h1 class="utp-h1 mb-2">Directorio de Egresados</h1>
            <p class="text-muted mb-0">Consulta el directorio público de egresados de TI (información de CV y habilidades)</p>
          </header>

          <div class="utp-app-info-box mb-4">
            <span class="fw-semibold">Información pública:</span> Este directorio muestra únicamente datos públicos de CV. Los datos privados de seguimiento (salarios, contratos) no son visibles.
          </div>

          <!-- Filtros -->
          <div class="utp-card mb-3">
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Buscar</label>
                <div class="position-relative">
                  <i class="bi bi-search utp-search-icon"></i>
                  <input type="text" class="form-control utp-input utp-search-input" id="searchInput" placeholder="Nombre, especialidad, habilidad...">
                </div>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Generación</label>
                <select class="form-select utp-select" id="filterGen">
                  <option value="">Todas las generaciones</option>
                  <?php foreach ($generaciones as $g => $_): ?>
                    <option value="<?= (int)$g ?>"><?= (int)$g ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Especialidad</label>
                <select class="form-select utp-select" id="filterEsp">
                  <option value="">Todas las especialidades</option>
                  <?php foreach ($especialidades as $esp => $_): ?>
                    <option value="<?= htmlspecialchars($esp) ?>"><?= htmlspecialchars($esp) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <p class="text-muted small mb-3" id="counterText"><?= count($egresados) ?> egresado<?= count($egresados) !== 1 ? 's' : '' ?> encontrado<?= count($egresados) !== 1 ? 's' : '' ?></p>

          <?php if (empty($egresados)): ?>
            <div class="utp-card text-center py-5">
              <h3 style="font-size:20px; font-weight:600; color:#121212;">No hay egresados registrados</h3>
            </div>
          <?php else: ?>
          <div class="utp-postulantes-list" id="egresadosList">
            <?php foreach ($egresados as $e):
              $eName = trim(($e['nombre_usuario'] ?? '') . ' ' . ($e['apellidos'] ?? ''));
              $eInitials = mb_strtoupper(mb_substr($e['nombre_usuario'] ?? '', 0, 1) . mb_substr($e['apellidos'] ?? '', 0, 1));
              $skills = json_decode($e['habilidades'] ?? '[]', true) ?: [];
              $gen = $e['generacion'] ?? '';
              $esp = $e['especialidad'] ?? '';
              $hasCV = !empty($e['cv_path']);
            ?>
            <article class="utp-egresado-card" data-name="<?= strtolower(htmlspecialchars($eName)) ?>" data-gen="<?= htmlspecialchars($gen) ?>" data-esp="<?= strtolower(htmlspecialchars($esp)) ?>" data-skills="<?= strtolower(htmlspecialchars(implode(',', $skills))) ?>">
              <div class="utp-egresado-header">
                <div class="utp-avatar-green utp-avatar-md"><?= htmlspecialchars($eInitials) ?></div>
                <div class="utp-egresado-info">
                  <h3 class="utp-egresado-name"><?= htmlspecialchars($eName) ?></h3>
                  <?php if ($gen): ?>
                    <p class="utp-egresado-gen">Generación: <?= htmlspecialchars($gen) ?></p>
                  <?php endif; ?>
                  <?php if ($esp): ?>
                    <p class="utp-egresado-esp"><?= htmlspecialchars($esp) ?></p>
                  <?php endif; ?>
                </div>
              </div>

              <?php if (!empty($skills)): ?>
              <div class="utp-egresado-skills">
                <span class="utp-skill-label">Habilidades:</span>
                <div class="utp-egresado-chips">
                  <?php foreach (array_slice($skills, 0, 6) as $s): ?>
                    <span class="utp-chip-green"><?= htmlspecialchars($s) ?></span>
                  <?php endforeach; ?>
                  <?php if (count($skills) > 6): ?>
                    <span class="utp-chip-green">+<?= count($skills) - 6 ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>

              <div class="utp-egresado-actions">
                <?php if ($hasCV): ?>
                  <button class="btn utp-btn-outline flex-fill" type="button">
                    <i class="bi bi-file-earmark-person"></i> Ver CV
                  </button>
                <?php else: ?>
                  <button class="btn utp-btn-outline utp-btn-disabled flex-fill" type="button" disabled>
                    <i class="bi bi-file-earmark-person"></i> Sin CV
                  </button>
                <?php endif; ?>
                <button class="btn btn-utp-red flex-fill btn-invitar" type="button" data-nombre="<?= htmlspecialchars($eName) ?>">
                  <i class="bi bi-send"></i> Invitar a postularse
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

  <!-- Toast -->
  <div class="utp-toast" id="toastInvitacion" style="display:none;">
    <i class="bi bi-check-circle-fill utp-toast-icon"></i>
    <div class="utp-toast-content">
      <div class="utp-toast-title">Invitación enviada</div>
      <div class="utp-toast-message" id="toastMessage"></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
  <script>
    // Invitar via data-attribute (XSS-safe)
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.btn-invitar');
      if (btn) {
        var nombre = btn.dataset.nombre;
        document.getElementById('toastMessage').textContent = 'Se ha enviado una notificación a ' + nombre;
        var toast = document.getElementById('toastInvitacion');
        toast.style.display = 'flex';
        setTimeout(function() { toast.style.display = 'none'; }, 3000);
      }
    });

    // Client-side filtering
    function applyFilters() {
      var q = document.getElementById('searchInput').value.toLowerCase();
      var gen = document.getElementById('filterGen').value;
      var esp = document.getElementById('filterEsp').value.toLowerCase();
      var cards = document.querySelectorAll('.utp-egresado-card');
      var visible = 0;

      cards.forEach(function(card) {
        var name = card.getAttribute('data-name') || '';
        var cGen = card.getAttribute('data-gen') || '';
        var cEsp = card.getAttribute('data-esp') || '';
        var cSkills = card.getAttribute('data-skills') || '';

        var matchSearch = !q || name.includes(q) || cEsp.includes(q) || cSkills.includes(q);
        var matchGen = !gen || cGen === gen;
        var matchEsp = !esp || cEsp === esp;

        if (matchSearch && matchGen && matchEsp) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });

      document.getElementById('counterText').textContent = visible + ' egresado' + (visible !== 1 ? 's' : '') + ' encontrado' + (visible !== 1 ? 's' : '');
    }

    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterGen').addEventListener('change', applyFilters);
    document.getElementById('filterEsp').addEventListener('change', applyFilters);
  </script>
</body>
</html>
