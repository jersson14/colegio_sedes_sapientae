// Aula virtual: entrega de tareas del estudiante (la vista carga este archivo solo para el rol ESTUDIANTE).
// Usa el mismo controlador que la pantalla de tareas, que revisa que la tarea sea suya, el plazo y si ya fue calificada.
function AulaVirtual_Entrega(i) {
  var t = AV.curso.tareas[i];
  $('#av_entrega_id').val(t.envio);
  $('#av_entrega_titulo').text((t.envio_estado === 'ENVIADO' ? 'Cambiar entrega: ' : 'Entregar: ') + AulaVirtual_Titulo(t.tema));
  $('#av_entrega_aviso').text(t.fecha_entrega && t.fecha_entrega < AV.curso.ahora ? 'El plazo venció el ' + AulaVirtual_Fecha(t.fecha_entrega, true) + ': la entrega quedará marcada como fuera de plazo.' :
    t.fecha_entrega ? 'Vence el ' + AulaVirtual_Fecha(t.fecha_entrega, true) + '.' : '');
  $('#av_entrega_archivos').val('');
  $('#av_modal_entrega').modal('show');
}

function AulaVirtual_Enviar() {
  var archivos = $('#av_entrega_archivos')[0].files;
  if (!archivos.length) { return Swal.fire('Falta el archivo', 'Elija el archivo de su trabajo.', 'warning'); }
  var fd = new FormData();
  fd.append('iddetalle', $('#av_entrega_id').val());
  fd.append('archivoactual', '');
  for (var i = 0; i < archivos.length; i++) { fd.append('archivos[]', archivos[i]); }
  var btn = $('#av_entrega_enviar').prop('disabled', true);
  $.ajax({ url: '../controller/tareas/controlador_registro_tareas_estudiante.php', type: 'POST', data: fd, contentType: false, processData: false })
    .done(function (r) {
      if (String(r).trim() === '1') {
        $('#av_modal_entrega').modal('hide');
        Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Tarea entregada' });
        AulaVirtual_Abrir(AV.curso.curso.id, 'tablon');
      } else {
        Swal.fire('No se pudo entregar', textoSeguro(String(r).slice(0, 200)) || 'Intente de nuevo.', 'warning');
      }
    }).always(function () { btn.prop('disabled', false); });
}
