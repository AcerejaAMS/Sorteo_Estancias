<?php
    session_start();
    include 'maestros.php';

    $columna = $_POST['columna'];
    $buscar= $_POST['buscar'];
    $admin = $_SESSION['admin'];
    $mostrar = $_POST['mostrar'];

    $maestro = new maestro();

    if($admin == 1){
        $resultado = $maestro->buscar_maestro_admin($columna, $buscar, $mostrar);
    }else{
        $resultado = $maestro->buscar_maestro($columna, $buscar);
    }
    

    echo $resultado;

?>