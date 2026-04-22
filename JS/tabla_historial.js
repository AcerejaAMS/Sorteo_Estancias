$(document).ready(function(){

    function historial_tabla(){
        $.ajax({
        type: "GET",
        url: "/Sorteo/Servicios/tabla_historial.php",
        dataType: "json",
        success: function(response){
            let filas = "";

            $.each(response.datos, function(index, item) {

                filas += `<div class='row border-bottom py-2 hover-row tuplaTabla'>`;
                filas += `<div class='col-1 fila'>${item.id}</div>`;
                filas += `<div class='col-1 fila'>${item.usuario}</div>`;
                filas += `<div class='col-1 fila'>${item.accion}</div>`;
                filas += `<div class='col-1 fila'>${item.fecha}</div>`;
                filas += `<div class='col-2 fila'>${item.nombre}</div>`;
                filas += `<div class='col-2 fila'>${item.rfc}</div>`;
                filas += `<div class='col-3 fila'>${item.plaza}</div>`;
                filas += `<div class='col-1 fila'>${item.origen}</div>`;
                filas +=`</div>`;

            });
            
            
            $("#cuerpoTablaHisto").html(filas);

        }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    }
    
    historial_tabla();

    $("#cuerpoTablaHisto").on("dblclick", ".tuplaTabla", function(){
        var id = $(this).find(".col-1").text();

        $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/mostrar_detalle.php",
        data:{
            id: id,
        },
        dataType: "json",
        success: function(response){

            $("#maestroNombre").text(response.datos.nombre);

            if(response.datos.detalles === null){
                $("#Detalles").text("No hay detalles disponibles para esta acción.");
            } else {
                $("#Detalles").text(response.datos.detalles);
            }

            $("#ventanaDetalles").show();
            
        }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });
    
    $("#cerrarVentana7").click(function(){
        $("#ventanaDetalles").hide();
    });

});