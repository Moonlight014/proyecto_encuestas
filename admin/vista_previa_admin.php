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
    <!-- CSS del sistema -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F8F9FA;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 65px);
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
            border-left: 4px solid #21bd00;
        }
        .pregunta-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .pregunta-numero {
            background: #21bd00;
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
        
        /* Estilos responsive para móvil */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0;
            }
            
            .container {
                padding: 0 0.5rem;
            }
            
            .welcome-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .welcome-section h2 {
                font-size: 1.5rem;
                margin-bottom: 0.75rem;
            }
            
            .welcome-section p {
                font-size: 0.95rem;
                line-height: 1.5;
            }
            
            .back-controls {
                margin-bottom: 1.5rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn {
                padding: 0.875rem 1rem;
                font-size: 0.95rem;
                width: 100%;
                text-align: center;
            }
            
            .encuesta-header {
                padding: 1.5rem 1rem;
            }
            
            .encuesta-title {
                font-size: 1.5rem;
                margin-bottom: 0.75rem;
            }
            
            .encuesta-descripcion {
                font-size: 1rem;
            }
            
            .encuesta-meta {
                padding: 1rem;
                font-size: 0.85rem;
                line-height: 1.4;
            }
            
            .preguntas-container {
                padding: 1rem;
            }
            
            .pregunta-item {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 6px;
            }
            
            .pregunta-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .pregunta-numero {
                align-self: flex-start;
                margin-bottom: 0.5rem;
            }
            
            .pregunta-contenido {
                margin-left: 0;
                width: 100%;
            }
            
            .pregunta-texto {
                font-size: 1rem;
                margin-bottom: 0.75rem;
                line-height: 1.4;
            }
            
            .pregunta-meta {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem;
                margin-bottom: 0.75rem;
                line-height: 1.3;
                word-break: break-word;
            }
            
            .pregunta-respuesta {
                margin-top: 0.75rem;
            }
            
            /* Ajustes específicos para campos de formulario en móvil */
            .pregunta-respuesta input,
            .pregunta-respuesta textarea,
            .pregunta-respuesta select {
                width: 100% !important;
                font-size: 16px; /* Previene zoom en iOS */
                padding: 0.75rem;
                border-radius: 6px;
                border: 1px solid #ddd;
                margin-bottom: 0.5rem;
            }
            
            .pregunta-respuesta textarea {
                min-height: 100px;
                resize: vertical;
            }
            
            /* Mejoras para preguntas tipo matriz en móvil */
            .pregunta-respuesta table {
                width: 100%;
                font-size: 0.85rem;
                border-collapse: collapse;
            }
            
            .pregunta-respuesta th,
            .pregunta-respuesta td {
                padding: 0.5rem 0.25rem;
                text-align: center;
                border: 1px solid #ddd;
            }
            
            .pregunta-respuesta th {
                background: #f8f9fa;
                font-weight: 600;
                font-size: 0.8rem;
            }
            
            /* Mejoras para checkboxes y radios en móvil */
            .pregunta-respuesta input[type="checkbox"],
            .pregunta-respuesta input[type="radio"] {
                width: auto !important;
                margin-right: 0.5rem;
                transform: scale(1.2);
            }
            
            .pregunta-respuesta label {
                display: flex;
                align-items: center;
                margin-bottom: 0.75rem;
                padding: 0.5rem;
                background: #f8f9fa;
                border-radius: 6px;
                cursor: pointer;
                line-height: 1.3;
            }
            
            .error-message {
                margin: 1rem 0.5rem;
                padding: 1rem;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            .encuesta-meta {
                font-size: 0.8rem;
            }
            
            .pregunta-meta {
                font-size: 0.75rem;
            }
            
            .pregunta-respuesta th,
            .pregunta-respuesta td {
                padding: 0.4rem 0.2rem;
                font-size: 0.8rem;
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
            font-size: 0.85rem !important;
            line-height: 1.3 !important;
            min-width: 90px !important;
            max-width: 140px !important;
            width: auto !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            text-align: center !important;
            padding: 0.75rem 0.3rem !important;
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
            display: block;
            font-family: inherit;
        }
        
        .matriz-escala-container {
            overflow-x: auto;
        }
        
        /* Estilos específicos para matriz_escala con máxima especificidad */
        .matriz-escala-container .matriz-escala-table {
            table-layout: fixed !important;
            width: 100% !important;
            border-spacing: 2px !important;
        }

        .matriz-escala-container .matriz-escala-table thead th {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
            text-overflow: clip !important;
            min-width: 100px !important;
            max-width: 140px !important;
            width: 100px !important;
            padding: 0.7rem 0.4rem !important;
            font-size: 0.85rem !important;
            line-height: 1.3 !important;
            vertical-align: middle !important;
            text-align: center !important;
            border: 1px solid #e0e0e0 !important;
            background: #f8f9fa !important;
            font-weight: 600 !important;
            color: #333 !important;
        }

        .matriz-escala-container .matriz-escala-table .matriz-label {
            min-width: 200px !important;
            white-space: normal !important;
            word-wrap: break-word !important;
            padding: 0.7rem 1rem !important;
            font-size: 0.9rem !important;
        }

        /* Forzar wrapping de texto solo en headers de matriz_seleccion */
        .matriz-table thead th {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
            text-overflow: clip !important;
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
            
            .matriz-table th {
                min-width: 75px !important;
                max-width: 110px !important;
                font-size: 0.7rem !important;
                padding: 0.6rem 0.2rem !important;
                white-space: normal !important;
                word-wrap: break-word !important;
                line-height: 1.2 !important;
            }
            
            .matriz-escala-container .matriz-escala-table thead th {
                min-width: 80px !important;
                max-width: 110px !important;
                width: 80px !important;
                font-size: 0.7rem !important;
                padding: 0.6rem 0.25rem !important;
                white-space: normal !important;
                word-wrap: break-word !important;
                line-height: 1.2 !important;
                border-spacing: 1px !important;
            }
            
            .matriz-escala-container .matriz-escala-table .matriz-label {
                min-width: 160px !important;
                font-size: 0.75rem !important;
                white-space: normal !important;
                word-wrap: break-word !important;
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
            
            .matriz-table th {
                min-width: 65px !important;
                max-width: 85px !important;
                font-size: 0.6rem !important;
                font-weight: 700 !important;
                padding: 0.5rem 0.15rem !important;
                white-space: normal !important;
                word-wrap: break-word !important;
                line-height: 1.1 !important;
            }
            
            .matriz-escala-container .matriz-escala-table thead th {
                min-width: 70px !important;
                max-width: 85px !important;
                width: 70px !important;
                font-size: 0.6rem !important;
                font-weight: 700 !important;
                padding: 0.5rem 0.15rem !important;
                white-space: normal !important;
                word-wrap: break-word !important;
                line-height: 1.1 !important;
                border-spacing: 1px !important;
            }
            
            .matriz-escala-container .matriz-escala-table .matriz-label {
                min-width: 140px !important;
                font-size: 0.65rem !important;
                white-space: normal !important;
                word-wrap: break-word !important;
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
    </style>
    
    <?= generarEstilosEscalas() ?>
</head>
<body>
    <?php include '../includes/navbar_complete.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="welcome-section">
                <h2><i class="fa-solid fa-eye"></i> Vista Previa Administrativa</h2>
                <p>Esta vista incluye información técnica que NO será visible para los encuestados en el enlace público.</p>
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
    </div> <!-- /main-content -->
</body>
</html>