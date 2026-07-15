<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/bootstrap.php';

// Si ya está autenticado, redirigir según rol
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: ' . dashboard_url($_SESSION['usuario_rol'] ?? null));
    exit;
}

require_once __DIR__ . '/../../app/helpers/Security.php';

// Procesar formulario si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../app/controllers/AuthController.php';
    
    $auth = new AuthController();
    if ($auth->processLogin()) {
        header('Location: ' . dashboard_url($_SESSION['usuario_rol'] ?? null));
        exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio de Sesión - Egresados UTP</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Global Styles -->
  <link href="<?= ASSETS_URL ?>/css/global.css" rel="stylesheet">
  <!-- Auth Styles -->
  <link href="<?= ASSETS_URL ?>/css/auth.css" rel="stylesheet">
</head>

<body>
  <main class="auth-shell">
    <div class="container-fluid p-0">
      
      <!-- Mobile Hero (solo visible en móvil) -->
      <div class="auth-mobile-hero">
        <img class="auth-mobile-logo" src="<?= ASSETS_URL ?>/img/utp-logo.png" alt="UTP Logo" />
        <h1 class="auth-mobile-title">Sistema de Egresados UTP</h1>
      </div>

      <div class="row g-0 min-vh-100">

        <!-- LEFT / HERO -->
        <section class="col-12 col-lg-6 auth-hero d-flex align-items-center justify-content-center">
          <div class="w-100 px-4 px-md-5 py-5">
            <div class="auth-hero-content">

              <img class="hero-image mb-4 img-drop-shadow"
                   src="<?= ASSETS_URL ?>/img/utp-logo.png"
                   alt="EGRESADOS UTP" />

              <h1 class="hero-title">Sistema de</h1>
              <h2 class="hero-title">Egresados UTP</h2>

              <p class="hero-sub mt-3 mb-4">
                Plataforma integral para la vinculación laboral<br>
                de egresados de Tecnologías de la Información
              </p>

              <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-2">
                  <span class="hero-dot"></span>
                  <span class="hero-bullet">Ofertas laborales validadas</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="hero-dot"></span>
                  <span class="hero-bullet">Seguimiento de egresados</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="hero-dot"></span>
                  <span class="hero-bullet">Conexión directa con empleadores</span>
                </div>
              </div>

            </div>
          </div>
        </section>

        <!-- RIGHT / FORM -->
        <section class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5 px-3 px-md-5">
          <div class="auth-card w-100 d-flex flex-column" style="max-width: 426px; padding: 33px; gap: 32px; background: rgba(255, 255, 255, 0.85); box-shadow: 0px 25px 50px -12px rgba(0, 0, 0, 0.25); border-radius: 24px; outline: 1px rgba(255, 255, 255, 0.30) solid; outline-offset: -1px;">

            <div class="text-center">
              <img src="<?= ASSETS_URL ?>/img/ibbt-big.png"
                   alt="Egresados EXA TIID"
                   style="width: 104.47px; height: 148px; object-fit: contain; display: inline-block;">
            </div>

            <header class="d-flex flex-column gap-2">
              <h3 class="mb-0 text-center" style="font-size:30px; font-weight:700; line-height:36px; color: var(--text);">
                Inicio de Sesión
              </h3>
              <p class="mb-0 text-center" style="color:var(--muted); font-size:16px; line-height:24px;">
                Ingresa tus credenciales para continuar
              </p>
            </header>

            <!-- Mostrar errores -->
            <?php if (isset($_SESSION['error'])): ?>
              <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
              <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Mostrar éxito -->
            <?php if (isset($_SESSION['success'])): ?>
              <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
              <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form class="needs-validation d-flex flex-column" method="POST" action="" style="gap: 24px;">
              
              <!-- CSRF Token -->
              <?= Security::csrfField() ?>
              
              <div>
                <label class="form-label" style="font-size:14px; font-weight:500; color: var(--text); line-height: 14px; margin-bottom: 8px;">
                  Usuario
                </label>
                <input class="form-control auth-input"
                       type="text"
                       name="identifier"
                       placeholder="Ingresa tu usuario"
                       required
                       autocomplete="username"
                       value="<?= htmlspecialchars($_POST['identifier'] ?? $_SESSION['login_prefill_user'] ?? '') ?>" />
                <?php unset($_SESSION['login_prefill_user']); ?>
              </div>

              <div>
                <label class="form-label" style="font-size:14px; font-weight:500; color: var(--text); line-height: 14px; margin-bottom: 8px;">
                  Contraseña
                </label>

                <div class="input-group" style="height: 48px;">
                  <input id="password"
                         class="form-control auth-input"
                         type="password"
                         name="password"
                         placeholder="Ingresa tu contraseña"
                         required
                         style="border-right: 0;" />
                  <button class="btn btn-outline-secondary"
                          type="button"
                          id="togglePassword"
                          style="border-left: 0; border-radius: 0 12px 12px 0;">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div>
                <a class="link-utp" href="<?= e(app_url('/forgot')) ?>" style="font-size:16px; line-height:24px; font-weight:500;">¿Olvidaste tu contraseña?</a>
              </div>

              <button class="btn btn-utp-green text-white w-100" type="submit" style="height: 48px; border-radius: 20px; font-size: 16px; font-weight: 500; line-height: 24px;">
                Iniciar Sesión
              </button>

              <div class="text-center">
                <span style="color:var(--muted); font-size:14px; line-height:20px; font-weight:400;">
                  ¿No tienes cuenta?
                </span>
                <a class="link-utp" href="<?= e(app_url('/register-step-1')) ?>" style="font-size:16px; line-height:24px; font-weight:500;">
                  Regístrate aquí
                </a>
              </div>
            </form>

          </div>
        </section>

      </div>
      
      <!-- Mobile Footer Bullets (solo visible en móvil) -->
      <div class="auth-mobile-footer">
        <div class="auth-mobile-bullet">
          <span class="hero-dot"></span>
          <span>Ofertas laborales validadas</span>
        </div>
        <div class="auth-mobile-bullet">
          <span class="hero-dot"></span>
          <span>Seguimiento de egresados</span>
        </div>
        <div class="auth-mobile-bullet">
          <span class="hero-dot"></span>
          <span>Conexión directa con empleadores</span>
        </div>
      </div>
      
      <!-- Mobile Copyright (solo visible en móvil) -->
      <div class="auth-mobile-copyright">
        © 2017 - 2026, Universidad Tecnológica de Puebla
      </div>

    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Scripts -->
  <script src="<?= ASSETS_URL ?>/js/app.js"></script>
  
  <!-- Script para mostrar/ocultar contraseña -->
  <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    });
  </script>
  
</body>
</html>