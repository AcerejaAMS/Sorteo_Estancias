<?php
// upload.php
require_once 'config.php';

// Mapeo de tablas por sección y paso
$tableMap = [
    'uset' => [
        0 => 'centro_trabajo',
        1 => 'cheque_cpto',
        2 => 'empleado',
        3 => 'empleado_plaza'
    ],
    'sepe' => [
        0 => 'sepe_cg_ct',
        1 => 'sepe_cheque_cpto',
        2 => 'sepe_empleado',
        3 => 'sepe_niv_desconcentrados',
        4 => 'sepe_vemp_plaza'
    ]
];

// Validar request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$section = $_POST['section'] ?? '';
$step = intval($_POST['step'] ?? -1);

if (!isset($tableMap[$section]) || !isset($tableMap[$section][$step])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sección o paso inválido']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error al subir archivo: ' . ($_FILES['file']['error'] ?? 'No file')]);
    exit;
}

$file = $_FILES['file'];
$table = $tableMap[$section][$step];

// Validar extensión
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'txt') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos .txt']);
    exit;
}

// Procesar según la tabla
$conn = getConnection();
$result = ['success' => false, 'message' => ''];

try {
    switch ($table) {
        case 'centro_trabajo':
            $result = procesarCentroTrabajo($conn, $file['tmp_name']);
            break;
        case 'cheque_cpto':
            $result = procesarChequeCpto($conn, $file['tmp_name']);
            break;
        case 'empleado':
            $result = procesarEmpleado($conn, $file['tmp_name']);
            break;
        case 'empleado_plaza':
            $result = procesarEmpleadoPlaza($conn, $file['tmp_name']);
            break;
        case 'sepe_cg_ct':
            $result = procesarSepeCgCt($conn, $file['tmp_name']);
            break;
        case 'sepe_cheque_cpto':
            $result = procesarSepeChequeCpto($conn, $file['tmp_name']);
            break;
        case 'sepe_empleado':
            $result = procesarSepeEmpleado($conn, $file['tmp_name']);
            break;
        case 'sepe_niv_desconcentrados':
            $result = procesarSepeNivDesconcentrados($conn, $file['tmp_name']);
            break;
        case 'sepe_vemp_plaza':
            $result = procesarSepeVempPlaza($conn, $file['tmp_name']);
            break;
        default:
            throw new Exception('Tabla no configurada');
    }
} catch (Exception $e) {
    $result = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

closeConnection($conn);
echo json_encode($result);

// ============================================
// USET - CENTRO DE TRABAJO (30 campos)
// ============================================
function procesarCentroTrabajo($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE centro_trabajo");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $sql = "INSERT INTO centro_trabajo (
        u_version, ent_fed, ct_clasif, ct_id, ct_secuencial, ct_digito_ver,
        qna_fin, qna_ini, ct_dep_admva, unidad_dist_cheque, ct_dep_norm,
        municipio, localidad, ct_dom_calle, ct_dom_pob, ct_tel,
        anio_estr_prog, ban_zbd, unidad_resp, nivel_ct_doc, zona_escolar,
        centro_computo, sector, zona_eco, grupo_clasif, sobre_sueldo,
        estatus_ct, descripcion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 30) {
            $errors[] = "Línea $lineNum: se esperaban 30 campos, se recibieron " . count($data);
            continue;
        }
        
        // Convertir tipos de datos según la estructura
        $params = [
            $data[0],   // u_version (char(1))
            intval($data[1]),   // ent_fed (smallint)
            $data[2],   // ct_clasif (char(1))
            $data[3],   // ct_id (char(2))
            intval($data[4]),   // ct_secuencial (smallint)
            $data[5],   // ct_digito_ver (char(1))
            intval($data[6]),   // qna_fin (int)
            intval($data[7]),   // qna_ini (int)
            intval($data[8]),   // ct_dep_admva (smallint)
            intval($data[9]),   // unidad_dist_cheque (smallint)
            $data[10],  // ct_dep_norm (char(2))
            intval($data[11]),  // municipio (smallint)
            intval($data[12]),  // localidad (smallint)
            $data[13],  // ct_dom_calle (char(50))
            $data[14],  // ct_dom_pob (char(50))
            $data[15],  // ct_tel (char(10))
            intval($data[16]),  // anio_estr_prog (smallint)
            intval($data[17]),  // ban_zbd (smallint)
            $data[18],  // unidad_resp (char(3))
            $data[19],  // nivel_ct_doc (char(1))
            intval($data[20]),  // zona_escolar (smallint)
            intval($data[21]),  // centro_computo (smallint)
            intval($data[22]),  // sector (smallint)
            $data[23],  // zona_eco (char(1))
            intval($data[24]),  // grupo_clasif (smallint)
            floatval($data[25]), // sobre_sueldo (double)
            intval($data[26]),  // estatus_ct (smallint)
            $data[27]   // descripcion (char(60))
        ];
        
        $types = "sissssiissssiisssiissiiiisidsis";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Centros de trabajo cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// USET - CHEQUE CONCEPTO (213 campos)
// ============================================
function procesarChequeCpto($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE cheque_cpto");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    // Construir query dinámicamente para 213 campos
    $campos = [
        'u_version', 'num_cons', 'tipo_nomina', 'centro_computo', 'unidad_dist_cheque',
        'cons_qna_proc', 'rfc', 'nom_emp', 'ent_fed', 'ct_clasif', 'ct_id', 'ct_secuencial',
        'ct_digito_ver', 'cod_pago', 'unidad', 'subunidad', 'cat_puesto', 'horas', 'cons_plaza',
        'niv_puesto', 'nivel_sueldo', 'grupo_nomina', 'tipo_pago', 'mot_mov', 'tipo_calculo',
        'qna_ini', 'qna_fin', 'qna_pago', 'qna_proc', 'num_cheque', 'cheque_dv',
        'tot_perc_cheque', 'tot_ded_cheque', 'tot_neto_cheque', 'num_perc', 'num_desc'
    ];
    
    // Agregar campos de conceptos 01-40 (5 campos cada uno = 200 campos)
    for ($i = 1; $i <= 40; $i++) {
        $num = str_pad($i, 2, '0', STR_PAD_LEFT);
        $campos[] = "perc_ded$num";
        $campos[] = "concepto$num";
        $campos[] = "importe$num";
        $campos[] = "qna_ini_$num";
        $campos[] = "qna_fin_$num";
    }
    
    $sql = "INSERT INTO cheque_cpto (" . implode(', ', $campos) . ") VALUES (" . str_repeat('?,', 212) . "?)";
    $stmt = $conn->prepare($sql);
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 213) {
            $errors[] = "Línea $lineNum: se esperaban 213 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [];
        $types = "";
        
        // Primeros 36 campos
        $params[] = $data[0]; $types .= "s"; // u_version
        $params[] = intval($data[1]); $types .= "i"; // num_cons
        $params[] = $data[2]; $types .= "s"; // tipo_nomina
        $params[] = intval($data[3]); $types .= "i"; // centro_computo
        $params[] = intval($data[4]); $types .= "i"; // unidad_dist_cheque
        $params[] = intval($data[5]); $types .= "i"; // cons_qna_proc
        $params[] = $data[6]; $types .= "s"; // rfc
        $params[] = $data[7]; $types .= "s"; // nom_emp
        $params[] = intval($data[8]); $types .= "i"; // ent_fed
        $params[] = $data[9]; $types .= "s"; // ct_clasif
        $params[] = $data[10]; $types .= "s"; // ct_id
        $params[] = intval($data[11]); $types .= "i"; // ct_secuencial
        $params[] = $data[12]; $types .= "s"; // ct_digito_ver
        $params[] = intval($data[13]); $types .= "i"; // cod_pago
        $params[] = intval($data[14]); $types .= "i"; // unidad
        $params[] = intval($data[15]); $types .= "i"; // subunidad
        $params[] = $data[16]; $types .= "s"; // cat_puesto
        $params[] = floatval($data[17]); $types .= "d"; // horas
        $params[] = intval($data[18]); $types .= "i"; // cons_plaza
        $params[] = $data[19]; $types .= "s"; // niv_puesto
        $params[] = intval($data[20]); $types .= "i"; // nivel_sueldo
        $params[] = intval($data[21]); $types .= "i"; // grupo_nomina
        $params[] = intval($data[22]); $types .= "i"; // tipo_pago
        $params[] = intval($data[23]); $types .= "i"; // mot_mov
        $params[] = $data[24]; $types .= "s"; // tipo_calculo
        $params[] = intval($data[25]); $types .= "i"; // qna_ini
        $params[] = intval($data[26]); $types .= "i"; // qna_fin
        $params[] = intval($data[27]); $types .= "i"; // qna_pago
        $params[] = intval($data[28]); $types .= "i"; // qna_proc
        $params[] = intval($data[29]); $types .= "i"; // num_cheque
        $params[] = $data[30]; $types .= "s"; // cheque_dv
        $params[] = floatval($data[31]); $types .= "d"; // tot_perc_cheque
        $params[] = floatval($data[32]); $types .= "d"; // tot_ded_cheque
        $params[] = floatval($data[33]); $types .= "d"; // tot_neto_cheque
        $params[] = intval($data[34]); $types .= "i"; // num_perc
        $params[] = intval($data[35]); $types .= "i"; // num_desc
        
        // Campos de conceptos 36-213
        for ($i = 36; $i < 213; $i++) {
            if ($i % 5 == 1 || $i % 5 == 2) { // perc_ded y concepto (char)
                $params[] = $data[$i];
                $types .= "s";
            } else { // importe (decimal) o qnas (int)
                if ($i % 5 == 3) { // importe
                    $params[] = floatval($data[$i]);
                    $types .= "d";
                } else { // qnas
                    $params[] = intval($data[$i]);
                    $types .= "i";
                }
            }
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Cheques concepto cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// USET - EMPLEADO (27 campos)
// ============================================
function procesarEmpleado($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE empleado");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $stmt = $conn->prepare("INSERT INTO empleado (
        u_version, rfc, nom_emp, sexo, edo_civil, edo_nac, qna_ini, act_inact,
        ban_pago, mot_baja, qna_baja, qna_ing_gob, qna_ing_sep, niv_academico,
        tipo_tit, tipo_lic, num_tit, num_lic, emp_cta_sar, acumulado_horas_42,
        acumulado_horas_48, emp_dom, emp_dom_col, emp_dom_pob, emp_dom_cp,
        emp_cta_bancaria, bco_admdor, bco_plaza
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 28) {
            $errors[] = "Línea $lineNum: se esperaban 28 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [
            $data[0],   // u_version
            $data[1],   // rfc
            $data[2],   // nom_emp
            $data[3],   // sexo
            intval($data[4]),   // edo_civil
            intval($data[5]),   // edo_nac
            intval($data[6]),   // qna_ini
            $data[7],   // act_inact
            intval($data[8]),   // ban_pago
            intval($data[9]),   // mot_baja
            intval($data[10]),  // qna_baja
            intval($data[11]),  // qna_ing_gob
            intval($data[12]),  // qna_ing_sep
            $data[13],  // niv_academico
            intval($data[14]),  // tipo_tit
            intval($data[15]),  // tipo_lic
            intval($data[16]),  // num_tit
            intval($data[17]),  // num_lic
            $data[18],  // emp_cta_sar
            floatval($data[19]), // acumulado_horas_42
            floatval($data[20]), // acumulado_horas_48
            $data[21],  // emp_dom
            $data[22],  // emp_dom_col
            $data[23],  // emp_dom_pob
            $data[24],  // emp_dom_cp
            $data[25],  // emp_cta_bancaria
            intval($data[26]),  // bco_admdor
            intval($data[27])   // bco_plaza
        ];
        
        $types = "sssssiisiiiiiiisiiissdssssssii";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Empleados cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// USET - EMPLEADO PLAZA (32 campos)
// ============================================
function procesarEmpleadoPlaza($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE empleado_plaza");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $stmt = $conn->prepare("INSERT INTO empleado_plaza (
        u_version, rfc, cod_pago, unidad, subunidad, cat_puesto, horas, cons_plaza,
        qna_fin, qna_ini, estatus_plaza, mot_mov, ban_pago, tipo_pago, num_hrs_pago,
        hsr_compat, nivel_sueldo, ban_camb_plaza, ban_plaza_fc, niv_puesto, niv_puesto_aux,
        qna_fin_ul_lic, ban_pa, ban_cptoexec, ban_cpspro, ban_cptoemppza, qna_ing_subsistema,
        modelo, subsis, subsubsis, hrs_docente, zona_eco, sobre_sueldo, tipo_serv
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 34) {
            $errors[] = "Línea $lineNum: se esperaban 34 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [
            $data[0],   // u_version
            $data[1],   // rfc
            intval($data[2]),   // cod_pago
            intval($data[3]),   // unidad
            intval($data[4]),   // subunidad
            $data[5],   // cat_puesto
            floatval($data[6]), // horas
            intval($data[7]),   // cons_plaza
            intval($data[8]),   // qna_fin
            intval($data[9]),   // qna_ini
            intval($data[10]),  // estatus_plaza
            intval($data[11]),  // mot_mov
            intval($data[12]),  // ban_pago
            intval($data[13]),  // tipo_pago
            floatval($data[14]), // num_hrs_pago
            floatval($data[15]), // hsr_compat
            intval($data[16]),  // nivel_sueldo
            intval($data[17]),  // ban_camb_plaza
            intval($data[18]),  // ban_plaza_fc
            $data[19],  // niv_puesto
            $data[20],  // niv_puesto_aux
            intval($data[21]),  // qna_fin_ul_lic
            intval($data[22]),  // ban_pa
            intval($data[23]),  // ban_cptoexec
            intval($data[24]),  // ban_cpspro
            intval($data[25]),  // ban_cptoemppza
            intval($data[26]),  // qna_ing_subsistema
            intval($data[27]),  // modelo
            intval($data[28]),  // subsis
            intval($data[29]),  // subsubsis
            floatval($data[30]), // hrs_docente
            $data[31],  // zona_eco
            $data[32],  // sobre_sueldo
            $data[33]   // tipo_serv
        ];
        
        $types = "ssiiisidiisiiiiddiisissiiiiiiiidsss";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Empleados plaza cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// SEPE - CG CT (10 campos)
// ============================================
function procesarSepeCgCt($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE sepe_cg_ct");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $stmt = $conn->prepare("INSERT INTO sepe_cg_ct (
        ct, nombre, domicilio, telefono, localidad, municipio, colonia, cod_postal, zona_eco, estatus
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 10) {
            $errors[] = "Línea $lineNum: se esperaban 10 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [
            $data[0],   // ct (char(10))
            $data[1],   // nombre (char(60))
            $data[2],   // domicilio (char(40))
            $data[3],   // telefono (char(18))
            intval($data[4]),   // localidad (tinyint)
            intval($data[5]),   // municipio (tinyint)
            $data[6],   // colonia (char(40))
            intval($data[7]),   // cod_postal (int)
            intval($data[8]),   // zona_eco (tinyint)
            intval($data[9])    // estatus (tinyint)
        ];
        
        $types = "ssssiisisi";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Catálogo SEPE cargado: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// SEPE - CHEQUE CONCEPTO (173 campos)
// ============================================
function procesarSepeChequeCpto($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE sepe_cheque_cpto");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    // Construir campos dinámicamente
    $campos = [
        'num_cons', 'rfc', 'paterno', 'materno', 'nombre', 'num_emp', 'num_hab',
        'ramo', 'sector', 'programa', 'subprograma', 'dependencia', 'unidad_res',
        'proyecto', 'nombramiento', 'categoria', 'digito_cat', 'cat_puesto', 'horas',
        'tipo_nom', 'cons_plaza', 'cat_pago', 'nivel_puesto', 'nivel_sueldo', 'mot_mov',
        'ct', 'tipo_nomina', 'grupo_nomina', 'qna_ini', 'qna_fin', 'qna_pago',
        'qna_proc', 'cons_qna_proc', 'num_cheque', 'cheque_dv', 'tot_perc_cheque',
        'tot_ded_cheque', 'tot_neto_cheque', 'num_perc', 'num_desc'
    ];
    
    // Conceptos 01-40 (4 campos cada uno, sin perc_ded separado como en USET)
    for ($i = 1; $i <= 40; $i++) {
        $num = str_pad($i, 2, '0', STR_PAD_LEFT);
        $campos[] = "perc_ded_$num";
        $campos[] = "concepto$num";
        $campos[] = "importe$num";
        $campos[] = "qna_ini_$num";
        $campos[] = "qna_fin_$num";
    }
    
    $sql = "INSERT INTO sepe_cheque_cpto (" . implode(', ', $campos) . ") VALUES (" . str_repeat('?,', 172) . "?)";
    $stmt = $conn->prepare($sql);
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 173) {
            $errors[] = "Línea $lineNum: se esperaban 173 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [];
        $types = "";
        
        // Primeros 40 campos
        $mapping = [
            [intval($data[0]), "i"], // num_cons
            [$data[1], "s"], // rfc
            [$data[2], "s"], // paterno
            [$data[3], "s"], // materno
            [$data[4], "s"], // nombre
            [floatval($data[5]), "d"], // num_emp (bigint)
            [intval($data[6]), "i"], // num_hab
            [$data[7], "s"], // ramo
            [$data[8], "s"], // sector
            [$data[9], "s"], // programa
            [$data[10], "s"], // subprograma
            [$data[11], "s"], // dependencia
            [intval($data[12]), "i"], // unidad_res
            [$data[13], "s"], // proyecto (text)
            [$data[14], "s"], // nombramiento
            [intval($data[15]), "i"], // categoria
            [intval($data[16]), "i"], // digito_cat
            [intval($data[17]), "i"], // cat_puesto
            [intval($data[18]), "i"], // horas
            [intval($data[19]), "i"], // tipo_nom
            [intval($data[20]), "i"], // cons_plaza
            [intval($data[21]), "i"], // cat_pago
            [$data[22], "s"], // nivel_puesto
            [intval($data[23]), "i"], // nivel_sueldo
            [intval($data[24]), "i"], // mot_mov
            [$data[25], "s"], // ct
            [$data[26], "s"], // tipo_nomina
            [floatval($data[27]), "d"], // grupo_nomina (bigint)
            [intval($data[28]), "i"], // qna_ini
            [intval($data[29]), "i"], // qna_fin
            [intval($data[30]), "i"], // qna_pago
            [intval($data[31]), "i"], // qna_proc
            [floatval($data[32]), "d"], // cons_qna_proc (bigint)
            [$data[33], "s"], // num_cheque (text)
            [intval($data[34]), "i"], // cheque_dv
            [floatval($data[35]), "d"], // tot_perc_cheque
            [floatval($data[36]), "d"], // tot_ded_cheque
            [floatval($data[37]), "d"], // tot_neto_cheque
            [intval($data[38]), "i"], // num_perc
            [intval($data[39]), "i"], // num_desc
        ];
        
        foreach ($mapping as $m) {
            $params[] = $m[0];
            $types .= $m[1];
        }
        
        // Campos de conceptos 40-173
        for ($i = 40; $i < 173; $i++) {
            $mod = ($i - 40) % 5;
            if ($mod == 0 || $mod == 1 || $mod == 2) { // perc_ded, concepto (varchar)
                $params[] = $data[$i];
                $types .= "s";
            } elseif ($mod == 3) { // importe (decimal)
                $params[] = floatval($data[$i]);
                $types .= "d";
            } else { // qna_ini, qna_fin (int)
                $params[] = intval($data[$i]);
                $types .= "i";
            }
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Cheques SEPE cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// SEPE - EMPLEADO (20 campos)
// ============================================
function procesarSepeEmpleado($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE sepe_empleado");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $stmt = $conn->prepare("INSERT INTO sepe_empleado (
        rfc, curp, paterno, materno, nombre, sexo, edo_civil, niv_academico,
        domicilio, colonia, localidad_emp, municipio, estado, telefono, cod_postal,
        qna_ing_sep, qna_baja, act_inact, email, cp_sat
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 20) {
            $errors[] = "Línea $lineNum: se esperaban 20 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [
            $data[0],   // rfc
            $data[1],   // curp
            $data[2],   // paterno
            $data[3],   // materno
            $data[4],   // nombre
            $data[5],   // sexo
            intval($data[6]),   // edo_civil
            intval($data[7]),   // niv_academico
            $data[8],   // domicilio
            $data[9],   // colonia
            $data[10],  // localidad_emp
            $data[11],  // municipio
            intval($data[12]),  // estado
            $data[13],  // telefono
            intval($data[14]),  // cod_postal
            intval($data[15]),  // qna_ing_sep
            intval($data[16]),  // qna_baja
            $data[17],  // act_inact
            $data[18],  // email
            intval($data[19])   // cp_sat
        ];
        
        $types = "ssssssisssssisiiiissi";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Empleados SEPE cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// SEPE - NIVEL DESCONCENTRADOS (28 campos)
// ============================================
function procesarSepeNivDesconcentrados($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE sepe_niv_desconcentrados");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $stmt = $conn->prepare("INSERT INTO sepe_niv_desconcentrados (
        rfc, curp, num_emp, num_hab, paterno, materno, nombre, qna_ing_sep,
        ramo, sector, programa, subprograma, dependencia, unidad_res, proyecto,
        nombramiento, categoria, digito_cat, cat_puesto, horas, tipo_nom,
        cons_plaza, cat_pago, nivel_puesto, nivel_sueldo, mot_mov, ct, imp_gravado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 28) {
            $errors[] = "Línea $lineNum: se esperaban 28 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [
            $data[0],   // rfc
            $data[1],   // curp
            intval($data[2]),   // num_emp
            intval($data[3]),   // num_hab
            $data[4],   // paterno
            $data[5],   // materno
            $data[6],   // nombre
            intval($data[7]),   // qna_ing_sep
            $data[8],   // ramo
            $data[9],   // sector
            $data[10],  // programa
            $data[11],  // subprograma
            $data[12],  // dependencia
            intval($data[13]),  // unidad_res
            $data[14],  // proyecto
            $data[15],  // nombramiento
            intval($data[16]),  // categoria
            intval($data[17]),  // digito_cat
            intval($data[18]),  // cat_puesto
            intval($data[19]),  // horas
            intval($data[20]),  // tipo_nom
            intval($data[21]),  // cons_plaza
            intval($data[22]),  // cat_pago
            $data[23],  // nivel_puesto
            intval($data[24]),  // nivel_sueldo
            intval($data[25]),  // mot_mov
            $data[26],  // ct
            floatval($data[27]) // imp_gravado
        ];
        
        $types = "ssiisssisssssisiiiiiiissiissd";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Niveles desconcentrados cargados: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

// ============================================
// SEPE - VEMP PLAZA (26 campos)
// ============================================
function procesarSepeVempPlaza($conn, $filePath) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE sepe_vemp_plaza");
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return ['success' => false, 'message' => 'No se pudo leer archivo'];
    
    $inserted = 0;
    $errors = [];
    $lineNum = 0;
    
    $stmt = $conn->prepare("INSERT INTO sepe_vemp_plaza (
        rfc, num_emp, ramo, sector, programa, subprograma, dependencia, unidad_res,
        proyecto, nombramiento, categoria, digito_cat, cat_puesto, cat_pago, horas,
        tipo_nom, cons_plaza, qna_ini, qna_fin, estatus_plaza, qna_ing_plaza,
        mot_mov, nivel_sueldo, nivel_puesto, cve_sindicato
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($line = fgets($handle)) !== false) {
        $lineNum++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = explode('|', $line);
        if (count($data) < 25) {
            $errors[] = "Línea $lineNum: se esperaban 25 campos, se recibieron " . count($data);
            continue;
        }
        
        $params = [
            $data[0],   // rfc
            intval($data[1]),   // num_emp
            $data[2],   // ramo
            $data[3],   // sector
            $data[4],   // programa
            $data[5],   // subprograma
            $data[6],   // dependencia
            intval($data[7]),   // unidad_res
            $data[8],   // proyecto
            $data[9],   // nombramiento
            intval($data[10]),  // categoria
            intval($data[11]),  // digito_cat
            intval($data[12]),  // cat_puesto
            intval($data[13]),  // cat_pago
            intval($data[14]),  // horas
            intval($data[15]),  // tipo_nom
            intval($data[16]),  // cons_plaza
            intval($data[17]),  // qna_ini
            intval($data[18]),  // qna_fin
            intval($data[19]),  // estatus_plaza
            intval($data[20]),  // qna_ing_plaza
            intval($data[21]),  // mot_mov
            intval($data[22]),  // nivel_sueldo
            $data[23],  // nivel_puesto
            intval($data[24])   // cve_sindicato
        ];
        
        $types = "sisssssisssiiiiiiiiiiiiis";
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = "Línea $lineNum: " . $stmt->error;
        }
    }
    
    fclose($handle);
    $stmt->close();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return [
        'success' => true,
        'message' => "Plazas SEPE cargadas: $inserted registros",
        'inserted' => $inserted,
        'errors' => $errors
    ];
}
?>