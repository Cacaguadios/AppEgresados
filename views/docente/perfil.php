<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !in_array($_SESSION['usuario_rol'] ?? '', ['docente', 'ti'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Usuario.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$usuarioModel = new Usuario();
$usuario = $usuarioModel->getById($_SESSION['usuario_id']);

// Para docentes, mostrar alerta simple de completitud
// usando campos básicos: nombre, email, etc.
$docenteCompletudinformacion = 0;
if (!empty($usuario['nombre']) && !empty($usuario['email'])) {
    $docenteCompletudinformacion = 100; // Docente con datos básicos = completo
} elseif (!empty($usuario['nombre']) || !empty($usuario['email'])) {
    $docenteCompletudinformacion = 50;
} else {
    $docenteCompletudinformacion = 0;
}

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// Handle form submission
$msgExito = '';
$msgError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $newEmail = trim($_POST['email'] ?? '');
        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $msgError = 'Ingresa un correo electrónico válido.';
        } else {
            // Check if email is taken by another user
            $existing = $usuarioModel->getByEmail($newEmail);
            if ($existing && $existing['id'] != $_SESSION['usuario_id']) {
                $msgError = 'Este correo ya está registrado por otro usuario.';
            } else {
                $usuarioModel->updateProfile($_SESSION['usuario_id'], [
                    'email' => $newEmail,
                ]);
                // Refresh data
                $usuario = $usuarioModel->getById($_SESSION['usuario_id']);
                $_SESSION['usuario_email'] = $newEmail;
                $msgExito = 'Perfil actualizado correctamente.';
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
  <title>Mi Perfil - Docente UTP</title>
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
      currentPage: 'perfil',
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

          <!-- Page Header -->
          <div class="mb-4">
            <h1 class="utp-h1 mb-2">Mi Perfil</h1>
            <div class="d-flex flex-wrap align-items-center gap-3">
              <span class="utp-verified-badge">
                <i class="bi bi-check-lg"></i> Verificado
              </span>
              <span class="utp-profile-hint">Tus datos de nombre y usuario no se pueden modificar.</span>
            </div>
          </div>

          <!-- Completitud Alertas -->
          <?php if ($docenteCompletudinformacion < 100): ?>
            <div class="alert <?= $docenteCompletudinformacion >= 50 ? 'alert-warning' : 'alert-danger' ?> alert-dismissible fade show mb-4" role="alert">
              <div class="d-flex align-items-start justify-content-between">
                <div>
                  <i class="bi <?= $docenteCompletudinformacion >= 50 ? 'bi-exclamation-triangle' : 'bi-info-circle' ?> me-2"></i>
                  <strong>Completitud de perfil:</strong> Solo tienes el <strong><?= $docenteCompletudinformacion ?>%</strong> de tu información completada.
                  <p class="mb-0 mt-2 small">Completa tu perfil para tener una presencia profesional completa en el sistema.</p>
                  <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= $docenteCompletudinformacion ?>%" aria-valuenow="<?= $docenteCompletudinformacion ?>" aria-valuemin="0" aria-valuemax="100"></div>
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
              <button class="utp-tab" data-tab="seguridad" role="tab" aria-selected="false">Seguridad</button>
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
                    <label class="utp-label">Usuario</label>
                    <input type="text" class="utp-input-disabled" value="<?= htmlspecialchars($usuario['usuario'] ?? '—') ?>" disabled>
                    <span class="utp-field-hint">Campo protegido</span>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="utp-form-group">
                    <label class="utp-label">Rol</label>
                    <input type="text" class="utp-input-disabled" value="Docente" disabled>
                    <span class="utp-field-hint">Campo protegido</span>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="utp-form-group">
                    <label class="utp-label">Cuenta creada</label>
                    <input type="text" class="utp-input-disabled" value="<?= htmlspecialchars($usuario['fecha_creacion'] ?? '—') ?>" disabled>
                    <span class="utp-field-hint">Informativo</span>
                  </div>
                </div>
              </div>

              <hr class="utp-divider mb-4">

              <!-- Editable Fields -->
              <h3 class="utp-subsection-title mb-3">Información de contacto</h3>

              <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                  <div class="utp-form-group">
                    <label class="utp-label">Correo electrónico *</label>
                    <input type="email" name="email" class="form-control utp-input" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" placeholder="tu.correo@utp.edu.mx" required>
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

          <!-- Tab Content: Seguridad -->
          <div class="utp-profile-card d-none" id="tab-seguridad">
            <h2 class="utp-section-title mb-4">Seguridad</h2>
            <p class="text-muted mb-3">Cambia tu contraseña y gestiona la seguridad de tu cuenta.</p>
            <a href="seguridad.php" class="btn btn-utp-red btn-utp-rounded">
              <i class="bi bi-lock me-2"></i>Ir a Seguridad
            </a>
          </div>

        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
  
  <!-- Modal de Recordatorio de Actualización -->
  <?php require_once __DIR__ . '/../compartido/modal-recordatorio-actualizacion.php'; ?>
  
  <script>
    // Tab switching
    document.querySelectorAll('.utp-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.utp-tab').forEach(function(t) {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('[id^="tab-"]').forEach(function(p) {
          p.classList.add('d-none');
        });
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');
        var target = document.getElementById('tab-' + this.getAttribute('data-tab'));
        if (target) target.classList.remove('d-none');
      });
    });
  </script>
</body>
</html>
