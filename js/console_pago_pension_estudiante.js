//LISTADO DE ROLES
var tbl_pago_pension;
function listar_pago_pension(){
    let id = document.getElementById('txtprincipalid').value;

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
          "url":"../controller/pago_pension/controlador_listar_matriculas_id.php",
          type:'POST',
          data:{
            id:id
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


        {"defaultContent":"<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fa fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='imprimir btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fa fa-print'></i> Kardex de pago</button>"},
        
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

function listar_pago_pension_buscar(){
    let id = document.getElementById('txtprincipalid').value;
    let año = document.getElementById('select_año').value;

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
          "url":"../controller/pago_pension/controlador_listar_matriculas_año_id.php",
          type:'POST',
          data:{
            id:id,
            año:año
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


        {"defaultContent":"<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fa fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='imprimir btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fa fa-print'></i> Kardex de pago</button>"},
        
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


function listar_todo(){
    let id = document.getElementById('txtprincipalid').value;

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
          "url":"../controller/pago_pension/controlador_listar_pension_todo.php",
          type:'POST',
          data:{
            id:id
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


        {"defaultContent":"<button class='mostrar btn btn-primary  btn-sm' title='Ver pagos'><i class='fa fa-eye'></i> Ver pagos</button>&nbsp;&nbsp; <button class='imprimir btn btn-warning  btn-sm' title='Imprimir kardex de pago'><i class='fa fa-print'></i> Kardex de pago</button>"},
        
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
function Cargar_Año(){
    let id = document.getElementById('txtprincipalid').value;
  
      $.ajax({
        "url":"../controller/notas/controlador_cargar_años_por_estudiante.php",
        type:'POST',
        data:{
          id:id
        }
      }).done(function(resp){
        let data=JSON.parse(resp);
        if(data.length>0){
          let cadena ="";
          for (let i = 0; i < data.length; i++) {
            cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+" || "+data[i][4]+" || "+data[i][5]+"</option>";    
          }
            document.getElementById('select_año').innerHTML=cadena;
        }else{
          cadena+="<option value=''>No hay secciones en la base de datos</option>";
          document.getElementById('select_año').innerHTML=cadena;
        }
      })
    }
  




//REGISTRANDO ROLES





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
            { "data": "sub_total" },
            {
                "defaultContent": "<button class='imprimir btn btn-success btn-sm' title='Imprimir boleta'><i class='fa fa-print'></i> Boleta</button>",
                "orderable": false // Evita la ordenación en esta columna
            }
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



$('#tabla_pago_pension').on('click','.imprimir',function(){
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