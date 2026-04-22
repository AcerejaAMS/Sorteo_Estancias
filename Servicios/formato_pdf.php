<?php
    require('fpdf.php');
    
    class PDF extends FPDF{

        public function Header(){
            
            $this->Image('../img/Fondo Carta.jpg',0,0,210,297);
            $this->Ln(23);
        }

        public function Footer(){
            $firmas = $this->firmas ?? [];

            $this->SetY(-60);

            $x_izq = 2;
            $x_der = 175;

            $y_base = $this->GetY();

            $max_por_lado = ceil(count($firmas ?? [])/2);

            for($i = 0; $i < count($firmas); $i++){

                if(empty($firmas[$i]['imagen'])){
                    continue;
                }

                $ruta = sys_get_temp_dir() . "/firma_$i.png";

                file_put_contents($ruta, $firmas[$i]['imagen']);

                if(!file_exists($ruta)){
                    continue;
                }

                if($i < $max_por_lado){
                    $this->Image($ruta, $x_izq, $y_base + ($i*10), 30, 10);
                } else {
                    $pos = $i - $max_por_lado;
                    $this->Image($ruta, $x_der, $y_base + ($pos*10), 30, 10);
                }
            }

            $this->SetY(10);
            $this->SetFont('Arial','I',10);
            $this->Cell(0,10,'Pag '.$this->PageNo(),0,0,'R');
        }
    }
?>