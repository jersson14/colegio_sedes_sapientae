//LISTADO DE ROLES
var tbl_roles;
function listar_roles(){
    tbl_roles = $("#tabla_roles").DataTable({
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
          "url":"../controller/roles/controlador_listar_roles.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE ROLES"
      },
        title: function() {
          return  "LISTA DE ROLES" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE ROLES"
      },
    title: function() {
      return  "LISTA DE ROLES"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE ROLES"
  
    }
    }],
      "columns":[
        {"data":"Id_rol"},
        {"data":"tipo_rol"},
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
tbl_roles.on('draw.td',function(){
  var PageInfo = $("#tabla_roles").DataTable().page.info();
  tbl_roles.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
//ENVIANDO DATOS PARA EDITAR
$('#tabla_roles').on('click','.editar',function(){
  var data = tbl_roles.row($(this).parents('tr')).data();

  if(tbl_roles.row(this).child.isShown()){
      var data = tbl_roles.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idrol').value=data.Id_rol;
  document.getElementById('txt_rol_editar').value=data.tipo_rol;
  document.getElementById('txt_descripcion_editar').value=data.descripcion;
  document.getElementById('txt_estatus').value=data.estado;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_rol(){
  let rol = document.getElementById('txt_rol').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(rol.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/roles/controlador_registro_roles.php",
    type:'POST',
    data:{
        rol:rol,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nuevo rol registrado satisfactoriamente!!!","success").then((value)=>{
        tbl_roles.ajax.reload();
          document.getElementById('txt_rol').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El rol que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Rol(){
  let id = document.getElementById('txt_idrol').value;
  let rol = document.getElementById('txt_rol_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(rol.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/roles/controlador_modificar_rol.php",
    type:'POST',
    data:{
      id:id,
      rol:rol,
      descrip:descrip,
      esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_roles.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El rol que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Rol(id){
    $.ajax({
      "url":"../controller/roles/controlador_eliminar_rol.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el rol con exito","success").then((value)=>{
            tbl_roles.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Error","No se pudo completar el proceso","error");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_roles').on('click','.delete',function(){
    var data = tbl_roles.row($(this).parents('tr')).data();
  
    if(tbl_roles.row(this).child.isShown()){
        var data = tbl_roles.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el rol: '+data.tipo_rol+'?',
      text: "Una vez aceptado el rol sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Rol(data.Id_rol);
      }
    })
  })