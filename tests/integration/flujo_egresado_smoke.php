<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/models/Egresado.php';
require_once __DIR__ . '/../../app/models/Oferta.php';
require_once __DIR__ . '/../../app/models/Postulacion.php';

function fail(string $message): void
{
    fwrite(STDERR, "[FAIL] {$message}" . PHP_EOL);
    exit(1);
}

function pass(string $message): void
{
    echo "[PASS] {$message}" . PHP_EOL;
}

function skip(string $message): void
{
    echo "[SKIP] {$message}" . PHP_EOL;
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fail($message);
    }
    pass($message);
}

function connect(): PDO
{
    return new PDO(
        'mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = connect();
    echo "Validando flujo de egresado contra la BD real..." . PHP_EOL;

    foreach (['egresados', 'ofertas', 'postulaciones'] as $table) {
        assertTrue(tableExists($pdo, $table), "Existe la tabla {$table}");
    }

    assertTrue(tableExists($pdo, 'postulacion_habilidades_blandas'), 'Existe la tabla postulacion_habilidades_blandas');

    $columnChecks = [
        ['egresados', 'habilidades_blandas'],
        ['egresados', 'fecha_proximo_recordatorio'],
        ['egresados', 'fecha_actualizacion_seguimiento'],
        ['ofertas', 'activo'],
        ['ofertas', 'fecha_baja'],
        ['ofertas', 'motivo_baja'],
        ['ofertas', 'vacantes'],
        ['postulaciones', 'retirada'],
        ['postulaciones', 'fecha_retiro'],
    ];

    foreach ($columnChecks as [$table, $column]) {
        assertTrue(columnExists($pdo, $table, $column), "Existe la columna {$table}.{$column}");
    }

    $egresadoRow = $pdo->query('SELECT id_usuario FROM egresados ORDER BY id ASC LIMIT 1')->fetch();
    if ($egresadoRow) {
        $egresadoModel = new Egresado();
        $egresado = $egresadoModel->getByUsuarioId((int)$egresadoRow['id_usuario']);

        assertTrue(is_array($egresado) && !empty($egresado), 'Se puede cargar un egresado real por usuario_id');

        $completitud = $egresadoModel->calcularCompletudinformacion($egresado);
        assertTrue(isset($completitud['porcentaje']) && $completitud['porcentaje'] >= 0 && $completitud['porcentaje'] <= 100, 'La completitud se calcula en un rango válido');

        $estadoRecordatorio = $egresadoModel->obtenerEstadoRecordatorio((int)$egresadoRow['id_usuario']);
        assertTrue(is_array($estadoRecordatorio) && array_key_exists('debe_mostrar', $estadoRecordatorio), 'El estado del recordatorio devuelve estructura válida');
        assertTrue(array_key_exists('razon', $estadoRecordatorio), 'El estado del recordatorio incluye el motivo');
    } else {
        skip('No hay egresados en la base; se omite la validación de instancia real');
    }

    $ofertaModel = new Oferta();
    $ofertasActivas = $ofertaModel->getApprovedAndActive();
    assertTrue(is_array($ofertasActivas), 'getApprovedAndActive devuelve un arreglo');

    foreach ($ofertasActivas as $oferta) {
        assertTrue((int)($oferta['activo'] ?? 0) === 1, 'Las ofertas activas vienen con activo=1');
        assertTrue((int)($oferta['vacantes'] ?? 0) > 0, 'Las ofertas activas vienen con vacantes > 0');
    }

    $ofertaRow = $pdo->query("SELECT id FROM ofertas WHERE estado = 'aprobada' AND activo = 1 AND vacantes > 0 ORDER BY id ASC LIMIT 1")->fetch();
    if ($ofertaRow) {
        $oferta = $ofertaModel->getById((int)$ofertaRow['id']);
        assertTrue(is_array($oferta) && !empty($oferta), 'Se puede cargar una oferta aprobada y activa');
    } else {
        skip('No hay ofertas aprobadas y activas para validar getById en una oferta viva');
    }

    if ($egresadoRow) {
        $postulacionModel = new Postulacion();
        $postulaciones = $postulacionModel->getByEgresadoId((int)($pdo->query('SELECT id FROM egresados ORDER BY id ASC LIMIT 1')->fetchColumn()));
        assertTrue(is_array($postulaciones), 'getByEgresadoId devuelve un arreglo');

        if (!empty($postulaciones)) {
            $primera = $postulaciones[0];
            assertTrue(array_key_exists('oferta_habilidades', $primera), 'La postulación incluye habilidades de la oferta');
            assertTrue(array_key_exists('vacantes', $primera), 'La postulación incluye vacantes');
            assertTrue(array_key_exists('estado_vacante', $primera), 'La postulación incluye estado de vacante');
        } else {
            skip('El egresado de prueba no tiene postulaciones; se omite la validación del detalle de postulación');
        }
    }

    echo PHP_EOL . 'Smoke test completado correctamente.' . PHP_EOL;
} catch (Throwable $e) {
    fail('Excepción durante el smoke test: ' . $e->getMessage());
}