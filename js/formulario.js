// Ayudas comunes de los formularios (Fase 13). Se carga en view/index.php para todas las pantallas.
window.Formulario = (function () {

  // Posible duplicado ("7|documento" desde el servidor): pregunta si es otra persona y, si se confirma,
  // reenvía la misma petición marcando confirmar_duplicado. Devuelve true si la respuesta era un duplicado.
  function duplicado(resp, ajax, quien) {
    if (String(resp).indexOf('7|') !== 0) { return false; }
    var doc = String(resp).substr(2);
    var etiqueta = (window.REGIONAL && REGIONAL.doc_nombre) || 'documento';
    Swal.fire({
      icon: 'question',
      title: '¿Es otra persona?',
      html: 'Ya hay un ' + textoSeguro(quien || 'registro') + ' con el <b>mismo nombre y fecha de nacimiento</b>, con ' + textoSeguro(etiqueta)
        + ' <b>' + textoSeguro(doc) + '</b>.<br><small class="text-muted">Si es la misma persona, revise el ' + textoSeguro(etiqueta)
        + ' escrito: puede ser un error de tipeo.</small>',
      showCancelButton: true,
      confirmButtonText: 'Sí, es otra persona: registrar',
      cancelButtonText: 'Revisar los datos'
    }).then(function (r) {
      if (!r.isConfirmed) { return; }
      var datos = ajax.data;
      if (datos instanceof FormData) { datos.set('confirmar_duplicado', '1'); } else { datos = (datos ? datos + '&' : '') + 'confirmar_duplicado=1'; }
      $.ajax($.extend({}, ajax, { data: datos }));
    });
    return true;
  }

  return { duplicado: duplicado };
})();
