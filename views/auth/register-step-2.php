<?php
session_start();

// Si ya está autenticado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $r = match($_SESSION['usuario_rol'] ?? '') { 'admin' => '../admin/inicio.php', 'docente','ti' => '../docente/inicio.php', default => '../egresado/inicio.php' };
    header('Location: ' . $r);
    exit;
}

require_once __DIR__ . '/../../app/controllers/RegisterController.php';

$registroController = new RegisterController();
$errorValidacion = null;
$datosVerificacion = null;

// Procesar POST para validar datos de verificación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener rol del formulario o sesión
    $role = $_POST['role'] ?? $_SESSION['registro_rol'] ?? 'egresado';
    
    // Preparar datos según el rol
    if ($role === 'egresado') {
      $datosValidar = [
        'curp' => $_POST['curp'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'email' => $_POST['email'] ?? ''
      ];
    } elseif ($role === 'docente') {
        $datosValidar = [
            'email_docente' => $_POST['email_docente'] ?? ''
        ];
    } else { // ti
        $datosValidar = [
            'id_ti' => $_POST['id_ti'] ?? ''
        ];
    }
    
    // Validar en backend usando el controlador
    $resultado = $registroController->validateVerification($role, $datosValidar);
    
    if ($resultado['success']) {
        // Guardar datos verificados en sesión
        $_SESSION['registro_rol'] = $role;
        $_SESSION['registro_verificacion'] = $resultado['data'];
        
        // Redirigir a Step 3
        header('Location: register-step-3.php');
        exit;
    } else {
        // Mostrar error de validación
        $errorValidacion = $resultado['message'];
    }
}

// Obtener rol de sesión o usar por defecto
$roleActual = $_SESSION['registro_rol'] ?? 'egresado';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - Verificación | Egresados UTP</title>

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

          <!-- Stepper: paso 2/4 activo -->
          <div class="auth-stepper" aria-label="Progreso de registro">
            <span class="auth-step active"></span>
            <span class="auth-step active"></span>
            <span class="auth-step"></span>
            <span class="auth-step"></span>
          </div>
        </header>

        <!-- Body -->
        <div class="auth-wizard-body">
          <div class="mb-4">
            <h2 class="auth-section-title mb-1">Verificación de autenticidad</h2>
            <p class="auth-section-subtitle mb-0" id="verificationSubtitle">Valida tu identidad</p>
          </div>

          <!-- Error Alert -->
          <?php if ($errorValidacion): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($errorValidacion); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <form id="verificationForm" method="POST" class="needs-validation" autocomplete="off">
            <!-- Campo oculto para el rol -->
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleActual); ?>">

            <!-- Campos Egresado -->
            <div id="fieldsEgresado" class="d-block">
              <div class="mb-3">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  CURP <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input"
                       type="text"
                       name="curp"
                       placeholder="18 caracteres"
                       maxlength="18"
                       value="<?php echo htmlspecialchars($_POST['curp'] ?? ''); ?>"
                       required />
                <div class="auth-help mt-2">Usado para validar autenticidad</div>
              </div>

              <div class="mb-3">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  Teléfono de contacto <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input"
                       type="tel"
                       name="telefono"
                       placeholder="10 dígitos"
                       inputmode="numeric"
                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                       required />
              </div>

              <div class="mb-3">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  Correo Electrónico Personal <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input"
                       type="email"
                       name="email"
                       placeholder="tu.correo@ejemplo.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       data-field="egresado"
                       required />
                <div class="auth-help mt-2">Usaremos este correo para verificación</div>
              </div>
            </div>

            <!-- Campos Docente -->
            <div id="fieldsDocente" class="d-none">
              <div class="mb-3">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  Correo Institucional Docente <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input"
                       type="email"
                       name="email_docente"
                    placeholder="nombre@utpuebla.edu.mx"
                       value="<?php echo htmlspecialchars($_POST['email_docente'] ?? ''); ?>"
                       data-field="docente"
                       required />
                  <div class="auth-help mt-2">Debe terminar en @utpuebla.edu.mx o @utp.edu.mx</div>
              </div>
            </div>

            <!-- Campos TI -->
            <div id="fieldsTI" class="d-none">
              <div class="mb-3">
                <label class="form-label" style="font-size:14px; font-weight:500;">
                  ID de TI <span class="text-danger">*</span>
                </label>
                <input class="form-control auth-input"
                       type="text"
                       name="id_ti"
                       placeholder="Ingresa tu ID de empleado TI"
                       data-field="ti"
                       required />
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2 auth-actions mt-4">
              <button class="btn btn-light auth-btn-back" type="button" id="btnBack">
                <i class="bi bi-chevron-left me-1"></i> Atrás
              </button>

              <button class="btn btn-utp-red auth-btn-continue" type="submit" id="btnContinue">
                Continuar <i class="bi bi-chevron-right ms-1"></i>
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
    // Priorizar sessionStorage (viene de Step 1) sobre el valor PHP por defecto
    const roleField = document.querySelector('input[name="role"]');
    const storedRol = sessionStorage.getItem('registroRol');
    const registroRol = storedRol || roleField.value || 'egresado';
    
    // Actualizar el campo oculto con el rol real
    roleField.value = registroRol;

    // Subtítulos según rol
    const subtitles = {
      egresado: 'Valida tu identidad con datos personales',
      docente: 'Valida tu identidad con correo institucional',
      ti: 'El ID de empleado valida tu identidad'
    };

    // Mostrar campos según rol y actualizar validación HTML5
    function mostrarCamposRol(rol) {
      // Ocultar todos
      document.getElementById('fieldsEgresado').classList.add('d-none');
      document.getElementById('fieldsDocente').classList.add('d-none');
      document.getElementById('fieldsTI').classList.add('d-none');

      // Remover required de todos los campos
      document.querySelectorAll('input[name="curp"], input[name="telefono"], input[name="email"], input[name="email_docente"], input[name="id_ti"]').forEach(input => {
        input.removeAttribute('required');
      });

      // Actualizar subtítulo
      document.getElementById('verificationSubtitle').textContent = subtitles[rol] || subtitles.egresado;

      // Mostrar y validar solo los del rol actual
      if (rol === 'egresado') {
        document.getElementById('fieldsEgresado').classList.remove('d-none');
        document.querySelector('input[name="curp"]').setAttribute('required', 'required');
        document.querySelector('input[name="telefono"]').setAttribute('required', 'required');
        document.querySelector('input[name="email"]').setAttribute('required', 'required');
      } else if (rol === 'docente') {
        document.getElementById('fieldsDocente').classList.remove('d-none');
        document.querySelector('input[name="email_docente"]').setAttribute('required', 'required');
      } else if (rol === 'ti') {
        document.getElementById('fieldsTI').classList.remove('d-none');
        document.querySelector('input[name="id_ti"]').setAttribute('required', 'required');
      }
    }

    // Inicializar
    mostrarCamposRol(registroRol);

    // Botón atrás
    document.getElementById('btnBack').addEventListener('click', function() {
      window.history.back();
    });
  </script>
  
</body>
</html>
