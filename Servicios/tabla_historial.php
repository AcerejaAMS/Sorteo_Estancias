<?php
    session_start();
    include 'historial.php';

    $histo = new historial();
    $resultado = $histo->cargar_historial();

    echo $resultado;

?>