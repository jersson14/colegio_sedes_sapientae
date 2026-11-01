// Tareas y exámenes de mis cursos (docente): tarjetas por curso con su avance, y formularios para crear y editar.
// Guarda con los controladores de tareas y exámenes de siempre (revisan que el curso sea del docente).
var TD = { tipo: null, datos: null, curso: '', aula: '', filtro: 'abiertas' };
var TD_URL = '../controller/docente/controlador_trabajos.php';
// Rutas para guardar: las pasa cada vista (tareas o exámenes), así cada rol solo usa las suyas
var TD_GUARDAR = {};

function TD_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function TD_Aula(c) { return TD_Titulo(c.grado) + (c.seccion && c.seccion !== 'UNICO' ? ' ' + c.seccion : '') + ' · ' + TD_Titulo(c.nivel); }
function TD_Curso(id) { return TD.datos.cursos.filter(function (c) { return +c.id === +id; })[0] || {}; }

function TD_Fecha(f, conHora) {
  if (!f || String(f).indexOf('0000') === 0) { return ''; }
  var d = new Date(String(f).length === 10 ? f + 'T00:00:00' : String(f).replace(' ', 'T'));
  if (isNaN(d)) { return textoSeguro(f); }
  var s = d.toLocaleDateString('es', { weekday: 'short', day: 'numeric', month: 'short' }) + (d.getFullYear() !== new Date().getFullYear() ? ' ' + d.getFullYear() : '');
  return s + (conHora && String(f).length > 10 ? ', ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') : '');
}

// aaaa-mm-dd hh:mm:ss -> valor de un <input type="datetime-local">
function TD_ParaInput(f) { return f ? String(f).replace(' ', 'T').slice(0, 16) : ''; }

function TareasDocente_Iniciar(rutas) { TD_GUARDAR.tareas = rutas; TD_Iniciar('tareas'); }
function ExamenesDocente_Iniciar(rutas) { TD_GUARDAR.examenes = rutas; TD_Iniciar('examenes'); }

function TD_Iniciar(tipo) {
  TD.tipo = tipo;
  TD.curso = '';
  TD.filtro = tipo === 'tareas' ? 'abiertas' : 'proximos';
  $(document).off('.td');
  $(document).on('change.td', '#td_anio', function () { TD_Cargar(); });
  $(document).on('click.td', '.td-curso', function () { TD.curso = String($(this).data('curso')); TD_Pintar(); });
  $(document).on('change.td', '#td_aula', function () { TD.aula = $(this).val(); TD.curso = ''; TD_Pintar(); });
  $(document).on('click.td', '.td-filtro', function () { TD.filtro = $(this).data('filtro'); TD_Pintar(); });
  $(document).on('click.td', '#td_nuevo', function () { TD_Formulario(null); });
  $(document).on('click.td', '.td-editar', function () { TD_Formulario(TD.datos.items[+$(this).data('i')]); });
  $(document).on('click.td', '.td-estado', function () { TD_Estado(TD.datos.items[+$(this).data('i')]); });
  $(document).on('click.td', '.td-eliminar', function () { TD_Eliminar(TD.datos.items[+$(this).data('i')]); });
  $(document).on('click.td', '.td-revisar', function () { window.RT_CURSO_INICIAL = +$(this).data('curso'); cargar_contenido('contenido_principal', 'tareas/view_registro_tareas.php'); });
  $(document).on('click.td', '.av-desc-mas', function () { $(this).prev('.av-desc').toggleClass('abierta'); $(this).text($(this).prev('.av-desc').hasClass('abierta') ? 'Ver menos' : 'Ver más'); });
  TD_Cargar();
}

function TD_Cargar() {
  $.post(TD_URL, { accion: TD.tipo, anio: $('#td_anio').val() || 0 }, null, 'json').done(function (d) {
    TD.datos = d;
    $('#td_anio').html(d.anios.map(function (a) {
      return '<option value="' + a.id + '">Año ' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
    }).join('')).val(d.anio);
    d.items.forEach(function (x, i) { x._i = i; x._grupo = TD_Grupo(x); });
    TD_Pintar();
  });
}

// Grupo de cada tarea (abiertas, por revisar, cerradas) o examen (próximos, pasados, realizados)
function TD_Grupo(x) {
  var ahora = TD.datos.ahora;
  if (TD.tipo === 'tareas') {
    if (x.estado === 'FINALIZADO') { return 'cerradas'; }
    return +x.entregadas > 0 ? 'revisar' : 'abiertas';
  }
  if (x.estado === 'REALIZADO') { return 'realizados'; }
  return x.fecha && x.fecha < ahora ? 'pasados' : 'proximos';
}

function TD_Pintar() {
  var d = TD.datos, tareas = TD.tipo === 'tareas';
  if (!d.cursos.length) { $('#td_contenido').html('<div class="av-vacio"><i class="fas fa-chalkboard"></i><p>No tiene cursos asignados en este año escolar.</p></div>'); return; }
  // Con muchos cursos (dirección) se elige primero el aula
  var aulas = Cursos_Aulas(d.cursos), porAula = aulas.length > 1 && d.cursos.length > 8;
  if (porAula && !aulas.some(function (a) { return a.clave === TD.aula; })) { TD.aula = aulas[0].clave; }
  var cursosAula = porAula ? d.cursos.filter(function (c) { return Curso_Aula_Clave(c) === TD.aula; }) : d.cursos;
  var idsAula = cursosAula.map(function (c) { return +c.id; });
  var deAula = d.items.filter(function (x) { return idsAula.indexOf(+x.curso) !== -1; });
  var enCurso = deAula.filter(function (x) { return !TD.curso || String(x.curso) === TD.curso; });
  var filtros = tareas ? [['abiertas', 'Abiertas', 'fa-hourglass-half'], ['revisar', 'Por revisar', 'fa-inbox'], ['cerradas', 'Cerradas', 'fa-lock']]
                       : [['proximos', 'Próximos', 'fa-calendar-day'], ['pasados', 'Sin marcar como realizados', 'fa-exclamation-circle'], ['realizados', 'Realizados', 'fa-check']];
  var n = {};
  filtros.forEach(function (f) { n[f[0]] = enCurso.filter(function (x) { return x._grupo === f[0]; }).length; });
  var cursos = '<div class="rn-cursos">' + (porAula ? '<select class="form-control rn-aula" id="td_aula" aria-label="Grado y sección">' + aulas.map(function (a) {
      return '<option value="' + textoSeguro(a.clave) + '"' + (a.clave === TD.aula ? ' selected' : '') + '>' + textoSeguro(a.nombre) + '</option>';
    }).join('') + '</select>' : '') + '<button type="button" class="rn-curso td-curso' + (TD.curso === '' ? ' activo' : '') + '" data-curso="" style="--av-color:var(--tema-primario)"><b>' +
    (porAula ? 'Todos los cursos' : 'Todos mis cursos') + '</b><span>' + deAula.length + (tareas ? ' tareas' : ' exámenes') + '</span></button>' + cursosAula.map(function (c) {
      var k = d.items.filter(function (x) { return +x.curso === +c.id; }).length;
      return '<button type="button" class="rn-curso td-curso' + (TD.curso === String(c.id) ? ' activo' : '') + '" data-curso="' + c.id + '" style="--av-color:' + Curso_Color(c.asignatura) + '">' +
        '<b>' + textoSeguro(TD_Titulo(c.asignatura)) + '</b><span>' + textoSeguro(TD_Aula(c)) + ' · ' + k + '</span></button>';
    }).join('') + '</div>';
  var barra = '<div class="pe-barra"><div class="pe-filtros">' + filtros.map(function (f) {
    return '<button type="button" class="pe-filtro td-filtro' + (TD.filtro === f[0] ? ' activo' : '') + '" data-filtro="' + f[0] + '"><i class="fas ' + f[2] + '"></i>' + f[1] + '<b>' + n[f[0]] + '</b></button>';
  }).join('') + '</div><button type="button" class="btn btn-success" id="td_nuevo"><i class="fas fa-plus mr-1"></i>' + (tareas ? 'Nueva tarea' : 'Programar examen') + '</button></div>';
  var lista = enCurso.filter(function (x) { return x._grupo === TD.filtro; });
  if (TD.tipo === 'examenes' && TD.filtro === 'proximos') { lista.sort(function (a, b) { return a.fecha < b.fecha ? -1 : 1; }); }
  var vacio = { abiertas: 'No hay tareas abiertas.', revisar: '¡Nada por revisar!', cerradas: 'No hay tareas cerradas.', proximos: 'No hay exámenes programados.',
                pasados: 'Todos los exámenes pasados están marcados como realizados.', realizados: 'Aún no hay exámenes realizados.' }[TD.filtro];
  $('#td_contenido').html(cursos + barra + (lista.length ? '<div class="av-flujo">' + lista.map(tareas ? TD_Tarea : TD_Examen).join('') + '</div>'
    : '<div class="av-vacio"><i class="fas ' + (tareas ? 'fa-clipboard-check' : 'fa-file-signature') + '"></i><p>' + vacio + '</p></div>'));
}

function TD_Cabecera(x, c) {
  var color = Curso_Color(c.asignatura);
  return '<p class="pe-curso-chip"><i style="background:' + color + '"></i>' + textoSeguro(TD_Titulo(c.asignatura)) + ' <span>· ' + textoSeguro(TD_Aula(c)) + '</span></p>' +
    '<h4>' + textoSeguro(TD_Titulo(x.tema)) + '</h4>';
}

function TD_Descripcion(x) {
  return x.descripcion ? '<p class="av-desc">' + textoSeguro(x.descripcion) + '</p>' + (x.descripcion.length > 180 ? '<button type="button" class="btn btn-link btn-sm p-0 av-desc-mas">Ver más</button>' : '') : '';
}

function TD_Tarea(t) {
  var c = TD_Curso(t.curso), color = Curso_Color(c.asignatura), total = +t.asignadas || 0, hechas = (+t.entregadas || 0) + (+t.calificadas || 0);
  var vencida = t.fecha_entrega && t.fecha_entrega < TD.datos.ahora;
  var acciones = (t.material ? '<a class="btn btn-outline-secondary btn-sm" href="../controller/tareas/controlador_archivos_tarea.php?carpeta=' + encodeURIComponent(t.material) + '" target="_blank" rel="noopener"><i class="fas fa-paperclip mr-1"></i>Material</a>' : '') +
    '<button type="button" class="btn btn-primary btn-sm td-revisar" data-curso="' + t.curso + '"><i class="fas fa-check-double mr-1"></i>Revisar y calificar' + (+t.entregadas ? ' (' + t.entregadas + ')' : '') + '</button>' +
    '<button type="button" class="btn btn-outline-secondary btn-sm td-editar" data-i="' + t._i + '"><i class="fas fa-edit mr-1"></i>Editar</button>' +
    '<button type="button" class="btn btn-outline-secondary btn-sm td-estado" data-i="' + t._i + '"><i class="fas ' + (t.estado === 'FINALIZADO' ? 'fa-lock-open' : 'fa-lock') + ' mr-1"></i>' + (t.estado === 'FINALIZADO' ? 'Reabrir' : 'Cerrar') + '</button>' +
    (hechas === 0 ? '<button type="button" class="btn btn-outline-danger btn-sm td-eliminar" data-i="' + t._i + '"><i class="fas fa-trash mr-1"></i>Eliminar</button>' : '');
  return '<article class="av-tarea pe-tarea" style="--av-color:' + color + '"><div class="av-tarea-cuerpo">' + TD_Cabecera(t, c) +
    '<p class="av-tarea-fechas">Publicada ' + TD_Fecha(t.publicada) + (t.fecha_entrega ? ' · <b' + (vencida && t.estado !== 'FINALIZADO' ? ' class="text-danger"' : '') + '>Vence ' + TD_Fecha(t.fecha_entrega, true) + '</b>' : '') + '</p>' +
    TD_Descripcion(t) +
    '<div class="av-tarea-pie"><span class="av-progreso" title="Entregaron ' + hechas + ' de ' + total + '"><i style="width:' + (total ? Math.round(hechas * 100 / total) : 0) + '%;background:' + color + '"></i></span>' +
    '<span class="small text-muted">' + hechas + ' de ' + total + ' entregaron · ' + (+t.calificadas || 0) + ' calificadas' + (+t.entregadas ? ' · <b>' + t.entregadas + ' por revisar</b>' : '') + '</span></div>' +
    '<div class="av-tarea-acciones">' + acciones + '</div></div></article>';
}

function TD_Examen(x) {
  var c = TD_Curso(x.curso), realizado = x.estado === 'REALIZADO';
  var acciones = '<button type="button" class="btn btn-outline-secondary btn-sm td-editar" data-i="' + x._i + '"><i class="fas fa-edit mr-1"></i>Editar</button>' +
    '<button type="button" class="btn btn-' + (realizado ? 'outline-secondary' : 'success') + ' btn-sm td-estado" data-i="' + x._i + '"><i class="fas ' + (realizado ? 'fa-undo' : 'fa-check') + ' mr-1"></i>' +
    (realizado ? 'Marcar como pendiente' : 'Marcar como realizado') + '</button>' +
    '<button type="button" class="btn btn-outline-danger btn-sm td-eliminar" data-i="' + x._i + '"><i class="fas fa-trash mr-1"></i>Eliminar</button>';
  var d = x.fecha ? new Date(String(x.fecha).replace(' ', 'T')) : null;
  var fecha = d && !isNaN(d) ? '<div class="td-fecha"><b>' + d.getDate() + '</b><span>' + d.toLocaleDateString('es', { month: 'short' }) + '</span><small>' +
    String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') + '</small></div>' : '';
  return '<article class="av-tarea pe-tarea" style="--av-color:' + Curso_Color(c.asignatura) + '">' + fecha + '<div class="av-tarea-cuerpo">' + TD_Cabecera(x, c) +
    '<p class="av-tarea-fechas">' + TD_Fecha(x.fecha, true) + (realizado ? ' · <span class="av-chip av-chip-ok">Realizado</span>' : '') + '</p>' +
    TD_Descripcion(x) + '<div class="av-tarea-acciones">' + acciones + '</div></div></article>';
}

// Formulario de tarea o examen (nuevo o editar)
function TD_Formulario(x) {
  var tareas = TD.tipo === 'tareas', d = TD.datos;
  var cursoSel = x ? x.curso : (TD.curso || (d.cursos[0] ? d.cursos[0].id : ''));
  var html = '<form id="td_form" class="text-left">' +
    '<div class="form-group"><label for="td_f_curso">Curso <b class="text-danger">(*)</b></label><select class="form-control" id="td_f_curso">' +
    d.cursos.map(function (c) { return '<option value="' + c.id + '"' + (+c.id === +cursoSel ? ' selected' : '') + '>' + textoSeguro(TD_Titulo(c.asignatura) + ' — ' + TD_Aula(c)) + '</option>'; }).join('') + '</select></div>' +
    '<div class="form-group"><label for="td_f_tema">Tema <b class="text-danger">(*)</b></label><input class="form-control" id="td_f_tema" maxlength="255" value="' + textoSeguro(x ? x.tema : '') + '"></div>' +
    '<div class="form-group"><label for="td_f_desc">' + (tareas ? 'Indicaciones' : 'Descripción u observación') + '</label><textarea class="form-control" id="td_f_desc" rows="3">' + textoSeguro(x ? x.descripcion || '' : '') + '</textarea></div>' +
    '<div class="form-group"><label for="td_f_fecha">' + (tareas ? 'Fecha y hora de entrega' : 'Fecha y hora del examen') + ' <b class="text-danger">(*)</b></label>' +
    '<input type="datetime-local" class="form-control" id="td_f_fecha" value="' + TD_ParaInput(x ? (tareas ? x.fecha_entrega : x.fecha) : '') + '"></div>' +
    (tareas ? '<div id="td_f_actual"></div><div class="form-group"><label for="td_f_archivos">' + (x ? 'Agregar archivos' : 'Material (archivos)') + '</label>' +
      '<input type="file" class="form-control-file" id="td_f_archivos" multiple><ul class="et-lista" id="td_f_lista"></ul></div>' +
      '<div class="form-group mb-0"><label for="td_f_enlaces">Enlaces de video <small class="text-muted">(uno por línea, YouTube o Drive)</small></label>' +
      '<textarea class="form-control" id="td_f_enlaces" rows="2" placeholder="https://youtu.be/..."></textarea>' +
      '<small class="text-muted">Adjunte al menos un archivo o un enlace.</small></div>' : '') + '</form>';
  Swal.fire({
    title: x ? (tareas ? 'Editar tarea' : 'Editar examen') : (tareas ? 'Nueva tarea' : 'Programar examen'), html: html, width: 640,
    showCancelButton: true, confirmButtonText: '<i class="fas fa-save mr-1"></i>Guardar', cancelButtonText: 'Cancelar', focusConfirm: false,
    didOpen: function () {
      if (!tareas) { return; }
      MaterialTarea.conectar('td_f_archivos', 'td_f_lista');
      if (x) { MaterialTarea.cargarActual(x.material, 'td_f_actual', 'td_f_enlaces'); }
    },
    preConfirm: function () {
      var tema = $.trim($('#td_f_tema').val()), fecha = $('#td_f_fecha').val();
      if (!tema) { Swal.showValidationMessage('Escriba el tema'); return false; }
      if (!fecha) { Swal.showValidationMessage('Elija la fecha y hora'); return false; }
      if (!x && fecha < new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16)) {
        Swal.showValidationMessage('La fecha ya pasó: elija una fecha futura'); return false;
      }
      var fd = new FormData();
      fd.append('asig', $('#td_f_curso').val());
      fd.append('tema', tema);
      fd.append('descrip', $.trim($('#td_f_desc').val()));
      fd.append('fecha', fecha.replace('T', ' '));
      if (x) { fd.append('id', x.id); }
      if (tareas) {
        var enlaces = MaterialTarea.enlaces('td_f_enlaces');
        if (enlaces === null) { return false; }
        fd.append('enlaces', enlaces);
        var archivos = $('#td_f_archivos')[0].files;
        for (var i = 0; i < archivos.length; i++) { fd.append('archivos[]', archivos[i]); }
        if (x) { fd.append('quitar', MaterialTarea.quitar('td_f_actual')); }
      }
      return fd;
    }
  }).then(function (r) { if (r.isConfirmed) { TD_Enviar(x, r.value); } });
}

function TD_Enviar(x, fd) {
  var tareas = TD.tipo === 'tareas';
  var url = TD_GUARDAR[TD.tipo][x ? 'editar' : 'nuevo'];
  $.ajax({ url: url, type: 'POST', data: fd, contentType: false, processData: false, xhr: tareas ? MaterialTarea.xhrConProgreso : undefined }).done(function (r) {
    if (String(r).trim() === '1') {
      Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: x ? 'Cambios guardados' : (tareas ? 'Tarea publicada' : 'Examen programado') });
      TD_Cargar();
    } else {
      Swal.fire('No se pudo guardar', String(r).trim() === '2' ? 'Ya existe ' + (tareas ? 'una tarea' : 'un examen') + ' con ese tema en el curso.' : 'Revise los datos e intente de nuevo.', 'warning');
    }
  });
}

function TD_Estado(x) {
  var tareas = TD.tipo === 'tareas';
  var nuevo = tareas ? (x.estado === 'FINALIZADO' ? 'PENDIENTE' : 'FINALIZADO') : (x.estado === 'REALIZADO' ? 'PENDIENTE' : 'REALIZADO');
  var texto = tareas ? (nuevo === 'FINALIZADO' ? 'Al cerrarla, los estudiantes ya no podrán entregar ni cambiar su entrega.' : 'Los estudiantes podrán volver a entregar.') : '';
  var seguir = function () {
    $.post(TD_GUARDAR[TD.tipo].estado, { id: x.id, estatus: nuevo }).done(function () {
      Swal.fire({ toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, icon: 'success', title: 'Listo' });
      TD_Cargar();
    });
  };
  if (!texto) { return seguir(); }
  Swal.fire({ title: nuevo === 'FINALIZADO' ? '¿Cerrar la tarea?' : '¿Reabrir la tarea?', text: texto, icon: 'question', showCancelButton: true,
              confirmButtonText: nuevo === 'FINALIZADO' ? 'Sí, cerrar' : 'Sí, reabrir', cancelButtonText: 'Cancelar' }).then(function (r) { if (r.isConfirmed) { seguir(); } });
}

function TD_Eliminar(x) {
  var tareas = TD.tipo === 'tareas';
  Swal.fire({ title: tareas ? '¿Eliminar la tarea?' : '¿Eliminar el examen?', html: '<b>' + textoSeguro(TD_Titulo(x.tema)) + '</b><br>No se puede deshacer.', icon: 'warning',
              showCancelButton: true, confirmButtonText: 'Sí, eliminar', confirmButtonColor: '#d33', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) { return; }
    $.post(TD_GUARDAR[TD.tipo].eliminar, { id: x.id }).done(function () {
      Swal.fire({ toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, icon: 'success', title: 'Eliminado' });
      TD_Cargar();
    });
  });
}
