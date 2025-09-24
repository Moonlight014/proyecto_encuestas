<?php
// No usar session_start() aquí - es público
require_once '../config/conexion.php';
require_once '../includes/render_preguntas.php';

$mensaje = '';
$error = '';
$encuesta = null;
$preguntas = [];
$enlace_publico = $_GET['id'] ?? '';

if (empty($enlace_publico)) {
    $error = "Enlace de encuesta no válido.";
} else {
    try {
        $pdo = obtenerConexion();
        
        // Obtener datos de la encuesta
        $stmt = $pdo->prepare("
            SELECT e.*, d.nombre as departamento_nombre 
            FROM encuestas e 
            LEFT JOIN departamentos d ON e.departamento_id = d.id 
            WHERE e.enlace_publico = ? AND e.estado = 'activa'
        ");
        $stmt->execute([$enlace_publico]);
        $encuesta = $stmt->fetch();
        
        if (!$encuesta) {
            $error = "Encuesta no encontrada o no está disponible.";
        } else {
            // Verificar fechas de vigencia
            $ahora = date('Y-m-d H:i:s');
            if ($encuesta['fecha_inicio'] && $ahora < $encuesta['fecha_inicio']) {
                $error = "Esta encuesta aún no ha comenzado.";
            } elseif ($encuesta['fecha_fin'] && $ahora > $encuesta['fecha_fin']) {
                $error = "Esta encuesta ha finalizado.";
            } else {
                // Obtener preguntas de la encuesta
                $stmt = $pdo->prepare("
                    SELECT bp.*, ep.orden, ep.obligatoria_encuesta, c.nombre as categoria_nombre, tp.nombre as tipo_nombre
                    FROM encuesta_preguntas ep
                    JOIN banco_preguntas bp ON ep.pregunta_id = bp.id
                    LEFT JOIN categorias c ON bp.categoria_id = c.id
                    LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
                    WHERE ep.encuesta_id = ? AND ep.activa = 1
                    ORDER BY ep.orden
                ");
                $stmt->execute([$encuesta['id']]);
                $preguntas = $stmt->fetchAll();
                
                if (empty($preguntas)) {
                    $error = "Esta encuesta no tiene preguntas configuradas.";
                }
            }
        }
        
        // Procesar respuesta
        if ($_POST && isset($_POST['enviar_respuesta']) && $encuesta && !empty($preguntas)) {
            $respuestas = $_POST['respuestas'] ?? [];
            
            // Validar respuestas obligatorias
            $errores_validacion = [];
            foreach ($preguntas as $pregunta) {
                if (($pregunta['obligatoria'] || $pregunta['obligatoria_encuesta']) && empty($respuestas[$pregunta['id']])) {
                    $errores_validacion[] = "La pregunta '" . substr($pregunta['texto'], 0, 50) . "...' es obligatoria.";
                }
            }
            
            if (empty($errores_validacion)) {
                // Crear hash de IP para control de duplicados (opcional)
                $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] . $encuesta['id']);
                
                // Generar token de sesión único
                $sesion_token = uniqid('resp_', true);
                
                // Insertar respuesta de encuesta
                $stmt = $pdo->prepare("
                    INSERT INTO respuestas_encuesta (encuesta_id, ip_hash, sesion_token, estado, progreso_porcentaje, fecha_completada) 
                    VALUES (?, ?, ?, 'completada', 100.00, NOW())
                ");
                
                if ($stmt->execute([$encuesta['id'], $ip_hash, $sesion_token])) {
                    $respuesta_encuesta_id = $pdo->lastInsertId();
                    
                    // Insertar respuestas detalladas
                    $stmt = $pdo->prepare("
                        INSERT INTO respuestas_detalle (respuesta_encuesta_id, pregunta_id, valor_respuesta) 
                        VALUES (?, ?, ?)
                    ");
                    
                    foreach ($respuestas as $pregunta_id => $valor) {
                        if (!empty($valor)) {
                            // Convertir valor a JSON para almacenar
                            $valor_json = json_encode($valor);
                            $stmt->execute([$respuesta_encuesta_id, $pregunta_id, $valor_json]);
                        }
                    }
                    
                    $mensaje = "¡Gracias por participar! Su respuesta ha sido registrada exitosamente.";
                    $preguntas = []; // Ocultar formulario después de enviar
                } else {
                    $error = "Error al procesar su respuesta. Por favor, intente nuevamente.";
                }
            } else {
                $error = implode('<br>', $errores_validacion);
            }
        }
        
    } catch(PDOException $e) {
        $error = "Error de conexión. Por favor, intente más tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $encuesta ? htmlspecialchars($encuesta['titulo']) : 'Encuesta' ?> - DAS Hualpén</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .header {
            background: #0d47a1;
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
        }
        .header .subtitle {
            margin-top: 0.5rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
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
            line-height: 1.5;
        }
        .encuesta-meta {
            background: #f8f9fa;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .form-content {
            padding: 2rem;
        }
        .pregunta-grupo {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fafafa;
        }
        .pregunta-numero {
            background: #0d47a1;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
            font-size: 0.9rem;
        }
        .pregunta-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .pregunta-texto {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
            line-height: 1.4;
        }
        .pregunta-obligatoria {
            color: #dc3545;
            font-weight: bold;
        }
        .pregunta-meta {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #0d47a1;
            box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
        }
        textarea.form-control {
            height: 100px;
            resize: vertical;
        }
        .radio-group, .checkbox-group {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        .radio-item, .checkbox-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .radio-item:hover, .checkbox-item:hover {
            background: #f0f8ff;
            border-color: #0d47a1;
        }
        .radio-item input, .checkbox-item input {
            margin-right: 0.75rem;
        }
        .likert-scale {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .likert-option {
            text-align: center;
            padding: 1rem 0.5rem;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .likert-option:hover {
            border-color: #0d47a1;
            background: #f0f8ff;
        }
        .likert-option input {
            margin-bottom: 0.5rem;
        }
        .likert-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .btn-enviar {
            background: #32CD32;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 2rem;
        }
        .btn-enviar:hover {
            background: #228B22;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(50, 205, 50, 0.3);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #32CD32;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .progreso {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            margin: 1rem 0;
            overflow: hidden;
        }
        .progreso-fill {
            background: #32CD32;
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        .footer {
            background: #0d47a1;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
    </style>
    
    <?= generarEstilosEscalas() ?>
</head>
<body>
    <div class="header">
        <h1>Dirección de Salud Hualpén</h1>
        <div class="subtitle">Encuestas Públicas de Satisfacción</div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger auto-hide-alert">
                <strong>Error:</strong> <?= $error ?>
            </div>
        <?php elseif ($mensaje): ?>
            <div class="alert alert-success auto-hide-alert">
                <?= $mensaje ?>
                <br><br>
                <strong>Municipalidad de Hualpén - Dirección de Salud</strong><br>
                Su opinión es importante para mejorar nuestros servicios.
            </div>
        <?php elseif ($encuesta): ?>
            <div class="encuesta-card">
                <div class="encuesta-header">
                    <h2 class="encuesta-title"><?= htmlspecialchars($encuesta['titulo']) ?></h2>
                    <p class="encuesta-descripcion"><?= htmlspecialchars($encuesta['descripcion']) ?></p>
                </div>
                
                <div class="encuesta-meta">
                    <strong>Departamento:</strong> <?= htmlspecialchars($encuesta['departamento_nombre']) ?>
                    <?php if ($encuesta['fecha_fin']): ?>
                        | <strong>Disponible hasta:</strong> <?= date('d/m/Y H:i', strtotime($encuesta['fecha_fin'])) ?>
                    <?php endif; ?>
                    | <strong>Preguntas:</strong> <?= count($preguntas) ?>
                </div>
                
                <?php if (!empty($preguntas)): ?>
                    <form method="POST" class="form-content">
                        <?php foreach ($preguntas as $index => $pregunta): ?>
                            <div class="pregunta-grupo">
                                <div class="pregunta-header">
                                    <div class="pregunta-numero"><?= $index + 1 ?></div>
                                    <div class="pregunta-texto">
                                        <?= htmlspecialchars($pregunta['texto']) ?>
                                        <?php if ($pregunta['obligatoria'] || $pregunta['obligatoria_encuesta']): ?>
                                            <span class="pregunta-obligatoria">*</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="pregunta-meta">
                                    Categoría: <?= htmlspecialchars($pregunta['categoria_nombre']) ?> | 
                                    Tipo: <?= htmlspecialchars($pregunta['tipo_nombre']) ?>
                                </div>
                                
                                <div class="pregunta-respuesta">
                                    <?= renderizarCampoPregunta($pregunta) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" name="enviar_respuesta" class="btn-enviar">
                            Enviar Respuestas
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p><strong>Municipalidad de Hualpén - Dirección de Salud</strong></p>
        <p>Su participación nos ayuda a mejorar los servicios de salud para toda la comunidad</p>
    </div>

    <script>
        // Auto-ocultar mensajes de alerta después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.auto-hide-alert');
            alerts.forEach(function(alert) {
                // Agregar animación de fade-out
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    
                    // Remover completamente después de la animación
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 3000); // 3 segundos
            });
        });
    </script>
</body>
</html>
