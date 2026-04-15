$(document).ready(function(){
    $("#imprimirFormatos").click(function(){
        $.ajax({
            type: "POST",
            url: "/Sorteo/Servicios/verificar_todos_firmados.php",
            dataType: "json",
            success: function(response) {
                let res = response.resultado.pendiente;

                if(res == 1){
                    let msg = "Para poder imprimir el documento debe contar con todas las firmas";
                    $("#ventanaInformacionSistema3").show();
                    $("#mensajeSistema3").text(msg);
                }else{
                    fetch('/Sorteo/Servicios/imprimir.php', {
                        method: 'POST'
                    })
                    .then(res => res.blob())
                    .then(blob => {
                        const url = URL.createObjectURL(blob);
                        window.open(url);
                    })
                    .catch(err => console.error(err));
                }
            }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });

    $("#cerrarVentana5").click(function(){
        $("#ventanaInformacionSistema3").hide();
    });

    $("#cerrarVentanaInfoSistema3").click(function(){
        $("#ventanaInformacionSistema3").hide();
    });
});
