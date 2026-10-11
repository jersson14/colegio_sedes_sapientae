// H-15: auditoría de la institución (view/auditoria/view_auditoria.php).
var Auditoria = (function () {
  var URL = '../controller/auditoria/controlador_listar_auditoria.php';
  var RESULTADOS = { 200: ['success', 'Correcto'], 401: ['warning', 'Sin sesión / fallido'], 402: ['warning', 'Bloqueado por el plan'],
    403: ['danger', 'Denegado'], 419: ['danger', 'Token inválido'], 422: ['warning', 'Datos inválidos'], 429: ['warning', 'Demasiados intentos'] };

  function escAuditoria(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
  }

  function buscarAuditoria() {
    var filtros = { buscar: 1, desde: $('#au_desde').val(), hasta: $('#au_hasta').val(), usuario: $('#au_usuario').val(), modulo: $('#au_modulo').val() };
    $.ajax({ url: URL, type: 'GET', data: filtros, dataType: 'json' }).done(function (filas) {
      var html = filas.map(function (f) {
        var r = RESULTADOS[f.estado] || [f.estado >= 400 ? 'danger' : 'secondary', String(f.estado)];
        return '<tr data-estado="' + escAuditoria(f.estado) + '"><td class="text-nowrap">' + escAuditoria(f.fecha) + '</td><td>' + escAuditoria(f.usuario) + '</td><td>' + escAuditoria(f.rol)
          + '</td><td>' + escAuditoria(f.modulo) + '</td><td>' + escAuditoria(f.accion) + '</td><td><span class="badge badge-' + r[0] + '">' + escAuditoria(r[1])
          + '</span></td><td>' + escAuditoria(f.ip) + '</td><td><small class="text-monospace" style="word-break:break-all">' + escAuditoria(f.datos) + '</small></td></tr>';
      });
      $('#tabla_auditoria tbody').html(html.length ? html.join('') : '<tr><td colspan="8" class="text-muted">Sin registros con esos filtros.</td></tr>');
    });
  }

  return {
    iniciar: function () {
      $.ajax({ url: URL, type: 'GET', dataType: 'json' }).done(function (c) {
        $('#au_modulo').append(c.modulos.map(function (m) { return '<option value="' + escAuditoria(m) + '">' + escAuditoria(m) + '</option>'; }).join(''));
      });
      $('#au_buscar').on('click', buscarAuditoria);
      buscarAuditoria();
    }
  };
})();
