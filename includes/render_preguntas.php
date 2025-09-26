<?php
/**
 * Funciones para renderizar diferentes tipos de pregunta
 * Sistema de Encuestas DAS Hualpén
 */

/**
 * Renderiza el campo de respuesta apropiado según el tipo de pregunta
 * @param array $pregunta - Datos de la pregunta incluyendo tipo
 * @param mixed $valor_actual - Valor actual de la respuesta (para edición)
 * @return string HTML del campo de respuesta
 */
function renderizarCampoPregunta($pregunta, $valor_actual = null) {
    $pregunta_id = $pregunta['id'];
    $tipo = $pregunta['nombre_tipo'] ?? $pregunta['tipo_nombre'] ?? 'texto_corto';
    $opciones = $pregunta['opciones'] ? json_decode($pregunta['opciones'], true) : [];
    $name = "respuestas[{$pregunta_id}]";
    $required = ($pregunta['obligatoria'] || $pregunta['obligatoria_encuesta']) ? 'required' : '';
    
    switch ($tipo) {
        case 'texto_corto':
            $limite_caracteres = isset($opciones['limite_caracteres']) ? $opciones['limite_caracteres'] : 255;
            return renderizarTextoCorto($name, $valor_actual, $required, $limite_caracteres);
            
        case 'texto_largo':
            $limite_caracteres = isset($opciones['limite_caracteres']) ? $opciones['limite_caracteres'] : 1000;
            return renderizarTextoLargo($name, $valor_actual, $required, $limite_caracteres);
            
        case 'opcion_multiple':
            return renderizarOpcionMultiple($name, $opciones, $valor_actual, $required);
            
        case 'seleccion_multiple':
            return renderizarSeleccionMultiple($name, $opciones, $valor_actual, $required);
            
        case 'escala_likert':
            return renderizarEscalaLikert($name, $valor_actual, $required);
            
        case 'escala_numerica':
            return renderizarEscalaNumerica($name, $opciones, $valor_actual, $required);
            
        case 'numero':
            return renderizarNumero($name, $valor_actual, $required);
            
        case 'fecha':
            return renderizarFecha($name, $valor_actual, $required);
            
        case 'selector_fecha_pasada':
            $fecha_maxima = isset($opciones['fecha_maxima']) ? $opciones['fecha_maxima'] : date('Y-m-d');
            return renderizarSelectorFechaPasada($name, $valor_actual, $required, $fecha_maxima);
            
        case 'email':
            return renderizarEmail($name, $valor_actual, $required);
            
        case 'clasificacion':
            return renderizarClasificacion($name, $opciones, $valor_actual, $required);
            
        case 'matriz_seleccion':
            return renderizarMatrizSeleccion($name, $opciones, $valor_actual, $required);
            
        case 'matriz_escala':
            return renderizarMatrizEscala($name, $opciones, $valor_actual, $required);
            
        case 'slider':
            return renderizarSlider($name, $opciones, $valor_actual, $required);
            
        case 'calificacion_estrellas':
            return renderizarCalificacionEstrellas($name, $valor_actual, $required);
            
        case 'net_promoter_score':
            return renderizarNetPromoterScore($name, $valor_actual, $required);
            
        case 'contacto':
            return renderizarContacto($name, $valor_actual, $required);
            
        default:
            $limite_caracteres = isset($opciones['limite_caracteres']) ? $opciones['limite_caracteres'] : 255;
            return renderizarTextoCorto($name, $valor_actual, $required, $limite_caracteres);
    }
}

/**
 * Campo de texto corto
 */
function renderizarTextoCorto($name, $valor = null, $required = '', $limite_caracteres = 255) {
    $valor = htmlspecialchars($valor ?? '');
    $limite = is_numeric($limite_caracteres) && $limite_caracteres > 0 ? (int)$limite_caracteres : 255;
    return "
        <input type='text' 
               name='{$name}' 
               value='{$valor}' 
               class='form-control' 
               maxlength='{$limite}'
               {$required}
               placeholder='Escriba su respuesta aquí...'>
        <small class='form-text text-muted'>Máximo {$limite} caracteres</small>
    ";
}

/**
 * Área de texto largo
 */
function renderizarTextoLargo($name, $valor = null, $required = '', $limite_caracteres = 1000) {
    $valor = htmlspecialchars($valor ?? '');
    $limite = is_numeric($limite_caracteres) && $limite_caracteres > 0 ? (int)$limite_caracteres : 1000;
    return "
        <textarea name='{$name}' 
                  class='form-control' 
                  rows='4' 
                  maxlength='{$limite}'
                  {$required}
                  placeholder='Escriba su respuesta detallada aquí...'>{$valor}</textarea>
        <small class='form-text text-muted'>Máximo {$limite} caracteres</small>
    ";
}

/**
 * Opción múltiple (radio buttons)
 */
function renderizarOpcionMultiple($name, $opciones, $valor = null, $required = '') {
    if (empty($opciones)) {
        $opciones = [
            'muy_malo' => 'Muy malo',
            'malo' => 'Malo', 
            'regular' => 'Regular',
            'bueno' => 'Bueno',
            'muy_bueno' => 'Muy bueno'
        ];
    }
    
    $html = "<div class='opciones-radio'>";
    
    foreach ($opciones as $key => $label) {
        $checked = ($valor == $key) ? 'checked' : '';
        $html .= "
            <div class='form-check'>
                <input type='radio' 
                       name='{$name}' 
                       value='{$key}' 
                       id='{$name}_{$key}' 
                       class='form-check-input'
                       {$checked}
                       {$required}>
                <label for='{$name}_{$key}' class='form-check-label'>
                    {$label}
                </label>
            </div>
        ";
    }
    
    $html .= "</div>";
    return $html;
}

/**
 * Selección múltiple (checkboxes) con límite opcional
 */
function renderizarSeleccionMultiple($name, $opciones, $valor = null, $required = '') {
    if (empty($opciones)) {
        $opciones = [
            'opcion1' => 'Opción 1',
            'opcion2' => 'Opción 2',
            'opcion3' => 'Opción 3'
        ];
    }
    
    // Extraer límite máximo si existe
    $limite_maximo = isset($opciones['_limite_maximo']) ? (int)$opciones['_limite_maximo'] : 0;
    unset($opciones['_limite_maximo']); // Remover del array de opciones
    
    $valores_seleccionados = is_array($valor) ? $valor : [];
    $unique_id = uniqid();
    
    $html = "<div class='opciones-checkbox' data-name='{$name}' data-limite='{$limite_maximo}'>";
    
    // Agregar mensaje de límite si existe
    if ($limite_maximo > 0) {
        $html .= "<div class='limite-info' style='margin-bottom: 0.5rem; font-size: 0.9rem; color: #6c757d;'>
                    <span id='contador_{$unique_id}'>0</span> de {$limite_maximo} opciones seleccionadas
                  </div>";
    }
    
    foreach ($opciones as $key => $label) {
        $checked = in_array($key, $valores_seleccionados) ? 'checked' : '';
        $html .= "
            <div class='form-check'>
                <input type='checkbox' 
                       name='{$name}[]' 
                       value='{$key}' 
                       id='{$name}_{$key}_{$unique_id}' 
                       class='form-check-input checkbox-limitado'
                       data-group='checkbox_{$unique_id}'
                       data-limite='{$limite_maximo}'
                       {$checked}>
                <label for='{$name}_{$key}_{$unique_id}' class='form-check-label'>
                    {$label}
                </label>
            </div>
        ";
    }
    
    $html .= "</div>";
    
    // Agregar JavaScript para manejo del límite
    if ($limite_maximo > 0) {
        $html .= "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('[data-group=\"checkbox_{$unique_id}\"]');
            const contador = document.getElementById('contador_{$unique_id}');
            
            function actualizarContador() {
                const seleccionados = Array.from(checkboxes).filter(cb => cb.checked).length;
                contador.textContent = seleccionados;
                
                // Deshabilitar checkboxes no seleccionados si se alcanzó el límite
                if (seleccionados >= {$limite_maximo}) {
                    checkboxes.forEach(cb => {
                        if (!cb.checked) {
                            cb.disabled = true;
                        }
                    });
                } else {
                    checkboxes.forEach(cb => {
                        cb.disabled = false;
                    });
                }
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    actualizarContador();
                    
                    // Mostrar mensaje si intenta seleccionar más del límite
                    const seleccionados = Array.from(checkboxes).filter(cb => cb.checked).length;
                    if (seleccionados > {$limite_maximo}) {
                        alert('Solo puedes seleccionar un máximo de {$limite_maximo} opciones.');
                        this.checked = false;
                        actualizarContador();
                    }
                });
            });
            
            // Inicializar contador
            actualizarContador();
        });
        </script>
        ";
    }
    
    return $html;
}

/**
 * Escala Likert (1-5)
 */
function renderizarEscalaLikert($name, $valor = null, $required = '') {
    $etiquetas = [
        1 => 'Muy en desacuerdo',
        2 => 'En desacuerdo', 
        3 => 'Neutral',
        4 => 'De acuerdo',
        5 => 'Muy de acuerdo'
    ];
    
    $html = "<div class='escala-likert'>";
    $html .= "<div class='escala-valores'>";
    
    for ($i = 1; $i <= 5; $i++) {
        $checked = ($valor == $i) ? 'checked' : '';
        $html .= "
            <div class='escala-item'>
                <input type='radio' 
                       name='{$name}' 
                       value='{$i}' 
                       id='{$name}_{$i}' 
                       class='escala-radio'
                       {$checked}
                       {$required}>
                <label for='{$name}_{$i}' class='escala-label'>
                    <span class='escala-texto'>{$etiquetas[$i]}</span>
                </label>
            </div>
        ";
    }
    
    $html .= "</div>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Escala numérica personalizable
 */
function renderizarEscalaNumerica($name, $opciones, $valor = null, $required = '') {
    $min = $opciones['min'] ?? 1;
    $max = $opciones['max'] ?? 5;  // Cambiado de 10 a 5
    $step = $opciones['step'] ?? 1;
    $etiqueta_min = $opciones['etiqueta_min'] ?? 'Mínimo';
    $etiqueta_max = $opciones['etiqueta_max'] ?? 'Máximo';
    
    $html = "<div class='escala-numerica'>";
    $html .= "<div class='escala-header'>";
    $html .= "<span class='escala-etiqueta-min'>{$etiqueta_min}</span>";
    $html .= "<span class='escala-etiqueta-max'>{$etiqueta_max}</span>";
    $html .= "</div>";
    
    $html .= "<div class='escala-valores'>";
    
    for ($i = $min; $i <= $max; $i += $step) {
        $checked = ($valor == $i) ? 'checked' : '';
        $html .= "
            <div class='escala-item'>
                <input type='radio' 
                       name='{$name}' 
                       value='{$i}' 
                       id='{$name}_{$i}' 
                       class='escala-radio'
                       {$checked}
                       {$required}>
                <label for='{$name}_{$i}' class='escala-numero-label'>{$i}</label>
            </div>
        ";
    }
    
    $html .= "</div>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Campo numérico simple con botones de incremento/decremento
 */
function renderizarNumero($name, $valor = null, $required = '') {
    $valor = htmlspecialchars($valor ?? '');
    $id = 'numero_' . uniqid();
    return "
        <div class='numero-input-container'>
            <input type='number' 
                   id='{$id}'
                   name='{$name}' 
                   value='{$valor}' 
                   class='form-control numero-field' 
                   min='0'
                   step='1'
                   {$required}
                   placeholder='Ingrese un número...'
                   onkeypress='return soloNumeros(event)'
                   oninput='validarNumero(this)'>
            <div class='numero-buttons'>
                <button type='button' class='numero-btn numero-up' onclick='incrementarNumero(\"{$id}\")'>
                    <span>▲</span>
                </button>
                <button type='button' class='numero-btn numero-down' onclick='decrementarNumero(\"{$id}\")'>
                    <span>▼</span>
                </button>
            </div>
        </div>
        <style>
            .numero-input-container {
                position: relative;
                display: inline-block;
                width: 100%;
            }
            .numero-input-container .numero-field {
                padding-right: 35px;
            }
            .numero-buttons {
                position: absolute;
                right: 2px;
                top: 50%;
                transform: translateY(-50%);
                display: flex;
                flex-direction: column;
                height: calc(100% - 4px);
            }
            .numero-btn {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 2px;
                padding: 0;
                margin: 0;
                width: 24px;
                height: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 10px;
                line-height: 1;
                color: #495057;
                transition: all 0.15s ease;
            }
            .numero-btn:hover {
                background: #e9ecef;
                color: #212529;
            }
            .numero-btn:active {
                background: #dee2e6;
            }
            .numero-up {
                border-bottom: none;
                border-radius: 2px 2px 0 0;
            }
            .numero-down {
                border-top: none;
                border-radius: 0 0 2px 2px;
            }
        </style>
        <script>
            function incrementarNumero(id) {
                const input = document.getElementById(id);
                const currentValue = parseInt(input.value) || 0;
                const min = parseInt(input.min) || 0;
                const step = parseInt(input.step) || 1;
                input.value = currentValue + step;
                validarNumero(input);
            }
            
            function decrementarNumero(id) {
                const input = document.getElementById(id);
                const currentValue = parseInt(input.value) || 0;
                const min = parseInt(input.min) || 0;
                const step = parseInt(input.step) || 1;
                const newValue = currentValue - step;
                input.value = Math.max(newValue, min);
                validarNumero(input);
            }
        </script>
    ";
}

/**
 * Selector de fecha
 */
function renderizarFecha($name, $valor = null, $required = '') {
    $valor = htmlspecialchars($valor ?? '');
    return "
        <input type='date' 
               name='{$name}' 
               value='{$valor}' 
               class='form-control' 
               {$required}>
    ";
}

/**
 * Selector de fecha pasada (solo permite fechas anteriores a la fecha de creación)
 */
function renderizarSelectorFechaPasada($name, $valor = null, $required = '', $fecha_maxima = null) {
    $valor = htmlspecialchars($valor ?? '');
    $fecha_maxima = $fecha_maxima ?? date('Y-m-d');
    
    return "
        <input type='date' 
               name='{$name}' 
               value='{$valor}' 
               class='form-control' 
               max='{$fecha_maxima}'
               {$required}>
        <small class='form-text text-muted'>Solo se permiten fechas hasta el {$fecha_maxima}</small>
    ";
}

/**
 * Campo de email
 */
function renderizarEmail($name, $valor = null, $required = '') {
    $valor = htmlspecialchars($valor ?? '');
    return "
        <input type='email' 
               name='{$name}' 
               value='{$valor}' 
               class='form-control' 
               {$required}
               placeholder='ejemplo@email.com'>
    ";
}

/**
 * Genera los estilos CSS necesarios para las escalas
 */
function generarEstilosEscalas() {
    return "
    <style>
        .escala-likert, .escala-numerica {
            margin: 15px 0;
        }
        
        .escala-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .escala-valores {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: nowrap;
            margin: 15px 0;
        }
        
        .escala-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 110px;
            max-width: 150px;
            padding: 10px 5px;
        }
        
        .escala-radio {
            margin-bottom: 8px;
        }
        
        .escala-label {
            text-align: center;
            cursor: pointer;
            font-size: 0.9rem;
            transition: color 0.2s;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .escala-label:hover {
            color: #0d47a1;
            background-color: #f0f4ff;
        }
        
        .escala-numero {
            display: block;
            font-weight: bold;
            font-size: 1.1rem;
            color: #0d47a1;
        }
        
        .escala-texto {
            display: block;
            font-size: 0.85rem;
            color: #333;
            font-weight: 500;
            line-height: 1.2;
        }
        
        .escala-numero-label {
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
            color: #0d47a1;
            transition: color 0.2s;
        }
        
        .escala-numero-label:hover {
            color: #1565c0;
        }
        
        .opciones-radio .form-check,
        .opciones-checkbox .form-check {
            margin-bottom: 10px;
        }
        
        .form-check-input {
            margin-right: 8px;
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        /* Estilos para campos numéricos */
        .numero-field {
            -webkit-appearance: textfield;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        
        .numero-field::-webkit-outer-spin-button,
        .numero-field::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .numero-field.invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .numero-field.valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        /* Estilos para Ranking */
        .ranking-container {
            margin: 15px 0;
        }
        
        .ranking-instructions {
            margin-bottom: 15px;
            font-style: italic;
            color: #666;
        }
        
        .ranking-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .ranking-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            cursor: move;
            transition: all 0.3s;
        }
        
        .ranking-item:hover {
            background-color: #e9ecef;
            border-color: #0d47a1;
        }
        
        .ranking-handle {
            margin-right: 12px;
            color: #6c757d;
            font-size: 1.2rem;
            user-select: none;
        }
        
        .ranking-text {
            font-weight: 500;
        }
        
        /* Estilos para Matriz */
        .matriz-container {
            margin: 15px 0;
            overflow-x: auto;
        }
        
        .matriz-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }
        
        .matriz-table th,
        .matriz-table td {
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .matriz-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .matriz-label {
            text-align: left !important;
            font-weight: 500;
            background-color: #f8f9fa;
        }
        
        .matriz-cell {
            position: relative;
        }
        
        .matriz-cell input[type=\'radio\'] {
            transform: scale(1.2);
        }
        
        /* Estilos para Matriz de Escalas - RESET COMPLETO */
        .matriz-escala-wrapper {
            all: initial !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            display: block !important;
            margin: 15px 0 !important;
        }
        
        .matriz-escala-wrapper * {
            box-sizing: border-box !important;
        }
        
        div.matriz-escala-container {
            margin: 0 !important;
            overflow-x: auto !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 6px !important;
            background-color: #fff !important;
            display: block !important;
        }
        
        table.matriz-escala-table {
            width: 100% !important;
            border-collapse: collapse !important;
            min-width: 600px !important;
            margin: 0 !important;
            background-color: #fff !important;
            font-family: inherit !important;
            display: table !important;
            table-layout: fixed !important;
        }
        
        table.matriz-escala-table thead {
            display: table-header-group !important;
        }
        
        table.matriz-escala-table tbody {
            display: table-row-group !important;
        }
        
        table.matriz-escala-table tr {
            display: table-row !important;
        }
        
        table.matriz-escala-table th,
        table.matriz-escala-table td {
            display: table-cell !important;
            border: 1px solid #dee2e6 !important;
            padding: 12px 8px !important;
            text-align: center !important;
            vertical-align: middle !important;
            background-color: #fff !important;
        }
        
        table.matriz-escala-table thead th {
            background-color: #f8f9fa !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            color: #495057 !important;
            white-space: nowrap !important;
        }
        
        table.matriz-escala-table thead th:first-child {
            background-color: #fff !important;
            border: none !important;
        }
        
        table.matriz-escala-table .matriz-label {
            text-align: left !important;
            font-weight: 500 !important;
            background-color: #f8f9fa !important;
            min-width: 150px !important;
            padding: 12px 15px !important;
            color: #333 !important;
        }
        
        table.matriz-escala-table .matriz-cell {
            width: 80px !important;
            background-color: #fff !important;
            text-align: center !important;
        }
        
        table.matriz-escala-table .matriz-cell input[type=\'radio\'] {
            transform: scale(1.2) !important;
            cursor: pointer !important;
            margin: 0 !important;
            position: relative !important;
            display: inline-block !important;
        }
        
        /* Estilos para Slider */
        .slider-container {
            margin: 20px 0;
        }
        
        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .slider-input {
            width: 100%;
            height: 6px;
            border-radius: 5px;
            background: #ddd;
            outline: none;
            margin: 15px 0;
        }
        
        .slider-input::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #0d47a1;
            cursor: pointer;
        }
        
        .slider-value {
            text-align: center;
            font-weight: 600;
            color: #0d47a1;
            margin-top: 10px;
        }
        
        /* Estilos para Estrellas */
        .estrellas-container {
            margin: 15px 0;
        }
        
        .estrellas-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        
        .estrellas-rating input[type=\'radio\'] {
            display: none;
        }
        
        .estrella {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
            margin-right: 5px;
        }
        
        .estrellas-rating input[type=\'radio\']:checked ~ label,
        .estrellas-rating label:hover,
        .estrellas-rating label:hover ~ label {
            color: #ffc107;
        }
        
        .estrellas-texto {
            text-align: center;
            font-weight: 500;
            color: #495057;
        }
        
        /* Estilos para Imágenes Múltiples */
        .imagen-multiple-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .imagen-opcion {
            position: relative;
        }
        
        .imagen-opcion input[type=\'checkbox\'] {
            display: none;
        }
        
        .imagen-label {
            display: block;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #fff;
        }
        
        .imagen-label:hover {
            border-color: #0d47a1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .imagen-opcion input[type=\'checkbox\']:checked + .imagen-label {
            border-color: #0d47a1;
            background-color: #e3f2fd;
        }
        
        .imagen-preview {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .imagen-texto {
            font-weight: 500;
            color: #495057;
        }
        
        /* Estilos para Archivo */
        .archivo-container {
            margin: 15px 0;
        }
        
        .archivo-input {
            display: none;
        }
        
        .archivo-label {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            background-color: #0d47a1;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .archivo-label:hover {
            background-color: #1565c0;
        }
        
        .archivo-icon {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .archivo-info {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Estilos para Firma */
        .firma-container {
            margin: 15px 0;
        }
        
        .firma-canvas {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: crosshair;
            background-color: #fff;
            display: block;
            margin: 0 auto;
        }
        
        .firma-controles {
            text-align: center;
            margin-top: 10px;
        }
        
        .btn-limpiar {
            padding: 8px 16px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-limpiar:hover {
            background-color: #c82333;
        }
        
        .firma-instrucciones {
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 10px;
        }
        
        /* Estilos para NPS */
        .nps-container {
            margin: 20px 0;
        }
        
        .nps-pregunta {
            font-weight: 500;
            margin-bottom: 15px;
            color: #495057;
        }
        
        .nps-escala {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 15px 0;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .nps-label-min,
        .nps-label-max {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .nps-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }
        
        .nps-option input[type=\'radio\'] {
            display: none;
        }
        
        .nps-numero {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border: 2px solid #dee2e6;
            border-radius: 50%;
            font-weight: bold;
            transition: all 0.3s;
            background-color: #fff;
        }
        
        .nps-option:hover .nps-numero {
            border-color: #0d47a1;
            background-color: #e3f2fd;
        }
        
        .nps-option input[type=\'radio\']:checked + .nps-numero {
            border-color: #0d47a1;
            background-color: #0d47a1;
            color: white;
        }
        
        .nps-categorias {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 0.8rem;
        }
        
        .nps-categoria {
            text-align: center;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .nps-categoria.detractor {
            background-color: #ffe6e6;
            color: #dc3545;
        }
        
        .nps-categoria.pasivo {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .nps-categoria.promotor {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Estilos para Contacto */
        .contacto-container {
            margin: 15px 0;
        }
        
        .contacto-field {
            margin-bottom: 15px;
        }
        
        .contacto-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        
        .contacto-field input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .contacto-field input:focus {
            outline: none;
            border-color: #0d47a1;
            box-shadow: 0 0 0 0.2rem rgba(13, 71, 161, 0.25);
        }
        
        @media (max-width: 768px) {
            .escala-valores {
                gap: 8px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .escala-item {
                min-width: 90px;
                max-width: 120px;
                flex: 0 0 auto;
                margin-bottom: 10px;
            }
            
            .escala-radio {
                margin-bottom: 5px;
            }
            
            .escala-texto {
                font-size: 0.8rem;
                text-align: center;
            }
            
            /* Estilos responsivos para matrices */
            .matriz-escala-container {
                border-radius: 4px;
            }
            
            .matriz-escala-table {
                min-width: 500px;
                font-size: 0.85rem;
            }
            
            .matriz-escala-table thead th {
                font-size: 0.75rem;
                padding: 8px 4px;
            }
            
            .matriz-escala-table .matriz-label {
                min-width: 120px;
                font-size: 0.8rem;
                padding: 10px 8px;
            }
            
            .matriz-escala-table .matriz-cell {
                width: 60px;
                padding: 8px 4px;
            }
        }
        
        @media (max-width: 480px) {
            .escala-valores {
                flex-direction: column;
                gap: 5px;
            }
            
            .escala-item {
                min-width: 100%;
                max-width: 100%;
                flex-direction: row;
                justify-content: flex-start;
                padding: 12px;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                margin-bottom: 8px;
                background-color: #fafafa;
            }
            
            .escala-radio {
                margin-bottom: 0;
                margin-right: 12px;
            }
            
            .escala-texto {
                font-size: 0.9rem;
                text-align: left;
                margin: 0;
            }
            
            /* Estilos para móviles pequeños - matrices */
            .matriz-escala-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 4px;
            }
            
            .matriz-escala-table {
                min-width: 450px;
                font-size: 0.8rem;
            }
            
            .matriz-escala-table thead th {
                font-size: 0.7rem;
                padding: 6px 2px;
            }
            
            .matriz-escala-table .matriz-label {
                min-width: 100px;
                font-size: 0.75rem;
                padding: 8px 5px;
            }
            
            .matriz-escala-table .matriz-cell {
                width: 50px;
                padding: 6px 2px;
            }
        }
    </style>
    
    <script>
        // Función para permitir solo números en campos numéricos
        function soloNumeros(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            // Permitir: backspace, delete, tab, escape, enter
            if (charCode === 46 || charCode === 8 || charCode === 9 || charCode === 27 || charCode === 13 ||
                // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (charCode === 65 && evt.ctrlKey === true) ||
                (charCode === 67 && evt.ctrlKey === true) ||
                (charCode === 86 && evt.ctrlKey === true) ||
                (charCode === 88 && evt.ctrlKey === true)) {
                return true;
            }
            // Permitir solo números (0-9)
            if (charCode < 48 || charCode > 57) {
                evt.preventDefault();
                return false;
            }
            return true;
        }
        
        // Función para validar el valor del campo numérico
        function validarNumero(campo) {
            var valor = campo.value;
            var esNumero = /^[0-9]+$/.test(valor);
            
            // Remover clases anteriores
            campo.classList.remove('valid', 'invalid');
            
            if (valor === '') {
                // Campo vacío - neutral
                return;
            } else if (esNumero && parseInt(valor) >= 0) {
                // Número válido
                campo.classList.add('valid');
            } else {
                // No es un número válido
                campo.classList.add('invalid');
                // Limpiar caracteres no numéricos
                campo.value = valor.replace(/[^0-9]/g, '');
                campo.classList.remove('invalid');
                if (campo.value !== '') {
                    campo.classList.add('valid');
                }
            }
        }
        
        // Prevenir paste de contenido no numérico
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.numero-field').forEach(function(campo) {
                campo.addEventListener('paste', function(e) {
                    e.preventDefault();
                    var paste = (e.clipboardData || window.clipboardData).getData('text');
                    if (/^[0-9]+$/.test(paste)) {
                        this.value = paste;
                        validarNumero(this);
                    }
                });
            });
        });
    </script>
    ";
}

/**
 * Renderiza un campo de clasificación (ranking)
 */
function renderizarClasificacion($name, $opciones, $valor_actual = null, $required = '') {
    if (empty($opciones)) {
        $opciones = ['Opción 1', 'Opción 2', 'Opción 3'];
    }
    
    $html = "<div class='ranking-container'>";
    $html .= "<p class='ranking-instructions'>Arrastra las opciones para ordenarlas por preferencia (1 = más preferido):</p>";
    $html .= "<ul id='ranking-list-{$name}' class='ranking-list'>";
    
    foreach ($opciones as $index => $opcion) {
        $html .= "<li class='ranking-item' data-value='{$index}'>";
        $html .= "<span class='ranking-handle'>≡</span>";
        $html .= "<span class='ranking-text'>{$opcion}</span>";
        $html .= "</li>";
    }
    
    $html .= "</ul>";
    $html .= "<input type='hidden' name='{$name}' id='ranking-{$name}' {$required}>";
    $html .= "</div>";
    
    return $html . generarJavaScriptRanking($name);
}

/**
 * Renderiza una matriz de selección única
 */
function renderizarMatrizSeleccion($name, $opciones, $valor_actual = null, $required = '') {
    $filas = $opciones['filas'] ?? ['Fila 1', 'Fila 2', 'Fila 3'];
    // Usar 'escala' en lugar de 'columnas' para matriz_seleccion
    $escala = $opciones['escala'] ?? $opciones['columnas'] ?? ['Columna 1', 'Columna 2', 'Columna 3'];
    
    $html = "<div class='matriz-container'>";
    $html .= "<table class='matriz-table'>";
    
    // Encabezados
    $html .= "<thead><tr><th></th>";
    foreach ($escala as $col) {
        $html .= "<th>{$col}</th>";
    }
    $html .= "</tr></thead>";
    
    // Filas
    $html .= "<tbody>";
    foreach ($filas as $fila_idx => $fila) {
        $html .= "<tr>";
        $html .= "<td class='matriz-label'>{$fila}</td>";
        foreach ($escala as $col_idx => $col) {
            $value = "{$fila_idx}_{$col_idx}";
            $checked = ($valor_actual === $value) ? 'checked' : '';
            $html .= "<td class='matriz-cell'>";
            $html .= "<input type='radio' name='{$name}[{$fila_idx}]' value='{$col_idx}' {$checked} {$required}>";
            $html .= "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Renderiza una matriz de escalas
 */
function renderizarMatrizEscala($name, $opciones, $valor_actual = null, $required = '') {
    $filas = $opciones['filas'] ?? ['Aspecto 1', 'Aspecto 2', 'Aspecto 3'];
    $escala = $opciones['escala'] ?? ['Muy malo', 'Malo', 'Regular', 'Bueno', 'Muy bueno'];
    
    $html = "<div class='matriz-escala-wrapper' style='all: initial; font-family: inherit; display: block; margin: 15px 0;'>";
    $html .= "<div class='matriz-escala-container'>";
    $html .= "<table class='matriz-escala-table'>";
    
    // Encabezados
    $html .= "<thead><tr><th></th>";
    foreach ($escala as $valor) {
        $html .= "<th>{$valor}</th>";
    }
    $html .= "</tr></thead>";
    
    // Filas
    $html .= "<tbody>";
    foreach ($filas as $fila_idx => $fila) {
        $html .= "<tr>";
        $html .= "<td class='matriz-label'>{$fila}</td>";
        foreach ($escala as $escala_idx => $valor_escala) {
            $value = "{$fila_idx}_{$escala_idx}";
            $checked = ($valor_actual === $value) ? 'checked' : '';
            $html .= "<td class='matriz-cell'>";
            $html .= "<input type='radio' name='{$name}[{$fila_idx}]' value='{$escala_idx}' {$checked} {$required}>";
            $html .= "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";
    $html .= "</div>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Renderiza un control deslizante (slider)
 */
function renderizarSlider($name, $opciones, $valor_actual = null, $required = '') {
    $min = $opciones['min'] ?? 0;
    $max = $opciones['max'] ?? 100;
    $step = $opciones['step'] ?? 1;
    $label_min = $opciones['label_min'] ?? $min;
    $label_max = $opciones['label_max'] ?? $max;
    $valor = $valor_actual ?? $min;
    
    $html = "<div class='slider-container'>";
    $html .= "<div class='slider-labels'>";
    $html .= "<span class='slider-min'>{$label_min}</span>";
    $html .= "<span class='slider-max'>{$label_max}</span>";
    $html .= "</div>";
    $html .= "<input type='range' name='{$name}' min='{$min}' max='{$max}' step='{$step}' value='{$valor}' class='slider-input' {$required}>";
    $html .= "<div class='slider-value'>Valor: <span id='slider-value-{$name}'>{$valor}</span></div>";
    $html .= "</div>";
    
    return $html . generarJavaScriptSlider($name);
}

/**
 * Renderiza calificación con estrellas
 */
function renderizarCalificacionEstrellas($name, $valor_actual = null, $required = '') {
    $html = "<div class='estrellas-container'>";
    $html .= "<div class='estrellas-rating' data-name='{$name}'>";
    
    for ($i = 1; $i <= 5; $i++) {
        $checked = ($valor_actual == $i) ? 'checked' : '';
        $html .= "<input type='radio' name='{$name}' value='{$i}' id='star-{$name}-{$i}' {$checked} {$required}>";
        $html .= "<label for='star-{$name}-{$i}' class='estrella'>★</label>";
    }
    
    $html .= "</div>";
    $html .= "<div class='estrellas-texto'>Calificación: <span id='rating-text-{$name}'>Sin calificar</span></div>";
    $html .= "</div>";
    
    return $html . generarJavaScriptEstrellas($name);
}

/**
 * Renderiza selección múltiple con imágenes
 */
function renderizarImagenMultiple($name, $opciones, $valor_actual = null, $required = '') {
    if (empty($opciones)) {
        $opciones = [
            ['texto' => 'Opción 1', 'imagen' => 'placeholder1.jpg'],
            ['texto' => 'Opción 2', 'imagen' => 'placeholder2.jpg']
        ];
    }
    
    $valores_seleccionados = is_array($valor_actual) ? $valor_actual : [];
    
    $html = "<div class='imagen-multiple-container'>";
    foreach ($opciones as $index => $opcion) {
        $checked = in_array($index, $valores_seleccionados) ? 'checked' : '';
        $texto = $opcion['texto'] ?? "Opción {$index}";
        $imagen = $opcion['imagen'] ?? 'placeholder.jpg';
        
        $html .= "<div class='imagen-opcion'>";
        $html .= "<input type='checkbox' name='{$name}[]' value='{$index}' id='img-{$name}-{$index}' {$checked} {$required}>";
        $html .= "<label for='img-{$name}-{$index}' class='imagen-label'>";
        $html .= "<img src='images/{$imagen}' alt='{$texto}' class='imagen-preview'>";
        $html .= "<span class='imagen-texto'>{$texto}</span>";
        $html .= "</label>";
        $html .= "</div>";
    }
    $html .= "</div>";
    
    return $html;
}

/**
 * Renderiza campo de subida de archivos
 */
function renderizarArchivo($name, $valor_actual = null, $required = '') {
    $html = "<div class='archivo-container'>";
    $html .= "<input type='file' name='{$name}' id='file-{$name}' class='archivo-input' {$required}>";
    $html .= "<label for='file-{$name}' class='archivo-label'>";
    $html .= "<span class='archivo-icon'>📁</span>";
    $html .= "<span class='archivo-text'>Seleccionar archivo</span>";
    $html .= "</label>";
    $html .= "<div class='archivo-info' id='file-info-{$name}'>Ningún archivo seleccionado</div>";
    $html .= "</div>";
    
    return $html . generarJavaScriptArchivo($name);
}

/**
 * Renderiza campo de firma digital
 */
function renderizarFirma($name, $valor_actual = null, $required = '') {
    $html = "<div class='firma-container'>";
    $html .= "<canvas id='canvas-{$name}' class='firma-canvas' width='400' height='200'></canvas>";
    $html .= "<input type='hidden' name='{$name}' id='firma-{$name}' {$required}>";
    $html .= "<div class='firma-controles'>";
    $html .= "<button type='button' onclick='limpiarFirma(\"{$name}\")' class='btn-limpiar'>Limpiar</button>";
    $html .= "</div>";
    $html .= "<p class='firma-instrucciones'>Dibuje su firma en el área de arriba</p>";
    $html .= "</div>";
    
    return $html . generarJavaScriptFirma($name);
}

/**
 * Renderiza Net Promoter Score
 */
function renderizarNetPromoterScore($name, $valor_actual = null, $required = '') {
    $html = "<div class='nps-container'>";
    $html .= "<p class='nps-pregunta'>¿Qué tan probable es que recomiendes nuestro producto/servicio a un amigo o colega?</p>";
    $html .= "<div class='nps-escala'>";
    $html .= "<span class='nps-label-min'>Nada probable (0)</span>";
    
    for ($i = 0; $i <= 10; $i++) {
        $checked = ($valor_actual == $i) ? 'checked' : '';
        $html .= "<label class='nps-option'>";
        $html .= "<input type='radio' name='{$name}' value='{$i}' {$checked} {$required}>";
        $html .= "<span class='nps-numero'>{$i}</span>";
        $html .= "</label>";
    }
    
    $html .= "<span class='nps-label-max'>Muy probable (10)</span>";
    $html .= "</div>";
    $html .= "<div class='nps-categorias'>";
    $html .= "<div class='nps-categoria detractor'>0-6: Detractores</div>";
    $html .= "<div class='nps-categoria pasivo'>7-8: Pasivos</div>";
    $html .= "<div class='nps-categoria promotor'>9-10: Promotores</div>";
    $html .= "</div>";
    $html .= "</div>";
    
    return $html;
}

/**
 * Renderiza formulario de contacto
 */
function renderizarContacto($name, $valor_actual = null, $required = '') {
    $valores = is_array($valor_actual) ? $valor_actual : [];
    
    $html = "<div class='contacto-container'>";
    $html .= "<div class='contacto-field'>";
    $html .= "<label for='{$name}-nombre'>Nombre completo:</label>";
    $html .= "<input type='text' name='{$name}[nombre]' id='{$name}-nombre' value='" . ($valores['nombre'] ?? '') . "' {$required}>";
    $html .= "</div>";
    
    $html .= "<div class='contacto-field'>";
    $html .= "<label for='{$name}-email'>Email:</label>";
    $html .= "<input type='email' name='{$name}[email]' id='{$name}-email' value='" . ($valores['email'] ?? '') . "' {$required}>";
    $html .= "</div>";
    
    $html .= "<div class='contacto-field'>";
    $html .= "<label for='{$name}-telefono'>Teléfono:</label>";
    $html .= "<input type='tel' name='{$name}[telefono]' id='{$name}-telefono' value='" . ($valores['telefono'] ?? '') . "'>";
    $html .= "</div>";
    
    $html .= "<div class='contacto-field'>";
    $html .= "<label for='{$name}-empresa'>Empresa (opcional):</label>";
    $html .= "<input type='text' name='{$name}[empresa]' id='{$name}-empresa' value='" . ($valores['empresa'] ?? '') . "'>";
    $html .= "</div>";
    
    $html .= "</div>";
    
    return $html;
}

// Funciones de JavaScript para los nuevos tipos
function generarJavaScriptRanking($name) {
    return "
    <script>
        // Funcionalidad de ranking con drag & drop
        (function() {
            const lista = document.getElementById('ranking-list-{$name}');
            const input = document.getElementById('ranking-{$name}');
            
            if (!lista || !input) return;
            
            let draggedElement = null;
            
            // Hacer elementos arrastrables
            lista.querySelectorAll('.ranking-item').forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.style.opacity = '0.5';
                });
                
                item.addEventListener('dragend', function(e) {
                    this.style.opacity = '';
                    draggedElement = null;
                });
                
                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                });
                
                item.addEventListener('drop', function(e) {
                    e.preventDefault();
                    if (draggedElement !== this) {
                        lista.insertBefore(draggedElement, this.nextSibling);
                        actualizarRanking();
                    }
                });
            });
            
            function actualizarRanking() {
                const items = lista.querySelectorAll('.ranking-item');
                const ranking = [];
                items.forEach((item, index) => {
                    ranking.push(item.dataset.value);
                });
                input.value = JSON.stringify(ranking);
            }
            
            // Inicializar ranking
            actualizarRanking();
        })();
    </script>";
}

function generarJavaScriptSlider($name) {
    return "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.querySelector('input[name=\"{$name}\"]');
            const display = document.getElementById('slider-value-{$name}');
            
            if (slider && display) {
                slider.addEventListener('input', function() {
                    display.textContent = this.value;
                });
            }
        });
    </script>";
}

function generarJavaScriptEstrellas($name) {
    return "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('[data-name=\"{$name}\"]');
            const texto = document.getElementById('rating-text-{$name}');
            const textos = ['Sin calificar', 'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'];
            
            if (container && texto) {
                container.addEventListener('change', function(e) {
                    if (e.target.type === 'radio') {
                        const valor = parseInt(e.target.value);
                        texto.textContent = textos[valor] || 'Sin calificar';
                    }
                });
                
                // Inicializar si hay valor seleccionado
                const checked = container.querySelector('input:checked');
                if (checked) {
                    const valor = parseInt(checked.value);
                    texto.textContent = textos[valor] || 'Sin calificar';
                }
            }
        });
    </script>";
}

function generarJavaScriptArchivo($name) {
    return "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('file-{$name}');
            const info = document.getElementById('file-info-{$name}');
            
            if (input && info) {
                input.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const archivo = this.files[0];
                        info.textContent = archivo.name + ' (' + (archivo.size / 1024).toFixed(1) + ' KB)';
                    } else {
                        info.textContent = 'Ningún archivo seleccionado';
                    }
                });
            }
        });
    </script>";
}

function generarJavaScriptFirma($name) {
    return "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('canvas-{$name}');
            const input = document.getElementById('firma-{$name}');
            
            if (!canvas || !input) return;
            
            const ctx = canvas.getContext('2d');
            let dibujando = false;
            
            // Configurar canvas
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            
            function iniciarDibujo(e) {
                dibujando = true;
                ctx.beginPath();
                const rect = canvas.getBoundingClientRect();
                ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
            }
            
            function dibujar(e) {
                if (!dibujando) return;
                const rect = canvas.getBoundingClientRect();
                ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
                ctx.stroke();
                
                // Guardar como base64
                input.value = canvas.toDataURL();
            }
            
            function terminarDibujo() {
                dibujando = false;
            }
            
            canvas.addEventListener('mousedown', iniciarDibujo);
            canvas.addEventListener('mousemove', dibujar);
            canvas.addEventListener('mouseup', terminarDibujo);
            
            // Soporte touch para móviles
            canvas.addEventListener('touchstart', function(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });
            
            canvas.addEventListener('touchmove', function(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });
            
            canvas.addEventListener('touchend', function(e) {
                e.preventDefault();
                const mouseEvent = new MouseEvent('mouseup', {});
                canvas.dispatchEvent(mouseEvent);
            });
        });
        
        function limpiarFirma(name) {
            const canvas = document.getElementById('canvas-' + name);
            const input = document.getElementById('firma-' + name);
            
            if (canvas && input) {
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                input.value = '';
            }
        }
    </script>";
}
?>