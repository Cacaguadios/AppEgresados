<?php
session_start();

// Si ya está autenticado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $r = match($_SESSION['usuario_rol'] ?? '') { 'admin' => '../admin/inicio.php', 'docente','ti' => '../docente/inicio.php', default => '../egresado/inicio.php' };
    header('Location: ' . $r);
    exit;
}

require_once __DIR__ . '/../../app/controllers/VerificationController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$verificationCtrl = new VerificationController();
$errorMsg = null;
$successMsg = null;

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Security::validateCsrfToken($csrf)) {
        $errorMsg = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (empty($email)) {
            $errorMsg = 'El correo electrónico es requerido.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'El formato del correo no es válido.';
        } else {
            $result = $verificationCtrl->sendPasswordResetCode($email);

            // Guardar email en sesión para pasos siguientes
            $_SESSION['reset_email'] = $email;

            // Redirigir a verificación de código
            header('Location: verify-code.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contraseña | Egresados UTP</title>

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

      <!-- Back to login -->
      <a class="auth-back d-inline-flex align-items-center gap-2 mb-3" href="login.php">
        <i class="bi bi-chevron-left"></i>
        <span>Volver al inicio de sesión</span>
      </a>

      <section class="auth-forgot-card mx-auto">
        <!-- Header -->
        <header class="text-center mb-4">
          <div class="auth-forgot-icon mx-auto mb-3">
            <i class="bi bi-shield-lock"></i>
          </div>

          <h1 class="auth-wizard-title mb-2">Recuperar contraseña</h1>
          <p class="auth-wizard-subtitle mb-0">
            Ingresa tu correo institucional y te enviaremos un código de verificación
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

          <div class="mb-4">
            <label class="form-label" style="font-size:14px; font-weight:500;">
              Correo institucional <span class="text-danger">*</span>
            </label>
            <input class="form-control auth-input"
                   type="email"
                   name="email"
                   placeholder="tu.correo@alumno.utpuebla.edu.mx"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   required />
            <div class="auth-help mt-2">
              Ingresa el correo institucional asociado a tu cuenta
            </div>
          </div>

          <button class="btn btn-utp-red w-100 auth-cta" type="submit">
            <i class="bi bi-send me-2"></i>Enviar código de verificación
          </button>
        </form>

        <!-- Link volver -->
        <div class="text-center mt-4">
          <a class="link-utp" href="login.php">
            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
          </a>
        </div>
      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/app.js"></script>
</body>
</html>
