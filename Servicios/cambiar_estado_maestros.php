<?php
    session_start();
    include 'maestros.php';

    $nom = $_POST['nombre'];
    $rfc= $_POST['rfc'];
    $plz = $_POST['plaza'];
    $estado = $_POST['estado'];

    $maestro = new maestro();
    $resultado = $maestro->cambiar_estado_admin($nom, $rfc, $plz, $estado);

    echo $resultado;

?>