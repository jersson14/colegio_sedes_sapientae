// Notas de mis cursos: planilla estudiantes × criterios de un periodo, editable como el registro auxiliar.
// Las notas se validan al escribir con la escala del colegio y se guardan juntas (inserta, corrige o borra).
var RN = { datos: null, cambios: {} };
var RN_URL = '../controller/notas/controlador_registro_notas_curso.php';

function RegistroNotas_Titulo(t) { return window.PanelComun ? PanelComun.titulo(t || '') : (t || ''); }
function RegistroNotas_Aula(c) { return RegistroNotas_Titulo(c.grado) + (c.seccion && c.seccion !== 'UNICO' ? ' ' + c.seccion : '') + ' · ' + RegistroNotas_Titulo(c.nivel); }
function RegistroNotas_Pendientes() { return Object.keys(RN.cambios).length; }

function RegistroNotas_Iniciar() {
  $(document).off('.rn');
  $(document).on('change.rn', '#rn_anio', function () { RegistroNotas_Ir({ anio: $(this).val(), curso: 0, periodo: 0 }); });
  $(document).on('click.rn', '.rn-curso', function () { RegistroNotas_Ir({ curso: $(this).data('curso') }); });
  $(document).on('click.rn', '.rn-periodo', function () { RegistroNotas_Ir({ periodo: $(this).data('periodo') }); });
  $(document).on('input.rn', '.rn-nota', function () { RegistroNotas_Cambio($(this)); });
  $(document).on('keydown.rn', '.rn-nota', RegistroNotas_Teclas);
  $(document).on('click.rn', '.rn-coment', RegistroNotas_Conclusion);
  $(document).on('click.rn', '#rn_guardar', RegistroNotas_Guardar);
  $(document).on('click.rn', '#rn_deshacer', function () { RN.cambios = {}; RegistroNotas_Pintar(); });
  RegistroNotas_Cargar({ anio: 0, curso: 0, periodo: 0 });
}

// Cambiar de curso, periodo o año: si hay notas sin guardar, se pregunta antes
function RegistroNotas_Ir(p) {
  var d = RN.datos, params = $.extend({ anio: d.anio, curso: d.curso ? d.curso.id : 0, periodo: d.periodo ? d.periodo.id : 0 }, p);
  if (!RegistroNotas_Pendientes()) { return RegistroNotas_Cargar(params); }
  Swal.fire({ title: 'Hay notas sin guardar', text: '¿Las guarda antes de cambiar?', icon: 'warning', showDenyButton: true, showCancelButton: true,
              confirmButtonText: 'Guardar', denyButtonText: 'Descartar', cancelButtonText: 'Seguir aquí' }).then(function (r) {
    if (r.isConfirmed) { RegistroNotas_Guardar(function () { RegistroNotas_Cargar(params); }); }
    else if (r.isDenied) { RN.cambios = {}; RegistroNotas_Cargar(params); }
  });
}

function RegistroNotas_Cargar(params) {
  $.post(RN_URL, $.extend({ accion: 'cargar' }, params), null, 'json').done(function (d) {
    RN.datos = d;
    RN.cambios = {};
    $('#rn_anio').html(d.anios.map(function (a) {
      return '<option value="' + a.id + '">Año ' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
    }).join('')).val(d.anio);
    $('#rn_cursos').html(d.cursos.map(function (c) {
      return '<button type="button" class="rn-curso' + (d.curso && +d.curso.id === +c.id ? ' activo' : '') + '" data-curso="' + c.id + '" style="--av-color:' + Curso_Color(c.asignatura) + '">' +
        '<b>' + textoSeguro(RegistroNotas_Titulo(c.asignatura)) + '</b><span>' + textoSeguro(RegistroNotas_Aula(c)) + '</span></button>';
    }).join(''));
    $('#rn_periodos').html(d.periodos.map(function (p) {
      return '<button type="button" class="pe-filtro rn-periodo' + (d.periodo && +d.periodo.id === +p.id ? ' activo' : '') + '" data-periodo="' + p.id + '">' +
        (p.estado === 'FINALIZADO' ? '<i class="fas fa-lock"></i>' : '') + textoSeguro(RegistroNotas_Titulo(p.nombre)) + '</button>';
    }).join(''));
    RegistroNotas_Pintar();
  });
}

function RegistroNotas_Valor(m, c) {
  var k = m + '|' + c;
  if (RN.cambios[k]) { return RN.cambios[k]; }
  var n = (RN.datos.notas[m] || {})[c];
  return n ? { nota: n.nota, conclusion: n.conclusion } : { nota: '', conclusion: '' };
}

function RegistroNotas_Pintar() {
  var d = RN.datos;
  if (!d.cursos.length) { $('#rn_contenido').html('<div class="av-vacio"><i class="fas fa-chalkboard"></i><p>No tiene cursos asignados en este año escolar.</p></div>'); return; }
  if (!d.periodo) { $('#rn_contenido').html('<div class="av-vacio"><i class="far fa-calendar"></i><p>Este año aún no tiene bimestres registrados.</p></div>'); return; }
  var c = d.curso, color = Curso_Color(c.asignatura);
  var total = d.estudiantes.length * d.criterios.length, puestas = 0;
  d.estudiantes.forEach(function (e) { d.criterios.forEach(function (cr) { if (RegistroNotas_Valor(e.matricula, cr.id).nota !== '') { puestas++; } }); });
  var cab = '<section class="rn-cabecera" style="--av-color:' + color + '"><div><h2>' + textoSeguro(RegistroNotas_Titulo(c.asignatura)) + '</h2>' +
    '<p>' + textoSeguro(RegistroNotas_Aula(c)) + ' · ' + d.estudiantes.length + ' estudiantes · ' + textoSeguro(RegistroNotas_Titulo(d.periodo.nombre)) + '</p></div>' +
    '<div class="rn-avance"><span>' + puestas + ' de ' + total + ' notas</span><span class="av-progreso"><i style="width:' + (total ? Math.round(puestas * 100 / total) : 0) + '%;background:' + color + '"></i></span></div></section>';
  if (!d.criterios.length) {
    $('#rn_contenido').html(cab + '<div class="av-vacio"><i class="fas fa-list-ol"></i><p>Este curso aún no tiene criterios de evaluación.<br>La dirección los registra en <b>Notas y evaluación → Criterios de evaluación</b>.</p></div>');
    return;
  }
  if (!d.estudiantes.length) { $('#rn_contenido').html(cab + '<div class="av-vacio"><i class="fas fa-users"></i><p>El aula no tiene estudiantes matriculados.</p></div>'); return; }
  var aviso = d.bloqueado ? '<div class="alert alert-light border rn-aviso"><i class="fas fa-lock mr-2"></i>El ' + textoSeguro(RegistroNotas_Titulo(d.periodo.nombre)) +
    ' está finalizado: las notas se pueden ver, pero solo la dirección puede corregirlas.</div>' : '';
  var tabla = '<div class="rn-tabla-caja"><table class="rn-tabla"><thead><tr><th class="rn-n">N°</th><th class="rn-est">Estudiante</th>' +
    d.criterios.map(function (cr, i) { return '<th title="' + textoSeguro(cr.nombre) + '"><b>C' + (i + 1) + '</b><span>' + textoSeguro(RegistroNotas_Titulo(cr.nombre)) + '</span></th>'; }).join('') +
    '<th class="rn-prom">Promedio</th></tr></thead><tbody>' +
    d.estudiantes.map(function (e, f) {
      return '<tr><td class="rn-n">' + (f + 1) + '</td><td class="rn-est"><b>' + textoSeguro(RegistroNotas_Titulo(e.apellidos)) + '</b> ' + textoSeguro(RegistroNotas_Titulo(e.nombres)) + '</td>' +
        d.criterios.map(function (cr, col) {
          var v = RegistroNotas_Valor(e.matricula, cr.id), k = e.matricula + '|' + cr.id;
          return '<td class="rn-celda' + (RN.cambios[k] ? ' cambiada' : '') + '"><input type="text" inputmode="decimal" maxlength="5" class="rn-nota ' + RegistroNotas_Clase(v.nota) + '" value="' + textoSeguro(v.nota) +
            '" data-m="' + e.matricula + '" data-c="' + cr.id + '" data-f="' + f + '" data-col="' + col + '"' + (d.bloqueado ? ' disabled' : '') +
            ' aria-label="' + textoSeguro(e.apellidos + ' ' + e.nombres + ' — C' + (col + 1)) + '">' +
            (d.bloqueado ? '' : '<button type="button" class="rn-coment' + (v.conclusion ? ' con-texto' : '') + '" data-m="' + e.matricula + '" data-c="' + cr.id + '" title="' + textoSeguro(v.conclusion || 'Agregar una conclusión') + '" tabindex="-1"><i class="fas fa-comment-dots"></i></button>') + '</td>';
        }).join('') + '<td class="rn-prom" id="rn_prom_' + e.matricula + '">' + RegistroNotas_Promedio(e.matricula) + '</td></tr>';
    }).join('') + '</tbody></table></div>';
  var leyenda = '<ul class="rn-leyenda">' + d.criterios.map(function (cr, i) { return '<li><b>C' + (i + 1) + '</b> ' + textoSeguro(RegistroNotas_Titulo(cr.nombre)) + '</li>'; }).join('') + '</ul>';
  var barra = d.bloqueado ? '' : '<div class="rn-barra"><span class="small text-muted">Escala: ' + textoSeguro(d.escala) + ' · Enter o ↓ baja a la siguiente fila</span>' +
    '<span><button type="button" class="btn btn-outline-secondary" id="rn_deshacer"' + (RegistroNotas_Pendientes() ? '' : ' disabled') + '><i class="fas fa-undo mr-1"></i>Deshacer</button> ' +
    '<button type="button" class="btn btn-success" id="rn_guardar"' + (RegistroNotas_Pendientes() ? '' : ' disabled') + '><i class="fas fa-save mr-1"></i>Guardar' +
    (RegistroNotas_Pendientes() ? ' (' + RegistroNotas_Pendientes() + ')' : '') + '</button></span></div>';
  $('#rn_contenido').html(cab + aviso + barra + tabla + leyenda);
}

function RegistroNotas_Clase(v) {
  if (v === '' || v === null || v === undefined) { return ''; }
  if (notaNormalizada(v) === null) { return 'invalida'; }
  return notaAprobada(v) ? 'aprobada' : 'desaprobada';
}

// Promedio en vivo (numérico, con los decimales del colegio); con letras lo calcula el servidor al guardar
function RegistroNotas_Promedio(m) {
  var nums = [], letras = false, d = RN.datos;
  d.criterios.forEach(function (cr) {
    var v = RegistroNotas_Valor(m, cr.id).nota, n = notaNormalizada(v);
    if (v === '' || n === null) { return; }
    if (isNaN(n)) { letras = true; } else { nums.push(parseFloat(n)); }
  });
  var guardado = d.promedios[m];
  if (letras) { return '<span class="' + RegistroNotas_Clase(guardado) + '">' + textoSeguro(guardado || '…') + '</span>'; }
  if (!nums.length) { return '<span class="text-muted">–</span>'; }
  var p = (nums.reduce(function (a, b) { return a + b; }, 0) / nums.length).toFixed(+REGIONAL.nota_decimales || 0);
  return '<span class="' + RegistroNotas_Clase(p) + '">' + p + '</span>';
}

function RegistroNotas_Cambio(inp) {
  var m = inp.data('m'), c = inp.data('c'), k = m + '|' + c, val = $.trim(inp.val()).toUpperCase();
  var orig = (RN.datos.notas[m] || {})[c], previa = RegistroNotas_Valor(m, c);
  var nuevo = { matricula: m, criterio: c, nota: val, conclusion: previa.conclusion };
  if ((orig ? orig.nota : '') === val && (orig ? orig.conclusion : '') === nuevo.conclusion) { delete RN.cambios[k]; } else { RN.cambios[k] = nuevo; }
  inp.removeClass('aprobada desaprobada invalida').addClass(RegistroNotas_Clase(val)).closest('td').toggleClass('cambiada', !!RN.cambios[k]);
  inp.attr('title', RegistroNotas_Clase(val) === 'invalida' ? 'Fuera de la escala: ' + RN.datos.escala : '');
  $('#rn_prom_' + m).html(RegistroNotas_Promedio(m));
  var n = RegistroNotas_Pendientes();
  $('#rn_guardar').prop('disabled', !n).html('<i class="fas fa-save mr-1"></i>Guardar' + (n ? ' (' + n + ')' : ''));
  $('#rn_deshacer').prop('disabled', !n);
}

// Enter / flechas: moverse por la planilla como en una hoja de cálculo
function RegistroNotas_Teclas(e) {
  var inp = $(this), f = +inp.data('f'), col = +inp.data('col'), destino = null;
  if (e.key === 'Enter' || e.key === 'ArrowDown') { destino = [f + 1, col]; }
  else if (e.key === 'ArrowUp') { destino = [f - 1, col]; }
  else if (e.key === 'ArrowRight' && this.selectionStart === this.value.length) { destino = [f, col + 1]; }
  else if (e.key === 'ArrowLeft' && this.selectionStart === 0) { destino = [f, col - 1]; }
  if (!destino) { return; }
  var sig = $('.rn-nota[data-f="' + destino[0] + '"][data-col="' + destino[1] + '"]');
  if (sig.length) { e.preventDefault(); sig.focus().select(); }
}

function RegistroNotas_Conclusion() {
  var b = $(this), m = b.data('m'), c = b.data('c'), v = RegistroNotas_Valor(m, c);
  Swal.fire({ title: 'Conclusión', input: 'textarea', inputValue: v.conclusion, inputAttributes: { maxlength: 255 }, inputPlaceholder: 'Por ejemplo: debe reforzar la lectura en casa',
              showCancelButton: true, confirmButtonText: 'Listo', cancelButtonText: 'Cancelar' }).then(function (r) {
    if (!r.isConfirmed) { return; }
    var k = m + '|' + c, orig = (RN.datos.notas[m] || {})[c], texto = $.trim(r.value || '');
    var nuevo = { matricula: m, criterio: c, nota: v.nota, conclusion: texto };
    if ((orig ? orig.nota : '') === nuevo.nota && (orig ? orig.conclusion : '') === texto) { delete RN.cambios[k]; } else { RN.cambios[k] = nuevo; }
    if (nuevo.nota === '' && texto) { Swal.fire('Falta la nota', 'Escriba primero la nota de este criterio; la conclusión se guarda junto con ella.', 'info'); delete RN.cambios[k]; }
    RegistroNotas_Pintar();
  });
}

function RegistroNotas_Guardar(despues) {
  var lista = Object.keys(RN.cambios).map(function (k) { return RN.cambios[k]; });
  var malas = lista.filter(function (x) { return x.nota !== '' && notaNormalizada(x.nota) === null; });
  if (malas.length) { return Swal.fire('Revise las notas en rojo', 'Hay ' + malas.length + (malas.length === 1 ? ' nota' : ' notas') + ' fuera de la escala: ' + RN.datos.escala, 'warning'); }
  var d = RN.datos, btn = $('#rn_guardar').prop('disabled', true);
  $.post(RN_URL, { accion: 'guardar', curso: d.curso.id, periodo: d.periodo.id, cambios: JSON.stringify(lista) }, null, 'json').done(function (r) {
    d.notas = r.notas;
    d.promedios = r.promedios;
    RN.cambios = {};
    RegistroNotas_Pintar();
    Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: r.guardadas + (r.guardadas === 1 ? ' nota guardada' : ' notas guardadas') });
    if (typeof despues === 'function') { despues(); }
  }).fail(function () { btn.prop('disabled', false); });
}
