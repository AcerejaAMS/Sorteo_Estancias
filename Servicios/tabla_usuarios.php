<?php
    session_start();
    include 'usuarios.php';

    $usrs = new usuario();
    $resultado = $usrs->ver_usuarios();

    echo $resultado;

?>