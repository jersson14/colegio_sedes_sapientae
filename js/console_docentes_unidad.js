// Fase 5.9: docente de cada unidad didáctica por periodo (view/plan_estudios/view_docentes_unidad.php).
var DocentesUnidad = (function () {
  var URL = '../controller/plan_estudios/controlador_docentes_unidad.php';
  var ROMANOS = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
  var docentes = [];

  function esc(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
  }

  function listarAsignaciones() {
    var programa = $('#du_programa').val();
    var periodo = $('#du_periodo').val();
    if (!programa || !periodo) { return; }
    $.ajax({ url: URL, type: 'GET', data: { programa: programa, periodo: periodo }, dataType: 'json' }).done(function (unidades) {
      var filas = unidades.map(function (u) {
        var opciones = '<option value="0">— Sin docente —</option>' + docentes.map(function (d) {
          return '<option value="' + d.id + '"' + (String(d.id) === String(u.id_docente) ? ' selected' : '') + '>' + esc(d.nombre) + '</option>';
        }).join('');
        return '<tr data-unidad="' + u.id_unidad + '"><td>' + ROMANOS[u.periodo_academico] + '</td><td>' + esc(u.codigo) + '</td><td>' + esc(u.nombre)
          + '</td><td>' + esc(u.modulo) + '</td><td><select class="form-control form-control-sm docente-unidad" data-unidad="' + u.id_unidad + '">' + opciones + '</select></td></tr>';
      });
      $('#tabla_docentes_unidad tbody').html(filas.length ? filas.join('') : '<tr><td colspan="5" class="text-muted">El programa no tiene unidades activas.</td></tr>');
    });
  }

  function guardarAsignacion(select) {
    $.ajax({ url: URL, type: 'POST', dataType: 'json',
      data: { unidad: $(select).data('unidad'), periodo: $('#du_periodo').val(), docente: $(select).val() } }).done(function (r) {
      if (r.codigo === 1) {
        Swal.fire({ toast: true, position: 'top-end', timer: 1800, showConfirmButton: false, icon: 'success', title: r.mensaje });
      } else {
        Swal.fire('Mensaje de Advertencia', r.mensaje, 'warning');
        listarAsignaciones();
      }
    }).fail(function (xhr) {
      Swal.fire('Mensaje de Error', (xhr.responseJSON && xhr.responseJSON.error) || 'No se completó el proceso', 'error');
    });
  }

  return {
    iniciar: function () {
      $.ajax({ url: URL, type: 'GET', dataType: 'json' }).done(function (c) {
        docentes = c.docentes;
        $('#du_programa').html(c.programas.map(function (x) { return '<option value="' + x.id + '">' + esc(x.nombre) + '</option>'; }).join(''));
        $('#du_periodo').html(c.periodos.map(function (x) { return '<option value="' + x.id + '">' + esc(x.nombre) + '</option>'; }).join(''));
        $('#du_programa, #du_periodo').on('change', listarAsignaciones);
        $('#tabla_docentes_unidad').on('change', '.docente-unidad', function () { guardarAsignacion(this); });
        listarAsignaciones();
      });
    }
  };
})();
