# Guía de Instalación - Sistema de Recordatorio de Actualización

## 🎯 Objetivo
Mostrar una ventana emergente cada 3 meses pidiendo al egresado actualizar su información laboral, indicando que "solo tienes el 50% de tu información" cuando el porcentaje es bajo.

## 📋 Archivos Creados/Modificados

### ✅ NUEVOS ARCHIVOS CREADOS:
1. **database/migrations/010_add_profile_completion_reminder.sql** - Estructura SQL
2. **database/migrations/run_010.php** - Script para ejecutar migración
3. **views/components/modal-recordatorio-actualizacion.php** - Modal Bootstrap
4. **public/api/marcar-recordatorio.php** - Endpoint AJAX
5. **RECORDATORIO_SETUP.md** - Documentación completa

### 🔄 ARCHIVOS MODIFICADOS:
1. **app/models/Egresado.php** - Agregados 6 métodos nuevos
2. **views/egresado/inicio.php** - Integración del modal
3. **views/egresado/perfil.php** - Actualizar completitud
4. **views/egresado/seguimiento.php** - Marcar próximo recordatorio

---

## 🚀 PASOS DE INSTALACIÓN

### PASO 1: Ejecutar la Migración

Opción A) Vía PHP CLI:
```bash
cd c:\laragon\www\AppEgresados
php database/migrations/run_010.php
```

Opción B) Vía phpMyAdmin o cliente SQL:
```sql
-- Ejecutar el contenido de:
database/migrations/010_add_profile_completion_reminder.sql
```

**Verificar que se ejecutó correctamente:**
```sql
DESCRIBE egresados;
-- Debe mostrar: fecha_proximo_recordatorio, recordatorio_visto, porcentaje_completitud
```

### PASO 2: Verificar Integración de Código

Los siguientes cambios YA ESTÁN REALIZADOS:

✓ **app/models/Egresado.php**
- Método `calcularCompletudinformacion()` - calcula % de completitud
- Método `necesitaActualizacion()` - verifica si pasaron 3 meses
- Método `obtenerEstadoRecordatorio()` - obtiene estado a mostrar
- Método `marcarRecordatorioVisto()` - marca como visto
- Método `setProximoRecordatorio()` - establece próximo en 3 meses
- Método `actualizarCompletudinformacion()` - recalcula %

✓ **views/egresado/inicio.php**
- Obtiene estado del recordatorio
- Incluye modal del recordatorio
- Inicializa JavaScript automáticamente

✓ **views/egresado/perfil.php**
- Llama a `actualizarCompletudinformacion()` después de guardar

✓ **views/egresado/seguimiento.php**
- Llama a `setProximoRecordatorio()` después de guardar
- Llama a `actualizarCompletudinformacion()` después de guardar

---

## ✅ TESTING

### Test 1: Verificar Modal en Primera Visita

1. Abrir navegador (incógnito preferiblemente)
2. Ir a: `http://localhost/AppEgresados/`
3. Login como egresado (sin información laboral)
4. Debería aparecer modal automáticamente con "50% de completitud"

**Esperado:**
- Modal violeta aparece
- Muestra porcentaje bajo
- Botones funcionan

### Test 2: Marcar Recordatorio como Visto

1. En el modal anterior, clickear "Recordarme después"
2. Esperar respuesta AJAX
3. Modal se cierra

**Esperado:**
- Modal desaparece
- BD se actualiza: `recordatorio_visto = 1`
- `fecha_proximo_recordatorio = hoy + 30 días`

### Test 3: Actualizar Información Laboral

1. Clickear "Actualizar información" (o ir a Perfil/Seguimiento)
2. Llenar información laboral (empresa, puesto, etc.)
3. Guardar
4. Volver a Inicio

**Esperado:**
- % de completitud aumenta
- Modal NO aparece (porque acaba de actualizar)
- BD se actualizó: `fecha_proximo_recordatorio = hoy + 3 meses`

### Test 4: Verificar Recordatorio cada 3 Meses

Para simular que pasaron 3 meses sin actualizar:

```sql
-- Cambiar último acceso a 3 meses atrás
UPDATE egresados 
SET fecha_actualizacion_seguimiento = DATE_SUB(NOW(), INTERVAL 91 DAY)
WHERE id_usuario = 1;

-- También resetear el flag de visto
UPDATE egresados
SET recordatorio_visto = 0
WHERE id_usuario = 1;
```

Ahora si accedes como ese usuario, el modal aparecerá nuevamente.

---

## 🎨 PERSONALIZACIÓN

### Cambiar el intervalo de 3 meses

**Archivo:** `app/models/Egresado.php`
**Método:** `necesitaActualizacion()`

```php
// Buscar esta línea:
$hace_3_meses = strtotime('-3 months');

// Cambiar a:
$hace_3_meses = strtotime('-6 months');  // 6 meses en lugar de 3
```

### Cambiar el texto del modal

**Archivo:** `views/components/modal-recordatorio-actualizacion.php`

```php
<!-- Cambiar estos textos -->
<h5 class="modal-title">Tus propios textos aquí</h5>
<!-- ... etc -->
```

### Cambiar el % mínimo para mostrar alerta

**Archivo:** `app/models/Egresado.php`
**Método:** `obtenerEstadoRecordatorio()`

```php
if ($completitud['porcentaje'] < 60) {  // Cambiar 60 a otro número
```

### Cambiar campos considerados para completitud

**Archivo:** `app/models/Egresado.php`
**Método:** `calcularCompletudinformacion()`

```php
$campos_perfil = [
    'nombre',
    'correo_personal',
    // Agregar o quitar campos
];

$campos_laborales = [
    'empresa_actual',
    // Agregar o quitar campos
];
```

### Cambiar colores del modal

**Archivo:** `views/components/modal-recordatorio-actualizacion.php`
**Sección:** `<style>`

```css
#modalRecordatorioActualizacion .modal-header {
  background: linear-gradient(135deg, #TU_COLOR_1 0%, #TU_COLOR_2 100%);
}
```

---

## 📊 Estructura de Datos

### Nueva Información en BD

**Tabla `egresados`:**
```
fecha_proximo_recordatorio DATETIME     -- Próxima fecha para mostrar recordatorio
recordatorio_visto TINYINT(1)           -- Flag: ha visto el recordatorio?
porcentaje_completitud INT              -- % de completitud (0-100)
```

**Tabla `usuarios`:**
```
fecha_ultima_actualizacion_perfil DATETIME  -- Tracking de cambios
```

---

## 🔗 Endpoints API

### GET: Obtener estado
```
GET /AppEgresados/public/api/marcar-recordatorio.php

Retorna:
{
  "success": true,
  "estado": {
    "debe_mostrar": true,
    "porcentaje_completitud": 45,
    "campos_llenos": 5,
    ...
  }
}
```

### POST: Marcar como visto
```
POST /AppEgresados/public/api/marcar-recordatorio.php
Content-Type: application/json

{
  "accion": "marcar_visto"
}

Retorna:
{
  "success": true,
  "proximo_recordatorio": "2026-06-23"
}
```

---

## 🐛 Solución de Problemas

### El modal no aparece
1. Verificar que la migración se ejecutó: `DESCRIBE egresados;`
2. Verificar que el egresado tiene `<60%` de completitud
3. Verificar en consola (F12) si hay errores JS
4. Verificar en red (F12) que se carga `modal-recordatorio-actualizacion.php`

### El modal aparece pero no funciona
1. Verificar que Bootstrap JS esté cargado (F12 > Console)
2. Verificar que no haya conflictos de CSS
3. Ver errores en consola del navegador (F12)

### AJAX no funciona
1. Verificar ruta correcta: `/AppEgresados/public/api/marcar-recordatorio.php`
2. Verificar que el usuario esté autenticado (sesión)
3. Ver respuesta en Network (F12) > marcar-recordatorio.php

### Completitud siempre en 0%
1. Ejecutar `actualizarCompletudinformacion()` manualmente
2. Verificar que los campos en BD correspondan con los métodos
3. Revisar nombres de campos en la BD vs en el código

---

## 📝 Logs y Debugging

Para ver qué está pasando:

**Opción 1:** Agregar console.log en JavaScript
```javascript
// En modal-recordatorio-actualizacion.php
console.log('Estado:', estadoRecordatorio);
console.log('Debe mostrar:', estadoRecordatorio.debe_mostrar);
```

**Opción 2:** Agregar var_dump en PHP
```php
// En inicio.php
var_dump($estadoRecordatorio);
```

**Opción 3:** Ver en Database
```sql
SELECT id_usuario, porcentaje_completitud, 
       fecha_proximo_recordatorio, 
       recordatorio_visto 
FROM egresados 
WHERE id_usuario = 1;
```

---

## ✨ Características Implementadas

✅ Modal Bootstrap moderno y responsivo
✅ Cálculo automático de completitud
✅ Recordatorio cada 3 meses
✅ AJAX para marcar como visto
✅ Mensajes diferentes según razón
✅ Barra de progreso visual
✅ Integración automática en inicio
✅ Actualización de completitud en perfil/seguimiento
✅ Índices DB optimizados
✅ Fully responsive (mobile/tablet/desktop)

---

## 📞 Soporte

Si algo no funciona:

1. Verificar que todos los archivos estén creados
2. Revisar errores en:
   - Consola del navegador (F12 > Console)
   - Network tab (F12 > Network)
   - Logs de PHP (si los tienes configurados)
3. Revisar la documentación en `RECORDATORIO_SETUP.md`
4. Hacer un test limpio con un usuario nueva

---

## 🎉 ¡Listo!

El sistema está completamente implementado y funcional. 

**Próximas sugerencias:**
- Agregar animaciones adicionales
- Integrar con sistema de notificaciones existente
- Crear reporte de egresados que necesitan actualizar
- Enviar emails recordatorios (adicional)

¡Usa y disfruta el nuevo sistema! 🚀
