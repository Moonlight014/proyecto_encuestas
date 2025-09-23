<?php
// Script de configuración actualizado para DAS Hualpén
// Incluye el banco de preguntas completo

require_once 'conexion.php';

try {
    $pdo = obtenerConexion();
    
    echo "=== CONFIGURANDO SISTEMA DE ENCUESTAS DAS HUALPEN ===\n\n";
    
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
    
    echo "\n=== CONFIGURACIÓN INICIAL COMPLETADA ===\n";
    echo "Base de datos: das_encuestas\n";
    echo "Usuario admin: admin@dashualpén.cl / admin123\n";
    echo "\nAhora puedes ejecutar el script de inserción del banco de preguntas.\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
