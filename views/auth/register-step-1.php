<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = '/AppEgresados';

// Si ya está autenticado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $r = match($_SESSION['usuario_rol'] ?? '') { 'admin' => '/AppEgresados/admin/inicio', 'docente','ti' => '/AppEgresados/docente/inicio', default => '/AppEgresados/egresado/inicio' };
    header('Location: ' . $r);
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dar de Alta Usuario - Egresados UTP</title>

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
  <main class="auth-wizard-shell">
    <div class="container py-4 py-md-5">
      <!-- Back -->
      <a class="auth-back d-inline-flex align-items-center gap-2 mb-3" href="/AppEgresados/login">
        <i class="bi bi-chevron-left"></i>
        <span>Volver al inicio de sesión</span>
      </a>

      <!-- Card -->
      <section class="auth-wizard-card mx-auto">
        <!-- Wizard header -->
        <header class="text-center auth-wizard-header">
          <img class="auth-wizard-icon mb-2"
               src="<?= ASSETS_URL ?>/img/utp-logo.png"
               alt="Icono UTP" />

          <h1 class="auth-wizard-title mb-2">Dar de Alta Usuario</h1>
          <p class="auth-wizard-subtitle mb-3">
            El sistema generará tus credenciales automáticamente
          </p>

          <!-- Stepper: paso 1/4 activo -->
          <div class="auth-stepper" aria-label="Progreso de registro">
            <span class="auth-step active"></span>
            <span class="auth-step"></span>
            <span class="auth-step"></span>
            <span class="auth-step"></span>
          </div>
        </header>

        <!-- Content -->
        <div class="auth-wizard-body">
          <div class="mb-4">
            <h2 class="auth-section-title mb-1">Selecciona el tipo de usuario</h2>
            <p class="auth-section-subtitle mb-0">Elige el rol para la cuenta que vas a crear</p>
          </div>

          <!-- Role options -->
          <form id="roleForm" class="d-grid gap-3">
            <!-- Egresado -->
            <button type="button"
                    class="role-card active"
                    data-role="egresado">
              <div class="role-card-title">
                <i class="bi bi-mortarboard me-2"></i>Egresado
              </div>
              <div class="role-card-subtitle">Buscar ofertas laborales y completar seguimiento</div>
            </button>

            <!-- Docente -->
            <button type="button"
                    class="role-card"
                    data-role="docente">
              <div class="role-card-title">
                <i class="bi bi-person-badge me-2"></i>Docente
              </div>
              <div class="role-card-subtitle">Publicar ofertas laborales y gestionar candidatos</div>
            </button>

            <!-- Personal TI -->
            <button type="button"
                    class="role-card"
                    data-role="ti">
              <div class="role-card-title">
                <i class="bi bi-gear me-2"></i>Personal TI
              </div>
              <div class="role-card-subtitle">Publicar ofertas y administrar sistema</div>
            </button>

            <div class="mt-3">
              <button class="btn btn-utp-red w-100 auth-btn-continue" type="submit" id="btnContinue">
                Continuar
              </button>
              <!-- Guardamos el rol seleccionado -->
              <input type="hidden" id="selectedRole" name="selectedRole" value="egresado">
            </div>
          </form>
        </div>
      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Scripts -->
  <script src="<?= ASSETS_URL ?>/js/app.js"></script>
  
  <script>
    // Seleccionar rol
    document.querySelectorAll('.role-card').forEach(card => {
      card.addEventListener('click', function() {
        // Remover active de todos
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
        // Agregar active al clickeado
        this.classList.add('active');
        // Guardar en hidden input
        document.getElementById('selectedRole').value = this.dataset.role;
      });
    });

    // Enviar formulario
    document.getElementById('roleForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const role = document.getElementById('selectedRole').value;
      // Guardar en sessionStorage y redirigir
      sessionStorage.setItem('registroRol', role);
      window.location.href = '/AppEgresados/register-step-2';
    });
  </script>
  
</body>
</html>
