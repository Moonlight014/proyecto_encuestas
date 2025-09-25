<?php
session_start();

// Simular sesión para debugging
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Test User';
$_SESSION['rol'] = 'super_admin';

echo "<h1>🔍 Diagnóstico de ver_encuestas.php</h1>";

try {
    echo "<h2>1. Verificando archivos requeridos:</h2>";
    
    if (file_exists('../config/conexion.php')) {
        echo "<p style='color: green;'>✅ ../config/conexion.php - EXISTE</p>";
        require_once '../config/conexion.php';
        echo "<p style='color: green;'>✅ conexion.php cargado correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ ../config/conexion.php - NO ENCONTRADO</p>";
    }
    
    if (file_exists('../config/url_helper.php')) {
        echo "<p style='color: green;'>✅ ../config/url_helper.php - EXISTE</p>";
        require_once '../config/url_helper.php';
        echo "<p style='color: green;'>✅ url_helper.php cargado correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ ../config/url_helper.php - NO ENCONTRADO</p>";
    }
    
    if (file_exists('../config/path_helper.php')) {
        echo "<p style='color: green;'>✅ ../config/path_helper.php - EXISTE</p>";
        require_once '../config/path_helper.php';
        echo "<p style='color: green;'>✅ path_helper.php cargado correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ ../config/path_helper.php - NO ENCONTRADO</p>";
    }

    echo "<h2>2. Test de detección de rutas:</h2>";
    $base_url = detectar_base_url();
    echo "<p><strong>Base URL detectada:</strong> {$base_url}</p>";
    echo "<p><strong>Host:</strong> {$_SERVER['HTTP_HOST']}</p>";
    echo "<p><strong>Script Name:</strong> {$_SERVER['SCRIPT_NAME']}</p>";
    echo "<p><strong>Request URI:</strong> {$_SERVER['REQUEST_URI']}</p>";

    echo "<h2>3. Test de conexión a base de datos:</h2>";
    $pdo = obtenerConexion();
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
    // Test simple de consulta
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM encuestas");
    $total = $stmt->fetchColumn();
    echo "<p style='color: green;'>✅ Consulta test exitosa - Total encuestas: {$total}</p>";
    
    echo "<h2>4. Test de archivos CSS:</h2>";
    $css_files = [
        'assets/css/styles.css',
        'assets/css/lists.css'
    ];
    
    foreach ($css_files as $file) {
        if (file_exists($file)) {
            echo "<p style='color: green;'>✅ {$file} - EXISTE</p>";
        } else {
            echo "<p style='color: red;'>❌ {$file} - NO ENCONTRADO</p>";
        }
    }
    
    echo "<h2>5. Test de navbar:</h2>";
    if (file_exists('../includes/navbar_complete.php')) {
        echo "<p style='color: green;'>✅ ../includes/navbar_complete.php - EXISTE</p>";
        echo "<p style='color: blue;'>ℹ️ Cargando navbar...</p>";
        include '../includes/navbar_complete.php';
        echo "<p style='color: green;'>✅ Navbar cargado sin errores</p>";
    } else {
        echo "<p style='color: red;'>❌ ../includes/navbar_complete.php - NO ENCONTRADO</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR ENCONTRADO:</h2>";
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Mensaje:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "</p>";
    
    echo "<h3>Stack Trace:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo $e->getTraceAsString();
    echo "</pre>";
}

echo "<h2>6. Enlaces de navegación:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php'>Dashboard</a></li>";
echo "<li><a href='ver_encuestas.php'>Ver Encuestas (problema)</a></li>";
echo "<li><a href='../test_dual_environment.php'>Test Dual Environment</a></li>";
echo "</ul>";

phpinfo(INFO_GENERAL);
?>