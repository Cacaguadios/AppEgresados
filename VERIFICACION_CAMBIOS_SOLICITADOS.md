# 📋 VERIFICACIÓN DE CAMBIOS SOLICITADOS

**Fecha:** Marzo 24, 2026
**Status:** Análisis completo realizado

---

## 📊 RESUMEN EJECUTIVO

| Estado | Cambios |
|--------|---------|
| ✅ **Implementados** | 3/14 |
| 🔄 **Parcialmente Implementados** | 3/14 |
| ❌ **NO IMPLEMENTADOS** | 8/14 |
| **Total** | **14 cambios solicitados** |

---

## 🔍 DETALLE POR SOLICITUD

### GRUPO 1: Cambios de Correo y Actualización de Información

#### 1. Cambiar correo institucional por correo personal ✅ PARCIALMENTE HECHO

**Status:** 🔄 **Parcialmente Implementado**
- **register-step-4.php:** ✅ Ya usa email personal (línea 18: "Para egresados, usar el email personal")
- **forgot.php:** ⚠️ **AÚN DICE "correo institucional"** en línea 32: `"Ingresa tu correo institucional y te enviaremos..."`
  - **Acción requerida:** Cambiar texto a "correo personal"
- **Modelos/Controllers:** ✅ Ya almacenan `correo_personal` en BD

**Archivos a actualizar:**
- [forgot.php](views/auth/forgot.php#L32) - Cambiar texto de "institucional" a "personal"

---

#### 2. Ventana emergente cada 3 meses para actualizar información ✅ IMPLEMENTADO

**Status:** ✅ **Completamente Implementado**
- **Archivo:** [modal-recordatorio-actualizacion.php](views/components/modal-recordatorio-actualizacion.php)
- **Ubicación:** `/views/compartido/`
- **Detalles:**
  - ✅ Modal Bootstrap con diseño profesional
  - ✅ Muestra porcentaje de completitud
  - ✅ Tiene validación de 3 meses en base de datos
  - ✅ Incluye script para calcular completitud
  - ✅ Botones de acción para completar perfil

---

#### 3. Mensaje "Solo el 50% de tu información" en perfiles ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado (Falta Mostrar en Vistas)**

- **Backend:** ✅ Completitud se calcula en `Egresado.php` (`calcularCompletudinformacion()`)
- **BD:** ✅ Tabla `egresados` tiene columna `porcentaje_completitud`
- **Modal:** ✅ El modal TIENE el mensaje de "50%" pero...

**Archivos que NECESITAN actualizar:**
- [views/egresado/perfil.php](views/egresado/perfil.php) - No muestra alerta de completitud actual
- [views/docente/perfil.php](views/docente/perfil.php) - No muestra alerta de completitud

**Lo que falta:**
- Agregar alerta visual en los perfiles mostrando: "Tienes completado el X% de tu información"
- Badge o porcentaje visible en la parte superior

---

### GRUPO 2: Ofertas Laborales - Egresado

#### 4. Egresado puede publicar ofertas ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado**

- **Docente/TI:** ✅ Pueden publicar ofertas via `publicar-oferta.php`
- **Egresado:** ❌ **No tiene vista `publicar-oferta.php`**
- **Sidebar:** ❌ No existe opción en menu de egresado

**Archivos que NECESITAN crearse:**
- `views/egresado/publicar-oferta.php` - Nueva página (copiar estructura docente con ajustes)
- Actualizar sidebar de egresado para agregar opción

**Base de datos:**
- ✅ Tabla `ofertas` ya soporta `id_usuario_creador` (funciona con cualquier rol)
- ✅ Estados quedan igual (pendiente_aprobacion → aprobada → activa)

---

#### 5. Información contacto: Puesto, Nombre, Teléfono ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado (Falta diseño BD y JS)**

- **Tabla ofertas actual:** Solo tiene campo `contacto` (email)
- **Falta:** Campos para Puesto, Nombre, Teléfono

**Cambios necesarios en BD:**
```sql
ALTER TABLE ofertas ADD COLUMN (
  contacto_nombre VARCHAR(255),
  contacto_puesto VARCHAR(255),
  contacto_telefono VARCHAR(20)
);
```

**Archivos que necesitan actualización:**
- `app/models/Oferta.php` - Actualizar queries
- `views/docente/publicar-oferta.php` - Agregar campos de forma
- `views/egresado/publicar-oferta.php` - Agregar campos de forma
- `views/docente/mis-ofertas.php` - Mostrar contacto completo
- `views/egresado/ofertas.php` / detalle - Mostrar contacto completo

---

#### 6. Eliminar vacante cuando se llena el cupo ❌ PARCIALMENTE IMPLEMENTADO

**Status:** 🔄 **Infraestructura existe, falta lógica**

- **BD:** ✅ Tabla `ofertas` tiene campo `estado_vacante` (verde/amarillo/rojo)
- **BD:** ✅ Tabla `ofertas` tiene campo `vacantes` (número)
- **Lógica:** ❌ **No hay trigger ni endpoint que actualice automáticamente**

**Lo que existe:**
```php
// En Oferta.php línea ~95: solo lectura del estado
$vacanteBadge = [
    'verde'    => ['label' => 'Disponible'],
    'amarillo' => ['label' => 'En proceso'],
    'rojo'     => ['label' => 'Cubierta'],
];
```

**Lo que falta:**
- Endpoint/función para decrementar vacantes cuando se acepta postulación
- Trigger automático (o endpoint) que pase a `estado_vacante = 'rojo'` cuando vacantes = 0
- Lógica en `aceptarPostulacion()` del modelo Postulacion

---

### GRUPO 3: Gestión de Ofertas y Postulaciones

#### 7. Opción de dar de baja oferta (editar, borrar) ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado**

**Para:** Egresado, Docente, Admin

**Lo que NO existe:**
- ❌ Endpoint para eliminar oferta
- ❌ Endpoint para editar oferta
- ❌ Botones de editar/borrar en `mis-ofertas.php` (docente)
- ❌ Vista de edición de oferta

**Lo que SÍ existe:**
- ✅ Modelo Oferta.php tiene método `updateOferta()` (pero no usado)
- ✅ Admin puede rechazar ofertas en moderación

**Archivos que necesitan crearse/actualizarse:**
- `views/docente/editar-oferta.php` - Nueva página
- `views/egresado/editar-oferta.php` - Nueva página
- `views/docente/mis-ofertas.php` - Agregar botones editar/borrar
- `views/egresado/mis-ofertas.php` - Agregar botones editar/borrar (cuando se cree)
- `app/models/Oferta.php` - Agregar método deleteOferta()

---

#### 8. Opción de dar de baja postulación (editar, borrar) ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado**

**Para:** Egresado, Docente, TI (en postulantes)

**Lo que NO existe:**
- ❌ Endpoint para cancelar postulación (por egresado)
- ❌ Botones en vista de postulaciones del egresado
- ❌ Opción docente/TI para rechazar postulante

**Archivos que necesitan actualizarse:**
- `views/egresado/postulaciones.php` - Agregar botón "Cancelar postulación"
- `views/docente/postulantes.php` - Agregar botón "Rechazar"
- `app/models/Postulacion.php` - Agregar método deletePostulacion()

---

#### 9. Agregar habilidades blandas para egresado ❌ PARCIALMENTE IMPLEMENTADO

**Status:** 🔄 **Campo existe, falta mostrar en postulaciones**

- **BD:** ✅ Tabla `egresados` tiene campo `habilidades` (JSON)
- **Perfil:** ✅ Se puede editar en `views/egresado/perfil.php` tab "CV/Habilidades"
- **Falta:** Mostrar en formulario de postulación y/o llenar while aplicando

**Opciones:**
1. Mostrar habilidades al postularse (informativo)
2. Requerir seleccionar habilidades blandas (cumple/no cumple) en cada postulación

**Archivos a actualizar:**
- `views/egresado/postulaciones.php` - Mostrar habilidades cumplidas
- `views/docente/postulantes.php` - Mostrar habilidades del egresado
- Considerar tabla intermedia para "habilidades requeridas vs cumplidas por postulante"

---

### GRUPO 4: Análisis y Reportes

#### 10. Gráficas y reportes ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado**

**Requiere:**
- Dashboard con gráficas (Chart.js o similar)
- KPIs:
  - Cuántas ofertas liberadas
  - Lista de egresados
  - Dónde trabajan
  - Estadísticas de postulaciones

**Archivos que necesitan crearse:**
- `views/admin/reportes.php` - Nueva página principal de reportes
- `public/api/reportes.php` - Endpoint para datos de gráficas
- `public/assets/js/reportes.js` - Lógica de gráficas

---

#### 11. Excel/CSV con campos necesarios ❌ NO IMPLEMENTADO

**Status:** ❌ **No Implementado**

**Campos solicitados:**
- Matrícula
- Correo (personal)
- Nombre
- CURP (para validación)

**Archivos que necesitan crearse:**
- `public/api/exportar-egresados.php` - Genera Excel/CSV
- Botón en panel admin para descargar

**Nota:** Generador puede usar librería `PhpSpreadsheet` o CSV nativo

---

### GRUPO 5: Validaciones y Cambios en BD

#### 12. Matricula 6-10 dígitos, comenzar por 23-24 ✅ PARCIALMENTE IMPLEMENTADO

**Status:** 🔄 **Validación existe, patrones pueden mejorar**

**Actual:**
- ✅ Se valida en `RegisterController.php`
- ✅ Se valida en `Egresado.php`
- ⚠️ Falta validar que comience por 23-24

**Cambio necesario:**
```php
// En RegisterController o Usuario modelo
private function validateMatricula($matricula) {
    // Validar 6-10 dígitos
    if (!preg_match('/^\d{6,10}$/', $matricula)) {
        return false;
    }
    // Validar que comience por 23 o 24
    if (!preg_match('/^(23|24)/', $matricula)) {
        return false;
    }
    return true;
}
```

**Archivos a actualizar:**
- [app/controllers/RegisterController.php](app/controllers/RegisterController.php) - Actualizar regex

---

#### 13. Quitar correo institucional, manter personal ✅ EN PROGRESO

**Status:** 🔄 **Parcialmente hecho**

- ✅ BD: Ya almacena `correo_personal`
- ✅ Formularios: Ya piden correo personal
- ⚠️ Mensajes: Aún dicen "institucional" en algunos lugares
- ❌ Completamente eliminado: Necesita auditoría completa

**Búsquedas a realizar:**
```bash
grep -r "institucional" views/ --include="*.php"
grep -r "@utp" views/ --include="*.php"
```

---

#### 14. No necesitamos base de datos para... ✅ N/A

**Status:** ℹ️ **Aclaración Necesaria**

Este punto parece referirse a algún servicio externo. Necesita clarificación del usuario.

---

## 📝 PRIORIDADES RECOMENDADAS

### FASE 1: CRÍTICA (Interfaz Usuario - 2 cambios)
1. ❌ **Cambiar "correo institucional" a "personal" en forgot.php**
   - Impacto: Alto (UI)
   - Tiempo: 5 minutos
   
2. ❌ **Mostrar mensaje de completitud en perfiles (egresado/docente)**
   - Impacto: Alto (UX)
   - Tiempo: 30 minutos

### FASE 2: BASE DE DATOS (1 cambio)
3. ❌ **Agregar campos a tabla ofertas (contacto_nombre, contacto_puesto, contacto_telefono)**
   - Impacto: Medio (bloqueante para formularios)
   - Tiempo: 15 minutos (BD + 1 migración)

### FASE 3: ALTA (Funcionalidades Core - 4 cambios)
4. ❌ **Crear vista publicar-oferta.php para egresado**
   - Impacto: Alto (nuevo feature)
   - Tiempo: 1-2 horas
   
5. ❌ **Implementar editar/borrar ofertas (docente, egresado, admin)**
   - Impacto: Alto (gestión)
   - Tiempo: 3-4 horas
   
6. ❌ **Implementar cancelar postulación (egresado)**
   - Impacto: Medio (gestión)
   - Tiempo: 2-3 horas
   
7. ❌ **Auto-eliminar vacante cuando se llena cupo**
   - Impacto: Medio (automatización)
   - Tiempo: 1-2 horas

### FASE 4: MEDIA (Características Secundarias - 3 cambios)
8. ❌ **Agregar opción de rechazar postulante (docente/TI)**
   - Impacto: Bajo-Medio
   - Tiempo: 1-2 horas
   
9. ❌ **Mejorar habilidades blandas en postulaciones**
   - Impacto: Medio
   - Tiempo: 2-3 horas
   
10. ❌ **Validación de matrícula: 23-24 al inicio**
    - Impacto: Bajo
    - Tiempo: 15 minutos

### FASE 5: BAJA (Analytics & Exports - 2 cambios)
11. ❌ **Gráficas y reportes en admin**
    - Impacto: Bajo (visualización)
    - Tiempo: 4-6 horas
    
12. ❌ **Exportar egresados a Excel/CSV**
    - Impacto: Bajo (reporting)
    - Tiempo: 2-3 horas

---

## ⚙️ MATRIZ DE CAMBIOS EN BD NECESARIOS

| Campo | Tabla | Tipo | Razón |
|-------|-------|------|-------|
| `contacto_nombre` | ofertas | VARCHAR(255) | Nombre de contacto |
| `contacto_puesto` | ofertas | VARCHAR(255) | Puesto de quien contacta |
| `contacto_telefono` | ofertas | VARCHAR(20) | Teléfono de contacto |

**Migración SQL a ejecutar:**
```sql
ALTER TABLE ofertas ADD COLUMN (
  contacto_nombre VARCHAR(255) NULL AFTER contacto,
  contacto_puesto VARCHAR(255) NULL AFTER contacto_nombre,
  contacto_telefono VARCHAR(20) NULL AFTER contacto_puesto
);
```

**Archivo de migración a crear:**
- `database/migrations/011_add_contacto_fields_to_ofertas.sql`
- `database/migrations/run_011.php`

---

## 📌 NOTA IMPORTANTE

**No se encontraron:**
- ❌ Comunicación por correo automática (envío de notificaciones)
- ❌ Perfil especial para "Empresa que solo publica ofertas"
- ❌ Sistema de soft-delete en modelo Oferta y Postulacion

---

## 🔄 PRÓXIMOS PASOS SUGERIDOS

1. **Confirmar prioridades** con el usuario
2. **Crear tickets** para cada cambio (usar estructura FASE 1-5)
3. **Ejecutar cambios** en orden sugerido
4. **Testing incremental** después de cada fase
5. **Documentar** cambios en CHANGELOG.md

