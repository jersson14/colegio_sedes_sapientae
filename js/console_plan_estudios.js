// Fase 5.3: plan de estudios de un instituto (view/plan_estudios/view_plan_estudios.php).
var Plan = (function () {
  var URL = '../controller/plan_estudios/controlador_plan.php';
  var ROMANOS = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
  var arbol = [];

  function e(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
  }

  function unidades() {
    var todas = [];
    arbol.forEach(function (p) { p.modulos.forEach(function (m) { m.unidades.forEach(function (u) { todas.push($.extend({ programa: p.id_programa }, u)); }); }); });
    return todas;
  }

  function buscarUnidad(id) {
    return unidades().filter(function (u) { return String(u.id_unidad) === String(id); })[0];
  }

  function enviar(datos, exito) {
    $.ajax({ url: URL, type: 'POST', data: datos, dataType: 'json' })
      .done(function (r) { exito(r); cargar(); })
      .fail(function (xhr) {
        Swal.fire('Mensaje de Advertencia', (xhr.responseJSON && xhr.responseJSON.error) || 'No se completó el proceso', 'warning');
      });
  }

  function pintar() {
    if (arbol.length === 0) {
      $('#plan_contenido').html('<p class="text-muted">Todavía no hay programas de estudio.</p>');
      return;
    }
    var html = '';
    arbol.forEach(function (p) {
      html += '<div class="card card-outline card-primary programa" data-programa="' + p.id_programa + '">'
        + '<div class="card-header"><b>' + e(p.codigo) + ' · ' + e(p.nombre) + '</b>'
        + (p.estado !== 'ACTIVO' ? ' <span class="badge badge-secondary">INACTIVO</span>' : '')
        + '<div class="float-right">'
        + '<button class="btn btn-xs btn-outline-primary mr-1" onclick="Plan.abrirPrograma(' + p.id_programa + ')">Editar</button>'
        + '<button class="btn btn-xs btn-outline-success mr-1 nuevo-modulo" onclick="Plan.abrirModulo(0,' + p.id_programa + ')">+ Módulo</button>'
        + '<button class="btn btn-xs btn-outline-danger" onclick="Plan.eliminar(\'programa\',' + p.id_programa + ')">Eliminar</button>'
        + '</div></div><div class="card-body p-2">';
      if (p.modulos.length === 0) {
        html += '<p class="text-muted m-2">Sin módulos formativos.</p>';
      }
      p.modulos.forEach(function (m) {
        var creditos = m.unidades.reduce(function (s, u) { return s + parseFloat(u.creditos); }, 0);
        html += '<div class="border rounded mb-2 modulo" data-modulo="' + m.id_modulo + '"><div class="p-2 bg-light"><b>Módulo ' + e(m.orden) + ':</b> ' + e(m.nombre)
          + ' <small class="text-muted">(' + creditos.toFixed(1) + ' créditos)</small><div class="float-right">'
          + '<button class="btn btn-xs btn-outline-primary mr-1" onclick="Plan.abrirModulo(' + m.id_modulo + ',' + p.id_programa + ')">Editar</button>'
          + '<button class="btn btn-xs btn-outline-success mr-1 nueva-unidad" onclick="Plan.abrirUnidad(0,' + m.id_modulo + ')">+ Unidad</button>'
          + '<button class="btn btn-xs btn-outline-danger" onclick="Plan.eliminar(\'modulo\',' + m.id_modulo + ')">Eliminar</button></div></div>'
          + '<table class="table table-sm mb-0"><thead><tr><th>Código</th><th>Unidad didáctica</th><th>Periodo</th><th>Créditos</th><th>HT/HP</th><th>Prerrequisitos</th><th></th></tr></thead><tbody>';
        m.unidades.forEach(function (u) {
          var requisitos = u.prerrequisitos.map(function (id) { var r = buscarUnidad(id); return r ? e(r.codigo) : id; }).join(', ');
          html += '<tr data-unidad="' + u.id_unidad + '"' + (u.estado !== 'ACTIVO' ? ' class="text-muted"' : '') + '><td>' + e(u.codigo) + '</td><td>' + e(u.nombre)
            + (u.estado !== 'ACTIVO' ? ' <span class="badge badge-secondary">INACTIVA</span>' : '') + '</td><td>' + ROMANOS[u.periodo_academico] + '</td><td>' + e(u.creditos)
            + '</td><td>' + e(u.horas_teoricas) + '/' + e(u.horas_practicas) + '</td><td>' + (requisitos || '—') + '</td><td class="text-nowrap">'
            + '<button class="btn btn-xs btn-outline-primary mr-1" onclick="Plan.abrirUnidad(' + u.id_unidad + ',' + m.id_modulo + ')">Editar</button>'
            + '<button class="btn btn-xs btn-outline-info mr-1 requisitos" onclick="Plan.abrirRequisitos(' + u.id_unidad + ')">Prerrequisitos</button>'
            + '<button class="btn btn-xs btn-outline-danger" onclick="Plan.eliminar(\'unidad\',' + u.id_unidad + ')">Eliminar</button></td></tr>';
        });
        html += '</tbody></table></div>';
      });
      html += '</div></div>';
    });
    $('#plan_contenido').html(html);
  }

  function cargar() {
    $.ajax({ url: URL, type: 'GET', dataType: 'json' }).done(function (datos) {
      arbol = datos;
      pintar();
      var abierto = $('#requisitos_unidad').val();
      if (abierto && $('#modal_requisitos').hasClass('show')) {
        abrirRequisitos(abierto);
      }
    });
  }

  function abrirPrograma(id) {
    var p = arbol.filter(function (x) { return String(x.id_programa) === String(id); })[0] || { id_programa: 0, codigo: '', nombre: '', estado: 'ACTIVO' };
    $('#programa_id').val(p.id_programa);
    $('#programa_codigo').val(p.codigo);
    $('#programa_nombre').val(p.nombre);
    $('#programa_estado').val(p.estado);
    $('#modal_programa').modal('show');
  }

  function abrirModulo(id, programa) {
    var m = { id_modulo: 0, nombre: '', orden: 1 };
    arbol.forEach(function (p) { p.modulos.forEach(function (x) { if (String(x.id_modulo) === String(id)) { m = x; } }); });
    $('#modulo_id').val(m.id_modulo);
    $('#modulo_programa').val(programa);
    $('#modulo_nombre').val(m.nombre);
    $('#modulo_orden').val(m.orden);
    $('#modal_modulo').modal('show');
  }

  function abrirUnidad(id, modulo) {
    var u = buscarUnidad(id) || { id_unidad: 0, codigo: '', nombre: '', periodo_academico: 1, creditos: '', horas_teoricas: 0, horas_practicas: 0, estado: 'ACTIVO' };
    $('#unidad_id').val(u.id_unidad);
    $('#unidad_modulo').val(modulo);
    $('#unidad_codigo').val(u.codigo);
    $('#unidad_nombre').val(u.nombre);
    $('#unidad_periodo').val(u.periodo_academico);
    $('#unidad_creditos').val(u.creditos);
    $('#unidad_ht').val(u.horas_teoricas);
    $('#unidad_hp').val(u.horas_practicas);
    $('#unidad_estado').val(u.estado);
    $('#modal_unidad').modal('show');
  }

  function abrirRequisitos(id) {
    var u = buscarUnidad(id);
    if (!u) { return; }
    $('#requisitos_unidad').val(u.id_unidad);
    $('#requisitos_de').text(u.codigo);
    var lista = u.prerrequisitos.map(function (r) {
      var req = buscarUnidad(r);
      return '<li>' + e(req ? req.codigo + ' · ' + req.nombre : r) + ' <a href="#" class="text-danger quitar-requisito" onclick="Plan.quitarRequisito(' + r + ');return false;">quitar</a></li>';
    });
    $('#requisitos_lista').html(lista.length ? lista.join('') : '<li class="text-muted">Ninguno</li>');
    var opciones = unidades().filter(function (x) {
      return x.programa === u.programa && x.id_unidad !== u.id_unidad && u.prerrequisitos.map(String).indexOf(String(x.id_unidad)) === -1;
    }).map(function (x) { return '<option value="' + x.id_unidad + '">' + e(x.codigo + ' · ' + x.nombre + ' (' + ROMANOS[x.periodo_academico] + ')') + '</option>'; });
    $('#requisitos_nuevo').html(opciones.join(''));
    $('#modal_requisitos').modal('show');
  }

  return {
    cargar: cargar,
    abrirPrograma: abrirPrograma,
    abrirModulo: abrirModulo,
    abrirUnidad: abrirUnidad,
    abrirRequisitos: abrirRequisitos,
    guardarPrograma: function () {
      enviar({ accion: 'guardar_programa', id: $('#programa_id').val(), codigo: $('#programa_codigo').val(), nombre: $('#programa_nombre').val(), estado: $('#programa_estado').val() },
        function () { $('#modal_programa').modal('hide'); });
    },
    guardarModulo: function () {
      enviar({ accion: 'guardar_modulo', id: $('#modulo_id').val(), programa: $('#modulo_programa').val(), nombre: $('#modulo_nombre').val(), orden: $('#modulo_orden').val() },
        function () { $('#modal_modulo').modal('hide'); });
    },
    guardarUnidad: function () {
      enviar({
        accion: 'guardar_unidad', id: $('#unidad_id').val(), modulo: $('#unidad_modulo').val(), codigo: $('#unidad_codigo').val(),
        nombre: $('#unidad_nombre').val(), periodo_academico: $('#unidad_periodo').val(), creditos: $('#unidad_creditos').val(),
        horas_teoricas: $('#unidad_ht').val(), horas_practicas: $('#unidad_hp').val(), estado: $('#unidad_estado').val()
      }, function () { $('#modal_unidad').modal('hide'); });
    },
    agregarRequisito: function () {
      enviar({ accion: 'agregar_requisito', unidad: $('#requisitos_unidad').val(), requisito: $('#requisitos_nuevo').val() }, function () {});
    },
    quitarRequisito: function (requisito) {
      enviar({ accion: 'quitar_requisito', unidad: $('#requisitos_unidad').val(), requisito: requisito }, function () {});
    },
    eliminar: function (que, id) {
      Swal.fire({ title: '¿Eliminar?', text: 'Solo se puede si no tiene contenido ni historial.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
        .then(function (r) { if (r.isConfirmed) { enviar({ accion: 'eliminar_' + que, id: id }, function () {}); } });
    }
  };
})();
