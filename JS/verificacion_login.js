$(document).ready(function(){

    function verificar(){
        $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/verificacion_login.php",
        dataType: "json",
        success: function(dataJson){

            if(dataJson.session === null){
                window.location.href = "index.html";
            }else{
                $("#nombreUsuario").text(dataJson.session.usuario);

                if (dataJson.session.admin == 1){
                    $("#Sindicato").text("Admin");
                    window.admin = true;
                }else{
                    $("#Sindicato").text(dataJson.session.sindicato);
                    window.admin = false;
                }
            }

            const url = window.location.href;

            if (url.includes("admin.html") && window.admin == false){
                window.location.href = "home.html";
            }
            if (url.includes("home.html") && window.admin == true){
                window.location.href = "admin.html";
            }
        }
        }).fail(function(xhr){
            console.log("Status:", xhr.status);
            console.log("Response:", xhr.responseText);
            alert("Error en petición");
        });
    }

    verificar();
});
