$(document).ready(function(){

    function areaFirma(){
        var canvas = document.getElementById("ejemplo");
        var ctx = canvas.getContext("2d");
        let dibujando = false;
        
        ctx.strokeStyle = 'black';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';

        function empezarDibujo(e) {
            dibujando = true;
            dibujar(e);
        }

        function pararDibujo() {
            dibujando = false;
            ctx.beginPath();
        }

        function dibujar(e) {
            if (!dibujando) return;

            let x = e.clientX - canvas.offsetLeft;
            let y = e.clientY - canvas.offsetTop;

            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        canvas.addEventListener('mousedown', empezarDibujo);
        canvas.addEventListener('mouseup', pararDibujo);
        canvas.addEventListener('mousemove', dibujar);

        canvas.addEventListener('touchstart', (e) => empezarDibujo(e.touches[0]));
        canvas.addEventListener('touchend', pararDibujo);
        canvas.addEventListener('touchmove', (e) => dibujar(e.touches[0]));

        $("#aceptarVentanaFirma").click(function(){
            const imagenData = canvas.toDataURL("image/png");
            $.ajax({
                type: "POST",
                url: "/Sorteo/Servicios/guardar_firma.php",
                dataType: 'json',
                data: { 
                    imagen: imagenData 
                },
                success: function(response) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    $("#ventanaFirma").hide();
                    $("#ventanaInformacionSistema").show();
                    $("#mensajeSistema").text(response.msg);
                }
            });
        });

        $("#cancelarVentanaFirma").click(function(){
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            $("#ventanaFirma").hide();
        });

        $("#reiniciarVentanaFirma").click(function(){
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    };

    $("#abrirVentanaFirma").click(function(){
        $("#ventanaFirma").show();
        areaFirma();
    });

    $("#cerrarVentana").click(function(){
        $("#ventanaFirma").hide();
    });

    $("#cerrarVentana2").click(function(){
        $("#ventanaInformacionSistema").hide();
    });

    $("#cerrarVentanaInfoSistema").click(function(){
        $("#ventanaInformacionSistema").hide();
    });


});