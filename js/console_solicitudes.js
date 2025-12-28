var tbl_solicitudes;
function listar_solicitudes() {
    tbl_solicitudes = $("#tabla_solicitudes").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": { "regex": false },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 10,
        "destroy": true,
        "async": false,
        "processing": true,
        "ajax": {
            "url": "../controller/controlador_listar_solicitudes.php",
            "type": 'POST'
        },
        "columns": [
            { "data": "id_solicitud" },
            { "data": "nombre_completo" },
            { "data": "email" },
            { "data": "telefono" },
            {
                "data": "nivel_interes",
                "render": function(data) {
                    if (data === 'inicial') {
                        return '<span class="badge badge-info">Inicial</span>';
                    } else if (data === 'primaria') {
                        return '<span class="badge badge-primary">Primaria</span>';
                    } else if (data === 'secundaria') {
                        return '<span class="badge badge-success">Secundaria</span>';
                    }
                    return data;
                }
            },
            { "data": "mensaje" },
            { "data": "fecha_formateada" },
            {
                "data": "estado",
                "render": function(data) {
                    if (data === 'PENDIENTE') {
                        return '<span class="badge badge-warning">PENDIENTE</span>';
                    } else if (data === 'CONTACTADO') {
                        return '<span class="badge badge-info">CONTACTADO</span>';
                    } else if (data === 'ATENDIDO') {
                        return '<span class="badge badge-success">ATENDIDO</span>';
                    } else if (data === 'CANCELADO') {
                        return '<span class="badge badge-danger">CANCELADO</span>';
                    }
                    return data;
                }
            },
            {
                "defaultContent": "<button class='ver btn btn-primary btn-sm' title='Ver detalles'><i class='fa fa-eye'></i></button>&nbsp;<button class='editar btn btn-success btn-sm' title='Cambiar estado'><i class='fa fa-edit'></i></button>"
            }
        ],
        "language": idioma_espanol,
        select: true
    });
    document.getElementById("tabla_solicitudes_filter").style.display = "none";
    $('input.global_filter').on('keyup click', function() {
        filterGlobal();
    });
    $('input.column_filter').on('keyup click', function() {
        filterColumn($(this).parents('tr').attr('data-column'));
    });
}

function filterGlobal() {
    $('#tabla_solicitudes').DataTable().search(
        $('#global_filter').val(),
    ).draw();
}

// Ver detalles de la solicitud
$('#tabla_solicitudes').on('click', '.ver', function() {
    var data = tbl_solicitudes.row($(this).parents('tr')).data();
    
    if (tbl_solicitudes.row(this).child.isShown()) {
        var data = tbl_solicitudes.row(this).data();
    }
    
    $("#modal_ver_solicitud").modal('show');
    document.getElementById('txt_id_solicitud').value = data.id_solicitud;
    document.getElementById('txt_nombre_ver').value = data.nombre_completo;
    document.getElementById('txt_email_ver').value = data.email;
    document.getElementById('txt_telefono_ver').value = data.telefono;
    document.getElementById('txt_nivel_ver').value = 
        data.nivel_interes === 'inicial' ? 'Educación Inicial' :
        data.nivel_interes === 'primaria' ? 'Educación Primaria' :
        'Educación Secundaria';
    document.getElementById('txt_mensaje_ver').value = data.mensaje;
    document.getElementById('txt_fecha_ver').value = data.fecha_formateada;
    document.getElementById('txt_estado_ver').value = data.estado;
    document.getElementById('txt_observaciones_ver').value = data.observaciones || '';
});

// Editar estado de la solicitud
$('#tabla_solicitudes').on('click', '.editar', function() {
    var data = tbl_solicitudes.row($(this).parents('tr')).data();
    
    if (tbl_solicitudes.row(this).child.isShown()) {
        var data = tbl_solicitudes.row(this).data();
    }
    
    $("#modal_editar_solicitud").modal('show');
    document.getElementById('txt_id_solicitud_editar').value = data.id_solicitud;
    document.getElementById('txt_nombre_editar').innerHTML = data.nombre_completo;
    document.getElementById('select_estado').value = data.estado;
    document.getElementById('txt_observaciones_editar').value = data.observaciones || '';
});

// Actualizar estado
function Actualizar_Estado_Solicitud() {
    var id = document.getElementById('txt_id_solicitud_editar').value;
    var estado = document.getElementById('select_estado').value;
    var observaciones = document.getElementById('txt_observaciones_editar').value;
    
    if (estado.length == 0) {
        return Swal.fire("Mensaje de Advertencia", "Seleccione un estado", "warning");
    }
    
    $.ajax({
        url: '../controller/controlador_actualizar_estado_solicitud.php',
        type: 'POST',
        data: {
            id: id,
            estado: estado,
            observaciones: observaciones
        }
    }).done(function(resp) {
        if (resp > 0) {
            Swal.fire("Mensaje de Confirmación", "Estado actualizado correctamente", "success").then((value) => {
                tbl_solicitudes.ajax.reload();
                $("#modal_editar_solicitud").modal('hide');
            });
        } else {
            Swal.fire("Mensaje de Error", "No se pudo actualizar el estado", "error");
        }
    });
}

// Filtrar por estado
function Filtrar_Por_Estado() {
    var estado = document.getElementById('select_filtro_estado').value;
    
    if (estado === '') {
        tbl_solicitudes.ajax.url('../controller/controlador_listar_solicitudes.php').load();
    } else {
        tbl_solicitudes.ajax.url('../controller/controlador_listar_solicitudes.php?estado=' + estado).load();
    }
}

// Obtener estadísticas
function Cargar_Estadisticas() {
    $.ajax({
        url: '../controller/controlador_estadisticas_solicitudes.php',
        type: 'POST'
    }).done(function(resp) {
        if(resp) {
            var data = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            document.getElementById('stat_total').innerHTML = data.total || 0;
            document.getElementById('stat_pendientes').innerHTML = data.pendientes || 0;
            if(document.getElementById('stat_contactados')) document.getElementById('stat_contactados').innerHTML = data.contactados || 0;
            document.getElementById('stat_atendidos').innerHTML = data.atendidos || 0;
            document.getElementById('stat_hoy').innerHTML = data.hoy || 0;
        }
    });
}

var idioma_espanol = {
    "sProcessing": "Procesando...",
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sEmptyTable": "Ningún dato disponible en esta tabla",
    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
    "sInfoPostFix": "",
    "sSearch": "Buscar:",
    "sUrl": "",
    "sInfoThousands": ",",
    "sLoadingRecords": "Cargando...",
    "oPaginate": {
        "sFirst": "Primero",
        "sLast": "Último",
        "sNext": "Siguiente",
        "sPrevious": "Anterior"
    },
    "oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    }
}
