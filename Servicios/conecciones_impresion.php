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

            $sql = 'SELECT nombre, rfc, plaza, ctr, fec_ing, region, origen, OrdenImpresion FROM profes_sorteo WHERE estado=1 ORDER BY region ASC,rfc ASC';
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

        public function cambiar_orden(){
            $conec = new conexion();
            $db = $conec->conectar();

            $db->prepare("UPDATE profes_sorteo SET OrdenImpresion = NULL WHERE estado = 0")->execute();

            $sql = "
                UPDATE profes_sorteo p
                JOIN (
                    SELECT num_maes, 
                        ROW_NUMBER() OVER (ORDER BY region ASC, rfc ASC) AS nuevo_orden
                    FROM profes_sorteo
                    WHERE estado = 1
                ) t ON p.num_maes = t.num_maes
                SET p.OrdenImpresion = t.nuevo_orden
            ";

            $db->prepare($sql)->execute();

            return json_encode(['success' => 1]);
        }
    }
?>

