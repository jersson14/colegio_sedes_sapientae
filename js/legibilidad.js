// Legibilidad de TODAS las tablas del sistema (Fase 13, paso 10), sin tocar cada pantalla ni lo guardado:
//  - Nombres, direcciones, cursos y grados guardados en MAYÚSCULAS se muestran en formato título ("Ana Quispe Rojas").
//  - Fechas "2025-12-31" o "2025-12-31 10:40:50" se muestran como "31/12/2025" o "31/12/2025 10:40".
//  - Estados como etiquetas con el mismo color para el mismo significado en todo el sistema.
//  - Tablas vacías con un mensaje útil y, si la pantalla tiene un botón para crear, la acción siguiente.
// Solo cambia lo que se ve en la página: el orden, los filtros y las exportaciones usan los datos originales.
//  - En el celular, cada fila se muestra como una tarjeta (Fase 13, paso 12).
// Una tabla puede excluirse con la clase "sin-mejoras" (o solo de las tarjetas con "sin-tarjetas").
(function ($) {
  if (!$ || !$.fn.dataTable) { return; }

  var COL_NOMBRE = /estudiante|alumno|docente|profesor|nombre|apellido|padre|madre|pap[aá]|mam[aá]|apoderado|personal|responsable|tutor|direcci[oó]n|curso|asignatura|[aá]rea|grado|aula|especialidad|trabajador|proveedor|atendido|registrado/i;
  var COL_ESTADO = /^(estado|estatus|situaci[oó]n)$/i;
  var FECHA = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/;

  // Mismo significado, mismo color: ok (verde), pendiente (ámbar), info (azul), mal (rojo), neutro (gris)
  var ESTADOS = {
    ok: ['ACTIVO', 'ACTIVA', 'VIGENTE', 'PAGADO', 'PAGADA', 'APROBADO', 'APROBADA', 'CALIFICADO', 'PRESENTE', 'VALIDO', 'VÁLIDO',
         'ATENDIDO', 'ATENDIDA', 'ENTREGADO', 'ENTREGADA', 'COMPLETADO', 'COMPLETADA', 'REALIZADO', 'REALIZADA', 'SI', 'SÍ', 'ABIERTA', 'ABIERTO'],
    pend: ['PENDIENTE', 'TARDE', 'TARDANZA', 'BORRADOR', 'POR VENCER', 'PARCIAL', 'EN PROCESO', 'POR CALIFICAR'],
    info: ['ENVIADO', 'ENVIADA', 'JUSTIFICADO', 'JUSTIFICADA', 'CONTACTADO', 'CONTACTADA', 'EN CURSO', 'NUEVO', 'NUEVA'],
    mal: ['VENCIDO', 'VENCIDA', 'DESAPROBADO', 'DESAPROBADA', 'AUSENTE', 'FALTA', 'ANULADO', 'ANULADA', 'RECHAZADO', 'RECHAZADA',
          'FUERA DE PLAZO', 'BLOQUEADO', 'BLOQUEADA', 'NO ENTREGADO', 'NO ENTREGADA'],
    neutro: ['INACTIVO', 'INACTIVA', 'NO', 'SIN USUARIO', 'CERRADO', 'CERRADA', 'FINALIZADO', 'FINALIZADA', 'ANTIGUO', 'ANTIGUA',
             'SUSPENDIDO', 'TODOS'],
    // No son estados, pero cada valor conserva su color de siempre: nivel (inicial ámbar, primaria verde, secundaria azul) y sexo
    'nivel-ini': ['INICIAL'], 'nivel-pri': ['PRIMARIA'], 'nivel-sec': ['SECUNDARIA'],
    masc: ['MASCULINO'], fem: ['FEMENINO']
  };
  var CLASE = {};
  Object.keys(ESTADOS).forEach(function (k) { ESTADOS[k].forEach(function (t) { CLASE[t] = 'est-' + k; }); });

  function texto(el) { return $.trim($(el).text()).replace(/\s+/g, ' '); }
  function soloTexto(cell) { return cell.children.length === 0; }
  function mayusculas(t) { return /[A-ZÁÉÍÓÚÑ]/.test(t) && !/[a-záéíóúñ]/.test(t); }
  function oracion(t) { return t.charAt(0) + t.slice(1).toLowerCase(); }
  function titulo(t) { return window.PanelComun ? PanelComun.titulo(t) : t; }

  // "28-12-2025", "28-12-2025 13:22" o "28-12-2025 - 13:22:05"
  var FECHA_DMY = /^(\d{2})-(\d{2})-(\d{4})(?:\s*-?\s*(\d{2}:\d{2})(?::\d{2})?)?$/;

  function fecha(t) {
    var d = FECHA_DMY.exec(t);
    if (d) { return d[1] + '/' + d[2] + '/' + d[3] + (d[4] ? ' ' + d[4] : ''); }   // "28-12-2025 - 13:22:05" -> "28/12/2025 13:22"
    var m = FECHA.exec(t);
    if (!m) { return null; }
    if (m[1] === '0000') { return '—'; }
    return m[3] + '/' + m[2] + '/' + m[1] + (m[4] && !(m[4] === '00' && m[5] === '00') ? ' ' + m[4] + ':' + m[5] : '');
  }

  // Cambia solo los nodos de texto (respeta íconos y botones dentro de la etiqueta)
  function cambiarTexto(el, fn) {
    $(el).contents().filter(function () { return this.nodeType === 3 && $.trim(this.nodeValue); }).each(function () {
      var original = this.nodeValue, lado = original.match(/^\s*/)[0], fin = original.match(/\s*$/)[0];
      this.nodeValue = lado + fn($.trim(original)) + fin;
    });
  }

  function etiqueta(el) {
    var t = texto(el).toUpperCase(), c = CLASE[t];
    if (!c) { return; }
    $(el).removeClass('est-ok est-pend est-info est-mal est-neutro est-nivel-ini est-nivel-pri est-nivel-sec est-masc est-fem bg-primary bg-success bg-warning bg-danger bg-info bg-dark bg-secondary').addClass('lg-estado ' + c);
    if (mayusculas(texto(el))) { cambiarTexto(el, oracion); }
  }

  function celda(cell, cabecera) {
    if (soloTexto(cell)) {
      var t = texto(cell);
      if (!t) { return; }
      var f = fecha(t);
      if (f !== null) { cell.textContent = f; return; }
      if (COL_ESTADO.test(cabecera) && CLASE[t.toUpperCase()]) {
        var span = document.createElement('span');
        span.className = 'badge';
        span.textContent = t;
        cell.textContent = '';
        cell.appendChild(span);
        etiqueta(span);
        return;
      }
      if (COL_NOMBRE.test(cabecera) && mayusculas(t) && /[A-ZÁÉÍÓÚÑ]{2}/.test(t)) { cell.textContent = titulo(t); }
      return;
    }
    $(cell).find('.badge').each(function () { etiqueta(this); });
  }

  // Tabla vacía: mensaje útil y el botón de crear de la pantalla (si lo hay)
  function vacia(api) {
    var td = $(api.table().body()).find('td.dataTables_empty');
    if (!td.length || td.data('lg')) { return; }
    var filtrada = api.search() !== '' || api.columns().search().toArray().some(function (s) { return s; });
    if (filtrada) { return; }   // "No se encontraron resultados" ya lo dice
    var btn = $(api.table().container()).closest('.card, .content').find('button, a').filter(function () {
      return !$(this).closest('.modal, .dataTables_wrapper').length && $(this).is(':visible') && /\b(nuevo|nueva|registrar|agregar|crear)\b/i.test($(this).text());
    }).first();
    var h = '<div class="lg-vacio"><i class="far fa-folder-open"></i> Aún no hay registros aquí.';
    if (btn.length) { h += ' <button type="button" class="btn btn-sm btn-outline-primary ml-2">' + textoSeguro($.trim(btn.text())) + '</button>'; }
    td.data('lg', true).html(h + '</div>');
    td.find('button').on('click', function () { btn[0].click(); });
  }

  // Columnas que el usuario tiene a la vista. No se usa el selector ':visible' de DataTables porque excluye las que
  // la extensión "responsive" esconde en pantallas angostas (en el celular serían casi todas).
  function columnas(api) {
    return api.columns().indexes().toArray().filter(function (i) { return api.column(i).visible(); });
  }

  // ---------- Celular: cada fila como una tarjeta (Fase 13, paso 12) ----------
  // Cada celda lleva el nombre de su columna (data-label) y css/tema.css la muestra como "Etiqueta: valor" en
  // pantallas angostas. La columna del nombre va arriba como título y las acciones al pie. Opción: "sin-tarjetas".
  // Título de la tarjeta: la columna más descriptiva, en este orden de preferencia
  var COL_TITULO = [/^(estudiante|alumnos?|docente|nombres?|apellidos( y nombres)?|personal|trabajador)$/i,
                    /^(tema|tema o tarea|t[ií]tulo|concepto|especialidad|rol)$/i, /^(curso|asignatura|curso - docente)$/i, /^(aula|grado|aula o grado|nivel)$/i];
  function prioridadTitulo(cab) {
    for (var k = 0; k < COL_TITULO.length; k++) { if (COL_TITULO[k].test(cab)) { return k; } }
    return -1;
  }
  var COL_ACCION = /^(acci[oó]n(es)?|opciones|libreta|ficha|ver|editar)$/i;
  var COL_NUMERO = /^(n(ro)?\.?|n\.?º|n°|#|item)$/i;

  function tarjetas(api) {
    var tabla = $(api.table().node());
    if (tabla.hasClass('sin-tarjetas')) { return; }
    tabla.addClass('lg-tarjetas');
    // la columna de título: la de mayor preferencia (y, entre iguales, la primera)
    var titulo = null, mejor = 99;
    columnas(api).forEach(function (i) {
      var p = prioridadTitulo($.trim($(api.column(i).header()).text()));
      if (p >= 0 && p < mejor) { mejor = p; titulo = i; }
    });
    columnas(api).forEach(function (i) {
      var cab = $.trim($(api.column(i).header()).text());
      var clase = COL_ACCION.test(cab) ? 'lg-acc' : (COL_NUMERO.test(cab) ? 'lg-num' : (i === titulo ? 'lg-tit' : ''));
      api.column(i, { page: 'current' }).nodes().each(function (cell) {
        if (!cell || cell.classList.contains('dataTables_empty')) { return; }
        cell.setAttribute('data-label', cab);
        if (clase) { cell.classList.add(clase); }
        // una celda solo con botones es de acciones aunque la columna no se llame así
        if (!clase && $(cell).children().length && $(cell).children().not('button, a.btn, .btn-group, .text-nowrap').length === 0) { cell.classList.add('lg-acc'); }
      });
    });
  }

  // ---------- Botones con solo un ícono: etiqueta para lectores de pantalla y para el dedo (title) ----------
  var ICONOS = { 'fa-trash': 'Eliminar', 'fa-trash-alt': 'Eliminar', 'fa-edit': 'Editar', 'fa-pen': 'Editar', 'fa-plus': 'Agregar',
                 'fa-minus': 'Quitar', 'fa-times': 'Quitar', 'fa-ban': 'Anular', 'fa-eye': 'Ver', 'fa-print': 'Imprimir',
                 'fa-file-pdf': 'Descargar PDF', 'fa-file-excel': 'Descargar Excel', 'fa-search': 'Buscar', 'fa-download': 'Descargar',
                 'fa-check': 'Confirmar', 'fa-save': 'Guardar', 'fa-sync': 'Actualizar', 'fa-redo': 'Repetir' };
  function etiquetarBotones(raiz) {
    $(raiz).find('button, a.btn').each(function () {
      if (this.getAttribute('aria-label') || $.trim($(this).text())) { return; }
      var t = this.getAttribute('title');
      if (!t) {
        var i = $(this).find('i, span.fa, span.fas').first();
        Object.keys(ICONOS).some(function (k) { if (i.hasClass(k)) { t = ICONOS[k]; return true; } return false; });
      }
      if (t) { this.setAttribute('aria-label', t); if (!this.getAttribute('title')) { this.setAttribute('title', t); } }
    });
  }

  function aplicar(api) {
    var node = api.table().node();
    if ($(node).hasClass('sin-mejoras')) { return; }
    columnas(api).forEach(function (i) {
      var cab = $.trim($(api.column(i).header()).text());
      api.column(i, { page: 'current' }).nodes().each(function (cell) {
        if (cell && !cell.classList.contains('dataTables_empty')) { celda(cell, cab); }
      });
    });
    tarjetas(api);
    etiquetarBotones(node);
    vacia(api);
  }

  // Ventanas y pantallas que se cargan después: sus botones de ícono también quedan etiquetados
  var espera = null;
  $(document).on('ajaxComplete shown.bs.modal', function () {
    clearTimeout(espera);
    espera = setTimeout(function () { etiquetarBotones(document.getElementById('contenido_principal') || document.body); etiquetarBotones($('.modal.show')); }, 120);
  });

  $(document).on('draw.dt', function (e, settings) {
    try { aplicar(new $.fn.dataTable.Api(settings)); } catch (err) { /* nunca romper una tabla por la presentación */ }
  });

  window.Legibilidad = { titulo: titulo, fecha: fecha, aplicar: aplicar };
})(window.jQuery);
