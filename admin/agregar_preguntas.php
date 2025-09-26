<?php
// Protección de sesión - DEBE ser lo primero
require_once '../includes/session_guard.php';

require_once '../config/conexion.php';
require_once '../config/path_helper.php';

$base_url = detectar_base_url();

$encuesta_id = $_GET['id'] ?? 0;
$mensaje = '';
$es_super_admin = ($_SESSION['rol'] ?? 'admin_departamental') === 'super_admin';

try {
    $pdo = obtenerConexion();
    
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ?");
    $stmt->execute([$encuesta_id]);
    $encuesta = $stmt->fetch();
    
    if ($_POST && isset($_POST['agregar_preguntas'])) {
        $preguntas_seleccionadas = $_POST['preguntas'] ?? [];
        if (!empty($preguntas_seleccionadas)) {
            $stmt = $pdo->prepare("SELECT MAX(orden) FROM encuesta_preguntas WHERE encuesta_id = ?");
            $stmt->execute([$encuesta_id]);
            $ultimo_orden = $stmt->fetchColumn() ?? 0;
            
            $agregadas = 0;
            foreach ($preguntas_seleccionadas as $pregunta_id) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM encuesta_preguntas WHERE encuesta_id = ? AND pregunta_id = ?");
                $stmt->execute([$encuesta_id, $pregunta_id]);
                
                if ($stmt->fetchColumn() == 0) {
                    $ultimo_orden++;
                    $stmt = $pdo->prepare("INSERT INTO encuesta_preguntas (encuesta_id, pregunta_id, orden) VALUES (?, ?, ?)");
                    if ($stmt->execute([$encuesta_id, $pregunta_id, $ultimo_orden])) {
                        $agregadas++;
                    }
                }
            }
            $mensaje = "$agregadas pregunta(s) agregada(s).";
        }
    }
    
    $preguntas_banco = $pdo->query("
        SELECT bp.*, c.nombre as categoria_nombre
        FROM banco_preguntas bp
        LEFT JOIN categorias c ON bp.categoria_id = c.id
        WHERE bp.activa = 1
        ORDER BY c.nombre, bp.orden
    ")->fetchAll();
    
    $preguntas_por_categoria = [];
    foreach ($preguntas_banco as $pregunta) {
        $categoria = $pregunta['categoria_nombre'] ?? 'Sin categoría';
        $preguntas_por_categoria[$categoria][] = $pregunta;
    }
    
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Preguntas - DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS del sistema -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; background: #f8f9fa; }
        h1 { color: #0d47a1; }
        .categoria { 
            border: 2px solid #e9ecef; 
            margin: 1rem 0; 
            border-radius: 8px;
            overflow: hidden;
        }
        .categoria-header { 
            background: #0d47a1; 
            color: white; 
            padding: 1rem; 
            cursor: pointer;
            display: flex; 
            justify-content: space-between;
            user-select: none;
        }
        .categoria-header:hover { background: #1565c0; }
        .categoria-header.activo { background: #32CD32; }
        .categoria-content { 
            display: none; 
            padding: 1rem; 
            background: white; 
        }
        .categoria-content.mostrar { display: block; }
        .pregunta { 
            margin: 0.5rem 0; 
            padding: 0.75rem; 
            border: 1px solid #eee; 
            border-radius: 4px;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .pregunta:hover { border-color: #32CD32; background: #f8fff8; }
        .pregunta input { margin-top: 0.2rem; }
        .pregunta-content { 
            flex: 1; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
        }
        .pregunta label { flex: 1; cursor: pointer; margin-right: 1rem; }
        .pregunta-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }
        .btn-edit {
            background: #0d47a1;
            color: white;
            padding: 0.25rem 0.5rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8rem;
            white-space: nowrap;
            transition: background 0.2s;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: white;
        }
        .btn { 
            background: #32CD32; 
            color: white; 
            padding: 1rem 2rem; 
            border: none; 
            cursor: pointer; 
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            /* Asegurar mismo tamaño que .btn-secondary */
            box-sizing: border-box;
            line-height: 1.5;
            min-height: 3.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn:hover { background: #228B22; }
        .btn-secondary { 
            background: #8B4513; /* Marrón para diferenciarlo de los toggles azules */
            color: white; 
            padding: 1rem 2rem; 
            border: none; 
            cursor: pointer; 
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            /* Asegurar mismo tamaño que .btn */
            box-sizing: border-box;
            line-height: 1.5;
            min-height: 3.5rem;
            align-items: center;
            justify-content: center;
        }
        .btn-secondary:hover { 
            background: #A0522D; 
            color: white;
        }
        .botones-container {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            align-items: stretch; /* Cambio de center a stretch para igualar alturas */
            flex-wrap: wrap;
        }
        .botones-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #e7f3ff;
            border-left: 4px solid #0d47a1;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #084298;
        }
        .alert { padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .back-btn {
            background: #32CD32;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
            display: inline-block;
        }
        .back-btn:hover {
            background: #228B22;
            color: white;
        }
        
        /* Media queries para responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }
            
            .pregunta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .pregunta-content {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .pregunta-actions {
                align-self: flex-start;
            }
            
            .botones-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn, .btn-secondary {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
                text-align: center;
            }
            
            h1 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 0.5rem 0.25rem;
            }
            
            .categoria {
                margin: 0.5rem 0;
            }
            
            .categoria-header {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .categoria-content {
                padding: 0.75rem;
            }
            
            .pregunta {
                padding: 0.5rem;
            }
            
            .btn-edit {
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_complete.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="welcome-section">
                <h2>Agregar Preguntas</h2>
                <p>Selecciona preguntas para agregar a: <strong><?= htmlspecialchars($encuesta['titulo']) ?></strong></p>
                <small style="color: #6c757d;">
                    <?= $es_super_admin ? '<i class="fa-solid fa-crown"></i> Super Administrador' : '<i class="fa-solid fa-user"></i> Administrador Departamental' ?>
                </small>
            </div>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-success auto-hide-alert"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <!-- Panel informativo sobre nuevas funciones -->
        <div style="background: #e7f3ff; border-left: 4px solid #0d47a1; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; font-size: 0.9rem; color: #084298;">
            <strong><i class="fa-solid fa-lightbulb"></i> Nuevas Opciones de Gestión:</strong><br>
            • <strong>Seleccionar:</strong> Marca las preguntas que quieres agregar a tu encuesta<br>
            • <strong><?= $es_super_admin ? 'Editar:' : 'Usar como Base:' ?></strong> <?= $es_super_admin ? 'Modifica directamente la pregunta del banco' : 'Crea una nueva pregunta basada en la existente' ?><br>
            • <strong>Crear Nueva:</strong> Agrega una pregunta completamente nueva al banco
        </div>
        
        <?php $num = 0; foreach ($preguntas_por_categoria as $categoria => $preguntas): $num++; ?>
            <div class="categoria">
                <div class="categoria-header" onclick="togglear(<?= $num ?>)" id="header<?= $num ?>">
                    <span><?= htmlspecialchars($categoria) ?></span>
                    <span><?= count($preguntas) ?> preguntas [+]</span>
                </div>
                <div class="categoria-content" id="content<?= $num ?>">
                    <?php foreach ($preguntas as $pregunta): ?>
                        <div class="pregunta">
                            <input type="checkbox" name="preguntas[]" value="<?= $pregunta['id'] ?>" id="p<?= $pregunta['id'] ?>">
                            <div class="pregunta-content">
                                <label for="p<?= $pregunta['id'] ?>">
                                    <?= htmlspecialchars($pregunta['texto']) ?>
                                    <small style="color: #666; display: block;">ID: <?= $pregunta['id'] ?></small>
                                </label>
                                <div class="pregunta-actions">
                                    <a href="editar_pregunta.php?id=<?= $pregunta['id'] ?>&from=agregar&encuesta_id=<?= $encuesta_id ?>" 
                                       class="btn-edit" 
                                       title="<?= $es_super_admin ? 'Editar pregunta' : 'Usar como base para nueva pregunta' ?>">
                                        <?= $es_super_admin ? '<i class="fa-solid fa-edit"></i> Editar' : '<i class="fa-solid fa-clipboard"></i> Usar como Base' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="botones-container">
            <button type="submit" name="agregar_preguntas" class="btn">
                <i class="fa-solid fa-check"></i> Agregar Preguntas Seleccionadas
            </button>
            <a href="crear_pregunta.php?from=agregar&encuesta_id=<?= $encuesta_id ?>" class="btn-secondary">
                <i class="fa-solid fa-plus"></i> Crear Nueva Pregunta
            </a>
        </div>
        
        <div class="botones-info">
            <strong><i class="fa-solid fa-lightbulb"></i> Opciones disponibles:</strong><br>
            • Selecciona preguntas existentes del banco para agregarlas a tu encuesta<br>
            • O crea una nueva pregunta personalizada con el botón "Crear Nueva Pregunta"
        </div>
    </form>

    <script>
        function togglear(num) {
            const content = document.getElementById('content' + num);
            const header = document.getElementById('header' + num);
            
            // Cerrar todos
            for(let i = 1; i <= <?= $num ?>; i++) {
                if(i !== num) {
                    document.getElementById('content' + i).classList.remove('mostrar');
                    document.getElementById('header' + i).classList.remove('activo');
                }
            }
            
            // Toggle actual
            if (content.classList.contains('mostrar')) {
                content.classList.remove('mostrar');
                header.classList.remove('activo');
            } else {
                content.classList.add('mostrar');
                header.classList.add('activo');
            }
        }

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
        </div> <!-- /container -->
    </div> <!-- /main-content -->
</body>
</html>
