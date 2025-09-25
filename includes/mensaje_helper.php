<?php
/**
 * Helper para manejo de mensajes temporales
 * Evita usar parámetros URL que persisten al recargar
 */

/**
 * Establece un mensaje temporal en la sesión
 * @param string $mensaje El mensaje a mostrar
 * @param string $tipo Tipo de mensaje: success, error, warning, info
 */
function establecerMensajeTemporal($mensaje, $tipo = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['mensaje_temporal'] = $mensaje;
    $_SESSION['mensaje_tipo'] = $tipo;
    $_SESSION['mensaje_timestamp'] = time();
}

/**
 * Obtiene y limpia el mensaje temporal de la sesión
 * @return array|null Array con 'mensaje', 'tipo' y 'timestamp' o null si no hay mensaje
 */
function obtenerMensajeTemporal() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['mensaje_temporal'])) {
        return null;
    }
    
    $tiempo_actual = time();
    
    // Solo devolver el mensaje si no han pasado más de 30 segundos
    if (isset($_SESSION['mensaje_timestamp']) && 
        ($tiempo_actual - $_SESSION['mensaje_timestamp']) < 30) {
        
        $mensaje_data = [
            'mensaje' => $_SESSION['mensaje_temporal'],
            'tipo' => $_SESSION['mensaje_tipo'] ?? 'info',
            'timestamp' => $_SESSION['mensaje_timestamp']
        ];
        
        // Limpiar mensaje temporal después de obtenerlo
        unset($_SESSION['mensaje_temporal']);
        unset($_SESSION['mensaje_tipo']);
        unset($_SESSION['mensaje_timestamp']);
        
        return $mensaje_data;
    }
    
    // Si el mensaje es muy antiguo, limpiarlo
    unset($_SESSION['mensaje_temporal']);
    unset($_SESSION['mensaje_tipo']);
    unset($_SESSION['mensaje_timestamp']);
    
    return null;
}

/**
 * Establece un mensaje de logout exitoso
 */
function establecerMensajeLogout() {
    establecerMensajeTemporal('Tu sesión ha sido cerrada correctamente.', 'success');
}

/**
 * Establece un mensaje de error de sesión expirada
 */
function establecerMensajeSesionExpirada() {
    establecerMensajeTemporal('Tu sesión ha expirado. Por favor, ingresa nuevamente.', 'warning');
}

/**
 * Renderiza el HTML para mostrar un mensaje temporal
 * @param array $mensaje_data Datos del mensaje obtenidos con obtenerMensajeTemporal()
 * @return string HTML del mensaje o string vacío si no hay mensaje
 */
function renderizarMensajeTemporal($mensaje_data) {
    if (!$mensaje_data) {
        return '';
    }
    
    $alert_class = 'alert-success';
    $icon_class = 'fas fa-check-circle';
    
    switch ($mensaje_data['tipo']) {
        case 'success':
            $alert_class = 'alert-success';
            $icon_class = 'fas fa-check-circle';
            break;
        case 'error':
            $alert_class = 'alert-danger';
            $icon_class = 'fas fa-times-circle';
            break;
        case 'warning':
            $alert_class = 'alert-warning';
            $icon_class = 'fas fa-exclamation-triangle';
            break;
        case 'info':
            $alert_class = 'alert-info';
            $icon_class = 'fas fa-info-circle';
            break;
    }
    
    return sprintf(
        '<div class="%s auto-hide-alert" id="mensaje-temporal">
            <i class="%s"></i> %s
        </div>',
        htmlspecialchars($alert_class),
        htmlspecialchars($icon_class),
        htmlspecialchars($mensaje_data['mensaje'])
    );
}
?>