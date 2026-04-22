<?php
    session_start();
    include 'maestros.php';

    $nom = $_POST['nombre'];
    $rfc= $_POST['rfc'];
    $plz = $_POST['plaza'];
    $ctr = $_POST['ct'];
    $fec_ing = $_POST['fec_ing'];

    $maestro = new maestro();
    $resultado = $maestro->datos_maestro($nom, $rfc, $plz, $ctr, $fec_ing);

    if($_SESSION['estado_firma'] == 1){
        echo json_encode(['success'=>$resultado, 'msg'=>'Firmado']);
        exit;
    }

    echo json_encode(['success'=>$resultado, 'msg'=>'No Firmado']);

?>