// Mejoras comunes para TODAS las tablas del sistema (Fase 11), sin tocar cada pantalla:
//  - "Filtros": un panel con un filtro por columna (lista desplegable si la columna tiene pocos valores, texto si no).
//  - "Columnas": elegir qué columnas ver; se recuerda en este navegador.
//  - Excel, PDF e Imprimir exportan solo las columnas visibles y las filas filtradas (sin la columna de acciones),
//    así cada listado sirve como reporte con los campos que el usuario elija.
// Una tabla puede excluirse con la clase "sin-mejoras".
(function ($) {
  if (!$ || !$.fn.dataTable) { return; }
  var MAX_OPCIONES = 30;

  function esAccion(texto) { return /^(acci[oó]n(es)?|opciones|editar|eliminar)$/i.test(texto); }
  function textoCabecera(api, i) { return $.trim($(api.column(i).header()).text()); }
  function plano(v) { return $('<div>').html(v == null ? '' : String(v)).text().replace(/\s+/g, ' ').trim(); }
  function clave(api) { return 'tm_cols_' + (api.table().node().id || '') + '_' + api.columns().count(); }

  function leer(k) { try { return JSON.parse(localStorage.getItem(k) || 'null'); } catch (e) { return null; } }
  function escribir(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* sin almacenamiento */ } }

  // Columnas que se pueden elegir/filtrar: con título y que no sean de acciones
  function columnasUtiles(api) {
    var r = [];
    api.columns().every(function (i) {
      var t = textoCabecera(api, i);
      if (t && !esAccion(t)) { r.push({ i: i, titulo: t }); }
    });
    return r;
  }

  // Exportar solo lo visible y nunca la columna de acciones
  function ajustarExportacion(api) {
    if (!api.buttons) { return; }
    var bs;
    try { bs = api.buttons(); } catch (e) { return; }
    for (var k = 0; k < bs.length; k++) {
      var b = bs[k], boton = b.inst && b.inst._nodeToButton ? b.inst._nodeToButton(b.node) : null;
      if (!boton || !boton.conf || !/excel|pdf|print|csv|copy/i.test(boton.conf.className || '') && !/excel|pdf|print|csv|copy/i.test(String(boton.conf.extend || ''))) { continue; }
      var eo = boton.conf.exportOptions || {};
      if (eo.columns === undefined || eo.columns === '') {
        boton.conf.exportOptions = $.extend({}, eo, {
          columns: function (idx) { return api.column(idx).visible() && !esAccion(textoCabecera(api, idx)); }
        });
      }
    }
  }

  function aplicarColumnasGuardadas(api) {
    var ocultas = leer(clave(api));
    if (Array.isArray(ocultas)) {
      ocultas.forEach(function (i) { if (i < api.columns().count()) { api.column(i).visible(false, false); } });
      api.columns.adjust().draw(false);
    }
  }

  function menuColumnas(api, cont) {
    var html = '<div class="dropdown-menu dropdown-menu-right p-2 tm-menu" style="max-height:60vh;overflow:auto;min-width:220px">'
      + '<small class="text-muted d-block mb-1">Columnas a mostrar y exportar</small>';
    columnasUtiles(api).forEach(function (c) {
      html += '<label class="dropdown-item px-1 mb-0" style="cursor:pointer"><input type="checkbox" class="mr-2" data-i="' + c.i + '"'
        + (api.column(c.i).visible() ? ' checked' : '') + '>' + textoSeguro(c.titulo) + '</label>';
    });
    html += '<div class="dropdown-divider"></div><button type="button" class="btn btn-link btn-sm tm-todas">Mostrar todas</button></div>';
    cont.find('.tm-menu').remove();
    cont.find('.tm-col').after(html);
    cont.find('.tm-menu').on('click', function (e) { e.stopPropagation(); });
    cont.find('.tm-menu input').on('change', function () {
      api.column(+$(this).data('i')).visible(this.checked);
      guardarColumnas(api);
    });
    cont.find('.tm-todas').on('click', function () {
      api.columns().visible(true);
      guardarColumnas(api);
      menuColumnas(api, cont);
    });
  }

  function guardarColumnas(api) {
    var ocultas = [];
    api.columns().every(function (i) { if (!this.visible()) { ocultas.push(i); } });
    escribir(clave(api), ocultas);
    api.columns.adjust();
  }

  function panelFiltros(api, panel) {
    var html = '<div class="form-row">';
    columnasUtiles(api).forEach(function (c) {
      if (!api.column(c.i).visible()) { return; }
      var valores = {};
      api.column(c.i).data().each(function (v) {
        var t = plano(v);
        if (t !== '' && t.length <= 60) { valores[t] = 1; }
      });
      var lista = Object.keys(valores).sort(function (a, b) { return a.localeCompare(b, 'es', { numeric: true }); });
      // sin filtro: fotos/imágenes y columnas sin datos (numeración, botones)
      if (/foto|imagen|logo/i.test(c.titulo) || !lista.length) { return; }
      var actual = api.column(c.i).search();
      html += '<div class="col-lg-2 col-md-3 col-6 mb-2"><label class="small mb-0 text-muted">' + textoSeguro(c.titulo) + '</label>';
      // lista si son pocos valores repetidos (estado, grado, sexo); búsqueda si casi todos son distintos (nombre, documento)
      var filas = api.rows().count();
      if (lista.length <= MAX_OPCIONES && (lista.length <= 8 || lista.length <= filas / 2)) {
        html += '<select class="form-control form-control-sm tm-filtro" data-i="' + c.i + '" data-exacto="1"><option value="">Todos</option>'
          + lista.map(function (v) { return '<option value="' + textoSeguro(v) + '">' + textoSeguro(v) + '</option>'; }).join('') + '</select>';
      } else {
        html += '<input type="search" class="form-control form-control-sm tm-filtro" data-i="' + c.i + '" placeholder="Contiene..." value="'
          + textoSeguro(actual) + '">';
      }
      html += '</div>';
    });
    html += '</div>';
    panel.html(html);
    panel.find('select.tm-filtro').each(function () {
      var s = api.column(+$(this).data('i')).search();
      var m = s.match(/^\(\^\|\[\^\\w\]\)(.*)\(\$\|\[\^\\w\]\)$/);
      if (m) { $(this).val(m[1].replace(/\\(.)/g, '$1')); }
    });
    panel.find('.tm-filtro').on('input change', function () {
      var v = $(this).val(), col = api.column(+$(this).data('i'));
      if ($(this).data('exacto')) {
        // palabra completa: "ACTIVO" no debe traer también "INACTIVO"
        col.search(v ? '(^|[^\\w])' + $.fn.dataTable.util.escapeRegex(v) + '($|[^\\w])' : '', true, false);
      } else {
        col.search(v, false, true);
      }
      col.draw();
      marcarActivos(api);
    });
  }

  function marcarActivos(api) {
    var n = 0;
    api.columns().every(function () { if (this.search()) { n++; } });
    var cont = $(api.table().container());
    cont.find('.tm-n').text(n ? n : '').toggle(n > 0);
    cont.find('.tm-limpiar').toggle(n > 0);
  }

  function mejorar(settings) {
    var api = new $.fn.dataTable.Api(settings);
    var tabla = $(api.table().node());
    if (tabla.hasClass('sin-mejoras') || tabla.data('tm') || settings.oFeatures.bServerSide || api.columns().count() < 2) { return; }
    tabla.data('tm', 1);
    ajustarExportacion(api);
    aplicarColumnasGuardadas(api);

    var cont = $(api.table().container());
    var barra = $('<div class="tm-barra d-flex justify-content-end flex-wrap mb-1" style="gap:4px">'
      + '<button type="button" class="btn btn-outline-secondary btn-sm tm-limpiar" style="display:none" title="Quitar todos los filtros"><i class="fas fa-times"></i> Limpiar</button>'
      + '<button type="button" class="btn btn-outline-primary btn-sm tm-fil" title="Filtrar por columna"><i class="fas fa-filter"></i> Filtros <span class="badge badge-primary tm-n" style="display:none"></span></button>'
      + '<div class="dropdown"><button type="button" class="btn btn-outline-secondary btn-sm tm-col" data-toggle="dropdown" title="Elegir columnas a mostrar y exportar">'
      + '<i class="fas fa-columns"></i> Columnas</button></div></div>');
    var panel = $('<div class="tm-panel border rounded p-2 mb-2 bg-light text-left" style="display:none"></div>');
    cont.prepend(panel).prepend(barra);
    menuColumnas(api, barra);

    barra.find('.tm-fil').on('click', function () {
      if (!panel.is(':visible')) { panelFiltros(api, panel); }
      panel.slideToggle(150);
    });
    barra.find('.tm-limpiar').on('click', function () {
      api.columns().search('');
      api.search('').draw();
      panel.find('.tm-filtro').val('');
      marcarActivos(api);
    });
    // al recargar datos (ajax.reload / filtros por fecha) se actualizan las opciones del panel abierto
    tabla.on('xhr.dt', function () {
      setTimeout(function () { if (panel.is(':visible')) { panelFiltros(api, panel); } }, 0);
    });
  }

  // una tabla destruida y creada de nuevo (p. ej. al regenerar un reporte) vuelve a recibir las mejoras
  $(document).on('destroy.dt', function (e, settings) {
    if (e.namespace === 'dt') { $(settings.nTable).removeData('tm'); }
  });

  $(document).on('init.dt', function (e, settings) {
    if (e.namespace !== 'dt') { return; }
    try { mejorar(settings); } catch (err) { if (window.console) { console.warn('tablas_mejoras', err); } }
  });
})(window.jQuery);
