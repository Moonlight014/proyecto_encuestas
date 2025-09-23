<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$error = '';

try {
    $pdo = obtenerConexion();
    
    // Obtener categorías y departamentos
    $categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $departamentos = $pdo->query("SELECT * FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetchAll();
    
    // Procesar formulario
    if ($_POST && isset($_POST['titulo'])) {
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $departamento_id = $_POST['departamento_id'];
        // Fecha de inicio SIEMPRE automática al momento de crear
        $fecha_inicio = date('Y-m-d H:i:s');
        // Normalizar fecha_fin desde input datetime-local (Y-m-d\TH:i)
        $fecha_fin = null;
        if (!empty($_POST['fecha_fin'])) {
            $ts_fin = strtotime($_POST['fecha_fin']);
            if ($ts_fin !== false) {
                $fecha_fin = date('Y-m-d H:i:s', $ts_fin);
            }
        }
        
        if (empty($titulo) || empty($descripcion)) {
            $error = "El título y la descripción son obligatorios.";
        } else {
            // Validación: Fecha fin no puede ser anterior a fecha inicio (ahora)
            if ($fecha_fin && $fecha_fin < $fecha_inicio) {
                $error = "La Fecha de Fin no puede ser anterior a la Fecha de Inicio (se toma automáticamente con la fecha y hora actual).";
            } else {
                // Generar enlace público único
                $enlace_publico = 'enc_' . uniqid();
                
                // Insertar encuesta
                $stmt = $pdo->prepare("INSERT INTO encuestas (titulo, descripcion, departamento_id, creado_por, fecha_inicio, fecha_fin, enlace_publico, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'borrador')");
                
                if ($stmt->execute([$titulo, $descripcion, $departamento_id, $_SESSION['user_id'], $fecha_inicio, $fecha_fin, $enlace_publico])) {
                    $encuesta_id = $pdo->lastInsertId();
                    $mensaje = "Encuesta creada exitosamente. ID: $encuesta_id";
                } else {
                    $error = "Error al crear la encuesta.";
                }
            }
        }
    }
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Encuesta - DAS Hualpén</title>
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #32CD32;
        }
        .form-title {
            color: #0d47a1;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #0d47a1;
            box-shadow: 0 0 0 2px rgba(13, 71, 161, 0.1);
        }
        textarea.form-control {
            height: 120px;
            resize: vertical;
        }
        .btn-primary {
            background: #0d47a1;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        .alert-success {
            background-color: #f0f8f0;
            border-left-color: #32CD32;
            color: #0f5132;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Crear Nueva Encuesta</h1>
            <a href="dashboard.php" class="back-btn">← Volver al Panel</a>
        </div>
    </div>
    
    <div class="container">
        <div class="form-card">
            <h2 class="form-title">Información Básica de la Encuesta</h2>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="titulo" class="form-label">Título de la Encuesta *</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required 
                           placeholder="Ej: Encuesta de Satisfacción CESFAM 2025"
                           value="<?= isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion" class="form-label">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required 
                              placeholder="Describe el propósito y alcance de esta encuesta..."><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="departamento_id" class="form-label">Departamento Responsable *</label>
                    <select id="departamento_id" name="departamento_id" class="form-control" required>
                        <option value="">Seleccionar departamento...</option>
                        <?php foreach ($departamentos as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= (isset($_POST['departamento_id']) && $_POST['departamento_id'] == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Fecha de Inicio</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y H:i') ?> (automática)" readonly disabled>
                        <small style="color:#6c757d;">Se toma automáticamente al crear la encuesta.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin" class="form-label">Fecha de Fin (opcional)</label>
                        <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control"
                               min="<?= date('Y-m-d\TH:i') ?>"
                               value="<?= isset($_POST['fecha_fin']) ? htmlspecialchars($_POST['fecha_fin']) : '' ?>">
                        <small style="color:#6c757d;">Debe ser igual o posterior a la fecha de inicio (ahora).</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-primary">Crear Encuesta</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
