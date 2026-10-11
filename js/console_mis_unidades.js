// Institutos: avance del estudiante por unidad didáctica (view/matricula_unidades/view_mis_unidades.php).
var ROMANOS_MIS_UNIDADES = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
var ESTADOS_MIS_UNIDADES = { APROBADO: ['success', 'Aprobada'], DESAPROBADO: ['danger', 'Desaprobada'], MATRICULADO: ['primary', 'En curso'], 'NO CURSADA': ['light', 'Por llevar'] };

function escMisUnidades(texto) {
  return $('<div>').text(texto == null ? '' : String(texto)).html();
}

function tarjetaMisUnidades(p) {
  var r = p.resumen;
  var cifras = [
    ['Promedio', r.promedio == null ? '—' : parseFloat(r.promedio).toFixed(2), 'info', r.estrategia],
    ['Créditos aprobados', r.creditos_aprobados, 'success', ''],
    ['Unidades aprobadas', r.unidades_aprobadas + ' de ' + p.unidades.length, 'primary', ''],
    ['Cargos pendientes', r.cargos.length, r.cargos.length ? 'danger' : 'secondary', r.cargos.length ? 'Unidades por volver a llevar' : '']
  ].map(function (c) {
    return '<div class="col-6 col-md-3"><div class="small-box bg-' + c[2] + '"><div class="inner"><h3>' + escMisUnidades(c[1]) + '</h3><p>'
      + escMisUnidades(c[0]) + (c[3] ? '<br><small>' + escMisUnidades(c[3]) + '</small>' : '') + '</p></div></div></div>';
  }).join('');
  var filas = p.unidades.map(function (u) {
    var e = ESTADOS_MIS_UNIDADES[u.estado] || ['light', u.estado];
    return '<tr><td>' + ROMANOS_MIS_UNIDADES[u.periodo_academico] + '</td><td>' + escMisUnidades(u.codigo) + '</td><td>' + escMisUnidades(u.nombre)
      + '</td><td>' + escMisUnidades(u.modulo) + '</td><td>' + escMisUnidades(u.creditos) + '</td><td>' + (u.nota == null ? '—' : escMisUnidades(u.nota) + (u.recuperacion ? ' (R)' : ''))
      + '</td><td><span class="badge badge-' + e[0] + '">' + e[1] + '</span></td><td>' + escMisUnidades(u.periodo || '') + '</td></tr>';
  }).join('');
  return '<div class="card card-outline card-primary mis-unidades-programa"><div class="card-header"><h3 class="card-title"><b>' + escMisUnidades(p.programa) + '</b></h3>'
    + '<div class="card-tools"><a class="btn btn-sm btn-outline-dark" target="_blank" href="../view/MPDF/REPORTE/record_academico.php?alumno=' + encodeURIComponent(p.id_alumno)
    + '&programa=' + encodeURIComponent(p.id_programa) + '">Récord académico (PDF)</a></div></div><div class="card-body">'
    + (p.completo ? '<div class="alert alert-success">¡Aprobaste todas las unidades del programa!</div>' : '')
    + '<div class="row">' + cifras + '</div><div class="table-responsive"><table class="table table-sm table-bordered">'
    + '<thead style="background-color:#023D77;color:white;"><tr><th>Sem.</th><th>Código</th><th>Unidad didáctica</th><th>Módulo</th><th>Créditos</th><th>Nota</th><th>Situación</th><th>Cursada en</th></tr></thead>'
    + '<tbody>' + filas + '</tbody></table></div><small class="text-muted">(R) nota de la evaluación de recuperación.</small></div></div>';
}

function cargarMisUnidades() {
  $.ajax({ url: '../controller/matricula_unidades/controlador_mis_unidades.php', type: 'GET', dataType: 'json' }).done(function (programas) {
    $('#mis_unidades_contenido').html(programas.length ? programas.map(function (p) { return tarjetaMisUnidades(p); }).join('')
      : '<div class="alert alert-info">Aún no tienes unidades didácticas matriculadas.</div>');
  });
}
