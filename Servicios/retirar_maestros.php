<?php
    session_start();
    include 'maestros.php';

    $nom = $_POST['nombre'];
    $rfc= $_POST['rfc'];
    $plz = $_POST['plaza'];
    $ctr = $_POST['ct'];
    $fec_ing = $_POST['fec_ing'];
    $mun = $_POST['municipio'];
    $reg = $_POST['region'];
    $ori = $_POST['origen'];
    $sin = $_POST['sindicato'];

    $maestro = new maestro();
    $resultado = $maestro->cambiar_estado($nom, $rfc, $plz, $ctr, $fec_ing, $mun, $reg, $ori, $sin);

    echo $resultado;

?>