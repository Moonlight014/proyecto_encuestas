<?php
//exportador de respuestas a Excel 

// Cargar autoload de Composer (intentar las rutas comunes)
$autoloadCandidates = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
];
$autoloadLoaded = false;
foreach ($autoloadCandidates as $path) {
    if (file_exists($path)) {
        require $path;
        $autoloadLoaded = true;
        break;
    }
}
// Si NO hay autoload, exportaremos CSV como alternativa sin dependencias
// Cuando autoload esté disponible, exportaremos XLSX con PhpSpreadsheet

require_once __DIR__ . '/config/conexion.php';

// Helper: aplanar un valor JSON a texto legible
function aplanarValor($jsonValue) {
    if ($jsonValue === null) return '';
    $decoded = json_decode($jsonValue, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // No era JSON válido, devolver tal cual
        return (string)$jsonValue;
    }
    return formatearValor($decoded);
}

function formatearValor($val) {
    if (is_null($val)) return '';
    if (is_scalar($val)) return (string)$val;
    if (is_array($val)) {
        // Lista indexada
        if (array_keys($val) === range(0, count($val) - 1)) {
            return implode(', ', array_map(function ($v) { return is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE); }, $val));
        }
        // Mapa asociativo
        $pairs = [];
        foreach ($val as $k => $v) {
            $pairs[] = $k . ': ' . (is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE));
        }
        return implode(' | ', $pairs);
    }
    // Fallback
    return json_encode($val, JSON_UNESCAPED_UNICODE);
}

// Obtener parámetro de encuesta: acepta id numérico o enlace_publico (string)
$param = $_GET['id'] ?? $_GET['encuesta_id'] ?? '';
if ($param === '') {
    http_response_code(400);
    echo 'Parámetro id o encuesta_id requerido.';
    exit;
}

try {
    $pdo = obtenerConexion();

    // Resolver encuesta_id
    if (ctype_digit((string)$param)) {
        $encuestaId = (int)$param;
    } else {
        $stmt = $pdo->prepare("SELECT id FROM encuestas WHERE enlace_publico = ?");
        $stmt->execute([$param]);
        $encuestaId = (int)($stmt->fetchColumn() ?: 0);
    }

    if ($encuestaId <= 0) {
        http_response_code(404);
        echo 'Encuesta no encontrada.';
        exit;
    }

    // Obtener preguntas de la encuesta (ordenadas)
    $stmt = $pdo->prepare("SELECT bp.id AS pregunta_id, bp.texto, tp.nombre AS tipo_nombre, ep.orden
                           FROM encuesta_preguntas ep
                           JOIN banco_preguntas bp ON ep.pregunta_id = bp.id
                           LEFT JOIN tipos_pregunta tp ON bp.tipo_pregunta_id = tp.id
                           WHERE ep.encuesta_id = ? AND ep.activa = 1
                           ORDER BY ep.orden");
    $stmt->execute([$encuestaId]);
    $preguntas = $stmt->fetchAll();

    // Obtener respuestas completadas
    $stmt = $pdo->prepare("SELECT id AS respuesta_encuesta_id, sesion_token, fecha_completada
                           FROM respuestas_encuesta
                           WHERE encuesta_id = ? AND estado = 'completada'
                           ORDER BY fecha_completada ASC, id ASC");
    $stmt->execute([$encuestaId]);
    $respuestas = $stmt->fetchAll();

    if ($autoloadLoaded) {
        // Exportar en formato XLSX usando PhpSpreadsheet
        $SpreadsheetClass = '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet';
        $XlsxWriterClass  = '\\PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx';

        $spreadsheet = new $SpreadsheetClass();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Respuestas');
        // Dar formato a la hoja
        // Encabezados como en el script original (3 columnas)
        $sheet->setCellValue('A1', 'Encuesta Respondida'); // ID o token de respuesta
        $sheet->setCellValue('B1', 'Pregunta');
        $sheet->setCellValue('C1', 'Respuestas');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C1')->getAlignment()->setHorizontal('left');

        $row = 2;
        $prevRespuestaId = null;

        if (!empty($respuestas) && !empty($preguntas)) {
            // Precompilar statement para respuestas detalle
            $stmtDetalle = $pdo->prepare("SELECT pregunta_id, valor_respuesta
                                          FROM respuestas_detalle
                                          WHERE respuesta_encuesta_id = ?");

            foreach ($respuestas as $resp) {
                $respuestaId = (int)$resp['respuesta_encuesta_id'];
                $token = $resp['sesion_token'];

                // Cargar detalle de esta respuesta en mapa
                $stmtDetalle->execute([$respuestaId]);
                $detalles = $stmtDetalle->fetchAll();
                $map = [];
                foreach ($detalles as $d) {
                    $map[(int)$d['pregunta_id']] = aplanarValor($d['valor_respuesta']);
                }

                // Escribir fila por pregunta
                foreach ($preguntas as $p) {
                    $pid = (int)$p['pregunta_id'];
                    $texto = $p['texto'];
                    $valor = $map[$pid] ?? '';

                    $sheet->setCellValue('A' . $row, $token ?: $respuestaId);
                    $sheet->setCellValue('B' . $row, $texto);
                    $sheet->setCellValue('C' . $row, $valor);

                    // Estilos por fila
                    $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => 'thin',
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal('left');
                    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal('left');

                    // Borde grueso para inicio de grupo como separación de encuestas respondidas.
                    if ($prevRespuestaId !== $respuestaId) {
                        $sheet->getStyle('A' . $row . ':C' . $row)
                              ->getBorders()->getOutline()->setBorderStyle('thick');
                        $prevRespuestaId = $respuestaId;
                    }

                    $row++;
                }
            }
        } else {
            // Si la encuesta está sin datos
            $sheet->setCellValue('A2', 'Sin respuestas para esta encuesta');
            $sheet->mergeCells('A2:C2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        }

        // Autoajustar columnas
        foreach (['A','B','C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Enviar archivo XLSX
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="respuestas_encuesta_' . $encuestaId . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new $XlsxWriterClass($spreadsheet);
        $writer->save('php://output');
        exit;
    } else {
        // Fallback: exportar CSV sin dependencias
        $out = fopen('php://temp', 'w+');
        // Encabezados
        fputcsv($out, ['Encuesta Respondida', 'Pregunta', 'Respuestas']);

        if (!empty($respuestas) && !empty($preguntas)) {
            $stmtDetalle = $pdo->prepare("SELECT pregunta_id, valor_respuesta
                                          FROM respuestas_detalle
                                          WHERE respuesta_encuesta_id = ?");

            foreach ($respuestas as $resp) {
                $respuestaId = (int)$resp['respuesta_encuesta_id'];
                $token = $resp['sesion_token'];

                $stmtDetalle->execute([$respuestaId]);
                $detalles = $stmtDetalle->fetchAll();
                $map = [];
                foreach ($detalles as $d) {
                    $map[(int)$d['pregunta_id']] = aplanarValor($d['valor_respuesta']);
                }

                foreach ($preguntas as $p) {
                    $pid = (int)$p['pregunta_id'];
                    $texto = $p['texto'];
                    $valor = $map[$pid] ?? '';
                    fputcsv($out, [$token ?: $respuestaId, $texto, $valor]);
                }
            }
        } else {
            fputcsv($out, ['Sin respuestas para esta encuesta']);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="respuestas_encuesta_' . $encuestaId . '.csv"');
        header('Cache-Control: max-age=0');
        // Agregar BOM UTF-8 para mejor compatibilidad con Excel
        echo "\xEF\xBB\xBF" . $csv;
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error al generar el Excel: ' . htmlspecialchars($e->getMessage());
    exit;
}
