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

// ─── Load profile from DB ───
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$egresadoModel = new Egresado();
$perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
$estadoRecordatorio = $perfil ? $egresadoModel->obtenerEstadoRecordatorio($_SESSION['usuario_id']) : null;

$edadCalculada = null;
if (!empty($perfil['año_nacimiento']) && ctype_digit((string)$perfil['año_nacimiento'])) {
  $anioNacimientoPerfil = (int)$perfil['año_nacimiento'];
  $anioActualPerfil = (int)date('Y');
  if ($anioNacimientoPerfil >= 1900 && $anioNacimientoPerfil <= $anioActualPerfil) {
    $edadCalculada = max(0, $anioActualPerfil - $anioNacimientoPerfil);
  }
}

// Calcular completitud de perfil
$completitud = $egresadoModel->calcularCompletudinformacion($perfil);
$porcentajeCompletitud = $completitud['porcentaje'] ?? 0;
$camposFaltantesDetalle = $completitud['campos_faltantes_detalle'] ?? [];

// Handle form submission
$msgExito = '';
$msgError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido. Recarga la página.';
    } else {
    $generoInput = trim((string)($_POST['genero'] ?? ''));
    $genero = $generoInput !== '' ? strtoupper($generoInput) : null;
    $generosValidos = ['M', 'F'];

    $anioActual = (int)date('Y');
    $anioNacimientoRaw = trim((string)($_POST['anio_nacimiento'] ?? ($_POST['año_nacimiento'] ?? '')));
    $anioNacimiento = $anioNacimientoRaw !== '' ? (int)$anioNacimientoRaw : null;

    $generacionRaw = trim((string)($_POST['generacion'] ?? ''));
    $generacion = $generacionRaw !== '' ? (int)$generacionRaw : null;

    if ($genero !== null && !in_array($genero, $generosValidos, true)) {
      $msgError = 'El género solo permite dos opciones: Masculino o Femenino.';
    }

    if ($msgError === '' && $anioNacimientoRaw !== '') {
      if (!ctype_digit($anioNacimientoRaw) || $anioNacimiento < 1940 || $anioNacimiento > ($anioActual - 15)) {
        $msgError = 'El año de nacimiento no es válido.';
      }
    }

    if ($msgError === '' && $generacionRaw !== '') {
      if (!ctype_digit($generacionRaw) || $generacion < 1990 || $generacion > ($anioActual + 1)) {
        $msgError = 'La generación no es válida.';
      }
    }

    if ($msgError !== '') {
      $perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
    } else {
        $data = [
      'genero'          => $genero,
      'año_nacimiento'  => $anioNacimiento,
            'especialidad'    => trim($_POST['especialidad'] ?? ''),
      'generacion'      => $generacion,
        ];
        $egresadoModel->updatePerfil($_SESSION['usuario_id'], $data);
        $perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
        
        // Recalcular completitud del perfil
        $egresadoModel->actualizarCompletudinformacion($_SESSION['usuario_id']);
        $egresadoModel->setProximoRecordatorio($_SESSION['usuario_id']);

        $completitud = $egresadoModel->calcularCompletudinformacion($perfil);
        $porcentajeCompletitud = $completitud['porcentaje'] ?? 0;
        $camposFaltantesDetalle = $completitud['campos_faltantes_detalle'] ?? [];
        
        $msgExito = 'Perfil actualizado correctamente.';
        }
    }
}

// Handle habilidades blandas submission
$msgExitoHabilidades = '';
$msgErrorHabilidades = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_habilidades'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgErrorHabilidades = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $habilidades = [];
        if (!empty($_POST['habilidades_blandas'])) {
            $habilidades = array_filter(array_map('trim', (array)$_POST['habilidades_blandas']));
        }
        $egresadoModel->updateHabilidadesBlandas($_SESSION['usuario_id'], $habilidades);
        $egresadoModel->setProximoRecordatorio($_SESSION['usuario_id']);
        $perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);

        $completitud = $egresadoModel->calcularCompletudinformacion($perfil);
        $porcentajeCompletitud = $completitud['porcentaje'] ?? 0;
        $camposFaltantesDetalle = $completitud['campos_faltantes_detalle'] ?? [];

        $msgExitoHabilidades = 'Habilidades blandas actualizadas correctamente.';
    }
}

// Load habilidades blandas
$habilidadesBlandas = $egresadoModel->getHabilidadesBlandas($_SESSION['usuario_id']);

// Map gender enum to select
$curGenero = $perfil['genero'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil - Egresados UTP</title>

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
      currentPage: 'perfil',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>,
      estadoRecordatorio: <?= $estadoRecordatorio ? json_encode($estadoRecordatorio) : 'null' ?>
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

              <!-- Page Header -->
              <div class="mb-4">
                <h1 class="utp-h1 mb-3">Mi Perfil</h1>
                <div class="d-flex flex-wrap align-items-center gap-3">
                  <span class="utp-verified-badge">
                    <i class="bi bi-check-lg"></i> Verificado
                  </span>
                  <span class="utp-profile-hint">Tus datos de contacto de registro y CURP están protegidos.</span>
                </div>
              </div>

              <!-- Completitud Alertas -->
              <?php if ($porcentajeCompletitud < 100): ?>
                <div class="alert <?= $porcentajeCompletitud >= 50 ? 'alert-warning' : 'alert-danger' ?> alert-dismissible fade show mb-4" role="alert">
                  <div class="d-flex align-items-start justify-content-between">
                    <div>
                      <i class="bi <?= $porcentajeCompletitud >= 50 ? 'bi-exclamation-triangle' : 'bi-info-circle' ?> me-2"></i>
                      <strong>Completitud de perfil:</strong> Solo tienes el <strong><?= $porcentajeCompletitud ?>%</strong> de tu información completada.
                      <p class="mb-0 mt-2 small">Completa tu perfil para mejorar tu visibilidad ante los empleadores y recibir mejores oportunidades.</p>
                      <?php if (!empty($camposFaltantesDetalle)): ?>
                        <div class="mt-2">
                          <span class="small fw-semibold">Te falta completar:</span>
                          <ul class="small mb-0 mt-1 ps-3">
                            <?php foreach (array_slice($camposFaltantesDetalle, 0, 6) as $campo): ?>
                              <li><?= htmlspecialchars($campo) ?></li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                      <?php endif; ?>
                      <div class="progress mt-2 utp-progress-thin">
                        <div class="progress-bar utp-progress-dynamic" role="progressbar" style="--utp-progress: <?= (int)$porcentajeCompletitud ?>%;" aria-valuenow="<?= $porcentajeCompletitud ?>" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php else: ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                  <i class="bi bi-check-circle me-2"></i>
                  <strong>Perfil completo:</strong> Tienes el 100% de tu información completada. Excelente trabajo!
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <!-- Tab Navigation -->
              <div class="utp-tabs-wrapper mb-4">
                <div class="utp-tabs" role="tablist">
                  <button class="utp-tab active" data-tab="personal" role="tab" aria-selected="true">Datos personales</button>
                  <button class="utp-tab" data-tab="habilidades" role="tab" aria-selected="false">Habilidades</button>
                  <a class="utp-tab utp-link-clean" href="seguimiento.php">Seguimiento</a>
                  <a class="utp-tab utp-link-clean" href="seguridad.php">Seguridad</a>
                </div>
              </div>

              <!-- Tab Content: Datos Personales -->
              <form method="POST" id="tab-personal">
                <div class="utp-profile-card">
                <?= Security::csrfField() ?>
                <input type="hidden" name="guardar_perfil" value="1">

                <?php if ($msgExito): ?>
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>
                <?php endif; ?>
                <?php if ($msgError): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msgError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>
                <?php endif; ?>

                <h2 class="utp-section-title mb-4">Información Personal</h2>

                <!-- Protected Fields -->
                <div class="row g-3 mb-4">
                  <div class="col-12">
                    <div class="utp-form-group">
                      <label class="utp-label">Nombre completo</label>
                      <input type="text" class="utp-input-disabled" value="<?= htmlspecialchars($fullName) ?>" disabled>
                      <span class="utp-field-hint">Campo protegido</span>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="utp-form-group">
                      <label class="utp-label">CURP</label>
                      <input type="text" class="utp-input-disabled utp-curp" value="<?= htmlspecialchars($perfil['curp'] ?? '—') ?>" disabled>
                      <span class="utp-field-hint">Campo protegido - usado para validación</span>
                    </div>
                  </div>
                </div>

                <hr class="utp-divider mb-4">

                <!-- Contact Information -->
                <h3 class="utp-subsection-title mb-3">Información de contacto</h3>

                <div class="row g-3 mb-4">
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Correo personal (registro)</label>
                      <input type="email" class="utp-input-disabled" value="<?= htmlspecialchars($perfil['correo_personal'] ?? ($perfil['email'] ?? '')) ?>" disabled>
                      <span class="utp-field-hint">Dato capturado durante el registro</span>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Teléfono (registro)</label>
                      <input type="text" class="utp-input-disabled" value="<?= htmlspecialchars($perfil['telefono'] ?? ($_SESSION['usuario_telefono'] ?? '')) ?>" disabled>
                      <span class="utp-field-hint">Dato capturado durante el registro</span>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Género</label>
                      <select name="genero" class="form-select utp-select">
                        <option value="">Selecciona</option>
                        <option value="M" <?= $curGenero === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $curGenero === 'F' ? 'selected' : '' ?>>Femenino</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Año de nacimiento</label>
                      <input type="number" name="anio_nacimiento" class="form-control utp-input" value="<?= htmlspecialchars($perfil['año_nacimiento'] ?? '') ?>" min="1940" max="<?= (int)date('Y') - 15 ?>" step="1" placeholder="1999">
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Edad (calculada)</label>
                      <input type="text" class="utp-input-disabled" value="<?= $edadCalculada !== null ? (int)$edadCalculada . ' años' : '—' ?>" disabled>
                      <span class="utp-field-hint">Dato visual calculado desde tu año de nacimiento</span>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Especialidad</label>
                      <select name="especialidad" class="form-select utp-select">
                        <option value="">Selecciona</option>
                        <?php
                        $espOptions = ['Desarrollo de Software','Redes y Telecomunicaciones','Multimedia y Diseño','Sistemas Computacionales'];
                        foreach ($espOptions as $esp): ?>
                          <option value="<?= $esp ?>" <?= ($perfil['especialidad'] ?? '') === $esp ? 'selected' : '' ?>><?= $esp ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Generación</label>
                      <input type="number" name="generacion" class="form-control utp-input" value="<?= htmlspecialchars($perfil['generacion'] ?? '') ?>" min="1990" max="<?= (int)date('Y') + 1 ?>" step="1" placeholder="2023">
                    </div>
                  </div>
                </div>

                <!-- Save Button -->
                <button type="submit" class="btn utp-btn-green">
                  <i class="bi bi-floppy me-2"></i>
                  Guardar cambios
                </button>
                </div>
              </form>

              <!-- Tab Content: Habilidades Blandas -->
              <form method="POST" id="tab-habilidades" class="d-none">
                <div class="utp-profile-card">
                  <?= Security::csrfField() ?>
                  <input type="hidden" name="guardar_habilidades" value="1">

                  <?php if ($msgExitoHabilidades): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExitoHabilidades) ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  <?php endif; ?>
                  <?php if ($msgErrorHabilidades): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msgErrorHabilidades) ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                  <?php endif; ?>

                  <h2 class="utp-section-title mb-4">Habilidades Blandas (Soft Skills)</h2>
                  <p class="text-muted mb-4">Agrega habilidades blandas que demuestren tus capacidades interpersonales y de liderazgo.</p>

                  <div class="utp-form-group mb-4">
                    <label class="utp-label mb-2">Habilidades Blandas</label>
                    <div id="soft-skills-container" class="d-flex flex-wrap gap-2 mb-3">
                      <?php if (!empty($habilidadesBlandas)): ?>
                        <?php foreach ($habilidadesBlandas as $skill): ?>
                          <span class="utp-skill-chip-editable" data-skill="<?= htmlspecialchars($skill) ?>">
                            <?= htmlspecialchars($skill) ?>
                            <i class="bi bi-x ms-1"></i>
                          </span>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                    <input type="hidden" id="habilidades-blandas-input" name="habilidades_blandas[]" value="">
                    <div class="input-group">
                      <input type="text" id="soft-skill-input" class="form-control utp-input" placeholder="Escribe una habilidad y presiona Enter (ej: Liderazgo, Comunicación, Trabajo en equipo)">
                      <button type="button" class="btn utp-btn-blue" id="add-soft-skill-btn">
                        <i class="bi bi-plus-lg"></i> Agregar
                      </button>
                    </div>
                    <small class="text-muted d-block mt-2">Ejemplos: Liderazgo, Comunicación, Trabajo en equipo, Resolución de problemas, Pensamiento crítico</small>
                  </div>

                  <!-- Save Button -->
                  <button type="submit" class="btn utp-btn-green">
                    <i class="bi bi-floppy me-2"></i>
                    Guardar habilidades
                  </button>
                </div>
              </form>

              <!-- Tab Content: Seguimiento redirect -->

              <!-- Tab Content: Seguridad redirect -->

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  
  <!-- Modal de Recordatorio de Actualización -->
  <?php require_once __DIR__ . '/../compartido/modal-recordatorio-actualizacion.php'; ?>
  
  <script>
    // Tab switching
    document.querySelectorAll('.utp-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        // Deactivate all tabs
        document.querySelectorAll('.utp-tab').forEach(function(t) {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        // Hide all panes
        document.querySelectorAll('[id^="tab-"]').forEach(function(p) {
          p.classList.add('d-none');
        });
        // Activate clicked tab
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');
        var target = document.getElementById('tab-' + this.getAttribute('data-tab'));
        if (target) target.classList.remove('d-none');
      });
    });

    // Soft Skills Management
    const softSkillInput = document.getElementById('soft-skill-input');
    const addSkillBtn = document.getElementById('add-soft-skill-btn');
    const skillsContainer = document.getElementById('soft-skills-container');
    const hiddenInput = document.getElementById('habilidades-blandas-input');

    function normalizeSkill(rawSkill) {
      const normalized = String(rawSkill || '').replace(/\s+/g, ' ').trim();
      return normalized.slice(0, 80);
    }

    function updateHiddenInput() {
      const skills = Array.from(document.querySelectorAll('.utp-skill-chip-editable')).map(el => el.dataset.skill);
      const inputElements = Array.from(document.querySelectorAll('input[name="habilidades_blandas[]"]'));
      inputElements.forEach(el => el.remove());
      
      skills.forEach(skill => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'habilidades_blandas[]';
        input.value = skill;
        document.getElementById('tab-habilidades').appendChild(input);
      });
    }

    function addSkill(skill) {
      const normalizedSkill = normalizeSkill(skill);
      if (!normalizedSkill) return;

      if (/[<>]/.test(normalizedSkill)) {
        alert('No se permiten etiquetas HTML en las habilidades.');
        return;
      }
      
      const existing = Array.from(document.querySelectorAll('.utp-skill-chip-editable'))
        .some(el => el.dataset.skill.toLowerCase() === normalizedSkill.toLowerCase());
      
      if (existing) {
        alert('Esta habilidad ya está agregada');
        return;
      }

      const chip = document.createElement('span');
      chip.className = 'utp-skill-chip-editable';
      chip.dataset.skill = normalizedSkill;
      chip.appendChild(document.createTextNode(normalizedSkill));

      const icon = document.createElement('i');
      icon.className = 'bi bi-x ms-1';
      chip.appendChild(icon);
      
      icon.addEventListener('click', function() {
        chip.remove();
        updateHiddenInput();
      });

      skillsContainer.appendChild(chip);
      updateHiddenInput();
      softSkillInput.value = '';
    }

    addSkillBtn.addEventListener('click', function() {
      addSkill(softSkillInput.value);
    });

    softSkillInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addSkill(this.value);
      }
    });

    // Delete existing skills on load
    document.querySelectorAll('.utp-skill-chip-editable i').forEach(icon => {
      icon.addEventListener('click', function() {
        this.parentElement.remove();
        updateHiddenInput();
      });
    });

    updateHiddenInput();

    document.addEventListener('DOMContentLoaded', function() {
      if (window.UTP_DATA && window.UTP_DATA.estadoRecordatorio) {
        inicializarRecordatorio(window.UTP_DATA.estadoRecordatorio);
      }
    });
  </script>
</body>
</html>
