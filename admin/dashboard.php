<?php
// Protección de sesión - DEBE ser lo primero
require_once '../includes/session_guard.php';

require_once '../config/conexion.php';
require_once '../config/path_helper.php';

// Usar la función helper para detección automática de rutas
$base_url = detectar_base_url();

try {
    $pdo = obtenerConexion();
    
    // Obtener estadísticas básicas
    $stmt = $pdo->query("SELECT COUNT(*) FROM encuestas");
    $total_encuestas = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM banco_preguntas WHERE activa = 1");
    $total_preguntas = $stmt->fetchColumn();
    
    // Obtener total de respuestas recibidas
    $stmt = $pdo->query("SELECT COUNT(*) FROM respuestas_encuesta");
    $total_respuestas = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
    $total_respuestas = 0;
}

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
</body>
</html>
