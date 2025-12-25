//LISTADO DE NIVEL ACADEMICO
var tbl_egresos_diversos;
function listar_egresos_diversos(){
    tbl_egresos_diversos = $("#tabla_egresos_diversos").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 5,
      "destroy":true,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "async": false ,
      "processing": true,
      "ajax":{
          "url":"../controller/egresos/controlador_listar_egresos_diversos.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE EGRESOS DIVERSOS"
      },
        title: function() {
          return  "LISTA DE EGRESOS DIVERSOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE EGRESOS DIVERSOS"
      },
    title: function() {
      return  "LISTA DE EGRESOS DIVERSOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE EGRESOS DIVERSOS"
  
    }
    }],
      "columns":[
        {"data":"id_egresos"},
        {"data":"nombre"},
        {"data":"cantidad"},
        {"data":"monto",
          render: function(data,type,row){
              if(data==data){
              return '<span class="badge bg-success">S/. '+data+'</span>';
              }
      }   
      },        
      {"data":"observacion"},

        {"data":"fecha_formateada"},
        {"data":"estado",
          render: function(data,type,row){
                  if(data=='VALIDO'){
                  return '<span class="badge bg-success">VALIDO</span>';
                  }else{
                  return '<span class="badge bg-danger">ANULADO</span>';
                  }
          }   
      },
      {"data":"estado",
        render: function(data,type,row){
                if(data=='VALIDO'){
                return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Anular ingreso'><i class='fa fa-trash'></i> Anular</button>";
                }else{
                return "<button hidden class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button hidden class='delete btn btn-danger  btn-sm' title='Anular ingreso'><i class='fa fa-trash'></i> Anular</button>&nbsp;&nbsp; <button class='view btn btn-warning  btn-sm' title='Motivo de anulación'><i class='fa fa-eye'></i> Ver motivo de anulación</button>";
                }
        }   
    },        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_egresos_diversos.on('draw.td',function(){
  var PageInfo = $("#tabla_egresos_diversos").DataTable().page.info();
  tbl_egresos_diversos.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

function listar_egresos_diversos_filtro(){
  let indi = document.getElementById('select_indicadores_buscar').value;
  let fechaini = document.getElementById('txtfechainicio').value;
  let fechafin = document.getElementById('txtfechafin').value;

  tbl_egresos_diversos = $("#tabla_egresos_diversos").DataTable({
    "ordering":false,   
    "bLengthChange":true,
    "searching": { "regex": false },
    "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
    "pageLength": 5,
    "destroy":true,
    pagingType: 'full_numbers',
    scrollCollapse: true,
    responsive: true,
    "async": false ,
    "processing": true,
    "ajax":{
        "url":"../controller/egresos/controlador_listar_egresos_diversos_filtros.php",
        type:'POST',
        data:{
          indi:indi,
          fechaini:fechaini,
          fechafin:fechafin
        }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE EGRESOS DIVERSOS"
    },
      title: function() {
        return  "LISTA DE EGRESOS DIVERSOS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE EGRESOS DIVERSOS"
    },
  title: function() {
    return  "LISTA DE EGRESOS DIVERSOS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE EGRESOS DIVERSOS"

  }
  }],
    "columns":[
      {"data":"id_egresos"},
      {"data":"nombre"},
      {"data":"cantidad"},
      {"data":"monto",
        render: function(data,type,row){
            if(data==data){
            return '<span class="badge bg-success">S/. '+data+'</span>';
            }
    }   
    },        {"data":"observacion"},

      {"data":"fecha_formateada"},
      {"data":"estado",
        render: function(data,type,row){
                if(data=='VALIDO'){
                return '<span class="badge bg-success">VALIDO</span>';
                }else{
                return '<span class="badge bg-danger">ANULADO</span>';
                }
        }   
    },
    {"data":"estado",
      render: function(data,type,row){
              if(data=='VALIDO'){
              return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Anular ingreso'><i class='fa fa-trash'></i> Anular</button>";
              }else{
                return "<button hidden class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button hidden class='delete btn btn-danger  btn-sm' title='Anular ingreso'><i class='fa fa-trash'></i> Anular</button>&nbsp;&nbsp; <button class='view btn btn-warning  btn-sm' title='Motivo de anulación'><i class='fa fa-eye'></i> Ver motivo de anulación</button>";
              }
      }   
  },
      
  ],

  "language":idioma_espanol,
  select: true
});
tbl_egresos_diversos.on('draw.td',function(){
var PageInfo = $("#tabla_egresos_diversos").DataTable().page.info();
tbl_egresos_diversos.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}
//CARGAR INDICADORES
function Cargar_Indicadores(){
    $.ajax({
      "url":"../controller/egresos/controlador_cargar_select_indicadores_egresos.php",
      type:'POST',
    }).done(function(resp){
      let data = JSON.parse(resp);
      let cadena = "<option value=''>--SELECCIONE--</option>";  // Añadir opción "SELECCIONE" al inicio
      if(data.length > 0){
        for (let i = 0; i < data.length; i++) {
          cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";    
        }
      } else {
        cadena += "<option value=''>No hay secciones en la base de datos</option>";
      }
      document.getElementById('select_indicadores_buscar').innerHTML = cadena;

      document.getElementById('select_indicadores').innerHTML = cadena;
      document.getElementById('select_indicadores_editar').innerHTML = cadena;
      document.getElementById('select_indicadores_anular').innerHTML = cadena;

    })
}



//ENVIANDO DATOS PARA EDITAR
$('#tabla_egresos_diversos').on('click','.editar',function(){
  var data = tbl_egresos_diversos.row($(this).parents('tr')).data();

  if(tbl_egresos_diversos.row(this).child.isShown()){
      var data = tbl_egresos_diversos.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_id_egreso').value=data.id_egresos;
  $("#select_indicadores_editar").select2().val(data.id_indicador).trigger('change.select2');
  document.getElementById('txt_cantidad_editar').value=data.cantidad;
  document.getElementById('txt_monto_editar').value=data.monto;
  document.getElementById('txt_observación_editar').value=data.observacion;

})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO NIVEL ACADEMICO
function Registrar_egreso() {
  let indi = document.getElementById('select_indicadores').value;
  let cantidad = document.getElementById('txt_cantidad').value;
  let monto = document.getElementById('txt_monto').value;
  let obse = document.getElementById('txt_observación').value;
  let usu = document.getElementById('txtprincipalid').value;

  // Convertir monto a número antes de la validación
  if (parseFloat(monto) <= 0) {
    return Swal.fire("Mensaje de Advertencia", "El monto no puede ser 0 ni menor a 0", "warning");
  }

  if (indi.length == 0 || cantidad.length == 0) {
    return Swal.fire("Mensaje de Advertencia", "El indicador y cantidad son obligatorio llenar", "warning");
  }

  $.ajax({
    url: "../controller/egresos/controlador_registrar_egreso.php",
    type: 'POST',
    data: {
      indi: indi,
      cantidad: cantidad,
      monto: monto,
      obse: obse,
      usu: usu
    }
  }).done(function (resp) {
    if (resp > 0) {
      Swal.fire("Mensaje de Confirmación", "¡Nuevo egreso registrado satisfactoriamente!", "success").then((value) => {
        document.getElementById('txt_cantidad').value = "";
        document.getElementById('txt_monto').value = "";
        document.getElementById('txt_observación').value = "";
        tbl_egresos_diversos.ajax.reload();
        $("#modal_registro").modal('hide');
      });
    } else {
      Swal.fire("Mensaje de Error", "No se completó el registro", "error");
    }
  });
}

//EDITANDO INDICADOR
function Modificar_Egreso(){
  let id = document.getElementById('txt_id_egreso').value;
  let indi = document.getElementById('select_indicadores_editar').value;
  let cantidad = document.getElementById('txt_cantidad_editar').value;
  let monto = document.getElementById('txt_monto_editar').value;
  let obser = document.getElementById('txt_observación_editar').value;
  let usu = document.getElementById('txtprincipalid').value;

  if (parseFloat(monto) <= 0) {
    return Swal.fire("Mensaje de Advertencia", "El monto no puede ser 0 ni menor a 0", "warning");
  }

  if (indi.length == 0 || cantidad.length == 0) {
    return Swal.fire("Mensaje de Advertencia", "El indicador y cantidad son obligatorio llenar", "warning");
  }
  $.ajax({
    "url":"../controller/egresos/controlador_modificar_egreso.php",
    type:'POST',
    data:{
      id:id,
      indi:indi,
      cantidad:cantidad,
      monto:monto,
      obser:obser,
      usu:usu
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
        tbl_egresos_diversos.ajax.reload();
        $("#modal_editar").modal('hide');
        });
    
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");
    }
  })
}
//ELIMINANDO ROL
$('#tabla_egresos_diversos').on('click','.delete',function(){
  var data = tbl_egresos_diversos.row($(this).parents('tr')).data();

  if(tbl_egresos_diversos.row(this).child.isShown()){
      var data = tbl_egresos_diversos.row(this).data();
  }
  $("#modal_anular").modal('show');
  document.getElementById('txt_id_egreso_anular').value=data.id_egresos;
  $("#select_indicadores_anular").select2().val(data.id_indicador).trigger('change.select2');
  document.getElementById('txt_cantidad_anular').value=data.cantidad;
  document.getElementById('txt_monto_anular').value=data.monto;

})

function Anular_Ingreso(){
  let id = document.getElementById('txt_id_egreso_anular').value;
  let obser = document.getElementById('txt_observación_anular').value;
  let usu = document.getElementById('txtprincipalid').value;


  if (id.length == 0 || obser.length == 0) {
    return Swal.fire("Mensaje de Advertencia", "El motivo de anulación es obligatorio llenar", "warning");
  }
  $.ajax({
    "url":"../controller/egresos/controlador_anular_egreso.php",
    type:'POST',
    data:{
      id:id,
      obser:obser,
      usu:usu
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se anulo correctamente el ingreso","success").then((value)=>{
        tbl_egresos_diversos.ajax.reload();
        $("#modal_anular").modal('hide');
        });
    
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");
    }
  })
}



$('#tabla_egresos_diversos').on('click','.view',function(){
  var data = tbl_egresos_diversos.row($(this).parents('tr')).data();

  if(tbl_egresos_diversos.row(this).child.isShown()){
      var data = tbl_egresos_diversos.row(this).data();
  }
  $("#modal_motivo").modal('show');
  document.getElementById('fecha_anulacion').value=data.fecha_anulacion;

  document.getElementById('txt_observación_motivo').value=data.motivo_anulacion;

})


var tbl_diferencia;
function listar_diferencia(){
    tbl_diferencia = $("#tabla_diferencia").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 5,
      "destroy":true,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "async": false ,
      "processing": true,
      "ajax":{
          "url":"../controller/egresos/controlador_listar_diferencia.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE DIFERENCIA"
      },
        title: function() {
          return  "LISTA DE DIFERENCIA" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE DIFERENCIA"
      },
    title: function() {
      return  "LISTA DE DIFERENCIA"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE DIFERENCIA"
  
    }
    }],
      "columns":[
        {"data":"FechaInicial"},
        {"data":"FechaFinal"},
        {"data":"totalingresos",
          render: function(data,type,row){
              if(data==data){
              return '<span class="badge bg-success">'+data+'</span>';
              }
      }   
      },        
      {"data":"totalgasto",
        render: function(data,type,row){
            if(data==data){
            return '<span class="badge bg-danger">'+data+'</span>';
            }
    }   
    },    
    {"data":"Diferencia",
        render: function(data,type,row){
            if(data<0){
            return '<span class="badge bg-danger">'+data+'</span>';
            }else{
                return '<span class="badge bg-success">'+data+'</span>';

            }
    }   
    },    
    ],

    "language":idioma_espanol,
    select: true
});
tbl_diferencia.on('draw.td',function(){
  var PageInfo = $("#tabla_diferencia").DataTable().page.info();
  tbl_diferencia.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function listar_diferencia_filtro(){
    let fechaini = document.getElementById('txtfechainicio3').value;
    let fechafin = document.getElementById('txtfechafin3').value;
  
    tbl_diferencia = $("#tabla_diferencia").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 5,
      "destroy":true,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "async": false ,
      "processing": true,
      "ajax":{
          "url":"../controller/egresos/controlador_listar_diferencia_filtro.php",
          type:'POST',
          data:{
            fechaini:fechaini,
            fechafin:fechafin
          }
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE DIFERENCIA"
      },
        title: function() {
          return  "LISTA DE DIFERENCIA" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE DIFERENCIA"
      },
    title: function() {
      return  "LISTA DE DIFERENCIA"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE DIFERENCIA"
  
    }
    }],
      "columns":[
        {"data":"FechaInicial"},
        {"data":"FechaFinal"},
        {"data":"totalingresos",
          render: function(data,type,row){
              if(data==data){
              return '<span class="badge bg-success">'+data+'</span>';
              }
      }   
      },        
      {"data":"totalgasto",
        render: function(data,type,row){
            if(data==data){
            return '<span class="badge bg-danger">'+data+'</span>';
            }
    }   
    },    
    {"data":"Diferencia",
        render: function(data,type,row){
            if(data<0){
            return '<span class="badge bg-danger">'+data+'</span>';
            }else{
                return '<span class="badge bg-success">'+data+'</span>';

            }
    }   
    },    
    ],

    "language":idioma_espanol,
    select: true
});
tbl_diferencia.on('draw.td',function(){
  var PageInfo = $("#tabla_diferencia").DataTable().page.info();
  tbl_diferencia.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
