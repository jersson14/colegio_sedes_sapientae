// Selectores de forma de pago y caja/cuenta, compartidos por Ingresos, Egresos y Contabilidad (Fase 5)
var FORMAS_PAGO = {metodos: [], cuentas: []};

function cargarFormasPago(callback) {
  $.ajax({
    url: "../controller/pago_pension/controlador_cargar_formas_pago.php",
    type: 'POST'
  }).done(function(resp) {
    FORMAS_PAGO = resp;
    if (callback) { callback(); }
  });
}

function llenarSelectMetodos(selector) {
  let html = "";
  FORMAS_PAGO.metodos.forEach(function(m) {
    html += "<option value='" + m.id_metodo + "'>" + $('<div>').text(m.nombre).html() + "</option>";
  });
  $(selector).html(html);
}

function llenarSelectCuentas(selector, con_vacio) {
  let html = con_vacio ? "<option value=''>-- Ninguna --</option>" : "";
  FORMAS_PAGO.cuentas.forEach(function(c) {
    html += "<option value='" + c.id_cuenta + "' data-tipo='" + c.tipo + "'>" + $('<div>').text(c.nombre).html() + "</option>";
  });
  $(selector).html(html);
}

// Efectivo -> caja, billetera -> billetera, el resto -> banco; marca si el n.° de operación es obligatorio
function sugerirCuenta(selMetodo, selCuenta, txtReferencia) {
  let m = FORMAS_PAGO.metodos.find(x => x.id_metodo == $(selMetodo).val());
  if (!m) { return; }
  let tipo = m.tipo == 'EFECTIVO' ? 'CAJA' : (m.tipo == 'BILLETERA' ? 'BILLETERA' : 'BANCO');
  let op = $(selCuenta).find("option[data-tipo='" + tipo + "']").first();
  if (op.length) { $(selCuenta).val(op.val()); }
  if (txtReferencia) {
    $(txtReferencia).attr('placeholder', m.requiere_referencia == 1 ? 'Obligatorio' : 'Opcional');
  }
}

// Prepara un formulario: carga los selectores y conecta el cambio de método. valores = {metodo, cuenta} para editar.
function prepararFormaPago(selMetodo, selCuenta, txtReferencia, valores) {
  cargarFormasPago(function() {
    llenarSelectMetodos(selMetodo);
    llenarSelectCuentas(selCuenta, true);
    $(document).off('change.fpc', selMetodo).on('change.fpc', selMetodo, function() { sugerirCuenta(selMetodo, selCuenta, txtReferencia); });
    if (valores && valores.metodo) {
      // Un registro antiguo con método inactivo/histórico se muestra igual
      if ($(selMetodo).find("option[value='" + valores.metodo + "']").length == 0 && valores.metodo_nombre) {
        $(selMetodo).append("<option value='" + valores.metodo + "'>" + $('<div>').text(valores.metodo_nombre).html() + "</option>");
      }
      $(selMetodo).val(valores.metodo);
      $(selCuenta).val(valores.cuenta || '');
    } else {
      sugerirCuenta(selMetodo, selCuenta, txtReferencia);
    }
  });
}
