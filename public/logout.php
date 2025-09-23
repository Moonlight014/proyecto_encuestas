<?php
session_start();

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

// Destruir la sesión
session_destroy();

// Limpiar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Prevenir acceso directo a páginas después del logout
header("Clear-Site-Data: \"cache\", \"cookies\", \"storage\"");

// Redirigir al login con mensaje de confirmación
header("Location: ../index.php?mensaje=sesion_cerrada");
exit();
?>
