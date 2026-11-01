// Cajas y bancos: tarjetas de saldo por caja/cuenta y, para la elegida, movimientos (con conciliación en bancos),
// cierre de caja y transferencias. Usa los controladores de contabilidad de siempre.
var CB = { cuentas: [], cuenta: null, pestana: 'movimientos', rango: 'mes', esperado: 0 };
var CB_URL = {
  saldos: '../controller/contabilidad/controlador_saldos.php', movimientos: '../controller/contabilidad/controlador_movimientos.php',
  conciliar: '../controller/contabilidad/controlador_conciliar.php', resumen: '../controller/contabilidad/controlador_caja_resumen.php',
  cerrar: '../controller/contabilidad/controlador_caja_cerrar.php', transferencias: '../controller/contabilidad/controlador_transferencias_listar.php',
  transferir: '../controller/contabilidad/controlador_transferencia_registrar.php', anular: '../controller/contabilidad/controlador_transferencia_anular.php'
};
var CB_ICONOS = { CAJA: 'fa-cash-register', BANCO: 'fa-university', BILLETERA: 'fa-mobile-alt' };

function CB_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function CB_Iso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
function CB_Hoy() { return CB_Iso(new Date()); }

function Cuentas_Iniciar() {
  $(document).off('.cb');
  $(document).on('click.cb', '.cb-cuenta', function () { CB.cuenta = CB.cuentas.filter(function (c) { return +c.id_cuenta === +$(this).data('id'); }, this)[0]; CB.pestana = 'movimientos'; CB_Pintar(); });
  $(document).on('click.cb', '.cb-pestana', function (e) { e.preventDefault(); CB.pestana = $(this).data('p'); CB_Detalle(); });
  $(document).on('click.cb', '.cb-rango', function () { CB.rango = String($(this).data('rango')); CB_Movimientos(); });
  $(document).on('change.cb', '#cb_desde, #cb_hasta', function () { CB.rango = 'otro'; CB_Movimientos(); });
  $(document).on('change.cb', '.cb-conciliar', function () {
    var chk = this;
    $.post(CB_URL.conciliar, { id: $(chk).data('id'), valor: chk.checked ? 1 : 0 }, null, 'json').done(function (r) { if (!r.ok) { chk.checked = !chk.checked; } });
  });
  $(document).on('change.cb', '#cb_fecha_cierre', CB_Cierre);
  $(document).on('input.cb', '#cb_contado', CB_Diferencia);
  $(document).on('click.cb', '#cb_cerrar', CB_Cerrar);
  $(document).on('click.cb', '#cb_transferir', CB_Transferir);
  $(document).on('click.cb', '.cb-anular-tr', function () { CB_AnularTransferencia($(this).data('id')); });
  CB_Cargar();
}

function CB_Cargar(seguir) {
  $.post(CB_URL.saldos, null, null, 'json').done(function (r) {
    CB.cuentas = r.data || [];
    if (!CB.cuenta || !CB.cuentas.some(function (c) { return +c.id_cuenta === +CB.cuenta.id_cuenta; })) { CB.cuenta = CB.cuentas[0] || null; }
    else { CB.cuenta = CB.cuentas.filter(function (c) { return +c.id_cuenta === +CB.cuenta.id_cuenta; })[0]; }
    CB_Pintar();
    if (seguir) { seguir(); }
  });
}

function CB_Pintar() {
  var total = CB.cuentas.reduce(function (s, c) { return s + (+c.saldo); }, 0);
  $('#cb_cuentas').html(CB.cuentas.length ? '<div class="cb-total"><span>Dinero total del colegio</span><b>' + formatoMoneda(total) + '</b></div>' + CB.cuentas.map(function (c) {
    var hoy = +c.entradas_hoy || +c.salidas_hoy;
    return '<button type="button" class="cb-cuenta cb-' + c.tipo.toLowerCase() + (CB.cuenta && +CB.cuenta.id_cuenta === +c.id_cuenta ? ' activa' : '') + '" data-id="' + c.id_cuenta + '">' +
      '<span class="cb-icono"><i class="fas ' + (CB_ICONOS[c.tipo] || 'fa-wallet') + '"></i></span><small>' + textoSeguro(CB_Titulo(c.tipo)) + (c.banco ? ' · ' + textoSeguro(c.banco) : '') + '</small>' +
      '<b>' + textoSeguro(CB_Titulo(c.nombre)) + '</b><strong>' + formatoMoneda(c.saldo) + '</strong>' +
      '<span class="cb-hoy">' + (hoy ? 'Hoy: <i class="pe-nota-ok">+' + formatoMoneda(c.entradas_hoy) + '</i> <i class="pe-nota-mal">−' + formatoMoneda(c.salidas_hoy) + '</i>' : 'Sin movimientos hoy') + '</span>' +
      (c.tipo !== 'CAJA' && +c.sin_conciliar ? '<span class="av-chip av-chip-pend">' + c.sin_conciliar + ' sin conciliar</span>' : '') +
      (c.tipo === 'CAJA' ? '<span class="cb-cierre">' + (c.ultimo_cierre ? 'Último cierre: ' + textoSeguro(c.ultimo_cierre) : 'Sin cierres') + '</span>' : '') + '</button>';
  }).join('') : '<div class="av-vacio"><i class="fas fa-university"></i><p>No hay cajas ni cuentas. Créelas en Formas de pago y cuentas.</p></div>');
  CB_Detalle();
}

function CB_Detalle() {
  var c = CB.cuenta;
  if (!c) { $('#cb_detalle').empty(); return; }
  var pestanas = [['movimientos', 'Movimientos', 'fa-list']].concat(c.tipo === 'CAJA' ? [['cierre', 'Cierre de caja', 'fa-lock']] : []).concat([['transferencias', 'Transferencias', 'fa-exchange-alt']]);
  if (!pestanas.some(function (p) { return p[0] === CB.pestana; })) { CB.pestana = 'movimientos'; }
  $('#cb_detalle').html('<ul class="nav av-pestanas">' + pestanas.map(function (p) {
    return '<li class="nav-item"><a class="nav-link cb-pestana' + (CB.pestana === p[0] ? ' active' : '') + '" href="#" data-p="' + p[0] + '"><i class="fas ' + p[2] + ' mr-1"></i>' + p[1] + '</a></li>';
  }).join('') + '</ul><div id="cb_panel"></div>');
  ({ movimientos: CB_Movimientos, cierre: CB_Cierre, transferencias: CB_Transferencias })[CB.pestana]();
}

function CB_Rango() {
  var hoy = new Date();
  if (CB.rango === 'otro') { return [$('#cb_desde').val(), $('#cb_hasta').val()]; }
  if (CB.rango === 'hoy') { return [CB_Hoy(), CB_Hoy()]; }
  if (CB.rango === 'semana') { var d = new Date(); d.setDate(d.getDate() - 6); return [CB_Iso(d), CB_Hoy()]; }
  if (CB.rango === 'anio') { return [CB_Iso(new Date(hoy.getFullYear(), 0, 1)), CB_Hoy()]; }
  return [CB_Iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), CB_Hoy()];
}

function CB_Movimientos() {
  var r = CB_Rango(), banco = CB.cuenta.tipo !== 'CAJA';
  $.post(CB_URL.movimientos, { cuenta: CB.cuenta.id_cuenta, desde: r[0], hasta: r[1] }, null, 'json').done(function (d) {
    var rangos = [['hoy', 'Hoy'], ['semana', '7 días'], ['mes', 'Este mes'], ['anio', 'Este año']];
    var cab = '<section class="pe-resumen"><div class="pe-dato"><span>Saldo anterior</span><b>' + formatoMoneda(d.saldo_anterior) + '</b></div>' +
      '<div class="pe-dato"><span>Entradas</span><b class="pe-nota-ok">' + formatoMoneda(d.entradas) + '</b></div>' +
      '<div class="pe-dato"><span>Salidas</span><b class="pe-nota-mal">' + formatoMoneda(d.salidas) + '</b></div>' +
      '<div class="pe-dato pe-dato-grande"><span>Saldo final</span><b>' + formatoMoneda(d.saldo_final) + '</b></div></section>' +
      '<div class="pe-barra"><div class="pe-filtros">' + rangos.map(function (x) {
        return '<button type="button" class="pe-filtro cb-rango' + (CB.rango === x[0] ? ' activo' : '') + '" data-rango="' + x[0] + '">' + x[1] + '</button>';
      }).join('') + '<span class="sa-fechas"><input type="date" class="form-control form-control-sm" id="cb_desde" value="' + r[0] + '" aria-label="Desde"> a ' +
      '<input type="date" class="form-control form-control-sm" id="cb_hasta" value="' + r[1] + '" aria-label="Hasta"></span></div>' +
      (banco ? '<span class="small text-muted">Marque cada movimiento cuando aparezca en el extracto del banco.</span>' : '') + '</div>';
    if (!d.data.length) { $('#cb_panel').html(cab + '<div class="av-vacio"><i class="fas fa-list"></i><p>Sin movimientos en este periodo.</p></div>'); return; }
    var grupos = {}, orden = [];
    // Se agrupa por día (fecha_txt trae también la hora)
    d.data.forEach(function (m) { var f = String(m.fecha_txt).slice(0, 10); if (!grupos[f]) { grupos[f] = []; orden.push(f); } grupos[f].push(m); });
    $('#cb_panel').html(cab + orden.map(function (f) {
      var p = f.split('-'), dia = p.length === 3 && p[2].length === 4 ? new Date(+p[2], +p[1] - 1, +p[0]).toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : f;
      return '<h3 class="pe-grupo">' + textoSeguro(dia.charAt(0).toUpperCase() + dia.slice(1)) + '</h3><div class="mv-movs">' + grupos[f].map(function (m) {
        var entrada = m.tipo === 'ENTRADA', anulado = m.estado !== 'VALIDO';
        return '<article class="mv-mov' + (anulado ? ' anulado' : '') + '"><span class="mv-icono ' + (entrada ? 'ingreso' : 'gasto') + '"><i class="fas ' + (entrada ? 'fa-arrow-down' : 'fa-arrow-up') + '"></i></span>' +
          '<div class="mv-cuerpo"><b>' + textoSeguro(CB_Titulo(m.descripcion)) + '</b><small>' + textoSeguro(CB_Titulo(m.origen)) + (m.referencia ? ' · Op. ' + textoSeguro(m.referencia) : '') +
          (m.usu_usuario ? ' · ' + textoSeguro(m.usu_usuario) : '') + (String(m.fecha_txt).length > 10 ? ' · ' + textoSeguro(String(m.fecha_txt).slice(11, 16)) : '') + '</small></div>' +
          '<div class="mv-monto ' + (entrada ? 'pe-nota-ok' : 'pe-nota-mal') + '">' + (entrada ? '+ ' : '− ') + formatoMoneda(m.monto) + '<small class="text-muted font-weight-normal">Saldo ' + formatoMoneda(m.saldo) + '</small>' +
          (banco && !anulado ? '<label class="cb-concil"><input type="checkbox" class="cb-conciliar" data-id="' + m.id_movimiento + '"' + (+m.conciliado ? ' checked' : '') + '> Conciliado</label>' : '') + '</div></article>';
      }).join('') + '</div>';
    }).join(''));
  });
}

function CB_Cierre() {
  var fecha = $('#cb_fecha_cierre').val() || CB_Hoy();
  $.post(CB_URL.resumen, { cuenta: CB.cuenta.id_cuenta, fecha: fecha }, null, 'json').done(function (d) {
    var r = d.data;
    CB.esperado = +r.saldo_esperado;
    var cerrado = +r.cerrado > 0;
    var html = '<div class="cb-cierre-caja"><div><label for="cb_fecha_cierre" class="small font-weight-bold">Día</label><input type="date" class="form-control mb-3" id="cb_fecha_cierre" value="' + fecha + '" max="' + CB_Hoy() + '">' +
      '<ul class="cb-cuentas-cierre"><li><span>Saldo al empezar el día</span><b>' + formatoMoneda(r.saldo_inicial) + '</b></li><li><span>Entradas</span><b class="pe-nota-ok">+ ' + formatoMoneda(r.entradas) + '</b></li>' +
      '<li><span>Salidas</span><b class="pe-nota-mal">− ' + formatoMoneda(r.salidas) + '</b></li><li class="cb-esperado"><span>Debe haber en caja</span><b>' + formatoMoneda(r.saldo_esperado) + '</b></li></ul></div>' +
      '<div>' + (cerrado ? '<div class="av-vacio"><i class="fas fa-lock"></i><p>La caja de este día ya está cerrada.</p></div>' :
        '<label for="cb_contado" class="small font-weight-bold">Efectivo contado</label><input type="text" inputmode="decimal" class="form-control form-control-lg" id="cb_contado" placeholder="0.00">' +
        '<p id="cb_diferencia" class="cb-diferencia"></p>' +
        '<label class="small d-flex align-items-center mb-2" style="gap:6px"><input type="checkbox" id="cb_ajustar"> Registrar la diferencia como ajuste (sobrante o faltante)</label>' +
        '<input type="text" class="form-control mb-3" id="cb_obs_cierre" maxlength="255" placeholder="Observación (opcional)">' +
        '<button type="button" class="btn btn-success btn-block" id="cb_cerrar"><i class="fas fa-lock mr-1"></i>Cerrar la caja del día</button>') + '</div></div>';
    html += '<h3 class="pe-seccion">Cierres anteriores</h3>' + (d.cierres.length ? '<div class="mv-movs">' + d.cierres.map(function (x) {
      var dif = +x.diferencia;
      return '<article class="mv-mov"><span class="mv-icono ' + (dif === 0 ? 'ingreso' : 'gasto') + '"><i class="fas ' + (dif === 0 ? 'fa-check' : 'fa-exclamation') + '"></i></span><div class="mv-cuerpo"><b>' + textoSeguro(x.fecha_txt) + '</b>' +
        '<small>Esperado ' + formatoMoneda(x.saldo_esperado) + ' · contado ' + formatoMoneda(x.saldo_contado) + (x.usu_usuario ? ' · ' + textoSeguro(x.usu_usuario) : '') + '</small>' +
        (x.observacion ? '<p>' + textoSeguro(x.observacion) + '</p>' : '') + '</div><div class="mv-monto ' + (dif === 0 ? 'pe-nota-ok' : dif > 0 ? 'pe-nota-ok' : 'pe-nota-mal') + '">' +
        (dif === 0 ? 'Cuadró' : (dif > 0 ? 'Sobró ' : 'Faltó ') + formatoMoneda(Math.abs(dif))) + (+x.ajustado ? '<small class="text-muted font-weight-normal">ajustado</small>' : '') + '</div></article>';
    }).join('') + '</div>' : '<p class="text-muted">Aún no hay cierres de esta caja.</p>');
    $('#cb_panel').html(html);
  });
}

function CB_Diferencia() {
  var v = parseFloat(String($('#cb_contado').val()).replace(',', '.'));
  if (isNaN(v)) { $('#cb_diferencia').html(''); return; }
  var dif = Math.round((v - CB.esperado) * 100) / 100;
  $('#cb_diferencia').html(dif === 0 ? '<b class="pe-nota-ok"><i class="fas fa-check mr-1"></i>Cuadra con el sistema</b>'
    : '<b class="' + (dif > 0 ? 'pe-nota-ok">Sobran ' : 'pe-nota-mal">Faltan ') + formatoMoneda(Math.abs(dif)) + '</b>');
}

function CB_Cerrar() {
  var contado = $.trim($('#cb_contado').val()).replace(',', '.');
  if (!/^\d+(\.\d{1,2})?$/.test(contado)) { return Swal.fire('Falta el monto', 'Escriba el efectivo contado.', 'warning'); }
  $.post(CB_URL.cerrar, { cuenta: CB.cuenta.id_cuenta, fecha: $('#cb_fecha_cierre').val(), contado: contado, observacion: $('#cb_obs_cierre').val(), ajustar: $('#cb_ajustar').is(':checked') ? 1 : 0 }, null, 'json').done(function (r) {
    if (+r.codigo === 1) { Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Caja cerrada' }); CB_Cargar(); }
    else { Swal.fire('No se cerró la caja', textoSeguro(r.mensaje || ''), 'warning'); }
  });
}

function CB_Transferencias() {
  var hoy = new Date(), desde = CB_Iso(new Date(hoy.getFullYear(), 0, 1));
  $.post(CB_URL.transferencias, { desde: desde, hasta: CB_Hoy() }, null, 'json').done(function (d) {
    var lista = (d.data || []).filter(function (t) { return [t.origen, t.destino].indexOf(CB.cuenta.nombre) !== -1; });
    $('#cb_panel').html('<p class="small text-muted">Depósitos, retiros y movimientos entre cuentas de este año en los que participa ' + textoSeguro(CB_Titulo(CB.cuenta.nombre)) + '.</p>' +
      (lista.length ? '<div class="mv-movs">' + lista.map(function (t) {
        var valido = t.estado === 'VALIDO';
        return '<article class="mv-mov' + (valido ? '' : ' anulado') + '"><span class="mv-icono cobro"><i class="fas fa-exchange-alt"></i></span><div class="mv-cuerpo"><b>' + textoSeguro(CB_Titulo(t.origen)) + ' → ' + textoSeguro(CB_Titulo(t.destino)) + '</b>' +
          '<small>' + textoSeguro(t.fecha_txt) + ' · ' + textoSeguro(CB_Titulo(t.tipo)) + (t.referencia ? ' · Op. ' + textoSeguro(t.referencia) : '') + (t.usu_usuario ? ' · ' + textoSeguro(t.usu_usuario) : '') + '</small>' +
          (t.observacion ? '<p>' + textoSeguro(t.observacion) + '</p>' : '') + (valido ? '' : '<p class="text-danger small mb-0">Anulada: ' + textoSeguro(t.motivo_anulacion || '') + '</p>') + '</div>' +
          '<div class="mv-monto">' + formatoMoneda(t.monto) + (valido ? '<span><button type="button" class="btn btn-outline-danger btn-sm cb-anular-tr" data-id="' + t.id_transferencia + '" title="Anular"><i class="fas fa-ban"></i></button></span>' : '') + '</div></article>';
      }).join('') + '</div>' : '<div class="av-vacio"><i class="fas fa-exchange-alt"></i><p>Sin transferencias este año.</p></div>'));
  });
}

// Depositar (caja → banco), retirar (banco → caja) o mover entre dos cuentas cualesquiera
function CB_Transferir() {
  if (CB.cuentas.length < 2) { return Swal.fire('Se necesitan dos cuentas', 'Cree otra caja o cuenta en Formas de pago y cuentas.', 'info'); }
  var op = function (sel) { return CB.cuentas.map(function (c) { return '<option value="' + c.id_cuenta + '"' + (+c.id_cuenta === +sel ? ' selected' : '') + '>' + textoSeguro(CB_Titulo(c.nombre)) + ' (' + formatoMoneda(c.saldo) + ')</option>'; }).join(''); };
  var caja = CB.cuentas.filter(function (c) { return c.tipo === 'CAJA'; })[0] || CB.cuentas[0], otra = CB.cuentas.filter(function (c) { return +c.id_cuenta !== +caja.id_cuenta; })[0];
  var html = '<div class="text-left"><div class="form-group"><label for="cb_t_tipo">Qué va a hacer</label><select class="form-control" id="cb_t_tipo">' +
    '<option value="DEPOSITO">Depositar efectivo de la caja al banco</option><option value="RETIRO">Retirar del banco a la caja</option><option value="TRANSFERENCIA">Mover entre dos cuentas</option></select></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="cb_t_origen">Sale de</label><select class="form-control" id="cb_t_origen">' + op(caja.id_cuenta) + '</select></div>' +
    '<div class="form-group col-6"><label for="cb_t_destino">Entra a</label><select class="form-control" id="cb_t_destino">' + op(otra.id_cuenta) + '</select></div></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="cb_t_monto">Monto</label><input type="text" inputmode="decimal" class="form-control" id="cb_t_monto"></div>' +
    '<div class="form-group col-6"><label for="cb_t_fecha">Fecha</label><input type="date" class="form-control" id="cb_t_fecha" value="' + CB_Hoy() + '" max="' + CB_Hoy() + '"></div></div>' +
    '<div class="form-group"><label for="cb_t_ref">N.° de operación</label><input class="form-control" id="cb_t_ref" maxlength="60"></div>' +
    '<div class="form-group mb-0"><label for="cb_t_obs">Observación</label><input class="form-control" id="cb_t_obs" maxlength="255"></div></div>';
  Swal.fire({
    title: 'Depositar o mover dinero', html: html, showCancelButton: true, focusConfirm: false, confirmButtonText: '<i class="fas fa-check mr-1"></i>Registrar', cancelButtonText: 'Cancelar',
    didOpen: function () {
      $('#cb_t_tipo').on('change', function () {
        var t = $(this).val(), cajaId = caja.id_cuenta, banco = CB.cuentas.filter(function (c) { return c.tipo !== 'CAJA'; })[0];
        if (t === 'DEPOSITO' && banco) { $('#cb_t_origen').val(cajaId); $('#cb_t_destino').val(banco.id_cuenta); }
        if (t === 'RETIRO' && banco) { $('#cb_t_origen').val(banco.id_cuenta); $('#cb_t_destino').val(cajaId); }
      });
    },
    preConfirm: function () {
      var monto = $.trim($('#cb_t_monto').val()).replace(',', '.');
      if (!/^\d+(\.\d{1,2})?$/.test(monto) || +monto <= 0) { Swal.showValidationMessage('Escriba un monto mayor a 0'); return false; }
      if ($('#cb_t_origen').val() === $('#cb_t_destino').val()) { Swal.showValidationMessage('Elija dos cuentas distintas'); return false; }
      return { tipo: $('#cb_t_tipo').val(), origen: $('#cb_t_origen').val(), destino: $('#cb_t_destino').val(), monto: monto, fecha: $('#cb_t_fecha').val(),
               referencia: $.trim($('#cb_t_ref').val()), observacion: $.trim($('#cb_t_obs').val()) };
    }
  }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.post(CB_URL.transferir, r.value, null, 'json').done(function (resp) {
      if (+resp.codigo === 1) { Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Registrado' }); CB_Cargar(); }
      else { Swal.fire('No se registró', textoSeguro(resp.mensaje || ''), 'warning'); }
    });
  });
}

function CB_AnularTransferencia(id) {
  Swal.fire({ title: '¿Anular este movimiento entre cuentas?', text: 'Se anulan la salida y la entrada. Escriba el motivo:', icon: 'warning', input: 'text', showCancelButton: true,
              confirmButtonText: 'Anular', confirmButtonColor: '#d33', cancelButtonText: 'Cancelar', inputValidator: function (v) { if (!v || !v.trim()) { return 'El motivo es obligatorio'; } } }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.post(CB_URL.anular, { id: id, motivo: r.value.trim() }, null, 'json').done(function (resp) {
      if (+resp.codigo === 1) { CB_Cargar(); } else { Swal.fire('No se anuló', textoSeguro(resp.mensaje || ''), 'warning'); }
    });
  });
}
