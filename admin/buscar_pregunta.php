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
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/lists.css">
    <style>
        /* Estilos específicos de buscar pregunta que no están en archivos comunes */
        .logo {
            color: var(--text-white);
            display: flex;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .logo i {
            margin-right: 0.5rem;
        }
        /* Ajuste específico de contenedor para esta página */
        .container {
            max-width: 1000px;
        }

        /* Estilos específicos para opciones-preview */
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

    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo"><i class="fa-solid fa-database"></i> Buscar y Editar Pregunta</div>
            <button onclick="document.referrer && document.referrer.includes('dashboard') ? history.back() : window.location.href='dashboard.php'" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </button>
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
            <h4><?= $es_super_admin ? '<i class="fa-solid fa-tools"></i> Modo Super Administrador' : '<i class="fa-solid fa-user"></i> Modo Administrador Departamental' ?></h4>
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
            <h2 class="section-title"><i class="fa-solid fa-search"></i> Buscar Pregunta por ID</h2>
            
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
                    <i class="fa-solid fa-search"></i> Buscar
                </button>
            </form>
        </div>
        
        <!-- Resultado de la búsqueda -->
        <?php if ($pregunta): ?>
            <div class="section">
                <h2 class="section-title"><i class="fa-solid fa-file-alt"></i> Pregunta Encontrada</h2>
                
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
                            <?= $es_super_admin ? '<i class="fa-solid fa-edit"></i> Editar Pregunta' : '<i class="fa-solid fa-clipboard"></i> Usar como Base' ?>
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
            <h3 class="section-title"><i class="fa-solid fa-lightbulb"></i> Instrucciones</h3>
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