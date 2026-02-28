/**
 * Components Loader
 * Carga dinámica de componentes compartidos (topbar, sidebar, notice)
 * Los componentes se inyectan en contenedores con IDs específicos.
 */
(function () {
  'use strict';

  // Datos inyectados por PHP en la página
  const DATA = window.UTP_DATA || {};

  /* ---------- Helpers de rutas ---------- */

  /** Raíz de la app, ej: /AppEgresados */
  function getAppBase() {
    const path = window.location.pathname;
    const idx = path.indexOf('/AppEgresados');
    return idx !== -1 ? path.substring(0, idx) + '/AppEgresados' : '/AppEgresados';
  }

  /** Ruta a la carpeta compartido/ (componentes HTML) */
  function getComponentsBase() {
    return getAppBase() + '/views/compartido/';
  }

  /** Ruta a public/assets/ */
  function getAssetBase() {
    return getAppBase() + '/public/assets/';
  }

  /** Ruta a la carpeta de vistas del rol activo */
  function getViewBase() {
    const role = DATA.role || 'egresado';
    return getAppBase() + '/views/' + role + '/';
  }

  /* ---------- Cargador de componentes ---------- */

  async function loadComponent(containerId, componentFile) {
    const container = document.getElementById(containerId);
    if (!container) return;

    try {
      const url = getComponentsBase() + componentFile;
      const resp = await fetch(url);
      if (!resp.ok) throw new Error('HTTP ' + resp.status + ' al cargar ' + componentFile);

      let html = await resp.text();

      // Reemplazar placeholders
      html = html.replace(/\{BASE\}/g, getViewBase());
      html = html.replace(/\{ASSETS\}/g, getAssetBase());
      html = html.replace(/\{APP\}/g, getAppBase());

      container.innerHTML = html;

      // Llenar datos dinámicos
      fillDynamicData(container);
    } catch (err) {
      console.warn('[ComponentsLoader]', componentFile, err);
    }
  }

  /* ---------- Inyección de datos dinámicos ---------- */

  function fillDynamicData(container) {
    // Iniciales del usuario
    const initialsEl = container.querySelector('#userInitials');
    if (initialsEl && DATA.initials) {
      initialsEl.textContent = DATA.initials;
    }

    // Nombre completo
    const nameEl = container.querySelector('#userName');
    if (nameEl && DATA.fullName) {
      nameEl.textContent = DATA.fullName;
    }

    // Rol
    const roleEl = container.querySelector('#userRole');
    if (roleEl && DATA.roleLabel) {
      roleEl.textContent = DATA.roleLabel;
    }

    // Imágenes con data-asset → src absoluta
    container.querySelectorAll('[data-asset]').forEach(function (img) {
      img.src = getAssetBase() + img.getAttribute('data-asset');
    });

    // Links con data-link
    container.querySelectorAll('[data-link]').forEach(function (el) {
      const link = el.getAttribute('data-link');
      const viewBase = getViewBase();
      const appBase = getAppBase();

      switch (link) {
        case 'profile':
          if (el.tagName === 'A') el.href = viewBase + 'perfil.php';
          break;
        case 'security':
          if (el.tagName === 'A') el.href = viewBase + 'seguridad.php';
          break;
        case 'notifications':
          if (el.tagName === 'A') el.href = appBase + '/views/notificaciones/index.php';
          break;
        case 'logout':
          if (el.tagName === 'A') {
            el.href = appBase + '/views/auth/logout.php';
          } else {
            el.addEventListener('click', function () {
              window.location.href = appBase + '/views/auth/logout.php';
            });
          }
          break;
      }
    });

    // Sidebar: marcar la página activa
    const currentPage = DATA.currentPage || '';
    container.querySelectorAll('[data-page]').forEach(function (item) {
      if (item.getAttribute('data-page') === currentPage) {
        item.classList.add('active');
      }
    });
  }

  /* ---------- Inicializar notice ---------- */

  function initNotice() {
    var dismissBtn = document.getElementById('btnDismissNotice');
    var notice = document.getElementById('passwordNotice');

    if (dismissBtn && notice) {
      dismissBtn.addEventListener('click', function () {
        notice.style.transition = 'opacity .25s ease';
        notice.style.opacity = '0';
        setTimeout(function () { notice.style.display = 'none'; }, 260);
      });
    }
  }

  /* ---------- Bootstrap re-init dentro de componentes ---------- */

  function reinitBootstrap(container) {
    // Dropdowns
    container.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (el) {
      new bootstrap.Dropdown(el);
    });
    // Collapse (sidebar móvil)
    container.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (el) {
      new bootstrap.Collapse(el, { toggle: false });
    });
    // Tooltips
    container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }

  /* ---------- Arranque ---------- */

  async function init() {
    var loads = [];

    // Notice (solo si requiere cambio de contraseña)
    if (DATA.requirePasswordChange) {
      loads.push(loadComponent('utp-notice-container', 'notice-password.html'));
    }

    // Topbar
    loads.push(loadComponent('utp-topbar-container', 'topbar.html'));

    // Sidebar según rol
    var sidebarFile = 'sidebar-' + (DATA.role || 'egresado') + '.html';
    loads.push(loadComponent('utp-sidebar-container', sidebarFile));

    await Promise.all(loads);

    // Re-inicializar componentes de Bootstrap dentro de los contenedores inyectados
    document.querySelectorAll('#utp-topbar-container, #utp-sidebar-container').forEach(reinitBootstrap);

    // Marcar body como listo (para transiciones CSS)
    document.body.classList.add('utp-ready');

    // Inicializar dismiss del notice
    initNotice();

    // Cargar script de notificaciones dinámicamente
    var notifScript = document.createElement('script');
    notifScript.src = getAssetBase() + 'js/shared/notifications.js';
    document.body.appendChild(notifScript);
  }

  // Ejecutar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
