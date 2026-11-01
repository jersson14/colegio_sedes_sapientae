// Panel de inicio del administrador (Fase 12): estado del colegio hoy. Datos: controller/panel/controlador_panel_admin.php
window.PanelAdmin = (function () {
  var datos = null;
  // Piezas comunes en js/panel_comun.js
  var C = PanelComun, MESES = C.MESES, fecha = C.fecha, dias = C.dias, plural = C.plural;
  function cabecera() { C.cabecera(datos); }
  function lineaAnio() { C.lineaAnio(datos, true); }

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
          return '<li><button type="button" title="Registrar la asistencia de hoy de esta aula" onclick="Asistencia_Abrir(' + (+a.id) + ')">' + textoSeguro(a.aula) + '</button></li>';
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
    items.push({ c: 'pi-info', i: 'fa-notes-medical', t: 'Atenciones de salud este mes', s: 'Enfermería ' + p.enfermeria_mes + ' · Psicología ' + p.psicologia_mes, a: ir('salud/view_enfermeria.php', 'Ver') });
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
    C.comunicados(datos.comunicados, '#pi_comunicados', 'Todavía no hay comunicados. ' + C.ir('comunicado/view_comunicado.php', 'Publicar el primero'));
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
