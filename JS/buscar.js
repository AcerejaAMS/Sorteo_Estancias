$(document).ready(function(){
    $("#formBuscar").submit(function(e){
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: "/Sorteo/Servicios/buscar_maestro.php",
            dataType: "json",
            data: {
                columna: $("#tipo").val(),
                buscar: $("#buscar").val(),
            },
            success: function(response){
                let filas = "";
                if(response.admin){
                    $.each(response.datos, function(index, item) {
                        filas += `<div class='row border-bottom py-2 hover-row tuplaTabla'>`;
                        filas += `<div class='col-4'>${item.nombre}</div>`;
                        filas += `<div class='col-3'>${item.rfc}</div>`;
                        filas += `<div class='col-3'>${item.plaza}</div>`;
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
                        filas += `<div class='col-4'>${item.nombre}</div>`;
                        filas += `<div class='col-2'>${item.rfc}</div>`;
                        filas += `<div class='col-3'>${item.plaza}</div>`;
                        filas += `<div class='col-2'>${item.ctr}</div>`;
                        filas += `<div class='col-1'>${item.fec_ing}</div>`;
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
    });
});