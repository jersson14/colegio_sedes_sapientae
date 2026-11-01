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
    s = s.replace(/(\s)(\S+)/g, function (m, esp, w) { return MENORES.indexOf(w.toLowerCase()) !== -1 ? esp + w.toLowerCase() : m; });
    // números romanos ("IV Bimestre") y una letra sola al final: la sección del aula ("Primer Grado A"), no un conector
    s = s.replace(/(^|\s)(i{1,3}|iv|vi{0,3}|ix|x|xi{1,3}|xiv)(?=\s|$)/gi, function (m, esp, r) { return esp + r.toUpperCase(); });
    return s.replace(/(\s)([a-zñ])$/, function (m, esp, l) { return esp + l.toUpperCase(); });
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
    // Cada comunicado se abre con un clic (Comunicado_Abrir registra la lectura); "Nuevo" si el usuario aún no lo leyó
    $(selector).html(lista.map(function (c) {
      var f = fecha(c.fecha);
      var dirigido = c.tipo === 'GENERAL' ? 'Para todo el colegio' : 'Para ' + (titulo(c.grado) || 'un grado');
      var nuevo = c.leido !== undefined && !+c.leido;
      return '<li class="pi-com" role="button" tabindex="0" onclick="Comunicado_Abrir(' + (+c.id) + ', this)" onkeydown="if(event.key===\'Enter\'){Comunicado_Abrir(' + (+c.id) + ', this)}">'
        + '<span class="pi-dia"><b>' + f.getDate() + '</b><span>' + MESES[f.getMonth()] + '</span></span>'
        + '<span class="pi-tit" title="' + textoSeguro(c.titulo) + '">' + textoSeguro(c.titulo) + '<small>' + textoSeguro(dirigido) + '</small></span>'
        + (c.estado ? '<span class="pi-estado ' + (c.estado === 'ACTIVO' ? 'on">Publicado' : 'off">Oculto') + '</span>'
          : (nuevo ? '<span class="pi-estado pi-nuevo">Nuevo</span>' : '<span></span>')) + '</li>';
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

// Abre un comunicado completo y registra que el usuario lo leyó (Fase 13, paso 14)
function Comunicado_Abrir(id, el) {
  $.post('../controller/comunicados/controlador_ver_comunicado.php', { id: id }, null, 'json').done(function (r) {
    var c = r.comunicado;
    var f = String(c.fecha).substr(0, 10).split('-').reverse().join('/');
    var dirigido = c.tipo === 'GENERAL' ? 'Para todo el colegio' : 'Para ' + PanelComun.titulo($.trim(c.grado) || 'un grado');
    var img = c.imagen && !/\/$/.test(c.imagen) ? '../' + c.imagen : '';
    $('#com_ver_titulo').text(c.titulo);
    $('#com_ver_meta').text(f + ' · ' + dirigido);
    $('#com_ver_texto').html(textoSeguro(c.descripcion || '').replace(/\n/g, '<br>'));
    $('#com_ver_imagen').html(img ? (/\.pdf$/i.test(img) ? '<a class="btn btn-outline-primary" target="_blank" rel="noopener" href="' + textoSeguro(img) + '"><i class="fas fa-file-pdf mr-1"></i>Ver documento adjunto</a>'
      : '<img src="' + textoSeguro(img) + '" alt="Imagen del comunicado" onerror="this.remove()">') : '');
    $(el).find('.pi-nuevo').remove();
    $('#modal_comunicado_ver').modal('show');
  });
}

// Enlace de WhatsApp con el mensaje ya escrito (se abre en el teléfono o en WhatsApp Web; lo envía la persona).
// Agrega el prefijo del país configurado si el número no lo trae. Devuelve '' si el número no sirve.
function Aviso_WhatsApp_Url(celular, texto) {
  var d = String(celular || '').replace(/\D/g, '');
  if (d.length < 6) { return ''; }
  var pref = String((window.REGIONAL && REGIONAL.prefijo_tel) || '').replace(/\D/g, '');
  if (pref && !(d.indexOf(pref) === 0 && d.length > 9)) { d = pref + d; }
  return 'https://wa.me/' + d + '?text=' + encodeURIComponent(texto);
}

// Color fijo de cada curso (por el nombre de la asignatura): el mismo en el calendario y en el aula virtual
var CURSO_COLORES = ['#1967d2', '#137333', '#c5221f', '#b45309', '#8430ce', '#007b83', '#b80672', '#3949ab', '#5f6368', '#795548', '#0b8043', '#d93025'];
function Curso_Color(nombre) {
  var h = 0, t = String(nombre || '').toUpperCase();
  if (t.indexOf('RECREO') === 0) { return '#9aa0a6'; }
  for (var i = 0; i < t.length; i++) { h = (h * 31 + t.charCodeAt(i)) >>> 0; }
  return CURSO_COLORES[h % CURSO_COLORES.length];
}

// Abre el aula virtual directamente en un curso (desde el calendario, el inicio u otra pantalla)
function AulaVirtual_Ir(curso) {
  window.AV_CURSO_INICIAL = curso;
  cargar_contenido('contenido_principal', 'aula_virtual/view_aula_virtual.php');
}

// Aula de un curso (nivel, grado y sección) como clave, y la lista de aulas distintas de una lista de cursos.
// Las pantallas de la dirección muestran un selector de aula cuando hay muchos cursos.
function Curso_Aula_Clave(c) { return [c.nivel, c.grado, c.seccion].join('|'); }
function Cursos_Aulas(cursos) {
  var vistas = {}, res = [];
  cursos.forEach(function (c) {
    var k = Curso_Aula_Clave(c);
    if (vistas[k]) { return; }
    vistas[k] = 1;
    res.push({ clave: k, nombre: PanelComun.titulo(c.nivel) + ' · ' + PanelComun.titulo(c.grado) + (c.seccion && c.seccion !== 'UNICO' ? ' ' + c.seccion : '') });
  });
  return res;
}
