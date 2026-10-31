// Pensiones por año escolar y nivel (Fase 8)
var tbl_pensiones;
var pensionesAnios = [];

function pensionEsc(t) {
  return $('<div>').text(t == null ? '' : t).html();
}

function Iniciar_Pensiones() {
  $.ajax({ url: "../controller/pensiones/controlador_anios_escolares.php", type: 'POST' }).done(function(r) {
    pensionesAnios = r.data;
    let op = "";
    r.data.forEach(function(a) {
      op += "<option value='" + a.id + "'" + (a.estado === 'ACTIVO' ? ' selected' : '') + ">" + pensionEsc(a.anio) + (a.estado === 'ACTIVO' ? ' (activo)' : '') + "</option>";
    });
    $('.select-anio').html(op);
    $('#select_anio_buscar').html("<option value='0'>TODOS</option>" + op);
    Cargar_Niveles_Pensiones();
  });
}

//TRAENDO DATOS DE LA NIVEL ACADEMICO
function Cargar_Niveles_Pensiones() {
  $.ajax({
    "url": "../controller/aulas/controlador_cargar_select_nivel.php",
    type: 'POST',
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "";
    for (let i = 0; i < data.length; i++) {
      cadena += "<option value='" + textoSeguro(data[i][0]) + "'>" + pensionEsc(data[i][1]) + "</option>";
    }
    $('.select-nivel').html(cadena || "<option value=''>No hay niveles registrados</option>");
    $('#select_nivel_buscar').html("<option value='0'>TODOS</option>" + cadena);
    listar_pensiones();
  });
}

//LISTADO DE PENSIONES
function listar_pensiones() {
  let anio = $('#select_anio_buscar').val() || 0;
  let nivel = $('#select_nivel_buscar').val() || 0;
  tbl_pensiones = $("#tabla_pensiones").DataTable({
    "ordering": false,
    "bLengthChange": true,
    "lengthMenu": [[12, 25, 50, 100, -1], [12, 25, 50, 100, "Todos"]],
    "pageLength": 12,
    "destroy": true,
    pagingType: 'full_numbers',
    responsive: true,
    "processing": true,
    "ajax": {
      "url": "../controller/pensiones/controlador_listar_pensiones.php",
      type: 'POST',
      data: { anio: anio, nivel: nivel }
    },
    dom: 'Bfrtip',
    buttons: [
      { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> ', titleAttr: 'Exportar a Excel', title: 'LISTA DE PENSIONES' },
      { extend: 'print', text: '<i class="fas fa-print"></i> ', titleAttr: 'Imprimir', title: 'LISTA DE PENSIONES' }
    ],
    "columns": [
      { "data": "id_pensiones" },
      { "data": "anio_escolar", render: function(data) { return pensionEsc(data); } },
      { "data": "Nivel_academico", render: function(data) { return '<span class="badge bg-primary">' + pensionEsc(data) + '</span>'; } },
      { "data": "mes" },
      { "data": "fecha_formateada", render: function(data, type, row) {
          return row.fecha_en_anio == 1 ? data : '<span class="text-danger font-weight-bold" title="El vencimiento no corresponde al año escolar">' + data + ' <i class="fas fa-exclamation-triangle"></i></span>';
        }
      },
      { "data": "PRECIO", render: function(data) { return formatoMoneda(data); } },
      { "data": "MORA", render: function(data) { return '<span class="badge bg-danger">' + formatoMoneda(data) + '</span>'; } },
      { "data": "pagos", render: function(data) { return data > 0 ? '<span class="badge bg-success">' + data + '</span>' : '<span class="text-muted">0</span>'; } },
      { "data": null, render: function(data, type, row) {
          // Con pagos no se elimina (el servidor tampoco lo permite)
          return "<button class='editar btn btn-primary btn-sm' title='Editar pensión'><i class='fas fa-edit'></i> Editar</button>"
               + (row.pagos > 0 ? "" : "&nbsp;&nbsp;<button class='delete btn btn-danger btn-sm' title='Eliminar pensión'><i class='fas fa-trash'></i> Eliminar</button>");
        }
      }
    ],
    "language": idioma_espanol,
    select: true
  });
  tbl_pensiones.on('draw.td', function() {
    var PageInfo = $("#tabla_pensiones").DataTable().page.info();
    tbl_pensiones.column(0, { page: 'current' }).nodes().each(function(cell, i) {
      cell.innerHTML = i + 1 + PageInfo.start;
    });
  });
  tbl_pensiones.on('xhr', function() {
    Aviso_Vencimientos(tbl_pensiones.ajax.json().data || []);
  });
}

// Avisa si hay pensiones con vencimiento fuera de su año escolar y ofrece ajustarlas
function Aviso_Vencimientos(filas) {
  let anios = {};
  filas.forEach(function(f) { if (f.fecha_en_anio == 0) { anios[f.id_anio] = (anios[f.id_anio] || 0) + 1; } });
  let html = "";
  Object.keys(anios).forEach(function(id) {
    let a = pensionesAnios.filter(function(x) { return String(x.id) === String(id); })[0];
    html += "<div class='alert alert-warning py-2'><i class='fas fa-exclamation-triangle'></i> En el año escolar <b>" + pensionEsc(a ? a.anio : id) + "</b> hay <b>" + anios[id]
          + "</b> pensión(es) con vencimiento fuera de su calendario: el cobro y la morosidad las toman como vencidas desde esa fecha. "
          + "<button class='btn btn-sm btn-dark ml-2' onclick='Ajustar_Vencimientos(" + parseInt(id, 10) + ")'><i class='fas fa-magic'></i> Revisar y ajustar</button></div>";
  });
  $('#aviso_vencimientos').html(html);
}

function Ajustar_Vencimientos(anio) {
  $.ajax({ url: "../controller/pensiones/controlador_ajustar_vencimientos.php", type: 'POST', data: { anio: anio, aplicar: 0 } }).done(function(r) {
    if (!r.data.length) {
      return Swal.fire("Mensaje", "No hay vencimientos para ajustar", "info");
    }
    let filas = "";
    r.data.forEach(function(f) {
      filas += "<tr><td>" + pensionEsc(f.nivel) + "</td><td>" + f.mes + "</td><td class='text-danger'>" + f.fecha_actual.split('-').reverse().join('-')
             + "</td><td class='text-success'><b>" + (f.fecha_nueva ? f.fecha_nueva.split('-').reverse().join('-') : '—') + "</b></td></tr>";
    });
    Swal.fire({
      title: 'Ajustar ' + r.data.length + ' vencimiento(s)',
      html: "<p style='font-size:14px'>Se mantiene el día y se usa el año que corresponde al mes dentro del año escolar. Queda registro del cambio.</p>"
          + "<div style='max-height:300px;overflow:auto'><table class='table table-sm table-bordered' style='font-size:13px'><thead><tr><th>Nivel</th><th>Mes</th><th>Actual</th><th>Nuevo</th></tr></thead><tbody>" + filas + "</tbody></table></div>",
      width: 650,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, ajustar',
      cancelButtonText: 'Cancelar'
    }).then(function(res) {
      if (!res.isConfirmed) { return; }
      $.ajax({ url: "../controller/pensiones/controlador_ajustar_vencimientos.php", type: 'POST', data: { anio: anio, aplicar: 1 } }).done(function(r2) {
        Swal.fire("Mensaje de Confirmación", r2.data.length + " vencimiento(s) ajustado(s)", "success");
        tbl_pensiones.ajax.reload();
      });
    });
  });
}

//ENVIANDO DATOS PARA EDITAR
$('#tabla_pensiones').on('click', '.editar', function() {
  var data = tbl_pensiones.row($(this).parents('tr')).data();
  if (tbl_pensiones.row(this).child.isShown()) {
    data = tbl_pensiones.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_id_pension').value = data.id_pensiones;
  $('#select_anio_editar').val(data.id_anio);
  $('#select_nivel_editar').val(data.id_nivel_academico);
  document.getElementById('select_mes_editar').value = data.mes;
  document.getElementById('txt_fecha_editar').value = data.fecha_vencimiento;
  document.getElementById('txt_precio_editar').value = data.precio;
  document.getElementById('txt_mora_editar').value = data.mora;
  $('#select_anio_editar, #select_nivel_editar, #select_mes_editar').prop('disabled', data.pagos > 0);
  $('#txt_aviso_pagos').text(data.pagos > 0 ? 'Tiene pagos registrados: solo se pueden cambiar el vencimiento, el precio y la mora.' : '');
});

//ABRIENDO MODAL REGISTRO
function AbrirRegistro() {
  if ($('#select_anio_buscar').val() > 0) { $('#select_anio').val($('#select_anio_buscar').val()); }
  if ($('#select_nivel_buscar').val() > 0) { $('#select_nivel').val($('#select_nivel_buscar').val()); }
  $("#modal_registro").modal({ backdrop: 'static', keyboard: false });
  $("#modal_registro").modal('show');
}
function AbrirGenerar() {
  if ($('#select_anio_buscar').val() > 0) { $('#select_anio_gen').val($('#select_anio_buscar').val()); }
  if ($('#select_nivel_buscar').val() > 0) { $('#select_nivel_gen').val($('#select_nivel_buscar').val()); }
  // Por defecto, del mes de inicio del año escolar hasta el mes de fin
  let a = pensionesAnios.filter(function(x) { return String(x.id) === String($('#select_anio_gen').val()); })[0];
  if (a) {
    $('#select_desde_gen').val(parseInt(a.fecha_inicio.substr(5, 2), 10));
    $('#select_hasta_gen').val(parseInt(a.fecha_fin.substr(5, 2), 10));
  }
  $("#modal_generar").modal('show');
}

function Mensaje_Codigo_Pension(resp, exito) {
  const mensajes = {
    2: "Ya existe una pensión de ese año escolar, nivel académico y mes",
    3: "La fecha de vencimiento no corresponde al calendario del año escolar elegido",
    4: "La pensión tiene pagos: no se puede cambiar su año escolar, nivel ni mes"
  };
  if (resp == 1) {
    return Swal.fire("Mensaje de Confirmación", exito, "success");
  }
  return Swal.fire("Mensaje de Advertencia", mensajes[resp] || "No se completó la operación", "warning");
}

//REGISTRANDO PENSION
function Registrar_pensiones() {
  let anio = $('#select_anio').val();
  let nivel = $('#select_nivel').val();
  let mes = $('#select_mes').val();
  let fecha = $('#txt_fecha').val();
  let precio = $('#txt_precio').val();
  let mora = $('#txt_mora').val();
  if (!anio || !nivel || !mes || !fecha || !precio) {
    return Swal.fire("Mensaje de Advertencia", "Tiene campos vacios revise por favor", "warning");
  }
  $.ajax({
    "url": "../controller/pensiones/controlador_registro_pensiones.php",
    type: 'POST',
    data: { anio: anio, nivel: nivel, mes: mes, fecha: fecha, precio: precio, mora: mora }
  }).done(function(resp) {
    Mensaje_Codigo_Pension(resp, "Nueva pensión registrada").then(function() {
      if (resp == 1) {
        tbl_pensiones.ajax.reload();
        $("#modal_registro").modal('hide');
      }
    });
  });
}

//EDITANDO PENSION
function Modificar_Pensiones() {
  let id = $('#txt_id_pension').val();
  let anio = $('#select_anio_editar').val();
  let nivel = $('#select_nivel_editar').val();
  let mes = $('#select_mes_editar').val();
  let fecha = $('#txt_fecha_editar').val();
  let precio = $('#txt_precio_editar').val();
  let mora = $('#txt_mora_editar').val();
  if (!id || !anio || !nivel || !mes || !fecha || !precio) {
    return Swal.fire("Mensaje de Advertencia", "Tiene campos vacios revise por favor", "warning");
  }
  $.ajax({
    "url": "../controller/pensiones/controlador_modificar_pension.php",
    type: 'POST',
    data: { id: id, anio: anio, nivel: nivel, mes: mes, fecha: fecha, precio: precio, mora: mora }
  }).done(function(resp) {
    Mensaje_Codigo_Pension(resp, "Datos actualizados correctamente").then(function() {
      if (resp == 1) {
        tbl_pensiones.ajax.reload();
        $("#modal_editar").modal('hide');
      }
    });
  });
}

//GENERANDO CRONOGRAMA
function Generar_Pensiones() {
  let datos = {
    anio: $('#select_anio_gen').val(), nivel: $('#select_nivel_gen').val(),
    desde: $('#select_desde_gen').val(), hasta: $('#select_hasta_gen').val(), dia: $('#select_dia_gen').val(),
    precio: $('#txt_precio_gen').val(), mora: $('#txt_mora_gen').val()
  };
  if (!datos.anio || !datos.nivel || !datos.precio) {
    return Swal.fire("Mensaje de Advertencia", "Elija año escolar y nivel e indique el precio", "warning");
  }
  $.ajax({ url: "../controller/pensiones/controlador_generar_pensiones.php", type: 'POST', data: datos }).done(function(r) {
    if (r.codigo == 1) {
      Swal.fire("Mensaje de Confirmación", pensionEsc(r.mensaje), "success");
      $("#modal_generar").modal('hide');
      $('#select_anio_buscar').val(datos.anio);
      $('#select_nivel_buscar').val(datos.nivel);
      listar_pensiones();
    } else {
      Swal.fire("Mensaje de Advertencia", pensionEsc(r.mensaje), "warning");
    }
  });
}

//ELIMINANDO PENSION
function Eliminar_Pensión(id) {
  $.ajax({
    "url": "../controller/pensiones/controlador_eliminar_pensiones.php",
    type: 'POST',
    data: { id: id }
  }).done(function(resp) {
    if (resp == 1) {
      Swal.fire("Mensaje de Confirmación", "Se elimino la pensión con exito", "success").then(function() {
        tbl_pensiones.ajax.reload();
      });
    } else {
      return Swal.fire("Mensaje de Advetencia", "No se puede eliminar esta pensión porque ya tiene pagos registrados", "warning");
    }
  });
}

//ENVIANDO AL BOTON DELETE
$('#tabla_pensiones').on('click', '.delete', function() {
  var data = tbl_pensiones.row($(this).parents('tr')).data();
  if (tbl_pensiones.row(this).child.isShown()) {
    data = tbl_pensiones.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar la pensión de <b class="text-primary">' + pensionEsc(data.mes) + '</b> - <b class="text-primary">' + pensionEsc(data.Nivel_academico) + '</b> (' + pensionEsc(data.anio_escolar) + ')?',
    text: "Una vez aceptado la pensión sera eliminada!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_Pensión(data.id_pensiones);
    }
  });
});
