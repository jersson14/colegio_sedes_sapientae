// Avisos a las familias (Fase 13, paso 14): cada aviso con su mensaje ya escrito para WhatsApp.
// Al tocar "WhatsApp" se registra el aviso (controller/avisos/controlador_avisos.php) y se abre el mensaje.
var AV = { datos: null, tipo: '' };
var AV_TIPOS = {
  FALTA: ['Faltas', 'fa-user-times', 'est-mal'], TAREA: ['Tareas vencidas', 'fa-tasks', 'est-pend'],
  PENSION: ['Pensiones', 'fa-receipt', 'est-info'], COMUNICADO: ['Comunicados sin leer', 'fa-bullhorn', 'est-neutro']
};

function Avisos_Iniciar() {
  $('#av_fecha').val(new Date().toISOString().substr(0, 10));
  $(document).off('.av');
  $(document).on('change.av', '#av_fecha, #av_enviados', function () { Avisos_Cargar(); });
  $(document).on('click.av', '#av_tipos button', function () { AV.tipo = $(this).data('tipo'); Avisos_Pintar(); });
  $(document).on('click.av', '#av_lista .av-wa', function () { Avisos_Enviar(this); });
  $(document).on('click.av', '#av_configurar', function () { Avisos_Config(); });
  Avisos_Cargar();
}

function Avisos_Cargar() {
  $('#av_lista').html('<p class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Buscando avisos…</p>');
  $.post('../controller/avisos/controlador_avisos.php', { accion: 'listar', fecha: $('#av_fecha').val(), con_enviados: $('#av_enviados').is(':checked') ? 1 : '' }, null, 'json')
    .done(function (d) {
      AV.datos = d;
      if (!AV.tipo || d.tipos.indexOf(AV.tipo) < 0) {
        var con = d.tipos.filter(function (t) { return d.avisos.some(function (a) { return a.tipo === t; }); });
        AV.tipo = con[0] || d.tipos[0] || '';
      }
      $('#av_configurar').prop('hidden', !d.puede_configurar);
      Avisos_Pintar();
    });
}

function Avisos_Mensaje(a) {
  var T = PanelComun.titulo, colegio = $.trim($('.tema-marca b').first().text()) || 'el colegio';
  var est = T(a.estudiante), quien = a.cel_madre ? (a.madre ? T(a.madre).split(' ')[0] : '') : (a.padre ? T(a.padre).split(' ')[0] : '');
  var saludo = 'Buenos días' + (quien ? ' ' + quien : '') + '. Le escribe ' + colegio + '. ';
  var f = function (iso) { return String(iso).substr(0, 10).split('-').reverse().join('/'); };
  if (a.tipo === 'FALTA') { return saludo + 'Le informamos que ' + est + ' no asistió a clases hoy, ' + f(a.fecha) + '. Si hubo algún motivo, por favor avísenos. Gracias.'; }
  if (a.tipo === 'TAREA') { return saludo + est + ' tiene pendiente la tarea "' + T(a.detalle) + '", que vencía el ' + f(a.fecha) + '. Por favor, ayúdenos a que la entregue. Gracias.'; }
  if (a.tipo === 'PENSION') {
    var p = String(a.detalle).split('|'), vencidas = +p[0];
    return saludo + 'Le recordamos que ' + est + (vencidas ? ' tiene pensiones vencidas' : ' tiene una pensión por vencer') + ' (' + T(p[1]) + '), por un total de '
      + formatoMoneda(p[2]) + '. Puede acercarse al colegio para regularizar. Si ya pagó, por favor no considere este mensaje. Gracias.';
  }
  return saludo + 'Publicamos el comunicado "' + textoPlano(a.detalle) + '". Puede leerlo ingresando al sistema con la cuenta de ' + est + '. Gracias.';
}

function Avisos_Motivo(a) {
  var T = PanelComun.titulo, f = function (iso) { return String(iso).substr(0, 10).split('-').reverse().join('/'); };
  if (a.tipo === 'FALTA') { return 'Faltó el ' + f(a.fecha) + (a.detalle ? ' · ' + textoSeguro(a.detalle) : ''); }
  if (a.tipo === 'TAREA') { return textoSeguro(T(a.detalle)) + ' · venció el ' + f(a.fecha); }
  if (a.tipo === 'PENSION') {
    var p = String(a.detalle).split('|');
    return (+p[0] ? '<b class="text-danger">' + PanelComun.plural(+p[0], 'vencida', 'vencidas') + '</b> · ' : 'Por vencer · ') + textoSeguro(T(p[1])) + ' · ' + formatoMoneda(p[2]);
  }
  return '"' + textoSeguro(textoPlano(a.detalle)) + '" · publicado el ' + f(a.fecha);
}

function Avisos_Pintar() {
  var d = AV.datos, T = PanelComun.titulo;
  if (!d.tipos.length) {
    $('#av_tipos').empty(); $('#av_resumen').empty();
    return $('#av_lista').html('<div class="alert alert-light border">Los avisos que le corresponden están apagados. ' + (d.puede_configurar ? 'Enciéndalos en <b>Configurar avisos</b>.' : 'Pídale a la dirección que los encienda.') + '</div>');
  }
  $('#av_tipos').html(d.tipos.map(function (t) {
    var n = d.avisos.filter(function (a) { return a.tipo === t && !a.enviado_at; }).length;
    return '<button type="button" class="btn btn-sm ' + (t === AV.tipo ? 'btn-primary' : 'btn-outline-secondary') + '" data-tipo="' + t + '" aria-pressed="' + (t === AV.tipo) + '">'
      + '<i class="fas ' + AV_TIPOS[t][1] + ' mr-1"></i>' + AV_TIPOS[t][0] + (n ? ' <span class="badge badge-light">' + n + '</span>' : '') + '</button>';
  }).join(''));
  $('#av_fecha_grupo').toggle(AV.tipo === 'FALTA');
  var lista = d.avisos.filter(function (a) { return a.tipo === AV.tipo; });
  var pend = lista.filter(function (a) { return !a.enviado_at; }).length;
  $('#av_resumen').html(lista.length ? '<span class="lb-chip ' + (pend ? 'lb-mal' : 'lb-ok') + '">' + PanelComun.plural(pend, 'familia por avisar', 'familias por avisar') + '</span>'
    + (lista.length - pend ? '<span class="lb-chip lb-ok">' + PanelComun.plural(lista.length - pend, 'ya avisada', 'ya avisadas') + '</span>' : '') : '');
  if (!lista.length) {
    return $('#av_lista').html('<div class="lg-vacio"><i class="far fa-check-circle"></i> No hay avisos pendientes de este tipo' + (AV.tipo === 'FALTA' ? ' para la fecha elegida' : '') + '.</div>');
  }
  var h = '<div class="table-responsive"><table class="table table-sm av-tabla"><thead><tr><th>Estudiante</th><th>Motivo</th><th>Contacto</th><th>Aviso</th></tr></thead><tbody>';
  lista.forEach(function (a, i) {
    var cel = a.cel_madre || a.cel_padre, nombre = a.cel_madre ? a.madre : a.padre;
    var url = cel ? Aviso_WhatsApp_Url(cel, Avisos_Mensaje(a)) : '';
    h += '<tr' + (a.enviado_at ? ' class="av-enviado"' : '') + '><td><b>' + textoSeguro(T(a.estudiante)) + '</b><small class="d-block text-muted">' + textoSeguro(T(a.aula)) + '</small></td>'
      + '<td>' + Avisos_Motivo(a) + '</td>'
      + '<td>' + (cel ? textoSeguro(T(nombre || (a.cel_madre ? 'Madre' : 'Padre'))) + '<small class="d-block text-muted">' + textoSeguro(cel) + '</small>' : '<span class="text-muted">Sin celular registrado</span>') + '</td>'
      + '<td class="text-nowrap">' + (a.enviado_at ? '<span class="badge lg-estado est-ok">Avisado</span><small class="d-block text-muted">' + textoSeguro(String(a.enviado_at).substr(0, 16).replace(/^(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1'))
        + (a.enviado_por ? ' · ' + textoSeguro(a.enviado_por) : '') + '</small>' : '')
      + (url ? (a.enviado_at ? ' ' : '') + '<a href="' + textoSeguro(url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-success av-wa" data-i="' + i + '"><i class="fab fa-whatsapp mr-1"></i>' + (a.enviado_at ? 'Otra vez' : 'WhatsApp') + '</a>' : '') + '</td></tr>';
  });
  $('#av_lista').html(h + '</tbody></table></div>');
  AV.visibles = lista;
}

// Registra el aviso (el enlace se abre igual: lo maneja el navegador)
function Avisos_Enviar(el) {
  var a = AV.visibles[+$(el).data('i')];
  var cel = a.cel_madre || a.cel_padre;
  $.post('../controller/avisos/controlador_avisos.php', { accion: 'marcar', tipo: a.tipo, referencia: a.referencia, matricula: a.matricula, destino: cel }, null, 'json').done(function () {
    a.enviado_at = a.enviado_at || new Date().toISOString().replace('T', ' ').substr(0, 16);
    setTimeout(Avisos_Pintar, 400);
  });
}

function Avisos_Config() {
  var c = AV.datos.config;
  ['falta', 'tarea', 'pension', 'comunicado'].forEach(function (k) { $('#cfg_' + k).prop('checked', c[k] === '1'); });
  $('#cfg_dias_pension').val(c.dias_pension);
  $('#modal_avisos_config').modal('show');
}

function Avisos_Guardar_Config() {
  var datos = { accion: 'configurar', dias_pension: $('#cfg_dias_pension').val() || '0' };
  ['falta', 'tarea', 'pension', 'comunicado'].forEach(function (k) { datos[k] = $('#cfg_' + k).is(':checked') ? 1 : ''; });
  $.post('../controller/avisos/controlador_avisos.php', datos, null, 'json').done(function () {
    $('#modal_avisos_config').data('fm-salir', true).modal('hide');
    Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Avisos configurados' });
    Avisos_Cargar();
  });
}
