<?php
/**
 * Protección de sesión para páginas administrativas
 * Incluir este archivo al inicio de cada página que requiera autenticación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers anti-caché para prevenir acceso a páginas protegidas desde caché
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si hay sesión activa
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Limpiar cualquier resto de sesión
    $_SESSION = array();
    
    // Destruir cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
    
    // Log de intento de acceso no autorizado
    error_log("Intento de acceso no autorizado - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . " - Página: " . ($_SERVER['REQUEST_URI'] ?? 'desconocida') . " - Timestamp: " . date('Y-m-d H:i:s'));
    
    // Redirigir al login
    header("Location: " . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../index.php' : 'index.php') . "?error=sesion_expirada");
    exit();
}

// Verificar que la sesión no haya expirado (opcional: agregar timeout)
$session_timeout = 3600; // 1 hora en segundos
if (isset($_SESSION['ultimo_acceso'])) {
    if (time() - $_SESSION['ultimo_acceso'] > $session_timeout) {
        // Sesión expirada
        $_SESSION = array();
        session_destroy();
        
        error_log("Sesión expirada por timeout - Usuario: " . ($_SESSION['username'] ?? 'desconocido') . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . " - Timestamp: " . date('Y-m-d H:i:s'));
        
        header("Location: " . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../index.php' : 'index.php') . "?error=sesion_expirada");
        exit();
    }
}

// Actualizar último acceso
$_SESSION['ultimo_acceso'] = time();

// Regenerar ID de sesión periódicamente para seguridad (cada 15 minutos)
if (!isset($_SESSION['regeneracion_sesion']) || (time() - $_SESSION['regeneracion_sesion']) > 900) {
    session_regenerate_id(true);
    $_SESSION['regeneracion_sesion'] = time();
}
?>

<script>
// JavaScript adicional para prevenir acceso por historial después del logout
(function() {
    // Detectar si venimos de una página de logout
    if (document.referrer && document.referrer.includes('logout.php')) {
        window.location.replace('<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../index.php' : 'index.php' ?>?mensaje=sesion_cerrada');
        return;
    }
    
    // Prevenir navegación hacia atrás después de logout
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Página cargada desde caché - verificar si la sesión sigue activa
            fetch('<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>includes/check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.active) {
                        window.location.replace('<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../index.php' : 'index.php' ?>?error=sesion_expirada');
                    }
                })
                .catch(() => {
                    // En caso de error, redirigir por seguridad
                    window.location.replace('<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../index.php' : 'index.php' ?>?error=sesion_expirada');
                });
        }
    });
    
    // Limpiar datos sensibles antes de salir de la página
    window.addEventListener('beforeunload', function() {
        // Limpiar formularios y datos temporales
        document.querySelectorAll('input[type="password"]').forEach(input => input.value = '');
        document.querySelectorAll('.sensitive-data').forEach(el => el.textContent = '');
    });
})();
</script>