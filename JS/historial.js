$(document).ajaxSend(function(event, xhr, settings) {


    if(settings.url === "/Sorteo/Servicios/retirar_maestros.php"){

        var nombre = $("#infoNombre").text();
        var rfc = $("#infoRFC").text();
        var plaza = $("#infoPlaza").text();
        var origen = $("#infoOrigen").text();

        $.ajax({
            url: "/Sorteo/Servicios/registroHistorial.php",
            method: "POST",
            data: { 
                url: settings.url,
                accion: 'Retirar Maestro',
                nombre: nombre,
                detalles: "",
                rfc: rfc,
                plaza: plaza,
                origen: origen,
                t_a: "profes_sorteo"
            },
            dataType: 'json',
            success: function() {
            }
        });
    }

    if(settings.url === "/Sorteo/Servicios/cambiar_estado_maestros.php"){

        let params = new URLSearchParams(settings.data);

        let nombre = params.get("nombre");
        let rfc = params.get("rfc");
        let plaza = params.get("plaza");
        let detalles = params.get("detalles");
        $.ajax({
            url: "/Sorteo/Servicios/registroHistorial.php",
            method: "POST",
            data: { 
                url: settings.url,
                accion: 'Cambiar Estado Maestro',
                nombre: nombre,
                detalles: detalles,
                rfc: rfc,
                plaza: plaza,
                origen: "",
                t_a: "profes_sorteo"
            },
            dataType: 'json',
            success: function() {
            }
        });
    }

    if(settings.url === "/Sorteo/Servicios/retirar_maestros.php" || settings.url === "/Sorteo/Servicios/cambiar_estado_maestros.php"){

        
        $.ajax({
            url: "/Sorteo/Servicios/cambiar_orden.php",
            method: "POST",
            data:{},
            dataType: 'json',
            success: function(response) {
            }
        });
    }

});