<?php
/**
 * Fix notificaciones table + insert seed notifications
 */
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Recreate table with correct schema
$pdo->exec("DROP TABLE IF EXISTS notificaciones");
$pdo->exec("
    CREATE TABLE notificaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        tipo ENUM('oferta_nueva','oferta_aprobada','oferta_rechazada','nueva_postulacion','postulacion_seleccionada','postulacion_rechazada','nuevo_usuario','general') NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        mensaje TEXT,
        url VARCHAR(500) DEFAULT NULL,
        leida TINYINT(1) NOT NULL DEFAULT 0,
        fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_usuario_leida (id_usuario, leida),
        INDEX idx_fecha (fecha_creacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Table recreated OK\n";

// 2. Insert notifications
$notifs = [
    // ── ADMIN (id=1) ──
    [1, 'nueva_postulacion', 'Nueva oferta por moderar', 'Pedro García publicó la oferta "Desarrollador Frontend Angular". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 0, '2026-02-22 10:01:00'],
    [1, 'nueva_postulacion', 'Nueva oferta por moderar', 'Carlos López publicó la oferta "Administrador de Redes y Servidores". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 0, '2026-02-23 08:31:00'],
    [1, 'nueva_postulacion', 'Nueva oferta por moderar', 'María López publicó la oferta "Ingeniero de Machine Learning". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 0, '2026-02-24 09:01:00'],
    [1, 'nueva_postulacion', 'Nueva oferta por moderar', 'Carlos López publicó la oferta "Técnico de Soporte (medio tiempo)". Revísala para aprobarla.', '../../views/admin/moderacion/list.php', 1, '2026-02-20 15:01:00'],

    // ── DOCENTE María López (id=5) ──
    [5, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Desarrollador Mobile React Native" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-07 15:01:00'],
    [5, 'nueva_postulacion', 'Nuevo postulante', 'Jose Hernandez se postuló a tu oferta "Desarrollador Mobile React Native".', '../../views/docente/postulantes.php', 0, '2026-02-08 13:01:00'],
    [5, 'nueva_postulacion', 'Nuevo postulante', 'Juan Pérez García se postuló a tu oferta "Desarrollador Mobile React Native".', '../../views/docente/postulantes.php', 0, '2026-02-09 10:31:00'],
    [5, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Desarrollador Full Stack Junior" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-01-16 10:01:00'],
    [5, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Hernández se postuló a tu oferta "Desarrollador Full Stack Junior".', '../../views/docente/postulantes.php', 1, '2026-01-20 14:31:00'],

    // ── DOCENTE Pedro García (id=12) ──
    [12, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Desarrollador Backend Python" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-12 10:31:00'],
    [12, 'nueva_postulacion', 'Nuevo postulante', 'Mauricio Popoca se postuló a tu oferta "Desarrollador Backend Python".', '../../views/docente/postulantes.php', 0, '2026-02-13 14:01:00'],
    [12, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Hernández se postuló a tu oferta "Desarrollador Backend Python".', '../../views/docente/postulantes.php', 0, '2026-02-14 11:01:00'],
    [12, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Data Scientist" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 0, '2026-02-16 10:01:00'],
    [12, 'nueva_postulacion', 'Nuevo postulante', 'Mauricio Popoca se postuló a tu oferta "Data Scientist".', '../../views/docente/postulantes.php', 0, '2026-02-17 11:31:00'],
    [12, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Cuaya se postuló a tu oferta "Data Scientist".', '../../views/docente/postulantes.php', 0, '2026-02-18 08:31:00'],
    [12, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Ingeniero de Infraestructura Cloud" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 0, '2026-02-20 08:31:00'],
    [12, 'nueva_postulacion', 'Nuevo postulante', 'Omar Anzures Campos se postuló a tu oferta "Ingeniero de Infraestructura Cloud".', '../../views/docente/postulantes.php', 0, '2026-02-21 16:01:00'],
    [12, 'nueva_postulacion', 'Nuevo postulante', 'Juan Pérez García se postuló a tu oferta "Ingeniero de Infraestructura Cloud".', '../../views/docente/postulantes.php', 0, '2026-02-22 09:16:00'],

    // ── TI Carlos López (id=13) ──
    [13, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "Analista de Ciberseguridad Jr" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-10 09:01:00'],
    [13, 'nueva_postulacion', 'Nuevo postulante', 'Omar Anzures Campos se postuló a tu oferta "Analista de Ciberseguridad Jr".', '../../views/docente/postulantes.php', 0, '2026-02-11 09:46:00'],
    [13, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Hernández se postuló a tu oferta "Analista de Ciberseguridad Jr".', '../../views/docente/postulantes.php', 0, '2026-02-12 08:01:00'],
    [13, 'oferta_aprobada', 'Tu oferta fue aprobada', 'La oferta "QA Automation Engineer" ha sido aprobada y ya es visible para los egresados.', '../../views/docente/mis-ofertas.php', 1, '2026-02-14 11:31:00'],
    [13, 'nueva_postulacion', 'Nuevo postulante', 'Jose Hernandez se postuló a tu oferta "QA Automation Engineer".', '../../views/docente/postulantes.php', 0, '2026-02-15 10:21:00'],
    [13, 'nueva_postulacion', 'Nuevo postulante', 'Carlos Cuaya se postuló a tu oferta "QA Automation Engineer".', '../../views/docente/postulantes.php', 0, '2026-02-16 15:46:00'],
    [13, 'oferta_rechazada', 'Oferta rechazada', 'La oferta "Técnico de Soporte (medio tiempo)" fue rechazada. Motivo: La oferta no cumple con los estándares mínimos de salario.', '../../views/docente/mis-ofertas.php', 0, '2026-02-21 09:00:00'],

    // ── EGRESADO test.egresado (id=4) ──
    [4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Backend Python". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=6', 1, '2026-02-12 10:31:00'],
    [4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Analista de Ciberseguridad Jr". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=7', 1, '2026-02-10 09:01:00'],
    [4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Mobile React Native". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=8', 1, '2026-02-07 15:01:00'],
    [4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Data Scientist". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=9', 0, '2026-02-16 10:01:00'],
    [4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "QA Automation Engineer". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=10', 0, '2026-02-14 11:31:00'],
    [4, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00'],
    [4, 'postulacion_rechazada', 'Postulación no seleccionada', 'Tu postulación para "Analista de Ciberseguridad Jr" no fue seleccionada en esta ocasión.', '../../views/egresado/postulaciones.php', 0, '2026-02-13 10:00:00'],

    // ── EGRESADO Mauricio (id=6) ──
    [6, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Backend Python". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=6', 1, '2026-02-12 10:31:00'],
    [6, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Data Scientist". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=9', 0, '2026-02-16 10:01:00'],
    [6, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00'],

    // ── EGRESADO Omar (id=7) ──
    [7, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Analista de Ciberseguridad Jr". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=7', 1, '2026-02-10 09:01:00'],
    [7, 'postulacion_seleccionada', '¡Has sido seleccionado!', '¡Felicidades! Fuiste seleccionado para la oferta "Analista de Ciberseguridad Jr".', '../../views/egresado/postulaciones.php', 0, '2026-02-15 14:00:00'],
    [7, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00'],

    // ── EGRESADO Jose (id=8) ──
    [8, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "QA Automation Engineer". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=10', 0, '2026-02-14 11:31:00'],
    [8, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Mobile React Native". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=8', 0, '2026-02-07 15:01:00'],

    // ── EGRESADO Carlos Cuaya (id=11) ──
    [11, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Data Scientist". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=9', 1, '2026-02-16 10:01:00'],
    [11, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "QA Automation Engineer". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=10', 0, '2026-02-14 11:31:00'],
    [11, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00'],

    // ── EGRESADO Juan Pérez (id=3) ──
    [3, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Desarrollador Mobile React Native". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=8', 1, '2026-02-07 15:01:00'],
    [3, 'oferta_nueva', '¡Nueva oferta disponible!', 'Se publicó la oferta "Ingeniero de Infraestructura Cloud". ¡Revísala y postúlate!', '../../views/egresado/oferta-detalle.php?id=11', 0, '2026-02-20 08:31:00'],
];

$stmt = $pdo->prepare("INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?)");

$ok = 0;
foreach ($notifs as $n) {
    $stmt->execute($n);
    $ok++;
}

echo "Notifications inserted: {$ok}\n";
echo "Done!\n";
