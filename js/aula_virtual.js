// Aula virtual: tarjetas de los cursos y, dentro de cada curso, tablón de tareas, horario, personas y calificaciones.
// El estudiante entrega sus tareas aquí (mismo controlador que la pantalla de tareas); el docente revisa en el Registro de tareas.
var AV = { lista: null, curso: null, pestana: 'tablon' };
var AV_URL = '../controller/aula_virtual/controlador_aula_virtual.php';
var AV_DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
var AV_MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

function AulaVirtual_Rol() { return $('#txtprincipalrol').val(); }
function AulaVirtual_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function AulaVirtual_Aula(c) { return AulaVirtual_Titulo(c.grado) + (c.seccion && c.seccion !== 'UNICO' ? ' ' + c.seccion : '') + ' · ' + AulaVirtual_Titulo(c.nivel); }

// "lun 3 nov, 23:59" (con el año si no es el actual)
function AulaVirtual_Fecha(f, conHora) {
  if (!f) { return ''; }
  var d = new Date(String(f).replace(' ', 'T'));
  if (isNaN(d)) { return textoSeguro(f); }
  var s = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'][d.getDay()] + ' ' + d.getDate() + ' ' + AV_MESES[d.getMonth()];
  if (d.getFullYear() !== new Date().getFullYear()) { s += ' ' + d.getFullYear(); }
  if (conHora && String(f).length > 10) { s += ', ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0'); }
  return s;
}

function AulaVirtual_Avatar(c, clase) {
  var inicial = textoSeguro((c.docente || '?').trim().charAt(0).toUpperCase());
  var fondo = 'style="background:' + Curso_Color(c.asignatura + 'x') + '"';
  return c.foto ? '<span class="av-avatar ' + (clase || '') + '"><img src="../' + textoSeguro(c.foto) + '" alt="" onerror="this.parentNode.textContent=\'' + inicial + '\'"></span>'
                : '<span class="av-avatar ' + (clase || '') + '" ' + fondo + '>' + inicial + '</span>';
}

function AulaVirtual_Proxima(p) {
  if (!p) { return '<span class="text-muted"><i class="far fa-clock mr-1"></i>Sin horario</span>'; }
  return '<span class="' + (p.hoy ? 'av-hoy' : '') + '"><i class="far fa-clock mr-1"></i>' + (p.hoy ? 'Hoy' : AV_DIAS[p.dia]) + ' ' + textoSeguro(p.inicio) + ' – ' + textoSeguro(p.fin) + '</span>';
}

function AulaVirtual_Iniciar() {
  $(document).off('.av');
  $(document).on('change.av', '#av_anio', function () { $('#av_aula').val(''); AulaVirtual_Cargar(); });
  $(document).on('change.av', '#av_aula', AulaVirtual_Cargar);
  $(document).on('click.av', '.av-tarjeta', function (e) {
    if ($(e.target).closest('a').length) { return; }
    AulaVirtual_Abrir(+$(this).data('curso'), $(this).data('pestana') || 'tablon');
  });
  $(document).on('click.av', '.av-tarjeta .av-ir', function (e) {
    e.preventDefault(); e.stopPropagation();
    AulaVirtual_Abrir(+$(this).closest('.av-tarjeta').data('curso'), $(this).data('pestana'));
  });
  $(document).on('keydown.av', '.av-tarjeta', function (e) { if (e.key === 'Enter') { $(this).trigger('click'); } });
  $(document).on('click.av', '#av_volver', AulaVirtual_Volver);
  $(document).on('click.av', '.av-pestanas .nav-link', function (e) { e.preventDefault(); AulaVirtual_Pestana($(this).data('pestana')); });
  $(document).on('click.av', '.av-entregar', function () { AulaVirtual_Entrega(+$(this).data('i')); });
  $(document).on('click.av', '#av_entrega_enviar', function () { AulaVirtual_Enviar(); });   // en js/aula_virtual_entrega.js (solo estudiantes)
  $(document).on('click.av', '.av-revisar', function () { window.RT_CURSO_INICIAL = AV.curso.curso.id; cargar_contenido('contenido_principal', 'tareas/view_registro_tareas.php'); });
  $(document).on('click.av', '.av-nueva', function () { cargar_contenido('contenido_principal', AulaVirtual_Rol() === 'DOCENTE' ? 'tareas/view_tareas_profesor.php' : 'tareas/view_tareas.php'); });
  $(document).on('click.av', '.av-calendario', function () { cargar_contenido('contenido_principal', 'horario/view_calendario.php'); });
  $(document).on('click.av', '.av-desc-mas', function () { $(this).prev('.av-desc').toggleClass('abierta'); $(this).text($(this).prev('.av-desc').hasClass('abierta') ? 'Ver menos' : 'Ver más'); });
  var inicial = window.AV_CURSO_INICIAL;
  window.AV_CURSO_INICIAL = 0;
  AulaVirtual_Cargar(inicial);
}

function AulaVirtual_Cargar(abrir) {
  $.post(AV_URL, { accion: 'cursos', anio: $('#av_anio').val() || 0, aula: $('#av_aula').val() || 0 }, null, 'json').done(function (d) {
    AV.lista = d;
    $('#av_anio').html(d.anios.map(function (a) {
      return '<option value="' + a.id + '">' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
    }).join('')).val(d.anio);
    $('#av_aula_grupo').toggleClass('d-none', !d.aulas.length);
    $('#av_aula').html(d.aulas.map(function (a) { return '<option value="' + a.id + '">' + textoSeguro(AulaVirtual_Aula(a)) + '</option>'; }).join('')).val(String(d.aula));
    AulaVirtual_Tarjetas();
    if (abrir) { AulaVirtual_Abrir(abrir, 'tablon'); }
  });
}

function AulaVirtual_Tarjetas() {
  var d = AV.lista, rol = AulaVirtual_Rol();
  if (!d.cursos.length) {
    $('#av_cursos').html('<div class="av-vacio"><i class="fas fa-chalkboard"></i><p>' + (d.sin_matricula ? 'No tiene matrícula en este año escolar.' :
      rol === 'DOCENTE' ? 'No tiene cursos asignados en este año escolar.' : 'Esta aula no tiene cursos asignados.') + '</p></div>');
    return;
  }
  $('#av_cursos').html(d.cursos.map(function (c) {
    var color = Curso_Color(c.asignatura), estado = '';
    if (rol === 'ESTUDIANTE') {
      estado = c.por_entregar ? '<span class="av-chip av-chip-pend">' + c.por_entregar + ' por entregar</span>' : '';
      estado += c.vencidas ? '<span class="av-chip av-chip-mal">' + c.vencidas + (c.vencidas === 1 ? ' sin entregar' : ' sin entregar') + '</span>' : '';
      estado = estado || (c.tareas ? '<span class="av-chip av-chip-ok"><i class="fas fa-check mr-1"></i>Al día</span>' : '<span class="av-chip">Sin tareas</span>');
    } else {
      estado = (c.por_revisar ? '<span class="av-chip av-chip-pend">' + c.por_revisar + ' por revisar</span>' : '') +
               '<span class="av-chip">' + c.tareas + (c.tareas === 1 ? ' tarea' : ' tareas') + '</span>';
    }
    var sub = rol === 'DOCENTE' ? AulaVirtual_Aula(c) : AulaVirtual_Titulo(c.docente);
    return '<article class="av-tarjeta" tabindex="0" role="button" data-curso="' + c.id + '" aria-label="' + textoSeguro(c.asignatura) + '">' +
      '<div class="av-banda" style="--av-color:' + color + '"><h3>' + textoSeguro(AulaVirtual_Titulo(c.asignatura)) + '</h3><p>' + textoSeguro(sub) + '</p></div>' +
      AulaVirtual_Avatar(c) +
      '<div class="av-cuerpo"><div class="av-proxima">' + AulaVirtual_Proxima(c.proxima) + '</div><div class="av-estado">' + estado + '</div></div>' +
      '<footer><a href="#" class="av-ir" data-pestana="tablon" title="Tareas"><i class="fas fa-clipboard-list"></i></a>' +
      '<a href="#" class="av-ir" data-pestana="horario" title="Horario"><i class="far fa-calendar-alt"></i></a>' +
      '<a href="#" class="av-ir" data-pestana="personas" title="Personas"><i class="fas fa-users"></i></a></footer></article>';
  }).join(''));
}

function AulaVirtual_Abrir(curso, pestana) {
  $.post(AV_URL, { accion: 'curso', curso: curso }, null, 'json').done(function (d) {
    AV.curso = d;
    var c = d.curso, rol = AulaVirtual_Rol();
    $('#av_lista').addClass('d-none');
    $('#av_curso').removeClass('d-none');
    var prox = d.horario.length ? AulaVirtual_Proxima(AulaVirtual_ProximaDe(d.horario)) : '';
    $('#av_portada').attr('style', '--av-color:' + Curso_Color(c.asignatura)).html(
      '<div><h2>' + textoSeguro(AulaVirtual_Titulo(c.asignatura)) + '</h2><p>' + textoSeguro(AulaVirtual_Aula(c)) + '</p>' +
      '<p class="av-portada-docente">' + textoSeguro(AulaVirtual_Titulo(c.docente)) + (prox ? ' · ' + prox : '') + '</p></div>' +
      (rol !== 'ESTUDIANTE' ? '<div class="av-portada-acciones"><button type="button" class="btn btn-light btn-sm av-nueva"><i class="fas fa-plus mr-1"></i>Nueva tarea</button>' +
        '<button type="button" class="btn btn-light btn-sm av-revisar"><i class="fas fa-check-double mr-1"></i>Revisar y calificar</button></div>' : ''));
    AulaVirtual_Pestana(pestana || 'tablon');
    $('html, body').scrollTop(0);
  });
}

function AulaVirtual_ProximaDe(horario) {
  var hoy = new Date(), dia = hoy.getDay() || 7, ahora = String(hoy.getHours()).padStart(2, '0') + ':' + String(hoy.getMinutes()).padStart(2, '0'), mejor = null;
  horario.forEach(function (h) {
    var dias = (h.dia - dia + 7) % 7;
    if (dias === 0 && h.fin <= ahora) { dias = 7; }
    var k = dias * 10000 + +h.inicio.replace(':', '');
    if (!mejor || k < mejor.k) { mejor = { k: k, dia: h.dia, inicio: h.inicio, fin: h.fin, hoy: dias === 0 }; }
  });
  return mejor;
}

function AulaVirtual_Volver() {
  $('#av_curso').addClass('d-none');
  $('#av_lista').removeClass('d-none');
  AulaVirtual_Cargar();
}

function AulaVirtual_Pestana(p) {
  AV.pestana = p;
  $('.av-pestanas .nav-link').removeClass('active').filter('[data-pestana="' + p + '"]').addClass('active');
  var html = { tablon: AulaVirtual_Tablon, horario: AulaVirtual_Horario, personas: AulaVirtual_Personas, notas: AulaVirtual_Notas }[p]();
  $('#av_contenido').html(html);
}

// Estado de la tarea para el estudiante: {clase, texto, puede (entregar/reenviar)}
function AulaVirtual_EstadoTarea(t, ahora) {
  if (t.envio_estado === 'CALIFICADO') { return { clase: notaAprobada(t.calificacion) ? 'av-chip-ok' : 'av-chip-mal', texto: 'Nota: ' + t.calificacion, puede: false }; }
  var vencida = t.fecha_entrega && t.fecha_entrega < ahora, cerrada = t.estado === 'FINALIZADO';
  if (t.envio_estado === 'ENVIADO') {
    var tarde = t.fecha_envio && t.fecha_entrega && t.fecha_envio > t.fecha_entrega;
    return { clase: 'av-chip-info', texto: tarde ? 'Entregada fuera de plazo' : 'Entregada', puede: !cerrada };
  }
  if (cerrada) { return { clase: 'av-chip-mal', texto: 'No entregada (cerrada)', puede: false }; }
  if (vencida) { return { clase: 'av-chip-mal', texto: 'Vencida: aún puede entregar', puede: true }; }
  return { clase: 'av-chip-pend', texto: 'Por entregar', puede: true };
}

function AulaVirtual_Material(t) {
  var url = '../controller/tareas/controlador_archivos_tarea.php?carpeta=';
  var h = t.material ? '<a class="btn btn-outline-secondary btn-sm" href="' + url + encodeURIComponent(t.material) + '" target="_blank" rel="noopener"><i class="fas fa-paperclip mr-1"></i>Material' + (t.videos ? ' y videos' : '') + '</a>' : '';
  if (t.envio_archivo) { h += '<a class="btn btn-outline-primary btn-sm" href="' + url + encodeURIComponent(t.envio_archivo) + '" target="_blank" rel="noopener"><i class="fas fa-file-alt mr-1"></i>Mi entrega</a>'; }
  return h;
}

function AulaVirtual_Tablon() {
  var d = AV.curso, est = AulaVirtual_Rol() === 'ESTUDIANTE', color = Curso_Color(d.curso.asignatura);
  var proximas = d.tareas.filter(function (t) {
    return t.estado !== 'FINALIZADO' && t.fecha_entrega >= d.ahora && (!est || t.envio_estado === 'PENDIENTE');
  }).sort(function (a, b) { return a.fecha_entrega < b.fecha_entrega ? -1 : 1; });
  var lateral = '<aside class="av-proximas"><h4>Próximas entregas</h4>' + (proximas.length ? proximas.slice(0, 5).map(function (t) {
    return '<p><span>' + AulaVirtual_Fecha(t.fecha_entrega, true) + '</span>' + textoSeguro(AulaVirtual_Titulo(t.tema)) + '</p>';
  }).join('') : '<p class="text-muted">' + (est ? '¡Nada pendiente!' : 'Ninguna tarea abierta.') + '</p>') +
    '<button type="button" class="btn btn-link btn-sm px-0 av-calendario"><i class="far fa-calendar-alt mr-1"></i>Ver calendario de clases</button></aside>';
  var tareas = d.tareas.length ? d.tareas.map(function (t, i) {
    var pie = '';
    if (est) {
      var e = AulaVirtual_EstadoTarea(t, d.ahora);
      pie = '<span class="av-chip ' + e.clase + '">' + textoSeguro(e.texto) + '</span>' +
            (e.puede && t.envio ? '<button type="button" class="btn btn-success btn-sm av-entregar" data-i="' + i + '"><i class="fas fa-upload mr-1"></i>' + (t.envio_estado === 'ENVIADO' ? 'Cambiar entrega' : 'Entregar') + '</button>' : '') +
            (t.envio_estado === 'CALIFICADO' && t.observacion ? '<p class="av-observacion"><i class="fas fa-comment-dots mr-1"></i>' + textoSeguro(t.observacion) + '</p>' : '');
    } else {
      var total = +t.asignadas || 0, hechas = (+t.entregadas || 0) + (+t.calificadas || 0);
      pie = '<span class="av-progreso" title="Entregaron ' + hechas + ' de ' + total + '"><i style="width:' + (total ? Math.round(hechas * 100 / total) : 0) + '%;background:' + color + '"></i></span>' +
            '<span class="small text-muted">' + hechas + ' de ' + total + ' entregaron · ' + (+t.calificadas || 0) + ' calificadas' + (+t.entregadas ? ' · <b>' + t.entregadas + ' por revisar</b>' : '') + '</span>' +
            (t.estado === 'FINALIZADO' ? '<span class="av-chip">Cerrada</span>' : '');
    }
    return '<article class="av-tarea"><span class="av-tarea-icono" style="background:' + color + '"><i class="fas fa-clipboard-list"></i></span><div class="av-tarea-cuerpo">' +
      '<h4>' + textoSeguro(AulaVirtual_Titulo(t.tema)) + '</h4><p class="av-tarea-fechas">Publicada ' + AulaVirtual_Fecha(t.publicada) +
      (t.fecha_entrega ? ' · <b>Vence ' + AulaVirtual_Fecha(t.fecha_entrega, true) + '</b>' : '') + '</p>' +
      (t.descripcion ? '<p class="av-desc">' + textoSeguro(t.descripcion) + '</p>' + (t.descripcion.length > 180 ? '<button type="button" class="btn btn-link btn-sm p-0 av-desc-mas">Ver más</button>' : '') : '') +
      '<div class="av-tarea-acciones">' + AulaVirtual_Material(t) + '</div><div class="av-tarea-pie">' + pie + '</div></div></article>';
  }).join('') : '<div class="av-vacio"><i class="fas fa-clipboard"></i><p>Aún no hay tareas en este curso.</p></div>';
  return '<div class="av-tablon">' + lateral + '<div class="av-flujo">' + tareas + '</div></div>';
}

function AulaVirtual_Horario() {
  var h = AV.curso.horario;
  if (!h.length) { return '<div class="av-vacio"><i class="far fa-clock"></i><p>Este curso aún no tiene horario.</p></div>'; }
  var color = Curso_Color(AV.curso.curso.asignatura);
  var porDia = {};
  h.forEach(function (x) { (porDia[x.dia] = porDia[x.dia] || []).push(x); });
  return '<div class="av-semana">' + Object.keys(porDia).map(function (dia) {
    return '<div class="av-dia"><h5>' + AV_DIAS[dia] + '</h5>' + porDia[dia].map(function (x) {
      return '<p style="border-left-color:' + color + '"><b>' + textoSeguro(x.inicio) + ' – ' + textoSeguro(x.fin) + '</b></p>';
    }).join('') + '</div>';
  }).join('') + '</div><p class="mt-3 mb-0 small text-muted">' + h.length + (h.length === 1 ? ' hora' : ' horas') +
    ' a la semana. <button type="button" class="btn btn-link btn-sm p-0 av-calendario">Ver el horario completo del aula</button></p>';
}

function AulaVirtual_Personas() {
  var d = AV.curso;
  var est = d.estudiantes.map(function (e) {
    var n = AulaVirtual_Titulo(e.nombres + ' ' + e.apellidos);
    return '<li><span class="av-avatar av-avatar-sm" style="background:' + Curso_Color(n) + '">' + textoSeguro(n.charAt(0)) + '</span>' + textoSeguro(n) + '</li>';
  }).join('');
  return '<div class="av-personas"><h4>Docente</h4><ul><li>' + AulaVirtual_Avatar(d.curso, 'av-avatar-sm') + textoSeguro(AulaVirtual_Titulo(d.curso.docente)) + '</li></ul>' +
    '<h4>Estudiantes <span>' + d.estudiantes.length + '</span></h4><ul>' + (est || '<li class="text-muted">Sin estudiantes matriculados.</li>') + '</ul></div>';
}

function AulaVirtual_Notas() {
  var d = AV.curso;
  if (AulaVirtual_Rol() !== 'ESTUDIANTE') {
    return '<div class="av-vacio"><i class="fas fa-table"></i><p>Las notas de todos los estudiantes, tarea por tarea, están en el Registro de tareas.</p>' +
      '<button type="button" class="btn btn-primary av-revisar"><i class="fas fa-check-double mr-1"></i>Abrir el Registro de tareas de este curso</button></div>';
  }
  if (!d.tareas.length) { return '<div class="av-vacio"><i class="fas fa-star"></i><p>Aún no hay tareas en este curso.</p></div>'; }
  var nums = [];
  var filas = d.tareas.map(function (t) {
    var e = AulaVirtual_EstadoTarea(t, d.ahora);
    if (t.envio_estado === 'CALIFICADO' && !isNaN(parseFloat(t.calificacion))) { nums.push(parseFloat(t.calificacion)); }
    return '<tr><td>' + textoSeguro(AulaVirtual_Titulo(t.tema)) + '</td><td>' + AulaVirtual_Fecha(t.fecha_entrega, true) + '</td><td><span class="av-chip ' + e.clase + '">' + textoSeguro(e.texto) + '</span></td></tr>';
  }).join('');
  var prom = nums.length ? (nums.reduce(function (a, b) { return a + b; }, 0) / nums.length) : null;
  return (prom !== null ? '<div class="av-promedio">Promedio de tus tareas calificadas: <b class="' + (notaAprobada(prom) ? 'text-success' : 'text-danger') + '">' + prom.toFixed(1) + '</b> (' + nums.length + ' de ' + d.tareas.length + ')</div>' : '') +
    '<div class="table-responsive"><table class="table table-sm av-tabla-notas"><thead><tr><th>Tarea</th><th>Vence</th><th>Estado</th></tr></thead><tbody>' + filas + '</tbody></table></div>';
}
