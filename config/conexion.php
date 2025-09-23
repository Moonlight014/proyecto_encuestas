<?php
/**
 * Archivo de configuración centralizada de base de datos
 * Sistema de Encuestas DAS Hualpén
 */

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'das_encuestas';
$username = 'DAS';
$password = 'das1324.';
$port = 3306;
$charset = 'utf8mb4';

/**
 * Función para obtener la conexión a la base de datos
 * @return PDO
 * @throws PDOException
 */
function obtenerConexion() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            // Forzar zona horaria del servidor PHP a GMT-3 (America/Santiago)
            // Usa America/Argentina/Buenos_Aires u otra equivalente si prefieres fijo GMT-3.
            if (function_exists('date_default_timezone_set')) {
                @date_default_timezone_set('America/Santiago');
            }
            $dsn = "mysql:host={$GLOBALS['host']};port={$GLOBALS['port']};dbname={$GLOBALS['dbname']};charset={$GLOBALS['charset']}";
            $pdo = new PDO($dsn, $GLOBALS['username'], $GLOBALS['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Alinear zona horaria de la sesión MySQL a GMT-3 para que NOW() y timestamps coincidan
            try {
                $stmtTz = $pdo->query("SET time_zone = '-03:00'");
                // opcional: $stmtTz->closeCursor();
            } catch (PDOException $tzEx) {
                // Silencioso si el servidor no soporta cambiar zona horaria
            }
        } catch(PDOException $e) {
            throw new PDOException("Error de conexión a la base de datos (Código " . $e->getCode() . "): " . $e->getMessage(), (int)$e->getCode());
        }
    }

    return $pdo;
}

/**
 * Función simplificada para ejecutar consultas SELECT
 * @param string $sql
 * @param array $params
 * @return array
 */
function consultar($sql, $params = []) {
    try {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Puedes registrar el error aquí si lo deseas
        return [];
    }
}

/**
 * Función simplificada para ejecutar consultas INSERT, UPDATE, DELETE
 * @param string $sql
 * @param array $params
 * @return int Número de filas afectadas
 */
function ejecutar($sql, $params = []) {
    $pdo = obtenerConexion();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Función para obtener el último ID insertado
 * @return string
 */
function ultimoId() {
    return obtenerConexion()->lastInsertId();
}
?>
