//LISTADO DE SECCION
var tbl_secciones;
function listar_secciones(){
    tbl_secciones = $("#tabla_secciones").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      "destroy":true,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "async": true ,
      "processing": true,
      "ajax":{
          "url":"../controller/seccion/controlador_listar_seccion.php",
          type:'POST',
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE SECCIONES"
      },
        title: function() {
          return  "LISTA DE SECCIONES" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE SECCIONES"
      },
    title: function() {
      return  "LISTA DE SECCIONES"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE SECCIONES"
  
    }
    }
],
      "columns":[
        {"data":"seccion_id"},
        {"data":"seccion_nombre"},
        {"data":"seccion_descripcion"},
        {"data":"fecha_formateada"},
        {"data":"seccion_estado",
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
tbl_secciones.on('draw.td',function(){
  var PageInfo = $("#tabla_secciones").DataTable().page.info();
  tbl_secciones.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
//ENVIANDO DATOS PARA EDITAR
$('#tabla_secciones').on('click','.editar',function(){
  var data = tbl_secciones.row($(this).parents('tr')).data();

  if(tbl_secciones.row(this).child.isShown()){
      var data = tbl_secciones.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idseccion').value=data.seccion_id;
  document.getElementById('txt_seccion_editar').value=data.seccion_nombre;
  document.getElementById('txt_descripcion_editar').value=data.seccion_descripcion;
  document.getElementById('txt_estatus').value=data.seccion_estado;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO SECCION
function Registrar_Seccion(){
  let seccion = document.getElementById('txt_seccion').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(seccion.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/seccion/controlador_registro_seccion.php",
    type:'POST',
    data:{
        seccion:seccion,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva sección registrada satisfactoriamente!!!","success").then((value)=>{
        tbl_secciones.ajax.reload();
          document.getElementById('txt_seccion').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La sección que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Rol(){
  let id = document.getElementById('txt_idseccion').value;
  let seccion = document.getElementById('txt_seccion_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(seccion.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/seccion/controlador_modificar_seccion.php",
    type:'POST',
    data:{
      id:id,
      seccion:seccion,
      descrip:descrip,
      esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_secciones.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La sección que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO SECCION
function Eliminar_Seccion(id){
    $.ajax({
      "url":"../controller/seccion/controlador_eliminar_seccion.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la sección con exito","success").then((value)=>{
            tbl_secciones.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advertencia","No se puede eliminar esta sección por que esta siendo utilizado en otro registro, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_secciones').on('click','.delete',function(){
    var data = tbl_secciones.row($(this).parents('tr')).data();
  
    if(tbl_secciones.row(this).child.isShown()){
        var data = tbl_secciones.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar la sección: '+data.seccion_nombre+'?',
      text: "Una vez aceptado la sección sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Seccion(data.seccion_id);
      }
    })
  })