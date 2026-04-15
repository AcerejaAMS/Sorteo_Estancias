<?php
include 'conexion.php';

    class historial{
        public function accion($user, $accion, $url, $nombre, $rfc, $plaza, $origen, $t_a, $detalles){
                        
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "INSERT INTO historial (usuario, accion, tabla_afectada, url_peticion, nombre, rfc, plaza, origen, detalles) VALUES (:usuario, :accion, :t_afectada, :url_p, :nombre, :rfc, :plaza, :origen, :detalles)";
            $query = $db->prepare($sql);
            $query->bindParam(':usuario', $user);
            $query->bindParam(':accion', $accion);
            $query->bindParam(':t_afectada', $t_a);
            $query->bindParam(':url_p', $url);
            $query->bindParam(':nombre', $nombre);
            $query->bindParam(':rfc', $rfc);
            $query->bindParam(':plaza', $plaza);
            $query->bindParam(':origen', $origen);
            $query->bindParam(':detalles', $detalles);
            $query->execute();
        }

        public function mostrar_detalles($id){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT nombre, detalles FROM historial WHERE id=:id";
            $query = $db->prepare($sql);
            $query->bindParam(':id', $id);
            $query->execute();

            $result = $query -> fetch(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos' => $result]);
            }else{
                return NULL;
            }
        }

        public function descubrir_origen($nombre, $rfc, $plaza){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT origen FROM profes_sorteo WHERE nombre=:nombre AND rfc=:rfc AND plaza=:plaza";
            $query = $db->prepare($sql);
            $query->bindParam(':nombre', $nombre);
            $query->bindParam(':rfc', $rfc);
            $query->bindParam(':plaza', $plaza);
            $query->execute();

            $result = $query -> fetch(PDO::FETCH_ASSOC);

            if($result){
                return $result['origen'];
            }else{
                return NULL;
            }
        }

        public function cargar_historial(){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'SELECT * FROM historial';
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos'=>$result]);
            }else{
                return json_encode(['datos'=>NULL]);
            }

        }

        public function buscar_historial($column_busqueda, $a_buscar){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT * FROM historial WHERE $column_busqueda LIKE :buscar";
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
    }
?>