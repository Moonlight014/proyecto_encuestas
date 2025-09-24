<?php
session_start();
require_once '../config/conexion.php';

// Headers anti-caché para prevenir duplicación de procesos
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$error = '';
$pregunta = null;
$es_super_admin = ($_SESSION['rol'] ?? 'admin_departamental') === 'super_admin';

try {
    $pdo = obtenerConexion();
    
    // Obtener ID de la pregunta a editar
    $pregunta_id = $_GET['id'] ?? null;
    if (!$pregunta_id || !is_numeric($pregunta_id)) {
        throw new Exception('ID de pregunta inválido');
    }
    
    // Variables para mostrar mensajes después del redirect
    if (isset($_SESSION['mensaje_editar_pregunta'])) {
        $mensaje = $_SESSION['mensaje_editar_pregunta'];
        unset($_SESSION['mensaje_editar_pregunta']);
    }
    if (isset($_SESSION['error_editar_pregunta'])) {
        $error = $_SESSION['error_editar_pregunta'];
        unset($_SESSION['error_editar_pregunta']);
    }
    
    // Procesar formulario de edición - Patrón PRG (Post-Redirect-Get)
    if ($_POST && isset($_POST['actualizar_pregunta'])) {
        $categoria_id = $_POST['categoria_id'];
        $tipo_pregunta_id = $_POST['tipo_pregunta_id'];
        $texto = $_POST['texto'];
        $departamento = $_POST['departamento'];
        $obligatoria = isset($_POST['obligatoria']) ? 1 : 0;
        $activa = isset($_POST['activa']) ? 1 : 0;
        
        // Procesar opciones según el tipo de pregunta
        $opciones = null;
        if (in_array($tipo_pregunta_id, [1, 2, 3, 4, 10, 11, 12, 14])) { // Tipos que requieren opciones
            $opciones = [];
            switch ($tipo_pregunta_id) {
                case 1: // texto_corto
                    if (isset($_POST['limite_caracteres']) && $_POST['limite_caracteres'] > 0) {
                        $limite_caracteres = (int)$_POST['limite_caracteres'];
                        // Validar límite máximo para texto corto
                        if ($limite_caracteres > 500) {
                            throw new Exception("El límite de caracteres para texto corto no puede exceder 500 caracteres.");
                        }
                        $opciones['limite_caracteres'] = $limite_caracteres;
                    }
                    break;
                case 2: // texto_largo
                    if (isset($_POST['limite_caracteres']) && $_POST['limite_caracteres'] > 0) {
                        $limite_caracteres = (int)$_POST['limite_caracteres'];
                        // Validar límite máximo para texto largo
                        if ($limite_caracteres > 5000) {
                            throw new Exception("El límite de caracteres para texto largo no puede exceder 5000 caracteres.");
                        }
                        $opciones['limite_caracteres'] = $limite_caracteres;
                    }
                    break;
                case 3: // opcion_multiple
                case 4: // seleccion_multiple
                    if (!empty($_POST['opciones_lista'])) {
                        foreach ($_POST['opciones_lista'] as $key => $valor) {
                            if (!empty($valor)) {
                                $opciones[$key] = $valor;
                            }
                        }
                        
                        // Para selección múltiple, validar y agregar límite máximo
                        if ($tipo_pregunta_id == 4 && isset($_POST['limite_maximo']) && $_POST['limite_maximo'] > 0) {
                            $limite_maximo = (int)$_POST['limite_maximo'];
                            $num_opciones = count($opciones);
                            
                            // Validar que el límite no sea mayor al número de opciones
                            if ($limite_maximo > $num_opciones) {
                                throw new Exception("El límite máximo ({$limite_maximo}) no puede ser mayor al número de opciones ({$num_opciones}).");
                            }
                            
                            $opciones['_limite_maximo'] = $limite_maximo;
                        }
                    }
                    break;
                case 6: // escala_numerica
                case 13: // slider
                    $opciones = [
                        'min' => (int)($_POST['min'] ?? 1),
                        'max' => (int)($_POST['max'] ?? 10),
                        'step' => (int)($_POST['step'] ?? 1),
                        'etiqueta_min' => $_POST['etiqueta_min'] ?? '',
                        'etiqueta_max' => $_POST['etiqueta_max'] ?? ''
                    ];
                    break;
                case 10: // clasificacion
                    if (!empty($_POST['opciones_ranking'])) {
                        $opciones = array_filter($_POST['opciones_ranking']);
                    }
                    break;
                case 11: // matriz_seleccion
                    $opciones = [
                        'filas' => array_filter($_POST['filas'] ?? []),
                        'columnas' => array_filter($_POST['columnas'] ?? [])
                    ];
                    break;
                case 12: // matriz_escala
                    $opciones = [
                        'filas' => array_filter($_POST['filas'] ?? []),
                        'escala' => array_filter($_POST['escala'] ?? [])
                    ];
                    break;
                case 14: // selector_fecha_pasada
                    // Mantiene la fecha máxima existente o usa la fecha actual si no existe
                    $fecha_maxima_actual = null;
                    if (isset($pregunta['opciones'])) {
                        $opciones_actuales = json_decode($pregunta['opciones'], true);
                        $fecha_maxima_actual = $opciones_actuales['fecha_maxima'] ?? null;
                    }
                    $opciones = [
                        'fecha_maxima' => $fecha_maxima_actual ?? date('Y-m-d')
                    ];
                    break;
            }
            $opciones = !empty($opciones) ? json_encode($opciones, JSON_UNESCAPED_UNICODE) : null;
        }
        
        if ($es_super_admin) {
            // Solo super_admin puede modificar preguntas del banco directamente
            $stmt = $pdo->prepare("UPDATE banco_preguntas 
                                   SET categoria_id = ?, tipo_pregunta_id = ?, texto = ?, 
                                       opciones = ?, departamento = ?, obligatoria = ?, activa = ?,
                                       fecha_actualizacion = CURRENT_TIMESTAMP
                                   WHERE id = ?");
            
            if ($stmt->execute([$categoria_id, $tipo_pregunta_id, $texto, $opciones, 
                               $departamento, $obligatoria, $activa, $pregunta_id])) {
                $_SESSION['mensaje_editar_pregunta'] = "Pregunta actualizada correctamente.";
            } else {
                $_SESSION['error_editar_pregunta'] = "Error al actualizar la pregunta.";
            }
        } else {
            // Administradores departamentales crean una nueva pregunta basada en la existente
            // Obtener último orden de la categoría
            $stmt = $pdo->prepare("SELECT MAX(orden) FROM banco_preguntas WHERE categoria_id = ?");
            $stmt->execute([$categoria_id]);
            $ultimo_orden = $stmt->fetchColumn() ?? 0;
            
            $stmt = $pdo->prepare("INSERT INTO banco_preguntas 
                                   (categoria_id, tipo_pregunta_id, texto, opciones, orden, 
                                    departamento, obligatoria, activa, created_by) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$categoria_id, $tipo_pregunta_id, $texto, $opciones, 
                               $ultimo_orden + 1, $departamento, $obligatoria, $activa, 
                               $_SESSION['username'] ?? 'admin'])) {
                $nueva_pregunta_id = $pdo->lastInsertId();
                $_SESSION['mensaje_editar_pregunta'] = "Nueva pregunta creada correctamente (ID: $nueva_pregunta_id). La pregunta original se mantiene sin modificar.";
            } else {
                $_SESSION['error_editar_pregunta'] = "Error al crear la nueva pregunta.";
            }
        }
        
        // Redirect para evitar reenvío del formulario (Patrón PRG)
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $pregunta_id);
        exit();
    }
    
    // Cargar datos de la pregunta
    $stmt = $pdo->prepare("SELECT bp.*, c.nombre as categoria_nombre, tp.nombre as tipo_nombre 
                           FROM banco_preguntas bp
                           LEFT JOIN categorias c ON bp.categoria_id = c.id
                           LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
                           WHERE bp.id = ?");
    $stmt->execute([$pregunta_id]);
    $pregunta = $stmt->fetch();
    
    if (!$pregunta) {
        throw new Exception('Pregunta no encontrada');
    }
    
    // Cargar categorías, tipos y departamentos para los selectores
    $categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $tipos_pregunta = $pdo->query("SELECT * FROM tipos_pregunta WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $departamentos = $pdo->query("SELECT * FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetchAll();
    
} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pregunta - DAS Hualpén</title>
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #32CD32;
        }
        .page-title {
            color: #0d47a1;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
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
        .alert-info {
            background-color: #e7f3ff;
            border-left-color: #0d47a1;
            color: #084298;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.15s;
        }
        .form-control:focus {
            border-color: #32CD32;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(50, 205, 50, 0.25);
        }
        .form-check {
            margin-bottom: 0.5rem;
        }
        .form-check-input {
            margin-right: 0.5rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
        }
        .btn-primary {
            background: #0d47a1;
            color: white;
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .btn-success {
            background: #32CD32;
            color: white;
        }
        .btn-success:hover {
            background: #228B22;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .opciones-container {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 0.5rem;
            background: #f8f9fa;
        }
        .opcion-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .opcion-item input {
            flex: 1;
            margin-right: 0.5rem;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
        }
        .hidden {
            display: none;
        }
        .row {
            display: flex;
            gap: 1rem;
        }
        .col-6 {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1><?= $es_super_admin ? 'Editar Pregunta' : 'Crear Pregunta Basada en Existente' ?></h1>
                <small style="opacity: 0.8; font-size: 0.8rem;">
                    <?= $es_super_admin ? '👑 Super Administrador' : '👤 Administrador Departamental' ?>
                </small>
            </div>
            <button onclick="history.back()" class="back-btn">← Volver</button>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger auto-hide-alert"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($pregunta): ?>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success auto-hide-alert"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <?php if (!$es_super_admin): ?>
                <div class="alert alert-info">
                    <strong>Información:</strong> Como administrador departamental, al guardar cambios se creará una nueva pregunta basada en la existente. La pregunta original permanecerá sin modificar en el banco.
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2 class="page-title">
                    <?= $es_super_admin ? 'Editar' : 'Crear Basada en' ?> Pregunta #<?= $pregunta['id'] ?>
                </h2>
                
                <?php if ($from === 'agregar' && $encuesta_id): ?>
                    <div style="background: #e7f3ff; border-left: 4px solid #0d47a1; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; font-size: 0.9rem; color: #084298;">
                        <strong>💡 <?= $es_super_admin ? 'Editando desde Agregar Preguntas:' : 'Creando desde Agregar Preguntas:' ?></strong><br>
                        <?= $es_super_admin ? 'Los cambios se aplicarán directamente a la pregunta del banco.' : 'Se creará una nueva pregunta basada en esta. La original permanecerá sin cambios.' ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="formEditarPregunta">
                    <div class="form-group">
                        <label class="form-label">Categoría *</label>
                        <select name="categoria_id" class="form-control" required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" 
                                        <?= $categoria['id'] == $pregunta['categoria_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tipo de Pregunta *</label>
                        <select name="tipo_pregunta_id" id="tipoPregunta" class="form-control" required>
                            <option value="">Seleccionar tipo</option>
                            <?php foreach ($tipos_pregunta as $tipo): ?>
                                <option value="<?= $tipo['id'] ?>" 
                                        <?= $tipo['id'] == $pregunta['tipo_pregunta_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['descripcion'] ?: $tipo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Texto de la Pregunta *</label>
                        <textarea name="texto" class="form-control" rows="3" required placeholder="Escriba aquí el texto de la pregunta..."><?= htmlspecialchars($pregunta['texto']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Departamento</label>
                        <select name="departamento" class="form-control">
                            <option value="general" <?= ($pregunta['departamento'] ?? 'general') === 'general' ? 'selected' : '' ?>>
                                General
                            </option>
                            <?php foreach ($departamentos as $departamento): ?>
                                <option value="<?= htmlspecialchars($departamento['codigo']) ?>" 
                                        <?= ($pregunta['departamento'] ?? '') === $departamento['codigo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($departamento['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="obligatoria" class="form-check-input" 
                                   <?= $pregunta['obligatoria'] ? 'checked' : '' ?>>
                            <label class="form-label">Pregunta obligatoria</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="activa" class="form-check-input" 
                                   <?= $pregunta['activa'] ? 'checked' : '' ?>>
                            <label class="form-label">Pregunta activa</label>
                        </div>
                    </div>
                    
                    <!-- Configuraciones específicas por tipo -->
                    <div id="opcionesContainer"></div>
                    
                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="submit" name="actualizar_pregunta" class="btn btn-success">
                            💾 <?= $es_super_admin ? 'Actualizar Pregunta' : 'Crear Nueva Pregunta' ?>
                        </button>
                        <button type="button" onclick="history.back()" class="btn btn-secondary">Cancelar</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Datos actuales de opciones
        const opcionesActuales = <?= json_encode($pregunta['opciones'] ? json_decode($pregunta['opciones'], true) : null) ?>;
        
        function mostrarOpcionesPorTipo(tipoId) {
            const container = document.getElementById('opcionesContainer');
            container.innerHTML = '';
            
            switch (tipoId) {
                case '1': // texto_corto
                    mostrarOpcionesTextoCorto();
                    break;
                case '2': // texto_largo
                    mostrarOpcionesTextoLargo();
                    break;
                case '3': // opcion_multiple
                case '4': // seleccion_multiple
                    mostrarOpcionesLista(tipoId);
                    break;
                case '6': // escala_numerica
                case '13': // slider
                    mostrarOpcionesEscala();
                    break;
                case '10': // clasificacion
                    mostrarOpcionesRanking();
                    break;
                case '11': // matriz_seleccion
                    mostrarOpcionesMatrizSeleccion();
                    break;
                case '12': // matriz_escala
                    mostrarOpcionesMatrizEscala();
                    break;
                case '14': // selector_fecha_pasada
                    mostrarOpcionesFechaPasada();
                    break;
            }
        }
        
        function mostrarOpcionesTextoCorto() {
            const limite_actual = (opcionesActuales && opcionesActuales.limite_caracteres) ? opcionesActuales.limite_caracteres : 255;
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Texto Corto</label>
                    <div class="opciones-container">
                        <div class="form-group">
                            <label class="form-label">Límite de Caracteres</label>
                            <input type="number" name="limite_caracteres" class="form-control" 
                                   placeholder="255" min="1" max="500" value="${limite_actual}" 
                                   style="width: 200px;">
                            <small style="color: #6c757d;">Máximo permitido: 500 caracteres. Por defecto: 255</small>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesTextoLargo() {
            const limite_actual = (opcionesActuales && opcionesActuales.limite_caracteres) ? opcionesActuales.limite_caracteres : 1000;
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Texto Largo</label>
                    <div class="opciones-container">
                        <div class="form-group">
                            <label class="form-label">Límite de Caracteres</label>
                            <input type="number" name="limite_caracteres" class="form-control" 
                                   placeholder="1000" min="1" max="5000" value="${limite_actual}" 
                                   style="width: 200px;">
                            <small style="color: #6c757d;">Máximo permitido: 5000 caracteres. Por defecto: 1000</small>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesFechaPasada() {
            const fecha_maxima = (opcionesActuales && opcionesActuales.fecha_maxima) ? 
                                  opcionesActuales.fecha_maxima : 
                                  new Date().toISOString().split('T')[0];
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Selector de Fecha Pasada</label>
                    <div class="opciones-container">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Información:</strong> Este selector solo permitirá a los usuarios seleccionar fechas anteriores o iguales a la fecha máxima configurada.
                        </div>
                        <p><strong>Fecha máxima permitida:</strong> ${fecha_maxima}</p>
                        <small style="color: #6c757d;">
                            Los usuarios no podrán seleccionar fechas posteriores a esta fecha. 
                            Esta configuración se estableció al crear la pregunta y no se puede modificar.
                        </small>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesLista(tipoId) {
            const titulo = tipoId === '3' ? 'Opciones (Selección Única)' : 'Opciones (Selección Múltiple)';
            const limite_actual = (opcionesActuales && opcionesActuales._limite_maximo) ? opcionesActuales._limite_maximo : '';
            const limiteField = tipoId === '4' ? `
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Límite Máximo de Selecciones</label>
                    <input type="number" name="limite_maximo" class="form-control" 
                           placeholder="0 = Sin límite" min="0" max="10" style="width: 200px;"
                           value="${limite_actual}">
                           <br>
                    <small style="color: #6c757d;">Deja en 0 para selecciones ilimitadas. Máximo recomendado: 5</small>
                </div>
            ` : '';
            
            const html = `
                <div class="form-group">
                    <label class="form-label">${titulo} *</label>
                    <div class="opciones-container">
                        <div id="opcionesList"></div>
                        <button type="button" onclick="agregarOpcion()" class="btn btn-secondary">+ Agregar Opción</button>
                    </div>
                    ${limiteField}
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
            
            // Cargar opciones existentes (excluyendo _limite_maximo)
            if (opcionesActuales && typeof opcionesActuales === 'object') {
                Object.entries(opcionesActuales).forEach(([key, value]) => {
                    if (key !== '_limite_maximo') {
                        agregarOpcion(key, value);
                    }
                });
            }
        }
        
        function mostrarOpcionesEscala() {
            const opciones = opcionesActuales || {};
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Escala</label>
                    <div class="opciones-container">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Valor Mínimo</label>
                                <input type="number" name="min" class="form-control" value="${opciones.min || 1}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Valor Máximo</label>
                                <input type="number" name="max" class="form-control" value="${opciones.max || 10}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Incremento</label>
                                <input type="number" name="step" class="form-control" value="${opciones.step || 1}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Etiqueta Mínimo</label>
                                <input type="text" name="etiqueta_min" class="form-control" value="${opciones.etiqueta_min || ''}" placeholder="Opcional">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Etiqueta Máximo</label>
                                <input type="text" name="etiqueta_max" class="form-control" value="${opciones.etiqueta_max || ''}" placeholder="Opcional">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesRanking() {
            const html = `
                <div class="form-group">
                    <label class="form-label">Elementos para Clasificar *</label>
                    <div class="opciones-container">
                        <div id="rankingList"></div>
                        <button type="button" onclick="agregarElementoRanking()" class="btn btn-secondary">+ Agregar Elemento</button>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
            
            // Cargar elementos existentes
            if (opcionesActuales && Array.isArray(opcionesActuales)) {
                opcionesActuales.forEach((elemento, index) => {
                    agregarElementoRanking(elemento);
                });
            }
        }
        
        function mostrarOpcionesMatrizSeleccion() {
            const opciones = opcionesActuales || {};
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Matriz</label>
                    <div class="opciones-container">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Filas (Preguntas) *</label>
                                <div id="filasList"></div>
                                <button type="button" onclick="agregarFila()" class="btn btn-secondary">+ Agregar Fila</button>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Columnas (Opciones) *</label>
                                <div id="columnasList"></div>
                                <button type="button" onclick="agregarColumna()" class="btn btn-secondary">+ Agregar Columna</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
            
            // Cargar datos existentes
            if (opciones.filas) {
                opciones.filas.forEach(fila => agregarFila(fila));
            }
            if (opciones.columnas) {
                opciones.columnas.forEach(columna => agregarColumna(columna));
            }
        }
        
        function mostrarOpcionesMatrizEscala() {
            const opciones = opcionesActuales || {};
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Matriz con Escala</label>
                    <div class="opciones-container">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Filas (Aspectos a evaluar) *</label>
                                <div id="filasEscalaList"></div>
                                <button type="button" onclick="agregarFilaEscala()" class="btn btn-secondary">+ Agregar Fila</button>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Escala de Evaluación *</label>
                                <div id="escalaList"></div>
                                <button type="button" onclick="agregarEscala()" class="btn btn-secondary">+ Agregar Nivel</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
            
            // Cargar datos existentes
            if (opciones.filas) {
                opciones.filas.forEach(fila => agregarFilaEscala(fila));
            }
            if (opciones.escala) {
                opciones.escala.forEach(nivel => agregarEscala(nivel));
            }
        }
        
        // Funciones auxiliares
        function agregarOpcion(key = '', value = '') {
            const lista = document.getElementById('opcionesList');
            const index = key || 'opcion_' + Date.now();
            const html = `
                <div class="opcion-item">
                    <input type="hidden" name="opciones_lista[${index}]" value="">
                    <input type="text" name="opciones_lista[${index}]" class="form-control" 
                           value="${value}" placeholder="Texto de la opción" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarElementoRanking(valor = '') {
            const lista = document.getElementById('rankingList');
            const index = Date.now();
            const html = `
                <div class="opcion-item">
                    <input type="text" name="opciones_ranking[]" class="form-control" 
                           value="${valor}" placeholder="Elemento a clasificar" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarFila(valor = '') {
            const lista = document.getElementById('filasList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="filas[]" class="form-control" 
                           value="${valor}" placeholder="Pregunta de la fila" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarColumna(valor = '') {
            const lista = document.getElementById('columnasList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="columnas[]" class="form-control" 
                           value="${valor}" placeholder="Opción de la columna" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarFilaEscala(valor = '') {
            const lista = document.getElementById('filasEscalaList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="filas[]" class="form-control" 
                           value="${valor}" placeholder="Aspecto a evaluar" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarEscala(valor = '') {
            const lista = document.getElementById('escalaList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="escala[]" class="form-control" 
                           value="${valor}" placeholder="Nivel de la escala" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const tipoPregunta = document.getElementById('tipoPregunta');
            
            // Mostrar opciones iniciales si hay un tipo seleccionado
            if (tipoPregunta.value) {
                mostrarOpcionesPorTipo(tipoPregunta.value);
            }
            
            // Cambiar opciones cuando cambie el tipo
            tipoPregunta.addEventListener('change', function() {
                mostrarOpcionesPorTipo(this.value);
            });

            // Auto-ocultar mensajes de alerta después de 5 segundos
            const alerts = document.querySelectorAll('.auto-hide-alert');
            alerts.forEach(function(alert) {
                // Agregar animación de fade-out
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    
                    // Remover completamente después de la animación
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }, 3000); // 3 segundos
            });
        });
    </script>
</body>
</html>