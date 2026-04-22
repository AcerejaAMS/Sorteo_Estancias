<?php
/**
 * generar_sorteo.php
 * 
 * IMPORTANTE: Este archivo DEBE guardarse como UTF-8 SIN BOM
 */

// ═══════════════════════════════════════════════════════════════
// 1. LIMPIAR BOM Y CONTROLAR BUFFER
// ═══════════════════════════════════════════════════════════════
if (ob_get_level() === 0) {
    ob_start();
}
ob_clean();

// ═══════════════════════════════════════════════════════════════
// 2. CONFIGURACIÓN DE ERRORES
// ═══════════════════════════════════════════════════════════════
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/generar_errors.log');

// ═══════════════════════════════════════════════════════════════
// 3. HEADER JSON
// ═══════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');

// ═══════════════════════════════════════════════════════════════
// 4. FUNCIÓN PARA RESPUESTA JSON LIMPIA
// ═══════════════════════════════════════════════════════════════
function jsonResponse($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// 5. CAPTURAR ERRORES FATALES
// ═══════════════════════════════════════════════════════════════
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR])) {
        jsonResponse([
            'success' => false,
            'message' => 'Error fatal: ' . $error['message'] . ' (línea ' . $error['line'] . ')',
            'steps' => []
        ]);
    }
});

// ═══════════════════════════════════════════════════════════════
// 6. INCLUIR CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════
try {
    if (!file_exists('config.php')) {
        throw new Exception('config.php no encontrado');
    }
    require_once 'config.php';
    
    if (!function_exists('getConnection')) {
        throw new Exception('Función getConnection() no existe. Verifica config.php');
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'steps' => []
    ]);
}

// ═══════════════════════════════════════════════════════════════
// 7. DEFINIR CONSTANTES DE BASES DE DATOS
// ═══════════════════════════════════════════════════════════════
if (!defined('DB_SEPE')) define('DB_SEPE', 'sepe');
if (!defined('DB_USET')) define('DB_USET', 'uset');
if (!defined('DB_SORTEO')) define('DB_SORTEO', 'sorteo');

// ═══════════════════════════════════════════════════════════════
// 8. FUNCIONES AUXILIARES
// ═══════════════════════════════════════════════════════════════

/**
 * Ejecuta SQL en la base de datos destino
 */
function execTarget($connTarget, $sql, $description = "") {
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => "Error en $description: " . $connTarget->error];
    }
    return ['success' => true];
}

/**
 * PASO 1: Crear índices en prueba (optimización)
 */
function crearIndices($connSource) {
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_emp_rfc_sepe ON sepe_empleado (rfc, qna_ing_sep)",
        "CREATE INDEX IF NOT EXISTS idx_plaza_main_sepe ON sepe_vemp_plaza (rfc, num_emp, cat_pago, cons_plaza, estatus_plaza, qna_fin, nombramiento, cve_sindicato)",
        "CREATE INDEX IF NOT EXISTS idx_cheque_join_sepe ON sepe_cheque_cpto (rfc, num_emp, cat_pago, cons_plaza, ct)",
        "CREATE INDEX IF NOT EXISTS idx_ct_sepe ON sepe_cg_ct (ct)",
        "CREATE INDEX IF NOT EXISTS idx_niveles ON sepe_niv_desconcentrados (ct, cat_pago, qna_ing_sep)",
        "CREATE INDEX IF NOT EXISTS idx_ct_niveles ON sepe_cg_ct (ct)",
        "CREATE INDEX IF NOT EXISTS idx_rfc_emp ON empleado(rfc)",
        "CREATE INDEX IF NOT EXISTS idx_rfc_plaza ON empleado_plaza(rfc)",
        "CREATE INDEX IF NOT EXISTS idx_plaza ON empleado_plaza(cons_plaza)",
        "CREATE INDEX IF NOT EXISTS idx_cheque_plaza ON cheque_cpto(cons_plaza)",
        "CREATE INDEX IF NOT EXISTS idx_ct ON centro_trabajo(ct_clasif, ct_id, ct_secuencial, ct_digito_ver)"
    ];
    
    foreach ($indices as $sql) {
        @$connSource->query($sql);
    }
    
    return ['success' => true, 'message' => 'Índices creados/verificados'];
}

/**
 * PASO 2: Crear tablas en prueba2
 */
function crearTablas($connTarget) {
    $tablas = [
        "CREATE TABLE IF NOT EXISTS profes_ini_31 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            cod_pago INT,
            unidad INT,
            subunidad INT,
            cat_puesto VARCHAR(20),
            horas DECIMAL(10,2),
            cons_plaza VARCHAR(20),
            ent_fed INT,
            ct_clasif VARCHAR(2),
            ct_id INT,
            ct_secuencial INT,
            ct_digito_ver VARCHAR(2),
            fec_ing INT,
            municipio VARCHAR(100),
            region INT DEFAULT 0,
            origen VARCHAR(20),
            sindicato INT,
            INDEX idx_rfc (rfc),
            INDEX idx_plaza (cons_plaza)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS profes_ini_55 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            num_emp INT,
            ramo INT,
            sector INT,
            programa INT,
            subprograma INT,
            dependencia INT,
            unidad_res INT,
            proyecto VARCHAR(10),
            nombramiento VARCHAR(10),
            categoria INT,
            digito_cat VARCHAR(2),
            cat_pago INT,
            horas INT,
            tipo_nom VARCHAR(10),
            cons_plaza VARCHAR(20),
            ct VARCHAR(20),
            fec_ing INT,
            qna_ing_sep INT,
            municipio VARCHAR(100),
            region INT DEFAULT 0,
            origen VARCHAR(20),
            sindicato INT,
            INDEX idx_rfc (rfc),
            INDEX idx_plaza (cons_plaza)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS profes_ini_31_limpia LIKE profes_ini_31",
        "CREATE TABLE IF NOT EXISTS profes_ini_55_limpia LIKE profes_ini_55",
        
        "CREATE TABLE IF NOT EXISTS profes_31 (
            num_maes INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            plaza VARCHAR(50),
            ctr VARCHAR(20),
            fec_ing INT,
            municipio VARCHAR(100),
            region INT DEFAULT 0,
            origen VARCHAR(20),
            sindicato INT,
            INDEX idx_rfc (rfc),
            INDEX idx_plaza (plaza)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS profes_55 (
            num_maes INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            plaza VARCHAR(50),
            ctr VARCHAR(20),
            fec_ing INT,
            municipio VARCHAR(100),
            region INT DEFAULT 0,
            origen VARCHAR(20),
            sindicato INT,
            INDEX idx_rfc (rfc),
            INDEX idx_plaza (plaza)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS profes_unificada (
            num_maes INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            plaza VARCHAR(50),
            ctr VARCHAR(20),
            fec_ing INT,
            municipio VARCHAR(100),
            region INT DEFAULT 0,
            origen VARCHAR(20),
            sindicato INT,
            INDEX idx_rfc (rfc),
            INDEX idx_plaza (plaza)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS profes_sorteo (
            num_maes INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            plaza VARCHAR(50),
            ctr VARCHAR(20),
            fec_ing INT,
            municipio VARCHAR(100),
            region INT DEFAULT 0,
            origen VARCHAR(20),
            sindicato INT,
            estado BOOLEAN DEFAULT TRUE,
            INDEX idx_rfc (rfc),
            INDEX idx_plaza (plaza)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS ganadores_atoaños (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            descripcion VARCHAR(200),
            anio INT,
            INDEX idx_rfc (rfc)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS comisionados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rfc VARCHAR(13) NOT NULL,
            nombre VARCHAR(200),
            plaza VARCHAR(50),
            ct VARCHAR(20),
            INDEX idx_rfc (rfc)
        ) ENGINE=InnoDB"
    ];
    
    foreach ($tablas as $sql) {
        $result = execTarget($connTarget, $sql, "crear tabla");
        if (!$result['success']) return $result;
    }
    
    return ['success' => true, 'message' => 'Tablas creadas correctamente'];
}

/**
 * PASO 3: Insertar datos en profes_ini_31 (desde prueba)
 */
function insertarProfes31($connSource, $connTarget) {
    $connTarget->query("TRUNCATE TABLE profes_ini_31");
    
    $sql = "INSERT INTO profes_ini_31 (
        rfc, nombre, cod_pago, unidad, subunidad, cat_puesto, 
        horas, cons_plaza, ent_fed, ct_clasif, ct_id, ct_secuencial, 
        ct_digito_ver, fec_ing, municipio, region, origen, sindicato
    )
    SELECT 
        e.rfc,
        e.nom_emp as nombre,
        ep.cod_pago,
        ep.unidad,
        ep.subunidad,
        ep.cat_puesto,
        ROUND(ep.horas, 2) as horas,
        ep.cons_plaza,
        cc.ent_fed,
        cc.ct_clasif,
        cc.ct_id,
        cc.ct_secuencial,
        cc.ct_digito_ver,
        e.qna_ing_sep as fec_ing,
        ct.municipio,
        0 as region,
        'USET' as origen,
        31 as sindicato
    FROM " . DB_USET . ".empleado_plaza ep
    JOIN " . DB_USET . ".empleado e ON e.rfc = ep.rfc
    JOIN " . DB_USET . ".cheque_cpto cc 
        ON ep.rfc = cc.rfc 
        AND ep.cat_puesto = cc.cat_puesto 
        AND ep.cons_plaza = cc.cons_plaza
    JOIN " . DB_USET . ".centro_trabajo ct 
        ON ct.ct_clasif = cc.ct_clasif
        AND ct.ct_id = cc.ct_id
        AND ct.ct_secuencial = cc.ct_secuencial
        AND ct.ct_digito_ver = cc.ct_digito_ver
    WHERE ep.estatus_plaza = 1
        AND ct.qna_fin >= 202509
        AND ep.qna_fin >= 202509
        AND e.qna_ing_sep < 202421
        AND ep.ban_pago > 0
        AND LEFT(TRIM(ep.cat_puesto), 1) = 'E'";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error insertando profes_ini_31: ' . $connTarget->error];
    }
    
    $res = $connTarget->query("SELECT COUNT(*) as c FROM profes_ini_31");

    if (!$res) {
        return ['success' => false, 'message' => $connTarget->error];
    }

    $count = $res->fetch_assoc()['c'];
}

/**
 * PASO 4: Insertar datos en profes_ini_55 (desde prueba - SEPE)
 */
function insertarProfes55($connSource, $connTarget) {
    $connTarget->query("TRUNCATE TABLE profes_ini_55");
    
    $sql = "INSERT INTO profes_ini_55 (
        rfc, nombre, num_emp, ramo, sector, programa, subprograma,
        dependencia, unidad_res, proyecto, nombramiento, categoria,
        digito_cat, cat_pago, horas, tipo_nom, cons_plaza, ct,
        qna_ing_sep, municipio, region, origen, sindicato
    )
    SELECT DISTINCT
        nd.rfc,
        CONCAT(TRIM(nd.paterno), ' ', TRIM(nd.materno), ' ', TRIM(nd.nombre)) AS nombre,
        nd.num_emp,
        nd.ramo,
        nd.sector,
        nd.programa,
        nd.subprograma,
        nd.dependencia,
        nd.unidad_res,
        nd.proyecto,
        nd.nombramiento,
        nd.categoria,
        nd.digito_cat,
        nd.cat_pago,
        nd.horas,
        nd.tipo_nom,
        nd.cons_plaza,
        nd.ct,
        nd.qna_ing_sep,
        ct.municipio,
        0 AS region,
        'NIVELES' AS origen,
        51 as sindicato
    FROM " . DB_SEPE . ".sepe_niv_desconcentrados nd
    JOIN " . DB_SEPE . ".sepe_cg_ct ct ON ct.ct = nd.ct
    WHERE nd.cat_pago = 870
        AND nd.qna_ing_sep < 202321";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error insertando profes_ini_55: ' . $connTarget->error];
    }
    
    $res = $connTarget->query("SELECT COUNT(*) as c FROM profes_ini_55");

    if (!$res) {
        return ['success' => false, 'message' => $connTarget->error];
    }

    $count = $res->fetch_assoc()['c'];
}

/**
 * PASO 5: Limpiar duplicados
 */
function limpiarDuplicados($connTarget) {

    $connTarget->query("TRUNCATE TABLE profes_ini_31_limpia");

    // =========================
    // 31 SIN DUPLICADOS
    // =========================
    $sql31 = "
    INSERT INTO profes_ini_31_limpia (
        rfc, nombre, cod_pago, unidad, subunidad, cat_puesto,
        horas, cons_plaza, ent_fed, ct_clasif, ct_id, ct_secuencial,
        ct_digito_ver, fec_ing, municipio, region, origen, sindicato
    )
    SELECT 
        rfc, nombre, cod_pago, unidad, subunidad, cat_puesto,
        horas, cons_plaza, ent_fed, ct_clasif, ct_id, ct_secuencial,
        ct_digito_ver, fec_ing, municipio, region, origen, sindicato
    FROM (
        SELECT *,
        ROW_NUMBER() OVER (
            PARTITION BY rfc
            ORDER BY CASE WHEN horas = 0 THEN 999 ELSE horas END DESC
        ) AS rn
        FROM profes_ini_31
    ) t
    WHERE rn = 1
    ";

    if (!$connTarget->query($sql31)) {

        $sql31_fallback = "
        INSERT INTO profes_ini_31_limpia
        SELECT t1.* FROM profes_ini_31 t1
        LEFT JOIN profes_ini_31 t2 
            ON t1.rfc = t2.rfc 
            AND (
                (t2.horas > t1.horas) OR 
                (t2.horas = t1.horas AND t2.id < t1.id)
            )
        WHERE t2.id IS NULL
        ";

        if (!$connTarget->query($sql31_fallback)) {
            return ['success' => false, 'message' => 'Error limpiando 31: ' . $connTarget->error];
        }
    }

    $connTarget->query("TRUNCATE TABLE profes_ini_55_limpia");

    // =========================
    // 55 SIN DUPLICADOS
    // =========================
    $sql55 = "
    INSERT INTO profes_ini_55_limpia (
    rfc, nombre, num_emp, ramo, sector, programa, subprograma,
    dependencia, unidad_res, proyecto, nombramiento, categoria,
    digito_cat, cat_pago, horas, tipo_nom, cons_plaza, ct,
    qna_ing_sep, municipio, region, origen, sindicato
    )
    SELECT
        rfc, nombre, num_emp, ramo, sector, programa, subprograma,
        dependencia, unidad_res, proyecto, nombramiento, categoria,
        digito_cat, cat_pago, horas, tipo_nom, cons_plaza, ct,
        qna_ing_sep, municipio, region, origen, sindicato
    FROM (
        SELECT *,
        ROW_NUMBER() OVER (
            PARTITION BY rfc
            ORDER BY CASE WHEN horas = 0 THEN 999 ELSE horas END DESC
        ) AS rn
        FROM profes_ini_55
    ) t
    WHERE rn = 1;
    ";

    if (!$connTarget->query($sql55)) {

        $sql55_fallback = "
        INSERT INTO profes_ini_55_limpia
        SELECT t1.* FROM profes_ini_55 t1
        LEFT JOIN profes_ini_55 t2 
            ON t1.rfc = t2.rfc 
            AND (
                (t2.horas > t1.horas) OR 
                (t2.horas = t1.horas AND t2.id < t1.id)
            )
        WHERE t2.id IS NULL
        ";

        if (!$connTarget->query($sql55_fallback)) {
            return ['success' => false, 'message' => 'Error limpiando 55: ' . $connTarget->error];
        }
    }

    $res = $connTarget->query("SELECT COUNT(*) as c FROM profes_ini_31_limpia");
    $row = $res ? $res->fetch_assoc() : ['c' => 0];
    $c31 = $row['c'];

    $res = $connTarget->query("SELECT COUNT(*) as c FROM profes_ini_55_limpia");
    $row = $res ? $res->fetch_assoc() : ['c' => 0];
    $c55 = $row['c'];

    return ['success' => true, 'message' => "Limpieza: 31=$c31, 55=$c55"];
}

/**
 * PASO 6: Generar profes_31
 */
function generarProfes31($connTarget) {
    $connTarget->query("TRUNCATE TABLE profes_31");
    
    $sql = "INSERT INTO profes_31 (rfc, nombre, plaza, ctr, fec_ing, municipio, region, origen, sindicato)
    SELECT 
        rfc,
        nombre,
        CONCAT(
            LPAD(cod_pago, 2, '0'),
            LPAD(unidad, 2, '0'),
            LPAD(subunidad, 2, '0'),
            LPAD(cat_puesto, 7, ' '),
            LPAD(CAST(horas AS UNSIGNED), 4, '0'),
            LPAD(cons_plaza, 6, '0')
        ) AS plaza,
        CONCAT(
            LPAD(ent_fed, 2, '0'),
            ct_clasif,
            LPAD(ct_id, 2, '0'),
            LPAD(ct_secuencial, 4, '0'),
            ct_digito_ver
        ) AS ctr,
        fec_ing,
        municipio,
        region,
        origen,
        sindicato
    FROM profes_ini_31_limpia";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error generando profes_31: ' . $connTarget->error];
    }
    
    $count = $connTarget->query("SELECT COUNT(*) as c FROM profes_31")->fetch_assoc()['c'];
    return ['success' => true, 'message' => "profes_31: $count registros"];
}

/**
 * PASO 7: Generar profes_55
 */
function generarProfes55($connTarget) {
    $connTarget->query("TRUNCATE TABLE profes_55");
    
    $sql = "INSERT INTO profes_55 (rfc, nombre, plaza, ctr, fec_ing, municipio, region, origen, sindicato)
    SELECT 
        rfc,
        nombre,
        CONCAT(
            LPAD(ramo, 2, '0'),
            LPAD(sector, 2, '0'),
            LPAD(programa, 2, '0'),
            LPAD(subprograma, 2, '0'),
            LPAD(dependencia, 2, '0'),
            LPAD(unidad_res, 2, '0'),
            RPAD(proyecto, 4, '0'),
            nombramiento,
            LPAD(categoria, 3, '0'),
            digito_cat,
            LPAD(cat_pago, 3, '0'),
            tipo_nom
        ) AS plaza,
        ct AS ctr,
        qna_ing_sep AS fec_ing,
        municipio,
        region,
        origen,
        sindicato
    FROM profes_ini_55_limpia";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error generando profes_55: ' . $connTarget->error];
    }
    
    $count = $connTarget->query("SELECT COUNT(*) as c FROM profes_55")->fetch_assoc()['c'];
    return ['success' => true, 'message' => "profes_55: $count registros"];
}

/**
 * PASO 8: Unificar tablas
 */
function unificarTablas($connTarget) {
    $connTarget->query("TRUNCATE TABLE profes_unificada");
    
    $sql = "INSERT INTO profes_unificada 
    (rfc, nombre, plaza, ctr, fec_ing, municipio, region, origen, sindicato)
    
    SELECT 
        rfc,
        nombre,
        plaza,
        ctr,
        fec_ing,
        municipio,
        region,
        origen,
        sindicato
    FROM profes_31
    
    UNION ALL
    
    SELECT 
        rfc,
        nombre,
        plaza,
        ctr,
        fec_ing,
        municipio,
        region,
        origen,
        sindicato
    FROM profes_55";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error unificando: ' . $connTarget->error];
    }
    
    $count = $connTarget->query("SELECT COUNT(*) as c FROM profes_unificada")->fetch_assoc()['c'];
    return ['success' => true, 'message' => "Unificados: $count registros"];
}
/**
 * PASO 9: Limpiar final
 */
function limpiarFinal($connTarget) {
    $connTarget->query("TRUNCATE TABLE profes_final");
    
    $sql = "INSERT INTO profes_sorteo
    (rfc, nombre, plaza, ctr, fec_ing, municipio, region, origen, sindicato)
    SELECT 
        rfc, nombre, plaza, ctr, fec_ing, municipio, region, origen, sindicato
    FROM (
        SELECT *,
            ROW_NUMBER() OVER (
                PARTITION BY rfc
                ORDER BY COALESCE(horas,0) DESC
            ) AS rn
        FROM profes_unificada
    ) x 
    WHERE rn = 1";
    
    $count = $connTarget->query("SELECT COUNT(*) as c FROM profes_final")->fetch_assoc()['c'];
    return ['success' => true, 'message' => "profes_final: $count registros"];
}
/**
 * PASO 10: Eliminar ganadores anteriores
 */
function eliminarGanadores($connTarget) {
    $connTarget->query("TRUNCATE TABLE ganadores_atoaños");
    
    $sqlCopy = "INSERT INTO ganadores_atoaños (rfc, nombre, descripcion, anio)
                SELECT rfc, nombre, descripcion, anio FROM " . DB_SORTEO . ".ganadores_atoaños";
    @$connTarget->query($sqlCopy);
    
    $sql = "DELETE p FROM profes_final p
    WHERE EXISTS (SELECT 1 FROM ganadores_atoaños g WHERE p.rfc = g.rfc)";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error eliminando ganadores: ' . $connTarget->error];
    }
    
    $afectados = $connTarget->affected_rows;
    return ['success' => true, 'message' => "Ganadores eliminados: $afectados"];
}

/**
 * PASO 11: Cargar comisionados
 */
function cargarComisionados($connTarget) {
    $connTarget->query("TRUNCATE TABLE comisionados");
    
    $sql = "INSERT INTO comisionados (rfc, nombre, plaza, ct)
            SELECT rfc, nombre, plaza, ct FROM " . DB_SORTEO . ".comisionados";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error copiando comisionados: ' . $connTarget->error];
    }
    
    $sql = "UPDATE comisionados c
    LEFT JOIN profes_31 p31 ON c.rfc = p31.rfc
    LEFT JOIN profes_55 p55 ON c.rfc = p55.rfc
    SET c.plaza = COALESCE(p31.plaza, p55.plaza, c.plaza)";
    
    if (!$connTarget->query($sql)) {
        return ['success' => false, 'message' => 'Error actualizando plazas: ' . $connTarget->error];
    }
    
    $count = $connTarget->query("SELECT COUNT(*) as c FROM comisionados")->fetch_assoc()['c'];
    return ['success' => true, 'message' => "Comisionados: $count"];
}

// ═══════════════════════════════════════════════════════════════
// 9. FUNCIÓN PRINCIPAL
// ═══════════════════════════════════════════════════════════════
function procesarSorteo() {
    $results = [];
    
    // Conexiones con manejo de errores

    



    try {
        $connSEPE  = getConnection('sepe');
        if (!$connSEPE) throw new Exception('No se pudo conectar a "uset"');
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error conectando a prueba: ' . $e->getMessage(),
            'steps' => []
        ];
    }
    
    try {
        $connUSET  = getConnection('uset');
        if (!$connUSET) throw new Exception('No se pudo conectar a "sepe"');
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error conectando a prueba2: ' . $e->getMessage(),
            'steps' => []
        ];
    }

    try {
        $connSORTEO = getConnection('sorteo');
        if (!$connSORTEO) throw new Exception('No se pudo conectar a "sortep"');
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error conectando a prueba2: ' . $e->getMessage(),
            'steps' => []
        ];
    }
    
    $connSORTEO->begin_transaction();
    
    try {
        // PASO 1: Crear índices (solo $connSource)
        $result = crearIndices($connSORTEO);
        $results[] = array_merge($result, ['step' => 'crearIndices']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 2: Crear tablas (solo $connTarget)
        $result = crearTablas($connSORTEO);
        $results[] = array_merge($result, ['step' => 'crearTablas']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 3: Insertar profes 31 (ambas conexiones)
        $result = insertarProfes31($connUSET, $connSORTEO);
        $results[] = array_merge($result, ['step' => 'insertarProfes31']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 4: Insertar profes 55 (ambas conexiones)
        $result = insertarProfes55($connSEPE, $connSORTEO);
        $results[] = array_merge($result, ['step' => 'insertarProfes55']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 5: Limpiar duplicados (solo $connTarget)
        $result = limpiarDuplicados($connSORTEO);
        $results[] = array_merge($result, ['step' => 'limpiarDuplicados']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 6: Generar profes 31 (solo $connTarget)
        $result = generarProfes31($connSORTEO);
        $results[] = array_merge($result, ['step' => 'generarProfes31']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 7: Generar profes 55 (solo $connTarget)
        $result = generarProfes55($connSORTEO);
        $results[] = array_merge($result, ['step' => 'generarProfes55']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 8: Unificar tablas (solo $connTarget)
        $result = unificarTablas($connSORTEO);
        $results[] = array_merge($result, ['step' => 'unificarTablas']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 9: Limpiar final (solo $connTarget)
        $result = limpiarFinal($connSORTEO);
        $results[] = array_merge($result, ['step' => 'limpiarFinal']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 10: Eliminar ganadores (solo $connTarget)
        $result = eliminarGanadores($connSORTEO);
        $results[] = array_merge($result, ['step' => 'eliminarGanadores']);
        if (!$result['success']) throw new Exception($result['message']);
        
        // PASO 11: Cargar comisionados ($connTarget primero, $connSource segundo)
        $result = cargarComisionados($connSORTEO);
        $results[] = array_merge($result, ['step' => 'cargarComisionados']);
        if (!$result['success']) throw new Exception($result['message']);
        
        $connSORTEO->commit();
        
        $finalCount = $connSORTEO->query("SELECT COUNT(*) as c FROM profes_sorteo")->fetch_assoc()['c'];
        
        return [
            'success' => true,
            'message' => 'Proceso completado exitosamente',
            'total_final' => $finalCount,
            'steps' => $results
        ];
        
    } catch (Exception $e) {
        $connSORTEO->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'steps' => $results
        ];
    } finally {
        if ($connSEPE) @$connSEPE->close();
        if ($connUSET) @$connUSET->close();
        if ($connSORTEO) @$connSORTEO->close();
    }
}

// ═══════════════════════════════════════════════════════════════
// 10. EJECUTAR
// ═══════════════════════════════════════════════════════════════
try {
    $response = procesarSorteo();
    jsonResponse($response);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error crítico no controlado: ' . $e->getMessage(),
        'steps' => []
    ]);
}
?>