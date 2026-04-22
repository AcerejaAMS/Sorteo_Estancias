<?php
    session_start();
    include 'historial.php';

    $user = $_SESSION['usuario'];
    $url = $_POST['url'];
    $accion = $_POST['accion'];
    $nombre = $_POST['nombre'];
    $rfc = $_POST['rfc'];
    $plaza = $_POST['plaza'];
    $origen = $_POST['origen'];
    $tabla_afectada=$_POST['t_a'];
    $detalles = $_POST['detalles'] ?? '';

    $historia = new historial();

    if($origen == ""){
        $origen = $historia -> descubrir_origen($nombre, $rfc, $plaza);
    }

    $resultado = $historia -> accion($user, $accion, $url, $nombre, $rfc, $plaza, $origen, $tabla_afectada, $detalles);


?>