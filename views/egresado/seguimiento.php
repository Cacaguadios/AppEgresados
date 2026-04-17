<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}
$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// ─── Load seguimiento data from DB ───
require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$egresadoModel = new Egresado();
$perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
$prestacionesArr = json_decode($perfil['prestaciones'] ?? '[]', true) ?: [];
$habilidadesArr  = json_decode($perfil['habilidades'] ?? '[]', true) ?: [];

// Handle save
$msgExito = '';
$msgError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_seguimiento'])) {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgError = 'Token de seguridad inválido.';
    } else {
        // Collect benefit chips
        $prestacionesPost = json_decode($_POST['prestaciones_json'] ?? '[]', true) ?: [];

        $data = [
            'trabaja_actualmente'  => !empty($_POST['trabaja_actualmente']) ? 1 : 0,
            'trabaja_en_ti'        => !empty($_POST['trabaja_en_ti']) ? 1 : 0,
            'empresa_actual'       => trim($_POST['empresa_actual'] ?? ''),
            'puesto_actual'        => trim($_POST['puesto_actual'] ?? ''),
            'modalidad_trabajo'    => $_POST['modalidad_trabajo'] ?? null,
            'jornada_trabajo'      => $_POST['jornada_trabajo'] ?? null,
            'ubicacion_trabajo'    => trim($_POST['ubicacion_trabajo'] ?? ''),
            'tipo_contrato'        => $_POST['tipo_contrato'] ?? null,
            'fecha_inicio_empleo'  => !empty($_POST['fecha_inicio_empleo']) ? $_POST['fecha_inicio_empleo'] : null,
            'rango_salarial'       => $_POST['rango_salarial'] ?? '',
            'prestaciones'         => json_encode($prestacionesPost),
            'anos_experiencia_ti'  => $_POST['anos_experiencia_ti'] ?? '',
            'descripcion_experiencia' => trim($_POST['descripcion_experiencia'] ?? ''),
        ];
        $egresadoModel->updateSeguimiento($_SESSION['usuario_id'], $data);
        $perfil = $egresadoModel->getByUsuarioId($_SESSION['usuario_id']);
        $prestacionesArr = json_decode($perfil['prestaciones'] ?? '[]', true) ?: [];
        
        // Actualizar próximo recordatorio de información (3 meses)
        $egresadoModel->setProximoRecordatorio($_SESSION['usuario_id']);
        
        // Recalcular completitud
        $egresadoModel->actualizarCompletudinformacion($_SESSION['usuario_id']);
        
        $msgExito = 'Seguimiento guardado correctamente.';
    }
}

$lastUpdate = $perfil['fecha_actualizacion_seguimiento'] ?? null;
$allBenefits = ['IMSS','Vales de despensa','Bonos','Aguinaldo','Vacaciones','Reparto de utilidades','Fondo de ahorro','Seguro gastos médicos'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguimiento - Egresados UTP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">
</head>

<body>
  <script>
    window.UTP_DATA = {
      role: 'egresado', roleLabel: 'Egresado',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'seguimiento',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="utp-layout">
    <div class="container-fluid px-3 px-md-4">
      <div class="row gx-4">
        <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

        <div class="col">
          <div class="utp-content">
            <div class="px-0 py-3 py-md-4" style="min-height: calc(100vh - 65px);">

              <!-- Privacy Notice -->
              <div class="utp-privacy-notice mb-4">
                <div class="d-flex gap-3">
                  <i class="bi bi-file-earmark-lock2 utp-privacy-icon"></i>
                  <div class="flex-grow-1">
                    <h3 class="utp-privacy-title">Información Privada y Confidencial</h3>
                    <p class="utp-privacy-desc mb-2">Estos datos son para seguimiento institucional. Solo tú y el Administrador pueden verlos.</p>
                    <div class="d-flex flex-wrap gap-2">
                      <span class="utp-visibility-tag green">✓ Visible para: Egresado (tú)</span>
                      <span class="utp-visibility-tag green">✓ Visible para: Administrador</span>
                      <span class="utp-visibility-tag red">✗ No visible para: Docente, TI, otros egresados</span>
                    </div>
                  </div>
                </div>
              </div>

              <?php if ($msgExito): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              <?php if ($msgError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($msgError) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <form method="POST" id="seguimientoForm">
              <?= Security::csrfField() ?>
              <input type="hidden" name="guardar_seguimiento" value="1">
              <input type="hidden" name="prestaciones_json" id="prestacionesJson" value="<?= htmlspecialchars(json_encode($prestacionesArr)) ?>">

              <!-- Section A: Situación Laboral -->
              <div class="utp-followup-card mb-4">
                <h2 class="utp-section-title mb-4">A) Situación Laboral</h2>

                <div class="utp-toggle-row mb-3">
                  <div class="utp-toggle-info">
                    <span class="utp-toggle-title">¿Actualmente trabajando?</span>
                    <span class="utp-toggle-desc">Indica si tienes un empleo actualmente</span>
                  </div>
                  <label class="utp-switch">
                    <input type="checkbox" name="trabaja_actualmente" id="toggleWorking" value="1" <?= !empty($perfil['trabaja_actualmente']) ? 'checked' : '' ?>>
                    <span class="utp-switch-slider"></span>
                  </label>
                </div>

                <div class="utp-toggle-row mb-4">
                  <div class="utp-toggle-info">
                    <span class="utp-toggle-title">¿Trabajas en el área de TI?</span>
                    <span class="utp-toggle-desc">Indica si tu trabajo está relacionado con tecnología</span>
                  </div>
                  <label class="utp-switch">
                    <input type="checkbox" name="trabaja_en_ti" id="toggleTI" value="1" <?= !empty($perfil['trabaja_en_ti']) ? 'checked' : '' ?>>
                    <span class="utp-switch-slider"></span>
                  </label>
                </div>

                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Empresa *</label>
                      <input type="text" name="empresa_actual" class="form-control utp-input" placeholder="Tech Solutions SA" value="<?= htmlspecialchars($perfil['empresa_actual'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Puesto *</label>
                      <input type="text" name="puesto_actual" class="form-control utp-input" placeholder="Desarrollador Frontend" value="<?= htmlspecialchars($perfil['puesto_actual'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Modalidad *</label>
                      <select name="modalidad_trabajo" class="form-select utp-select">
                        <option value="">Selecciona...</option>
                        <?php foreach (['presencial'=>'Presencial','hibrido'=>'Híbrido','remoto'=>'Remoto'] as $v => $l): ?>
                          <option value="<?= $v ?>" <?= ($perfil['modalidad_trabajo'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Jornada *</label>
                      <select name="jornada_trabajo" class="form-select utp-select">
                        <option value="">Selecciona...</option>
                        <?php foreach (['completo'=>'Tiempo completo','parcial'=>'Medio tiempo','freelance'=>'Freelance'] as $v => $l): ?>
                          <option value="<?= $v ?>" <?= ($perfil['jornada_trabajo'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="utp-form-group">
                      <label class="utp-label">Ubicación (Ciudad/Estado)</label>
                      <input type="text" name="ubicacion_trabajo" class="form-control utp-input" placeholder="Puebla, Puebla" value="<?= htmlspecialchars($perfil['ubicacion_trabajo'] ?? '') ?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section B: Contrato -->
              <div class="utp-followup-card mb-4">
                <h2 class="utp-section-title mb-4">B) Contrato</h2>
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Tipo de contrato *</label>
                      <select name="tipo_contrato" class="form-select utp-select">
                        <option value="">Selecciona...</option>
                        <?php foreach (['indefinido'=>'Indefinido','temporal'=>'Temporal','proyecto'=>'Por proyecto','honorarios'=>'Honorarios'] as $v => $l): ?>
                          <option value="<?= $v ?>" <?= ($perfil['tipo_contrato'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Fecha de inicio</label>
                      <input type="date" name="fecha_inicio_empleo" class="form-control utp-input" value="<?= htmlspecialchars($perfil['fecha_inicio_empleo'] ?? '') ?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section C: Ingresos -->
              <div class="utp-followup-card mb-4">
                <h2 class="utp-section-title mb-4">C) Ingresos</h2>

                <div class="utp-form-group mb-4">
                  <label class="utp-label">Rango salarial mensual</label>
                  <select name="rango_salarial" class="form-select utp-select">
                    <option value="">Selecciona un rango</option>
                    <?php foreach (['0-8000'=>'$0 - $8,000 MXN','8001-12000'=>'$8,001 - $12,000 MXN','12001-18000'=>'$12,001 - $18,000 MXN','18001-25000'=>'$18,001 - $25,000 MXN','25001-35000'=>'$25,001 - $35,000 MXN','35001+'=>'Más de $35,000 MXN'] as $v => $l): ?>
                      <option value="<?= $v ?>" <?= ($perfil['rango_salarial'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                  </select>
                  <span class="utp-field-hint">Esta información es confidencial y solo se usa para estadísticas internas</span>
                </div>

                <div class="utp-form-group">
                  <label class="utp-label mb-1">Prestaciones</label>
                  <p class="utp-field-hint mb-3">Selecciona las prestaciones que recibes</p>
                  <div class="row g-2">
                    <?php foreach ($allBenefits as $benefit): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                      <button type="button" class="utp-benefit-chip w-100 <?= in_array($benefit, $prestacionesArr) ? 'active' : '' ?>" data-benefit="<?= htmlspecialchars($benefit) ?>"><?= htmlspecialchars($benefit) ?></button>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <!-- Section D: Experiencia Laboral -->
              <div class="utp-followup-card mb-4">
                <h2 class="utp-section-title mb-4">D) Experiencia Laboral</h2>

                <div class="row g-3 mb-4">
                  <div class="col-12 col-md-6">
                    <div class="utp-form-group">
                      <label class="utp-label">Años de experiencia en TI</label>
                      <select name="anos_experiencia_ti" class="form-select utp-select">
                        <?php foreach (['0'=>'Sin experiencia','0-1'=>'Menos de 1 año','1-2'=>'1-2 años','2-3'=>'2-3 años','3-5'=>'3-5 años','5+'=>'Más de 5 años'] as $v => $l): ?>
                          <option value="<?= $v ?>" <?= ($perfil['anos_experiencia_ti'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="utp-form-group mb-4">
                  <label class="utp-label">Descripción de experiencia laboral</label>
                  <textarea name="descripcion_experiencia" class="form-control utp-textarea" rows="4" placeholder="Describe brevemente tu trayectoria profesional..."><?= htmlspecialchars($perfil['descripcion_experiencia'] ?? '') ?></textarea>
                </div>

                <div class="utp-form-group">
                  <label class="utp-label mb-2">Tecnologías principales que dominas</label>
                  <div class="utp-tech-display">
                    <?php if (!empty($habilidadesArr)): ?>
                      <?php foreach ($habilidadesArr as $skill): ?>
                        <span class="utp-tech-chip"><?= htmlspecialchars($skill) ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="utp-tech-chip">—</span>
                    <?php endif; ?>
                  </div>
                  <span class="utp-field-hint mt-2 d-block">Puedes editar tus habilidades en la sección CV / Habilidades</span>
                </div>
              </div>

              <!-- Section E: Campos Adicionales -->
              <div class="utp-followup-card mb-4">
                <div class="d-flex align-items-start gap-3 mb-4">
                  <i class="bi bi-info-circle text-primary fs-5"></i>
                  <div>
                    <h2 class="utp-section-title mb-1">E) Campos Adicionales</h2>
                    <p class="utp-field-hint mb-0">Este apartado se actualizará cuando dirección confirme los campos oficiales.</p>
                  </div>
                </div>

                <div class="utp-disabled-section">
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <div class="utp-form-group">
                        <label class="utp-label">Campo Adicional 1 (Por definir)</label>
                        <input type="text" class="utp-input-disabled" value="Pendiente de especificación" disabled>
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="utp-form-group">
                        <label class="utp-label">Campo Adicional 2 (Por definir)</label>
                        <input type="text" class="utp-input-disabled" value="Pendiente de especificación" disabled>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Save Button & Info -->
              <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <button type="submit" class="btn utp-btn-green">
                  <i class="bi bi-floppy me-2"></i>
                  Guardar seguimiento
                </button>
                <div class="utp-last-update">
                  <i class="bi bi-clock-history"></i>
                  <span><?= $lastUpdate ? 'Última actualización: ' . date('d/m/Y H:i', strtotime($lastUpdate)) : 'Sin datos guardados aún' ?></span>
                </div>
              </div>
              </form>

              <!-- Info Box -->
              <div class="utp-app-info-box">
                <span>Puedes actualizar esta información en cualquier momento. Tus datos ayudarán a la universidad a mejorar los programas académicos y de vinculación laboral.</span>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/shared/app.js"></script>
  <script>
    // Benefit chips ↔ hidden JSON sync
    const benefitChips = document.querySelectorAll('.utp-benefit-chip');
    const prestacionesInput = document.getElementById('prestacionesJson');

    function syncBenefits() {
      const selected = [];
      benefitChips.forEach(c => { if (c.classList.contains('active')) selected.push(c.dataset.benefit); });
      prestacionesInput.value = JSON.stringify(selected);
    }

    benefitChips.forEach(chip => {
      chip.addEventListener('click', function() {
        this.classList.toggle('active');
        syncBenefits();
      });
    });

    // Sync on form submit as safety net
    document.getElementById('seguimientoForm').addEventListener('submit', syncBenefits);
  </script>
</body>
</html>
