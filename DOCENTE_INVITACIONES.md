# Funcionalidades Docente - Sistema de Invitaciones y Gestión de Ofertas

## 📋 Resumen de Cambios

Se han implementado nuevas funcionalidades para permitir que los usuarios **Docentes** gestionen ofertas de trabajo de manera similar a los egresados, con capacidades adicionales de invitación directa.

---

## 🎯 Funcionalidades Implementadas

### 1. **Sistema de Invitaciones**
- Los docentes pueden invitar egresados específicos a postularse en sus ofertas
- Los egresados reciben notificaciones de invitación
- Los egresados pueden aceptar (y se postularán automáticamente) o rechazar invitaciones
- Las invitaciones tienen estados: `pendiente`, `visto`, `aceptado`, `rechazado`

### 2. **Gestión de Ofertas por Docentes**
- Los docentes pueden crear, editar y dar de baja ofertas
- Las ofertas requieren aprobación de administrador antes de ser públicas
- Las ofertas se eliminan automáticamente cuando se llena el cupo
- Los docentes pueden ver todos los postulantes a sus ofertas

### 3. **Retiro de Postulaciones**
- Los egresados pueden retirar sus postulaciones en cualquier momento
- Los docentes reciben notificación cuando un egresado retira su postulación
- El estado se marca como `retirada` para mantener historial

### 4. **Notificaciones**
Se han agregado nuevos tipos de notificaciones:
- `invitacion_oferta` - Cuando se invita a un egresado
- `postulacion_retirada` - Cuando un egresado retira su postulación

---

## 📁 Archivos Modificados/Creados

### Base de Datos
- **`database/migrations/015_add_invitaciones.sql`** - Tabla de invitaciones + campos adicionales

### Modelos
- **`app/models/Invitacion.php`** ✨ NUEVO - CRUD para invitaciones
- **`app/models/Notificacion.php`** - Métodos nuevos: `onInvitacionOferta()`, `onPostulacionRecibida()`, `onPostulacionRetirada()`
- **`app/models/Egresado.php`** - Método nuevo: `getAll()` para obtener todos los egresados

### APIs
- **`public/api/invitaciones.php`** ✨ NUEVO - Gestión de invitaciones (crear, aceptar, rechazar, marcar_visto)
- **`public/api/postulaciones-update.php`** - Notificación al docente cuando se retira postulación

### Vistas
- **`views/egresado/invitaciones.php`** ✨ NUEVO - Panel de invitaciones recibidas
- **`views/docente/invitar-egresados.php`** ✨ NUEVO - Interfaz para invitar egresados
- **`views/docente/mis-ofertas.php`** - Botón "Invitar" agregado

---

## 🚀 Cómo Usar

### Para Docentes (Publicar y Gestionar Ofertas)

1. **Crear Oferta**
   - Ir a `Mis Ofertas` → `Nueva oferta`
   - Llenar datos: título, empresa, descripción, ubicación, etc.
   - Requiere que sea aprobada por administrador

2. **Invitar Egresados**
   - Ir a `Mis Ofertas`
   - Hacer clic en botón `Invitar` de la oferta (solo si está aprobada)
   - Seleccionar egresados y enviar invitaciones
   - Los egresados recibirán notificación

3. **Ver Postulantes**
   - Hacer clic en "X postulantes" o ir a `Alumnos/Postulantes`
   - Ver estado de todas las postulaciones
   - Cambiar estado a: Validado, Seleccionado, Rechazado
   - Los egresados recibirán notificación de cambio de estado

4. **Dar de Baja Oferta**
   - Hacer clic en `Dar de baja` en la oferta
   - Esto desactiva la oferta inmediatamente

### Para Egresados (Responder a Invitaciones)

1. **Ver Invitaciones**
   - Ir a `Invitaciones` en el menú
   - Ver todas las invitaciones pendientes

2. **Aceptar Invitación**
   - Revisar detalles de la oferta
   - Hacer clic en `Aceptar y Postularme`
   - Se creará automáticamente la postulación
   - Recibirá confirmación

3. **Rechazar Invitación**
   - Hacer clic en `Rechazar`
   - La invitación se marca como rechazada

4. **Retirar Postulación**
   - Ir a `Mis Postulaciones`
   - Hacer clic en `Dar de baja` en la postulación
   - El docente recibirá notificación

---

## 🔐 Permisos y Seguridad

### Docentes/TI pueden:
- ✓ Crear y gestionar sus propias ofertas
- ✓ Invitar egresados específicos
- ✓ Ver y cambiar estado de postulaciones a sus ofertas
- ✓ Eliminar/retirar postulaciones de sus ofertas

### Egresados pueden:
- ✓ Ver invitaciones recibidas
- ✓ Aceptar o rechazar invitaciones
- ✓ Ver todas sus postulaciones
- ✓ Retirar sus propias postulaciones

### Admin puede:
- ✓ Aprobar/rechazar ofertas
- ✓ Cambiar estado de cualquier postulación
- ✓ Retirar/eliminar cualquier postulación

---

## 💌 Notificaciones por Email

El sistema simula envío de emails registrando en `storage/logs/emails.log`:
- Invitación a oferta
- Postulación recibida
- Postulación seleccionada/rechazada
- Postulación retirada

---

## 🛠️ Instalación

### Ejecutar Migración
```bash
# En la terminal/consola de la base de datos
cd database/migrations
# Ejecutar: 015_add_invitaciones.sql
```

O usar el script de setup:
```bash
php database/setup_laragon.php
```

---

## 📊 Estructura de Base de Datos

### Tabla `invitaciones`
```sql
CREATE TABLE invitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_oferta INT,
    id_docente INT,
    id_egresado INT,
    estado ENUM('pendiente', 'visto', 'rechazado', 'aceptado'),
    fecha_invitacion DATETIME,
    fecha_respuesta DATETIME,
    -- Índices para búsquedas rápidas
)
```

### Cambios a `postulaciones`
```sql
ALTER TABLE postulaciones
ADD COLUMN retirada TINYINT(1) DEFAULT 0,
ADD COLUMN fecha_retiro DATETIME NULL;
```

### Cambios a `notificaciones`
```sql
-- Tipos nuevos agregados:
-- 'invitacion_oferta'
-- 'postulacion_retirada'
```

---

## ✅ Testing Checklist

- [ ] Docente puede crear oferta
- [ ] Admin aprueba oferta
- [ ] Docente puede invitar egresados
- [ ] Egresado recibe notificación
- [ ] Egresado acepta invitación y se postula
- [ ] Docente ve postulación
- [ ] Docente cambia estado a "Seleccionado"
- [ ] Egresado recibe notificación de selección
- [ ] Egresado retira postulación
- [ ] Docente recibe notificación de retiro
- [ ] Oferta se da de baja cuando cupo se llena
- [ ] Emails se registran en log

---

## 🐛 Troubleshooting

**Problema:** Las invitaciones no se crean
- Verificar que el docente es propietario de la oferta
- Verificar que la oferta existe y está aprobada
- Revisar logs de error

**Problema:** Egresado no recibe notificación
- Revisar `storage/logs/emails.log`
- Verificar que los IDs de usuario son correctos

**Problema:** Oferta no se desactiva al llenar cupo
- Verificar manualmente en base de datos
- Asegurarse de que `decrementVacancies()` se llama cuando se postula

---

## 📞 Contacto

Para reportar bugs o sugerencias, contactar al equipo de desarrollo.

