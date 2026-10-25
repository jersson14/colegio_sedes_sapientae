// Registro mensual de asistencia (Fase 11): planilla estudiantes × días del mes, como la hoja de asistencia en papel.
// Clic o teclado para marcar; los cambios se guardan juntos con "Guardar".
window.RegistroAsistencia = (function () {
  var ORDEN = ['', 'P', 'T', 'F', 'J'];
  var NOMBRES = { P: 'Presente', T: 'Tardanza', F: 'Falta', J: 'Justificada' };
  var datos = null;
  var cambios = {};   // "matricula|fecha" -> código ('' = borrar)

  function clave(m, f) { return m + '|' + f; }
  function pendientes() { return Object.keys(cambios).length; }

  function marca(est, dia) {
    var k = clave(est.matricula, dia.fecha);
    return cambios.hasOwnProperty(k) ? cambios[k] : (est.marcas[dia.dia] || '');
  }

  function totales(est) {
    var t = { P: 0, T: 0, F: 0, J: 0 };
    datos.dias.forEach(function (d) { var c = marca(est, d); if (t.hasOwnProperty(c)) { t[c]++; } });
    var n = t.P + t.T + t.F + t.J;
    t.pct = n ? Math.round((t.P + t.T + t.J) * 100 / n) + '%' : '';
    return t;
  }

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
      pintarFiltros();
      pintar();
    }).fail(function () {
      $('#ra_tabla').html('<tbody><tr><td class="p-3 text-danger">No se pudo cargar la planilla.</td></tr></tbody>');
    });
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
    $('#ra_btn_hoy').prop('disabled', !hoy).attr('title', hoy ? '' : 'Disponible en el mes actual (lunes a viernes)');
  }

  function pintar() {
    var d = datos.dias, h = '';
    if (!datos.aula) {
      $('#ra_tabla').html('<tbody><tr><td class="p-3 text-muted">No tiene aulas con estudiantes matriculados en este año.</td></tr></tbody>');
      return;
    }
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
        var c = marca(e, x);
        h += '<td class="ra-celda' + (c ? ' ra-' + c : '') + (x.habilitado ? '' : ' ra-bloq') + (x.hoy ? ' ra-col-hoy' : '')
          + (cambios.hasOwnProperty(clave(e.matricula, x.fecha)) ? ' ra-cambio' : '') + '" data-r="' + r + '" data-i="' + i + '"'
          + (x.habilitado ? ' tabindex="0"' : '') + ' title="' + (c ? NOMBRES[c] : '') + '">' + c + '</td>';
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
    var p = pendientes();
    $('#ra_pendientes').text(p).toggle(p > 0);
  }

  function poner(r, i, c) {
    var e = datos.estudiantes[r], x = datos.dias[i];
    if (!e || !x || !x.habilitado) { return; }
    var original = e.marcas[x.dia] || '', k = clave(e.matricula, x.fecha);
    if (c === original) { delete cambios[k]; } else { cambios[k] = c; }
  }

  function enfocar(r, i) {
    var celda = $('#ra_tabla td.ra-celda[data-r="' + r + '"][data-i="' + i + '"]');
    if (celda.length) { celda[0].focus(); }
  }

  function repintarEn(r, i) { pintar(); enfocar(r, i); }

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
    $('#ra_anio').off('change').on('change', function () { $('#ra_aula').val(''); cargar(); });
    $('#ra_aula, #ra_mes').off('change').on('change', function () { cargar(); });
  }

  function todosPresentesHoy() {
    var i = datos.dias.findIndex(function (x) { return x.hoy; });
    if (i < 0) { return; }
    var n = 0;
    datos.estudiantes.forEach(function (e, r) { if (!marca(e, datos.dias[i])) { poner(r, i, 'P'); n++; } });
    pintar();
    Swal.fire({ toast: true, position: 'top-end', timer: 3500, showConfirmButton: false, icon: 'info',
      title: n + ' marcado(s) como presentes. Cambie a T, F o J a quien corresponda y pulse Guardar.' });
  }

  function guardar() {
    var lista = Object.keys(cambios).map(function (k) {
      var p = k.split('|');
      return { matricula: +p[0], fecha: p[1], codigo: cambios[k] };
    });
    if (!lista.length) {
      return Swal.fire('Sin cambios', 'No hay marcas nuevas para guardar.', 'info');
    }
    $.post('../controller/registro_asistencia/controlador_guardar.php', {
      anio: datos.anio.id, aula: datos.aula.id, cambios: JSON.stringify(lista)
    }, null, 'json').done(function (r) {
      cambios = {};
      Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: 'success', title: 'Asistencia guardada (' + r.guardados + ')' });
      cargar({ anio: datos.anio.id, aula: datos.aula.id, mes: datos.mes });
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

  function iniciar() {
    eventos();
    cargar({ anio: 0, aula: 0, mes: '' });
  }

  return { iniciar: iniciar, guardar: guardar, todosPresentesHoy: todosPresentesHoy, moverMes: moverMes, pdf: pdf, excel: excel };
})();
