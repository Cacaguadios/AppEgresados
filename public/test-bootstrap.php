<?php
/**
 * Página de prueba de Bootstrap
 * Muestra todos los componentes principales
 */
require_once __DIR__ . '/../config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap - Prueba de Componentes</title>
    
    <?php include_css(BOOTSTRAP_CSS); ?>
    <?php include_css(BOOTSTRAP_ICONS); ?>
    <?php include_css(AUTH_CSS); ?>
    
    <style>
        body {
            background: linear-gradient(135deg, #f5f5f5 0%, #fff 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .demo-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .demo-title {
            color: var(--utp-red);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .component-demo {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid var(--utp-green);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <?php echo render_navbar('Sistema de Egresados UTP'); ?>
    
    <div class="container mt-5">
        
        <!-- 1. Alertas -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-exclamation-circle"></i> Alertas
            </div>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle"></i> <strong>Éxito:</strong> Operación completada correctamente.
            </div>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> <strong>Información:</strong> Esto es un mensaje de información.
            </div>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <strong>Advertencia:</strong> Por favor revisa esto.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <strong>Error:</strong> Ocurrió un problema.
            </div>
        </div>

        <!-- 2. Botones -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-hand-index"></i> Botones
            </div>
            <div class="component-demo">
                <button class="btn btn-primary me-2">Primario</button>
                <button class="btn btn-success me-2">Éxito</button>
                <button class="btn btn-warning me-2">Advertencia</button>
                <button class="btn btn-danger me-2">Peligro</button>
                <button class="btn btn-secondary">Secundario</button>
            </div>
            <div class="component-demo">
                <button class="btn btn-primary btn-sm me-2">Pequeño</button>
                <button class="btn btn-primary">Normal</button>
                <button class="btn btn-primary btn-lg ms-2">Grande</button>
            </div>
            <div class="component-demo">
                <button class="btn btn-outline-primary me-2">Outline</button>
                <button class="btn btn-success" disabled>Deshabilitado</button>
            </div>
        </div>

        <!-- 3. Grid System -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-grid-3x3"></i> Grid System
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background: #e9ecef; padding: 15px; border-radius: 6px;">
                        col-md-6 (50% en desktop)
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background: #e9ecef; padding: 15px; border-radius: 6px;">
                        col-md-6 (50% en desktop)
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div style="background: #e9ecef; padding: 15px; border-radius: 6px;">
                        col-md-4 (33%)
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: #e9ecef; padding: 15px; border-radius: 6px;">
                        col-md-4 (33%)
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: #e9ecef; padding: 15px; border-radius: 6px;">
                        col-md-4 (33%)
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Formularios -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-pencil-square"></i> Formularios
            </div>
            <form class="needs-validation">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" placeholder="tu@email.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="agreeTerm" required>
                    <label class="form-check-label" for="agreeTerm">
                        Acepto los términos y condiciones
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>
        </div>

        <!-- 5. Cards -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-collection"></i> Cards
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Título de Card</h5>
                            <p class="card-text">Contenido de la tarjeta con descripción.</p>
                            <a href="#" class="btn btn-primary btn-sm">Ver más</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Egresado destacado</h5>
                            <p class="card-text">Juan Pérez - Ingeniero en Sistemas</p>
                            <span class="badge bg-success">Empleado</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="card-title">Oferta Laboral</h5>
                            <p class="card-text">Empresa: Acme Corp</p>
                            <p class="card-text"><small class="text-muted">Publicado hace 2 días</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Badges -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-tag"></i> Badges
            </div>
            <p>
                <span class="badge bg-primary">Primary</span>
                <span class="badge bg-success">Success</span>
                <span class="badge bg-danger">Danger</span>
                <span class="badge bg-warning text-dark">Warning</span>
                <span class="badge bg-info text-dark">Info</span>
                <span class="badge bg-light text-dark">Light</span>
            </p>
        </div>

        <!-- 7. Modal Demo -->
        <div class="demo-section">
            <div class="demo-title">
                <i class="bi bi-box"></i> Modal
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#demoModal">
                Abrir Modal
            </button>
        </div>

        <!-- Footer -->
        <footer class="text-center py-4 text-muted">
            <p>© 2017 - 2026, Universidad Tecnológica de Puebla</p>
        </footer>

    </div>

    <!-- Modal Demo -->
    <div class="modal fade" id="demoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--utp-red); color: white;">
                    <h5 class="modal-title">Modal de Bootstrap</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Esto es un componente Modal de Bootstrap. Puede contener cualquier contenido HTML.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <?php include_js(BOOTSTRAP_JS); ?>
    <?php include_js(APP_JS); ?>
    
</body>
</html>
