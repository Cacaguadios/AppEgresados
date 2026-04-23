<?php
/**
 * Seed: Insert test data for all egresado pages
 * Run once: php database/_seed_test_data.php
 */
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "=== SEEDING TEST DATA ===" . PHP_EOL;

// ====================================================
// 1. Create a docente user (to be offers creator)
// ====================================================
$existDocente = $pdo->query("SELECT id FROM usuarios WHERE usuario = 'maria.lopez'")->fetch();
if (!$existDocente) {
    $pdo->exec("INSERT INTO usuarios (usuario, email, contraseña, nombre, apellidos, tipo_usuario, activo, requiere_cambio_pass)
        VALUES (
            'maria.lopez',
            'maria.lopez@utp.edu.mx',
            '" . password_hash('Docente123!', PASSWORD_BCRYPT) . "',
            'María',
            'López Hernández',
            'docente',
            1,
            0
        )");
    $docenteId = $pdo->lastInsertId();
    echo "OK: Created docente user ID=$docenteId (maria.lopez)" . PHP_EOL;
} else {
    $docenteId = $existDocente['id'];
    echo "SKIP: Docente user already exists ID=$docenteId" . PHP_EOL;
}

// Get admin ID
$adminRow = $pdo->query("SELECT id FROM usuarios WHERE tipo_usuario = 'admin' LIMIT 1")->fetch();
$adminId = $adminRow ? $adminRow['id'] : 1;

// ====================================================
// 2. Resolve egresado users and update their profiles
// ====================================================
$testUserRow = $pdo->query("SELECT id FROM usuarios WHERE usuario = 'test.egresado' LIMIT 1")->fetch();
$juanUserRow = $pdo->query("SELECT id FROM usuarios WHERE usuario = 'juan.perez' LIMIT 1")->fetch();

if (!$testUserRow || !$juanUserRow) {
    throw new RuntimeException('No se encontraron usuarios base test.egresado y/o juan.perez en la tabla usuarios.');
}

$testUserId = (int) $testUserRow['id'];
$juanUserId = (int) $juanUserRow['id'];

// Ensure egresado rows exist for both users.
$pdo->exec("INSERT INTO egresados (id_usuario)
    SELECT {$testUserId}
    WHERE NOT EXISTS (SELECT 1 FROM egresados WHERE id_usuario = {$testUserId})");

$pdo->exec("INSERT INTO egresados (id_usuario)
    SELECT {$juanUserId}
    WHERE NOT EXISTS (SELECT 1 FROM egresados WHERE id_usuario = {$juanUserId})");

$pdo->exec("UPDATE egresados SET
    correo_personal  = 'carlos.hdz@gmail.com',
    genero           = 'M',
    año_nacimiento   = 2001,
    telefono         = '222-555-1234',
    especialidad     = 'Desarrollo de Software',
    generacion       = 2023,
    trabaja_actualmente = 1,
    trabaja_en_ti    = 1,
    empresa_actual   = 'Softtek Puebla',
    puesto_actual    = 'Desarrollador Frontend Jr',
    modalidad_trabajo = 'hibrido',
    jornada_trabajo  = 'completo',
    ubicacion_trabajo = 'Puebla, Puebla',
    tipo_contrato    = 'indefinido',
    fecha_inicio_empleo = '2025-08-15',
    rango_salarial   = '12001-18000',
    prestaciones     = '[\"IMSS\",\"Vales de despensa\",\"Aguinaldo\",\"Vacaciones\"]',
    anos_experiencia_ti = '1-2',
    descripcion_experiencia = 'He trabajado como desarrollador frontend con React y Vue.js en proyectos de comercio electrónico. También tengo experiencia en backend con Node.js y PHP.',
    habilidades      = '[\"React\",\"JavaScript\",\"Node.js\",\"PHP\",\"MySQL\",\"Git\"]',
    fecha_actualizacion_seguimiento = NOW()
WHERE id_usuario = {$testUserId}");
echo "OK: Updated egresado profile for test.egresado (usuario_id={$testUserId})" . PHP_EOL;

// Also update the other egresado (juan.perez)
$pdo->exec("UPDATE egresados SET
    correo_personal  = 'juan.perez.garcia@gmail.com',
    genero           = 'M',
    año_nacimiento   = 2000,
    telefono         = '222-333-9876',
    especialidad     = 'Redes y Telecomunicaciones',
    generacion       = 2022,
    trabaja_actualmente = 0,
    trabaja_en_ti    = 0,
    habilidades      = '[\"Windows\",\"Redes\",\"Hardware\",\"Cisco\",\"Linux\"]'
WHERE id_usuario = {$juanUserId}");
echo "OK: Updated egresado profile for juan.perez (usuario_id={$juanUserId})" . PHP_EOL;

// ====================================================
// 3. Insert 5 Ofertas (approved, with varied statuses)
// ====================================================
$ofertas = [
    [
        'id_usuario_creador' => $docenteId,
        'titulo' => 'Desarrollador Full Stack Junior',
        'empresa' => 'TechSolutions México',
        'ubicacion' => 'Guadalajara, Jalisco',
        'modalidad' => 'hibrido',
        'jornada' => 'completo',
        'salario_min' => 15000.00,
        'salario_max' => 20000.00,
        'descripcion' => 'Buscamos desarrollador Full Stack junior con conocimientos en React y Node.js para unirse a nuestro equipo de desarrollo. Participarás en proyectos innovadores para clientes del sector fintech. Ambiente de trabajo colaborativo y oportunidades de crecimiento.',
        'requisitos' => json_encode([
            'Licenciatura en Ingeniería en Sistemas o afín',
            'Conocimientos en React y Node.js',
            'Experiencia mínima de 1 año',
            'Inglés intermedio'
        ]),
        'beneficios' => json_encode([
            'Seguro de gastos médicos mayores',
            'Vales de despensa',
            'Capacitación continua',
            'Home office flexible'
        ]),
        'habilidades' => json_encode(['React', 'Node.js', 'JavaScript', 'MySQL']),
        'contacto' => 'reclutamiento@techsolutions.mx',
        'estado' => 'aprobada',
        'estado_vacante' => 'amarillo',
        'vacantes' => 2,
        'especialidad_requerida' => 'Desarrollo de Software',
        'experiencia_minima' => 1,
        'fecha_expiracion' => '2026-03-15 00:00:00',
        'fecha_aprobacion' => '2026-01-16 10:00:00',
        'id_admin_aprobador' => $adminId,
    ],
    [
        'id_usuario_creador' => $docenteId,
        'titulo' => 'Analista de Datos',
        'empresa' => 'DataInsights Corp',
        'ubicacion' => 'Ciudad de México',
        'modalidad' => 'remoto',
        'jornada' => 'completo',
        'salario_min' => 18000.00,
        'salario_max' => 25000.00,
        'descripcion' => 'Se requiere analista de datos para procesar y visualizar información de ventas y operaciones. Colaborarás con equipos multidisciplinarios para generar insights accionables que impulsen la toma de decisiones estratégicas.',
        'requisitos' => json_encode([
            'Ingeniería en Sistemas, Estadística o afín',
            'Dominio de SQL y Python',
            'Experiencia con Power BI o Tableau',
            'Conocimientos de estadística aplicada'
        ]),
        'beneficios' => json_encode([
            'Trabajo 100% remoto',
            'Bono de productividad trimestral',
            'Seguro de vida',
            'Días personales adicionales'
        ]),
        'habilidades' => json_encode(['Python', 'SQL', 'Power BI', 'Excel']),
        'contacto' => 'hr@datainsights.com.mx',
        'estado' => 'aprobada',
        'estado_vacante' => 'verde',
        'vacantes' => 1,
        'especialidad_requerida' => 'Desarrollo de Software',
        'experiencia_minima' => 0,
        'fecha_expiracion' => '2026-03-18 00:00:00',
        'fecha_aprobacion' => '2026-01-19 09:30:00',
        'id_admin_aprobador' => $adminId,
    ],
    [
        'id_usuario_creador' => $adminId,
        'titulo' => 'Soporte Técnico TI',
        'empresa' => 'Universidad Tecnológica de Puebla',
        'ubicacion' => 'Puebla, Puebla',
        'modalidad' => 'presencial',
        'jornada' => 'completo',
        'salario_min' => 10000.00,
        'salario_max' => 14000.00,
        'descripcion' => 'Puesto de soporte técnico para el departamento de TI de la universidad. Incluye mantenimiento de equipos, soporte a usuarios, gestión de redes locales y documentación de incidencias.',
        'requisitos' => json_encode([
            'TSU o Ingeniería en TI, Redes o afín',
            'Manejo de Windows Server y Active Directory',
            'Conocimientos de redes LAN/WAN',
            'Disponibilidad inmediata'
        ]),
        'beneficios' => json_encode([
            'Prestaciones de ley',
            'Comedor subsidiado',
            'Capacitación interna'
        ]),
        'habilidades' => json_encode(['Windows', 'Redes', 'Hardware', 'Atención al cliente']),
        'contacto' => 'ti@utpuebla.edu.mx',
        'estado' => 'aprobada',
        'estado_vacante' => 'rojo',
        'vacantes' => 0,
        'especialidad_requerida' => 'Redes y Telecomunicaciones',
        'experiencia_minima' => 0,
        'fecha_expiracion' => '2026-02-10 00:00:00',
        'fecha_aprobacion' => '2026-01-11 08:00:00',
        'id_admin_aprobador' => $adminId,
    ],
    [
        'id_usuario_creador' => $docenteId,
        'titulo' => 'DevOps Engineer',
        'empresa' => 'CloudTech Solutions',
        'ubicacion' => 'Monterrey, Nuevo León',
        'modalidad' => 'remoto',
        'jornada' => 'completo',
        'salario_min' => 25000.00,
        'salario_max' => 35000.00,
        'descripcion' => 'Buscamos un ingeniero DevOps para automatizar pipelines de CI/CD, gestionar infraestructura cloud y mejorar los procesos de deployment. Se trabaja con tecnologías de punta y metodología ágil.',
        'requisitos' => json_encode([
            'Ingeniería en Sistemas o afín',
            'Experiencia con AWS o Azure',
            'Conocimientos de Docker y Kubernetes',
            'Manejo de herramientas CI/CD (Jenkins, GitHub Actions)',
            'Mínimo 2 años de experiencia'
        ]),
        'beneficios' => json_encode([
            'Trabajo remoto permanente',
            'Seguro de gastos médicos mayores',
            'Presupuesto anual para capacitación',
            'Stock options',
            'Horario flexible'
        ]),
        'habilidades' => json_encode(['AWS', 'Docker', 'Kubernetes', 'Jenkins', 'Linux']),
        'contacto' => 'talent@cloudtech.mx',
        'estado' => 'aprobada',
        'estado_vacante' => 'verde',
        'vacantes' => 3,
        'especialidad_requerida' => 'Desarrollo de Software',
        'experiencia_minima' => 2,
        'fecha_expiracion' => '2026-04-01 00:00:00',
        'fecha_aprobacion' => '2026-01-20 14:00:00',
        'id_admin_aprobador' => $adminId,
    ],
    [
        'id_usuario_creador' => $docenteId,
        'titulo' => 'Diseñador UX/UI',
        'empresa' => 'CreativeApps Studio',
        'ubicacion' => 'Puebla, Puebla',
        'modalidad' => 'hibrido',
        'jornada' => 'completo',
        'salario_min' => 16000.00,
        'salario_max' => 22000.00,
        'descripcion' => 'Estudio de desarrollo de apps móviles busca diseñador UX/UI para crear interfaces intuitivas y atractivas. Trabajarás de la mano con desarrolladores y product managers en proyectos para startups.',
        'requisitos' => json_encode([
            'Licenciatura en Diseño, Multimedia o afín',
            'Dominio de Figma o Sketch',
            'Portafolio de proyectos UX/UI',
            'Conocimientos básicos de HTML/CSS'
        ]),
        'beneficios' => json_encode([
            'Horario flexible',
            'Viernes corto',
            'Seguro dental',
            'Snacks y café ilimitados'
        ]),
        'habilidades' => json_encode(['Figma', 'Adobe XD', 'HTML', 'CSS', 'Prototyping']),
        'contacto' => 'jobs@creativeapps.mx',
        'estado' => 'aprobada',
        'estado_vacante' => 'verde',
        'vacantes' => 1,
        'especialidad_requerida' => 'Multimedia y Diseño',
        'experiencia_minima' => 0,
        'fecha_expiracion' => '2026-03-30 00:00:00',
        'fecha_aprobacion' => '2026-02-01 11:00:00',
        'id_admin_aprobador' => $adminId,
    ],
];

// Clear existing ofertas for clean seed
$pdo->exec("DELETE FROM postulaciones");
$pdo->exec("DELETE FROM ofertas");
$pdo->exec("ALTER TABLE ofertas AUTO_INCREMENT = 1");
$pdo->exec("ALTER TABLE postulaciones AUTO_INCREMENT = 1");

$ofertaIds = [];
foreach ($ofertas as $i => $oferta) {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($oferta)));
    $phs  = implode(', ', array_fill(0, count($oferta), '?'));
    $stmt = $pdo->prepare("INSERT INTO ofertas ($cols) VALUES ($phs)");
    $stmt->execute(array_values($oferta));
    $ofertaIds[] = $pdo->lastInsertId();
    echo "OK: Inserted oferta #{$ofertaIds[$i]}: {$oferta['titulo']}" . PHP_EOL;
}

// ====================================================
// 4. Insert Postulaciones for test.egresado and juan.perez
// ====================================================
$egresadoRow = $pdo->query("SELECT id FROM egresados WHERE id_usuario = {$testUserId} LIMIT 1")->fetch();
$egresadoId = $egresadoRow ? (int) $egresadoRow['id'] : null;

$egresado2Row = $pdo->query("SELECT id FROM egresados WHERE id_usuario = {$juanUserId} LIMIT 1")->fetch();
$egresado2Id = $egresado2Row ? (int) $egresado2Row['id'] : null;

if (!$egresadoId || !$egresado2Id) {
    throw new RuntimeException('No se pudieron resolver los registros de egresados para test.egresado y/o juan.perez.');
}

$postulaciones = [
    // test.egresado applied to offer 1 (Full Stack) - preseleccionado
    [
        'id_oferta' => $ofertaIds[0],
        'id_egresado' => $egresadoId,
        'fecha_postulacion' => '2026-01-20 14:30:00',
        'estado' => 'preseleccionado',
        'validacion_automatica' => 'cumple',
    ],
    // test.egresado applied to offer 2 (Analista) - pendiente
    [
        'id_oferta' => $ofertaIds[1],
        'id_egresado' => $egresadoId,
        'fecha_postulacion' => '2026-02-01 09:15:00',
        'estado' => 'pendiente',
        'validacion_automatica' => 'no_cumple',
    ],
    // test.egresado applied to offer 3 (Soporte) - rechazado
    [
        'id_oferta' => $ofertaIds[2],
        'id_egresado' => $egresadoId,
        'fecha_postulacion' => '2026-01-12 16:00:00',
        'estado' => 'rechazado',
        'validacion_automatica' => 'no_cumple',
        'mensaje' => 'Vacante ya cubierta antes de revisar postulación.',
    ],
    // test.egresado applied to offer 5 (UX/UI) - contactado
    [
        'id_oferta' => $ofertaIds[4],
        'id_egresado' => $egresadoId,
        'fecha_postulacion' => '2026-02-05 10:00:00',
        'estado' => 'contactado',
        'validacion_automatica' => 'cumple',
        'feedback_resultado' => 'pendiente',
    ],
    // juan.perez applied to offer 1 (Full Stack) - pendiente
    [
        'id_oferta' => $ofertaIds[0],
        'id_egresado' => $egresado2Id,
        'fecha_postulacion' => '2026-01-22 11:00:00',
        'estado' => 'pendiente',
        'validacion_automatica' => 'no_cumple',
    ],
];

foreach ($postulaciones as $p) {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($p)));
    $phs  = implode(', ', array_fill(0, count($p), '?'));
    $stmt = $pdo->prepare("INSERT INTO postulaciones ($cols) VALUES ($phs)");
    $stmt->execute(array_values($p));
    echo "OK: Inserted postulación egresado_id={$p['id_egresado']} → oferta_id={$p['id_oferta']} ({$p['estado']})" . PHP_EOL;
}

echo PHP_EOL . "=== SEED COMPLETE ===" . PHP_EOL;
echo "Users: admin(ID=$adminId), docente(ID=$docenteId), egresados({$egresadoId},{$egresado2Id})" . PHP_EOL;
echo "Ofertas: " . count($ofertaIds) . " inserted (IDs: " . implode(',', $ofertaIds) . ")" . PHP_EOL;
echo "Postulaciones: " . count($postulaciones) . " inserted" . PHP_EOL;
echo PHP_EOL . "Login: test.egresado / Test1234!" . PHP_EOL;
