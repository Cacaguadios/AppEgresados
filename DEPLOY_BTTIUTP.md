# Despliegue en ti.utpuebla.edu.mx/bttiutp

## Ruta del servidor

Subir el contenido del proyecto a:

```text
/var/www/html/bttiutp/
```

La URL publica esperada es:

```text
https://ti.utpuebla.edu.mx/bttiutp/
```

## Archivos y carpetas a subir

```text
app/
config/
public/
views/
vendor/
storage/
.htaccess
composer.json
composer.lock
```

No subir:

```text
.git/
.env
config/env.php local
database/
docker/
docs/
tests/
storage/logs/emails.log
```

## Configuracion privada del servidor

En el servidor, copiar:

```text
config/env.server.example.php
```

como:

```text
config/env.php
```

Completar `APP_DB_PASS` y, si se usa SMTP, completar `MAIL_USER`, `MAIL_PASS` y `MAIL_FROM`.

Para primer despliegue se recomienda dejar:

```text
MAIL_DRIVER=log
```

Los correos se registran en:

```text
storage/logs/emails.log
```

## Validacion en servidor

```bash
cd /var/www/html/bttiutp
php -v
php -l public/index.php
find app config public views -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Pruebas HTTP

```bash
curl -I https://ti.utpuebla.edu.mx/bttiutp/
curl -I https://ti.utpuebla.edu.mx/bttiutp/login
curl -I https://ti.utpuebla.edu.mx/bttiutp/forgot
curl -I https://ti.utpuebla.edu.mx/bttiutp/register-step-1
curl -I https://ti.utpuebla.edu.mx/bttiutp/admin/inicio
curl -I https://ti.utpuebla.edu.mx/bttiutp/docente/inicio
curl -I https://ti.utpuebla.edu.mx/bttiutp/egresado/inicio
```

Las rutas protegidas sin sesion deben responder `302` hacia `/bttiutp/login`.
