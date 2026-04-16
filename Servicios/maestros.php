<?php
include 'conexion.php';

    class maestro{
        public function cargar_maestros($sin){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'SELECT nombre, rfc, plaza, ctr, fec_ing FROM profes_sorteo WHERE estado=1 AND sindicato=:sindicato';
            $query = $db->prepare($sql);
            $query->bindParam(':sindicato', $sin);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos'=>$result]);
            }else{
                return json_encode(['datos'=>NULL]);
            }

        }

        public function cargar_todos_maestros(){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'SELECT nombre, rfc, plaza, estado FROM profes_sorteo';
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos' => $result, 'admin' => 1]);
            }else{
                return json_encode(NULL);
            }
        }

        public function datos_maestro($nom, $rfc, $plz, $ctr, $fec_ing){
            
            $conec = new conexion();
            $db = $conec->conectar();

            if($ctr == "" || $fec_ing == ""){
                $sql = 'SELECT nombre, rfc, plaza, ctr, fec_ing, municipio, region, origen, sindicato FROM profes_sorteo WHERE nombre = :nombre AND rfc = :rfc AND plaza = :plaza';
                $query = $db->prepare($sql);
                $query->bindParam(':nombre', $nom);
                $query->bindParam(':rfc', $rfc);
                $query->bindParam(':plaza', $plz);
            }else{
                $sql = 'SELECT nombre, rfc, plaza, ctr, fec_ing, municipio, region, origen, sindicato FROM profes_sorteo WHERE nombre = :nombre AND rfc = :rfc AND plaza = :plaza AND ctr = :ctr AND fec_ing = :fec_ing';
                $query = $db->prepare($sql);
                $query->bindParam(':nombre', $nom);
                $query->bindParam(':rfc', $rfc);
                $query->bindParam(':plaza', $plz);
                $query->bindParam(':ctr', $ctr);
                $query->bindParam(':fec_ing', $fec_ing);
            }
            
            $query->execute();

            $result = $query -> fetch(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos'=>$result]);
            }else{
                return json_encode(['datos'=>NULL]);
            }

        }

        public function buscar_maestro($column_busqueda, $a_buscar){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT nombre, rfc, plaza, ctr, fec_ing FROM profes_sorteo WHERE estado=1 AND $column_busqueda LIKE :buscar";
            $query = $db->prepare($sql);
            $buscar = "%" . trim($a_buscar) . "%";
            $query->bindParam(':buscar', $buscar);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos'=>$result]);
            }else{
                return json_encode(['datos'=>NULL]);
            }
        }

        public function buscar_maestro_admin($column_busqueda, $a_buscar){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT nombre, rfc, plaza, estado FROM profes_sorteo WHERE $column_busqueda LIKE :buscar";
            $query = $db->prepare($sql);
            $buscar = "%" . trim($a_buscar) . "%";
            $query->bindParam(':buscar', $buscar);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos'=>$result, 'admin' => 1]);
            }else{
                return json_encode(['datos'=>NULL]);
            }
        }

        public function cambiar_estado($nom, $rfc, $plz, $ctr, $fec_ing, $mun, $reg, $ori, $sin){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'UPDATE profes_sorteo SET estado=0 WHERE 
                nombre = :nombre AND 
                rfc = :rfc AND 
                plaza = :plaza AND 
                ctr = :ctr AND 
                fec_ing = :fec_ing AND
                municipio = :municipio AND
                region = :region AND
                origen = :origen AND
                sindicato = :sindicato
            ';
            $query = $db->prepare($sql);
            $query->bindParam(':nombre', $nom);
            $query->bindParam(':rfc', $rfc);
            $query->bindParam(':plaza', $plz);
            $query->bindParam(':ctr', $ctr);
            $query->bindParam(':fec_ing', $fec_ing);
            $query->bindParam(':municipio', $mun);
            $query->bindParam(':region', $reg);
            $query->bindParam(':origen', $ori);
            $query->bindParam(':sindicato', $sin);
            $query->execute();

            $result = $query -> rowCount();

            if($result<=0){
                return json_encode(['susses'=>0, 'msg'=>"Ocurrio un error al retirar de la lista"]);
                
            }else{
                return json_encode(['susses'=>1, 'msg'=>"Maestro retirado de la lista"]);
            }

        }
        public function cambiar_estado_admin($nom, $rfc, $plz, $edo){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'UPDATE profes_sorteo SET estado=1-estado WHERE 
                nombre = :nombre AND 
                rfc = :rfc AND 
                plaza = :plaza
            ';
            $query = $db->prepare($sql);
            $query->bindParam(':nombre', $nom);
            $query->bindParam(':rfc', $rfc);
            $query->bindParam(':plaza', $plz);
            // $query->bindParam(':estado', $edo);

            $query->execute();

            $result = $query -> rowCount();

            if($edo == '1'){
                return json_encode(['susses'=>0]);
                
            }else{
                return json_encode(['susses'=>1]);
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