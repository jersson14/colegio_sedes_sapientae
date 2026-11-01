// Pensiones: una sección por nivel con sus meses como tarjetas; editar, agregar un mes, generar el cronograma y ajustar
// vencimientos fuera del año escolar. Usa los controladores de pensiones de siempre.
var PN = { anios: [], niveles: [], lista: [] };
var PN_URL = {
  anios: '../controller/pensiones/controlador_anios_escolares.php', niveles: '../controller/aulas/controlador_cargar_select_nivel.php',
  listar: '../controller/pensiones/controlador_listar_pensiones.php', nueva: '../controller/pensiones/controlador_registro_pensiones.php',
  editar: '../controller/pensiones/controlador_modificar_pension.php', eliminar: '../controller/pensiones/controlador_eliminar_pensiones.php',
  generar: '../controller/pensiones/controlador_generar_pensiones.php', ajustar: '../controller/pensiones/controlador_ajustar_vencimientos.php'
};
var PN_MESES = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
var PN_CODIGOS = { 2: 'Ya existe una pensión de ese año escolar, nivel y mes.', 3: 'La fecha de vencimiento no corresponde al calendario del año escolar.',
                   4: 'La pensión ya tiene pagos: no se puede cambiar su nivel ni su mes.' };

function PN_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function PN_Fecha(f) { return f ? new Date(f + 'T00:00:00').toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; }

function Pensiones_Iniciar() {
  $(document).off('.pn');
  $(document).on('change.pn', '#pn_anio', PN_Cargar);
  $(document).on('click.pn', '.pn-mes', function () { PN_Formulario(PN.lista[+$(this).data('i')]); });
  $(document).on('click.pn', '#pn_nueva', function () { PN_Formulario(null); });
  $(document).on('click.pn', '.pn-agregar', function () { PN_Formulario(null, $(this).data('nivel')); });
  $(document).on('click.pn', '#pn_generar, .pn-generar', function () { PN_Generar($(this).data('nivel')); });
  $(document).on('click.pn', '#pn_ajustar', PN_Ajustar);
  $.when($.post(PN_URL.anios, null, null, 'json'), $.post(PN_URL.niveles, null, null, 'json')).done(function (a, n) {
    PN.anios = a[0].data;
    PN.niveles = (typeof n[0] === 'string' ? JSON.parse(n[0]) : n[0]).map(function (x) { return { id: x[0], nombre: x[1] }; });
    var activo = PN.anios.filter(function (x) { return x.estado === 'ACTIVO'; })[0] || PN.anios[0];
    $('#pn_anio').html(PN.anios.map(function (x) { return '<option value="' + x.id + '">Año ' + textoSeguro(x.anio) + (x.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>'; }).join('')).val(activo ? activo.id : '');
    PN_Cargar();
  });
}

function PN_Cargar() {
  $.post(PN_URL.listar, { anio: $('#pn_anio').val(), nivel: 0 }, null, 'json').done(function (r) {
    PN.lista = r.data || [];
    PN.lista.forEach(function (p, i) { p._i = i; });
    PN_Pintar();
  });
}

function PN_Pintar() {
  var fuera = PN.lista.filter(function (p) { return !+p.fecha_en_anio; }).length;
  var aviso = fuera ? '<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between" style="gap:8px"><span><i class="fas fa-exclamation-triangle mr-2"></i><b>' + fuera +
    (fuera === 1 ? ' pensión vence' : ' pensiones vencen') + ' fuera del año escolar.</b> Se cobran como vencidas desde esa fecha.</span>' +
    '<button type="button" class="btn btn-dark btn-sm" id="pn_ajustar"><i class="fas fa-magic mr-1"></i>Revisar y ajustar</button></div>' : '';
  var secciones = PN.niveles.map(function (n) {
    var meses = PN.lista.filter(function (p) { return +p.id_nivel_academico === +n.id; }).sort(function (a, b) { return a.mes_num - b.mes_num; });
    var total = meses.reduce(function (s, p) { return s + (+p.precio); }, 0);
    var color = Curso_Color(n.nombre);
    return '<section class="pn-nivel" style="--av-color:' + color + '"><header><div><h2>' + textoSeguro(PN_Titulo(n.nombre)) + '</h2><p>' +
      (meses.length ? meses.length + (meses.length === 1 ? ' mes' : ' meses') + ' · ' + formatoMoneda(total) + ' en el año' : 'Sin pensiones en este año') + '</p></div>' +
      '<span><button type="button" class="btn btn-outline-secondary btn-sm pn-agregar" data-nivel="' + n.id + '"><i class="fas fa-plus mr-1"></i>Un mes</button> ' +
      (meses.length ? '' : '<button type="button" class="btn btn-primary btn-sm pn-generar" data-nivel="' + n.id + '"><i class="fas fa-calendar-alt mr-1"></i>Generar cronograma</button>') + '</span></header>' +
      (meses.length ? '<div class="cj-meses">' + meses.map(function (p) {
        var fueraAnio = !+p.fecha_en_anio;
        return '<button type="button" class="pn-mes' + (fueraAnio ? ' fuera' : '') + '" data-i="' + p._i + '" title="Editar"><b>' + textoSeguro(PN_Titulo(p.mes)) + '</b><strong>' + formatoMoneda(p.precio) + '</strong>' +
          '<small>Mora ' + formatoMoneda(p.mora) + '</small><small class="' + (fueraAnio ? 'text-danger font-weight-bold' : '') + '">' + (fueraAnio ? '<i class="fas fa-exclamation-triangle mr-1"></i>' : '') + 'Vence ' + PN_Fecha(p.fecha_vencimiento) + '</small>' +
          '<span class="av-chip ' + (+p.pagos ? 'av-chip-ok' : '') + '">' + (+p.pagos ? p.pagos + (+p.pagos === 1 ? ' pago' : ' pagos') : 'Sin pagos') + '</span></button>';
      }).join('') + '</div>' : '') + '</section>';
  }).join('');
  $('#pn_contenido').html(aviso + (secciones || '<div class="av-vacio"><i class="fas fa-layer-group"></i><p>No hay niveles registrados.</p></div>'));
}

function PN_Opciones(lista, elegido) { return lista.map(function (o) { return '<option value="' + o.v + '"' + (String(o.v) === String(elegido) ? ' selected' : '') + '>' + textoSeguro(o.t) + '</option>'; }).join(''); }

// Agregar un mes o editar uno (tarjeta): nivel, mes, vencimiento, precio y mora; eliminar si no tiene pagos
function PN_Formulario(p, nivel) {
  var conPagos = p && +p.pagos > 0;
  var html = '<div class="text-left"><div class="form-row"><div class="form-group col-6"><label for="pn_f_nivel">Nivel</label><select class="form-control" id="pn_f_nivel"' + (conPagos ? ' disabled' : '') + '>' +
    PN_Opciones(PN.niveles.map(function (n) { return { v: n.id, t: PN_Titulo(n.nombre) }; }), p ? p.id_nivel_academico : (nivel || '')) + '</select></div>' +
    '<div class="form-group col-6"><label for="pn_f_mes">Mes</label><select class="form-control" id="pn_f_mes"' + (conPagos ? ' disabled' : '') + '>' +
    PN_Opciones(PN_MESES.map(function (m) { return { v: m, t: PN_Titulo(m) }; }), p ? p.mes : 'MARZO') + '</select></div></div>' +
    '<div class="form-group"><label for="pn_f_fecha">Vence el</label><input type="date" class="form-control" id="pn_f_fecha" value="' + (p ? p.fecha_vencimiento : '') + '"></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="pn_f_precio">Precio</label><input type="text" inputmode="decimal" class="form-control" id="pn_f_precio" value="' + (p ? (+p.precio).toFixed(2) : '') + '"></div>' +
    '<div class="form-group col-6"><label for="pn_f_mora">Mora</label><input type="text" inputmode="decimal" class="form-control" id="pn_f_mora" value="' + (p ? (+p.mora).toFixed(2) : '0.00') + '"></div></div>' +
    (conPagos ? '<p class="small text-muted mb-0"><i class="fas fa-lock mr-1"></i>Tiene pagos: se puede cambiar la fecha, el precio y la mora, pero no el nivel ni el mes.</p>' : '') + '</div>';
  Swal.fire({
    title: p ? 'Pensión de ' + PN_Titulo(p.mes) : 'Agregar un mes', html: html, showCancelButton: true, showDenyButton: !!p && !conPagos, focusConfirm: false,
    confirmButtonText: '<i class="fas fa-save mr-1"></i>Guardar', denyButtonText: '<i class="fas fa-trash mr-1"></i>Eliminar', cancelButtonText: 'Cancelar',
    preConfirm: function () {
      var precio = $.trim($('#pn_f_precio').val()).replace(',', '.'), mora = $.trim($('#pn_f_mora').val() || '0').replace(',', '.');
      if (!$('#pn_f_fecha').val()) { Swal.showValidationMessage('Elija la fecha de vencimiento'); return false; }
      if (!/^\d+(\.\d{1,2})?$/.test(precio) || +precio <= 0) { Swal.showValidationMessage('Escriba un precio mayor a 0'); return false; }
      if (!/^\d+(\.\d{1,2})?$/.test(mora)) { Swal.showValidationMessage('Escriba una mora válida (0 si no tiene)'); return false; }
      var d = { anio: $('#pn_anio').val(), nivel: $('#pn_f_nivel').val(), mes: $('#pn_f_mes').val(), fecha: $('#pn_f_fecha').val(), precio: precio, mora: mora };
      if (p) { d.id = p.id_pensiones; }
      return d;
    }
  }).then(function (r) {
    if (r.isDenied) { return PN_Eliminar(p); }
    if (!r.isConfirmed) { return; }
    $.post(p ? PN_URL.editar : PN_URL.nueva, r.value).done(function (resp) {
      if (String(resp).trim() === '1') {
        Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: 'success', title: p ? 'Pensión actualizada' : 'Mes agregado' });
        PN_Cargar();
      } else {
        Swal.fire('No se pudo guardar', PN_CODIGOS[String(resp).trim()] || 'Revise los datos.', 'warning');
      }
    });
  });
}

function PN_Eliminar(p) {
  Swal.fire({ title: '¿Eliminar la pensión de ' + PN_Titulo(p.mes) + '?', text: 'No tiene pagos, así que se puede eliminar.', icon: 'warning', showCancelButton: true,
              confirmButtonText: 'Sí, eliminar', confirmButtonColor: '#d33', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.post(PN_URL.eliminar, { id: p.id_pensiones }).done(function (resp) {
      if (String(resp).trim() === '1') { Swal.fire({ toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, icon: 'success', title: 'Eliminada' }); PN_Cargar(); }
      else { Swal.fire('No se pudo eliminar', 'Puede que ya tenga pagos.', 'warning'); }
    });
  });
}

// Generar el cronograma de un nivel: un registro por mes entre "desde" y "hasta"
function PN_Generar(nivel) {
  var meses = PN_MESES.map(function (m, i) { return { v: i + 1, t: PN_Titulo(m) }; });
  var dias = [{ v: 0, t: 'Último día del mes' }].concat(Array.apply(null, Array(31)).map(function (x, i) { return { v: i + 1, t: 'Día ' + (i + 1) }; }));
  var html = '<div class="text-left"><div class="form-group"><label for="pn_g_nivel">Nivel</label><select class="form-control" id="pn_g_nivel">' +
    PN_Opciones(PN.niveles.map(function (n) { return { v: n.id, t: PN_Titulo(n.nombre) }; }), nivel || '') + '</select></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="pn_g_desde">Desde</label><select class="form-control" id="pn_g_desde">' + PN_Opciones(meses, 3) + '</select></div>' +
    '<div class="form-group col-6"><label for="pn_g_hasta">Hasta</label><select class="form-control" id="pn_g_hasta">' + PN_Opciones(meses, 12) + '</select></div></div>' +
    '<div class="form-group"><label for="pn_g_dia">Vence</label><select class="form-control" id="pn_g_dia">' + PN_Opciones(dias, 0) + '</select></div>' +
    '<div class="form-row"><div class="form-group col-6"><label for="pn_g_precio">Precio de cada mes</label><input type="text" inputmode="decimal" class="form-control" id="pn_g_precio"></div>' +
    '<div class="form-group col-6"><label for="pn_g_mora">Mora</label><input type="text" inputmode="decimal" class="form-control" id="pn_g_mora" value="0.00"></div></div>' +
    '<p class="small text-muted mb-0">Los meses que ya existen en ese nivel no se duplican.</p></div>';
  Swal.fire({
    title: 'Generar cronograma', html: html, showCancelButton: true, focusConfirm: false, confirmButtonText: '<i class="fas fa-calendar-check mr-1"></i>Generar', cancelButtonText: 'Cancelar',
    preConfirm: function () {
      var precio = $.trim($('#pn_g_precio').val()).replace(',', '.');
      if (!/^\d+(\.\d{1,2})?$/.test(precio) || +precio <= 0) { Swal.showValidationMessage('Escriba el precio de cada mes'); return false; }
      if (+$('#pn_g_desde').val() > +$('#pn_g_hasta').val()) { Swal.showValidationMessage('El mes "desde" debe ser anterior a "hasta"'); return false; }
      return { anio: $('#pn_anio').val(), nivel: $('#pn_g_nivel').val(), desde: $('#pn_g_desde').val(), hasta: $('#pn_g_hasta').val(), dia: $('#pn_g_dia').val(),
               precio: precio, mora: $.trim($('#pn_g_mora').val() || '0').replace(',', '.') };
    }
  }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.post(PN_URL.generar, r.value, null, 'json').done(function (resp) {
      Swal.fire(+resp.codigo === 1 ? 'Cronograma generado' : 'No se generó', textoSeguro(resp.mensaje || ''), +resp.codigo === 1 ? 'success' : 'warning');
      PN_Cargar();
    });
  });
}

function PN_Ajustar() {
  var anio = $('#pn_anio').val();
  $.post(PN_URL.ajustar, { anio: anio, aplicar: 0 }, null, 'json').done(function (r) {
    if (!r.data.length) { return Swal.fire('Todo en orden', 'No hay vencimientos para ajustar.', 'info'); }
    Swal.fire({
      title: 'Ajustar ' + r.data.length + (r.data.length === 1 ? ' vencimiento' : ' vencimientos'), width: 620, icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sí, ajustar', cancelButtonText: 'Cancelar',
      html: '<p class="small">Se mantiene el día y se usa el año que corresponde al mes dentro del año escolar. Queda registro del cambio.</p><div class="sa-historial-lista">' +
        r.data.map(function (f) { return '<div><b>' + textoSeguro(PN_Titulo(f.nivel)) + ' · ' + textoSeguro(PN_Titulo(f.mes)) + '</b><p><span class="text-danger">' + PN_Fecha(f.fecha_actual) +
          '</span> → <span class="text-success font-weight-bold">' + (f.fecha_nueva ? PN_Fecha(f.fecha_nueva) : '—') + '</span></p></div>'; }).join('') + '</div>'
    }).then(function (x) {
      if (!x.isConfirmed) { return; }
      $.post(PN_URL.ajustar, { anio: anio, aplicar: 1 }, null, 'json').done(function (r2) {
        Swal.fire('Listo', r2.data.length + (r2.data.length === 1 ? ' vencimiento ajustado' : ' vencimientos ajustados'), 'success');
        PN_Cargar();
      });
    });
  });
}
