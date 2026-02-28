 # Inventario Completo — AppEgresados (Sistema de Egresados UTP)

> Generado automáticamente a partir de lectura directa de cada archivo del proyecto.
> **Lectura únicamente — ningún archivo fue modificado.**

---

## 1. Visión General

| Aspecto | Detalle |
|---|---|
| Nombre | Sistema de Egresados — Universidad Tecnológica de Puebla (UTP) |
| Lenguaje | PHP 8.0+ |
| Base de datos | MySQL (`bolsa_trabajo_utp`), PDO |
| UI | Bootstrap 5.3.0 (CDN), Bootstrap Icons 1.11.0 |
| Servidor | XAMPP / Apache en Windows |
| Ruta local | `C:\xampp\htdocs\AppEgresados` |
| URL base | `/AppEgresados` |
| Arquitectura | MVC personalizado sin framework. Las vistas incluyen guardas de sesión y lógica POST inline |
| Roles | `egresado`, `docente`, `ti` (registro), `admin` |
| Autoloading | PSR-4 — namespace `App\` → directorio `app/` |

---

## 2. Esquema de Base de Datos

Definido en 6 archivos SQL dentro de `database/migrations/`.

### 2.1 `usuarios`

| Columna | Tipo | Notas |
|---|---|---|
| id | INT PK AI | |
| usuario | VARCHAR(100) UNIQUE | Auto-generado como `nombre.apellido` |
| email | VARCHAR(255) | Correo personal |
| email_institucional | VARCHAR(255) | `@alumno.utpuebla.edu.mx` o `@utpuebla.edu.mx` |
| email_verificado | TINYINT(1) DEFAULT 0 | |
| contraseña | VARCHAR(255) | bcrypt hash |
| nombre | VARCHAR(100) | |
| apellidos | VARCHAR(100) | |
| tipo_usuario | ENUM('egresado','docente','ti','admin') | |
| activo | TINYINT(1) DEFAULT 1 | |
| requiere_cambio_pass | TINYINT(1) DEFAULT 1 | Fuerza cambio en primer login |
| fecha_creacion | TIMESTAMP | |
| fecha_ultima_login | TIMESTAMP NULL | |
| verificacion_estado | ENUM('pendiente','verificado','rechazado') DEFAULT 'pendiente' | |
| verificacion_motivo_rechazo | TEXT NULL | |
| verificacion_fecha | TIMESTAMP NULL | |

### 2.2 `egresados`

| Columna | Tipo | Notas |
|---|---|---|
| id | INT PK AI | |
| id_usuario | INT FK→usuarios | |
| matricula | VARCHAR(20) UNIQUE | 10 dígitos |
| curp | VARCHAR(18) UNIQUE | 18 caracteres alfanuméricos |
| correo_personal | VARCHAR(255) | |
| telefono | VARCHAR(20) | |
| genero | ENUM('masculino','femenino','otro','prefiero_no_decir') | |
| año_nacimiento | INT | |
| especialidad | VARCHAR(100) | |
| generacion | VARCHAR(20) | |
| trabaja_actualmente | TINYINT(1) DEFAULT 0 | Seguimiento laboral ↓ |
| trabaja_en_ti | TINYINT(1) DEFAULT 0 | |
| empresa_actual | VARCHAR(255) | |
| puesto_actual | VARCHAR(255) | |
| modalidad_trabajo | ENUM('presencial','hibrido','remoto') | |
| jornada_trabajo | ENUM('completo','parcial','freelance') | |
| ubicacion_trabajo | VARCHAR(255) | |
| tipo_contrato | ENUM('indefinido','temporal','proyecto','honorarios') | |
| fecha_inicio_empleo | DATE | |
| rango_salarial | VARCHAR(50) | |
| prestaciones | JSON | |
| anos_experiencia_ti | INT | |
| descripcion_experiencia | TEXT | |
| habilidades | JSON | |
| cv_path | VARCHAR(500) | Ruta de archivo CV subido |
| fecha_actualizacion_seguimiento | TIMESTAMP | |

### 2.3 `ofertas`

| Columna | Tipo | Notas |
|---|---|---|
| id | INT PK AI | |
| id_usuario_creador | INT FK→usuarios | Docente que crea la oferta |
| titulo | VARCHAR(255) | |
| empresa | VARCHAR(255) | |
| ubicacion | VARCHAR(255) | |
| modalidad | ENUM('presencial','hibrido','remoto') | |
| jornada | ENUM('completo','parcial','freelance') | |
| salario_min | DECIMAL(10,2) | |
| salario_max | DECIMAL(10,2) | |
| beneficios | JSON | |
| habilidades | JSON | |
| descripcion | TEXT | |
| requisitos | JSON | |
| contacto | VARCHAR(255) | |
| estado | ENUM('pendiente_aprobacion','aprobada','rechazada') | |
| estado_vacante | ENUM('verde','amarillo','rojo') DEFAULT 'verde' | Semáforo de vacantes |
| vacantes | INT DEFAULT 1 | |
| especialidad_requerida | VARCHAR(100) | |
| experiencia_minima | INT DEFAULT 0 | |
| fecha_creacion | TIMESTAMP | |
| fecha_expiracion | DATE | |
| fecha_aprobacion | TIMESTAMP NULL | |
| id_admin_aprobador | INT NULL | |
| razon_rechazo | TEXT NULL | |

### 2.4 `postulaciones`

| Columna | Tipo | Notas |
|---|---|---|
| id | INT PK AI | |
| id_oferta | INT FK→ofertas | |
| id_egresado | INT FK→egresados | |
| fecha_postulacion | TIMESTAMP | |
| estado | ENUM('pendiente','preseleccionado','contactado','rechazado') DEFAULT 'pendiente' | |
| validacion_automatica | TINYINT(1) DEFAULT 0 | |
| mensaje | TEXT | |

### 2.5 `codigos_verificacion`

| Columna | Tipo | Notas |
|---|---|---|
| id | INT PK AI | |
| email | VARCHAR(255) | |
| codigo | VARCHAR(6) | 6 dígitos aleatorios |
| tipo | ENUM('registro','recuperacion') | |
| usado | TINYINT(1) DEFAULT 0 | |
| intentos | INT DEFAULT 0 | Máximo 5 |
| fecha_creacion | TIMESTAMP | |
| fecha_expiracion | TIMESTAMP | +10 minutos |

### 2.6 `notificaciones`

| Columna | Tipo | Notas |
|---|---|---|
| id | INT PK AI | |
| id_usuario | INT FK→usuarios | |
| tipo | ENUM('oferta_nueva','oferta_aprobada','oferta_rechazada','nueva_postulacion','postulacion_seleccionada','postulacion_rechazada','nuevo_usuario','general') | |
| titulo | VARCHAR(255) | |
| mensaje | TEXT | |
| url | VARCHAR(500) | Ruta relativa para navegación |
| leida | TINYINT(1) DEFAULT 0 | |
| fecha_creacion | TIMESTAMP | |

---

## 3. Modelos (`app/models/`)

### 3.1 `Database.php` (107 líneas)

Clase base. Conexión PDO a `bolsa_trabajo_utp` en `localhost` con usuario `root` (sin contraseña).

| Método | Descripción |
|---|---|
| `__construct()` | Crea conexión PDO, modo excepciones |
| `query($sql, $params)` | Ejecuta prepared statement |
| `fetchAll($sql, $params)` | Retorna array de rows |
| `fetchOne($sql, $params)` | Retorna una row |
| `insert($table, $data)` | INSERT genérico, retorna `lastInsertId` |
| `update($table, $data, $where, $params)` | UPDATE genérico |
| `delete($table, $where, $params)` | DELETE genérico |
| `count($table, $where, $params)` | COUNT genérico |

### 3.2 `Usuario.php` (341 líneas)

Extiende `Database`.

| Método | Retorna | Descripción |
|---|---|---|
| `getByEmail($email)` | row | Busca por email |
| `getByUsuario($usuario)` | row | Busca por username |
| `getById($id)` | row | Busca por ID |
| `create($data)` | ID | Inserta usuario básico |
| `createFull($data)` | ID | Inserta con todos los campos |
| `createEgresado($data)` | ID | Inserta en tabla `egresados` |
| `verifyPassword($plain, $hash)` | bool | `password_verify()` |
| `updateLastLogin($id)` | — | Actualiza `fecha_ultima_login` |
| `updatePassword($id, $hash)` | — | Cambia contraseña + `requiere_cambio_pass=0` |
| `emailExists($email)` | bool | |
| `usuarioExists($usuario)` | bool | |
| `matriculaExists($mat)` | bool | |
| `curpExists($curp)` | bool | |
| `updateProfile($id, $data)` | — | Actualización de perfil |
| `getAll()` | rows | Todos los usuarios |
| `getAdminStats()` | row | Conteos: total, activos, bloqueados, verificados |
| `getAllForAdmin()` | rows | Lista completa con campos admin |
| `toggleBlock($id, $block)` | — | Activa/desactiva cuenta |
| `resetPassword($id, $tempPass)` | — | Hash + `requiere_cambio_pass=1` |
| `updateUserAdmin($id, $data)` | — | Edita nombre/rol/estado |
| `getPendingVerification()` | rows | Estado `pendiente` |
| `getAllVerification($tipo)` | rows | Por tipo de usuario |
| `verifyUser($id)` | — | Marca `verificado` |
| `rejectVerification($id, $motivo)` | — | Marca `rechazado` + motivo |
| `countPendingVerification()` | row | Conteos por tipo |
| `getByInstitutionalEmail($email)` | row | |
| `updateInstitutionalEmail($id, $email)` | — | + `email_verificado=1` |
| `createVerificationCode($email, $code, $tipo)` | ID | Inserta código 6 dígitos, exp. 10 min |
| `getVerificationCode($email, $code, $tipo)` | row | Código válido (no usado, no expirado) |
| `invalidateVerificationCodes($email, $tipo)` | — | Marca todos como usados |
| `incrementVerificationAttempts($id)` | — | +1 intento |
| `markVerificationCodeUsed($id)` | — | Marca usado |

### 3.3 `Egresado.php` (120 líneas)

Extiende `Database`.

| Método | Retorna | Descripción |
|---|---|---|
| `getByUsuarioId($id)` | row | JOIN con `usuarios` |
| `getByMatricula($mat)` | row | |
| `create($data)` | ID | |
| `updatePerfil($id, $data)` | — | Datos personales |
| `updateSeguimiento($id, $data)` | — | Datos laborales + `fecha_actualizacion_seguimiento` |
| `uploadCV($id, $path)` | — | Guarda ruta CV |
| `getAllWithUser()` | rows | JOIN para directorio |
| `getStats()` | row | total, empleados, en_ti |
| `getSeguimientoStats()` | row | total, empleados, en_ti, salarios promedio |
| `getAllSeguimiento()` | rows | Todos con datos laborales, JOIN usuarios |

### 3.4 `Oferta.php` (390 líneas)

Extiende `Database`.

| Método | Retorna | Descripción |
|---|---|---|
| `getById($id)` | row | JOIN con `usuarios` (creador) |
| `getAllApproved()` | rows | Estado `aprobada` |
| `getApprovedAndActive()` | rows | Aprobada + vacante no `rojo` |
| `create($data)` | ID | |
| `getByUserId($id)` | rows | Ofertas del docente |
| `getStatsByUser($id)` | row | total, pendientes, aprobadas, rechazadas |
| `getTotalPostulantesByUser($userId)` | int | Total postulantes en ofertas del docente |
| `getPostulantesByUser($userId)` | rows | JOIN ofertas + postulaciones + egresados + usuarios |
| `updateOferta($id, $data)` | — | |
| `getPending()` | rows | Estado `pendiente_aprobacion` |
| `getAllForModeration()` | rows | Todas con nombre creador, ORDER BY estado/fecha |
| `getModeracionStats()` | row | pendientes, aprobadas, rechazadas |
| `approve($id, $adminId)` | — | Cambia estado + fecha_aprobacion |
| `reject($id, $razon)` | — | Cambia estado + razon_rechazo |
| `updateVacancyStatus($id)` | — | Semáforo: verde(>50%), amarillo(>0), rojo(0) |

### 3.5 `Postulacion.php` (~90 líneas)

Extiende `Database`.

| Método | Retorna | Descripción |
|---|---|---|
| `getByEgresadoId($id)` | rows | JOIN ofertas + usuarios |
| `getStatsByEgresado($id)` | row | total, pendientes, preseleccionadas, contactadas, rechazadas |
| `hasApplied($ofertaId, $egresadoId)` | bool | |
| `countByOferta($ofertaId)` | int | |
| `create($data)` | ID | |
| `updateEstado($id, $estado)` | — | |
| `getById($id)` | row | |

### 3.6 `Notificacion.php` (~170 líneas)

Extiende `Database`.

| Método | Retorna | Descripción |
|---|---|---|
| `crear($data)` | ID | Inserta notificación |
| `crearParaRol($rol, $data)` | — | Crea para todos los usuarios de un rol |
| `notificarAdmins($data)` | — | → `crearParaRol('admin', …)` |
| `notificarEgresados($data)` | — | → `crearParaRol('egresado', …)` |
| `getByUsuario($userId, $limit)` | rows | Últimas N, ORDER BY fecha DESC |
| `contarNoLeidas($userId)` | int | |
| `marcarLeida($id, $userId)` | — | |
| `marcarTodasLeidas($userId)` | — | |
| `limpiarAntiguas($dias)` | — | Elimina leídas con más de N días |
| `onOfertaCreada($titulo, $creadorId)` | — | Notifica admins |
| `onOfertaAprobada($ofertaId, $titulo, $creadorId)` | — | Notifica creador + todos egresados |
| `onOfertaRechazada($titulo, $creadorId, $razon)` | — | Notifica creador |
| `onPostulacion($ofertaId, $titulo, $creadorId, $egresadoNombre)` | — | Notifica docente creador |
| `onPostulanteSeleccionado($egresadoUserId, $titulo)` | — | Notifica egresado |
| `onPostulanteRechazado($egresadoUserId, $titulo)` | — | Notifica egresado |

---

## 4. Controladores (`app/controllers/`)

### 4.1 `AuthController.php` (~100 líneas)

| Método | Descripción |
|---|---|
| `processLogin()` | Valida CSRF → busca usuario por username o email → `password_verify()` → verifica cuenta activa → setea `$_SESSION` (usuario_id, usuario_nombre, usuario_apellidos, usuario_rol, usuario_usuario, logged_in, requiere_cambio_pass) → redirige por rol (`egresado`→egresado/inicio, `docente`→docente/inicio, `admin`/`ti`→admin/inicio) |

### 4.2 `RegisterController.php` (~230 líneas)

| Método | Descripción |
|---|---|
| `validateRoleSelection()` | Valida `tipo_usuario` en sesión |
| `validateVerification()` | Dispatcher: según rol llama a `validateEgresado()`, `validateDocente()` o `validateTI()` |
| `validateEgresado()` | Matrícula: 10 dígitos exactos, no duplicada. CURP: 18 alfanuméricos, no duplicado |
| `validateDocente()` | ID docente: 6-8 alfanuméricos |
| `validateTI()` | ID TI: 5-6 dígitos |
| `createUser()` | Genera username (`nombre.apellido`, anti-duplicados con sufijo numérico) → genera password seguro 12 chars → hash bcrypt → `Usuario::createFull()` → Si egresado: `Usuario::createEgresado()` con matrícula/curp → guarda credenciales en sesión para mostrar en paso final |
| `generateUsername($nombre, $apellidos)` | `removeAccents()` → lowercase → `nombre.apellido` → si existe añade número |
| `generatePassword()` | 12 chars: mayúsculas + minúsculas + dígitos + especiales |
| `removeAccents($str)` | `strtr()` con mapa de acentos |

### 4.3 `PasswordController.php` (~100 líneas)

| Método | Descripción |
|---|---|
| `changePassword()` | Valida CSRF → verifica contraseña actual → valida nueva (8+ chars, mayúscula, minúscula, dígito, especial) → impide reutilización → `Usuario::updatePassword()` → actualiza sesión `requiere_cambio_pass=0` |

### 4.4 `VerificationController.php` (~250 líneas)

| Método | Descripción |
|---|---|
| `getAllowedDomain($rol)` | egresado→`@alumno.utpuebla.edu.mx`, docente/ti→`@utpuebla.edu.mx` |
| `getDomainLabel($rol)` | Etiqueta para UI |
| `validateInstitutionalEmail($email, $rol)` | Verifica dominio correcto + no duplicado |
| `sendVerificationCode($email, $tipo)` | Invalida códigos previos → genera 6 dígitos → inserta → `simulateEmail()` (dev) |
| `verifyCode($email, $code, $tipo)` | Busca código válido → verifica intentos < 5 → marca usado |
| `verifyRegistrationEmail()` | Valida email institucional → envía código / verifica código → `Usuario::updateInstitutionalEmail()` |
| `sendPasswordResetCode()` | Busca usuario por email → envía código tipo `recuperacion` |
| `resetPassword()` | Valida strength → `Usuario::updatePassword()` |
| `simulateEmail($to, $subject, $body)` | Modo dev: escribe en `storage/logs/emails.log` en lugar de enviar con PHPMailer |

### 4.5 `NotificacionController.php` (~80 líneas)

Archivo procedural (no es clase). Actúa como API endpoint JSON.

| Acción (GET/POST) | Descripción |
|---|---|
| `count` (GET) | Retorna `{ count: N }` de no leídas |
| `list` (GET) | Retorna últimas 20 notificaciones |
| `read` (POST) | Marca notificación como leída |
| `read_all` (POST) | Marca todas como leídas |

---

## 5. Helper (`app/helpers/Security.php`, ~35 líneas)

| Método estático | Descripción |
|---|---|
| `generateCsrfToken()` | Genera token aleatorio de 32 bytes hex, guarda en `$_SESSION['csrf_token']` |
| `csrfField()` | Retorna `<input type="hidden" name="csrf_token" value="…">` |
| `validateCsrfToken($token)` | `hash_equals()` contra sesión |
| `sanitize($input)` | `htmlspecialchars(trim($input))` |
| `verifyPassword($plain, $hash)` | `password_verify()` |

---

## 6. Configuración

### 6.1 `config/bootstrap.php`

- Define `BASE_URL` = `/AppEgresados`
- CDN: Bootstrap 5.3.0 CSS/JS, Bootstrap Icons 1.11.0
- `include_css($path)` → `<link>` con ruta desde BASE_URL
- `include_js($path)` → `<script>` con ruta desde BASE_URL
- `render_navbar()` → `views/compartido/navbar.php`

### 6.2 `composer.json`

```json
{
  "name": "utp/app-egresados",
  "description": "Sistema de Gestión de Egresados - UTP",
  "type": "project",
  "require": {
    "php": ">=8.0",
    "phpmailer/phpmailer": "^6.5"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0"
  },
  "autoload": {
    "psr-4": { "App\\": "app/" }
  }
}
```

---

## 7. Router (`public/index.php`, 331 líneas)

Switch/case sobre `$_GET['url']` con guardas de sesión por rol.

| Ruta | Archivo destino | Rol requerido |
|---|---|---|
| `/` ó `/login` | `views/auth/login.php` | Público |
| `/logout` | `views/auth/logout.php` | Público |
| `/register/step-1` | `views/auth/register-step-1.php` | Público |
| `/register/step-2` | `views/auth/register-step-2.php` | Público |
| `/register/step-3` | `views/auth/register-step-3.php` | Público |
| `/register/step-4` | `views/auth/register-step-4.php` | Público |
| `/register/success` | `views/auth/credentials-success.php` | Público |
| `/forgot-password` | `views/auth/forgot.php` | Público |
| `/verify-code` | `views/auth/verify-code.php` | Público |
| `/reset-password` | `views/auth/reset-password.php` | Público |
| `/password-updated` | `views/auth/password-updated.php` | Público |
| `/egresado/dashboard` | redirect→inicio | egresado |
| `/egresado/inicio` | `views/egresado/inicio.php` | egresado |
| `/egresado/perfil` | `views/egresado/perfil.php` | egresado |
| `/egresado/ofertas` | `views/egresado/ofertas.php` | egresado |
| `/egresado/oferta/{id}` | `views/egresado/oferta-detalle.php` | egresado |
| `/egresado/postulaciones` | `views/egresado/postulaciones.php` | egresado |
| `/egresado/seguimiento` | `views/egresado/seguimiento.php` | egresado |
| `/egresado/seguridad` | `views/egresado/seguridad.php` | egresado |
| `/docente/dashboard` | redirect→inicio | docente |
| `/docente/inicio` | `views/docente/inicio.php` | docente |
| `/docente/publicar-oferta` | `views/docente/publicar-oferta.php` | docente |
| `/docente/mis-ofertas` | `views/docente/mis-ofertas.php` | docente |
| `/docente/postulantes` | `views/docente/postulantes.php` | docente |
| `/docente/directorio` | `views/docente/directorio.php` | docente |
| `/docente/perfil` | `views/docente/perfil.php` | docente |
| `/docente/seguridad` | `views/docente/seguridad.php` | docente |
| `/admin/dashboard` | redirect→inicio | admin |
| `/admin/inicio` | `views/admin/inicio.php` | admin |
| `/admin/moderacion` | `views/admin/moderacion/list.php` | admin |
| `/admin/verificacion` | `views/admin/verificacion/list.php` | admin |
| `/admin/seguimiento` | `views/admin/seguimiento/list.php` | admin |
| `/admin/usuarios` | `views/admin/users.php` | admin |
| `/admin/seguridad` | `views/admin/seguridad.php` | admin |
| `/notificaciones` | `views/notificaciones/index.php` | Cualquier rol autenticado |

---

## 8. Vistas — Inventario Detallado

### 8.1 Autenticación (`views/auth/`)

| Archivo | Líneas | Modelos/Helpers | POST | Descripción |
|---|---|---|---|---|
| `login.php` | 260 | AuthController, Security | `AuthController::processLogin()` | Dos columnas (hero + form). Acepta usuario o email. CSRF protegido |
| `register-step-1.php` | ~130 | — | — (client-side) | Selección de rol: egresado/docente/TI. Guarda en `sessionStorage` |
| `register-step-2.php` | ~200 | RegisterController, Security | `RegisterController::validateVerification()` | Validación de identidad por rol |
| `register-step-3.php` | ~180 | RegisterController, Security | `RegisterController::createUser()` | Nombre y apellidos → auto-genera usuario+contraseña |
| `register-step-4.php` | ~280 | VerificationController, Security | `send_code`, `verify_code`, `resend_code` | Verificación email institucional con código 6 dígitos |
| `credentials-success.php` | ~120 | — | — | Muestra usuario+contraseña generados con botones de copiar. Limpia sesión |
| `forgot.php` | ~140 | VerificationController, Security | `sendPasswordResetCode()` | Ingreso de email para reset |
| `verify-code.php` | ~190 | VerificationController, Security | `verifyCode()` | Código 6 dígitos con auto-focus en inputs |
| `reset-password.php` | ~180 | VerificationController, Security | `resetPassword()` | Nueva contraseña + confirmación |
| `password-updated.php` | ~70 | — | — | Confirmación con enlace a login |
| `logout.php` | ~15 | — | — | `session_destroy()` + borrar cookies + redirect |

### 8.2 Egresado (`views/egresado/`)

| Archivo | Líneas | Modelos/Helpers | POST | Descripción |
|---|---|---|---|---|
| `inicio.php` | 249 | Oferta, Egresado, Postulacion | — | Dashboard: 4 KPIs (ofertas disponibles, postulaciones, en revisión, perfil %). Acciones rápidas |
| `ofertas.php` | ~300 | Oferta | — | Listado de ofertas aprobadas+activas. Filtros client-side: búsqueda, ubicación, modalidad, habilidades |
| `oferta-detalle.php` | 320 | Oferta, Egresado, Postulacion, Notificacion, Security | Crea postulación + notificación + actualiza semáforo vacante | Detalle completo. Cálculo de match de habilidades |
| `postulaciones.php` | ~300 | Egresado, Postulacion | — | Lista de postulaciones propias con badges de estado y stats |
| `perfil.php` | ~300 | Egresado, Usuario, Security | `Egresado::updatePerfil()` | Editor con tabs. Campos protegidos: nombre, matrícula, CURP |
| `seguimiento.php` | 364 | Egresado, Security | `Egresado::updateSeguimiento()` | Formulario laboral: situación, contrato, ingresos, experiencia. Aviso de privacidad |
| `seguridad.php` | 259 | PasswordController, Security | `PasswordController::changePassword()` | Cambio de contraseña con indicador de fuerza |

### 8.3 Docente (`views/docente/`)

| Archivo | Líneas | Modelos/Helpers | POST | Descripción |
|---|---|---|---|---|
| `inicio.php` | 168 | Oferta | — | Dashboard: KPIs (total ofertas, pendientes, activas, total postulantes). Sin POST |
| `publicar-oferta.php` | 342 | Oferta, Notificacion, Security | `Oferta::create()` + `Notificacion::onOfertaCreada()` | Formulario completo de oferta: título, empresa, ubicación, modalidad, jornada, salario, beneficios JSON, habilidades JSON, requisitos JSON, vacantes, contacto, expiración. Estado inicial: `pendiente_aprobacion` |
| `mis-ofertas.php` | 178 | Oferta | — | Lista de ofertas propias con badges de estado y vacante (semáforo) |
| `postulantes.php` | 307 | Oferta, Postulacion, Notificacion, Security | `Postulacion::updateEstado()` + notificaciones | Gestión de postulantes: filtros por oferta/estado. Acciones: preseleccionar, contactar, rechazar. Envía notificaciones al egresado |
| `directorio.php` | 231 | Egresado | — | Browse de egresados con filtros (búsqueda, generación, especialidad). Solo datos públicos |
| `perfil.php` | 211 | Usuario, Security | `Usuario::updateProfile()` | Edita email. Nombre y username protegidos |
| `seguridad.php` | 192 | PasswordController, Security | `PasswordController::changePassword()` | Cambio de contraseña |

### 8.4 Administrador (`views/admin/`)

| Archivo | Líneas | Modelos/Helpers | POST | Descripción |
|---|---|---|---|---|
| `inicio.php` | 214 | Usuario, Oferta, Egresado | — | Panel con KPIs: ofertas pendientes, activas, egresados registrados, verificados. Acciones prioritarias: moderar ofertas, verificar usuarios. Gestión: seguimiento, usuarios, auditoría (próximamente). Estado del sistema: ofertas activas, tasa verificación, usuarios activos |
| `users.php` | 534 | Usuario, Security | `edit_user`, `reset_password`, `toggle_block` | Gestión completa de usuarios. Stats (total, activos, bloqueados, pendientes verif). Filtros: búsqueda, rol, estado. Tabla desktop + tarjetas mobile. Acciones: editar (nombre/rol/estado), resetear contraseña, bloquear/desbloquear. Protección: no puede editarse a sí mismo |
| `seguridad.php` | 182 | PasswordController, Security | `PasswordController::changePassword()` | Cambio de contraseña del admin |
| `moderacion/list.php` | 483 | Oferta, Notificacion, Security | `aprobar`, `rechazar` | Moderación de ofertas. Stats de pendientes/aprobadas/rechazadas. Aprobar: `Oferta::approve()` + `Notificacion::onOfertaAprobada()` (notifica creador + todos egresados). Rechazar: `Oferta::reject()` + `Notificacion::onOfertaRechazada()` (notifica creador con razón) |
| `verificacion/list.php` | 534 | Usuario, Security | `verificar`, `rechazar` | Verificación de identidad. Tabs: Egresados / Docentes. Stats: total pendientes por tipo. Aprobar: `Usuario::verifyUser()`. Rechazar: `Usuario::rejectVerification($id, $motivo)` |
| `seguimiento/list.php` | 463 | Egresado | — | Consulta de situación laboral. Stats: total, empleados, en TI, tasa empleo. Filtros: búsqueda, generación, especialidad, situación laboral. Datos confidenciales (aviso). Labels: modalidad, contrato, jornada |

### 8.5 Notificaciones (`views/notificaciones/`)

| Archivo | Líneas | Modelos/Helpers | POST | Descripción |
|---|---|---|---|---|
| `index.php` | 329 | Notificacion, Security | `mark_read`, `mark_all_read` | Vista compartida (cualquier rol autenticado). Lista últimas 50 notificaciones. Click en notificación: marca leída vía fetch() + navega a URL. Botón "Marcar todas como leídas". Iconos y colores por tipo de notificación. CSS inline para estilos de lista |

### 8.6 Compartido (`views/compartido/`)

| Archivo | Descripción |
|---|---|
| `topbar.html` | Header: logo UTP + "Sistema de Egresados", campana de notificaciones con badge `#notifCount`, avatar con dropdown (perfil/seguridad/cerrar sesión). Placeholders `{BASE}`, `{ASSETS}`, `{APP}` reemplazados por JS |
| `sidebar-egresado.html` | Nav: Inicio, Ofertas, Mis Postulaciones, Mi Perfil, Seguimiento, Cerrar sesión |
| `sidebar-docente.html` | Nav: Inicio, Nueva Oferta, Mis Ofertas, Postulantes, Directorio, Mi Perfil, Cerrar sesión |
| `sidebar-admin.html` | Nav: Panel, Moderar Ofertas, Verificar Usuarios, Seguimiento, Usuarios, Seguridad, Cerrar sesión |
| `notice-password.html` | Barra de aviso para contraseña temporal con botón "Cambiar ahora" y dismiss |

### 8.7 Layout (`views/layouts/`)

| Archivo | Descripción |
|---|---|
| `base.php` | Layout base (no utilizado activamente; cada vista incluye su propio `<html>` completo) |

---

## 9. Arquitectura CSS (`public/assets/css/`)

### Archivo maestro: `app-main.css`

Importa en orden:
1. `theme.css` — Variables CSS (colores, tipografía, espaciado)
2. `layout.css` — Grid, sidebar, topbar, contenido principal
3. `forms.css` — Inputs, selects, labels, validación
4. `components.css` — Cards (utp-card), badges, KPIs, action cards, mini-iconos
5. `pages.css` — Estilos específicos por página (importa subcarpeta `pages/`)
6. `auth.css` — Páginas de login/registro (hero, splits)
7. `utilities.css` — Helpers (bg-soft, text truncation, etc.)

Además, `app-main.css` define:
- `body`: font-family system stack, `#FAFAFA` fondo
- Scrollbar custom (`#e0e0e0`, 6px)
- Focus visible: outline `#004CEB` con offset

### Archivos page-specific (`pages/`)
- `admin.css` — Estilos panel admin
- `docente.css` — Estilos vistas docente
- `egresado.css` — Estilos vistas egresado
- `shared.css` — Estilos compartidos entre roles

### Archivo independiente: `global.css`
Existe pero no es importado por `app-main.css` (posible legado).

---

## 10. Arquitectura JavaScript (`public/assets/js/`)

### 10.1 `app.js` (~85 líneas, raíz)

- Inicializa tooltips y popovers de Bootstrap
- Auto-dismiss de alertas tras 10 segundos
- Sistema de toast notifications
- Validación nativa de formularios Bootstrap (`was-validated`)

### 10.2 `shared/app.js` (~45 líneas)

- Init Bootstrap específico para dashboards
- Auto-cierre de alertas tras 5 segundos
- Protección contra doble-submit en formularios

### 10.3 `shared/components-loader.js` (~160 líneas)

Cargador dinámico de componentes compartidos:
1. Detecta `window.UTP_DATA` (role, fullName, initials, currentPage, requirePasswordChange)
2. Carga `topbar.html`, `sidebar-{role}.html`, `notice-password.html` vía `fetch()`
3. Reemplaza placeholders `{BASE}`, `{ASSETS}`, `{APP}` con rutas calculadas
4. Rellena datos de usuario (iniciales, nombre, label de rol)
5. Marca página activa en sidebar
6. Reinicializa Dropdowns / Collapse / Tooltips de Bootstrap
7. Carga `notifications.js` dinámicamente

### 10.4 `shared/notifications.js` (~55 líneas)

- Polling cada 30 segundos a `/public/api/notificaciones.php?action=count`
- Actualiza badge `#notifCount` en topbar
- Muestra/oculta badge según cantidad

### 10.5 `egresado/seguridad.js` (~155 líneas)

- Toggle de visibilidad de contraseña (ojo abierto/cerrado)
- Indicador de fuerza en tiempo real (5 requisitos: longitud, mayúscula, minúscula, dígito, especial)
- Validación de coincidencia de confirmación
- Bloqueo de submit si no cumple todos los requisitos

### 10.6 `egresado/inicio.js` (~25 líneas)

- Muestra modal de recordatorio de seguridad si `requirePasswordChange === true`

---

## 11. API Endpoint

### `public/api/notificaciones.php`

Archivo proxy que incluye `app/controllers/NotificacionController.php`.

| Método HTTP | Parámetro `action` | Respuesta |
|---|---|---|
| GET | `count` | `{ "count": int }` |
| GET | `list` | `[ { id, tipo, titulo, mensaje, url, leida, fecha_creacion }, … ]` |
| POST | `read` | `{ "success": true }` |
| POST | `read_all` | `{ "success": true }` |

---

## 12. Flujos Principales

### 12.1 Registro (4 pasos)

```
Step 1: Seleccionar rol (egresado/docente/ti) → sessionStorage
Step 2: Validar identidad (matrícula+CURP / ID docente / ID TI)
Step 3: Ingresar nombre+apellidos → auto-genera usuario+contraseña 12 chars
Step 4: Verificar email institucional con código 6 dígitos (10 min exp, max 5 intentos)
→ Resultado: Pantalla con credenciales generadas + botones de copiar
```

### 12.2 Login

```
1. Usuario ingresa nombre de usuario O email + contraseña
2. AuthController::processLogin() busca por username, luego por email
3. password_verify() contra hash bcrypt
4. Verifica cuenta activa
5. Setea sesión (id, nombre, apellidos, rol, usuario, logged_in, requiere_cambio_pass)
6. Redirige según rol
7. Si requiere_cambio_pass → notice-password.html aparece en dashboard
```

### 12.3 Recuperación de Contraseña

```
1. /forgot-password → ingresa email → sendPasswordResetCode()
2. /verify-code → ingresa código 6 dígitos
3. /reset-password → nueva contraseña (8+ chars, upper/lower/digit/special)
4. /password-updated → confirmación
```

### 12.4 Ciclo de vida de una Oferta

```
1. Docente → publicar-oferta.php → Oferta::create(estado='pendiente_aprobacion')
   → Notificacion::onOfertaCreada() → notifica admins
2. Admin → moderacion/list.php → aprobar o rechazar
   Aprobar: Oferta::approve() → Notificacion::onOfertaAprobada() → notifica creador + todos egresados
   Rechazar: Oferta::reject() → Notificacion::onOfertaRechazada() → notifica creador
3. Egresado → ofertas.php → oferta-detalle.php → Postulacion::create()
   → Oferta::updateVacancyStatus() (semáforo verde/amarillo/rojo)
   → Notificacion::onPostulacion() → notifica docente
4. Docente → postulantes.php → cambiar estado (preseleccionar/contactar/rechazar)
   → Notificacion::onPostulanteSeleccionado() o onPostulanteRechazado() → notifica egresado
```

### 12.5 Sistema de Notificaciones

```
Creación: modelos de negocio llaman Notificacion::on*()
Almacenamiento: tabla `notificaciones` con tipo, título, mensaje, URL
Polling: notifications.js cada 30s → GET /api/notificaciones.php?action=count
Badge: #notifCount en topbar actualizado dinámicamente
Vista: /notificaciones → lista con mark_read (fetch+navigate) y mark_all_read
```

---

## 13. Archivos Utilitarios y Debug

| Archivo | Propósito |
|---|---|
| `_check_admin.php` | Verifica existencia de usuario admin en BD |
| `_run_migration_004.php` | Ejecuta migración 004 |
| `database/_run_migration.php` | Runner genérico de migraciones SQL |
| `database/_run_migration_003.php` | Ejecuta migración 003 |
| `database/_seed_test_data.php` | Seed de datos de prueba |
| `database/_check_users.php` | Inspección de usuarios en BD |
| `database/_inspect.php` | Inspección general de BD |
| `database/_inspect_docente.php` | Inspección de datos docente |
| `database/migrations/reset_pass.php` | Reset de contraseña específico |
| `database/migrations/reset_docente_pass.php` | Reset de contraseña docente |
| `database/migrations/run_007.php` | Ejecuta migración 007 (seed data) |
| `database/migrations/run_007_notifs.php` | Ejecuta seed de notificaciones |
| `public/test-bootstrap.php` | Test de carga de Bootstrap |
| `public/verificar-login.html` | Página de verificación de login |

---

## 14. Archivos de Documentación Existentes

| Archivo | Contenido |
|---|---|
| `README_REGISTRO.md` | Documentación del flujo de registro |
| `REGISTRO.md` | Notas de registro |
| `BOOTSTRAP.md` | Notas sobre integración Bootstrap |
| `QUICK_START.md` | Guía de inicio rápido |
| `TESTING.md` | Guía de testing |
| `INTEGRACION_BACKEND.md` | Notas de integración backend |
| `ESTADO_FINAL.md` | Estado final del proyecto |

---

## 15. Seguridad

| Mecanismo | Implementación |
|---|---|
| CSRF | Token por sesión, validado en todo POST via `Security::validateCsrfToken()` |
| Contraseñas | bcrypt (`password_hash` / `password_verify`), forzar cambio en primer login |
| Fuerza de contraseña | 8+ chars, mayúscula, minúscula, dígito, carácter especial |
| Sanitización | `htmlspecialchars(trim())` vía `Security::sanitize()` |
| SQL Injection | Prepared statements (PDO) en todas las queries |
| Sesión | Guardas de rol en cada vista, `session_start()` + verificación `$_SESSION['logged_in']` |
| Verificación de identidad | Matrícula + CURP / ID institucional + email institucional con código 6 dígitos |
| Bloqueo de cuenta | Admin puede bloquear/desbloquear (`activo=0/1`) |
| Prevención doble-submit | JS en `shared/app.js` deshabilita botón tras primer click |

---

## 16. Resumen de Conteos

| Categoría | Cantidad |
|---|---|
| Tablas de BD | 6 |
| Modelos PHP | 6 |
| Controladores PHP | 5 |
| Helpers PHP | 1 |
| Migraciones SQL | 6 |
| Vistas de autenticación | 11 |
| Vistas de egresado | 7 |
| Vistas de docente | 7 |
| Vistas de admin | 6 |
| Vistas compartidas | 1 (notificaciones) |
| Componentes HTML compartidos | 5 |
| Archivos CSS | ~12 |
| Archivos JS | 6 |
| Archivos de documentación | 7 |
| Archivos utilitarios/debug | 14 |
| **Total archivos del proyecto** | **~92** |
