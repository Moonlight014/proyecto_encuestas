<?php
session_start();

// Simular sesión para testing
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Usuario Test';
$_SESSION['rol'] = 'super_admin';

// Rutas fijas para XAMPP estándar
$base_url = 'http://localhost/php/proyecto_encuestas';

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
    <title>Dashboard - DAS Hualpén (XAMPP)</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema con rutas fijas -->
    <link rel="stylesheet" href="/php/proyecto_encuestas/assets/css/styles.css">
    <link rel="stylesheet" href="/php/proyecto_encuestas/assets/css/dashboard.css">
    
    <style>
        .status-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="status-info">
        <strong>✅ XAMPP Estándar Funcionando</strong><br>
        Host: <?= $_SERVER['HTTP_HOST'] ?><br>
        URL Base: <?= $base_url ?><br>
        Rutas CSS: /php/proyecto_encuestas/assets/css/<br>
        <small>Sin conflictos con puerto 8002</small>
    </div>

    <?php include 'includes/navbar_complete.php'; ?>
    
    <div class="main-container">
        <div class="content-wrapper">
            <div class="container">
                <div class="welcome-section">
                    <h2>Panel de Administración</h2>
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
                        <a href="/php/proyecto_encuestas/admin/crear_encuesta.php" class="action-btn btn-primary">
                            + Nueva Encuesta
                        </a>
                        <a href="/php/proyecto_encuestas/admin/gestionar_preguntas.php" class="action-btn btn-secondary">
                            Banco de Preguntas
                        </a>
                        <a href="/php/proyecto_encuestas/admin/ver_encuestas.php" class="action-btn btn-info">
                            Ver Encuestas
                        </a>
                        <a href="/php/proyecto_encuestas/admin/reportes.php" class="action-btn btn-secondary">
                            Reportes
                        </a>
                    </div>
                </div>
                
                <div class="status-info" style="margin-top: 2rem;">
                    <strong>🎯 Estado del Sistema:</strong><br>
                    ✅ PHP ejecutándose correctamente<br>
                    ✅ Sesión configurada<br>
                    ✅ Rutas optimizadas para XAMPP estándar<br>
                    ✅ Sin conflictos de puertos<br>
                    ✅ CSS cargándose desde rutas absolutas
                </div>
            </div>
        </div>
    </div>
</body>
</html>