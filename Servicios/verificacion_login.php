<?php
    session_start();

    header('Content-Type: application/json');

    if($_SESSION){
        echo json_encode(['session'=>$_SESSION]);
        exit;
    }

    echo json_encode(['session'=>NULL]);
?>