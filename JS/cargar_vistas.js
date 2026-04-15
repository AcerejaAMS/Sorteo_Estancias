$(document).ready(function(){
    function cargarIframe(url) {
        const contenedor = document.getElementById('contenidoAdmin');

        const iframe = document.createElement('iframe');
        iframe.src = url + '?v=' + new Date().getTime();
        iframe.style.width = '100%';
        iframe.style.border = '0';
        iframe.style.height = '100%';
        iframe.id = 'miIframe';


        contenedor.replaceChildren(iframe);
    }

    cargarIframe('historial_admin.html');

    $("#botonUsr").click(function(){
        cargarIframe('usuario_admin.html');
        cerrar();
    });

    $("#botonHis").click(function(){
        cargarIframe('historial_admin.html');
        cerrar();

    });

    function accionar(){
        let menu = document.getElementById("menuMts");
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    }

    function cerrar(){
        let menu = document.getElementById("menuMts");
        if (menu && menu.style.display === "block") {
            menu.style.display = "none";
        }
    }



    $("#botonMts").click(function(){
        accionar();
    });

    $("#opcionVer").click(function(e){
        e.preventDefault();
        cargarIframe('maestros_admin.html');
        accionar();
    });

    $("#opcionCargar").click(function(e){
        e.preventDefault();
        cargarIframe('carga_maestro.html');
        accionar();
    });
});