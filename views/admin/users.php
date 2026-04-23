<?php
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/models/Usuario.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$initials  = mb_strtoupper(mb_substr($nombre,0,1) . mb_substr($apellidos,0,1));
$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

$usuarioModel = new Usuario();
$msgExito = '';
$msgError = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit_user') {
        $uid = (int)$_POST['user_id'];
        $data = [
            'nombre'       => trim($_POST['edit_nombre'] ?? ''),
            'tipo_usuario' => $_POST['edit_rol'] ?? '',
            'activo'       => (int)($_POST['edit_estado'] ?? 1),
        ];
        if ($uid && $uid !== (int)$_SESSION['usuario_id'] && in_array($data['tipo_usuario'], ['egresado','docente','admin'])) {
            $usuarioModel->updateUserAdmin($uid, $data);
            $msgExito = 'Usuario actualizado correctamente.';
        }
    }

    if ($action === 'reset_password') {
        $uid = (int)$_POST['user_id'];
        $tempPass = $_POST['temp_password'] ?? '';
        if ($uid && $tempPass) {
            $usuarioModel->resetPassword($uid, $tempPass);
            $msgExito = 'Contraseña restablecida. El usuario deberá cambiarla al iniciar sesión.';
        }
    }

    if ($action === 'toggle_block') {
        $uid = (int)$_POST['user_id'];
        $block = (int)$_POST['block'];
        if ($uid && $uid !== (int)$_SESSION['usuario_id']) {
            $usuarioModel->toggleBlock($uid, $block);
            $msgExito = $block ? 'Usuario bloqueado.' : 'Usuario desbloqueado.';
        }
    }
}

// Load data
$stats = $usuarioModel->getAdminStats();
$usuarios = $usuarioModel->getAllForAdmin();

// Badge helpers
$rolBadge = [
    'egresado' => ['label' => 'Egresado', 'class' => 'bg-success bg-opacity-10 text-success'],
    'docente'  => ['label' => 'Docente',  'class' => 'bg-primary bg-opacity-10 text-primary'],
    'admin'    => ['label' => 'Admin',    'class' => 'bg-danger bg-opacity-10 text-danger'],
];
$verifBadge = [
    'pendiente'  => ['label' => 'Pendiente',  'class' => 'bg-warning bg-opacity-10 text-warning'],
    'verificado' => ['label' => 'Verificado', 'class' => 'bg-success bg-opacity-10 text-success'],
    'rechazado'  => ['label' => 'Rechazado',  'class' => 'bg-danger bg-opacity-10 text-danger'],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Usuarios - Admin UTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/app-main.css" rel="stylesheet">
</head>

<body class="bg-soft">
  <script>
    window.UTP_DATA = {
      role: 'admin', roleLabel: 'Administrador',
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'usuarios',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
  </script>

  <div id="utp-notice-container"></div>
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <div class="container-fluid px-0">
    <div class="row g-0">
      <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

      <main class="col utp-content">
        <div class="container-fluid px-3 px-md-4 py-4 py-md-5">

          <!-- Header -->
          <section class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
              <h1 class="utp-h1 mb-2">Gestión de Usuarios</h1>
              <p class="utp-subtitle mb-0">Administra cuentas, roles y accesos del sistema</p>
            </div>
          </section>

          <?php if ($msgExito): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
              <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msgExito) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- Stats -->
          <section class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon blue"><i class="bi bi-people"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['total'] ?></div>
                <div class="text-muted small">Total usuarios</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon green"><i class="bi bi-check-circle"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['activos'] ?></div>
                <div class="text-muted small">Activos</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon red"><i class="bi bi-slash-circle"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['bloqueados'] ?></div>
                <div class="text-muted small">Bloqueados</div>
              </article>
            </div>
            <div class="col-6 col-xl-3">
              <article class="utp-card">
                <div class="d-flex align-items-start justify-content-between">
                  <div class="utp-miniicon yellow"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="utp-kpi mt-3"><?= (int)$stats['verif_pendientes'] ?></div>
                <div class="text-muted small">Verificación pendiente</div>
              </article>
            </div>
          </section>

          <!-- Filtros -->
          <div class="utp-card mb-3">
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label utp-label">Buscar</label>
                <div class="position-relative">
                  <i class="bi bi-search utp-search-icon"></i>
                  <input type="text" class="form-control utp-input utp-search-input" id="searchInput" placeholder="Nombre, usuario o correo...">
                </div>
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label utp-label">Rol</label>
                <select class="form-select utp-select" id="filterRol">
                  <option value="">Todos los roles</option>
                  <option value="egresado">Egresado</option>
                  <option value="docente">Docente</option>
                  <option value="admin">Administrador</option>
                </select>
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label utp-label">Estado</label>
                <select class="form-select utp-select" id="filterEstado">
                  <option value="">Todos</option>
                  <option value="activo">Activo</option>
                  <option value="bloqueado">Bloqueado</option>
                </select>
              </div>
            </div>
          </div>

          <p class="text-muted small mb-3" id="counterText">Mostrando <?= count($usuarios) ?> usuario<?= count($usuarios) !== 1 ? 's' : '' ?></p>

          <!-- Desktop Table -->
          <div class="utp-card d-none d-lg-block mb-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr class="text-muted small">
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Verificación</th>
                    <th>Último acceso</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($usuarios as $u):
                    $uNombre = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
                    $uInitials = mb_strtoupper(mb_substr($u['nombre'] ?? '',0,1) . mb_substr($u['apellidos'] ?? '',0,1));
                    $rb = $rolBadge[$u['tipo_usuario']] ?? $rolBadge['egresado'];
                    $vb = $verifBadge[$u['verificacion_estado'] ?? 'pendiente'] ?? $verifBadge['pendiente'];
                    $lastLogin = $u['fecha_ultima_login'] ? date('d/m/Y H:i', strtotime($u['fecha_ultima_login'])) : 'Nunca';
                    $isBlocked = !(int)$u['activo'];
                    $isSelf = (int)$u['id'] === (int)$_SESSION['usuario_id'];
                  ?>
                  <tr class="user-row" 
                      data-name="<?= strtolower(htmlspecialchars($uNombre)) ?>" 
                      data-user="<?= strtolower(htmlspecialchars($u['usuario'] ?? '')) ?>" 
                      data-email="<?= strtolower(htmlspecialchars($u['email'] ?? '')) ?>"
                      data-rol="<?= htmlspecialchars($u['tipo_usuario']) ?>"
                      data-estado="<?= $isBlocked ? 'bloqueado' : 'activo' ?>">
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="utp-useravatar"><?= $uInitials ?></div>
                        <div>
                          <div class="fw-medium"><?= htmlspecialchars($u['usuario'] ?? '—') ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($uNombre) ?></td>
                    <td><span class="badge <?= $rb['class'] ?>"><?= $rb['label'] ?></span></td>
                    <td>
                      <?php if ($isBlocked): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger">Bloqueado</span>
                      <?php else: ?>
                        <span class="badge bg-success bg-opacity-10 text-success">Activo</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $vb['class'] ?>"><?= $vb['label'] ?></span></td>
                    <td class="text-muted small"><?= $lastLogin ?></td>
                    <td class="text-end">
                      <?php if (!$isSelf): ?>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary btn-edit" title="Editar"
                          data-id="<?= (int)$u['id'] ?>" data-nombre="<?= htmlspecialchars($uNombre) ?>" data-rol="<?= htmlspecialchars($u['tipo_usuario']) ?>" data-activo="<?= (int)$u['activo'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-reset" title="Restablecer contraseña"
                          data-id="<?= (int)$u['id'] ?>" data-nombre="<?= htmlspecialchars($uNombre) ?>">
                          <i class="bi bi-key"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-block" title="<?= $isBlocked ? 'Desbloquear' : 'Bloquear' ?>"
                          data-id="<?= (int)$u['id'] ?>" data-nombre="<?= htmlspecialchars($uNombre) ?>" data-block="<?= $isBlocked ? '0' : '1' ?>">
                          <i class="bi bi-<?= $isBlocked ? 'unlock' : 'lock' ?>"></i>
                        </button>
                      </div>
                      <?php else: ?>
                        <span class="text-muted small">Tú</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Mobile Cards -->
          <div class="d-lg-none">
            <?php foreach ($usuarios as $u):
              $uNombre = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
              $uInitials = mb_strtoupper(mb_substr($u['nombre'] ?? '',0,1) . mb_substr($u['apellidos'] ?? '',0,1));
              $rb = $rolBadge[$u['tipo_usuario']] ?? $rolBadge['egresado'];
              $vb = $verifBadge[$u['verificacion_estado'] ?? 'pendiente'] ?? $verifBadge['pendiente'];
              $lastLogin = $u['fecha_ultima_login'] ? date('d/m/Y H:i', strtotime($u['fecha_ultima_login'])) : 'Nunca';
              $isBlocked = !(int)$u['activo'];
              $isSelf = (int)$u['id'] === (int)$_SESSION['usuario_id'];
            ?>
            <div class="utp-card mb-3 user-card"
                 data-name="<?= strtolower(htmlspecialchars($uNombre)) ?>"
                 data-user="<?= strtolower(htmlspecialchars($u['usuario'] ?? '')) ?>"
                 data-email="<?= strtolower(htmlspecialchars($u['email'] ?? '')) ?>"
                 data-rol="<?= htmlspecialchars($u['tipo_usuario']) ?>"
                 data-estado="<?= $isBlocked ? 'bloqueado' : 'activo' ?>">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="utp-useravatar"><?= $uInitials ?></div>
                <div class="flex-grow-1">
                  <div class="fw-semibold"><?= htmlspecialchars($uNombre) ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($u['usuario'] ?? '') ?> · <?= htmlspecialchars($u['email'] ?? '') ?></div>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge <?= $rb['class'] ?>"><?= $rb['label'] ?></span>
                <?php if ($isBlocked): ?>
                  <span class="badge bg-danger bg-opacity-10 text-danger">Bloqueado</span>
                <?php else: ?>
                  <span class="badge bg-success bg-opacity-10 text-success">Activo</span>
                <?php endif; ?>
                <span class="badge <?= $vb['class'] ?>"><?= $vb['label'] ?></span>
              </div>
              <div class="text-muted small mb-3">Último acceso: <?= $lastLogin ?></div>
              <?php if (!$isSelf): ?>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill btn-edit"
                  data-id="<?= (int)$u['id'] ?>" data-nombre="<?= htmlspecialchars($uNombre) ?>" data-rol="<?= htmlspecialchars($u['tipo_usuario']) ?>" data-activo="<?= (int)$u['activo'] ?>">
                  <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning flex-fill btn-reset"
                  data-id="<?= (int)$u['id'] ?>" data-nombre="<?= htmlspecialchars($uNombre) ?>">
                  <i class="bi bi-key me-1"></i>Reset
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger flex-fill btn-block"
                  data-id="<?= (int)$u['id'] ?>" data-nombre="<?= htmlspecialchars($uNombre) ?>" data-block="<?= $isBlocked ? '0' : '1' ?>">
                  <i class="bi bi-<?= $isBlocked ? 'unlock' : 'lock' ?> me-1"></i><?= $isBlocked ? 'Desbloquear' : 'Bloquear' ?>
                </button>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

        </div>
      </main>
    </div>
  </div>

  <!-- Modal: Editar Usuario -->
  <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
          <?= Security::csrfField() ?>
          <input type="hidden" name="action" value="edit_user">
          <input type="hidden" name="user_id" id="editUserId">
          <div class="modal-header">
            <h5 class="modal-title">Editar usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted mb-3" id="editUserName"></p>
            <div class="mb-3">
              <label class="form-label fw-medium">Nombre completo</label>
              <input type="text" class="form-control utp-input" name="edit_nombre" id="editNombre" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Rol</label>
              <select class="form-select utp-select" name="edit_rol" id="editRol">
                <option value="egresado">Egresado</option>
                <option value="docente">Docente</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-medium">Estado</label>
              <select class="form-select utp-select" name="edit_estado" id="editEstado">
                <option value="1">Activo</option>
                <option value="0">Bloqueado</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-utp-red">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Restablecer Contraseña -->
  <div class="modal fade" id="modalReset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
          <?= Security::csrfField() ?>
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="user_id" id="resetUserId">
          <div class="modal-header">
            <h5 class="modal-title">Restablecer contraseña</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted">Se generará una contraseña temporal para <strong id="resetUserName"></strong>.</p>
            <div class="mb-3">
              <label class="form-label fw-medium">Contraseña temporal</label>
              <div class="input-group">
                <input type="text" class="form-control utp-input" name="temp_password" id="tempPassword" readonly>
                <button type="button" class="btn btn-outline-secondary" onclick="generarTempPass()">
                  <i class="bi bi-arrow-clockwise"></i> Generar
                </button>
              </div>
              <div class="form-text">El usuario deberá cambiarla en su próximo inicio de sesión.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning">Restablecer</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Bloquear/Desbloquear -->
  <div class="modal fade" id="modalBloquear" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
          <?= Security::csrfField() ?>
          <input type="hidden" name="action" value="toggle_block">
          <input type="hidden" name="user_id" id="blockUserId">
          <input type="hidden" name="block" id="blockValue">
          <div class="modal-header">
            <h5 class="modal-title" id="blockTitle">Bloquear usuario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="blockMessage"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger" id="blockBtn">Bloquear</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/components-loader.js"></script>
  <script src="<?= ASSETS_URL ?>/js/shared/app.js"></script>
  <script>
    // --- Delegated click handlers (XSS-safe via data-* attributes) ---
    document.addEventListener('click', function(e) {
      var btn;
      if ((btn = e.target.closest('.btn-edit'))) {
        abrirModalEditar(btn.dataset.id, btn.dataset.nombre, btn.dataset.rol, parseInt(btn.dataset.activo));
      } else if ((btn = e.target.closest('.btn-reset'))) {
        abrirModalReset(btn.dataset.id, btn.dataset.nombre);
      } else if ((btn = e.target.closest('.btn-block'))) {
        abrirModalBloquear(btn.dataset.id, btn.dataset.nombre, parseInt(btn.dataset.block));
      }
    });

    // --- Modal: Editar ---
    function abrirModalEditar(id, nombre, rol, activo) {
      document.getElementById('editUserId').value = id;
      document.getElementById('editUserName').textContent = nombre;
      document.getElementById('editNombre').value = nombre;
      document.getElementById('editRol').value = rol;
      document.getElementById('editEstado').value = activo;
      new bootstrap.Modal(document.getElementById('modalEditar')).show();
    }

    // --- Modal: Reset Password ---
    function abrirModalReset(id, nombre) {
      document.getElementById('resetUserId').value = id;
      document.getElementById('resetUserName').textContent = nombre;
      generarTempPass();
      new bootstrap.Modal(document.getElementById('modalReset')).show();
    }

    function generarTempPass() {
      var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
      var pass = '';
      for (var i = 0; i < 12; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
      // Ensure at least one uppercase, lowercase, digit, special
      pass = pass.substring(0,8) + 'A' + 'a' + '1' + '!';
      document.getElementById('tempPassword').value = pass;
    }

    // --- Modal: Bloquear ---
    function abrirModalBloquear(id, nombre, block) {
      document.getElementById('blockUserId').value = id;
      document.getElementById('blockValue').value = block;
      var nameEl = document.getElementById('blockUserName');
      if (nameEl) nameEl.textContent = nombre;
      if (block) {
        document.getElementById('blockTitle').textContent = 'Bloquear usuario';
        document.getElementById('blockMessage').textContent = '¿Estás seguro de que deseas bloquear a este usuario? No podrá iniciar sesión.';
        document.getElementById('blockBtn').textContent = 'Bloquear';
        document.getElementById('blockBtn').className = 'btn btn-danger';
      } else {
        document.getElementById('blockTitle').textContent = 'Desbloquear usuario';
        document.getElementById('blockMessage').textContent = '¿Deseas desbloquear a este usuario? Podrá iniciar sesión nuevamente.';
        document.getElementById('blockBtn').textContent = 'Desbloquear';
        document.getElementById('blockBtn').className = 'btn btn-success';
      }
      new bootstrap.Modal(document.getElementById('modalBloquear')).show();
    }

    // --- Client-side filtering ---
    function applyFilters() {
      var q = document.getElementById('searchInput').value.toLowerCase();
      var rol = document.getElementById('filterRol').value;
      var estado = document.getElementById('filterEstado').value;
      var visible = 0;

      // Desktop rows
      document.querySelectorAll('.user-row').forEach(function(row) {
        var match = filterMatch(row, q, rol, estado);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      // Mobile cards
      document.querySelectorAll('.user-card').forEach(function(card) {
        card.style.display = filterMatch(card, q, rol, estado) ? '' : 'none';
      });

      document.getElementById('counterText').textContent = 'Mostrando ' + visible + ' usuario' + (visible !== 1 ? 's' : '');
    }

    function filterMatch(el, q, rol, estado) {
      var name = el.getAttribute('data-name') || '';
      var user = el.getAttribute('data-user') || '';
      var email = el.getAttribute('data-email') || '';
      var elRol = el.getAttribute('data-rol') || '';
      var elEstado = el.getAttribute('data-estado') || '';

      var matchSearch = !q || name.includes(q) || user.includes(q) || email.includes(q);
      var matchRol = !rol || elRol === rol;
      var matchEstado = !estado || elEstado === estado;
      return matchSearch && matchRol && matchEstado;
    }

    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterRol').addEventListener('change', applyFilters);
    document.getElementById('filterEstado').addEventListener('change', applyFilters);
  </script>
</body>
</html>
