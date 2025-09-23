<?php
// Script completo para generar la base de datos DAS Hualpén con todos los datos
// Combina la estructura de base de datos y el banco de preguntas completo

require_once 'conexion.php';

try {
    $pdo = obtenerConexion();

    echo "=== GENERANDO BASE DE DATOS COMPLETA DAS HUALPEN ===\n\n";

    // Crear la base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS das_encuestas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE das_encuestas");

    echo "✓ Base de datos 'das_encuestas' creada/verificada\n";

    // Crear estructura de tablas
    $estructura_sql = "
    -- Tabla de Categorías
    CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        color VARCHAR(7) DEFAULT '#007bff',
        activo BOOLEAN DEFAULT TRUE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Tabla de Tipos de Pregunta
    CREATE TABLE IF NOT EXISTS tipos_pregunta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL,
        descripcion TEXT,
        activo BOOLEAN DEFAULT TRUE
    );

    -- Tabla de Departamentos
    CREATE TABLE IF NOT EXISTS departamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        activo BOOLEAN DEFAULT TRUE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Tabla de Usuarios
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        departamento_id INT,
        cargo VARCHAR(100),
        rol ENUM('super_admin', 'admin_departamental', 'visualizador') DEFAULT 'visualizador',
        activo BOOLEAN DEFAULT FALSE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_acceso TIMESTAMP NULL,
        FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
    );

    -- Banco de Preguntas
    CREATE TABLE IF NOT EXISTS banco_preguntas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categoria_id INT NOT NULL,
        tipo_pregunta_id INT NOT NULL,
        texto TEXT NOT NULL,
        opciones JSON NULL,
        orden INT DEFAULT 0,
        departamento VARCHAR(100) DEFAULT 'general',
        obligatoria BOOLEAN DEFAULT FALSE,
        activa BOOLEAN DEFAULT TRUE,
        created_by VARCHAR(50) DEFAULT 'admin',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id),
        FOREIGN KEY (tipo_pregunta_id) REFERENCES tipos_pregunta(id)
    );

    -- Tabla de Encuestas
    CREATE TABLE IF NOT EXISTS encuestas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT NOT NULL,
        departamento_id INT NOT NULL,
        creado_por INT NOT NULL,
        estado ENUM('borrador', 'activa', 'pausada', 'finalizada') DEFAULT 'borrador',
        fecha_inicio DATETIME NULL,
        fecha_fin DATETIME NULL,
        enlace_publico VARCHAR(100) UNIQUE NOT NULL,
        codigo_qr TEXT NULL,
        configuracion JSON NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (departamento_id) REFERENCES departamentos(id),
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
    );

    -- Relación Encuestas-Preguntas
    CREATE TABLE IF NOT EXISTS encuesta_preguntas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        encuesta_id INT NOT NULL,
        pregunta_id INT NOT NULL,
        orden INT NOT NULL,
        seccion VARCHAR(100) NULL,
        obligatoria_encuesta BOOLEAN DEFAULT FALSE,
        configuracion_especifica JSON NULL,
        activa BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE,
        FOREIGN KEY (pregunta_id) REFERENCES banco_preguntas(id),
        UNIQUE KEY unique_encuesta_pregunta_orden (encuesta_id, orden)
    );

    -- Tabla de Respuestas
    CREATE TABLE IF NOT EXISTS respuestas_encuesta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        encuesta_id INT NOT NULL,
        ip_hash VARCHAR(255) NULL,
        sesion_token VARCHAR(100) UNIQUE NOT NULL,
        estado ENUM('iniciada', 'completada', 'abandonada') DEFAULT 'iniciada',
        progreso_porcentaje DECIMAL(5,2) DEFAULT 0.00,
        fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_completada TIMESTAMP NULL,
        fecha_ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (encuesta_id) REFERENCES encuestas(id)
    );

    -- Respuestas Detalladas
    CREATE TABLE IF NOT EXISTS respuestas_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        respuesta_encuesta_id INT NOT NULL,
        pregunta_id INT NOT NULL,
        valor_respuesta JSON NOT NULL,
        fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (respuesta_encuesta_id) REFERENCES respuestas_encuesta(id) ON DELETE CASCADE,
        FOREIGN KEY (pregunta_id) REFERENCES banco_preguntas(id),
        UNIQUE KEY unique_respuesta_pregunta (respuesta_encuesta_id, pregunta_id)
    );

    -- Sesiones de usuario
    CREATE TABLE IF NOT EXISTS sesiones_usuario (
        id VARCHAR(128) PRIMARY KEY,
        usuario_id INT NOT NULL,
        datos_sesion JSON,
        fecha_expiracion TIMESTAMP NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );";

    $pdo->exec($estructura_sql);
    echo "✓ Estructura de tablas creada\n";

    // Insertar tipos de pregunta
    $tipos_stmt = $pdo->prepare("SELECT COUNT(*) FROM tipos_pregunta");
    $tipos_stmt->execute();

    if ($tipos_stmt->fetchColumn() == 0) {
        echo "→ Insertando tipos de pregunta...\n";
        $tipos = [
            [1, 'texto_corto', 'Campo de texto de una línea'],
            [2, 'texto_largo', 'Área de texto multilínea'],
            [3, 'opcion_multiple', 'Selección única entre varias opciones'],
            [4, 'seleccion_multiple', 'Selección múltiple entre varias opciones'],
            [5, 'escala_likert', 'Escala de 1 a 5 (Likert)'],
            [6, 'escala_numerica', 'Escala numérica con rango personalizable'],
            [7, 'numero', 'Campo numérico simple'],
            [8, 'fecha', 'Selector de fecha'],
            [9, 'email', 'Campo de correo electrónico con validación']
        ];

        $stmt = $pdo->prepare("INSERT INTO tipos_pregunta (id, nombre, descripcion) VALUES (?, ?, ?)");
        foreach ($tipos as $tipo) {
            $stmt->execute($tipo);
        }
        echo "✓ Tipos de pregunta insertados\n";
    }

    // Insertar categorías
    $cat_stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias");
    $cat_stmt->execute();

    if ($cat_stmt->fetchColumn() == 0) {
        echo "→ Insertando categorías...\n";
        $categorias = [
            [1, 'Satisfacción', 'Preguntas sobre satisfacción general', '#28a745'],
            [2, 'Atención Primaria', 'Preguntas específicas de atención primaria', '#007bff'],
            [3, 'Atención Secundaria', 'Preguntas sobre derivaciones y especialistas', '#17a2b8'],
            [4, 'Atención Terciaria', 'Preguntas sobre hospitalizaciones y urgencias', '#dc3545'],
            [5, 'CESFAM', 'Preguntas específicas del Centro de Salud Familiar', '#ffc107'],
            [6, 'CECOSF', 'Preguntas del Centro Comunitario de Salud Familiar', '#6f42c1'],
            [7, 'SAPU', 'Preguntas del Servicio de Atención Primaria de Urgencia', '#fd7e14'],
            [8, 'Postas Rurales', 'Preguntas sobre atención en postas rurales', '#198754'],
            [9, 'Servicios', 'Preguntas sobre servicios específicos de salud', '#0d6efd'],
            [10, 'General', 'Preguntas demográficas y generales', '#6c757d']
        ];

        $stmt = $pdo->prepare("INSERT INTO categorias (id, nombre, descripcion, color) VALUES (?, ?, ?, ?)");
        foreach ($categorias as $cat) {
            $stmt->execute($cat);
        }
        echo "✓ Categorías insertadas\n";
    }

    // Insertar departamentos
    $dept_stmt = $pdo->prepare("SELECT COUNT(*) FROM departamentos");
    $dept_stmt->execute();

    if ($dept_stmt->fetchColumn() == 0) {
        echo "→ Insertando departamentos...\n";
        $departamentos = [
            ['general', 'General', 'Departamento General'],
            ['atencion-primaria', 'Atención Primaria', 'Departamento de Atención Primaria de Salud'],
            ['atencion-secundaria', 'Atención Secundaria', 'Departamento de Atención Secundaria'],
            ['atencion-terciaria', 'Atención Terciaria', 'Departamento de Atención Terciaria'],
            ['cesfam', 'CESFAM', 'Centro de Salud Familiar'],
            ['cecosf', 'CECOSF', 'Centro Comunitario de Salud Familiar'],
            ['sapu', 'SAPU', 'Servicio de Atención Primaria de Urgencia'],
            ['postas-rurales', 'Postas Rurales', 'Postas de Salud Rural'],
            ['servicios', 'Servicios', 'Servicios Específicos de Salud'],
            ['administracion', 'Administración', 'Departamento de Administración']
        ];

        $stmt = $pdo->prepare("INSERT INTO departamentos (codigo, nombre, descripcion) VALUES (?, ?, ?)");
        foreach ($departamentos as $dept) {
            $stmt->execute($dept);
        }
        echo "✓ Departamentos insertados\n";
    }

    // Crear usuario administrador
    $user_stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'super_admin'");
    $user_stmt->execute();

    if ($user_stmt->fetchColumn() == 0) {
        echo "→ Creando usuario administrador...\n";
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuarios (username, nombre, apellido, email, password_hash, departamento_id, cargo, rol, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'Administrador',
            'Sistema',
            'admin@dashualpén.cl',
            $password_hash,
            1,
            'Administrador del Sistema',
            'super_admin',
            1
        ]);
        echo "✓ Usuario administrador creado (admin / admin123)\n";
    }

    // Crear índices
    echo "→ Creando índices...\n";
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_banco_categoria ON banco_preguntas(categoria_id)",
        "CREATE INDEX IF NOT EXISTS idx_banco_tipo ON banco_preguntas(tipo_pregunta_id)",
        "CREATE INDEX IF NOT EXISTS idx_banco_departamento ON banco_preguntas(departamento)",
        "CREATE INDEX IF NOT EXISTS idx_encuestas_estado ON encuestas(estado)",
        "CREATE INDEX IF NOT EXISTS idx_encuestas_departamento ON encuestas(departamento_id)",
        "CREATE INDEX IF NOT EXISTS idx_respuestas_encuesta ON respuestas_encuesta(encuesta_id, estado)"
    ];

    foreach ($indices as $indice) {
        $pdo->exec($indice);
    }
    echo "✓ Índices creados\n";

    // ========================================
    // INSERTAR BANCO DE PREGUNTAS COMPLETO
    // ========================================

    echo "\n=== INSERTANDO BANCO DE PREGUNTAS COMPLETO ===\n";

    // Limpiar banco de preguntas anterior si existe
    echo "→ Limpiando banco de preguntas anterior...\n";
    $pdo->exec("DELETE FROM banco_preguntas");

    // Insertar nuevas preguntas organizadas por categorías
    echo "→ Insertando preguntas por categoría...\n";

    // Preguntas Generales (Satisfacción)
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

    // Preguntas SAPU
    $preguntas_sapu = [
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

    // Preguntas Atención Terciaria
    $preguntas_terciaria = [
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

    // Preguntas Generales adicionales
    $preguntas_generales_extra = [
        "¿Cuál es su género?",
        "¿En qué rango de edad se encuentra?",
        "¿Cuál es su nivel de educación?",
        "¿Cuál es su situación laboral actual?",
        "¿En qué sector de Hualpén reside?",
        "¿Cuánto tiempo lleva viviendo en Hualpén?",
        "¿Con qué frecuencia visita los servicios de salud?",
        "¿Qué medio de transporte utiliza para llegar a los servicios de salud?",
        "¿Ha utilizado el servicio de telemedicina?",
        "¿Cómo se enteró de los servicios de salud disponibles?"
    ];

    // Insertar todas las preguntas
    $stmt = $pdo->prepare("INSERT INTO banco_preguntas (categoria_id, tipo_pregunta_id, texto, orden, departamento, created_by) VALUES (?, ?, ?, ?, ?, ?)");

    $total_insertadas = 0;

    // Categoría Satisfacción (ID: 1) - Preguntas Generales
    foreach ($preguntas_generales as $index => $pregunta) {
        $stmt->execute([1, 5, $pregunta, $index + 1, 'general', 'admin']); // tipo_pregunta_id = 5 (escala_likert)
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_generales) . " preguntas de satisfacción insertadas\n";

    // Categoría CESFAM (ID: 5)
    foreach ($preguntas_cesfam as $index => $pregunta) {
        $stmt->execute([5, 5, $pregunta, $index + 1, 'cesfam', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_cesfam) . " preguntas CESFAM insertadas\n";

    // Categoría CECOSF (ID: 6)
    foreach ($preguntas_cecosf as $index => $pregunta) {
        $stmt->execute([6, 5, $pregunta, $index + 1, 'cecosf', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_cecosf) . " preguntas CECOSF insertadas\n";

    // Categoría SAPU (ID: 7)
    foreach ($preguntas_sapu as $index => $pregunta) {
        $stmt->execute([7, 5, $pregunta, $index + 1, 'sapu', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_sapu) . " preguntas SAPU insertadas\n";

    // Categoría Atención Terciaria (ID: 4)
    foreach ($preguntas_terciaria as $index => $pregunta) {
        $stmt->execute([4, 5, $pregunta, $index + 1, 'urgencias', 'admin']);
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_terciaria) . " preguntas de urgencias insertadas\n";

    // Categoría General (ID: 10) - Preguntas demográficas
    foreach ($preguntas_generales_extra as $index => $pregunta) {
        $stmt->execute([10, 3, $pregunta, $index + 1, 'general', 'admin']); // tipo_pregunta_id = 3 (opcion_multiple)
        $total_insertadas++;
    }
    echo "✓ " . count($preguntas_generales_extra) . " preguntas demográficas insertadas\n";

    echo "\n=== CONFIGURACIÓN COMPLETA FINALIZADA ===\n";
    echo "Base de datos: das_encuestas\n";
    echo "Usuario admin: admin@dashualpén.cl / admin123\n";
    echo "Total de categorías: 10\n";
    echo "Total de preguntas: $total_insertadas\n";
    echo "Total de tipos de pregunta: 9\n";
    echo "Total de departamentos: 10\n";
    echo "\nEl sistema está listo para usar.\n";

} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
