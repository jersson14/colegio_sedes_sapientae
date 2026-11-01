// Ingresos y Gastos: movimientos del periodo agrupados por día, resumen, filtro por categoría y formulario.
// Cada vista pasa sus rutas (listar, registrar, anular...): así cada pantalla solo usa sus controladores.
var MV = { tipo: null, rutas: null, lista: [], resumen: null, rango: 'mes', grupo: '', texto: '', anulados: false, categorias: null, formas: null };

function MV_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function MV_Iso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
// "28-02-2026" o "2026-02-28" -> "2026-02-28"
function MV_Fecha(f) { f = String(f || '').slice(0, 10); return /^\d{2}-\d{2}-\d{4}$/.test(f) ? f.split('-').reverse().join('-') : f; }

function Movimientos_Iniciar(tipo, rutas) {
  MV.tipo = tipo;
  MV.rutas = rutas;
  MV.rango = 'mes';
  MV.grupo = '';
  MV.texto = '';
  $(document).off('.mv');
  $(document).on('click.mv', '#mv_nuevo', function () { MV_Formulario(null); });
  $(document).on('click.mv', '.mv-rango', function () { MV.rango = String($(this).data('rango')); MV_Cargar(); });
  $(document).on('change.mv', '#mv_desde, #mv_hasta', function () { MV.rango = 'otro'; MV_Cargar(); });
  $(document).on('click.mv', '.mv-grupo', function () { MV.grupo = String($(this).data('grupo')); MV_Lista(); $('.mv-grupo').removeClass('activo').filter(this).addClass('activo'); });
  $(document).on('input.mv', '#mv_buscar', function () { MV.texto = $(this).val(); MV_Lista(); });
  $(document).on('change.mv', '#mv_anulados', function () { MV.anulados = this.checked; MV_Lista(); });
  $(document).on('click.mv', '.mv-editar', function () { MV_Formulario(MV.lista[+$(this).data('i')]); });
  $(document).on('click.mv', '.mv-anular', function () { MV_Anular(MV.lista[+$(this).data('i')]); });
  $.post(rutas.formas, null, null, 'json').done(function (f) { MV.formas = f; });
  $.post(rutas.categorias, null, null, 'json').done(function (c) { MV.categorias = c; });
  MV_Cargar();
}

function MV_Rango() {
  var hoy = new Date(), desde, hasta = hoy;
  if (MV.rango === 'otro') { return [$('#mv_desde').val(), $('#mv_hasta').val()]; }
  if (MV.rango === 'mes') { desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1); }
  else if (MV.rango === 'anterior') { desde = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1); hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0); }
  else if (MV.rango === 'anio') { desde = new Date(hoy.getFullYear(), 0, 1); }
  else { desde = new Date(hoy); desde.setDate(hoy.getDate() - 6); }
  return [MV_Iso(desde), MV_Iso(hasta)];
}

function MV_Cargar() {
  var r = MV_Rango(), ingreso = MV.tipo === 'INGRESO';
  var pedidos = [$.post(MV.rutas.listar, { indi: 0, fechaini: r[0], fechafin: r[1] }, null, 'json'),
                 $.post(MV.rutas.diferencia, { fechaini: r[0], fechafin: r[1] }, null, 'json')];
  if (ingreso) { pedidos.push($.post(MV.rutas.cobros, { fechaini: r[0], fechafin: r[1] }, null, 'json')); }
  $.when.apply($, pedidos).done(function (a, b, c) {
    var propios = (a[0].data || []).map(function (x) {
      return { origen: 'manual', id: x.id_ingreso || x.id_egresos, fecha: MV_Fecha(x.created_at || x.fecha_formateada), monto: +x.monto, cantidad: +x.cantidad || 1,
               categoria: x.nombre, grupo: x.grupo || '', indicador: x.id_indicador, obs: x.observacion || '', metodo: x.metodo || '', cuenta: x.cuenta || '',
               metodo_id: x.metodo_pago_id, cuenta_id: x.cuenta_id, referencia: x.referencia || '', estado: x.estado, motivo: x.motivo_anulacion || '',
               beneficiario: x.beneficiario || '', comprobante: x.nro_comprobante || '', adjunto: x.adjunto || '' };
    });
    var cobros = ingreso ? (c[0].data || []).map(function (x) {
      return { origen: 'cobro', id: x.id_ingreso, fecha: MV_Fecha(x.created_at || x.fecha_formateada), monto: +x.monto, cantidad: 1, categoria: x.PAGADO === 'PENSION' && x.mes ? 'Pensión ' + x.mes : (x.PAGADO || x.observacion),
               grupo: 'COBROS A ESTUDIANTES', estudiante: x.Estudiante || '', obs: '', estado: x.estado, motivo: x.motivo_anulacion || '' };
    }) : [];
    MV.lista = propios.concat(cobros).sort(function (x, y) { return x.fecha < y.fecha ? 1 : x.fecha > y.fecha ? -1 : (+y.id) - (+x.id); });
    MV.lista.forEach(function (x, i) { x._i = i; });
    MV.resumen = (b[0].data || [])[0] || {};
    MV_Pintar(r);
  });
}

function MV_Pintar(r) {
  var ingreso = MV.tipo === 'INGRESO', validos = MV.lista.filter(function (x) { return x.estado !== 'ANULADO'; });
  var total = validos.reduce(function (s, x) { return s + x.monto; }, 0);
  var cobros = validos.filter(function (x) { return x.origen === 'cobro'; }).reduce(function (s, x) { return s + x.monto; }, 0);
  var dif = +MV.resumen.Diferencia || 0;
  var resumen = '<section class="pe-resumen">' +
    '<div class="pe-dato pe-dato-grande"><span>' + (ingreso ? 'Ingresos del periodo' : 'Gastos del periodo') + '</span><b class="' + (ingreso ? 'pe-nota-ok' : 'pe-nota-mal') + '">' + formatoMoneda(total) + '</b><small>' +
      validos.length + (validos.length === 1 ? ' movimiento' : ' movimientos') + '</small></div>' +
    (ingreso ? '<div class="pe-dato"><span>Cobros a estudiantes</span><b>' + formatoMoneda(cobros) + '</b><small>desde Cobrar pensión</small></div>' +
               '<div class="pe-dato"><span>Otros ingresos</span><b>' + formatoMoneda(total - cobros) + '</b></div>'
             : '<div class="pe-dato"><span>Ingresos del periodo</span><b>' + formatoMoneda(+MV.resumen.totalingresos || 0) + '</b></div>') +
    '<div class="pe-dato"><span>Ingresos − gastos</span><b class="' + (dif >= 0 ? 'pe-nota-ok' : 'pe-nota-mal') + '">' + formatoMoneda(dif) + '</b><small>en el mismo periodo</small></div></section>';
  var rangos = [['semana', '7 días'], ['mes', 'Este mes'], ['anterior', 'Mes anterior'], ['anio', 'Este año']];
  var grupos = {};
  MV.lista.forEach(function (x) { if (x.grupo) { grupos[x.grupo] = (grupos[x.grupo] || 0) + (x.estado !== 'ANULADO' ? x.monto : 0); } });
  var barra = '<div class="pe-barra"><div class="pe-filtros">' + rangos.map(function (x) {
    return '<button type="button" class="pe-filtro mv-rango' + (MV.rango === x[0] ? ' activo' : '') + '" data-rango="' + x[0] + '">' + x[1] + '</button>';
  }).join('') + '<span class="sa-fechas"><input type="date" class="form-control form-control-sm" id="mv_desde" value="' + r[0] + '" aria-label="Desde"> a ' +
    '<input type="date" class="form-control form-control-sm" id="mv_hasta" value="' + r[1] + '" aria-label="Hasta"></span></div>' +
    '<input type="search" class="form-control sa-buscar" id="mv_buscar" placeholder="Buscar concepto, estudiante u observación…" value="' + textoSeguro(MV.texto) + '"></div>' +
    '<div class="mv-grupos"><button type="button" class="pe-filtro mv-grupo' + (MV.grupo === '' ? ' activo' : '') + '" data-grupo="">Todas las categorías</button>' +
    Object.keys(grupos).sort().map(function (g) {
      return '<button type="button" class="pe-filtro mv-grupo' + (MV.grupo === g ? ' activo' : '') + '" data-grupo="' + textoSeguro(g) + '">' + textoSeguro(MV_Titulo(g)) + '<b>' + formatoMoneda(grupos[g]) + '</b></button>';
    }).join('') + '<label class="mv-anulados"><input type="checkbox" id="mv_anulados"' + (MV.anulados ? ' checked' : '') + '> Ver anulados</label></div>';
  $('#mv_contenido').html(resumen + barra + '<div id="mv_lista"></div>');
  MV_Lista();
}

function MV_Lista() {
  var ingreso = MV.tipo === 'INGRESO', q = $.trim(MV.texto).toLowerCase();
  var lista = MV.lista.filter(function (x) {
    return (MV.anulados || x.estado !== 'ANULADO') && (!MV.grupo || x.grupo === MV.grupo) &&
      (!q || (x.categoria + ' ' + (x.estudiante || '') + ' ' + x.obs + ' ' + (x.beneficiario || '') + ' ' + (x.comprobante || '')).toLowerCase().indexOf(q) !== -1);
  });
  if (!lista.length) { $('#mv_lista').html('<div class="av-vacio"><i class="fas ' + (ingreso ? 'fa-hand-holding-usd' : 'fa-file-invoice-dollar') + '"></i><p>No hay movimientos en este periodo.</p></div>'); return; }
  var grupos = {}, orden = [];
  lista.forEach(function (x) { if (!grupos[x.fecha]) { grupos[x.fecha] = []; orden.push(x.fecha); } grupos[x.fecha].push(x); });
  $('#mv_lista').html(orden.map(function (f) {
    var suma = grupos[f].filter(function (x) { return x.estado !== 'ANULADO'; }).reduce(function (s, x) { return s + x.monto; }, 0);
    var titulo = new Date(f + 'T00:00:00').toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    return '<h3 class="pe-grupo mv-dia">' + titulo.charAt(0).toUpperCase() + titulo.slice(1) + ' <span>' + formatoMoneda(suma) + '</span></h3><div class="mv-movs">' + grupos[f].map(function (x) {
      var anulado = x.estado === 'ANULADO';
      var detalle = x.origen === 'cobro' ? textoSeguro(MV_Titulo(x.estudiante)) :
        [x.cantidad > 1 ? x.cantidad + ' unidades' : '', x.beneficiario ? textoSeguro(MV_Titulo(x.beneficiario)) : '', x.comprobante ? 'Comp. ' + textoSeguro(x.comprobante) : '',
         x.metodo ? textoSeguro(MV_Titulo(x.metodo)) + (x.cuenta ? ' · ' + textoSeguro(MV_Titulo(x.cuenta)) : '') : '', x.referencia ? 'Op. ' + textoSeguro(x.referencia) : ''].filter(Boolean).join(' · ');
      var acciones = x.origen === 'cobro' || anulado ? '' :
        (x.adjunto && MV.rutas.comprobante ? '<a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="' + MV.rutas.comprobante + '?id=' + encodeURIComponent(x.id) + '"><i class="fas fa-paperclip"></i></a>' : '') +
        '<button type="button" class="btn btn-outline-secondary btn-sm mv-editar" data-i="' + x._i + '" title="Editar"><i class="fas fa-edit"></i></button>' +
        '<button type="button" class="btn btn-outline-danger btn-sm mv-anular" data-i="' + x._i + '" title="Anular"><i class="fas fa-ban"></i></button>';
      return '<article class="mv-mov' + (anulado ? ' anulado' : '') + '"><span class="mv-icono ' + (x.origen === 'cobro' ? 'cobro' : ingreso ? 'ingreso' : 'gasto') + '"><i class="fas ' +
        (x.origen === 'cobro' ? 'fa-user-graduate' : ingreso ? 'fa-arrow-down' : 'fa-arrow-up') + '"></i></span><div class="mv-cuerpo">' +
        '<b>' + textoSeguro(MV_Titulo(x.categoria)) + '</b><small>' + (x.grupo ? textoSeguro(MV_Titulo(x.grupo)) + (detalle ? ' · ' : '') : '') + detalle + '</small>' +
        (x.obs ? '<p>' + textoSeguro(x.obs) + '</p>' : '') + (anulado ? '<p class="text-danger small mb-0">Anulado' + (x.motivo ? ': ' + textoSeguro(x.motivo) : '') + '</p>' : '') + '</div>' +
        '<div class="mv-monto ' + (ingreso ? 'pe-nota-ok' : 'pe-nota-mal') + '">' + (ingreso ? '+ ' : '− ') + formatoMoneda(x.monto) + '<span>' + acciones + '</span></div></article>';
    }).join('') + '</div>';
  }).join(''));
}

// Formulario de ingreso o gasto: categoría, monto, cantidad, fecha, forma de pago, cuenta, referencia y observación
function MV_Formulario(x) {
  var ingreso = MV.tipo === 'INGRESO';
  if (!MV.categorias || !MV.formas) { return; }
  if (!MV.categorias.length) { return Swal.fire('Faltan categorías', 'Primero cree las categorías de ' + (ingreso ? 'ingresos' : 'gastos') + ' en Ingresos y gastos → Categorías.', 'info'); }
  var op = function (lista, valor, texto, elegido) { return lista.map(function (o) { return '<option value="' + o[valor] + '"' + (+o[valor] === +elegido ? ' selected' : '') + '>' + textoSeguro(MV_Titulo(texto(o))) + '</option>'; }).join(''); };
  var html = '<div class="text-left"><div class="form-group"><label for="mv_f_cat">Categoría <b class="text-danger">(*)</b></label><select class="form-control" id="mv_f_cat">' +
    op(MV.categorias, 'id_indicadores', function (o) { return o.nombre; }, x ? x.indicador : '') + '</select></div>' +
    '<div class="form-row"><div class="form-group col-7"><label for="mv_f_monto">Monto total <b class="text-danger">(*)</b></label><input type="text" inputmode="decimal" class="form-control" id="mv_f_monto" value="' + (x ? x.monto.toFixed(2) : '') + '"></div>' +
    '<div class="form-group col-5"><label for="mv_f_cant">Cantidad</label><input type="number" min="1" class="form-control" id="mv_f_cant" value="' + (x ? x.cantidad : 1) + '"></div></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="mv_f_fecha">Fecha</label><input type="date" class="form-control" id="mv_f_fecha" value="' + (x ? x.fecha : MV_Iso(new Date())) + '" max="' + MV_Iso(new Date()) + '"></div>' +
    '<div class="form-group col-6"><label for="mv_f_metodo">Forma de pago <b class="text-danger">(*)</b></label><select class="form-control" id="mv_f_metodo">' +
    op(MV.formas.metodos.filter(function (m) { return +m.requiere_cuenta || +m.id_metodo === +(x && x.metodo_id); }), 'id_metodo', function (o) { return o.nombre; }, x ? x.metodo_id : '') + '</select></div></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="mv_f_cuenta">Caja o cuenta</label><select class="form-control" id="mv_f_cuenta"><option value="">—</option>' +
    op(MV.formas.cuentas, 'id_cuenta', function (o) { return o.nombre + (o.banco ? ' · ' + o.banco : ''); }, x ? x.cuenta_id : '') + '</select></div>' +
    '<div class="form-group col-6"><label for="mv_f_ref">N.° de operación</label><input class="form-control" id="mv_f_ref" maxlength="60" value="' + textoSeguro(x ? x.referencia : '') + '"></div></div>' +
    (ingreso ? '' : '<div class="form-row"><div class="form-group col-7"><label for="mv_f_benef">Pagado a</label><input class="form-control" id="mv_f_benef" maxlength="150" value="' + textoSeguro(x ? x.beneficiario : '') + '" placeholder="Proveedor o persona"></div>' +
      '<div class="form-group col-5"><label for="mv_f_comp">N.° de comprobante</label><input class="form-control" id="mv_f_comp" maxlength="30" value="' + textoSeguro(x ? x.comprobante : '') + '"></div></div>' +
      (x ? '' : '<div class="form-group"><label for="mv_f_adj">Comprobante escaneado (opcional)</label><input type="file" class="form-control-file" id="mv_f_adj" accept="image/*,.pdf"></div>')) +
    '<div class="form-group mb-0"><label for="mv_f_obs">Observación</label><input class="form-control" id="mv_f_obs" maxlength="255" value="' + textoSeguro(x ? x.obs : '') + '"></div></div>';
  Swal.fire({
    title: x ? 'Editar ' + (ingreso ? 'ingreso' : 'gasto') : (ingreso ? 'Nuevo ingreso' : 'Nuevo gasto'), html: html, width: 620, showCancelButton: true, focusConfirm: false,
    confirmButtonText: '<i class="fas fa-save mr-1"></i>Guardar', cancelButtonText: 'Cancelar',
    didOpen: function () {
      var sugerir = function () {
        var m = MV.formas.metodos.filter(function (o) { return +o.id_metodo === +$('#mv_f_metodo').val(); })[0];
        if (!m) { return; }
        var tipo = m.tipo === 'EFECTIVO' ? 'CAJA' : (m.tipo === 'BILLETERA' ? 'BILLETERA' : 'BANCO');
        var c = MV.formas.cuentas.filter(function (o) { return o.tipo === tipo; })[0];
        if (c) { $('#mv_f_cuenta').val(c.id_cuenta); }
      };
      $('#mv_f_metodo').on('change', sugerir);
      if (!x || !x.cuenta_id) { sugerir(); }
    },
    preConfirm: function () {
      var monto = $.trim($('#mv_f_monto').val()).replace(',', '.');
      if (!/^\d+(\.\d{1,2})?$/.test(monto) || +monto <= 0) { Swal.showValidationMessage('Escriba un monto mayor a 0'); return false; }
      var m = MV.formas.metodos.filter(function (o) { return +o.id_metodo === +$('#mv_f_metodo').val(); })[0] || {};
      if (+m.requiere_referencia && !$.trim($('#mv_f_ref').val())) { Swal.showValidationMessage('Escriba el número de operación'); return false; }
      if (+m.requiere_cuenta && !$('#mv_f_cuenta').val()) { Swal.showValidationMessage('Elija la caja o cuenta'); return false; }
      var fd = new FormData();
      if (x) { fd.append('id', x.id); }
      fd.append('indi', $('#mv_f_cat').val());
      fd.append('cantidad', $('#mv_f_cant').val() || 1);
      fd.append('monto', monto);
      fd.append(x ? 'obser' : 'obse', $.trim($('#mv_f_obs').val()));
      fd.append('fecha', $('#mv_f_fecha').val());
      fd.append('metodo', $('#mv_f_metodo').val());
      fd.append('cuenta', $('#mv_f_cuenta').val());
      fd.append('referencia', $.trim($('#mv_f_ref').val()));
      if (!ingreso) {
        fd.append('beneficiario', $.trim($('#mv_f_benef').val()));
        fd.append('comprobante', $.trim($('#mv_f_comp').val()));
        if ($('#mv_f_adj').length && $('#mv_f_adj')[0].files[0]) { fd.append('adjunto', $('#mv_f_adj')[0].files[0]); }
      }
      return fd;
    }
  }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.ajax({ url: MV.rutas[x ? 'editar' : 'nuevo'], type: 'POST', data: r.value, contentType: false, processData: false, dataType: 'json' }).done(function (resp) {
      if (+resp.codigo === 1) {
        Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: x ? 'Cambios guardados' : (ingreso ? 'Ingreso registrado' : 'Gasto registrado') });
        MV_Cargar();
      } else {
        Swal.fire('No se pudo guardar', textoSeguro(resp.mensaje || 'Revise los datos.'), 'warning');
      }
    });
  });
}

function MV_Anular(x) {
  Swal.fire({ title: '¿Anular este ' + (MV.tipo === 'INGRESO' ? 'ingreso' : 'gasto') + '?', html: '<b>' + textoSeguro(MV_Titulo(x.categoria)) + '</b> · ' + formatoMoneda(x.monto) + '<br><small>Queda anulado con su motivo (no se borra) y deja de sumar en las cuentas.</small>',
              icon: 'warning', input: 'text', inputPlaceholder: 'Motivo de la anulación', showCancelButton: true, confirmButtonText: 'Anular', confirmButtonColor: '#d33', cancelButtonText: 'Cancelar',
              inputValidator: function (v) { if (!v || !v.trim()) { return 'El motivo es obligatorio'; } } }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.post(MV.rutas.anular, { id: x.id, obser: r.value.trim() }).done(function (resp) {
      if (String(resp).trim() === '1') {
        Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Anulado' });
        MV_Cargar();
      } else {
        Swal.fire('No se pudo anular', 'Intente de nuevo.', 'warning');
      }
    });
  });
}
