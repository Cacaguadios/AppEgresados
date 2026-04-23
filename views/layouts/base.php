<?php
/**
 * Layout base con Bootstrap
 * Incluye todos los estilos y scripts necesarios
 */

if (!defined('ASSETS_URL')) {
    require_once __DIR__ . '/../../config/bootstrap.php';
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($title) ? htmlspecialchars($title) : 'AppEgresados UTP' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= htmlspecialchars(ASSETS_URL . '/css/auth.css') ?>" rel="stylesheet">
    <?php if (isset($customCss)): ?>
        <link href="<?= htmlspecialchars($customCss) ?>" rel="stylesheet">
    <?php endif; ?>

    <style>
        :root {
            --utp-red: #7A1501;
            --utp-green: #00C247;
            --bg: #FAFAFA;
            --text: #121212;
            --muted: #757575;
        }
    </style>
</head>
<body>
    <!-- El contenido va aquí -->
    <?php
    if (isset($content)) {
        echo $content;
    }
    ?>

    <!-- Bootstrap JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    <script src="<?= htmlspecialchars(ASSETS_URL . '/js/app.js') ?>"></script>
    <?php if (isset($customJs)): ?>
        <script src="<?= htmlspecialchars($customJs) ?>"></script>
    <?php endif; ?>
</body>
</html>
