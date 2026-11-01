// Mis boletas de pago (todo el personal): una tarjeta por mes con el neto, el detalle y la boleta en PDF.
var MB = { lista: [] };

function MisBoletas_Iniciar() {
  $(document).off('.mb');
  $(document).on('change.mb', '#mb_anio', MisBoletas_Pintar);
  $.post('../controller/planilla/controlador_mis_boletas.php', null, null, 'json').done(function (r) {
    MB.lista = r.data || [];
    var anios = [];
    MB.lista.forEach(function (b) { if (anios.indexOf(String(b.anio)) === -1) { anios.push(String(b.anio)); } });
    anios.sort().reverse();
    $('#mb_anio').toggleClass('d-none', anios.length < 2).html(anios.map(function (a) { return '<option value="' + a + '">Año ' + a + '</option>'; }).join(''));
    MisBoletas_Pintar();
  });
}

function MisBoletas_Estado(e) {
  var x = String(e || '').toUpperCase();
  if (x === 'PAGADA') { return '<span class="av-chip av-chip-ok">Pagada</span>'; }
  if (x === 'APROBADA') { return '<span class="av-chip av-chip-pend">Por pagar</span>'; }
  return '<span class="av-chip">' + textoSeguro(window.PanelComun ? PanelComun.titulo(x) : x) + '</span>';
}

function MisBoletas_Pintar() {
  if (!MB.lista.length) {
    $('#mb_contenido').html('<div class="av-vacio"><i class="fas fa-file-invoice-dollar"></i><p>Aún no tiene boletas de pago.<br>Aparecerán aquí cuando la contadora apruebe la planilla del mes.</p></div>');
    return;
  }
  var anio = $('#mb_anio').val() || String(MB.lista[0].anio);
  var lista = MB.lista.filter(function (b) { return String(b.anio) === anio; });
  var cobrado = 0, pendiente = 0;
  lista.forEach(function (b) { cobrado += +b.pagado || 0; pendiente += Math.max(0, (+b.neto || 0) - (+b.pagado || 0)); });
  var ultima = lista[0];
  var resumen = '<section class="pe-resumen">' +
    '<div class="pe-dato pe-dato-grande"><span>Último neto (' + textoSeguro(ultima.mes_nombre) + ')</span><b>' + formatoMoneda(ultima.neto) + '</b></div>' +
    '<div class="pe-dato"><span>Cobrado en el año</span><b class="pe-nota-ok">' + formatoMoneda(cobrado) + '</b><small>' + lista.length + (lista.length === 1 ? ' boleta' : ' boletas') + '</small></div>' +
    '<div class="pe-dato"><span>Por cobrar</span><b class="' + (pendiente > 0 ? 'pe-nota-mal' : '') + '">' + formatoMoneda(pendiente) + '</b><small>' + (pendiente > 0 ? 'boletas aprobadas sin pagar' : 'todo cobrado') + '</small></div></section>';
  var tarjetas = '<div class="mb-boletas">' + lista.map(function (b) {
    var fila = function (t, v, clase) { return '<li><span>' + t + '</span><b class="' + (clase || '') + '">' + v + '</b></li>'; };
    return '<article class="mb-boleta"><header><div><h4>' + textoSeguro(b.mes_nombre) + ' ' + textoSeguro(b.anio) + '</h4><small>' + textoSeguro(window.PanelComun ? PanelComun.titulo(b.tipo_contrato) : b.tipo_contrato) + '</small></div>' +
      MisBoletas_Estado(b.estado) + '</header><p class="mb-neto"><span>Neto</span>' + formatoMoneda(b.neto) + '</p><ul>' +
      fila('Sueldo / horas', formatoMoneda(b.base)) + (+b.ingresos ? fila('Otros ingresos', '+ ' + formatoMoneda(b.ingresos), 'pe-nota-ok') : '') +
      (+b.descuentos ? fila('Descuentos', '− ' + formatoMoneda(b.descuentos), 'pe-nota-mal') : '') + (+b.adelantos ? fila('Adelantos', '− ' + formatoMoneda(b.adelantos), 'pe-nota-mal') : '') +
      fila('Pagado', formatoMoneda(b.pagado)) + '</ul>' +
      '<a class="btn btn-outline-primary btn-block" target="_blank" rel="noopener" href="../view/MPDF/REPORTE/boleta_docente.php?id=' + encodeURIComponent(b.id_detalle) + '"><i class="fas fa-print mr-1"></i>Ver boleta</a></article>';
  }).join('') + '</div>';
  $('#mb_contenido').html(resumen + tarjetas);
}
