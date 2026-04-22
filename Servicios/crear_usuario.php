<?php
    session_start();
    include 'usuarios.php';
    include 'caracteristicas_usuarios.php';

    $rfc = $_POST['rfc'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $origen = $_POST['origen'] ?? '';
    $sindicato = $_POST['sindicato'] ?? '';


    if(empty($rfc) || empty($nombre) || empty($origen) || empty($sindicato)){
        echo json_encode(array('success' => 0, 'msg'=>'Faltan Datos'));
        exit;
    }

    $creador_usuarios = new generador_usuarios();

    $verificacion_RFC = $creador_usuarios -> comprobacion_RFC($rfc);

    if($verificacion_RFC == false){
        echo json_encode(array('success' => 0, 'msg'=>'Estructura de RFC no valida'));
        exit;
    }

    $passwrd = $creador_usuarios -> generar_password();

    $usuario = new usuario();
    $resultado = $usuario->nuevo_usuario($rfc, $nombre, $passwrd, $origen, $sindicato);

    echo $resultado;

?>