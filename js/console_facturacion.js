// Módulo opcional: facturación electrónica con Nubefact (view/facturacion/view_facturacion.php).
var Facturacion = (function () {
  var URL = '../controller/facturacion/controlador_facturacion.php';
  var ESTADOS = { PENDIENTE: ['warning', 'Pendiente'], ERROR: ['danger', 'Rechazado'], EMITIDO: ['info', 'Emitido'],
    ACEPTADO: ['success', 'Aceptado SUNAT'], ANULADO: ['secondary', 'Anulado'] };
  var pendientes = [];
  var operacion = null; // { accion: 'emitir'|'reintentar', id, tipo }

  function escFe(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
  }

  function soles(n) {
    return 'S/ ' + parseFloat(n).toFixed(2);
  }

  function cargarFacturacion() {
    $.ajax({ url: URL, type: 'GET', dataType: 'json' }).done(function (d) {
      var c = d.configuracion;
      $('#fe_ruta').val(c.ruta);
      $('#fe_token').val('');
      $('#fe_serie_boleta').val(c.serie_boleta);
      $('#fe_serie_factura').val(c.serie_factura);
      $('#fe_ultimo_boleta').attr('placeholder', c.ultimo_boleta).val('');
      $('#fe_ultimo_factura').attr('placeholder', c.ultimo_factura).val('');
      $('#fe_afectacion').val(c.afectacion);
      $('#fe_enviar_cliente').val(c.enviar_cliente ? '1' : '0');
      var lista = c.ruta !== '' && c.token_configurado;
      $('#fe_estado_cuenta').attr('class', 'badge ml-2 badge-' + (lista ? 'success' : 'secondary')).text(lista ? 'Configurada' : 'Sin configurar');
      if (!lista) { $('#fe_tarjeta_configuracion').removeClass('collapsed-card'); }

      pendientes = d.pendientes;
      $('#tabla_fe_pendientes tbody').html(pendientes.length ? pendientes.map(function (p) {
        return '<tr data-ingreso="' + p.id_ingreso + '"><td class="text-nowrap">' + escFe(p.fecha) + '</td><td>' + escFe(p.descripcion) + '</td><td class="text-nowrap">' + soles(p.monto)
          + '</td><td>' + escFe(p.cliente_doc ? p.cliente_doc + ' · ' + p.cliente_nombre : '—') + '</td><td class="text-nowrap">'
          + '<button class="btn btn-xs btn-primary fe-boleta mr-1" onclick="Facturacion.abrirEmision(' + p.id_ingreso + ',2)">Boleta</button>'
          + '<button class="btn btn-xs btn-outline-primary fe-factura" onclick="Facturacion.abrirEmision(' + p.id_ingreso + ',1)">Factura</button></td></tr>';
      }).join('') : '<tr><td colspan="5" class="text-muted">Todos los ingresos tienen comprobante.</td></tr>');

      $('#tabla_fe_emitidos tbody').html(d.emitidos.length ? d.emitidos.map(function (e) {
        var est = ESTADOS[e.estado] || ['light', e.estado];
        var acciones = '';
        if (e.enlace_pdf) { acciones += '<a class="btn btn-xs btn-outline-dark mr-1" target="_blank" rel="noopener" href="' + escFe(e.enlace_pdf) + '">PDF</a>'; }
        if (e.estado === 'PENDIENTE' || e.estado === 'EMITIDO') { acciones += '<button class="btn btn-xs btn-info mr-1 fe-consultar" onclick="Facturacion.consultarComprobante(' + e.id + ')">Consultar</button>'; }
        if (e.estado === 'ERROR') { acciones += '<button class="btn btn-xs btn-warning mr-1 fe-reintentar" onclick="Facturacion.abrirReintento(' + e.id + ')">Corregir y reenviar</button>'; }
        if (e.estado === 'EMITIDO' || e.estado === 'ACEPTADO') { acciones += '<button class="btn btn-xs btn-outline-danger fe-anular" onclick="Facturacion.anularComprobante(' + e.id + ')">Anular</button>'; }
        var detalle = e.error ? '<br><small class="text-danger">' + escFe(e.error) + '</small>' : (e.sunat_descripcion ? '<br><small>' + escFe(e.sunat_descripcion) + '</small>' : '');
        return '<tr data-id="' + e.id + '" data-estado="' + escFe(e.estado) + '"><td class="text-nowrap">' + escFe(e.serie + '-' + e.numero) + '</td><td class="text-nowrap">' + escFe(e.created_at)
          + '</td><td>' + escFe(e.cliente_doc + ' · ' + e.cliente_nombre) + '</td><td>' + escFe(e.descripcion) + '</td><td class="text-nowrap">' + soles(e.total)
          + '</td><td><span class="badge badge-' + est[0] + '">' + est[1] + '</span>' + detalle + '</td><td class="text-nowrap">' + acciones + '</td></tr>';
      }).join('') : '<tr><td colspan="7" class="text-muted">Aún no hay comprobantes.</td></tr>');
      window.FACTURACION_EMITIDOS = d.emitidos;
    }).fail(errorFacturacion);
  }

  function errorFacturacion(xhr) {
    Swal.fire('Mensaje de Error', (xhr.responseJSON && xhr.responseJSON.error) || 'No se completó el proceso', 'error');
  }

  function enviarFacturacion(datos, alTerminar) {
    $.ajax({ url: URL, type: 'POST', data: datos, dataType: 'json' }).done(function (r) {
      Swal.fire(r.codigo === 1 ? 'Mensaje de Confirmación' : 'Mensaje de Advertencia', r.mensaje, r.codigo === 1 ? 'success' : 'warning');
      if (alTerminar) { alTerminar(r); }
      cargarFacturacion();
    }).fail(errorFacturacion);
  }

  function llenarCliente(c) {
    $('#fe_tipo_doc').val(c.tipo_doc);
    $('#fe_doc').val(c.doc);
    $('#fe_nombre').val(c.nombre);
    $('#fe_direccion').val(c.direccion);
    $('#fe_email').val(c.email || '');
  }

  return {
    iniciar: function () {
      $('#fe_guardar_configuracion').on('click', function () {
        enviarFacturacion({ accion: 'configurar', ruta: $('#fe_ruta').val(), token: $('#fe_token').val(), serie_boleta: $('#fe_serie_boleta').val(),
          serie_factura: $('#fe_serie_factura').val(), ultimo_boleta: $('#fe_ultimo_boleta').val(), ultimo_factura: $('#fe_ultimo_factura').val(),
          afectacion: $('#fe_afectacion').val(), enviar_cliente: $('#fe_enviar_cliente').val() });
      });
      $('#fe_confirmar_emision').on('click', function () {
        var datos = { accion: operacion.accion, tipo_doc: $('#fe_tipo_doc').val(), doc: $('#fe_doc').val(), nombre: $('#fe_nombre').val(),
          direccion: $('#fe_direccion').val(), email: $('#fe_email').val() };
        if (operacion.accion === 'emitir') { datos.ingreso = operacion.id; datos.tipo = operacion.tipo; } else { datos.id = operacion.id; }
        $('#modal_fe_emitir').modal('hide');
        enviarFacturacion(datos);
      });
      cargarFacturacion();
    },
    abrirEmision: function (idIngreso, tipo) {
      var p = pendientes.filter(function (x) { return String(x.id_ingreso) === String(idIngreso); })[0];
      if (!p) { return; }
      operacion = { accion: 'emitir', id: idIngreso, tipo: tipo };
      $('#fe_titulo_emitir').text(tipo === 1 ? 'Emitir factura' : 'Emitir boleta');
      $('#fe_detalle_ingreso').text(p.descripcion + ' — ' + soles(p.monto));
      llenarCliente(tipo === 1 ? { tipo_doc: '6', doc: '', nombre: '', direccion: '' }
        : { tipo_doc: p.cliente_doc ? '1' : '-', doc: p.cliente_doc, nombre: p.cliente_nombre, direccion: p.cliente_direccion });
      $('#modal_fe_emitir').modal('show');
    },
    abrirReintento: function (id) {
      var e = (window.FACTURACION_EMITIDOS || []).filter(function (x) { return String(x.id) === String(id); })[0];
      if (!e) { return; }
      operacion = { accion: 'reintentar', id: id };
      $('#fe_titulo_emitir').text('Corregir y reenviar ' + e.serie + '-' + e.numero);
      $('#fe_detalle_ingreso').text(e.descripcion + ' — ' + soles(e.total));
      llenarCliente({ tipo_doc: e.cliente_tipo_doc, doc: e.cliente_doc === '-' ? '' : e.cliente_doc, nombre: e.cliente_nombre, direccion: '' });
      $('#modal_fe_emitir').modal('show');
    },
    consultarComprobante: function (id) {
      enviarFacturacion({ accion: 'consultar', id: id });
    },
    anularComprobante: function (id) {
      Swal.fire({ title: 'Anular comprobante', input: 'text', inputPlaceholder: 'Motivo (p. ej. ERROR EN EL MONTO)', showCancelButton: true,
        confirmButtonText: 'Anular', cancelButtonText: 'Cancelar' }).then(function (r) {
        if (r.isConfirmed) { enviarFacturacion({ accion: 'anular', id: id, motivo: r.value }); }
      });
    }
  };
})();
