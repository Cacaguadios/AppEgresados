<?php
$titles = [
    404 => 'Pagina no encontrada',
    405 => 'Metodo no permitido',
    419 => 'Sesion o formulario expirado',
    429 => 'Demasiadas solicitudes',
    500 => 'Error interno',
];
$title = $titles[$errorStatus] ?? 'No fue posible completar la solicitud';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars((string) $errorStatus, ENT_QUOTES, 'UTF-8') ?> - AppEgresados</title>
  <?php if (defined('ASSETS_URL')): ?>
    <link href="<?= htmlspecialchars(ASSETS_URL . '/css/global.css', ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
  <?php endif; ?>
</head>
<body>
  <main class="container py-5">
    <section class="mx-auto" style="max-width: 640px">
      <p><?= htmlspecialchars((string) $errorStatus, ENT_QUOTES, 'UTF-8') ?></p>
      <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
      <p>Referencia de soporte: <code><?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?></code></p>
      <a href="<?= htmlspecialchars(defined('BASE_URL') ? BASE_URL . '/' : '/', ENT_QUOTES, 'UTF-8') ?>">Volver al inicio</a>
    </section>
  </main>
</body>
</html>
