$(document).ready(function(){

    function maestros_tabla(){
        $.ajax({
        type: "GET",
        url: "/Sorteo/Servicios/tabla_maestros.php",
        dataType: "json",
        success: function(response){
            let filas = "";

            if(response.admin){
                $.each(response.datos, function(index, item) {
                    filas += `<div class='row border-bottom py-2 hover-row tuplaTabla'>`;
                    filas += `<div class='col-4 fila'>${item.nombre}</div>`;
                    filas += `<div class='col-3 fila'>${item.rfc}</div>`;
                    filas += `<div class='col-3 fila'>${item.plaza}</div>`;
                    if(item.estado == 1){
                        filas += `<div class='col-2 fila estado'><button value='1' class="cambiarEstadoAdmin">Participante</button></div>`;
                    }else{
                        filas += `<div class='col-2 fila estado'><button value='0' class="cambiarEstadoAdmin">Retirado</button></div>`;
                    }
                    
                    filas +=`</div>`;
                });
            }else{
                $.each(response.datos, function(index, item) {
                    filas += `<div class="row border-bottom py-2 hover-row tuplaTabla" data-rfc="${item.rfc}">`;
                    filas += `<div class='col-4 fila'>${item.nombre}</div>`;
                    filas += `<div class='col-2 fila'>${item.rfc}</div>`;
                    filas += `<div class='col-3 fila'>${item.plaza}</div>`;
                    filas += `<div class='col-2 fila'>${item.ctr}</div>`;
                    filas += `<div class='col-1 fila'>${item.fec_ing}</div>`;
                    filas +=`</div>`;

                });
            }
            $('#cuerpoTabla').html(filas);
        }
        }).fail(function(xhr){

            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    }
    
    maestros_tabla();

    $("#cuerpoTabla").on("dblclick", ".tuplaTabla", function(){
        
        try {
            var ct = $(this).find("div:eq(3)").text();
            var fec_ing = $(this).find("div:eq(4)").text();
        }catch{
            var ct="";
            var fec_ing="";
        }
        finally{
            var nombre = $(this).find("div:eq(0)").text();
            var rfc = $(this).find("div:eq(1)").text();
            var plaza = $(this).find("div:eq(2)").text();
        }
        
        $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/datos_maestros.php",
        data:{
            nombre: nombre,
            rfc: rfc,
            plaza: plaza,
            ct: ct,
            fec_ing: fec_ing
        },
        dataType: "json",
        success: function(response){
            let datosParseados = JSON.parse(response.success);

            $("#infoNombre").text(datosParseados.datos.nombre);
            $("#infoRFC").text(datosParseados.datos.rfc);
            $("#infoPlaza").text(datosParseados.datos.plaza);
            $("#infoCT").text(datosParseados.datos.ctr);
            $("#infoFecIng").text(datosParseados.datos.fec_ing);
            $("#infoMun").text(datosParseados.datos.municipio);
            $("#infoReg").text(datosParseados.datos.region);
            $("#infoOrigen").text(datosParseados.datos.origen);
            $("#infoSindicato").text(datosParseados.datos.sindicato);
            $("#ventanaInformacionMaestro").show();

            if(response.msg=="Firmado"){
                $("#borrarMaestro").prop("disabled", true);
            }
        }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });

    $("#cuerpoTabla").on("click", ".cambiarEstadoAdmin", function(){

        $("#ventanaDetallesHis").show();

        let fila = $(this).closest(".row");

        var nombre = fila.find("div:eq(0)").text();
        var rfc = fila.find("div:eq(1)").text();
        var plaza = fila.find("div:eq(2)").text();
        var estado = $(this).val();

        $("#aceptarCambio").click(function(){
            var detalles = $("#detalles_h").val();

            $.ajax({
            type: "POST",
            url: "/Sorteo/Servicios/cambiar_estado_maestros.php",
            data:{
                nombre: nombre,
                detalles: detalles,
                rfc: rfc,
                plaza: plaza,
                estado: estado
            },
            dataType: "json",
            success: function(response){
                let nuevoEstado = response.susses; 

                // Reemplazamos el botón según el nuevo estado
                if(nuevoEstado == '1') {
                    fila.find('.estado').html(
                        `<button value='1' class="cambiarEstadoAdmin">Participante</button>`
                    );
                } else {
                    fila.find('.estado').html(
                        `<button value='0' class="cambiarEstadoAdmin">Retirado</button>`
                    );
                }
            }
            }).fail(function(xhr){
                console.log("Status:", xhr.status);
                console.log("Response:", xhr.responseText);
                alert("Error en petición");
            });

            $("#ventanaDetallesHis").hide();
        });

        $("#cancelarCambio").click(function(){
            $("#detalles_h").val("");
            $("#ventanaDetallesHis").hide();
        });

        $("#cerrarVentana6").click(function(){
            $("#ventanaDetallesHis").hide();
        });
    });

    $('#borrarMaestro').click(function(){
        $("#ventanaInformacionMaestro").hide();
        $("#ventanaConfirmacion").show();
    });

    $('#aceptarRetirar').click(function(){
        $("#ventanaConfirmacion").hide();

        var nombre = $("#infoNombre").text();
        var rfc = $("#infoRFC").text();
        var plaza = $("#infoPlaza").text();
        var ct = $("#infoCT").text();
        var fec_ing = $("#infoFecIng").text();
        var municipio = $("#infoMun").text();
        var region = $("#infoReg").text();
        var origen = $("#infoOrigen").text();
        var sindicato = $("#infoSindicato").text();

        $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/retirar_maestros.php",
        data:{
            nombre: nombre,
            rfc: rfc,
            plaza: plaza,
            ct: ct,
            fec_ing: fec_ing,
            municipio:municipio,
            region: region,
            origen: origen,
            sindicato:sindicato
        },
        dataType: "json",
        success: function(response){

            let rfc = $("#infoRFC").text().trim();

            $(`[data-rfc='${rfc}']`).fadeOut(300, function(){
                $(this).remove();
            });

            $("#ventanaInformacionSistema2").show();
            $("#mensajeSistema2").text(response.msg);
                }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });

    $('#cancelarRetirar').click(function(){
        $("#ventanaConfirmacion").hide();
        $("#ventanaInformacionMaestro").show();
    });

    $("#cerrarVentana3").click(function(){
        $("#ventanaConfirmacion").hide();
    });

    $("#cerrarVentanaInfoMaestro").click(function(){
        $("#ventanaInformacionMaestro").hide();
    });
    
    $("#cerrarVentana4").click(function(){
        $("#ventanaInformacionSistema").hide();
    });

    $("#cerrarVentanaInfoSistema2").click(function(){
        $("#ventanaInformacionSistema2").hide();
    });
});