<?php
    session_start();
    include 'conectar_usuario_firma.php';

    if(isset($_POST['imagen'])){
        $user = $_SESSION['usuario'] ?? '';
        $base64 = $_POST['imagen'];
    
        $img = str_replace('data:image/png;base64,', '', $base64);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);

        $guardar_firma = new usuario_firma();
        $resultado = $guardar_firma->subir_firma($user, $data);

        $_SESSION['estado_firma'] = 1;

        echo $resultado;

    }

?>