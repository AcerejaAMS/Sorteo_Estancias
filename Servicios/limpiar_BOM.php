<?php
/**
 * limpiar_bom.php - Elimina BOM de todos los archivos PHP
 * Ejecutar: http://localhost/tu_proyecto/limpiar_bom.php
 */

header('Content-Type: text/plain; charset=utf-8');

function removeBOM($file) {
    if (!file_exists($file)) return " No existe: $file";
    
    $content = file_get_contents($file);
    $original_size = strlen($content);
    
    // Detectar y eliminar BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
        file_put_contents($file, $content);
        return " BOM eliminado de: $file (bytes: $original_size → " . strlen($content) . ")";
    }
    
    return " Sin BOM: $file";
}

// Archivos a limpiar
$archivos = [
    'generar_sorteo.php',
    'config.php',
    'verificar_datos.php',
    'upload_uset.php',
    'upload_sepe.php',
    'upload_autos.php',
    'upload_comisionados.php'
];

echo "=== LIMPIANDO BOM DE ARCHIVOS ===\n\n";

foreach ($archivos as $archivo) {
    echo removeBOM($archivo) . "\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
echo "Ahora prueba generar_sorteo.php nuevamente\n";
?>