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

// Verificar que existan credenciales generadas en sesión
if (!isset($_SESSION['nuevas_credenciales'])) {
    // No hay credenciales, redirigir a paso 1
    header('Location: ' . $baseUrl . '/register-step-1');
    exit;
}

$cred    = $_SESSION['nuevas_credenciales'];
$usuario = $cred['usuario']  ?? '';
$pass    = $cred['password']  ?? '';
$role    = $cred['role']      ?? 'egresado';
$nombre  = $cred['nombre']    ?? '';

// Mapear rol a etiqueta visible
$roleLabels = [
    'egresado' => 'Egresado',
    'docente'  => 'Docente',
    'ti'       => 'Personal TI',
];
$roleLabel = $roleLabels[$role] ?? 'Egresado';

// Limpiar credenciales de sesión para que no se puedan volver a ver
// (mantener solo usuario para login pre-llenado)
$_SESSION['login_prefill_user'] = $usuario;
unset($_SESSION['nuevas_credenciales']);
unset($_SESSION['registro_rol']);
unset($_SESSION['registro_verificacion']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Credenciales generadas | Egresados UTP</title>

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

      <section class="auth-wizard-card auth-cred-card mx-auto">

        <!-- Header éxito -->
        <header class="text-center">
          <div class="auth-success-icon mx-auto mb-3" aria-hidden="true">
            <i class="bi bi-check2"></i>
          </div>

          <h1 class="auth-wizard-title mb-2">¡Registro exitoso!</h1>

          <p class="auth-success-sub mb-0">
            Tu cuenta como <span class="auth-success-role"><?php echo htmlspecialchars($roleLabel); ?></span> ha sido creada
          </p>
        </header>

        <!-- Credenciales -->
        <section class="auth-cred-box mt-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-key auth-cred-title-icon"></i>
            <h2 class="auth-cred-title mb-0">Tus credenciales de acceso</h2>
          </div>

          <div class="d-grid gap-3">
            <!-- Usuario -->
            <div>
              <div class="auth-cred-label">Usuario generado</div>

              <div class="d-flex gap-2 align-items-stretch flex-wrap flex-sm-nowrap">
                <div class="auth-cred-value flex-grow-1">
                  <i class="bi bi-person"></i>
                  <code id="credUser"><?php echo htmlspecialchars($usuario); ?></code>
                </div>

                <button class="auth-cred-iconbtn" type="button"
                        data-copy-target="#credUser" aria-label="Copiar usuario">
                  <i class="bi bi-copy"></i>
                </button>
              </div>
            </div>

            <!-- Password -->
            <div>
              <div class="auth-cred-label">Contraseña temporal</div>

              <div class="d-flex gap-2 align-items-stretch flex-wrap flex-sm-nowrap">
                <div class="auth-cred-value flex-grow-1">
                  <i class="bi bi-shield-lock"></i>
                  <code id="credPass" data-value="<?php echo htmlspecialchars($pass); ?>">••••••••••••</code>
                </div>

                <button class="auth-cred-iconbtn" type="button"
                        id="togglePass" aria-label="Mostrar/ocultar contraseña">
                  <i class="bi bi-eye"></i>
                </button>

                <button class="auth-cred-iconbtn" type="button"
                        data-copy-target="#credPass" data-copy-value="data-value"
                        aria-label="Copiar contraseña">
                  <i class="bi bi-copy"></i>
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- Avisos -->
        <section class="mt-4 d-grid gap-3">
          <div class="auth-alert auth-alert-warn">
            <div class="auth-alert-title">⚠️ Importante</div>
            <ul class="auth-alert-list mb-0">
              <li>Guarda estas credenciales en un lugar seguro</li>
              <li>Deberás cambiar tu contraseña en el primer inicio de sesión</li>
              <li>Tu cuenta será verificada por un administrador antes de activarse completamente</li>
            </ul>
          </div>

          <div class="auth-alert auth-alert-info">
            <div>
              <span class="auth-alert-title">Consejo:</span>
              <span class="auth-alert-text">
                Copia tus credenciales antes de continuar o toma una captura de pantalla.
              </span>
            </div>
          </div>
        </section>

        <!-- Acciones -->
        <section class="mt-4">
          <a class="btn btn-utp-green text-white w-100 auth-cta" href="/AppEgresados/login">
            Continuar al inicio de sesión
          </a>

          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-light w-50 auth-secondary" type="button" id="copyAll">
              Copiar todo
            </button>
            <button class="btn btn-light w-50 auth-secondary" type="button" id="printCreds">
              Imprimir
            </button>
          </div>
        </section>

      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/app.js"></script>

  <script>
  (function () {
    /* ── helpers ── */
    const $ = (s) => document.querySelector(s);
    const passCode  = $('#credPass');
    const realPass  = passCode.dataset.value;
    let   passShown = false;

    /* ── Toggle password visibility ── */
    $('#togglePass').addEventListener('click', function () {
      passShown = !passShown;
      passCode.textContent = passShown ? realPass : '••••••••••••';
      this.querySelector('i').className = passShown ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    /* ── Copy buttons ── */
    document.querySelectorAll('[data-copy-target]').forEach(btn => {
      btn.addEventListener('click', function () {
        const target = $(this.dataset.copyTarget);
        const value  = this.dataset.copyValue
                     ? target.getAttribute(this.dataset.copyValue)
                     : target.textContent.trim();

        navigator.clipboard.writeText(value).then(() => {
          const icon = this.querySelector('i');
          icon.className = 'bi bi-check-lg';
          setTimeout(() => { icon.className = 'bi bi-copy'; }, 1500);
        });
      });
    });

    /* ── Copy all ── */
    $('#copyAll').addEventListener('click', function () {
      const user = $('#credUser').textContent.trim();
      const text = `Usuario: ${user}\nContraseña: ${realPass}`;

      navigator.clipboard.writeText(text).then(() => {
        this.textContent = '✓ Copiado';
        setTimeout(() => { this.textContent = 'Copiar todo'; }, 2000);
      });
    });

    /* ── Print ── */
    $('#printCreds').addEventListener('click', function () {
      // Mostrar contraseña antes de imprimir
      const wasHidden = !passShown;
      if (wasHidden) passCode.textContent = realPass;

      window.print();

      // Volver a ocultar si estaba oculta
      if (wasHidden) passCode.textContent = '••••••••••••';
    });
  })();
  </script>
</body>
</html>
