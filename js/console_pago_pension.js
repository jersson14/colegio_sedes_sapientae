//LISTADO DE ROLES
var tbl_pago_pension;
function listar_pago_pension(){
    tbl_pago_pension = $("#tabla_pago_pension").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      "destroy":true,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "async": false ,
      "processing": true,
      "ajax":{
          "url":"../controller/pago_pension/controlador_listar_matriculas.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE AULAS"
      },
        title: function() {
          return  "LISTA DE AULAS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE AULAS"
      },
    title: function() {
      return  "LISTA DE AULAS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fas fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE AULAS"
  
    }
    }],
      "columns":[
        {"data":"id_matricula"},
        {"data":"alum_dni"},
        {"data":"Estudiante"},
        {"data":"Grado"},
        {"data":"Nivel_academico",
            render: function(data,type,row){
                if(data=='INICIAL'){
                return '<span class="badge bg-warning">INICIAL</span>';
                }else if(data=='PRIMARIA'){
                return '<span class="badge bg-success">PRIMARIA</span>';
                }else{
                return '<span class="badge bg-primary">SECUNDARIA</span>';
                }
        }
        },
        {"data":"año_escolar"},
        {"data":"FECHA_pago"},


        {"defaultContent":"<button class='pagar btn btn-success  btn-sm' title='Mostras datos'><i class='fas fa-coins'></i> Pagar pensión</button>&nbsp;&nbsp;<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fas fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='kardex btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fas fa-print'></i> Kardex de pago</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_pago_pension.on('draw.td',function(){
  var PageInfo = $("#tabla_pago_pension").DataTable().page.info();
  tbl_pago_pension.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function listar_pago_pension_filtro(){
  let año = document.getElementById('select_año_buscar').value;
  let grado = document.getElementById('select_aula_buscar').value;
  tbl_pago_pension = $("#tabla_pago_pension").DataTable({
    "ordering":false,   
    "bLengthChange":true,
    "searching": { "regex": false },
    "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
    "pageLength": 10,
    "destroy":true,
    pagingType: 'full_numbers',
    scrollCollapse: true,
    responsive: true,
    "async": false ,
    "processing": true,
    "ajax":{
        "url":"../controller/pago_pension/controlador_listar_matriculas_filtro.php",
        type:'POST',
        data:{
          año:año,
          grado:grado
        }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE AULAS"
    },
      title: function() {
        return  "LISTA DE AULAS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE AULAS"
    },
  title: function() {
    return  "LISTA DE AULAS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fas fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE AULAS"

  }
  }],
    "columns":[
      {"data":"id_matricula"},
      {"data":"alum_dni"},
      {"data":"Estudiante"},
      {"data":"Grado"},
      {"data":"Nivel_academico",
          render: function(data,type,row){
              if(data=='INICIAL'){
              return '<span class="badge bg-warning">INICIAL</span>';
              }else if(data=='PRIMARIA'){
              return '<span class="badge bg-success">PRIMARIA</span>';
              }else{
              return '<span class="badge bg-primary">SECUNDARIA</span>';
              }
      }
      },
      {"data":"año_escolar"},
      {"data":"FECHA_pago"},


      {"defaultContent":"<button class='pagar btn btn-success  btn-sm' title='Mostras datos'><i class='fas fa-coins'></i> Pagar pensión</button>&nbsp;&nbsp;<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fas fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='kardex btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fas fa-print'></i> Kardex de pago</button>"},
      
  ],

  "language":idioma_espanol,
  select: true
});
tbl_pago_pension.on('draw.td',function(){
var PageInfo = $("#tabla_pago_pension").DataTable().page.info();
tbl_pago_pension.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}


function Cargar_Select_Nivelaca(id_nivel_seleccionado) {
  $.ajax({
    url: "../controller/aulas/controlador_cargar_select_nivel.php",
    type: 'POST'
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "<option value=''>Seleccionar Nivel Académico</option>"; // Siempre inicializa la cadena
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + textoSeguro(data[i][0]) + "'>" + textoSeguro(data[i][1]) + "</option>";
      }
    } else {
      cadena += "<option value=''>No se encontraron registros</option>";
    }
    $('#select_nivel').html(cadena);

    // Si hay un nivel seleccionado previamente, actualizar el select y cargar las pensiones
    if (id_nivel_seleccionado) {
      $("#select_nivel").val(id_nivel_seleccionado).trigger('change');
      Cargar_Select_pensiones(id_nivel_seleccionado); // Cargar pensiones automáticamente
    }
  });
}

function Cargar_Select_pensiones(id) {
  // Pensiones del nivel de la matrícula, con su año y si ya están pagadas
  $.ajax({
    url: "../controller/pago_pension/controlador_cargar_select_pension.php",
    type: 'POST',
    data: {
      id_matri: $("#txt_id_matricula").val()
    }
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "<option value=''>Seleccionar Pensión</option>";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + textoSeguro(data[i][0]) + "'>" + textoSeguro(data[i][1]) + "</option>";
      }
    } else {
      cadena += "<option value=''>No se encontraron registros</option>";
    }
    $('#select_pension').html(cadena);
    Traerpension($("#select_pension").val());
  });
}

function Limpiar_saldo() {
  $("#txt_monto_pagar").val('').data('saldo', 0).data('mora', 0);
  $("#txt_fecha_ven").val('');
  $("#txt_estado").val('').css({"color": "", "font-weight": ""});
  $("#txt_mora").val('');
  $("#chk_exonerar_mora").prop('checked', false);
  $("#div_exonerar_mora").hide();
  $("#div_motivo_mora").hide();
  $("#txt_motivo_mora").val('');
}

// Trae del servidor el saldo de la pensión: precio, mora aplicable y lo ya pagado
function Traerpension(id) {
  Limpiar_saldo();
  if (!id || $("#select_concepto").val() != "PENSION") {
    return;
  }
  $.ajax({
    url: "../controller/pago_pension/controlador_saldo_pension.php",
    type: 'POST',
    data: {
      id_matri: $("#txt_id_matricula").val(),
      id_pension: id
    }
  }).done(function(resp) {
    if (!resp.ok) {
      return;
    }
    let s = resp.data;
    let colores = {PAGADO: "blue", VENCIDO: "red", PARCIAL: "orange", PENDIENTE: "green"};
    $("#txt_fecha_ven").val(s.fecha_vencimiento).css({"color": colores[s.estado], "font-weight": "bold"});
    $("#txt_estado").val(s.estado + (parseFloat(s.pagado_pension) > 0 ? " (pagado " + s.pagado_pension + " de " + s.precio + ")" : ""))
                    .css({"color": colores[s.estado], "font-weight": "bold"});
    $("#txt_monto_pagar").val(s.saldo_total).data('saldo', parseFloat(s.saldo_total)).data('mora', parseFloat(s.saldo_mora));
    if (parseFloat(s.saldo_mora) > 0) {
      $("#txt_mora").val(s.saldo_mora + " (incluida en el monto)");
      $("#div_exonerar_mora").show();
    } else {
      $("#txt_mora").val(s.mora_exonerada == 1 ? "EXONERADA" : "0.00");
    }
  });
}

$(document).off('change.pago', '#chk_exonerar_mora').on('change.pago', '#chk_exonerar_mora', function() {
  let saldo = $("#txt_monto_pagar").data('saldo') || 0;
  let mora = $("#txt_monto_pagar").data('mora') || 0;
  if (this.checked) {
    $("#div_motivo_mora").show();
    $("#txt_monto_pagar").val((saldo - mora).toFixed(2));
  } else {
    $("#div_motivo_mora").hide();
    $("#txt_monto_pagar").val(saldo.toFixed(2));
  }
});

$(document).off('change.pago', '#select_concepto').on('change.pago', '#select_concepto', function() {
  let es_pension = $(this).val() == "PENSION";
  $("#select_pension").prop('disabled', !es_pension);
  if (es_pension) {
    Traerpension($("#select_pension").val());
  } else {
    Limpiar_saldo();
  }
});

// Evento para el botón "Pagar" en la tabla
$('#tabla_pago_pension').on('click', '.pagar', function() {
  var data = tbl_pago_pension.row($(this).parents('tr')).data();

  if (tbl_pago_pension.row(this).child.isShown()) {
    data = tbl_pago_pension.row(this).data();
  }

  $("#modal_registro").modal('show');
  document.getElementById('txt_id_matricula').value = data.id_matricula;
  Reiniciar_cobro();

  // Cargar el select de nivel académico y seleccionar el nivel correspondiente
  Cargar_Select_Nivelaca(data.Id_nivel); // Paso el nivel seleccionado directamente
});

  function Cargar_Año(){
    $.ajax({
      "url":"../controller/matricula/controlador_cargar_select_año.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+"</option>";    
        }
          document.getElementById('select_año_buscar').innerHTML=cadena;

      }else{
        let cadena = "<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año_buscar').innerHTML=cadena;

      }
    })
  }
  function Cargar_Select_Nivelaca2(){
    $.ajax({
      "url":"../controller/aulas/controlador_cargar_select_nivel.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+"</option>";    
        }
        $('#select_nivel_buscar').html(cadena);
  
        var id =$("#select_nivel_buscar").val();
        Cargar_Select_Aula(id);
  
      }else{
        let cadena = "<option value=''>No se encontraron regitros</option>";
        $('#select_nivel_buscar').html(cadena);
  
      }
    })
  }
  
  //TRAENDO DATOS DE LA AULAS
  function Cargar_Select_Aula(id){
    $.ajax({
        "url":"../controller/asistencias/controlador_cargar_select_aula_id.php",
        type:'POST',
        data: {
            id: id  // Ensure this matches the parameter name expected by the PHP script
        },
        dataType: 'json',  // Expect JSON response
        success: function(data){
            if(data.length > 0){
                let cadena = "";
                for (let i = 0; i < data.length; i++) {
                    cadena += "<option value='" + textoSeguro(data[i][1]) + "'>" + textoSeguro(data[i][2]) + "</option>";    
                }
                $('#select_aula_buscar').html(cadena);
            } else {
                $('#select_aula_buscar').html("<option value=''>No hay secciones en la base de datos</option>");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + " - " + error);
            $('#select_aula_buscar').html("<option value=''>Error al cargar las secciones</option>");
        }
    });
  }
//ENVIANDO DATOS PARA MOSTRAR


function Agregar_pago(){
  var id_matri=$("#txt_id_matricula").val();
  var concepto=$("#select_concepto").val();
  var es_pension = concepto=="PENSION";
  var id_pension2= es_pension ? $("#select_pension option:selected").val() : "";
  var id_pension= es_pension ? $("#select_pension option:selected").text() : concepto;
  var monto=$("#txt_monto_pagar").val().replace(",", ".");
  var exonerar = es_pension && $("#chk_exonerar_mora").is(':checked') ? 1 : 0;
  var motivo = $("#txt_motivo_mora").val().trim();

  if(es_pension && id_pension2==""){
    return Swal.fire("Mensaje de Advertencia","Seleccione la pensión a pagar","warning");
  }
  if(!/^\d+(\.\d{1,2})?$/.test(monto) || (parseFloat(monto)<=0 && !exonerar)){
    return Swal.fire("Mensaje de Advertencia","Ingrese un monto válido mayor a 0","warning");
  }
  if(es_pension && parseFloat(monto) > ($("#txt_monto_pagar").data('saldo') || 0)){
    return Swal.fire("Mensaje de Advertencia","El monto excede el saldo pendiente de la pensión","warning");
  }
  if(exonerar && motivo.length==0){
    return Swal.fire("Mensaje de Advertencia","Indique el motivo para exonerar la mora","warning");
  }
  if(verificarid(id_pension)){
   return Swal.fire("Mensaje de Advertencia","Ese concepto ya fue agregado a la tabla","warning");
  }

  var tr = $("<tr>").attr('data-exonerar', exonerar).attr('data-motivo', motivo);
  tr.append($("<td>").text(id_matri));
  tr.append($("<td>").text(concepto));
  tr.append($("<td>").text(id_pension2));
  tr.append($("<td for='id'>").text(id_pension));
  tr.append($("<td>").text(parseFloat(monto).toFixed(2)));
  tr.append($("<td>").html("<button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button>"
                           + (exonerar ? " <span class='badge badge-warning'>Mora exonerada</span>" : "")));
  $("#tabla_pago").append(tr);
  SumarTotal();
}
function remove(t){
  var td =t.parentNode;
  var tr=td.parentNode;
  var table =tr.parentNode;
  table.removeChild(tr);
  SumarTotal();

}
function SumarTotal(){
  let total=0;
   $("#tabla_pago tbody#tbody_tabla_pago tr").each(function(){
     total += parseFloat($(this).find('td').eq(4).text()) || 0;
   })
   $("#lbl_totalneto").html("<b>Total: </b>"+formatoMoneda(total));
   if ($("#tbody_formas_pago tr").length == 1) {
     $("#tbody_formas_pago .fp_monto").val(total.toFixed(2));
   }
   Actualizar_diferencia_pago();

 }

function verificarid(id){
  let idverificar=document.querySelectorAll('#tabla_pago td[for="id"]');
  return [].filter.call(idverificar, td=>td.textContent ===id).length===1;
}

function Registrar_Pago() {
  let matricula = document.getElementById('txt_id_matricula').value;
  let fecha = document.getElementById('fecha_pago').value;
  let lineas = [];

  $("#tabla_pago tbody#tbody_tabla_pago tr").each(function () {
    lineas.push({
      concepto: $(this).find('td').eq(1).text(),
      id_pension: $(this).find('td').eq(2).text(),
      monto: $(this).find('td').eq(4).text(),
      exonerar: $(this).attr('data-exonerar'),
      motivo: $(this).attr('data-motivo')
    });
  });

  if (lineas.length == 0) {
    return Swal.fire("Mensaje de Advertencia", "La tabla de pagos debe contener al menos un registro", "warning");
  }
  let metodos = [];
  $("#tbody_formas_pago tr").each(function () {
    metodos.push({
      id_metodo: $(this).find('.fp_metodo').val(),
      id_cuenta: $(this).find('.fp_cuenta').val(),
      monto: $(this).find('.fp_monto').val(),
      referencia: $(this).find('.fp_referencia').val()
    });
  });
  // Un pago que solo exonera mora no lleva forma de pago
  if (Total_conceptos() == 0) {
    metodos = [];
  } else if (Diferencia_formas_pago() != 0) {
    return Swal.fire("Mensaje de Advertencia", "Las formas de pago deben sumar el total a pagar (" + formatoMoneda(Total_conceptos()) + ")", "warning");
  }

  let formData = new FormData();
  formData.append('id_matri', matricula);
  formData.append('lineas', JSON.stringify(lineas));
  formData.append('metodos', JSON.stringify(metodos));
  formData.append('observacion', $("#txt_observacion_pago").val());
  let voucher = $("#file_voucher")[0].files[0];
  if (voucher) {
    formData.append('voucher', voucher);
  }

  $.ajax({
    url: "../controller/pago_pension/controlador_detalle_pago_pension.php",
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false
  }).done(function (resp) {
    if(resp.ok){
      $("#tbody_tabla_pago").html("");
      SumarTotal();
      Swal.fire({
        title: 'Pago registrado',
        html: "Recibo N.° <b>" + resp.recibo + "</b> por <b>" + formatoMoneda(resp.total) + "</b>",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Imprimir recibo'
      }).then((result) => {
        if (result.isConfirmed) {
          var url = "../view/MPDF/REPORTE/recibo.php?id=" + encodeURIComponent(resp.id_pago) + "#zoom=100%";

          // Abrir una nueva ventana con la URL construida
          var newWindow = window.open(url, "PAGO", "scrollbars=NO");

          // Asegurarse de que la ventana se abre en tamaño máximo
          if (newWindow) {
              newWindow.moveTo(0, 0);
              newWindow.resizeTo(screen.width, screen.height);
          }
        }
        $("#modal_registro").modal('hide');
        tbl_pago_pension.ajax.reload();
      })
    } else {
      // Si un concepto falla no se registra ninguno de la tabla
      Swal.fire("Mensaje de Advertencia", (resp.linea ? "Fila " + resp.linea + ": " : "") + resp.mensaje + ". No se registró ningún pago.", "warning");
    }
  });
}

//EDITANDO ROL
var tbl_pagos;
function listar_pagos(id) {
    tbl_pagos = $("#tabla_pagos2").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": { "regex": false },
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
        "pageLength": 5,
        "destroy": true,
        pagingType: 'full_numbers',
        scrollCollapse: true,
        responsive: true,
        "async": false,
        "processing": true,
        "ajax": {
            "url": "../controller/pago_pension/controlador_listar_tabla_pagos.php",
            type: 'POST',
            data: {
                id: id
            },
            dataSrc: function(json) {
                console.log(json);  // Imprime la respuesta JSON para depuración

                if (json.total_sub_total !== undefined) {
                    $('#total_sub_total').text(formatoMoneda(json.total_sub_total));
                } else {
                    $('#total_sub_total').text(formatoMoneda(0));  // Valor por defecto si no está definido
                }
                return json.data;
            }
        },
        "columns": [
            { "data": "id_pago_pension" },
            { "data": "recibo", render: function(data){ return data ? "<b>" + textoSeguro(data) + "</b>" : "-"; } },
            { "data": "concepto", render: function(data,type,row){ return row.mora_exonerada == 1 ? textoSeguro(data) + " <span class='badge badge-warning'>exonerada</span>" : textoSeguro(data); } },
            { "data": "mes" },
            { "data": "fecha_formateada2" },
            { "data": "metodos", render: function(data){ return data ? $('<div>').text(data).html() : "-"; } },
            { "data": "sub_total", render: function(data){ return formatoMoneda(data); } },

            {"data":"concepto",
              render: function(data,type,row){
                  let botones = "<button class='imprimir btn btn-success btn-sm' title='Imprimir recibo'><i class='fas fa-print'></i> Recibo</button> ";
                  if(data=='PENSION' || data=='MORA'){
                    if(row.n_metodos <= 1){
                      botones += "<button class='editar_1 btn btn-primary btn-sm' title='Editar pago'><i class='fas fa-edit'></i> Editar</button>";
                    }
                    botones += "<button class='anular btn btn-danger btn-sm' title='Anular el recibo'><i class='fas fa-trash'></i> Anular</button>";
                  }
                  return botones;
          }
          },
         
        ],
        "language": idioma_espanol,
        select: true
    });

    // Cambiar el evento a 'draw.dt' para corregir el error
    tbl_pagos.on('draw.dt', function() {
        var PageInfo = tbl_pagos.page.info();
        tbl_pagos.column(0, { page: 'current' }).nodes().each(function(cell, i) {
            if (cell) {  // Verifica que cell no sea nulo
                cell.innerHTML = i + 1 + PageInfo.start;
            }
        });
    });
}




$('#tabla_pago_pension').on('click','.mostrar',function(){
  var data = tbl_pago_pension.row($(this).parents('tr')).data();

  if(tbl_pago_pension.row(this).child.isShown()){
      var data = tbl_pago_pension.row(this).data();
  }
$("#modal_ver_pagos").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>PAGOS DEL ESTUDIANTE: "+textoSeguro(data.Estudiante)+"</b>";
  listar_pagos(data.id_matri);

})




$('#tabla_pagos2').on('click','.imprimir',function(){
  var data = tbl_pagos.row($(this).parents('tr')).data();

  if(tbl_pagos.row(this).child.isShown()){
      var data = tbl_pagos.row(this).data();
  }
  // Pagos con recibo: recibo completo; los muy antiguos sin recibo: boleta del concepto
  var url = data.id_pago ? "../view/MPDF/REPORTE/recibo.php?id=" + encodeURIComponent(data.id_pago) + "#zoom=100%"
          : "../view/MPDF/REPORTE/boleta_pago.php?codigo=" + encodeURIComponent(data.id_matri) + "&idpagopen=" + encodeURIComponent(data.id_pago_pension)+ "#zoom=100%";

  // Abrir una nueva ventana con la URL construida
  var newWindow = window.open(url, "BOLETA DE PAGO", "scrollbars=NO");
  
  // Asegurarse de que la ventana se abre en tamaño máximo
  if (newWindow) {
      newWindow.moveTo(0, 0);
      newWindow.resizeTo(screen.width, screen.height);
  }

})

$('#tabla_pago_pension').on('click','.kardex',function(){
  var data = tbl_pago_pension.row($(this).parents('tr')).data();

  if(tbl_pago_pension.row(this).child.isShown()){
      var data = tbl_pago_pension.row(this).data();
  }
  var url = "../view/MPDF/REPORTE/kardex.php?codigo=" + encodeURIComponent(data.id_matri)+ "#zoom=100%";

  // Abrir una nueva ventana con la URL construida
  var newWindow = window.open(url, "KARDEX DE PAGO", "scrollbars=NO");
  
  // Asegurarse de que la ventana se abre en tamaño máximo
  if (newWindow) {
      newWindow.moveTo(0, 0);
      newWindow.resizeTo(screen.width, screen.height);
  }

})


function Anular_pago(id, motivo){
  $.ajax({
    "url":"../controller/pago_pension/controlador_eliminar_pago_pension.php",
    type:'POST',
    data:{
      id:id,
      motivo:motivo
    }
  }).done(function(resp){
    if(resp.codigo==1){
        Swal.fire("Mensaje de Confirmación","Se anuló el pago satisfactoriamente","success").then((value)=>{
          tbl_pagos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Advertencia",resp.mensaje,"warning");

    }
  })
}

//ENVIANDO AL BOTON DELETE
$('#tabla_pagos2').on('click','.anular',function(){
  var data = tbl_pagos.row($(this).parents('tr')).data();

  if(tbl_pagos.row(this).child.isShown()){
      var data = tbl_pagos.row(this).data();
  }
  Swal.fire({
    title: data.recibo ? '¿Anular el recibo ' + textoSeguro(data.recibo) + '?' : '¿Anular el pago de ' + textoSeguro(data.concepto) + '?',
    text: (data.recibo ? "Se anularán TODOS los conceptos de ese recibo y sus ingresos" : "El pago y su ingreso quedarán ANULADOS") + " (no se borran). Indique el motivo:",
    icon: 'warning',
    input: 'text',
    inputPlaceholder: 'Motivo de la anulación',
    inputValidator: (valor) => { if (!valor || !valor.trim()) { return 'El motivo es obligatorio'; } },
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Anular'
  }).then((result) => {
    if (result.isConfirmed) {
      Anular_pago(data.id_pago_pension, result.value.trim());
    }
  })
})



$('#tabla_pagos2').on('click','.editar_1',function(){
  var data = tbl_pagos.row($(this).parents('tr')).data();

  if(tbl_pagos.row(this).child.isShown()){
      var data = tbl_pagos.row(this).data();
  }
  $("#modal_editar2").modal('show');
  document.getElementById('lb_titulo4').innerHTML="<b>EDITANDO LA PENSIÓN DE: "+textoSeguro(data.mes)+"</b>";
  document.getElementById('txt_id_pagopension').value=data.id_pago_pension;
  document.getElementById('txt_monto_edita').value=data.sub_total;

  document.getElementById('txt_observación_motivo').value=data.motivo_edicion;

})



function Editar_pago(){
  let id = document.getElementById('txt_id_pagopension').value;
  let monto = document.getElementById('txt_monto_edita').value;
  let descrip = document.getElementById('txt_observación_motivo').value;

  if (parseFloat(monto) < 0) {
    return Swal.fire("Mensaje de Advertencia", "El monto no puede ser 0 ni menor a 0", "warning");
  }
  if(monto.length==0 || descrip.length==0){
      return Swal.fire("Mensaje de Advertencia","El monto y la descripción son obligatorias de llenar.","warning");
  }
  $.ajax({
    "url":"../controller/pago_pension/controlador_modificar_pago_solo.php",
    type:'POST',
    data:{
      id:id,
      monto:monto,
      descrip:descrip
    }
  }).done(function(resp){
    if(resp.codigo==1){
        Swal.fire("Mensaje de Confirmación","Pago actualizado correctamente","success").then((value)=>{
          tbl_pagos.ajax.reload();
            $("#modal_editar2").modal('hide');
        });
      
    }else{
      return Swal.fire("Mensaje de Advertencia",resp.mensaje,"warning");

    }
  })
}
// ---------- Formas de pago del recibo (Fase 4) ----------
var formas_pago = {metodos: [], cuentas: []};

function Cargar_Formas_Pago(callback) {
  $.ajax({
    url: "../controller/pago_pension/controlador_cargar_formas_pago.php",
    type: 'POST'
  }).done(function(resp) {
    formas_pago = resp;
    if (callback) { callback(); }
  });
}

function opciones_cuentas(tipo_preferido) {
  let html = "<option value=''>-- Seleccione --</option>";
  formas_pago.cuentas.forEach(function(c) {
    let detalle = c.banco ? " (" + c.banco + (c.numero_cuenta ? " " + c.numero_cuenta : "") + ")" : "";
    html += "<option value='" + c.id_cuenta + "' data-tipo='" + c.tipo + "'>" + $('<div>').text(c.nombre + detalle).html() + "</option>";
  });
  return html;
}

// Cuenta sugerida según el método: efectivo -> caja, billetera -> billetera, el resto -> banco
function sugerir_cuenta(fila) {
  let metodo = formas_pago.metodos.find(m => m.id_metodo == fila.find('.fp_metodo').val());
  if (!metodo) { return; }
  let tipo = metodo.tipo == 'EFECTIVO' ? 'CAJA' : (metodo.tipo == 'BILLETERA' ? 'BILLETERA' : 'BANCO');
  let opcion = fila.find(".fp_cuenta option[data-tipo='" + tipo + "']").first();
  fila.find('.fp_cuenta').val(opcion.length ? opcion.val() : '');
  fila.find('.fp_referencia').attr('placeholder', metodo.requiere_referencia == 1 ? 'Obligatorio' : 'Opcional');
}

function Agregar_forma_pago(monto) {
  let metodos = "";
  formas_pago.metodos.forEach(function(m) {
    metodos += "<option value='" + m.id_metodo + "'>" + $('<div>').text(m.nombre).html() + "</option>";
  });
  let fila = $("<tr>"
    + "<td><select class='form-control form-control-sm fp_metodo'>" + metodos + "</select></td>"
    + "<td><select class='form-control form-control-sm fp_cuenta'>" + opciones_cuentas() + "</select></td>"
    + "<td><input type='text' inputmode='decimal' class='form-control form-control-sm fp_monto'></td>"
    + "<td><input type='text' class='form-control form-control-sm fp_referencia' maxlength='60'></td>"
    + "<td><button type='button' class='btn btn-danger btn-sm' onclick='Quitar_forma_pago(this)'><i class='fas fa-times'></i></button></td>"
    + "</tr>");
  $("#tbody_formas_pago").append(fila);
  sugerir_cuenta(fila);
  fila.find('.fp_monto').val(monto !== undefined ? monto : Diferencia_formas_pago().toFixed(2));
  Actualizar_diferencia_pago();
}

function Quitar_forma_pago(boton) {
  $(boton).closest('tr').remove();
  Actualizar_diferencia_pago();
}

function Total_conceptos() {
  let total = 0;
  $("#tabla_pago tbody#tbody_tabla_pago tr").each(function() {
    total += parseFloat($(this).find('td').eq(4).text()) || 0;
  });
  return Math.round(total * 100) / 100;
}

function Diferencia_formas_pago() {
  let suma = 0;
  $("#tbody_formas_pago .fp_monto").each(function() { suma += parseFloat(this.value) || 0; });
  return Math.round((Total_conceptos() - suma) * 100) / 100;
}

function Actualizar_diferencia_pago() {
  let dif = Diferencia_formas_pago();
  $("#lbl_diferencia_pago").html(dif == 0 ? "<span class='text-success'><b>Formas de pago completas</b></span>"
    : "<span class='text-danger'><b>Falta asignar: " + formatoMoneda(dif) + "</b></span>");
}

$(document).off('change.fp', '.fp_metodo').on('change.fp', '.fp_metodo', function() { sugerir_cuenta($(this).closest('tr')); });
$(document).off('input.fp', '.fp_monto').on('input.fp', '.fp_monto', Actualizar_diferencia_pago);

// Al abrir el cobro se limpia lo del alumno anterior y se propone una forma de pago (efectivo en caja)
function Reiniciar_cobro() {
  $("#tbody_tabla_pago").html("");
  $("#tbody_formas_pago").html("");
  $("#file_voucher").val("");
  $("#txt_observacion_pago").val("");
  $("#lbl_totalneto").html("");
  Cargar_Formas_Pago(function() { Agregar_forma_pago('0.00'); });
}
