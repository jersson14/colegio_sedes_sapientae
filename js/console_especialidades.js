//LISTADO DE ESPECIALIDADES
var tbl_especialidad;
function listar_especialidad(){
    tbl_especialidad = $("#tabla_especialidad").DataTable({
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
          "url":"../controller/especialidad/controlador_listar_especialidad.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE ESPECIALIDADES"
      },
        title: function() {
          return  "LISTA DE ESPECIALIDADES" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE ESPECIALIDADES"
      },
    title: function() {
      return  "LISTA DE ESPECIALIDADES"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE ESPECIALIDADES"
  
    }
    }],
      "columns":[
        {"data":"Id_especilidad"},
        {"data":"Especialidad"},
        {"data":"Descripcion"},
        {"data":"fecha_formateada"},
        {"data":"Estado",
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
tbl_especialidad.on('draw.td',function(){
  var PageInfo = $("#tabla_especialidad").DataTable().page.info();
  tbl_especialidad.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
//ENVIANDO DATOS PARA EDITAR
$('#tabla_especialidad').on('click','.editar',function(){
  var data = tbl_especialidad.row($(this).parents('tr')).data();

  if(tbl_especialidad.row(this).child.isShown()){
      var data = tbl_especialidad.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idespe').value=data.Id_especilidad;
  document.getElementById('txt_especialidad_editar').value=data.Especialidad;
  document.getElementById('txt_descripcion_editar').value=data.Descripcion;
  document.getElementById('txt_estatus').value=data.Estado;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ESPECIALIDAD
function Registrar_Especialidad(){
  let especialidad = document.getElementById('txt_especialidad').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(especialidad.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre de la especialidad debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/especialidad/controlador_registro_especialidad.php",
    type:'POST',
    data:{
        especialidad:especialidad,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva especialidad registrada satisfactoriamente!!!","success").then((value)=>{
          tbl_especialidad.ajax.reload();
          document.getElementById('txt_especialidad').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La especialidad que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ESPECIALIDAD
function Modificar_Especialidad(){
  let id = document.getElementById('txt_idespe').value;
  let especialidad = document.getElementById('txt_especialidad_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(especialidad.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/especialidad/controlador_modificar_especialidad.php",
    type:'POST',
    data:{
      id:id,
      especialidad:especialidad,
      descrip:descrip,
      esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
          tbl_especialidad.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La especialidad que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO TRAMITE
function Eliminar_especialidad(id){
    $.ajax({
      "url":"../controller/especialidad/controlador_eliminar_especialidad.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la especialidad con exito","success").then((value)=>{
            tbl_especialidad.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Error","No se pudo completar el proceso","error");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_especialidad').on('click','.delete',function(){
    var data = tbl_especialidad.row($(this).parents('tr')).data();
  
    if(tbl_especialidad.row(this).child.isShown()){
        var data = tbl_especialidad.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar la especialidad: '+data.Especialidad+'?',
      text: "Una vez aceptado la especialidad sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_especialidad(data.Id_especilidad);
      }
    })
  })