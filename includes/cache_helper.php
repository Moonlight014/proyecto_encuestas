<?php
/**
 * Cache Helper - Sistema centralizado de limpieza de caché
 * Previene la duplicación de procesos importantes
 */

/**
 * Aplicar headers anti-caché para prevenir duplicación de procesos
 */
function aplicarHeadersAntiCache() {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

/**
 * Limpiar datos del formulario después de un proceso exitoso (Patrón PRG)
 * @param string $mensaje Mensaje de éxito para mostrar
 * @param string $clave_sesion Clave de sesión para el mensaje
 * @param string $redirect_url URL de redirección
 */
function limpiarCacheFormulario($mensaje, $clave_sesion = 'mensaje_cache', $redirect_url = null) {
    $_SESSION[$clave_sesion] = $mensaje;
    
    if ($redirect_url === null) {
        $redirect_url = $_SERVER['PHP_SELF'];
        // Conservar parámetros GET importantes si existen
        if (!empty($_GET)) {
            $params = [];
            $params_importantes = ['id', 'encuesta_id', 'from'];
            foreach ($params_importantes as $param) {
                if (isset($_GET[$param])) {
                    $params[$param] = $_GET[$param];
                }
            }
            if (!empty($params)) {
                $redirect_url .= '?' . http_build_query($params);
            }
        }
    }
    
    header("Location: " . $redirect_url);
    exit();
}

/**
 * Obtener y limpiar mensaje de caché de la sesión
 * @param string $clave_sesion Clave de sesión para el mensaje
 * @return string Mensaje de caché o cadena vacía
 */
function obtenerMensajeCache($clave_sesion = 'mensaje_cache') {
    if (isset($_SESSION[$clave_sesion])) {
        $mensaje = $_SESSION[$clave_sesion];
        unset($_SESSION[$clave_sesion]);
        return $mensaje;
    }
    return '';
}

/**
 * Obtener y limpiar error de caché de la sesión
 * @param string $clave_sesion Clave de sesión para el error
 * @return string Error de caché o cadena vacía
 */
function obtenerErrorCache($clave_sesion = 'error_cache') {
    if (isset($_SESSION[$clave_sesion])) {
        $error = $_SESSION[$clave_sesion];
        unset($_SESSION[$clave_sesion]);
        return $error;
    }
    return '';
}

/**
 * Configurar mensaje de error en caché para mostrar después del redirect
 * @param string $error Mensaje de error
 * @param string $clave_sesion Clave de sesión para el error
 * @param string $redirect_url URL de redirección
 */
function configurarErrorCache($error, $clave_sesion = 'error_cache', $redirect_url = null) {
    $_SESSION[$clave_sesion] = $error;
    
    if ($redirect_url === null) {
        $redirect_url = $_SERVER['PHP_SELF'];
        // Conservar parámetros GET importantes si existen
        if (!empty($_GET)) {
            $params = [];
            $params_importantes = ['id', 'encuesta_id', 'from'];
            foreach ($params_importantes as $param) {
                if (isset($_GET[$param])) {
                    $params[$param] = $_GET[$param];
                }
            }
            if (!empty($params)) {
                $redirect_url .= '?' . http_build_query($params);
            }
        }
    }
    
    header("Location: " . $redirect_url);
    exit();
}

/**
 * Generar token único para formularios (prevenir reenvío)
 * @return string Token único
 */
function generarTokenFormulario() {
    return hash('sha256', uniqid('form_', true) . time() . $_SERVER['REMOTE_ADDR']);
}

/**
 * Validar token de formulario
 * @param string $token Token recibido del formulario
 * @param int $tiempo_valido Tiempo en segundos que el token es válido (default: 3600 = 1 hora)
 * @return bool True si el token es válido
 */
function validarTokenFormulario($token, $tiempo_valido = 3600) {
    // Para formularios públicos, implementar validación más simple
    // basada en tiempo y estructura del token
    if (empty($token) || strlen($token) < 32) {
        return false;
    }
    
    // Verificar que el token tenga el formato esperado
    return preg_match('/^[a-f0-9]{64}$/', $token);
}

/**
 * Prevenir duplicación basada en IP y tiempo para formularios públicos
 * @param PDO $pdo Conexión a la base de datos
 * @param string $tabla Tabla para verificar duplicados
 * @param array $condiciones Condiciones adicionales para la consulta
 * @param int $minutos_espera Minutos de espera entre envíos (default: 5)
 * @return bool True si se puede procesar, False si hay duplicación
 */
function prevenirDuplicacionPublica($pdo, $tabla, $condiciones = [], $minutos_espera = 5) {
    $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] . date('Y-m-d'));
    
    $sql = "SELECT COUNT(*) FROM {$tabla} WHERE ip_hash = ? AND fecha_completada > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    $params = [$ip_hash, $minutos_espera];
    
    // Agregar condiciones adicionales
    foreach ($condiciones as $campo => $valor) {
        $sql .= " AND {$campo} = ?";
        $params[] = $valor;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchColumn() == 0;
}

/**
 * Limpiar caché completo del navegador mediante JavaScript
 * @return string Script JavaScript para limpiar caché del navegador
 */
function generarScriptLimpiezaCache() {
    return "
    <script>
    // Limpiar caché del navegador después de procesos importantes
    if ('caches' in window) {
        caches.keys().then(function(names) {
            names.forEach(function(name) {
                caches.delete(name);
            });
        });
    }
    
    // Prevenir navegación hacia atrás después de procesos críticos
    if (window.history && window.history.pushState) {
        window.history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function() {
            window.history.pushState(null, null, window.location.href);
        });
    }
    </script>";
}