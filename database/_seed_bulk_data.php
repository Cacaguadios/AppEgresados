<?php
/**
 * Seed masivo para pruebas de flujo completo.
 *
 * Uso:
 *   php database/_seed_bulk_data.php
 */

$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

mt_srand(20260422);

echo "=== SEED MASIVO (30+) ===" . PHP_EOL;

function pick(array $values, int $index)
{
    return $values[$index % count($values)];
}

$firstNames = [
    'Luis', 'Mariana', 'Jose', 'Andrea', 'Carlos', 'Fernanda', 'Miguel', 'Sofia', 'Ricardo', 'Valeria',
    'Daniel', 'Karla', 'Hector', 'Paula', 'Jorge', 'Natalia', 'Diego', 'Camila', 'Oscar', 'Alejandra',
    'Ivan', 'Gabriela', 'Emilio', 'Ximena', 'Ruben', 'Daniela', 'Marco', 'Ana', 'Raul', 'Elena',
];

$lastNames1 = [
    'Garcia', 'Hernandez', 'Lopez', 'Martinez', 'Gonzalez', 'Perez', 'Rodriguez', 'Sanchez', 'Ramirez', 'Torres',
    'Flores', 'Rivera', 'Gomez', 'Diaz', 'Vazquez', 'Cruz', 'Morales', 'Reyes', 'Ortiz', 'Castro',
    'Ruiz', 'Mendoza', 'Alvarez', 'Romero', 'Herrera', 'Medina', 'Aguilar', 'Silva', 'Navarro', 'Rojas',
];

$lastNames2 = [
    'Jimenez', 'Moreno', 'Gutierrez', 'Molina', 'Soto', 'Ramos', 'Delgado', 'Campos', 'Vega', 'Ibarra',
    'Salinas', 'Cortes', 'Fuentes', 'Valdez', 'Serrano', 'Luna', 'Padilla', 'Acosta', 'Nuñez', 'Mejia',
    'Pineda', 'Camacho', 'Rosales', 'Escobar', 'Ortega', 'Macias', 'Esquivel', 'Mora', 'Bravo', 'Montes',
];

$especialidades = [
    'Desarrollo de Software',
    'Redes y Telecomunicaciones',
    'Inteligencia Artificial',
    'Ciberseguridad',
    'Base de Datos',
    'Multimedia y Diseño',
];

$modalidades = ['presencial', 'hibrido', 'remoto'];
$jornadas = ['completo', 'parcial', 'freelance'];
$contratos = ['indefinido', 'temporal', 'proyecto', 'honorarios'];
$rangos = ['9000-12000', '12001-18000', '18001-25000', '25001-35000'];
$estadosPost = ['pendiente', 'preseleccionado', 'contactado', 'rechazado'];
$estadosInv = ['pendiente', 'visto', 'aceptado', 'rechazado'];
$estadosVacante = ['verde', 'amarillo', 'rojo'];

$skillsPool = [
    'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Node.js', 'MySQL', 'PostgreSQL', 'Docker',
    'Linux', 'Git', 'AWS', 'Azure', 'Python', 'Java', 'C#', 'Power BI', 'Excel', 'Figma',
];

$benefitsPool = [
    'Prestaciones de ley',
    'Seguro de gastos medicos',
    'Vales de despensa',
    'Capacitacion continua',
    'Home office',
    'Horario flexible',
    'Bono trimestral',
    'Seguro de vida',
];

$positions = [
    'Desarrollador Junior',
    'Desarrollador Full Stack',
    'Analista de Datos',
    'Soporte Tecnico TI',
    'QA Tester',
    'DevOps Engineer',
    'Disenador UX/UI',
    'Analista BI',
    'Administrador de Redes',
    'Backend Developer',
];

$companies = [
    'TechNova', 'DataPulse', 'CloudWorks', 'InnovaSoft', 'CodeFactory',
    'VisionApps', 'BlueStack', 'NetBridge', 'DigitalMind', 'NexaLabs',
];

$cities = [
    'Puebla, Puebla',
    'Ciudad de Mexico',
    'Guadalajara, Jalisco',
    'Monterrey, Nuevo Leon',
    'Queretaro, Queretaro',
];

try {
    $adminRow = $pdo->query("SELECT id FROM usuarios WHERE tipo_usuario = 'admin' ORDER BY id ASC LIMIT 1")->fetch();
    if (!$adminRow) {
        throw new RuntimeException('No existe usuario admin para aprobar ofertas.');
    }
    $adminId = (int) $adminRow['id'];

    $docentes = $pdo->query("SELECT id FROM usuarios WHERE tipo_usuario = 'docente' ORDER BY id ASC")->fetchAll();
    if (count($docentes) === 0) {
        $insertDoc = $pdo->prepare("INSERT INTO usuarios (usuario, email, `contraseña`, nombre, apellidos, tipo_usuario, activo, requiere_cambio_pass) VALUES (?, ?, ?, ?, ?, 'docente', 1, 0)");
        $hashDoc = password_hash('Docente123!', PASSWORD_BCRYPT);
        for ($i = 1; $i <= 2; $i++) {
            $insertDoc->execute([
                'docente.demo' . $i,
                'docente.demo' . $i . '@utp.edu.mx',
                $hashDoc,
                'Docente',
                'Demo ' . $i,
            ]);
        }
        $docentes = $pdo->query("SELECT id FROM usuarios WHERE tipo_usuario = 'docente' ORDER BY id ASC")->fetchAll();
    }

    $insertUser = $pdo->prepare("INSERT INTO usuarios (usuario, email, `contraseña`, nombre, apellidos, tipo_usuario, activo, requiere_cambio_pass, verificacion_estado, email_verificado) VALUES (?, ?, ?, ?, ?, 'egresado', 1, 0, 'verificado', 1)");
    $insertEgresado = $pdo->prepare("INSERT INTO egresados (id_usuario, matricula, curp, correo_personal, telefono, especialidad, generacion, genero, `año_nacimiento`, trabaja_actualmente, trabaja_en_ti, empresa_actual, puesto_actual, modalidad_trabajo, jornada_trabajo, ubicacion_trabajo, tipo_contrato, fecha_inicio_empleo, rango_salarial, prestaciones, anos_experiencia_ti, descripcion_experiencia, habilidades, fecha_actualizacion_seguimiento, porcentaje_completitud) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");

    $hashEgresado = password_hash('Egresado123!', PASSWORD_BCRYPT);

    $createdUsers = 0;
    for ($i = 1; $i <= 30; $i++) {
        $username = sprintf('egresado.demo%02d', $i);
        $email = sprintf('egresado.demo%02d@egresados.utp.edu.mx', $i);

        $existingUser = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
        $existingUser->execute([$username]);
        $userRow = $existingUser->fetch();

        if (!$userRow) {
            $nombre = pick($firstNames, $i - 1);
            $apellidos = pick($lastNames1, $i - 1) . ' ' . pick($lastNames2, $i - 1);
            $insertUser->execute([$username, $email, $hashEgresado, $nombre, $apellidos]);
            $userId = (int) $pdo->lastInsertId();
            $createdUsers++;
        } else {
            $userId = (int) $userRow['id'];
        }

        $checkEgresado = $pdo->prepare("SELECT id FROM egresados WHERE id_usuario = ? LIMIT 1");
        $checkEgresado->execute([$userId]);
        if (!$checkEgresado->fetch()) {
            $especialidad = pick($especialidades, $i - 1);
            $modalidad = pick($modalidades, $i - 1);
            $jornada = pick($jornadas, $i - 1);
            $contrato = pick($contratos, $i - 1);
            $rango = pick($rangos, $i - 1);
            $year = 2020 + ($i % 7);
            $nacimiento = 1995 + ($i % 8);
            $trabaja = ($i % 3 !== 0) ? 1 : 0;
            $trabajaTi = ($i % 4 !== 0) ? 1 : 0;
            $empresa = pick($companies, $i - 1) . ' MX';
            $puesto = pick($positions, $i - 1);
            $skills = json_encode([
                pick($skillsPool, $i - 1),
                pick($skillsPool, $i + 2),
                pick($skillsPool, $i + 5),
                pick($skillsPool, $i + 8),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $prestaciones = json_encode([
                pick($benefitsPool, $i - 1),
                pick($benefitsPool, $i + 2),
                pick($benefitsPool, $i + 4),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $insertEgresado->execute([
                $userId,
                sprintf('2026%06d', $i),
                sprintf('DEMO%06dHPLABC%02d', $i, $i % 90),
                sprintf('contacto.demo%02d@gmail.com', $i),
                sprintf('222-700-%04d', $i),
                $especialidad,
                $year,
                ($i % 2 === 0) ? 'M' : 'H',
                $nacimiento,
                $trabaja,
                $trabajaTi,
                $trabaja ? $empresa : null,
                $trabaja ? $puesto : null,
                $trabaja ? $modalidad : null,
                $trabaja ? $jornada : null,
                pick($cities, $i - 1),
                $trabaja ? $contrato : null,
                $trabaja ? sprintf('2025-%02d-%02d', (($i % 12) + 1), (($i % 27) + 1)) : null,
                $rango,
                $prestaciones,
                (string) ($i % 5),
                'Perfil generado para pruebas masivas del sistema.',
                $skills,
                70 + ($i % 31),
            ]);
        }
    }

    // Limpiar datos transaccionales para mantener un escenario de prueba estable.
    $pdo->exec('DELETE FROM invitaciones');
    $pdo->exec('DELETE FROM postulaciones');
    $pdo->exec('DELETE FROM ofertas');
    $pdo->exec('ALTER TABLE ofertas AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE postulaciones AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE invitaciones AUTO_INCREMENT = 1');

    $egresados = $pdo->query("SELECT e.id, e.id_usuario, u.usuario FROM egresados e JOIN usuarios u ON u.id = e.id_usuario WHERE u.tipo_usuario = 'egresado' ORDER BY e.id ASC")->fetchAll();
    $docenteIds = array_map(static fn($d) => (int) $d['id'], $docentes);

    $insertOferta = $pdo->prepare(
        "INSERT INTO ofertas (id_usuario_creador, titulo, empresa, ubicacion, modalidad, jornada, salario_min, salario_max, descripcion, requisitos, beneficios, habilidades, contacto, estado, activo, estado_vacante, vacantes, especialidad_requerida, experiencia_minima, fecha_expiracion, fecha_aprobacion, id_admin_aprobador) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aprobada', 1, ?, ?, ?, ?, ?, NOW(), ?)"
    );

    $ofertaIds = [];
    for ($i = 1; $i <= 30; $i++) {
        $creator = $docenteIds[($i - 1) % count($docenteIds)];
        $position = pick($positions, $i - 1);
        $company = pick($companies, $i - 1) . ' ' . (2020 + ($i % 7));
        $city = pick($cities, $i - 1);
        $modalidad = pick($modalidades, $i - 1);
        $jornada = pick($jornadas, $i - 1);
        $esp = pick($especialidades, $i - 1);
        $estadoVacante = pick($estadosVacante, $i - 1);
        $salMin = 10000 + ($i * 500);
        $salMax = $salMin + 6000;
        $requisitos = json_encode([
            'TSU o Ingenieria afin',
            'Conocimiento de ' . pick($skillsPool, $i - 1),
            'Manejo de ' . pick($skillsPool, $i + 1),
            'Trabajo en equipo',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $beneficios = json_encode([
            pick($benefitsPool, $i - 1),
            pick($benefitsPool, $i + 1),
            pick($benefitsPool, $i + 3),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $habilidades = json_encode([
            pick($skillsPool, $i - 1),
            pick($skillsPool, $i + 2),
            pick($skillsPool, $i + 4),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $insertOferta->execute([
            $creator,
            $position . ' Demo ' . $i,
            $company,
            $city,
            $modalidad,
            $jornada,
            $salMin,
            $salMax,
            'Vacante de prueba generada automaticamente para validar filtros, flujo de postulacion e invitaciones.',
            $requisitos,
            $beneficios,
            $habilidades,
            sprintf('reclutamiento%02d@demo.mx', $i),
            $estadoVacante,
            ($i % 4) + 1,
            $esp,
            $i % 3,
            sprintf('2026-%02d-%02d 23:59:59', (($i % 12) + 1), (($i % 27) + 1)),
            $adminId,
        ]);

        $ofertaIds[] = (int) $pdo->lastInsertId();
    }

    $insertPost = $pdo->prepare(
        "INSERT INTO postulaciones (id_oferta, id_egresado, fecha_postulacion, estado, validacion_automatica, mensaje, feedback_resultado) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $postCount = 0;
    $egCount = count($egresados);
    for ($i = 0; $i < count($ofertaIds); $i++) {
        $ofertaId = $ofertaIds[$i];
        for ($j = 0; $j < 2; $j++) {
            $egIndex = ($i * 2 + $j) % $egCount;
            $egresadoId = (int) $egresados[$egIndex]['id'];
            $estado = pick($estadosPost, $i + $j);
            $insertPost->execute([
                $ofertaId,
                $egresadoId,
                sprintf('2026-%02d-%02d %02d:%02d:00', (($i + 1) % 12) + 1, (($j + 10 + $i) % 27) + 1, 9 + ($j * 2), ($i * 3) % 60),
                $estado,
                ($estado === 'rechazado' || $estado === 'pendiente') ? 'no_cumple' : 'cumple',
                'Postulacion de prueba para validacion del flujo.',
                ($estado === 'contactado' || $estado === 'preseleccionado') ? 'pendiente' : null,
            ]);
            $postCount++;
        }
    }

    $insertInv = $pdo->prepare(
        "INSERT INTO invitaciones (id_oferta, id_docente, id_egresado, estado, fecha_invitacion, fecha_respuesta) VALUES (?, ?, ?, ?, ?, ?)"
    );

    $invCount = 0;
    for ($i = 0; $i < count($ofertaIds); $i++) {
        $ofertaId = $ofertaIds[$i];
        $docenteId = $docenteIds[$i % count($docenteIds)];
        $egresadoId = (int) $egresados[($i + 3) % $egCount]['id'];
        $estadoInv = pick($estadosInv, $i);
        $fechaInv = sprintf('2026-%02d-%02d %02d:%02d:00', (($i + 2) % 12) + 1, (($i + 5) % 27) + 1, 8 + ($i % 8), ($i * 7) % 60);
        $fechaResp = ($estadoInv === 'pendiente') ? null : sprintf('2026-%02d-%02d %02d:%02d:00', (($i + 3) % 12) + 1, (($i + 8) % 27) + 1, 10 + ($i % 6), ($i * 5) % 60);

        $insertInv->execute([
            $ofertaId,
            $docenteId,
            $egresadoId,
            $estadoInv,
            $fechaInv,
            $fechaResp,
        ]);
        $invCount++;
    }

    echo "OK: Usuarios egresado creados (nuevos): {$createdUsers}" . PHP_EOL;
    echo "OK: Ofertas insertadas: " . count($ofertaIds) . PHP_EOL;
    echo "OK: Postulaciones insertadas: {$postCount}" . PHP_EOL;
    echo "OK: Invitaciones insertadas: {$invCount}" . PHP_EOL;
    echo PHP_EOL;
    echo "Login demo: egresado.demo01 / Egresado123!" . PHP_EOL;
    echo "Login demo docente: maria.lopez / Docente123!" . PHP_EOL;
    echo "=== SEED MASIVO COMPLETADO ===" . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
