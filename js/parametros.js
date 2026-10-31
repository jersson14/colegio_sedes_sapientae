// Parámetros del sistema (Fase 13, paso 15): Configuración → Parámetros.
var PA = { datos: null };
var PA_CAMPOS = ['login_max_intentos', 'login_minutos_bloqueo', 'video_max_mb', 'clases_sabado', 'asistencia_P', 'asistencia_T', 'asistencia_F', 'asistencia_J'];

function Parametros_Iniciar() {
  $(document).off('.pa');
  $(document).on('click.pa', '#pa_guardar', Parametros_Guardar);
  $(document).on('click.pa', '#pa_defecto', function () { Parametros_Pintar(PA.datos.defectos); });
  $(document).on('input.pa', '#pa_video_max_mb', Parametros_Limite);
  $.post('../controller/configuracion/controlador_parametros.php', { accion: 'listar' }, null, 'json').done(function (d) {
    PA.datos = d;
    Parametros_Pintar(d.valores);
  });
}

function Parametros_Pintar(v) {
  PA_CAMPOS.forEach(function (k) {
    if (k === 'clases_sabado') { $('#pa_clases_sabado').prop('checked', v[k] === '1'); } else { $('#pa_' + k).val(v[k]); }
  });
  Parametros_Limite();
}

// El servidor también limita el tamaño de los archivos: se avisa si el valor elegido lo supera
function Parametros_Limite() {
  var lim = PA.datos.limite_servidor_mb, v = +$('#pa_video_max_mb').val();
  $('#pa_limite').html('El servidor acepta archivos de hasta <b>' + lim + ' MB</b>.' + (v > lim ? ' <span class="text-danger">Se usará ' + lim + ' MB hasta que el técnico amplíe ese límite.</span>' : ''));
}

function Parametros_Guardar() {
  var datos = { accion: 'guardar' };
  PA_CAMPOS.forEach(function (k) { datos[k] = k === 'clases_sabado' ? ($('#pa_clases_sabado').is(':checked') ? '1' : '0') : $.trim($('#pa_' + k).val()); });
  var seguir = function () {
    $.post('../controller/configuracion/controlador_parametros.php', datos, null, 'json').done(function (d) {
      PA.datos.valores = d.valores;
      Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Parámetros guardados' });
    });
  };
  if (datos.login_max_intentos === '0') {
    return Swal.fire({ title: '¿Quitar el bloqueo por intentos?', text: 'Cualquiera podrá probar contraseñas sin límite. Se recomienda dejar un número, por ejemplo 5.',
                       icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, quitarlo', cancelButtonText: 'Cancelar' }).then(function (r) { if (r.isConfirmed) { seguir(); } });
  }
  seguir();
}
