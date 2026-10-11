// Módulo opcional: asistente de IA del panel (botón flotante en view/index.php).
// Funciones globales con nombre propio: tools/analizar_roles.py sigue las llamadas desde el layout de cada rol.
var conversacionAsistente = []; // [{rol: 'usuario'|'asistente', texto}], se manda como historial (últimos turnos)

function textoAsistente(texto) {
  return $('<div>').text(texto == null ? '' : String(texto)).html().replace(/\n/g, '<br>');
}

function agregarMensajeAsistente(rol, texto, clase) {
  var estilo = rol === 'usuario' ? 'bg-primary text-white ml-4' : 'bg-light mr-4';
  $('#asistente_mensajes').append('<div class="asistente-mensaje p-2 mb-2 rounded ' + estilo + (clase ? ' ' + clase : '') + '" data-rol="' + rol + '">'
    + textoAsistente(texto) + '</div>');
  $('#asistente_mensajes').scrollTop($('#asistente_mensajes')[0].scrollHeight);
}

function usoAsistente(r) {
  if (r && r.limite_mes != null) {
    $('#asistente_uso').text('Consultas de la institución este mes: ' + r.usadas_mes + ' de ' + r.limite_mes);
  }
}

function abrirAsistente() {
  $('#asistente_panel').css('display', 'flex');
  $('#asistente_pregunta').trigger('focus');
  $.ajax({ url: '../controller/asistente/controlador_asistente.php', type: 'GET', dataType: 'json', global: false }).done(usoAsistente);
}

function cerrarAsistente() {
  $('#asistente_panel').css('display', 'none');
}

function enviarPreguntaAsistente() {
  var pregunta = $.trim($('#asistente_pregunta').val());
  if (!pregunta) { return; }
  agregarMensajeAsistente('usuario', pregunta);
  $('#asistente_pregunta').val('');
  $('#asistente_enviar, #asistente_pregunta').prop('disabled', true);
  agregarMensajeAsistente('asistente', 'Consultando…', 'asistente-pensando text-muted');
  $.ajax({
    url: '../controller/asistente/controlador_asistente.php', type: 'POST', dataType: 'json', global: false,
    data: { pregunta: pregunta, historial: JSON.stringify(conversacionAsistente.slice(-6)) }
  }).done(function (r) {
    $('.asistente-pensando').remove();
    agregarMensajeAsistente('asistente', r.respuesta, 'asistente-respuesta');
    conversacionAsistente.push({ rol: 'usuario', texto: pregunta }, { rol: 'asistente', texto: r.respuesta });
    usoAsistente(r);
  }).fail(function (xhr) {
    $('.asistente-pensando').remove();
    if (xhr.status === 401 || xhr.status === 419) { window.location.reload(); return; }
    agregarMensajeAsistente('asistente', (xhr.responseJSON && xhr.responseJSON.error) || 'El asistente no respondió.', 'asistente-error text-danger');
  }).always(function () {
    $('#asistente_enviar, #asistente_pregunta').prop('disabled', false);
    $('#asistente_pregunta').trigger('focus');
  });
}
