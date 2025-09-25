<?php
session_start();

// TEMPORAL: Simular login para testing
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Administrador Test';
$_SESSION['rol'] = 'super_admin';

// Comentar autenticación temporalmente
// require_once '../config/conexion.php';
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

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

// Datos simulados en lugar de base de datos
$total_encuestas = 4;
$total_preguntas = 105;
$total_respuestas = 12;

$nombre = $_SESSION['nombre'];
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/dashboard.css">

</head>
<body>
    <?php include '../includes/navbar_complete.php'; ?>
    
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
                        <a href="crear_encuesta.php" class="action-btn btn-primary">
                            + Nueva Encuesta
                        </a>
                        <a href="gestionar_preguntas.php" class="action-btn btn-secondary">
                            Banco de Preguntas
                        </a>
                        <a href="ver_encuestas.php" class="action-btn btn-info">
                            Ver Encuestas
                        </a>
                        <a href="reportes.php" class="action-btn btn-secondary">
                            Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>