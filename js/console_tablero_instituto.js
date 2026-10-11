// Tablero de inicio de un instituto (administrador y secretaría académica), dibujado
// en #tablero_instituto con los datos del controlador del tablero (institución).
function escTablero(texto) {
  return $('<div>').text(texto == null ? '' : String(texto)).html();
}

function cifraTablero(titulo, valor, detalle, color, icono) {
  return '<div class="col-lg-3 col-6"><div class="small-box bg-' + color + '"><div class="inner"><h3>' + escTablero(valor) + '</h3><p>'
    + escTablero(titulo) + (detalle ? '<br><small>' + escTablero(detalle) + '</small>' : '') + '</p></div><div class="icon"><i class="fas ' + icono + '"></i></div></div></div>';
}

function listaTablero(titulo, icono, filas, vacio) {
  return '<div class="card card-outline card-secondary h-100"><div class="card-header"><h3 class="card-title"><i class="fas ' + icono + ' mr-1"></i> ' + escTablero(titulo)
    + '</h3></div><div class="card-body p-0">' + (filas.length ? '<ul class="list-group list-group-flush">' + filas.join('') + '</ul>'
    : '<p class="text-muted p-3 mb-0">' + escTablero(vacio) + '</p>') + '</div></div>';
}

function cargarTableroInstituto() {
  $.ajax({ url: '../controller/institucion/controlador_tablero_instituto.php', type: 'GET', dataType: 'json' }).done(function (t) {
    var periodo = t.periodo ? t.periodo.nombre : 'sin periodo registrado';
    var cifras = cifraTablero('Matriculados en el semestre', t.matriculados, t.estudiantes_activos + ' estudiantes activos', 'info', 'fa-user-graduate')
      + cifraTablero('Unidades en curso', t.unidades_en_curso, t.unidades + ' unidades en ' + t.programas + ' programa(s)', 'primary', 'fa-book-open')
      + cifraTablero('Aprobación del semestre', t.aprobacion == null ? '—' : t.aprobacion + ' %', 'de las unidades ya calificadas', 'success', 'fa-chart-line')
      + cifraTablero('Notas por registrar', t.por_calificar, 'matrículas en curso sin nota', 'warning', 'fa-pen')
      + cifraTablero('Cargos pendientes', t.cargos, 'unidades desaprobadas por volver a llevar', 'danger', 'fa-exclamation-triangle')
      + cifraTablero('Créditos por estudiante', t.creditos_promedio, 'promedio matriculado en el semestre', 'secondary', 'fa-layer-group')
      + cifraTablero('Docentes activos', t.docentes_activos, '', 'teal', 'fa-chalkboard-teacher')
      + (t.ingresos_del_mes != null ? cifraTablero('Ingresos del mes', 'S/ ' + parseFloat(t.ingresos_del_mes).toFixed(2), '', 'olive', 'fa-coins') : '');
    var maximo = Math.max.apply(null, t.por_programa.map(function (p) { return parseInt(p.matriculados, 10); }).concat([1]));
    var programas = t.por_programa.map(function (p) {
      var ancho = Math.round(100 * parseInt(p.matriculados, 10) / maximo);
      return '<li class="list-group-item"><div class="d-flex justify-content-between"><span>' + escTablero(p.programa) + '</span><b>' + escTablero(p.matriculados)
        + '</b></div><div class="progress progress-xs mt-1"><div class="progress-bar bg-info" style="width:' + ancho + '%"></div></div></li>';
    });
    var sinDocente = t.sin_docente.map(function (u) {
      return '<li class="list-group-item d-flex justify-content-between"><span>' + escTablero(u.codigo + ' · ' + u.nombre) + '</span><span class="badge badge-warning">'
        + escTablero(u.matriculados) + ' matriculados</span></li>';
    });
    var menor = t.menor_aprobacion.map(function (u) {
      var color = parseFloat(u.aprobacion) < 60 ? 'danger' : (parseFloat(u.aprobacion) < 80 ? 'warning' : 'success');
      return '<li class="list-group-item d-flex justify-content-between"><span>' + escTablero(u.codigo + ' · ' + u.nombre) + '</span><span class="badge badge-' + color + '">'
        + escTablero(u.aprobacion) + ' % (' + escTablero(u.desaprobados) + ' desaprobados)</span></li>';
    });
    var accesos = [['plan_estudios/view_plan_estudios.php', 'fa-sitemap', 'Plan de estudios'], ['matricula_unidades/view_matricula_unidades.php', 'fa-user-plus', 'Matricular'],
      ['plan_estudios/view_docentes_unidad.php', 'fa-chalkboard-teacher', 'Docentes por unidad']].map(function (a) {
      return '<button type="button" class="btn btn-outline-primary mr-2 mb-2 acceso-tablero" data-vista="' + a[0] + '"><i class="fas ' + a[1] + ' mr-1"></i>' + a[2] + '</button>';
    }).join('');
    $('#tablero_instituto').html(
      '<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 pt-3"><h4 class="mb-2"><i class="fas fa-university mr-2"></i>Semestre en curso: <b>'
      + escTablero(periodo) + '</b></h4><div>' + accesos + '</div></div>'
      + '<div class="row">' + cifras + '</div>'
      + '<div class="row"><div class="col-lg-4 mb-3">' + listaTablero('Matriculados por programa', 'fa-sitemap', programas, 'Aún no hay programas de estudio.') + '</div>'
      + '<div class="col-lg-4 mb-3">' + listaTablero('Unidades sin docente asignado', 'fa-user-slash', sinDocente, 'Todas las unidades en curso tienen docente.') + '</div>'
      + '<div class="col-lg-4 mb-3">' + listaTablero('Unidades con menor aprobación', 'fa-chart-bar', menor, 'Aún no hay unidades calificadas en el semestre.') + '</div></div>'
    );
    $('#tablero_instituto .acceso-tablero').on('click', function () { cargar_contenido('contenido_principal', $(this).data('vista')); });
  }).fail(function () {
    $('#tablero_instituto').html('<div class="alert alert-warning mt-3">No se pudo cargar el tablero.</div>');
  });
}
