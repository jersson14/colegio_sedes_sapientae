// Registro de tareas (Fase 11): estudiantes × tareas de un curso, con estado de entrega y nota.
// Desde la casilla se ve lo entregado y se califica (usa el mismo controlador de calificación de la pantalla de tareas).
window.RegistroTareas = (function () {
  var datos = null;

  function cargar(params) {
    $('#rt_tabla').html('<tbody><tr><td class="p-3 text-muted">Cargando...</td></tr></tbody>');
    $.post('../controller/registro_tareas/controlador_registro.php', params || {
      anio: $('#rt_anio').val() || 0, curso: $('#rt_curso').val() || 0, periodo: $('#rt_periodo').val() || 0
    }, null, 'json').done(function (r) {
      datos = r;
      filtros();
      pintar();
    }).fail(function () {
      $('#rt_tabla').html('<tbody><tr><td class="p-3 text-danger">No se pudo cargar el registro.</td></tr></tbody>');
    });
  }

  function nombreCurso(c) {
    return c.nivel + ' - ' + c.grado + (c.seccion && c.seccion !== 'UNICO' ? ' ' + c.seccion : '') + ' · ' + c.asignatura;
  }

  function filtros() {
    $('#rt_anio').html(datos.anios.map(function (a) {
      return '<option value="' + a.id + '">' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
    }).join('')).val(datos.anio);
    $('#rt_curso').html(datos.cursos.length ? datos.cursos.map(function (c) {
      return '<option value="' + c.id + '">' + textoSeguro(nombreCurso(c)) + (txtDocente(c)) + ' (' + c.tareas + ' tareas)</option>';
    }).join('') : '<option value="">Sin cursos asignados</option>').val(datos.curso ? datos.curso.id : '');
    $('#rt_periodo').html('<option value="0">Todo el año</option>' + datos.periodos.map(function (p) {
      return '<option value="' + p.id + '">' + textoSeguro(p.nombre) + '</option>';
    }).join('')).val(datos.periodo);
  }
  function txtDocente(c) { return $('#txtprincipalrol').val() === 'DOCENTE' ? '' : textoSeguro(' — ' + c.docente); }

  // Estado de una casilla: calificada, entregada, pendiente o no entregó (pendiente con plazo vencido)
  function estado(env, tarea) {
    if (!env) { return { tipo: 'sin', html: '<span class="text-muted">–</span>', texto: '' }; }
    var tarde = env.fecha_envio && tarea.fecha_entrega && env.fecha_envio > tarea.fecha_entrega;
    var r = estadoBase(env, tarea);
    if (tarde && (r.tipo === 'nota' || r.tipo === 'enviado')) {
      r.html += ' <i class="fas fa-clock text-warning" title="Entregada fuera de plazo"></i>';
      r.texto += ' (fuera de plazo)';
    }
    return r;
  }
  function estadoBase(env, tarea) {
    if (env.estado === 'CALIFICADO') {
      var ok = notaAprobada(env.calificacion);
      return { tipo: 'nota', nota: env.calificacion, texto: env.calificacion,
        html: '<span class="' + (ok ? 'rt-nota-ok' : 'rt-nota-mal') + '">' + textoSeguro(env.calificacion) + '</span>' };
    }
    if (env.estado === 'ENVIADO') { return { tipo: 'enviado', texto: 'Entregada', html: '<i class="fas fa-inbox text-primary" title="Entregada, por calificar"></i>' }; }
    if (tarea.fecha_entrega && tarea.fecha_entrega < datos.ahora) {
      return { tipo: 'falta', texto: 'No entregó', html: '<i class="fas fa-times text-danger" title="No entregó"></i>' };
    }
    return { tipo: 'pendiente', texto: 'Pendiente', html: '<i class="fas fa-hourglass-half text-secondary" title="Pendiente"></i>' };
  }

  function resumen(e) {
    var entregadas = 0, notas = [];
    datos.tareas.forEach(function (t) {
      var s = estado(e.envios[t.id], t);
      if (s.tipo === 'nota' || s.tipo === 'enviado') { entregadas++; }
      if (s.tipo === 'nota' && !isNaN(parseFloat(s.nota)) && isFinite(s.nota)) { notas.push(parseFloat(s.nota)); }
    });
    var dec = (window.REGIONAL && +REGIONAL.nota_decimales) || 0;
    var prom = notas.length ? (notas.reduce(function (a, b) { return a + b; }, 0) / notas.length).toFixed(Math.max(dec, 1)) : '';
    return { entregadas: entregadas, promedio: prom };
  }

  function pintar() {
    if (!datos.curso) {
      $('#rt_tabla').html('<tbody><tr><td class="p-3 text-muted">No tiene cursos asignados en este año.</td></tr></tbody>');
      return;
    }
    var T = datos.tareas, h = '<thead><tr><th class="rt-fija">Nº &nbsp; ESTUDIANTE</th>';
    T.forEach(function (t, i) {
      h += '<th class="rt-tarea" data-i="' + i + '" title="Ver material: ' + textoSeguro(t.tema) + '">' + textoSeguro(t.tema)
        + '<small>' + textoSeguro((t.fecha_entrega || '').substr(0, 10).split('-').reverse().join('/')) + '</small></th>';
    });
    h += '<th>Entregadas</th><th>Promedio</th></tr></thead><tbody>';
    if (!T.length) {
      h += '<tr><td class="rt-fija text-muted" colspan="3">El curso no tiene tareas en este periodo.</td></tr>';
    }
    datos.estudiantes.forEach(function (e, r) {
      h += '<tr><td class="rt-fija">' + (r + 1) + '. ' + textoSeguro(e.apellidos + ', ' + e.nombres) + '</td>';
      T.forEach(function (t, i) {
        h += '<td class="rt-celda" data-r="' + r + '" data-i="' + i + '">' + estado(e.envios[t.id], t).html + '</td>';
      });
      var s = resumen(e);
      h += '<td class="rt-tot">' + s.entregadas + '/' + T.length + '</td><td class="rt-tot">' + textoSeguro(s.promedio) + '</td></tr>';
    });
    // fila de resumen por tarea
    if (T.length) {
      h += '</tbody><tfoot><tr><td class="rt-fija"><b>Entregadas</b></td>';
      T.forEach(function (t) {
        var n = datos.estudiantes.filter(function (e) { var s = estado(e.envios[t.id], t).tipo; return s === 'nota' || s === 'enviado'; }).length;
        h += '<td class="rt-tot">' + n + '/' + datos.estudiantes.length + '</td>';
      });
      h += '<td colspan="2"></td></tr></tfoot>';
    }
    $('#rt_tabla').html(h);
  }

  function urlMaterial(carpeta) {
    return '../controller/tareas/controlador_archivos_tarea.php?carpeta=' + encodeURIComponent(carpeta);
  }

  function calificar(r, i) {
    var e = datos.estudiantes[r], t = datos.tareas[i], env = e.envios[t.id];
    if (!env) { return; }
    var s = estado(env, t);
    var entrega = env.carpeta ? '<a class="btn btn-sm btn-outline-primary mb-2" target="_blank" href="' + urlMaterial(env.carpeta) + '"><i class="fas fa-folder-open"></i> Ver lo que entregó</a>'
      : '<p class="text-muted small mb-2">No subió archivos' + (s.tipo === 'falta' ? ' (plazo vencido)' : '') + '.</p>';
    Swal.fire({
      title: textoSeguro(t.tema),
      html: '<p class="mb-1"><b>' + textoSeguro(e.apellidos + ', ' + e.nombres) + '</b><br><small>' + textoSeguro(s.texto || '')
        + (env.fecha_envio ? ' · enviada el ' + textoSeguro(env.fecha_envio) : '') + '</small></p>' + entrega
        + '<input id="rt_nota" class="swal2-input" style="width:60%" placeholder="Nota (' + textoSeguro(descripcionEscala()) + ')" value="' + textoSeguro(env.estado === 'CALIFICADO' ? env.calificacion : '') + '">'
        + '<textarea id="rt_obs" class="swal2-textarea" style="width:80%" placeholder="Observación (opcional)">' + textoSeguro(env.observacion || '') + '</textarea>',
      showCancelButton: true, confirmButtonText: '<i class="fas fa-save"></i> Guardar nota', cancelButtonText: 'Cerrar',
      preConfirm: function () {
        var nota = document.getElementById('rt_nota').value.trim();
        if (!notaValida(nota)) { Swal.showValidationMessage('La nota debe estar entre ' + descripcionEscala()); return false; }
        return { nota: nota, obser: document.getElementById('rt_obs').value.trim() };
      }
    }).then(function (res) {
      if (!res.isConfirmed) { return; }
      $.post('../controller/tareas/controlador_registro_calificacion.php', { id: env.id, nota: res.value.nota, obser: res.value.obser }).done(function () {
        Swal.fire({ toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, icon: 'success', title: 'Nota guardada' });
        cargar({ anio: datos.anio, curso: datos.curso.id, periodo: datos.periodo });
      });
    });
  }

  function tabla() {
    var cab = ['Nº', 'Estudiante'].concat(datos.tareas.map(function (t) { return t.tema + ' (' + (t.fecha_entrega || '').substr(0, 10) + ')'; }), ['Entregadas', 'Promedio']);
    var filas = datos.estudiantes.map(function (e, r) {
      var s = resumen(e);
      return [r + 1, e.apellidos + ', ' + e.nombres].concat(datos.tareas.map(function (t) { return estado(e.envios[t.id], t).texto; }),
        [s.entregadas + '/' + datos.tareas.length, s.promedio]);
    });
    return { cab: cab, filas: filas };
  }
  function titulo() { return 'REGISTRO DE TAREAS - ' + nombreCurso(datos.curso) + ' - ' + $('#rt_periodo option:selected').text(); }

  function excel() {
    if (!datos || !datos.curso) { return; }
    var d = tabla();
    var tmp = $('<table class="sin-mejoras"></table>').appendTo($('<div style="display:none">').appendTo('body'));
    var t = tmp.DataTable({ data: d.filas, columns: d.cab.map(function (c) { return { title: c }; }), paging: false, ordering: false, dom: 't',
      buttons: [{ extend: 'excelHtml5', title: titulo(), filename: 'registro_tareas' }] });
    t.button(0).trigger();
    t.destroy();
    tmp.parent().remove();
  }

  function imprimir() {
    if (!datos || !datos.curso) { return; }
    var d = tabla();
    var w = window.open('', '_blank');
    w.document.write('<html><head><meta charset="utf-8"><title>' + textoSeguro(titulo()) + '</title><style>body{font-family:Arial;font-size:11px}'
      + 'table{border-collapse:collapse;width:100%}th,td{border:1px solid #777;padding:3px;text-align:center}td:nth-child(2){text-align:left}th{background:#eee}</style></head><body>'
      + '<h3>' + textoSeguro(titulo()) + '</h3><table><tr>' + d.cab.map(function (c) { return '<th>' + textoSeguro(c) + '</th>'; }).join('') + '</tr>'
      + d.filas.map(function (f) { return '<tr>' + f.map(function (c) { return '<td>' + textoSeguro(c) + '</td>'; }).join('') + '</tr>'; }).join('')
      + '</table></body></html>');
    w.document.close();
    w.focus();
    w.print();
  }

  function iniciar() {
    $(document).off('.rt');
    $(document).on('click.rt', '#rt_tabla td.rt-celda', function () { calificar(+$(this).data('r'), +$(this).data('i')); });
    $(document).on('click.rt', '#rt_tabla th.rt-tarea', function () {
      var t = datos.tareas[+$(this).data('i')];
      if (t.carpeta) { window.open(urlMaterial(t.carpeta), '_blank'); }
    });
    $('#rt_anio').off('change').on('change', function () { $('#rt_curso').val(''); $('#rt_periodo').val(0); cargar(); });
    $('#rt_curso, #rt_periodo').off('change').on('change', function () { cargar(); });
    // Desde el aula virtual se abre directamente en un curso
    var inicial = window.RT_CURSO_INICIAL || 0;
    window.RT_CURSO_INICIAL = 0;
    cargar({ anio: 0, curso: inicial, periodo: 0 });
  }

  return { iniciar: iniciar, excel: excel, imprimir: imprimir };
})();
