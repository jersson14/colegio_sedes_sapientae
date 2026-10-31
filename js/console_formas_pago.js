// Configuración > Formas de pago y cuentas: métodos de pago y cajas/cuentas del colegio
var datos_formas = {metodos: [], cuentas: []};
var esc = function(v) { return $('<div>').text(v === null || v === undefined ? '' : v).html(); };
var badge_estado = function(e) { return "<span class='badge " + (e == 'ACTIVO' ? "bg-success" : "bg-secondary") + "'>" + e + "</span>"; };

function Listar_Formas_Pago(){
  $.ajax({
    url: "../controller/configuracion/controlador_formas_pago_listar.php",
    type: 'POST'
  }).done(function(resp){
    datos_formas = resp;
    let filas = "";
    resp.cuentas.forEach(function(c){
      filas += "<tr><td>" + c.tipo + "</td><td><b>" + esc(c.nombre) + "</b></td><td>" + esc(c.banco) + "</td><td>" + esc(c.numero_cuenta)
             + "</td><td>" + esc(c.codigo_interbancario) + "</td><td>" + formatoMoneda(c.saldo_inicial) + "</td><td>" + formatoMoneda(c.cobrado)
             + "</td><td>" + badge_estado(c.estado) + "</td><td><button class='btn btn-primary btn-sm' onclick='Abrir_Cuenta(" + c.id_cuenta + ")'><i class='fas fa-edit'></i> Editar</button></td></tr>";
    });
    $("#tbody_cuentas").html(filas);
    filas = "";
    resp.metodos.forEach(function(m){
      let historico = m.nombre == 'NO REGISTRADO (HISTÓRICO)';
      filas += "<tr><td><b>" + esc(m.nombre) + "</b>" + (historico ? " <small class='text-muted'>(pagos anteriores)</small>" : "") + "</td><td>" + m.tipo
             + "</td><td>" + (m.requiere_referencia == 1 ? "Sí" : "No") + "</td><td>" + (m.requiere_cuenta == 1 ? "Sí" : "No") + "</td><td>" + m.comision_pct
             + "</td><td>" + m.usos + "</td><td>" + badge_estado(m.estado) + "</td><td>"
             + (historico ? "" : "<button class='btn btn-primary btn-sm' onclick='Abrir_Metodo(" + m.id_metodo + ")'><i class='fas fa-edit'></i> Editar</button>") + "</td></tr>";
    });
    $("#tbody_metodos").html(filas);
  });
}

function Abrir_Cuenta(id){
  let c = datos_formas.cuentas.find(x => x.id_cuenta == id) || {tipo: 'BANCO', saldo_inicial: 0, estado: 'ACTIVO'};
  $("#lb_titulo_cuenta").html("<b>" + (id ? "EDITAR CAJA / CUENTA" : "NUEVA CAJA / CUENTA") + "</b>");
  $("#txt_id_cuenta").val(id || '');
  $("#select_tipo_cuenta").val(c.tipo);
  $("#txt_nombre_cuenta").val(c.nombre || '');
  $("#txt_banco_cuenta").val(c.banco || '');
  $("#txt_numero_cuenta").val(c.numero_cuenta || '');
  $("#txt_cci_cuenta").val(c.codigo_interbancario || '');
  $("#txt_titular_cuenta").val(c.titular || '');
  $("#txt_saldo_cuenta").val(c.saldo_inicial);
  $("#txt_fecha_saldo_cuenta").val(c.fecha_saldo_inicial || '');
  $("#select_estado_cuenta").val(c.estado);
  $("#modal_cuenta").modal('show');
}

function Guardar_Cuenta(){
  if ($("#txt_nombre_cuenta").val().trim().length == 0) {
    return Swal.fire("Mensaje de Advertencia", "Ingrese el nombre de la caja o cuenta", "warning");
  }
  $.ajax({
    url: "../controller/configuracion/controlador_cuenta_guardar.php",
    type: 'POST',
    data: {
      id: $("#txt_id_cuenta").val(), tipo: $("#select_tipo_cuenta").val(), nombre: $("#txt_nombre_cuenta").val(),
      banco: $("#txt_banco_cuenta").val(), numero: $("#txt_numero_cuenta").val(), cci: $("#txt_cci_cuenta").val(),
      titular: $("#txt_titular_cuenta").val(), saldo: $("#txt_saldo_cuenta").val(), fecha_saldo: $("#txt_fecha_saldo_cuenta").val(),
      estado: $("#select_estado_cuenta").val()
    }
  }).done(function(resp){
    if (resp.ok) {
      $("#modal_cuenta").modal('hide');
      Swal.fire("Mensaje de Confirmación", resp.mensaje, "success");
      Listar_Formas_Pago();
    } else {
      Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
    }
  });
}

function Abrir_Metodo(id){
  let m = datos_formas.metodos.find(x => x.id_metodo == id) || {tipo: 'BILLETERA', requiere_referencia: 1, requiere_cuenta: 1, comision_pct: 0, estado: 'ACTIVO'};
  $("#lb_titulo_metodo").html("<b>" + (id ? "EDITAR MÉTODO DE PAGO" : "NUEVO MÉTODO DE PAGO") + "</b>");
  $("#txt_id_metodo").val(id || '');
  $("#txt_nombre_metodo").val(m.nombre || '');
  $("#select_tipo_metodo").val(m.tipo);
  $("#txt_comision_metodo").val(m.comision_pct);
  $("#chk_ref_metodo").prop('checked', m.requiere_referencia == 1);
  $("#chk_cta_metodo").prop('checked', m.requiere_cuenta == 1);
  $("#select_estado_metodo").val(m.estado);
  $("#modal_metodo").modal('show');
}

function Guardar_Metodo(){
  if ($("#txt_nombre_metodo").val().trim().length == 0) {
    return Swal.fire("Mensaje de Advertencia", "Ingrese el nombre del método de pago", "warning");
  }
  $.ajax({
    url: "../controller/configuracion/controlador_metodo_guardar.php",
    type: 'POST',
    data: {
      id: $("#txt_id_metodo").val(), nombre: $("#txt_nombre_metodo").val(), tipo: $("#select_tipo_metodo").val(),
      comision: $("#txt_comision_metodo").val(), requiere_referencia: $("#chk_ref_metodo").is(':checked') ? 1 : 0,
      requiere_cuenta: $("#chk_cta_metodo").is(':checked') ? 1 : 0, estado: $("#select_estado_metodo").val()
    }
  }).done(function(resp){
    if (resp.ok) {
      $("#modal_metodo").modal('hide');
      Swal.fire("Mensaje de Confirmación", resp.mensaje, "success");
      Listar_Formas_Pago();
    } else {
      Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
    }
  });
}
