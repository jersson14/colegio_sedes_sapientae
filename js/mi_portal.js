// Pantallas del estudiante: Mis tareas, Mis notas, Mis pagos y Mi asistencia.
// Cada una pide sus datos a controller/estudiante/ (siempre con la matrícula del estudiante en sesión).
var MP = { datos: null, pantalla: null, filtro: null, curso: '', periodo: null };
var MP_MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
var MP_MES_CORTO = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

// ---------- Común ----------
function MiPortal_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }

function MiPortal_Fecha(f, conHora) {
  if (!f || String(f).indexOf('0000') === 0) { return ''; }
  // Una fecha sola (aaaa-mm-dd) se lee como hora local: sin la hora, el navegador la toma en UTC y en Perú cae el día anterior
  var d = new Date(String(f).length === 10 ? f + 'T00:00:00' : String(f).replace(' ', 'T'));
  if (isNaN(d)) { return textoSeguro(f); }
  var s = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'][d.getDay()] + ' ' + d.getDate() + ' ' + MP_MES_CORTO[d.getMonth()];
  if (d.getFullYear() !== new Date().getFullYear()) { s += ' ' + d.getFullYear(); }
  if (conHora && String(f).length > 10) { s += ', ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0'); }
  return s;
}

function MiPortal_Vacio(icono, texto) {
  return '<div class="av-vacio"><i class="fas ' + icono + '"></i><p>' + texto + '</p></div>';
}

// Carga los datos de la pantalla y deja listo el selector de año
function MiPortal_Cargar(pantalla, url, pintar) {
  MP.pantalla = pantalla;
  $(document).off('.mp');
  $(document).on('change.mp', '#mp_anio', function () { MiPortal_Pedir(url, pintar); });
  MiPortal_Pedir(url, pintar);
}

function MiPortal_Pedir(url, pintar) {
  $.post(url, { anio: $('#mp_anio').val() || 0 }, null, 'json').done(function (d) {
    MP.datos = d;
    $('#mp_anio').toggleClass('d-none', d.anios.length < 2).html(d.anios.map(function (a) {
      return '<option value="' + a.id + '">Año ' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
    }).join('')).val(d.matricula ? d.matricula.anio_id : '');
    if (!d.matricula) { $('#mp_contenido').html(MiPortal_Vacio('fa-user-graduate', 'Aún no tienes matrícula registrada.')); return; }
    pintar();
  });
}

function MiPortal_Aula(m) {
  return MiPortal_Titulo(m.grado) + (m.seccion && m.seccion !== 'UNICO' ? ' ' + m.seccion : '') + ' · ' + MiPortal_Titulo(m.nivel) + ' · ' + m.anio;
}

// ---------- Mis tareas ----------
function MisTareas_Iniciar() {
  MP.filtro = 'pendientes';
  MP.curso = '';
  MiPortal_Cargar('tareas', '../controller/estudiante/controlador_mis_tareas.php', MisTareas_Pintar);
  $(document).on('click.mp', '.pe-filtro', function () { MP.filtro = $(this).data('filtro'); MisTareas_Lista(); });
  $(document).on('change.mp', '#mp_curso', function () { MP.curso = $(this).val(); MisTareas_Lista(); });
  $(document).on('click.mp', '.pe-entregar', function () {
    Entrega_Abrir(MP.datos.tareas[+$(this).data('i')], function () { MiPortal_Pedir('../controller/estudiante/controlador_mis_tareas.php', MisTareas_Pintar); });
  });
  $(document).on('click.mp', '.av-desc-mas', function () { $(this).prev('.av-desc').toggleClass('abierta'); $(this).text($(this).prev('.av-desc').hasClass('abierta') ? 'Ver menos' : 'Ver más'); });
}

// Grupo de cada tarea: pendientes, atrasadas (venció o la cerraron sin entregar), entregadas o calificadas
function MisTareas_Grupo(t, ahora) {
  if (t.envio_estado === 'CALIFICADO') { return 'calificadas'; }
  if (t.envio_estado === 'ENVIADO') { return 'entregadas'; }
  if (t.estado === 'FINALIZADO' || (t.fecha_entrega && t.fecha_entrega < ahora)) { return 'atrasadas'; }
  return 'pendientes';
}

function MisTareas_Pintar() {
  var d = MP.datos, cursos = {};
  d.tareas.forEach(function (t, i) { t._i = i; t._grupo = MisTareas_Grupo(t, d.ahora); cursos[t.asignatura] = 1; });
  var n = { pendientes: 0, atrasadas: 0, entregadas: 0, calificadas: 0 };
  d.tareas.forEach(function (t) { n[t._grupo]++; });
  // Si no hay nada por entregar pero sí atrasadas, se abre en atrasadas
  if (MP.filtro === 'pendientes' && !n.pendientes && n.atrasadas) { MP.filtro = 'atrasadas'; }
  var filtros = [['pendientes', 'Por entregar', 'fa-hourglass-half'], ['atrasadas', 'Atrasadas', 'fa-exclamation-circle'],
                 ['entregadas', 'Entregadas', 'fa-inbox'], ['calificadas', 'Calificadas', 'fa-star']];
  $('#mp_contenido').html(
    '<div class="pe-barra"><div class="pe-filtros" role="tablist">' + filtros.map(function (f) {
      return '<button type="button" class="pe-filtro pe-filtro-' + f[0] + '" data-filtro="' + f[0] + '" role="tab"><i class="fas ' + f[2] + '"></i>' + f[1] + '<b>' + n[f[0]] + '</b></button>';
    }).join('') + '</div><select class="form-control pe-curso" id="mp_curso" aria-label="Curso"><option value="">Todos los cursos</option>' +
    Object.keys(cursos).sort().map(function (c) { return '<option value="' + textoSeguro(c) + '">' + textoSeguro(MiPortal_Titulo(c)) + '</option>'; }).join('') +
    '</select></div><div id="mp_lista"></div>');
  $('#mp_curso').val(MP.curso);
  MisTareas_Lista();
}

function MisTareas_Lista() {
  var d = MP.datos;
  $('.pe-filtro').removeClass('activo').attr('aria-selected', 'false').filter('[data-filtro="' + MP.filtro + '"]').addClass('activo').attr('aria-selected', 'true');
  var lista = d.tareas.filter(function (t) { return t._grupo === MP.filtro && (!MP.curso || t.asignatura === MP.curso); });
  if (!lista.length) {
    var vacio = { pendientes: ['fa-check-circle', '¡Estás al día! No tienes tareas por entregar.'], atrasadas: ['fa-thumbs-up', 'No tienes tareas atrasadas.'],
                  entregadas: ['fa-inbox', 'No tienes entregas esperando nota.'], calificadas: ['fa-star', 'Aún no tienes tareas calificadas.'] }[MP.filtro];
    $('#mp_lista').html(MiPortal_Vacio(vacio[0], vacio[1]));
    return;
  }
  if (MP.filtro !== 'pendientes') { $('#mp_lista').html('<div class="av-flujo">' + lista.map(MisTareas_Tarjeta).join('') + '</div>'); return; }
  // Por entregar: de la más próxima a la más lejana, agrupadas por cuándo vencen
  lista.sort(function (a, b) { return (a.fecha_entrega || '9') < (b.fecha_entrega || '9') ? -1 : 1; });
  var hoy = d.ahora.slice(0, 10), manana = new Date(hoy + 'T00:00:00'), semana = new Date(hoy + 'T00:00:00');
  manana.setDate(manana.getDate() + 1); semana.setDate(semana.getDate() + 7);
  var iso = function (x) { return x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0'); };
  var grupos = [['Vence hoy', []], ['Vence mañana', []], ['Esta semana', []], ['Más adelante', []]];
  lista.forEach(function (t) {
    var f = (t.fecha_entrega || '9999').slice(0, 10);
    grupos[f === hoy ? 0 : f === iso(manana) ? 1 : f <= iso(semana) ? 2 : 3][1].push(t);
  });
  $('#mp_lista').html(grupos.filter(function (g) { return g[1].length; }).map(function (g) {
    return '<h3 class="pe-grupo">' + g[0] + ' <span>' + g[1].length + '</span></h3><div class="av-flujo">' + g[1].map(MisTareas_Tarjeta).join('') + '</div>';
  }).join(''));
}

function MisTareas_Tarjeta(t) {
  var color = Curso_Color(t.asignatura), url = '../controller/tareas/controlador_archivos_tarea.php?carpeta=';
  var acciones = (t.material ? '<a class="btn btn-outline-secondary btn-sm" href="' + url + encodeURIComponent(t.material) + '" target="_blank" rel="noopener"><i class="fas fa-paperclip mr-1"></i>Material' + (t.videos ? ' y videos' : '') + '</a>' : '') +
                 (t.envio_archivo ? '<a class="btn btn-outline-primary btn-sm" href="' + url + encodeURIComponent(t.envio_archivo) + '" target="_blank" rel="noopener"><i class="fas fa-file-alt mr-1"></i>Mi entrega</a>' : '');
  var puede = t.envio && t.estado !== 'FINALIZADO' && (t._grupo === 'pendientes' || t._grupo === 'atrasadas' || t._grupo === 'entregadas');
  if (puede) {
    acciones += '<button type="button" class="btn btn-success btn-sm pe-entregar" data-i="' + t._i + '"><i class="fas fa-upload mr-1"></i>' + (t.envio_estado === 'ENVIADO' ? 'Cambiar entrega' : 'Entregar') + '</button>';
  }
  var estado = '';
  if (t._grupo === 'calificadas') {
    estado = '<div class="pe-nota ' + (notaAprobada(t.calificacion) ? 'pe-nota-ok' : 'pe-nota-mal') + '" title="Nota">' + textoSeguro(t.calificacion) + '</div>';
  } else if (t._grupo === 'entregadas') {
    var tarde = t.fecha_envio && t.fecha_entrega && t.fecha_envio > t.fecha_entrega;
    estado = '<span class="av-chip av-chip-info">' + (tarde ? 'Entregada fuera de plazo' : 'Entregada') + ' · ' + MiPortal_Fecha(t.fecha_envio, true) + '</span>';
  } else if (t._grupo === 'atrasadas') {
    estado = '<span class="av-chip av-chip-mal">' + (t.estado === 'FINALIZADO' ? 'Cerrada sin entregar' : 'Venció: aún puedes entregar') + '</span>';
  }
  return '<article class="av-tarea pe-tarea" style="--av-color:' + color + '"><div class="av-tarea-cuerpo">' +
    '<p class="pe-curso-chip"><i style="background:' + color + '"></i>' + textoSeguro(MiPortal_Titulo(t.asignatura)) + ' <span>· ' + textoSeguro(MiPortal_Titulo(t.docente)) + '</span></p>' +
    '<h4>' + textoSeguro(MiPortal_Titulo(t.tema)) + '</h4>' +
    '<p class="av-tarea-fechas">Publicada ' + MiPortal_Fecha(t.publicada) + (t.fecha_entrega ? ' · <b>Vence ' + MiPortal_Fecha(t.fecha_entrega, true) + '</b>' : '') + '</p>' +
    (t.descripcion ? '<p class="av-desc">' + textoSeguro(t.descripcion) + '</p>' + (t.descripcion.length > 180 ? '<button type="button" class="btn btn-link btn-sm p-0 av-desc-mas">Ver más</button>' : '') : '') +
    (t._grupo === 'calificadas' && t.observacion ? '<p class="av-observacion"><i class="fas fa-comment-dots mr-1"></i>' + textoSeguro(t.observacion) + '</p>' : '') +
    '<div class="av-tarea-acciones">' + acciones + (t._grupo === 'calificadas' ? '' : estado) + '</div></div>' +
    (t._grupo === 'calificadas' ? estado : '') + '</article>';
}

// ---------- Mis notas ----------
function MisNotas_Iniciar() {
  MP.periodo = null;
  MiPortal_Cargar('notas', '../controller/estudiante/controlador_mis_notas.php', MisNotas_Pintar);
  $(document).on('click.mp', '.pe-periodo', function () { MP.periodo = $(this).data('periodo'); MisNotas_Pintar(); });
  $(document).on('click.mp', '.pe-curso-nota', function () { $(this).toggleClass('abierto'); });
  $(document).on('keydown.mp', '.pe-curso-nota', function (e) { if (e.key === 'Enter') { $(this).toggleClass('abierto'); } });
}

function MisNotas_Clase(n) {
  if (n === null || n === undefined || n === '') { return 'pe-nota-sin'; }
  return notaAprobada(n) ? 'pe-nota-ok' : 'pe-nota-mal';
}

function MisNotas_Pintar() {
  var d = MP.datos, L = d.libreta, m = d.matricula;
  if (!L) { $('#mp_contenido').html(MiPortal_Vacio('fa-star', 'Aún no hay notas registradas.')); return; }
  var yo = L.yo, periodos = L.periodos;
  if (MP.periodo === null) {
    // Por defecto el último periodo con notas; si no hay ninguna, el año completo
    MP.periodo = 'anio';
    periodos.forEach(function (p) {
      if (L.cursos.some(function (c) { return yo.cursos[c.id] && yo.cursos[c.id].periodos[p.id] !== null; })) { MP.periodo = p.id; }
    });
  }
  var a = yo.asistencia, totalAs = a.P + a.T + a.F + a.J;
  var aprobados = L.cursos.filter(function (c) { return yo.cursos[c.id] && yo.cursos[c.id].aprobado === true; }).length;
  var conNota = L.cursos.filter(function (c) { return yo.cursos[c.id] && yo.cursos[c.id].final !== null; }).length;
  var pdf = '../view/MPDF/REPORTE/libreta.php?matricula=' + encodeURIComponent(m.matricula) + (MP.periodo !== 'anio' ? '&periodo=' + encodeURIComponent(MP.periodo) : '');

  var resumen = '<section class="pe-resumen">' +
    '<div class="pe-dato pe-dato-grande"><span>Promedio del año</span><b class="' + MisNotas_Clase(yo.promedio) + '">' + (yo.promedio !== null ? textoSeguro(yo.promedio) : '—') + '</b></div>' +
    '<div class="pe-dato"><span>Puesto en el aula</span><b>' + (yo.puesto ? yo.puesto + '°' : '—') + '</b><small>' + (yo.puesto ? 'de ' + L.total_aula + ' estudiantes' : 'sin notas aún') + '</small></div>' +
    '<div class="pe-dato"><span>Cursos aprobados</span><b>' + aprobados + '<small> / ' + conNota + '</small></b><small>' + (yo.desaprobados ? yo.desaprobados + ' por mejorar' : 'con nota del año') + '</small></div>' +
    '<div class="pe-dato"><span>Asistencia</span><b>' + (totalAs ? Math.round((a.P + a.T) * 100 / totalAs) + '%' : '—') + '</b><small>' + a.F + ' faltas · ' + a.T + ' tardanzas</small></div>' +
    '<a class="btn btn-outline-primary pe-libreta" href="' + pdf + '" target="_blank" rel="noopener"><i class="fas fa-file-pdf mr-1"></i>Descargar libreta</a></section>';

  var pestanas = '<div class="pe-filtros pe-periodos">' + periodos.map(function (p) {
    return '<button type="button" class="pe-filtro pe-periodo' + (MP.periodo === p.id ? ' activo' : '') + '" data-periodo="' + p.id + '">' + textoSeguro(MiPortal_Titulo(p.nombre)) + '</button>';
  }).join('') + '<button type="button" class="pe-filtro pe-periodo' + (MP.periodo === 'anio' ? ' activo' : '') + '" data-periodo="anio">Año completo</button></div>';

  var tarjetas = L.cursos.filter(function (c) { return c.nombre.toUpperCase().indexOf('RECREO') !== 0; }).map(function (c) {
    var x = yo.cursos[c.id] || { periodos: {}, final: null };
    var nota = MP.periodo === 'anio' ? x.final : x.periodos[MP.periodo];
    var tira = periodos.map(function (p, i) {
      var v = x.periodos[p.id];
      return '<span class="' + MisNotas_Clase(v) + (MP.periodo === p.id ? ' actual' : '') + '" title="' + textoSeguro(p.nombre) + '"><small>B' + (i + 1) + '</small>' + (v !== null && v !== undefined ? textoSeguro(v) : '–') + '</span>';
    }).join('') + '<span class="' + MisNotas_Clase(x.final) + (MP.periodo === 'anio' ? ' actual' : '') + '" title="Nota del año"><small>Año</small>' + (x.final !== null ? textoSeguro(x.final) : '–') + '</span>';
    var det = (yo.detalle[c.id] || []).filter(function (r) { return MP.periodo === 'anio' || r.periodo === MP.periodo; });
    var nombreP = {};
    periodos.forEach(function (p) { nombreP[p.id] = MiPortal_Titulo(p.nombre); });
    var detalle = det.length ? '<table class="pe-criterios"><tbody>' + det.map(function (r) {
      return '<tr><td>' + (MP.periodo === 'anio' ? '<small class="text-muted">' + textoSeguro(nombreP[r.periodo] || '') + '</small><br>' : '') + textoSeguro(MiPortal_Titulo(r.criterio)) +
             (r.conclusion ? '<br><small class="text-muted">' + textoSeguro(r.conclusion) + '</small>' : '') + '</td><td class="' + MisNotas_Clase(r.nota) + '">' + textoSeguro(r.nota) + '</td></tr>';
    }).join('') + '</tbody></table>' : '<p class="text-muted small mb-0">Sin notas por criterio en este periodo.</p>';
    return '<article class="pe-curso-nota" tabindex="0" style="--av-color:' + Curso_Color(c.nombre) + '">' +
      '<header><h4>' + textoSeguro(MiPortal_Titulo(c.nombre)) + '</h4><div class="pe-nota ' + MisNotas_Clase(nota) + '">' + (nota !== null && nota !== undefined ? textoSeguro(nota) : '–') + '</div></header>' +
      '<div class="pe-tira">' + tira + '</div><div class="pe-detalle">' + detalle + '</div>' +
      '<footer><i class="fas fa-chevron-down"></i> Ver criterios</footer></article>';
  }).join('');

  $('#mp_contenido').html('<p class="pe-aula">' + textoSeguro(MiPortal_Aula(m)) + '</p>' + resumen + pestanas +
    '<div class="pe-notas">' + tarjetas + '</div><p class="small text-muted mt-3 mb-0">' + textoSeguro(L.escala || '') + '</p>');
}

// ---------- Mis pagos ----------
function MisPagos_Iniciar() {
  MiPortal_Cargar('pagos', '../controller/estudiante/controlador_mis_pagos.php', MisPagos_Pintar);
}

// Estado de una pensión: {clase, texto}
function MisPagos_Estado(p, hoy) {
  var saldo = +p.saldo_pension + +p.saldo_mora;
  if (+p.exonerada) { return { tipo: 'exonerada', clase: 'av-chip-info', texto: 'Exonerada' }; }
  if (saldo <= 0) { return { tipo: 'pagada', clase: 'av-chip-ok', texto: 'Pagada' }; }
  if (+p.vencida) { return { tipo: 'vencida', clase: 'av-chip-mal', texto: 'Vencida' }; }
  var en7 = new Date(hoy + 'T00:00:00'); en7.setDate(en7.getDate() + 7);
  if (p.vence && new Date(p.vence + 'T00:00:00') <= en7) { return { tipo: 'pronto', clase: 'av-chip-pend', texto: 'Vence pronto' }; }
  return { tipo: 'pendiente', clase: 'av-chip', texto: 'Por pagar' };
}

function MisPagos_Pintar() {
  var d = MP.datos, m = d.matricula;
  var pagado = d.recibos.reduce(function (s, r) { return s + r.total; }, 0), deuda = 0, vencido = 0, proxima = null;
  d.pensiones.forEach(function (p) {
    var e = MisPagos_Estado(p, d.hoy), saldo = +p.saldo_pension + +p.saldo_mora;
    p._e = e;
    if (e.tipo === 'pagada' || e.tipo === 'exonerada') { return; }
    deuda += saldo;
    if (e.tipo === 'vencida') { vencido += saldo; } else if (!proxima || p.vence < proxima.vence) { proxima = p; }
  });
  var kardex = '../view/MPDF/REPORTE/kardex.php?codigo=' + encodeURIComponent(m.matricula);
  var resumen = '<section class="pe-resumen">' +
    '<div class="pe-dato"><span>Pagado en el año</span><b class="pe-nota-ok">' + formatoMoneda(pagado) + '</b><small>' + d.recibos.length + (d.recibos.length === 1 ? ' pago' : ' pagos') + '</small></div>' +
    '<div class="pe-dato"><span>Vencido</span><b class="' + (vencido ? 'pe-nota-mal' : '') + '">' + formatoMoneda(vencido) + '</b><small>' + (vencido ? 'incluye la mora' : 'nada vencido') + '</small></div>' +
    '<div class="pe-dato"><span>Próximo pago</span><b>' + (proxima ? formatoMoneda(+proxima.saldo_pension + +proxima.saldo_mora) : '—') + '</b><small>' +
      (proxima ? textoSeguro(MiPortal_Titulo(proxima.mes)) + ' · vence ' + MiPortal_Fecha(proxima.vence) : (deuda ? '' : '¡Todo pagado!')) + '</small></div>' +
    '<div class="pe-dato"><span>Falta pagar en el año</span><b>' + formatoMoneda(deuda) + '</b><small>pensiones pendientes</small></div>' +
    '<a class="btn btn-outline-primary pe-libreta" href="' + kardex + '" target="_blank" rel="noopener"><i class="fas fa-file-pdf mr-1"></i>Kardex de pagos</a></section>';

  var meses = d.pensiones.length ? '<div class="pe-meses">' + d.pensiones.map(function (p) {
    var e = p._e, pag = +p.pagado_pension, avance = +p.precio ? Math.min(100, Math.round(pag * 100 / +p.precio)) : 0;
    return '<article class="pe-mes pe-mes-' + e.tipo + '"><header><h4>' + textoSeguro(MiPortal_Titulo(p.mes)) + '</h4><span class="av-chip ' + e.clase + '">' + e.texto + '</span></header>' +
      '<b>' + formatoMoneda(+p.precio) + '</b>' +
      (e.tipo === 'vencida' && +p.saldo_mora > 0 ? '<p class="pe-mora">+ ' + formatoMoneda(+p.saldo_mora) + ' de mora</p>' : '') +
      (pag > 0 && e.tipo !== 'pagada' ? '<p class="small mb-1">Pagado ' + formatoMoneda(pag) + '</p><span class="av-progreso"><i style="width:' + avance + '%;background:var(--tema-primario)"></i></span>' : '') +
      '<small>Vence ' + MiPortal_Fecha(p.vence) + '</small></article>';
  }).join('') + '</div>' : MiPortal_Vacio('fa-calendar', 'No hay pensiones registradas para este año.');

  var recibos = d.recibos.length ? '<div class="pe-recibos">' + d.recibos.map(function (r) {
    var url = r.id_pago ? '../view/MPDF/REPORTE/recibo.php?id=' + encodeURIComponent(r.id_pago)
                        : '../view/MPDF/REPORTE/boleta_pago.php?codigo=' + encodeURIComponent(m.matricula) + '&idpagopen=' + encodeURIComponent(r.id_pago_pension);
    return '<article class="pe-recibo"><span class="pe-recibo-icono"><i class="fas fa-receipt"></i></span><div class="pe-recibo-cuerpo">' +
      '<h4>' + MiPortal_Fecha(r.fecha) + (r.recibo ? ' <small>· Recibo ' + textoSeguro(r.recibo) + '</small>' : '') + '</h4>' +
      '<p>' + r.conceptos.map(function (c) { return '<span class="av-chip">' + textoSeguro(MiPortal_Titulo(c.concepto)) + ' · ' + formatoMoneda(c.monto) + '</span>'; }).join(' ') + '</p>' +
      (r.metodos ? '<small class="text-muted">' + textoSeguro(MiPortal_Titulo(r.metodos)) + '</small>' : '') + '</div>' +
      '<div class="pe-recibo-total"><b>' + formatoMoneda(r.total) + '</b><a class="btn btn-outline-secondary btn-sm" href="' + url + '" target="_blank" rel="noopener"><i class="fas fa-print mr-1"></i>Recibo</a></div></article>';
  }).join('') + '</div>' : MiPortal_Vacio('fa-receipt', 'Aún no hay pagos registrados este año.');

  $('#mp_contenido').html('<p class="pe-aula">' + textoSeguro(MiPortal_Aula(m)) + '</p>' + resumen +
    '<h3 class="pe-seccion">Pensiones del año</h3>' + meses + '<h3 class="pe-seccion">Pagos realizados</h3>' + recibos);
}

// ---------- Mi asistencia ----------
function MiAsistencia_Iniciar() {
  MiPortal_Cargar('asistencia', '../controller/estudiante/controlador_mi_asistencia.php', MiAsistencia_Pintar);
}

function MiAsistencia_Pintar() {
  var d = MP.datos, m = d.matricula, nom = d.nombres, marcas = {}, n = { P: 0, T: 0, F: 0, J: 0 };
  d.dias.forEach(function (x) { marcas[x.fecha] = x; n[x.codigo]++; });
  var total = n.P + n.T + n.F + n.J, pct = total ? Math.round((n.P + n.T) * 100 / total) : null;

  // Meses del año escolar hasta hoy, más cualquier mes que tenga registros
  var meses = {}, ini = new Date((m.fecha_inicio || d.hoy.slice(0, 4) + '-03-01') + 'T00:00:00'), fin = new Date((m.fecha_fin && m.fecha_fin < d.hoy ? m.fecha_fin : d.hoy) + 'T00:00:00');
  for (var x = new Date(ini.getFullYear(), ini.getMonth(), 1); x <= fin; x.setMonth(x.getMonth() + 1)) { meses[x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0')] = 1; }
  d.dias.forEach(function (r) { meses[r.fecha.slice(0, 7)] = 1; });
  var ultimo = d.sabado ? 6 : 5, cab = ['L', 'M', 'M', 'J', 'V', 'S'].slice(0, ultimo);

  var calendarios = Object.keys(meses).sort().reverse().map(function (ym) {
    var y = +ym.slice(0, 4), mo = +ym.slice(5, 7) - 1, dias = new Date(y, mo + 1, 0).getDate(), celdas = '', cuenta = { P: 0, T: 0, F: 0, J: 0 };
    var primero = (new Date(y, mo, 1).getDay() + 6) % 7;   // 0 = lunes
    for (var v = 0; v < primero && v < ultimo; v++) { celdas += '<span></span>'; }
    for (var dia = 1; dia <= dias; dia++) {
      var dw = (new Date(y, mo, dia).getDay() + 6) % 7;
      if (dw >= ultimo) { continue; }
      var f = ym + '-' + String(dia).padStart(2, '0'), r = marcas[f];
      if (r) { cuenta[r.codigo]++; }
      var tit = r ? nom[r.codigo] + (r.observacion ? ': ' + r.observacion : '') : (f > d.hoy ? '' : 'Sin registro');
      celdas += '<span class="pe-dia' + (r ? ' pe-dia-' + r.codigo : '') + (f > d.hoy ? ' futuro' : '') + (f === d.hoy ? ' hoy' : '') + (r && r.observacion ? ' con-obs' : '') + '" title="' + textoSeguro(tit) + '">' + dia + '</span>';
    }
    return '<article class="pe-mes-cal"><header><h4>' + MP_MESES[mo].charAt(0).toUpperCase() + MP_MESES[mo].slice(1) + ' ' + y + '</h4><small>' +
      (cuenta.F ? cuenta.F + ' F ' : '') + (cuenta.T ? cuenta.T + ' T ' : '') + (cuenta.J ? cuenta.J + ' J' : '') + '</small></header>' +
      '<div class="pe-semana-cab" style="--cols:' + ultimo + '">' + cab.map(function (c) { return '<span>' + c + '</span>'; }).join('') + '</div>' +
      '<div class="pe-dias" style="--cols:' + ultimo + '">' + celdas + '</div></article>';
  }).join('');

  var faltas = d.dias.filter(function (r) { return r.codigo !== 'P'; }).reverse();
  var lista = faltas.length ? '<ul class="pe-faltas">' + faltas.map(function (r) {
    return '<li><span class="ra-letra ra-' + r.codigo + '">' + r.codigo + '</span><b>' + MiPortal_Fecha(r.fecha) + '</b> ' + textoSeguro(nom[r.codigo]) +
           (r.observacion ? '<small> · ' + textoSeguro(r.observacion) + '</small>' : '') + '</li>';
  }).join('') + '</ul>' : '<p class="text-muted">Sin faltas ni tardanzas. ¡Muy bien!</p>';

  var anillo = '<div class="pe-anillo" style="--pct:' + (pct || 0) + '"><b>' + (pct !== null ? pct + '%' : '—') + '</b><small>asistencia</small></div>';
  $('#mp_contenido').html('<p class="pe-aula">' + textoSeguro(MiPortal_Aula(m)) + '</p>' +
    '<section class="pe-resumen pe-resumen-asis">' + anillo + ['P', 'T', 'F', 'J'].map(function (c) {
      return '<div class="pe-dato"><span><i class="ra-letra ra-' + c + '">' + c + '</i> ' + textoSeguro(nom[c]) + '</span><b>' + n[c] + '</b><small>' + (n[c] === 1 ? 'día' : 'días') + '</small></div>';
    }).join('') + '</section>' +
    '<div class="pe-asis"><div><h3 class="pe-seccion">Mes por mes</h3>' + (calendarios || MiPortal_Vacio('fa-calendar-check', 'Aún no hay asistencia registrada.')) + '</div>' +
    '<aside><h3 class="pe-seccion">Faltas y tardanzas</h3>' + lista + '</aside></div>');
}
