/**
 * Notification Badge Handler
 * Actualiza el contador de notificaciones no leídas en la campanita del topbar.
 * Se incluye automáticamente en todas las páginas del dashboard.
 */
(function () {
  'use strict';

  function getAppBase() {
    var current = document.currentScript;
    if (current && current.src) {
      try {
        var scriptPath = new URL(current.src, window.location.origin).pathname;
        var marker = '/public/assets/';
        var markerIdx = scriptPath.indexOf(marker);
        if (markerIdx !== -1) {
          return scriptPath.substring(0, markerIdx);
        }
      } catch (e) {
        // continuar con deteccion por pathname
      }
    }

    var path = window.location.pathname;
    var markers = ['/views/', '/public/'];

    for (var i = 0; i < markers.length; i++) {
      var idx = path.indexOf(markers[i]);
      if (idx !== -1) {
        return path.substring(0, idx);
      }
    }

    var parts = path.split('/').filter(Boolean);
    if (parts.length > 0) {
      return '/' + parts[0];
    }

    return '';
  }

  var API = getAppBase() + '/public/api/notificaciones.php';
  var POLL_INTERVAL = 30000; // 30 segundos

  function updateBadge() {
    fetch(API + '?action=count')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var badge = document.getElementById('notifCount');
        if (!badge) return;

        var count = data.count || 0;
        if (count > 0) {
          badge.textContent = count > 99 ? '99+' : count;
          badge.classList.remove('d-none');
        } else {
          badge.classList.add('d-none');
        }
      })
      .catch(function () { /* silencioso */ });
  }

  function init() {
    // Esperar a que el topbar se cargue
    var check = setInterval(function () {
      var btn = document.getElementById('notifDropdownBtn');
      if (btn) {
        clearInterval(check);
        updateBadge();
        setInterval(updateBadge, POLL_INTERVAL);
      }
    }, 300);

    setTimeout(function () { clearInterval(check); }, 10000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
