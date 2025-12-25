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
      text:      '<i class="fa fa-print"></i> ',
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


        {"defaultContent":"<button class='pagar btn btn-success  btn-sm' title='Mostras datos'><i class='fa fa-coins'></i> Pagar pensión</button>&nbsp;&nbsp;<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fa fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='kardex btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fa fa-print'></i> Kardex de pago</button>"},
        
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
    text:      '<i class="fa fa-print"></i> ',
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


      {"defaultContent":"<button class='pagar btn btn-success  btn-sm' title='Mostras datos'><i class='fa fa-coins'></i> Pagar pensión</button>&nbsp;&nbsp;<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fa fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='kardex btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fa fa-print'></i> Kardex de pago</button>"},
      
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
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
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
  $.ajax({
    url: "../controller/pago_pension/controlador_cargar_select_pension.php",
    type: 'POST',
    data: {
      id: id
    }
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "<option value=''>Seleccionar Pensión</option>";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
      }
    } else {
      cadena += "<option value=''>No se encontraron registros</option>";
    }
    $('#select_pension').html(cadena);

    var idSeleccionado = $("#select_pension").val();
    Traerpension(idSeleccionado);
  });
}

function Traerpension(id) {
  $.ajax({
    url: "../controller/pago_pension/controlador_traermonto.php",
    type: 'POST',
    data: {
      id: id
    }
  }).done(function(resp) {
    var data = JSON.parse(resp);
    if (data.length > 0) {
      // Asignar el monto de pago y fecha de vencimiento
      $("#txt_monto_pagar").val(data[0][1]);
      let fechaVencimiento = data[0][2]; // Asegúrate de que data[0][2] contenga la fecha de vencimiento

      // Asigna la fecha de vencimiento
      $("#txt_fecha_ven").val(fechaVencimiento);

      // Verifica si la fecha de vencimiento es anterior a la fecha actual
      let fechaActual = new Date().toISOString().split('T')[0]; // Formato YYYY-MM-DD
      if (fechaVencimiento < fechaActual) {
        // Si está vencido, poner la fecha en rojo y el estado en rojo con texto "VENCIDO" en negrita
        $("#txt_fecha_ven").css({
          "color": "red",
          "font-weight": "bold"  // Aplicar negrita
        });
        $("#txt_estado").val("VENCIDO").css({
          "color": "red",
          "font-weight": "bold"  // Aplicar negrita
        });
      } else {
        // Si no está vencido, poner el estado en verde con texto "PENDIENTE" en negrita
        $("#txt_fecha_ven").css({
          "color": "green",
          "font-weight": "bold"  // Aplicar negrita
        });  // Mantener el color verde si no está vencido
        $("#txt_estado").val("PENDIENTE").css({
          "color": "green",
          "font-weight": "bold"  // Aplicar negrita
        });
      }
    } else {
      // Si no hay datos, limpiar los campos
      $("#txt_monto_pagar").val('');
      $("#txt_fecha_ven").val('');
      $("#txt_estado").val('');
    }
  });
}

// Evento para el botón "Pagar" en la tabla
$('#tabla_pago_pension').on('click', '.pagar', function() {
  var data = tbl_pago_pension.row($(this).parents('tr')).data();

  if (tbl_pago_pension.row(this).child.isShown()) {
    data = tbl_pago_pension.row(this).data();
  }

  $("#modal_registro").modal('show');
  document.getElementById('txt_id_matricula').value = data.id_matricula;

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
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
          document.getElementById('select_año_buscar').innerHTML=cadena;

      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
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
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
        $('#select_nivel_buscar').html(cadena);
  
        var id =$("#select_nivel_buscar").val();
        Cargar_Select_Aula(id);
  
      }else{
        cadena+="<option value=''>No se encontraron regitros</option>";
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
                    cadena += "<option value='" + data[i][1] + "'>" + data[i][2] + "</option>";    
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
  var id_pension2=$("#select_pension option:selected").val();
  var id_pension=$("#select_pension option:selected").text();

  var monto=$("#txt_monto_pagar").val();



  if(verificarid(id_pension)){
   return Swal.fire("Mensaje de Advertencia","La asignatura ya fue agregado a la tabla","warning");
  }

  var datos_agregar ="<tr>";
  datos_agregar+="<td >"+id_matri+"</td>";
  datos_agregar+="<td>"+concepto+"</td>";
  datos_agregar+="<td>"+id_pension2+"</td>";

  datos_agregar+="<td for='id'>"+id_pension+"</td>";
  datos_agregar+="<td>"+monto+"</td>";
  datos_agregar+="<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'><i></button></td>";
  datos_agregar+="</tr>";
  $("#tabla_pago").append(datos_agregar);
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
  let arreglo_total=new Array();
   let count=0;
   let total=0;
   $("#tabla_pago tbody#tbody_tabla_pago tr").each(function(){
     arreglo_total.push($(this).find('td').eq(4).text());
     count++;
   })
   for(var i=0;i<count;i++){
     var suma = arreglo_total[i];
     total=(parseFloat(total)+parseFloat(suma)).toFixed(2);
     
   };
   $("#lbl_totalneto").html("<b>Total:</b>S/."+total);

 }

function verificarid(id){
  let idverificar=document.querySelectorAll('#tabla_pago td[for="id"]');
  return [].filter.call(idverificar, td=>td.textContent ===id).length===1;
}
//REGISTRANDO ROLES



function Registrar_Pago() {
  let count = 0;
  let arreglo_id = new Array();
  let arreglo_concepto = new Array();
  let arreglo_pension = new Array();
  let arreglo_subtotal = new Array();

  let matricula = document.getElementById('txt_id_matricula').value;
  let fecha = document.getElementById('fecha_pago').value;


  $("#tabla_pago tbody#tbody_tabla_pago tr").each(function () {
    arreglo_id.push($(this).find('td').eq(0).text());
    arreglo_concepto.push($(this).find('td').eq(1).text());
    arreglo_pension.push($(this).find('td').eq(2).text());
    arreglo_subtotal.push($(this).find('td').eq(4).text());

    count++;
  });

  if (count == 0) {
    return Swal.fire("Mensaje de Advertencia", "La tabla de pagos debe contener al menos un registro", "warning");
  }

  let id_matri = arreglo_id.toString();
  let concepto = arreglo_concepto.toString();
  let id_pension = arreglo_pension.toString();
  let monto = arreglo_subtotal.toString();

  $.ajax({
    url: "../controller/pago_pension/controlador_detalle_pago_pension.php",
    type: 'POST',
    data: {
      id_matri: id_matri,
      concepto: concepto,
      id_pension: id_pension,
      monto: monto
    }
  }).done(function (resp) {
    if(resp==1){
      Swal.fire({
        title: 'Se registro el pago correctamente<b>',
        text: "Datos de Confirmación",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Imprimir Boleta!'
      }).then((result) => {
        if (result.isConfirmed) {
          var url = "../view/MPDF/REPORTE/pago.php?codigo="+ encodeURIComponent(matricula) + "&fecha=" + encodeURIComponent(fecha) + "#zoom=100%";;

          // Abrir una nueva ventana con la URL construida
          var newWindow = window.open(url, "PAGO", "scrollbars=NO");
          
          // Asegurarse de que la ventana se abre en tamaño máximo
          if (newWindow) {
              newWindow.moveTo(0, 0);
              newWindow.resizeTo(screen.width, screen.height);
          }           
           $("#modal_registro").modal('hide');
           tbl_pago_pension.ajax.reload();
           tbl_pago_pension.clear().draw();


        }else{
          $("#modal_registro").modal('hide');
          tbl_pago_pension.ajax.reload();
          tbl_pago_pension.clear().draw();

        }
      })
    } else if (resp == 2) {
      Swal.fire("Mensaje de Advertencia", "El mes que desea pagar ya existe en la base de datos, verifique por favor", "warning");
    } else {
      Swal.fire("Mensaje de Error", "Error al registrar el pago", "error");
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
                    $('#total_sub_total').text(json.total_sub_total.toFixed(2));
                } else {
                    $('#total_sub_total').text('0.00');  // Valor por defecto si no está definido
                }
                return json.data;
            }
        },
        "columns": [
            { "data": "id_pago_pension" },
            { "data": "concepto" },
            { "data": "mes" },
            { "data": "fecha_formateada2" },
            { "data": "sub_total" },

            {"data":"concepto",
              render: function(data,type,row){
                  if(data=='PENSION'){
                  return "<button class='imprimir btn btn-success btn-sm' title='Imprimir boleta'><i class='fa fa-print'></i> Boleta</button> <button class='editar_1 btn btn-primary btn-sm' title='Editar pago'><i class='fa fa-edit'></i> Editar</button><button class='anular btn btn-danger btn-sm' title='Anular pago'><i class='fa fa-trash'></i> Anular</button>";
                  }else{
                  return "<button class='imprimir btn btn-success btn-sm' title='Imprimir boleta'><i class='fa fa-print'></i> Boleta</button>";
                  }
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
  document.getElementById('lb_titulo').innerHTML="<b>PAGOS DEL ESTUDIANTE: "+data.Estudiante+"</b>";
  listar_pagos(data.id_matri);

})




$('#tabla_pagos2').on('click','.imprimir',function(){
  var data = tbl_pagos.row($(this).parents('tr')).data();

  if(tbl_pagos.row(this).child.isShown()){
      var data = tbl_pagos.row(this).data();
  }
  var url = "../view/MPDF/REPORTE/boleta_pago.php?codigo=" + encodeURIComponent(data.id_matri) + "&idpagopen=" + encodeURIComponent(data.id_pago_pension)+ "#zoom=100%";

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


function Anular_pago(id){
  $.ajax({
    "url":"../controller/pago_pension/controlador_eliminar_pago_pension.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se anulo el pago satisfactoriamente","success").then((value)=>{
          tbl_pagos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Error","No se pudo completar el proceso","error");

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
    title: 'Desea anular el pago con el concepto de : '+data.concepto+'?',
    text: "Una vez aceptado el pago sera anulado y eliminado de esta lista!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Anular'
  }).then((result) => {
    if (result.isConfirmed) {
      Anular_pago(data.id_pago_pension);
    }
  })
})



$('#tabla_pagos2').on('click','.editar_1',function(){
  var data = tbl_pagos.row($(this).parents('tr')).data();

  if(tbl_pagos.row(this).child.isShown()){
      var data = tbl_pagos.row(this).data();
  }
  $("#modal_editar2").modal('show');
  document.getElementById('lb_titulo4').innerHTML="<b>EDITANDO LA PENSIÓN DE: "+data.mes+"</b>";
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
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Pago actualizado correctamente","success").then((value)=>{
          tbl_pagos.ajax.reload();
            $("#modal_editar2").modal('hide');
        });
      
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}