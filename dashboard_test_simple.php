<?php
// Versión simplificada sin autenticación para testing
session_start();

// Simular sesión para prueba
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Usuario Test';
$_SESSION['rol'] = 'super_admin';

// Detectar la ruta base para los assets
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$current_path = $_SERVER['REQUEST_URI'];

// Determinar si estamos en localhost:8002 o localhost/php/proyecto_encuestas
if (strpos($host, ':8002') !== false) {
    $base_url = $protocol . $host;
} else {
    // Extraer la ruta base del proyecto
    $path_parts = explode('/', trim($current_path, '/'));
    if (in_array('php', $path_parts) && in_array('proyecto_encuestas', $path_parts)) {
        $base_url = $protocol . $host . '/php/proyecto_encuestas';
    } else {
        $base_url = $protocol . $host;
    }
}

// Simulamos datos para el dashboard
$total_encuestas = 4;
$total_preguntas = 105;
$total_respuestas = 12;
$rol = 'super_admin';
$nombre = 'Usuario Test';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - DAS Hualpén (Test)</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css">
    
    <style>
        .debug-info {
            background: #e7f3ff;
            border: 1px solid #0066cc;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        <strong>🔧 DEBUG INFO:</strong><br>
        HOST: <?= $host ?><br>
        REQUEST_URI: <?= $current_path ?><br>
        BASE_URL: <?= $base_url ?><br>
        CSS styles: <?= $base_url ?>/assets/css/styles.css<br>
        CSS dashboard: <?= $base_url ?>/assets/css/dashboard.css
    </div>

    <?php include 'includes/navbar_complete.php'; ?>
    
    <div class="main-container">
        <div class="content-wrapper">
            <div class="container">
                <div class="welcome-section">
                    <h2>Panel de Administración (TEST)</h2>
                    <p>Gestiona las encuestas públicas del DAS Hualpén desde este panel central. Tu rol actual es: <strong><?= htmlspecialchars($rol) ?></strong></p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_encuestas ?></div>
                        <div class="stat-label">Encuestas Creadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_preguntas ?></div>
                        <div class="stat-label">Preguntas en el Banco</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_respuestas ?></div>
                        <div class="stat-label">Respuestas Recibidas</div>
                    </div>
                </div>

                <div class="actions-section">
                    <h3 class="section-title">Acciones Principales</h3>
                    <div class="action-buttons">
                        <a href="<?= $base_url ?>/admin/crear_encuesta.php" class="action-btn btn-primary">
                            + Nueva Encuesta
                        </a>
                        <a href="<?= $base_url ?>/admin/gestionar_preguntas.php" class="action-btn btn-secondary">
                            Banco de Preguntas
                        </a>
                        <a href="<?= $base_url ?>/admin/ver_encuestas.php" class="action-btn btn-info">
                            Ver Encuestas
                        </a>
                        <a href="<?= $base_url ?>/admin/reportes.php" class="action-btn btn-secondary">
                            Reportes
                        </a>
                    </div>
                </div>
                
                <div class="debug-info" style="margin-top: 2rem;">
                    <strong>📊 TEST RESULTS:</strong><br>
                    ✅ PHP ejecutándose correctamente<br>
                    ✅ Variables de sesión configuradas<br>
                    ✅ Detección de rutas funcionando<br>
                    <?php if (file_exists('assets/css/styles.css')): ?>
                        ✅ Archivo styles.css encontrado<br>
                    <?php else: ?>
                        ❌ Archivo styles.css NO encontrado<br>
                    <?php endif; ?>
                    <?php if (file_exists('assets/css/dashboard.css')): ?>
                        ✅ Archivo dashboard.css encontrado<br>
                    <?php else: ?>
                        ❌ Archivo dashboard.css NO encontrado<br>
                    <?php endif; ?>
                    <?php if (file_exists('includes/navbar_complete.php')): ?>
                        ✅ Archivo navbar_complete.php encontrado<br>
                    <?php else: ?>
                        ❌ Archivo navbar_complete.php NO encontrado<br>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>