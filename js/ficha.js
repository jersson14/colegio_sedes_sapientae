/* Buscador general (barra superior) y ficha única del estudiante (Fase 13, paso 6).
 * Funciones con nombre propio: las llama el index desde el bloque de cada rol y así las rastrea
 * tests/herramientas/revisar_permisos.php. Las secciones que llegan dependen del rol (controlador_ficha.php). */

var __buscadorGlobal = { espera: null, peticion: null, items: [], activo: -1 };

function Ficha_Foto(ruta) {
  ruta = textoPlano(ruta || '');
  return ruta && ruta.slice(-1) !== '/' ? '../' + ruta : '../img/blanco1.jpg';
}

function Ficha_Fecha(iso) {
  if (!iso) { return '—'; }
  var p = String(iso).substr(0, 10).split('-');
  return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : textoSeguro(iso);
}

/* ---------- Buscador general ---------- */
function Buscador_Global(input) {
  var q = $.trim(input.value);
  var $res = $('#buscador_global_resultados');
  clearTimeout(__buscadorGlobal.espera);
  if (q.length < 2) {
    $res.prop('hidden', true).empty();
    __buscadorGlobal.items = [];
    return;
  }
  __buscadorGlobal.espera = setTimeout(function () {
    if (__buscadorGlobal.peticion) { __buscadorGlobal.peticion.abort(); }
    __buscadorGlobal.peticion = $.ajax({
      url: '../controller/alumnos/controlador_buscar_global.php',
      type: 'POST',
      dataType: 'json',
      data: { q: q }
    }).done(function (r) {
      var lista = (r && r.data) || [];
      __buscadorGlobal.items = lista;
      __buscadorGlobal.activo = lista.length ? 0 : -1;
      if (!lista.length) {
        $res.html('<div class="bg-vacio">No hay estudiantes con «' + textoSeguro(q) + '».</div>').prop('hidden', false);
        return;
      }
      var h = '';
      lista.forEach(function (a, i) {
        h += '<a href="#" role="option" class="bg-item' + (i === 0 ? ' activo' : '') + '" data-i="' + i + '">'
          + '<img src="' + textoSeguro(Ficha_Foto(a.foto)) + '" alt="" onerror="this.onerror=null;this.src=\'../img/blanco1.jpg\'">'
          + '<span><b>' + textoSeguro(PanelComun.titulo(a.apellidos + ', ' + a.nombres)) + '</b>'
          + '<small>' + textoSeguro(a.documento) + (a.aula ? ' · ' + textoSeguro(a.aula) : ' · Sin matrícula') + '</small></span></a>';
      });
      $res.html(h).prop('hidden', false);
    });
  }, 250);
}

function Buscador_Global_Tecla(ev) {
  var $res = $('#buscador_global_resultados');
  var n = __buscadorGlobal.items.length;
  if (ev.key === 'Escape') {
    ev.target.value = '';
    $res.prop('hidden', true).empty();
    __buscadorGlobal.items = [];
    ev.target.blur();
    return;
  }
  if (!n || $res.prop('hidden')) { return; }
  if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
    ev.preventDefault();
    __buscadorGlobal.activo = (__buscadorGlobal.activo + (ev.key === 'ArrowDown' ? 1 : n - 1)) % n;
    $res.find('.bg-item').removeClass('activo').eq(__buscadorGlobal.activo).addClass('activo');
  } else if (ev.key === 'Enter') {
    ev.preventDefault();
    Buscador_Global_Elegir(Math.max(0, __buscadorGlobal.activo));
  }
}

function Buscador_Global_Elegir(i) {
  var a = __buscadorGlobal.items[i];
  if (!a) { return; }
  $('#buscador_global_resultados').prop('hidden', true).empty();
  $('#buscador_global').val('').blur();
  __buscadorGlobal.items = [];
  Ficha_Abrir(a.id);
}

$(function () {
  $(document).on('mousedown', '#buscador_global_resultados .bg-item', function (e) {
    e.preventDefault();
    Buscador_Global_Elegir(+$(this).data('i'));
  });
  $(document).on('click', '#buscador_global_resultados .bg-item', function (e) { e.preventDefault(); });
  $(document).on('blur', '#buscador_global', function () {
    setTimeout(function () { $('#buscador_global_resultados').prop('hidden', true); }, 150);
  });
  $(document).on('focus', '#buscador_global', function () {
    if (__buscadorGlobal.items.length) { $('#buscador_global_resultados').prop('hidden', false); }
  });
  // "/" lleva al buscador desde cualquier lugar (salvo mientras se escribe en un campo)
  $(document).on('keydown', function (e) {
    if (e.key === '/' && !$(e.target).is('input, textarea, select, [contenteditable]') && $('#buscador_global').length) {
      e.preventDefault();
      $('#buscador_global').trigger('focus');
    }
  });
});

/* ---------- Ficha ---------- */
function Ficha_Abrir(id) {
  $('#contenido_principal').load('alumnos/view_ficha.php', function () {
    aplicarRegional(this);
    window.scrollTo(0, 0);
    Ficha_Mostrar(id);
  });
}

function Ficha_Imprimir() {
  window.print();
}

function Ficha_Mostrar(id, matricula) {
  var $c = $('#ficha_contenido');
  $.ajax({
    url: '../controller/alumnos/controlador_ficha.php',
    type: 'POST',
    dataType: 'json',
    data: { id: id, matricula: matricula || '' }
  }).done(function (f) {
    $c.html(Ficha_Html(f));
    $c.find('#ficha_anio').on('change', function () { Ficha_Mostrar(id, this.value); });
  }).fail(function (x) {
    var m = (x.responseJSON && x.responseJSON.mensaje) || 'No se pudo cargar la ficha.';
    $c.html('<div class="pi-tarjeta"><p class="mb-0 text-danger"><i class="fas fa-exclamation-circle mr-2"></i>' + textoSeguro(m) + '</p></div>');
  });
}

function Ficha_Html(f) {
  var T = PanelComun.titulo;
  var a = f.alumno, m = f.matricula, sec = f.secciones || [];
  var tiene = function (s) { return sec.indexOf(s) !== -1 && f[s] !== undefined; };
  var dato = function (etq, v) { return '<div><dt>' + etq + '</dt><dd>' + (v === null || v === undefined || v === '' ? '<span class="pi-tenue">—</span>' : v) + '</dd></div>'; };
  var tel = function (n) { return n ? '<a href="tel:' + textoSeguro(n) + '">' + textoSeguro(n) + '</a>' : ''; };
  var tarjeta = function (titulo, icono, cuerpo, extra) {
    return '<section class="pi-tarjeta fc-tarjeta"><header><h2><i class="fas ' + icono + ' mr-2"></i>' + titulo + '</h2>' + (extra || '') + '</header>' + cuerpo + '</section>';
  };
  var h = '';

  // Encabezado
  var chips = '<span class="fc-chip">' + textoSeguro(a.documento) + '</span>';
  if (a.edad !== null) { chips += '<span class="fc-chip">' + textoSeguro(a.edad) + ' años</span>'; }
  if (a.sexo) { chips += '<span class="fc-chip">' + textoSeguro(T(a.sexo)) + '</span>'; }
  if (a.tipo) { chips += '<span class="fc-chip">' + (a.tipo === 'NUEVO' ? 'Estudiante nuevo' : 'Estudiante antiguo') + '</span>'; }
  if (m && m.acceso) { chips += '<span class="fc-chip ' + (m.acceso === 'ACTIVO' ? 'fc-ok' : 'fc-mal') + '">' + (m.acceso === 'ACTIVO' ? 'Con acceso al sistema' : 'Acceso desactivado') + '</span>'; }
  var anios = '';
  if (f.matriculas.length > 1) {
    anios = '<label class="fc-anio">Año <select id="ficha_anio" class="custom-select custom-select-sm">';
    f.matriculas.forEach(function (x) { anios += '<option value="' + x.id + '"' + (m && x.id === m.id ? ' selected' : '') + '>' + textoSeguro(x.anio) + '</option>'; });
    anios += '</select></label>';
  }
  h += '<section class="pi-tarjeta fc-cabecera">'
    + '<img class="fc-foto" src="' + textoSeguro(Ficha_Foto(a.foto)) + '" alt="Foto del estudiante" onerror="this.onerror=null;this.src=\'../img/blanco1.jpg\'">'
    + '<div class="fc-identidad"><h2>' + textoSeguro(T(a.nombres + ' ' + a.apellidos)) + '</h2>'
    + '<p class="fc-aula">' + (m ? textoSeguro(m.aula) + ' · ' + textoSeguro(m.anio) : '<span class="pi-tenue">Sin matrícula registrada</span>') + '</p>'
    + '<div class="fc-chips">' + chips + '</div></div>'
    + '<div class="fc-acciones">' + anios + '<button type="button" class="btn btn-outline-primary btn-sm" onclick="Ficha_Imprimir()"><i class="fas fa-print mr-1"></i>Imprimir ficha</button></div>'
    + '</section>';

  h += '<div class="fc-grilla">';
  // Datos personales y padres
  h += tarjeta('Datos personales', 'fa-id-card', '<dl class="fc-datos">'
    + dato('Nacimiento', a.nacimiento ? Ficha_Fecha(a.nacimiento) : '')
    + dato('Teléfono', tel(a.telefono))
    + dato('Dirección', a.direccion ? textoSeguro(T(a.direccion)) : '')
    + dato('Usuario', m && m.usuario ? textoSeguro(m.usuario) : '')
    + '</dl>');
  if (tiene('padres')) {
    var p = f.padres || {};
    var persona = function (nombre, doc, cel) {
      if (!nombre) { return '<span class="pi-tenue">Sin registrar</span>'; }
      return textoSeguro(T(nombre)) + '<small>' + (doc ? textoSeguro(doc) : '') + (cel ? ' · ' + tel(cel) : '') + '</small>';
    };
    h += tarjeta('Padres', 'fa-users', '<dl class="fc-datos fc-padres">'
      + dato('Padre', persona(p.padre, p.dni_padre, p.cel_padre))
      + dato('Madre', persona(p.madre, p.dni_madre, p.cel_madre)) + '</dl>');
  }
  h += '</div>';

  // Matrículas
  if (f.matriculas.length) {
    var filas = '';
    f.matriculas.forEach(function (x) {
      var beca = x.beca_tipo === 'PORCENTAJE' ? x.beca_valor + '%' : (x.beca_tipo === 'MONTO' ? formatoMoneda(x.beca_valor) : '');
      filas += '<tr' + (m && x.id === m.id ? ' class="fc-actual"' : '') + '><td>' + textoSeguro(x.anio) + '</td><td>' + textoSeguro(x.aula) + '</td><td>'
        + Ficha_Fecha(x.fecha) + '</td><td>' + (beca ? 'Beca ' + textoSeguro(beca) : '') + '</td><td>' + (x.procedencia ? textoSeguro(T(x.procedencia)) : '') + '</td></tr>';
    });
    h += tarjeta('Matrículas', 'fa-id-badge', '<div class="table-responsive"><table class="table table-sm fc-tabla"><thead><tr><th>Año</th><th>Aula</th><th>Fecha</th><th>Beca</th><th>Procedencia</th></tr></thead><tbody>' + filas + '</tbody></table></div>');
  }

  if (!m) { return h; }
  var del = ' <small class="pi-tenue">(' + textoSeguro(m.anio) + ')</small>';

  // Asistencia
  if (tiene('asistencia')) {
    var t = f.asistencia.total || {}, dias = +t.dias || 0;
    var cuerpo;
    if (!dias) {
      cuerpo = '<p class="pi-tenue mb-0">Todavía no hay asistencia registrada.</p>';
    } else {
      var pct = Math.round((dias - (+t.F || 0)) * 100 / dias);
      var barra = '<div class="pi-barra">';
      ['P', 'T', 'J', 'F'].forEach(function (k) { if (+t[k]) { barra += '<span class="pi-c-' + k + '" style="width:' + (+t[k] * 100 / dias) + '%"></span>'; } });
      barra += '</div>';
      cuerpo = '<p class="fc-cifra"><b>' + pct + '%</b> de asistencia en ' + PanelComun.plural(dias, 'día', 'días') + '</p>' + barra
        + '<ul class="pi-asis-cifras"><li><i class="pi-c-P"></i>' + PanelComun.plural(+t.P || 0, 'presente', 'presentes')
        + '</li><li><i class="pi-c-T"></i>' + PanelComun.plural(+t.T || 0, 'tardanza', 'tardanzas')
        + '</li><li><i class="pi-c-J"></i>' + PanelComun.plural(+t.J || 0, 'justificada', 'justificadas')
        + '</li><li><i class="pi-c-F"></i>' + PanelComun.plural(+t.F || 0, 'falta', 'faltas') + '</li></ul>';
      if (f.asistencia.faltas.length) {
        var nombres = { AUSENTE: 'Falta', TARDE: 'Tardanza', JUSTIFICADO: 'Justificada' };
        cuerpo += '<p class="fc-sub">Últimas faltas y tardanzas</p><ul class="fc-lista">';
        f.asistencia.faltas.forEach(function (x) {
          cuerpo += '<li><span>' + Ficha_Fecha(x.fecha) + '</span><span class="fc-estado fc-e-' + x.estado + '">' + nombres[x.estado] + '</span>'
            + (x.observacion ? '<small>' + textoSeguro(x.observacion) + '</small>' : '') + '</li>';
        });
        cuerpo += '</ul>';
      }
    }
    h += tarjeta('Asistencia' + del, 'fa-calendar-check', cuerpo);
  }

  // Notas: un curso por fila y un periodo por columna; promedio si todas son numéricas
  if (tiene('notas')) {
    var cuerpoN;
    if (!f.notas.length) {
      cuerpoN = '<p class="pi-tenue mb-0">Todavía no hay notas registradas.</p>';
    } else {
      var periodos = [], cursos = {}, orden = [];
      f.notas.forEach(function (x) {
        if (!periodos.some(function (p) { return p.id === x.id_periodo; })) { periodos.push({ id: x.id_periodo, nombre: x.periodo }); }
        if (!cursos[x.curso]) { cursos[x.curso] = {}; orden.push(x.curso); }
        (cursos[x.curso][x.id_periodo] = cursos[x.curso][x.id_periodo] || []).push($.trim(x.nota));
      });
      var celda = function (lista) {
        if (!lista) { return '<td class="pi-tenue">—</td>'; }
        var numeros = lista.every(function (v) { return v !== '' && !isNaN(v); });
        var v = numeros ? String(Math.round(lista.reduce(function (s, n) { return s + parseFloat(n); }, 0) / lista.length * 10) / 10) : lista.join(' ');
        var mal = numeros ? !notaAprobada(v) : lista.some(function (n) { return !notaAprobada(n); });
        return '<td class="' + (mal ? 'fc-desaprobado' : '') + '" title="' + textoSeguro(lista.length + (lista.length === 1 ? ' nota' : ' notas') + ': ' + lista.join(', ')) + '">' + textoSeguro(v) + '</td>';
      };
      cuerpoN = '<div class="table-responsive"><table class="table table-sm fc-tabla fc-notas"><thead><tr><th>Curso</th>';
      periodos.forEach(function (p) { cuerpoN += '<th>' + textoSeguro(PanelComun.nombrePeriodo(p.nombre)) + '</th>'; });
      cuerpoN += '</tr></thead><tbody>';
      orden.forEach(function (c) {
        cuerpoN += '<tr><td>' + textoSeguro(T(c)) + '</td>';
        periodos.forEach(function (p) { cuerpoN += celda(cursos[c][p.id]); });
        cuerpoN += '</tr>';
      });
      cuerpoN += '</tbody></table></div><p class="fc-nota-pie pi-tenue">Promedio de los criterios de cada periodo. Escala: ' + textoSeguro(descripcionEscala()) + '. En rojo, lo desaprobado.</p>';
    }
    h += tarjeta('Notas' + del, 'fa-star-half-alt', cuerpoN);
  }

  // Tareas
  if (tiene('tareas')) {
    var r = f.tareas.resumen || {}, total = +r.total || 0;
    var cuerpoT = !total ? '<p class="pi-tenue mb-0">No tiene tareas asignadas.</p>'
      : '<p class="fc-cifra"><b>' + (+r.entregadas || 0) + ' de ' + total + '</b>' + (total === 1 ? ' tarea entregada · ' : ' tareas entregadas · ') + PanelComun.plural(+r.calificadas || 0, 'calificada', 'calificadas') + '</p>';
    if (+r.vencidas) {
      cuerpoT += '<p class="fc-sub">Vencidas sin entregar (' + r.vencidas + ')</p><ul class="fc-lista">';
      f.tareas.vencidas.forEach(function (x) {
        cuerpoT += '<li><span>' + Ficha_Fecha(x.entrega) + '</span><span>' + textoSeguro(T(x.curso)) + '</span><small>' + textoSeguro(x.tema) + '</small></li>';
      });
      cuerpoT += '</ul>';
    }
    h += tarjeta('Tareas' + del, 'fa-tasks', cuerpoT);
  }

  // Pensiones y pagos
  if (tiene('pensiones')) {
    var deuda = 0, meses = '';
    f.pensiones.forEach(function (x) {
      var saldo = +x.saldo;
      var estado = +x.exonerada ? ['fc-ok', 'Exonerada'] : saldo <= 0 ? ['fc-ok', 'Pagada'] : +x.vencida ? ['fc-mal', 'Vencida'] : ['', 'Pendiente'];
      if (+x.vencida && saldo > 0) { deuda += saldo; }
      meses += '<li class="' + estado[0] + '" title="Vence el ' + Ficha_Fecha(x.vence) + '"><b>' + textoSeguro(T(x.mes)) + '</b><span>' + estado[1]
        + (saldo > 0 ? ' · ' + formatoMoneda(saldo) : '') + '</span></li>';
    });
    var cuerpoP = !f.pensiones.length ? '<p class="pi-tenue">No tiene pensiones generadas.</p>'
      : '<p class="fc-cifra">' + (deuda > 0 ? 'Deuda vencida: <b class="text-danger">' + formatoMoneda(deuda) + '</b>' : '<b>Sin deuda vencida</b>') + '</p><ul class="fc-meses">' + meses + '</ul>';
    if (f.pagos.length) {
      cuerpoP += '<p class="fc-sub">Pagos</p><div class="table-responsive"><table class="table table-sm fc-tabla"><thead><tr><th>Comprobante</th><th>Fecha</th><th>Conceptos</th><th class="text-right">Total</th></tr></thead><tbody>';
      f.pagos.forEach(function (x) {
        cuerpoP += '<tr' + (x.estado === 'ANULADO' ? ' class="fc-anulado" title="Anulado"' : '') + '><td>' + textoSeguro(x.comprobante) + '</td><td>' + Ficha_Fecha(x.fecha) + '</td><td>'
          + textoSeguro(T(x.conceptos)) + (x.estado === 'ANULADO' ? ' (anulado)' : '') + '</td><td class="text-right">' + formatoMoneda(x.total) + '</td></tr>';
      });
      cuerpoP += '</tbody></table></div>';
    }
    h += tarjeta('Pensiones y pagos' + del, 'fa-receipt', cuerpoP);
  }

  // Salud
  if (tiene('salud')) {
    var cuerpoS = !f.salud.length ? '<p class="pi-tenue mb-0">Sin atenciones registradas.</p>' : '<ul class="fc-lista">';
    f.salud.forEach(function (x) {
      cuerpoS += '<li><span>' + Ficha_Fecha(x.fecha) + '</span><span>' + (x.tipo === 'PSICOLOGIA' ? 'Psicología' : 'Enfermería') + '</span><small>'
        + textoSeguro(x.motivo) + (x.diagnostico ? ' — ' + textoSeguro(x.diagnostico) : '') + '</small></li>';
    });
    if (f.salud.length) { cuerpoS += '</ul>'; }
    h += tarjeta('Atenciones de salud', 'fa-briefcase-medical', cuerpoS);
  }
  return h;
}
