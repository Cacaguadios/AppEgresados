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

// Handle form submission
$msgExito = '';
$msgError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $data = [
            'correo_personal' => trim($_POST['correo_personal'] ?? ''),
            'telefono'        => trim($_POST['telefono'] ?? ''),
            'genero'          => $_POST['genero'] ?? null,
            'año_nacimiento'  => !empty($_POST['año_nacimiento']) ? (int)$_POST['año_nacimiento'] : null,
            'especialidad'    => trim($_POST['especialidad'] ?? ''),
            'generacion'      => !empty($_POST['generacion']) ? (int)$_POST['generacion'] : null,
        ];
        $egresadoModel->updatePerfil($_SESSION['usuario_id'], $data);
        $perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
        $msgExito = 'Perfil actualizado correctamente.';
    }
}

// Map gender enum to select
$generoMap = ['M' => 'M', 'F' => 'F', 'Otro' => 'Otro'];
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
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body>
  <script>
    window.UTP_DATA = {
      role: 'egresado', roleLabel: 'Egresado',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'perfil',
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
            <div class="px-0 py-3 py-md-4" style="min-height: calc(100vh - 65px);">

              <!-- Page Header -->
              <div class="mb-4">
                <h1 style="font-size:36px; font-weight:700; line-height:40px; color:#121212;" class="mb-3">Mi Perfil</h1>
                <div class="d-flex flex-wrap align-items-center gap-3">
                  <span class="utp-verified-badge">
                    <i class="bi bi-check-lg"></i> Verificado
                  </span>
                  <span class="utp-profile-hint">Tus datos de matrícula y CURP no se pueden modificar.</span>
                </div>
              </div>

              <!-- Tab Navigation -->
              <div class="utp-tabs-wrapper mb-4">
                <div class="utp-tabs" role="tablist">
                  <button class="utp-tab active" data-tab="personal" role="tab" aria-selected="true">Datos personales</button>
                  <button class="utp-tab" data-tab="cv" role="tab" aria-selected="false">CV / Habilidades</button>
                  <a class="utp-tab" href="seguimiento.php" style="text-decoration:none;">Seguimiento</a>
                  <a class="utp-tab" href="seguridad.php" style="text-decoration:none;">Seguridad</a>
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
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Nombre completo</label>
                      <input type="text" class="utp-input-disabled" value="<?= htmlspecialchars($fullName) ?>" disabled>
                      <span class="utp-field-hint">Campo protegido</span>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Matrícula UTP</label>
                      <input type="text" class="utp-input-disabled" value="<?= htmlspecialchars($perfil['matricula'] ?? '—') ?>" disabled>
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
                      <label class="utp-label">Correo personal *</label>
                      <input type="email" name="correo_personal" class="form-control utp-input" value="<?= htmlspecialchars($perfil['correo_personal'] ?? '') ?>" placeholder="tu.correo@gmail.com">
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Teléfono</label>
                      <input type="tel" name="telefono" class="form-control utp-input" value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" placeholder="222-123-4567">
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Género</label>
                      <select name="genero" class="form-select utp-select">
                        <option value="">Selecciona</option>
                        <option value="M" <?= $curGenero === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $curGenero === 'F' ? 'selected' : '' ?>>Femenino</option>
                        <option value="Otro" <?= $curGenero === 'Otro' ? 'selected' : '' ?>>Otro</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Año de nacimiento</label>
                      <input type="text" name="año_nacimiento" class="form-control utp-input" value="<?= htmlspecialchars($perfil['año_nacimiento'] ?? '') ?>" placeholder="1999">
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
                      <input type="text" name="generacion" class="form-control utp-input" value="<?= htmlspecialchars($perfil['generacion'] ?? '') ?>" placeholder="2023">
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

              <!-- Tab Content: CV (placeholder) -->
              <div class="utp-profile-card d-none" id="tab-cv">
                <h2 class="utp-section-title mb-4">CV / Habilidades</h2>
                <p class="text-muted">Próximamente podrás subir tu CV y gestionar tus habilidades.</p>
              </div>

              <!-- Tab Content: Seguimiento redirect -->

              <!-- Tab Content: Seguridad redirect -->

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
  </script>
</body>
</html>
