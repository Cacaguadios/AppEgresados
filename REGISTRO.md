# Sistema de Registro de 3 Pasos

## Descripción

El registro de nuevos usuarios se divide en **3 pasos**, manejados con `sessionStorage` de JavaScript para mantener el estado entre pasos.

## Archivo de rutas

```
views/auth/
├── register-step-1.php    ← Seleccionar tipo de usuario
├── register-step-2.php    ← Verificación de autenticidad
└── register-step-3.php    ← Información básica y generación de credenciales
```

## Flujo del registro

### PASO 1: Seleccionar Tipo de Usuario
**URL:** `http://localhost/AppEgresados/views/auth/register-step-1.php`

- Usuario elige entre: **Egresado**, **Docente** o **Personal TI**
- El rol seleccionado se guarda en `sessionStorage.registroRol`
- Al hacer clic en "Continuar", redirige a Step 2

**sessionStorage:**
```javascript
{
  registroRol: "egresado" // o "docente", "ti"
}
```

### PASO 2: Verificación de Autenticidad
**URL:** `http://localhost/AppEgresados/views/auth/register-step-2.php`

- Muestra campos **dinámicos según el rol**:
  - **Egresado**: Matrícula UTP + CURP
  - **Docente**: ID de Docente
  - **Personal TI**: ID de TI
- Valida los datos ingresados
- Al hacer clic en "Continuar", guarda `verificacionCompleta` en sessionStorage

**sessionStorage:**
```javascript
{
  registroRol: "egresado",
  verificacionCompleta: "true"
}
```

### PASO 3: Información Básica
**URL:** `http://localhost/AppEgresados/views/auth/register-step-3.php`

- Usuario ingresa: **Nombre** y **Apellidos**
- Al hacer clic en "Generar Credenciales":
  1. Sistema genera usuario y contraseña temporal
  2. Muestra modal con credenciales
  3. Usuario puede copiar al portapapeles
  4. Botón "Ir al Login" para iniciar sesión

**sessionStorage:**
```javascript
{
  registroRol: "egresado",
  verificacionCompleta: "true",
  usuarioGenerado: "juan.perez",
  passwordGenerado: "Temporal234!",
  nombreCompleto: "Juan Pérez"
}
```

## Variables de SessionStorage

| Variable | Paso | Tipo | Ejemplo |
|----------|------|------|---------|
| `registroRol` | 1 | string | `"egresado"` \| `"docente"` \| `"ti"` |
| `verificacionCompleta` | 2 | string | `"true"` |
| `usuarioGenerado` | 3 | string | `"juan.perez"` |
| `passwordGenerado` | 3 | string | `"Temporal234!"` |
| `nombreCompleto` | 3 | string | `"Juan Pérez"` |

## Estilos CSS Utilizados

- `.auth-wizard-shell` - Fondo gradiente
- `.auth-wizard-card` - Card contenedor
- `.auth-stepper` - Indicador de progreso
- `.role-card` - Tarjetas de rol seleccionable
- `.auth-cred-box` - Caja de credenciales
- `.btn-utp-red` - Botón rojo UTP
- `.btn-utp-green` - Botón verde UTP

## Funcionalidades JavaScript

### Step 1
- Click en rol: selecciona y marca como `active`
- Submit: guarda rol y redirige a Step 2

### Step 2
- Muestra/oculta campos según rol en sessionStorage
- Botón atrás: retrocede un paso
- Submit: guarda verificación y redirige a Step 3

### Step 3
- Genera usuario y contraseña
- Modal muestra credenciales
- Botón copiar: copia al portapapeles
- "Ir al Login": redirige a login.php

## Integración Backend (TODO)

Los siguientes endpoints requieren implementarse en el backend:

1. **Validar datos de verificación**
   ```php
   POST /api/auth/verify-user
   {
     role: "egresado",
     matricula: "2019010123",
     curp: "PEJI900112HDFRNN01"
   }
   Response: { valid: true/false }
   ```

2. **Crear usuario**
   ```php
   POST /api/auth/create-user
   {
     nombre: "Juan",
     apellidos: "Pérez",
     role: "egresado",
     email: "juan.perez@egresados.utp"
   }
   Response: { usuario: "juan.perez", password: "Temporal123!", success: true }
   ```

3. **Guardar credenciales en BD**
   - Insertar en tabla `usuarios`
   - Hash password con bcrypt
   - Generar token de email verification (opcional)

## Testing

1. Abre: `http://localhost/AppEgresados/views/auth/register-step-1.php`
2. Selecciona un rol (Egresado por defecto)
3. Haz clic en "Continuar"
4. Ingresa datos de verificación
5. Haz clic en "Continuar"
6. Ingresa nombre y apellidos
7. Haz clic en "Generar Credenciales"
8. Copia las credenciales del modal
9. Ve al login e inicia sesión

## Notes

- El estado se mantiene en `sessionStorage` (no persiste al cerrar navegador)
- Para persistencia entre sesiones, usar base de datos o cookies
- Las credenciales se generan en cliente (en producción, hacer en servidor)
- Los botones "Atrás" usan `window.history.back()`
- Los modales usan Bootstrap 5


2024050567
LOAP900315HDFLRL05

Juan
Pérez García