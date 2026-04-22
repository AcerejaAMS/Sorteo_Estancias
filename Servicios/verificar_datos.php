<?php
/**
 * verificar_datos.php
 */

// Limpiar TODO buffer de salida
while (ob_get_level()) {
    ob_end_clean();
}

// Forzar codificación limpia
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

try {
    $conn = getConnection('prueba');
    
    if (!$conn) {
        throw new Exception("No se pudo conectar");
    }

    $tablasVerificar = [
        'uset' => ['empleado', 'empleado_plaza', 'cheque_cpto', 'centro_trabajo'],
        'sepe' => ['sepe_empleado', 'sepe_vemp_plaza', 'sepe_cheque_cpto', 'sepe_cg_ct', 'sepe_niv_desconcentrados'],
        'autos' => ['ganadores_autoanios'],
        'comisionados' => ['comisionados']
    ];

    $resultado = [
        'success' => true,
        'tablas' => [],
        'detalles' => []
    ];

    foreach ($tablasVerificar as $key => $tablas) {
        $tablasArray = is_array($tablas) ? $tablas : [$tablas];
        $tieneDatos = true;
        $detallesGrupo = [];
        
        foreach ($tablasArray as $tabla) {
            $info = verificarTabla($conn, $tabla);
            $detallesGrupo[$tabla] = $info;
            if (!$info['existe'] || $info['count'] == 0) {
                $tieneDatos = false;
            }
        }
        
        $resultado['tablas'][$key] = $tieneDatos;
        $resultado['detalles'][$key] = $detallesGrupo;
    }

    closeConnection($conn);
    
    // Salida limpia
    exit(json_encode($resultado));

} catch (Exception $e) {
    exit(json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tablas' => ['uset' => false, 'sepe' => false, 'autos' => false, 'comisionados' => false]
    ]));
}

function verificarTabla($conn, $tabla) {
    $resultado = ['existe' => false, 'count' => 0, 'error' => null];
    
    try {
        $checkResult = $conn->query("SHOW TABLES LIKE '$tabla'");
        
        if (!$checkResult || $checkResult->num_rows == 0) {
            $resultado['error'] = "No existe";
            return $resultado;
        }
        
        $resultado['existe'] = true;
        $res = $conn->query("SELECT COUNT(*) as total FROM `$tabla`");
        
        if ($res && $row = $res->fetch_assoc()) {
            $resultado['count'] = (int)$row['total'];
        }
        
    } catch (Exception $e) {
        $resultado['error'] = $e->getMessage();
    }
    
    return $resultado;
}