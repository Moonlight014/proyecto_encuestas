<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = obtenerConexion();
    
    // Estadísticas básicas
    $total_encuestas = $pdo->query("SELECT COUNT(*) FROM encuestas")->fetchColumn();
    $encuestas_activas = $pdo->query("SELECT COUNT(*) FROM encuestas WHERE estado = 'activa'")->fetchColumn();
    $total_respuestas = $pdo->query("SELECT COUNT(*) FROM respuestas_encuesta WHERE estado = 'completada'")->fetchColumn();
    $total_preguntas = $pdo->query("SELECT COUNT(*) FROM banco_preguntas WHERE activa = 1")->fetchColumn();
    
    // Encuestas por estado
    $encuestas_por_estado = $pdo->query("
        SELECT estado, COUNT(*) as total 
        FROM encuestas 
        GROUP BY estado 
        ORDER BY total DESC
    ")->fetchAll();
    
    // Encuestas por departamento
    $encuestas_por_depto = $pdo->query("
        SELECT d.nombre as departamento, COUNT(e.id) as total
        FROM departamentos d
        LEFT JOIN encuestas e ON d.id = e.departamento_id
        GROUP BY d.id, d.nombre
        ORDER BY total DESC
    ")->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .back-btn {
            background: #32CD32;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #228B22;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #0d47a1;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }
        .section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            color: #0d47a1;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #32CD32;
            padding-bottom: 0.5rem;
        }
        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .chart-label {
            min-width: 120px;
            font-weight: 500;
        }
        .chart-progress {
            flex: 1;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            margin: 0 1rem;
            overflow: hidden;
        }
        .chart-fill {
            height: 100%;
            background: #32CD32;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .chart-value {
            min-width: 40px;
            text-align: right;
            font-weight: bold;
            color: #0d47a1;
        }
        .coming-soon {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Reportes del Sistema</h1>
            <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_encuestas ?></div>
                <div class="stat-label">Encuestas Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $encuestas_activas ?></div>
                <div class="stat-label">Encuestas Activas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_respuestas ?></div>
                <div class="stat-label">Respuestas Recibidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_preguntas ?></div>
                <div class="stat-label">Preguntas Disponibles</div>
            </div>
        </div>
        
        <!-- Encuestas por estado -->
        <div class="section">
            <h3 class="section-title">Distribución por Estado</h3>
            <?php 
            $max_estado = max(array_column($encuestas_por_estado, 'total'));
            if ($max_estado == 0) $max_estado = 1;
            ?>
            <?php foreach ($encuestas_por_estado as $estado): ?>
                <div class="chart-bar">
                    <div class="chart-label"><?= ucfirst($estado['estado']) ?></div>
                    <div class="chart-progress">
                        <div class="chart-fill" style="width: <?= ($estado['total'] / $max_estado) * 100 ?>%"></div>
                    </div>
                    <div class="chart-value"><?= $estado['total'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Encuestas por departamento -->
        <div class="section">
            <h3 class="section-title">Encuestas por Departamento</h3>
            <?php 
            $max_depto = max(array_column($encuestas_por_depto, 'total'));
            if ($max_depto == 0) $max_depto = 1;
            ?>
            <?php foreach ($encuestas_por_depto as $depto): ?>
                <div class="chart-bar">
                    <div class="chart-label"><?= htmlspecialchars($depto['departamento']) ?></div>
                    <div class="chart-progress">
                        <div class="chart-fill" style="width: <?= ($depto['total'] / $max_depto) * 100 ?>%"></div>
                    </div>
                    <div class="chart-value"><?= $depto['total'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Reportes avanzados -->
        <div class="section">
            <h3 class="section-title">Reportes Detallados</h3>
            <div class="coming-soon">
                <h4>Próximamente disponible:</h4>
                <p>• Análisis de respuestas por pregunta<br>
                • Exportación a Excel<br>
                • Gráficos interactivos<br>
                • Comparativas temporales</p>
            </div>
        </div>
    </div>
</body>
</html>
