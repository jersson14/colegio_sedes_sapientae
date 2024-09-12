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


        {"defaultContent":"<button class='pagar btn btn-success  btn-sm' title='Mostras datos'><i class='fa fa-coins'></i> Pagar pensión</button>&nbsp;&nbsp;<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fa fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='imprimir btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fa fa-print'></i> Kardex de pago</button>"},
        
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
function Cargar_Select_Nivelaca(){
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
      $('#select_nivel').html(cadena);

      var id =$("#select_nivel").val();
      Cargar_Select_pensiones(id);
    }else{
      cadena+="<option value=''>No se encontraron regitros</option>";
      $('#select_nivel').html(cadena);

    }
  })
}
//TRAENDO DATOS DEL LAS PENSIONES

  function Cargar_Select_pensiones(id){
    $.ajax({
      "url":"../controller/pago_pension/controlador_cargar_select_pension.php",
      type:'POST',
          data:{
            id:id
          }
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="<option value=''>Seleccionar Pensión</option>";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
      $('#select_pension').html(cadena);

      var id =$("#select_pension").val();
      Traerpension(id);
    }else{
      cadena+="<option value=''>No se encontraron regitros</option>";
      $('#select_pension').html(cadena);

    }
    })
  }
  function Traerpension(id){
    $.ajax({
      "url":"../controller/pago_pension/controlador_traermonto.php",
      type:'POST',
          data:{
            id:id
          }
        }).done(function(resp){
          
        var data = JSON.parse(resp);
        var cadena="";
        if(data.length>0){
          $("#txt_monto_pagar").val(data[0][1]);

        }
        else{
          cadena+="<option value=''>No se encontraron regitros</option>";
          $('#txt_tipo').html(cadena);


      }
    })
  }


//ENVIANDO DATOS PARA MOSTRAR
$('#tabla_pago_pension').on('click','.pagar',function(){
  var data = tbl_pago_pension.row($(this).parents('tr')).data();

  if(tbl_pago_pension.row(this).child.isShown()){
      var data = tbl_pago_pension.row(this).data();
  }
  $("#modal_registro").modal('show');
  document.getElementById('txt_id_matricula').value=data.id_matricula;

})


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



function Registrar_Detalle_asigdocente() {
  let count = 0;
  let arreglo_id = new Array();
  let arreglo_concepto = new Array();
  let arreglo_pension = new Array();
  let arreglo_subtotal = new Array();

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
    if (resp ==1) {
        Swal.fire("Mensaje de Confirmación","Nueva pensión registrada satisfactoriamente!!!","success").then((value)=>{

        tbl_pago_pension.ajax.reload();
        tbl_pago_pension.clear().draw();

        $("#modal_registro").modal('hide');
        document.getElementById('select_curso').value = "";

        table.removeChild(tr);
      });
    
    } else {
      Swal.fire("Mensaje de Advertencia","La pensión del estudiante que desear pagar ya se encuentra en la base de datos, revise por favor","warning");
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
      "pageLength": 10,
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
          { "data": "sub_total" }
      ],
      "language": idioma_espanol,
      select: true
  });

  tbl_pagos.on('draw.td', function() {
      var PageInfo = $("#tabla_pagos2").DataTable().page.info();
      tbl_pagos.column(0, { page: 'current' }).nodes().each(function(cell, i) {
          cell.innerHTML = i + 1 + PageInfo.start;
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

