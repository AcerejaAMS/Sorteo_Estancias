<?php
include 'conexion.php';

    class usuario{
        public function login($usuario, $password){
            
            $conec = new conexion();
            $db = $conec->conectar();

            $sql = 'SELECT * FROM usuarios WHERE usuario = :usuario';
            $query = $db->prepare($sql);
            $query->bindParam(':usuario', $usuario);
            $query->execute();

            $result = $query -> fetch(PDO::FETCH_ASSOC);

                //$hash = password_hash($_POST['passwrd'], PASSWORD_DEFAULT, ['cost' => 10]);
            if($result){
                if($password === $result['passwrd']){
                    $_SESSION['usuario'] = $result['usuario'];
                    $_SESSION['id'] = $result['id'];
                    $_SESSION['login']=true;
                    $_SESSION['sindicato']=$result['sindicato'];
                    $_SESSION['admin']=$result['admin'];
                    $_SESSION['estado_firma']=$result['estado_firma'];
                    
                    if($_SESSION['admin'] == 1){
                        return json_encode(['success'=>2, 'msg'=>'Login']);
                    }

                    return json_encode(['success'=>1, 'msg'=>'Login']);
                }else{
                    return json_encode(['success'=>0, 'msg'=>'Contraseña incorrecta']);
                }
            }else{
                return json_encode(['success'=>0, 'msg'=>'Usuario no encontrado']);
            }
        }

        public function nuevo_usuario($rfc, $nombre, $passwrd, $origen, $sindicato){
            $conec = new conexion();
            $db = $conec->conectar();

            
            $sql = "INSERT INTO usuarios (rfc, usuario, origen, sindicato, passwrd)
                    VALUES (:rfc, :usuario, :origen, :sindicato, :passwrd)";
            $query = $db->prepare($sql);
            $query->bindParam(':rfc', $rfc);
            $query->bindParam(':usuario', $nombre);
            $query->bindParam(':origen', $origen);
            $query->bindParam(':sindicato', $sindicato);
            $query->bindParam(':passwrd', $passwrd);

            $query->execute();

            $id = $db->lastInsertId();

            if ($id > 0) {
                return json_encode(['success'=>1, 'msg'=>'Usuario Creado']);
            }else{
                return json_encode(['success'=>0, 'msg'=>'Error']);

            }
            
        }

        public function ver_usuarios(){

            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT id, usuario, rfc, passwrd, sindicato, origen, estado_firma FROM usuarios WHERE admin = 0";
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                return json_encode(['datos'=>$result]);
            }else{
                return json_encode(['datos'=>[]]);
            }
        }
    }
?>