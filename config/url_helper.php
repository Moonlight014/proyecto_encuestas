<?php
/**
 * Helper para generar URLs dinámicas
 * Detecta automáticamente si el proyecto está en localhost:8000 o localhost/php/proyecto_encuestas
 */

/**
 * Obtiene la URL base del proyecto
 * @return string URL base completa
 */
function obtenerUrlBase() {
    // Verificar si estamos en entorno web
    if (!isset($_SERVER['HTTP_HOST'])) {
        // Si no estamos en web, devolver URL por defecto para XAMPP
        return 'http://localhost/php/proyecto_encuestas/public';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $port = $_SERVER['SERVER_PORT'] ?? 80;
    
    // Construir la URL base
    $base_url = $protocol . '://' . $host;
    
    // Agregar puerto solo si no es el estándar
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $base_url .= ':' . $port;
    }
    
    // Detectar si estamos en el servidor de desarrollo (puerto 8000) o en XAMPP
    if ($port == 8000) {
        // Servidor de desarrollo - el responder.php está en la raíz
        return $base_url;
    } else {
        // XAMPP - incluir la ruta completa del proyecto
        return $base_url . '/php/proyecto_encuestas/public';
    }
}

/**
 * Genera URL completa para responder.php
 * @param string $enlace_publico ID del enlace público
 * @return string URL completa
 */
function generarUrlResponder($enlace_publico) {
    return obtenerUrlBase() . '/responder.php?id=' . urlencode($enlace_publico);
}

/**
 * Genera ruta relativa para responder.php desde el directorio admin
 * @param string $enlace_publico ID del enlace público
 * @return string Ruta relativa
 */
function generarRutaRelativaResponder($enlace_publico) {
    // Desde admin/, la ruta relativa a public/responder.php es ../public/responder.php
    return '../public/responder.php?id=' . urlencode($enlace_publico);
}
?>