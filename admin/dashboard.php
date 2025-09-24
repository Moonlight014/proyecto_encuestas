<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Estilos específicos del dashboard que no están en archivos externos */
        .logo-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logo-small {
            width: 40px;
            height: 40px;
            background: var(--color-success);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .header-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        .actions-section {
            background: var(--bg-white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }
        .section-title {
            color: var(--text-dark);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--bg-light);
            padding-bottom: 0.5rem;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .action-btn {
            display: block;
            text-decoration: none;
            padding: 1rem;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 500;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            text-decoration: none;
        }
        .btn-info {
            background: var(--color-success);
            color: var(--text-white);
        }
        .btn-info:hover {
            background: var(--color-success-hover);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-header">
                <div class="logo-small">DAS</div>
                <h1 class="header-title">Sistema de Encuestas - DAS Hualpén</h1>
            </div>
            <div class="user-info">
                <span>Bienvenido, <?= htmlspecialchars($nombre) ?></span>
                <a href="../public/logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>
    
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
</body>
</html>
