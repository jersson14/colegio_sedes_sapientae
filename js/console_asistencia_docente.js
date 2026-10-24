// Asistencia docente (Fase 8B): registro diario y resumen del mes
var AD = { filas: [] };
var escAD = function(v) { return $('<div>').text(v === null || v === undefined ? '' : v).html(); };

function Iniciar_Asistencia_Docente() {
  let hoy = new Date();
  $("#txt_fecha_ad").val(hoy.toLocaleDateString('en-CA'));
  $("#txt_anio_ad").val(hoy.getFullYear());
  $("#select_mes_ad").val(hoy.getMonth() + 1);
  $(document).off('change.ad', '.ad-estado').on('change.ad', '.ad-estado', function() { Cambio_Estado_Asistencia($(this).closest('tr')); });
  Cargar_Asistencia_Dia();
  Resumen_Asistencia_Docente();
}

function Cargar_Asistencia_Dia() {
  $.ajax({ url: "../controller/asistencia_docente/controlador_dia.php", type: 'POST', data: { fecha: $("#txt_fecha_ad").val() } }).done(function(resp) {
    AD.filas = resp.data;
    let filas = "";
    resp.data.forEach(function(d) {
      let horario = parseFloat(d.horas_horario);
      let estado = d.estado || '';
      let horas = d.id_asistencia ? d.horas_dictadas : (horario > 0 ? horario : '');
      filas += "<tr data-docente='" + d.Id_docente + "' data-horario='" + horario + "' data-nombre='" + escAD(d.docente) + "'><td><b>" + escAD(d.docente) + "</b>"
             + (d.id_asistencia ? " <span class='badge bg-success'>registrado</span>" : "") + "</td><td>" + escAD(d.tipo_contrato || 'Sin contrato') + "</td>"
             + "<td class='text-right'>" + (horario > 0 ? horario.toFixed(2) + ' h' : '<span class="text-muted">—</span>') + "</td>"
             + "<td><select class='form-control form-control-sm ad-estado'><option value=''>— sin registrar —</option>"
             + ['PRESENTE', 'TARDANZA', 'FALTA', 'JUSTIFICADA'].map(function(e) { return "<option" + (e == estado ? " selected" : "") + ">" + e + "</option>"; }).join('')
             + "</select></td><td><input type='number' step='0.25' min='0' max='16' class='form-control form-control-sm ad-horas' value='" + escAD(horas) + "'></td>"
             + "<td><input type='number' min='0' class='form-control form-control-sm ad-minutos' value='" + escAD(d.minutos_tardanza || '') + "'" + (estado == 'TARDANZA' ? "" : " disabled") + "></td>"
             + "<td><input type='text' maxlength='255' class='form-control form-control-sm ad-obs' value='" + escAD(d.observacion || '') + "'></td></tr>";
    });
    $("#tbody_asistencia_docente").html(filas || "<tr><td colspan='7' class='text-center text-muted'>No hay docentes activos</td></tr>");
  });
}

// Falta = 0 horas; tardanza pide minutos; presente vuelve a las horas del horario si estaba en 0
function Cambio_Estado_Asistencia(tr) {
  let estado = tr.find('.ad-estado').val();
  tr.find('.ad-minutos').prop('disabled', estado != 'TARDANZA');
  if (estado != 'TARDANZA') { tr.find('.ad-minutos').val(''); }
  if (estado == 'FALTA') { tr.find('.ad-horas').val(0); }
  if ((estado == 'PRESENTE' || estado == 'TARDANZA') && !(parseFloat(tr.find('.ad-horas').val()) > 0) && parseFloat(tr.data('horario')) > 0) {
    tr.find('.ad-horas').val(tr.data('horario'));
  }
}

function Marcar_Todos_Presentes() {
  $("#tbody_asistencia_docente tr[data-docente]").each(function() {
    if ($(this).find('.ad-estado').val() == '') {
      $(this).find('.ad-estado').val('PRESENTE');
      Cambio_Estado_Asistencia($(this));
    }
  });
}

function Guardar_Asistencia_Docente() {
  let filas = [];
  let faltan = [];
  $("#tbody_asistencia_docente tr[data-docente]").each(function() {
    let estado = $(this).find('.ad-estado').val();
    if (estado == '') { return; }
    let minutos = $(this).find('.ad-minutos').val();
    if (estado == 'TARDANZA' && !(parseInt(minutos, 10) > 0)) { faltan.push($(this).data('nombre')); }
    filas.push({ docente: $(this).data('docente'), nombre: $(this).data('nombre'), estado: estado, horas: $(this).find('.ad-horas').val() || '0',
                 minutos: minutos || '0', observacion: $(this).find('.ad-obs').val() });
  });
  if (!filas.length) {
    return Swal.fire("Mensaje de Advertencia", "Elija el estado de al menos un docente", "warning");
  }
  if (faltan.length) {
    return Swal.fire("Mensaje de Advertencia", "Indique los minutos de tardanza de: " + escAD(faltan.join(', ')), "warning");
  }
  $.ajax({ url: "../controller/asistencia_docente/controlador_guardar.php", type: 'POST',
           data: { fecha: $("#txt_fecha_ad").val(), filas: JSON.stringify(filas) } }).done(function(resp) {
    if (resp.ok) {
      Swal.fire("Mensaje de Confirmación", "Asistencia guardada (" + resp.guardadas + " docente(s))", "success");
    } else {
      Swal.fire("Mensaje de Advertencia", (resp.guardadas ? resp.guardadas + " guardada(s). " : "") + escAD(resp.errores.join(' · ')), "warning");
    }
    Cargar_Asistencia_Dia();
  });
}

function Resumen_Asistencia_Docente() {
  $.ajax({ url: "../controller/asistencia_docente/controlador_resumen.php", type: 'POST',
           data: { anio: $("#txt_anio_ad").val(), mes: $("#select_mes_ad").val() } }).done(function(resp) {
    if ($.fn.DataTable.isDataTable('#tabla_resumen_ad')) { $('#tabla_resumen_ad').DataTable().destroy(); }
    let filas = "";
    resp.data.forEach(function(r) {
      filas += "<tr><td><b>" + escAD(r.docente) + "</b></td><td class='text-right'>" + r.dias + "</td><td class='text-right'>" + (r.presentes || 0)
             + "</td><td class='text-right'>" + (r.tardanzas || 0) + "</td><td class='text-right'>" + r.minutos_tardanza + "</td><td class='text-right"
             + (r.faltas > 0 ? " text-danger font-weight-bold" : "") + "'>" + (r.faltas || 0) + "</td><td class='text-right'>" + (r.justificadas || 0)
             + "</td><td class='text-right'>" + parseFloat(r.horas_programadas).toFixed(2) + "</td><td class='text-right'><b>" + parseFloat(r.horas_dictadas).toFixed(2) + "</b></td></tr>";
    });
    $('#tabla_resumen_ad tbody').html(filas);
    $('#tabla_resumen_ad').DataTable({
      paging: false, searching: true, info: false, order: [], language: idioma_espanol, dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>rt",
      buttons: [{ extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm',
                  title: 'Asistencia docente ' + $("#select_mes_ad option:selected").text() + ' ' + $("#txt_anio_ad").val() }]
    });
  });
}
