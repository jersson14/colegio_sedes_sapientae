// Paneles de inicio de docentes, estudiantes, auxiliar, enfermería y psicología (Fase 13, paso 4).
// Datos: controller/panel/controlador_panel_rol.php (siempre los del usuario de la sesión).
window.PanelRol = (function () {
  var C = PanelComun, d = null;

  function item(clase, icono, texto, sub, accion) {
    return '<li class="' + clase + '"><span class="pi-ico"><i class="fas ' + icono + '"></i></span><span class="pi-texto">' + texto
      + (sub ? '<small>' + sub + '</small>' : '') + '</span>' + (accion || '') + '</li>';
  }
  function vacio(sel, texto) { $(sel).html('<li class="pi-vacio">' + texto + '</li>'); }
  function cuando(iso) {
    var dif = C.dias(d.hoy, iso);
    var f = C.fecha(iso), otroAnio = f.getFullYear() !== C.fecha(d.hoy).getFullYear();
    var dia = dif === 0 ? 'Hoy' : (dif === 1 ? 'Mañana' : (dif === -1 ? 'Ayer'
      : f.toLocaleDateString('es-PE', otroAnio ? { day: 'numeric', month: 'short', year: 'numeric' } : { weekday: 'short', day: 'numeric', month: 'short' })));
    var h = C.hora(iso);
    return dia + (h && h !== '00:00' ? ' ' + h : '');
  }

  // Clases del día con la que está en curso resaltada
  function clases(mostrarAula) {
    if (!d.dia) {
      $('#pr_clases_titulo').text('Clases');
      return vacio('#pr_clases', 'Hoy no hay clases (fin de semana).');
    }
    if (!d.clases || !d.clases.length) { return vacio('#pr_clases', 'No tiene clases registradas en el horario de hoy.'); }
    var ahora = String(d.ahora).substr(11, 5);
    $('#pr_clases').html(d.clases.map(function (c) {
      var estado = ahora >= c.fin ? 'pasada' : (ahora >= c.inicio ? 'ahora' : '');
      return '<li class="pr-clase ' + estado + '"><span class="pr-hora">' + c.inicio + '<small>' + c.fin + '</small></span><span class="pr-curso">'
        + textoSeguro(C.titulo(c.curso)) + '<small>' + textoSeguro(mostrarAula ? C.titulo(c.aula) : C.titulo(c.docente)) + '</small></span>'
        + (estado === 'ahora' ? '<span class="pr-ahora">Ahora</span>' : '') + '</li>';
    }).join(''));
  }

  function examenes() {
    if (!d.examenes || !d.examenes.length) { return vacio('#pr_examenes', 'No hay exámenes próximos.'); }
    $('#pr_examenes').html(d.examenes.map(function (e) {
      return item('pi-info', 'fa-file-signature', textoSeguro(C.titulo(e.tema)), textoSeguro(cuando(e.fecha) + ' · ' + C.titulo(e.curso) + (e.aula ? ' · ' + C.titulo(e.aula) : '')));
    }).join(''));
  }

  function docente() {
    clases(true);
    if (!d.aulas.length) { vacio('#pr_aulas', 'No tiene aulas con estudiantes este año.'); } else {
      $('#pr_aulas').html(d.aulas.map(function (a) {
        return +a.registrada
          ? item('pi-bien', 'fa-check', textoSeguro(C.titulo(a.aula)), 'Asistencia registrada')
          : item('pi-aviso', 'fa-clipboard-list', textoSeguro(C.titulo(a.aula)), d.dia ? 'Falta registrar la asistencia de hoy' : 'Sin clases hoy',
                 d.dia ? '<a href="#" onclick="Asistencia_Abrir(' + (+a.id) + ');return false;">Registrar</a>' : '');
      }).join(''));
    }
    if (!d.por_calificar.length) { vacio('#pr_tareas', '<i class="fas fa-check-circle text-success"></i> No tiene entregas por calificar.'); } else {
      $('#pr_tareas').html(d.por_calificar.map(function (t) {
        return item('pi-aviso', 'fa-inbox', textoSeguro(C.titulo(t.tema)), textoSeguro(C.titulo(t.curso)) + ' · ' + C.plural(+t.entregas, 'entrega', 'entregas') + ' por calificar',
                    C.ir('tareas/view_registro_tareas.php', 'Calificar'));
      }).join(''));
    }
    examenes();
  }

  function estudiante() {
    if (!d.matricula) {
      $('#pr_clases, #pr_tareas, #pr_examenes, #pr_notas, #pr_pensiones').html('<li class="pi-vacio">No tiene una matrícula en el año escolar actual.</li>');
      $('#pr_asistencia').html('<p class="pi-tenue mb-0">Sin matrícula en el año actual.</p>');
      return;
    }
    $('#pr_aula').text(C.titulo(d.matricula.aula));
    clases(false);
    if (!d.tareas.length) { vacio('#pr_tareas', '<i class="fas fa-check-circle text-success"></i> No tiene tareas pendientes.'); } else {
      $('#pr_tareas').html(d.tareas.map(function (t) {
        return item(+t.vencida ? 'pi-alerta' : 'pi-aviso', +t.vencida ? 'fa-exclamation' : 'fa-hourglass-half', textoSeguro(C.titulo(t.tema)),
          textoSeguro(C.titulo(t.curso)) + ' · ' + (+t.vencida ? 'venció ' : 'entrega ') + textoSeguro(cuando(t.entrega).toLowerCase()),
          C.ir('estudiante/view_mis_tareas.php', +t.vencida ? 'Enviar' : 'Ver'));
      }).join(''));
    }
    examenes();
    if (!d.notas.length) { vacio('#pr_notas', 'Aún no tiene tareas calificadas.'); } else {
      $('#pr_notas').html(d.notas.map(function (n) {
        var ok = notaAprobada(n.nota);
        return item(ok ? 'pi-bien' : 'pi-alerta', ok ? 'fa-check' : 'fa-redo', textoSeguro(C.titulo(n.tema)), textoSeguro(C.titulo(n.curso)),
          '<b class="pr-nota ' + (ok ? 'ok' : 'mal') + '">' + textoSeguro(n.nota) + '</b>');
      }).join(''));
    }
    var a = d.asistencia, total = a.P + a.T + a.F + a.J;
    $('#pr_asistencia').html(total
      ? '<div class="pi-asis-grande"><b>' + Math.round((a.P + a.T + a.J) * 100 / total) + '%</b><span class="pi-tenue">de asistencia este mes</span></div>'
        + '<ul class="pi-asis-cifras mt-2"><li><i class="pi-c-P"></i>Presente <b>' + a.P + '</b></li><li><i class="pi-c-T"></i>Tardanzas <b>' + a.T
        + '</b></li><li><i class="pi-c-F"></i>Faltas <b>' + a.F + '</b></li><li><i class="pi-c-J"></i>Justificadas <b>' + a.J + '</b></li></ul>'
      : '<p class="pi-tenue mb-0">Aún no hay asistencia registrada este mes.</p>');
    if (!d.pensiones.length) { vacio('#pr_pensiones', '<i class="fas fa-check-circle text-success"></i> Está al día con sus pensiones.'); } else {
      $('#pr_pensiones').html(d.pensiones.map(function (p) {
        return item(+p.vencida ? 'pi-alerta' : 'pi-info', 'fa-receipt', textoSeguro(C.titulo(p.mes)) + ': ' + formatoMoneda(p.saldo),
          (+p.vencida ? 'Venció el ' : 'Vence el ') + C.fecha(p.vence).toLocaleDateString('es-PE', { day: 'numeric', month: 'long' }));
      }).join(''));
    }
  }

  function auxiliar() {
    if (!d.aulas.length) { return $('#pr_aulas_aux').html('<p class="pi-tenue mb-0">No hay aulas con estudiantes en el año actual.</p>'); }
    var faltan = d.aulas.filter(function (a) { return +a.registrados === 0; }).length;
    $('#pr_aulas_aux').html('<p class="mb-2">' + (d.dia ? (faltan ? '<b>' + C.plural(faltan, 'aula', 'aulas') + '</b> sin asistencia registrada hoy.' : '<span class="pi-ok"><i class="fas fa-check-circle"></i> Todas las aulas registraron asistencia.</span>') : 'Hoy no hay clases.') + '</p>'
      + '<div class="pr-grilla">' + d.aulas.map(function (a) {
        var pct = +a.estudiantes ? Math.round(+a.registrados * 100 / +a.estudiantes) : 0;
        return '<button type="button" class="pr-aula ' + (pct >= 100 ? 'ok' : (pct ? 'parcial' : 'falta')) + '" onclick="Asistencia_Abrir(' + (+a.id) + ')">'
          + '<b>' + textoSeguro(C.titulo(a.aula)) + '</b><span>' + a.registrados + ' de ' + a.estudiantes + ' registrados</span></button>';
      }).join('') + '</div>');
  }

  function salud() {
    $('#pr_salud_hoy').text(d.atenciones_hoy);
    $('#pr_salud_mes').text(d.atenciones_mes);
    if (!d.recientes.length) { return vacio('#pr_salud', 'Aún no hay atenciones registradas.'); }
    $('#pr_salud').html(d.recientes.map(function (r) {
      return item('pi-info', d.tipo === 'ENFERMERIA' ? 'fa-notes-medical' : 'fa-brain', textoSeguro(C.titulo(r.estudiante)),
        textoSeguro(cuando(r.fecha) + ' · ' + C.titulo(r.motivo || 'Sin motivo registrado')));
    }).join(''));
  }

  function cargar() {
    $.post('../controller/panel/controlador_panel_rol.php', {}, null, 'json').done(function (r) {
      d = r;
      C.cabecera(d);
      C.lineaAnio(d, false);
      ({ DOCENTE: docente, ESTUDIANTE: estudiante, AUXILIAR: auxiliar, ENFERMERA: salud, PSICOLOGA: salud }[d.rol] || function () {})();
      C.comunicados(d.comunicados, '#pi_comunicados');
    }).fail(function () {
      $('.pi .pi-tarjeta ul, .pi .pi-tarjeta > div').first().html('<p class="text-danger mb-0">No se pudo cargar el panel. Recargue la página.</p>');
    });
  }

  return { cargar: cargar };
})();
