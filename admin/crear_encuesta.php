<?php
// Protección de sesión - DEBE ser lo primero
require_once '../includes/session_guard.php';

require_once '../config/conexion.php';

$mensaje = '';
$error = '';

try {
    $pdo = obtenerConexion();
    
    // Obtener categorías y departamentos
    $categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $departamentos = $pdo->query("SELECT * FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetchAll();
    
    // Variables para mostrar mensajes después del redirect
    $mensaje = '';
    $error = '';
    
    // Verificar si hay mensajes en la sesión
    if (isset($_SESSION['mensaje_encuesta'])) {
        $mensaje = $_SESSION['mensaje_encuesta'];
        unset($_SESSION['mensaje_encuesta']);
    }
    if (isset($_SESSION['error_encuesta'])) {
        $error = $_SESSION['error_encuesta'];
        unset($_SESSION['error_encuesta']);
    }
    
    // Procesar formulario - Patrón PRG (Post-Redirect-Get)
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
            $_SESSION['error_encuesta'] = "El título y la descripción son obligatorios.";
        } else {
            // Validación ESTRICTA: fecha_fin debe ser futura (no puede ser igual o anterior al momento actual)
            $momento_actual = date('Y-m-d H:i:s');
            if ($fecha_fin && $fecha_fin <= $momento_actual) {
                $_SESSION['error_encuesta'] = "La fecha de cierre debe ser futura. No se puede establecer una fecha que ya pasó o es el momento actual.";
            } else {
                // Generar enlace público único
                $enlace_publico = 'enc_' . uniqid();
                
                // Insertar encuesta
                $stmt = $pdo->prepare("INSERT INTO encuestas (titulo, descripcion, departamento_id, creado_por, fecha_inicio, fecha_fin, enlace_publico, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'borrador')");
                
                if ($stmt->execute([$titulo, $descripcion, $departamento_id, $_SESSION['user_id'], $fecha_inicio, $fecha_fin, $enlace_publico])) {
                    $encuesta_id = $pdo->lastInsertId();
                    $_SESSION['mensaje_encuesta'] = "Encuesta creada exitosamente. ID: $encuesta_id";
                } else {
                    $_SESSION['error_encuesta'] = "Error al crear la encuesta.";
                }
            }
        }
        
        // Redirect para evitar reenvío del formulario (Patrón PRG)
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}

// Determinar texto del botón "Volver" basado en el referer
$texto_volver = "Volver";
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'ver_encuestas.php') !== false) {
    $texto_volver = "Volver a Encuestas";
} elseif (strpos($referer, 'dashboard.php') !== false) {
    $texto_volver = "Volver al Panel";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS del sistema -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
    <?php include '../includes/navbar_complete.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="welcome-section">
                <h2>Crear Nueva Encuesta</h2>
                <p>Complete la información básica para crear una nueva encuesta</p>
            </div>
            
            <div class="form-card">
                <h3 class="form-title">Información Básica de la Encuesta</h3>
            
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
                        <label class="form-label">Fecha de Apertura</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y H:i') ?> (hoy)" readonly disabled>
                        <small style="color:#6c757d;">La encuesta estará disponible desde ahora.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin" class="form-label">Fecha de Cierre (opcional)</label>
                        <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control"
                               min="<?= date('Y-m-d\TH:i', strtotime('+5 minutes')) ?>"
                               value="<?= isset($_POST['fecha_fin']) ? htmlspecialchars($_POST['fecha_fin']) : '' ?>">
                        <small style="color:#6c757d;">Si deseas que la encuesta tenga una fecha límite, selecciona cuándo debe cerrar.</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-primary">Crear Encuesta</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función para volver a la página anterior de manera inteligente
        function volverAtras() {
            // Verificar si hay historial disponible y es seguro navegar hacia atrás
            if (window.history.length > 1 && document.referrer) {
                try {
                    // Verificar si el referrer es del mismo dominio
                    const referrerUrl = new URL(document.referrer);
                    const currentUrl = new URL(window.location.href);
                    
                    if (referrerUrl.hostname === currentUrl.hostname) {
                        // Verificar que no sea la misma página (evita loops)
                        if (document.referrer !== window.location.href) {
                            window.history.back();
                            return;
                        }
                    }
                } catch (e) {
                    // Error al procesar URLs, usar fallback
                    console.log('Error procesando referrer:', e);
                }
            }
            
            // Fallback: ir al dashboard por defecto
            window.location.href = 'dashboard.php';
        }

        // Validación ESTRICTA en tiempo real para fecha_fin
        document.addEventListener('DOMContentLoaded', function() {
            const fechaFinInput = document.getElementById('fecha_fin');
            
            if (fechaFinInput) {
                // Actualizar valor mínimo cada vez que se abre el selector
                function updateMinDateTime() {
                    const now = new Date();
                    // Agregar 5 minutos de margen para evitar conflictos
                    now.setMinutes(now.getMinutes() + 5);
                    
                    const minDateTime = now.getFullYear() + '-' + 
                        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(now.getDate()).padStart(2, '0') + 'T' + 
                        String(now.getHours()).padStart(2, '0') + ':' + 
                        String(now.getMinutes()).padStart(2, '0');
                    
                    fechaFinInput.setAttribute('min', minDateTime);
                }
                
                // Establecer mínimo inicial
                updateMinDateTime();
                
                // Actualizar mínimo cuando se hace foco en el campo
                fechaFinInput.addEventListener('focus', updateMinDateTime);
                
                // Validación ESTRICTA al cambiar el valor
                fechaFinInput.addEventListener('change', function() {
                    const selectedDateTime = new Date(this.value);
                    const currentDateTime = new Date();
                    
                    // Debe ser estrictamente mayor al momento actual (no igual)
                    if (selectedDateTime <= currentDateTime) {
                        alert('La fecha de cierre debe ser futura. No puedes seleccionar una fecha que ya pasó o es el momento actual.');
                        this.value = '';
                        return;
                    }
                });
                
                // Validación adicional al enviar el formulario
                const form = fechaFinInput.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (fechaFinInput.value) {
                            const selectedDateTime = new Date(fechaFinInput.value);
                            const currentDateTime = new Date();
                            
                            if (selectedDateTime <= currentDateTime) {
                                e.preventDefault();
                                alert('Error: La fecha de cierre debe ser futura. Por favor, selecciona una fecha válida.');
                                fechaFinInput.focus();
                                return false;
                            }
                        }
                    });
                }
            }
        });

        // Prevenir navegación hacia atrás después de operaciones importantes
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // La página fue cargada desde caché del navegador
                location.reload();
            }
        });

        // Limpiar historial para prevenir duplicaciones
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    </div> <!-- /main-content -->
</body>
</html>
