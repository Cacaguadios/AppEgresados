/**
 * Inicio – Dashboard del Egresado
 * Scripts específicos para la página de inicio.
 */
(function () {
  'use strict';

  var DATA = window.UTP_DATA || {};

  document.addEventListener('DOMContentLoaded', function () {

    /* --- Modal de recordatorio de seguridad --- */
    if (DATA.requirePasswordChange) {
      var modalEl = document.getElementById('securityReminderModal');
      if (modalEl) {
        var modal = new bootstrap.Modal(modalEl);
        // Mostrar con un pequeño delay para no interrumpir la carga visual
        setTimeout(function () { modal.show(); }, 1200);
      }
    }

  });
})();
