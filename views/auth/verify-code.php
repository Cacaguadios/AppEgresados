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

// Verificar que venga del paso anterior (email debe estar en sesión)
if (empty($_SESSION['reset_email'])) {
    header('Location: ' . $baseUrl . '/forgot');
    exit;
}

require_once __DIR__ . '/../../app/controllers/VerificationController.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$verificationCtrl = new VerificationController();
$email = $_SESSION['reset_email'];
$errorMsg = null;
$successMsg = null;

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'resend') {
        // Reenviar código
        $result = $verificationCtrl->sendPasswordResetCode($email);
      if ($result['success']) {
        $successMsg = 'Nuevo código enviado a ' . $email;
      } else {
        $errorMsg = $result['message'] ?? 'No fue posible reenviar el código.';
      }
    } else {
        // Verificar código
        $code = '';
        for ($i = 1; $i <= 6; $i++) {
            $code .= $_POST["code{$i}"] ?? '';
        }

        $result = $verificationCtrl->verifyCode($email, $code, 'recuperacion');

        if ($result['success']) {
            // Marcar que el código fue verificado
            $_SESSION['reset_code_verified'] = true;
            header('Location: ' . $baseUrl . '/reset-password');
            exit;
        } else {
            $errorMsg = $result['message'];
        }
    }
}

// Ofuscar email para mostrar
$emailParts = explode('@', $email);
$localPart = $emailParts[0];
$domainPart = $emailParts[1] ?? '';
$masked = substr($localPart, 0, 2) . str_repeat('•', max(0, strlen($localPart) - 4)) . substr($localPart, -2);
$maskedEmail = $masked . '@' . $domainPart;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificar código | Egresados UTP</title>

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
  <main class="auth-forgot-shell">
    <div class="container py-4 py-md-5">

      <!-- Back -->
      <a class="auth-back d-inline-flex align-items-center gap-2 mb-3" href="/AppEgresados/forgot">
        <i class="bi bi-chevron-left"></i>
        <span>Volver</span>
      </a>

      <section class="auth-forgot-card mx-auto">
        <!-- Header -->
        <header class="text-center mb-4">
          <div class="auth-forgot-icon mx-auto mb-3">
            <i class="bi bi-envelope-check"></i>
          </div>

          <h1 class="auth-wizard-title mb-2">Verificar código</h1>
          <p class="auth-wizard-subtitle mb-0">
            Ingresa el código de 6 dígitos enviado a<br>
            <strong><?php echo htmlspecialchars($maskedEmail); ?></strong>
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

        <!-- Success Alert -->
        <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i>
          <?php echo htmlspecialchars($successMsg); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="needs-validation" autocomplete="off" id="verifyForm">
          <input type="hidden" name="action" value="verify">

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

          <!-- Reenviar -->
          <div class="text-center mb-4">
            <span class="text-muted" style="font-size:14px;">¿No recibiste el código?</span>
            <button type="button" class="btn btn-link link-utp p-0 ms-1" style="font-size:14px;" id="btnResend">
              Reenviar código
            </button>
          </div>

          <button class="btn btn-utp-red w-100 auth-cta" type="submit" id="btnVerify" disabled>
            <i class="bi bi-check-circle me-2"></i>Verificar código
          </button>
        </form>

        <!-- Form oculto para reenviar -->
        <form method="POST" id="resendForm" class="d-none">
          <input type="hidden" name="action" value="resend">
        </form>
      </section>
    </div>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/app.js"></script>

  <script>
  (function() {
    const codeInputs = document.querySelectorAll('.auth-code-input');
    const btnVerify = document.getElementById('btnVerify');

    codeInputs.forEach((input, index) => {
      input.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').slice(0, 1);
        if (this.value && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }
        checkCodeComplete();
      });

      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && index > 0) {
          codeInputs[index - 1].focus();
        }
      });

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
      btnVerify.disabled = !allFilled;
    }

    // Reenviar código
    document.getElementById('btnResend').addEventListener('click', function() {
      document.getElementById('resendForm').submit();
    });
  })();
  </script>
</body>
</html>
