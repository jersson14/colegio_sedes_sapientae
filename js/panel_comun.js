// Piezas comunes de los paneles de inicio (Fase 12-13): fechas, encabezado con el periodo en curso,
// línea del año escolar y lista de comunicados. Las usan js/panel_admin.js y js/panel_rol.js.
window.PanelComun = (function () {
  var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

  function fecha(iso) { var p = String(iso).substr(0, 10).split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function dias(a, b) { return Math.round((fecha(b) - fecha(a)) / 86400000); }
  function corta(iso) { var f = fecha(iso); return f.getDate() + ' ' + MESES[f.getMonth()]; }
  function plural(n, uno, varios) { return n + ' ' + (n === 1 ? uno : varios); }
  function hora(iso) { return String(iso).substr(11, 5); }
  // "IV BIMESTRE" -> "IV Bimestre" (el número romano queda en mayúsculas)
  function nombrePeriodo(t) {
    return String(t).split(' ').map(function (w) { return /^[IVX]+$/.test(w) ? w : w.charAt(0) + w.substr(1).toLowerCase(); }).join(' ');
  }
  // Nombres guardados en MAYÚSCULAS -> "Ana Quispe Rojas"
  var MENORES = ['y', 'e', 'o', 'u', 'de', 'del', 'la', 'las', 'los', 'el', 'en', 'a', 'al', 'con', 'por', 'para'];
  function titulo(t) {
    var s = textoPlano(t).toLowerCase().replace(/(^|[\s\-'·(])([a-záéíóúñü])/g, function (m, a, b) { return a + b.toUpperCase(); });
    // "Ciencia y Ambiente", "Juan de la Cruz": artículos y conectores en minúscula (salvo al inicio)
    return s.replace(/(\s)(\S+)/g, function (m, esp, w) { return MENORES.indexOf(w.toLowerCase()) !== -1 ? esp + w.toLowerCase() : m; });
  }
  function ir(vista, texto) {
    return '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'' + vista + '\');return false;">' + texto + '</a>';
  }

  function cabecera(datos) {
    var hoy = fecha(datos.hoy);
    $('#pi_fecha').text(hoy.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
    var actual = (datos.periodos || []).filter(function (p) { return datos.hoy >= p.inicio && datos.hoy <= p.fin; })[0];
    if (actual) {
      $('#pi_periodo').text(nombrePeriodo(actual.nombre) + ' · semana ' + (Math.floor(dias(actual.inicio, datos.hoy) / 7) + 1)).show();
    } else if (datos.anio && datos.hoy >= datos.anio.fecha_inicio && datos.hoy <= datos.anio.fecha_fin) {
      $('#pi_periodo').text('Vacaciones entre bimestres').show();
    } else {
      $('#pi_periodo').hide();
    }
  }

  // Línea del año escolar: bimestres como tramos, la semana actual marcada como "Hoy"
  function lineaAnio(datos, enlaceConfigurar) {
    var a = datos.anio;
    if (!a) {
      $('#pi_anio_nombre').text('Año escolar');
      $('#pi_linea').html('<p class="pi-tenue mb-0">No hay un año escolar activo.' + (enlaceConfigurar
        ? ' <a href="#" onclick="cargar_contenido(\'contenido_principal\',\'año/view_año_escolar.php\');return false;">Configurar año escolar</a>' : '') + '</p>');
      $('#pi_anio_resumen').text('');
      return;
    }
    var total = Math.max(1, dias(a.fecha_inicio, a.fecha_fin));
    var pos = function (iso) { return Math.min(100, Math.max(0, dias(a.fecha_inicio, iso) * 100 / total)); };
    var h = '<div class="pi-pista">';
    (datos.periodos || []).forEach(function (p) {
      var ini = pos(p.inicio), fin = pos(p.fin);
      if (fin <= ini) { return; }
      var clase = datos.hoy > p.fin ? ' pi-pasado' : (datos.hoy >= p.inicio ? ' pi-actual' : '');
      h += '<div class="pi-tramo' + clase + '" style="left:' + ini + '%;width:' + (fin - ini) + '%" title="' + textoSeguro(p.nombre + ': ' + corta(p.inicio) + ' – ' + corta(p.fin)) + '">'
        + textoSeguro(p.nombre.replace(' BIMESTRE', ' bim.').replace(' TRIMESTRE', ' trim.')) + '</div>';
    });
    if (datos.hoy >= a.fecha_inicio && datos.hoy <= a.fecha_fin) {
      h += '<div class="pi-hoy" style="left:' + pos(datos.hoy) + '%"><span>Hoy</span></div>';
    }
    h += '</div><div class="pi-fechas"><span>Inicio ' + corta(a.fecha_inicio) + '</span><span>Fin ' + corta(a.fecha_fin) + '</span></div>';
    $('#pi_linea').html(h).attr('aria-label', 'Año escolar ' + a.anio + ': del ' + corta(a.fecha_inicio) + ' al ' + corta(a.fecha_fin));
    $('#pi_anio_nombre').text('Año escolar ' + a.anio);
    var resta = dias(datos.hoy, a.fecha_fin);
    $('#pi_anio_resumen').text(datos.hoy < a.fecha_inicio ? 'Empieza en ' + plural(dias(datos.hoy, a.fecha_inicio), 'día', 'días')
      : (resta >= 0 ? Math.round(pos(datos.hoy)) + '% del año · faltan ' + plural(resta, 'día', 'días') : 'Año escolar terminado'));
  }

  // Lista de comunicados (fecha, título y a quién va dirigido)
  function comunicados(lista, selector, vacio) {
    if (!lista || !lista.length) {
      $(selector).html('<li class="pi-vacio">' + (vacio || 'No hay comunicados recientes.') + '</li>');
      return;
    }
    $(selector).html(lista.map(function (c) {
      var f = fecha(c.fecha);
      var dirigido = c.tipo === 'GENERAL' ? 'Para todo el colegio' : 'Para ' + (titulo(c.grado) || 'un grado');
      return '<li><span class="pi-dia"><b>' + f.getDate() + '</b><span>' + MESES[f.getMonth()] + '</span></span>'
        + '<span class="pi-tit" title="' + textoSeguro(c.titulo) + '">' + textoSeguro(c.titulo) + '<small>' + textoSeguro(dirigido) + '</small></span>'
        + (c.estado ? '<span class="pi-estado ' + (c.estado === 'ACTIVO' ? 'on">Publicado' : 'off">Oculto') + '</span>' : '<span></span>') + '</li>';
    }).join(''));
  }

  return { MESES: MESES, fecha: fecha, dias: dias, corta: corta, plural: plural, hora: hora, nombrePeriodo: nombrePeriodo,
           titulo: titulo, ir: ir, cabecera: cabecera, lineaAnio: lineaAnio, comunicados: comunicados };
})();

// Abre el registro de asistencia de un aula en modo Día (desde los paneles de inicio; lo lee js/registro_asistencia.js)
function Asistencia_Abrir(aula) {
  window.__asistenciaInicial = { aula: aula, modo: 'dia' };
  cargar_contenido('contenido_principal', 'asistencia/view_registro_asistencia.php');
}
