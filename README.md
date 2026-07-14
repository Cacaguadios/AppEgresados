# AppEgresados

Plataforma web de la Universidad Tecnológica de Puebla para gestionar la bolsa de trabajo y dar seguimiento laboral a sus egresados.

La aplicación permite publicar y moderar ofertas laborales, registrar postulaciones, invitar egresados a vacantes, mantener actualizado su perfil profesional y consultar indicadores administrativos.

## Funcionalidades

### Egresados

- Registro y verificación de cuenta.
- Perfil académico, profesional y datos de empleabilidad.
- Consulta de ofertas aprobadas y activas.
- Postulación, retiro y seguimiento de candidaturas.
- Publicación y administración de ofertas propias.
- Recepción de invitaciones y notificaciones.
- Recordatorios periódicos para actualizar información laboral.

### Docentes y personal de TI

- Publicación y administración de ofertas laborales.
- Consulta del directorio de egresados.
- Invitación de egresados a ofertas específicas.
- Revisión de postulantes y actualización de su estado.
- Registro de retroalimentación sobre postulaciones.

### Administradores

- Administración y activación de usuarios.
- Verificación de perfiles de egresados.
- Moderación de ofertas laborales.
- Seguimiento de empleabilidad.
- Reportes y exportación de datos en CSV o Excel.

## Tecnologías

- PHP 7.4
- MySQL 8 o MariaDB compatible
- Apache 2 con `mod_rewrite`
- PDO MySQL
- Composer
- PHPMailer 6
- Bootstrap 5
- JavaScript y CSS sin proceso de compilación

PHP 7.4 es la plataforma mínima de producción y la versión usada para resolver y validar las dependencias de Composer.

## Estructura del proyecto

```text
AppEgresados/
├── app/
│   ├── controllers/       Controladores de autenticación y procesos
│   ├── helpers/           Seguridad y plantillas de correo
│   └── models/            Modelos y acceso a MySQL mediante PDO
├── config/                Bootstrap y configuración de entorno
├── database/
│   ├── migrations/        Migraciones SQL
│   └── *.php              Utilidades de instalación y mantenimiento
├── docs/                  Documentación adicional de despliegue
├── public/
│   ├── api/               Endpoints HTTP
│   ├── assets/            CSS, JavaScript, imágenes y archivos subidos
│   └── index.php          Front controller y router principal
├── tests/integration/     Pruebas de integración contra MySQL
├── views/                 Vistas organizadas por rol
├── composer.json
└── .htaccess
```

## Requisitos

- PHP 7.4 o posterior.
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl` y `json`.
- MySQL o MariaDB.
- Composer.
- Apache con `mod_rewrite` para usar las reglas incluidas.
- Permisos de escritura en:
  - `storage/`
  - `storage/logs/`
  - `storage/cache/`
  - `public/assets/uploads/`

## Instalación local

### 1. Obtener el proyecto

```bash
git clone <URL_DEL_REPOSITORIO> AppEgresados
cd AppEgresados
composer install
```

### 2. Configurar el entorno

En desarrollo local se puede utilizar un archivo PHP no versionado:

```bash
cp config/env.example.php config/env.php
```

Edita `config/env.php` y configura, como mínimo:

```php
set_env_if_missing('APP_BASE_PATH', '/AppEgresados');

set_env_if_missing('APP_DB_HOST', '127.0.0.1');
set_env_if_missing('APP_DB_PORT', '3306');
set_env_if_missing('APP_DB_NAME', 'bolsa_trabajo_utp');
set_env_if_missing('APP_DB_USER', 'usuario_db');
set_env_if_missing('APP_DB_PASS', 'contraseña_db');
```

Usa `APP_BASE_PATH=''` cuando el `DocumentRoot` apunte directamente a `public/`. Si la aplicación se publica en `http://localhost/AppEgresados`, usa `/AppEgresados`.

`config/env.php` contiene secretos y está excluido de Git. No debe incorporarse al repositorio.
En producción este archivo se ignora: define las variables en el proceso de
Apache siguiendo [docs/PRODUCTION_SECRETS.md](docs/PRODUCTION_SECRETS.md).

### 3. Preparar la base de datos

El proyecto ofrece dos alternativas.

#### Instalación limpia con Laragon

```bash
php database/setup_laragon.php
```

> Advertencia: este comando elimina y reconstruye completamente la base configurada. Debe utilizarse únicamente en un entorno local o desechable.

#### Base existente y migraciones

Configura primero las credenciales y ejecuta los scripts en el orden documentado:

```bash
php database/_run_migration.php
php database/_run_migration_003.php
php _run_migration_004.php
php database/run_011.php
php database/run_013.php
php database/run_014.php
php database/run_015.php
php database/run_016.php
```

Antes de ejecutar migraciones en una instancia con información real, genera una copia de seguridad y revisa cada script pendiente.

### 4. Verificar el entorno

```bash
php database/_server_preflight.php
```

El comando comprueba la versión de PHP, extensiones, dependencias, configuración y directorios escribibles.

### 5. Servir la aplicación

La configuración recomendada usa Apache con `public/` como raíz pública:

```apache
<VirtualHost *:80>
    ServerName appegresados.local
    DocumentRoot /ruta/absoluta/AppEgresados/public

    <Directory /ruta/absoluta/AppEgresados/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Activa la reescritura de URLs y recarga Apache:

```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

También es posible publicar la raíz completa del proyecto; el `.htaccess` principal redirige las solicitudes a `public/` y bloquea el acceso directo a carpetas sensibles. Usar `public/` como `DocumentRoot` sigue siendo la opción preferida.

## Correo electrónico

La aplicación admite dos modos:

- `MAIL_DRIVER=log`: apropiado para desarrollo.
- `MAIL_DRIVER=smtp`: envío mediante un servidor SMTP.

Para SMTP, configura en `config/env.php`:

```php
set_env_if_missing('MAIL_DRIVER', 'smtp');
set_env_if_missing('MAIL_HOST', 'smtp.example.com');
set_env_if_missing('MAIL_PORT', '587');
set_env_if_missing('MAIL_ENCRYPTION', 'tls');
set_env_if_missing('MAIL_USER', 'usuario_smtp');
set_env_if_missing('MAIL_PASS', 'contraseña_smtp');
set_env_if_missing('MAIL_FROM', 'no-reply@example.com');
set_env_if_missing('MAIL_FROM_NAME', 'Bolsa de Trabajo UTP');
```

No almacenes credenciales SMTP reales en archivos versionados.

## Pruebas y verificaciones

### Validación sintáctica

```bash
find app config public views tests database -type f -name '*.php' -print0 \
  | xargs -0 -n1 php -l
```

### Smoke test de integración

```bash
php tests/integration/flujo_egresado_smoke.php
```

Esta prueba consulta una base MySQL real y actualmente espera una instancia local llamada `bolsa_trabajo_utp`, con usuario `root` sin contraseña. No debe ejecutarse contra producción y puede requerir adaptar su función `connect()` al entorno local.

El proyecto incluye PHPUnit como dependencia de desarrollo, pero todavía no cuenta con una suite unitaria ni con un archivo `phpunit.xml`.

## Rutas principales

| Área | Ruta inicial |
|---|---|
| Autenticación | `/login` |
| Registro | `/register-step-1` |
| Egresado | `/egresado/inicio` |
| Docente/TI | `/docente/inicio` |
| Administración | `/admin/inicio` |
| Notificaciones | `/notificaciones` |

El router también mantiene compatibilidad con varias URLs antiguas terminadas en `.php`.

## Seguridad y operación

- Cambia inmediatamente cualquier contraseña incluida en datos de demostración o migraciones antiguas.
- No expongas `config/`, `database/`, `tests/`, `vendor/` ni `storage/` desde el servidor web.
- No ejecutes scripts de limpieza, seed o restablecimiento de contraseñas en producción sin revisar su contenido.
- Usa HTTPS en producción.
- Respalda la base de datos antes de aplicar migraciones.
- Mantén `config/env.php` y los archivos generados fuera del control de versiones.
- Revisa los permisos de `storage/` y `public/assets/uploads/`; evita permisos globales `777`.

## Despliegue

La guía detallada para Apache, MySQL y Ubuntu está disponible en [docs/DEPLOY_UBUNTU_20_04.md](docs/DEPLOY_UBUNTU_20_04.md).

Flujo resumido:

```bash
composer install --no-dev --classmap-authoritative
cp config/env.example.php config/env.php
php database/_server_preflight.php
# Respaldar la base y aplicar únicamente las migraciones pendientes
sudo systemctl reload apache2
```

## Política de dependencias

- `composer.lock` es la fuente reproducible para CI y despliegues; `vendor/` no se versiona.
- Dependabot revisa las dependencias de Composer semanalmente.
- CI ejecuta `composer validate --strict`, `composer audit --locked` y revisa las licencias de producción.
- Las actualizaciones deben conservar PHP 7.4 y pasar el smoke test antes de integrarse.
- Una vulnerabilidad o licencia incompatible bloquea la integración, salvo una excepción temporal documentada en el pull request con riesgo, mitigación, responsable y fecha de vencimiento.

## Estado actual

El proyecto cuenta con los flujos principales implementados y validación CSRF en la mayoría de las operaciones mutables. Antes de una puesta en producción se recomienda reforzar la configuración de sesiones, centralizar el bootstrap de los endpoints API, evitar mostrar errores internos de MySQL y ampliar la cobertura automatizada de pruebas.

## Licencia

Este proyecto se distribuye bajo la Mozilla Public License 2.0. Consulta el archivo [LICENSE](LICENSE) para conocer los términos completos.
