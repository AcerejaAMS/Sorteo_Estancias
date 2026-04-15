<?php
    session_start();
    include 'maestros.php';

    $sindicato = $_SESSION['sindicato'];
    $admin = $_SESSION['admin'];

    $maestros = new maestro();

    if($admin == 1){
        $resultado = $maestros->cargar_todos_maestros();
    }else{
        $resultado = $maestros->cargar_maestros($sindicato);
    }
    
    echo $resultado;

?>