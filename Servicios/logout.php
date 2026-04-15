<?php
    session_start();
    unset($_SESSION);
    session_destroy();

    echo json_encode(['session'=>NULL]);
?>