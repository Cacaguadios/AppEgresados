# RESUMEN DE CAMBIOS - SISTEMA DE OFERTAS PARA EGRESADOS

## Cambios Realizados

### 1. MIGRACIÓN DE BASE DE DATOS
**Archivo**: `database/migrations/011_add_contact_info_to_ofertas.sql`

Se agregaron tres columnas a la tabla `ofertas`:
```sql
ALTER TABLE ofertas
  ADD COLUMN nombre_contacto VARCHAR(255) NULL,
  ADD COLUMN puesto_contacto VARCHAR(255) NULL,
  ADD COLUMN telefono_contacto VARCHAR(20) NULL;
```

**Para ejecutar la migración**:
```
1. Visita: http://localhost/AppEgresados/database/run_011.php
   O ejecuta manualmente el archivo SQL en phpMyAdmin
```

---

### 2. NUEVA FUNCIONALIDAD: EGRESADOS PUBLICAN OFERTAS

#### Archivo: `views/egresado/publicar-oferta.php` (NUEVO)
- Permite que egresados publiquen ofertas (igual que docentes)
- Requiere rol 'egresado' en sesión
- Incluye formulario con campos:
  - Información básica: título, empresa, ubicación, modalidad, jornada
  - Descripción de la posición
  - Rangos salariales (opcional)
  - Número de vacantes
  - Requisitos (dinamico - agregar/eliminar)
  - Beneficios (dinámico - agregar/eliminar)
  - Habilidades requeridas (chips dinámicos)
  - **NUEVO**: Información de contacto:
    - Email de contacto
    - Nombre del contacto
    - Puesto del contacto
    - Teléfono del contacto

**Características**:
- Validación CSRF
- Notificación a administradores al crear
- Redirige a mis-ofertas.php
- Estados: pendiente_aprobacion → aprobada

#### Archivo: `views/egresado/mis-ofertas.php` (NUEVO)
- Panel para que egresados gestionen sus ofertas
- Muestra:
  - Estado de aprobación
  - Número de vacantes disponibles
  - Cantidad de postulantes
  - Fechas de creación y expiración
  - Habilidades requeridas
  - Rango salarial
- Enlace para crear nueva oferta

---

### 3. ACTUALIZACIÓN: DOCENTES - INFORMACIÓN DE CONTACTO EXTENDIDA

#### Archivo: `views/docente/publicar-oferta.php` (MODIFICADO)
Se agregaron los mismos campos de contacto:
- nombre_contacto
- puesto_contacto
- telefono_contacto

---

### 4. LÓGICA DE ELIMINACIÓN AUTOMÁTICA DE VACANTES

#### Archivo: `app/models/Oferta.php` (MODIFICADO)

**Nuevo método: `decrementVacancies($id)`**
```php
public function decrementVacancies($id) {
    $oferta = $this->getById($id);
    if (!$oferta) return false;
    
    $nuevasVacantes = $oferta['vacantes'] - 1;
    
    if ($nuevasVacantes <= 0) {
        // Elimina la oferta automáticamente
        $this->delete('ofertas', ['id' => $id]);
        return true;
    } else {
        $this->update('ofertas', ['vacantes' => $nuevasVacantes], ['id' => $id]);
        return true;
    }
}
```

**Flujo**:
1. Egresado se postula a una oferta
2. Sistema decrementa vacantes en 1
3. Si vacantes llega a 0:
   - La oferta se ELIMINA de la base de datos
   - Se redirige al usuario a lista de ofertas con confirmación
4. Si quedan vacantes:
   - Se actualiza estado visual (verde/amarillo/rojo)

---

### 5. VISTAS ACTUALIZADAS

#### Archivo: `views/egresado/oferta-detalle.php` (MODIFICADO)
- Integración con el nuevo método `decrementVacancies()`
- Muestra new información de contacto:
  - Email de contacto
  - Nombre del contacto
  - Puesto del contacto  
  - Teléfono de contacto
- Mensaje de confirmación cuando se llena cupo

---

## ARQUITECTURA Y ESTILOS

### Consistencia del Proyecto
- Estilos: Usa clases Bootstrap 5.3 y estilos UTP existentes
  - `.utp-input`, `.utp-select`
  - `.utp-form-card`, `.utp-badge-*`
  - `.utp-skill-chip-sm`
  - `.utp-info-item`, `.utp-info-label`, `.utp-info-value`

- JavaScript: Vanilla JS con Bootstrap Bundle
  - Funciones dinámicas para agregar/eliminar items
  - Manejo de chips para habilidades
  - CSRF token validation

- Seguridad:
  - Token CSRF en todos los formularios
  - Validación de rol de usuario
  - Validación de datos en backend

---

## FLUJOS DE USO

### Egresado Publica Oferta
```
1. Egresado -> Menú lateral "Publicar Oferta"
2. Completa formulario con todos los datos
3. Sistema valida y crea con estado "pendiente_aprobacion"
4. Admin revisa y aprueba
5. Egresado ve en "Mis Ofertas"
6. Otros egresados pueden postularse
7. Cuando vacantes = 0, oferta se elimina automáticamente
```

### Docente Publica Oferta (sin cambios sustanciales)
```
1. Igual flujo anterior
2. Nuevos campos de contacto son opcionales
3. Si se completan, aparecen en oferta-detalle para postulantes
```

---

## PRÓXIMOS PASOS SUGERIDOS

1. **Ejecutar migración**: `http://localhost/AppEgresados/database/run_011.php`

2. **Pruebas**:
   - Login como egresado
   - Visitar "Publicar Oferta"
   - Llenar formulario completo
   - Verificar que aparece en "Mis Ofertas"
   - Login como otro egresado
   - Postularse a la oferta
   - Verificar eliminación automática cuando vacantes = 0

3. **Validables**:
   - Información de contacto se muestra correctamente
   - Notificaciones se envían a admin
   - Estados de aprobación funcionan correctamente

---

## ARCHIVOS MODIFICADOS

| Archivo | Cambio | Tipo |
|---------|--------|------|
| `database/migrations/011_add_contact_info_to_ofertas.sql` | Migración BD | Nuevo |
| `database/run_011.php` | Script ejecución | Nuevo |
| `views/egresado/publicar-oferta.php` | CRUD ofertas egresado | Nuevo |
| `views/egresado/mis-ofertas.php` | Gestión ofertas egresado | Nuevo |
| `views/docente/publicar-oferta.php` | Campos contacto | Modificado |
| `views/egresado/oferta-detalle.php` | Lógica vacantes, contacto | Modificado |
| `app/models/Oferta.php` | Métodos vacantes | Modificado |

---

## NOTAS IMPORTANTES

- Las vistas están diseñadas siguiendo la sinergia del proyecto existente
- No se usaronEmojis (según requisito)
- Los estilos y JavaScript usan las convenciones del proyecto
- La lógica de eliminación automática es permanente (no se puede deshacer)
- Los postulantes ya registrados se mantienen aunque la oferta sea eliminada
