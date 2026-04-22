<?php
// Crear conexión
// $conn = new mysqli($servidor, $usuario, $password, $base_datos);

// Verificar conexión
// if ($conn->connect_error) {
//     die("Conexión fallida: " . $conn->connect_error);
// }

// Crear conexion

    class conexion{

        private $servidor = "localhost";
        private $usuario = "root"; // Usuario por defecto en XAMPP
        private $password = ""; // Vacío por defecto
        private $base_datos_1 = "sorteo";
        private $base_datos_2 = "sepe";
        private $base_datos_3 = "uset";

        public function conectar(){

            $db = new PDO("mysql:host=$this->servidor;dbname=$this->base_datos_1", $this->usuario, $this->password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $db;
        }

        public function conectar_2(){

            $db = new PDO("mysql:host=$this->servidor;dbname=$this->base_datos_2", $this->usuario, $this->password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $db;
        }

        public function conectar_3(){

            $db = new PDO("mysql:host=$this->servidor;dbname=$this->base_datos_3", $this->usuario, $this->password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $db;
        }
    }

?>
