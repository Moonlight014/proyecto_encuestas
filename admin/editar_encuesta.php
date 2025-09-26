<?php
session_start();
require_once '../config/conexion.php';
require_once '../config/path_helper.php';

$base_url = detectar_base_url();

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
$encuesta_id = $_GET['id'] ?? 0;

try {
    $pdo = obtenerConexion();
    
    // Obtener datos de la encuesta
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ?");
    $stmt->execute([$encuesta_id]);
    $encuesta = $stmt->fetch();
    
    if (!$encuesta) {
        header("Location: ver_encuestas.php");
        exit();
    }
    
    // Obtener departamentos
    $departamentos = $pdo->query("SELECT * FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetchAll();
    
    // Variables para mostrar mensajes después del redirect
    if (isset($_SESSION['mensaje_editar_encuesta'])) {
        $mensaje = $_SESSION['mensaje_editar_encuesta'];
        unset($_SESSION['mensaje_editar_encuesta']);
    }
    if (isset($_SESSION['error_editar_encuesta'])) {
        $error = $_SESSION['error_editar_encuesta'];
        unset($_SESSION['error_editar_encuesta']);
    }
    
    // Procesar formulario - Patrón PRG (Post-Redirect-Get)
    if ($_POST && isset($_POST['actualizar'])) {
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $departamento_id = $_POST['departamento_id'];
        
        // Fecha de inicio SIEMPRE se actualiza al momento actual cuando se edita
        $fecha_inicio_actual = date('Y-m-d H:i:s');
        
        // Normalizar fecha_fin desde input
        $fecha_fin = null;
        if (!empty($_POST['fecha_fin'])) {
            $ts_fin = strtotime($_POST['fecha_fin']);
            if ($ts_fin !== false) {
                $fecha_fin = date('Y-m-d H:i:s', $ts_fin);
            }
        }
        
        if (empty($titulo) || empty($descripcion)) {
            $_SESSION['error_editar_encuesta'] = "El título y la descripción son obligatorios.";
        } else {
            // Validación ESTRICTA: fecha_fin debe ser futura (no puede ser igual o anterior al momento actual)
            $momento_actual = date('Y-m-d H:i:s');
            if ($fecha_fin && $fecha_fin <= $momento_actual) {
                $_SESSION['error_editar_encuesta'] = "La fecha de cierre debe ser futura. No se puede establecer una fecha que ya pasó o es el momento actual.";
            } else {
                $stmt = $pdo->prepare("UPDATE encuestas SET titulo = ?, descripcion = ?, departamento_id = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
                
                if ($stmt->execute([$titulo, $descripcion, $departamento_id, $fecha_inicio_actual, $fecha_fin, $encuesta_id])) {
                    $_SESSION['mensaje_encuesta'] = "Encuesta actualizada correctamente.";
                    // Redirect a ver_encuestas.php después de actualización exitosa
                    header("Location: ver_encuestas.php");
                    exit();
                } else {
                    $_SESSION['error_editar_encuesta'] = "Error al actualizar la encuesta.";
                }
            }
        }
        
        // Redirect para evitar reenvío del formulario (solo en caso de error)
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $encuesta_id);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS del sistema -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/forms.css">
    <title>Editar Encuesta - DAS Hualpén</title>
</head>
<body class="form-page-body">
    <?php include '../includes/navbar_complete.php'; ?>
    
    <div class="main-content">
        <div class="form-page-container">
            <div class="welcome-section">
                <h2>Editar Encuesta</h2>
                <p>Modifica la información de la encuesta: <strong><?= htmlspecialchars($encuesta['titulo']) ?></strong></p>
            </div>
            
            <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="titulo" class="form-label">Título de la Encuesta *</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required 
                           value="<?= htmlspecialchars($encuesta['titulo']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion" class="form-label">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required><?= htmlspecialchars($encuesta['descripcion']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="departamento_id" class="form-label">Departamento Responsable *</label>
                    <select id="departamento_id" name="departamento_id" class="form-control" required>
                        <?php foreach ($departamentos as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($encuesta['departamento_id'] == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Fecha de Apertura</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y H:i') . ' (al guardar cambios)' ?>" readonly disabled>
                        <small style="color:#6c757d;">La encuesta estará disponible desde hoy cuando guardes los cambios.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin" class="form-label">Fecha de Cierre</label>
                        <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control"
                               min="<?= date('Y-m-d\TH:i', strtotime('+5 minutes')) ?>"
                               value="<?= $encuesta['fecha_fin'] ? date('Y-m-d\TH:i', strtotime($encuesta['fecha_fin'])) : '' ?>">
                        <small style="color:#6c757d;">Selecciona cuándo quieres que termine la encuesta. Debe ser una fecha futura.</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="actualizar" class="btn-primary">Actualizar Encuesta</button>
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
            
            // Fallback: ir a ver encuestas por defecto
            window.location.href = 'ver_encuestas.php';
        }

        // Validación ESTRICTA en tiempo real para fecha_fin en edición
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
            </div> <!-- /form-card -->
        </div> <!-- /container -->
    </div> <!-- /main-content -->
</body>
</html>
