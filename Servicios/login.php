<?php
    session_start();
    include 'usuarios.php';

    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['passwrd'] ?? '';

    if(empty($user) && empty($pass)){
        echo json_encode(array('success' => 0, 'msg'=>'Falta Usuario y Contraseña'));
        exit;
    }

    $usuario = new usuario();
    $resultado = $usuario->login($user, $pass);

    echo $resultado;

?>