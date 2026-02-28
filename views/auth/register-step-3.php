<?php
session_start();

// Si ya está autenticado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $r = match($_SESSION['usuario_rol'] ?? '') { 'admin' => '../admin/inicio.php', 'docente','ti' => '../docente/inicio.php', default => '../egresado/inicio.php' };
    header('Location: ' . $r);
    exit;
}

// Verificar que venga del paso 2 (debe existir el rol y la verificación en sesión)
if (empty($_SESSION['registro_rol']) || empty($_SESSION['registro_verificacion'])) {
    header('Location: register-step-1.php');
    exit;
}

require_once __DIR__ . '/../../app/controllers/RegisterController.php';

$registroController = new RegisterController();
$errorCreacion = null;

// Procesar POST: crear usuario en BD y redirigir a success
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = $_POST['nombre']    ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $role      = $_SESSION['registro_rol'];
    $verif     = $_SESSION['registro_verificacion'];

    $resultado = $registroController->createUser($nombre, $apellidos, $role, $verif);

    if ($resultado['success']) {
        // Guardar credenciales en sesión para verificación de email
        $_SESSION['nuevas_credenciales'] = [
            'usuario'   => $resultado['usuario'],
            'password'  => $resultado['password'],
            'email'     => $resultado['email'],
            'nombre'    => $resultado['nombre'],
            'apellidos' => $resultado['apellidos'],
            'role'      => $resultado['role'],
        ];

        // Redirigir al paso 4 - Verificación de email institucional
        header('Location: register-step-4.php');
        exit;
    } else {
        $errorCreacion = $resultado['message'];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - Información básica | Egresados UTP</title>

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
  <main class="auth-wizard-shell">
    <div class="container py-4 py-md-5">

      <!-- Back to login -->
      <a class="auth-back d-inline-flex align-items-center gap-2 mb-3" href="login.php">
        <i class="bi bi-chevron-left"></i>
        <span>Volver al inicio de sesión</span>
      </a>

      <section class="auth-wizard-card mx-auto">
        <!-- Header -->
        <header class="text-center auth-wizard-header">
          <img class="auth-wizard-icon mb-2"
               src="../../public/assets/img/utp-logo.png"
               alt="Icono UTP" />

          <h1 class="auth-wizard-title mb-2">Dar de Alta Usuario</h1>
          <p class="auth-wizard-subtitle mb-3">
            El sistema generará tus credenciales automáticamente
          </p>

          <!-- Stepper: 3/4 activos -->
          <div class="auth-stepper" aria-label="Progreso de registro">
            <span class="auth-step active"></span>
            <span class="auth-step active"></span>
            <span class="auth-step active"></span>
            <span class="auth-step"></span>
          </div>
        </header>

        <div class="auth-wizard-body">
          <div class="mb-4">
            <h2 class="auth-section-title mb-1">Información básica</h2>
            <p class="auth-section-subtitle mb-0">
              El sistema generará tu usuario y contraseña automáticamente
            </p>
          </div>

          <!-- Error Alert -->
          <?php if ($errorCreacion): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($errorCreacion); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <form id="informatcionBasicaForm" method="POST" class="needs-validation" autocomplete="off">
            <!-- Inputs en 2 columnas en desktop, 1 en móvil -->
            <div class="row g-3 mb-4">
              <div class="col-12 col-md-6">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  Nombre(s) <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input" 
                       type="text" 
                       name="nombre"
                       placeholder="Ingresa tu nombre"
                       required />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  Apellidos <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input" 
                       type="text" 
                       name="apellidos"
                       placeholder="Ingresa tus apellidos"
                       required />
              </div>
            </div>

            <!-- Nota informativa -->
            <div class="auth-note mb-4">
              <span class="auth-note-title">
                <i class="bi bi-info-circle me-1"></i>Nota:
              </span>
              <span class="auth-note-text">
                El sistema generará automáticamente tu nombre de usuario y una contraseña temporal.
                Deberás cambiar la contraseña en tu primer inicio de sesión.
              </span>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2 auth-actions">
              <button class="btn btn-light auth-btn-back" type="button" id="btnBack">
                <i class="bi bi-chevron-left me-1"></i> Atrás
              </button>

              <button class="btn btn-utp-green auth-btn-continue" type="submit" id="btnGenerate">
                <i class="bi bi-check-circle me-1"></i> Generar Credenciales
              </button>
            </div>
          </form>
        </div>
      </section>

    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Scripts -->
  <script src="../../public/assets/js/app.js"></script>
  
  <script>
    // Botón atrás
    document.getElementById('btnBack').addEventListener('click', function () {
      window.history.back();
    });
  </script>
  
</body>
</html>
