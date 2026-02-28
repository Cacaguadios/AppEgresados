/**
 * Seguridad – Cambio de contraseña (Egresado)
 * Toggle contraseñas, indicador de fortaleza, validación client-side.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ===== Toggle password visibility ===== */
    document.querySelectorAll('.utp-toggle-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-target'));
        if (!target) return;

        var isPassword = target.type === 'password';
        target.type = isPassword ? 'text' : 'password';

        var icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye', !isPassword);
          icon.classList.toggle('bi-eye-slash', isPassword);
        }
      });
    });

    /* ===== Password strength indicator ===== */
    var newPwd        = document.getElementById('newPassword');
    var strengthWrap  = document.getElementById('strengthContainer');
    var strengthBar   = document.getElementById('strengthBar');
    var strengthLabel = document.getElementById('strengthLabel');

    var reqs = {
      length:  { el: document.getElementById('req-length'),  test: function (v) { return v.length >= 8; } },
      upper:   { el: document.getElementById('req-upper'),   test: function (v) { return /[A-Z]/.test(v); } },
      lower:   { el: document.getElementById('req-lower'),   test: function (v) { return /[a-z]/.test(v); } },
      number:  { el: document.getElementById('req-number'),  test: function (v) { return /[0-9]/.test(v); } },
      special: { el: document.getElementById('req-special'), test: function (v) { return /[^A-Za-z0-9]/.test(v); } }
    };

    if (newPwd) {
      newPwd.addEventListener('input', function () {
        var val = newPwd.value;

        // Mostrar/ocultar contenedor
        if (val.length > 0) {
          strengthWrap.classList.remove('d-none');
        } else {
          strengthWrap.classList.add('d-none');
          return;
        }

        // Evaluar requisitos
        var passed = 0;
        var total  = Object.keys(reqs).length;

        for (var key in reqs) {
          var ok   = reqs[key].test(val);
          var icon = reqs[key].el.querySelector('i');

          if (ok) {
            passed++;
            icon.className = 'bi bi-check-circle-fill text-success me-1';
          } else {
            icon.className = 'bi bi-x-circle text-danger me-1';
          }
        }

        // Barra y etiqueta
        var pct = Math.round((passed / total) * 100);
        strengthBar.style.width = pct + '%';

        if (passed <= 2) {
          strengthBar.style.background = '#FF3333';
          strengthLabel.textContent = 'Débil';
          strengthLabel.style.color = '#FF3333';
        } else if (passed <= 3) {
          strengthBar.style.background = '#FFB800';
          strengthLabel.textContent = 'Regular';
          strengthLabel.style.color = '#FFB800';
        } else if (passed === 4) {
          strengthBar.style.background = '#00C247';
          strengthLabel.textContent = 'Buena';
          strengthLabel.style.color = '#00C247';
        } else {
          strengthBar.style.background = '#00853E';
          strengthLabel.textContent = 'Fuerte';
          strengthLabel.style.color = '#00853E';
        }
      });
    }

    /* ===== Confirm password match ===== */
    var confirmPwd   = document.getElementById('confirmPassword');
    var confirmError = document.getElementById('confirmError');

    if (confirmPwd) {
      confirmPwd.addEventListener('input', checkMatch);
      if (newPwd) newPwd.addEventListener('input', function () {
        if (confirmPwd.value.length > 0) checkMatch();
      });
    }

    function checkMatch() {
      if (!newPwd || !confirmPwd) return;

      if (confirmPwd.value.length === 0) {
        confirmPwd.classList.remove('is-invalid', 'is-valid');
        return;
      }

      if (confirmPwd.value === newPwd.value) {
        confirmPwd.classList.remove('is-invalid');
        confirmPwd.classList.add('is-valid');
      } else {
        confirmPwd.classList.remove('is-valid');
        confirmPwd.classList.add('is-invalid');
      }
    }

    /* ===== Form submit validation ===== */
    var form      = document.getElementById('changePasswordForm');
    var btnSubmit = document.getElementById('btnSubmit');

    if (form) {
      form.addEventListener('submit', function (e) {
        // Verificar campos vacíos
        var current = document.getElementById('currentPassword');
        if (!current.value || !newPwd.value || !confirmPwd.value) {
          return; // dejamos que el browser muestre los required nativos
        }

        // Verificar coincidencia
        if (newPwd.value !== confirmPwd.value) {
          e.preventDefault();
          confirmPwd.classList.add('is-invalid');
          confirmPwd.focus();
          return;
        }

        // Verificar fortaleza mínima (5/5)
        var allPassed = true;
        for (var key in reqs) {
          if (!reqs[key].test(newPwd.value)) {
            allPassed = false;
            break;
          }
        }

        if (!allPassed) {
          e.preventDefault();
          newPwd.focus();
          strengthWrap.classList.remove('d-none');
          return;
        }

        // Deshabilitar botón para evitar doble envío
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando…';
      });
    }

  });
})();
