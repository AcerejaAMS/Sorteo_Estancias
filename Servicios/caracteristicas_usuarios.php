<?php
    class generador_usuarios{
        public function comprobacion_RFC($rfc){
            $rfc = strtoupper(trim($rfc));

            $pattern = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/';
            if (preg_match($pattern, $rfc)) {
                return true;
            }
            return false;
        }

        public function generar_password(){
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $pass = "";

            for($i = 0; $i < 8; $i++){
                $pass .= $chars[rand(0, strlen($chars)-1)];
            }

            return $pass;
        }
            
    }
?>