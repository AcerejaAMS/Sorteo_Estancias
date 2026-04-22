<?php
    session_start();
    include 'historial.php';

    $columna = $_POST['columna'];
    $buscar= $_POST['buscar'];

    $hist = new historial();

    $resultado = $hist->buscar_historial($columna, $buscar);

    echo $resultado;

?>