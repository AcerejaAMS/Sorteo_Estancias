<?php
    session_start();
    include 'usuarios.php';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=usuarios_sorteo.csv');


    $usuario = new usuario();
    $resultado = $usuario->descargar_usuarios();

    $output = fopen('php://output', 'w');

    fputcsv($output, ['ID','Usuario','RFC','Contraseña','Sindicato','Origen']);

    foreach ($resultado as $fila) {
        fputcsv($output, $fila);
    }

    fclose($output);
    exit;

?>