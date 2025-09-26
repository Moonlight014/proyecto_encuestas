<?php
session_start();
require_once 'config/conexion.php';
require_once 'config/path_helper.php';

$base_url = detectar_base_url();

// Headers anti-caché para prevenir duplicación de procesos de login
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$error = '';
$mensaje = '';
$mensaje_tipo = '';

// Verificar si hay mensajes temporales en la sesión
if (isset($_SESSION['mensaje_temporal'])) {
    $tiempo_actual = time();
    
    // Solo mostrar el mensaje si no han pasado más de 30 segundos (evita mensajes antiguos)
    if (isset($_SESSION['mensaje_timestamp']) && 
        ($tiempo_actual - $_SESSION['mensaje_timestamp']) < 30) {
        
        $mensaje = $_SESSION['mensaje_temporal'];
        $mensaje_tipo = $_SESSION['mensaje_tipo'] ?? 'info';
    }
    
    // Limpiar mensaje temporal después de mostrarlo
    unset($_SESSION['mensaje_temporal']);
    unset($_SESSION['mensaje_tipo']);
    unset($_SESSION['mensaje_timestamp']);
}

// Verificar si hay mensajes en la sesión (PRG)
if (isset($_SESSION['mensaje_login'])) {
    $mensaje = $_SESSION['mensaje_login'];
    $mensaje_tipo = 'success';
    unset($_SESSION['mensaje_login']);
}
if (isset($_SESSION['error_login'])) {
    $error = $_SESSION['error_login'];
    unset($_SESSION['error_login']);
}

// Verificar si hay error de sesión expirada
if (isset($_GET['error']) && $_GET['error'] === 'sesion_expirada') {
    $error = 'Tu sesión ha expirado. Por favor, ingresa nuevamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    try {
        $pdo = obtenerConexion();

        $email = trim($_POST['email']);
        $password_input = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, username, nombre, apellido, email, password_hash, rol, activo FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password_input, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            
            $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            if (!$stmt->execute([$user['id']])) {
                $_SESSION['error_login'] = "No se pudo actualizar el Ãºltimo acceso. Intente nuevamente.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                header("Location: admin/dashboard.php");
                exit();
            }
        } else {
            $_SESSION['error_login'] = "Credenciales incorrectas o cuenta inactiva.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch(PDOException $e) {
        error_log("PDOException in login.php: " . $e->getMessage());
        $_SESSION['error_login'] = "Error de conexión al sistema.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Encuestas - DAS Hualpén</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/auth.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="header-section">
            <div class="logo-institucional">DAS</div>
            <h1 class="institucion-title">Dirección de Salud</h1>
            <p class="sistema-subtitle">Sistema de Gestión de Encuestas</p>
            <p class="departamento">Municipalidad de Hualpén</p>
        </div>

        <div class="login-form">
            <h2 class="form-title">Acceso al Sistema</h2>

            <div class="credenciales-demo">
                <strong>Credenciales:</strong><br>
                admin@dashualpen.cl / admin123
            </div>

            <?php if ($mensaje): ?>
                <?php 
                $alert_class = 'alert-success';
                $icon_class = 'fas fa-check-circle';
                
                if ($mensaje_tipo === 'success') {
                    $alert_class = 'alert-success';
                    $icon_class = 'fas fa-check-circle';
                } else if ($mensaje_tipo === 'info') {
                    $alert_class = 'alert-info';
                    $icon_class = 'fas fa-info-circle';
                } else if ($mensaje_tipo === 'warning') {
                    $alert_class = 'alert-warning'; 
                    $icon_class = 'fas fa-exclamation-triangle';
                }
                ?>
                <div class="<?= $alert_class ?> auto-hide-alert" id="mensaje-temporal">
                    <i class="<?= $icon_class ?>"></i> <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-danger auto-hide-alert">
                    <i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn-login">Iniciar Sesión</button>
            </form>
        </div>

        <div class="footer-institucional">
            <p class="footer-text">Sistema interno de gestión</p>
            <p class="footer-municipio">Municipalidad de Hualpén - Dirección de Salud</p>
        </div>
    </div>

    <script>
        // Auto-ocultar mensajes de alerta despues de 3 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.auto-hide-alert');
            alerts.forEach(function(alert) {
                // Agregar animacion de fade-out
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    
                    // Remover completamente despues de la animacion
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 3000); // 3 segundos exactos, modificables
            });
            
            // Para mensajes temporales, limpiar URL si contiene parámetros antiguos
            if (window.location.search.includes('mensaje=') || window.location.search.includes('error=')) {
                // Solo si el mensaje temporal ya se está mostrando
                const mensajeTemporal = document.getElementById('mensaje-temporal');
                if (mensajeTemporal) {
                    // Cambiar URL sin recargar la página
                    const urlSinParametros = window.location.protocol + "//" + 
                                           window.location.host + 
                                           window.location.pathname;
                    window.history.replaceState({}, document.title, urlSinParametros);
                }
            }
        });
    </script>
</body>
</html>
