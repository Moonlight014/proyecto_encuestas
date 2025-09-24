<?php
session_start();
require_once '../config/conexion.php';
require_once '../includes/render_preguntas.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$encuesta_id = $_GET['id'] ?? '';
$encuesta = null;
$preguntas = [];
$error = '';

if (empty($encuesta_id) || !is_numeric($encuesta_id)) {
    $error = "ID de encuesta no válido.";
} else {
    try {
        $pdo = obtenerConexion();
        
        if (!$pdo) {
            throw new PDOException("No se pudo establecer conexión con la base de datos");
        }
        
        // Obtener datos de la encuesta
        $stmt = $pdo->prepare("
            SELECT e.*, d.nombre as departamento_nombre 
            FROM encuestas e 
            LEFT JOIN departamentos d ON e.departamento_id = d.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$encuesta_id]);
        $encuesta = $stmt->fetch();
        
        if (!$encuesta) {
            $error = "Encuesta no encontrada.";
        } else {
            // Obtener preguntas de la encuesta
            $stmt = $pdo->prepare("
                SELECT bp.*, c.nombre as categoria_nombre, tp.nombre as tipo_nombre, ep.obligatoria_encuesta, ep.orden
                FROM encuesta_preguntas ep
                INNER JOIN banco_preguntas bp ON ep.pregunta_id = bp.id
                LEFT JOIN categorias c ON bp.categoria_id = c.id
                LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
                WHERE ep.encuesta_id = ? AND ep.activa = 1
                ORDER BY ep.orden ASC
            ");
            $stmt->execute([$encuesta_id]);
            $preguntas = $stmt->fetchAll();
        }
        
    } catch(PDOException $e) {
        $error = "Error de conexión: " . $e->getMessage() . " | Archivo: " . $e->getFile() . " | Línea: " . $e->getLine();
    } catch(Exception $e) {
        $error = "Error general: " . $e->getMessage() . " | Archivo: " . $e->getFile() . " | Línea: " . $e->getLine();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa Administrativa - <?= $encuesta ? htmlspecialchars($encuesta['titulo']) : 'Encuesta' ?></title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F8F9FA;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .header-admin {
            background: #0d47a1;
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
            text-align: center;
            margin-bottom: 0;
        }
        .admin-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 0 0 8px 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .encuesta-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .encuesta-header {
            background: #32CD32;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .encuesta-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .encuesta-descripcion {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        .encuesta-meta {
            background: #f8f9fa;
            padding: 1rem 2rem;
            font-size: 0.9rem;
            color: #6c757d;
            border-bottom: 1px solid #e9ecef;
        }
        .preguntas-container {
            padding: 2rem;
        }
        .pregunta-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #32CD32;
        }
        .pregunta-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .pregunta-numero {
            background: #32CD32;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .pregunta-contenido {
            flex: 1;
            margin-left: 1rem;
        }
        .pregunta-texto {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .pregunta-meta {
            background: #e3f2fd;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #1565c0;
            border: 1px solid #bbdefb;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .pregunta-obligatoria {
            color: #dc3545;
            font-weight: 600;
            margin-left: 0.25rem;
        }
        .pregunta-respuesta {
            margin-top: 1rem;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        .back-controls {
            text-align: center;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #0d47a1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 0.5rem;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #1565c0;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-admin">
            <h1><i class="fa-solid fa-eye"></i> Vista Previa Administrativa</h1>
        </div>
        
        <div class="admin-notice">
            <i class="fa-solid fa-info-circle"></i> <strong>Vista Administrativa:</strong> Esta vista incluye información técnica (categorías, tipos, etc.) que NO será visible para los encuestados en el enlace público.
        </div>
        
        <div class="back-controls">
            <a href="ver_encuestas.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Volver a Encuestas</a>
            <?php if ($encuesta && $encuesta['estado'] === 'activa'): ?>
                <a href="../public/responder.php?id=<?= htmlspecialchars($encuesta['enlace_publico']) ?>" 
                   class="btn btn-secondary" target="_blank">
                   <i class="fa-solid fa-external-link-alt"></i> Ver Versión Pública
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fa-solid fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($encuesta): ?>
            <div class="encuesta-card">
                <div class="encuesta-header">
                    <h2 class="encuesta-title"><?= htmlspecialchars($encuesta['titulo']) ?></h2>
                    <p class="encuesta-descripcion"><?= htmlspecialchars($encuesta['descripcion']) ?></p>
                </div>
                
                <div class="encuesta-meta">
                    <strong>Departamento:</strong> <?= htmlspecialchars($encuesta['departamento_nombre']) ?> |
                    <strong>Estado:</strong> <?= ucfirst($encuesta['estado']) ?> |
                    <strong>Total de preguntas:</strong> <?= count($preguntas) ?>
                </div>
                
                <?php if (!empty($preguntas)): ?>
                    <div class="preguntas-container">
                        <?php foreach ($preguntas as $index => $pregunta): ?>
                            <div class="pregunta-item">
                                <div class="pregunta-header">
                                    <div class="pregunta-numero"><?= $index + 1 ?></div>
                                    <div class="pregunta-contenido">
                                        <div class="pregunta-texto">
                                            <?= htmlspecialchars($pregunta['texto']) ?>
                                            <?php if ($pregunta['obligatoria'] || $pregunta['obligatoria_encuesta']): ?>
                                                <span class="pregunta-obligatoria">*</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="pregunta-meta">
                                            <i class="fa-solid fa-tag"></i> Categoría: <?= htmlspecialchars($pregunta['categoria_nombre']) ?> | 
                                            <i class="fa-solid fa-cog"></i> Tipo: <?= htmlspecialchars($pregunta['tipo_nombre']) ?> |
                                            <i class="fa-solid fa-database"></i> ID: <?= $pregunta['id'] ?>
                                        </div>
                                        
                                        <div class="pregunta-respuesta">
                                            <?= renderizarCampoPregunta($pregunta) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="preguntas-container">
                        <div style="text-align: center; padding: 2rem; color: #6c757d;">
                            <i class="fa-solid fa-clipboard-list" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <p>No hay preguntas agregadas a esta encuesta aún.</p>
                            <a href="agregar_preguntas.php?id=<?= $encuesta['id'] ?>" class="btn">
                                <i class="fa-solid fa-plus"></i> Agregar Preguntas
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>