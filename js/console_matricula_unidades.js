// Fase 5.4: matrícula por unidad didáctica (view/matricula_unidades/view_matricula_unidades.php).
var MatriculaUnidades = (function () {
  var URL = '../controller/matricula_unidades/controlador_matricula_unidades.php';
  var ROMANOS = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
  var SITUACION = {
    APROBADA: ['success', 'Aprobada'],
    MATRICULADO: ['primary', 'Matriculado'],
    DESAPROBADO: ['danger', 'Desaprobada'],
    RETIRADO: ['secondary', 'Retirado'],
    INACTIVA: ['light', 'Inactiva'],
    FALTA_REQUISITO: ['warning', 'Falta prerrequisito'],
    DISPONIBLE: ['info', 'Disponible']
  };

  function e(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
  }

  function opciones(lista, conDni) {
    return lista.map(function (x) { return '<option value="' + x.id + '">' + e(conDni ? x.dni + ' · ' + x.nombre : x.nombre) + '</option>'; }).join('');
  }

  function seleccion() {
    return { alumno: $('#mu_alumno').val(), programa: $('#mu_programa').val(), periodo: $('#mu_periodo').val() };
  }

  function cargar() {
    var s = seleccion();
    if (!s.alumno || !s.programa || !s.periodo) { return; }
    $.ajax({ url: URL, type: 'GET', data: s, dataType: 'json' }).done(function (unidades) {
      var creditos = 0;
      var filas = unidades.map(function (u) {
        var sit = SITUACION[u.situacion] || ['light', u.situacion];
        if (u.situacion === 'MATRICULADO') { creditos += parseFloat(u.creditos); }
        var detalle = u.situacion === 'FALTA_REQUISITO' ? '<br><small>Falta: ' + e(u.faltan.join(', ')) + '</small>'
          : (u.nota_final != null ? '<br><small>Nota: ' + e(u.nota_final) + '</small>' : '');
        var accion = '';
        if (u.situacion === 'DISPONIBLE' || u.situacion === 'DESAPROBADO' || u.situacion === 'RETIRADO') {
          accion = '<button class="btn btn-xs btn-success matricular" onclick="MatriculaUnidades.matricular(' + u.id_unidad + ')">Matricular</button>';
        } else if (u.situacion === 'MATRICULADO') {
          accion = '<button class="btn btn-xs btn-outline-danger retirar" onclick="MatriculaUnidades.retirar(' + u.id_matricula_unidad + ')">Retirar</button>';
        }
        return '<tr data-unidad="' + u.id_unidad + '" data-situacion="' + e(u.situacion) + '"><td>' + ROMANOS[u.periodo_academico] + '</td><td>' + e(u.codigo) + '</td><td>' + e(u.nombre)
          + '</td><td>' + e(u.modulo) + '</td><td>' + e(u.creditos) + '</td><td><span class="badge badge-' + sit[0] + '">' + sit[1] + '</span>' + detalle + '</td><td>' + accion + '</td></tr>';
      });
      $('#tabla_matricula_unidades tbody').html(filas.length ? filas.join('') : '<tr><td colspan="7" class="text-muted">El programa no tiene unidades didácticas.</td></tr>');
      $('#mu_creditos').text(creditos.toFixed(1));
    });
  }

  function enviar(datos) {
    $.ajax({ url: URL, type: 'POST', data: datos, dataType: 'json' }).done(function (r) {
      Swal.fire(r.codigo === 1 ? 'Mensaje de Confirmación' : 'Mensaje de Advertencia', r.mensaje, r.codigo === 1 ? 'success' : 'warning');
      cargar();
    }).fail(function (xhr) {
      Swal.fire('Mensaje de Error', (xhr.responseJSON && xhr.responseJSON.error) || 'No se completó el proceso', 'error');
    });
  }

  return {
    iniciar: function () {
      $.ajax({ url: URL, type: 'GET', dataType: 'json' }).done(function (c) {
        $('#mu_alumno').html('<option value="">— Alumno —</option>' + opciones(c.alumnos, true));
        $('#mu_programa').html(opciones(c.programas));
        $('#mu_periodo').html(opciones(c.periodos));
        if ($.fn.select2) { $('#mu_alumno').select2(); }
        $('#mu_alumno, #mu_programa, #mu_periodo').on('change', cargar);
      });
    },
    matricular: function (unidad) {
      var s = seleccion();
      enviar({ accion: 'matricular', alumno: s.alumno, unidad: unidad, periodo: s.periodo });
    },
    retirar: function (id) {
      enviar({ accion: 'retirar', id: id });
    },
    recargar: cargar
  };
})();
