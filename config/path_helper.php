<?php
/**
 * Helper para detección automática de rutas base
 * Funciona tanto con localhost:8002 como localhost/php/proyecto_encuestas
 */

function detectar_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    // Detectar el contexto del servidor
    if (strpos($host, ':') !== false && !strpos($host, ':80') && !strpos($host, ':443')) {
        // Servidor con puerto específico (ej: localhost:8002)
        return $protocol . $host;
    } else {
        // Servidor estándar (ej: localhost/php/proyecto_encuestas)
        $path_parts = explode('/', trim($script_name, '/'));
        
        if (in_array('php', $path_parts) && in_array('proyecto_encuestas', $path_parts)) {
            return $protocol . $host . '/php/proyecto_encuestas';
        } else {
            return $protocol . $host;
        }
    }
}

function get_css_base_url() {
    return detectar_base_url() . '/assets/css';
}

function get_asset_url($path) {
    return detectar_base_url() . '/' . ltrim($path, '/');
}

// Variables globales para compatibilidad
if (!isset($base_url)) {
    $base_url = detectar_base_url();
}
?>