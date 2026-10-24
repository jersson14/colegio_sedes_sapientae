// Categorías de ingresos y egresos agrupadas en el plan de cuentas (Fase 8)
var tbl_indicadores;
var indicadorGrupos = [];

function indEsc(t) {
  return $('<div>').text(t == null ? '' : t).html();
}

// Selector de grupo según el tipo elegido (INGRESOS → 4.x, GASTOS → 5.x)
function Cargar_Grupos(tipo, destino, seleccionado) {
  let op = "<option value=''>Seleccionar</option>";
  indicadorGrupos.filter(function(g) { return g.tipo_indicador === tipo; }).forEach(function(g) {
    op += "<option value='" + g.id_indicadores + "'" + (String(g.id_indicadores) === String(seleccionado) ? ' selected' : '') + ">"
        + indEsc(g.codigo + ' ' + g.nombre) + "</option>";
  });
  $(destino).html(op);
}

function listar_nivel_academico() {
  $.ajax({ url: "../controller/indicadores/controlador_listar_grupos.php", type: 'POST' }).done(function(r) {
    indicadorGrupos = r.data;
  });
  tbl_indicadores = $("#tabla_indicadores").DataTable({
    "ordering": false,
    "bLengthChange": true,
    "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "Todos"]],
    "pageLength": 25,
    "destroy": true,
    pagingType: 'full_numbers',
    responsive: true,
    "processing": true,
    "ajax": {
      "url": "../controller/indicadores/controlador_listar_indicadores.php",
      type: 'POST'
    },
    dom: 'Bfrtip',
    buttons: [
      { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> ', titleAttr: 'Exportar a Excel', title: 'PLAN DE CATEGORÍAS' },
      { extend: 'print', text: '<i class="fa fa-print"></i> ', titleAttr: 'Imprimir', title: 'PLAN DE CATEGORÍAS' }
    ],
    "columns": [
      { "data": "codigo", render: function(data, type, row) { return row.es_grupo == 1 ? '<b>' + indEsc(data) + '</b>' : indEsc(data); } },
      { "data": "tipo_indicador", render: function(data) {
          return data == 'GASTOS' ? '<span class="badge bg-danger">EGRESOS</span>' : '<span class="badge bg-success">INGRESOS</span>';
        }
      },
      { "data": "nombre", render: function(data, type, row) {
          return row.es_grupo == 1 ? '<b>' + indEsc(data) + '</b> <span class="badge badge-secondary">GRUPO</span>' : '&nbsp;&nbsp;&nbsp;' + indEsc(data);
        }
      },
      { "data": "descripcion", render: function(data) { return indEsc(data); } },
      { "data": "usos", render: function(data, type, row) { return row.es_grupo == 1 ? row.hijos + ' categoría(s)' : data + ' movimiento(s)'; } },
      { "data": "estado", render: function(data) {
          return data == 'ACTIVO' ? '<span class="badge bg-success">ACTIVO</span>' : '<span class="badge bg-danger">INACTIVO</span>';
        }
      },
      { "data": null, render: function(data, type, row) {
          let b = "<button class='editar btn btn-primary btn-sm' title='Editar'><i class='fa fa-edit'></i> Editar</button>";
          if (row.del_sistema == 0 && row.usos == 0 && row.hijos == 0) {
            b += "&nbsp;&nbsp;<button class='delete btn btn-danger btn-sm' title='Eliminar'><i class='fa fa-trash'></i> Eliminar</button>";
          }
          return b;
        }
      }
    ],
    "language": idioma_espanol,
    select: true
  });
}

//ENVIANDO DATOS PARA EDITAR
$('#tabla_indicadores').on('click', '.editar', function() {
  var data = tbl_indicadores.row($(this).parents('tr')).data();
  if (tbl_indicadores.row(this).child.isShown()) {
    data = tbl_indicadores.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('id_indiciador').value = data.id_indicadores;
  document.getElementById('txt_tipo_editar').value = data.tipo_indicador;
  document.getElementById('txt_nombre_editar').value = $('<div>').html(data.nombre).text();
  document.getElementById('txt_descripcion_editar').value = $('<div>').html(data.descripcion || '').text();
  document.getElementById('txt_estatus').value = data.estado;
  Cargar_Grupos(data.tipo_indicador, '#txt_grupo_editar', data.padre_id);
  // Un grupo no cambia de tipo ni de grupo; las categorías del sistema tampoco
  $('#txt_tipo_editar, #txt_grupo_editar').prop('disabled', data.es_grupo == 1 || data.del_sistema == 1);
  $('#fila_grupo_editar').toggle(data.es_grupo != 1);
  $('#txt_aviso_editar').text(data.es_grupo == 1 ? 'Es un grupo del plan de cuentas: solo se cambian el nombre, la descripción y el estado.'
    : (data.del_sistema == 1 ? 'La usa el sistema (cobranza, planilla o comisiones): no se cambian su tipo ni su grupo.' : ''));
});

$(document).on('change', '#txt_tipo', function() { Cargar_Grupos(this.value, '#txt_grupo'); });
$(document).on('change', '#txt_tipo_editar', function() { Cargar_Grupos(this.value, '#txt_grupo_editar'); });

//ABRIENDO MODAL REGISTRO
function AbrirRegistro() {
  Cargar_Grupos($('#txt_tipo').val(), '#txt_grupo');
  $("#modal_registro").modal({ backdrop: 'static', keyboard: false });
  $("#modal_registro").modal('show');
}

function Mensaje_Codigo_Indicador(resp, exito) {
  const mensajes = {
    2: "Ya existe una categoría de ese tipo con ese nombre",
    3: "Elija un grupo del mismo tipo (ingresos o egresos)",
    4: "No se puede cambiar el tipo ni el grupo: la usa el sistema o ya tiene movimientos de otro tipo"
  };
  if (resp == 1) {
    return Swal.fire("Mensaje de Confirmación", exito, "success");
  }
  return Swal.fire("Mensaje de Advertencia", mensajes[resp] || "No se completó la operación", "warning");
}

//REGISTRANDO CATEGORIA
function Registrar_indicador() {
  let tipo = $('#txt_tipo').val();
  let padre = $('#txt_grupo').val();
  let nombre = $('#txt_nombre').val();
  let descrip = $('#txt_descripcion').val();
  if (!tipo || !padre || nombre.trim().length == 0) {
    return Swal.fire("Mensaje de Advertencia", "El tipo, el grupo y el nombre son obligatorios", "warning");
  }
  $.ajax({
    "url": "../controller/indicadores/controlador_registro_indicadores.php",
    type: 'POST',
    data: { tipo: tipo, padre: padre, nombre: nombre, descrip: descrip }
  }).done(function(resp) {
    Mensaje_Codigo_Indicador(resp, "Nueva categoría registrada").then(function() {
      if (resp == 1) {
        tbl_indicadores.ajax.reload();
        $('#txt_nombre, #txt_descripcion').val('');
        $("#modal_registro").modal('hide');
      }
    });
  });
}

//EDITANDO CATEGORIA
function Modificar_Indicador() {
  let id = $('#id_indiciador').val();
  let tipo = $('#txt_tipo_editar').val();
  let padre = $('#txt_grupo_editar').val();
  let nombre = $('#txt_nombre_editar').val();
  let descrip = $('#txt_descripcion_editar').val();
  let esta = $('#txt_estatus').val();
  if (!tipo || !id || nombre.trim().length == 0 || ($('#fila_grupo_editar').is(':visible') && !padre)) {
    return Swal.fire("Mensaje de Advertencia", "Tiene campos vacios.", "warning");
  }
  $.ajax({
    "url": "../controller/indicadores/controlador_modificar_indicador.php",
    type: 'POST',
    data: { id: id, tipo: tipo, padre: padre || '', nombre: nombre, descrip: descrip, esta: esta }
  }).done(function(resp) {
    Mensaje_Codigo_Indicador(resp, "Datos actualizados correctamente").then(function() {
      if (resp == 1) {
        tbl_indicadores.ajax.reload();
        $("#modal_editar").modal('hide');
      }
    });
  });
}

//ELIMINANDO CATEGORIA
function Eliminar_Indicador(id) {
  $.ajax({
    "url": "../controller/indicadores/controlador_eliminar_indicador.php",
    type: 'POST',
    data: { id: id }
  }).done(function(resp) {
    if (resp == 1) {
      Swal.fire("Mensaje de Confirmación", "Se eliminó la categoría", "success").then(function() {
        tbl_indicadores.ajax.reload();
      });
    } else if (resp == 2) {
      Swal.fire("Mensaje de Advertencia", "Tiene ingresos o egresos registrados: no se elimina. Puede desactivarla para que no se use más.", "warning");
    } else {
      Swal.fire("Mensaje de Advertencia", "Es un grupo con categorías o una categoría que usa el sistema: no se elimina.", "warning");
    }
  });
}

//ENVIANDO AL BOTON DELETE
$('#tabla_indicadores').on('click', '.delete', function() {
  var data = tbl_indicadores.row($(this).parents('tr')).data();
  if (tbl_indicadores.row(this).child.isShown()) {
    data = tbl_indicadores.row(this).data();
  }
  Swal.fire({
    title: '¿Desea eliminar la categoría ' + indEsc($('<div>').html(data.nombre).text()) + '?',
    text: "Una vez aceptado la categoría será eliminada",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_Indicador(data.id_indicadores);
    }
  });
});
