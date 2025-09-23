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
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #0d47a1;
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logo-small {
            width: 40px;
            height: 40px;
            background: #32CD32;
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
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logout-btn {
            background: #32CD32;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #228B22;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #32CD32;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #0d47a1;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .actions-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            color: #212529;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f8f9fa;
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
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #0d47a1;
            color: white;
        }
        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .btn-info {
            background: #32CD32;
            color: white;
        }
        .btn-info:hover {
            background: #228B22;
            transform: translateY(-2px);
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
                <div class="stat-number">0</div>
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
