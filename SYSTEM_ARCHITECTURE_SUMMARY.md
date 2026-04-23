# System Architecture Summary: Egresados Job Offers & Applications Management

## Overview
This system manages job offers and applications for graduates (egresados) of UTP. It has multiple roles: admin, docente (teacher), ti (IT staff), and egresado (graduate). Egresados can browse approved offers, submit applications, manage their profiles with soft skills, and track application status. Docentes/TI can create and publish offers (pending admin approval). Admin manages offer approvals and moderates the system.

---

## 1. DATABASE SCHEMA

### Core Tables & Structure

#### **usuarios** (Users/Authentication)
- **File:** [database/setup_laragon.php](database/setup_laragon.php#L108-L128)
- **Roles:** `ENUM('admin','docente','ti','egresado')`
- **Key Fields:**
  - `tipo_usuario` - Determines access level and available features
  - `verificacion_estado` - ENUM('pendiente','verificado','rechazado')
  - `email_verificado` - Boolean flag for email verification
  - `requiere_cambio_pass` - Forces password change on first login

#### **egresados** (Graduate Profiles)
- **File:** [database/setup_laragon.php](database/setup_laragon.php#L130-L168)
- **Key Fields:**
  - `id_usuario` (FK) - Links to usuarios table
  - `matricula` - Student ID (unique)
  - `curp` - National ID (unique)
  - `habilidades` (TEXT/JSON) - Technical skills
  - `habilidades_blandas` (TEXT/JSON) - **Soft skills - Migration 012**
  - `trabaja_actualmente` - Employment status tracking
  - `empresa_actual`, `puesto_actual` - Current job info
  - `modalidad_trabajo` ENUM('presencial','hibrido','remoto')
  - `jornada_trabajo` ENUM('completo','parcial','freelance')
  - `porcentaje_completitud` - Profile completion percentage

#### **ofertas** (Job Offers)
- **File:** [database/setup_laragon.php](database/setup_laragon.php#L170-L198)
- **Key Fields:**
  - `id_usuario_creador` (FK) - Docente/TI who created the offer
  - `id_admin_aprobador` (FK) - Admin who approved it
  - `estado` ENUM('pendiente_aprobacion','aprobada','rechazada')
  - `activo` - Boolean flag (Migration 012)
  - `fecha_baja` - When offer was deactivated
  - `estado_vacante` ENUM('verde','amarillo','rojo') - Vacancy status
  - `vacantes` - Number of open positions
  - `fecha_expiracion` - When offer expires
  - `especilidad_requerida`, `experiencia_minima` - Requirements

**Related Migrations:**
- **Migration 003:** [003_add_ofertas_and_seguimiento_fields.sql](database/migrations/003_add_ofertas_and_seguimiento_fields.sql) - Added empresa, ubicacion, modalidad, jornada, salario_min/max, beneficios, habilidades
- **Migration 011:** [011_add_contact_info_to_ofertas.sql](database/migrations/011_add_contact_info_to_ofertas.sql) - Added contact information fields
- **Migration 012:** [012_add_soft_skills_and_offers_management.sql](database/migrations/012_add_soft_skills_and_offers_management.sql) - Added activo, fecha_baja, motivo_baja

#### **postulaciones** (Applications)
- **File:** [database/setup_laragon.php](database/setup_laragon.php#L200-L216)
- **Key Fields:**
  - `id_oferta` (FK) - References ofertas
  - `id_egresado` (FK) - References egresados
  - `estado` ENUM('pendiente','preseleccionado','contactado','rechazado')
  - `retirada` - Boolean (Migration 012) - Egresado withdrew application
  - `fecha_retiro` - When application was withdrawn
  - `mensaje` - Optional cover letter
  - **Unique constraint:** (id_oferta, id_egresado) - Can't apply twice to same offer

#### **postulacion_habilidades_blandas** (Soft Skills Checklist per Application)
- **File:** [database/setup_laragon.php](database/setup_laragon.php#L246+) | Migration: [013_add_postulacion_soft_skills_checklist.sql](database/migrations/013_add_postulacion_soft_skills_checklist.sql)
- **Purpose:** Track soft skills requirements for each application (for evaluation by hiring manager)
- **Key Fields:**
  - `id_postulacion` (FK) - Links to postulaciones
  - `habilidad` VARCHAR(120) - Skill name
  - `cumple` TINYINT(1) NULL - Evaluated by hiring manager (true/false/null)
  - `evaluado_por` (FK) - Admin/TI user who evaluated
  - `fecha_evaluacion` - When skill was evaluated
  - **Unique constraint:** (id_postulacion, habilidad)

#### **notificaciones** (Notification System)
- **File:** [database/setup_laragon.php](database/setup_laragon.php#L218-L230)
- **Migration:** [006_add_notificaciones.sql](database/migrations/006_add_notificaciones.sql)
- **Types:** ENUM('oferta_nueva','oferta_aprobada','oferta_rechazada','nueva_postulacion','postulacion_seleccionada','postulacion_rechazada','nuevo_usuario','general')

---

## 2. ROLES & PERMISSIONS

### Role-Based Access Control

**Authentication & Role Check:**
- **File:** [app/controllers/AuthController.php](app/controllers/AuthController.php#L89) - Sets `$_SESSION['usuario_rol']` from `tipo_usuario`

**View-Level Authorization:**
All egresado views enforce role check at the top:
```php
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] 
    || ($_SESSION['usuario_rol'] ?? '') !== 'egresado') {
    header('Location: ../auth/login.php');
    exit;
}
```

Examples:
- [views/egresado/ofertas.php](views/egresado/ofertas.php#L1-L5) - Browse approved offers
- [views/egresado/postulaciones.php](views/egresado/postulaciones.php#L1-L5) - View own applications
- [views/egresado/publicar-oferta.php](views/egresado/publicar-oferta.php#L1-L4) - Create offers (egresados can also publish)

### Role-Specific Features

| Action | Admin | Docente | TI | Egresado |
|--------|-------|---------|----|---------| 
| View all offers | ✓ | - | - | Only approved |
| Create offers | - | ✓ | ✓ | ✓ |
| Approve offers | ✓ | - | - | - |
| View applicants | ✓ | ✓ (own) | ✓ (own) | - |
| Update application status | ✓ | ✓ (own) | ✓ (own) | - |
| Apply to offers | - | - | - | ✓ |
| Withdraw application | - | - | - | ✓ |
| Evaluate soft skills | ✓ | ✓ (own) | ✓ (own) | - |

**Email Domain Validation:**
- **File:** [app/controllers/VerificationController.php](app/controllers/VerificationController.php#L21-L27)
- Egresados: `@alumno.utpuebla.edu.mx` (personal email for graduation)
- Docentes/TI: `@utpuebla.edu.mx` (institutional email)

---

## 3. CONTROLLERS FOR OFFER MANAGEMENT

### Oferta Model (Business Logic)
- **File:** [app/models/Oferta.php](app/models/Oferta.php)
- **Key Methods:**
  - `getById($id)` - Get single offer with creator and applicant count
  - `getAllApproved()` - Get all approved offers (including expired)
  - `getApprovedAndActive()` - Get approved, active offers with vacancies (for egresados browsing)
  - `getByUserId($id_usuario)` - Get offers created by a user
  - `create($data)` - Create new offer
  - `update()` - Update offer details
  - `delete()` - Delete offer

**Query Example** - [Line 28-37](app/models/Oferta.php#L28-L37):
```php
// Egresados see only approved, active, available offers
SELECT o.*, CONCAT(u.nombre, ' ', IFNULL(u.apellidos,'')) AS creador,
       (SELECT COUNT(*) FROM postulaciones WHERE id_oferta = o.id) AS postulantes_count
FROM ofertas o
JOIN usuarios u ON o.id_usuario_creador = u.id
WHERE o.estado = 'aprobada' 
AND o.activo = 1
AND o.vacantes > 0
AND o.fecha_expiracion > NOW()
ORDER BY o.fecha_creacion DESC
```

### No Dedicated OfertaController - Uses Direct DB Access
Offers are managed directly through views and API endpoints:
- **Create offer:** [views/egresado/publicar-oferta.php](views/egresado/publicar-oferta.php#L15-L78) - POST handler
- **Update status:** [public/api/ofertas-update.php](public/api/ofertas-update.php) - AJAX API

---

## 4. EGRESADO VIEWS FOR OFFER MANAGEMENT

### Main Egresado Views
All require role check: `$_SESSION['usuario_rol'] === 'egresado'`

#### **ofertas.php** - Browse & Search Offers
- **File:** [views/egresado/ofertas.php](views/egresado/ofertas.php)
- **Features:**
  - Load all approved/active offers
  - Client-side filtering by skills, location, company
  - Search functionality
  - Offer detail cards with application button

#### **postulaciones.php** - Track Applications
- **File:** [views/egresado/postulaciones.php](views/egresado/postulaciones.php)
- **Features:**
  - Display all applications for logged-in egresado
  - Show application stats (enviadas, en_revision, seleccionado, no_seleccionado)
  - Status badges with icons
  - Withdraw/restore application buttons

**Status States:**
```php
$statusMap = [
    'pendiente'       => ['label' => 'Enviada',         'icon' => 'bi-clock-history'],
    'preseleccionado' => ['label' => 'En revisión',     'icon' => 'bi-eye'],
    'contactado'      => ['label' => 'Seleccionado',    'icon' => 'bi-check-circle'],
    'rechazado'       => ['label' => 'No seleccionado', 'icon' => 'bi-x-circle'],
    'retirada'        => ['label' => 'Retirada',        'icon' => 'bi-archive'],
];
```

#### **publicar-oferta.php** - Create New Offer
- **File:** [views/egresado/publicar-oferta.php](views/egresado/publicar-oferta.php#L15-L78)
- **Features:**
  - Form to create offer (title, company, description, requirements, benefits)
  - Parse JSON skills, requisitos, beneficios
  - Salary range, modality, journee selection
  - CSRF token validation
  - Offer created with `estado = 'pendiente_aprobacion'`
  - Automatic expiration (30 days default)
  - Notifies admins after creation

**Creation Fields** [Line 32-75](views/egresado/publicar-oferta.php#L32-L75):
```php
$data = [
    'id_usuario_creador' => $_SESSION['usuario_id'],
    'titulo' => $titulo,
    'empresa' => $empresa,
    'descripcion' => $descripcion,
    'requisitos' => json_encode($requisitos),
    'beneficios' => json_encode($beneficios),
    'habilidades' => json_encode($habilidades),
    'salario_min' => $salarioMin,
    'salario_max' => $salarioMax,
    'modalidad' => $_POST['modalidad'] ?? 'hibrido',
    'jornada' => $_POST['jornada'] ?? 'completo',
    'estado' => 'pendiente_aprobacion',
    'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+30 days')),
];
```

#### **mis-ofertas.php** - Manage Own Offers
- **File:** [views/egresado/mis-ofertas.php](views/egresado/mis-ofertas.php)
- **Features:**
  - List offers created by current user
  - Show offer status (pendiente_aprobacion, aprobada, rechazada)
  - Show vacancy status (verde/amarillo/rojo)
  - Link to create new offer
  - View applicants for each offer

#### **oferta-detalle.php** - View Offer Details & Apply
- **File:** [views/egresado/oferta-detalle.php](views/egresado/oferta-detalle.php)
- **Features:**
  - Full offer details (company, location, salary, requirements, benefits)
  - "Apply" button if not already applied
  - Show existing application status if already applied
  - Estimated skill match percentage

---

## 5. NOTIFICATION SYSTEM

### Notificacion Model
- **File:** [app/models/Notificacion.php](app/models/Notificacion.php)

### Notification Types & Triggers

#### **onOfertaCreada** (Line 107-113)
- **Trigger:** Egresado publishes offer
- **Recipients:** All admins
- **Type:** `'nueva_postulacion'`
- **Message:** "Reviewer published offer X. Review to approve."

#### **onOfertaAprobada** (Line 118-135)
- **Trigger:** Admin approves offer
- **Recipients:** 
  - Offer creator (docente/ti/egresado)
  - ALL egresados
- **Types:** `'oferta_aprobada'` (creator), `'oferta_nueva'` (egresados)

#### **onOfertaRechazada** (Line 140-149)
- **Trigger:** Admin rejects offer
- **Recipients:** Offer creator
- **Type:** `'oferta_rechazada'`

#### **onPostulacion** (Line 154-166)
- **Trigger:** Egresado submits application
- **Recipients:** Offer creator (docente/ti)
- **Type:** `'nueva_postulacion'`

#### **onPostulanteSeleccionado** (Not shown in excerpt)
- **Trigger:** Application status changed to 'contactado'
- **Recipients:** Applicant (egresado)
- **Type:** `'postulacion_seleccionada'`
- **File:** [public/api/postulaciones-update.php](public/api/postulaciones-update.php#L70-L86)

#### **onPostulanteRechazado**
- **Trigger:** Application status changed to 'rechazado'
- **Recipients:** Applicant (egresado)
- **Type:** `'postulacion_rechazada'`

### Notification Storage
- **Table:** `notificaciones`
- **Fields:** id, id_usuario, tipo, titulo, mensaje, url, leida, fecha_creacion
- **Indexes:** idx_usuario, idx_usuario_leida for fast lookups

### Notification API
- **File:** [app/controllers/NotificacionController.php](app/controllers/NotificacionController.php)
- **Endpoints:**
  - `?action=count` - Get unread notification count
  - `?action=list` - List 10-20 most recent notifications
  - `POST ?action=read` - Mark single notification as read
  - `POST ?action=read_all` - Mark all as read
- **Authentication:** Requires `$_SESSION['logged_in'] === true`

---

## 6. SOFT SKILLS / CHECKLIST SYSTEM

### Storage Architecture

#### **egresados.habilidades_blandas** (Egresado Profile)
- **Migration:** [012_add_soft_skills_and_offers_management.sql](database/migrations/012_add_soft_skills_and_offers_management.sql#L6)
- **Storage:** JSON array stored in TEXT field
- **Model Access:** [app/models/Egresado.php](app/models/Egresado.php#L44-L57)
- **Methods:**
  - `updateHabilidadesBlandas($id_usuario, $habilidades)` - Save soft skills to egresado profile
  - `getHabilidadesBlandas($id_usuario)` - Retrieve and decode JSON soft skills

**Example Usage** [Line 47](app/models/Egresado.php#L47):
```php
$data = ['habilidades_blandas' => is_array($habilidades) 
    ? json_encode($habilidades) : $habilidades];
$this->update('egresados', $data, ['id_usuario' => $id_usuario]);
```

#### **postulacion_habilidades_blandas** (Per-Application Checklist)
- **Migration:** [013_add_postulacion_soft_skills_checklist.sql](database/migrations/013_add_postulacion_soft_skills_checklist.sql)
- **Purpose:** Track which soft skills are required for each specific job application and their evaluation status
- **Table Structure:**
  ```sql
  id INT PRIMARY KEY
  id_postulacion INT (FK -> postulaciones)
  habilidad VARCHAR(120)          -- Skill name
  cumple TINYINT(1) NULL          -- Meets requirement? (true/false/null)
  evaluado_por INT (FK -> usuarios) -- Who evaluated
  fecha_evaluacion DATETIME       -- When evaluated
  fecha_creacion DATETIME
  ```

### Soft Skills Workflow

1. **Egresado updates their soft skills profile**
   - View: `views/egresado/perfil.php` (not shown in exploration)
   - Calls: `Egresado::updateHabilidadesBlandas()`

2. **Egresado applies to offer**
   - Application created in `postulaciones`
   - If offer requires soft skills → initialize checklist

3. **Initialize Soft Skills Checklist**
   - **Method:** [Postulacion::inicializarChecklistHabilidadesBlandas()](app/models/Postulacion.php#L140-L165)
   - **Lines:** 140-165
   - Creates row in `postulacion_habilidades_blandas` for each skill
   - Sets `cumple = NULL` (unevaluated)
   - Prevents duplicate skills

4. **Hiring Manager/Admin Evaluates Skills**
   - **Method:** [Postulacion::evaluarHabilidadBlanda()](app/models/Postulacion.php#L176-L182)
   - **Line:** 181
   - Updates: `cumple` (1/0), `evaluado_por`, `fecha_evaluacion`

5. **Retrieve Skill Evaluation**
   - **Method:** [Postulacion::getEvaluacionHabilidadesBlandas()](app/models/Postulacion.php#L167-L173)
   - **Line:** 171
   - Returns: All skills for application with cumple status

### Key Model Methods

**Postulacion Model - Soft Skills Methods** [app/models/Postulacion.php](app/models/Postulacion.php):

| Method | Lines | Purpose |
|--------|-------|---------|
| `inicializarChecklistHabilidadesBlandas($postulacionId, array $habilidades)` | 140-165 | Create soft skills checklist for new application |
| `getEvaluacionHabilidadesBlandas($postulacionId)` | 167-173 | Get all skills + evaluation status for an application |
| `evaluarHabilidadBlanda($postulacionId, $habilidad, $cumple, $evaluadoPor)` | 176-182 | Mark a skill as meets/doesn't meet requirement |

---

## 7. KEY FILE LOCATIONS REFERENCE

### Models
- [app/models/Database.php](app/models/Database.php) - Base database class
- [app/models/Usuario.php](app/models/Usuario.php) - User authentication & management
- [app/models/Oferta.php](app/models/Oferta.php) - Job offer CRUD & queries
- [app/models/Postulacion.php](app/models/Postulacion.php) - Application management + soft skills
- [app/models/Egresado.php](app/models/Egresado.php) - Graduate profile + soft skills storage
- [app/models/Notificacion.php](app/models/Notificacion.php) - Notification system

### Controllers
- [app/controllers/AuthController.php](app/controllers/AuthController.php#L89) - Session role setup
- [app/controllers/NotificacionController.php](app/controllers/NotificacionController.php) - Notification API
- [app/controllers/VerificationController.php](app/controllers/VerificationController.php#L21) - Role-based email validation

### Views (Egresado)
- [views/egresado/ofertas.php](views/egresado/ofertas.php) - Browse approved offers
- [views/egresado/oferta-detalle.php](views/egresado/oferta-detalle.php) - Offer details & apply
- [views/egresado/postulaciones.php](views/egresado/postulaciones.php) - Track applications
- [views/egresado/publicar-oferta.php](views/egresado/publicar-oferta.php#L15) - Create offer (POST handler at line 15)
- [views/egresado/mis-ofertas.php](views/egresado/mis-ofertas.php) - Manage own offers

### API Endpoints
- [public/api/postulaciones-update.php](public/api/postulaciones-update.php) - Application status updates + role-based permissions
- [public/api/ofertas-update.php](public/api/ofertas-update.php) - Offer updates
- [public/api/notificaciones.php](public/api/notificaciones.php) - Notification API

### Database Migrations
- [database/setup_laragon.php](database/setup_laragon.php#L108) - Initial schema (usuarios, egresados, ofertas, postulaciones)
- [database/migrations/003_add_ofertas_and_seguimiento_fields.sql](database/migrations/003_add_ofertas_and_seguimiento_fields.sql) - Offer details + egresado tracking
- [database/migrations/006_add_notificaciones.sql](database/migrations/006_add_notificaciones.sql) - Notification system
- [database/migrations/012_add_soft_skills_and_offers_management.sql](database/migrations/012_add_soft_skills_and_offers_management.sql) - Soft skills + offer management
- [database/migrations/013_add_postulacion_soft_skills_checklist.sql](database/migrations/013_add_postulacion_soft_skills_checklist.sql) - Per-application skill evaluation

---

## 8. APPLICATION FLOW DIAGRAM

```
EGRESADO FLOW:
==============
1. Login → [AuthController] → Sets $_SESSION['usuario_rol'] = 'egresado'

2. Browse Offers
   └─ GET /views/egresado/ofertas.php
   └─ Calls: Oferta::getApprovedAndActive()
   └─ Shows: Only estado='aprobada', activo=1, vacantes>0, fecha_expiracion>NOW()

3. View Offer Detail & Apply
   └─ GET /views/egresado/oferta-detalle.php?id={oferta_id}
   └─ Check if already applied: Postulacion::hasApplied()
   └─ POST creates: Postulaciones row (estado='pendiente')
   └─ Trigger: Notificacion::onPostulacion() → notify offer creator
   └─ If applicable: Postulacion::inicializarChecklistHabilidadesBlandas()

4. Track Applications
   └─ GET /views/egresado/postulaciones.php
   └─ Calls: Postulacion::getByEgresadoId() → show all apps
   └─ Calls: Postulacion::getStatsByEgresado() → stats (enviadas, revision, etc)

5. Create New Offer
   └─ GET /views/egresado/publicar-oferta.php → form
   └─ POST → Validate CSRF → parse JSON fields
   └─ Oferta::create() → insert with estado='pendiente_aprobacion'
   └─ Trigger: Notificacion::onOfertaCreada() → notify all admins

6. Manage Own Offers
   └─ GET /views/egresado/mis-ofertas.php
   └─ Calls: Oferta::getByUserId($_SESSION['usuario_id'])
   └─ Shows: estado badges, applicant counts

7. Withdraw Application
   └─ POST /public/api/postulaciones-update.php?action=retirar
   └─ Calls: Postulacion::retirar() → set retirada=1, fecha_retiro=NOW()
   └─ Authorization: Must be egresado OR offer creator OR admin

OFFER CREATOR FLOW (Docente/TI):
=================================
1. Create Offer
   └─ Same as egresado: publicar-oferta.php
   └─ estado = 'pendiente_aprobacion'

2. View Applicants
   └─ GET /views/docente/postulantes.php (inferred)
   └─ Shows: All applications to their offers

3. Update Application Status
   └─ POST /public/api/postulaciones-update.php?action=actualizar_estado
   └─ Allowed states: pendiente, preseleccionado, contactado, rechazado
   └─ Triggers notifications to applicants
   └─ May evaluate soft skills: Postulacion::evaluarHabilidadBlanda()

ADMIN FLOW:
===========
1. Approve/Reject Offers
   └─ All pending offers appear in admin panel
   └─ Update: estado='aprobada' or 'rechazada', id_admin_aprobador
   └─ Triggers: Notificacion::onOfertaAprobada/Rechazada()

2. View All Applications & Evaluate
   └─ Admin can see all postulations
   └─ Evaluate soft skills for any application

3. Full System Moderation
```

---

## Summary Table: Key Implementation Points

| Feature | Table | Model | View | API | Line Reference |
|---------|-------|-------|------|-----|-----------------|
| **Offer Browse** | ofertas | Oferta::getApprovedAndActive() | ofertas.php | - | [Oferta.php:28](app/models/Oferta.php#L28) |
| **Offer Create** | ofertas | Oferta::create() | publicar-oferta.php | - | [publicar-oferta.php:15](views/egresado/publicar-oferta.php#L15) |
| **Application** | postulaciones | Postulacion::create() | oferta-detalle.php | - | [Postulacion.php line ~115] |
| **Application Withdraw** | postulaciones | Postulacion::retirar() | postulaciones.php | postulaciones-update.php | [Postulacion.php:100](app/models/Postulacion.php#L100) |
| **Soft Skills (Profile)** | egresados | Egresado::updateHabilidadesBlandas() | perfil.php | - | [Egresado.php:47](app/models/Egresado.php#L47) |
| **Soft Skills (App Eval)** | postulacion_habilidades_blandas | Postulacion::inicializarChecklistHabilidadesBlandas() | - | - | [Postulacion.php:140](app/models/Postulacion.php#L140) |
| **Skill Evaluation** | postulacion_habilidades_blandas | Postulacion::evaluarHabilidadBlanda() | - | - | [Postulacion.php:176](app/models/Postulacion.php#L176) |
| **Notifications** | notificaciones | Notificacion::crear() | - | notificaciones.php | [Notificacion.php:16](app/models/Notificacion.php#L16) |
| **Authorization** | usuarios | - | All views | postulaciones-update.php | [postulaciones-update.php:34](public/api/postulaciones-update.php#L34) |

