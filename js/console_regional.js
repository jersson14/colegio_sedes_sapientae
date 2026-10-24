// Configuración > Datos regionales: país, moneda, zona horaria y documento del colegio
var datos_regional = {paises: [], monedas: []};

function Cargar_Regional(){
  $.ajax({
    url: "../controller/configuracion/controlador_regional_cargar.php",
    type: 'POST'
  }).done(function(resp){
    datos_regional = resp;
    let paises = "", monedas = "", zonas = "";
    resp.paises.forEach(function(p){ paises += "<option value='" + p.id_pais + "'>" + $('<div>').text(p.nombre).html() + "</option>"; });
    resp.monedas.forEach(function(m){ monedas += "<option value='" + m.id_moneda + "'>" + m.codigo + " - " + $('<div>').text(m.nombre).html() + " (" + m.simbolo + ")</option>"; });
    resp.zonas.forEach(function(z){ zonas += "<option value='" + z + "'>" + z.replace('America/', '').replace(/_/g, ' ') + "</option>"; });
    $("#select_pais").html(paises);
    $("#select_moneda").html(monedas);
    $("#select_zona").html(zonas);
    if (resp.config) {
      $("#select_pais").val(resp.config.pais_id);
      $("#select_moneda").val(resp.config.moneda_id);
      $("#select_zona").val(resp.config.zona_horaria);
      $("#txt_id_tributario").val(resp.config.identificacion_tributaria);
      $("#chk_doc_extranjero").prop('checked', resp.config.permite_doc_extranjero == 1);
      Poner_Escala(resp.config);
    }
    let filas = "";
    resp.historial.forEach(function(h){
      filas += "<tr><td>" + h.fecha + "</td><td>" + $('<div>').text(h.campo).html() + "</td><td>" + $('<div>').text(h.valor_anterior || '').html()
             + "</td><td>" + $('<div>').text(h.valor_nuevo || '').html() + "</td><td>" + $('<div>').text(h.usu_usuario || '').html() + "</td></tr>";
    });
    $("#tbody_historial").html(filas || "<tr><td colspan='5' class='text-center text-muted'>Sin cambios registrados</td></tr>");
    Vista_Previa_Regional();
  });
}

// Al elegir otro país se proponen su moneda y su zona horaria (se pueden cambiar)
$(document).off('change.regional', '#select_pais').on('change.regional', '#select_pais', function(){
  let p = datos_regional.paises.find(x => x.id_pais == $(this).val());
  if (p) {
    $("#select_moneda").val(p.moneda_id);
    $("#select_zona").val(p.zona_horaria);
    Poner_Escala(p);
  }
  Vista_Previa_Regional();
});
$(document).off('input.regional', '#txt_nota_min, #txt_nota_max, #txt_nota_aprobatoria, #txt_notas_literales, #txt_literales_aprobatorios')
  .on('input.regional', '#txt_nota_min, #txt_nota_max, #txt_nota_aprobatoria, #txt_notas_literales, #txt_literales_aprobatorios', Vista_Previa_Regional);
$(document).off('change.regional', '#select_nota_decimales').on('change.regional', '#select_nota_decimales', Vista_Previa_Regional);

// Escala de notas (Fase 8C): la del colegio o la que trae el país elegido
function Poner_Escala(e) {
  let n = function(v) { return String(Number(v)); };
  $("#txt_nota_min").val(n(e.nota_min));
  $("#txt_nota_max").val(n(e.nota_max));
  $("#txt_nota_aprobatoria").val(n(e.nota_aprobatoria));
  $("#select_nota_decimales").val(e.nota_decimales);
  $("#txt_notas_literales").val(e.notas_literales || '');
  $("#txt_literales_aprobatorios").val(e.literales_aprobatorios || '');
}
$(document).off('change.regional', '#select_moneda').on('change.regional', '#select_moneda', Vista_Previa_Regional);

function Vista_Previa_Regional(){
  let p = datos_regional.paises.find(x => x.id_pais == $("#select_pais").val());
  let m = datos_regional.monedas.find(x => x.id_moneda == $("#select_moneda").val());
  if (!p || !m) {
    return;
  }
  let partes = (1500.5).toFixed(m.decimales).split('.');
  let texto = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, m.sep_miles) + (partes[1] ? m.sep_decimal + partes[1] : '');
  $("#prev_monto").text(m.simbolo_antes == 1 ? m.simbolo + ' ' + texto : texto + ' ' + m.simbolo);
  $("#prev_documento").text(p.doc_nombre + ' (ej. ' + p.doc_ejemplo + ')');
  $("#prev_prefijo").text(p.prefijo_tel);
  $("#lbl_id_tributario").text(p.id_tributario + ' del colegio:');
  let lit = $("#txt_notas_literales").val().trim();
  $("#prev_escala").text($("#txt_nota_min").val() + ' a ' + $("#txt_nota_max").val() + ' con ' + $("#select_nota_decimales").val()
    + ' decimal(es), aprueba con ' + $("#txt_nota_aprobatoria").val() + (lit ? ' · o letras ' + lit + ' (aprueban ' + $("#txt_literales_aprobatorios").val() + ')' : ''));
}

function Guardar_Regional(confirmar){
  $.ajax({
    url: "../controller/configuracion/controlador_regional_guardar.php",
    type: 'POST',
    data: {
      pais: $("#select_pais").val(),
      moneda: $("#select_moneda").val(),
      zona: $("#select_zona").val(),
      id_tributario: $("#txt_id_tributario").val(),
      extranjero: $("#chk_doc_extranjero").is(':checked') ? 1 : 0,
      nota_min: $("#txt_nota_min").val(),
      nota_max: $("#txt_nota_max").val(),
      nota_aprobatoria: $("#txt_nota_aprobatoria").val(),
      nota_decimales: $("#select_nota_decimales").val(),
      notas_literales: $("#txt_notas_literales").val(),
      literales_aprobatorios: $("#txt_literales_aprobatorios").val(),
      confirmar_moneda: confirmar ? 1 : 0
    }
  }).done(function(resp){
    if (resp.ok) {
      // Se recarga el sistema para aplicar la nueva moneda/documento en todas las pantallas
      Swal.fire("Mensaje de Confirmación", "Configuración guardada. El sistema se recargará para aplicar los cambios.", "success").then(() => {
        location.reload();
      });
    } else if (resp.confirmar) {
      Swal.fire({
        title: 'Cambiar la moneda',
        text: resp.mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cambiar'
      }).then((r) => {
        if (r.isConfirmed) {
          Guardar_Regional(true);
        }
      });
    } else {
      Swal.fire("Mensaje de Advertencia", resp.mensaje, "warning");
    }
  });
}
