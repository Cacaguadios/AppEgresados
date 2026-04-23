<?php
/**
 * Script de Configuración - Migración 015: Sistema de Invitaciones
 * 
 * Ejecutar: php database/run_015.php
 */

require_once __DIR__ . '/../app/models/Database.php';

$db = new Database();

echo "▶ Ejecutando Migración 015: Sistema de Invitaciones...\n\n";

try {
    // 1. Agregar campos a postulaciones
    echo "→ Agregando campos a tabla postulaciones...\n";
    try {
        $db->query("ALTER TABLE postulaciones ADD COLUMN retirada TINYINT(1) NOT NULL DEFAULT 0");
        echo "  ✓ Columna 'retirada' agregada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '1060') === false) { // 1060 = Duplicate column name
            throw $e;
        }
        echo "  ⓘ Columna 'retirada' ya existe\n";
    }

    try {
        $db->query("ALTER TABLE postulaciones ADD COLUMN fecha_retiro DATETIME NULL");
        echo "  ✓ Columna 'fecha_retiro' agregada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '1060') === false) {
            throw $e;
        }
        echo "  ⓘ Columna 'fecha_retiro' ya existe\n";
    }
    echo "\n";

    // 2. Crear tabla invitaciones
    echo "→ Creando tabla invitaciones...\n";
    try {
        $db->query("CREATE TABLE invitaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_oferta INT NOT NULL,
            id_docente INT NOT NULL,
            id_egresado INT NOT NULL,
            estado ENUM('pendiente', 'visto', 'rechazado', 'aceptado') NOT NULL DEFAULT 'pendiente',
            fecha_invitacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta DATETIME NULL,
            INDEX idx_oferta (id_oferta),
            INDEX idx_docente (id_docente),
            INDEX idx_egresado (id_egresado),
            INDEX idx_estado (estado),
            INDEX idx_egresado_estado (id_egresado, estado),
            FOREIGN KEY (id_oferta) REFERENCES ofertas(id) ON DELETE CASCADE,
            FOREIGN KEY (id_docente) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (id_egresado) REFERENCES egresados(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "  ✓ Tabla 'invitaciones' creada\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '1050') === false) { // 1050 = Table already exists
            throw $e;
        }
        echo "  ⓘ Tabla 'invitaciones' ya existe\n";
    }
    echo "\n";

    // 3. Actualizar enum de notificaciones
    echo "→ Actualizando tipos de notificaciones...\n";
    try {
        $db->query("ALTER TABLE notificaciones 
                   MODIFY COLUMN tipo ENUM('oferta_nueva', 'oferta_aprobada', 'oferta_rechazada', 
                   'nueva_postulacion', 'postulacion_seleccionada', 'postulacion_rechazada', 
                   'nuevo_usuario', 'general', 'invitacion_oferta', 'postulacion_retirada') 
                   NOT NULL DEFAULT 'general'");
        echo "  ✓ Tipos de notificación actualizados\n";
    } catch (Exception $e) {
        // Posible que el tipo ya exista o esté bien
        if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'Syntax') === false) {
            throw $e;
        }
        echo "  ⓘ Tipos de notificación ya están actualizados\n";
    }
    echo "\n";

    // 4. Actualizar enum de postulaciones
    echo "→ Actualizando estados de postulaciones...\n";
    try {
        $db->query("ALTER TABLE postulaciones 
                   MODIFY COLUMN estado ENUM('pendiente', 'preseleccionado', 'contactado', 'rechazado', 'retirada') 
                   NOT NULL DEFAULT 'pendiente'");
        echo "  ✓ Estados de postulaciones actualizados\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'Syntax') === false) {
            throw $e;
        }
        echo "  ⓘ Estados de postulaciones ya están actualizados\n";
    }
    echo "\n";

    echo "✅ ¡Migración 015 completada exitosamente!\n\n";
    echo "Cambios aplicados:\n";
    echo "  • Tabla invitaciones creada\n";
    echo "  • Campos retirada y fecha_retiro agregados a postulaciones\n";
    echo "  • Nuevos tipos de notificación: invitacion_oferta, postulacion_retirada\n";
    echo "  • Nuevo estado de postulación: retirada\n";
    exit(0);

} catch (Exception $e) {
    echo "❌ Error durante la migración:\n";
    echo "  " . $e->getMessage() . "\n";
    exit(1);
}
?>
