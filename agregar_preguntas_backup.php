<?php
session_start();
require_once 'config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$error = '';
$encuesta_id = $_GET['id'] ?? 0;

try {
    $pdo = obtenerConexion();

    // Verificar que la encuesta existe
    $stmt = $pdo->prepare("SELECT * FROM encuestas WHERE id = ?");
    $stmt->execute([$encuesta_id]);
    $encuesta = $stmt->fetch();

    if (!$encuesta) {
        header("Location: ver_encuestas.php");
        exit();
    }

    // Procesar formulario de agregar preguntas
    if ($_POST && isset($_POST['agregar_preguntas'])) {
        $preguntas_seleccionadas = $_POST['preguntas'] ?? [];

        if (!empty($preguntas_seleccionadas)) {
            // Obtener el último orden
            $stmt = $pdo->prepare("SELECT MAX(orden) FROM encuesta_preguntas WHERE encuesta_id = ?");
            $stmt->execute([$encuesta_id]);
            $ultimo_orden = $stmt->fetchColumn() ?? 0;

            $agregadas = 0;
            foreach ($preguntas_seleccionadas as $pregunta_id) {
                // Verificar que no esté ya agregada
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM encuesta_preguntas WHERE encuesta_id = ? AND pregunta_id = ?");
                $stmt->execute([$encuesta_id, $pregunta_id]);

                if ($stmt->fetchColumn() == 0) {
                    $ultimo_orden++;
                    $stmt = $pdo->prepare("INSERT INTO encuesta_preguntas (encuesta_id, pregunta_id, orden, obligatoria_encuesta) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$encuesta_id, $pregunta_id, $ultimo_orden, 0])) {
                        $agregadas++;
                    }
                }
            }

            if ($agregadas > 0) {
                $mensaje = "$agregadas pregunta(s) agregada(s) a la encuesta.";
            } else {
                $error = "No se pudo agregar ninguna pregunta. Pueden estar ya incluidas.";
            }
        }
    }

    // Procesar eliminación de preguntas
    if ($_POST && isset($_POST['eliminar_pregunta'])) {
        $pregunta_id = $_POST['pregunta_id'];
        $stmt = $pdo->prepare("DELETE FROM encuesta_preguntas WHERE encuesta_id = ? AND pregunta_id = ?");
        if ($stmt->execute([$encuesta_id, $pregunta_id])) {
            // Reordenar preguntas
            $stmt = $pdo->prepare("SELECT id FROM encuesta_preguntas WHERE encuesta_id = ? ORDER BY orden");
            $stmt->execute([$encuesta_id]);
            $preguntas_restantes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $orden = 1;
            foreach ($preguntas_restantes as $ep_id) {
                $stmt = $pdo->prepare("UPDATE encuesta_preguntas SET orden = ? WHERE id = ?");
                $stmt->execute([$orden, $ep_id]);
                $orden++;
            }

            $mensaje = "Pregunta eliminada de la encuesta.";
        }
    }

    // Obtener preguntas del banco por categoría
    $query = "
        SELECT bp.*, c.nombre as categoria_nombre, tp.nombre as tipo_nombre
        FROM banco_preguntas bp
        LEFT JOIN categorias c ON bp.categoria_id = c.id
        LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
        WHERE bp.activa = 1
        ORDER BY c.nombre, bp.orden, bp.id
    ";
    $preguntas_banco = $pdo->query($query)->fetchAll();

    // Organizar por categoría
    $preguntas_por_categoria = [];
    foreach ($preguntas_banco as $pregunta) {
        $categoria = $pregunta['categoria_nombre'] ?? 'Sin categoría';
        if (!isset($preguntas_por_categoria[$categoria])) {
            $preguntas_por_categoria[$categoria] = [];
        }
        $preguntas_por_categoria[$categoria][] = $pregunta;
    }

    // Obtener preguntas ya agregadas a la encuesta
    $query = "
        SELECT bp.*, ep.orden, ep.obligatoria_encuesta, c.nombre as categoria_nombre, tp.nombre as tipo_nombre
        FROM encuesta_preguntas ep
        JOIN banco_preguntas bp ON ep.pregunta_id = bp.id
        LEFT JOIN categorias c ON bp.categoria_id = c.id
        LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
        WHERE ep.encuesta_id = ?
        ORDER BY ep.orden
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$encuesta_id]);
    $preguntas_encuesta = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
    <title>Agregar Preguntas - DAS Hualpén</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #0d47a1;
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-btn {
            background: #32CD32;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #228B22;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #32CD32;
        }
        .page-title {
            color: #0d47a1;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .encuesta-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            color: #0d47a1;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 0.5rem;
        }
        .categoria-group {
            margin-bottom: 2rem;
        }
        .categoria-title {
            background: #0d47a1;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .pregunta-item {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .pregunta-item:hover {
            border-color: #32CD32;
        }
        .pregunta-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .pregunta-checkbox input[type="checkbox"] {
            margin-top: 0.2rem;
        }
        .pregunta-texto {
            flex: 1;
            font-weight: 500;
            color: #212529;
        }
        .pregunta-meta {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .pregunta-encuesta {
            border: 1px solid #32CD32;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8fff8;
        }
        .pregunta-orden {
            background: #0d47a1;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 0.75rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background: #0d47a1;
            color: white;
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        .alert-success {
            background-color: #f0f8f0;
            border-left-color: #32CD32;
            color: #0f5132;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        .form-actions {
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Agregar Preguntas a la Encuesta</h1>
            <a href="ver_encuestas.php" class="back-btn">← Volver a Encuestas</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2 class="page-title">Gestionar Preguntas</h2>
            <div class="encuesta-info">
                <strong>Encuesta:</strong> <?= htmlspecialchars($encuesta['titulo']) ?><br>
                <strong>Estado:</strong> <?= ucfirst($encuesta['estado']) ?><br>
                <strong>Preguntas actuales:</strong> <?= count($preguntas_encuesta) ?>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Preguntas de la encuesta -->
            <div class="section">
                <h3 class="section-title">Preguntas en la Encuesta (<?= count($preguntas_encuesta) ?>)</h3>

                <?php if (empty($preguntas_encuesta)): ?>
                    <div class="empty-state">
                        <p>Esta encuesta no tiene preguntas agregadas.</p>
                        <p>Selecciona preguntas del banco para comenzar.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($preguntas_encuesta as $pregunta): ?>
                        <div class="pregunta-encuesta">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                <div style="display: flex; align-items: flex-start;">
                                    <span class="pregunta-orden"><?= $pregunta['orden'] ?></span>
                                    <div>
                                        <div class="pregunta-texto"><?= htmlspecialchars($pregunta['texto']) ?></div>
                                        <div class="pregunta-meta">
                                            <?= htmlspecialchars($pregunta['categoria_nombre']) ?> |
                                            <?= htmlspecialchars($pregunta['tipo_nombre']) ?>
                                        </div>
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta pregunta de la encuesta?')">
                                    <input type="hidden" name="pregunta_id" value="<?= $pregunta['id'] ?>">
                                    <button type="submit" name="eliminar_pregunta" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Banco de preguntas -->
            <div class="section">
                <h3 class="section-title">Banco de Preguntas</h3>

                <form method="POST">
                    <?php foreach ($preguntas_por_categoria as $categoria => $preguntas): ?>
                        <div class="categoria-group">
                            <div class="categoria-title"><?= htmlspecialchars($categoria) ?></div>

                            <?php foreach ($preguntas as $pregunta): ?>
                                <div class="pregunta-item">
                                    <div class="pregunta-checkbox">
                                        <input type="checkbox" name="preguntas[]" value="<?= $pregunta['id'] ?>" id="pregunta_<?= $pregunta['id'] ?>">
                                        <label for="pregunta_<?= $pregunta['id'] ?>">
                                            <div class="pregunta-texto"><?= htmlspecialchars($pregunta['texto']) ?></div>
                                            <div class="pregunta-meta">
                                                ID: <?= $pregunta['id'] ?> |
                                                Tipo: <?= htmlspecialchars($pregunta['tipo_nombre']) ?> |
                                                Departamento: <?= htmlspecialchars($pregunta['departamento']) ?>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="form-actions">
                        <button type="submit" name="agregar_preguntas" class="btn btn-primary">
                            Agregar Preguntas Seleccionadas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
