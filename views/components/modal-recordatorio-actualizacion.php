<?php
/**
 * Modal recordatorio de actualización de información
 * Se muestra cada 3 meses recordando al egresado actualizar su información laboral
 */
?>

<!-- Modal Recordatorio de Actualización -->
<div class="modal fade" id="modalRecordatorioActualizacion" tabindex="-1" aria-labelledby="modalRecordatorioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <!-- Header con color de alerta -->
      <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
        <div class="d-flex align-items-center gap-3" style="width: 100%;">
          <div class="icon-wrapper" style="font-size: 2.5rem;">
            <i class="bi bi-exclamation-circle-fill text-white"></i>
          </div>
          <div>
            <h5 class="modal-title text-white mb-0" id="modalRecordatorioLabel">
              Actualiza tu Información
            </h5>
            <small class="text-white-50">Te pedimos que revises tus datos laborales</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">
        <div class="alert alert-info border-start border-4 border-info mb-4" role="alert">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Completitud de perfil: <span id="completitudPorcentaje">0</span>%</strong>
          <p class="mb-0 mt-2 small">
            Solo tienes el <span id="completitudMensaje">50%</span> de tu información laboral completada.
          </p>
        </div>

        <!-- Progress bar -->
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <label class="form-label small fw-bold">Progreso de completitud</label>
            <span class="badge bg-primary" id="badgeCampos">0/0</span>
          </div>
          <div class="progress" style="height: 25px;">
            <div class="progress-bar bg-gradient" id="progressBarCompletudinformacion" role="progressbar" 
                 style="width: 0%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%)" 
                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
              <span class="text-white fw-bold small" id="progressText">0%</span>
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
            <ul id="listaCamposFaltantes" class="list-unstyled">
              <!-- Se llenará dinámicamente -->
            </ul>
          </div>

          <div id="msgActualizacionVencida" style="display: none;">
            <h6 class="mb-3">
              <i class="bi bi-calendar-event text-warning me-2"></i>
              Tu información laboral necesita actualización
            </h6>
            <p class="text-muted">
              No hemos visto cambios en tu información laboral en los últimos 3 meses. 
              Ayúdanos a mantener nuestra base de datos actualizada.
            </p>
            <div class="bg-light p-3 rounded">
              <small class="d-block mb-2"><strong>Información que podría cambiar:</strong></small>
              <ul class="list-unstyled small ms-2">
                <li><i class="bi bi-briefcase me-2 text-primary"></i> Empresa donde trabajas</li>
                <li><i class="bi bi-person-check me-2 text-primary"></i> Tu puesto/rol actual</li>
                <li><i class="bi bi-map me-2 text-primary"></i> Ubicación laboral</li>
                <li><i class="bi bi-hammer me-2 text-primary"></i> Tus habilidades técnicas</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="card bg-light border-0 mb-4">
          <div class="card-body">
            <small class="text-muted d-block mb-2">
              <i class="bi bi-lightbulb me-2"></i>
              <strong>¿Por qué es importante?</strong>
            </small>
            <p class="mb-0 small text-muted">
              Mantener tu información actualizada te ayuda a:
            </p>
            <ul class="list-unstyled small text-muted ms-3 mt-2">
              <li><i class="bi bi-check2 me-1 text-success"></i> Recibir ofertas más relevantes</li>
              <li><i class="bi bi-check2 me-1 text-success"></i> Mejorar tu visibilidad en la plataforma</li>
              <li><i class="bi bi-check2 me-1 text-success"></i> Conectar con empresas que buscan tu perfil</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer border-top bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="marcarRecordatorioVisto()">
          <i class="bi bi-x-circle me-2"></i> Recordarme después
        </button>
        <a href="perfil.php" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
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

  // Actualizar datos en el modal
  const porcentaje = estadoRecordatorio.porcentaje_completitud;
  const camposLlenos = estadoRecordatorio.campos_llenos;
  const camposTotales = estadoRecordatorio.campos_totales;
  const razon = estadoRecordatorio.razon;

  // Actualizar porcentaje
  document.getElementById('completitudPorcentaje').textContent = porcentaje;
  document.getElementById('completitudMensaje').textContent = porcentaje + '%';
  
  // Actualizar progress bar
  const progressBar = document.getElementById('progressBarCompletudinformacion');
  progressBar.style.width = porcentaje + '%';
  document.getElementById('progressText').textContent = porcentaje + '%';
  document.getElementById('badgeCampos').textContent = camposLlenos + '/' + camposTotales;

  // Mostrar mensaje según la razón
  document.getElementById('msgCompletudinformacionBaja').style.display = 
    razon.includes('completitud') ? 'block' : 'none';
  document.getElementById('msgActualizacionVencida').style.display = 
    razon.includes('actualizacion') ? 'block' : 'none';

  // Si es por completitud baja, mostrar campos faltantes
  if (razon.includes('completitud') && estadoRecordatorio.campos_faltantes > 0) {
    mostrarCamposFaltantes(estadoRecordatorio.campos_faltantes);
  }

  // Mostrar el modal
  const modal = new bootstrap.Modal(document.getElementById('modalRecordatorioActualizacion'));
  modal.show();
}

/**
 * Mostrar lista de campos faltantes (simulada, ajustar según datos disponibles)
 */
function mostrarCamposFaltantes(camposFaltantes) {
  const campos = [
    { nombre: 'Correo Electrónico Personal', icon: 'bi-envelope' },
    { nombre: 'Teléfono de Contacto', icon: 'bi-telephone' },
    { nombre: 'Empresa Actual', icon: 'bi-briefcase' },
    { nombre: 'Puesto/Rol Actual', icon: 'bi-person-check' },
    { nombre: 'Modalidad de Trabajo', icon: 'bi-file-earmark' },
    { nombre: 'Jornada Laboral', icon: 'bi-clock' },
    { nombre: 'Habilidades Técnicas', icon: 'bi-hammer' },
    { nombre: 'Experiencia en TI', icon: 'bi-graph-up' }
  ];

  const lista = document.getElementById('listaCamposFaltantes');
  lista.innerHTML = '';

  campos.forEach(campo => {
    const li = document.createElement('li');
    li.className = 'p-2 border-bottom d-flex align-items-center';
    li.innerHTML = `
      <i class="bi ${campo.icon} me-2 text-warning"></i>
      <span>${campo.nombre}</span>
    `;
    lista.appendChild(li);
  });
}

/**
 * Marcar el recordatorio como visto
 */
function marcarRecordatorioVisto() {
  fetch('../api/marcar-recordatorio.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ accion: 'marcar_visto' })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Recordatorio marcado como visto');
    }
  })
  .catch(error => console.error('Error:', error));
}
</script>

<style>
#modalRecordatorioActualizacion .modal-header {
  backdrop-filter: blur(10px);
}

#progressBarCompletudinformacion {
  transition: width 0.3s ease;
}

.icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
}

#modalRecordatorioActualizacion .btn-primary {
  transition: transform 0.2s ease;
}

#modalRecordatorioActualizacion .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
</style>
