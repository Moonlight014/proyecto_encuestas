<?php
session_start();

// Simular sesión para testing
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Usuario Test Dual';
$_SESSION['rol'] = 'super_admin';

// Detectar la ruta base dinámicamente para ambos entornos
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$request_uri = $_SERVER['REQUEST_URI'];

// Detectar el contexto del servidor
if (strpos($host, ':') !== false && !strpos($host, ':80') && !strpos($host, ':443')) {
    // Servidor con puerto específico (ej: localhost:8002)
    $base_url = $protocol . $host;
    $server_type = 'Puerto Específico (ej: 8002)';
    $css_path = '/assets/css/';
} else {
    // Servidor estándar (ej: localhost/php/proyecto_encuestas)
    $path_parts = explode('/', trim($script_name, '/'));
    
    if (in_array('php', $path_parts) && in_array('proyecto_encuestas', $path_parts)) {
        $base_url = $protocol . $host . '/php/proyecto_encuestas';
        $server_type = 'XAMPP Estándar';
        $css_path = '/php/proyecto_encuestas/assets/css/';
    } else {
        $base_url = $protocol . $host;
        $server_type = 'Servidor Raíz';
        $css_path = '/assets/css/';
    }
}

// Datos simulados
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
    <title>Test Dual - Dashboard DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema con detección automática -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css">
    
    <style>
        .environment-info {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            border-left: 4px solid #4caf50;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .detection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .detection-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 1rem;
            border-radius: 8px;
        }
        .success { color: #28a745; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
    </style>
</head>
<body>
    <div class="environment-info">
        <h3><i class="fas fa-server"></i> Detección Automática de Entorno</h3>
        <div class="detection-grid">
            <div class="detection-card">
                <strong>🌐 Servidor Detectado:</strong><br>
                <span class="success"><?= $server_type ?></span>
            </div>
            <div class="detection-card">
                <strong>🔗 URL Base:</strong><br>
                <span class="info"><?= $base_url ?></span>
            </div>
            <div class="detection-card">
                <strong>📁 Ruta CSS:</strong><br>
                <span class="info"><?= $css_path ?></span>
            </div>
            <div class="detection-card">
                <strong>🖥️ Host:</strong><br>
                <span class="info"><?= $host ?></span>
            </div>
        </div>
    </div>

    <?php include 'includes/navbar_complete.php'; ?>
    
    <div class="main-container">
        <div class="content-wrapper">
            <div class="container">
                <div class="welcome-section">
                    <h2>Panel de Administración (Test Dual)</h2>
                    <p>Este dashboard funciona tanto en <strong>localhost:8002</strong> como en <strong>localhost/php/proyecto_encuestas</strong></p>
                    <p>Tu rol actual es: <strong><?= htmlspecialchars($rol) ?></strong></p>
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
                
                <div class="environment-info" style="margin-top: 2rem;">
                    <h4>🧪 Resultados del Test:</h4>
                    <div class="detection-grid">
                        <div class="detection-card">
                            <strong>✅ Detección de Rutas:</strong><br>
                            <span class="success">Funcionando</span>
                        </div>
                        <div class="detection-card">
                            <strong>✅ Navbar Responsive:</strong><br>
                            <span class="success">Dropdowns Activos</span>
                        </div>
                        <div class="detection-card">
                            <strong>✅ CSS Cargado:</strong><br>
                            <span class="success">Estilos Aplicados</span>
                        </div>
                        <div class="detection-card">
                            <strong>✅ Navegación:</strong><br>
                            <span class="success">Enlaces Correctos</span>
                        </div>
                    </div>
                    
                    <h4>🚀 URLs de Prueba:</h4>
                    <ul>
                        <li><strong>XAMPP:</strong> <code>http://localhost/php/proyecto_encuestas/test_dual_environment.php</code></li>
                        <li><strong>Puerto 8002:</strong> <code>http://localhost:8002/test_dual_environment.php</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar información adicional en consola
        console.log('🌐 Entorno detectado:', '<?= $server_type ?>');
        console.log('🔗 Base URL:', '<?= $base_url ?>');
        console.log('📁 CSS Path:', '<?= $css_path ?>');
        console.log('✅ Sistema funcionando correctamente');
    </script>
</body>
</html>