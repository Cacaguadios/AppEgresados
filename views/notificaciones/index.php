<?php
session_start();

// ─── Guard: requiere autenticación (cualquier rol) ───
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../app/helpers/Security.php';
require_once __DIR__ . '/../../app/models/Notificacion.php';

$rol       = $_SESSION['usuario_rol'] ?? 'egresado';
$nombre    = $_SESSION['usuario_nombre']   ?? '';
$apellidos = $_SESSION['usuario_apellidos'] ?? '';
$fullName  = trim($nombre . ' ' . $apellidos);
$userId    = $_SESSION['usuario_id'] ?? 0;

$initials = '';
if ($nombre)    $initials .= mb_substr($nombre, 0, 1);
if ($apellidos) $initials .= mb_substr($apellidos, 0, 1);
$initials = mb_strtoupper($initials);

$requirePasswordChange = !empty($_SESSION['requiere_cambio_pass']);

// ─── Acciones POST ───
$notifModel = new Notificacion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Token de seguridad inválido.';
        header('Location: index.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read' && !empty($_POST['notif_id'])) {
        $notifModel->marcarLeida((int)$_POST['notif_id'], $userId);
    } elseif ($action === 'mark_all_read') {
        $notifModel->marcarTodasLeidas($userId);
    }

    header('Location: index.php');
    exit;
}

// ─── Cargar notificaciones ───
$notificaciones = $notifModel->getByUsuario($userId, 50);
$noLeidas = $notifModel->contarNoLeidas($userId);

// ─── Rol label y datos para layout ───
$roleLabels = [
    'egresado' => 'Egresado',
    'docente'  => 'Docente',
    'ti'       => 'TI',
    'admin'    => 'Administrador',
];
$roleLabel = $roleLabels[$rol] ?? ucfirst($rol);

// Íconos por tipo de notificación
function getNotifIcon($tipo) {
    $map = [
        'oferta_nueva'             => 'bi-briefcase',
        'oferta_aprobada'          => 'bi-check-circle',
        'oferta_rechazada'         => 'bi-x-circle',
        'nueva_postulacion'        => 'bi-person-plus',
        'postulacion_seleccionada' => 'bi-trophy',
        'postulacion_rechazada'    => 'bi-person-x',
        'nuevo_usuario'            => 'bi-person-badge',
        'general'                  => 'bi-info-circle',
    ];
    return $map[$tipo] ?? 'bi-bell';
}

function getNotifColor($tipo) {
    $map = [
        'oferta_nueva'             => 'blue',
        'oferta_aprobada'          => 'green',
        'oferta_rechazada'         => 'red',
        'nueva_postulacion'        => 'blue',
        'postulacion_seleccionada' => 'green',
        'postulacion_rechazada'    => 'red',
        'nuevo_usuario'            => 'yellow',
        'general'                  => 'blue',
    ];
    return $map[$tipo] ?? 'blue';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notificaciones - Sistema de Egresados UTP</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <!-- App CSS -->
  <link href="../../public/assets/css/app-main.css" rel="stylesheet">

  <style>
    /* ===== Notification Page Styles ===== */
    .notif-list { display: flex; flex-direction: column; gap: 0; }

    .notif-item {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 20px 24px;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      transition: background .15s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      position: relative;
    }
    .notif-item:hover { background: rgba(0,76,235,0.03); color: inherit; }
    .notif-item.unread { background: rgba(0,76,235,0.02); }

    .notif-icon {
      width: 44px; height: 44px; min-width: 44px;
      border-radius: 14px;
      display: grid; place-items: center;
      font-size: 18px;
    }
    .notif-icon.blue  { background: rgba(0,76,235,0.10); color: #004CEB; }
    .notif-icon.green { background: rgba(0,133,62,0.10);  color: #00853E; }
    .notif-icon.red   { background: rgba(122,21,1,0.10);  color: #7A1501; }
    .notif-icon.yellow{ background: rgba(255,225,106,0.30);color: #7A1501; }

    .notif-body { flex: 1; min-width: 0; }
    .notif-title {
      font-weight: 600; font-size: 15px; line-height: 22px;
      color: #121212; margin-bottom: 4px;
    }
    .notif-msg {
      font-size: 14px; line-height: 20px; color: #757575;
      margin-bottom: 4px;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .notif-date {
      font-size: 12px; color: #9e9e9e; line-height: 16px;
    }

    .notif-badge {
      background: #004CEB; color: #fff;
      font-size: 11px; font-weight: 600;
      padding: 4px 10px; border-radius: 20px;
      white-space: nowrap; align-self: center;
    }

    .notif-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 16px; flex-wrap: wrap; gap: 12px;
    }
    .notif-header h1 {
      font-size: 36px; font-weight: 700; line-height: 40px; color: #121212; margin: 0;
    }

    .notif-empty {
      text-align: center; padding: 60px 20px; color: #9e9e9e;
    }
    .notif-empty i { font-size: 48px; display: block; margin-bottom: 12px; }
    .notif-empty p { font-size: 16px; margin: 0; }
  </style>
</head>

<body>
  <!-- Datos para JS -->
  <script>
    window.UTP_DATA = {
      role: <?= json_encode($rol) ?>,
      roleLabel: <?= json_encode($roleLabel) ?>,
      fullName: <?= json_encode($fullName) ?>,
      initials: <?= json_encode($initials) ?>,
      currentPage: 'notificaciones',
      requirePasswordChange: <?= $requirePasswordChange ? 'true' : 'false' ?>
    };
  </script>

  <!-- ===== Notice ===== -->
  <div id="utp-notice-container"></div>

  <!-- ===== Topbar ===== -->
  <div id="utp-topbar-container" class="utp-topbar"></div>

  <!-- ===== Layout principal ===== -->
  <div class="utp-layout">
    <div class="container-fluid px-3 px-md-4">
      <div class="row gx-4">

        <!-- Sidebar -->
        <div id="utp-sidebar-container" class="col-12 col-md-auto"></div>

        <!-- Contenido principal -->
        <div class="col">
          <div class="utp-content">

            <!-- Encabezado -->
            <div class="notif-header">
              <h1>Notificaciones</h1>
              <?php if ($noLeidas > 0): ?>
              <form method="POST" class="d-inline">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline-secondary btn-sm" style="border-radius:10px;">
                  <i class="bi bi-check-all me-1"></i> Marcar todas como leídas
                </button>
              </form>
              <?php endif; ?>
            </div>

            <?php if (!empty($_SESSION['flash_error'])): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
              <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- Lista de notificaciones -->
            <?php if (empty($notificaciones)): ?>
              <div class="utp-card">
                <div class="notif-empty">
                  <i class="bi bi-bell-slash"></i>
                  <p>No tienes notificaciones</p>
                </div>
              </div>
            <?php else: ?>
              <div class="utp-card" style="padding:0; overflow:hidden;">
                <div class="notif-list">
                  <?php foreach ($notificaciones as $n): ?>
                    <?php
                      $isUnread = !(int)$n['leida'];
                      $icon     = getNotifIcon($n['tipo']);
                      $color    = getNotifColor($n['tipo']);
                      $url      = $n['url'] ?? '#';
                      $fechaRaw = strtotime($n['fecha_creacion']);
                      $ampm     = date('A', $fechaRaw) === 'AM' ? 'a.m.' : 'p.m.';
                      $fecha    = date('j/n/Y, g:i:s ', $fechaRaw) . $ampm;

                      // Fix relative URLs - ensure they work from /views/notificaciones/
                      if ($url && $url !== '#' && strpos($url, 'http') !== 0) {
                          // URLs stored as ../../views/X — from notificaciones/ we need ../X
                          $url = str_replace('../../views/', '../', $url);
                      }
                    ?>
                    <div class="notif-item <?= $isUnread ? 'unread' : '' ?>"
                         data-notif-id="<?= (int)$n['id'] ?>"
                         data-url="<?= htmlspecialchars($url) ?>"
                         data-read="<?= $isUnread ? '0' : '1' ?>">
                      <div class="notif-icon <?= $color ?>">
                        <i class="bi <?= $icon ?>"></i>
                      </div>
                      <div class="notif-body">
                        <div class="notif-title"><?= htmlspecialchars($n['titulo']) ?></div>
                        <?php if (!empty($n['mensaje'])): ?>
                          <div class="notif-msg"><?= htmlspecialchars($n['mensaje']) ?></div>
                        <?php endif; ?>
                        <div class="notif-date"><?= htmlspecialchars($fecha) ?></div>
                      </div>
                      <?php if ($isUnread): ?>
                        <span class="notif-badge">Nueva</span>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

          </div><!-- .utp-content -->
        </div><!-- .col -->
      </div><!-- .row -->
    </div><!-- .container-fluid -->
  </div><!-- .utp-layout -->

  <!-- Hidden form for marking individual notifications as read -->
  <form id="markReadForm" method="POST" style="display:none;">
    <?= Security::csrfField() ?>
    <input type="hidden" name="action" value="mark_read">
    <input type="hidden" name="notif_id" id="markReadNotifId" value="">
  </form>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Components loader -->
  <script src="../../public/assets/js/shared/components-loader.js"></script>
  <script src="../../public/assets/js/app.js"></script>

  <script>
    // Click on notification → mark as read + navigate
    document.querySelectorAll('.notif-item').forEach(function(item) {
      item.addEventListener('click', function() {
        var notifId = this.getAttribute('data-notif-id');
        var url = this.getAttribute('data-url');
        var isRead = this.getAttribute('data-read') === '1';

        if (!isRead) {
          // Submit form to mark as read, then navigate
          var form = document.getElementById('markReadForm');
          document.getElementById('markReadNotifId').value = notifId;

          if (url && url !== '#') {
            // Use fetch to mark read, then navigate
            var formData = new FormData(form);
            fetch('index.php', {
              method: 'POST',
              body: formData
            }).then(function() {
              window.location.href = url;
            }).catch(function() {
              window.location.href = url;
            });
          } else {
            form.submit();
          }
        } else if (url && url !== '#') {
          window.location.href = url;
        }
      });
    });
  </script>
</body>
</html>
