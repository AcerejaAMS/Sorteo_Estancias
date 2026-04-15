$("#formLogin").submit(function(e){
    e.preventDefault();

    $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/login.php",
        data: {
            usuario: $("#usuario").val(),
            passwrd: $("#passwrd").val()
        },
        dataType: "json",
        success: function(dataJson) {
            if(dataJson.success == 1){
                window.location.href = "home.html";
            }if (dataJson.success == 2) {
                window.location.href = "admin.html";
            }else{
                console.log(dataJson);
                $("#alertIngreso").show()
                $("#errorIngreso").show().text(dataJson.msg);
            }
        }
    }).fail(function(xhr){
        console.log("Status:", xhr.status);
        console.log("Response:", xhr.responseText);
        alert("Error en petición");
    });
});