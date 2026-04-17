# CAMBIOS IMPLEMENTADOS - NUEVAS FUNCIONALIDADES

## Fecha: 24 de Marzo de 2026

## RESUMEN EJECUTIVO

Se implementaron tres funcionalidades principales siguiendo la estructura y patrones del proyecto:

1. **Habilidades Blandas para Egresado**: Gestión de soft skills en el perfil
2. **Dar de Baja Oferta**: Sistema para retirar ofertas sin perderlas
3. **Editar y Borrar Ofertas**: Gestión completa de ofertas por creador
4. **Editar y Retirar Postulaciones**: Egresados pueden retirar sus postulaciones

---

## CAMBIOS EN BASE DE DATOS

### Migración: `database/migrations/012_add_soft_skills_and_offers_management.sql`

Se agregaron las siguientes columnas a las tablas:

#### Tabla `egresados`:
- `habilidades_blandas` (TEXT, JSON) - Array de habilidades blandas/soft skills

#### Tabla `ofertas`:
- `activo` (TINYINT(1), DEFAULT 1) - Indica si la oferta está activa
- `fecha_baja` (DATETIME, NULL) - Fecha de retiro de la oferta
- `motivo_baja` (VARCHAR(255), NULL) - Razón de retiro

#### Tabla `postulaciones`:
- `retirada` (TINYINT(1), DEFAULT 0) - Indica si fue retirada por egresado
- `fecha_retiro` (DATETIME, NULL) - Fecha de retiro de postulación

### Cómo ejecutar la migración:
```
1. Ir a http://localhost/AppEgresados/database/run_011.php (verificar si existe runner)
2. O ejecutar manualmente en phpMyAdmin:
   - Seleccionar base de datos: bolsa_trabajo_utp
   - Ir a Importar
   - Seleccionar: database/migrations/012_add_soft_skills_and_offers_management.sql
   - Hacer clic en Continuar
```

---

## CAMBIOS EN MODELOS

### `app/models/Egresado.php`

**Nuevos métodos:**
```php
// Guardar habilidades blandas
updateHabilidadesBlandas($id_usuario, $habilidades)

// Obtener habilidades blandas
getHabilidadesBlandas($id_usuario)
```

### `app/models/Oferta.php`

**Nuevos métodos:**
```php
// Dar de baja una oferta
setBaja($id, $motivo = null)

// Reactivar una oferta
setActiva($id)

// Editar oferta existente
edit($id, $data)

// Obtener ofertas activas de un usuario
getByUserIdActive($id_usuario)
```

### `app/models/Postulacion.php`

**Nuevos métodos:**
```php
// Retirar postulación (marcar como retirada)
retirar($id)

// Obtener postulaciones activas de egresado
getByEgresadoIdActivas($egresadoId)
```

---

## CAMBIOS EN VISTAS

### 1. `views/egresado/perfil.php`

**Cambios:**
- Agregar pestaña "Habilidades" en lugar de "CV / Habilidades"
- Nueva sección para gestionar habilidades blandas con:
  - Chips dinámicos que se pueden agregar/eliminar
  - Input para escribir nuevas habilidades
  - Botón para agregar habilidades
  - Ejemplos de habilidades sugeridas
  
**Funcionalidad PHP:**
- Manejo POST para guardar habilidades blandas
- Carga de habilidades existentes
- Validación CSRF

**Funcionalidad JavaScript:**
- Agregar habilidades blandas dinámicamente
- Eliminar habilidades con click en la X
- Sincronizar cambios con inputs ocultos

### 2. `views/egresado/mis-ofertas.php`

**Cambios:**
- Agregados botones de acción en cada tarjeta de oferta:
  - **Ver**: Ir a detalles de la oferta
  - **Editar**: Editar los datos de la oferta
  - **Dar de baja**: Retirar la oferta (con confirmación)
  - **Reactivar**: Si está dada de baja
  - **Postulantes**: Ver cantidad de postulantes

**Funcionalidad JavaScript:**
- Función `confirmarBaja()` para dar de baja ofertas
- Función `confirmarActivacion()` para reactivarlas
- Llamadas AJAX a `public/api/ofertas-update.php`

### 3. `views/egresado/editar-oferta.php` (NUEVO)

**Descripción:**
Vista para editar ofertas existentes. Estructura idéntica a publicar-oferta.php pero:
- Pre-llena todos los campos con datos actuales
- Permite editar: título, empresa, ubicación, modalidad, jornada, descrip, requisitos, beneficios, habilidades, salario, vacantes, fecha expiracio, contacto
- NO permite editar: creador, estado de aprobación
- Muestra información de la oferta en sidebar

**Validaciones:**
- Solo el creador o admin puede editar
- CSRF protection
- Validación de campos obligatorios

### 4. `views/egresado/postulaciones.php`

**Cambios:**
- Agregado botón "Retirar" en cada postulación activa
- El botón no aparece si la postulación está rechazada
- Confirmación antes de retirar
- AJAX call a `public/api/postulaciones-update.php`

---

## NUEVOS ARCHIVOS API

### `public/api/ofertas-update.php`

**Acciones:**
- `baja`: Dar de baja una oferta
- `activar`: Reactivar una oferta dada de baja
- `editar`: Actualizar datos de la oferta
- `eliminar`: Eliminar permanentemente (solo admin)

**Validaciones:**
- Sesión activa requerida
- Permisos (creador o admin)
- Validación de datos
- Respuesta JSON

### `public/api/postulaciones-update.php`

**Acciones:**
- `retirar`: Marcar postulación como retirada por egresado
- `actualizar_estado`: Cambiar estado (solo docente/admin)

**Validaciones:**
- Solo egresado puede retirar su postulación
- Docente/Admin pueden cambiar estado
- Estados válidos: pendiente, preseleccionado, contactado, rechazado

---

## CAMBIOS EN CSS

### `public/assets/css/components.css`

**Nuevas clases:**
```css
.utp-skill-chip-editable
- Display: inline-flex
- Padding: 6px 10px
- Background: rgba(0, 76, 235, 0.10)
- Border: 1px solid rgba(0, 76, 235, 0.25)
- Border-radius: 16px
- Color: #004CEB
- Font-size: 13px
- Transiciones suaves

.utp-skill-chip-editable i
- Cursor: pointer
- Font-size: 14px
- Hover effect con color rojo (#dc3545)
```

---

## FLUJOS DE USUARIO

### 1. Gestionar Habilidades Blandas (Egresado)

```
1. Egresado → Perfil → Pestaña "Habilidades"
2. Escribe habilidad en input (ej: "Liderazgo")
3. Click en "Agregar" o presiona Enter
4. Habilidad aparece como chip removible
5. Click en X del chip para eliminar
6. Click en "Guardar habilidades"
7. AJAX POST a perfil.php
8. Confirmar con mensaje de éxito
```

### 2. Dar de Baja Oferta (Egresado/Docente/Admin)

```
1. Ir a "Mis Ofertas"
2. Click en botón "Dar de baja" en la oferta
3. Confirmar acción
4. AJAX POST a ofertas-update.php?action=baja
5. Oferta pasa a estado "Dada de baja"
6. Botón cambia a "Reactivar"
7. Page reload para reflejar cambios
```

### 3. Editar Oferta (Egresado/Docente/Admin)

```
1. Ir a "Mis Ofertas"
2. Click en botón "Editar"
3. Ir a editar-oferta.php?id=X
4. Campos pre-llenados con datos actuales
5. Modificar los campos deseados
6. Click en "Guardar cambios"
7. POST a editar-oferta.php
8. Confirmar con mensaje de éxito
```

### 4. Retirar Postulación (Egresado)

```
1. Ir a "Mis Postulaciones"
2. Click en botón "Retirar" de la postulación
3. Confirmar acción
4. AJAX POST a postulaciones-update.php?action=retirar
5. Postulación marcada como retirada
6. Botón desaparece (no se muestra en postulaciones activas)
7. Page reload
```

---

## CONSIDERACIONES DE SEGURIDAD

1. **Validación CSRF**: Todos los POST incluyen token CSRF
2. **Permisos**: Se valida que solo el creador o admin pueden editar/dar de baja
3. **Validación de datos**: Filtrado y sanitizado de inputs
4. **Errores**: Respuestas JSON con mensajes claros
5. **Respuestas**: Usa Content-Type application/json

---

## ESTÁNDARES SEGUIDOS

✓ **Estructura de carpetas**: Se respetó la organización existente
✓ **Estilos**: Se usaron clases CSS del proyecto (utp-*)
✓ **Bootstrap**: Se mantuvo Bootstrap 5.3.0
✓ **Icons**: Se usaron Bootstrap Icons
✓ **JavaScript**: Vanilla JS, sin dependencias externas
✓ **Backend**: PHP con salami-style MVC
✓ **Modelos**: Extiendo clase Database existente
✓ **API**: Respuestas JSON estandarizadas

---

## PRÓXIMOS PASOS / RECOMENDATIONS

1. Ejecutar la migración de BD
2. Probar flujos en navegador
3. Verificar permisos en cada rol (egresado, docente, admin)
4. Considerar agregar logs de auditoría para cambios
5. Implementar vista de editar-oferta para docentes/admin
6. Agregar notificaciones cuando se da de baja/retira oferta

---

## TESTING CHECKLIST

- [ ] Egresado puede agregar habilidades blandas
- [ ] Egresado puede eliminar habilidades blandas
- [ ] Egresado puede dar de baja su oferta
- [ ] Egresado puede reactivar oferta dada de baja
- [ ] Egresado puede editar su oferta
- [ ] Egresado puede retirar su postulación
- [ ] Docente tiene mismas opciones de editar/baja
- [ ] Admin puede dar de baja cualquier oferta
- [ ] Admin puede editar cualquier oferta
- [ ] Validaciones CSRF funcionan
- [ ] Mensajes de éxito/error aparecen
- [ ] Base de datos se actualiza correctamente
- [ ] Estilos CSS se muestran correctamente
- [ ] Links de navegación funcionan
- [ ] Mobile responsive funciona
