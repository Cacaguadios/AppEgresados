<?php
/**
 * Instalador completo para Laragon
 * - Crea la base de datos bolsa_trabajo_utp
 * - Crea el esquema compatible con la app actual
 * - Inserta usuarios, egresados, ofertas y postulaciones de prueba
 *
 * Uso recomendado:
 *   php database/setup_laragon.php
 *
 * ATENCIÓN: este script recrea la base de datos desde cero.
 */

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

function env_value(array $names, $default) {
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

function pdo_options(): array {
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
}

function connect_server(array $config): PDO {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=utf8mb4',
        $config['host'],
        $config['port']
    );

    return new PDO($dsn, $config['user'], $config['pass'], pdo_options());
}

function connect_database(array $config): PDO {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['name']
    );

    return new PDO($dsn, $config['user'], $config['pass'], pdo_options());
}

function create_database(PDO $pdo, string $dbName): void {
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $dbName)
    ));
    $pdo->exec(sprintf('USE `%s`', str_replace('`', '``', $dbName)));
}

function drop_database(PDO $pdo, string $dbName): void {
    $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', str_replace('`', '``', $dbName)));
}

function exec_sql(PDO $pdo, string $sql): void {
    $pdo->exec($sql);
}

function exec_statements(PDO $pdo, array $statements): void {
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
    }
}

function insert_row(PDO $pdo, string $table, array $data): int {
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');

    $sql = sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (%s)',
        $table,
        implode('`, `', $columns),
        implode(', ', $placeholders)
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    return (int) $pdo->lastInsertId();
}

function json_text(array $value): string {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$config = [
    'host' => env_value(['APP_DB_HOST', 'DB_HOST'], '127.0.0.1'),
    'port' => env_value(['APP_DB_PORT', 'DB_PORT'], '3306'),
    'user' => env_value(['APP_DB_USER', 'DB_USER'], 'root'),
    'pass' => env_value(['APP_DB_PASS', 'DB_PASS'], ''),
    'name' => env_value(['APP_DB_NAME', 'DB_NAME'], 'bolsa_trabajo_utp'),
];

$server = connect_server($config);

printf("Recreating database %s...\n", $config['name']);
drop_database($server, $config['name']);
create_database($server, $config['name']);
$db = connect_database($config);

exec_statements($db, [
    'SET NAMES utf8mb4',
    'SET FOREIGN_KEY_CHECKS = 0',

    "CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NULL,
    email VARCHAR(255) NOT NULL,
    contraseña VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(255) NULL,
    tipo_usuario ENUM('admin','docente','ti','egresado') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    requiere_cambio_pass TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_login DATETIME NULL,
    verificacion_estado ENUM('pendiente','verificado','rechazado') NOT NULL DEFAULT 'pendiente',
    verificacion_motivo_rechazo TEXT NULL,
    verificacion_fecha DATETIME NULL,
    email_institucional VARCHAR(255) NULL,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    email_verificado_registro DATETIME NULL,
    fecha_ultima_actualizacion_perfil DATETIME NULL,
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    KEY idx_usuarios_tipo (tipo_usuario),
    KEY idx_usuarios_verificacion (verificacion_estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE egresados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    matricula VARCHAR(20) NULL,
    curp VARCHAR(18) NULL,
    correo_personal VARCHAR(255) NULL,
    telefono VARCHAR(20) NULL,
    especialidad VARCHAR(255) NULL,
    generacion INT NULL,
    genero CHAR(1) NULL,
    `año_nacimiento` SMALLINT NULL,
    trabaja_actualmente TINYINT(1) NOT NULL DEFAULT 0,
    trabaja_en_ti TINYINT(1) NOT NULL DEFAULT 0,
    empresa_actual VARCHAR(255) NULL,
    puesto_actual VARCHAR(255) NULL,
    modalidad_trabajo ENUM('presencial','hibrido','remoto') NULL,
    jornada_trabajo ENUM('completo','parcial','freelance') NULL,
    ubicacion_trabajo VARCHAR(255) NULL,
    tipo_contrato ENUM('indefinido','temporal','proyecto','honorarios') NULL,
    fecha_inicio_empleo DATE NULL,
    rango_salarial VARCHAR(50) NULL,
    prestaciones TEXT NULL,
    anos_experiencia_ti VARCHAR(20) NULL,
    descripcion_experiencia TEXT NULL,
    habilidades TEXT NULL,
    habilidades_blandas TEXT NULL,
    fecha_actualizacion_seguimiento DATETIME NULL,
    fecha_proximo_recordatorio DATETIME NULL,
    recordatorio_visto TINYINT(1) NOT NULL DEFAULT 0,
    porcentaje_completitud INT NOT NULL DEFAULT 0,
    campo_adicional_1 VARCHAR(255) NULL,
    campo_adicional_2 VARCHAR(255) NULL,
    cv_path VARCHAR(255) NULL,
    UNIQUE KEY uq_egresados_usuario (id_usuario),
    UNIQUE KEY uq_egresados_matricula (matricula),
    UNIQUE KEY uq_egresados_curp (curp),
    KEY idx_egresados_generacion (generacion),
    KEY idx_egresados_recordatorio (recordatorio_visto, fecha_proximo_recordatorio),
    KEY idx_egresados_completitud (porcentaje_completitud),
    CONSTRAINT fk_egresados_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE ofertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario_creador INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    empresa VARCHAR(255) NULL,
    ubicacion VARCHAR(255) NULL,
    modalidad ENUM('presencial','remoto','hibrido') NULL DEFAULT 'presencial',
    jornada ENUM('completo','parcial','freelance') NULL DEFAULT 'completo',
    salario_min DECIMAL(10,2) NULL,
    salario_max DECIMAL(10,2) NULL,
    descripcion TEXT NULL,
    requisitos TEXT NULL,
    beneficios TEXT NULL,
    habilidades TEXT NULL,
    contacto VARCHAR(255) NULL,
    nombre_contacto VARCHAR(255) NULL,
    puesto_contacto VARCHAR(255) NULL,
    telefono_contacto VARCHAR(20) NULL,
    estado ENUM('pendiente_aprobacion','aprobada','rechazada') NOT NULL DEFAULT 'pendiente_aprobacion',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_baja DATETIME NULL,
    motivo_baja VARCHAR(255) NULL,
    estado_vacante ENUM('verde','amarillo','rojo') NOT NULL DEFAULT 'verde',
    vacantes INT NOT NULL DEFAULT 1,
    especialidad_requerida VARCHAR(255) NULL,
    experiencia_minima INT NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NULL,
    fecha_aprobacion DATETIME NULL,
    id_admin_aprobador INT NULL,
    razon_rechazo TEXT NULL,
    KEY idx_ofertas_estado (estado),
    KEY idx_ofertas_creador (id_usuario_creador),
    KEY idx_ofertas_activo (activo),
    KEY idx_ofertas_vacante (estado_vacante),
    CONSTRAINT fk_ofertas_creador FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_ofertas_aprobador FOREIGN KEY (id_admin_aprobador) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE postulaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_oferta INT NOT NULL,
    id_egresado INT NOT NULL,
    fecha_postulacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente','preseleccionado','contactado','rechazado') NOT NULL DEFAULT 'pendiente',
    validacion_automatica VARCHAR(20) NULL,
    mensaje TEXT NULL,
    retirada TINYINT(1) NOT NULL DEFAULT 0,
    fecha_retiro DATETIME NULL,
    UNIQUE KEY uq_postulacion (id_oferta, id_egresado),
    KEY idx_postulaciones_oferta (id_oferta),
    KEY idx_postulaciones_egresado (id_egresado),
    KEY idx_postulaciones_estado (estado),
    CONSTRAINT fk_postulaciones_oferta FOREIGN KEY (id_oferta) REFERENCES ofertas(id) ON DELETE CASCADE,
    CONSTRAINT fk_postulaciones_egresado FOREIGN KEY (id_egresado) REFERENCES egresados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('oferta_nueva','oferta_aprobada','oferta_rechazada','nueva_postulacion','postulacion_seleccionada','postulacion_rechazada','nuevo_usuario','general') NOT NULL DEFAULT 'general',
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT,
    url VARCHAR(500) DEFAULT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notificaciones_usuario (id_usuario),
    KEY idx_notificaciones_leida (id_usuario, leida),
    CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE codigos_verificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    tipo ENUM('registro','recuperacion') NOT NULL DEFAULT 'registro',
    usado TINYINT(1) NOT NULL DEFAULT 0,
    intentos INT NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    KEY idx_codigos_email_tipo (email, tipo),
    KEY idx_codigos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE email_verification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    email_verificado VARCHAR(255) NOT NULL,
    tipo_verificacion ENUM('registro','recuperacion','cambio_email') NOT NULL DEFAULT 'registro',
    codigo_usado VARCHAR(6) NOT NULL,
    ip_direccion VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_verificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_email_verification_usuario (usuario_id),
    KEY idx_email_verification_fecha (fecha_verificacion),
    CONSTRAINT fk_email_verification_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE postulacion_habilidades_blandas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_postulacion INT NOT NULL,
    habilidad VARCHAR(120) NOT NULL,
    cumple TINYINT(1) NULL,
    evaluado_por INT NULL,
    fecha_evaluacion DATETIME NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_postulacion_habilidad (id_postulacion, habilidad),
    KEY idx_postulacion_habilidad_postulacion (id_postulacion),
    KEY idx_postulacion_habilidad_evaluador (evaluado_por),
    CONSTRAINT fk_post_hb_postulacion FOREIGN KEY (id_postulacion) REFERENCES postulaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_hb_evaluador FOREIGN KEY (evaluado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    contexto TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'SET FOREIGN_KEY_CHECKS = 1',
]);

$now = new DateTimeImmutable('now');
$past = (new DateTimeImmutable('now'))->modify('-14 days');
$past2 = (new DateTimeImmutable('now'))->modify('-35 days');
$future = (new DateTimeImmutable('now'))->modify('+30 days');
$future2 = (new DateTimeImmutable('now'))->modify('+45 days');

$users = [];
$users['admin'] = insert_row($db, 'usuarios', [
    'usuario' => 'admin',
    'email' => 'admin@egresados.utp.edu.mx',
    'contraseña' => password_hash('Admin1234!', PASSWORD_BCRYPT),
    'nombre' => 'Administrador',
    'apellidos' => 'Sistema',
    'tipo_usuario' => 'admin',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $now->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'verificado',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => $now->format('Y-m-d H:i:s'),
    'email_institucional' => null,
    'email_verificado' => 1,
    'email_verificado_registro' => $now->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $now->format('Y-m-d H:i:s'),
]);

$users['maria'] = insert_row($db, 'usuarios', [
    'usuario' => 'maria.lopez',
    'email' => 'maria.lopez@utp.edu.mx',
    'contraseña' => password_hash('Docente123!', PASSWORD_BCRYPT),
    'nombre' => 'María',
    'apellidos' => 'López Hernández',
    'tipo_usuario' => 'docente',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $past->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'verificado',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => $past->format('Y-m-d H:i:s'),
    'email_institucional' => 'maria.lopez@utp.edu.mx',
    'email_verificado' => 1,
    'email_verificado_registro' => $past->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $past->format('Y-m-d H:i:s'),
]);

$users['pedro'] = insert_row($db, 'usuarios', [
    'usuario' => 'pedro.garcia',
    'email' => 'pedro.garcia@utpuebla.edu.mx',
    'contraseña' => password_hash('Docente123!', PASSWORD_BCRYPT),
    'nombre' => 'Pedro',
    'apellidos' => 'García Díaz',
    'tipo_usuario' => 'docente',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $past->modify('-2 days')->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'verificado',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => $past->modify('-2 days')->format('Y-m-d H:i:s'),
    'email_institucional' => 'pedro.garcia@utpuebla.edu.mx',
    'email_verificado' => 1,
    'email_verificado_registro' => $past->modify('-2 days')->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $past->modify('-2 days')->format('Y-m-d H:i:s'),
]);

$users['carlos'] = insert_row($db, 'usuarios', [
    'usuario' => 'carlos.anzurez',
    'email' => 'carlos.anzurez@utpuebla.edu.mx',
    'contraseña' => password_hash('Carlos1234!', PASSWORD_BCRYPT),
    'nombre' => 'Carlos',
    'apellidos' => 'Anzures Pérez',
    'tipo_usuario' => 'ti',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $past->modify('-1 day')->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'verificado',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => $past->modify('-1 day')->format('Y-m-d H:i:s'),
    'email_institucional' => 'carlos.anzurez@utpuebla.edu.mx',
    'email_verificado' => 1,
    'email_verificado_registro' => $past->modify('-1 day')->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $past->modify('-1 day')->format('Y-m-d H:i:s'),
]);

$users['juan'] = insert_row($db, 'usuarios', [
    'usuario' => 'juan.perez',
    'email' => 'juan.perez@egresados.utp.edu.mx',
    'contraseña' => password_hash('Juan1234!', PASSWORD_BCRYPT),
    'nombre' => 'Juan',
    'apellidos' => 'Pérez García',
    'tipo_usuario' => 'egresado',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $past2->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'verificado',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => $past2->format('Y-m-d H:i:s'),
    'email_institucional' => null,
    'email_verificado' => 1,
    'email_verificado_registro' => $past2->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $past2->format('Y-m-d H:i:s'),
]);

$users['test'] = insert_row($db, 'usuarios', [
    'usuario' => 'test.egresado',
    'email' => 'test.egresado@egresados.utp.edu.mx',
    'contraseña' => password_hash('Test1234!', PASSWORD_BCRYPT),
    'nombre' => 'Test',
    'apellidos' => 'Egresado',
    'tipo_usuario' => 'egresado',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $past->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'verificado',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => $past->format('Y-m-d H:i:s'),
    'email_institucional' => null,
    'email_verificado' => 1,
    'email_verificado_registro' => $past->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $past->format('Y-m-d H:i:s'),
]);

$users['ana'] = insert_row($db, 'usuarios', [
    'usuario' => 'ana.ortiz',
    'email' => 'ana.ortiz@egresados.utp.edu.mx',
    'contraseña' => password_hash('Ana1234!', PASSWORD_BCRYPT),
    'nombre' => 'Ana',
    'apellidos' => 'Ortiz Rivera',
    'tipo_usuario' => 'egresado',
    'activo' => 1,
    'requiere_cambio_pass' => 0,
    'fecha_creacion' => $past->modify('-5 days')->format('Y-m-d H:i:s'),
    'fecha_ultima_login' => null,
    'verificacion_estado' => 'pendiente',
    'verificacion_motivo_rechazo' => null,
    'verificacion_fecha' => null,
    'email_institucional' => null,
    'email_verificado' => 1,
    'email_verificado_registro' => $past->modify('-5 days')->format('Y-m-d H:i:s'),
    'fecha_ultima_actualizacion_perfil' => $past2->format('Y-m-d H:i:s'),
]);

$egresados = [];
$egresados['juan'] = insert_row($db, 'egresados', [
    'id_usuario' => $users['juan'],
    'matricula' => '2023010001',
    'curp' => 'PEJG000101HPLRRN01',
    'correo_personal' => 'juan.perez@gmail.com',
    'telefono' => '2225550001',
    'especialidad' => 'Desarrollo de Software',
    'generacion' => 2023,
    'genero' => 'M',
    'año_nacimiento' => 2000,
    'trabaja_actualmente' => 1,
    'trabaja_en_ti' => 1,
    'empresa_actual' => 'Softtek Puebla',
    'puesto_actual' => 'Desarrollador Frontend Jr',
    'modalidad_trabajo' => 'hibrido',
    'jornada_trabajo' => 'completo',
    'ubicacion_trabajo' => 'Puebla, Puebla',
    'tipo_contrato' => 'indefinido',
    'fecha_inicio_empleo' => '2025-08-15',
    'rango_salarial' => '12001-18000',
    'prestaciones' => json_text(['IMSS', 'Vales de despensa', 'Aguinaldo']),
    'anos_experiencia_ti' => '1-2',
    'descripcion_experiencia' => 'Experiencia en React, Node.js y PHP en proyectos web.',
    'habilidades' => json_text(['React', 'JavaScript', 'Node.js', 'PHP', 'MySQL']),
    'habilidades_blandas' => json_text(['Trabajo en equipo', 'Comunicación', 'Resolución de problemas']),
    'fecha_actualizacion_seguimiento' => $past->format('Y-m-d H:i:s'),
    'fecha_proximo_recordatorio' => $future->format('Y-m-d H:i:s'),
    'recordatorio_visto' => 0,
    'porcentaje_completitud' => 88,
    'campo_adicional_1' => null,
    'campo_adicional_2' => null,
    'cv_path' => null,
]);

$egresados['test'] = insert_row($db, 'egresados', [
    'id_usuario' => $users['test'],
    'matricula' => '2023010002',
    'curp' => 'AERT000202HPLRRN02',
    'correo_personal' => 'test.egresado@gmail.com',
    'telefono' => '2225550002',
    'especialidad' => 'Redes y Telecomunicaciones',
    'generacion' => 2022,
    'genero' => 'M',
    'año_nacimiento' => 2001,
    'trabaja_actualmente' => 0,
    'trabaja_en_ti' => 0,
    'empresa_actual' => null,
    'puesto_actual' => null,
    'modalidad_trabajo' => null,
    'jornada_trabajo' => null,
    'ubicacion_trabajo' => null,
    'tipo_contrato' => null,
    'fecha_inicio_empleo' => null,
    'rango_salarial' => null,
    'prestaciones' => null,
    'anos_experiencia_ti' => '0-1',
    'descripcion_experiencia' => 'Perfil de prueba con información parcial para disparar el recordatorio.',
    'habilidades' => json_text(['Windows', 'Redes', 'Hardware']),
    'habilidades_blandas' => json_text(['Puntualidad', 'Atención al cliente']),
    'fecha_actualizacion_seguimiento' => $past2->format('Y-m-d H:i:s'),
    'fecha_proximo_recordatorio' => $past2->format('Y-m-d H:i:s'),
    'recordatorio_visto' => 0,
    'porcentaje_completitud' => 41,
    'campo_adicional_1' => null,
    'campo_adicional_2' => null,
    'cv_path' => null,
]);

$egresados['ana'] = insert_row($db, 'egresados', [
    'id_usuario' => $users['ana'],
    'matricula' => '2024010003',
    'curp' => 'ORRA010303MPLRRN03',
    'correo_personal' => 'ana.ortiz@gmail.com',
    'telefono' => '2225550003',
    'especialidad' => 'Inteligencia Artificial',
    'generacion' => 2024,
    'genero' => 'F',
    'año_nacimiento' => 2002,
    'trabaja_actualmente' => 0,
    'trabaja_en_ti' => 0,
    'empresa_actual' => null,
    'puesto_actual' => null,
    'modalidad_trabajo' => null,
    'jornada_trabajo' => null,
    'ubicacion_trabajo' => null,
    'tipo_contrato' => null,
    'fecha_inicio_empleo' => null,
    'rango_salarial' => null,
    'prestaciones' => null,
    'anos_experiencia_ti' => null,
    'descripcion_experiencia' => null,
    'habilidades' => json_text(['Python', 'SQL', 'Pandas']),
    'habilidades_blandas' => json_text(['Aprendizaje rápido', 'Trabajo en equipo']),
    'fecha_actualizacion_seguimiento' => null,
    'fecha_proximo_recordatorio' => $future2->format('Y-m-d H:i:s'),
    'recordatorio_visto' => 0,
    'porcentaje_completitud' => 28,
    'campo_adicional_1' => null,
    'campo_adicional_2' => null,
    'cv_path' => null,
]);

$ofertas = [];
$ofertas['fullstack'] = insert_row($db, 'ofertas', [
    'id_usuario_creador' => $users['maria'],
    'titulo' => 'Desarrollador Full Stack Junior',
    'empresa' => 'TechSolutions México',
    'ubicacion' => 'Guadalajara, Jalisco',
    'modalidad' => 'hibrido',
    'jornada' => 'completo',
    'salario_min' => 15000.00,
    'salario_max' => 20000.00,
    'descripcion' => 'Puesto para desarrollar soluciones web con React y Node.js.',
    'requisitos' => json_text(['React', 'Node.js', 'Git', 'SQL']),
    'beneficios' => json_text(['Seguro médico', 'Vales de despensa', 'Home office flexible']),
    'habilidades' => json_text(['React', 'Node.js', 'JavaScript', 'MySQL']),
    'contacto' => 'reclutamiento@techsolutions.mx',
    'nombre_contacto' => 'Laura Gómez',
    'puesto_contacto' => 'RH',
    'telefono_contacto' => '2221110001',
    'estado' => 'aprobada',
    'activo' => 1,
    'fecha_baja' => null,
    'motivo_baja' => null,
    'estado_vacante' => 'amarillo',
    'vacantes' => 2,
    'especialidad_requerida' => 'Desarrollo de Software',
    'experiencia_minima' => 1,
    'fecha_creacion' => $past->format('Y-m-d H:i:s'),
    'fecha_expiracion' => $future2->format('Y-m-d H:i:s'),
    'fecha_aprobacion' => $past->format('Y-m-d H:i:s'),
    'id_admin_aprobador' => $users['admin'],
    'razon_rechazo' => null,
]);

$ofertas['data'] = insert_row($db, 'ofertas', [
    'id_usuario_creador' => $users['pedro'],
    'titulo' => 'Analista de Datos',
    'empresa' => 'DataInsights Corp',
    'ubicacion' => 'Ciudad de México',
    'modalidad' => 'remoto',
    'jornada' => 'completo',
    'salario_min' => 18000.00,
    'salario_max' => 25000.00,
    'descripcion' => 'Análisis y visualización de datos para equipos de negocio.',
    'requisitos' => json_text(['SQL', 'Python', 'Power BI']),
    'beneficios' => json_text(['Trabajo remoto', 'Bono de productividad', 'Seguro de vida']),
    'habilidades' => json_text(['Python', 'SQL', 'Power BI', 'Excel']),
    'contacto' => 'hr@datainsights.com.mx',
    'nombre_contacto' => 'Natalia Ruiz',
    'puesto_contacto' => 'Talent Acquisition',
    'telefono_contacto' => '2221110002',
    'estado' => 'aprobada',
    'activo' => 1,
    'fecha_baja' => null,
    'motivo_baja' => null,
    'estado_vacante' => 'verde',
    'vacantes' => 1,
    'especialidad_requerida' => 'Desarrollo de Software',
    'experiencia_minima' => 0,
    'fecha_creacion' => $past->modify('-3 days')->format('Y-m-d H:i:s'),
    'fecha_expiracion' => $future->format('Y-m-d H:i:s'),
    'fecha_aprobacion' => $past->modify('-3 days')->format('Y-m-d H:i:s'),
    'id_admin_aprobador' => $users['admin'],
    'razon_rechazo' => null,
]);

$ofertas['soporte'] = insert_row($db, 'ofertas', [
    'id_usuario_creador' => $users['carlos'],
    'titulo' => 'Soporte Técnico TI',
    'empresa' => 'Universidad Tecnológica de Puebla',
    'ubicacion' => 'Puebla, Puebla',
    'modalidad' => 'presencial',
    'jornada' => 'completo',
    'salario_min' => 10000.00,
    'salario_max' => 14000.00,
    'descripcion' => 'Soporte a usuarios, redes y mantenimiento de equipos.',
    'requisitos' => json_text(['Windows Server', 'Redes LAN/WAN', 'Disponibilidad inmediata']),
    'beneficios' => json_text(['Prestaciones de ley', 'Comedor subsidiado']),
    'habilidades' => json_text(['Windows', 'Redes', 'Hardware', 'Atención al cliente']),
    'contacto' => 'ti@utpuebla.edu.mx',
    'nombre_contacto' => 'Ricardo Méndez',
    'puesto_contacto' => 'Coordinación TI',
    'telefono_contacto' => '2221110003',
    'estado' => 'aprobada',
    'activo' => 1,
    'fecha_baja' => null,
    'motivo_baja' => null,
    'estado_vacante' => 'rojo',
    'vacantes' => 0,
    'especialidad_requerida' => 'Redes y Telecomunicaciones',
    'experiencia_minima' => 0,
    'fecha_creacion' => $past->modify('-5 days')->format('Y-m-d H:i:s'),
    'fecha_expiracion' => $past->modify('+20 days')->format('Y-m-d H:i:s'),
    'fecha_aprobacion' => $past->modify('-5 days')->format('Y-m-d H:i:s'),
    'id_admin_aprobador' => $users['admin'],
    'razon_rechazo' => null,
]);

$ofertas['pendiente'] = insert_row($db, 'ofertas', [
    'id_usuario_creador' => $users['maria'],
    'titulo' => 'Desarrollador Frontend Angular',
    'empresa' => 'Accenture México',
    'ubicacion' => 'Puebla, Puebla',
    'modalidad' => 'hibrido',
    'jornada' => 'completo',
    'salario_min' => 17000.00,
    'salario_max' => 24000.00,
    'descripcion' => 'Frontend con Angular para proyecto bancario.',
    'requisitos' => json_text(['Angular', 'TypeScript', 'RxJS']),
    'beneficios' => json_text(['Seguro médico', 'Academia interna', 'Certificaciones']),
    'habilidades' => json_text(['Angular', 'TypeScript', 'HTML', 'CSS']),
    'contacto' => 'reclutamiento.mx@accenture.com',
    'nombre_contacto' => 'Andrea Salas',
    'puesto_contacto' => 'RH',
    'telefono_contacto' => '2221110004',
    'estado' => 'pendiente_aprobacion',
    'activo' => 1,
    'fecha_baja' => null,
    'motivo_baja' => null,
    'estado_vacante' => 'verde',
    'vacantes' => 3,
    'especialidad_requerida' => 'Desarrollo de Software',
    'experiencia_minima' => 1,
    'fecha_creacion' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
    'fecha_expiracion' => $future2->format('Y-m-d H:i:s'),
    'fecha_aprobacion' => null,
    'id_admin_aprobador' => null,
    'razon_rechazo' => null,
]);

$ofertas['rechazada'] = insert_row($db, 'ofertas', [
    'id_usuario_creador' => $users['carlos'],
    'titulo' => 'Técnico de Soporte (medio tiempo)',
    'empresa' => 'Cyber Café Digital',
    'ubicacion' => 'Puebla, Puebla',
    'modalidad' => 'presencial',
    'jornada' => 'parcial',
    'salario_min' => 4000.00,
    'salario_max' => 6000.00,
    'descripcion' => 'Oferta de prueba con salario bajo para validar rechazo.',
    'requisitos' => json_text(['Conocimientos básicos de computación']),
    'beneficios' => json_text(['Horario flexible']),
    'habilidades' => json_text(['Windows', 'Hardware', 'Impresoras']),
    'contacto' => 'contacto@cyberdigital.mx',
    'nombre_contacto' => 'Jorge León',
    'puesto_contacto' => 'Dueño',
    'telefono_contacto' => '2221110005',
    'estado' => 'rechazada',
    'activo' => 1,
    'fecha_baja' => null,
    'motivo_baja' => null,
    'estado_vacante' => 'verde',
    'vacantes' => 1,
    'especialidad_requerida' => null,
    'experiencia_minima' => 0,
    'fecha_creacion' => $past->modify('-7 days')->format('Y-m-d H:i:s'),
    'fecha_expiracion' => $past->modify('+23 days')->format('Y-m-d H:i:s'),
    'fecha_aprobacion' => null,
    'id_admin_aprobador' => $users['admin'],
    'razon_rechazo' => 'La oferta no cumple con las condiciones mínimas para egresados.',
]);

$postulaciones = [];
$postulaciones[] = insert_row($db, 'postulaciones', [
    'id_oferta' => $ofertas['fullstack'],
    'id_egresado' => $egresados['juan'],
    'fecha_postulacion' => $past->modify('-2 days')->format('Y-m-d H:i:s'),
    'estado' => 'preseleccionado',
    'validacion_automatica' => 'cumple',
    'mensaje' => null,
    'retirada' => 0,
    'fecha_retiro' => null,
]);

$postulaciones[] = insert_row($db, 'postulaciones', [
    'id_oferta' => $ofertas['data'],
    'id_egresado' => $egresados['test'],
    'fecha_postulacion' => $past->modify('-1 day')->format('Y-m-d H:i:s'),
    'estado' => 'pendiente',
    'validacion_automatica' => 'cumple',
    'mensaje' => null,
    'retirada' => 0,
    'fecha_retiro' => null,
]);

$postulaciones[] = insert_row($db, 'postulaciones', [
    'id_oferta' => $ofertas['data'],
    'id_egresado' => $egresados['ana'],
    'fecha_postulacion' => $now->format('Y-m-d H:i:s'),
    'estado' => 'pendiente',
    'validacion_automatica' => 'cumple',
    'mensaje' => null,
    'retirada' => 0,
    'fecha_retiro' => null,
]);

$postulaciones[] = insert_row($db, 'postulaciones', [
    'id_oferta' => $ofertas['soporte'],
    'id_egresado' => $egresados['juan'],
    'fecha_postulacion' => $past->modify('-5 days')->format('Y-m-d H:i:s'),
    'estado' => 'contactado',
    'validacion_automatica' => 'cumple',
    'mensaje' => null,
    'retirada' => 0,
    'fecha_retiro' => null,
]);

$postulaciones[] = insert_row($db, 'postulaciones', [
    'id_oferta' => $ofertas['fullstack'],
    'id_egresado' => $egresados['test'],
    'fecha_postulacion' => $past->modify('-6 days')->format('Y-m-d H:i:s'),
    'estado' => 'rechazado',
    'validacion_automatica' => 'no_cumple',
    'mensaje' => null,
    'retirada' => 0,
    'fecha_retiro' => null,
]);

$postulaciones[] = insert_row($db, 'postulaciones', [
    'id_oferta' => $ofertas['data'],
    'id_egresado' => $egresados['juan'],
    'fecha_postulacion' => $past->modify('-9 days')->format('Y-m-d H:i:s'),
    'estado' => 'pendiente',
    'validacion_automatica' => 'cumple',
    'mensaje' => null,
    'retirada' => 0,
    'fecha_retiro' => null,
]);

foreach ($postulaciones as $postulacionId) {
    exec_sql($db, sprintf(
        "INSERT INTO postulacion_habilidades_blandas (id_postulacion, habilidad, cumple, evaluado_por, fecha_evaluacion) VALUES\n        (%d, 'Trabajo en equipo', NULL, NULL, NULL),\n        (%d, 'Comunicación', NULL, NULL, NULL)",
        $postulacionId,
        $postulacionId
    ));
}

$notifications = [
    [$users['admin'], 'general', 'Base de datos lista', 'Se creó la base de prueba con usuarios, ofertas y postulaciones.', '../../views/admin/inicio.php'],
    [$users['maria'], 'nueva_postulacion', 'Nuevo postulante', 'Juan Pérez García se postuló a tu oferta "Desarrollador Full Stack Junior".', '../../views/docente/postulantes.php'],
    [$users['carlos'], 'nueva_postulacion', 'Nuevo postulante', 'Test Egresado se postuló a una oferta administrada por TI.', '../../views/docente/postulantes.php'],
    [$users['juan'], 'oferta_nueva', 'Nueva oferta disponible', 'Hay una nueva vacante que coincide con tu perfil.', '../../views/egresado/ofertas.php'],
    [$users['test'], 'oferta_nueva', 'Nueva oferta disponible', 'Revisa las vacantes activas para tu perfil.', '../../views/egresado/ofertas.php'],
    [$users['ana'], 'general', 'Recordatorio de perfil', 'Completa tu información laboral para activar el recordatorio de seguimiento.', '../../views/egresado/perfil.php'],
];

foreach ($notifications as [$idUsuario, $tipo, $titulo, $mensaje, $url]) {
    insert_row($db, 'notificaciones', [
        'id_usuario' => $idUsuario,
        'tipo' => $tipo,
        'titulo' => $titulo,
        'mensaje' => $mensaje,
        'url' => $url,
        'leida' => 0,
        'fecha_creacion' => $now->format('Y-m-d H:i:s'),
    ]);
}

insert_row($db, 'audit_logs', [
    'tipo' => 'setup_laragon',
    'descripcion' => 'Base de datos inicializada correctamente para pruebas locales.',
    'contexto' => json_text([
        'database' => $config['name'],
        'admin_user' => 'admin',
        'docente_user' => 'maria.lopez',
        'egresado_user' => 'juan.perez',
    ]),
    'fecha_creacion' => $now->format('Y-m-d H:i:s'),
]);

$summary = [
    'usuarios' => count($users),
    'egresados' => count($egresados),
    'ofertas' => count($ofertas),
    'postulaciones' => count($postulaciones),
    'notificaciones' => count($notifications),
];

echo "Database created successfully.\n\n";
echo "Test credentials:\n";
echo "- admin / Admin1234!\n";
echo "- maria.lopez / Docente123!\n";
echo "- pedro.garcia / Docente123!\n";
echo "- carlos.anzurez / Carlos1234!\n";
echo "- juan.perez / Juan1234!\n";
echo "- test.egresado / Test1234!\n";
echo "- ana.ortiz / Ana1234!\n\n";
echo "Summary:\n";
foreach ($summary as $label => $count) {
    echo sprintf("- %s: %d\n", $label, $count);
}

echo "\nRecommended URLs:\n";
echo "- http://localhost/AppEgresados/views/auth/login.php\n";
echo "- http://localhost/AppEgresados/public/index.php\n";
echo "\nIf you use another MySQL host or port, set APP_DB_HOST / APP_DB_PORT before running this script.\n";
