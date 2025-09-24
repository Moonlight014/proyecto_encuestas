<?php
session_start();
require_once '../config/conexion.php';

// Headers anti-caché para prevenir duplicación de procesos
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$error = '';
$es_super_admin = ($_SESSION['rol'] ?? 'admin_departamental') === 'super_admin';

try {
    $pdo = obtenerConexion();
    
    // Procesar acciones (solo super_admin puede activar/desactivar)
    if ($_POST && $es_super_admin) {
        if (isset($_POST['desactivar_pregunta'])) {
            $pregunta_id = $_POST['pregunta_id'];
            $stmt = $pdo->prepare("UPDATE banco_preguntas SET activa = 0 WHERE id = ?");
            if ($stmt->execute([$pregunta_id])) {
                $mensaje = "Pregunta desactivada.";
            }
        } elseif (isset($_POST['activar_pregunta'])) {
            $pregunta_id = $_POST['pregunta_id'];
            $stmt = $pdo->prepare("UPDATE banco_preguntas SET activa = 1 WHERE id = ?");
            if ($stmt->execute([$pregunta_id])) {
                $mensaje = "Pregunta activada.";
            }
        }
    }
    
    // Obtener estadísticas
    $stats = $pdo->query("
        SELECT 
            c.nombre as categoria,
            COUNT(bp.id) as total_preguntas,
            SUM(CASE WHEN bp.activa = 1 THEN 1 ELSE 0 END) as activas,
            SUM(CASE WHEN bp.activa = 0 THEN 1 ELSE 0 END) as inactivas
        FROM categorias c 
        LEFT JOIN banco_preguntas bp ON c.id = bp.categoria_id 
        GROUP BY c.id, c.nombre
        ORDER BY c.nombre
    ")->fetchAll();
    
    // Obtener preguntas por categoría
    $preguntas = $pdo->query("
        SELECT bp.*, c.nombre as categoria_nombre, tp.nombre as tipo_nombre
        FROM banco_preguntas bp
        LEFT JOIN categorias c ON bp.categoria_id = c.id
        LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
        ORDER BY c.nombre, bp.orden, bp.id
    ")->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Preguntas - DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-title {
            color: #0d47a1;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #32CD32;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            color: #0d47a1;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 0.5rem;
        }
        .categoria-group {
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
            align-items: center;
            user-select: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .categoria-header:hover { 
            background: #1565c0; 
        }
        .categoria-header.activo { 
            background: #32CD32; 
        }
        .categoria-content { 
            display: none; 
            padding: 1rem; 
            background: white; 
        }
        .categoria-content.mostrar { 
            display: block; 
        }
        .pregunta-item {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: white;
        }
        .pregunta-item.inactiva {
            background: #f8f9fa;
            opacity: 0.7;
        }
        .pregunta-texto {
            font-weight: 500;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        .pregunta-meta {
            font-size: 0.8rem;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pregunta-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .btn-primary {
            background: #0d47a1;
            color: white;
        }
        .btn-success {
            background: #32CD32;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        a.btn {
            text-decoration: none;
            display: inline-block;
        }
        .create-btn {
            background: linear-gradient(135deg, #32CD32, #28a745);
            color: white;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(50, 205, 50, 0.3);
        }
        .create-btn:hover {
            box-shadow: 0 4px 12px rgba(50, 205, 50, 0.4);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
            box-sizing: border-box;
        }
        textarea.form-control {
            height: 80px;
            resize: vertical;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>Gestionar Banco de Preguntas</h1>
                <small style="opacity: 0.8; font-size: 0.8rem;">
                    <?= $es_super_admin ? '<i class="fa-solid fa-crown"></i> Super Administrador' : '<i class="fa-solid fa-user"></i> Administrador Departamental' ?>
                </small>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-success auto-hide-alert"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger auto-hide-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-title"><?= htmlspecialchars($stat['categoria']) ?></div>
                    <div class="stat-number"><?= $stat['total_preguntas'] ?></div>
                    <div style="font-size: 0.8rem; color: #6c757d;">
                        <?= $stat['activas'] ?> activas | <?= $stat['inactivas'] ?> inactivas
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="content-grid">
            <!-- Lista de preguntas -->
            <div class="section">
                <h3 class="section-title">Preguntas por Categoría</h3>
                
                <?php 
                $preguntas_por_categoria = [];
                foreach ($preguntas as $pregunta) {
                    $categoria = $pregunta['categoria_nombre'] ?? 'Sin categoría';
                    if (!isset($preguntas_por_categoria[$categoria])) {
                        $preguntas_por_categoria[$categoria] = [];
                    }
                    $preguntas_por_categoria[$categoria][] = $pregunta;
                }
                ?>
                
                <?php $num = 0; foreach ($preguntas_por_categoria as $categoria => $preguntas_cat): $num++; ?>
                    <div class="categoria-group">
                        <div class="categoria-header" onclick="togglearCategoria(<?= $num ?>)" id="header<?= $num ?>">
                            <span><?= htmlspecialchars($categoria) ?></span>
                            <span><?= count($preguntas_cat) ?> preguntas [+]</span>
                        </div>
                        
                        <div class="categoria-content" id="content<?= $num ?>">
                            <?php foreach ($preguntas_cat as $pregunta): ?>
                                <div class="pregunta-item <?= $pregunta['activa'] ? '' : 'inactiva' ?>">
                                    <div class="pregunta-texto"><?= htmlspecialchars($pregunta['texto']) ?></div>
                                    <div class="pregunta-meta">
                                        <span>ID: <?= $pregunta['id'] ?> | Tipo: <?= htmlspecialchars($pregunta['tipo_nombre']) ?></span>
                                        <div class="pregunta-actions">
                                            <a href="editar_pregunta.php?id=<?= $pregunta['id'] ?>" class="btn btn-primary btn-sm">
                                                <?= $es_super_admin ? 'Editar' : 'Usar como Base' ?>
                                            </a>
                                            <?php if ($es_super_admin): ?>
                                                <?php if ($pregunta['activa']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="pregunta_id" value="<?= $pregunta['id'] ?>">
                                                        <button type="submit" name="desactivar_pregunta" class="btn btn-warning btn-sm">Desactivar</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="pregunta_id" value="<?= $pregunta['id'] ?>">
                                                        <button type="submit" name="activar_pregunta" class="btn btn-success btn-sm">Activar</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Acciones rápidas -->
            <div class="section">
                <h3 class="section-title">Gestión de Preguntas</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="crear_pregunta.php" class="btn create-btn" style="padding: 12px; text-align: center; text-decoration: none; font-weight: 600;">
                        <i class="fa-solid fa-plus"></i> Crear Nueva Pregunta
                    </a>
                    
                    <a href="buscar_pregunta.php" class="btn" style="padding: 12px; text-align: center; text-decoration: none; font-weight: 600; background: linear-gradient(135deg, #007bff, #0056b3); color: white; box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);">
                        <i class="fa-solid fa-search"></i> Editar Pregunta
                    </a>
                    
                    <div style="border-top: 1px solid #e9ecef; margin: 0.5rem 0; padding-top: 1rem;">
                        <h4 style="color: #6c757d; font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <?= $es_super_admin ? 'Acciones de Super Admin:' : 'Acciones Disponibles:' ?>
                        </h4>
                        <p style="font-size: 0.85rem; color: #6c757d; line-height: 1.4;">
                            <?php if ($es_super_admin): ?>
                                • <strong>Editar:</strong> Modifica preguntas directamente en el banco<br>
                                • <strong>Activar/Desactivar:</strong> Controla disponibilidad de preguntas<br>
                                • <strong>Crear:</strong> Agrega nuevas preguntas al banco
                            <?php else: ?>
                                • <strong>Usar como Base:</strong> Crea nueva pregunta basada en existente<br>
                                • <strong>Crear:</strong> Agrega nuevas preguntas personalizadas<br>
                                • <strong>Nota:</strong> No puedes modificar preguntas existentes del banco
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglearCategoria(num) {
            const content = document.getElementById('content' + num);
            const header = document.getElementById('header' + num);
            
            // Cerrar todas las otras categorías
            const totalCategorias = <?= $num ?>;
            for(let i = 1; i <= totalCategorias; i++) {
                if(i !== num) {
                    const otherContent = document.getElementById('content' + i);
                    const otherHeader = document.getElementById('header' + i);
                    if(otherContent) {
                        otherContent.classList.remove('mostrar');
                        otherHeader.classList.remove('activo');
                        // Cambiar el indicador a [+]
                        const span = otherHeader.querySelector('span:last-child');
                        if(span) {
                            span.innerHTML = span.innerHTML.replace('[−]', '[+]');
                        }
                    }
                }
            }
            
            // Toggle la categoría actual
            if (content.classList.contains('mostrar')) {
                content.classList.remove('mostrar');
                header.classList.remove('activo');
                // Cambiar el indicador a [+]
                const span = header.querySelector('span:last-child');
                if(span) {
                    span.innerHTML = span.innerHTML.replace('[−]', '[+]');
                }
            } else {
                content.classList.add('mostrar');
                header.classList.add('activo');
                // Cambiar el indicador a [−]
                const span = header.querySelector('span:last-child');
                if(span) {
                    span.innerHTML = span.innerHTML.replace('[+]', '[−]');
                }
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
</body>
</html>
