<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Notificacion.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$msgExito = '';
$msgError = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publicar_oferta'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $titulo      = trim($_POST['titulo'] ?? '');
        $empresa     = trim($_POST['empresa'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contacto    = trim($_POST['contacto'] ?? '');
        $nombreContacto = trim($_POST['nombre_contacto'] ?? '');
        $puestoContacto = trim($_POST['puesto_contacto'] ?? '');
        $telefonoContacto = trim($_POST['telefono_contacto'] ?? '');

        if (empty($titulo) || empty($empresa) || empty($descripcion)) {
          $msgError = 'Los campos Título, Empresa y Descripción son obligatorios.';
        } elseif (empty($contacto) || empty($nombreContacto) || empty($puestoContacto) || empty($telefonoContacto)) {
          $msgError = 'Toda la información de contacto es obligatoria para publicar una vacante.';
        } elseif (!filter_var($contacto, FILTER_VALIDATE_EMAIL)) {
          $msgError = 'El email de contacto no tiene un formato válido.';
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
                'id_usuario_creador' => $_SESSION['usuario_id'],
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
                'nombre_contacto'    => $nombreContacto,
                'puesto_contacto'    => $puestoContacto,
                'telefono_contacto'  => $telefonoContacto,
                'estado'             => 'pendiente_aprobacion',
                'estado_vacante'     => 'verde',
                'fecha_creacion'     => date('Y-m-d H:i:s'),
                'fecha_expiracion'   => !empty($_POST['fecha_expiracion']) ? $_POST['fecha_expiracion'] : date('Y-m-d H:i:s', strtotime('+30 days')),
            ];

            $ofertaModel = new Oferta();
            $newId = $ofertaModel->create($data);

            if ($newId) {
                // Notificar a todos los admins
                $notifModel = new Notificacion();
                $notifModel->onOfertaCreada($titulo, $fullName);

                header('Location: mis-ofertas.php?creada=1');
                exit;
            } else {
                $msgError = 'Error al crear la oferta. Intenta de nuevo.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Nueva Oferta - Docente UTP</title>

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
      currentPage: 'publicar-oferta',
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
            <a href="inicio.php" class="btn btn-link text-dark text-decoration-none p-0 mb-3 d-inline-flex align-items-center gap-2">
              <i class="bi bi-chevron-left"></i> Volver
            </a>
            <h1 class="utp-h1 mb-2">Crear Nueva Oferta</h1>
            <p class="utp-subtitle mb-0">Publica una vacante para egresados UTP</p>
          </section>

          <?php if ($msgError): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msgError) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST" id="formOferta">
          <?= Security::csrfField() ?>
          <input type="hidden" name="publicar_oferta" value="1">
          <input type="hidden" name="habilidades_json" id="habilidadesJson" value="[]">

          <div class="row g-4">
            <!-- Left Column: Form -->
            <div class="col-12 col-xl-8">

              <!-- Card: Información básica -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Información básica</h2>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Título de la oferta *</label>
                    <input type="text" name="titulo" class="form-control utp-input" placeholder="Ej: Desarrollador Full Stack Junior" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Empresa *</label>
                    <input type="text" name="empresa" class="form-control utp-input" placeholder="Nombre de la empresa" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control utp-input" placeholder="Ciudad, Estado">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Modalidad</label>
                    <select name="modalidad" class="form-select utp-select">
                      <option value="hibrido" selected>Híbrido</option>
                      <option value="presencial">Presencial</option>
                      <option value="remoto">Remoto</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Jornada</label>
                    <select name="jornada" class="form-select utp-select">
                      <option value="completo" selected>Tiempo completo</option>
                      <option value="parcial">Medio tiempo</option>
                      <option value="freelance">Freelance</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripción *</label>
                    <textarea name="descripcion" class="form-control utp-input utp-textarea" rows="5" placeholder="Describe la posición, responsabilidades y lo que buscas en un candidato..." required></textarea>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Salario mínimo (MXN)</label>
                    <input type="number" name="salario_min" class="form-control utp-input" placeholder="15000" min="0" step="1000">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Salario máximo (MXN)</label>
                    <input type="number" name="salario_max" class="form-control utp-input" placeholder="25000" min="0" step="1000">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Número de vacantes</label>
                    <input type="number" name="vacantes" class="form-control utp-input" value="1" min="1">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Fecha de expiración</label>
                    <input type="date" name="fecha_expiracion" class="form-control utp-input">
                  </div>
                </div>
              </article>

              <!-- Card: Requisitos -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Requisitos</h2>
                <div id="requirementsList">
                  <div class="d-flex gap-2 mb-2">
                    <input type="text" name="requisitos[]" class="form-control utp-input" placeholder="Requisito 1">
                  </div>
                </div>
                <button type="button" class="btn btn-utp-outline-gray w-100 mt-2" onclick="addDynamicItem('requirementsList', 'requisitos[]', 'Requisito')">
                  <i class="bi bi-plus-lg me-2"></i> Agregar requisito
                </button>
              </article>

              <!-- Card: Beneficios -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Beneficios</h2>
                <div id="benefitsList">
                  <div class="d-flex gap-2 mb-2">
                    <input type="text" name="beneficios[]" class="form-control utp-input" placeholder="Beneficio 1">
                  </div>
                </div>
                <button type="button" class="btn btn-utp-outline-gray w-100 mt-2" onclick="addDynamicItem('benefitsList', 'beneficios[]', 'Beneficio')">
                  <i class="bi bi-plus-lg me-2"></i> Agregar beneficio
                </button>
              </article>

              <!-- Card: Habilidades requeridas -->
              <article class="utp-form-card mb-4">
                <h2 class="utp-form-card-title">Habilidades requeridas</h2>
                <div class="d-flex gap-2">
                  <input type="text" class="form-control utp-input flex-grow-1" id="skillInput" maxlength="60" placeholder="Agregar habilidad (ej: React, Node.js)">
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
                    <label class="form-label">Email de contacto *</label>
                    <input type="email" name="contacto" class="form-control utp-input" placeholder="contacto@empresa.com" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Nombre del contacto *</label>
                    <input type="text" name="nombre_contacto" class="form-control utp-input" placeholder="Nombre completo" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Puesto del contacto *</label>
                    <input type="text" name="puesto_contacto" class="form-control utp-input" placeholder="Ej: Gerente de RH" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Teléfono del contacto *</label>
                    <input type="tel" name="telefono_contacto" class="form-control utp-input" placeholder="Ej: +52 123 456 7890" inputmode="tel" pattern="^[0-9+()\-\s]{7,20}$" maxlength="20" required>
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
                    <i class="bi bi-send"></i> Enviar para aprobación
                  </button>
                  <a href="inicio.php" class="btn btn-link text-dark">Cancelar</a>
                </div>
                <div class="utp-info-box mt-4">
                  <p class="mb-0">Tu oferta será revisada por un administrador antes de publicarse.</p>
                </div>
              </article>

              <article class="utp-form-card">
                <h3 class="utp-form-card-subtitle">Consejos</h3>
                <ul class="utp-tips-list">
                  <li>✓ Sé claro y específico en los requisitos</li>
                  <li>✓ Incluye el rango salarial para más postulaciones</li>
                  <li>✓ Agrega beneficios atractivos</li>
                  <li>✓ Especifica las tecnologías exactas</li>
                </ul>
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
    // Dynamic list items (requisitos, beneficios)
    function addDynamicItem(containerId, nameAttr, label) {
      const container = document.getElementById(containerId);
      const count = container.children.length + 1;
      const div = document.createElement('div');
      div.className = 'd-flex gap-2 mb-2';
      div.innerHTML = '<input type="text" name="' + nameAttr + '" class="form-control utp-input" placeholder="' + label + ' ' + count + '">'
        + '<button type="button" class="btn btn-utp-outline-red btn-sm" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>';
      container.appendChild(div);
    }

    // Skills
    const skills = [];
    function normalizeSkill(rawSkill) {
      const normalized = String(rawSkill || '').replace(/\s+/g, ' ').trim();
      return normalized.slice(0, 60);
    }

    function addSkill() {
      const input = document.getElementById('skillInput');
      const value = normalizeSkill(input.value);
      if (!value || skills.some(s => s.toLowerCase() === value.toLowerCase())) { input.value = ''; return; }
      skills.push(value);
      input.value = '';
      renderSkills();
    }
    document.getElementById('skillInput').addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); addSkill(); }
    });
    function removeSkill(idx) { skills.splice(idx, 1); renderSkills(); }
    function renderSkills() {
      const container = document.getElementById('skillsContainer');
      container.innerHTML = '';
      skills.forEach(function(s, i) {
        const chip = document.createElement('span');
        chip.className = 'utp-skill-chip-sm d-inline-flex align-items-center gap-1';
        const label = document.createElement('span');
        label.textContent = s;
        chip.appendChild(label);

        const icon = document.createElement('i');
        icon.className = 'bi bi-x utp-clickable';
        icon.setAttribute('onclick', 'removeSkill(' + i + ')');
        chip.appendChild(icon);
        container.appendChild(chip);
      });
      document.getElementById('habilidadesJson').value = JSON.stringify(skills);
    }

    // Sync skills on submit
    document.getElementById('formOferta').addEventListener('submit', function() {
      document.getElementById('habilidadesJson').value = JSON.stringify(skills);
    });
  </script>
</body>
</html>
