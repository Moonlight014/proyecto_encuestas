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
    <title>Dashboard Debug - Versión Simplificada</title>
    
    <!-- CSS con rutas absolutas para debug -->
    <link rel="stylesheet" href="http://localhost/php/proyecto_encuestas/assets/css/styles.css">
    <link rel="stylesheet" href="http://localhost/php/proyecto_encuestas/assets/css/dashboard.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS inline de respaldo */
        .fallback-btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 8px;
            background: #0d47a1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .fallback-btn:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }
        .fallback-btn.secondary {
            background: #6c757d;
        }
        .fallback-btn.success {
            background: #32CD32;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Dashboard Debug - Versión Simplificada</h1>
        
        <div style="background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 8px;">
            <strong>Base URL:</strong> <?= $base_url ?><br>
            <strong>CSS Styles:</strong> http://localhost/php/proyecto_encuestas/assets/css/styles.css<br>
            <strong>CSS Dashboard:</strong> http://localhost/php/proyecto_encuestas/assets/css/dashboard.css
        </div>

        <h2>❌ Sección Problemática (CSS Externo):</h2>
        <div class="actions-section">
            <h3 class="section-title">Acciones Principales</h3>
            <div class="action-buttons">
                <a href="crear_encuesta.php" class="action-btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Encuesta
                </a>
                <a href="gestionar_preguntas.php" class="action-btn btn-secondary">
                    <i class="fas fa-question-circle"></i> Banco de Preguntas
                </a>
                <a href="ver_encuestas.php" class="action-btn btn-info">
                    <i class="fas fa-list"></i> Ver Encuestas
                </a>
                <a href="reportes.php" class="action-btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
            </div>
        </div>

        <h2>✅ Sección de Respaldo (CSS Inline):</h2>
        <div>
            <a href="crear_encuesta.php" class="fallback-btn">
                <i class="fas fa-plus"></i> Nueva Encuesta
            </a>
            <a href="gestionar_preguntas.php" class="fallback-btn secondary">
                <i class="fas fa-question-circle"></i> Banco de Preguntas
            </a>
            <a href="ver_encuestas.php" class="fallback-btn success">
                <i class="fas fa-list"></i> Ver Encuestas
            </a>
            <a href="reportes.php" class="fallback-btn secondary">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
        </div>

        <h2>🧪 Test de Estadísticas:</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">4</div>
                <div class="stat-label">Encuestas Creadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">105</div>
                <div class="stat-label">Preguntas en el Banco</div>
            </div>
        </div>
    </div>

    <script>
        // Verificar si los CSS cargaron
        window.addEventListener('load', function() {
            const testElement = document.createElement('div');
            testElement.className = 'action-btn btn-primary';
            testElement.style.visibility = 'hidden';
            testElement.style.position = 'absolute';
            document.body.appendChild(testElement);
            
            const styles = window.getComputedStyle(testElement);
            const backgroundColor = styles.backgroundColor;
            
            console.log('Botón test - background color:', backgroundColor);
            
            if (backgroundColor === 'rgba(0, 0, 0, 0)' || backgroundColor === 'transparent') {
                console.error('❌ CSS no aplicado - dashboard.css no cargó');
                alert('⚠️ CSS no cargado: dashboard.css no se está aplicando');
            } else {
                console.log('✅ CSS aplicado correctamente');
            }
            
            document.body.removeChild(testElement);
        });
    </script>
</body>
</html>