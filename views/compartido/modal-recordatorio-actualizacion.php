<?php
/**
 * Modal recordatorio de actualización de información
 * Se muestra cada 3 meses recordando al egresado/usuario actualizar su información laboral
 */
require_once __DIR__ . '/../../app/helpers/Security.php';
?>
<script>window.UTP_CSRF_TOKEN = window.UTP_CSRF_TOKEN || <?= json_encode(Security::generateCsrfToken()) ?>;</script>

<!-- Modal Recordatorio de Actualización -->
<div class="modal fade" id="modalRecordatorioActualizacion" tabindex="-1" aria-labelledby="modalRecordatorioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content utp-modal utp-reminder-modal-content">
      <div class="modal-header utp-reminder-modal-header border-0">
        <div class="d-flex align-items-center gap-3 w-100">
          <div class="utp-miniicon utp-empty-icon cyan utp-shrink-0">
            <i class="bi bi-info-circle"></i>
          </div>
          <div>
            <h5 class="modal-title utp-reminder-modal-title mb-0" id="modalRecordatorioLabel">
              Actualiza tu Información
            </h5>
            <small class="utp-reminder-modal-subtitle">Te pedimos que revises tus datos</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">
        <div class="utp-reminder-banner mb-4" role="alert">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Completitud de perfil: <span id="completitudPorcentaje">0</span>%</strong>
          <p class="mb-0 mt-2">
            Solo tienes el <span id="completitudMensaje">50%</span> de tu información completada.
          </p>
        </div>

        <!-- Progress bar -->
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Progreso de completitud</label>
            <span class="badge text-bg-primary" id="badgeCampos">0/0</span>
          </div>
          <div class="progress utp-reminder-progress">
            <div class="progress-bar" id="progressBarCompletudinformacion" role="progressbar"
                 style="width: 0%;"
                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
              <span class="fw-semibold small" id="progressText">0%</span>
            </div>
          </div>
        </div>

        <!-- Mensaje según la razón del recordatorio -->
        <div class="message-container mb-4">
          <div id="msgCompletudinformacionBaja" style="display: none;">
            <h6 class="mb-3">
              <i class="bi bi-clipboard-check text-info me-2"></i>
              Campos que faltan completar:
            </h6>
            <ul id="listaCamposFaltantes" class="list-unstyled mb-0 utp-reminder-missing-list">
              <!-- Se llenará dinámicamente -->
            </ul>
          </div>

          <div id="msgActualizacionVencida" style="display: none;">
            <h6 class="mb-3">
              <i class="bi bi-calendar-event text-warning me-2"></i>
              Tu información laboral necesita actualización
            </h6>
            <p class="text-muted">
              No hemos visto cambios en tu información en los últimos 3 meses. 
              Ayúdanos a mantener nuestra base de datos actualizada.
            </p>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="card bg-light border-0 mb-0 rounded-4">
          <div class="card-body">
            <small class="text-muted d-block mb-2">
              <i class="bi bi-lightbulb me-2"></i>
              <strong>¿Por qué es importante?</strong>
            </small>
            <p class="mb-0 small text-muted">
              Mantener tu información actualizada te ayuda a mejorar tu visibilidad en la plataforma.
            </p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer border-top bg-light rounded-bottom-4">
        <button type="button" class="btn btn-utp-outline-gray" data-bs-dismiss="modal" onclick="marcarRecordatorioVisto()">
          <i class="bi bi-x-circle me-2"></i> Recordarme después
        </button>
        <a href="perfil.php" class="btn btn-utp-red">
          <i class="bi bi-pencil-square me-2"></i> Actualizar información
        </a>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * Inicializar y mostrar el modal de recordatorio
 */
function inicializarRecordatorio(estadoRecordatorio) {
  if (!estadoRecordatorio.debe_mostrar) {
    return;
  }

  const porcentaje = estadoRecordatorio.porcentaje_completitud;
  const camposLlenos = estadoRecordatorio.campos_llenos;
  const camposTotales = estadoRecordatorio.campos_totales;
  const razon = estadoRecordatorio.razon;

  document.getElementById('completitudPorcentaje').textContent = porcentaje;
  document.getElementById('completitudMensaje').textContent = porcentaje + '%';
  
  const progressBar = document.getElementById('progressBarCompletudinformacion');
  progressBar.style.width = porcentaje + '%';
  document.getElementById('progressText').textContent = porcentaje + '%';
  document.getElementById('badgeCampos').textContent = camposLlenos + '/' + camposTotales;

  document.getElementById('msgCompletudinformacionBaja').style.display = 
    razon.includes('completitud') ? 'block' : 'none';
  document.getElementById('msgActualizacionVencida').style.display = 
    razon.includes('actualizacion') ? 'block' : 'none';

  if (razon.includes('completitud') && estadoRecordatorio.campos_faltantes > 0) {
    mostrarCamposFaltantes(estadoRecordatorio.campos_faltantes_detalle || []);
  }

  const modal = new bootstrap.Modal(document.getElementById('modalRecordatorioActualizacion'));
  modal.show();
}

function mostrarCamposFaltantes(camposFaltantes) {
  const iconosPorCampo = {
    'Empresa actual': 'bi-briefcase',
    'Puesto actual': 'bi-person-badge',
    'Modalidad de trabajo': 'bi-laptop',
    'Jornada de trabajo': 'bi-clock',
    'Tipo de contrato': 'bi-file-earmark-text',
    'Habilidades tecnicas': 'bi-code-slash',
    'Experiencia en TI': 'bi-graph-up-arrow',
    'Especialidad': 'bi-mortarboard'
  };

  const lista = document.getElementById('listaCamposFaltantes');
  lista.innerHTML = '';

  camposFaltantes.forEach(nombreCampo => {
    const li = document.createElement('li');
    li.className = 'd-flex align-items-center gap-2 py-2 border-bottom';
    const icono = iconosPorCampo[nombreCampo] || 'bi-dot';
    li.innerHTML = `
      <i class="bi ${icono} text-warning"></i>
      <span>${nombreCampo}</span>
    `;
    lista.appendChild(li);
  });
}

/**
 * Marcar el recordatorio como visto
 */
function marcarRecordatorioVisto() {
  fetch('../../public/api/marcar-recordatorio.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': window.UTP_CSRF_TOKEN || ''
    },
    body: JSON.stringify({ accion: 'marcar_visto', csrf_token: window.UTP_CSRF_TOKEN || '' })
  })
  .then(response => response.json())
  .then(data => console.log('Recordatorio actualizado'))
  .catch(error => console.error('Error:', error));
}
</script>

<style>
#modalRecordatorioActualizacion .utp-reminder-modal-content {
  border-radius: 20px;
}

#modalRecordatorioActualizacion .utp-reminder-modal-header {
  background: #F9FAFB;
  padding: 20px 24px;
}

#modalRecordatorioActualizacion .utp-reminder-modal-title {
  color: #121212;
  font-size: 22px;
  font-weight: 700;
}

#modalRecordatorioActualizacion .utp-reminder-modal-subtitle {
  color: #757575;
}

#modalRecordatorioActualizacion .utp-reminder-banner {
  background: #DFF4FF;
  border: 1px solid #93D8FF;
  color: #0B4560;
  border-radius: 14px;
  padding: 16px 18px;
}

#modalRecordatorioActualizacion .utp-reminder-progress {
  height: 22px;
  background: #E9ECEF;
}

#modalRecordatorioActualizacion #progressBarCompletudinformacion {
  background: #0D6EFD;
  color: #0B1F33;
  min-width: 64px;
  overflow: visible;
  transition: width 0.3s ease;
}

#modalRecordatorioActualizacion #progressText {
  color: #0B1F33;
  text-shadow: none;
}

#modalRecordatorioActualizacion .modal-body,
#modalRecordatorioActualizacion .modal-body p,
#modalRecordatorioActualizacion .modal-body h6,
#modalRecordatorioActualizacion .modal-body span,
#modalRecordatorioActualizacion .modal-body small {
  color: #1F2937;
}

#modalRecordatorioActualizacion .utp-reminder-missing-list li:last-child {
  border-bottom: none !important;
}
</style>
