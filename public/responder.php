<?php
// No usar session_start() aquí - es público
require_once '../config/conexion.php';
require_once '../includes/render_preguntas.php';

// Headers anti-caché para prevenir duplicación de procesos
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$mensaje = '';
$error = '';
$error_persistente = false; // Variable para errores que no deben desaparecer
$mensaje_persistente = false; // Variable para mensajes que no deben desaparecer
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
        
        // Procesar respuesta - Implementar protección contra reenvío
        if ($_POST && isset($_POST['enviar_respuesta']) && $encuesta && !empty($preguntas)) {
            // Verificar token de una sola vez para prevenir reenvío duplicado
            $form_token = $_POST['form_token'] ?? '';
            $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] . $encuesta['id']);
            
            // Verificar si ya existe una respuesta con este token en los últimos 5 minutos
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM respuestas_encuesta 
                WHERE ip_hash = ? AND encuesta_id = ? AND fecha_completada > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->execute([$ip_hash, $encuesta['id']]);
            $respuestas_recientes = $stmt->fetchColumn();
            
            if ($respuestas_recientes > 0) {
                $error = "Ya ha enviado una respuesta recientemente. Si necesita enviar otra respuesta, espere unos minutos.";
                $error_persistente = true; // Marcar como error que no debe desaparecer
            } else {
                $respuestas = $_POST['respuestas'] ?? [];
                
                // Validar respuestas obligatorias
                $errores_validacion = [];
                foreach ($preguntas as $pregunta) {
                    if (($pregunta['obligatoria'] || $pregunta['obligatoria_encuesta']) && empty($respuestas[$pregunta['id']])) {
                        $errores_validacion[] = "La pregunta '" . substr($pregunta['texto'], 0, 50) . "...' es obligatoria.";
                    }
                }
                
                if (empty($errores_validacion)) {
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
                        
                        // Redirect con mensaje de éxito para evitar reenvío
                        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . urlencode($enlace_publico) . "&success=1");
                        exit();
                    } else {
                        $error = "Error al procesar su respuesta. Por favor, intente nuevamente.";
                    }
                } else {
                    $error = implode('<br>', $errores_validacion);
                }
            }
        }
        
        // Mostrar mensaje de éxito después del redirect
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            $mensaje = "¡Gracias por participar! Su respuesta ha sido registrada exitosamente.";
            $mensaje_persistente = true; // Marcar como mensaje que no debe desaparecer
            $preguntas = []; // Ocultar formulario después de enviar
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
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background: #21bd00;
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
        /* .pregunta-meta removido - información técnica no se muestra en vista pública */
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
            background: #21bd00;
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
            border-left: 4px solid #21bd00;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .persistent-alert {
            animation: pulse-glow 2s infinite;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
        }
        .persistent-success-alert {
            animation: pulse-glow-success 3s infinite;
            box-shadow: 0 0 15px rgba(50, 205, 50, 0.4);
            border: 2px solid rgba(50, 205, 50, 0.6);
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 10px rgba(220, 53, 69, 0.3); }
            50% { box-shadow: 0 0 15px rgba(220, 53, 69, 0.5); }
            100% { box-shadow: 0 0 10px rgba(220, 53, 69, 0.3); }
        }
        @keyframes pulse-glow-success {
            0% { 
                box-shadow: 0 0 15px rgba(50, 205, 50, 0.4);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 25px rgba(50, 205, 50, 0.7);
                transform: scale(1.02);
            }
            100% { 
                box-shadow: 0 0 15px rgba(50, 205, 50, 0.4);
                transform: scale(1);
            }
        }
        .progreso {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            margin: 1rem 0;
            overflow: hidden;
        }
        .progreso-fill {
            background: #21bd00;
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
        
        /* Media queries para responsive */
        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }
            
            .header {
                padding: 1.5rem 0;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header .subtitle {
                font-size: 1rem;
            }
            
            .encuesta-header {
                padding: 1.5rem 1rem;
            }
            
            .encuesta-title {
                font-size: 1.4rem;
            }
            
            .encuesta-meta {
                padding: 1rem;
                font-size: 0.9rem;
            }
            
            .form-content {
                padding: 1rem;
            }
            
            .pregunta {
                margin-bottom: 1.5rem;
            }
            
            .likert-scale {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .likert-option {
                padding: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .radio-group, .checkbox-group {
                gap: 0.5rem;
            }
            
            .radio-item, .checkbox-item {
                padding: 0.5rem;
                flex-wrap: wrap;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 0.25rem;
            }
            
            .encuesta-card {
                margin-bottom: 1rem;
            }
            
            .encuesta-header {
                padding: 1rem 0.75rem;
            }
            
            .encuesta-title {
                font-size: 1.2rem;
            }
            
            .form-content {
                padding: 0.75rem;
            }
            
            .pregunta-titulo {
                font-size: 1rem;
            }
            
            .form-control {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .radio-item, .checkbox-item {
                padding: 0.4rem;
                font-size: 0.9rem;
            }
            
            .radio-item input, .checkbox-item input {
                margin-right: 0.5rem;
            }
            
            .footer {
                padding: 1.5rem 1rem;
            }
        }
        
        /* Estilos específicos para escala numérica - forzar vista horizontal */
        .escala-numerica {
            margin: 15px 0 !important;
        }
        
        .escala-valores {
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            gap: 15px !important;
            flex-wrap: nowrap !important;
            margin: 15px 0 !important;
            flex-direction: row !important;
        }
        
        .escala-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            flex: 1 !important;
            min-width: 60px !important;
            max-width: 80px !important;
            padding: 10px 5px !important;
        }
        
        .escala-radio {
            margin-bottom: 8px !important;
            width: 18px !important;
            height: 18px !important;
            accent-color: #21bd00 !important;
        }
        
        .escala-numero-label {
            text-align: center !important;
            cursor: pointer !important;
            font-weight: bold !important;
            font-size: 1.1rem !important;
            color: #0d47a1 !important;
            transition: color 0.2s !important;
        }
        
        .escala-numero-label:hover {
            color: #1565c0 !important;
        }
        
        .escala-header {
            display: flex !important;
            justify-content: space-between !important;
            margin-bottom: 10px !important;
            font-size: 0.9rem !important;
            color: #666 !important;
        }
        
        /* Responsive para escalas en móvil */
        @media (max-width: 768px) {
            .escala-valores {
                gap: 10px !important;
            }
            
            .escala-item {
                min-width: 50px !important;
                max-width: 70px !important;
                padding: 8px 3px !important;
            }
            
            .escala-numero-label {
                font-size: 1rem !important;
            }
            
            .escala-header {
                font-size: 0.85rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .escala-valores {
                gap: 8px !important;
            }
            
            .escala-item {
                min-width: 45px !important;
                max-width: 60px !important;
                padding: 6px 2px !important;
            }
            
            .escala-numero-label {
                font-size: 0.9rem !important;
            }
            
            .escala-header {
                font-size: 0.8rem !important;
            }
        }
        
        /* Estilos específicos para escala Likert - mejorar espaciado en móvil */
        .escala-likert .escala-valores {
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            gap: 10px !important;
            flex-wrap: wrap !important;
            margin: 15px 0 !important;
        }
        
        .escala-likert .escala-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            flex: 1 !important;
            min-width: 80px !important;
            max-width: 120px !important;
            padding: 10px 3px !important;
            margin-bottom: 10px !important;
        }
        
        .escala-likert .escala-radio {
            margin-bottom: 8px !important;
            width: 18px !important;
            height: 18px !important;
            accent-color: #21bd00 !important;
        }
        
        .escala-likert .escala-label {
            text-align: center !important;
            cursor: pointer !important;
            font-size: 0.85rem !important;
            transition: all 0.2s !important;
            padding: 5px 2px !important;
            border-radius: 4px !important;
            line-height: 1.3 !important;
        }
        
        .escala-likert .escala-label:hover {
            color: #0d47a1 !important;
            background-color: #f0f4ff !important;
        }
        
        .escala-likert .escala-texto {
            display: block !important;
            font-size: 0.8rem !important;
            color: #333 !important;
            font-weight: 500 !important;
            line-height: 1.2 !important;
            word-wrap: break-word !important;
            hyphens: auto !important;
        }
        
        /* Responsive específico para escala Likert */
        @media (max-width: 768px) {
            .escala-likert .escala-valores {
                gap: 8px !important;
                flex-wrap: wrap !important;
            }
            
            .escala-likert .escala-item {
                min-width: 70px !important;
                max-width: 90px !important;
                padding: 8px 2px !important;
                margin-bottom: 8px !important;
            }
            
            .escala-likert .escala-label {
                font-size: 0.8rem !important;
                padding: 4px 1px !important;
            }
            
            .escala-likert .escala-texto {
                font-size: 0.75rem !important;
                line-height: 1.1 !important;
            }
        }
        
        @media (max-width: 480px) {
            .escala-likert .escala-valores {
                gap: 6px !important;
                justify-content: center !important;
            }
            
            .escala-likert .escala-item {
                min-width: 60px !important;
                max-width: 75px !important;
                padding: 6px 1px !important;
                margin-bottom: 6px !important;
            }
            
            .escala-likert .escala-label {
                font-size: 0.75rem !important;
                padding: 3px 1px !important;
            }
            
            .escala-likert .escala-texto {
                font-size: 0.7rem !important;
                line-height: 1.0 !important;
            }
            
            .escala-likert .escala-radio {
                width: 16px !important;
                height: 16px !important;
            }
        }
        
        /* Estilos para matrices */
        .matriz-container {
            width: 100%;
            overflow-x: auto;
            margin: 1rem 0;
        }
        
        .matriz-table,
        .matriz-escala-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: fit-content;
        }
        
        .matriz-table th,
        .matriz-table td,
        .matriz-escala-table th,
        .matriz-escala-table td {
            padding: 0.75rem 0.5rem;
            text-align: center;
            border: 1px solid #e0e0e0;
            vertical-align: middle;
            word-wrap: break-word;
            hyphens: auto;
        }
        
        .matriz-table th,
        .matriz-escala-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 0.85rem;
            line-height: 1.3;
            min-width: 80px;
            max-width: 120px;
            width: auto;
        }
        
        .matriz-label {
            text-align: left !important;
            font-weight: 500;
            background: #f8f9fa !important;
            color: #333;
            padding-left: 1rem !important;
            min-width: 150px;
            white-space: nowrap;
        }
        
        .matriz-cell {
            background: white;
            position: relative;
            min-width: 60px;
            width: auto;
        }
        
        .matriz-cell input[type="radio"] {
            width: 18px !important;
            height: 18px !important;
            margin: 0 !important;
            cursor: pointer;
            accent-color: #21bd00;
        }
        
        .matriz-escala-wrapper {
            margin: 1rem 0;
        }
        
        .matriz-escala-container {
            overflow-x: auto;
        }
        
        /* Responsive para matrices */
        @media (max-width: 768px) {
            .matriz-container,
            .matriz-escala-container {
                font-size: 0.8rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .matriz-table,
            .matriz-escala-table {
                min-width: 600px;
            }
            
            .matriz-table th,
            .matriz-table td,
            .matriz-escala-table th,
            .matriz-escala-table td {
                padding: 0.6rem 0.3rem;
                font-size: 0.75rem;
                line-height: 1.2;
            }
            
            .matriz-table th,
            .matriz-escala-table th {
                min-width: 70px;
                max-width: 100px;
                font-size: 0.7rem;
                padding: 0.6rem 0.2rem;
            }
            
            .matriz-label {
                padding-left: 0.5rem !important;
                font-size: 0.8rem;
                min-width: 120px;
                max-width: 150px;
            }
            
            .matriz-cell {
                min-width: 50px;
            }
            
            .matriz-cell input[type="radio"] {
                width: 16px !important;
                height: 16px !important;
            }
        }
        
        @media (max-width: 480px) {
            .matriz-container,
            .matriz-escala-container {
                margin: 0.5rem -0.5rem;
                padding: 0 0.5rem;
            }
            
            .matriz-table,
            .matriz-escala-table {
                font-size: 0.7rem;
                min-width: 500px;
            }
            
            .matriz-table th,
            .matriz-table td,
            .matriz-escala-table th,
            .matriz-escala-table td {
                padding: 0.4rem 0.15rem;
                font-size: 0.65rem;
                line-height: 1.1;
            }
            
            .matriz-table th,
            .matriz-escala-table th {
                min-width: 60px;
                max-width: 80px;
                font-size: 0.6rem;
                font-weight: 700;
                padding: 0.5rem 0.1rem;
            }
            
            .matriz-label {
                padding-left: 0.3rem !important;
                font-size: 0.7rem;
                min-width: 100px;
                max-width: 120px;
            }
            
            .matriz-cell {
                min-width: 45px;
            }
            
            .matriz-cell input[type="radio"] {
                width: 14px !important;
                height: 14px !important;
            }
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
            <div class="alert alert-danger <?= $error_persistente ? 'persistent-alert' : 'auto-hide-alert' ?>">
                <strong>Error:</strong> <?= $error ?>
                <?php if ($error_persistente): ?>
                    <div style="margin-top: 10px; font-size: 0.9em; color: #721c24;">
                        <i><i class="fa-solid fa-lightbulb"></i> Este mensaje permanecerá visible para su información.</i>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($mensaje): ?>
            <div class="alert alert-success <?= $mensaje_persistente ? 'persistent-success-alert' : 'auto-hide-alert' ?>">
                <?= $mensaje ?>
                <br><br>
                <strong>Municipalidad de Hualpén - Dirección de Salud</strong><br>
                Su opinión es importante para mejorar nuestros servicios.
                <?php if ($mensaje_persistente): ?>
                    <div style="margin-top: 15px; font-size: 0.9em; color: #155724; text-align: center;">
                        <i><i class="fa-solid fa-sparkles"></i> Este mensaje permanece visible como confirmación de su participación.</i>
                    </div>
                <?php endif; ?>
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
                        <input type="hidden" name="form_token" value="<?= uniqid('form_', true) ?>">
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
                                
                                <!-- Información técnica removida para vista pública -->
                                
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
        // Auto-ocultar mensajes de alerta después de 3 segundos (excepto persistentes)
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.auto-hide-alert');
            alerts.forEach(function(alert) {
                // Solo aplicar auto-hide si NO es una alerta persistente (error o éxito)
                if (!alert.classList.contains('persistent-alert') && !alert.classList.contains('persistent-success-alert')) {
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
                }
            });
            
            // Para alertas de error persistentes, mostrar un efecto visual especial
            const persistentAlerts = document.querySelectorAll('.persistent-alert');
            persistentAlerts.forEach(function(alert) {
                // Agregar un pequeño icono de información permanente
                const infoIcon = document.createElement('span');
                infoIcon.innerHTML = ' 📌';
                infoIcon.style.float = 'right';
                infoIcon.style.fontSize = '1.2em';
                infoIcon.title = 'Este mensaje permanece visible para su información';
                alert.appendChild(infoIcon);
            });
            
            // Para alertas de éxito persistentes, mostrar un efecto visual especial diferente
            const persistentSuccessAlerts = document.querySelectorAll('.persistent-success-alert');
            persistentSuccessAlerts.forEach(function(alert) {
                // Agregar un icono de éxito permanente
                const successIcon = document.createElement('span');
                successIcon.innerHTML = ' <i class="fa-solid fa-trophy"></i>';
                successIcon.style.float = 'right';
                successIcon.style.fontSize = '1.5em';
                successIcon.style.animation = 'bounce 2s infinite';
                successIcon.title = 'Confirmación permanente de su participación exitosa';
                alert.appendChild(successIcon);
                
                // Agregar estilo de bounce animation
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes bounce {
                        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                        40% { transform: translateY(-10px); }
                        60% { transform: translateY(-5px); }
                    }
                `;
                document.head.appendChild(style);
            });
        });
    </script>
</body>
</html>
