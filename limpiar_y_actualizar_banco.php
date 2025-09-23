<?php
require_once 'config/conexion.php';

try {
    $pdo = obtenerConexion();
    
    echo "=== LIMPIANDO Y ACTUALIZANDO BANCO DE PREGUNTAS ===\n\n";
    
    // Paso 1: Eliminar relaciones encuesta-preguntas
    echo "→ Eliminando preguntas de encuestas existentes...\n";
    $pdo->exec("DELETE FROM encuesta_preguntas");
    echo "✓ Relaciones eliminadas\n";
    
    // Paso 2: Eliminar preguntas del banco
    echo "→ Eliminando banco de preguntas anterior...\n";
    $pdo->exec("DELETE FROM banco_preguntas");
    echo "✓ Banco limpio\n";
    
    // Paso 3: Eliminar categorías
    echo "→ Eliminando categorías anteriores...\n";
    $pdo->exec("DELETE FROM categorias");
    echo "✓ Categorías eliminadas\n";
    
    // Paso 4: Insertar nuevas categorías
    echo "→ Insertando nuevas categorías...\n";
    $nuevas_categorias = [
        [1, 'Generales', 'Preguntas generales sobre servicios de salud municipales', '#0d47a1'],
        [2, 'CESFAM', 'Centro de Salud Familiar', '#32CD32'],
        [3, 'CECOSF', 'Centro Comunitario de Salud Familiar', '#17a2b8'],
        [4, 'SAR', 'Servicio de Atención Primaria de Urgencia', '#ffc107'],
        [5, 'Urgencias', 'Servicios de urgencia hospitalaria', '#dc3545']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO categorias (id, nombre, descripcion, color) VALUES (?, ?, ?, ?)");
    foreach ($nuevas_categorias as $cat) {
        $stmt->execute($cat);
    }
    echo "✓ " . count($nuevas_categorias) . " categorías insertadas\n";
    
    // Paso 5: Insertar nuevas preguntas
    echo "→ Insertando nuevas preguntas...\n";
    
    $preguntas_por_categoria = [
        1 => [ // Generales
            "¿Con qué frecuencia utiliza los servicios de salud municipales en Hualpén?",
            "¿Cómo evalúa la calidad general de la atención en los servicios de salud de Hualpén?",
            "¿Considera que el tiempo de espera para ser atendido es adecuado?",
            "¿Se siente informado sobre los servicios de salud disponibles en su comuna?",
            "¿Qué tan fácil es acceder a información sobre horarios y servicios?",
            "¿Qué tan satisfecho está con la atención recibida en su última consulta?",
            "¿Considera que las instalaciones de salud en Hualpén están bien mantenidas?",
            "¿Ha tenido dificultades para obtener horas médicas en el sistema de salud municipal?",
            "¿Cómo evalúa la amabilidad del personal de salud en general?",
            "¿Recomendaría los servicios de salud municipales a familiares o amigos?",
            "¿Considera que los servicios de salud de Hualpén cubren sus necesidades básicas?",
            "¿Cree que existe suficiente personal médico para la demanda actual?",
            "¿Está conforme con la atención de urgencia en la comuna?",
            "¿Cómo calificaría la limpieza e higiene en los recintos de salud?",
            "¿Cree que la comuna invierte lo suficiente en salud?",
            "¿Se siente seguro al acudir a los recintos de salud de Hualpén?",
            "¿Qué tan fácil le resulta acceder a medicamentos en la farmacia municipal?",
            "¿Ha participado en actividades de promoción de la salud organizadas por el municipio?",
            "¿Qué tan satisfecho está con el trato recibido por el personal administrativo?",
            "¿Qué mejoras le gustaría ver en el sistema de salud municipal de Hualpén?"
        ],
        2 => [ // CESFAM
            "¿Cómo evalúa la atención en su CESFAM de referencia?",
            "¿Recibe atención oportuna cuando solicita una hora en el CESFAM?",
            "¿Cómo calificaría la atención del médico en su CESFAM?",
            "¿Cómo calificaría la atención del equipo de enfermería?",
            "¿Recibe información clara sobre diagnósticos y tratamientos?",
            "¿Ha tenido problemas con la disponibilidad de medicamentos en la farmacia del CESFAM?",
            "¿Qué tan satisfecho está con la atención de salud preventiva?",
            "¿Le resulta fácil acceder a controles de enfermedades crónicas en el CESFAM?",
            "¿Considera que el CESFAM tiene la infraestructura adecuada?",
            "¿Cómo calificaría la atención recibida en el SOME del CESFAM?",
            "¿Cree que el CESFAM responde a las necesidades de su comunidad?",
            "¿Qué tan fácil le resulta solicitar una hora médica?",
            "¿Está satisfecho con los tiempos de espera en sala?",
            "¿Recibe recordatorios de sus controles de salud?",
            "¿Ha tenido acceso a programas de salud mental en el CESFAM?",
            "¿Está conforme con los servicios odontológicos en el CESFAM?",
            "¿Ha participado en talleres o charlas organizadas por el CESFAM?",
            "¿Cómo evalúa la coordinación entre los distintos profesionales del CESFAM?",
            "¿Siente que el equipo de salud escucha sus inquietudes?",
            "¿Qué tan satisfecho está con el tiempo de atención durante sus consultas?"
        ]
        // Agregar las otras categorías aquí...
    ];
    
    $stmt = $pdo->prepare("INSERT INTO banco_preguntas (categoria_id, tipo_pregunta_id, texto, orden, departamento, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    $total_insertadas = 0;
    foreach ($preguntas_por_categoria as $categoria_id => $preguntas) {
        foreach ($preguntas as $index => $pregunta) {
            $departamento = ($categoria_id == 1) ? 'general' : strtolower($nuevas_categorias[$categoria_id - 1][1]);
            $stmt->execute([$categoria_id, 5, $pregunta, $index + 1, $departamento, 'admin']);
            $total_insertadas++;
        }
    }
    
    echo "✓ $total_insertadas preguntas insertadas\n";
    
    echo "\n=== ACTUALIZACIÓN COMPLETADA ===\n";
    echo "IMPORTANTE: Las encuestas existentes han perdido sus preguntas.\n";
    echo "Deberás volver a agregar preguntas a las encuestas desde el nuevo banco.\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
