<?php
session_start();
require_once 'config/conexion.php';

// Headers anti-caché para prevenir duplicación de procesos de login
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$error = '';
$mensaje = '';

// Verificar si hay mensaje de logout
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'sesion_cerrada') {
    $mensaje = 'Tu sesión ha sido cerrada correctamente.';
}

// Verificar si hay mensajes en la sesión (PRG)
if (isset($_SESSION['mensaje_login'])) {
    $mensaje = $_SESSION['mensaje_login'];
    unset($_SESSION['mensaje_login']);
}
if (isset($_SESSION['error_login'])) {
    $error = $_SESSION['error_login'];
    unset($_SESSION['error_login']);
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
                $_SESSION['error_login'] = "No se pudo actualizar el último acceso. Intente nuevamente.";
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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 480px;
            border: 1px solid #e9ecef;
        }
        .header-section {
            background: #ffffff;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 3px solid #198754;
        }
        .logo-institucional {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .institucion-title {
            color: #212529;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        .sistema-subtitle {
            color: #495057;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .departamento {
            color: #198754;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .login-form {
            padding: 2rem;
        }
        .form-title {
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
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
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #198754;
        }
        .btn-login {
            width: 100%;
            background-color: #198754;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-login:hover {
            background-color: #157347;
        }
        .footer-institucional {
            background-color: #f8f9fa;
            padding: 1.5rem 2rem;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .footer-text {
            color: #6c757d;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        .footer-municipio {
            color: #495057;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
        }
        .credenciales-demo {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #0c5aa0;
        }
    </style>
</head>
<body>
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
                <div class="alert-success auto-hide-alert">
                    <strong>✓</strong> <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-danger auto-hide-alert">
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
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
