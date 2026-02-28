# Guía de Integración Backend - Registro

## Arquitectura

El registro funciona en **3 pasos** con comunicación **cliente-servidor**:

```
Step 1: Seleccionar rol
   ↓ (sessionStorage)
Step 2: Verificar identidad
   ↓ (sessionStorage)
Step 3: Crear usuario
   ↓ (API POST)
Backend: Validar, procesar, guardar en BD
```

## Archivos Implicados

| Archivo | Tipo | Propósito |
|---------|------|----------|
| `views/auth/register-step-1.php` | View | Seleccionar rol |
| `views/auth/register-step-2.php` | View | Verificar identidad |
| `views/auth/register-step-3.php` | View | Información y credenciales |
| `app/controllers/RegisterController.php` | Controller | Lógica de registro (YA CREADO) |
| `app/models/Usuario.php` | Model | Operaciones BD (ACTUALIZADO) |
| `app/helpers/Security.php` | Helper | CSRF, hashing, sanitización |

## Implementación Paso a Paso

### FASE 1: Actualizar Step 1 (Rol Selection)

**Archivo:** `views/auth/register-step-1.php`

El Step 1 ya está completo. Solo almacena el rol en `sessionStorage` y valida cliente-side.

**Status:** ✅ COMPLETO

### FASE 2: Actualizar Step 2 (Verificación)

**Archivo:** `views/auth/register-step-2.php`

Necesita agregar validación backend:

```php
<?php
// Al inicio del archivo, después de incluir helpers
require_once __DIR__ . '/../controllers/RegisterController.php';

$registroController = new RegisterController();
$validacion = null;

// Si es POST, validar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    $role = $_SESSION['registro_rol'] ?? $_POST['role'] ?? '';
    
    // Preparar datos según el rol
    if ($role === 'egresado') {
        $data = [
            'matricula' => $_POST['matricula'] ?? '',
            'curp' => $_POST['curp'] ?? ''
        ];
    } elseif ($role === 'docente') {
        $data = [
            'id_docente' => $_POST['id_docente'] ?? ''
        ];
    } else {
        $data = [
            'id_ti' => $_POST['id_ti'] ?? ''
        ];
    }
    
    // Validar en backend
    $validacion = $registroController->validateVerification($role, $data);
    
    if ($validacion['success']) {
        // Guardar en sesión para Step 3
        $_SESSION['registro_verificacion'] = $data;
        $_SESSION['registro_rol'] = $role;
        
        // Redirigir a Step 3
        header('Location: register-step-3.php');
        exit;
    }
}
?>
```

**Status:** 🟡 PARCIAL - Necesita agregar PHP backend

### FASE 3: Actualizar Step 3 (Crear Usuario)

**Archivo:** `views/auth/register-step-3.php`

Necesita llamar a API o submeter a backend handler:

```php
<?php
// Al inicio del archivo
require_once __DIR__ . '/../controllers/RegisterController.php';

$registroController = new RegisterController();
$resultadoRegistro = null;

// Si es POST, crear usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    $nombre = $_POST['nombre'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $role = $_SESSION['registro_rol'] ?? '';
    $verificacionData = $_SESSION['registro_verificacion'] ?? [];
    
    // Generar email temporal o solicitarlo
    $email = $nombre . '.' . explode(' ', $apellidos)[0] . '@egresados.utp.edu.mx';
    $email = strtolower(str_replace(' ', '', $email));
    
    // Crear usuario en backend
    $resultadoRegistro = $registroController->createUser(
        $nombre,
        $apellidos,
        $role,
        $email,
        $verificacionData
    );
    
    if ($resultadoRegistro['success']) {
        // Guardar en sesión y mostrar credenciales
        $_SESSION['nuevas_credenciales'] = [
            'usuario' => $resultadoRegistro['usuario'],
            'password' => $resultadoRegistro['password'],
            'email' => $resultadoRegistro['email']
        ];
    }
}
?>
```

**Status:** 🟡 PARCIAL - Necesita agregar PHP backend

### FASE 4: Crear API Endpoints (Alternativa a PHP)

Si prefieres usar **AJAX + API REST**, crea endpoints:

#### POST `/api/auth/verify-user`

```php
<?php
// file: public/api/auth/verify-user.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/controllers/RegisterController.php';

$registroController = new RegisterController();

$role = $_POST['role'] ?? '';
$data = [
    'matricula' => $_POST['matricula'] ?? '',
    'curp' => $_POST['curp'] ?? '',
    'id_docente' => $_POST['id_docente'] ?? '',
    'id_ti' => $_POST['id_ti'] ?? ''
];

$resultado = $registroController->validateVerification($role, $data);

echo json_encode($resultado);
?>
```

#### POST `/api/auth/create-user`

```php
<?php
// file: public/api/auth/create-user.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/controllers/RegisterController.php';

$registroController = new RegisterController();

$nombre = $_POST['nombre'] ?? '';
$apellidos = $_POST['apellidos'] ?? '';
$role = $_POST['role'] ?? '';
$email = $_POST['email'] ?? '';
$verificacionData = json_decode($_POST['verificacion_data'] ?? '{}', true);

$resultado = $registroController->createUser(
    $nombre,
    $apellidos,
    $role,
    $email,
    $verificacionData
);

echo json_encode($resultado);
?>
```

## Estado Actual vs. TODO

| Componente | Estado | Prioridad |
|------------|--------|-----------|
| Step 1 UI + JS | ✅ Completo | - |
| Step 2 UI + JS | ✅ Completo | - |
| Step 3 UI + JS | ✅ Completo | - |
| RegisterController | ✅ Creado | 🔴 URGENTE |
| Usuario model | ✅ Actualizado | - |
| Step 2 PHP backend | ❌ Falta | 🔴 URGENTE |
| Step 3 PHP backend | ❌ Falta | 🔴 URGENTE |
| Validación BD (matricula) | ❌ Falta | 🟠 IMPORTANTE |
| Validación BD (docente) | ❌ Falta | 🟠 IMPORTANTE |
| Validación BD (TI) | ❌ Falta | 🟠 IMPORTANTE |
| Email verification | ❌ Falta | 🟡 SECUNDARIO |
| OTP system | ❌ Falta | 🟡 SECUNDARIO |
| Welcome email | ❌ Falta | 🟡 SECUNDARIO |

## PRÓXIMOS PASOS

### 1. **Actualizar register-step-2.php** (Validación)
- Agregar PHP backend handler
- Consultar RegisterController::validateVerification()
- Guardar datos verificados en $_SESSION
- Mostrar errores si validación falla

### 2. **Actualizar register-step-3.php** (Crear Usuario)
- Agregar PHP backend handler
- Consultar RegisterController::createUser()
- Guardar credenciales en $_SESSION
- Mostrar modal con usuario/contraseña generados

### 3. **Conectar con bases de datos reales** (FUTURO)
- Sistema de egresados UTP (validar matrícula/CURP)
- Sistema de docentes UTP (validar ID docente)
- Sistema Personal TI (validar ID TI)

### 4. **Agregar validaciones externas** (FUTURO)
- CURP validator service
- Email verification via token
- SMS OTP verification
- Double factor authentication

## Testing Local

**URL de prueba:**
```
http://localhost/AppEgresados/views/auth/register-step-1.php
```

**Test Flow:**
1. Selecciona rol (Egresado)
2. Ingresa data: matrícula=2019010123, curp=PEJI900112HDFRNN01
3. Sistema valida y guarda en sesión
4. Avanza a Step 3
5. Ingresa: Juan Pérez
6. Sistema genera: usuario=juan.perez, password=Abc123!@Temp
7. Modal muestra credenciales
8. Click "Ir al Login"
9. Ingresa credenciales en login.php

## Esquema Base de Datos (Recomendado)

```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    contraseña VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    tipo_usuario ENUM('egresado', 'docente', 'ti') NOT NULL,
    
    -- Campos de verificación
    matricula VARCHAR(20) NULLABLE,
    curp VARCHAR(18) NULLABLE,
    id_docente VARCHAR(20) NULLABLE,
    id_ti VARCHAR(20) NULLABLE,
    
    -- Estado
    activo BOOLEAN DEFAULT true,
    email_verificado BOOLEAN DEFAULT false,
    fecha_verificacion_email DATETIME NULLABLE,
    
    -- Auditoría
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_login DATETIME NULLABLE,
    fecha_ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_usuario ON usuarios(usuario);
CREATE INDEX idx_email ON usuarios(email);
CREATE INDEX idx_tipo_usuario ON usuarios(tipo_usuario);
```

## Notas de Seguridad

1. ✅ CSRF token en login (implementado en Security.php)
2. ⚠️ Agregar CSRF token en registration forms
3. ⚠️ Validar servidor-side TODOS los datos
4. ⚠️ No confiar en cliente-side validation
5. ✅ Contraseñas hasheadas con bcrypt (lista en RegisterController)
6. ⚠️ Implementar rate limiting en endpoints
7. ⚠️ Implementar email verification antes de activar cuenta

## Archivos para Crear

Para completar la integración, necesitas crear o actualizar:

```
❌ public/api/auth/verify-user.php          (será un endpoint para Step 2)
❌ public/api/auth/create-user.php          (será un endpoint para Step 3)
❌ public/api/errors/register-errors.json   (configuración de errores)
🟡 views/auth/register-step-2.php          (agregar PHP backend)
🟡 views/auth/register-step-3.php          (agregar PHP backend)
```

## Recomendación Final

**Opción 1: Implementar directo en vistas (MÁS SIMPLE)**
- Actualizar register-step-2.php y 3.php con PHP backend
- POST directo a las mismas vistas
- Validar y procesar con RegisterController
- Más simple, menos reques HTTP

**Opción 2: Crear API REST (MÁS ESCALABLE)**
- Crear endpoints en public/api/auth/
- Usar AJAX desde vistas
- Respuestas JSON
- Más modular, reutilizable

Yo recomiendo **Opción 1** por ahora (más simple), luego refactorizar a Opción 2 cuando escales.
