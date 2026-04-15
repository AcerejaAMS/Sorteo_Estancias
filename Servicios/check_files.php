<?php
function hasBOM($filename) {
    if (!file_exists($filename)) return "No existe";
    
    $content = file_get_contents($filename);
    $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
    
    if (substr($content, 0, 3) === $bom) {
        return "TIENE BOM ";
    }
    
    // Verificar primer byte
    $firstByte = ord($content[0]);
    if ($firstByte !== 60) { // 60 = '<'
        return "Primer byte: $firstByte (debería ser 60) ";
    }
    
    return "OK ";
}

$files = ['config.php', 'verificar_datos.php', 'generar_sorteo.php'];

echo "<h2>Verificación de archivos</h2>";
foreach ($files as $file) {
    echo "<p><strong>$file:</strong> " . hasBOM($file) . "</p>";
}
?>