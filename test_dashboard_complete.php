<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Usuario Test';
$_SESSION['rol'] = 'super_admin';

// Simulamos datos para el dashboard
$total_encuestas = 4;
$total_preguntas = 105;
$total_respuestas = 12;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard Completo</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/navbar_complete.php'; ?>
    
    <div class="main-container">
        <div class="content-wrapper">
            <div class="container">
                <div class="welcome-section">
                    <h2>Panel de Administración</h2>
                    <p>Gestiona las encuestas públicas del DAS Hualpén desde este panel central. Tu rol actual es: <strong>Super Admin</strong></p>
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
                        <a href="admin/crear_encuesta.php" class="action-btn btn-primary">
                            + Nueva Encuesta
                        </a>
                        <a href="admin/gestionar_preguntas.php" class="action-btn btn-secondary">
                            Banco de Preguntas
                        </a>
                        <a href="admin/ver_encuestas.php" class="action-btn btn-info">
                            Ver Encuestas
                        </a>
                        <a href="admin/reportes.php" class="action-btn btn-secondary">
                            Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>