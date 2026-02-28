<?php
session_start();

// Si ya está autenticado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $r = match($_SESSION['usuario_rol'] ?? '') { 'admin' => '../admin/inicio.php', 'docente','ti' => '../docente/inicio.php', default => '../egresado/inicio.php' };
    header('Location: ' . $r);
    exit;
}

// Verificar que venga del flujo de recuperación
if (empty($_SESSION['password_updated'])) {
    header('Location: login.php');
    exit;
}

// Limpiar flag
unset($_SESSION['password_updated']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contraseña actualizada | Egresados UTP</title>

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

      <section class="auth-forgot-card mx-auto">
        <!-- Header éxito -->
        <header class="text-center mb-4">
          <div class="auth-forgot-icon auth-forgot-icon-success mx-auto mb-3">
            <i class="bi bi-check-lg"></i>
          </div>

          <h1 class="auth-wizard-title mb-2">¡Contraseña actualizada!</h1>
          <p class="auth-wizard-subtitle mb-0">
            Tu contraseña ha sido cambiada exitosamente.<br>
            Ya puedes iniciar sesión con tu nueva contraseña.
          </p>
        </header>

        <!-- CTA -->
        <a class="btn btn-utp-green text-white w-100 auth-cta" href="login.php">
          <i class="bi bi-box-arrow-in-right me-2"></i>Ir al inicio de sesión
        </a>
      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/app.js"></script>
</body>
</html>
