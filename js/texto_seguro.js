// Seguridad al mostrar datos (Fase 8C).
// Todo texto que viene de la base se muestra como texto, nunca como HTML: así un nombre, tema o comentario
// con "<script>" o atributos no se ejecuta en el navegador de quien lo ve.
//
// Muchos registros antiguos se guardaron ya escapados (htmlspecialchars al registrar: "&amp;", "&#039;").
// textoSeguro() primero decodifica esas entidades y luego escapa: se ve "O'Brien" y no "O&#039;Brien",
// sin escapar dos veces y sin permitir HTML.
(function () {
  var decodificador = document.createElement('textarea');
  var reemplazos = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;', '`': '&#096;' };

  // Texto plano (sin entidades HTML) para comparar, buscar o poner en .val()/.text()
  window.textoPlano = function (v) {
    if (v === null || v === undefined) { return ''; }
    var s = String(v);
    if (s.indexOf('&') === -1) { return s; }
    decodificador.innerHTML = s.replace(/</g, '&lt;');
    return decodificador.value;
  };

  // Texto listo para insertar en HTML (contenido o atributos entre comillas simples o dobles)
  window.textoSeguro = function (v) {
    return window.textoPlano(v).replace(/[&<>"'`]/g, function (c) { return reemplazos[c]; });
  };

  // DataTables: toda columna sin "render" propio se muestra escapada (antes se insertaba como HTML).
  // Las columnas con render propio construyen su HTML y usan textoSeguro() para los datos.
  if (window.jQuery && jQuery.fn.dataTable) {
    jQuery.extend(true, jQuery.fn.dataTable.defaults, {
      columnDefs: [{
        targets: '_all',
        render: function (data, type) {
          // null/objeto: DataTables usa entonces "defaultContent" (botones de acción) como antes
          if (data === null || data === undefined) { return null; }
          if (typeof data === 'object') { return data; }
          return type === 'display' ? window.textoSeguro(data) : window.textoPlano(data);
        }
      }]
    });
  }
})();
