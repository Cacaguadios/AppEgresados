/**
 * Components Loader
 * Carga dinámica de componentes compartidos (topbar, sidebar, notice)
 * Los componentes se inyectan en contenedores con IDs específicos.
 */
(function () {
  "use strict";

  // Datos inyectados por PHP en la página
  const DATA = window.UTP_DATA || {};
  const LOADER_SCRIPT_URL =
    document.currentScript && document.currentScript.src
      ? document.currentScript.src
      : "";
  const THEME_STORAGE_KEY = "utp-theme-mode";
  let darkReaderPromise = null;

  /* ---------- Helpers de rutas ---------- */

  /** Raiz de la app, por ejemplo: /AppEgresados o '' en dominio raiz */
  function getAppBase() {
    if (typeof DATA.appBase === "string") {
      return DATA.appBase;
    }

    // Derivar desde la URL del script cargado
    if (LOADER_SCRIPT_URL) {
      try {
        var scriptPath = new URL(
          LOADER_SCRIPT_URL,
          window.location.origin
        ).pathname;
        var marker = "/assets/";
        var markerIdx = scriptPath.indexOf(marker);
        if (markerIdx !== -1) {
          return scriptPath.substring(0, markerIdx);
        }
      } catch (e) {
        // continuar con otros metodos
      }
    }

    const path = window.location.pathname;
    const markers = ["/views/", "/public/"];

    for (const marker of markers) {
      const idx = path.indexOf(marker);
      if (idx !== -1) {
        return path.substring(0, idx);
      }
    }

    // Fallback para rutas limpias con un posible prefijo de instalacion.
    var parts = path.split("/").filter(Boolean);
    if (parts.length > 0) {
      return "/" + parts[0];
    }

    return "";
  }

  /** Ruta a los componentes HTML publicos. */
  function getComponentsBase() {
    return getAssetBase() + "components/";
  }

  /** Ruta publica a los assets. */
  function getAssetBase() {
    return getAppBase() + "/assets/";
  }

  /** Ruta a la carpeta de vistas del rol activo */
  function getViewBase() {
    const role = DATA.role || "egresado";
    return getAppBase() + "/" + role + "/";
  }

  /* ---------- Cargador de componentes ---------- */

  async function loadComponent(containerId, componentFile) {
    const container = document.getElementById(containerId);
    if (!container) return;

    try {
      const url = getComponentsBase() + componentFile;
      const resp = await fetch(url);
      if (!resp.ok)
        throw new Error("HTTP " + resp.status + " al cargar " + componentFile);

      let html = await resp.text();

      // Reemplazar placeholders
      html = html.replace(/\{BASE\}([a-z0-9-]+)\.php/gi, function (_, page) {
        return getViewBase() + page;
      });
      html = html.replace(/\{BASE\}/g, getViewBase());
      html = html.replace(/\{ASSETS\}/g, getAssetBase());
      html = html.replace(/\{APP\}/g, getAppBase());

      container.innerHTML = html;

      // Llenar datos dinámicos
      fillDynamicData(container);
    } catch (err) {
      console.warn("[ComponentsLoader]", componentFile, err);
    }
  }

  /* ---------- Inyección de datos dinámicos ---------- */

  function fillDynamicData(container) {
    // Iniciales del usuario
    const initialsEl = container.querySelector("#userInitials");
    if (initialsEl && DATA.initials) {
      initialsEl.textContent = DATA.initials;
    }

    // Nombre completo
    const nameEl = container.querySelector("#userName");
    if (nameEl && DATA.fullName) {
      nameEl.textContent = DATA.fullName;
    }

    // Rol
    const roleEl = container.querySelector("#userRole");
    if (roleEl && DATA.roleLabel) {
      roleEl.textContent = DATA.roleLabel;
    }

    // Imágenes con data-asset → src absoluta
    container.querySelectorAll("[data-asset]").forEach(function (img) {
      img.src = getAssetBase() + img.getAttribute("data-asset");
    });

    // Links con data-link
    container.querySelectorAll("[data-link]").forEach(function (el) {
      const link = el.getAttribute("data-link");
      const appBase = getAppBase();
      const roleBase = appBase + "/" + (DATA.role || "egresado");

      switch (link) {
        case "profile":
          if (
            (DATA.role === "docente" || DATA.role === "ti") &&
            el.closest("li")
          ) {
            el.closest("li").remove();
            return;
          }
          if (el.tagName === "A") el.href = roleBase + "/perfil";
          break;
        case "security":
          if (
            (DATA.role === "docente" || DATA.role === "ti") &&
            el.closest("li")
          ) {
            el.closest("li").remove();
            return;
          }
          if (el.tagName === "A") el.href = roleBase + "/seguridad";
          break;
        case "notifications":
          if (el.tagName === "A") el.href = appBase + "/notificaciones";
          break;
        case "logout":
          if (el.tagName === "A") {
            el.href = appBase + "/logout";
          } else {
            el.addEventListener("click", function () {
              window.location.href = appBase + "/logout";
            });
          }
          break;
      }
    });

    // Sidebar: marcar la página activa
    const currentPage = DATA.currentPage || "";
    container.querySelectorAll("[data-page]").forEach(function (item) {
      if (item.getAttribute("data-page") === currentPage) {
        item.classList.add("active");
      }
    });
  }

  function ensureDarkReader() {
    if (window.DarkReader) {
      return Promise.resolve(window.DarkReader);
    }

    if (darkReaderPromise) {
      return darkReaderPromise;
    }

    darkReaderPromise = new Promise(function (resolve, reject) {
      var existing = document.querySelector("script[data-darkreader]");
      if (existing && window.DarkReader) {
        resolve(window.DarkReader);
        return;
      }

      var script = document.createElement("script");
      script.src =
        "https://cdn.jsdelivr.net/npm/darkreader@4.9.128/darkreader.min.js";
      script.async = true;
      script.setAttribute("data-darkreader", "true");
      script.onload = function () {
        resolve(window.DarkReader);
      };
      script.onerror = function () {
        reject(new Error("No se pudo cargar Dark Reader"));
      };
      document.head.appendChild(script);
    });

    return darkReaderPromise;
  }

  function getStoredThemeMode() {
    try {
      return localStorage.getItem(THEME_STORAGE_KEY) || "";
    } catch (e) {
      return "";
    }
  }

  function setStoredThemeMode(mode) {
    try {
      localStorage.setItem(THEME_STORAGE_KEY, mode);
    } catch (e) {
      // Ignorar si el storage no está disponible
    }
  }

  function updateThemeButtonState(button, enabled) {
    if (!button) return;
    button.classList.toggle("is-dark", enabled);
    button.setAttribute("aria-pressed", enabled ? "true" : "false");
  }

  async function applyThemeMode(mode) {
    var DarkReader = await ensureDarkReader();
    var button = document.getElementById("themeToggleBtn");

    if (mode === "dark") {
      DarkReader.enable({ brightness: 100, contrast: 90, sepia: 10 });
      updateThemeButtonState(button, true);
      setStoredThemeMode("dark");
      return;
    }

    if (mode === "auto") {
      DarkReader.auto({ brightness: 100, contrast: 90, sepia: 10 });
      updateThemeButtonState(button, DarkReader.isEnabled());
      setStoredThemeMode("auto");
      return;
    }

    DarkReader.disable();
    updateThemeButtonState(button, false);
    setStoredThemeMode("light");
  }

  async function initThemeToggle() {
    var button = document.getElementById("themeToggleBtn");
    if (!button) return;

    var storedMode = getStoredThemeMode();
    var systemPrefersDark =
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches;

    await ensureDarkReader();

    if (storedMode === "dark") {
      await applyThemeMode("dark");
    } else if (storedMode === "auto") {
      await applyThemeMode("auto");
    } else if (storedMode === "light") {
      await applyThemeMode("light");
    } else if (systemPrefersDark) {
      await applyThemeMode("auto");
    } else {
      await applyThemeMode("light");
    }

    button.addEventListener("click", async function () {
      var enabled = window.DarkReader && window.DarkReader.isEnabled();
      await applyThemeMode(enabled ? "light" : "dark");
    });
  }

  /* ---------- Inicializar notice ---------- */

  function initNotice() {
    var dismissBtn = document.getElementById("btnDismissNotice");
    var notice = document.getElementById("passwordNotice");

    if (dismissBtn && notice) {
      dismissBtn.addEventListener("click", function () {
        notice.style.transition = "opacity .25s ease";
        notice.style.opacity = "0";
        setTimeout(function () {
          notice.style.display = "none";
        }, 260);
      });
    }
  }

  /* ---------- Bootstrap re-init dentro de componentes ---------- */

  function reinitBootstrap(container) {
    // Dropdowns
    container
      .querySelectorAll('[data-bs-toggle="dropdown"]')
      .forEach(function (el) {
        new bootstrap.Dropdown(el);
      });
    // Collapse (sidebar móvil)
    container
      .querySelectorAll('[data-bs-toggle="collapse"]')
      .forEach(function (el) {
        new bootstrap.Collapse(el, { toggle: false });
      });
    // Tooltips
    container
      .querySelectorAll('[data-bs-toggle="tooltip"]')
      .forEach(function (el) {
        new bootstrap.Tooltip(el);
      });
  }

  /* ---------- Arranque ---------- */

  async function init() {
    var loads = [];

    // Notice (solo si requiere cambio de contraseña)
    if (DATA.requirePasswordChange) {
      loads.push(loadComponent("utp-notice-container", "notice-password.html"));
    }

    // Topbar
    loads.push(loadComponent("utp-topbar-container", "topbar.html"));

    // Sidebar según rol
    var sidebarRole = DATA.role === "ti" ? "docente" : DATA.role || "egresado";
    var sidebarFile = "sidebar-" + sidebarRole + ".html";
    loads.push(loadComponent("utp-sidebar-container", sidebarFile));

    await Promise.all(loads);

    // Re-inicializar componentes de Bootstrap dentro de los contenedores inyectados
    document
      .querySelectorAll("#utp-topbar-container, #utp-sidebar-container")
      .forEach(reinitBootstrap);

    // Tema oscuro compartido
    await initThemeToggle();

    // Marcar body como listo (para transiciones CSS)
    document.body.classList.add("utp-ready");

    // Inicializar dismiss del notice
    initNotice();

    // Cargar script de notificaciones dinámicamente
    var notifScript = document.createElement("script");
    notifScript.src = getAssetBase() + "js/shared/notifications.js";
    document.body.appendChild(notifScript);
  }

  // Ejecutar cuando el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
