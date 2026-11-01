// Entrega de tareas del estudiante (aula virtual y Mis tareas). Solo se carga para el rol ESTUDIANTE.
// Usa el mismo controlador que la pantalla de tareas, que revisa que la tarea sea suya, el plazo y si ya fue calificada.
// Entrega_Abrir(tarea, listo): tarea = {envio, tema, fecha_entrega, envio_estado}; listo() se llama al entregar.

function Entrega_Ventana() {
  if ($('#et_modal').length) { return $('#et_modal'); }
  $('body').append(
    '<div class="modal fade" id="et_modal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content">' +
    '<div class="modal-header ventana-cabecera"><h5 class="modal-title text-white" id="et_titulo">Entregar tarea</h5>' +
    '<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>' +
    '<div class="modal-body"><p class="text-muted small mb-2" id="et_aviso"></p>' +
    '<label class="et-zona" for="et_archivos"><i class="fas fa-cloud-upload-alt"></i><b>Elija los archivos de su trabajo</b>' +
    '<span>Fotos, PDF, Word… puede elegir varios. Si vuelve a entregar, reemplazan a los anteriores.</span></label>' +
    '<input type="file" id="et_archivos" multiple class="d-none"><ul class="et-lista" id="et_lista"></ul></div>' +
    '<div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>' +
    '<button type="button" class="btn btn-success" id="et_enviar"><i class="fas fa-upload mr-1"></i>Entregar</button></div></div></div></div>');
  $(document).on('change', '#et_archivos', function () {
    $('#et_lista').html($.map(this.files, function (f) {
      return '<li><i class="far fa-file mr-2"></i>' + textoSeguro(f.name) + ' <small class="text-muted">(' + Math.max(1, Math.round(f.size / 1024)) + ' KB)</small></li>';
    }).join(''));
  });
  $(document).on('click', '#et_enviar', Entrega_Enviar);
  return $('#et_modal');
}

function Entrega_Fecha(f) {
  var d = new Date(String(f).replace(' ', 'T'));
  return isNaN(d) ? f : d.toLocaleDateString('es', { weekday: 'short', day: 'numeric', month: 'short' }) + ', ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
}

function Entrega_Abrir(t, listo) {
  var m = Entrega_Ventana();
  m.data('tarea', t).data('listo', listo);
  $('#et_titulo').text((t.envio_estado === 'ENVIADO' ? 'Cambiar entrega: ' : 'Entregar: ') + (window.PanelComun ? PanelComun.titulo(t.tema) : t.tema));
  var ahora = new Date().toISOString().slice(0, 19).replace('T', ' ');
  $('#et_aviso').text(!t.fecha_entrega ? '' : t.fecha_entrega < ahora ? 'El plazo venció el ' + Entrega_Fecha(t.fecha_entrega) + ': la entrega quedará marcada como fuera de plazo.'
                                                          : 'Vence el ' + Entrega_Fecha(t.fecha_entrega) + '.');
  $('#et_archivos').val('');
  $('#et_lista').empty();
  m.modal('show');
}

function Entrega_Enviar() {
  var m = $('#et_modal'), t = m.data('tarea'), archivos = $('#et_archivos')[0].files;
  if (!archivos.length) { return Swal.fire('Falta el archivo', 'Elija el archivo de su trabajo.', 'warning'); }
  var fd = new FormData();
  fd.append('iddetalle', t.envio);
  fd.append('archivoactual', '');
  for (var i = 0; i < archivos.length; i++) { fd.append('archivos[]', archivos[i]); }
  var btn = $('#et_enviar').prop('disabled', true);
  $.ajax({ url: '../controller/tareas/controlador_registro_tareas_estudiante.php', type: 'POST', data: fd, contentType: false, processData: false })
    .done(function (r) {
      if (String(r).trim() === '1') {
        m.modal('hide');
        Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Tarea entregada' });
        if (m.data('listo')) { m.data('listo')(); }
      } else {
        Swal.fire('No se pudo entregar', textoSeguro(String(r).slice(0, 200)) || 'Intente de nuevo.', 'warning');
      }
    }).always(function () { btn.prop('disabled', false); });
}
