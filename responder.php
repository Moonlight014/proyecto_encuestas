<?php
/**
 * Responder.php para servidor de desarrollo (puerto 8000)
 * Redirecciona al responder.php principal en public/
 */

// Obtener el ID de la encuesta
$enlace_publico = $_GET['id'] ?? '';

if (empty($enlace_publico)) {
    // Si no hay ID, mostrar error
    http_response_code(400);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error - Enlace inválido</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 2rem; }
            .error { color: #dc3545; }
        </style>
    </head>
    <body>
        <h1 class='error'>Error: Enlace de encuesta no válido</h1>
        <p>El enlace que está intentando acceder no es válido.</p>
    </body>
    </html>";
    exit();
}

// Redirigir al responder.php principal
$redirect_url = "public/responder.php?id=" . urlencode($enlace_publico);
header("Location: " . $redirect_url);
exit();
?>