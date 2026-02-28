/**
 * App.js – Scripts globales del Dashboard
 * Inicialización de componentes Bootstrap y utilidades compartidas.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* --- Bootstrap: tooltips & popovers --- */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
      new bootstrap.Popover(el);
    });

    /* --- Auto-cerrar alertas después de 5 s --- */
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function (alert) {
      setTimeout(function () {
        if (alert.parentNode) {
          var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
          bsAlert.close();
        }
      }, 5000);
    });

    /* --- Protección contra doble envío de forms --- */
    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function () {
        var btn = form.querySelector('[type="submit"]');
        if (btn && !btn.disabled) {
          btn.disabled = true;
          setTimeout(function () { btn.disabled = false; }, 3000);
        }
      });
    });

  });
})();
