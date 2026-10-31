// Respaldos de la base de datos (Fase 11): generar, descargar y eliminar. Solo el administrador.
window.Respaldos = (function () {
  var URL = '../controller/respaldos/controlador_respaldos.php';
  var tabla = null;

  function tamano(b) {
    return b < 1048576 ? Math.max(1, Math.round(b / 1024)) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
  }

  function listar() {
    if (tabla) { tabla.ajax.reload(); return; }
    tabla = $('#tabla_respaldos').DataTable({
      ajax: { url: URL, type: 'POST', data: { accion: 'listar' }, dataSrc: 'respaldos' },
      order: [[0, 'desc']], pageLength: 10, language: window.idioma_espanol, dom: 'frtip',
      columns: [
        { data: 'fecha', render: function (d, t) { return t === 'display' ? textoSeguro(d.substr(8, 2) + '/' + d.substr(5, 2) + '/' + d.substr(0, 4) + ' ' + d.substr(11, 5)) : d; } },
        { data: 'archivo' },
        { data: 'tipo' },
        { data: 'tamano', render: function (d, t) { return t === 'display' ? tamano(d) : d; } },
        { data: 'archivo', orderable: false, render: function (d, t) {
          if (t !== 'display') { return d; }
          return '<div class="text-nowrap"><a class="btn btn-success btn-sm" href="' + URL + '?accion=descargar&archivo=' + encodeURIComponent(d) + '"><i class="fas fa-download"></i> Descargar</a> '
            + '<button class="btn btn-outline-danger btn-sm eliminar" data-archivo="' + textoSeguro(d) + '" title="Eliminar"><i class="fas fa-trash"></i></button></div>';
        } }
      ]
    });
  }

  function generar() {
    var btn = $('#btn_generar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generando respaldo...');
    $('#resultado_respaldo').html('');
    $.post(URL, { accion: 'generar' }, null, 'json').done(function (r) {
      var avisos = (r.avisos || []).map(function (a) { return '<div class="alert alert-warning small mt-2 mb-0">' + textoSeguro(a) + '</div>'; }).join('');
      $('#resultado_respaldo').html('<div class="alert alert-success mb-0"><b><i class="fas fa-check-circle"></i> Respaldo generado</b><br>'
        + '<small>' + r.tablas + ' tablas · ' + r.filas + ' registros · ' + r.rutinas + ' procedimientos · ' + r.triggers + ' triggers · ' + tamano(r.tamano) + '</small><br>'
        + '<a class="btn btn-warning btn-sm mt-2 text-dark" href="' + URL + '?accion=descargar&archivo=' + encodeURIComponent(r.archivo) + '"><i class="fas fa-download"></i> Descargar ahora</a></div>' + avisos);
      listar();
    }).always(function () {
      btn.prop('disabled', false).html('<i class="fas fa-database"></i> Generar respaldo ahora');
    });
  }

  function iniciar() {
    listar();
    $('#tabla_respaldos').off('click', '.eliminar').on('click', '.eliminar', function () {
      var archivo = $(this).data('archivo');
      Swal.fire({ title: '¿Eliminar este respaldo?', html: '<b>' + textoSeguro(archivo) + '</b><br>No se puede recuperar. Asegúrese de tener una copia descargada.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' })
        .then(function (res) {
          if (!res.isConfirmed) { return; }
          $.post(URL, { accion: 'eliminar', archivo: archivo }, null, 'json').done(function () {
            Swal.fire({ toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, icon: 'success', title: 'Respaldo eliminado' });
            listar();
          });
        });
    });
  }

  return { iniciar: iniciar, generar: generar };
})();
