//LISTADO DE NIVEL ACADEMICO
var tbl_nivel_academico;
function listar_nivel_academico(){
    tbl_nivel_academico = $("#tabla_nivel_academico").DataTable({
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
          "url":"../controller/nivel_academico/controlador_listar_nivel_academico.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE NIVELES ACADEMICOS"
      },
        title: function() {
          return  "LISTA DE NIVELES ACADEMICOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE NIVELES ACADEMICOS"
      },
    title: function() {
      return  "LISTA DE NIVELES ACADEMICOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE NIVELES ACADEMICOS"
  
    }
    }],
      "columns":[
        {"data":"Id_nivel"},
        {"data":"Nivel_academico"},
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
tbl_nivel_academico.on('draw.td',function(){
  var PageInfo = $("#tabla_nivel_academico").DataTable().page.info();
  tbl_nivel_academico.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

//ENVIANDO DATOS PARA EDITAR
$('#tabla_nivel_academico').on('click','.editar',function(){
  var data = tbl_nivel_academico.row($(this).parents('tr')).data();

  if(tbl_nivel_academico.row(this).child.isShown()){
      var data = tbl_nivel_academico.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idnivelaca').value=data.Id_nivel;
  document.getElementById('txt_nivel_aca_editar').value=data.Nivel_academico;
  document.getElementById('txt_descripcion_editar').value=data.descripcion;
  document.getElementById('txt_estatus').value=data.estado;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO NIVEL ACADEMICO
function Registrar_nivel_academico(){
  let nivel = document.getElementById('txt_nivel_academico').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(nivel.length==0){
      return Swal.fire("Mensaje de Advertencia","El nivel académico debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/nivel_academico/controlador_registro_nivel_aca.php",
    type:'POST',
    data:{
        nivel:nivel,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nuevo nivel académico registrado satisfactoriamente!!!","success").then((value)=>{
        tbl_nivel_academico.ajax.reload();
          document.getElementById('txt_nivel_academico').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El nivel académico que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Rol(){
  let id = document.getElementById('txt_idnivelaca').value;
  let nivel = document.getElementById('txt_nivel_aca_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(nivel.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/nivel_academico/controlador_modificar_niveaca.php",
    type:'POST',
    data:{
      id:id,
      nivel:nivel,
      descrip:descrip,
      esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_nivel_academico.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El nivel académico que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Rol(id){
    $.ajax({
      "url":"../controller/nivel_academico/controlador_eliminar_nivel_academico.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el nivel académico con exito","success").then((value)=>{
            tbl_nivel_academico.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advertencia","No se puede eliminar este nivel académico por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_nivel_academico').on('click','.delete',function(){
    var data = tbl_nivel_academico.row($(this).parents('tr')).data();
  
    if(tbl_nivel_academico.row(this).child.isShown()){
        var data = tbl_nivel_academico.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el nivel académico: '+data.Nivel_academico+'?',
      text: "Una vez aceptado el nivel académico sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Rol(data.Id_nivel);
      }
    })
  })