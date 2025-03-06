var urlBase = '../php/';
var urlImpresion = '../php/facturas/verRecibo.php';

var idDocente;


window.addEventListener("load", function () {
    // Manejo de selección de todas las cuotas
    $("#selectAllCuotas").click(function () {
        $(".select-cuota:not(:disabled)").prop('checked', this.checked);
    });

    // Manejo del botón para pagar todas las cuotas seleccionadas
    $("#pagarTodosBtn").click(function () {
        let selectedCuotas = [];
        $(".select-cuota:checked").each(function () {
            selectedCuotas.push($(this).val());
        });

        if (selectedCuotas.length > 0) {
            PagarTodos(selectedCuotas);
        } else {
            alert("Por favor, seleccione al menos una cuota.");
        }
    });

    // Configuración de fecha inicial
    $("#fechaPago_cuota").val(moment().format('YYYY-MM-DD'));

    // Función para obtener parámetros de la URL
    function getUrlParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    // Verificar pago por MercadoPago o Viumi
    const okMP = getUrlParam("payment");
    const okViumi = getUrlParam("payment_viumi");

    if (okMP === "ok" || okViumi === "ok") {
        const idCuota = getUrlParam("id_cuotas");
        const status = getUrlParam(okMP === "ok" ? "collection_status" : "status");
        const payment_id = getUrlParam(okMP === "ok" ? "payment_id" : "operation_id");

        if (idCuota && status === "approved" && payment_id) {
            const datos = JSON.stringify({
                idCuota,
                estadoCuota: "PAGADA",
                paymentId: payment_id
            });

            const apiEndpoint = okMP === "ok" ? "./api/RegistrarPagoMP.php" : "./api/RegistrarPagoViumi.php";

            $.post(apiEndpoint, { datos })
                .then(() => {
                    return $.post('https://cdocente.com.ar/intranet/AFIP/facturar.php', { idCuota });
                })
                .then(() => {
                    $("#modalPagoRegistrado").modal("show");
                    CargarPagos();
                })
                .catch((error) => {
                    console.error("Error al registrar el pago o facturar:", error);
                    alert("Hubo un problema al procesar el pago.");
                });
        } else {
            console.error("Faltan parámetros o el estado del pago no es aprobado.");
        }
    }
});


const login = () => {
    let dni = document.getElementById('dni_docente').value;
    $.post(urlBase+"nuevaVenta/BuscarDocente.php",{dni})
    .then((res)=>{
        if(res!="[]"){ //Está el docente, damos login y guardamos el id del Docente
            var data=JSON.parse(res);
            data=data[0];
            idDocente = data.id_docente;
            url = "https://cdocente.com.ar/intranet/docentes/dashboard.html?id="+idDocente;
            window.location.href = url;
        }else{ // No existe docente, vaciamos campos y mostramos un error.
            $("#docenteNoEncontrado").modal('show');
        }
    })
}

const CargarPagos = () => {
    var id = location.search.split('id=')[1];
    if(id){
        $.post(urlBase+"docentes/ObtenerDocente.php",{idDocente:id})
        .then((data)=>{
            data = JSON.parse(data);
            data = data[0];
            let docente = data.apellido_docente + " " + data.nombre_docente;
            $("#docente").html("USUARIO: "+docente.toUpperCase())
            $.post(urlBase + "docentes/ObtenerCuotas.php", { idDocente: id })
            .then((data) => {
                let estado = '';
                $(".filaCuotas").remove();
                if (data) {
                    data = JSON.parse(data);
                    data.forEach(e => {
                        acciones = '';
                        switch (e.estado_cuota) {
                            case "1":
                                estado = '<span class="badge bg-light-danger text-danger w-100 estado btnPagar" onclick="PagarModal(' + e.id_cuota + ');" style="font-size:1em"> PAGAR </span>';
                                break;
                            case "2":
                                estado = '<span class="badge bg-light-success text-success w-100 estado"> PAGADA </span>';
                                break;
                        }
                        if (e.idFactura_cuota != 0) {
                            acciones = '<a href="javascript:;" onclick="ImprimirVenta(' + e.idFactura_cuota + ')" class="text-success" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-bs-original-title="Imprimir Venta" aria-label="Imprimir"><i class="bx bx-printer"></i></a>';
                        }

                        var htmlTags = '<tr class="filaCuotas">' +
                            '<td><input type="checkbox" class="select-cuota" ' + (e.estado_cuota == 2 ? 'disabled' : '') + ' value="' + e.id_cuota + '"></td>'+
                            '<td>' + e.numero_cuota + '</td>' +
                            '<td>' + moment(e.vto_cuota).format('DD/MM/YYYY') + '</td>' +
                            '<td> $ ' + parseFloat(e.monto_cuota).toFixed(2) + '</td>' +
                            '<td>' + estado + '</td>' +
                            '<td>' + acciones + '</td>' +
                            '</tr>';
                        $('#cuotas tbody').append(htmlTags);
                    });
                }
            })

        })
    }else{
        url = "://intranet.cdocente.com.ar/docentes/index.html";
    }
}

const PagarModal = (idCuota) => {
    $.post("./api/GenerarLink.php",{idCuota})
    .then((data)=> {
        data = JSON.parse(data);
        $("#url_mp").val(data.mercadopago.url);
        $("#id_cuota_transferencia").val(idCuota);
        $("#preference_id").val(data.mercadopago.id);
        $("#url_viumi").val(data.viumi.url);
        $("#viumi_id").val(data.viumi.id);
    })
    $("#modalMetodoPago").modal('show');
}

const PagarMP = () => {
    let url = $("#url_mp").val();
    let prf_id = $("#preference_id").val();
    let idCuota = $("#id_cuota_transferencia").val();
    $.post("./api/AsociarMP.php",{idCuota,prf_id})
    .then(()=> {
        window.location.href = url;
    })
}

const PagarViumi = () => {
    let url = $("#url_viumi").val();
    let prf_id = $("#viumi_id").val();
    let idCuota = $("#id_cuota_transferencia").val();
    $.post("./api/AsociarViumi.php",{idCuota,prf_id})
    .then(()=> {
        window.location.href = url;
    })
}

const GuardarTransferencia = () => {
    let idCuota = $("#id_cuota_transferencia").val();
    let fechaPago = $("#fechaPago_cuota").val();
    let comprobante = document.getElementById('comprobante_cuota').value;
    if(idCuota && fechaPago && comprobante){
        let formData = new FormData(document.getElementById("formNuevoComprobante"));
        formData.append('id_cuota',idCuota);
        formData.append('fechaPago',fechaPago);
        $.ajax({
          url: "./api/SubirComprobante.php",
          type: "post",
          dataType: "html",
          data: formData,
          cache: false,
          contentType: false,
          processData: false
        }).done(function(res){
          $("#modalInformarTrasferencia").modal('hide');
          $("#modalTransferenciaInformada").modal('show');
          $('body').removeClass('modal-open');
          $('.modal-backdrop').remove();
          document.getElementById('comprobante_cuota').value = '';
        }); 
    }else{
        Lobibox.notify('error', {
            pauseDelayOnHover: true,
            continueDelayOnInactiveTab: false,
            position: 'top right',
            icon: 'bx bx-message-error',
            msg: 'Error. Debe completar la fecha de pago y subir el comprobante.',
          });
    }

}

function ImprimirVenta(idFactura){
    window.open(urlImpresion+'?idFactura='+idFactura, '_blank');
}

const PagarTodos = (cuotas) => {
    if(cuotas.length>1){
        $.post("./api/GenerarLinksMasivos.php",{idCuotas:JSON.stringify(cuotas)})
        .then((data)=> {
            data = JSON.parse(data);
            $("#url_mp").val(data.mercadopago.url);
            $("#id_cuota_transferencia").val(idCuota);
            $("#preference_id").val(data.mercadopago.id);
            $("#url_viumi").val(data.viumi.url);
            $("#viumi_id").val(data.viumi.id);
        })
        $("#modalMetodoPago").modal('show');
    }
    if(cuotas.length==1){
        $.post("./api/GenerarLink.php",{idCuota:cuotas[0]})
        .then((data)=> {
            data = JSON.parse(data);
            $("#url_mp").val(data.mercadopago.url);
            $("#id_cuota_transferencia").val(idCuota);
            $("#preference_id").val(data.mercadopago.id);
            $("#url_viumi").val(data.viumi.url);
            $("#viumi_id").val(data.viumi.id);
        })
        $("#modalMetodoPago").modal('show');
    }
}
