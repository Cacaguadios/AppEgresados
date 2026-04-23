<?php
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}
if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/helpers/Security.php';

// Procesar POST (cambio de contraseña)
$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../app/controllers/PasswordController.php';
    $ctrl = new PasswordController();
    $resultado = $ctrl->changePassword();
}

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);

$initials = '';
if ($nombre)    $initials .= mb_substr($nombre, 0, 1);
if ($apellidos) $initials .= mb_substr($apellidos, 0, 1);
$initials = mb_strtoupper($initials);

$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cambiar Contraseña - Admin UTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'admin',
      roleLabel: 'Administrador',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'seguridad',
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
          <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-6">
              <article class="utp-card">

                <div class="text-center mb-5">
                  <div class="d-flex justify-content-center mb-3">
                    <div class="utp-miniicon yellow utp-large-icon">
                      <i class="bi bi-lock"></i>
                    </div>
                  </div>
                  <h1 class="utp-h2 mb-2">Cambiar contraseña</h1>
                  <p class="text-muted mb-0">
                    Actualiza tu contraseña para mantener tu cuenta segura.
                  </p>
                </div>

                <?php if (isset($_SESSION['pwd_success'])): ?>
                  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($_SESSION['pwd_success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>
                  <?php unset($_SESSION['pwd_success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['pwd_error'])): ?>
                  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?= htmlspecialchars($_SESSION['pwd_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>
                  <?php unset($_SESSION['pwd_error']); ?>
                <?php endif; ?>

                <form id="changePasswordForm" method="POST" action="" novalidate>
                  <?= Security::csrfField() ?>

                  <div class="mb-4">
                    <label for="currentPassword" class="form-label fw-medium">Contraseña actual</label>
                    <div class="position-relative">
                      <input type="password" class="form-control utp-input" id="currentPassword" name="current_password" placeholder="Ingresa tu contraseña actual" required>
                      <button type="button" class="utp-toggle-password" data-target="#currentPassword" aria-label="Mostrar/Ocultar contraseña">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="newPassword" class="form-label fw-medium">Nueva contraseña</label>
                    <div class="position-relative">
                      <input type="password" class="form-control utp-input" id="newPassword" name="new_password" placeholder="Ingresa tu nueva contraseña" required minlength="8">
                      <button type="button" class="utp-toggle-password" data-target="#newPassword" aria-label="Mostrar/Ocultar contraseña">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                    <div id="strengthContainer" class="mt-2 d-none">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="utp-strength-bar flex-grow-1">
                          <div class="utp-strength-fill" id="strengthBar" style="width:0%;"></div>
                        </div>
                        <span class="utp-strength-text" id="strengthLabel" style="min-width:70px;"></span>
                      </div>
                      <ul class="list-unstyled small text-muted mt-1 mb-0" id="reqList">
                        <li id="req-length"><i class="bi bi-x-circle text-danger me-1"></i>Mínimo 8 caracteres</li>
                        <li id="req-upper"><i class="bi bi-x-circle text-danger me-1"></i>Una letra mayúscula</li>
                        <li id="req-lower"><i class="bi bi-x-circle text-danger me-1"></i>Una letra minúscula</li>
                        <li id="req-number"><i class="bi bi-x-circle text-danger me-1"></i>Un número</li>
                        <li id="req-special"><i class="bi bi-x-circle text-danger me-1"></i>Un carácter especial</li>
                      </ul>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="confirmPassword" class="form-label fw-medium">Confirmar nueva contraseña</label>
                    <div class="position-relative">
                      <input type="password" class="form-control utp-input" id="confirmPassword" name="confirm_password" placeholder="Confirma tu nueva contraseña" required>
                      <button type="button" class="utp-toggle-password" data-target="#confirmPassword" aria-label="Mostrar/Ocultar contraseña">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                    <div class="invalid-feedback" id="confirmError">Las contraseñas no coinciden.</div>
                  </div>

                  <button type="submit" id="btnSubmit" class="btn btn-utp-red w-100 btn-utp-lg mb-4">
                    Guardar y continuar
                  </button>
                </form>

                <div class="utp-info-box">
                  <div class="d-flex gap-2">
                    <div class="flex-shrink-0 utp-info-icon">
                      <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="flex-grow-1">
                      <strong class="utp-info-title">Consejo:</strong>
                      <p class="utp-info-text mb-0">
                        Usa una contraseña única que no uses en otros sitios y guárdala en un lugar seguro.
                      </p>
                    </div>
                  </div>
                </div>

              </article>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <script src="<?= ASSETS_URL ?>/js/egresado/seguridad.js"></script>
</body>
</html>
