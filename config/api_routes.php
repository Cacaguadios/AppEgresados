<?php
/**
 * Politicas declarativas de todos los endpoints fisicos de public/api.
 * Los permisos de propiedad de recursos se validan despues de cargarlos.
 */

return [
    'exportar-egresados.php' => [
        'methods' => ['GET'],
        'roles' => ['admin'],
        'csrf' => false,
    ],
    'feedback-postulacion.php' => [
        'methods' => ['POST'],
        'roles' => ['admin', 'docente', 'ti', 'egresado'],
        'csrf' => true,
    ],
    'invitaciones.php' => [
        'methods' => ['POST'],
        'roles' => ['docente', 'ti', 'egresado'],
        'csrf' => true,
    ],
    'marcar-recordatorio.php' => [
        'methods' => ['GET', 'POST'],
        'roles' => ['egresado'],
        'csrf' => true,
    ],
    'notificaciones.php' => [
        'methods' => ['GET', 'POST'],
        'roles' => ['admin', 'docente', 'ti', 'egresado'],
        'csrf' => true,
    ],
    'ofertas-update.php' => [
        'methods' => ['POST'],
        'roles' => ['admin', 'docente', 'ti', 'egresado'],
        'csrf' => true,
    ],
    'postulaciones-update.php' => [
        'methods' => ['POST'],
        'roles' => ['admin', 'docente', 'ti', 'egresado'],
        'csrf' => true,
    ],
    'reportes.php' => [
        'methods' => ['GET'],
        'roles' => ['admin'],
        'csrf' => false,
    ],
];
