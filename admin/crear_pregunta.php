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
$es_super_admin = ($_SESSION['rol'] ?? 'admin_departamental') === 'super_admin';

// Capturar parámetros de origen para determinar la URL de vuelta
$from = $_GET['from'] ?? '';
$encuesta_id = isset($_GET['encuesta_id']) ? (int)$_GET['encuesta_id'] : 0;

try {
    $pdo = obtenerConexion();
    
    // Variables para mostrar mensajes después del redirect
    if (isset($_SESSION['mensaje_crear_pregunta'])) {
        $mensaje = $_SESSION['mensaje_crear_pregunta'];
        unset($_SESSION['mensaje_crear_pregunta']);
    }
    if (isset($_SESSION['error_crear_pregunta'])) {
        $error = $_SESSION['error_crear_pregunta'];
        unset($_SESSION['error_crear_pregunta']);
    }
    
    // Procesar formulario de creación - Patrón PRG (Post-Redirect-Get)
    if ($_POST && isset($_POST['crear_pregunta'])) {
        $categoria_id = $_POST['categoria_id'];
        $tipo_pregunta_id = $_POST['tipo_pregunta_id'];
        $texto = $_POST['texto'];
        $departamento = $_POST['departamento'] ?: 'general';
        $obligatoria = isset($_POST['obligatoria']) ? 1 : 0;
        $activa = isset($_POST['activa']) ? 1 : 0;
        $created_by = $_SESSION['username'] ?? 'admin';
        
        // Procesar opciones según el tipo de pregunta
        $opciones = null;
        if (in_array($tipo_pregunta_id, [1, 2, 3, 4, 6, 10, 11, 12, 13, 14])) { // Tipos que requieren opciones o configuración
            $opciones = [];
            switch ($tipo_pregunta_id) {
                case 1: // texto_corto
                case 2: // texto_largo
                    if (isset($_POST['limite_caracteres']) && $_POST['limite_caracteres'] > 0) {
                        $limite = (int)$_POST['limite_caracteres'];
                        // Validar límites razonables
                        $max_limite = ($tipo_pregunta_id == 1) ? 500 : 5000; // texto_corto: 500, texto_largo: 5000
                        if ($limite > $max_limite) {
                            throw new Exception("El límite de caracteres no puede ser mayor a {$max_limite} para este tipo de pregunta.");
                        }
                        $opciones['limite_caracteres'] = $limite;
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
                    // Guarda la fecha de creación de la pregunta como límite máximo
                    $opciones = [
                        'fecha_maxima' => date('Y-m-d') // Fecha actual como límite máximo
                    ];
                    break;
            }
            $opciones = !empty($opciones) ? json_encode($opciones, JSON_UNESCAPED_UNICODE) : null;
        }
        
        // Obtener el siguiente orden
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM banco_preguntas WHERE categoria_id = ?");
        $stmt->execute([$categoria_id]);
        $orden = $stmt->fetchColumn();
        
        // Insertar nueva pregunta
        $stmt = $pdo->prepare("INSERT INTO banco_preguntas 
                               (categoria_id, tipo_pregunta_id, texto, opciones, orden, 
                                departamento, obligatoria, activa, created_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$categoria_id, $tipo_pregunta_id, $texto, $opciones, $orden,
                           $departamento, $obligatoria, $activa, $created_by])) {
            $nueva_id = $pdo->lastInsertId();
            $_SESSION['mensaje_crear_pregunta'] = "Pregunta creada correctamente con ID #$nueva_id.";
        } else {
            $_SESSION['error_crear_pregunta'] = "Error al crear la pregunta.";
        }
        
        // Redirect para evitar reenvío del formulario (Patrón PRG)
        $redirect_url = $_SERVER['PHP_SELF'];
        if ($from && $encuesta_id) {
            $redirect_url .= "?from=" . urlencode($from) . "&encuesta_id=" . $encuesta_id;
        }
        header("Location: " . $redirect_url);
        exit();
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
    <title>Crear Nueva Pregunta - DAS Hualpén</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .tipo-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 4px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #1565c0;
        }
        .preview-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .preview-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <h1>Crear Nueva Pregunta</h1>
                <small style="opacity: 0.8; font-size: 0.8rem;">
                    <?= $es_super_admin ? '<i class="fa-solid fa-crown"></i> Super Administrador' : '<i class="fa-solid fa-user"></i> Administrador Departamental' ?>
                </small>
            </div>
            <button onclick="history.back()" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Volver</button>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-success auto-hide-alert">
                <?= htmlspecialchars($mensaje) ?>
                <button onclick="history.back()" style="margin-left: 1rem; background: #0d47a1; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                    ← Continuar
                </button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger auto-hide-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="page-title"><i class="fa-solid fa-plus-circle"></i> Nueva Pregunta para el Banco</h2>
            <?php if ($from === 'agregar' && $encuesta_id): ?>
                <div style="background: #e7f3ff; border-left: 4px solid #0d47a1; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; font-size: 0.9rem; color: #084298;">
                    <strong><i class="fa-solid fa-lightbulb"></i> Creando desde Agregar Preguntas:</strong><br>
                    Esta pregunta se agregará al Banco y podrás seleccionarla inmediatamente para tu encuesta.
                </div>
            <?php endif; ?>
            <p style="color: #6c757d; margin-bottom: 2rem;">
                Complete el formulario para agregar una nueva pregunta personalizable al banco de preguntas.
            </p>
            
            <form method="POST" id="formCrearPregunta">
                <div class="form-group">
                    <label class="form-label">Categoría *</label>
                    <select name="categoria_id" class="form-control" required>
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>" 
                                    <?= ($_POST['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : '' ?>>
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
                                    <?= ($_POST['tipo_pregunta_id'] ?? '') == $tipo['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo['descripcion'] ?: $tipo['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="tipoInfo" class="tipo-info hidden"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Texto de la Pregunta *</label>
                    <textarea name="texto" class="form-control" rows="3" required 
                              placeholder="Escriba aquí el texto de la pregunta..."><?= htmlspecialchars($_POST['texto'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Departamento</label>
                    <select name="departamento" class="form-control">
                        <option value="general" <?= ($_POST['departamento'] ?? 'general') === 'general' ? 'selected' : '' ?>>
                            General
                        </option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= htmlspecialchars($departamento['codigo']) ?>" 
                                    <?= ($_POST['departamento'] ?? '') === $departamento['codigo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($departamento['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6c757d;">Departamento al que pertenece esta pregunta</small>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="obligatoria" class="form-check-input" 
                               <?= ($_POST['obligatoria'] ?? false) ? 'checked' : '' ?>>
                        <label class="form-label">Pregunta obligatoria</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="activa" class="form-check-input" checked>
                        <label class="form-label">Pregunta activa</label>
                    </div>
                </div>
                
                <!-- Configuraciones específicas por tipo -->
                <div id="opcionesContainer"></div>
                
                <!-- Vista previa -->
                <div id="previewContainer" class="preview-section hidden">
                    <div class="preview-title">Vista Previa:</div>
                    <div id="previewContent"></div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" name="crear_pregunta" class="btn btn-success"><i class="fa-solid fa-save"></i> Crear Pregunta</button>
                    <button type="button" onclick="limpiarFormulario()" class="btn btn-secondary"><i class="fa-solid fa-trash"></i> Limpiar</button>
                    <button type="button" onclick="history.back()" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Volver</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Información sobre tipos de pregunta
        const tiposInfo = {
            '1': 'Campo de texto corto para respuestas de una línea (nombres, códigos, etc.)',
            '2': 'Área de texto largo para comentarios y descripciones extensas',
            '3': 'Lista de opciones donde el usuario puede seleccionar solo una',
            '4': 'Lista de opciones donde el usuario puede seleccionar múltiples',
            '5': 'Escala de satisfacción de 1 a 5 (Muy malo, Malo, Regular, Bueno, Excelente)',
            '6': 'Escala numérica personalizable con rango específico',
            '7': 'Campo numérico simple para cantidades o valores',
            '8': 'Selector de fecha con calendario',
            '9': 'Campo de email con validación automática',
            '10': 'Lista de elementos para ordenar por prioridad o preferencia',
            '11': 'Tabla con filas y columnas para selecciones múltiples',
            '12': 'Tabla con filas y escala de evaluación (matriz Likert)',
            '13': 'Control deslizante para seleccionar valores en un rango',
            '14': 'Selector de fecha pasada (solo fechas anteriores a la creación)',
            '18': 'Escala NPS de 0 a 10 para medir lealtad',
            '19': 'Formulario de contacto (nombre, email, teléfono)'
        };
        
        function mostrarInfoTipo(tipoId) {
            const infoDiv = document.getElementById('tipoInfo');
            if (tiposInfo[tipoId]) {
                infoDiv.textContent = tiposInfo[tipoId];
                infoDiv.classList.remove('hidden');
            } else {
                infoDiv.classList.add('hidden');
            }
        }
        
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
            
            actualizarPreview();
        }
        
        function mostrarOpcionesTextoCorto() {
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Texto Corto</label>
                    <div class="opciones-container">
                        <div class="form-group">
                            <label class="form-label">Límite de Caracteres</label>
                            <input type="number" name="limite_caracteres" class="form-control" 
                                   placeholder="255" min="1" max="500" value="255" 
                                   style="width: 200px;">
                            <small style="color: #6c757d;">Máximo permitido: 500 caracteres. Por defecto: 255</small>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesTextoLargo() {
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Texto Largo</label>
                    <div class="opciones-container">
                        <div class="form-group">
                            <label class="form-label">Límite de Caracteres</label>
                            <input type="number" name="limite_caracteres" class="form-control" 
                                   placeholder="1000" min="1" max="5000" value="1000" 
                                   style="width: 200px;">
                            <small style="color: #6c757d;">Máximo permitido: 5000 caracteres. Por defecto: 1000</small>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesFechaPasada() {
            const fechaActual = new Date().toISOString().split('T')[0];
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Selector de Fecha Pasada</label>
                    <div class="opciones-container">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Información:</strong> Este selector solo permitirá a los usuarios seleccionar fechas anteriores o iguales a la fecha de creación de la pregunta (${fechaActual}).
                        </div>
                        <p><strong>Fecha máxima permitida:</strong> ${fechaActual}</p>
                        <small style="color: #6c757d;">
                            Los usuarios no podrán seleccionar fechas posteriores a esta fecha. 
                            Esta configuración se establece automáticamente al crear la pregunta.
                        </small>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesLista(tipoId) {
            const titulo = tipoId === '3' ? 'Opciones (Selección Única)' : 'Opciones (Selección Múltiple)';
            const limiteField = tipoId === '4' ? `
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Límite Máximo de Selecciones</label>
                    <input type="number" name="limite_maximo" class="form-control" 
                           placeholder="0 = Sin límite" min="0" max="2" style="width: 200px;"
                           oninput="validarLimiteInput(this)">
                    <small style="color: #6c757d;"> Máximo actual: 2 (número de opciones)</small>
                           <br>
                    <small style="color: #6c757d;">Deja en 0 para selecciones ilimitadas(Máximo recomendado: 5).</small>
                </div>
            ` : '';
            
            const html = `
                <div class="form-group">
                    <label class="form-label">${titulo} *</label>
                    <div class="opciones-container">
                        <div id="opcionesList">
                            <div class="opcion-item">
                                <input type="text" name="opciones_lista[opcion_1]" class="form-control" 
                                       placeholder="Primera opción" required>
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                            </div>
                            <div class="opcion-item">
                                <input type="text" name="opciones_lista[opcion_2]" class="form-control" 
                                       placeholder="Segunda opción" required>
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                            </div>
                        </div>
                        <button type="button" onclick="agregarOpcion()" class="btn btn-secondary">+ Agregar Opción</button>
                    </div>
                    ${limiteField}
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesEscala() {
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Escala</label>
                    <div class="opciones-container">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Valor Mínimo</label>
                                <input type="number" name="min" class="form-control" value="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Valor Máximo</label>
                                <input type="number" name="max" class="form-control" value="10" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Incremento</label>
                                <input type="number" name="step" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Etiqueta Mínimo</label>
                                <input type="text" name="etiqueta_min" class="form-control" placeholder="Ej: Muy malo">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Etiqueta Máximo</label>
                                <input type="text" name="etiqueta_max" class="form-control" placeholder="Ej: Excelente">
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
                        <div id="rankingList">
                            <div class="opcion-item">
                                <input type="text" name="opciones_ranking[]" class="form-control" 
                                       placeholder="Primer elemento" required>
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                            </div>
                            <div class="opcion-item">
                                <input type="text" name="opciones_ranking[]" class="form-control" 
                                       placeholder="Segundo elemento" required>
                                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                            </div>
                        </div>
                        <button type="button" onclick="agregarElementoRanking()" class="btn btn-secondary">+ Agregar Elemento</button>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesMatrizSeleccion() {
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Matriz</label>
                    <div class="opciones-container">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Filas (Preguntas) *</label>
                                <div id="filasList">
                                    <div class="opcion-item">
                                        <input type="text" name="filas[]" class="form-control" 
                                               placeholder="Primera pregunta" required>
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                                    </div>
                                </div>
                                <button type="button" onclick="agregarFila()" class="btn btn-secondary">+ Agregar Fila</button>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Columnas (Opciones) *</label>
                                <div id="columnasList">
                                    <div class="opcion-item">
                                        <input type="text" name="columnas[]" class="form-control" 
                                               placeholder="Primera opción" required>
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                                    </div>
                                </div>
                                <button type="button" onclick="agregarColumna()" class="btn btn-secondary">+ Agregar Columna</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        function mostrarOpcionesMatrizEscala() {
            const html = `
                <div class="form-group">
                    <label class="form-label">Configuración de Matriz con Escala</label>
                    <div class="opciones-container">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Filas (Aspectos a evaluar) *</label>
                                <div id="filasEscalaList">
                                    <div class="opcion-item">
                                        <input type="text" name="filas[]" class="form-control" 
                                               placeholder="Primer aspecto" required>
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                                    </div>
                                </div>
                                <button type="button" onclick="agregarFilaEscala()" class="btn btn-secondary">+ Agregar Fila</button>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Escala de Evaluación *</label>
                                <div id="escalaList">
                                    <div class="opcion-item">
                                        <input type="text" name="escala[]" class="form-control" 
                                               placeholder="Muy malo" required>
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                                    </div>
                                    <div class="opcion-item">
                                        <input type="text" name="escala[]" class="form-control" 
                                               placeholder="Excelente" required>
                                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                                    </div>
                                </div>
                                <button type="button" onclick="agregarEscala()" class="btn btn-secondary">+ Agregar Nivel</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('opcionesContainer').innerHTML = html;
        }
        
        // Funciones auxiliares
        function agregarOpcion() {
            const lista = document.getElementById('opcionesList');
            const index = 'opcion_' + Date.now();
            const html = `
                <div class="opcion-item">
                    <input type="text" name="opciones_lista[${index}]" class="form-control" 
                           placeholder="Nueva opción" required>
                    <button type="button" class="btn-remove" onclick="eliminarOpcion(this)">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
            validarLimiteOpciones();
        }
        
        function eliminarOpcion(btn) {
            btn.parentElement.remove();
            validarLimiteOpciones();
        }
        
        function validarLimiteOpciones() {
            const opciones = document.querySelectorAll('#opcionesList .opcion-item').length;
            const limiteInput = document.querySelector('input[name="limite_maximo"]');
            
            if (limiteInput) {
                limiteInput.max = opciones;
                if (parseInt(limiteInput.value) > opciones) {
                    limiteInput.value = opciones;
                }
                
                // Actualizar el texto de ayuda
                const helpText = limiteInput.nextElementSibling;
                if (helpText && helpText.tagName === 'SMALL') {
                    helpText.innerHTML = `Deja en 0 para selecciones ilimitadas (Máximo recomendado: 5). Máximo actual: ${opciones} (número de opciones)`;
                }
            }
        }
        
        function validarLimiteInput(input) {
            const opciones = document.querySelectorAll('#opcionesList .opcion-item').length;
            const valor = parseInt(input.value);
            
            if (valor > opciones) {
                input.value = opciones;
                alert(`El límite no puede ser mayor al número de opciones disponibles (${opciones})`);
            }
        }
        
        function agregarElementoRanking() {
            const lista = document.getElementById('rankingList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="opciones_ranking[]" class="form-control" 
                           placeholder="Nuevo elemento" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarFila() {
            const lista = document.getElementById('filasList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="filas[]" class="form-control" 
                           placeholder="Nueva pregunta" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarColumna() {
            const lista = document.getElementById('columnasList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="columnas[]" class="form-control" 
                           placeholder="Nueva opción" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarFilaEscala() {
            const lista = document.getElementById('filasEscalaList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="filas[]" class="form-control" 
                           placeholder="Nuevo aspecto" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function agregarEscala() {
            const lista = document.getElementById('escalaList');
            const html = `
                <div class="opcion-item">
                    <input type="text" name="escala[]" class="form-control" 
                           placeholder="Nuevo nivel" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">✕</button>
                </div>
            `;
            lista.insertAdjacentHTML('beforeend', html);
        }
        
        function actualizarPreview() {
            // Implementar vista previa básica
            const texto = document.querySelector('textarea[name="texto"]').value;
            const tipoId = document.getElementById('tipoPregunta').value;
            
            if (texto && tipoId) {
                const preview = document.getElementById('previewContainer');
                const content = document.getElementById('previewContent');
                
                let previewHtml = `<strong>${texto}</strong><br><br>`;
                
                switch (tipoId) {
                    case '1':
                        previewHtml += '<input type="text" style="width: 300px; padding: 5px;" placeholder="Respuesta...">';
                        break;
                    case '2':
                        previewHtml += '<textarea style="width: 300px; height: 80px; padding: 5px;" placeholder="Respuesta..."></textarea>';
                        break;
                    case '5':
                        previewHtml += '😞 1 ⚪ 2 ⚪ 3 ⚪ 4 ⚪ 5 😊';
                        break;
                    default:
                        previewHtml += '<em>Vista previa disponible después de configurar las opciones</em>';
                }
                
                content.innerHTML = previewHtml;
                preview.classList.remove('hidden');
            } else {
                document.getElementById('previewContainer').classList.add('hidden');
            }
        }
        
        function limpiarFormulario() {
            if (confirm('¿Está seguro de que desea limpiar el formulario?')) {
                document.getElementById('formCrearPregunta').reset();
                document.getElementById('opcionesContainer').innerHTML = '';
                document.getElementById('tipoInfo').classList.add('hidden');
                document.getElementById('previewContainer').classList.add('hidden');
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const tipoPregunta = document.getElementById('tipoPregunta');
            const textoInput = document.querySelector('textarea[name="texto"]');
            
            tipoPregunta.addEventListener('change', function() {
                mostrarInfoTipo(this.value);
                mostrarOpcionesPorTipo(this.value);
            });
            
            textoInput.addEventListener('input', actualizarPreview);
            
            // Si hay un tipo seleccionado inicialmente
            if (tipoPregunta.value) {
                mostrarInfoTipo(tipoPregunta.value);
                mostrarOpcionesPorTipo(tipoPregunta.value);
            }

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