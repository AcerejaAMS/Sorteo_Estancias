<?php
    session_start();
    include 'maestros.php';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=maestros_retirados.csv');


    $usuario = new maestro();
    $resultado = $usuario->descargar_maestros();

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Nombre','RFC','Plaza','CTR','Fecha_ingreso','Origen','Sindicato','Region']);

    foreach ($resultado as $fila) {
        fputcsv($output, $fila);
    }

    fclose($output);
    exit;

?>