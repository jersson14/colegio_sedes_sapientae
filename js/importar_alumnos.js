// Importar estudiantes desde Excel (Fase 13, paso 14): revisar el archivo fila por fila y grabar solo las filas correctas.
// Las reglas son las del registro manual (utilitario/importar_alumnos.php).
var IM = { listas: 0 };

function Importar_Abrir() {
  $('#im_archivo').val('');
  $('#im_resultado').empty();
  $('#im_grabar').prop('disabled', true).html('<i class="fas fa-file-import mr-1"></i>Importar');
  IM.listas = 0;
  $('#modal_importar').modal({ backdrop: 'static' }).modal('show');
}

function Importar_Enviar(modo) {
  var f = $('#im_archivo')[0].files[0];
  if (!f) { return $.Deferred().reject().promise(); }
  var datos = new FormData();
  datos.append('modo', modo);
  datos.append('archivo', f);
  return $.ajax({ url: '../controller/alumnos/controlador_importar.php', type: 'POST', data: datos, processData: false, contentType: false, dataType: 'json' });
}

function Importar_Revisar() {
  if (!$('#im_archivo')[0].files.length) {
    return Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: 'warning', title: 'Elija el archivo de Excel' });
  }
  $('#im_resultado').html('<p class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Revisando…</p>');
  Importar_Enviar('revisar').done(function (r) {
    IM.listas = r.listas;
    if (r.faltan_columnas.length) {
      $('#im_resultado').html('<div class="alert alert-danger">El archivo no tiene estas columnas obligatorias: <b>' + r.faltan_columnas.map(textoSeguro).join(', ')
        + '</b>. Use la plantilla sin cambiar los encabezados.</div>');
      return $('#im_grabar').prop('disabled', true);
    }
    var T = PanelComun.titulo;
    var h = '<div class="lb-resumen">' + (r.listas ? '<span class="lb-chip lb-ok">' + PanelComun.plural(r.listas, 'fila lista', 'filas listas') + ' para importar</span>' : '')
      + (r.con_error ? '<span class="lb-chip lb-mal">' + PanelComun.plural(r.con_error, 'fila con errores', 'filas con errores') + ' (no se importarán)</span>' : '') + '</div>'
      + '<div class="table-responsive im-tabla"><table class="table table-sm"><thead><tr><th>Fila</th><th>Estado</th><th>Estudiante</th><th>Documento</th><th>Nacimiento</th><th>Madre</th><th>Qué revisar</th></tr></thead><tbody>';
    r.filas.forEach(function (f) {
      var d = f.datos, ok = !f.errores.length;
      h += '<tr class="' + (ok ? (f.aviso ? 'im-aviso' : '') : 'im-error') + '"><td>' + f.fila + '</td><td><span class="badge lg-estado ' + (ok ? 'est-ok">Lista' : 'est-mal">Con errores') + '</span></td>'
        + '<td>' + textoSeguro(T([d.nombre, d.apepa, d.apema].join(' '))) + '</td><td>' + textoSeguro(d.dni) + '</td>'
        + '<td>' + textoSeguro(/^\d{4}-\d{2}-\d{2}$/.test(d.fechanaci) ? d.fechanaci.split('-').reverse().join('/') : d.fechanaci) + '</td>'
        + '<td>' + textoSeguro(T(d.nomma)) + '</td><td>' + (ok ? (f.aviso ? '<span class="text-warning">' + textoSeguro(f.aviso) + '</span>' : '<span class="text-muted">—</span>')
        : '<ul class="im-errores">' + f.errores.map(function (e) { return '<li>' + textoSeguro(e) + '</li>'; }).join('') + '</ul>') + '</td></tr>';
    });
    h += '</tbody></table></div>';
    $('#im_resultado').html(h);
    $('#im_grabar').prop('disabled', !r.listas).html('<i class="fas fa-file-import mr-1"></i>Importar ' + PanelComun.plural(r.listas, 'estudiante', 'estudiantes'));
  }).fail(function (x) {
    $('#im_resultado').html('<div class="alert alert-danger">' + textoSeguro((x.responseJSON && x.responseJSON.mensaje) || 'No se pudo revisar el archivo') + '</div>');
    $('#im_grabar').prop('disabled', true);
  });
}

function Importar_Grabar() {
  if (!IM.listas) { return; }
  Importar_Enviar('grabar').done(function (r) {
    var html = '<p><b>' + PanelComun.plural(r.grabadas, 'estudiante importado', 'estudiantes importados') + '.</b></p>'
      + (r.con_error ? '<p>' + PanelComun.plural(r.con_error, 'fila tenía errores y no se importó', 'filas tenían errores y no se importaron') + ': corríjalas y vuelva a importar el archivo (las ya importadas se marcarán como existentes).</p>' : '')
      + (r.fallidas.length ? '<p class="text-danger">No se pudieron grabar las filas ' + r.fallidas.join(', ') + '.</p>' : '')
      + '<p class="text-muted">Para matricularlos use <b>Matrícula → Nuevo Registro</b>.</p>';
    $('#modal_importar').data('fm-salir', true).modal('hide');
    Swal.fire({ icon: r.grabadas ? 'success' : 'warning', title: 'Importación terminada', html: html });
    if (window.tb_alumnos) { tb_alumnos.ajax.reload(); }
  });
}

$(document).off('.im');
$(document).on('click.im', '#im_revisar', Importar_Revisar);
$(document).on('change.im', '#im_archivo', function () { $('#im_resultado').empty(); $('#im_grabar').prop('disabled', true); IM.listas = 0; });
