//LISTADO DE PENSIONES
var tbl_pensiones;
function listar_pensiones(){
    tbl_pensiones = $("#tabla_pensiones").DataTable({
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
          "url":"../controller/pensiones/controlador_listar_pensiones.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE PENSIONES"
      },
        title: function() {
          return  "LISTA DE PENSIONES" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE PENSIONES"
      },
    title: function() {
      return  "LISTA DE PENSIONES"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE PENSIONES"
  
    }
    }],
      "columns":[
        {"data":"id_pensiones"},
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
        {"data":"mes"},
        {"data":"fecha_formateada"},
        {"data":"PRECIO"},
        {"data":"MORA",
            render: function(data,type,row){
                    if(data==data){
                    return '<span class="badge bg-danger">'+data+'</span>';
                    }
            }   
        },
        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_pensiones.on('draw.td',function(){
  var PageInfo = $("#tabla_pensiones").DataTable().page.info();
  tbl_pensiones.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

function listar_pensiones_filtro(){
  let nivel = document.getElementById('select_nivel_buscar').value;

  tbl_pensiones = $("#tabla_pensiones").DataTable({
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
        "url":"../controller/pensiones/controlador_listar_pensiones_filtro.php",
        type:'POST',
        data:{
          nivel:nivel
        }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE PENSIONES"
    },
      title: function() {
        return  "LISTA DE PENSIONES" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE PENSIONES"
    },
  title: function() {
    return  "LISTA DE PENSIONES"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE PENSIONES"

  }
  }],
    "columns":[
      {"data":"id_pensiones"},
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
      {"data":"mes"},
      {"data":"fecha_formateada"},
      {"data":"PRECIO"},
      {"data":"MORA",
          render: function(data,type,row){
                  if(data==data){
                  return '<span class="badge bg-danger">'+data+'</span>';
                  }
          }   
      },
      {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},
      
  ],

  "language":idioma_espanol,
  select: true
});
tbl_pensiones.on('draw.td',function(){
var PageInfo = $("#tabla_pensiones").DataTable().page.info();
tbl_pensiones.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}
//TRAENDO DATOS DE LA NIVEL ACADEMICO

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
            document.getElementById('select_nivel').innerHTML=cadena;
            document.getElementById('select_nivel_editar').innerHTML=cadena;
            document.getElementById('select_nivel_buscar').innerHTML=cadena;

      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
            document.getElementById('select_nivel').innerHTML=cadena;
            document.getElementById('select_nivel_editar').innerHTML=cadena;
            document.getElementById('select_nivel_buscar').innerHTML=cadena;

          }
    })
  }
  
//ENVIANDO DATOS PARA EDITAR
$('#tabla_pensiones').on('click','.editar',function(){
  var data = tbl_pensiones.row($(this).parents('tr')).data();

  if(tbl_pensiones.row(this).child.isShown()){
      var data = tbl_pensiones.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_id_pension').value=data.id_pensiones;
  $("#select_nivel_editar").select2().val(data.id_nivel_academico).trigger('change.select2');
  document.getElementById('select_mes_editar').value=data.mes;
  document.getElementById('txt_fecha_editar').value=data.fecha_vencimiento;
  document.getElementById('txt_precio_editar').value=data.precio;
  document.getElementById('txt_mora_editar').value=data.mora;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_pensiones(){
  let nivel = document.getElementById('select_nivel').value;
  let mes = document.getElementById('select_mes').value;
  let fecha = document.getElementById('txt_fecha').value;
  let precio = document.getElementById('txt_precio').value;
  let mora = document.getElementById('txt_mora').value;

  if(nivel.length==0||mes.length==0||fecha.length==0||precio.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios revise por favor","warning");
  }
  $.ajax({
    "url":"../controller/pensiones/controlador_registro_pensiones.php",
    type:'POST',
    data:{
        nivel:nivel,
        mes:mes,
        fecha:fecha,
        precio:precio,
        mora:mora

    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva pensión registrada satisfactoriamente!!!","success").then((value)=>{
          tbl_pensiones.ajax.reload();

        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El nivel académico y mes de la pensión que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO Pensiones
function Modificar_Pensiones(){
  let id = document.getElementById('txt_id_pension').value;
  let nivel = document.getElementById('select_nivel_editar').value;
  let mes = document.getElementById('select_mes_editar').value;
  let fecha = document.getElementById('txt_fecha_editar').value;
  let precio = document.getElementById('txt_precio_editar').value;
  let mora = document.getElementById('txt_mora_editar').value;

  if(nivel.length==0||mes.length==0||fecha.length==0||precio.length==0||id.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios revise por favor","warning");
  }
  $.ajax({
    "url":"../controller/pensiones/controlador_modificar_pension.php",
    type:'POST',
    data:{
      id:id,
      nivel:nivel,
      mes:mes,
      fecha:fecha,
      precio:precio,
      mora:mora
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_pensiones.ajax.reload();
            $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El nivel académico y mes de la pensión que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Pensión(id){
    $.ajax({
      "url":"../controller/pensiones/controlador_eliminar_pensiones.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la pensión con exito","success").then((value)=>{
            tbl_pensiones.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta pensión por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_pensiones').on('click','.delete',function(){
    var data = tbl_pensiones.row($(this).parents('tr')).data();
  
    if(tbl_pensiones.row(this).child.isShown()){
        var data = tbl_pensiones.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar la pensión del mes de: <b style="color:blue">'+data.mes+'</b> - nivel académico de:<b style="color:blue">'+data.Nivel_academico+'</b>?',
      text: "Una vez aceptado la pensión sera eliminada!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Pensión(data.id_pensiones);
      }
    })
  })