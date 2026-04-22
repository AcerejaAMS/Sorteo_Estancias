<?php
include 'conexion.php';

    class usuario_firma{
        public function subir_firma($usuario, $img){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $tamanoKB = strlen($img) / 1024;
            
            if($tamanoKB > 2){
                if($usuario != ''){
                    $sql = 'UPDATE usuarios SET imagen = :img, fecha_firma=:fecha, estado_firma=1  WHERE usuario = :usuario';
                    $query = $db->prepare($sql);
                    $query->bindParam(':usuario', $usuario);
                    $query->bindParam(':img', $img);
                    date_default_timezone_set("America/Mexico_City");
                    $fecha = date("Y-m-d H:i:s");
                    $query->bindParam(':fecha', $fecha);
                    $query->execute();

                    return json_encode(['success'=>1, 'msg'=>'Guardado Correctamente']);
                }
            }

            return json_encode(['success'=>0, 'msg'=>'Ocurrio un error al guardar']);

        }

        public function todos_firmados(){
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT EXISTS(
                SELECT 1 FROM usuarios WHERE estado_firma = 0
                ) AS pendiente";
            
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetch(PDO::FETCH_ASSOC);

            return json_encode(["resultado"=>$result]);
        }

        public function mostrar_firma($id){

            $conec = new conexion();
            $db = $conec->conectar($conec);

            $sql = "SELECT imagen, fecha_firma FROM usuarios WHERE estado_firma = 1 AND id=:id";
            $query = $db->prepare($sql);
            $query->bindParam(':id', $id);
            $query->execute();
            $result = $query -> fetch(PDO::FETCH_ASSOC);

            if($result){
                $imagen = base64_encode($result['imagen']);
                return json_encode(["success" => 1, "img" => $imagen, "msg" => "Imagen", "fecha" => $result['fecha_firma']]);
            }else{
                return json_encode(["success" => 0, "img" => [], "msg" => "Aun no se ha firmado", "fecha" => []]);
            }
        }
    }
?>