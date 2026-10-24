// Cajas y bancos (Fase 5): saldos, movimientos, depósitos/transferencias, cierre de caja y conciliación
var cuentas_cb = [];
var escCB = function(v) { return $('<div>').text(v === null || v === undefined ? '' : v).html(); };
var hoyCB = function() { return new Date().toLocaleDateString('en-CA'); };

function Iniciar_Cajas_Bancos() {
  let hoy = hoyCB();
  let inicioMes = hoy.substring(0, 8) + '01';
  $("#txt_desde_mov, #txt_desde_tr").val(inicioMes);
  $("#txt_hasta_mov, #txt_hasta_tr, #txt_fecha_tr, #txt_fecha_cierre").val(hoy);
  Cargar_Saldos(function() {
    Listar_Movimientos();
    Listar_Transferencias();
    Resumen_Caja();
  });
}

function Cargar_Saldos(callback) {
  $.ajax({ url: "../controller/contabilidad/controlador_saldos.php", type: 'POST' }).done(function(resp) {
    cuentas_cb = resp.data.filter(c => c.estado == 'ACTIVO');
    let tarjetas = "", opciones = "", cajas = "";
    let iconos = {CAJA: 'fa-cash-register', BANCO: 'fa-university', BILLETERA: 'fa-mobile-alt'};
    let colores = {CAJA: 'bg-success', BANCO: 'bg-info', BILLETERA: 'bg-warning'};
    cuentas_cb.forEach(function(c) {
      tarjetas += "<div class='col-lg-3 col-md-4 col-6'><div class='small-box " + colores[c.tipo] + "'><div class='inner'>"
                + "<h4>" + formatoMoneda(c.saldo) + "</h4><p><b>" + escCB(c.nombre) + "</b><br><small>Hoy: +" + formatoMoneda(c.entradas_hoy)
                + " / -" + formatoMoneda(c.salidas_hoy) + "</small></p></div><div class='icon'><i class='fas " + iconos[c.tipo] + "'></i></div>"
                + "<a href='#' class='small-box-footer' onclick='Ver_Cuenta(" + c.id_cuenta + ");return false;'>Ver movimientos <i class='fas fa-arrow-circle-right'></i></a></div></div>";
      opciones += "<option value='" + c.id_cuenta + "' data-tipo='" + c.tipo + "'>" + escCB(c.nombre) + "</option>";
      if (c.tipo == 'CAJA') { cajas += "<option value='" + c.id_cuenta + "'>" + escCB(c.nombre) + "</option>"; }
    });
    $("#tarjetas_cuentas").html(tarjetas || "<div class='col-12'><div class='alert alert-warning'>No hay cajas ni cuentas activas. Créelas en Formas de pago y cuentas.</div></div>");
    let actual = $("#select_cuenta_mov").val();
    $("#select_cuenta_mov").html(opciones).val(actual || $("#select_cuenta_mov option:first").val());
    $("#select_origen_tr, #select_destino_tr").html(opciones);
    $("#select_caja_cierre").html(cajas || "<option value=''>No hay cajas</option>");
    Sugerir_Transferencia();
    if (callback) { callback(); }
  });
}

function Ver_Cuenta(id) {
  $("#select_cuenta_mov").val(id);
  $('a[href="#tab_movimientos"]').tab('show');
  Listar_Movimientos();
}

function Listar_Movimientos() {
  let cuenta = $("#select_cuenta_mov").val();
  if (!cuenta) { return; }
  let es_banco = cuentas_cb.some(c => c.id_cuenta == cuenta && c.tipo != 'CAJA');
  $.ajax({
    url: "../controller/contabilidad/controlador_movimientos.php",
    type: 'POST',
    data: { cuenta: cuenta, desde: $("#txt_desde_mov").val(), hasta: $("#txt_hasta_mov").val() }
  }).done(function(resp) {
    $("#resumen_mov").html(
      "<div class='col'><small>Saldo anterior</small><br><b>" + formatoMoneda(resp.saldo_anterior) + "</b></div>"
      + "<div class='col text-success'><small>Entradas</small><br><b>" + formatoMoneda(resp.entradas) + "</b></div>"
      + "<div class='col text-danger'><small>Salidas</small><br><b>" + formatoMoneda(resp.salidas) + "</b></div>"
      + "<div class='col'><small>Saldo final</small><br><b>" + formatoMoneda(resp.saldo_final) + "</b></div>");
    let filas = "";
    resp.data.forEach(function(m) {
      let anulado = m.estado != 'VALIDO';
      let estilo = anulado ? " style='text-decoration:line-through;color:#999'" : "";
      let chk = (!anulado && es_banco) ? "<input type='checkbox' " + (m.conciliado == 1 ? "checked" : "") + " onchange='Conciliar(" + m.id_movimiento + ", this)'>" : "";
      filas += "<tr" + estilo + "><td>" + m.fecha_txt + "</td><td>" + m.origen + "</td><td>" + escCB(m.descripcion) + "</td><td>" + escCB(m.referencia)
             + "</td><td class='text-right text-success'>" + (m.tipo == 'ENTRADA' ? formatoMoneda(m.monto) : "") + "</td><td class='text-right text-danger'>"
             + (m.tipo == 'SALIDA' ? formatoMoneda(m.monto) : "") + "</td><td class='text-right'>" + formatoMoneda(m.saldo) + "</td><td>" + escCB(m.usu_usuario)
             + "</td><td class='text-center'>" + chk + "</td></tr>";
    });
    $("#tbody_movimientos").html(filas || "<tr><td colspan='9' class='text-center text-muted'>Sin movimientos en el período</td></tr>");
  });
}

function Conciliar(id, chk) {
  $.ajax({
    url: "../controller/contabilidad/controlador_conciliar.php",
    type: 'POST',
    data: { id: id, valor: chk.checked ? 1 : 0 }
  }).done(function(resp) {
    if (!resp.ok) { chk.checked = !chk.checked; }
  });
}

// Depósito: caja -> banco; retiro: banco -> caja
function Sugerir_Transferencia() {
  let tipo = $("#select_tipo_tr").val();
  let primero = function(cond) { let c = cuentas_cb.find(cond); return c ? c.id_cuenta : ''; };
  if (tipo == 'DEPOSITO') {
    $("#select_origen_tr").val(primero(c => c.tipo == 'CAJA'));
    $("#select_destino_tr").val(primero(c => c.tipo != 'CAJA'));
  } else if (tipo == 'RETIRO') {
    $("#select_origen_tr").val(primero(c => c.tipo != 'CAJA'));
    $("#select_destino_tr").val(primero(c => c.tipo == 'CAJA'));
  }
}
$(document).off('change.cb', '#select_tipo_tr').on('change.cb', '#select_tipo_tr', Sugerir_Transferencia);

function Registrar_Transferencia() {
  let monto = $("#txt_monto_tr").val();
  if (!(parseFloat(monto) > 0)) {
    return Swal.fire("Mensaje de Advertencia", "Ingrese un monto mayor a 0", "warning");
  }
  $.ajax({
    url: "../controller/contabilidad/controlador_transferencia_registrar.php",
    type: 'POST',
    data: {
      tipo: $("#select_tipo_tr").val(), origen: $("#select_origen_tr").val(), destino: $("#select_destino_tr").val(),
      monto: monto, fecha: $("#txt_fecha_tr").val(), referencia: $("#txt_ref_tr").val(), observacion: $("#txt_obs_tr").val()
    }
  }).done(function(resp) {
    if (resp.codigo == 1) {
      Swal.fire("Mensaje de Confirmación", "Registrado correctamente", "success");
      $("#txt_monto_tr, #txt_ref_tr, #txt_obs_tr").val("");
      Cargar_Saldos(function() { Listar_Transferencias(); Listar_Movimientos(); });
    } else {
      Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
    }
  });
}

function Listar_Transferencias() {
  $.ajax({
    url: "../controller/contabilidad/controlador_transferencias_listar.php",
    type: 'POST',
    data: { desde: $("#txt_desde_tr").val(), hasta: $("#txt_hasta_tr").val() }
  }).done(function(resp) {
    let filas = "";
    resp.data.forEach(function(t) {
      let valido = t.estado == 'VALIDO';
      filas += "<tr><td>" + t.fecha_txt + "</td><td>" + t.tipo + "</td><td>" + escCB(t.origen) + "</td><td>" + escCB(t.destino)
             + "</td><td class='text-right'>" + formatoMoneda(t.monto) + "</td><td>" + escCB(t.referencia) + "</td><td>" + escCB(t.observacion)
             + "</td><td>" + escCB(t.usu_usuario) + "</td><td>" + (valido ? "<span class='badge bg-success'>VALIDO</span>" : "<span class='badge bg-danger' title='" + escCB(t.motivo_anulacion) + "'>ANULADO</span>")
             + "</td><td>" + (valido ? "<button class='btn btn-danger btn-sm' onclick='Anular_Transferencia(" + t.id_transferencia + ")'><i class='fas fa-ban'></i></button>" : "") + "</td></tr>";
    });
    $("#tbody_transferencias").html(filas || "<tr><td colspan='10' class='text-center text-muted'>Sin registros en el período</td></tr>");
  });
}

function Anular_Transferencia(id) {
  Swal.fire({
    title: '¿Anular este movimiento entre cuentas?',
    text: 'Se anulan la salida y la entrada. Indique el motivo:',
    icon: 'warning', input: 'text',
    inputValidator: (v) => { if (!v || !v.trim()) { return 'El motivo es obligatorio'; } },
    showCancelButton: true, confirmButtonText: 'Sí, anular'
  }).then((r) => {
    if (!r.isConfirmed) { return; }
    $.ajax({
      url: "../controller/contabilidad/controlador_transferencia_anular.php",
      type: 'POST',
      data: { id: id, motivo: r.value.trim() }
    }).done(function(resp) {
      if (resp.codigo == 1) {
        Cargar_Saldos(function() { Listar_Transferencias(); Listar_Movimientos(); });
      } else {
        Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
      }
    });
  });
}

var esperado_cierre = 0;
function Resumen_Caja() {
  let caja = $("#select_caja_cierre").val();
  if (!caja) { return; }
  $.ajax({
    url: "../controller/contabilidad/controlador_caja_resumen.php",
    type: 'POST',
    data: { cuenta: caja, fecha: $("#txt_fecha_cierre").val() }
  }).done(function(resp) {
    let r = resp.data;
    esperado_cierre = parseFloat(r.saldo_esperado);
    $("#ci_inicial").text(formatoMoneda(r.saldo_inicial));
    $("#ci_entradas").text(formatoMoneda(r.entradas));
    $("#ci_salidas").text(formatoMoneda(r.salidas));
    $("#ci_esperado").text(formatoMoneda(r.saldo_esperado));
    $("#txt_contado").val('');
    $("#ci_diferencia").html('');
    $("#panel_cierre").show();
    $("#btn_cerrar_caja").toggle(r.cerrado == 0);
    $("#ci_cerrada").toggle(r.cerrado > 0);
    let filas = "";
    resp.cierres.forEach(function(c) {
      let dif = parseFloat(c.diferencia);
      filas += "<tr><td>" + c.fecha_txt + "</td><td class='text-right'>" + formatoMoneda(c.saldo_inicial) + "</td><td class='text-right'>" + formatoMoneda(c.entradas)
             + "</td><td class='text-right'>" + formatoMoneda(c.salidas) + "</td><td class='text-right'>" + formatoMoneda(c.saldo_esperado)
             + "</td><td class='text-right'>" + formatoMoneda(c.saldo_contado) + "</td><td class='text-right " + (dif == 0 ? "" : (dif > 0 ? "text-success" : "text-danger")) + "'>"
             + formatoMoneda(dif) + (c.ajustado == 1 ? " <small>(ajustado)</small>" : "") + "</td><td>" + escCB(c.observacion) + "</td><td>" + escCB(c.usu_usuario) + "</td></tr>";
    });
    $("#tbody_cierres").html(filas || "<tr><td colspan='9' class='text-center text-muted'>Sin cierres registrados</td></tr>");
  });
}

$(document).off('input.cb', '#txt_contado').on('input.cb', '#txt_contado', function() {
  let contado = parseFloat(this.value);
  if (isNaN(contado)) { $("#ci_diferencia").html(''); return; }
  let dif = Math.round((contado - esperado_cierre) * 100) / 100;
  $("#ci_diferencia").html(dif == 0 ? "<b class='text-success'>Cuadra con el sistema</b>"
    : "<b class='" + (dif > 0 ? "text-success'>Sobrante: " : "text-danger'>Faltante: ") + formatoMoneda(Math.abs(dif)) + "</b>");
});
$(document).off('change.cb', '#select_caja_cierre, #txt_fecha_cierre').on('change.cb', '#select_caja_cierre, #txt_fecha_cierre', Resumen_Caja);

function Cerrar_Caja() {
  if ($("#txt_contado").val() === '') {
    return Swal.fire("Mensaje de Advertencia", "Ingrese el efectivo contado", "warning");
  }
  $.ajax({
    url: "../controller/contabilidad/controlador_caja_cerrar.php",
    type: 'POST',
    data: {
      cuenta: $("#select_caja_cierre").val(), fecha: $("#txt_fecha_cierre").val(), contado: $("#txt_contado").val(),
      observacion: $("#txt_obs_cierre").val(), ajustar: $("#chk_ajustar").is(':checked') ? 1 : 0
    }
  }).done(function(resp) {
    if (resp.codigo == 1) {
      Swal.fire("Mensaje de Confirmación", "Caja cerrada", "success");
      $("#txt_obs_cierre").val('');
      $("#chk_ajustar").prop('checked', false);
      Cargar_Saldos(function() { Resumen_Caja(); Listar_Movimientos(); });
    } else {
      Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
    }
  });
}
