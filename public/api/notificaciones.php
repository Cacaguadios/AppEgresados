<?php
/**
 * API de Notificaciones - Acceso directo
 * Redirige al controlador de notificaciones
 */
require_once __DIR__ . '/../../app/helpers/Http.php';
api_bootstrap(__FILE__);
require_once __DIR__ . '/../../app/controllers/NotificacionController.php';
