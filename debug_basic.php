<?php
// Test básico sin includes externos
session_start();

// Simular sesión
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Test User';
$_SESSION['rol'] = 'super_admin';

echo "<h1>🧪 Test de Carga PHP Básico</h1>";
echo "<p><strong>Fecha/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Servidor:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Sesión User ID:</strong> " . $_SESSION['user_id'] . "</p>";

// Verificar archivos
$files_to_check = [
    'assets/css/styles.css',
    'assets/css/dashboard.css', 
    'includes/navbar_complete.php',
    'webicon.png'
];

echo "<h2>📁 Verificación de Archivos:</h2>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - EXISTE</p>";
    } else {
        echo "<p style='color: red;'>❌ $file - NO ENCONTRADO</p>";
    }
}

// Test de conexión a base de datos
echo "<h2>🗄️ Test de Base de Datos:</h2>";
try {
    if (file_exists('../config/conexion.php')) {
        echo "<p style='color: green;'>✅ Archivo conexion.php encontrado</p>";
        require_once '../config/conexion.php';
        $pdo = obtenerConexion();
        echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    } else {
        echo "<p style='color: red;'>❌ Archivo conexion.php no encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de base de datos: " . $e->getMessage() . "</p>";
}

echo "<h2>🌐 URLs de Test:</h2>";
echo "<ul>";
echo "<li><a href='test_static.html'>test_static.html (HTML estático)</a></li>";
echo "<li><a href='dashboard_test_simple.php'>dashboard_test_simple.php (PHP simplificado)</a></li>";
echo "<li><a href='admin/dashboard.php'>admin/dashboard.php (Original)</a></li>";
echo "</ul>";

phpinfo(INFO_GENERAL);
?>