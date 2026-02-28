# Bootstrap en AppEgresados

## Instalación: CDN (Recomendado)

Bootstrap 5.3.0 está configurado via **CDN** (Content Delivery Network). No requiere instalación local.

## Estructura

```
public/
├── assets/
│   ├── css/
│   │   ├── auth.css          ← Estilos de autenticación
│   │   └── bootstrap.min.css ← Referencia CDN
│   └── js/
│       ├── app.js            ← Scripts principales
│       └── bootstrap.bundle.min.js ← Referencia CDN
views/
├── layouts/
│   └── base.php              ← Layout base con Bootstrap
├── auth/
│   └── login.php             ← Página de login
```

## Uso

### 1. En vistas PHP simples:

```php
<?php
// Definir título y CSS personalizado
$title = "Mi Página";
$customCss = "/AppEgresados/assets/css/custom.css";
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Hola Bootstrap</h1>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### 2. Con el layout base:

```php
<?php
$title = "Dashboard";
$customCss = "/AppEgresados/assets/css/dashboard.css";
?>

<!-- Cargar el layout -->
<?php include __DIR__ . '/../layouts/base.php'; ?>

<!-- Contenido -->
<div class="container mt-5">
    <h1>Dashboard</h1>
</div>
```

## Clases Bootstrap disponibles

### Grid System
```html
<div class="container">
    <div class="row">
        <div class="col-md-6">Media 6 columnas</div>
        <div class="col-md-6">Media 6 columnas</div>
    </div>
</div>
```

### Buttons
```html
<button class="btn btn-primary">Primario</button>
<button class="btn btn-success">Éxito</button>
<button class="btn btn-danger">Peligro</button>
<button class="btn btn-warning">Advertencia</button>
```

### Alerts
```html
<div class="alert alert-success" role="alert">
    ¡Éxito! Tu cuenta ha sido creada.
</div>
```

### Formularios
```html
<form>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Enviar</button>
</form>
```

### Modals
```html
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
    Abrir Modal
</button>

<div class="modal fade" id="exampleModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Título</h5>
            </div>
            <div class="modal-body">Contenido</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
```

## CSS Personalizado (UTP)

Variables globales en `auth.css`:

```css
:root {
    --utp-red: #7A1501;
    --utp-green: #00C247;
    --bg: #FAFAFA;
    --text: #121212;
    --muted: #757575;
}
```

Uso:
```css
.btn-utp {
    background-color: var(--utp-green);
}
```

## Scripts incluyos

### app.js
- Inicialización de tooltips
- Inicialización de popovers
- Auto-dismiss de alertas (5 segundos)
- Validación de formularios Bootstrap
- Función `showToast(message, type)` para notificaciones

Ejemplo:
```html
<script>
    showToast('Operación completada', 'success');
</script>
```

## Documentación oficial

- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/
- **Bootstrap Icons**: https://icons.getbootstrap.com/
- **CDN**: https://cdn.jsdelivr.net/

## CDN URLs

```html
<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- JS Bundle (incluye Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

## Soporte

Para agregar Bootstrap localmente en el futuro:
1. Descargar desde https://getbootstrap.com/docs/5.3/getting-started/download/
2. Guardar en `public/assets/bootstrap/`
3. Actualizar referencias CDN
