// Auditoría (Fase 8D): quién cambió qué y cuándo en las tablas financieras y de configuración
var etiquetaAccionAud = { INSERT: ['Alta', 'bg-success'], UPDATE: ['Cambio', 'bg-primary'], DELETE: ['Baja', 'bg-danger'] };

function Iniciar_Auditoria() {
  let hoy = new Date().toLocaleDateString('en-CA');
  $("#txt_hasta_aud").val(hoy);
  $("#txt_desde_aud").val(hoy.substring(0, 8) + '01');
  Listar_Auditoria();
}

function Valor_Auditoria(v) {
  if (v === null || v === undefined || v === '') { return "<span class='text-muted'>—</span>"; }
  let t = textoPlano(v);
  return textoSeguro(t.length > 80 ? t.substring(0, 80) + '…' : t);
}

function Listar_Auditoria() {
  $.ajax({ url: "../controller/configuracion/controlador_auditoria_listar.php", type: 'POST',
           data: { tabla: $("#select_tabla_aud").val(), desde: $("#txt_desde_aud").val(), hasta: $("#txt_hasta_aud").val(),
                   registro: $("#txt_registro_aud").val() } }).done(function(r) {
    if ($("#select_tabla_aud option").length <= 1) {
      let op = "<option value=''>Todo</option>";
      Object.keys(r.tablas).forEach(function(k) { op += "<option value='" + textoSeguro(k) + "'>" + textoSeguro(r.tablas[k]) + "</option>"; });
      $("#select_tabla_aud").html(op);
    }
    if ($.fn.DataTable.isDataTable('#tabla_auditoria')) { $('#tabla_auditoria').DataTable().destroy(); }
    let filas = "";
    r.data.forEach(function(a) {
      let cambios = a.cambios.map(function(c) {
        if (a.accion === 'UPDATE') { return "<b>" + textoSeguro(c.campo) + "</b>: " + Valor_Auditoria(c.antes) + " → " + Valor_Auditoria(c.despues); }
        return "<b>" + textoSeguro(c.campo) + "</b>: " + Valor_Auditoria(a.accion === 'INSERT' ? c.despues : c.antes);
      });
      let resumen = cambios.slice(0, 6).join('<br>') + (cambios.length > 6 ? "<br><small class='text-muted'>y " + (cambios.length - 6) + " campo(s) más</small>" : "");
      let acc = etiquetaAccionAud[a.accion];
      filas += "<tr><td data-order='" + a.id_auditoria + "' style='white-space:nowrap'>" + textoSeguro(a.fecha_txt) + "</td><td>" + textoSeguro(a.usuario)
             + "</td><td>" + textoSeguro(a.tabla_nombre) + "</td><td>" + textoSeguro(a.registro_id) + "</td><td><span class='badge " + acc[1] + "'>" + acc[0]
             + "</span></td><td style='font-size:12px'>" + resumen + "</td><td><small>" + textoSeguro(a.ip || '') + "</small></td></tr>";
    });
    $('#tabla_auditoria tbody').html(filas);
    $('#tabla_auditoria').DataTable({ order: [[0, 'desc']], pageLength: 25, language: idioma_espanol, autoWidth: false,
      dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>rt<'row'<'col-sm-5'i><'col-sm-7'p>>",
      buttons: [{ extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm', title: 'Auditoría' }] });
  });
}
