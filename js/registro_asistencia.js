// Registro de asistencia (Fase 11; modo Día y observaciones en Fase 13): la única pantalla para registrar asistencia.
// - Mes: planilla estudiantes × días, como la hoja en papel. Clic o teclado para marcar.
// - Día: un día a la vez, con botones grandes y observación por estudiante (pensado para el celular).
// Los cambios se guardan juntos con "Guardar".
window.RegistroAsistencia = (function () {
  var ORDEN = ['', 'P', 'T', 'F', 'J'];
  var NOMBRES = { P: 'Presente', T: 'Tardanza', F: 'Falta', J: 'Justificada' };
  var datos = null;
  var cambios = {};   // "matricula|fecha" -> código ('' = borrar)
  var notas = {};     // "matricula|fecha" -> observación nueva
  var modo = 'mes';
  var diaSel = -1;    // índice en datos.dias del día que se ve en modo Día
  var irA = null;     // al cargar otro mes desde el modo Día: 'primero' o 'ultimo'

  function clave(m, f) { return m + '|' + f; }
  function pendientes() { return Object.keys($.extend({}, cambios, notas)).length; }

  function marca(est, dia) {
    var k = clave(est.matricula, dia.fecha);
    return cambios.hasOwnProperty(k) ? cambios[k] : (est.marcas[dia.dia] || '');
  }
  function observacion(est, dia) {
    var k = clave(est.matricula, dia.fecha);
    return notas.hasOwnProperty(k) ? notas[k] : textoPlano((est.obs && est.obs[dia.dia]) || '');
  }

  function totales(est) {
    var t = { P: 0, T: 0, F: 0, J: 0 };
    datos.dias.forEach(function (d) { var c = marca(est, d); if (t.hasOwnProperty(c)) { t[c]++; } });
    var n = t.P + t.T + t.F + t.J;
    t.pct = n ? Math.round((t.P + t.T + t.J) * 100 / n) + '%' : '';
    return t;
  }

  function guardarModo(m) { try { localStorage.setItem('ra_modo', m); } catch (e) {} }
  function modoGuardado() { try { return localStorage.getItem('ra_modo'); } catch (e) { return null; } }

  function cargar(params) {
    if (pendientes() && !confirm('Tiene ' + pendientes() + ' cambio(s) sin guardar. ¿Descartarlos?')) {
      pintarFiltros();
      return;
    }
    $('#ra_tabla').html('<tbody><tr><td class="p-3 text-muted">Cargando...</td></tr></tbody>');
    $.post('../controller/registro_asistencia/controlador_planilla.php', params || {
      anio: $('#ra_anio').val() || 0, aula: $('#ra_aula').val() || 0, mes: $('#ra_mes').val()
    }, null, 'json').done(function (r) {
      datos = r;
      cambios = {};
      notas = {};
      if (r.nombres) { NOMBRES = r.nombres; pintarLeyenda(); }   // nombres de Configuración → Parámetros
      elegirDia();
      pintarFiltros();
      pintar();
    }).fail(function () {
      $('#ra_tabla').html('<tbody><tr><td class="p-3 text-danger">No se pudo cargar la planilla.</td></tr></tbody>');
      $('#ra_dia').html('<p class="p-3 text-danger">No se pudo cargar la asistencia.</p>');
    });
  }

  // Día que se muestra en modo Día: hoy si está en el mes; si no, el último día habilitado (o el primero, al avanzar)
  function elegirDia() {
    var d = datos.dias, hab = d.map(function (x, i) { return x.habilitado ? i : -1; }).filter(function (i) { return i >= 0; });
    var hoy = d.findIndex(function (x) { return x.hoy; });
    if (irA === 'primero') { diaSel = hab.length ? hab[0] : -1; }
    else if (irA === 'ultimo') { diaSel = hab.length ? hab[hab.length - 1] : -1; }
    else { diaSel = hoy >= 0 ? hoy : (hab.length ? hab[hab.length - 1] : -1); }
    irA = null;
  }

  function pintarLeyenda() {
    $('.ra-leyenda').html(['P', 'T', 'F', 'J'].map(function (c) { return '<span class="ra-' + c + '">' + c + '</span>' + textoSeguro(NOMBRES[c]); }).join(' &nbsp; '));
  }

  function pintarFiltros() {
    if (!datos) { return; }
    $('#ra_anio').html(datos.anios.map(function (a) {
      return '<option value="' + a.id + '">' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
    }).join('')).val(datos.anio.id);
    $('#ra_aula').html(datos.aulas.length ? datos.aulas.map(function (a) {
      return '<option value="' + a.id + '">' + textoSeguro(a.nivel + ' - ' + a.grado + (a.seccion && a.seccion !== 'UNICO' ? ' ' + a.seccion : ''))
        + ' (' + a.alumnos + ')</option>';
    }).join('') : '<option value="">Sin aulas con estudiantes</option>').val(datos.aula ? datos.aula.id : '');
    $('#ra_mes').val(datos.mes);
    var hoy = datos.dias.some(function (d) { return d.hoy; });
    $('#ra_btn_hoy').prop('disabled', modo === 'mes' && !hoy).attr('title', modo === 'mes' && !hoy ? 'Disponible en el mes actual (días de clase)' : '')
      .html('<i class="fas fa-check-double"></i> ' + (modo === 'dia' ? 'Todos presentes' : 'Hoy: todos presentes'));
  }

  function pintar() {
    $('.ra-modo [data-modo]').removeClass('active').attr('aria-pressed', 'false').filter('[data-modo="' + modo + '"]').addClass('active').attr('aria-pressed', 'true');
    $('.ra-solo-mes').toggle(modo === 'mes');
    $('#ra_dia').toggle(modo === 'dia');
    var p = pendientes();
    $('#ra_pendientes').text(p).toggle(p > 0);
    if (!datos.aula) {
      var vacio = 'No tiene aulas con estudiantes matriculados en este año.';
      $('#ra_tabla').html('<tbody><tr><td class="p-3 text-muted">' + vacio + '</td></tr></tbody>');
      $('#ra_dia').html('<p class="p-3 text-muted mb-0">' + vacio + '</p>');
      return;
    }
    if (modo === 'dia') { pintarDia(); } else { pintarMes(); }
  }

  function pintarMes() {
    var d = datos.dias, h = '';
    var fuera = d.filter(function (x) { return !x.en_anio; }).length;
    $('#ra_aviso').html(fuera ? '<div class="alert alert-light border py-1 small"><i class="fas fa-info-circle text-info"></i> Los días en gris claro están fuera de las fechas del año escolar '
      + textoSeguro(datos.anio.anio) + ' (' + textoSeguro(datos.anio.fecha_inicio || '') + ' a ' + textoSeguro(datos.anio.fecha_fin || '') + ').</div>' : '');
    // Encabezado: semanas y días
    var semanas = {};
    d.forEach(function (x) { semanas[x.semana] = (semanas[x.semana] || 0) + 1; });
    h += '<thead><tr><th rowspan="2" class="ra-num">Nº</th><th rowspan="2" class="ra-fija ra-nombre">APELLIDOS Y NOMBRES<br><small class="text-muted">'
      + textoSeguro(datos.nombre_mes) + '</small></th>';
    Object.keys(semanas).forEach(function (s) { h += '<th colspan="' + semanas[s] + '" class="ra-sem' + s + '">SEMANA ' + s + '</th>'; });
    h += '<th colspan="5">RESUMEN</th></tr><tr>';
    d.forEach(function (x, i) {
      h += '<th class="ra-dia ra-sem' + x.semana + (x.hoy ? ' ra-hoy' : '') + (x.en_anio ? '' : ' ra-fuera') + '" data-i="' + i + '" title="'
        + (x.habilitado ? 'Marcar a todos en este día' : 'Día futuro') + '">' + x.letra + '<br>' + (x.dia < 10 ? '0' : '') + x.dia + '</th>';
    });
    h += '<th class="ra-tot ra-P">P</th><th class="ra-tot ra-T">T</th><th class="ra-tot ra-F">F</th><th class="ra-tot ra-J">J</th><th class="ra-tot">%</th></tr></thead><tbody>';
    // Estudiantes
    datos.estudiantes.forEach(function (e, r) {
      h += '<tr data-r="' + r + '"><td class="ra-num">' + (r + 1) + '</td><td class="ra-fija ra-nombre" title="' + textoSeguro(e.documento) + '">'
        + textoSeguro(e.apellidos + ', ' + e.nombres) + '</td>';
      d.forEach(function (x, i) {
        var c = marca(e, x), o = c ? observacion(e, x) : '', k = clave(e.matricula, x.fecha);
        h += '<td class="ra-celda' + (c ? ' ra-' + c : '') + (o ? ' ra-obs' : '') + (x.habilitado ? '' : ' ra-bloq') + (x.hoy ? ' ra-col-hoy' : '')
          + (cambios.hasOwnProperty(k) || notas.hasOwnProperty(k) ? ' ra-cambio' : '') + '" data-r="' + r + '" data-i="' + i + '"'
          + (x.habilitado ? ' tabindex="0"' : '') + ' title="' + (c ? NOMBRES[c] : '') + (o ? ' — ' + textoSeguro(o) : '') + '">' + c + '</td>';
      });
      var t = totales(e);
      h += '<td class="ra-tot">' + t.P + '</td><td class="ra-tot">' + t.T + '</td><td class="ra-tot">' + t.F + '</td><td class="ra-tot">' + t.J
        + '</td><td class="ra-tot">' + t.pct + '</td></tr>';
    });
    if (!datos.estudiantes.length) {
      h += '<tr><td colspan="' + (d.length + 7) + '" class="p-3 text-muted">El aula no tiene estudiantes matriculados.</td></tr>';
    }
    // Pie: asistentes por día
    h += '</tbody><tfoot><tr><td class="ra-num"></td><td class="ra-fija ra-nombre">Asistentes (P+T)</td>';
    d.forEach(function (x) {
      var n = 0;
      datos.estudiantes.forEach(function (e) { var c = marca(e, x); if (c === 'P' || c === 'T') { n++; } });
      h += '<td>' + (n || '') + '</td>';
    });
    h += '<td colspan="5"></td></tr></tfoot>';
    $('#ra_tabla').html(h);
  }

  function pintarDia() {
    $('#ra_aviso').html('');
    var x = datos.dias[diaSel];
    var nav = '<div class="ra-dia-nav">'
      + '<button type="button" class="btn btn-outline-secondary" onclick="RegistroAsistencia.moverDia(-1)" title="Día de clase anterior" aria-label="Día anterior"><i class="fas fa-chevron-left"></i></button>'
      + '<div class="ra-dia-fecha">' + (x ? '<b>' + fechaLarga(x.fecha) + '</b>' + (x.hoy ? '<span class="ra-hoy-chip">Hoy</span>' : '') : '<b>Sin días de clase para registrar</b>') + '</div>'
      + '<button type="button" class="btn btn-outline-secondary" onclick="RegistroAsistencia.moverDia(1)" title="Día de clase siguiente" aria-label="Día siguiente"' + (puedeAvanzar() ? '' : ' disabled') + '><i class="fas fa-chevron-right"></i></button>'
      + '</div>';
    if (!x) {
      $('#ra_dia').html(nav + '<p class="text-muted p-2 mb-0">En este mes todavía no hay días de clase hasta hoy.</p>');
      return;
    }
    var cuenta = { P: 0, T: 0, F: 0, J: 0, '': 0 };
    datos.estudiantes.forEach(function (e) { cuenta[marca(e, x)]++; });
    var h = nav + '<p class="ra-dia-resumen">' + datos.estudiantes.length + (datos.estudiantes.length === 1 ? ' estudiante · ' : ' estudiantes · ') + '<span class="ra-P">' + cuenta.P + ' P</span> <span class="ra-T">' + cuenta.T
      + ' T</span> <span class="ra-F">' + cuenta.F + ' F</span> <span class="ra-J">' + cuenta.J + ' J</span>' + (cuenta[''] ? ' · <b>' + cuenta[''] + ' sin marcar</b>' : '') + '</p>';
    if (!x.en_anio) {
      h += '<div class="alert alert-light border py-1 small"><i class="fas fa-info-circle text-info"></i> Este día está fuera de las fechas del año escolar.</div>';
    }
    h += '<ol class="ra-lista">';
    datos.estudiantes.forEach(function (e, r) {
      var c = marca(e, x), o = observacion(e, x), k = clave(e.matricula, x.fecha);
      h += '<li class="ra-fila' + (cambios.hasOwnProperty(k) || notas.hasOwnProperty(k) ? ' ra-cambio' : '') + '" data-r="' + r + '">'
        + '<span class="ra-fila-num">' + (r + 1) + '</span>'
        + '<span class="ra-fila-nombre">' + textoSeguro(e.apellidos + ', ' + e.nombres) + '</span>'
        + '<span class="ra-botones" role="radiogroup" aria-label="Asistencia de ' + textoSeguro(e.nombres) + '">';
      ['P', 'T', 'F', 'J'].forEach(function (cod) {
        h += '<button type="button" class="ra-btn' + (c === cod ? ' ra-' + cod + ' activo' : '') + '" data-cod="' + cod + '" role="radio" aria-checked="' + (c === cod) + '" title="' + NOMBRES[cod] + '">' + cod + '</button>';
      });
      h += '</span><input type="text" class="form-control form-control-sm ra-obs-input" maxlength="255" placeholder="' + (c ? 'Observación (opcional)' : 'Marque primero la asistencia')
        + '" value="' + textoSeguro(o) + '"' + (c ? '' : ' disabled') + ' aria-label="Observación de ' + textoSeguro(e.nombres) + '"></li>';
    });
    h += '</ol>';
    if (!datos.estudiantes.length) { h += '<p class="text-muted p-2 mb-0">El aula no tiene estudiantes matriculados.</p>'; }
    $('#ra_dia').html(h);
  }

  function fechaLarga(iso) {
    var p = iso.split('-'), f = new Date(+p[0], +p[1] - 1, +p[2]);
    var t = f.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
    return textoSeguro(t.charAt(0).toUpperCase() + t.slice(1));
  }

  function puedeAvanzar() {
    var hab = datos.dias.filter(function (x, i) { return i > diaSel && x.habilitado; });
    if (hab.length) { return true; }
    // pasar al mes siguiente solo si ese mes ya empezó
    var p = datos.mes.split('-'), sig = new Date(+p[0], +p[1], 1);
    return sig <= new Date();
  }

  function moverDia(n) {
    var i = diaSel + n;
    while (i >= 0 && i < datos.dias.length && !datos.dias[i].habilitado) { i += n; }
    if (i >= 0 && i < datos.dias.length) {
      diaSel = i;
      pintar();
      return;
    }
    if (n > 0 && !puedeAvanzar()) { return; }
    irA = n < 0 ? 'ultimo' : 'primero';
    moverMes(n);
  }

  function poner(r, i, c) {
    var e = datos.estudiantes[r], x = datos.dias[i];
    if (!e || !x || !x.habilitado) { return; }
    var original = e.marcas[x.dia] || '', k = clave(e.matricula, x.fecha);
    if (c === original) { delete cambios[k]; } else { cambios[k] = c; }
  }

  function anotar(r, i, texto) {
    var e = datos.estudiantes[r], x = datos.dias[i];
    if (!e || !x) { return; }
    var original = textoPlano((e.obs && e.obs[x.dia]) || ''), k = clave(e.matricula, x.fecha);
    texto = $.trim(texto);
    if (texto === original) { delete notas[k]; } else { notas[k] = texto; }
  }

  function enfocar(r, i) {
    var celda = $('#ra_tabla td.ra-celda[data-r="' + r + '"][data-i="' + i + '"]');
    if (celda.length) { celda[0].focus(); }
  }

  function repintarEn(r, i) { pintar(); enfocar(r, i); }

  function cambiarModo(m) {
    modo = m === 'dia' ? 'dia' : 'mes';
    guardarModo(modo);
    if (datos) {
      if (diaSel < 0) { elegirDia(); }
      pintarFiltros();
      pintar();
    }
  }

  function eventos() {
    $(document).off('.ra');
    $(document).on('click.ra', '#ra_tabla td.ra-celda:not(.ra-bloq)', function () {
      var r = +$(this).data('r'), i = +$(this).data('i');
      var actual = marca(datos.estudiantes[r], datos.dias[i]);
      poner(r, i, ORDEN[(ORDEN.indexOf(actual) + 1) % ORDEN.length]);
      repintarEn(r, i);
    });
    $(document).on('keydown.ra', '#ra_tabla td.ra-celda', function (ev) {
      var r = +$(this).data('r'), i = +$(this).data('i'), k = ev.key.toUpperCase();
      var mov = { ARROWUP: [-1, 0], ARROWDOWN: [1, 0], ARROWLEFT: [0, -1], ARROWRIGHT: [0, 1] }[k];
      if (mov) { ev.preventDefault(); enfocar(r + mov[0], i + mov[1]); return; }
      if (['P', 'T', 'F', 'J'].indexOf(k) !== -1 || k === 'DELETE' || k === 'BACKSPACE') {
        ev.preventDefault();
        poner(r, i, k.length === 1 ? k : '');
        repintarEn(Math.min(r + 1, datos.estudiantes.length - 1), i);   // baja al siguiente estudiante, como en la hoja
      }
    });
    $(document).on('click.ra', '#ra_tabla th.ra-dia', function () {
      var i = +$(this).data('i'), x = datos.dias[i];
      if (!x.habilitado) { return; }
      var sin = datos.estudiantes.filter(function (e) { return !marca(e, x); }).length;
      Swal.fire({
        title: x.letra + ' ' + x.dia + ' de ' + datos.nombre_mes.toLowerCase(),
        html: sin ? 'Hay <b>' + sin + '</b> estudiante(s) sin marca este día.' : 'Todos tienen marca este día.',
        showDenyButton: true, showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Marcar presentes a los que faltan',
        denyButtonText: '<i class="fas fa-eraser"></i> Limpiar el día', cancelButtonText: 'Cancelar'
      }).then(function (res) {
        if (res.isConfirmed) {
          datos.estudiantes.forEach(function (e, r) { if (!marca(e, x)) { poner(r, i, 'P'); } });
        } else if (res.isDenied) {
          datos.estudiantes.forEach(function (e, r) { poner(r, i, ''); });
        }
        pintar();
      });
    });
    // Modo Día: un toque marca; tocar de nuevo la misma letra la quita
    $(document).on('click.ra', '#ra_dia .ra-btn', function () {
      var r = +$(this).closest('.ra-fila').data('r'), cod = $(this).data('cod');
      poner(r, diaSel, marca(datos.estudiantes[r], datos.dias[diaSel]) === cod ? '' : cod);
      pintar();
      var fila = $('#ra_dia .ra-fila[data-r="' + r + '"] .ra-btn[data-cod="' + cod + '"]');
      if (fila.length) { fila[0].focus(); }
    });
    $(document).on('change.ra', '#ra_dia .ra-obs-input', function () {
      anotar(+$(this).closest('.ra-fila').data('r'), diaSel, this.value);
      var p = pendientes();
      $('#ra_pendientes').text(p).toggle(p > 0);
      $(this).closest('.ra-fila').toggleClass('ra-cambio', true);
    });
    $(document).on('click.ra', '.ra-modo [data-modo]', function () { cambiarModo($(this).data('modo')); });
    $('#ra_anio').off('change').on('change', function () { $('#ra_aula').val(''); cargar(); });
    $('#ra_aula, #ra_mes').off('change').on('change', function () { cargar(); });
  }

  // Marca presentes a los que no tienen marca: el día elegido (modo Día) o hoy (modo Mes)
  function todosPresentesHoy() {
    var i = modo === 'dia' ? diaSel : datos.dias.findIndex(function (x) { return x.hoy; });
    if (i < 0) { return; }
    var n = 0;
    datos.estudiantes.forEach(function (e, r) { if (!marca(e, datos.dias[i])) { poner(r, i, 'P'); n++; } });
    pintar();
    Swal.fire({ toast: true, position: 'top-end', timer: 3500, showConfirmButton: false, icon: 'info',
      title: n + ' marcado(s) como presentes. Cambie a T, F o J a quien corresponda y pulse Guardar.' });
  }

  function guardar() {
    // la observación de la fila en edición todavía no disparó "change"
    $('#ra_dia .ra-obs-input:focus').trigger('change');
    var lista = Object.keys($.extend({}, cambios, notas)).map(function (k) {
      var p = k.split('|'), m = +p[0];
      var e = datos.estudiantes.filter(function (x) { return +x.matricula === m; })[0];
      var dia = datos.dias.filter(function (x) { return x.fecha === p[1]; })[0];
      var item = { matricula: m, fecha: p[1], codigo: marca(e, dia) };
      if (notas.hasOwnProperty(k)) { item.obs = notas[k]; }
      return item;
    }).filter(function (x) { return x.codigo !== '' || cambios.hasOwnProperty(clave(x.matricula, x.fecha)); });
    if (!lista.length) {
      return Swal.fire('Sin cambios', 'No hay marcas nuevas para guardar.', 'info');
    }
    $.post('../controller/registro_asistencia/controlador_guardar.php', {
      anio: datos.anio.id, aula: datos.aula.id, cambios: JSON.stringify(lista)
    }, null, 'json').done(function (r) {
      cambios = {};
      notas = {};
      var fecha = modo === 'dia' && datos.dias[diaSel] ? datos.dias[diaSel].fecha : null;
      Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Asistencia guardada (' + r.guardados + ')' });
      $.post('../controller/registro_asistencia/controlador_planilla.php', { anio: datos.anio.id, aula: datos.aula.id, mes: datos.mes }, null, 'json').done(function (d) {
        datos = d;
        var i = fecha ? datos.dias.findIndex(function (x) { return x.fecha === fecha; }) : -1;
        if (i >= 0) { diaSel = i; } else { elegirDia(); }
        pintarFiltros();
        pintar();
      });
    });
  }

  function moverMes(n) {
    var p = ($('#ra_mes').val() || datos.mes).split('-'), f = new Date(+p[0], +p[1] - 1 + n, 1);
    $('#ra_mes').val(f.getFullYear() + '-' + String(f.getMonth() + 1).padStart(2, '0'));
    cargar();
  }

  function pdf(vacia) {
    if (!datos || !datos.aula) { return; }
    if (!vacia && pendientes()) { return Swal.fire('Cambios sin guardar', 'Guarde antes de imprimir para que la planilla incluya todas las marcas.', 'warning'); }
    window.open('../view/MPDF/REPORTE/asistencia_mensual.php?anio=' + datos.anio.id + '&aula=' + datos.aula.id + '&mes=' + datos.mes + (vacia ? '&vacia=1' : ''), '_blank');
  }

  // Excel con el mismo contenido de la planilla (usa la exportación de DataTables)
  function excel() {
    if (!datos || !datos.aula) { return; }
    var cab = ['Nº', 'Apellidos y nombres', datosDoc()].concat(datos.dias.map(function (x) { return x.letra + ' ' + x.dia; }), ['P', 'T', 'F', 'J', '%']);
    var filas = datos.estudiantes.map(function (e, r) {
      var t = totales(e);
      return [r + 1, e.apellidos + ', ' + e.nombres, e.documento].concat(datos.dias.map(function (x) { return marca(e, x); }), [t.P, t.T, t.F, t.J, t.pct]);
    });
    var tmp = $('<table class="sin-mejoras"><thead><tr></tr></thead></table>').appendTo($('<div style="display:none">').appendTo('body'));
    var aula = datos.aula.nivel + ' ' + datos.aula.grado + (datos.aula.seccion !== 'UNICO' ? ' ' + datos.aula.seccion : '');
    var t = tmp.DataTable({ data: filas, columns: cab.map(function (c) { return { title: c }; }), paging: false, ordering: false, dom: 't',
      buttons: [{ extend: 'excelHtml5', title: 'ASISTENCIA ' + datos.nombre_mes + ' - ' + aula, filename: 'asistencia_' + datos.mes + '_' + aula.replace(/\W+/g, '_') }] });
    t.button(0).trigger();
    t.destroy();
    tmp.parent().remove();
  }
  function datosDoc() { return (window.REGIONAL && REGIONAL.doc_nombre) || 'Documento'; }

  // Desde los paneles de inicio se puede abrir directo un aula en modo Día: Asistencia_Abrir(aula) en js/panel_comun.js
  function iniciar() {
    var ini = window.__asistenciaInicial || {};
    window.__asistenciaInicial = null;
    modo = ini.modo || modoGuardado() || (window.innerWidth < 768 ? 'dia' : 'mes');
    eventos();
    cargar({ anio: 0, aula: ini.aula || 0, mes: '' });
  }

  return { iniciar: iniciar, guardar: guardar, todosPresentesHoy: todosPresentesHoy, moverMes: moverMes, moverDia: moverDia,
           cambiarModo: cambiarModo, pdf: pdf, excel: excel };
})();

