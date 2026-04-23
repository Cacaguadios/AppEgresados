<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['usuario_rol'] !== 'egresado') {
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

$ofertaId = (int)($_GET['id'] ?? 0);
if (!$ofertaId) {
    header('Location: mis-ofertas.php');
    exit;
}

$ofertaModel = new Oferta();
$oferta = $ofertaModel->getById($ofertaId);

if (!$oferta) {
    header('Location: mis-ofertas.php');
    exit;
}

// Validar que es el propietario
if ($oferta['id_usuario_creador'] != $_SESSION['usuario_id']) {
    header('Location: mis-ofertas.php');
    exit;
}

$msgExito = '';
$msgError = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_oferta'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $titulo      = trim($_POST['titulo'] ?? '');
        $empresa     = trim($_POST['empresa'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contacto    = trim($_POST['contacto'] ?? '');

        if (empty($titulo) || empty($empresa) || empty($descripcion) || empty($contacto)) {
            $msgError = 'Los campos Título, Empresa, Descripción y Email de contacto son obligatorios.';
        } else {
            // Parse requisitos
            $requisitos = [];
            if (!empty($_POST['requisitos']) && is_array($_POST['requisitos'])) {
                foreach ($_POST['requisitos'] as $r) {
                    $r = trim($r);
                    if ($r !== '') $requisitos[] = $r;
                }
            }

            // Parse beneficios
            $beneficios = [];
            if (!empty($_POST['beneficios']) && is_array($_POST['beneficios'])) {
                foreach ($_POST['beneficios'] as $b) {
                    $b = trim($b);
                    if ($b !== '') $beneficios[] = $b;
                }
            }

            // Parse habilidades from JSON
            $habilidades = json_decode($_POST['habilidades_json'] ?? '[]', true) ?: [];

            // Salary parsing
            $salarioMin = !empty($_POST['salario_min']) ? (float)$_POST['salario_min'] : null;
            $salarioMax = !empty($_POST['salario_max']) ? (float)$_POST['salario_max'] : null;

            $data = [
                'titulo'             => $titulo,
                'empresa'            => $empresa,
                'ubicacion'          => trim($_POST['ubicacion'] ?? ''),
                'modalidad'          => $_POST['modalidad'] ?? 'hibrido',
                'jornada'            => $_POST['jornada'] ?? 'completo',
                'descripcion'        => $descripcion,
                'requisitos'         => json_encode($requisitos),
                'beneficios'         => json_encode($beneficios),
                'habilidades'        => json_encode($habilidades),
                'salario_min'        => $salarioMin,
                'salario_max'        => $salarioMax,
                'vacantes'           => max(1, (int)($_POST['vacantes'] ?? 1)),
                'contacto'           => $contacto,
                'nombre_contacto'    => trim($_POST['nombre_contacto'] ?? ''),
                'puesto_contacto'    => trim($_POST['puesto_contacto'] ?? ''),
                'telefono_contacto'  => trim($_POST['telefono_contacto'] ?? ''),
                'fecha_expiracion'   => !empty($_POST['fecha_expiracion']) ? $_POST['fecha_expiracion'] : $oferta['fecha_expiracion'],
            ];

            $ofertaModel->edit($ofertaId, $data);
            $oferta = $ofertaModel->getById($ofertaId);
            $msgExito = 'Oferta actualizada correctamente.';
        }
    }
}

// Parse existing arrays
$requisitos = json_decode($oferta['requisitos'] ?? '[]', true) ?: [];
$beneficios = json_decode($oferta['beneficios'] ?? '[]', true) ?: [];
$habilidades = json_decode($oferta['habilidades'] ?? '[]', true) ?: [];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Oferta - Egresado UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'egresado', roleLabel: 'Egresado',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'editar-oferta',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="container-fluid px-0">
    <div class="row g-0">
      <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

      <main class="col utp-content">
        <div class="container-fluid px-3 px-md-4 py-4 py-md-5">

          <!-- Header -->
          <section class="mb-4">
            <a href="mis-ofertas.php" class="btn btn-link text-dark text-decoration-none p-0 mb-3 d-inline-flex align-items-center gap-2">
              <i class="bi bi-chevron-left"></i> Volver
            </a>
            <h1 class="utp-h1 mb-2">Editar Oferta</h1>
            <p class="utp-subtitle mb-0">Modifica los detalles de tu oferta</p>
          </section>

          <?php if ($msgExito): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          <?php if ($msgError): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msgError) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST" id="formOferta">
          <?= Security::csrfField() ?>
          <input type="hidden" name="editar_oferta" value="1">
          <input type="hidden" name="habilidades_json" id="habilidadesJson" value="<?= htmlspecialchars(json_encode($habilidades)) ?>">

          <div class="row g-4">
            <!-- Left Column: Form -->
            <div class="col-12 col-xl-8">

              <!-- Card: Información básica -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Información básica</h2>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Título de la oferta *</label>
                    <input type="text" name="titulo" class="form-control utp-input" value="<?= htmlspecialchars($oferta['titulo']) ?>" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Empresa *</label>
                    <input type="text" name="empresa" class="form-control utp-input" value="<?= htmlspecialchars($oferta['empresa']) ?>" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control utp-input" value="<?= htmlspecialchars($oferta['ubicacion'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Modalidad</label>
                    <select name="modalidad" class="form-select utp-select">
                      <option value="hibrido" <?= ($oferta['modalidad'] ?? 'hibrido') === 'hibrido' ? 'selected' : '' ?>>Híbrido</option>
                      <option value="presencial" <?= ($oferta['modalidad'] ?? '') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                      <option value="remoto" <?= ($oferta['modalidad'] ?? '') === 'remoto' ? 'selected' : '' ?>>Remoto</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Jornada</label>
                    <select name="jornada" class="form-select utp-select">
                      <option value="completo" <?= ($oferta['jornada'] ?? 'completo') === 'completo' ? 'selected' : '' ?>>Tiempo completo</option>
                      <option value="parcial" <?= ($oferta['jornada'] ?? '') === 'parcial' ? 'selected' : '' ?>>Medio tiempo</option>
                      <option value="freelance" <?= ($oferta['jornada'] ?? '') === 'freelance' ? 'selected' : '' ?>>Freelance</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripción *</label>
                    <textarea name="descripcion" class="form-control utp-input utp-textarea" rows="5" required><?= htmlspecialchars($oferta['descripcion'] ?? '') ?></textarea>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Salario mínimo (MXN)</label>
                    <input type="number" name="salario_min" class="form-control utp-input" value="<?= $oferta['salario_min'] ?? '' ?>" min="0" step="1000">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Salario máximo (MXN)</label>
                    <input type="number" name="salario_max" class="form-control utp-input" value="<?= $oferta['salario_max'] ?? '' ?>" min="0" step="1000">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Número de vacantes</label>
                    <input type="number" name="vacantes" class="form-control utp-input" value="<?= $oferta['vacantes'] ?? 1 ?>" min="1">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Fecha de expiración</label>
                    <input type="date" name="fecha_expiracion" class="form-control utp-input" value="<?= date('Y-m-d', strtotime($oferta['fecha_expiracion'] ?? date('Y-m-d', strtotime('+30 days')))) ?>">
                  </div>
                </div>
              </article>

              <!-- Card: Requisitos -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Requisitos</h2>
                <div id="requirementsList">
                  <?php foreach ($requisitos as $r): ?>
                    <div class="d-flex gap-2 mb-2">
                      <input type="text" name="requisitos[]" class="form-control utp-input" value="<?= htmlspecialchars($r) ?>">
                      <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-utp-outline-gray w-100 mt-2" onclick="addDynamicItem('requirementsList', 'requisitos[]', 'Requisito')">
                  <i class="bi bi-plus-lg me-2"></i> Agregar requisito
                </button>
              </article>

              <!-- Card: Beneficios -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Beneficios</h2>
                <div id="benefitsList">
                  <?php foreach ($beneficios as $b): ?>
                    <div class="d-flex gap-2 mb-2">
                      <input type="text" name="beneficios[]" class="form-control utp-input" value="<?= htmlspecialchars($b) ?>">
                      <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-utp-outline-gray w-100 mt-2" onclick="addDynamicItem('benefitsList', 'beneficios[]', 'Beneficio')">
                  <i class="bi bi-plus-lg me-2"></i> Agregar beneficio
                </button>
              </article>

              <!-- Card: Habilidades requeridas -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Habilidades requeridas</h2>
                <div class="d-flex gap-2">
                  <input type="text" class="form-control utp-input flex-grow-1" id="skillInput" placeholder="Agregar habilidad (ej: React, Node.js)">
                  <button type="button" class="btn btn-utp-green d-flex align-items-center gap-2" onclick="addSkill()">
                    <i class="bi bi-plus-lg"></i> Agregar
                  </button>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3" id="skillsContainer"></div>
              </article>

              <!-- Card: Contacto -->
              <article class="utp-form-card">
                <h2 class="utp-form-card-title">Información de contacto</h2>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Email de contacto</label>
                    <input type="email" name="contacto" class="form-control utp-input" value="<?= htmlspecialchars($oferta['contacto'] ?? '') ?>" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Nombre del contacto</label>
                    <input type="text" name="nombre_contacto" class="form-control utp-input" value="<?= htmlspecialchars($oferta['nombre_contacto'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Puesto del contacto</label>
                    <input type="text" name="puesto_contacto" class="form-control utp-input" value="<?= htmlspecialchars($oferta['puesto_contacto'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Teléfono del contacto</label>
                    <input type="tel" name="telefono_contacto" class="form-control utp-input" value="<?= htmlspecialchars($oferta['telefono_contacto'] ?? '') ?>">
                  </div>
                </div>
              </article>
            </div>

            <!-- Right Column: Actions + Tips -->
            <div class="col-12 col-xl-4">
              <article class="utp-form-card mb-4">
                <h3 class="utp-form-card-subtitle">Acciones</h3>
                <div class="d-grid gap-3">
                  <button type="submit" class="btn btn-utp-green d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-floppy"></i> Guardar cambios
                  </button>
                  <a href="mis-ofertas.php" class="btn btn-link text-dark">Cancelar</a>
                </div>
              </article>

              <article class="utp-form-card">
                <h3 class="utp-form-card-subtitle">Información</h3>
                <div class="utp-info-box">
                  <p class="small mb-0"><strong>Estado:</strong> <?= ucfirst(str_replace('_', ' ', $oferta['estado'])) ?></p>
                  <p class="small mb-0"><strong>Creada:</strong> <?= date('d/m/Y', strtotime($oferta['fecha_creacion'])) ?></p>
                  <?php if ($oferta['activo'] == 0): ?>
                    <p class="small mb-0 text-danger"><strong>Dada de baja:</strong> Sí</p>
                  <?php endif; ?>
                </div>
              </article>
            </div>
          </div>
          </form>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  
  <script>
    // Skills management
    const skillsData = <?= json_encode($habilidades) ?>;
    
    function initSkills() {
      const container = document.getElementById('skillsContainer');
      container.innerHTML = '';
      skillsData.forEach(skill => {
        addSkillChip(skill);
      });
    }

    function addSkillChip(skill) {
      const container = document.getElementById('skillsContainer');
      const chip = document.createElement('span');
      chip.className = 'utp-skill-chip-editable';
      chip.innerHTML = `${skill}<button type="button" class="btn btn-sm p-0 border-0 bg-transparent" onclick="removeSkill(this)"><i class="bi bi-x-lg"></i></button>`;
      container.appendChild(chip);
      updateHabilidadesJson();
    }

    function addSkill() {
      const input = document.getElementById('skillInput');
      const skill = input.value.trim();
      if (!skill) return;
      if (skillsData.includes(skill)) {
        alert('Esta habilidad ya está agregada');
        return;
      }
      skillsData.push(skill);
      addSkillChip(skill);
      input.value = '';
    }

    function removeSkill(btn) {
      const chip =  btn.closest('.utp-skill-chip');
      const text = chip.textContent.trim().slice(0, -1).trim();
      const idx = skillsData.indexOf(text);
      if (idx >= 0) skillsData.splice(idx, 1);
      chip.remove();
      updateHabilidadesJson();
    }

    function updateHabilidadesJson() {
      document.getElementById('habilidadesJson').value = JSON.stringify(skillsData);
    }

    function addDynamicItem(containerId, name, label) {
      const container = document.getElementById(containerId);
      const div = document.createElement('div');
      div.className = 'd-flex gap-2 mb-2';
      div.innerHTML = `
        <input type="text" name="${name}" class="form-control utp-input" placeholder="${label}">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
          <i class="bi bi-trash"></i>
        </button>
      `;
      container.appendChild(div);
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
      initSkills();
      document.getElementById('skillInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          addSkill();
        }
      });
    });
  </script>
</body>
</html>
