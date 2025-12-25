//LISTADO DE ROLES
var tbl_aulas;
function listar_aulas(){
    tbl_aulas = $("#tabla_aulas").DataTable({
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
          "url":"../controller/aulas/controlador_listar_aulas.php",
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
        {"data":"Id_aula"},
        {"data":"Grado"},
        {"data":"seccion_nombre"},
        {"data":"Nivel_academico",
            render: function(data,type,row){
                if(data=='INICIAL'){
                return '<span class="badge bg-warning">INICIAL</span>';
                }else if(data=='PRIMARIA'){
                return '<span class="badge bg-success">PRIMARIA</span>';
                }else if(data=='SECUNDARIA'){
                  return '<span class="badge bg-primary">SECUNDARIA</span>';
                  }else{
                return '<span class="badge bg-dark">TODOS</span>';
                }
        }
        },
        {"data":"descripcion"},
        {"data":"fecha_formateada"},
        {"data":"estado",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_aulas.on('draw.td',function(){
  var PageInfo = $("#tabla_aulas").DataTable().page.info();
  tbl_aulas.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

function listar_aulas_filtro(){
  let nivel = document.getElementById('select_nivel_buscar').value;

  tbl_aulas = $("#tabla_aulas").DataTable({
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
        "url":"../controller/aulas/controlador_listar_aulas_filtro.php",
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
      {"data":"Id_aula"},
      {"data":"Grado"},
      {"data":"seccion_nombre"},
      {"data":"Nivel_academico",
          render: function(data,type,row){
              if(data=='INICIAL'){
              return '<span class="badge bg-warning">INICIAL</span>';
              }else if(data=='PRIMARIA'){
              return '<span class="badge bg-success">PRIMARIA</span>';
              }else if(data=='SECUNDARIA'){
                return '<span class="badge bg-primary">SECUNDARIA</span>';
                }else{
              return '<span class="badge bg-dark">TODOS</span>';
              }
      }
      },
      {"data":"descripcion"},
      {"data":"fecha_formateada"},
      {"data":"estado",
          render: function(data,type,row){
                  if(data=='ACTIVO'){
                  return '<span class="badge bg-success">ACTIVO</span>';
                  }else{
                  return '<span class="badge bg-danger">INACTIVO</span>';
                  }
          }   
      },
      {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},
      
  ],

  "language":idioma_espanol,
  select: true
});
tbl_aulas.on('draw.td',function(){
var PageInfo = $("#tabla_aulas").DataTable().page.info();
tbl_aulas.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}
//TRAENDO DATOS DE LA SECCION
function Cargar_Select_Seccion(){
    $.ajax({
      "url":"../controller/aulas/controlador_cargar_select_seccion.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
          document.getElementById('select_seccion').innerHTML=cadena;
          document.getElementById('select_seccion_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_seccion').innerHTML=cadena;
        document.getElementById('select_seccion_editar').innerHTML=cadena;
      }
    })
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
          document.getElementById('select_nivel_aca').innerHTML=cadena;
          document.getElementById('select_nivel_aca_editar').innerHTML=cadena;
          document.getElementById('select_nivel_buscar').innerHTML=cadena;

      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_nivel_aca').innerHTML=cadena;
        document.getElementById('select_nivel_aca_editar').innerHTML=cadena;
        document.getElementById('select_nivel_buscar').innerHTML=cadena;

      }
    })
  }
  
//ENVIANDO DATOS PARA EDITAR
$('#tabla_aulas').on('click','.editar',function(){
  var data = tbl_aulas.row($(this).parents('tr')).data();

  if(tbl_aulas.row(this).child.isShown()){
      var data = tbl_aulas.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_id_grado').value=data.Id_aula;
  document.getElementById('txt_grado_editar').value=data.Grado;
  $("#select_seccion_editar").select2().val(data.id_seccion).trigger('change.select2');
  $("#select_nivel_aca_editar").select2().val(data.id_nivel_academico).trigger('change.select2');
  document.getElementById('txt_descripcion_editar').value=data.descripcion;
  document.getElementById('txt_estatus').value=data.estado;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_aula(){
  let grado = document.getElementById('txt_grado').value;
  let seccion = document.getElementById('select_seccion').value;
  let nivel = document.getElementById('select_nivel_aca').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(grado.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/aulas/controlador_registro_aulas.php",
    type:'POST',
    data:{
        grado:grado,
        seccion:seccion,
        nivel:nivel,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva grado académico registrado satisfactoriamente!!!","success").then((value)=>{
          tbl_aulas.ajax.reload();
          document.getElementById('txt_grado').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El grado académico que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Rol(){
  let id = document.getElementById('txt_id_grado').value;
  let grado = document.getElementById('txt_grado_editar').value;
  let seccion = document.getElementById('select_seccion_editar').value;
  let nivel = document.getElementById('select_nivel_aca_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let estatus = document.getElementById('txt_estatus').value;

  if(grado.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/aulas/controlador_modificar_aula.php",
    type:'POST',
    data:{
      id:id,
      grado:grado,
      seccion:seccion,
      nivel:nivel,
      descrip:descrip,
      estatus:estatus
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_aulas.ajax.reload();
            $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El grado académico que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Aula(id){
    $.ajax({
      "url":"../controller/aulas/controlador_eliminar_rol.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el grado académico con exito","success").then((value)=>{
            tbl_aulas.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este grado académico por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_aulas').on('click','.delete',function(){
    var data = tbl_aulas.row($(this).parents('tr')).data();
  
    if(tbl_aulas.row(this).child.isShown()){
        var data = tbl_aulas.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el grado académico: '+data.Grado+'?',
      text: "Una vez aceptado el grado académico sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Aula(data.Id_aula);
      }
    })
  })