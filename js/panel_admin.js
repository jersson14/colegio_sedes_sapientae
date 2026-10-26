// Panel de inicio del administrador (Fase 12): estado del colegio hoy. Datos: controller/panel/controlador_panel_admin.php
window.PanelAdmin = (function () {
  var datos = null;
  var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

  function fecha(iso) { var p = String(iso).substr(0, 10).split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function dias(a, b) { return Math.round((fecha(b) - fecha(a)) / 86400000); }
  function corta(iso) { var f = fecha(iso); return f.getDate() + ' ' + MESES[f.getMonth()]; }
  function plural(n, uno, varios) { return n + ' ' + (n === 1 ? uno : varios); }
  // "IV BIMESTRE" -> "IV Bimestre" (el número romano queda en mayúsculas)
  function nombrePeriodo(t) {
    return String(t).split(' ').map(function (w) { return /^[IVX]+$/.test(w) ? w : w.charAt(0) + w.substr(1).toLowerCase(); }).join(' ');
  }

  function cabecera() {
    var hoy = fecha(datos.hoy);
    $('#pi_fecha').text(hoy.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
    var actual = (datos.periodos || []).filter(function (p) { return datos.hoy >= p.inicio && datos.hoy <= p.fin; })[0];
    if (actual) {
      $('#pi_periodo').text(nombrePeriodo(actual.nombre) + ' · semana ' + (Math.floor(dias(actual.inicio, datos.hoy) / 7) + 1)).show();
    } else if (datos.anio && datos.hoy >= datos.anio.fecha_inicio && datos.hoy <= datos.anio.fecha_fin) {
      $('#pi_periodo').text('Vacaciones entre bimestres').show();
    } else {
      $('#pi_periodo').hide();
    }
  }

  // Línea del año escolar: bimestres como tramos, la semana actual marcada como "Hoy"
  function lineaAnio() {
    var a = datos.anio;
    if (!a) {
      $('#pi_anio_nombre').text('Año escolar');
      $('#pi_linea').html('<p class="pi-tenue mb-0">No hay un año escolar activo. <a href="#" onclick="cargar_contenido(\'contenido_principal\',\'año/view_año_escolar.php\');return false;">Configurar año escolar</a></p>');
      $('#pi_anio_resumen').text('');
      return;
    }
    var total = Math.max(1, dias(a.fecha_inicio, a.fecha_fin));
    var pos = function (iso) { return Math.min(100, Math.max(0, dias(a.fecha_inicio, iso) * 100 / total)); };
    var h = '<div class="pi-pista">';
    (datos.periodos || []).forEach(function (p) {
      var ini = pos(p.inicio), fin = pos(p.fin);
      if (fin <= ini) { return; }
      var clase = datos.hoy > p.fin ? ' pi-pasado' : (datos.hoy >= p.inicio ? ' pi-actual' : '');
      h += '<div class="pi-tramo' + clase + '" style="left:' + ini + '%;width:' + (fin - ini) + '%" title="' + textoSeguro(p.nombre + ': ' + corta(p.inicio) + ' – ' + corta(p.fin)) + '">'
        + textoSeguro(p.nombre.replace(' BIMESTRE', ' bim.').replace(' TRIMESTRE', ' trim.')) + '</div>';
    });
    if (datos.hoy >= a.fecha_inicio && datos.hoy <= a.fecha_fin) {
      h += '<div class="pi-hoy" style="left:' + pos(datos.hoy) + '%"><span>Hoy</span></div>';
    }
    h += '</div><div class="pi-fechas"><span>Inicio ' + corta(a.fecha_inicio) + '</span><span>Fin ' + corta(a.fecha_fin) + '</span></div>';
    $('#pi_linea').html(h).attr('aria-label', 'Año escolar ' + a.anio + ': del ' + corta(a.fecha_inicio) + ' al ' + corta(a.fecha_fin));
    $('#pi_anio_nombre').text('Año escolar ' + a.anio);
    var resta = dias(datos.hoy, a.fecha_fin);
    $('#pi_anio_resumen').text(datos.hoy < a.fecha_inicio ? 'Empieza en ' + plural(dias(datos.hoy, a.fecha_inicio), 'día', 'días')
      : (resta >= 0 ? Math.round(pos(datos.hoy)) + '% del año · faltan ' + plural(resta, 'día', 'días') : 'Año escolar terminado'));
  }

  function comunidad() {
    var c = datos.comunidad;
    $('#pi_estudiantes').text(c.estudiantes);
    $('#pi_estudiantes_det').text(plural(c.aulas, 'aula', 'aulas') + (c.nuevos ? ' · ' + plural(c.nuevos, 'nuevo', 'nuevos') : ''));
    $('#pi_docentes').text(c.docentes);
    $('#pi_docentes_det').text(c.docentes_inactivos ? plural(c.docentes_inactivos, 'inactivo', 'inactivos') : 'todos activos');
    $('#pi_administrativos').text(c.administrativos);
    $('#pi_usuarios').text(c.usuarios);
    $('#pi_usuarios_det').text(c.usuarios_inactivos ? plural(c.usuarios_inactivos, 'inactivo', 'inactivos') : 'activos');
  }

  function asistencia() {
    var s = datos.asistencia, total = datos.comunidad.estudiantes;
    var dia = fecha(s.dia).toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
    $('#pi_asis_titulo').text(s.es_hoy ? 'Asistencia de hoy' : 'Asistencia del ' + dia);
    if (!s.en_anio) {
      $('#pi_asistencia').html('<p class="pi-tenue mb-0">Fuera del periodo de clases del año escolar.</p>');
      return;
    }
    var sin = Math.max(0, total - s.registrados);
    var asistieron = s.P + s.T;
    var pct = s.registrados ? Math.round((s.P + s.T + s.J) * 100 / s.registrados) : null;
    var tramo = function (n, c) { return n ? '<span class="pi-c-' + c + '" style="width:' + (n * 100 / Math.max(total, 1)) + '%" title="' + n + '"></span>' : ''; };
    var h = '<div class="pi-asis-grande">' + (pct === null
        ? '<b>0 de ' + total + '</b><span class="pi-tenue">estudiantes con asistencia registrada</span>'
        : '<b>' + pct + '%</b><span class="pi-tenue">' + plural(asistieron, 'estudiante asistió', 'estudiantes asistieron') + ' de ' + s.registrados + ' registrados</span>') + '</div>'
      + '<div class="pi-barra" aria-hidden="true">' + tramo(s.P, 'P') + tramo(s.T, 'T') + tramo(s.F, 'F') + tramo(s.J, 'J') + '</div>'
      + '<ul class="pi-asis-cifras">'
      + '<li><i class="pi-c-P"></i>Presentes <b>' + s.P + '</b></li><li><i class="pi-c-T"></i>Tardanzas <b>' + s.T + '</b></li>'
      + '<li><i class="pi-c-F"></i>Faltas <b>' + s.F + '</b></li><li><i class="pi-c-J"></i>Justificadas <b>' + s.J + '</b></li>'
      + '<li><i style="background:#D9D6CF"></i>Sin registro <b>' + sin + '</b></li></ul>';
    if (s.aulas_pendientes.length) {
      h += '<p class="mb-1" style="font-size:.88rem;font-weight:600">Aulas sin registro:</p><ul class="pi-aulas">'
        + s.aulas_pendientes.map(function (a) {
          return '<li><button type="button" title="Abrir el registro mensual" onclick="cargar_contenido(\'contenido_principal\',\'asistencia/view_registro_asistencia.php\')">' + textoSeguro(a.aula) + '</button></li>';
        }).join('') + '</ul>';
    } else if (s.registrados) {
      h += '<p class="pi-ok mb-0"><i class="fas fa-check-circle"></i> Todas las aulas registraron asistencia.</p>';
    }
    $('#pi_asistencia').html(h);
  }

  function caja() {
    var k = datos.caja;
    $('#pi_ing_hoy').text(formatoMoneda(k.ingresos_hoy));
    $('#pi_egr_hoy').text(formatoMoneda(k.egresos_hoy));
    $('#pi_ing_mes').text(formatoMoneda(k.ingresos_mes));
    $('#pi_recibos').text(k.recibos_hoy);
  }

  function pendientes() {
    var p = datos.pendientes, k = datos.caja, items = [];
    var ir = function (vista, texto) { return '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'' + vista + '\');return false;">' + texto + '</a>'; };
    items.push(+k.alumnos_morosos
      ? { c: 'pi-alerta', i: 'fa-file-invoice-dollar', t: plural(+k.alumnos_morosos, 'estudiante con pensiones vencidas', 'estudiantes con pensiones vencidas'), s: 'Deuda vencida: ' + formatoMoneda(k.morosidad), a: ir('reportes/view_reportes_financieros.php', 'Ver morosidad') }
      : { c: 'pi-bien', i: 'fa-check', t: 'Sin pensiones vencidas', s: 'Las familias están al día', a: '' });
    items.push(p.tareas_por_calificar
      ? { c: 'pi-aviso', i: 'fa-inbox', t: plural(p.tareas_por_calificar, 'tarea entregada por calificar', 'tareas entregadas por calificar'), s: 'Los docentes las ven en su registro de tareas', a: ir('tareas/view_registro_tareas.php', 'Ver registro') }
      : { c: 'pi-bien', i: 'fa-check', t: 'Todas las tareas entregadas están calificadas', s: '', a: '' });
    items.push({ c: 'pi-info', i: 'fa-notes-medical', t: 'Atenciones de salud este mes', s: 'Enfermería ' + p.enfermeria_mes + ' · Psicología ' + p.psicologia_mes, a: ir('atenciones/view_atencion_enfermeria.php', 'Ver') });
    var r = datos.ultimo_respaldo, hace = r ? dias(r.substr(0, 10), datos.hoy) : null;
    items.push(r === null || hace > 7
      ? { c: 'pi-alerta', i: 'fa-database', t: r === null ? 'No hay respaldos de la base de datos' : 'Último respaldo hace ' + plural(hace, 'día', 'días'), s: 'Genere uno y descárguelo fuera del servidor', a: ir('configuracion/view_respaldos.php', 'Respaldar') }
      : { c: 'pi-bien', i: 'fa-database', t: 'Respaldo al día', s: hace === 0 ? 'Último respaldo: hoy' : 'Último respaldo hace ' + plural(hace, 'día', 'días'), a: ir('configuracion/view_respaldos.php', 'Ver') });
    $('#pi_pendientes').html(items.map(function (x) {
      return '<li class="' + x.c + '"><span class="pi-ico"><i class="fas ' + x.i + '"></i></span><span class="pi-texto">' + textoSeguro(x.t)
        + (x.s ? '<small>' + textoSeguro(x.s) + '</small>' : '') + '</span>' + x.a + '</li>';
    }).join(''));
  }

  function comunicados() {
    var l = datos.comunicados || [];
    if (!l.length) {
      $('#pi_comunicados').html('<li class="pi-vacio">Todavía no hay comunicados. <a href="#" onclick="cargar_contenido(\'contenido_principal\',\'comunicado/view_comunicado.php\');return false;">Publicar el primero</a></li>');
      return;
    }
    $('#pi_comunicados').html(l.map(function (c) {
      var f = fecha(c.fecha);
      var dirigido = c.tipo === 'GENERAL' ? 'Para todo el colegio' : 'Para ' + (textoPlano(c.grado) || 'un grado');
      return '<li><span class="pi-dia"><b>' + f.getDate() + '</b><span>' + MESES[f.getMonth()] + '</span></span>'
        + '<span class="pi-tit" title="' + textoSeguro(c.titulo) + '">' + textoSeguro(c.titulo) + '<small>' + textoSeguro(dirigido) + '</small></span>'
        + '<span class="pi-estado ' + (c.estado === 'ACTIVO' ? 'on">Publicado' : 'off">Oculto') + '</span></li>';
    }).join(''));
  }

  function institucion() {
    var e = datos.empresa || {};
    $('#pi_inst_nombre').text(textoPlano(e.emp_razon || ''));
    $('#pi_inst_cod').text(textoPlano(e.emp_cod || '—'));
    $('#pi_inst_tel').text(textoPlano(e.emp_telefono || '—'));
    $('#pi_inst_email').text(textoPlano(e.emp_email || '—'));
    $('#pi_inst_dir').text(textoPlano(e.emp_direccion || '—'));
    if (e.emp_logo) { $('#pi_inst_logo').attr('src', '../' + e.emp_logo); }
  }

  function cargar() {
    $.post('../controller/panel/controlador_panel_admin.php', {}, null, 'json').done(function (r) {
      datos = r;
      cabecera(); lineaAnio(); comunidad(); asistencia(); caja(); pendientes(); comunicados(); institucion();
    }).fail(function () {
      $('#pi_asistencia, #pi_pendientes, #pi_comunicados').html('<p class="text-danger mb-0">No se pudo cargar esta información. Recargue la página.</p>');
    });
  }

  // Editar datos y escudo: reutiliza las ventanas de la institución (js/console_empresa.js)
  function editarInstitucion() { if (datos && datos.empresa) { abrirEditarEmpresa(datos.empresa); } }
  function cambiarLogo() { if (datos && datos.empresa) { abrirFotoEmpresa(datos.empresa); } }

  return { cargar: cargar, editarInstitucion: editarInstitucion, cambiarLogo: cambiarLogo };
})();
