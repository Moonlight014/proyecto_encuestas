<?php
session_start();

// Simular sesión para testing
$_SESSION['user_id'] = 1;
$_SESSION['nombre'] = 'Test User';
$_SESSION['rol'] = 'super_admin';

// Headers anti-caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Cargar helper de rutas
require_once '../config/path_helper.php';
$base_url = detectar_base_url();

echo "<h1>🧪 Test Simplificado de Ver Encuestas</h1>";
echo "<p><strong>Base URL:</strong> {$base_url}</p>";
echo "<p><strong>Host:</strong> {$_SERVER['HTTP_HOST']}</p>";

try {
    require_once '../config/conexion.php';
    $pdo = obtenerConexion();
    
    // Consulta simple
    $stmt = $pdo->query("SELECT id, titulo, descripcion, estado, fecha_creacion FROM encuestas ORDER BY fecha_creacion DESC LIMIT 5");
    $encuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>✅ Encuestas Encontradas:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Título</th><th>Descripción</th><th>Estado</th><th>Fecha</th></tr>";
    
    foreach ($encuestas as $encuesta) {
        echo "<tr>";
        echo "<td>{$encuesta['id']}</td>";
        echo "<td>{$encuesta['titulo']}</td>";
        echo "<td>" . substr($encuesta['descripcion'], 0, 50) . "...</td>";
        echo "<td>{$encuesta['estado']}</td>";
        echo "<td>{$encuesta['fecha_creacion']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<p>{$e->getMessage()}</p>";
}

echo "<h2>🔗 Enlaces de Navegación:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php'>Dashboard</a></li>";
echo "<li><a href='debug_ver_encuestas.php'>Debug Ver Encuestas</a></li>";
echo "<li><a href='../test_dual_environment.php'>Test Dual</a></li>";
echo "</ul>";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Ver Encuestas - DAS Hualpén</title>
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/styles.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin: 20px 0; }
        th, td { padding: 8px 12px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div style="background: #e3f2fd; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <h3>✅ Test Exitoso</h3>
        <p>Si ves este contenido, el problema no está en la conexión básica.</p>
        <p>El error puede estar en el navbar o en alguna función específica de ver_encuestas.php</p>
    </div>
</body>
</html>