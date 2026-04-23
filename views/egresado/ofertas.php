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

// ─── Load offers from DB ───
require_once __DIR__ . '/../../app/models/Oferta.php';
$ofertaModel = new Oferta();
$ofertas = $ofertaModel->getApprovedAndActive();

// Collect all unique skills and locations for filters
$allSkills = [];
$allLocations = [];
foreach ($ofertas as $o) {
    $skills = json_decode($o['habilidades'] ?? '[]', true) ?: [];
    $allSkills = array_merge($allSkills, $skills);
    if (!empty($o['ubicacion'])) $allLocations[] = $o['ubicacion'];
}
$allSkills = array_values(array_unique($allSkills));
$allLocations = array_values(array_unique($allLocations));
sort($allSkills);
sort($allLocations);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ofertas Laborales - Egresados UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
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
            <div class="container-fluid px-0 py-4 py-md-5">

              <!-- Title -->
              <section class="mb-4">
                <h1 class="utp-h1 mb-2">Ofertas laborales</h1>
                <p class="utp-subtitle mb-0">Encuentra oportunidades laborales validadas para egresados UTP</p>
              </section>

              <!-- Filters Card -->
              <section class="utp-filter-card mb-4">
                <div class="position-relative mb-3">
                  <i class="bi bi-search utp-search-icon"></i>
                  <input type="text" class="form-control utp-input utp-search-input"
                         placeholder="Buscar por título, empresa o descripción..." id="searchOffers">
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <select class="form-select utp-select" id="filterLocation">
                      <option selected value="">Todas las ubicaciones</option>
                      <?php foreach ($allLocations as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <select class="form-select utp-select" id="filterModality">
                      <option selected value="">Todas las modalidades</option>
                      <option value="presencial">Presencial</option>
                      <option value="remoto">Remoto</option>
                      <option value="hibrido">Híbrido</option>
                    </select>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <select class="form-select utp-select" id="filterStatus">
                      <option selected value="">Todos los estados</option>
                      <option value="verde">Disponible</option>
                      <option value="amarillo">Con postulados</option>
                      <option value="rojo">Vacante cubierta</option>
                    </select>
                  </div>
                </div>

                <div class="mb-0">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-funnel"></i>
                    <span class="fw-medium">Filtrar por habilidades:</span>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($allSkills as $skill): ?>
                      <button type="button" class="utp-skill-chip" data-skill="<?= htmlspecialchars($skill) ?>"><?= htmlspecialchars($skill) ?></button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </section>

              <!-- Results Count -->
              <p class="utp-results-count mb-3">
                Mostrando <strong id="showCount"><?= count($ofertas) ?></strong> de <strong><?= count($ofertas) ?></strong> ofertas
              </p>

              <!-- Job Cards List -->
              <section class="row g-4" id="jobCardsContainer">

                <?php if (empty($ofertas)): ?>
                  <div class="col-12">
                    <div class="utp-card text-center py-5">
                      <div class="utp-empty-briefcase-icon mx-auto mb-3">
                        <i class="bi bi-briefcase"></i>
                      </div>
                      <h3 class="utp-empty-muted-title mt-3">No hay ofertas disponibles</h3>
                      <p class="text-muted mb-0">Vuelve más tarde para ver nuevas oportunidades.</p>
                    </div>
                  </div>
                <?php else: ?>
                  <?php foreach ($ofertas as $oferta):
                    $habilidades = json_decode($oferta['habilidades'] ?? '[]', true) ?: [];
                    $modalidadLabel = ['presencial'=>'Presencial','remoto'=>'Remoto','hibrido'=>'Híbrido'][$oferta['modalidad']] ?? $oferta['modalidad'];
                    
                    // Badge color and label
                    $badgeColor = 'green'; $badgeLabel = 'Disponible';
                    if ($oferta['estado_vacante'] === 'amarillo') { $badgeColor = 'yellow'; $badgeLabel = 'Con postulados'; }
                    if ($oferta['estado_vacante'] === 'rojo') { $badgeColor = 'red'; $badgeLabel = 'Vacante cubierta'; }
                    
                    $fechaPublicacion = date('d/m/Y', strtotime($oferta['fecha_aprobacion'] ?? $oferta['fecha_creacion']));
                  ?>
                  <div class="col-12 col-md-6 col-lg-6">
                  <article class="utp-job-card"
                           data-titulo="<?= htmlspecialchars(mb_strtolower($oferta['titulo'])) ?>"
                           data-empresa="<?= htmlspecialchars(mb_strtolower($oferta['empresa'] ?? '')) ?>"
                           data-desc="<?= htmlspecialchars(mb_strtolower(mb_substr($oferta['descripcion'],0,200))) ?>"
                           data-ubicacion="<?= htmlspecialchars($oferta['ubicacion'] ?? '') ?>"
                           data-modalidad="<?= htmlspecialchars($oferta['modalidad'] ?? '') ?>"
                           data-estado="<?= htmlspecialchars($oferta['estado_vacante']) ?>"
                           data-habilidades="<?= htmlspecialchars(implode(',', array_map('mb_strtolower', $habilidades))) ?>">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-3">
                      <div class="flex-grow-1">
                        <h2 class="utp-job-title mb-2"><?= htmlspecialchars($oferta['titulo']) ?></h2>
                        <div class="d-flex align-items-center gap-2 text-muted">
                          <i class="bi bi-building"></i>
                          <span><?= htmlspecialchars($oferta['empresa'] ?? 'Sin empresa') ?></span>
                        </div>
                      </div>
                      <span class="utp-status-badge <?= $badgeColor ?>">
                        <span class="utp-status-dot"></span>
                        <?= $badgeLabel ?>
                      </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mb-3 text-muted small">
                      <div class="d-flex align-items-center gap-1"><i class="bi bi-geo-alt"></i><span><?= htmlspecialchars($oferta['ubicacion'] ?? '—') ?></span></div>
                      <div class="d-flex align-items-center gap-1"><i class="bi bi-laptop"></i><span><?= $modalidadLabel ?></span></div>
                      <div class="d-flex align-items-center gap-1"><i class="bi bi-calendar3"></i><span><?= $fechaPublicacion ?></span></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <?php foreach ($habilidades as $skill): ?>
                        <span class="utp-tech-tag"><?= htmlspecialchars($skill) ?></span>
                      <?php endforeach; ?>
                    </div>
                    <a href="oferta-detalle.php?id=<?= (int)$oferta['id'] ?>" class="btn btn-utp-red btn-utp-rounded">Ver detalles</a>
                  </article>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>

              </section>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <script>
    // Client-side filtering
    (function() {
      var search   = document.getElementById('searchOffers');
      var locSel   = document.getElementById('filterLocation');
      var modSel   = document.getElementById('filterModality');
      var staSel   = document.getElementById('filterStatus');
      var cards    = document.querySelectorAll('#jobCardsContainer .utp-job-card');
      var counter  = document.getElementById('showCount');

      function applyFilters() {
        var q = (search.value || '').toLowerCase();
        var loc = locSel.value;
        var mod = modSel.value;
        var sta = staSel.value;
        var activeSkills = [];
        document.querySelectorAll('.utp-skill-chip.active').forEach(function(c) {
          activeSkills.push(c.getAttribute('data-skill').toLowerCase());
        });

        var visible = 0;
        cards.forEach(function(card) {
          var show = true;
          // Text search
          if (q && card.dataset.titulo.indexOf(q) === -1 && card.dataset.empresa.indexOf(q) === -1 && card.dataset.desc.indexOf(q) === -1) show = false;
          // Location
          if (loc && card.dataset.ubicacion !== loc) show = false;
          // Modality
          if (mod && card.dataset.modalidad !== mod) show = false;
          // Status
          if (sta && card.dataset.estado !== sta) show = false;
          // Skills
          if (activeSkills.length > 0) {
            var cardSkills = card.dataset.habilidades.split(',');
            var hasAll = activeSkills.every(function(s) { return cardSkills.indexOf(s) !== -1; });
            if (!hasAll) show = false;
          }
          card.style.display = show ? '' : 'none';
          if (show) visible++;
        });
        counter.textContent = visible;
      }

      search.addEventListener('input', applyFilters);
      locSel.addEventListener('change', applyFilters);
      modSel.addEventListener('change', applyFilters);
      staSel.addEventListener('change', applyFilters);

      document.querySelectorAll('.utp-skill-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
          this.classList.toggle('active');
          applyFilters();
        });
      });
    })();
  </script>
</body>
</html>
