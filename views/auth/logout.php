<?php
require_once __DIR__ . '/../../config/application.php';
/**
 * Logout – Cierra la sesión y redirige al login
 */
$baseUrl = '/AppEgresados';

app_logout();

// Redirigir al login
header('Location: ' . $baseUrl . '/login');
exit;
