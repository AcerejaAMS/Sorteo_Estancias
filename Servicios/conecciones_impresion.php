<?php
    include 'conexion.php';

    class datos_impresion{
        public function recuperar_todas_firmas(){
            
            $conec = new conexion();
            $db = $conec->conectar($conec);

            $sql = "SELECT imagen FROM usuarios WHERE imagen IS NOT NULL";
            $query = $db->prepare($sql);
            $query->execute();
            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return $result;
            }else{
                return [];
            }
        }

        public function cargar_todos_maestros(){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'SELECT nombre, rfc, plaza, ctr, fec_ing, region, origen FROM profes_sorteo WHERE estado=1 ORDER BY region ASC';
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode($result);
            }else{
                return json_encode(NULL);
            }
        }

        public function nombres_region(){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'SELECT * FROM regiones';
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                $nuevo = [];

                foreach ($result as $fila) {
                    $nuevo[$fila["id"]] = trim($fila["nombre"]);
                }

                return json_encode($nuevo);
            }else{
                return json_encode(['datos'=>NULL]);
            }
        }
    }
?>

