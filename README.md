# AppEgresados

Plataforma web para la Bolsa de Trabajo y seguimiento de egresados de la Universidad Tecnologica de Puebla.

La aplicacion permite registrar egresados, verificar cuentas, publicar ofertas laborales, gestionar postulaciones, invitar candidatos, enviar notificaciones y consultar reportes administrativos.

## Funcionalidades principales

- Registro, verificacion y recuperacion de cuentas.
- Panel para egresados con perfil, ofertas, postulaciones, invitaciones y seguimiento.
- Panel para docentes/TI con directorio, publicacion de ofertas e invitaciones.
- Panel administrativo con usuarios, verificacion, moderacion, seguimiento y reportes.
- APIs internas para notificaciones, postulaciones, ofertas, invitaciones y exportaciones.
- Soporte para rutas limpias bajo subcarpeta, por ejemplo `/bttiutp`.
- Modo oscuro en la interfaz compartida.

## Tecnologias

- PHP 7.4 o posterior.
- MySQL o MariaDB.
- Apache con `mod_rewrite`.
- PDO MySQL.
- Composer.
- PHPMailer 6.
- Bootstrap 5.
- JavaScript y CSS sin proceso de compilacion.

## Estructura

```text
app/        Controladores, modelos y helpers
config/     Bootstrap y plantillas de configuracion
database/   Migraciones y utilidades de base de datos
docs/       Documentacion adicional
public/     Front controller, APIs y assets publicos
storage/    Archivos privados, cache y logs
tests/      Pruebas de integracion
views/      Vistas por rol
vendor/     Dependencias instaladas por Composer
```


## Verificacion

Validar sintaxis PHP:

```bash
find app config public views -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

Validar dependencias:

```bash
composer validate --no-check-publish
composer install --no-dev --optimize-autoloader
```

Rutas principales:

| Area | Ruta |
|---|---|
| Login | `/login` |
| Registro | `/register-step-1` |
| Egresado | `/egresado/inicio` |
| Docente/TI | `/docente/inicio` |
| Administracion | `/admin/inicio` |
| Notificaciones | `/notificaciones` |

En servidor con subcarpeta, las rutas quedan bajo `/bttiutp`, por ejemplo `/bttiutp/login`.

## Seguridad

- Mantener `config/env.php` fuera de Git.
- Usar HTTPS en produccion.
- Proteger `storage/`; incluye `.htaccess` con `Require all denied`.
- Respaldar la base antes de ejecutar migraciones.
- No ejecutar scripts de seed, limpieza o reseteo de contrasenas en produccion sin revisarlos.
- Revisar permisos de `storage/` y `public/assets/uploads/`.

## Licencia

Mozilla Public License 2.0. Ver [LICENSE](LICENSE).
