<?php
$pdo = new PDO('mysql:host=localhost;dbname=bolsa_trabajo_utp;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// ==========================================
// 1. Add columns to OFERTAS
// ==========================================
$alterOfertas = [
    "ALTER TABLE ofertas ADD COLUMN empresa VARCHAR(255) NULL AFTER titulo",
    "ALTER TABLE ofertas ADD COLUMN ubicacion VARCHAR(255) NULL AFTER empresa",
    "ALTER TABLE ofertas ADD COLUMN modalidad ENUM('presencial','remoto','hibrido') NULL DEFAULT 'presencial' AFTER ubicacion",
    "ALTER TABLE ofertas ADD COLUMN jornada ENUM('completo','parcial','freelance') NULL DEFAULT 'completo' AFTER modalidad",
    "ALTER TABLE ofertas ADD COLUMN salario_min DECIMAL(10,2) NULL AFTER jornada",
    "ALTER TABLE ofertas ADD COLUMN salario_max DECIMAL(10,2) NULL AFTER salario_min",
    "ALTER TABLE ofertas ADD COLUMN beneficios TEXT NULL AFTER salario_max",
    "ALTER TABLE ofertas ADD COLUMN habilidades TEXT NULL AFTER beneficios",
];

// ==========================================
// 2. Add seguimiento columns to EGRESADOS
// ==========================================
$alterEgresados = [
    "ALTER TABLE egresados ADD COLUMN correo_personal VARCHAR(255) NULL AFTER curp",
    "ALTER TABLE egresados ADD COLUMN trabaja_actualmente TINYINT(1) NOT NULL DEFAULT 0 AFTER correo_personal",
    "ALTER TABLE egresados ADD COLUMN trabaja_en_ti TINYINT(1) NOT NULL DEFAULT 0 AFTER trabaja_actualmente",
    "ALTER TABLE egresados ADD COLUMN empresa_actual VARCHAR(255) NULL AFTER trabaja_en_ti",
    "ALTER TABLE egresados ADD COLUMN puesto_actual VARCHAR(255) NULL AFTER empresa_actual",
    "ALTER TABLE egresados ADD COLUMN modalidad_trabajo ENUM('presencial','hibrido','remoto') NULL AFTER puesto_actual",
    "ALTER TABLE egresados ADD COLUMN jornada_trabajo ENUM('completo','parcial','freelance') NULL AFTER modalidad_trabajo",
    "ALTER TABLE egresados ADD COLUMN ubicacion_trabajo VARCHAR(255) NULL AFTER jornada_trabajo",
    "ALTER TABLE egresados ADD COLUMN tipo_contrato ENUM('indefinido','temporal','proyecto','honorarios') NULL AFTER ubicacion_trabajo",
    "ALTER TABLE egresados ADD COLUMN fecha_inicio_empleo DATE NULL AFTER tipo_contrato",
    "ALTER TABLE egresados ADD COLUMN rango_salarial VARCHAR(50) NULL AFTER fecha_inicio_empleo",
    "ALTER TABLE egresados ADD COLUMN prestaciones TEXT NULL AFTER rango_salarial",
    "ALTER TABLE egresados ADD COLUMN anos_experiencia_ti VARCHAR(20) NULL AFTER prestaciones",
    "ALTER TABLE egresados ADD COLUMN descripcion_experiencia TEXT NULL AFTER anos_experiencia_ti",
    "ALTER TABLE egresados ADD COLUMN habilidades TEXT NULL AFTER descripcion_experiencia",
    "ALTER TABLE egresados ADD COLUMN fecha_actualizacion_seguimiento TIMESTAMP NULL AFTER habilidades",
];

foreach (array_merge($alterOfertas, $alterEgresados) as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 75) . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "SKIP (exists): " . substr($sql, 0, 60) . PHP_EOL;
        } else {
            echo "ERR: " . $e->getMessage() . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Migration 003 complete." . PHP_EOL;
