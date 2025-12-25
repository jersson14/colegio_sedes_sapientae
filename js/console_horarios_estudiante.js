//LISTADO DE ROLES
var tbl_horarios;
function listar_horarios(){
    let id = document.getElementById('txtprincipalid').value;

    tbl_horario = $("#tabla_horario").DataTable({
        
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
          "url":"../controller/horarios/controlador_listar_horarios_estudiante_id.php",
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
        return  "LISTA DE HORARIOS"
      },
        title: function() {
            return  "LISTA DE HORARIOS"
        }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE HORARIOS"
      },
    title: function() {
        return  "LISTA DE HORARIOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
        return  "LISTA DE HORARIOS"
  
    }
    }],
      "columns":[
        {"data":"id_año_academico"},
        {"data":"año_escolar"},
        {"data":"GRADO"},
        {"data":"Nivel_academico"},
        
        {           
             "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver horario'><i class='fa fa-eye'></i> Horario por aula</button>"
        },
        {"data":"HORARIO",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },

        {"defaultContent":"<button class='print btn btn-warning  btn-sm' title='Imprimir horario'><i class='fa fa-print'></i> Imprimir horario</button>"},

    ],

    "language":idioma_espanol,
    select: true
});
tbl_horario.on('draw.td',function(){
  var PageInfo = $("#tabla_horario").DataTable().page.info();
  tbl_horario.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}


function listar_horarios_año(){
  let id = document.getElementById('txtprincipalid').value;
  let año = document.getElementById('select_año').value;

  tbl_horario = $("#tabla_horario").DataTable({
      
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
        "url":"../controller/horarios/controlador_listar_horarios_estudiante_id_año.php",
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
      return  "LISTA DE HORARIOS"
    },
      title: function() {
          return  "LISTA DE HORARIOS"
      }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE HORARIOS"
    },
  title: function() {
      return  "LISTA DE HORARIOS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
      return  "LISTA DE HORARIOS"

  }
  }],
    "columns":[
      {"data":"id_año_academico"},
      {"data":"año_escolar"},
      {"data":"GRADO"},
      {"data":"Nivel_academico"},
      
      {           
           "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver horario'><i class='fa fa-eye'></i> Horario por aula</button>"
      },
      {"data":"HORARIO",
          render: function(data,type,row){
                  if(data=='ACTIVO'){
                  return '<span class="badge bg-success">ACTIVO</span>';
                  }else{
                  return '<span class="badge bg-danger">INACTIVO</span>';
                  }
          }   
      },

      {"defaultContent":"<button class='print btn btn-warning  btn-sm' title='Imprimir horario'><i class='fa fa-print'></i> Imprimir horario</button>"},

  ],

  "language":idioma_espanol,
  select: true
});
tbl_horario.on('draw.td',function(){
var PageInfo = $("#tabla_horario").DataTable().page.info();
tbl_horario.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}

function listar_todo(){
  let id = document.getElementById('txtprincipalid').value;

  tbl_horario = $("#tabla_horario").DataTable({
      
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
        "url":"../controller/horarios/controlador_listar_horarios_estudiante_todo.php",
        type:'POST',
        data:{
          id:id,
        }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE HORARIOS"
    },
      title: function() {
          return  "LISTA DE HORARIOS"
      }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE HORARIOS"
    },
  title: function() {
      return  "LISTA DE HORARIOS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
      return  "LISTA DE HORARIOS"

  }
  }],
    "columns":[
      {"data":"id_año_academico"},
      {"data":"año_escolar"},
      {"data":"GRADO"},
      {"data":"Nivel_academico"},
      
      {           
           "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver horario'><i class='fa fa-eye'></i> Horario por aula</button>"
      },
      {"data":"HORARIO",
          render: function(data,type,row){
                  if(data=='ACTIVO'){
                  return '<span class="badge bg-success">ACTIVO</span>';
                  }else{
                  return '<span class="badge bg-danger">INACTIVO</span>';
                  }
          }   
      },

      {"defaultContent":"<button class='print btn btn-warning  btn-sm' title='Imprimir horario'><i class='fa fa-print'></i> Imprimir horario</button>"},

  ],

  "language":idioma_espanol,
  select: true
});
tbl_horario.on('draw.td',function(){
var PageInfo = $("#tabla_horario").DataTable().page.info();
tbl_horario.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}
//TRAENDO DATOS DE LA SECCION
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
  

// Evento para abrir el modal de edición y cargar los datos correspondientes

//EDITANDO ROL

//MOSTRAR
  var tbl_vistas;
  function listar_horas(id) {
    tbl_vistas = $("#tabla_vista_horario").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": { "regex": false },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 12,
        "destroy": true,
        pagingType: 'full_numbers',
        scrollCollapse: true,
        responsive: true,
        "async": false,
        "processing": true,
        "ajax": {
            "url": "../controller/horarios/controlador_listar_horarios_id.php",
            type: 'POST',
            data: { id: id },
        },
       "columns": [
    { 
        "data": "hora",
        "render": function(data, type, row) {
            return '<strong>' + data + '</strong>';
        }
    },
    { 
        "data": "Lunes",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Martes",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Miercoles",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Jueves",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Viernes",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    }
],

        "language": idioma_espanol,
        select: true
    });

    tbl_vistas.on('draw.td', function () {
        var PageInfo = $("#tabla_vista_horario").DataTable().page.info();
        tbl_vistas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
        });
    });
}
  
$('#tabla_horario').on('click','.mostrar',function(){
  var data = tbl_horario.row($(this).parents('tr')).data();

  if(tbl_horario.row(this).child.isShown()){
      var data = tbl_horario.row(this).data();
  }
$("#modal_ver_horario").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>AÑO ACADEMICO: "+data.año_escolar+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>AULA O GRADO: "+data.Grado+"</b>";
  listar_horas(data.id_aula);

})



$('#tabla_horario').on('click','.print',function(){
  var data = tbl_horario.row($(this).parents('tr')).data();

  if(tbl_horario.row(this).child.isShown()){
      var data = tbl_horario.row(this).data();
  }
  var url = "../view/MPDF/REPORTE/horario.php?codigo=" + encodeURIComponent(data.id_aula)+ "#zoom=100%";

  // Abrir una nueva ventana con la URL construida
  var newWindow = window.open(url, "HORARIOS POR AULA", "scrollbars=NO");
  
  // Asegurarse de que la ventana se abre en tamaño máximo
  if (newWindow) {
      newWindow.moveTo(0, 0);
      newWindow.resizeTo(screen.width, screen.height);
  }

})

