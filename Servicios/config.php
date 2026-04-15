<?php
// config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_SEPE', 'sepe');
define('DB_USET', 'uset');
define('DB_SORTEO', 'sorteo');

function getConnection($db = 'sorteo') {
    $databaseMap = [
        'sepe'   => DB_SEPE,
        'uset'   => DB_USET,
        'sorteo' => DB_SORTEO
    ];
    
    $dbName = isset($databaseMap[$db]) ? $databaseMap[$db] : $db;
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        error_log("Error conexión: " . $conn->connect_error);
        return false;
    }
    
    if (!$conn->select_db($dbName)) {
        error_log("Error seleccionando BD: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

function closeConnection($conn) {
    if ($conn && $conn instanceof mysqli && $conn->thread_id) {
        $conn->close();
    }
}

function checkDatabases() {
    $errors = [];
    $dbs = [
        'sepe'   => DB_SEPE,
        'uset'   => DB_USET,
        'sorteo' => DB_SORTEO
    ];
    
    foreach ($dbs as $label => $dbName) {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, $dbName);
        if ($conn->connect_error) {
            $errors[] = "$label: " . $conn->connect_error;
        } else {
            $conn->close();
        }
    }
    
    return empty($errors) ? 
        ['success' => true, 'message' => 'Conectado'] :
        ['success' => false, 'errors' => $errors];
}