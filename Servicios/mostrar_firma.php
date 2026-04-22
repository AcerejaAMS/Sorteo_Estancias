<?php
    session_start();
    include 'conectar_usuario_firma.php';

    $id = $_POST['id'];

    $usr_firma = new usuario_firma();
    $firma = $usr_firma -> mostrar_firma($id);

    echo $firma;
?>