<?php
session_start();

// Si ya está autenticado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $r = match($_SESSION['usuario_rol'] ?? '') { 'admin' => '../admin/inicio.php', 'docente','ti' => '../docente/inicio.php', default => '../egresado/inicio.php' };
    header('Location: ' . $r);
    exit;
}

// Verificar que el código fue verificado
if (empty($_SESSION['reset_email']) || empty($_SESSION['reset_code_verified'])) {
    header('Location: forgot.php');
    exit;
}

require_once __DIR__ . '/../../app/controllers/VerificationController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$verificationCtrl = new VerificationController();
$errorMsg = null;

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Security::validateCsrfToken($csrf)) {
        $errorMsg = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'];

        $result = $verificationCtrl->resetPassword($email, $password, $confirmPassword);

        if ($result['success']) {
            // Limpiar sesión de recuperación
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_code_verified']);

            // Redirigir a confirmación
            $_SESSION['password_updated'] = true;
            header('Location: password-updated.php');
            exit;
        } else {
            $errorMsg = $result['message'];
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva contraseña | Egresados UTP</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Global Styles -->
  <link href="../../public/assets/css/global.css" rel="stylesheet">
  <!-- Auth Styles -->
  <link href="../../public/assets/css/auth.css" rel="stylesheet">
</head>

<body>
  <main class="auth-forgot-shell">
    <div class="container py-4 py-md-5">

      <!-- Back -->
      <a class="auth-back d-inline-flex align-items-center gap-2 mb-3" href="forgot.php">
        <i class="bi bi-chevron-left"></i>
        <span>Volver</span>
      </a>

      <section class="auth-forgot-card mx-auto">
        <!-- Header -->
        <header class="text-center mb-4">
          <div class="auth-forgot-icon mx-auto mb-3">
            <i class="bi bi-key"></i>
          </div>

          <h1 class="auth-wizard-title mb-2">Nueva contraseña</h1>
          <p class="auth-wizard-subtitle mb-0">
            Crea una nueva contraseña para tu cuenta
          </p>
        </header>

        <!-- Error Alert -->
        <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <?php echo htmlspecialchars($errorMsg); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="needs-validation" autocomplete="off">
          <?php echo Security::csrfField(); ?>

          <!-- Nueva contraseña -->
          <div class="mb-3">
            <label class="form-label" style="font-size:14px; font-weight:500;">
              Nueva contraseña <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input class="form-control auth-input"
                     type="password"
                     name="password"
                     id="password"
                     placeholder="Mínimo 8 caracteres"
                     minlength="8"
                     required />
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <!-- Confirmar contraseña -->
          <div class="mb-3">
            <label class="form-label" style="font-size:14px; font-weight:500;">
              Confirmar contraseña <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input class="form-control auth-input"
                     type="password"
                     name="confirm_password"
                     id="confirmPassword"
                     placeholder="Repite tu contraseña"
                     minlength="8"
                     required />
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <!-- Nota de requisitos -->
          <div class="auth-note mb-4">
            <span class="auth-note-title">
              <i class="bi bi-info-circle me-1"></i>Requisitos:
            </span>
            <span class="auth-note-text">
              Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
            </span>
          </div>

          <button class="btn btn-utp-green w-100 auth-cta" type="submit">
            <i class="bi bi-check-circle me-2"></i>Guardar contraseña
          </button>
        </form>
      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/app.js"></script>

  <script>
    function togglePassword(fieldId, btn) {
      const field = document.getElementById(fieldId);
      const icon = btn.querySelector('i');
      if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
      } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
      }
    }
  </script>
</body>
</html>
