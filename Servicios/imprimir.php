<?php
    include 'conecciones_impresion.php';
    include 'formato_pdf.php';

    $datos = new datos_impresion();
    $datos -> cambiar_orden();
    $maestros_imprimir = $datos -> cargar_todos_maestros();
    $firmas_recuperadas = $datos -> recuperar_todas_firmas();
    $regiones = $datos -> nombres_region();
    $regiones = json_decode($regiones, true);

    $pdf = new PDF();
    $pdf->firmas = $firmas_recuperadas;
    $pdf->SetMargins(15,25,15);
    $pdf->SetAutoPageBreak(true,25); 

    $region_actual = null;
    $region_count = null;
    $total_count = 0;

    $data = json_decode($maestros_imprimir, true);
    $numero = 1;

    foreach($data as $fila){

        $total_count+=1;

        if($region_actual != $fila["region"]){
            if ($region_count != 0){
                $pdf->Ln(3);
                $pdf->SetFont('Arial','B',11);
                $pdf->Cell(0,8,'SUBTOTAL DE REGISTROS: '.$region_count,0,1,'R');
                $pdf->Ln(5);
            }

            $region_count = 0;
            $region_actual = $fila["region"];
            $nom_region_actual = $regiones[$region_actual];

            $pdf->AddPage();

            $pdf->SetFont('Arial','B',11);
            $pdf->Cell(0,10,utf8_decode('UNIDAD DE SERVICIOS EDUCATIVOS DEL ESTADO DE TLAXCALA'),0,1,'C');
            $pdf->Cell(0,10,utf8_decode('DIRECCIÓN DE RELACIONES LABORALES ** DEPARTAMENTO DE NÓMINA EDUCATIVA'),0,1,'C');
            $pdf->Cell(0,10,'** LISTADO DE MAESTROS  **  SORTEO 15 DE MAYO DE 2026 **',0,1,'C');

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,10,utf8_decode('REGION '.$region_actual.' - '.$nom_region_actual),0,1,'C');

            // encabezado tabla
            $pdf->SetFont('Arial','B',10);

            $x = (210 - 146) / 2;
            $pdf -> SetX($x);
            $pdf->Cell(12,10,'No.',0,0,'C');
            $pdf->Cell(34,10,'RFC',0,0,'C');
            $pdf->Cell(17,10,'Origen',0,0,'C');
            $pdf->Cell(34,10,'CTR',0,0,'C');
            $pdf->Cell(49,10,'Nombre',0,1,'C');

            $pdf->Line(35,$pdf->GetY(),175,$pdf->GetY());

            $pdf->SetFont('Arial','',9);

        }
        if($pdf->GetY() > 230){ 
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,10,utf8_decode('REGION '.$region_actual.' - '.$nom_region_actual),0,1,'C');

            $x = (210 - 146) / 2;
            $pdf -> SetX($x);
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(12,10,'No.',0,0,'C');
            $pdf->Cell(34,10,'RFC',0,0,'C');
            $pdf->Cell(17,10,'Origen',0,0,'C');
            $pdf->Cell(34,10,'CTR',0,0,'C');
            $pdf->Cell(49,10,'Nombre',0,1,'C');
            $pdf->Line(35,$pdf->GetY(),175,$pdf->GetY());
            $pdf->SetFont('Arial','',9);
        }
        $x = (210 - 146) / 2;
        $pdf -> SetX($x);
        $y = $pdf->GetY();

        $pdf->MultiCell(12,8,utf8_decode($fila['OrdenImpresion']),0,'C');
        $y1 = $pdf->GetY();
        $pdf->SetXY($x+12,$y);

        $pdf->MultiCell(34,8,utf8_decode($fila['rfc']),0,'C');
        $y1 = $pdf->GetY();
        $pdf->SetXY($x+46,$y);

        $pdf->MultiCell(17,8,utf8_decode($fila['origen']),0,'C');
        $y2 = $pdf->GetY();
        $pdf->SetXY($x+63,$y);

        $pdf->MultiCell(34,8,utf8_decode($fila['ctr']),0,'C');
        $y3 = $pdf->GetY();
        $pdf->SetXY($x+97,$y);

        $pdf->MultiCell(49,8,utf8_decode($fila['nombre']),0,'L');
        $y4 = $pdf->GetY();

        $maxY = max($y1,$y2,$y3,$y4);
        $pdf->SetXY($x,$maxY);
        $region_count+=1;
        $numero+=1;

    }   

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,8,'SUBTOTAL DE REGISTROS: '.$region_count,0,1,'R');
    $pdf->Cell(0,8,'TOTAL DE REGISTROS: '.$total_count,0,1,'R');
    $pdf->Ln(5);

    $pdf->Output();

?>