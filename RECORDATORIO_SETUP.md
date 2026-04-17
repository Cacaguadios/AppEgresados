# Sistema de Recordatorio - Actualizar Información Laboral

## Resumen

Sistema automático que muestra una ventana emergente (modal Bootstrap) a cada egresado **cada 3 meses** para recordarle que actualice su información laboral. El modal también indica el **porcentaje de completitud** de su perfil (mostrando "50%" si no tiene suficiente información).

## Componentes Implementados

### 1. Base de Datos (`database/migrations/010_add_profile_completion_reminder.sql`)

**Columnas agregadas a tabla `egresados`:**
- `fecha_proximo_recordatorio` DATETIME - Próxima fecha para mostrar el recordatorio
- `recordatorio_visto` TINYINT(1) - Flag para marcar si el usuario vio el recordatorio
- `porcentaje_completitud` INT - Porcentaje de campo completados (0-100)

**Columnas agregadas a tabla `usuarios`:**
- `fecha_ultima_actualizacion_perfil` DATETIME - Rastrear última vez que actualizó su perfil

### 2. Modelo Egresado (`app/models/Egresado.php`)

**Métodos nuevos agregados:**

#### `calcularCompletudinformacion($egresado_data)`
Calcula el porcentaje de completitud basado en campos:
- Información personal: nombre, correo_personal, telefono, especialidad
- Información laboral: empresa_actual, puesto_actual, modalidad_trabajo, jornada_trabajo, tipo_contrato, habilidades

Retorna array con:
- `porcentaje`: 0-100
- `campos_llenos`: número de campos completados
- `campos_totales`: total de campos considerados
- `campos_faltantes`: diferencia

#### `necesitaActualizacion($egresado_data)`
Verifica si pasaron **más de 3 meses** desde la última actualización.

**Retorna:**
- `true` si nunca actualizó (fecha_actualizacion_seguimiento es NULL)
- `true` si la última actualización fue hace más de 3 meses
- `false` en otros casos

#### `obtenerEstadoRecordatorio($id_usuario)`
Obtiene el estado actual del recordatorio para mostrar en el dashboard.

**Retorna array con:**
```php
[
    'debe_mostrar' => bool,         // Si mostrar o no el modal
    'razon' => string,              // 'completitud_baja' o 'actualizacion_vencida'
    'porcentaje_completitud' => int, // 0-100
    'campos_llenos' => int,
    'campos_totales' => int,
    'campos_faltantes' => int,
    'necesita_actualizacion' => bool,
    'recordatorio_visto' => bool
]
```

#### `marcarRecordatorioVisto($id_usuario)`
Marca el recordatorio como visto y establece próximo en 30 días.

#### `setProximoRecordatorio($id_usuario)`
Establece el próximo recordatorio para 3 meses después (cuando se actualiza la información).

#### `actualizarCompletudinformacion($id_usuario)`
Recalcula y actualiza el porcentaje de completitud en la BD.

### 3. Componente Modal (`views/components/modal-recordatorio-actualizacion.php`)

Modal Bootstrap con:
- Header violeta con icono de alerta
- Barra de progreso visual
- Mostrar % de completitud ("50%" cuando está bajo)
- Dos tipos de mensajes:
  - **Completitud Baja**: Lista campos faltantes
  - **Actualización Vencida**: Recordar que hace 3+ meses no actualiza
- Botones: "Recordarme después" y "Actualizar información"
- Información sobre por qué es importante

**Funciones JavaScript incluidas:**
- `inicializarRecordatorio(estadoRecordatorio)` - Inicializa el modal con datos
- `mostrarCamposFaltantes(camposFaltantes)` - Muestra campos a completar
- `marcarRecordatorioVisto()` - Envía AJAX para marcar como visto

### 4. API Endpoint (`public/api/marcar-recordatorio.php`)

**GET**: Obtener estado actual del recordatorio
```
GET /AppEgresados/public/api/marcar-recordatorio.php

Respuesta:
{
    "success": true,
    "estado": { /* estado del recordatorio */ }
}
```

**POST**: Marcar recordatorio como visto
```
POST /AppEgresados/public/api/marcar-recordatorio.php
Body: { "accion": "marcar_visto" }

Respuesta:
{
    "success": true,
    "message": "Recordatorio marcado como visto",
    "proximo_recordatorio": "2026-06-23"
}
```

### 5. Vista Egresado (`views/egresado/inicio.php`)

**Integraciones:**
1. Obtiene estado del recordatorio al cargar la vista
2. Pasa datos a JavaScript en `window.UTP_DATA.estadoRecordatorio`
3. Incluye el modal HTML
4. Inicializa el modal cuando la página carga

## Flujo de Funcionamiento

### Primer Acceso del Egresado
1. Usuario accede a `egresado/inicio.php`
2. Se calcula completitud (ej: 40% si solo llenó algunos campos)
3. Si `<60%` de completitud → `debe_mostrar = true`
4. Modal se muestra automáticamente
5. Usuario click en:
   - "Actualizar información" → Va a perfil.php y actualiza
   - "Recordarme después" → Se marca como visto + próximo en 30 días

### Actualización de Información
Cuando el usuario va a `perfil.php` y actualiza:
1. Llamar `$egresadoModel->setProximoRecordatorio($id_usuario)`
2. Esto establece próximo recordatorio a **+3 meses**
3. Próxima vez que visite en 3 meses, volverá a ver el modal

### Cada 3 Meses
1. `necesitaActualizacion()` retorna `true`
2. El recordatorio se muestra nuevamente
3. Ciclo se repite

## Instalación y Configuración

### Paso 1: Ejecutar Migración
```bash
php database/migrations/run_010.php
```

O ejecutar SQL directamente si usas interface SQL:
```sql
ALTER TABLE egresados
ADD COLUMN fecha_proximo_recordatorio DATETIME NULL,
ADD COLUMN recordatorio_visto TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN porcentaje_completitud INT NOT NULL DEFAULT 0;
```

### Paso 2: Actualizar Perfil en `perfil.php`

Cuando el usuario actualiza su información, agregar:
```php
$egresadoModel = new Egresado();
$egresadoModel->setProximoRecordatorio($_SESSION['usuario_id']);
```

### Paso 3: Verificar Integración

La vista `egresado/inicio.php` ya incluye:
- La lógica para obtener estado del recordatorio
- El modal HTML
- Script de inicialización

Solo falta:
1. Ejecutar la migración
2. (Opcional) Actualizar el archivo de perfil para llamar `setProximoRecordatorio()`

## Personalización

### Cambiar Intervalo de Recordatorio
En `app/models/Egresado.php`, método `necesitaActualizacion()`:
```php
// Cambiar '-3 months' a lo que desees
$hace_3_meses = strtotime('-6 months'); // 6 meses en lugar de 3
```

### Cambiar Porcentaje Mínimo
En método `obtenerEstadoRecordatorio()`, línea donde dice:
```php
if ($completitud['porcentaje'] < 60) { // Cambiar 60 a otro número
```

### Cambiar Campos Considerados
En método `calcularCompletudinformacion()`, ajustar arrays:
```php
$campos_perfil = [
    'nombre',
    'correo_personal',
    // Agregar o quitar campos aquí
];
```

### Cambiar Estilo del Modal
El archivo `views/components/modal-recordatorio-actualizacion.php` tiene CSS incluido:
```html
<style>
  /* Personalizar aquí */
</style>
```

## Mensajes y Textos

Todos los textos están en `views/components/modal-recordatorio-actualizacion.php`:
- Cambiar título en `<h5 class="modal-title">`
- Cambiar mensajes en divs `#msgCompletudinformacionBaja` y `#msgActualizacionVencida`
- Cambiar etiquetas de botones en `<button>` y `<a>`

## Testing

Para probar rápidamente:

1. Actualizar BD con SQL para un usuario test:
```sql
UPDATE egresados 
SET fecha_actualizacion_seguimiento = DATE_SUB(NOW(), INTERVAL 3 MONTH + 1 DAY)
WHERE id_usuario = 1;
```

2. Acceder a `egresado/inicio.php` como ese usuario
3. El modal deberá aparecer

## Notas Importantes

- El modal se muestra automáticamente al cargar la página si cumple las condiciones
- No requiere recargar la página para cerrar (usa Bootstrap modal estándar)
- Los datos se envían por AJAX para marcar como visto (sin recargar)
- La completitud se recalcula cada vez que se accede a la vista
- Los tiempos (3 meses, 30 días) pueden ajustarse según necesidad

## Archivos Modificados/Creados

```
Creados:
- database/migrations/010_add_profile_completion_reminder.sql
- database/migrations/run_010.php
- views/components/modal-recordatorio-actualizacion.php
- public/api/marcar-recordatorio.php
- RECORDATORIO_SETUP.md (este archivo)

Modificados:
- app/models/Egresado.php (+200 líneas de métodos)
- views/egresado/inicio.php (+5 líneas)
```

## Próximos Pasos Recomendados

1. ✓ Ejecutar la migración
2. ✓ Verificar que el modal aparezca en egresado/inicio.php
3. ✓ (Opcional) Integrar `setProximoRecordatorio()` en perfil.php
4. ✓ Testear el flujo completo

¡Listo para usar!
