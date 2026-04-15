<?php
/**
 * upload_sepe.php - Compatible PHP 8.0
 * Trunca y recarga las 5 tablas SEPE.
 */

require_once 'config.php';

ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

function buildConceptoColumnsSEPE() {
    $cols = [];
    for ($i = 1; $i <= 40; $i++) {
        $pad = str_pad($i, 2, '0', STR_PAD_LEFT);
        $cols[] = 'perc_ded_' . $pad;
        $cols[] = 'concepto'  . $pad;
        $cols[] = 'importe'   . $pad;
        $cols[] = 'qna_ini_'  . $pad;
        $cols[] = 'qna_fin_'  . $pad;
    }
    return $cols;
}

$sepe_cheque_columns = array_merge([
    'rfc','paterno','materno','nombre','num_emp','num_hab',
    'ramo','sector','programa','subprograma','dependencia',
    'unidad_res','proyecto','nombramiento','categoria',
    'digito_cat','cat_puesto','horas','tipo_nom','cons_plaza',
    'cat_pago','nivel_puesto','nivel_sueldo','mot_mov','ct',
    'tipo_nomina','grupo_nomina','qna_ini','qna_fin','qna_pago',
    'qna_proc','cons_qna_proc','num_cheque','cheque_dv',
    'tot_perc_cheque','tot_ded_cheque','tot_neto_cheque',
    'num_perc','num_desc'
], buildConceptoColumnsSEPE());

$tableConfig = [
    'cg_ct' => [
        'table'   => 'sepe_cg_ct',
        'fileKey' => 'cg_ct',
        'columns' => [
            'ct','nombre','domicilio','telefono','localidad',
            'municipio','colonia','cod_postal','zona_eco','estatus'
        ]
    ],
    'cheque_cpto' => [
        'table'   => 'sepe_cheque_cpto',
        'fileKey' => 'cheque_cpto',
        'columns' => $sepe_cheque_columns
    ],
    'empleado' => [
        'table'   => 'sepe_empleado',
        'fileKey' => 'empleado',
        'columns' => [
            'rfc','curp','paterno','materno','nombre','sexo',
            'edo_civil','niv_academico','domicilio','colonia',
            'localidad_emp','municipio','estado','telefono',
            'cod_postal','qna_ing_sep','qna_baja','act_inact',
            'email','cp_sat'
        ]
    ],
    'niv_desconcentrados' => [
        'table'   => 'sepe_niv_desconcentrados',
        'fileKey' => 'niv_desconcentrados',
        'columns' => [
            'rfc','curp','num_emp','num_hab','paterno','materno',
            'nombre','qna_ing_sep','ramo','sector','programa',
            'subprograma','dependencia','unidad_res','proyecto',
            'nombramiento','categoria','digito_cat','cat_puesto',
            'horas','tipo_nom','cons_plaza','cat_pago','nivel_puesto',
            'nivel_sueldo','mot_mov','ct','imp_gravado'
        ]
    ],
    'vemp_plaza' => [
        'table'   => 'sepe_vemp_plaza',
        'fileKey' => 'vemp_plaza',
        'columns' => [
            'rfc','num_emp','ramo','sector','programa','subprograma',
            'dependencia','unidad_res','proyecto','nombramiento',
            'categoria','digito_cat','cat_puesto','cat_pago','horas',
            'tipo_nom','cons_plaza','qna_ini','qna_fin','estatus_plaza',
            'qna_ing_plaza','mot_mov','nivel_sueldo','nivel_puesto',
            'cve_sindicato'
        ]
    ]
];

$conn    = getConnection();
$results = [];
$errors  = [];

foreach ($tableConfig as $key => $cfg) {
    $fileKey = $cfg['fileKey'];
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        $errors[$key] = "Archivo '$fileKey' no recibido o con error de subida.";
        continue;
    }
    $result = processTxtFile($conn, $cfg['table'], $cfg['columns'], $_FILES[$fileKey]['tmp_name'], $key);
    if ($result['success']) {
        $results[] = ['tabla' => $cfg['table'], 'filas' => $result['rows']];
    } else {
        $errors[$key] = $result['message'];
    }
}

closeConnection($conn);

if (empty($errors)) {
    echo json_encode(['success' => true, 'message' => 'Maestros SEPE cargados correctamente.', 'results' => $results]);
} else {
    echo json_encode(['success' => false, 'message' => implode(' | ', array_values($errors)), 'errors' => $errors, 'results' => $results]);
}

function processTxtFile($conn, $table, $columns, $filePath, $key) {
    $handle = @fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => "No se pudo abrir el archivo ($key)."];

    $delimiter = detectDelimiter($filePath);
    $colCount  = count($columns);

    if (!$conn->query("TRUNCATE TABLE `$table`")) {
        fclose($handle);
        return ['success' => false, 'message' => "Error al truncar $table: " . $conn->error];
    }

    $placeholders = implode(',', array_fill(0, $colCount, '?'));
    $colNames     = implode(',', array_map(function($c) { return "`$c`"; }, $columns));
    $stmt         = $conn->prepare("INSERT INTO `$table` ($colNames) VALUES ($placeholders)");

    if (!$stmt) {
        fclose($handle);
        return ['success' => false, 'message' => "Error prepare $table: " . $conn->error];
    }

    $types = str_repeat('s', $colCount);
    $rows  = 0;
    $conn->begin_transaction();

    try {
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if (trim($line) === '') continue;
            $fields = explode($delimiter, $line);
            while (count($fields) < $colCount) $fields[] = null;
            $fields = array_slice($fields, 0, $colCount);
            $fields = array_map(function($v) { return (trim($v ?? '') === '' ? null : trim($v)); }, $fields);
            $stmt->bind_param($types, ...$fields);
            $stmt->execute();
            $rows++;
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        fclose($handle);
        $stmt->close();
        return ['success' => false, 'message' => "Error insertando en $table: " . $e->getMessage()];
    }

    fclose($handle);
    $stmt->close();
    return ['success' => true, 'rows' => $rows];
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
