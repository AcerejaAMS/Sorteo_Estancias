$(document).ready(function(){
    $("#formBuscarH").submit(function(e){
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: "/Sorteo/Servicios/buscar_historial.php",
            dataType: "json",
            data: {
                columna: $("#tipoH").val(),
                buscar: $("#buscarH").val(),
            },
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
                
                $('#cuerpoTablaHisto').html(filas);
            }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });
});