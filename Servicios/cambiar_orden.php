<?php
    session_start();
    include 'maestros.php';

    $maestro = new maestro();
    $accion = $maestro -> cambiar_orden();

    echo $accion;
?>