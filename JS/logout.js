$("#formLogout").submit(function(e){
    e.preventDefault();

    $.ajax({
        type: "POST",
        url: "/Sorteo/Servicios/logout.php",
        dataType: "json",
        success: function() {
            window.location.href = "index.html";
        }
    }).fail(function(xhr){
        console.log("Status:", xhr.status);
        console.log("Response:", xhr.responseText);
        alert("Error en petición");
    });
});