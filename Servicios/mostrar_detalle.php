<?php
    session_start();
    include 'historial.php';

    $id = $_POST['id'];

    $usr_firma = new historial();
    $firma = $usr_firma -> mostrar_detalles($id);

    echo $firma;
?>