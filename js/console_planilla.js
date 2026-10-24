// Planilla de docentes (Fase 6) y del personal administrativo (Fase 8B): contratos, planilla mensual, pagos, adelantos y conceptos
var PL = {contratos: [], tipos: [], conceptos: [], detalle: [], planilla: null};
// Tipo de personal elegido arriba de la pantalla: DOCENTE o ADMINISTRATIVO
var tipoPL = function() { return $("#select_tipo_personal").val() || 'DOCENTE'; };
var nombreTipoPL = function() { return tipoPL() == 'DOCENTE' ? 'docentes' : 'personal administrativo'; };
var escPL = function(v) { return $('<div>').text(v === null || v === undefined ? '' : v).html(); };
var hoyPL = function() { return new Date().toLocaleDateString('en-CA'); };
var esAdminPL = function() { return $("#txtprincipalrol").val() == 'ADMINISTRADOR'; };
var colorEstadoPL = {BORRADOR: 'bg-secondary', APROBADA: 'bg-primary', PAGADA: 'bg-success', ANULADA: 'bg-danger', VALIDO: 'bg-success', ANULADO: 'bg-danger'};
var badgePL = function(e) { return "<span class='badge " + (colorEstadoPL[e] || 'bg-secondary') + "'>" + e + "</span>"; };

function Iniciar_Planilla() {
  let hoy = new Date();
  $("#txt_anio_pla").val(hoy.getFullYear());
  $("#select_mes_pla").val(hoy.getMonth() + 1);
  $("#txt_desde_ade").val(hoyPL().substring(0, 5) + '01-01');
  $("#txt_hasta_ade, #txt_fecha_ade").val(hoyPL());
  cargarFormasPago(function() {
    llenarSelectMetodos('#select_metodo_ade, #select_metodo_contrato, #select_metodo_pagar_doc');
    llenarSelectCuentas('#select_cuenta_ade, #select_cuenta_pagar_doc', true);
    sugerirCuenta('#select_metodo_ade', '#select_cuenta_ade', '#txt_ref_ade');
  });
  $(document).off('change.pl', '#select_metodo_ade').on('change.pl', '#select_metodo_ade', function() { sugerirCuenta('#select_metodo_ade', '#select_cuenta_ade', '#txt_ref_ade'); });
  $(document).off('change.pl', '#select_metodo_pagar_doc').on('change.pl', '#select_metodo_pagar_doc', function() { sugerirCuenta('#select_metodo_pagar_doc', '#select_cuenta_pagar_doc', '#txt_ref_pagar_doc'); });
  $(document).off('change.pl', '#select_tipo_contrato').on('change.pl', '#select_tipo_contrato', Mostrar_Campos_Contrato);
  $(document).off('change.pl', '#select_tipo_personal').on('change.pl', '#select_tipo_personal', Cambiar_Tipo_Personal);
  Listar_Planillas();
  Listar_Contratos();
  Listar_Adelantos();
  Listar_Conceptos();
}

// Al cambiar de docentes a personal administrativo (o al revés) se recarga todo lo que depende del tipo
function Cambiar_Tipo_Personal() {
  PL.planilla = null;
  $("#panel_detalle").hide();
  $(".lb-tipo-personal").text(tipoPL() == 'DOCENTE' ? 'Docente' : 'Trabajador');
  $(".lb-tipo-personal-plural").text(tipoPL() == 'DOCENTE' ? 'Docentes' : 'Trabajadores');
  Listar_Planillas();
  Listar_Contratos();
  Listar_Adelantos();
}

// ---------- Planillas ----------
function Listar_Planillas() {
  $.ajax({ url: "../controller/planilla/controlador_planillas_listar.php", type: 'POST', data: { anio: $("#txt_anio_pla").val(), tipo_personal: tipoPL() } }).done(function(resp) {
    let filas = "";
    resp.data.forEach(function(p) {
      filas += "<tr><td><b>" + p.mes_nombre + " " + p.anio + "</b></td><td>" + p.docentes + "</td><td class='text-right'>" + formatoMoneda(p.total_bruto)
             + "</td><td class='text-right'>" + formatoMoneda(p.total_descuentos) + "</td><td class='text-right'><b>" + formatoMoneda(p.total_neto)
             + "</b></td><td class='text-right'>" + formatoMoneda(p.total_pagado) + "</td><td>" + badgePL(p.estado) + "</td><td>" + escPL(p.aprobado_por || '')
             + (p.fecha_aprobacion ? "<br><small>" + p.fecha_aprobacion + "</small>" : "") + "</td><td>"
             + (p.estado != 'ANULADA' ? "<button class='btn btn-primary btn-sm' onclick='Ver_Planilla(" + p.id_planilla + ")'><i class='fas fa-eye'></i> Ver</button>" : escPL(p.motivo_anulacion))
             + "</td></tr>";
    });
    $("#tbody_planillas").html(filas || "<tr><td colspan='9' class='text-center text-muted'>No hay planillas en ese año</td></tr>");
  });
}

function Generar_Planilla() {
  let mes = $("#select_mes_pla option:selected").text(), anio = $("#txt_anio_pla").val();
  Swal.fire({ title: '¿Generar la planilla de ' + mes + ' ' + anio + '?', text: 'Planilla de ' + nombreTipoPL() + ': se incluye a todos los que tienen contrato vigente en ese mes y se descuentan sus adelantos pendientes.' + (tipoPL() == 'DOCENTE' ? ' Los contratos por horas toman las horas de la asistencia docente.' : ''),
              icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, generar' }).then((r) => {
    if (!r.isConfirmed) { return; }
    $.ajax({ url: "../controller/planilla/controlador_planilla_generar.php", type: 'POST', data: { anio: anio, mes: $("#select_mes_pla").val(), tipo_personal: tipoPL() } }).done(function(resp) {
      if (resp.codigo == 1) {
        Swal.fire("Mensaje de Confirmación", resp.mensaje, "success");
        Listar_Planillas();
        Ver_Planilla(resp.id);
      } else {
        Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
      }
    });
  });
}

function Ver_Planilla(id) {
  $.ajax({ url: "../controller/planilla/controlador_planilla_detalle.php", type: 'POST', data: { id: id } }).done(function(resp) {
    PL.detalle = resp.data;
    PL.conceptos = resp.conceptos;
    PL.planilla = id;
    let estado = resp.data.length ? resp.data[0].estado_planilla : '';
    let p = resp.data[0] || {};
    $("#titulo_detalle").text("Detalle de la planilla de " + nombreTipoPL());
    $("#estado_detalle").html(badgePL(estado));
    let botones = "";
    if (estado == 'BORRADOR') {
      botones += esAdminPL() ? "<button class='btn btn-success btn-sm' onclick='Accion_Planilla(\"aprobar\")'><i class='fas fa-check'></i> Aprobar</button> "
                             : "<small class='text-muted'>La aprueba el administrador</small> ";
      botones += "<button class='btn btn-outline-danger btn-sm' onclick='Anular_Planilla()'><i class='fas fa-ban'></i> Anular</button>";
    } else if (estado == 'APROBADA' && esAdminPL()) {
      botones += "<button class='btn btn-outline-secondary btn-sm' onclick='Accion_Planilla(\"reabrir\")' title='Solo si no tiene pagos'><i class='fas fa-undo'></i> Volver a borrador</button> "
               + "<button class='btn btn-outline-danger btn-sm' onclick='Anular_Planilla()'><i class='fas fa-ban'></i> Anular</button>";
    }
    $("#botones_detalle").html(botones);
    let filas = "", tot = {base: 0, ingresos: 0, descuentos: 0, adelantos: 0, neto: 0, pagado: 0};
    resp.data.forEach(function(d) {
      Object.keys(tot).forEach(k => tot[k] += parseFloat(d[k]));
      let acciones = "<a class='btn btn-default btn-sm' target='_blank' href='../view/MPDF/REPORTE/boleta_docente.php?id=" + d.id_detalle + "' title='Boleta'><i class='fas fa-print'></i></a> ";
      if (estado == 'BORRADOR') { acciones += "<button class='btn btn-primary btn-sm' onclick='Abrir_Detalle(" + d.id_detalle + ")'><i class='fas fa-edit'></i> Ajustar</button>"; }
      if (estado == 'APROBADA' || estado == 'PAGADA') { acciones += "<button class='btn btn-success btn-sm' onclick='Abrir_Pago(" + d.id_detalle + ")'><i class='fas fa-money-bill-wave'></i> " + (parseFloat(d.saldo) > 0 ? 'Pagar' : 'Pagos') + "</button>"; }
      filas += "<tr" + (parseFloat(d.neto) < 0 ? " class='table-danger'" : "") + "><td><b>" + escPL(d.docente) + "</b>" + (d.cargo && d.cargo != 'DOCENTE' ? " <small class='text-muted'>(" + escPL(d.cargo) + ")</small>" : "")
             + "<br><small>" + escPL(d.conceptos) + "</small>" + Asistencia_Detalle_PL(d) + "</td><td>" + escPL(d.tipo_contrato)
             + "</td><td class='text-right'>" + (d.calculo == 'POR_HORA' ? d.horas + "<br><small class='text-muted'>" + ({ASISTENCIA: 'asistencia', ESTIMADO: 'estimado', MANUAL: 'ajustado'}[d.horas_origen] || '') + "</small>" : '-') + "</td><td class='text-right'>" + formatoMoneda(d.base)
             + "</td><td class='text-right text-success'>" + formatoMoneda(d.ingresos) + "</td><td class='text-right text-danger'>" + formatoMoneda(d.descuentos)
             + "</td><td class='text-right text-danger'>" + formatoMoneda(d.adelantos) + "</td><td class='text-right'><b>" + formatoMoneda(d.neto) + "</b></td><td class='text-right'>"
             + formatoMoneda(d.pagado) + "</td><td>" + escPL(d.metodo) + (d.cuenta_docente ? "<br><small>" + escPL(d.banco_docente) + " " + escPL(d.cuenta_docente) + "</small>" : "")
             + "</td><td>" + acciones + "</td></tr>";
    });
    filas += "<tr class='font-weight-bold'><td colspan='3'>TOTAL</td><td class='text-right'>" + formatoMoneda(tot.base) + "</td><td class='text-right'>" + formatoMoneda(tot.ingresos)
           + "</td><td class='text-right'>" + formatoMoneda(tot.descuentos) + "</td><td class='text-right'>" + formatoMoneda(tot.adelantos) + "</td><td class='text-right'>"
           + formatoMoneda(tot.neto) + "</td><td class='text-right'>" + formatoMoneda(tot.pagado) + "</td><td colspan='2'></td></tr>";
    $("#tbody_detalle").html(filas);
    $("#panel_detalle").show();
  });
}

function Accion_Planilla(accion) {
  $.ajax({ url: "../controller/planilla/controlador_planilla_" + accion + ".php", type: 'POST', data: { id: PL.planilla } }).done(function(resp) {
    Swal.fire(resp.codigo == 1 ? "Mensaje de Confirmación" : "Mensaje de Advertencia", resp.mensaje, resp.codigo == 1 ? "success" : "warning");
    Listar_Planillas();
    Ver_Planilla(PL.planilla);
  });
}

function Anular_Planilla() {
  Swal.fire({ title: '¿Anular la planilla?', text: 'Los adelantos que descontaba quedan pendientes para la próxima. Indique el motivo:', icon: 'warning', input: 'text',
              inputValidator: (v) => { if (!v || !v.trim()) { return 'El motivo es obligatorio'; } }, showCancelButton: true, confirmButtonText: 'Sí, anular' }).then((r) => {
    if (!r.isConfirmed) { return; }
    $.ajax({ url: "../controller/planilla/controlador_planilla_anular.php", type: 'POST', data: { id: PL.planilla, motivo: r.value.trim() } }).done(function(resp) {
      Swal.fire(resp.codigo == 1 ? "Mensaje de Confirmación" : "Mensaje de Advertencia", resp.mensaje, resp.codigo == 1 ? "success" : "warning");
      if (resp.codigo == 1) { $("#panel_detalle").hide(); }
      Listar_Planillas();
      Listar_Adelantos();
    });
  });
}

// ---------- Ajuste del borrador ----------
function Abrir_Detalle(id) {
  let d = PL.detalle.find(x => x.id_detalle == id);
  $("#txt_id_detalle").val(id);
  $("#lb_detalle").text(d.docente + " – " + d.tipo_contrato);
  $("#grupo_horas_det").toggle(d.calculo == 'POR_HORA');
  $("#grupo_base_det").toggle(d.calculo != 'POR_HORA');
  $("#txt_horas_det").val(d.horas);
  $("#txt_base_det").val(d.base);
  let ops = "";
  PL.conceptos.filter(c => c.estado == 'ACTIVO').forEach(function(c) {
    ops += "<option value='" + c.id_concepto + "'>" + (c.tipo == 'INGRESO' ? '+ ' : '- ') + escPL(c.nombre) + (c.forma == 'PORCENTAJE' ? ' (%)' : '') + "</option>";
  });
  $("#select_concepto_det").html(ops);
  $("#txt_valor_det").val('');
  Cargar_Conceptos_Detalle();
  $("#modal_detalle").modal('show');
}

function Cargar_Conceptos_Detalle() {
  let id = $("#txt_id_detalle").val();
  $.ajax({ url: "../controller/planilla/controlador_detalle_conceptos.php", type: 'POST', data: { id: id } }).done(function(resp) {
    let filas = "";
    resp.conceptos.forEach(function(c) {
      filas += "<tr><td>" + (c.tipo == 'INGRESO' ? "<span class='text-success'>+</span> " : "<span class='text-danger'>-</span> ") + escPL(c.nombre)
             + (c.forma == 'PORCENTAJE' ? " (" + c.valor + "%)" : "") + "</td><td class='text-right'>" + formatoMoneda(c.monto)
             + "</td><td><button class='btn btn-danger btn-sm' onclick='Quitar_Concepto_Detalle(" + c.id + ")'><i class='fas fa-times'></i></button></td></tr>";
    });
    $("#tbody_conceptos_det").html(filas || "<tr><td class='text-muted'>Sin ingresos ni descuentos adicionales</td></tr>");
    Ver_Planilla(PL.planilla);
    setTimeout(function() {
      let d = PL.detalle.find(x => x.id_detalle == id);
      if (d) {
        $("#resumen_det").html("Base " + formatoMoneda(d.base) + " + ingresos " + formatoMoneda(d.ingresos) + " - descuentos " + formatoMoneda(d.descuentos)
          + " - adelantos " + formatoMoneda(d.adelantos) + " = <b>" + formatoMoneda(d.neto) + "</b>");
      }
    }, 400);
  });
}

function Actualizar_Detalle() {
  $.ajax({ url: "../controller/planilla/controlador_detalle_actualizar.php", type: 'POST',
           data: { id: $("#txt_id_detalle").val(), horas: $("#txt_horas_det").val(), base: $("#txt_base_det").val() } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    Cargar_Conceptos_Detalle();
  });
}

function Agregar_Concepto_Detalle() {
  $.ajax({ url: "../controller/planilla/controlador_concepto_agregar.php", type: 'POST',
           data: { id: $("#txt_id_detalle").val(), concepto: $("#select_concepto_det").val(), valor: $("#txt_valor_det").val() } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    $("#txt_valor_det").val('');
    Cargar_Conceptos_Detalle();
  });
}

function Quitar_Concepto_Detalle(id) {
  $.ajax({ url: "../controller/planilla/controlador_concepto_quitar.php", type: 'POST', data: { id: id } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    Cargar_Conceptos_Detalle();
  });
}

// ---------- Pagos ----------
function Abrir_Pago(id) {
  let d = PL.detalle.find(x => x.id_detalle == id);
  $("#txt_id_pagar").val(id);
  $("#lb_pagar").text("PAGO A " + d.docente + " – pendiente " + formatoMoneda(d.saldo));
  $("#txt_monto_pagar_doc").val(parseFloat(d.saldo) > 0 ? d.saldo : '');
  $("#txt_fecha_pagar_doc").val(hoyPL());
  $("#txt_ref_pagar_doc").val('');
  // Se propone la forma de pago del contrato del docente
  $("#select_metodo_pagar_doc").val(d.metodo_pago_id);
  sugerirCuenta('#select_metodo_pagar_doc', '#select_cuenta_pagar_doc', '#txt_ref_pagar_doc');
  Cargar_Pagos_Detalle(id);
  $("#modal_pagar").modal('show');
}

function Cargar_Pagos_Detalle(id) {
  $.ajax({ url: "../controller/planilla/controlador_detalle_conceptos.php", type: 'POST', data: { id: id } }).done(function(resp) {
    let filas = "";
    resp.pagos.forEach(function(p) {
      filas += "<tr" + (p.estado != 'VALIDO' ? " style='text-decoration:line-through;color:#999'" : "") + "><td>" + p.fecha_txt + "</td><td class='text-right'>" + formatoMoneda(p.monto)
             + "</td><td>" + escPL(p.metodo) + "</td><td>" + escPL(p.cuenta) + "</td><td>" + escPL(p.referencia) + "</td><td>" + badgePL(p.estado) + "</td><td>"
             + (p.estado == 'VALIDO' ? "<button class='btn btn-danger btn-sm' onclick='Anular_Pago_Docente(" + p.id_pago_docente + ", " + id + ")'><i class='fas fa-ban'></i></button>" : "")
             + "</td></tr>";
    });
    $("#tbody_pagos_det").html(filas || "<tr><td colspan='7' class='text-muted text-center'>Sin pagos</td></tr>");
  });
}

function Pagar_Detalle() {
  let id = $("#txt_id_pagar").val();
  $.ajax({ url: "../controller/planilla/controlador_pagar.php", type: 'POST',
           data: { id: id, monto: $("#txt_monto_pagar_doc").val(), fecha: $("#txt_fecha_pagar_doc").val(), metodo: $("#select_metodo_pagar_doc").val(),
                   cuenta: $("#select_cuenta_pagar_doc").val(), referencia: $("#txt_ref_pagar_doc").val() } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    Swal.fire("Mensaje de Confirmación", "Pago registrado. Se generó el egreso en Remuneraciones " + (tipoPL() == 'DOCENTE' ? "docentes." : "administrativas."), "success");
    $("#modal_pagar").modal('hide');
    Listar_Planillas();
    Ver_Planilla(PL.planilla);
  });
}

function Anular_Pago_Docente(id, id_detalle) {
  Swal.fire({ title: '¿Anular este pago?', text: 'También se anula su egreso y vuelve el dinero a la caja/cuenta. Motivo:', icon: 'warning', input: 'text',
              inputValidator: (v) => { if (!v || !v.trim()) { return 'El motivo es obligatorio'; } }, showCancelButton: true, confirmButtonText: 'Sí, anular' }).then((r) => {
    if (!r.isConfirmed) { return; }
    $.ajax({ url: "../controller/planilla/controlador_pago_anular.php", type: 'POST', data: { id: id, motivo: r.value.trim() } }).done(function(resp) {
      if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
      if (id_detalle) { Cargar_Pagos_Detalle(id_detalle); Ver_Planilla(PL.planilla); }
      Listar_Planillas();
      Listar_Adelantos();
      Listar_Contratos();
    });
  });
}

// Resumen de la asistencia docente del mes (faltas y tardanzas para decidir descuentos)
function Asistencia_Detalle_PL(d) {
  if (!d.docente_id || !d.asis_dias) { return tipoPL() == 'DOCENTE' ? "<br><small class='text-muted'>Sin asistencia registrada en el mes</small>" : ""; }
  return "<br><small><i class='fas fa-user-check'></i> " + d.asis_dias + " día(s), " + parseFloat(d.asis_horas).toFixed(2) + " h dictadas"
       + (d.asis_faltas > 0 ? ", <b class='text-danger'>" + d.asis_faltas + " falta(s)</b>" : "")
       + (d.asis_tardanzas > 0 ? ", <b class='text-warning'>" + d.asis_tardanzas + " tardanza(s) (" + d.asis_minutos + " min)</b>" : "") + "</small>";
}

// ---------- Contratos ----------
function Listar_Contratos() {
  $.ajax({ url: "../controller/planilla/controlador_contratos_listar.php", type: 'POST', data: { tipo_personal: tipoPL() } }).done(function(resp) {
    PL.contratos = resp.data;
    PL.tipos = resp.tipos;
    let filas = "", docentes = "";
    resp.data.forEach(function(c) {
      let monto = c.id_contrato ? (c.calculo == 'POR_HORA' ? formatoMoneda(c.tarifa_hora) + " / hora" : formatoMoneda(c.sueldo_base) + " / mes") : "";
      filas += "<tr><td><b>" + escPL(c.docente) + "</b><br><small>" + escPL(c.docente_dni) + (c.cargo && c.cargo != 'DOCENTE' ? " · " + escPL(c.cargo) : "") + "</small></td><td>" + (c.tipo_contrato ? escPL(c.tipo_contrato) : "<span class='badge bg-warning'>SIN CONTRATO</span>")
             + "</td><td>" + (c.fecha_inicio_txt || '') + "</td><td class='text-right'>" + monto + "</td><td class='text-right'>" + (c.id_contrato ? c.horas_semanales : '')
             + "</td><td>" + escPL(c.metodo || '') + "</td><td>" + escPL((c.banco_docente || '') + ' ' + (c.cuenta_docente || '')) + "</td><td class='text-right'>"
             + (parseFloat(c.adelantos_pendientes) > 0 ? formatoMoneda(c.adelantos_pendientes) : '') + "</td><td>"
             + (c.docente_estatus == 'ACTIVO' ? "<button class='btn btn-primary btn-sm' onclick='Abrir_Contrato(" + c.Id_docente + ", false)'><i class='fas fa-edit'></i> " + (c.id_contrato ? 'Editar' : 'Registrar') + "</button> " : "")
             + (c.id_contrato ? "<button class='btn btn-outline-primary btn-sm' onclick='Abrir_Contrato(" + c.Id_docente + ", true)' title='Nuevo contrato (aumento, cambio de tipo)'><i class='fas fa-plus'></i> Nuevo</button>" : "")
             + "</td></tr>";
      if (c.id_contrato) { docentes += "<option value='" + c.Id_docente + "'>" + escPL(c.docente) + "</option>"; }
    });
    $("#tbody_contratos").html(filas);
    $("#select_docente_ade").html(docentes || "<option value=''>Nadie tiene contrato</option>");
    let tipos = "";
    resp.tipos.forEach(t => tipos += "<option value='" + t.id_tipo_contrato + "' data-calculo='" + t.calculo + "'>" + escPL(t.nombre) + "</option>");
    $("#select_tipo_contrato").html(tipos);
  });
}

function Mostrar_Campos_Contrato() {
  let hora = $("#select_tipo_contrato option:selected").data('calculo') == 'POR_HORA';
  $(".grupo-hora").toggle(hora);
  $(".grupo-mensual").toggle(!hora);
}

function Abrir_Contrato(docente, nuevo) {
  let c = PL.contratos.find(x => x.Id_docente == docente);
  let editar = c.id_contrato && !nuevo;
  $("#lb_contrato").text((editar ? "EDITAR CONTRATO – " : "NUEVO CONTRATO – ") + c.docente);
  $("#txt_id_contrato").val(editar ? c.id_contrato : '');
  $("#txt_docente_contrato").val(docente);
  $("#aviso_contrato").toggle(!!(c.id_contrato && nuevo));
  $("#btn_finalizar_contrato").toggle(!!editar);
  $("#select_tipo_contrato").val(c.tipo_contrato_id || $("#select_tipo_contrato option:first").val());
  $("#txt_inicio_contrato").val(editar ? c.fecha_inicio : hoyPL());
  $("#txt_sueldo_contrato").val(c.sueldo_base || '');
  $("#txt_tarifa_contrato").val(c.tarifa_hora || '');
  $("#txt_horas_contrato").val(c.horas_semanales || '');
  $("#select_metodo_contrato").val(c.metodo_pago_id || $("#select_metodo_contrato option:first").val());
  $("#txt_banco_contrato").val(c.banco_docente || '');
  $("#txt_cuenta_contrato").val(c.cuenta_docente || '');
  $("#txt_obs_contrato").val(c.observacion || '');
  Mostrar_Campos_Contrato();
  $("#modal_contrato").modal('show');
}

function Guardar_Contrato() {
  $.ajax({ url: "../controller/planilla/controlador_contrato_guardar.php", type: 'POST',
           data: { id: $("#txt_id_contrato").val(), tipo_personal: tipoPL(), docente: $("#txt_docente_contrato").val(), tipo: $("#select_tipo_contrato").val(), inicio: $("#txt_inicio_contrato").val(),
                   sueldo: $("#txt_sueldo_contrato").val(), tarifa: $("#txt_tarifa_contrato").val(), horas: $("#txt_horas_contrato").val(), metodo: $("#select_metodo_contrato").val(),
                   banco: $("#txt_banco_contrato").val(), cuenta: $("#txt_cuenta_contrato").val(), observacion: $("#txt_obs_contrato").val() } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    $("#modal_contrato").modal('hide');
    Swal.fire("Mensaje de Confirmación", resp.mensaje, "success");
    Listar_Contratos();
  });
}

function Finalizar_Contrato() {
  Swal.fire({ title: 'Finalizar contrato', text: 'Fecha de fin (AAAA-MM-DD):', input: 'text', inputValue: hoyPL(), showCancelButton: true, confirmButtonText: 'Finalizar' }).then((r) => {
    if (!r.isConfirmed) { return; }
    $.ajax({ url: "../controller/planilla/controlador_contrato_finalizar.php", type: 'POST', data: { id: $("#txt_id_contrato").val(), fin: r.value.trim() } }).done(function(resp) {
      if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
      $("#modal_contrato").modal('hide');
      Listar_Contratos();
    });
  });
}

// ---------- Adelantos ----------
function Registrar_Adelanto() {
  $.ajax({ url: "../controller/planilla/controlador_adelanto_registrar.php", type: 'POST',
           data: { tipo_personal: tipoPL(), docente: $("#select_docente_ade").val(), monto: $("#txt_monto_ade").val(), fecha: $("#txt_fecha_ade").val(), metodo: $("#select_metodo_ade").val(),
                   cuenta: $("#select_cuenta_ade").val(), referencia: $("#txt_ref_ade").val(), observacion: $("#txt_obs_ade").val() } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    Swal.fire("Mensaje de Confirmación", resp.mensaje, "success");
    $("#txt_monto_ade, #txt_ref_ade, #txt_obs_ade").val('');
    Listar_Adelantos();
    Listar_Contratos();
    if (PL.planilla) { Ver_Planilla(PL.planilla); }
  });
}

function Listar_Adelantos() {
  $.ajax({ url: "../controller/planilla/controlador_adelantos_listar.php", type: 'POST', data: { desde: $("#txt_desde_ade").val(), hasta: $("#txt_hasta_ade").val(), tipo_personal: tipoPL() } }).done(function(resp) {
    let filas = "";
    resp.data.forEach(function(a) {
      filas += "<tr><td>" + a.fecha_txt + "</td><td>" + escPL(a.docente) + "</td><td class='text-right'>" + formatoMoneda(a.monto) + "</td><td>" + escPL(a.metodo)
             + (a.cuenta ? " · " + escPL(a.cuenta) : "") + "</td><td>" + escPL(a.referencia) + "</td><td>" + escPL(a.observacion) + "</td><td><small>" + escPL(a.situacion)
             + "</small></td><td>" + badgePL(a.estado) + "</td><td>" + (a.estado == 'VALIDO' ? "<button class='btn btn-danger btn-sm' onclick='Anular_Pago_Docente(" + a.id_pago_docente + ", 0)'><i class='fas fa-ban'></i></button>" : "") + "</td></tr>";
    });
    $("#tbody_adelantos").html(filas || "<tr><td colspan='9' class='text-center text-muted'>Sin adelantos en el período</td></tr>");
  });
}

// ---------- Conceptos ----------
function Listar_Conceptos() {
  $.ajax({ url: "../controller/planilla/controlador_conceptos_listar.php", type: 'POST' }).done(function(resp) {
    PL.conceptos = resp.data;
    let filas = "";
    resp.data.forEach(function(c) {
      filas += "<tr><td><b>" + escPL(c.nombre) + "</b></td><td>" + c.tipo + "</td><td>" + (c.forma == 'PORCENTAJE' ? 'PORCENTAJE' : 'MONTO FIJO') + "</td><td class='text-right'>"
             + (c.forma == 'PORCENTAJE' ? c.valor_defecto + " %" : formatoMoneda(c.valor_defecto)) + "</td><td>" + "<span class='badge " + (c.estado == 'ACTIVO' ? "bg-success" : "bg-secondary") + "'>" + c.estado + "</span>"
             + "</td><td><button class='btn btn-primary btn-sm' onclick='Abrir_Concepto(" + c.id_concepto + ")'><i class='fas fa-edit'></i></button></td></tr>";
    });
    $("#tbody_conceptos").html(filas);
  });
}

function Abrir_Concepto(id) {
  let c = PL.conceptos.find(x => x.id_concepto == id) || {tipo: 'INGRESO', forma: 'MONTO', valor_defecto: 0, estado: 'ACTIVO'};
  $("#txt_id_concepto").val(id || '');
  $("#txt_nombre_concepto").val(c.nombre || '');
  $("#select_tipo_concepto").val(c.tipo);
  $("#select_forma_concepto").val(c.forma);
  $("#txt_valor_concepto").val(c.valor_defecto);
  $("#select_estado_concepto").val(c.estado);
  $("#modal_concepto").modal('show');
}

function Guardar_Concepto() {
  $.ajax({ url: "../controller/planilla/controlador_concepto_guardar.php", type: 'POST',
           data: { id: $("#txt_id_concepto").val(), nombre: $("#txt_nombre_concepto").val(), tipo: $("#select_tipo_concepto").val(), forma: $("#select_forma_concepto").val(),
                   valor: $("#txt_valor_concepto").val(), estado: $("#select_estado_concepto").val() } }).done(function(resp) {
    if (resp.codigo != 1) { return Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning"); }
    $("#modal_concepto").modal('hide');
    Listar_Conceptos();
  });
}
