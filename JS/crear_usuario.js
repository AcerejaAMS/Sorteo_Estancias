$(document).ready(function(){
    $("#formAgregarUsuario").submit(function(e){
        e.preventDefault();
        let origen_sindicato = $("#origen_usu").val()

        if (origen_sindicato == "uset"){
            var origen = "USET";
            var sindicato = "31";
        }else{
            var origen = "SEPE";
            var sindicato = "55";
        }

        $.ajax({
            type: "POST",
            url: "/Sorteo/Servicios/crear_usuario.php",
            data: {
                rfc: $("#rfc_u").val(),
                nombre: $("#nombre_u").val(),
                origen:origen,
                sindicato:sindicato 
            },
            dataType: "json",
            success: function(dataJson) {
                $("#modalAgregarUsuario").hide();
                alert(dataJson.msg);
            }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    });

    /// que ya funcione porfis
});