// Cobrar pensión (caja): buscar al estudiante, marcar pensiones u otros conceptos, ajustar montos o exonerar mora,
// repartir en formas de pago y cobrar. El cobro lo valida y registra controlador_detalle_pago_pension.php (como siempre).
var CJ = { estado: null, carrito: [], formas: null, pagos: [], temporizador: null, encontrados: [] };
var CJ_URL = '../controller/pago_pension/controlador_caja.php';
var CJ_COBRAR = '../controller/pago_pension/controlador_detalle_pago_pension.php';
var CJ_ANULAR = '../controller/pago_pension/controlador_eliminar_pago_pension.php';
var CJ_FORMAS = '../controller/pago_pension/controlador_cargar_formas_pago.php';

function CJ_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function CJ_Aula(e) { return CJ_Titulo(e.grado) + (e.seccion && e.seccion !== 'UNICO' ? ' ' + e.seccion : '') + ' · ' + CJ_Titulo(e.nivel); }
function CJ_Fecha(f) { return f ? new Date(f + 'T00:00:00').toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; }
function CJ_Redondear(n) { return Math.round((+n || 0) * 100) / 100; }

function Caja_Iniciar() {
  CJ.carrito = [];
  CJ.estado = null;
  $(document).off('.cj');
  $(document).on('input.cj', '#cj_buscar', CJ_Buscar);
  $(document).on('keydown.cj', '#cj_buscar', function (e) { if (e.key === 'Enter' && CJ.encontrados.length === 1) { CJ_Elegir(0); } });
  $(document).on('click.cj', '.cj-resultado', function () { CJ_Elegir(+$(this).data('i')); });
  $(document).on('click.cj', function (e) { if (!$(e.target).closest('.cj-buscador').length) { $('#cj_resultados').addClass('d-none'); } });
  $(document).on('click.cj', '.cj-mes:not(.cerrado), .cj-otro:not(.cerrado)', function () { CJ_Alternar($(this).data('clave')); });
  $(document).on('input.cj', '.cj-monto', function () { var l = CJ_Linea($(this).data('clave')); l.monto = $(this).val(); CJ_Totales(); });
  $(document).on('change.cj', '.cj-exonerar', function () { CJ_Exonerar($(this).data('clave'), this.checked); });
  $(document).on('input.cj', '.cj-motivo', function () { CJ_Linea($(this).data('clave')).motivo = $(this).val(); });
  $(document).on('click.cj', '.cj-quitar', function () { CJ_Alternar($(this).data('clave')); });
  $(document).on('click.cj', '#cj_dividir', function () { CJ.pagos.push({ metodo: CJ.formas.metodos[0].id_metodo, cuenta: '', monto: CJ_Falta().toFixed(2), referencia: '' }); CJ_SugerirCuenta(CJ.pagos.length - 1); CJ_PintarCobro(); });
  $(document).on('click.cj', '.cj-fp-quitar', function () { CJ.pagos.splice(+$(this).data('i'), 1); CJ_PintarCobro(); });
  $(document).on('change.cj', '.cj-fp-metodo', function () { var i = +$(this).data('i'); CJ.pagos[i].metodo = $(this).val(); CJ_SugerirCuenta(i); CJ_PintarCobro(); });
  $(document).on('change.cj', '.cj-fp-cuenta', function () { CJ.pagos[+$(this).data('i')].cuenta = $(this).val(); });
  $(document).on('input.cj', '.cj-fp-monto', function () { CJ.pagos[+$(this).data('i')].monto = $(this).val(); CJ_Totales(); });
  $(document).on('input.cj', '.cj-fp-ref', function () { CJ.pagos[+$(this).data('i')].referencia = $(this).val(); });
  $(document).on('click.cj', '#cj_cobrar', CJ_Cobrar);
  $(document).on('click.cj', '.cj-anular', function () { CJ_Anular(CJ.estado.recibos[+$(this).data('i')]); });
  $(document).on('change.cj', '#cj_anio', function () { $('#cj_buscar').val('').focus(); $('#cj_resultados').addClass('d-none'); });
  $.post(CJ_URL, { accion: 'anios' }, null, 'json').done(function (d) {
    $('#cj_anio').html(d.anios.map(function (a) { return '<option value="' + a.id + '">Año ' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>'; }).join('')).val(d.anio);
  });
  $.post(CJ_FORMAS, null, null, 'json').done(function (f) { CJ.formas = f; });
  CJ_PintarCobro();
  $('#cj_buscar').focus();
}

// ---------- Buscar ----------
function CJ_Buscar() {
  var q = $.trim($(this).val());
  clearTimeout(CJ.temporizador);
  if (q.length < 2) { $('#cj_resultados').addClass('d-none').empty(); return; }
  CJ.temporizador = setTimeout(function () {
    $.post(CJ_URL, { accion: 'buscar', anio: $('#cj_anio').val(), q: q }, null, 'json').done(function (r) {
      CJ.encontrados = r.estudiantes;
      $('#cj_resultados').removeClass('d-none').html(r.estudiantes.length ? r.estudiantes.map(function (e, i) {
        var deuda = e.vencido > 0 ? '<span class="av-chip av-chip-mal">Vencido ' + formatoMoneda(e.vencido) + '</span>'
                  : e.pendiente > 0 ? '<span class="av-chip">Por pagar ' + formatoMoneda(e.pendiente) + '</span>' : '<span class="av-chip av-chip-ok">Al día</span>';
        return '<button type="button" class="cj-resultado" data-i="' + i + '"><span><b>' + textoSeguro(CJ_Titulo(e.apellidos + ', ' + e.nombres)) + '</b>' +
          '<small>' + textoSeguro(CJ_Aula(e)) + ' · DNI ' + textoSeguro(e.documento) + '</small></span>' + deuda + '</button>';
      }).join('') : '<p class="text-muted mb-0 p-2">Ningún estudiante matriculado en ese año coincide.</p>');
    });
  }, 250);
}

function CJ_Elegir(i) {
  var e = CJ.encontrados[i];
  $('#cj_resultados').addClass('d-none');
  $('#cj_buscar').val(CJ_Titulo(e.apellidos + ', ' + e.nombres));
  CJ_Cargar(e.matricula);
}

function CJ_Cargar(matricula) {
  $.post(CJ_URL, { accion: 'estado', matricula: matricula }, null, 'json').done(function (d) {
    CJ.estado = d;
    CJ.carrito = [];
    CJ.pagos = [];
    CJ_PintarEstado();
    CJ_PintarCobro();
  });
}

// ---------- Estado de cuenta ----------
function CJ_EstadoPension(p) {
  var saldo = +p.saldo_pension + +p.saldo_mora;
  if (+p.exonerada) { return { tipo: 'exonerada', chip: 'av-chip-info', texto: 'Exonerada', saldo: 0 }; }
  if (saldo <= 0) { return { tipo: 'pagada', chip: 'av-chip-ok', texto: 'Pagada', saldo: 0 }; }
  if (+p.vencida) { return { tipo: 'vencida', chip: 'av-chip-mal', texto: 'Vencida', saldo: saldo }; }
  if (+p.pagado_pension > 0) { return { tipo: 'parcial', chip: 'av-chip-pend', texto: 'Pago parcial', saldo: saldo }; }
  return { tipo: 'pendiente', chip: 'av-chip', texto: 'Por pagar', saldo: saldo };
}

function CJ_PintarEstado() {
  var d = CJ.estado, e = d.estudiante, vencido = 0, pendiente = 0, pagado = 0;
  d.pensiones.forEach(function (p) {
    var s = CJ_EstadoPension(p);
    p._s = s;
    pendiente += s.saldo;
    if (s.tipo === 'vencida') { vencido += s.saldo; }
  });
  d.recibos.forEach(function (r) { pagado += r.total; });
  var beca = e.beca_tipo && e.beca_tipo !== 'NINGUNA' ? '<span class="av-chip av-chip-info">Beca ' + (e.beca_tipo === 'PORCENTAJE' ? Number(e.beca_valor) + '%' : formatoMoneda(e.beca_valor)) + '</span>' : '';
  var cabecera = '<section class="cj-estudiante"><span class="av-avatar" style="background:' + Curso_Color(e.nombres + e.apellidos) + '">' + textoSeguro(e.nombres.charAt(0)) + '</span>' +
    '<div><h2>' + textoSeguro(CJ_Titulo(e.nombres + ' ' + e.apellidos)) + '</h2><p>' + textoSeguro(CJ_Aula(e)) + ' · DNI ' + textoSeguro(e.documento) + ' ' + beca + '</p></div>' +
    '<div class="cj-cifras"><span><small>Vencido</small><b class="' + (vencido ? 'pe-nota-mal' : '') + '">' + formatoMoneda(vencido) + '</b></span>' +
    '<span><small>Por pagar en el año</small><b>' + formatoMoneda(pendiente) + '</b></span><span><small>Pagado</small><b class="pe-nota-ok">' + formatoMoneda(pagado) + '</b></span></div></section>';
  var meses = '<h3 class="pe-seccion">Pensiones <small class="text-muted">— toque un mes para agregarlo al cobro</small></h3><div class="cj-meses">' + d.pensiones.map(function (p) {
    var s = p._s, cerrado = !s.saldo, clave = 'P' + p.id;
    return '<button type="button" class="cj-mes cj-' + s.tipo + (cerrado ? ' cerrado' : '') + (CJ_Linea(clave) ? ' elegido' : '') + '" data-clave="' + clave + '"' + (cerrado ? ' aria-disabled="true"' : '') + '>' +
      '<span class="cj-check"><i class="fas fa-check"></i></span><b>' + textoSeguro(CJ_Titulo(p.mes)) + '</b>' +
      '<strong>' + formatoMoneda(s.saldo || +p.precio) + '</strong>' + (s.tipo === 'vencida' && +p.saldo_mora > 0 ? '<small class="pe-nota-mal">incluye ' + formatoMoneda(p.saldo_mora) + ' de mora</small>' : '<small>Vence ' + CJ_Fecha(p.vence) + '</small>') +
      '<span class="av-chip ' + s.chip + '">' + s.texto + '</span></button>';
  }).join('') + '</div>';
  var otros = '<h3 class="pe-seccion">Otros conceptos</h3><div class="cj-meses">' + d.otros.map(function (o) {
    var clave = 'O' + o.concepto, cerrado = o.monto > 0 && o.saldo <= 0;
    return '<button type="button" class="cj-otro' + (cerrado ? ' cerrado cj-pagada' : '') + (CJ_Linea(clave) ? ' elegido' : '') + '" data-clave="' + clave + '">' +
      '<span class="cj-check"><i class="fas fa-check"></i></span><b>' + textoSeguro(CJ_Titulo(o.concepto)) + '</b>' +
      '<strong>' + (o.monto > 0 ? formatoMoneda(o.saldo) : 'Monto libre') + '</strong><small>' + (o.monto > 0 ? 'Acordado ' + formatoMoneda(o.monto) + ' · pagado ' + formatoMoneda(o.pagado) : 'No se fijó en la matrícula') + '</small>' +
      (cerrado ? '<span class="av-chip av-chip-ok">Pagado</span>' : '') + '</button>';
  }).join('') + '</div>';
  var recibos = '<h3 class="pe-seccion">Pagos realizados</h3>' + (d.recibos.length ? '<div class="pe-recibos">' + d.recibos.map(function (r, i) {
    var url = r.id_pago ? '../view/MPDF/REPORTE/recibo.php?id=' + encodeURIComponent(r.id_pago)
                        : '../view/MPDF/REPORTE/boleta_pago.php?codigo=' + encodeURIComponent(e.matricula) + '&idpagopen=' + encodeURIComponent(r.id_pago_pension);
    return '<article class="pe-recibo"><span class="pe-recibo-icono"><i class="fas fa-receipt"></i></span><div class="pe-recibo-cuerpo"><h4>' + CJ_Fecha(r.fecha) +
      (r.recibo ? ' <small>· Recibo ' + textoSeguro(r.recibo) + '</small>' : '') + '</h4><p>' + r.conceptos.map(function (c) { return '<span class="av-chip">' + textoSeguro(CJ_Titulo(c.concepto)) + ' · ' + formatoMoneda(c.monto) + '</span>'; }).join(' ') +
      '</p><small class="text-muted">' + textoSeguro(CJ_Titulo(r.metodos || '')) + '</small></div><div class="pe-recibo-total"><b>' + formatoMoneda(r.total) + '</b><span>' +
      '<a class="btn btn-outline-secondary btn-sm" href="' + url + '" target="_blank" rel="noopener"><i class="fas fa-print mr-1"></i>Recibo</a> ' +
      '<button type="button" class="btn btn-outline-danger btn-sm cj-anular" data-i="' + i + '"><i class="fas fa-ban mr-1"></i>Anular</button></span></div></article>';
  }).join('') + '</div>' : '<p class="text-muted">Aún no tiene pagos en este año.</p>') +
    '<p class="mt-3"><a href="../view/MPDF/REPORTE/kardex.php?codigo=' + encodeURIComponent(e.matricula) + '" target="_blank" rel="noopener" class="btn btn-link px-0"><i class="fas fa-file-pdf mr-1"></i>Kardex de pagos del estudiante</a></p>';
  $('#cj_estado').html(cabecera + meses + otros + recibos);
}

// ---------- Cobro ----------
function CJ_Linea(clave) { return CJ.carrito.filter(function (l) { return l.clave === clave; })[0]; }

function CJ_Alternar(clave) {
  var existe = CJ_Linea(clave);
  if (existe) { CJ.carrito = CJ.carrito.filter(function (l) { return l.clave !== clave; }); }
  else if (clave.charAt(0) === 'P') {
    var p = CJ.estado.pensiones.filter(function (x) { return 'P' + x.id === clave; })[0];
    CJ.carrito.push({ clave: clave, concepto: 'PENSION', id_pension: p.id, nombre: 'Pensión ' + CJ_Titulo(p.mes), saldo: p._s.saldo, mora: +p.saldo_mora,
                      monto: p._s.saldo.toFixed(2), exonerar: false, motivo: '' });
  } else {
    var o = CJ.estado.otros.filter(function (x) { return 'O' + x.concepto === clave; })[0];
    CJ.carrito.push({ clave: clave, concepto: o.concepto, id_pension: '', nombre: CJ_Titulo(o.concepto), saldo: o.monto > 0 ? o.saldo : null, mora: 0,
                      monto: o.monto > 0 ? o.saldo.toFixed(2) : '', exonerar: false, motivo: '' });
  }
  $('.cj-mes[data-clave="' + clave + '"], .cj-otro[data-clave="' + clave + '"]').toggleClass('elegido', !existe);
  CJ_PintarCobro();
}

function CJ_Exonerar(clave, si) {
  var l = CJ_Linea(clave);
  l.exonerar = si;
  l.monto = (si ? l.saldo - l.mora : l.saldo).toFixed(2);
  CJ_PintarCobro();
}

function CJ_Total() { return CJ_Redondear(CJ.carrito.reduce(function (s, l) { return s + (parseFloat(l.monto) || 0); }, 0)); }
function CJ_Falta() { return CJ_Redondear(CJ_Total() - CJ.pagos.reduce(function (s, p) { return s + (parseFloat(p.monto) || 0); }, 0)); }

function CJ_SugerirCuenta(i) {
  var p = CJ.pagos[i], m = CJ.formas.metodos.filter(function (x) { return +x.id_metodo === +p.metodo; })[0];
  if (!m) { return; }
  var tipo = m.tipo === 'EFECTIVO' ? 'CAJA' : (m.tipo === 'BILLETERA' ? 'BILLETERA' : 'BANCO');
  var c = CJ.formas.cuentas.filter(function (x) { return x.tipo === tipo; })[0];
  p.cuenta = c ? c.id_cuenta : '';
}

function CJ_PintarCobro() {
  if (!CJ.estado) { $('#cj_cobro').html('<h4>Cobro</h4><p class="text-muted small mb-0">Aquí aparecerá lo que va a cobrar.</p>'); return; }
  if (!CJ.carrito.length) { CJ.pagos = []; $('#cj_cobro').html('<h4>Cobro</h4><p class="text-muted small mb-0">Toque los meses o conceptos que va a pagar.</p>'); return; }
  if (!CJ.pagos.length && CJ.formas) { CJ.pagos = [{ metodo: CJ.formas.metodos[0].id_metodo, cuenta: '', monto: '', referencia: '' }]; CJ_SugerirCuenta(0); }
  if (CJ.pagos.length === 1) { CJ.pagos[0].monto = CJ_Total().toFixed(2); }
  var lineas = CJ.carrito.map(function (l) {
    return '<div class="cj-linea"><div class="cj-linea-cab"><b>' + textoSeguro(l.nombre) + '</b><button type="button" class="cj-quitar" data-clave="' + l.clave + '" aria-label="Quitar"><i class="fas fa-times"></i></button></div>' +
      '<div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">' + textoSeguro(REGIONAL.simbolo || 'S/') + '</span></div>' +
      '<input type="text" inputmode="decimal" class="form-control cj-monto" data-clave="' + l.clave + '" value="' + textoSeguro(l.monto) + '" aria-label="Monto de ' + textoSeguro(l.nombre) + '"></div>' +
      (l.saldo !== null ? '<small class="text-muted">Saldo ' + formatoMoneda(l.saldo) + (l.concepto === 'PENSION' ? ' · puede pagar una parte' : '') + '</small>' : '') +
      (l.mora > 0 ? '<label class="cj-exo"><input type="checkbox" class="cj-exonerar" data-clave="' + l.clave + '"' + (l.exonerar ? ' checked' : '') + '> Exonerar la mora (' + formatoMoneda(l.mora) + ')</label>' +
        (l.exonerar ? '<input type="text" class="form-control form-control-sm cj-motivo" data-clave="' + l.clave + '" maxlength="200" placeholder="Motivo de la exoneración (obligatorio)" value="' + textoSeguro(l.motivo) + '">' : '') : '') + '</div>';
  }).join('');
  var metodos = CJ.formas ? CJ.formas.metodos : [], cuentas = CJ.formas ? CJ.formas.cuentas : [];
  var pagos = CJ.pagos.map(function (p, i) {
    var m = metodos.filter(function (x) { return +x.id_metodo === +p.metodo; })[0] || {};
    return '<div class="cj-fp"><div class="cj-fp-fila"><select class="form-control form-control-sm cj-fp-metodo" data-i="' + i + '" aria-label="Forma de pago">' +
      metodos.map(function (x) { return '<option value="' + x.id_metodo + '"' + (+x.id_metodo === +p.metodo ? ' selected' : '') + '>' + textoSeguro(CJ_Titulo(x.nombre)) + '</option>'; }).join('') + '</select>' +
      (CJ.pagos.length > 1 ? '<input type="text" inputmode="decimal" class="form-control form-control-sm cj-fp-monto" data-i="' + i + '" value="' + textoSeguro(p.monto) + '" aria-label="Monto">' +
        '<button type="button" class="cj-quitar cj-fp-quitar" data-i="' + i + '" aria-label="Quitar forma de pago"><i class="fas fa-times"></i></button>' : '') + '</div>' +
      '<select class="form-control form-control-sm cj-fp-cuenta" data-i="' + i + '" aria-label="Caja o cuenta"><option value="">Caja o cuenta…</option>' +
      cuentas.map(function (c) { return '<option value="' + c.id_cuenta + '"' + (+c.id_cuenta === +p.cuenta ? ' selected' : '') + '>' + textoSeguro(CJ_Titulo(c.nombre) + (c.banco ? ' · ' + c.banco : '')) + '</option>'; }).join('') + '</select>' +
      (+m.requiere_referencia ? '<input type="text" class="form-control form-control-sm cj-fp-ref" data-i="' + i + '" maxlength="60" placeholder="N.° de operación (obligatorio)" value="' + textoSeguro(p.referencia) + '">' : '') + '</div>';
  }).join('');
  $('#cj_cobro').html('<h4>Cobro</h4>' + lineas +
    '<div class="cj-total"><span>Total</span><b id="cj_total">' + formatoMoneda(CJ_Total()) + '</b></div>' +
    '<h5>Forma de pago</h5>' + pagos + '<button type="button" class="btn btn-link btn-sm px-0" id="cj_dividir"><i class="fas fa-plus mr-1"></i>Pagar con otra forma más</button>' +
    '<p id="cj_falta" class="small mb-2"></p>' +
    '<input type="text" class="form-control form-control-sm mb-2" id="cj_obs" maxlength="255" placeholder="Observación (opcional)">' +
    '<label class="small text-muted mb-1" for="cj_voucher">Voucher (opcional)</label><input type="file" class="form-control-file mb-3" id="cj_voucher" accept="image/*,.pdf">' +
    '<button type="button" class="btn btn-success btn-lg btn-block" id="cj_cobrar"><i class="fas fa-check mr-1"></i>Cobrar <span id="cj_boton_total">' + formatoMoneda(CJ_Total()) + '</span></button>');
  CJ_Totales();
}

function CJ_Totales() {
  if (CJ.pagos.length === 1) { CJ.pagos[0].monto = CJ_Total().toFixed(2); }
  $('#cj_total, #cj_boton_total').text(formatoMoneda(CJ_Total()));
  var falta = CJ_Falta();
  $('#cj_falta').html(CJ.pagos.length < 2 ? '' : falta === 0 ? '<span class="text-success"><i class="fas fa-check mr-1"></i>Formas de pago completas</span>'
    : '<span class="text-danger">' + (falta > 0 ? 'Falta asignar ' + formatoMoneda(falta) : 'Sobra ' + formatoMoneda(-falta)) + '</span>');
}

function CJ_Cobrar() {
  var e = CJ.estado.estudiante, error = '';
  CJ.carrito.forEach(function (l) {
    if (error) { return; }
    if (!/^\d+(\.\d{1,2})?$/.test(String(l.monto).replace(',', '.'))) { error = 'Escriba un monto válido en ' + l.nombre; }
    else if (parseFloat(l.monto) <= 0 && !l.exonerar) { error = 'El monto de ' + l.nombre + ' debe ser mayor a 0'; }
    else if (l.saldo !== null && parseFloat(l.monto) > CJ_Redondear(l.exonerar ? l.saldo - l.mora : l.saldo)) { error = 'El monto de ' + l.nombre + ' supera su saldo'; }
    else if (l.exonerar && !$.trim(l.motivo)) { error = 'Escriba el motivo para exonerar la mora de ' + l.nombre; }
  });
  var total = CJ_Total(), metodos = total > 0 ? CJ.pagos : [];
  metodos.forEach(function (p) {
    var m = CJ.formas.metodos.filter(function (x) { return +x.id_metodo === +p.metodo; })[0] || {};
    if (!error && +m.requiere_referencia && !$.trim(p.referencia)) { error = 'Escriba el número de operación de ' + CJ_Titulo(m.nombre); }
    if (!error && +m.requiere_cuenta && !p.cuenta) { error = 'Elija la caja o cuenta donde entra el dinero'; }
  });
  if (!error && total > 0 && CJ_Falta() !== 0) { error = 'Las formas de pago deben sumar ' + formatoMoneda(total); }
  if (error) { return Swal.fire('Revise el cobro', error, 'warning'); }
  Swal.fire({ title: '¿Cobrar ' + formatoMoneda(total) + '?', html: textoSeguro(CJ_Titulo(e.nombres + ' ' + e.apellidos)) + '<br><small>' + CJ.carrito.map(function (l) { return textoSeguro(l.nombre); }).join(', ') + '</small>',
              icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, cobrar', cancelButtonText: 'Revisar' }).then(function (r) {
    if (!r.isConfirmed) { return; }
    var fd = new FormData();
    fd.append('id_matri', e.matricula);
    fd.append('lineas', JSON.stringify(CJ.carrito.map(function (l) { return { concepto: l.concepto, id_pension: l.id_pension, monto: String(l.monto).replace(',', '.'), exonerar: l.exonerar ? 1 : 0, motivo: l.motivo }; })));
    fd.append('metodos', JSON.stringify(metodos.map(function (p) { return { id_metodo: p.metodo, id_cuenta: p.cuenta, monto: p.monto, referencia: p.referencia }; })));
    fd.append('observacion', $('#cj_obs').val());
    if ($('#cj_voucher')[0].files[0]) { fd.append('voucher', $('#cj_voucher')[0].files[0]); }
    $('#cj_cobrar').prop('disabled', true);
    $.ajax({ url: CJ_COBRAR, type: 'POST', data: fd, contentType: false, processData: false }).done(function (resp) {
      if (!resp.ok) {
        $('#cj_cobrar').prop('disabled', false);
        return Swal.fire('No se registró el pago', (resp.linea ? 'Fila ' + resp.linea + ': ' : '') + textoSeguro(resp.mensaje || ''), 'warning');
      }
      Swal.fire({ title: 'Pago registrado', html: 'Recibo N.° <b>' + textoSeguro(resp.recibo || '') + '</b> por <b>' + formatoMoneda(resp.total) + '</b>', icon: 'success',
                  showCancelButton: true, confirmButtonText: '<i class="fas fa-print mr-1"></i>Imprimir recibo', cancelButtonText: 'Listo' }).then(function (x) {
        if (x.isConfirmed && resp.id_pago) { window.open('../view/MPDF/REPORTE/recibo.php?id=' + encodeURIComponent(resp.id_pago), '_blank'); }
      });
      CJ_Cargar(e.matricula);
    }).fail(function () { $('#cj_cobrar').prop('disabled', false); });
  });
}

function CJ_Anular(r) {
  Swal.fire({ title: r.recibo ? '¿Anular el recibo ' + textoSeguro(r.recibo) + '?' : '¿Anular este pago?', text: 'Se anulan todos sus conceptos y su ingreso (no se borran). Escriba el motivo:',
              icon: 'warning', input: 'text', inputPlaceholder: 'Motivo de la anulación', showCancelButton: true, confirmButtonText: 'Anular', confirmButtonColor: '#d33', cancelButtonText: 'Cancelar',
              inputValidator: function (v) { if (!v || !v.trim()) { return 'El motivo es obligatorio'; } } }).then(function (x) {
    if (!x.isConfirmed) { return; }
    $.post(CJ_ANULAR, { id: r.id_pago_pension, motivo: x.value.trim() }, null, 'json').done(function (resp) {
      if (resp && resp.ok === false) { return Swal.fire('No se pudo anular', textoSeguro(resp.mensaje || ''), 'warning'); }
      Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Pago anulado' });
      CJ_Cargar(CJ.estado.estudiante.matricula);
    });
  });
}
