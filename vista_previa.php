<?php
session_start();
require_once 'config/conexion.php';
require_once 'config/url_helper.php';
require_once 'includes/render_preguntas.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$encuesta_id = $_GET['id'] ?? 0;

try {
    
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
        header("Location: ver_encuestas.php");
        exit();
    }
    
    // Obtener preguntas de la encuesta con sus tipos
    $stmt = $pdo->prepare("
        SELECT bp.*, ep.orden, ep.obligatoria_encuesta, c.nombre as categoria_nombre, tp.nombre as tipo_nombre, tp.descripcion as tipo_descripcion
        FROM encuesta_preguntas ep
        JOIN banco_preguntas bp ON ep.pregunta_id = bp.id
        LEFT JOIN categorias c ON bp.categoria_id = c.id
        LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
        WHERE ep.encuesta_id = ? AND ep.activa = 1
        ORDER BY ep.orden
    ");
    $stmt->execute([$encuesta_id]);
    $preguntas = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}

function renderizarPreguntaPreview($pregunta) {
    $html = '<div class="pregunta-preview">';
    $html .= '<div class="pregunta-titulo">';
    $html .= '<span class="pregunta-numero">' . $pregunta['orden'] . '</span>';
    $html .= '<span class="pregunta-texto">' . htmlspecialchars($pregunta['texto']) . '</span>';
    if ($pregunta['obligatoria'] || $pregunta['obligatoria_encuesta']) {
        $html .= '<span class="obligatoria">*</span>';
    }
    $html .= '</div>';
    
    $html .= '<div class="pregunta-meta">';
    $html .= '<small>Categoría: ' . htmlspecialchars($pregunta['categoria_nombre']) . ' | ';
    $html .= 'Tipo: ' . htmlspecialchars($pregunta['tipo_descripcion']) . '</small>';
    $html .= '</div>';
    
    $html .= '<div class="respuesta-preview">';
    
    // Usar nuestro sistema de renderizado pero deshabilitado para preview
    $pregunta_temp = $pregunta;
    $campo_html = renderizarCampoPregunta($pregunta_temp);
    
    // Deshabilitar todos los campos para la vista previa
    $campo_html = str_replace('<input ', '<input disabled ', $campo_html);
    $campo_html = str_replace('<textarea ', '<textarea disabled ', $campo_html);
    $campo_html = str_replace('<select ', '<select disabled ', $campo_html);
    
    $html .= $campo_html;
    $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - <?= htmlspecialchars($encuesta['titulo']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .admin-bar {
            background: #0d47a1;
            color: white;
            padding: 1rem;
            text-align: center;
            border-bottom: 3px solid #32CD32;
        }
        .admin-bar h1 {
            margin: 0;
            font-size: 1.2rem;
        }
        .admin-actions {
            margin-top: 0.5rem;
        }
        .btn-admin {
            background: #32CD32;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 0.5rem;
            font-size: 0.9rem;
        }
        .btn-admin:hover {
            background: #228B22;
        }
        
        /* Estilos de la vista previa - simulando la interfaz pública */
        .public-header {
            background: #0d47a1;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 0;
        }
        .public-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
        }
        .public-subtitle {
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
        
        .form-preview {
            padding: 2rem;
        }
        
        .pregunta-preview {
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fafafa;
        }
        
        .pregunta-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        
        .pregunta-numero {
            background: #0d47a1;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .pregunta-texto {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
            line-height: 1.4;
        }
        
        .obligatoria {
            color: #dc3545;
            font-weight: bold;
        }
        
        .pregunta-meta {
            margin-bottom: 1rem;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .respuesta-preview {
            margin-top: 1rem;
        }
        
        /* Estilos específicos por tipo de pregunta */
        .likert-preview {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
        }
        
        .likert-option {
            text-align: center;
            padding: 0.75rem 0.5rem;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 6px;
        }
        
        .likert-option input {
            margin-bottom: 0.5rem;
        }
        
        .likert-option small {
            display: block;
            font-size: 0.7rem;
            color: #6c757d;
        }
        
        .radio-preview, .checkbox-preview {
            margin: 0.5rem 0;
            padding: 0.75rem;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        
        .escala-preview {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .escala-preview input[type="range"] {
            width: 100%;
            margin: 0.5rem 0;
        }
        
        .escala-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .textarea-preview {
            width: 100%;
            height: 100px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
        }
        
        .text-preview, .number-preview, .email-preview, .date-preview {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .preview-note {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin: 2rem 0;
            border-left: 4px solid #ffc107;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .footer-preview {
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
    <div class="admin-bar">
        <h1>VISTA PREVIA DE ADMINISTRADOR</h1>
        <div class="admin-actions">
            <a href="ver_encuestas.php" class="btn-admin">← Volver a Encuestas</a>
            <a href="agregar_preguntas.php?id=<?= $encuesta_id ?>" class="btn-admin">Editar Preguntas</a>
            <?php if ($encuesta['estado'] === 'activa'): ?>
                <a href="<?= generarRutaRelativaResponder($encuesta['enlace_publico']) ?>" class="btn-admin" target="_blank">Ver Como Ciudadano</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="public-header">
        <h2>Dirección de Salud Hualpén</h2>
        <div class="public-subtitle">Encuestas Públicas de Satisfacción</div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="encuesta-card">
                <div class="encuesta-header">
                    <h3 class="encuesta-title"><?= htmlspecialchars($encuesta['titulo']) ?></h3>
                    <p class="encuesta-descripcion"><?= htmlspecialchars($encuesta['descripcion']) ?></p>
                </div>
                
                <div class="encuesta-meta">
                    <strong>Departamento:</strong> <?= htmlspecialchars($encuesta['departamento_nombre']) ?>
                    <?php if ($encuesta['fecha_fin']): ?>
                        | <strong>Disponible hasta:</strong> <?= date('d/m/Y H:i', strtotime($encuesta['fecha_fin'])) ?>
                    <?php endif; ?>
                    | <strong>Estado:</strong> <?= ucfirst($encuesta['estado']) ?>
                    | <strong>Preguntas:</strong> <?= count($preguntas) ?>
                </div>
                
                <div class="form-preview">
                    <?php if (empty($preguntas)): ?>
                        <div class="empty-state">
                            <h4>Esta encuesta no tiene preguntas</h4>
                            <p>Agrega preguntas desde el banco para ver la vista previa</p>
                            <a href="agregar_preguntas.php?id=<?= $encuesta_id ?>" class="btn-admin">Agregar Preguntas</a>
                        </div>
                    <?php else: ?>
                        <div class="preview-note">
                            <strong>Nota:</strong> Esta es una vista previa de cómo verán la encuesta los ciudadanos. 
                            Los campos están deshabilitados para evitar envíos accidentales.
                        </div>
                        
                        <?php foreach ($preguntas as $pregunta): ?>
                            <?= renderizarPreguntaPreview($pregunta) ?>
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; margin-top: 3rem;">
                            <button class="btn-admin" disabled style="padding: 15px 30px; font-size: 1.1rem; opacity: 0.6;">
                                Enviar Respuestas (Deshabilitado en vista previa)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer-preview">
        <p><strong>Municipalidad de Hualpén - Dirección de Salud</strong></p>
        <p>Su participación nos ayuda a mejorar los servicios de salud para toda la comunidad</p>
    </div>
</body>
</html>
