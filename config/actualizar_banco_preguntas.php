<?php
// Script para actualizar el banco de preguntas con las nuevas categorías

require_once 'conexion.php';

try {
    $pdo = obtenerConexion();
    
    echo "=== ACTUALIZANDO BANCO DE PREGUNTAS DAS HUALPEN ===\n\n";
    
    // Limpiar datos anteriores
    echo "→ Limpiando banco de preguntas anterior...\n";
    $pdo->exec("DELETE FROM banco_preguntas");
    $pdo->exec("DELETE FROM categorias");
    
    // Insertar nuevas categorías
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
    
    // Insertar nuevas preguntas
    echo "→ Insertando preguntas por categoría...\n";
    
    // Preguntas Generales
    $preguntas_generales = [
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
    ];
    
    // Preguntas CESFAM
    $preguntas_cesfam = [
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
    ];
    
    // Preguntas CECOSF
    $preguntas_cecosf = [
        "¿Conoce los servicios que entrega su CECOSF más cercano?",
        "¿Le resulta accesible acudir a un CECOSF en su barrio?",
        "¿Está satisfecho con la atención recibida en el CECOSF?",
        "¿Qué tan bien lo orientan en el CECOSF sobre derivaciones al CESFAM?",
        "¿Ha participado en actividades comunitarias organizadas por el CECOSF?",
        "¿Considera que los horarios de atención del CECOSF son adecuados?",
        "¿Está conforme con la atención de enfermería en el CECOSF?",
        "¿Ha recibido atención preventiva en su CECOSF?",
        "¿Qué tan fácil es obtener una hora en el CECOSF?",
        "¿El personal del CECOSF le entrega información clara?",
        "¿Considera que el CECOSF contribuye a mejorar la salud en su barrio?",
        "¿Se siente escuchado por el equipo de salud del CECOSF?",
        "¿Ha accedido a programas de salud mental en el CECOSF?",
        "¿Qué tan satisfecho está con las campañas de vacunación?",
        "¿Ha tenido acceso a controles de niño sano en el CECOSF?",
        "¿Cómo evalúa la atención de salud de la mujer en el CECOSF?",
        "¿Ha recibido visitas domiciliarias desde el CECOSF?",
        "¿Está conforme con la infraestructura del CECOSF?",
        "¿Cree que el CECOSF responde a las necesidades de su comunidad?",
        "¿Recomendaría el CECOSF a sus vecinos?"
    ];
    
    // Preguntas SAR
    $preguntas_sar = [
        "¿Ha utilizado el Servicio de Atención Primaria de Urgencia (SAR) en Hualpén?",
        "¿Cómo calificaría la rapidez de la atención en el SAR?",
        "¿Considera adecuado el tiempo de espera en el SAR?",
        "¿Está conforme con la atención del personal médico en el SAR?",
        "¿Cómo evalúa la atención del equipo de enfermería en el SAR?",
        "¿Se siente seguro al acudir al SAR por una urgencia?",
        "¿Ha recibido información clara sobre su diagnóstico en el SAR?",
        "¿El personal del SAR le brinda un trato amable y respetuoso?",
        "¿Está conforme con la infraestructura del SAR?",
        "¿Considera que el SAR está bien equipado?",
        "¿Qué tan fácil le resulta acceder al SAR desde su hogar?",
        "¿Se siente satisfecho con la resolución de su problema de salud en el SAR?",
        "¿Recibe derivaciones oportunas a hospitales u otros servicios?",
        "¿Cómo evalúa la limpieza en las instalaciones del SAR?",
        "¿Ha tenido acceso oportuno a medicamentos en el SAR?",
        "¿Considera que el SAR ayuda a descongestionar hospitales?",
        "¿Cómo calificaría la atención en situaciones de urgencia nocturna?",
        "¿Está conforme con la atención de niños en el SAR?",
        "¿Recomendaría el SAR a familiares o vecinos?",
        "¿Qué mejoras propondría para el SAR de Hualpén?"
    ];
    
    // Preguntas Urgencias
    $preguntas_urgencias = [
        "¿Ha acudido a un servicio de urgencias en Hualpén en el último año?",
        "¿Cómo evalúa el tiempo de espera en la urgencia?",
        "¿Está conforme con la atención del personal médico en urgencias?",
        "¿Cómo calificaría la atención del personal de enfermería en urgencias?",
        "¿Recibió un diagnóstico claro y oportuno en la urgencia?",
        "¿Considera que el servicio de urgencias está bien equipado?",
        "¿Se siente seguro en la sala de espera de urgencias?",
        "¿Cómo calificaría la limpieza e higiene en el área de urgencias?",
        "¿Está satisfecho con el trato recibido por el personal de urgencias?",
        "¿Recibió la medicación necesaria durante su atención en urgencias?",
        "¿Considera que los tiempos de espera son razonables en urgencias?",
        "¿Ha tenido que acudir varias veces a urgencias por la misma condición?",
        "¿Cómo evalúa la atención en urgencias pediátricas?",
        "¿Está conforme con la atención recibida en horarios nocturnos?",
        "¿Recibió derivación oportuna a otro centro de mayor complejidad?",
        "¿Qué tan fácil le resultó llegar al servicio de urgencias?",
        "¿Recibió orientación clara al momento del alta médica?",
        "¿Cómo evalúa la coordinación entre urgencias y otros servicios?",
        "¿Recomendaría el servicio de urgencias a sus vecinos?",
        "¿Qué mejoras propondría para el servicio de urgencias en Hualpén?"
    ];
    
    // Insertar todas las preguntas
    $stmt = $pdo->prepare("INSERT INTO banco_preguntas (categoria_id, tipo_pregunta_id, texto, orden, departamento, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    $total_insertadas = 0;
    
    // Categoría Generales (ID: 1)
    foreach ($preguntas_generales as $index => $pregunta) {
        $stmt->execute([1, 5, $pregunta, $index + 1, 'general', 'admin']); // tipo_pregunta_id = 5 (escala_likert)
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_generales) . " preguntas generales insertadas\n";
    
    // Categoría CESFAM (ID: 2)
    foreach ($preguntas_cesfam as $index => $pregunta) {
        $stmt->execute([2, 5, $pregunta, $index + 1, 'cesfam', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_cesfam) . " preguntas CESFAM insertadas\n";
    
    // Categoría CECOSF (ID: 3)
    foreach ($preguntas_cecosf as $index => $pregunta) {
        $stmt->execute([3, 5, $pregunta, $index + 1, 'cecosf', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_cecosf) . " preguntas CECOSF insertadas\n";
    
    // Categoría SAR (ID: 4)
    foreach ($preguntas_sar as $index => $pregunta) {
        $stmt->execute([4, 5, $pregunta, $index + 1, 'sar', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_sar) . " preguntas SAR insertadas\n";
    
    // Categoría Urgencias (ID: 5)
    foreach ($preguntas_urgencias as $index => $pregunta) {
        $stmt->execute([5, 5, $pregunta, $index + 1, 'urgencias', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_urgencias) . " preguntas de urgencias insertadas\n";
    
    echo "\n=== ACTUALIZACIÓN COMPLETADA ===\n";
    echo "Total de categorías: " . count($nuevas_categorias) . "\n";
    echo "Total de preguntas: $total_insertadas\n";
    echo "Banco de preguntas actualizado exitosamente.\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
