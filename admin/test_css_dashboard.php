<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Test User';
$_SESSION['rol'] = 'super_admin';

require_once '../config/path_helper.php';
$base_url = detectar_base_url();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CSS - Dashboard</title>
    <style>
        .debug-box {
            background: #f0f8ff;
            border: 1px solid #0066cc;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
        }
        .test-buttons {
            margin: 20px 0;
        }
        .test-btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .test-btn-primary { background: #0d47a1; color: white; }
        .test-btn-secondary { background: #6c757d; color: white; }
        .test-btn-info { background: #32CD32; color: white; }
    </style>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css">
</head>
<body>
    <h1>🔍 Diagnóstico CSS Dashboard</h1>
    
    <div class="debug-box">
        <h3>Información de Rutas:</h3>
        <p><strong>Base URL:</strong> <?= $base_url ?></p>
        <p><strong>Host:</strong> <?= $_SERVER['HTTP_HOST'] ?></p>
        <p><strong>Script Name:</strong> <?= $_SERVER['SCRIPT_NAME'] ?></p>
        <p><strong>Request URI:</strong> <?= $_SERVER['REQUEST_URI'] ?></p>
        <p><strong>CSS styles.css:</strong> <?= $base_url ?>/assets/css/styles.css</p>
        <p><strong>CSS dashboard.css:</strong> <?= $base_url ?>/assets/css/dashboard.css</p>
    </div>

    <div class="debug-box">
        <h3>Test de Archivos CSS:</h3>
        <?php
        $css_files = [
            'assets/css/styles.css',
            'assets/css/dashboard.css'
        ];
        
        foreach ($css_files as $file) {
            if (file_exists("../$file")) {
                echo "<p style='color: green;'>✅ ../$file - EXISTE</p>";
            } else {
                echo "<p style='color: red;'>❌ ../$file - NO ENCONTRADO</p>";
            }
        }
        ?>
    </div>

    <h2>Test de Botones Inline (sin CSS externo):</h2>
    <div class="test-buttons">
        <a href="#" class="test-btn test-btn-primary">+ Nueva Encuesta</a>
        <a href="#" class="test-btn test-btn-secondary">Banco de Preguntas</a>
        <a href="#" class="test-btn test-btn-info">Ver Encuestas</a>
        <a href="#" class="test-btn test-btn-secondary">Reportes</a>
    </div>

    <h2>Test de Botones con CSS Externo:</h2>
    <div class="actions-section">
        <h3 class="section-title">Acciones Principales</h3>
        <div class="action-buttons">
            <a href="#" class="action-btn btn-primary">+ Nueva Encuesta</a>
            <a href="#" class="action-btn btn-secondary">Banco de Preguntas</a>
            <a href="#" class="action-btn btn-info">Ver Encuestas</a>
            <a href="#" class="action-btn btn-secondary">Reportes</a>
        </div>
    </div>

    <h2>Test de Cards de Estadísticas:</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">4</div>
            <div class="stat-label">Encuestas Creadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">105</div>
            <div class="stat-label">Preguntas en el Banco</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">12</div>
            <div class="stat-label">Respuestas Recibidas</div>
        </div>
    </div>

    <div class="debug-box">
        <h3>Instrucciones:</h3>
        <p><strong>Si los botones inline se ven bien pero los externos no:</strong></p>
        <ul>
            <li>El problema es que dashboard.css no se está cargando</li>
            <li>Revisar la consola del navegador para errores 404</li>
        </ul>
        <p><strong>Si ningún botón se ve bien:</strong></p>
        <ul>
            <li>El problema es más general con el CSS</li>
            <li>Verificar que XAMPP esté sirviendo archivos CSS correctamente</li>
        </ul>
    </div>

    <script>
        console.log('Base URL:', '<?= $base_url ?>');
        console.log('CSS paths:');
        console.log('  styles.css:', '<?= $base_url ?>/assets/css/styles.css');
        console.log('  dashboard.css:', '<?= $base_url ?>/assets/css/dashboard.css');
    </script>
</body>
</html>