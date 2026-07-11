# Despliegue en Ubuntu Server 20.04

Esta guia deja AppEgresados operativa con Apache2 y PHP 7.4.

## 1) Paquetes base

```bash
sudo apt update
sudo apt install -y software-properties-common ca-certificates lsb-release apt-transport-https
sudo apt install -y apache2 mysql-server unzip git curl composer \
  php7.4 php7.4-cli php7.4-common php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-opcache
```

PHP 7.4 es la version de produccion soportada por este proyecto.

## 2) Copiar proyecto

```bash
sudo mkdir -p /var/www/AppEgresados
sudo chown -R $USER:$USER /var/www/AppEgresados
cd /var/www/AppEgresados
# copiar archivos del proyecto aqui
composer install --no-dev --classmap-authoritative
```

## 3) Configurar variables de entorno

```bash
cp config/env.example.php config/env.php
nano config/env.php
```

Configura al menos:
- APP_BASE_PATH (vacío si usas VirtualHost con /public como DocumentRoot)
- APP_DB_HOST, APP_DB_PORT, APP_DB_NAME, APP_DB_USER, APP_DB_PASS
- MAIL_* (si usarás SMTP)

## 4) Permisos

```bash
sudo chown -R www-data:www-data storage public/assets/uploads
sudo find storage public/assets/uploads -type d -exec chmod 775 {} \;
sudo find storage public/assets/uploads -type f -exec chmod 664 {} \;
```

## 5) Apache VirtualHost

Crear `/etc/apache2/sites-available/appegresados.conf`:

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
  DocumentRoot /var/www/AppEgresados/public

  <Directory /var/www/AppEgresados/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/appegresados_error.log
    CustomLog ${APACHE_LOG_DIR}/appegresados_access.log combined
</VirtualHost>
```

Alternativa compatible (si quieres mantener `DocumentRoot /var/www/AppEgresados`):

- Mantener la configuracion de [/.htaccess](.htaccess) en la raiz del proyecto.
- Definir `APP_BASE_PATH` segun tu URL publica (`''` en raiz, `/AppEgresados` en subcarpeta).

Activar sitio y rewrite:

```bash
sudo a2enmod rewrite
sudo a2ensite appegresados.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

## 6) Base de datos y migraciones

```bash
php database/_server_preflight.php
php database/_run_migration.php
php database/_run_migration_003.php
php _run_migration_004.php
php database/run_011.php
php database/run_013.php
php database/run_014.php
php database/run_015.php
php database/run_016.php
```

## 7) Verificacion final

```bash
php database/_server_preflight.php
```

Si todo sale en `[OK]`, la instancia esta lista.
