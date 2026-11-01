// Calendario de horarios: las clases semanales de un aula (o de un docente) en vista de semana, mes o lista.
// Usa FullCalendar 5 de la plantilla; se carga la primera vez que se abre la pantalla.
var HC = { datos: null, cal: null };
var HC_URL = '../controller/horarios/controlador_calendario.php';
var HC_DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

function Calendario_Libreria(listo) {
  if (window.FullCalendar && FullCalendar.globalLocales && FullCalendar.globalLocales.length) { return listo(); }
  if (!$('link[href*="fullcalendar/main.min.css"]').length) {
    $('head').append('<link rel="stylesheet" href="../plantilla/plugins/fullcalendar/main.min.css">');
  }
  $.ajax({ url: '../plantilla/plugins/fullcalendar/main.min.js', dataType: 'script', cache: true })
    .then(function () { return $.ajax({ url: '../plantilla/plugins/fullcalendar/locales/es.js', dataType: 'script', cache: true }); })
    .then(listo);
}

function Calendario_Iniciar() {
  $(document).off('.hc');
  $(document).on('change.hc', '#hc_anio', function () { $('#hc_aula').val(''); Calendario_Cargar(); });
  $(document).on('change.hc', '#hc_aula', Calendario_Cargar);
  $(document).on('click.hc', '#hc_imprimir', function () { window.print(); });
  Calendario_Libreria(function () { Calendario_Cargar(); });
}

function Calendario_Cargar() {
  $.post(HC_URL, { anio: $('#hc_anio').val() || 0, aula: $('#hc_aula').val() || 0 }, null, 'json').done(function (d) {
    HC.datos = d;
    Calendario_Filtros();
    Calendario_Pintar();
  });
}

function Calendario_NombreAula(a) {
  return a.nivel + ' · ' + a.grado + (a.seccion && a.seccion !== 'UNICO' ? ' ' + a.seccion : '');
}

function Calendario_Filtros() {
  var d = HC.datos, rol = $('#txtprincipalrol').val();
  $('#hc_anio').html(d.anios.map(function (a) {
    return '<option value="' + a.id + '">' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
  }).join('')).val(d.anio.id);
  var op = (rol === 'DOCENTE' ? '<option value="0">Mis clases (todas mis aulas)</option>' : '') + d.aulas.map(function (a) {
    return '<option value="' + a.id + '">' + textoSeguro(Calendario_NombreAula(a)) + '</option>';
  }).join('');
  $('#hc_aula').html(op || '<option value="">Sin aulas en este año</option>').val(d.modo === 'mio' ? '0' : String(d.aula));
  $('#hc_aula').prop('disabled', rol === 'ESTUDIANTE');
}

// Suma un día a una fecha aaaa-mm-dd (el fin de la repetición en FullCalendar no se incluye)
function Calendario_DiaSiguiente(f) {
  var d = new Date(f + 'T00:00:00');
  d.setDate(d.getDate() + 1);
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function Calendario_Pintar() {
  var d = HC.datos, mio = d.modo === 'mio';
  var eventos = d.clases.map(function (c) {
    return {
      title: c.asignatura, daysOfWeek: [c.dia], startTime: c.inicio, endTime: c.fin,
      startRecur: d.anio.fecha_inicio || undefined, endRecur: d.anio.fecha_fin ? Calendario_DiaSiguiente(d.anio.fecha_fin) : undefined,
      backgroundColor: Curso_Color(c.asignatura), borderColor: Curso_Color(c.asignatura),
      extendedProps: { clase: c, detalle: mio ? Calendario_NombreAula(c) : c.docente }
    };
  });
  var conSabado = d.sabado || d.clases.some(function (c) { return c.dia === 6; });
  var inicios = d.clases.map(function (c) { return c.inicio; }).sort(), fines = d.clases.map(function (c) { return c.fin; }).sort();
  var hoyDentro = (!d.anio.fecha_inicio || d.hoy >= d.anio.fecha_inicio) && (!d.anio.fecha_fin || d.hoy <= d.anio.fecha_fin);
  var celular = window.innerWidth < 768;

  $('#hc_vacio').toggleClass('d-none', d.clases.length > 0).html(d.clases.length ? '' : (
    $('#txtprincipalrol').val() === 'ESTUDIANTE' && !d.aulas.length ? 'No tiene matrícula en este año escolar.' :
    'Esta aula aún no tiene horario. La dirección lo arma en <b>Cursos y horarios → Horarios</b>.'));

  if (HC.cal) { HC.cal.destroy(); }
  HC.cal = new FullCalendar.Calendar(document.getElementById('hc_calendario'), {
    locale: 'es',
    initialView: celular ? 'listWeek' : 'timeGridWeek',
    initialDate: hoyDentro ? d.hoy : (d.anio.fecha_inicio || d.hoy),
    headerToolbar: celular ? { left: 'prev,next', center: 'title', right: 'listWeek,dayGridMonth' }
                           : { left: 'prev,next today', center: 'title', right: 'timeGridWeek,dayGridMonth,listWeek' },
    buttonText: { today: 'Hoy', week: 'Semana', month: 'Mes', list: 'Lista' },
    hiddenDays: conSabado ? [0] : [0, 6],
    allDaySlot: false,
    slotMinTime: inicios.length ? inicios[0].slice(0, 2) + ':00:00' : '07:00:00',
    slotMaxTime: fines.length ? (Math.min(23, +fines[fines.length - 1].slice(0, 2) + 1)) + ':00:00' : '14:00:00',
    slotDuration: '00:15:00', slotLabelInterval: '01:00', expandRows: true, height: 'auto',
    dayMaxEvents: 4, nowIndicator: true, eventDisplay: 'block',
    validRange: d.anio.fecha_inicio && d.anio.fecha_fin ? { start: d.anio.fecha_inicio, end: Calendario_DiaSiguiente(d.anio.fecha_fin) } : undefined,
    events: eventos,
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    views: {
      timeGridWeek: { eventContent: function (arg) {
        return { html: '<div class="hc-evento"><b>' + textoSeguro(arg.event.title) + '</b><span>' + textoSeguro(arg.timeText) + '</span><small>' +
                       textoSeguro(Legibilidad_Titulo(arg.event.extendedProps.detalle)) + '</small></div>' };
      } },
      // En el mes, una línea por clase: hora de inicio y curso
      dayGridMonth: { eventContent: function (arg) {
        return { html: '<div class="hc-mes" style="border-left-color:' + arg.event.backgroundColor + '"><b>' + textoSeguro(arg.event.extendedProps.clase.inicio) + '</b> ' +
                       textoSeguro(Legibilidad_Titulo(arg.event.title)) + '</div>' };
      }, eventDisplay: 'list-item' }
    },
    eventClick: function (info) { Calendario_Detalle(info.event.extendedProps.clase); }
  });
  HC.cal.render();
  Calendario_Leyenda();
}

// Nombre en formato título (como en las tablas) si legibilidad.js está cargado
function Legibilidad_Titulo(t) {
  return window.Legibilidad && Legibilidad.titulo ? Legibilidad.titulo(t || '') : (t || '');
}

function Calendario_Leyenda() {
  var vistos = {}, html = '';
  HC.datos.clases.forEach(function (c) {
    var k = c.asignatura + '|' + (HC.datos.modo === 'mio' ? c.aula : c.docente);
    if (vistos[k]) { vistos[k].horas++; return; }
    vistos[k] = { c: c, horas: 1 };
  });
  Object.keys(vistos).forEach(function (k) {
    var c = vistos[k].c, n = vistos[k].horas;
    html += '<span class="hc-curso"><i style="background:' + Curso_Color(c.asignatura) + '"></i><b>' + textoSeguro(c.asignatura) + '</b> · ' +
            textoSeguro(Legibilidad_Titulo(HC.datos.modo === 'mio' ? Calendario_NombreAula(c) : c.docente)) + ' · ' + n + (n === 1 ? ' hora' : ' horas') + ' a la semana</span>';
  });
  $('#hc_leyenda').html(html);
}

function Calendario_Detalle(c) {
  var rol = $('#txtprincipalrol').val(), aulaVirtual = rol === 'ESTUDIANTE' || rol === 'DOCENTE' || rol === 'ADMINISTRADOR';
  Swal.fire({
    title: textoSeguro(c.asignatura),
    html: '<div class="text-left"><p class="mb-1"><i class="fas fa-calendar-day mr-2 text-muted"></i>' + HC_DIAS[c.dia] + ', ' + textoSeguro(c.inicio) + ' a ' + textoSeguro(c.fin) + '</p>' +
          '<p class="mb-1"><i class="fas fa-chalkboard-teacher mr-2 text-muted"></i>' + textoSeguro(Legibilidad_Titulo(c.docente)) + '</p>' +
          '<p class="mb-0"><i class="fas fa-door-open mr-2 text-muted"></i>' + textoSeguro(Calendario_NombreAula(c)) + '</p></div>',
    showCancelButton: aulaVirtual, cancelButtonText: 'Cerrar', confirmButtonText: aulaVirtual ? '<i class="fas fa-chalkboard mr-1"></i>Abrir en el aula virtual' : 'Cerrar'
  }).then(function (r) {
    if (r.isConfirmed && aulaVirtual) { AulaVirtual_Ir(c.curso); }
  });
}
