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

// Verificar que venga del paso 3 (credenciales deben existir en sesión)
if (!isset($_SESSION['nuevas_credenciales'])) {
    header('Location: ' . $baseUrl . '/register-step-1');
    exit;
}

require_once __DIR__ . '/../../app/controllers/VerificationController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$verificationCtrl = new VerificationController();
$cred = $_SESSION['nuevas_credenciales'];
$role = $cred['role'] ?? 'egresado';

// Para egresados, usar el email personal. Para otros roles, usar el email generado
$emailParaVerificacion = $cred['email'];
$errorMsg = null;
$successMsg = null;
$emailSent = false;

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_code') {
        // Enviar código de verificación al email del usuario
        $result = $verificationCtrl->sendVerificationCode($emailParaVerificacion, 'registro');
        if ($result['success']) {
            $emailSent = true;
            $successMsg = 'Código enviado a ' . $emailParaVerificacion . '. Revisa tu bandeja de entrada.';
        } else {
            $errorMsg = $result['message'];
        }
    } elseif ($action === 'verify_code') {
        // Verificar código ingresado
        $code = '';
        for ($i = 1; $i <= 6; $i++) {
            $code .= $_POST["code{$i}"] ?? '';
        }

        $result = $verificationCtrl->verifyCode($emailParaVerificacion, $code, 'registro');
        if ($result['success']) {
            // Buscar el usuario recién creado y actualizar email verificado
            require_once __DIR__ . '/../../app/models/Usuario.php';
            $usuarioModel = new Usuario();
            $usuario = $usuarioModel->getByUsuario($cred['usuario']);
            if ($usuario) {
                $verificationCtrl->verifyRegistrationEmail($usuario['id'], $emailParaVerificacion, $code);
            }

            // Redirigir a credenciales exitosas
            header('Location: ' . $baseUrl . '/credentials-success');
            exit;
        } else {
            $errorMsg = $result['message'];
            $emailSent = true; // Mantener visible la sección de código
        }
    } elseif ($action === 'resend_code') {
        // Reenviar código
        $result = $verificationCtrl->sendVerificationCode($emailParaVerificacion, 'registro');
        if ($result['success']) {
            $emailSent = true;
            $successMsg = 'Nuevo código enviado a ' . $emailParaVerificacion;
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
  <title>Registro - Verificación Email | Egresados UTP</title>

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

      <!-- Back to login -->
      <a class="auth-back d-inline-flex align-items-center gap-2 mb-3" href="/AppEgresados/login">
        <i class="bi bi-chevron-left"></i>
        <span>Volver al inicio de sesión</span>
      </a>

      <section class="auth-wizard-card mx-auto">
        <!-- Header -->
        <header class="text-center auth-wizard-header">
          <img class="auth-wizard-icon mb-2"
               src="<?= ASSETS_URL ?>/img/utp-logo.png"
               alt="Icono UTP" />

          <h1 class="auth-wizard-title mb-2">Verificar Correo</h1>
          <p class="auth-wizard-subtitle mb-3">
            Confirma tu correo electrónico
          </p>

          <!-- Stepper: 4/4 todos activos -->
          <div class="auth-stepper" aria-label="Progreso de registro">
            <span class="auth-step active"></span>
            <span class="auth-step active"></span>
            <span class="auth-step active"></span>
            <span class="auth-step active"></span>
          </div>
        </header>

        <div class="auth-wizard-body">
          <div class="mb-4">
            <h2 class="auth-section-title mb-1">Verificación de correo</h2>
            <p class="auth-section-subtitle mb-0">
              Ingresa el código que enviamos a tu correo
            </p>
          </div>

          <!-- Error Alert -->
          <?php if ($errorMsg): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($errorMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <!-- Success Alert -->
          <?php if ($successMsg): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($successMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <!-- Sección: Enviar código (inicial) -->
          <div id="sendCodeSection" class="<?php echo $emailSent ? 'd-none' : ''; ?>">
            <form method="POST" class="needs-validation" autocomplete="off">
              <input type="hidden" name="action" value="send_code">

              <div class="text-center mb-4">
                <div class="auth-forgot-icon mx-auto mb-3">
                  <i class="bi bi-envelope-heart"></i>
                </div>
                <p class="auth-section-subtitle">
                  Enviaremos un código de verificación a<br>
                  <strong><?php echo htmlspecialchars($emailParaVerificacion); ?></strong>
                </p>
              </div>

              <!-- Actions -->
              <div class="d-flex gap-2 auth-actions mt-4">
                <button class="btn btn-light auth-btn-back" type="button" id="btnBack">
                  <i class="bi bi-chevron-left me-1"></i> Atrás
                </button>

                <button class="btn btn-utp-red auth-btn-continue" type="submit">
                  <i class="bi bi-envelope me-1"></i> Enviar Código
                </button>
              </div>
            </form>
          </div>

          <!-- Sección: Verificar código (aparece después de enviar) -->
          <div id="verifyCodeSection" class="<?php echo $emailSent ? '' : 'd-none'; ?>">
            <div class="text-center mb-4">
              <div class="auth-forgot-icon mx-auto mb-3">
                <i class="bi bi-envelope-check"></i>
              </div>
              <p class="auth-section-subtitle">
                Ingresa el código de 6 dígitos enviado a<br>
                <strong><?php echo htmlspecialchars($emailParaVerificacion); ?></strong>
              </p>
            </div>

            <form method="POST" class="needs-validation" autocomplete="off" id="verifyForm">
              <input type="hidden" name="action" value="verify_code">

              <!-- 6 inputs individuales -->
              <div class="auth-code-inputs d-flex justify-content-center gap-2 mb-4">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <input type="text"
                       class="auth-code-input form-control text-center"
                       name="code<?php echo $i; ?>"
                       maxlength="1"
                       inputmode="numeric"
                       pattern="[0-9]"
                       required
                       autocomplete="off" />
                <?php endfor; ?>
              </div>

              <!-- Reenviar código -->
              <div class="text-center mb-4">
                <span class="text-muted" style="font-size:14px;">¿No recibiste el código?</span>
                <button type="button" class="btn btn-link link-utp p-0 ms-1" style="font-size:14px;" id="btnResend">
                  Reenviar código
                </button>
              </div>

              <!-- Actions -->
              <div class="d-flex gap-2 auth-actions">
                <button class="btn btn-light auth-btn-back" type="button" id="btnChangeEmail">
                  <i class="bi bi-chevron-left me-1"></i> Atrás
                </button>

                <button class="btn btn-utp-green auth-btn-continue" type="submit" id="btnVerify" disabled>
                  <i class="bi bi-check-circle me-1"></i> Verificar
                </button>
              </div>
            </form>

            <!-- Form oculto para reenviar -->
            <form method="POST" id="resendForm" class="d-none">
              <input type="hidden" name="action" value="resend_code">
            </form>
          </div>
        </div>
      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/app.js"></script>

  <script>
  (function() {
    // ── Botón atrás ──
    const btnBack = document.getElementById('btnBack');
    if (btnBack) {
      btnBack.addEventListener('click', function() {
        window.history.back();
      });
    }

    // ── Cambiar email: volver a mostrar sección de envío ──
    const btnChangeEmail = document.getElementById('btnChangeEmail');
    if (btnChangeEmail) {
      btnChangeEmail.addEventListener('click', function() {
        document.getElementById('sendCodeSection').classList.remove('d-none');
        document.getElementById('verifyCodeSection').classList.add('d-none');
      });
    }

    // ── Reenviar código ──
    const btnResend = document.getElementById('btnResend');
    if (btnResend) {
      btnResend.addEventListener('click', function() {
        document.getElementById('resendForm').submit();
      });
    }

    // ── Code inputs: auto-focus y habilitar botón ──
    const codeInputs = document.querySelectorAll('.auth-code-input');
    const btnVerify = document.getElementById('btnVerify');

    codeInputs.forEach((input, index) => {
      input.addEventListener('input', function(e) {
        // Solo permitir dígitos
        this.value = this.value.replace(/\D/g, '').slice(0, 1);

        // Auto-focus al siguiente
        if (this.value && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }

        // Habilitar/deshabilitar botón verificar
        checkCodeComplete();
      });

      input.addEventListener('keydown', function(e) {
        // Backspace: volver al anterior
        if (e.key === 'Backspace' && !this.value && index > 0) {
          codeInputs[index - 1].focus();
        }
      });

      // Soporte para pegar código completo
      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        if (pasted.length === 6) {
          codeInputs.forEach((inp, i) => {
            inp.value = pasted[i] || '';
          });
          codeInputs[5].focus();
          checkCodeComplete();
        }
      });
    });

    function checkCodeComplete() {
      const allFilled = Array.from(codeInputs).every(inp => inp.value.length === 1);
      if (btnVerify) {
        btnVerify.disabled = !allFilled;
      }
    }
  })();
  </script>
</body>
</html>
