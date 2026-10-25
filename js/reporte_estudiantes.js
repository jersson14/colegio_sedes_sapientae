// Reporte de estudiantes a medida (Fase 11): el usuario marca los campos, filtra y exporta a Excel, PDF o imprime.
window.ReporteEstudiantes = (function () {
  var cat = null;
  var orden = [];      // claves marcadas, en el orden en que se marcaron
  var tabla = null;
  var PLANTILLAS = {
    basico: ['documento', 'apellidos', 'nombres', 'nivel', 'grado', 'seccion'],
    padres: ['apellidos', 'nombres', 'grado', 'seccion', 'mama', 'cel_mama', 'papa', 'cel_papa', 'direccion'],
    seguimiento: ['apellidos', 'nombres', 'grado', 'seccion', 'asistencia', 'faltas', 'tardanzas', 'tareas'],
    cobranza: ['documento', 'apellidos', 'nombres', 'grado', 'seccion', 'cel_mama', 'cel_papa', 'pensiones_vencidas', 'deuda'],
    ninguno: []
  };
  var CLAVE_GUARDADA = 'reporte_estudiantes_mio';

  function pintarCampos() {
    var h = '', grupo = '';
    cat.campos.forEach(function (c) {
      if (c.grupo !== grupo) { grupo = c.grupo; h += '<div class="re-grupo">' + textoSeguro(grupo) + '</div>'; }
      h += '<label class="re-campo"><input type="checkbox" class="mr-1" value="' + c.clave + '"' + (orden.indexOf(c.clave) !== -1 ? ' checked' : '') + '> '
        + textoSeguro(c.titulo) + '</label>';
    });
    $('#re_campos').html(h);
    $('#re_campos input').on('change', function () {
      var k = this.value;
      orden = orden.filter(function (x) { return x !== k; });
      if (this.checked) { orden.push(k); }
    });
  }

  function aulas() {
    var nivel = $('#re_nivel').val();
    $('#re_aula').html('<option value="0">Todas</option>' + cat.aulas.filter(function (a) {
      return !+nivel || cat.niveles.some(function (n) { return +n.id === +nivel && n.nombre === a.nivel; });
    }).map(function (a) {
      return '<option value="' + a.id + '">' + textoSeguro(a.nivel + ' - ' + a.grado + (a.seccion !== 'UNICO' ? ' ' + a.seccion : '')) + '</option>';
    }).join(''));
  }

  function catalogo(anio) {
    $.post('../controller/reportes/controlador_reporte_estudiantes.php', { accion: 'catalogo', anio: anio || 0 }, null, 'json').done(function (r) {
      cat = r;
      $('#re_anio').html(r.anios.map(function (a) {
        return '<option value="' + a.id + '">' + textoSeguro(a.anio) + (a.estado === 'ACTIVO' ? ' (actual)' : '') + '</option>';
      }).join('')).val(r.anio);
      $('#re_nivel').html('<option value="0">Todos</option>' + r.niveles.map(function (n) {
        return '<option value="' + n.id + '">' + textoSeguro(n.nombre) + '</option>';
      }).join(''));
      aulas();
      pintarCampos();
    });
  }

  function plantilla(nombre) {
    orden = PLANTILLAS[nombre].slice();
    pintarCampos();
  }

  function formato(tipo) {
    return function (v, t) {
      if (v === null || v === undefined || v === '') { return ''; }
      if (t !== 'display') { return v; }
      if (tipo === 'moneda') { return formatoMoneda(v); }
      if (tipo === 'porcentaje') { return textoSeguro(v) + '%'; }
      return textoSeguro(v);
    };
  }

  function generar() {
    if (!orden.length) { return Swal.fire('Elija campos', 'Marque al menos un campo (o use una de las listas rápidas).', 'info'); }
    $.post('../controller/reportes/controlador_reporte_estudiantes.php', {
      campos: orden.join(','), anio: $('#re_anio').val(), nivel: $('#re_nivel').val(), aula: $('#re_aula').val(),
      sexo: $('#re_sexo').val(), tipo: $('#re_tipo').val(), con_deuda: $('#re_deuda').is(':checked') ? 1 : 0, buscar: $('#re_buscar').val()
    }, null, 'json').done(function (r) {
      if (tabla) { tabla.destroy(); $('#re_tabla').empty(); }
      $('#re_vacio').hide();
      var titulo = 'REPORTE DE ESTUDIANTES - ' + $('#re_anio option:selected').text().replace(' (actual)', '')
        + ($('#re_aula').val() !== '0' ? ' - ' + $('#re_aula option:selected').text() : ($('#re_nivel').val() !== '0' ? ' - ' + $('#re_nivel option:selected').text() : ''));
      var cols = [{ title: 'Nº', data: null, orderable: false, render: function (d, t, f, m) { return m.row + 1; } }]
        .concat(r.columnas.map(function (c, i) { return { title: c.titulo, data: i, render: formato(c.tipo) }; }));
      tabla = $('#re_tabla').DataTable({
        data: r.filas, columns: cols, pageLength: 25, order: [], language: window.idioma_espanol,
        dom: 'Bfrtip',
        buttons: [
          { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', title: titulo },
          { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm', title: titulo,
            orientation: r.columnas.length > 6 ? 'landscape' : 'portrait', pageSize: 'A4' },
          { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-secondary btn-sm', title: titulo }
        ]
      });
      tabla.on('order.dt search.dt', function () {
        tabla.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (celda, i) { celda.innerHTML = i + 1; });
      });
    });
  }

  function guardarSeleccion() {
    if (!orden.length) { return Swal.fire('Sin campos', 'Marque los campos que quiere guardar.', 'info'); }
    try { localStorage.setItem(CLAVE_GUARDADA, JSON.stringify(orden)); } catch (e) { /* sin almacenamiento */ }
    Swal.fire({ toast: true, position: 'top-end', timer: 2000, showConfirmButton: false, icon: 'success', title: 'Selección guardada en este equipo' });
  }

  function usarGuardada() {
    var g = null;
    try { g = JSON.parse(localStorage.getItem(CLAVE_GUARDADA) || 'null'); } catch (e) { /* sin almacenamiento */ }
    if (!Array.isArray(g) || !g.length) { return Swal.fire('Sin reporte guardado', 'Marque campos y use "Guardar como mi reporte".', 'info'); }
    orden = g.filter(function (k) { return cat.campos.some(function (c) { return c.clave === k; }); });
    pintarCampos();
  }

  function iniciar() {
    orden = PLANTILLAS.basico.slice();
    $('#re_anio').off('change').on('change', function () { catalogo($(this).val()); });
    $('#re_nivel').off('change').on('change', aulas);
    $('#re_buscar').off('keydown').on('keydown', function (e) { if (e.key === 'Enter') { generar(); } });
    catalogo(0);
  }

  return { iniciar: iniciar, generar: generar, plantilla: plantilla, guardarSeleccion: guardarSeleccion, usarGuardada: usarGuardada };
})();
