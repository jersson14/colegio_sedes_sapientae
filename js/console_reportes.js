// Reportes financieros (Fase 7): utilidad, comparativo, flujo de caja, formas de pago,
// morosidad, proyección de pensiones, costo de planilla y cierres de caja.
var rfFiltros = { anios: [], niveles: [], aulas: [] };
var rfGrafico = null;
var rfMorosidad = null;

function rfEsc(t) {
  return $('<div>').text(t == null ? '' : t).html();
}
function rfFechaLocal(d) {
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}
function rfFechaTexto(f) {
  return f ? f.substr(8, 2) + '-' + f.substr(5, 2) + '-' + f.substr(0, 4) : '';
}
// Celda de monto: muestra la moneda configurada y guarda el número para ordenar y exportar a Excel
function rfMonto(v, clase) {
  return "<td class='text-right text-nowrap " + (clase || '') + "' data-order='" + Number(v) + "'>" + formatoMoneda(v) + "</td>";
}
function rfNum(v, sufijo) {
  return "<td class='text-right' data-order='" + (v == null ? '' : Number(v)) + "'>" + (v == null ? '—' : v + (sufijo || '')) + "</td>";
}
function rfTarjeta(titulo, valor, clase, col) {
  return "<div class='" + (col || 'col-lg-3 col-6') + "'><div class='small-box rf-tarjeta " + clase + "'><div class='inner'><h4>" + valor + "</h4><p>" + titulo + "</p></div></div></div>";
}
function rfPeriodoTexto() {
  return rfFechaTexto($('#rf_desde').val()) + ' al ' + rfFechaTexto($('#rf_hasta').val());
}

// --- Tablas exportables (Excel e impresión con DataTables Buttons) ---
function rfLimpiar(sel) {
  if ($.fn.DataTable.isDataTable(sel)) {
    $(sel).DataTable().destroy();
  }
}
function rfValorExportado(data, node) {
  if (node && node.getAttribute && node.getAttribute('data-order') !== null && node.getAttribute('data-order') !== '') {
    return node.getAttribute('data-order');
  }
  return $('<div>').html(data).text().trim();
}
function rfExportable(sel, titulo, completa) {
  var formato = {
    body: function(data, fila, col, node) { return rfValorExportado(data, node); },
    footer: function(data, col, node) { return rfValorExportado(data, node); }
  };
  $(sel).DataTable({
    paging: !!completa, searching: !!completa, info: !!completa, ordering: !!completa,
    order: [], autoWidth: false, pageLength: 25, language: idioma_espanol,
    dom: completa ? "<'row'<'col-sm-6'B><'col-sm-6'f>>rt<'row'<'col-sm-5'i><'col-sm-7'p>>" : "<'mb-2'B>rt",
    buttons: [
      { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', title: titulo, footer: true, exportOptions: { format: formato } },
      { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-secondary btn-sm', title: titulo, footer: true }
    ]
  });
}

// --- Inicio y filtros ---
function Iniciar_Reportes() {
  var hoy = new Date();
  $('#rf_desde').val(rfFechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), 1)));
  $('#rf_hasta').val(rfFechaLocal(hoy));
  $('#rf_anio').val(hoy.getFullYear());
  $('#rf_rapido').on('change', function() { Periodo_Rapido(this.value); });
  $('#rf_nivel').on('change', Filtrar_Aulas);
  $('#rf_tabs a[data-toggle="pill"]').on('shown.bs.tab', function() {
    Mostrar_Filtros();
    Cargar_Reporte();
  });
  $.ajax({ url: '../controller/reportes/controlador_filtros.php', type: 'POST' }).done(function(r) {
    rfFiltros = r;
    var op = '';
    r.anios.forEach(function(a) {
      op += "<option value='" + a.id + "'" + (a.estado === 'ACTIVO' ? ' selected' : '') + ">" + rfEsc(a.anio) + (a.estado === 'ACTIVO' ? ' (activo)' : '') + "</option>";
    });
    $('#rf_anio_escolar').html(op);
    op = "<option value='0'>TODOS</option>";
    r.niveles.forEach(function(n) { op += "<option value='" + n.id + "'>" + rfEsc(n.nombre) + "</option>"; });
    $('#rf_nivel').html(op);
    Filtrar_Aulas();
    Mostrar_Filtros();
    Cargar_Reporte();
  });
}
function Filtrar_Aulas() {
  var nivel = $('#rf_nivel').val();
  var op = "<option value='0'>TODOS</option>";
  rfFiltros.aulas.forEach(function(a) {
    if (nivel === '0' || String(a.nivel) === nivel) { op += "<option value='" + a.id + "'>" + rfEsc(a.nombre) + "</option>"; }
  });
  $('#rf_aula').html(op);
}
function Mostrar_Filtros() {
  var usa = ($('#rf_tabs a.active').data('filtros') || '').split(',');
  $('.rf-filtro').each(function() {
    $(this).toggle(usa.indexOf($(this).data('usa')) >= 0);
  });
}
function Periodo_Rapido(v) {
  var hoy = new Date(), desde = null, hasta = hoy;
  if (v === 'hoy') { desde = hoy; }
  if (v === 'mes') { desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1); }
  if (v === 'mes_anterior') { desde = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1); hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0); }
  if (v === 'anio') { desde = new Date(hoy.getFullYear(), 0, 1); }
  if (v === 'anio_escolar') {
    var a = rfFiltros.anios.filter(function(x) { return x.estado === 'ACTIVO'; })[0];
    if (!a) { return Swal.fire('Mensaje de advertencia', 'No hay un año escolar activo', 'warning'); }
    $('#rf_desde').val(a.fecha_inicio.substr(0, 10));
    $('#rf_hasta').val(a.fecha_fin.substr(0, 10));
  }
  if (desde) {
    $('#rf_desde').val(rfFechaLocal(desde));
    $('#rf_hasta').val(rfFechaLocal(hasta));
  }
  $('#rf_rapido').val('');
  Cargar_Reporte();
}
function rfParametros(tipo) {
  if (tipo === 'periodo') { return { desde: $('#rf_desde').val(), hasta: $('#rf_hasta').val() }; }
  if (tipo === 'anio') { return { anio: $('#rf_anio').val() }; }
  return { anio: $('#rf_anio_escolar').val(), nivel: $('#rf_nivel').val(), aula: $('#rf_aula').val() };
}
function Cargar_Reporte() {
  var reporte = $('#rf_tabs a.active').data('reporte');
  var acciones = {
    resultados: Reporte_Resultados, mensual: Reporte_Mensual, flujo: Reporte_Flujo, metodos: Reporte_Metodos,
    morosidad: Reporte_Morosidad, proyeccion: Reporte_Proyeccion, planilla: Reporte_Planilla, cierres: Reporte_Cierres
  };
  if (acciones[reporte]) { acciones[reporte](); }
}
function Abrir_PDF(reporte) {
  var p = reporte === 'morosidad' ? rfParametros('escolar') : rfParametros('periodo');
  var urls = {
    estado_resultados: '../view/MPDF/REPORTE/estado_resultados.php',
    flujo_caja: '../view/MPDF/REPORTE/flujo_caja.php',
    morosidad: '../view/MPDF/REPORTE/morosidad.php'
  };
  window.open(urls[reporte] + '?' + $.param(p), '_blank');
}

// --- Estado de resultados ---
function Reporte_Resultados() {
  $.ajax({ url: '../controller/reportes/controlador_resultados.php', type: 'POST', data: rfParametros('periodo') }).done(function(r) {
    var ut = r.utilidad >= 0;
    $('#rf_resultados_tarjetas').html(
      rfTarjeta('Ingresos', formatoMoneda(r.INGRESO.total), 'bg-success') +
      rfTarjeta('Egresos', formatoMoneda(r.EGRESO.total), 'bg-danger') +
      rfTarjeta(ut ? 'Utilidad' : 'Pérdida', formatoMoneda(r.utilidad), ut ? 'bg-primary' : 'bg-warning') +
      rfTarjeta('Margen sobre ingresos', r.margen == null ? '—' : r.margen + ' %', 'bg-info'));
    var filas = '';
    [['INGRESO', 'INGRESOS'], ['EGRESO', 'EGRESOS']].forEach(function(par) {
      var lado = r[par[0]];
      filas += "<tr class='fila-grupo'><td>" + par[1] + "</td><td></td><td></td><td></td></tr>";
      lado.grupos.forEach(function(g) {
        filas += "<tr class='fila-grupo'><td>" + rfEsc((g.codigo ? g.codigo + ' ' : '') + g.nombre) + "</td><td></td>" + rfMonto(g.total)
               + rfNum(lado.total > 0 ? Math.round(g.total / lado.total * 1000) / 10 : 0, ' %') + "</tr>";
        g.categorias.forEach(function(c) {
          filas += "<tr class='fila-categoria'><td>" + rfEsc(c.nombre) + "</td>" + rfNum(c.cantidad) + rfMonto(c.total) + rfNum(c.porcentaje, ' %') + "</tr>";
          c.detalle.forEach(function(d) {
            filas += "<tr class='fila-detalle'><td>" + rfEsc(d.concepto) + "</td>" + rfNum(d.cantidad) + rfMonto(d.total) + "<td></td></tr>";
          });
        });
      });
      if (!lado.grupos.length) {
        filas += "<tr><td class='text-muted'>Sin movimientos en el período</td><td></td><td></td><td></td></tr>";
      }
      filas += "<tr class='fila-total'><td>TOTAL " + par[1] + "</td><td></td>" + rfMonto(lado.total) + "<td></td></tr>";
    });
    filas += "<tr class='fila-total'><td>" + (ut ? 'UTILIDAD DEL PERÍODO' : 'PÉRDIDA DEL PERÍODO') + "</td><td></td>"
           + rfMonto(r.utilidad, ut ? 'text-primary' : 'text-danger') + rfNum(r.margen, ' %') + "</tr>";
    rfLimpiar('#rf_resultados');
    $('#rf_resultados tbody').html(filas);
    rfExportable('#rf_resultados', 'Estado de resultados ' + rfPeriodoTexto());
  });
}

// --- Comparativo mensual ---
function Reporte_Mensual() {
  $.ajax({ url: '../controller/reportes/controlador_mensual.php', type: 'POST', data: rfParametros('anio') }).done(function(r) {
    var t = r.totales;
    $('#rf_mensual_tarjetas').html(
      rfTarjeta('Ingresos ' + r.anio, formatoMoneda(t.ingresos), 'bg-success') +
      rfTarjeta('Egresos ' + r.anio, formatoMoneda(t.egresos), 'bg-danger') +
      rfTarjeta((t.utilidad >= 0 ? 'Utilidad ' : 'Pérdida ') + r.anio + (t.margen == null ? '' : ' (' + t.margen + ' %)'), formatoMoneda(t.utilidad), t.utilidad >= 0 ? 'bg-primary' : 'bg-warning') +
      rfTarjeta('Ingresos vs. ' + (r.anio - 1), t.variacion_ingresos == null ? '—' : (t.variacion_ingresos > 0 ? '+' : '') + t.variacion_ingresos + ' %', 'bg-info'));
    var filas = '';
    r.data.forEach(function(m) {
      filas += "<tr><td>" + m.mes_nombre + "</td>" + rfMonto(m.ingresos, 'text-success') + rfMonto(m.egresos, 'text-danger') + rfMonto(m.utilidad, m.utilidad < 0 ? 'text-danger' : '')
             + rfMonto(m.ingresos_anterior) + rfMonto(m.egresos_anterior) + rfMonto(m.utilidad_anterior) + "</tr>";
    });
    rfLimpiar('#rf_mensual');
    $('#rf_mensual tbody').html(filas);
    $('#rf_mensual tfoot').html("<tr><th>TOTAL</th>" + rfMonto(t.ingresos).replace(/td/g, 'th') + rfMonto(t.egresos).replace(/td/g, 'th') + rfMonto(t.utilidad).replace(/td/g, 'th')
      + rfMonto(t.ingresos_anterior).replace(/td/g, 'th') + rfMonto(t.egresos_anterior).replace(/td/g, 'th') + rfMonto(t.utilidad_anterior).replace(/td/g, 'th') + "</tr>");
    rfExportable('#rf_mensual', 'Comparativo mensual ' + r.anio + ' vs ' + (r.anio - 1));

    if (rfGrafico) { rfGrafico.destroy(); }
    rfGrafico = new Chart(document.getElementById('rf_mensual_grafico'), {
      type: 'bar',
      data: {
        labels: r.data.map(function(m) { return m.mes_nombre.substr(0, 3); }),
        datasets: [
          { type: 'line', label: 'Utilidad ' + r.anio, data: r.data.map(function(m) { return m.utilidad; }), borderColor: '#0A5D86', backgroundColor: 'transparent', lineTension: 0, pointRadius: 3 },
          { type: 'line', label: 'Utilidad ' + (r.anio - 1), data: r.data.map(function(m) { return m.utilidad_anterior; }), borderColor: '#adb5bd', borderDash: [5, 5], backgroundColor: 'transparent', lineTension: 0, pointRadius: 2 },
          { label: 'Ingresos', data: r.data.map(function(m) { return m.ingresos; }), backgroundColor: 'rgba(40,167,69,0.75)' },
          { label: 'Egresos', data: r.data.map(function(m) { return m.egresos; }), backgroundColor: 'rgba(220,53,69,0.75)' }
        ]
      },
      options: {
        maintainAspectRatio: false,
        tooltips: { mode: 'index', intersect: false, callbacks: { label: function(it, d) { return d.datasets[it.datasetIndex].label + ': ' + formatoMoneda(it.yLabel); } } },
        scales: { yAxes: [{ ticks: { beginAtZero: true, callback: function(v) { return formatoMoneda(v); } } }] }
      }
    });
  });
}

// --- Flujo de caja ---
function Reporte_Flujo() {
  $.ajax({ url: '../controller/reportes/controlador_flujo_caja.php', type: 'POST', data: rfParametros('periodo') }).done(function(r) {
    var filas = '';
    var campos = ['saldo_anterior', 'cobros', 'transferencias_entrada', 'ajustes_entrada', 'pagos', 'transferencias_salida', 'ajustes_salida'];
    r.data.forEach(function(c) {
      filas += "<tr><td><b>" + rfEsc(c.nombre) + "</b></td><td>" + c.tipo + "</td>";
      campos.forEach(function(k) { filas += rfMonto(c[k]); });
      filas += rfMonto(c.saldo_final, c.saldo_final < 0 ? 'text-danger font-weight-bold' : 'font-weight-bold') + "</tr>";
    });
    rfLimpiar('#rf_flujo');
    $('#rf_flujo tbody').html(filas || "<tr><td>No hay cajas ni cuentas registradas</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>");
    var pie = "<tr><th>TOTAL</th><th></th>";
    campos.concat(['saldo_final']).forEach(function(k) { pie += rfMonto(r.totales[k]).replace(/td/g, 'th'); });
    $('#rf_flujo tfoot').html(pie + "</tr>");
    rfExportable('#rf_flujo', 'Flujo de caja ' + rfPeriodoTexto());
    var s = r.sin_cuenta;
    $('#rf_flujo_sin_cuenta').html(s ? "<div class='alert alert-info py-2'><i class='fas fa-info-circle'></i> Además, en el período hay <b>" + formatoMoneda(s.cobros)
      + "</b> de cobros y <b>" + formatoMoneda(s.pagos) + "</b> de pagos registrados <b>sin caja/cuenta</b> (anteriores a la Fase 5 o sin cuenta asignada). Suman en la utilidad pero no en estos saldos.</div>" : '');
  });
}

// --- Formas de pago ---
function Reporte_Metodos() {
  $.ajax({ url: '../controller/reportes/controlador_por_metodo.php', type: 'POST', data: rfParametros('periodo') }).done(function(r) {
    [['INGRESO', '#rf_metodos_ingreso', 'Cobros'], ['EGRESO', '#rf_metodos_egreso', 'Pagos']].forEach(function(par) {
      var lado = r[par[0]], filas = '';
      lado.data.forEach(function(f) {
        filas += "<tr><td>" + rfEsc(f.metodo) + "</td><td>" + rfEsc(f.cuenta) + "</td>" + rfNum(f.operaciones) + rfMonto(f.total) + rfMonto(f.comision) + rfNum(f.porcentaje, ' %') + "</tr>";
      });
      rfLimpiar(par[1]);
      $(par[1] + ' tbody').html(filas || "<tr><td class='text-muted'>Sin movimientos</td><td></td><td></td><td></td><td></td><td></td></tr>");
      $(par[1] + ' tfoot').html("<tr><th>TOTAL</th><th></th><th></th>" + rfMonto(lado.total).replace(/td/g, 'th') + rfMonto(lado.comision).replace(/td/g, 'th') + "<th></th></tr>");
      rfExportable(par[1], par[2] + ' por forma de pago ' + rfPeriodoTexto());
    });
  });
}

// --- Morosidad ---
function Reporte_Morosidad() {
  $.ajax({ url: '../controller/reportes/controlador_morosidad.php', type: 'POST', data: rfParametros('escolar') }).done(function(r) {
    rfMorosidad = r;
    var t = r.totales;
    $('#rf_morosidad_tarjetas').html(
      rfTarjeta('Deuda vencida total', formatoMoneda(t.deuda), 'bg-danger') +
      rfTarjeta('Alumnos con deuda', t.alumnos, 'bg-warning') +
      rfTarjeta('Pensiones', formatoMoneda(t.pension), 'bg-secondary') +
      rfTarjeta('Mora', formatoMoneda(t.mora), 'bg-secondary'));
    var aviso = '';
    if (r.avisos.length) {
      var ej = r.avisos.slice(0, 4).map(function(a) { return rfEsc(a.nivel + ' ' + a.mes) + ' (' + rfFechaTexto(a.fecha_vencimiento) + ')'; }).join(', ');
      aviso = "<div class='alert alert-warning py-2'><i class='fas fa-exclamation-triangle'></i> <b>" + r.avisos.length + " pensiones</b> tienen un vencimiento que no corresponde al año escolar"
            + " (p. ej. " + ej + (r.avisos.length > 4 ? '…' : '') + "). Se cuentan como vencidas desde esa fecha; corrija las fechas en <b>Pensiones</b> para que los días de atraso y la mora sean correctos.</div>";
    }
    $('#rf_morosidad_avisos').html(aviso);

    var filas = '';
    r.alumnos.forEach(function(a) {
      filas += "<tr><td><b>" + rfEsc(a.alumno) + "</b></td><td>" + rfEsc(a.documento) + "</td><td>" + rfEsc(a.nivel) + "</td><td>" + rfEsc(a.grado + (a.seccion && a.seccion !== 'UNICO' ? ' ' + a.seccion : ''))
             + "</td><td>" + rfEsc(a.telefonos) + "</td><td>" + rfEsc(a.meses) + "</td>" + rfNum(a.dias_atraso) + rfMonto(a.pension) + rfMonto(a.mora) + rfMonto(a.deuda, 'text-danger font-weight-bold') + "</tr>";
    });
    rfLimpiar('#rf_morosidad_alumnos');
    $('#rf_morosidad_alumnos tbody').html(filas);
    rfExportable('#rf_morosidad_alumnos', 'Morosidad por alumno', true);

    filas = '';
    r.detalle.forEach(function(d) {
      filas += "<tr><td>" + rfEsc(d.alumno) + "</td><td>" + rfEsc(d.nivel) + "</td><td>" + rfEsc(d.grado) + "</td><td>" + d.mes + "</td><td data-order='" + d.fecha_vencimiento + "'>" + rfFechaTexto(d.fecha_vencimiento)
             + "</td>" + rfNum(d.dias_atraso) + rfMonto(d.precio) + rfMonto(d.pagado_pension) + rfMonto(d.saldo_pension) + rfMonto(d.saldo_mora) + rfMonto(d.deuda, 'font-weight-bold') + "</tr>";
    });
    rfLimpiar('#rf_morosidad_detalle');
    $('#rf_morosidad_detalle tbody').html(filas);
    rfExportable('#rf_morosidad_detalle', 'Morosidad por pensión', true);

    filas = '';
    r.aulas.forEach(function(a) {
      filas += "<tr><td>" + rfEsc(a.aula) + "</td>" + rfNum(a.alumnos) + rfMonto(a.deuda, 'font-weight-bold') + "</tr>";
    });
    rfLimpiar('#rf_morosidad_aulas');
    $('#rf_morosidad_aulas tbody').html(filas);
    rfExportable('#rf_morosidad_aulas', 'Morosidad por grado', true);
  });
}
function Vista_Morosidad(vista) {
  $('.rf-moro').hide();
  $('#rf_moro_' + vista).show();
  $('[id^=rf_moro_ver_]').removeClass('active');
  $('#rf_moro_ver_' + vista).addClass('active');
  $('#rf_morosidad_' + vista).DataTable().columns.adjust();
}

// --- Proyección de pensiones ---
function Reporte_Proyeccion() {
  $.ajax({ url: '../controller/reportes/controlador_proyeccion.php', type: 'POST', data: rfParametros('escolar') }).done(function(r) {
    var t = r.totales;
    $('#rf_proyeccion_tarjetas').html(
      rfTarjeta('Pensiones del año', formatoMoneda(t.esperado), 'bg-info') +
      rfTarjeta('Cobrado (' + t.avance + ' %)', formatoMoneda(t.cobrado), 'bg-success') +
      rfTarjeta('Vencido sin cobrar', formatoMoneda(t.vencido), 'bg-danger') +
      rfTarjeta('Por vencer', formatoMoneda(t.por_vencer), 'bg-secondary'));
    var filas = '';
    r.data.forEach(function(m) {
      filas += "<tr><td><b>" + m.mes + "</b></td><td>" + rfFechaTexto(m.vencimiento) + "</td>" + rfNum(m.matriculas) + rfMonto(m.esperado) + rfMonto(m.cobrado, 'text-success')
             + rfMonto(m.vencido, m.vencido > 0 ? 'text-danger' : '') + rfMonto(m.mora_pendiente) + rfMonto(m.por_vencer)
             + "<td data-order='" + m.avance + "'><div class='progress progress-sm mb-0'><div class='progress-bar bg-success' style='width:" + Math.min(m.avance, 100) + "%'></div></div><small>" + m.avance + " %</small></td></tr>";
    });
    rfLimpiar('#rf_proyeccion');
    $('#rf_proyeccion tbody').html(filas || "<tr><td class='text-muted'>No hay matrículas ni pensiones en este año escolar</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>");
    $('#rf_proyeccion tfoot').html("<tr><th>TOTAL</th><th></th><th></th>" + ['esperado', 'cobrado', 'vencido', 'mora_pendiente', 'por_vencer'].map(function(k) { return rfMonto(t[k]).replace(/td/g, 'th'); }).join('')
      + "<th data-order='" + t.avance + "'>" + t.avance + " %</th></tr>");
    rfExportable('#rf_proyeccion', 'Proyección de pensiones ' + $('#rf_anio_escolar option:selected').text());
  });
}

// --- Costo de planilla ---
function Reporte_Planilla() {
  $.ajax({ url: '../controller/reportes/controlador_planilla.php', type: 'POST', data: rfParametros('anio') }).done(function(r) {
    var tarjetas = '';
    r.tipos.forEach(function(x) {
      tarjetas += rfTarjeta(rfEsc(x.tipo_contrato) + ' · ' + x.docentes + ' docente(s) · ' + x.porcentaje + ' % del costo<br><small>Promedio mensual ' + formatoMoneda(x.promedio_mes) + '</small>',
                            formatoMoneda(x.bruto), 'bg-light', 'col-lg-4 col-12');
    });
    $('#rf_planilla_tipos').html(tarjetas || "<div class='col-12 text-muted mb-2'>No hay planillas en " + r.anio + ".</div>");
    var filas = '';
    var campos = ['base', 'ingresos', 'bruto', 'descuentos', 'adelantos', 'neto', 'pagado'];
    r.data.forEach(function(f) {
      filas += "<tr><td>" + f.mes_nombre + "</td><td>" + f.estado + "</td><td>" + rfEsc(f.tipo_contrato) + "</td>" + rfNum(f.docentes) + rfNum(f.horas);
      campos.forEach(function(k) { filas += rfMonto(f[k], k === 'bruto' ? 'font-weight-bold' : ''); });
      filas += "</tr>";
    });
    rfLimpiar('#rf_planilla');
    $('#rf_planilla tbody').html(filas);
    $('#rf_planilla tfoot').html("<tr><th>TOTAL</th><th></th><th></th><th></th>" + rfNum(r.totales.horas).replace(/td/g, 'th') + campos.map(function(k) { return rfMonto(r.totales[k]).replace(/td/g, 'th'); }).join('') + "</tr>");
    rfExportable('#rf_planilla', 'Costo de planilla docente ' + r.anio);
  });
}

// --- Cierres de caja ---
function Reporte_Cierres() {
  $.ajax({ url: '../controller/reportes/controlador_cierres.php', type: 'POST', data: rfParametros('periodo') }).done(function(r) {
    var t = r.totales;
    $('#rf_cierres_tarjetas').html(
      rfTarjeta('Cierres', t.cierres, 'bg-info') +
      rfTarjeta('Con diferencia', t.con_diferencia, t.con_diferencia ? 'bg-warning' : 'bg-success') +
      rfTarjeta('Faltantes', formatoMoneda(t.faltantes), 'bg-danger') +
      rfTarjeta('Sobrantes', formatoMoneda(t.sobrantes), 'bg-secondary'));
    var filas = '';
    r.data.forEach(function(c) {
      filas += "<tr><td data-order='" + c.fecha + "'>" + rfFechaTexto(c.fecha) + "</td><td>" + rfEsc(c.cuenta) + "</td>" + rfMonto(c.saldo_inicial) + rfMonto(c.entradas) + rfMonto(c.salidas)
             + rfMonto(c.saldo_esperado) + rfMonto(c.saldo_contado) + rfMonto(c.diferencia, c.diferencia < 0 ? 'text-danger font-weight-bold' : (c.diferencia > 0 ? 'text-primary font-weight-bold' : ''))
             + "<td>" + (c.ajustado == 1 ? 'Sí' : 'No') + "</td><td>" + rfEsc(c.observacion) + "</td><td>" + rfEsc(c.usuario) + "</td></tr>";
    });
    rfLimpiar('#rf_cierres');
    $('#rf_cierres tbody').html(filas);
    rfExportable('#rf_cierres', 'Cierres de caja ' + rfPeriodoTexto(), true);
  });
}
