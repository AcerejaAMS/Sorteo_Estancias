<?php
    session_start();
    include 'maestros.php';

    $sindicato = $_SESSION['sindicato'];
    $admin = $_SESSION['admin'];
    $mostrar = $_GET['mostrar'];

    $maestros = new maestro();

    if($admin == 1){
        $resultado = $maestros->cargar_todos_maestros($mostrar);
    }else{
        $resultado = $maestros->cargar_maestros($sindicato);
    }
    
    echo $resultado;

?>