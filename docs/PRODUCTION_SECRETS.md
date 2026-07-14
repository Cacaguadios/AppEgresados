# Secretos y configuracion de produccion

La aplicacion no carga `config/env.php` cuando `APP_ENV=production`. En el VPS,
los secretos deben llegar al proceso de Apache mediante variables de entorno y
nunca deben almacenarse dentro del checkout.

## Rotacion inicial obligatoria

Antes del primer despliegue:

1. Revoca la contrasena de aplicacion SMTP usada anteriormente y genera una nueva.
2. Cambia la contrasena del usuario MySQL utilizado por la aplicacion.
3. Crea un usuario MySQL exclusivo de runtime con permisos `SELECT`, `INSERT`,
   `UPDATE` y `DELETE` unicamente sobre la base de la aplicacion.
4. Genera `APP_KEY` con al menos 32 bytes aleatorios.
5. No pegues ninguno de estos valores en tickets, commits, logs o conversaciones.

## Archivo protegido del VPS

Crea `/etc/appegresados/appegresados.env` como `root`, modo `0600`, con las
variables de `.env.example` y valores reales. `APP_ENV=production`,
`APP_DEBUG=false`, `APP_URL` debe usar HTTPS y `MAIL_DRIVER=smtp`.

Configura el servicio Apache para heredar ese archivo mediante un override de
systemd:

```ini
[Service]
EnvironmentFile=/etc/appegresados/appegresados.env
```

Tras `systemctl daemon-reload` y `systemctl restart apache2`, ejecuta el
preflight. Este nunca imprime los valores de los secretos:

```bash
php database/_server_preflight.php
```

## Revocacion y recuperacion

- Rota DB y SMTP de inmediato ante una sospecha de exposicion.
- Actualiza primero el archivo protegido, reinicia Apache y valida conexiones.
- Revoca la credencial anterior solo despues de confirmar la nueva cuando el
  proveedor permita una ventana de solapamiento.
- Registra fecha, responsable y resultado de la rotacion, nunca el valor.
