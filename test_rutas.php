<?php
session_start();
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Detección de Rutas</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css">
    
    <style>
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        <h3>Información de Debug - Detección de Rutas</h3>
        <p><strong>HOST:</strong> <?= $host ?></p>
        <p><strong>REQUEST_URI:</strong> <?= $current_path ?></p>
        <p><strong>BASE_URL detectada:</strong> <?= $base_url ?></p>
        <p><strong>CSS styles.css:</strong> <?= $base_url ?>/assets/css/styles.css</p>
        <p><strong>CSS dashboard.css:</strong> <?= $base_url ?>/assets/css/dashboard.css</p>
        <p><strong>Logo:</strong> <?= $base_url ?>/webicon.png</p>
    </div>
    
    <?php include 'includes/navbar_complete.php'; ?>
    
    <div class="main-container">
        <div class="content-wrapper">
            <div class="container">
                <div class="welcome-section">
                    <h2>Test - Panel de Administración</h2>
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
            </div>
        </div>
    </div>
</body>
</html>