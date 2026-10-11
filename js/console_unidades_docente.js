// Fase 5.9: el docente califica a los matriculados en sus unidades (view/matricula_unidades/view_unidades_docente.php).
var UnidadesDocente = (function () {
  var URL = '../controller/matricula_unidades/controlador_unidades_docente.php';
  var ESTADOS = { MATRICULADO: ['primary', 'En curso'], APROBADO: ['success', 'Aprobada'], DESAPROBADO: ['danger', 'Desaprobada'] };

  function esc(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
  }

  function cargarMisUnidades() {
    $.ajax({ url: URL, type: 'GET', data: { periodo: $('#ud_periodo').val() }, dataType: 'json' }).done(function (unidades) {
      $('#ud_unidad').html(unidades.length
        ? unidades.map(function (u) {
          return '<option value="' + u.id_unidad + '">' + esc(u.codigo + ' · ' + u.nombre + ' (' + u.programa + ') — ' + u.matriculados + ' matriculados') + '</option>';
        }).join('')
        : '<option value="">No tienes unidades asignadas en este periodo</option>');
      cargarMatriculados();
    });
  }

  function cargarMatriculados() {
    var unidad = $('#ud_unidad').val();
    if (!unidad) {
      $('#tabla_unidades_docente tbody').html('<tr><td colspan="5" class="text-muted">Sin unidades en este periodo.</td></tr>');
      return;
    }
    $.ajax({ url: URL, type: 'GET', data: { unidad: unidad, periodo: $('#ud_periodo').val() }, dataType: 'json' }).done(function (alumnos) {
      var cfg = window.INSTITUCION || { notaMinima: 13, recuperacionDesde: 10 };
      var filas = alumnos.map(function (a) {
        var estado = ESTADOS[a.estado] || ['light', a.estado];
        var nota = a.nota_final == null ? '—' : esc(a.nota_final) + (a.nota_recuperacion != null ? ' · recuperación: ' + esc(a.nota_recuperacion) : '');
        var accion = '';
        if (a.estado === 'MATRICULADO') {
          accion = '<button class="btn btn-xs btn-primary calificar-unidad" onclick="UnidadesDocente.calificarDocente(' + a.id_matricula_unidad + ')">Calificar</button>';
        } else if (a.estado === 'DESAPROBADO' && a.nota_recuperacion == null
            && parseFloat(a.nota_final) >= cfg.recuperacionDesde && parseFloat(a.nota_final) < cfg.notaMinima) {
          accion = '<button class="btn btn-xs btn-warning recuperar-unidad" onclick="UnidadesDocente.recuperarDocente(' + a.id_matricula_unidad + ')">Recuperación</button>';
        }
        return '<tr data-matricula="' + a.id_matricula_unidad + '" data-estado="' + esc(a.estado) + '"><td>' + esc(a.dni) + '</td><td>' + esc(a.alumno)
          + '</td><td><span class="badge badge-' + estado[0] + '">' + estado[1] + '</span></td><td>' + nota + '</td><td>' + accion + '</td></tr>';
      });
      $('#tabla_unidades_docente tbody').html(filas.length ? filas.join('') : '<tr><td colspan="5" class="text-muted">Nadie matriculado en esta unidad.</td></tr>');
    });
  }

  function registrarNotaDocente(que, id) {
    Swal.fire({
      title: que === 'calificar' ? 'Nota final de la unidad' : 'Nota de la evaluación de recuperación',
      input: 'text', inputPlaceholder: 'De 0 a 20 (p. ej. 14 o 13.5)', showCancelButton: true,
      confirmButtonText: 'Registrar', cancelButtonText: 'Cancelar'
    }).then(function (r) {
      if (!r.isConfirmed) { return; }
      $.ajax({ url: URL, type: 'POST', dataType: 'json', data: { accion: que, id: id, nota: String(r.value).replace(',', '.').trim() } }).done(function (res) {
        Swal.fire(res.codigo === 1 ? 'Mensaje de Confirmación' : 'Mensaje de Advertencia', res.mensaje, res.codigo === 1 ? 'success' : 'warning');
        cargarMatriculados();
      }).fail(function (xhr) {
        Swal.fire('Mensaje de Error', (xhr.responseJSON && xhr.responseJSON.error) || 'No se completó el proceso', 'error');
      });
    });
  }

  return {
    iniciar: function () {
      $.ajax({ url: URL, type: 'GET', dataType: 'json' }).done(function (c) {
        $('#ud_periodo').html(c.periodos.map(function (x) { return '<option value="' + x.id + '">' + esc(x.nombre) + '</option>'; }).join(''));
        $('#ud_periodo').on('change', cargarMisUnidades);
        $('#ud_unidad').on('change', cargarMatriculados);
        cargarMisUnidades();
      });
    },
    calificarDocente: function (id) { registrarNotaDocente('calificar', id); },
    recuperarDocente: function (id) { registrarNotaDocente('recuperar', id); }
  };
})();
