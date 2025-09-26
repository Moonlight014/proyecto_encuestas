<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $pdo = obtenerConexion();
    
    echo "=== COMPLETANDO PREGUNTAS FALTANTES ===\n\n";
    
    // Preguntas CECOSF (categoría 6)
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
    
    // Preguntas SAPU (categoría 7)
    $preguntas_sar = [
        "¿Ha utilizado el Servicio de Atención Primaria de Urgencia (SAPU) en Hualpén?",
        "¿Cómo calificaría la rapidez de la atención en el SAPU?",
        "¿Considera adecuado el tiempo de espera en el SAPU?",
        "¿Está conforme con la atención del personal médico en el SAPU?",
        "¿Cómo evalúa la atención del equipo de enfermería en el SAPU?",
        "¿Se siente seguro al acudir al SAPU por una urgencia?",
        "¿Ha recibido información clara sobre su diagnóstico en el SAPU?",
        "¿El personal del SAPU le brinda un trato amable y respetuoso?",
        "¿Está conforme con la infraestructura del SAPU?",
        "¿Considera que el SAPU está bien equipado?",
        "¿Qué tan fácil le resulta acceder al SAPU desde su hogar?",
        "¿Se siente satisfecho con la resolución de su problema de salud en el SAPU?",
        "¿Recibe derivaciones oportunas a hospitales u otros servicios?",
        "¿Cómo evalúa la limpieza en las instalaciones del SAPU?",
        "¿Ha tenido acceso oportuno a medicamentos en el SAPU?",
        "¿Considera que el SAPU ayuda a descongestionar hospitales?",
        "¿Cómo calificaría la atención en situaciones de urgencia nocturna?",
        "¿Está conforme con la atención de niños en el SAPU?",
        "¿Recomendaría el SAPU a familiares o vecinos?",
        "¿Qué mejoras propondría para el SAPU de Hualpén?"
    ];
    
    // Preguntas Servicios (categoría 9)
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
        $stmt->execute([6, 5, $pregunta, $index + 1, 'cecosf', 'admin']); // Corregido de 3 a 6
    }
    echo "✓ " . count($preguntas_cecosf) . " preguntas CECOSF insertadas\n";
    
    // Insertar SAPU
    foreach ($preguntas_sar as $index => $pregunta) {
        $stmt->execute([7, 5, $pregunta, $index + 1, 'sapu', 'admin']); // Corregido de 4 a 7, y 'sar' a 'sapu'
    }
    echo "✓ " . count($preguntas_sar) . " preguntas SAPU insertadas\n";
    
    // Insertar Servicios (antes Urgencias)
    foreach ($preguntas_urgencias as $index => $pregunta) {
        $stmt->execute([9, 5, $pregunta, $index + 1, 'servicios', 'admin']); // Corregido de 5 a 9, y 'urgencias' a 'servicios'
    }
    echo "✓ " . count($preguntas_urgencias) . " preguntas de Servicios insertadas\n";
    
    // Verificar total
    $stmt = $pdo->query("SELECT COUNT(*) FROM banco_preguntas");
    $total = $stmt->fetchColumn();
    
    echo "\n=== COMPLETADO ===\n";
    echo "Total de preguntas en el banco: $total\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
