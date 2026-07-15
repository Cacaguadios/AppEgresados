<?php
/**
 * API de Notificaciones - Acceso directo
 * Redirige al controlador de notificaciones
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../app/controllers/NotificacionController.php';
