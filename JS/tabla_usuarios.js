$(document).ready(function(){

    function usuarios_tabla(){
        $.ajax({
        type: "GET",
        url: "/Sorteo/Servicios/tabla_usuarios.php",
        dataType: "json",
        success: function(response){
            let filas = "";

            $.each(response.datos, function(index, item) {

                filas += `<div class='row border-bottom py-2 hover-row tuplaTabla'>`;
                filas += `<div class='col-1 fila'>${item.id}</div>`;
                filas += `<div class='col-2 fila'>${item.usuario}</div>`;
                filas += `<div class='col-2 fila'>${item.rfc}</div>`;
                filas += `<div class='col-2 fila password-cell'>
                            <span class="password-text" data-pass="${item.passwrd}">••••••</span>
                            <button class="toggle-pass"><ion-icon name="eye-off-outline" class="ojo"></ion-icon></button>
                        </div>`;
                filas += `<div class='col-2 fila'>${item.sindicato}</div>`;
                filas += `<div class='col-2 fila'>${item.origen}</div>`; // Luego ver si se pude poner la imagen imagen y cuando firmo
                if(item.estado_firma == 1){
                    filas += `<div class='col-1 fila'><ion-icon name="checkmark-circle" class="estado-si"></ion-icon></div>`;
                }else{
                    filas += `<div class='col-1 fila'><ion-icon name="close-circle" class="estado-no"></ion-icon></div>`;
                }

                filas +=`</div>`;

            });
            
            $("#cuerpoTablaUsers").html(filas);

        }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    }
    
    usuarios_tabla();

    $("#cuerpoTablaUsers").on('click', '.toggle-pass', function() {
    
        let span = $(this).siblings('.password-text');
        let ojo = $(this).find('ion-icon');
        let realPass = span.data('pass');

        if(span.text() === '••••••'){
            span.text(realPass);
            ojo.attr('name', 'eye-outline');
        } else {
            span.text('••••••');
            ojo.attr('name', 'eye-off-outline');
        }

    });

    $("#cuerpoTablaUsers").on("dblclick", ".tuplaTabla", function(){
        
        var id = $(this).find("div:eq(0)").text();
        
        $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/mostrar_firma.php",
        data:{
            id: id
        },
        dataType: "json",
        success: function(response){
            
            if(response.success == 0){
                $("#mensajeSistemaFF").text(response.msg);
                $("#faltaFirma").show();
            }else if(response.success == 1){
                $("#mensajeSistemaMF").text("Fecha de Firma: " + response.fecha);
                $("#imagenFirma").attr("src", "data:image/png;base64," + response.img);
                $("#mostrarFirma").show();
            }


        }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });

    $("#cerrarVentanaA2").click(function(){
        $("#faltaFirma").hide();
    });

    $("#cerrarVentanaInfoSistemaA2").click(function(){
        $("#faltaFirma").hide();
    });

    $("#cerrarVentanaA1").click(function(){
        $("#mostrarFirma").hide();
    });

    $("#cerrarVentanaInfoSistemaA1").click(function(){
        $("#mostrarFirma").hide();
    });

    $("#abrirModalUsuario").click(function(){
        $("#modalAgregarUsuario").show();
    });

    $("#cerrarVentanaCU").click(function(){
        $("#modalAgregarUsuario").hide();
    });


    
    //Comentario

});