<?php
// Protección de sesión - DEBE ser lo primero
require_once '../includes/session_guard.php';
header("Pragma: no-cache");
header("Expires: 0");

// Comentar temporalmente la verificación de login para testing
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

try {
    require_once '../config/conexion.php';
    require_once '../config/path_helper.php';
    require_once '../config/url_helper.php';
    
    // Usar la función helper para detección automática de rutas
    $base_url = detectar_base_url();
    
} catch (Exception $e) {
    die("Error cargando configuración: " . $e->getMessage());
}

$mensaje = '';
$error = '';
$es_super_admin = ($_SESSION['rol'] ?? 'admin_departamental') === 'super_admin';

// Verificar si hay mensajes en la sesión (incluyendo desde editar_encuesta.php)
if (isset($_SESSION['mensaje_encuesta'])) {
    $mensaje = $_SESSION['mensaje_encuesta'];
    unset($_SESSION['mensaje_encuesta']);
}
if (isset($_SESSION['error_encuesta'])) {
    $error = $_SESSION['error_encuesta'];
    unset($_SESSION['error_encuesta']);
}

try {
    $pdo = obtenerConexion();
    
    // Procesar exportación de respuestas
    if ($_POST && isset($_POST['exportar_excel'])) {
        $encuesta_id = $_POST['encuesta_id'];
        header("Location: ../export_excel.php?id=" . $encuesta_id);
        exit();
    }
    
    // Procesar cambios de estado
    if ($_POST && isset($_POST['cambiar_estado'])) {
        $encuesta_id = $_POST['encuesta_id'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Validar permisos: solo super_admin puede finalizar encuestas
        if ($nuevo_estado === 'finalizada' && !$es_super_admin) {
            $error = "Solo el Super Administrador puede finalizar encuestas.";
        } else {
            $stmt = $pdo->prepare("UPDATE encuestas SET estado = ? WHERE id = ?");
            if ($stmt->execute([$nuevo_estado, $encuesta_id])) {
                switch($nuevo_estado) {
                    case 'finalizada':
                        $mensaje = "Encuesta finalizada correctamente.";
                        break;
                    case 'pausada':
                        $mensaje = "Encuesta pausada correctamente.";
                        break;
                    case 'activa':
                        $mensaje = "Encuesta activada/reactivada correctamente.";
                        break;
                    default:
                        $mensaje = "Estado de la encuesta actualizado correctamente.";
                }
            } else {
                $error = "Error al actualizar el estado.";
            }
        }
    }
    
    // Obtener todas las encuestas con información del departamento y usuario
    $query = "
        SELECT e.*, d.nombre as departamento_nombre, u.nombre as creador_nombre 
        FROM encuestas e 
        LEFT JOIN departamentos d ON e.departamento_id = d.id 
        LEFT JOIN usuarios u ON e.creado_por = u.id 
        ORDER BY e.fecha_creacion DESC
    ";
    
    $encuestas = $pdo->query($query)->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Encuestas - DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/lists.css?v=<?= time() ?>">

</head>
<body>
    <?php include '../includes/navbar_complete.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h2 class="page-title">Encuestas del Sistema</h2>
                    <p>Administra las encuestas creadas, cambia su estado y gestiona enlaces públicos.</p>
                </div>
                <a href="crear_encuesta.php" class="action-btn btn-primary" style="background: #32CD32; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                    <i class="fa-solid fa-plus"></i> Nueva Encuesta
                </a>
            </div>
            
            <!-- Panel informativo sobre permisos -->
            <div style="background: #e7f3ff; border-left: 4px solid #0d47a1; padding: 1rem; margin: 1rem 0; border-radius: 4px; font-size: 0.9rem; color: #084298;">
                <strong><i class="fa-solid fa-lightbulb"></i> Permisos de Estado:</strong><br>
                <?php if ($es_super_admin): ?>
                    • Como Super Administrador puedes <strong>activar</strong>, <strong>pausar</strong>, <strong>reactivar</strong> y <strong>finalizar</strong> encuestas<br>
                    • La acción "Finalizar" es irreversible y solo tú puedes realizarla
                <?php else: ?>
                    • Como Administrador Departamental puedes <strong>activar</strong>, <strong>pausar</strong> y <strong>reactivar</strong> tus encuestas<br>
                    • Solo el Super Administrador puede <strong>finalizar</strong> encuestas definitivamente
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="encuestas-grid">
            <?php if (empty($encuestas)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <h3>No hay encuestas creadas</h3>
                    <p>Comienza creando tu primera encuesta para el DAS Hualpén.</p>
                    <a href="crear_encuesta.php" class="btn btn-primary">+ Crear Primera Encuesta</a>
                </div>
            <?php else: ?>
                <?php foreach ($encuestas as $encuesta): ?>
                    <div class="encuesta-card <?= $encuesta['estado'] ?>">
                        <div class="encuesta-header">
                            <div>
                                <h3 class="encuesta-title"><?= htmlspecialchars($encuesta['titulo']) ?></h3>
                                <div class="encuesta-meta">
                                    <strong>Departamento:</strong> <?= htmlspecialchars($encuesta['departamento_nombre']) ?> | 
                                    <strong>Creado por:</strong> <?= htmlspecialchars($encuesta['creador_nombre']) ?> | 
                                    <strong>Fecha Creación:</strong> <?= date('d/m/Y H:i', strtotime($encuesta['fecha_creacion'])) ?>
                                </div>
                            </div>
                            <span class="estado-badge estado-<?= $encuesta['estado'] ?>"><?= ucfirst($encuesta['estado']) ?></span>
                        </div>
                        
                        <div class="encuesta-descripcion">
                            <?= htmlspecialchars($encuesta['descripcion']) ?>
                        </div>
                        
                        <?php if ($encuesta['fecha_inicio'] || $encuesta['fecha_fin']): ?>
                            <div class="encuesta-meta">
                                <?php if ($encuesta['fecha_inicio']): ?>
                                    <strong>Inicio:</strong> <?= date('d/m/Y H:i', strtotime($encuesta['fecha_inicio'])) ?>
                                <?php endif; ?>
                                <?php if ($encuesta['fecha_fin']): ?>
                                    <?= $encuesta['fecha_inicio'] ? ' | ' : '' ?>
                                    <strong>Fin:</strong> <?= date('d/m/Y H:i', strtotime($encuesta['fecha_fin'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($encuesta['estado'] === 'activa'): ?>
                            <div class="enlace-publico">
                                <strong>Enlace público:</strong><br>
                                <?php 
                                $url_publica = generarUrlResponder($encuesta['enlace_publico']);
                                $encuesta_id_encoded = urlencode($encuesta['enlace_publico']);
                                ?>
                                <div class="enlace-container" style="margin: 10px 0;">
                                    <div class="enlace-row" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <a href="<?= $url_publica ?>" 
                                           target="_blank" 
                                           class="enlace-publico-btn btn btn-info btn-sm"
                                           onclick="copiarAlPortapapeles('<?= $url_publica ?>', this); return true;"
                                           style="text-decoration: none; flex: 1; min-width: 200px;">
                                            <i class="fa-solid fa-external-link-alt"></i> Abrir Encuesta
                                        </a>
                                        <button type="button" 
                                                class="btn btn-secondary btn-sm qr-btn" 
                                                onclick="toggleQR('qr-<?= $encuesta['id'] ?>', '<?= $url_publica ?>')"
                                                style="white-space: nowrap;">
                                            <i class="fa-solid fa-qrcode"></i> QR
                                        </button>
                                    </div>
                                    <div class="url-display" style="font-size: 0.9em; margin-top: 5px; word-break: break-all;">
                                        <span class="url-clickable" 
                                              onclick="copiarSoloUrl('<?= $url_publica ?>', this)"
                                              style="color: #007bff; cursor: pointer; text-decoration: underline; transition: all 0.3s ease;"
                                              onmouseover="this.style.color='#0056b3'; this.style.backgroundColor='#e6f3ff'; this.style.padding='2px 4px'; this.style.borderRadius='3px';"
                                              onmouseout="this.style.color='#007bff'; this.style.backgroundColor='transparent'; this.style.padding='0';"
                                              title="Haz clic para copiar solo el enlace (sin abrir)">
                                            <i class="fa-solid fa-copy"></i> <?= $url_publica ?>
                                        </span>
                                    </div>
                                    <div id="qr-<?= $encuesta['id'] ?>" class="qr-container" style="display: none; margin-top: 10px; text-align: center;">
                                        <div class="qr-code"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <br>
                        <div class="encuesta-actions">
                            <a href="editar_encuesta.php?id=<?= $encuesta['id'] ?>" class="btn btn-primary">Editar</a>
                            <a href="agregar_preguntas.php?id=<?= $encuesta['id'] ?>" class="btn btn-secondary">+ Preguntas</a>
                            <a href="vista_previa_admin.php?id=<?= $encuesta['id'] ?>" class="btn btn-primary" style="background: #17a2b8;"><i class="fa-solid fa-eye"></i> Vista Previa</a>
                            
                            <!-- Botón de exportar disponible para todas las encuestas -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="encuesta_id" value="<?= $encuesta['id'] ?>">
                                <button type="submit" name="exportar_excel" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Exportar Respuestas</button>
                            </form>
                            
                            <?php if ($encuesta['estado'] === 'borrador'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="encuesta_id" value="<?= $encuesta['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="activa">
                                    <button type="submit" name="cambiar_estado" class="btn btn-success">Activar</button>
                                </form>
                            <?php elseif ($encuesta['estado'] === 'activa'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="encuesta_id" value="<?= $encuesta['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="pausada">
                                    <button type="submit" name="cambiar_estado" class="btn btn-warning">Pausar</button>
                                </form>
                                <?php if ($es_super_admin): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="encuesta_id" value="<?= $encuesta['id'] ?>">
                                        <input type="hidden" name="nuevo_estado" value="finalizada">
                                        <button type="submit" name="cambiar_estado" class="btn btn-danger" 
                                                onclick="return confirm('¿Estás seguro de finalizar esta encuesta? Esta acción no se puede deshacer.')">
                                            <i class="fa-solid fa-flag-checkered"></i> Finalizar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($encuesta['estado'] === 'pausada'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="encuesta_id" value="<?= $encuesta['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="activa">
                                    <button type="submit" name="cambiar_estado" class="btn btn-success">Reactivar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Librerías necesarias para QR -->
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    
    <script>
        // Función para copiar al portapapeles (no bloquea la navegación)
        function copiarAlPortapapeles(url, elemento) {
            // Ejecutar la copia de forma asíncrona sin bloquear
            setTimeout(async () => {
                try {
                    await navigator.clipboard.writeText(url);
                    
                    // Feedback visual
                    const textoOriginal = elemento.innerHTML;
                    elemento.innerHTML = '<i class="fa-solid fa-check"></i> ¡Copiado!';
                    elemento.style.background = '#28a745';
                    
                    setTimeout(() => {
                        elemento.innerHTML = textoOriginal;
                        elemento.style.background = '';
                    }, 2000);
                    
                } catch (err) {
                    // Fallback para navegadores que no soportan clipboard API
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    
                    try {
                        document.execCommand('copy');
                        const textoOriginal = elemento.innerHTML;
                        elemento.innerHTML = '<i class="fa-solid fa-check"></i> ¡Copiado!';
                        elemento.style.background = '#28a745';
                        
                        setTimeout(() => {
                            elemento.innerHTML = textoOriginal;
                            elemento.style.background = '';
                        }, 2000);
                    } catch (err) {
                        console.error('No se pudo copiar:', err);
                    }
                    
                    document.body.removeChild(textArea);
                }
            }, 100); // Pequeño delay para permitir que la navegación ocurra primero
        }

        // Función para copiar SOLO el URL sin abrir la encuesta
        function copiarSoloUrl(url, elemento) {
            // Prevenir cualquier acción de navegación
            event.preventDefault();
            event.stopPropagation();
            
            // Ejecutar la copia inmediatamente
            navigator.clipboard.writeText(url).then(() => {
                // Feedback visual mejorado para URL
                const textoOriginal = elemento.innerHTML;
                elemento.innerHTML = '<i class="fa-solid fa-check"></i> ¡Enlace copiado!';
                elemento.style.color = '#28a745';
                elemento.style.backgroundColor = '#d4edda';
                elemento.style.padding = '4px 8px';
                elemento.style.borderRadius = '4px';
                elemento.style.fontWeight = 'bold';
                
                setTimeout(() => {
                    elemento.innerHTML = textoOriginal;
                    elemento.style.color = '#007bff';
                    elemento.style.backgroundColor = 'transparent';
                    elemento.style.padding = '0';
                    elemento.style.fontWeight = 'normal';
                }, 3000); // 3 segundos para mostrar confirmación
                
            }).catch(err => {
                // Fallback para navegadores que no soportan clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = url;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    
                    // Feedback visual
                    const textoOriginal = elemento.innerHTML;
                    elemento.innerHTML = '<i class="fa-solid fa-check"></i> ¡Enlace copiado!';
                    elemento.style.color = '#28a745';
                    elemento.style.backgroundColor = '#d4edda';
                    elemento.style.padding = '4px 8px';
                    elemento.style.borderRadius = '4px';
                    elemento.style.fontWeight = 'bold';
                    
                    setTimeout(() => {
                        elemento.innerHTML = textoOriginal;
                        elemento.style.color = '#007bff';
                        elemento.style.backgroundColor = 'transparent';
                        elemento.style.padding = '0';
                        elemento.style.fontWeight = 'normal';
                    }, 3000);
                    
                } catch (err) {
                    console.error('No se pudo copiar el enlace:', err);
                    // Mostrar mensaje de error
                    const textoOriginal = elemento.innerHTML;
                    elemento.innerHTML = '<i class="fa-solid fa-times"></i> Error al copiar';
                    elemento.style.color = '#dc3545';
                    
                    setTimeout(() => {
                        elemento.innerHTML = textoOriginal;
                        elemento.style.color = '#007bff';
                    }, 3000);
                }
                
                document.body.removeChild(textArea);
            });
        }

        // Función para mostrar/ocultar código QR
        function toggleQR(containerId, url) {
            const container = document.getElementById(containerId);
            const qrCodeDiv = container.querySelector('.qr-code');
            
            if (container.style.display === 'none') {
                // Mostrar QR
                container.style.display = 'block';
                
                // Generar QR si no existe
                if (!qrCodeDiv.innerHTML) {
                    qrCodeDiv.innerHTML = '<canvas id="qr-canvas-' + containerId + '"></canvas>';
                    
                    const qr = new QRious({
                        element: document.getElementById('qr-canvas-' + containerId),
                        value: url,
                        size: 200,
                        backgroundAlpha: 1,
                        foreground: '#0d47a1',
                        background: '#ffffff',
                        level: 'M'
                    });
                    
                    // Agregar botón para descargar QR
                    const downloadBtn = document.createElement('button');
                    downloadBtn.innerHTML = '<i class="fa-solid fa-download"></i> Descargar QR';
                    downloadBtn.className = 'btn btn-sm btn-primary';
                    downloadBtn.style.marginTop = '10px';
                    downloadBtn.onclick = function() {
                        const canvas = document.getElementById('qr-canvas-' + containerId);
                        const link = document.createElement('a');
                        link.download = 'qr-encuesta.png';
                        link.href = canvas.toDataURL();
                        link.click();
                    };
                    
                    qrCodeDiv.appendChild(downloadBtn);
                }
            } else {
                // Ocultar QR
                container.style.display = 'none';
            }
        }

        // Mejorar estilos dinámicamente
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar estilos para los botones QR
            const style = document.createElement('style');
            style.textContent = `
                .btn-info {
                    background: #17a2b8;
                    color: white;
                }
                .btn-info:hover {
                    background: #138496;
                    color: white;
                }
                .btn-sm {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.875rem;
                }
                .qr-container {
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    padding: 15px;
                    background: #ffffff;
                }
                .enlace-publico {
                    background: #f8f9fa !important;
                    border: 1px solid #dee2e6;
                    border-radius: 6px;
                }
                .url-clickable {
                    user-select: text;
                    -webkit-user-select: text;
                    -moz-user-select: text;
                    -ms-user-select: text;
                    word-break: break-all;
                    position: relative;
                }
                .url-clickable:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,123,255,0.2);
                }
                .url-clickable::before {
                    content: "Clic para copiar";
                    position: absolute;
                    top: -25px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #333;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    white-space: nowrap;
                    opacity: 0;
                    transition: opacity 0.3s;
                    pointer-events: none;
                    z-index: 1000;
                }
                .url-clickable:hover::before {
                    opacity: 1;
                }
                /* Estilos para iconos de Font Awesome */
                .fa-solid {
                    margin-right: 6px;
                }
                .url-clickable .fa-copy {
                    color: #007bff;
                    margin-right: 8px;
                }
                .fa-qrcode {
                    margin-right: 4px;
                }
                .fa-check {
                    margin-right: 6px;
                    animation: checkBounce 0.6s ease-in-out;
                }
                .fa-times {
                    margin-right: 6px;
                    animation: shake 0.5s ease-in-out;
                }
                @keyframes checkBounce {
                    0% { transform: scale(0.8); }
                    50% { transform: scale(1.2); }
                    100% { transform: scale(1); }
                }
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
            `;
            document.head.appendChild(style);
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
</body>
</html>
