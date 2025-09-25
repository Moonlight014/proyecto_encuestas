<?php
/**
 * Logout seguro del sistema
 * Limpia completamente la sesión y redirige al login
 */

// Iniciar la sesión para poder destruirla luego
session_start();

// Headers de seguridad adicionales
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Limpiar todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, eliminar también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Antes de destruir la sesión, crear una nueva para el mensaje temporal
session_destroy();

// Iniciar nueva sesión para mensaje temporal
session_start();
session_regenerate_id(true);

// Establecer mensaje temporal de logout
$_SESSION['mensaje_temporal'] = 'Tu sesión ha sido cerrada correctamente.';
$_SESSION['mensaje_tipo'] = 'success';
$_SESSION['mensaje_timestamp'] = time();

// Limpiar caché del navegador agresivamente
header("Cache-Control: no-cache, no-store, must-revalidate, post-check=0, pre-check=0"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT"); // Proxies

// Prevenir acceso directo a páginas después del logout (solo navegadores compatibles)
if (isset($_SERVER['HTTP_SEC_FETCH_SITE'])) {
    header("Clear-Site-Data: \"cache\", \"cookies\", \"storage\"");
}

// Log de seguridad (opcional, para auditoría)
error_log("Usuario deslogueado - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . " - Timestamp: " . date('Y-m-d H:i:s'));

// Página con JavaScript para limpiar historial y redirigir
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando Sesión...</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            text-align: center; 
            padding: 2rem; 
            background: #f8f9fa; 
        }
        .logout-message { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            max-width: 400px; 
            margin: 2rem auto; 
        }
        .spinner { 
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #0d47a1; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            animation: spin 1s linear infinite; 
            margin: 1rem auto; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="logout-message">
        <div class="spinner"></div>
        <h2>Cerrando sesión...</h2>
        <p>Tu sesión se está cerrando de forma segura.</p>
        <p><small>Serás redirigido automáticamente al login.</small></p>
    </div>

    <script>
        // Limpiar todo el historial del navegador
        (function() {
            // Prevenir navegación hacia atrás
            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
                window.location.replace('index.php');
            });
            
            // Limpiar localStorage y sessionStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.clear();
                sessionStorage.clear();
            }
            
            // Limpiar cookies adicionales del lado del cliente
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            
            // Forzar limpieza de caché
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    names.forEach(function(name) {
                        caches.delete(name);
                    });
                });
            }
            
            // Redirigir después de 2 segundos
            setTimeout(function() {
                window.location.replace('index.php');
            }, 2000);
            
            // Prevenir que esta página se guarde en caché
            window.addEventListener('beforeunload', function() {
                window.location.replace('index.php');
            });
            
            // Si el usuario intenta recargar esta página, redirigir inmediatamente
            if (performance.navigation.type === 1) {
                window.location.replace('index.php');
            }
        })();
    </script>
</body>
</html>
<?php
exit();
?>