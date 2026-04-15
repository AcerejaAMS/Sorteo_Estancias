<?php
/**
 * upload_uset.php - Compatible PHP 8.0
 * Trunca y recarga las 4 tablas USET.
 */


header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

// Generar columnas de conceptos 01-40 
function buildConceptoColumns() {
    $cols = [];
    for ($i = 1; $i <= 40; $i++) {
        $pad = str_pad($i, 2, '0', STR_PAD_LEFT);
        $cols[] = 'perc_ded'  . $pad;
        $cols[] = 'concepto'  . $pad;
        $cols[] = 'importe'   . $pad;
        $cols[] = 'qna_ini_'  . $pad;
        $cols[] = 'qna_fin_'  . $pad;
    }
    return $cols;
}

$cheque_cpto_columns = array_merge([
    'u_version','num_cons','tipo_nomina','centro_computo',
    'unidad_dist_cheque','cons_qna_proc','rfc','nom_emp',
    'ent_fed','ct_clasif','ct_id','ct_secuencial','ct_digito_ver',
    'cod_pago','unidad','subunidad','cat_puesto','horas',
    'cons_plaza','niv_puesto','nivel_sueldo','grupo_nomina',
    'tipo_pago','mot_mov','tipo_calculo','qna_ini','qna_fin',
    'qna_pago','qna_proc','num_cheque','cheque_dv',
    'tot_perc_cheque','tot_ded_cheque','tot_neto_cheque',
    'num_perc','num_desc'
], buildConceptoColumns());

$tableConfig = [
    'centro_trabajo' => [
        'table'   => 'centro_trabajo',
        'fileKey' => 'centro_trabajo',
        'columns' => [
            'u_version','ent_fed','ct_clasif','ct_id','ct_secuencial',
            'ct_digito_ver','qna_fin','qna_ini','ct_dep_admva',
            'unidad_dist_cheque','ct_dep_norm','municipio','localidad',
            'ct_dom_calle','ct_dom_pob','ct_tel','anio_estr_prog',
            'ban_zbd','unidad_resp','nivel_ct_doc','zona_escolar',
            'centro_computo','sector','zona_eco','grupo_clasif',
            'sobre_sueldo','estatus_ct','descripcion'
        ]
    ],
    'cheque_cpto' => [
        'table'   => 'cheque_cpto',
        'fileKey' => 'cheque_cpto',
        'columns' => $cheque_cpto_columns
    ],
    'empleado' => [
        'table'   => 'empleado',
        'fileKey' => 'empleado',
        'columns' => [
            'u_version','rfc','nom_emp','sexo','edo_civil','edo_nac',
            'qna_ini','act_inact','ban_pago','mot_baja','qna_baja',
            'qna_ing_gob','qna_ing_sep','niv_academico','tipo_tit',
            'tipo_lic','num_tit','num_lic','emp_cta_sar',
            'acumulado_horas_42','acumulado_horas_48','emp_dom',
            'emp_dom_col','emp_dom_pob','emp_dom_cp','emp_cta_bancaria',
            'bco_admdor','bco_plaza'
        ]
    ],
    'empleado_plaza' => [
        'table'   => 'empleado_plaza',
        'fileKey' => 'empleado_plaza',
        'columns' => [
            'u_version','rfc','cod_pago','unidad','subunidad',
            'cat_puesto','horas','cons_plaza','qna_fin','qna_ini',
            'estatus_plaza','mot_mov','ban_pago','tipo_pago',
            'num_hrs_pago','hsr_compat','nivel_sueldo','ban_camb_plaza',
            'ban_plaza_fc','niv_puesto','niv_puesto_aux','qna_fin_ul_lic',
            'ban_pa','ban_cptoexec','ban_cpspro','ban_cptoemppza',
            'qna_ing_subsistema','modelo','subsis','subsubsis',
            'hrs_docente','zona_eco','sobre_sueldo','tipo_serv'
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
    echo json_encode(['success' => true, 'message' => 'Maestros USET cargados correctamente.', 'results' => $results]);
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
