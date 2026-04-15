<?php
/**
 * upload_autos.php
 * Recibe 1 archivo .txt/.csv con la lista de ganadores de autos.
 * Trunca la tabla `ganadores_autos` y recarga los datos.
 */

require_once 'config.php';

ini_set('max_execution_time', 120);
ini_set('memory_limit', '256M');


$table   = 'ganadores_autoaños';
$fileKey = 'ganadores';
$columns = [
    'rfc',
    'nombre',
    'anio',

];

// ─────────────────────────────────────────────────────────────
//  VALIDAR ARCHIVO
// ─────────────────────────────────────────────────────────────
if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => "Archivo '$fileKey' no recibido o con error de subida."
    ]);
    exit;
}

$tmpPath = $_FILES[$fileKey]['tmp_name'];
$conn    = getConnection();
$result  = processTxtFile($conn, $table, $columns, $tmpPath);
closeConnection($conn);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => "Ganadores de Autos cargados correctamente.",
        'results' => [['tabla' => $table, 'filas' => $result['rows']]]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}

// ─────────────────────────────────────────────────────────────
//  FUNCIÓN: truncar + insertar
// ─────────────────────────────────────────────────────────────
function processTxtFile($conn, $table, $columns, $filePath) {
    $handle = @fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo abrir el archivo.'];

    $delimiter = detectDelimiter($filePath);
    $colCount  = count($columns);

    if (!$conn->query("TRUNCATE TABLE `$table`")) {
        fclose($handle);
        return ['success' => false, 'message' => "Error al truncar $table: " . $conn->error];
    }

    $placeholders = implode(',', array_fill(0, $colCount, '?'));
    $colNames     = implode(',', array_map(fn($c) => "`$c`", $columns));
    $sql          = "INSERT INTO `$table` ($colNames) VALUES ($placeholders)";
    $stmt         = $conn->prepare($sql);

    if (!$stmt) {
        fclose($handle);
        return ['success' => false, 'message' => "Error prepare: " . $conn->error];
    }

    $types = str_repeat('s', $colCount);
    $rows  = 0;
    $conn->begin_transaction();

    try {
        // Saltar primera línea si parece encabezado
        $firstLine = fgets($handle);
        if (!isHeaderLine($firstLine, $delimiter)) {
            // No era encabezado, procesar la línea
            $line   = rtrim($firstLine, "\r\n");
            $fields = normalizeFields(explode($delimiter, $line), $colCount);
            $stmt->bind_param($types, ...$fields);
            $stmt->execute();
            $rows++;
        }

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if (trim($line) === '') continue;
            $fields = normalizeFields(explode($delimiter, $line), $colCount);
            $stmt->bind_param($types, ...$fields);
            $stmt->execute();
            $rows++;
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        fclose($handle);
        $stmt->close();
        return ['success' => false, 'message' => "Error insertando: " . $e->getMessage()];
    }

    fclose($handle);
    $stmt->close();
    return ['success' => true, 'rows' => $rows];
}

function normalizeFields(array $fields, int $colCount): array {
    while (count($fields) < $colCount) $fields[] = null;
    $fields = array_slice($fields, 0, $colCount);
    return array_map(fn($v) => (trim($v ?? '') === '' ? null : trim($v)), $fields);
}

function isHeaderLine(string $line, string $delimiter): bool {
    $fields = explode($delimiter, $line);
    foreach ($fields as $f) {
        if (is_numeric(trim($f))) return false;
    }
    return true;
}

function detectDelimiter($filePath) {
    $candidates = ["\t", '|', ';', ','];
    $counts     = array_fill_keys($candidates, 0);
    $handle     = fopen($filePath, 'r');
    $checked    = 0;
    while (($line = fgets($handle)) !== false && $checked < 5) {
        if (trim($line) === '') continue;
        foreach ($candidates as $d) $counts[$d] += substr_count($line, $d);
        $checked++;
    }
    fclose($handle);
    arsort($counts);
    $best = array_key_first($counts);
    return ($counts[$best] > 0) ? $best : "\t";
}
