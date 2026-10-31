// Libretas de notas (Fase 13, paso 9): notas por curso, promedio y orden de mérito de un aula.
// Los cálculos los hace el servidor (utilitario/libreta.php); el PDF usa los mismos (view/MPDF/REPORTE/libreta.php).
var LB = { datos: null, tabla: null };

function Libreta_Iniciar() {
  $('#lb_anio').off('change').on('change', function () { $('#lb_aula').val(''); $('#lb_periodo').val(''); Libreta_Cargar(true); });
  $('#lb_aula, #lb_periodo').off('change').on('change', function () { Libreta_Cargar(); });
  $('#lb_tabla').off('click.lb').on('click.lb', '.lb-pdf', function () { Libreta_Pdf(+$(this).data('m')); });
  Libreta_Cargar(true);
}

// elegirPeriodo: al abrir o cambiar de año se propone el periodo en curso (o el último que ya empezó)
function Libreta_Cargar(elegirPeriodo) {
  var periodo = elegirPeriodo ? null : $('#lb_periodo').val();
  $.post('../controller/boletas/controlador_libreta.php', {
    anio: $('#lb_anio').val() || 0, aula: $('#lb_aula').val() || 0, periodo: periodo || ''
  }, null, 'json').done(function (d) {
    if (elegirPeriodo) {
      var hoy = new Date().toISOString().substr(0, 10);
      var actual = d.periodos_anio.filter(function (p) { return p.inicio <= hoy; }).pop();
      if (actual) { $('#lb_periodo').html('<option value="' + actual.id + '"></option>').val(actual.id); return Libreta_Cargar(); }
    }
    LB.datos = d;
    Libreta_Filtros();
    Libreta_Pintar();
  });
}

function Libreta_Filtros() {
  var d = LB.datos, T = PanelComun.titulo;
  $('#lb_anio').html(d.anios.map(function (a) {
    return '<option value="' + a.id + '">' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
  }).join('')).val(d.anio);
  $('#lb_aula').html(d.aulas.length ? d.aulas.map(function (a) {
    return '<option value="' + a.id + '">' + textoSeguro(T(a.nivel) + ' - ' + T(a.grado) + (a.seccion && a.seccion !== 'UNICO' ? ' ' + a.seccion : '')) + ' (' + a.alumnos + ')</option>';
  }).join('') : '<option value="">Sin aulas con estudiantes</option>').val(d.aula || '');
  $('#lb_periodo').html('<option value="">Todo el año</option>' + d.periodos_anio.map(function (p) {
    return '<option value="' + p.id + '">' + textoSeguro(PanelComun.nombrePeriodo(p.nombre)) + '</option>';
  }).join('')).val(d.periodo || '');
}

function Libreta_Pintar() {
  var l = LB.datos.libreta, T = PanelComun.titulo;
  if (LB.tabla) { LB.tabla.destroy(); LB.tabla = null; $('#lb_tabla').empty(); }
  if (!l) {
    $('#lb_resumen').html('<p class="text-muted">No hay aulas con estudiantes en este año.</p>');
    $('#lb_nota').text('');
    return;
  }
  var est = l.estudiantes;
  var sinNotas = est.filter(function (e) { return e.puesto === null; }).length;
  var conDesap = est.filter(function (e) { return e.desaprobados > 0; }).length;
  $('#lb_resumen').html('<span class="lb-chip">' + PanelComun.plural(est.length, 'estudiante', 'estudiantes') + '</span>'
    + (est.length - sinNotas - conDesap > 0 ? '<span class="lb-chip lb-ok">' + PanelComun.plural(est.length - sinNotas - conDesap, 'aprueba todo', 'aprueban todo') + '</span>' : '')
    + (conDesap ? '<span class="lb-chip lb-mal">' + PanelComun.plural(conDesap, 'con cursos desaprobados', 'con cursos desaprobados') + '</span>' : '')
    + (sinNotas ? '<span class="lb-chip">' + PanelComun.plural(sinNotas, 'sin notas aún', 'sin notas aún') + '</span>' : ''));
  var celda = function (v, ok) {
    if (v === null || v === undefined) { return '<span class="text-muted">—</span>'; }
    return '<span class="' + (ok === false ? 'lb-desap' : '') + '">' + textoSeguro(v) + '</span>';
  };
  var columnas = [
    { title: 'Puesto', data: 'puesto', className: 'text-center', render: function (v, t) { return t === 'display' ? (v === null ? '<span class="text-muted">—</span>' : '<b>' + v + '</b>') : (v === null ? 9999 : v); } },
    { title: 'Estudiante', data: null, className: 'text-nowrap', render: function (e) { return textoSeguro(T(e.apellidos + ', ' + e.nombres)); } }
  ];
  l.cursos.forEach(function (c) {
    columnas.push({ title: textoSeguro(T(c.nombre)), data: null, className: 'text-center', orderable: false,
      render: function (e) { var x = e.cursos[c.id]; return celda(x.final, x.aprobado); } });
  });
  columnas.push({ title: 'Promedio', data: 'promedio', className: 'text-center', render: function (v, t) { return t === 'display' ? '<b>' + celda(v) + '</b>' : (v || ''); } });
  columnas.push({ title: 'Desaprobados', data: 'desaprobados', className: 'text-center', render: function (v, t) { return t === 'display' && v ? '<span class="lb-desap">' + v + '</span>' : v; } });
  columnas.push({ title: 'Libreta', data: 'matricula', orderable: false, className: 'text-center',
    render: function (m) { return '<button type="button" class="btn btn-sm btn-outline-primary lb-pdf text-nowrap" data-m="' + (+m) + '" title="Libreta en PDF"><i class="fas fa-file-pdf"></i> PDF</button>'; } });
  var aula = T(l.aula.nivel + ' ' + l.aula.grado) + (l.aula.seccion && l.aula.seccion !== 'UNICO' ? ' ' + l.aula.seccion : '');
  var periodo = l.todo_el_anio ? 'Año ' + l.aula.anio : PanelComun.nombrePeriodo(l.periodos[0].nombre) + ' ' + l.aula.anio;
  LB.tabla = $('#lb_tabla').DataTable({
    data: est, columns: columnas, order: [[0, 'asc']], paging: false, info: false, language: idioma_espanol,
    dom: 'Bfrt',
    buttons: [{ extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> <b>Excel</b>', title: 'Notas ' + aula + ' - ' + periodo,
                filename: 'notas_' + aula.replace(/\W+/g, '_'), exportOptions: { columns: ':not(:last-child)' } }]
  });
  $('#lb_nota').text((l.todo_el_anio ? 'Nota final de cada curso: promedio de sus periodos con notas. ' : 'Nota de cada curso: promedio de sus criterios en el periodo. ')
    + 'Escala: ' + l.escala + '. En rojo, lo desaprobado. Empates comparten el puesto.');
}

// Libreta en PDF: de un estudiante (matricula), de toda el aula, o el orden de mérito
function Libreta_Pdf(matricula, merito) {
  var d = LB.datos;
  if (!d || !d.libreta) { return; }
  var p = 'periodo=' + encodeURIComponent(d.periodo || '');
  var url = '../view/MPDF/REPORTE/libreta.php?' + (matricula ? 'matricula=' + matricula + '&' + p
    : 'anio=' + d.anio + '&aula=' + d.aula + '&' + p + (merito ? '&merito=1' : ''));
  window.open(url + '#zoom=100', '_blank');
}
