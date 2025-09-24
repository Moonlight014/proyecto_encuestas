<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$error = '';
$pregunta = null;
$es_super_admin = ($_SESSION['rol'] ?? 'admin_departamental') === 'super_admin';

try {
    $pdo = obtenerConexion();
    
    // Buscar pregunta por ID
    if (isset($_GET['buscar']) || isset($_POST['buscar_pregunta'])) {
        $pregunta_id = $_GET['id'] ?? $_POST['pregunta_id'] ?? null;
        
        if ($pregunta_id && is_numeric($pregunta_id)) {
            $stmt = $pdo->prepare("
                SELECT bp.*, c.nombre as categoria_nombre, tp.nombre as tipo_nombre
                FROM banco_preguntas bp
                JOIN categorias c ON bp.categoria_id = c.id
                JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
                WHERE bp.id = ?
            ");
            $stmt->execute([$pregunta_id]);
            $pregunta = $stmt->fetch();
            
            if (!$pregunta) {
                $error = "No se encontró ninguna pregunta con el ID: " . htmlspecialchars($pregunta_id);
            }
        } else {
            $error = "Por favor, ingresa un ID válido.";
        }
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar y Editar Pregunta - Sistema de Encuestas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0d47a1;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .section-title {
            color: #0d47a1;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: end;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #0d47a1;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
        }
        .btn-primary {
            background: #0d47a1;
            color: white;
        }
        .btn-primary:hover {
            background: #0a3a87;
            transform: translateY(-1px);
        }
        .btn-success {
            background: #32CD32;
            color: white;
        }
        .btn-success:hover {
            background: #28a745;
            transform: translateY(-1px);
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .pregunta-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .pregunta-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .pregunta-id {
            background: #0d47a1;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .pregunta-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .pregunta-texto {
            font-size: 1.1rem;
            font-weight: 500;
            margin: 1rem 0;
            color: #333;
        }
        .opciones-preview {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
        }
        .opciones-preview h4 {
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .opcion-item {
            padding: 0.25rem 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .actions-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .role-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2196f3;
        }
        .role-info h4 {
            color: #1565c0;
            margin-bottom: 0.5rem;
        }
        .role-info p {
            color: #0d47a1;
            margin: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🗃️ Buscar y Editar Pregunta</div>
            <button onclick="history.back()" class="back-btn">← Volver</button>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-success auto-hide-alert"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger auto-hide-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Información del rol -->
        <div class="role-info">
            <h4><?= $es_super_admin ? '🔧 Modo Super Administrador' : '👤 Modo Administrador Departamental' ?></h4>
            <p>
                <?php if ($es_super_admin): ?>
                    Puedes editar preguntas directamente en el banco. Los cambios modificarán la pregunta original.
                <?php else: ?>
                    Al "editar" una pregunta, se creará una nueva versión sin modificar la original del banco.
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Formulario de búsqueda -->
        <div class="section">
            <h2 class="section-title">🔍 Buscar Pregunta por ID</h2>
            
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="pregunta_id">ID de la Pregunta:</label>
                    <input type="number" 
                           id="pregunta_id" 
                           name="pregunta_id" 
                           class="form-control" 
                           placeholder="Ingresa el ID único de la pregunta"
                           value="<?= htmlspecialchars($_POST['pregunta_id'] ?? '') ?>"
                           min="1" 
                           required>
                </div>
                <button type="submit" name="buscar_pregunta" class="btn btn-primary">
                    🔍 Buscar
                </button>
            </form>
        </div>
        
        <!-- Resultado de la búsqueda -->
        <?php if ($pregunta): ?>
            <div class="section">
                <h2 class="section-title">📝 Pregunta Encontrada</h2>
                
                <div class="pregunta-card">
                    <div class="pregunta-header">
                        <span class="pregunta-id">ID: <?= $pregunta['id'] ?></span>
                        <div class="pregunta-meta">
                            Categoría: <?= htmlspecialchars($pregunta['categoria_nombre']) ?> | 
                            Tipo: <?= htmlspecialchars($pregunta['tipo_nombre']) ?> | 
                            Estado: <?= $pregunta['activa'] ? 'Activa' : 'Inactiva' ?>
                        </div>
                    </div>
                    
                    <div class="pregunta-texto">
                        "<?= htmlspecialchars($pregunta['texto']) ?>"
                    </div>
                    
                    <?php if ($pregunta['opciones']): ?>
                        <div class="opciones-preview">
                            <h4>Opciones disponibles:</h4>
                            <?php 
                            $opciones = json_decode($pregunta['opciones'], true);
                            if ($opciones):
                                foreach ($opciones as $key => $valor):
                                    if ($key !== '_limite_maximo'):
                            ?>
                                <div class="opcion-item">• <?= htmlspecialchars($valor) ?></div>
                            <?php 
                                    endif;
                                endforeach;
                                if (isset($opciones['_limite_maximo'])):
                            ?>
                                <div style="margin-top: 0.5rem; font-weight: 500; color: #495057;">
                                    Límite máximo de selecciones: <?= $opciones['_limite_maximo'] ?>
                                </div>
                            <?php endif; endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="actions-buttons">
                        <a href="editar_pregunta.php?id=<?= $pregunta['id'] ?>&from=busqueda" 
                           class="btn <?= $es_super_admin ? 'btn-warning' : 'btn-success' ?>">
                            <?= $es_super_admin ? '✏️ Editar Pregunta' : '📋 Usar como Base' ?>
                        </a>
                        
                        <?php if ($es_super_admin && $pregunta['activa']): ?>
                            <form method="POST" style="display: inline;" action="gestionar_preguntas.php">
                                <input type="hidden" name="pregunta_id" value="<?= $pregunta['id'] ?>">
                                <button type="submit" name="desactivar_pregunta" class="btn btn-warning">
                                    ⏸️ Desactivar
                                </button>
                            </form>
                        <?php elseif ($es_super_admin): ?>
                            <form method="POST" style="display: inline;" action="gestionar_preguntas.php">
                                <input type="hidden" name="pregunta_id" value="<?= $pregunta['id'] ?>">
                                <button type="submit" name="activar_pregunta" class="btn btn-success">
                                    ▶️ Activar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Instrucciones -->
        <div class="section">
            <h3 class="section-title">💡 Instrucciones</h3>
            <div style="color: #6c757d; line-height: 1.6;">
                <p><strong>1.</strong> Ingresa el ID único de la pregunta que deseas buscar</p>
                <p><strong>2.</strong> Haz clic en "Buscar" para localizar la pregunta</p>
                <p><strong>3.</strong> Una vez encontrada, podrás:</p>
                <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                    <?php if ($es_super_admin): ?>
                        <li><strong>Editar:</strong> Modificar la pregunta directamente</li>
                        <li><strong>Activar/Desactivar:</strong> Controlar su disponibilidad</li>
                    <?php else: ?>
                        <li><strong>Usar como Base:</strong> Crear una nueva pregunta basada en la existente</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
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