<?php
    session_start();
    include 'conectar_usuario_firma.php';

    $firmas = new usuario_firma();
    $result = $firmas -> todos_firmados();

    echo $result;

?>
