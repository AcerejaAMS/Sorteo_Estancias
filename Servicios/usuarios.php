<?php
include 'conexion.php';

    class usuario{
        private $CLAVE = "USET_SEPE_SORTEO2026_Nomina_Educativa";

        public function decryptPassword($data){

            $decoded = base64_decode($data);

            if($decoded === false || strlen($decoded) < 17){
                return false;
            }

            $iv = substr($decoded, 0, 16);
            $encrypted = substr($decoded, 16);

            return openssl_decrypt(
                $encrypted,
                "AES-256-CBC",
                $this->CLAVE,
                OPENSSL_RAW_DATA,
                $iv
            );
        }

        public function login($usuario, $password){

            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
            $query = $db->prepare($sql);
            $query->execute([':usuario' => $usuario]);

            $user = $query->fetch(PDO::FETCH_ASSOC);

            if(!$user){
                return json_encode(['success'=>0, 'msg'=>'Usuario no encontrado']);
            }

            $passwordDescifrada = $this->decryptPassword($user['passwrd']);

            if($passwordDescifrada === false){
                return json_encode([
                    'success'=>0,
                    'msg'=>'Error al desencriptar contraseña'
                ]);
            }

            if ($password === $passwordDescifrada) {

                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['id'] = $user['id'];
                $_SESSION['login'] = true;
                $_SESSION['sindicato'] = $user['sindicato'];
                $_SESSION['admin'] = $user['admin'];
                $_SESSION['estado_firma'] = $user['estado_firma'];

                if($user['admin'] == 1){
                    return json_encode(['success'=>2, 'msg'=>'Login']);
                }

                return json_encode(['success'=>1, 'msg'=>'Login']);

            } else {
                return json_encode(['success'=>0, 'msg'=>'Contraseña incorrecta']);
            }
        }

        public function nuevo_usuario($rfc, $nombre, $passwrd, $origen, $sindicato){
            $iv = random_bytes(16);

            $conec = new conexion();
            $db = $conec->conectar();

            $hash = openssl_encrypt($passwrd, "AES-256-CBC", $this->CLAVE, OPENSSL_RAW_DATA, $iv);
            
            $sql = "INSERT INTO usuarios (rfc, usuario, origen, sindicato, passwrd)
                    VALUES (:rfc, :usuario, :origen, :sindicato, :passwrd)";
            $query = $db->prepare($sql);
            $query->bindParam(':rfc', $rfc);
            $query->bindParam(':usuario', $nombre);
            $query->bindParam(':origen', $origen);
            $query->bindParam(':sindicato', $sindicato);
            $query->bindParam(':passwrd', $hash);

            $query->execute();

            $id = $db->lastInsertId();

            if ($id > 0) {
                return json_encode(['success'=>1, 'msg'=>'Usuario Creado']);
            }else{
                return json_encode(['success'=>0, 'msg'=>'Error']);

            }
            
        }

        public function ver_usuarios(){

            $iv = random_bytes(16);

            $conec = new conexion();
            $db = $conec->conectar();

            $sql = "SELECT id, usuario, rfc, passwrd, sindicato, origen, estado_firma FROM usuarios WHERE admin = 0";
            $query = $db->prepare($sql);
            $query->execute();

            $result = $query -> fetchALL(PDO::FETCH_ASSOC);

            if($result){
                foreach ($result as &$usuario) {
                    $usuario['passwrd'] = $this->decryptPassword($usuario['passwrd']);
                }
                return json_encode(['datos'=>$result]);
            }else{
                return json_encode(['datos'=>[]]);
            }
        }
    }
?>
