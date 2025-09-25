<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $pdo = obtenerConexion();
    
    echo "=== COMPLETANDO PREGUNTAS FALTANTES ===\n\n";
    
    // Preguntas CECOSF (categoría 3)
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
    
    // Preguntas SAR (categoría 4)
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
    
    // Preguntas Urgencias (categoría 5)
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
    
    $stmt = $pdo->prepare("INSERT INTO banco_preguntas (categoria_id, tipo_pregunta_id, texto, orden, departamento, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Insertar CECOSF
    foreach ($preguntas_cecosf as $index => $pregunta) {
        $stmt->execute([3, 5, $pregunta, $index + 1, 'cecosf', 'admin']);
    }
    echo "✓ " . count($preguntas_cecosf) . " preguntas CECOSF insertadas\n";
    
    // Insertar SAR
    foreach ($preguntas_sar as $index => $pregunta) {
        $stmt->execute([4, 5, $pregunta, $index + 1, 'sar', 'admin']);
    }
    echo "✓ " . count($preguntas_sar) . " preguntas SAR insertadas\n";
    
    // Insertar Urgencias
    foreach ($preguntas_urgencias as $index => $pregunta) {
        $stmt->execute([5, 5, $pregunta, $index + 1, 'urgencias', 'admin']);
    }
    echo "✓ " . count($preguntas_urgencias) . " preguntas Urgencias insertadas\n";
    
    // Verificar total
    $stmt = $pdo->query("SELECT COUNT(*) FROM banco_preguntas");
    $total = $stmt->fetchColumn();
    
    echo "\n=== COMPLETADO ===\n";
    echo "Total de preguntas en el banco: $total\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
