<?php
/**
 * Verificador de sesión para AJAX
 * Retorna JSON indicando si la sesión está activa
 */

// Headers para AJAX
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Iniciar sesión
session_start();

// Verificar estado de la sesión
$session_active = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Retornar respuesta JSON
echo json_encode([
    'active' => $session_active,
    'user_id' => $session_active ? $_SESSION['user_id'] : null,
    'timestamp' => time()
]);
exit();
?>