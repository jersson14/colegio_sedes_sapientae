// Cambio de año escolar asistido (Fase 13, paso 13): Años → Promoción → Copias → Resumen.
// Regla: solo se propone promover a quien aprobó TODOS sus cursos; el resto lo decide la dirección, estudiante por estudiante.
window.CambioAnio = (function () {
  var D = null, paso = 1;
  var decision = {};   // matricula -> 'PROMOVER' | 'REPETIR' | 'EGRESA' | 'NO_CONTINUA' | 'LUEGO'
  var destinoAula = {}; // id aula origen -> id aula destino, o 'EGRESA'
  var T = function (t) { return PanelComun.titulo(t); };
  var RESULTADO = {
    APROBO_TODO: ['est-ok', 'Aprobó todo'], DESAPROBADOS: ['est-mal', 'Desaprobó'], SIN_NOTAS: ['est-neutro', 'Sin notas'],
    YA_MATRICULADO: ['est-info', 'Ya matriculado en el año nuevo']
  };

  function nombreAula(a) { return T(a.nivel) + ' · ' + T(a.grado) + (a.seccion && a.seccion !== 'UNICO' && !new RegExp(' ' + a.seccion + '$').test(a.grado) ? ' ' + a.seccion : ''); }
  function aulaPorId(id) { return D.todas_las_aulas.filter(function (a) { return +a.id === +id; })[0]; }

  function cargar(params) {
    $.post('../controller/cambio_anio/controlador_propuesta.php', params || {}, null, 'json').done(function (r) {
      D = r;
      decision = {}; destinoAula = {};
      (D.aulas || []).forEach(function (g) {
        destinoAula[g.aula.id] = g.siguiente === 'EGRESA' ? 'EGRESA' : (g.aula_siguiente || '');
        g.estudiantes.forEach(function (e) { decision[e.matricula] = e.sugerida; });
      });
      pintarAnios();
      ir(1);
    });
  }

  function ir(n) {
    if (n > 1 && !D.destino) { return aviso('Primero elija (o cree) el año nuevo'); }
    paso = Math.max(1, Math.min(4, n));
    $('.ca-paso').prop('hidden', true).filter('[data-paso="' + paso + '"]').prop('hidden', false);
    $('.mp-pasos li').each(function () {
      var p = +$(this).data('paso');
      $(this).toggleClass('hecho', p < paso).toggleClass('actual', p === paso);
    });
    $('#ca_atras').prop('hidden', paso === 1);
    $('#ca_siguiente').prop('hidden', paso === 4);
    $('#ca_ejecutar').prop('hidden', paso !== 4);
    if (paso === 2) { pintarPromocion(); }
    if (paso === 3) { pintarCopias(); }
    if (paso === 4) { pintarResumen(); }
  }

  function aviso(t) { Swal.fire({ toast: true, position: 'top-end', timer: 3500, showConfirmButton: false, icon: 'warning', title: t }); }

  // ---------- 1. Años ----------
  function pintarAnios() {
    var op = function (a) { return '<option value="' + a.id + '">' + a.anio + (a.estado === 'ACTIVO' ? ' (activo)' : '') + '</option>'; };
    $('#ca_origen').html(D.anios.map(op).join('')).val(D.origen);
    $('#ca_destino').html('<option value="">Elija el año nuevo</option>' + D.anios.filter(function (a) { return +a.id !== +D.origen; }).map(op).join('')).val(D.destino || '');
    var h;
    if (!D.destino) {
      var sig = +D.anios.filter(function (a) { return +a.id === +D.origen; })[0].anio + 1;
      h = '<div class="alert alert-warning">Todavía no existe el año escolar <b>' + sig + '</b>. Créelo en '
        + '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'año/view_año_escolar.php\');return false;">Años escolares</a> y vuelva aquí.</div>';
    } else {
      var y = D.ya_tiene;
      var fila = function (n, t) { return '<li>' + (n ? '<b>' + n + '</b> ' + t : '<span class="text-muted">Sin ' + t + '</span>') + '</li>'; };
      h = '<div class="mp-tarjeta"><b>El año nuevo ya tiene:</b><ul class="mp-datos">' + fila(y.matriculas, 'matrículas') + fila(y.periodos, 'periodos')
        + fila(y.pensiones, 'pensiones') + fila(y.cursos, 'docentes con cursos asignados') + fila(y.horas, 'horas de clase') + '</ul></div>';
    }
    $('#ca_destino_info').html(h);
  }

  // ---------- 2. Promoción ----------
  function opcionesDestino(g) {
    var actual = destinoAula[g.aula.id];
    var h = '<option value="EGRESA"' + (actual === 'EGRESA' ? ' selected' : '') + '>Egresan (terminan sus estudios)</option>';
    D.todas_las_aulas.forEach(function (a) {
      h += '<option value="' + a.id + '"' + (+actual === +a.id ? ' selected' : '') + '>' + textoSeguro(nombreAula(a)) + '</option>';
    });
    return (actual === '' ? '<option value="" selected>Elija el aula del año nuevo</option>' : '') + h;
  }

  function opcionesDecision(g, e) {
    var egresa = destinoAula[g.aula.id] === 'EGRESA';
    var ops = [[egresa ? 'EGRESA' : 'PROMOVER', egresa ? 'Egresa' : 'Promover'], ['REPETIR', 'Repite el grado'], ['NO_CONTINUA', 'No continúa'], ['LUEGO', 'Decidir luego']];
    var actual = decision[e.matricula];
    if (egresa && actual === 'PROMOVER') { actual = decision[e.matricula] = 'EGRESA'; }
    if (!egresa && actual === 'EGRESA') { actual = decision[e.matricula] = 'PROMOVER'; }
    return ops.map(function (o) { return '<option value="' + o[0] + '"' + (o[0] === actual ? ' selected' : '') + '>' + o[1] + '</option>'; }).join('');
  }

  function pintarPromocion() {
    if (!D.aulas.length) { return $('#ca_aulas').html('<p class="text-muted">El año que termina no tiene estudiantes matriculados.</p>'); }
    var h = '';
    D.aulas.forEach(function (g) {
      var c = { APROBO_TODO: 0, DESAPROBADOS: 0, SIN_NOTAS: 0, YA_MATRICULADO: 0 };
      g.estudiantes.forEach(function (e) { c[e.resultado]++; });
      h += '<section class="pi-tarjeta ca-aula" data-aula="' + g.aula.id + '"><header class="ca-aula-cab"><h2>' + textoSeguro(nombreAula(g.aula)) + '</h2>'
        + '<label class="ca-destino">pasa a <select class="form-control form-control-sm ca-sel-destino">' + opcionesDestino(g) + '</select></label></header>'
        + '<div class="lb-resumen">' + (c.APROBO_TODO ? '<span class="lb-chip lb-ok">' + PanelComun.plural(c.APROBO_TODO, 'aprobó todo', 'aprobaron todo') + '</span>' : '')
        + (c.DESAPROBADOS ? '<span class="lb-chip lb-mal">' + PanelComun.plural(c.DESAPROBADOS, 'con desaprobados', 'con desaprobados') + '</span>' : '')
        + (c.SIN_NOTAS ? '<span class="lb-chip">' + PanelComun.plural(c.SIN_NOTAS, 'sin notas', 'sin notas') + '</span>' : '')
        + ((c.DESAPROBADOS + c.SIN_NOTAS) ? '<span class="ca-masivo">Los que no aprobaron todo: <button type="button" class="btn btn-sm btn-outline-secondary ca-todos" data-accion="REPETIR">Repiten</button> '
          + '<button type="button" class="btn btn-sm btn-outline-secondary ca-todos" data-accion="LUEGO">Decidir luego</button></span>' : '') + '</div>'
        + '<div class="table-responsive"><table class="table table-sm ca-tabla"><thead><tr><th>Estudiante</th><th>Resultado del año</th><th style="width:210px">Decisión</th></tr></thead><tbody>';
      g.estudiantes.forEach(function (e) {
        var r = RESULTADO[e.resultado];
        var det = e.resultado === 'DESAPROBADOS' ? ': ' + e.desaprobados.map(T).join(', ') : (e.promedio ? ' · promedio ' + e.promedio : '');
        h += '<tr data-m="' + e.matricula + '"><td>' + textoSeguro(T(e.nombre)) + '</td><td><span class="badge lg-estado ' + r[0] + '">' + r[1] + '</span>'
          + '<small class="ca-det">' + textoSeguro(det) + '</small></td><td>'
          + (e.resultado === 'YA_MATRICULADO' ? '<span class="text-muted">—</span>' : '<select class="form-control form-control-sm ca-sel-dec">' + opcionesDecision(g, e) + '</select>') + '</td></tr>';
      });
      h += '</tbody></table></div></section>';
    });
    $('#ca_aulas').html(h);
    marcarDecisiones();
  }

  // Resalta las decisiones que la dirección tomó fuera de la regla (promover con cursos desaprobados o sin notas)
  function marcarDecisiones() {
    D.aulas.forEach(function (g) {
      g.estudiantes.forEach(function (e) {
        var fila = $('#ca_aulas tr[data-m="' + e.matricula + '"]');
        fila.toggleClass('ca-excepcion', decision[e.matricula] === 'PROMOVER' && e.resultado !== 'APROBO_TODO');
      });
    });
  }

  // ---------- 3. Copias ----------
  function pintarCopias() {
    var y = D.ya_tiene;
    var op = function (id, t, ya, ayuda) {
      return '<div class="custom-control custom-checkbox mb-2"><input type="checkbox" class="custom-control-input" id="' + id + '"' + (ya ? ' disabled' : ' checked') + '>'
        + '<label class="custom-control-label" for="' + id + '"><b>' + t + '</b> <small class="text-muted">' + (ya ? '— el año nuevo ya tiene ' + ya + ', no se copian' : ayuda) + '</small></label></div>';
    };
    $('#ca_copias').html(op('ca_c_periodos', 'Periodos', y.periodos, 'mismos bimestres, con las fechas del año nuevo')
      + op('ca_c_pensiones', 'Pensiones', y.pensiones, 'mismos montos y moras, con vencimientos del año nuevo')
      + op('ca_c_cursos', 'Cursos de cada docente', y.cursos, 'el mismo reparto de cursos y sus criterios de evaluación')
      + op('ca_c_horarios', 'Horas de clase y horarios', y.horas, 'los mismos bloques y horarios por aula (necesita copiar también los cursos)'));
  }

  // ---------- 4. Resumen ----------
  function cuenta() {
    var c = { PROMOVER: 0, REPETIR: 0, EGRESA: 0, NO_CONTINUA: 0, LUEGO: 0, excepciones: 0, sinAula: 0 };
    D.aulas.forEach(function (g) {
      g.estudiantes.forEach(function (e) {
        var d = decision[e.matricula];
        if (!d || d === 'NADA') { return; }
        c[d]++;
        if (d === 'PROMOVER' && e.resultado !== 'APROBO_TODO') { c.excepciones++; }
        if (d === 'PROMOVER' && !destinoAula[g.aula.id]) { c.sinAula++; }
      });
    });
    return c;
  }

  function pintarResumen() {
    var c = cuenta();
    var anio = function (id) { return D.anios.filter(function (a) { return +a.id === +id; })[0].anio; };
    var copias = ['periodos', 'pensiones', 'cursos', 'horarios'].filter(function (k) { return $('#ca_c_' + k).is(':checked'); });
    var h = '<h5>' + anio(D.origen) + ' → ' + anio(D.destino) + '</h5><table class="table table-sm mp-tabla"><tbody>'
      + '<tr><td>Se matriculan en el grado siguiente</td><td class="text-right"><b>' + c.PROMOVER + '</b></td></tr>'
      + '<tr><td>Se matriculan en el mismo grado (repiten)</td><td class="text-right"><b>' + c.REPETIR + '</b></td></tr>'
      + '<tr><td>Egresan</td><td class="text-right"><b>' + c.EGRESA + '</b></td></tr>'
      + '<tr><td>No continúan</td><td class="text-right"><b>' + c.NO_CONTINUA + '</b></td></tr>'
      + '<tr><td>Quedan para decidir luego (no se matriculan ahora)</td><td class="text-right"><b>' + c.LUEGO + '</b></td></tr></tbody></table>';
    if (c.excepciones) {
      h += '<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle mr-1"></i><b>' + PanelComun.plural(c.excepciones, 'estudiante', 'estudiantes')
        + '</b> se promueve' + (c.excepciones === 1 ? '' : 'n') + ' sin haber aprobado todo, por decisión de la dirección.</div>';
    }
    if (c.sinAula) { h += '<div class="alert alert-danger py-2">Hay aulas sin aula de destino: vuelva a Promoción y elíjala.</div>'; }
    h += '<p class="mp-nota">Copias: ' + (copias.length ? copias.map(function (k) { return { periodos: 'periodos', pensiones: 'pensiones', cursos: 'cursos de docentes', horarios: 'horas y horarios' }[k]; }).join(', ') : 'ninguna') + '.</p>'
      + '<p class="mp-nota">Las matrículas se crean sin cobro: la matrícula se cobra después en <b>Cobrar pensión</b>, como siempre.</p>'
      + '<p class="mp-nota">' + ($('#ca_activar').is(':checked') ? '<b>Al terminar, el año ' + anio(D.destino) + ' pasará a ser el activo.</b>' : 'El año activo no cambia (puede activarlo después en Años escolares).') + '</p>';
    $('#ca_resumen').html(h);
    $('#ca_ejecutar').prop('disabled', c.sinAula > 0);
  }

  function ejecutar() {
    var lista = [];
    D.aulas.forEach(function (g) {
      g.estudiantes.forEach(function (e) {
        var d = decision[e.matricula];
        if (!d || d === 'NADA') { return; }
        lista.push({ matricula: e.matricula, accion: d, aula: d === 'PROMOVER' ? destinoAula[g.aula.id] : null });
      });
    });
    Swal.fire({ title: '¿Hacer el cambio de año?', text: 'Se crearán las matrículas y las copias indicadas en el resumen.', icon: 'question',
                showCancelButton: true, confirmButtonText: 'Sí, hacer el cambio', cancelButtonText: 'Revisar otra vez' }).then(function (res) {
      if (!res.isConfirmed) { return; }
      var datos = { origen: D.origen, destino: D.destino, decisiones: JSON.stringify(lista), activar: $('#ca_activar').is(':checked') ? 1 : '' };
      ['periodos', 'pensiones', 'cursos', 'horarios'].forEach(function (k) { datos['copiar_' + k] = $('#ca_c_' + k).is(':checked') ? 1 : ''; });
      $.post('../controller/cambio_anio/controlador_ejecutar.php', datos, null, 'json').done(function (r) {
        var h = '<ul class="text-left"><li>' + r.promovidos + ' promovidos y ' + r.repiten + ' repiten (' + r.matriculas + ' matrículas nuevas)</li>'
          + '<li>' + r.egresan + ' egresan o no continúan; ' + r.pendientes + ' para decidir luego</li>'
          + '<li>Copiados: ' + r.periodos + ' periodos, ' + r.pensiones + ' pensiones, ' + r.cursos + ' docentes con cursos (' + r.criterios + ' criterios), '
          + r.horas + ' horas y ' + r.horarios + ' horarios</li>' + (r.activado ? '<li><b>El año nuevo ya está activo.</b></li>' : '') + '</ul>';
        if (r.omitidos.length) { h += '<div class="alert alert-warning text-left py-2"><b>No se matricularon:</b><br>' + r.omitidos.map(textoSeguro).join('<br>') + '</div>'; }
        Swal.fire({ icon: 'success', title: 'Cambio de año hecho', html: h }).then(function () { cargar({ origen: D.origen, destino: D.destino }); });
      });
    });
  }

  function eventos() {
    $(document).off('.ca');
    $(document).on('click.ca', '#ca_siguiente', function () {
      if (paso === 2 && D.aulas.some(function (g) { return destinoAula[g.aula.id] === ''; }) && cuenta().sinAula) { return aviso('Elija el aula del año nuevo de cada aula'); }
      ir(paso + 1);
    });
    $(document).on('click.ca', '#ca_atras', function () { ir(paso - 1); });
    $(document).on('click.ca', '.mp-pasos li.hecho', function () { ir(+$(this).data('paso')); });
    $(document).on('change.ca', '#ca_origen', function () { cargar({ origen: this.value }); });
    $(document).on('change.ca', '#ca_destino', function () { cargar({ origen: $('#ca_origen').val(), destino: this.value }); });
    $(document).on('change.ca', '.ca-sel-destino', function () {
      destinoAula[$(this).closest('.ca-aula').data('aula')] = this.value;
      pintarPromocion();
    });
    $(document).on('change.ca', '.ca-sel-dec', function () {
      decision[$(this).closest('tr').data('m')] = this.value;
      marcarDecisiones();
    });
    $(document).on('click.ca', '.ca-todos', function () {
      var id = $(this).closest('.ca-aula').data('aula'), accion = $(this).data('accion');
      D.aulas.filter(function (g) { return +g.aula.id === +id; })[0].estudiantes.forEach(function (e) {
        if (e.resultado === 'DESAPROBADOS' || e.resultado === 'SIN_NOTAS') { decision[e.matricula] = accion; }
      });
      pintarPromocion();
    });
    $(document).on('click.ca', '#ca_ejecutar', ejecutar);
  }

  function iniciar() { eventos(); cargar(); }
  return { iniciar: iniciar };
})();
