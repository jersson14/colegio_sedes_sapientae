//LISTADO DE ROLES
var tbl_año_escolar;
function listar_año_escolar(){
    tbl_año_escolar = $("#tabla_año_escolar").DataTable({
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
          "url":"../controller/año_escolar/controlador_listar_año_escolar.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE AÑO ESCOLAR"
      },
        title: function() {
          return  "LISTA DE AÑO ESCOLAR" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE AÑO ESCOLAR"
      },
    title: function() {
      return  "LISTA DE AÑO ESCOLAR"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE AÑO ESCOLAR"
  
    }
    }],
      "columns":[
        {"data":"Id_año_escolar"},
        {"data":"año_escolar"},
        {"data":"Nombre_año"},
        {"data":"fecha_ini"},
        {"data":"fecha_fi"},
        {"data":"descripcion"},
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
tbl_año_escolar.on('draw.td',function(){
  var PageInfo = $("#tabla_año_escolar").DataTable().page.info();
  tbl_año_escolar.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
//ENVIANDO DATOS PARA EDITAR
$('#tabla_año_escolar').on('click','.editar',function(){
  var data = tbl_año_escolar.row($(this).parents('tr')).data();

  if(tbl_año_escolar.row(this).child.isShown()){
      var data = tbl_año_escolar.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idaño').value=data.Id_año_escolar;
  document.getElementById('txt_año_editar').value=data.año_escolar;
  document.getElementById('txt_nombre_editar').value=data.Nombre_año;
  document.getElementById('txt_inicio_editar').value=data.fecha_inicio;
  document.getElementById('txt_fin_editar').value=data.fecha_fin;
  document.getElementById('txt_descripcion_editar').value=data.descripcion;
  document.getElementById('txt_estatus').value=data.estado;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_Año(){
  let año = document.getElementById('txt_año').value;
  let nombre = document.getElementById('txt_nombre').value;
  let inicio = document.getElementById('txt_inicio').value;
  let fin = document.getElementById('txt_fin').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(año.length==0 || nombre.length==0 || inicio.length ==0 || fin.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/año_escolar/controlador_registro_año_escolar.php",
    type:'POST',
    data:{
        año:año,
        nombre:nombre,
        inicio:inicio,
        fin:fin,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nuevo año academico registrado satisfactoriamente!!!","success").then((value)=>{
        tbl_año_escolar.ajax.reload();
          document.getElementById('txt_año').value="";
          document.getElementById('txt_nombre').value="";
          document.getElementById('txt_inicio').value="";
          document.getElementById('txt_fin').value="";
          document.getElementById('txt_descripcion').value="";

        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El año que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Año(){
  let id = document.getElementById('txt_idaño').value;
  let año = document.getElementById('txt_año_editar').value;
  let nombre = document.getElementById('txt_nombre_editar').value;
  let inicio = document.getElementById('txt_inicio_editar').value;
  let fin = document.getElementById('txt_fin_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(id.length==0||año.length==0 || nombre.length==0 || inicio.length ==0 || fin.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/año_escolar/controlador_modificar_año_escolar.php",
    type:'POST',
    data:{
        id:id,
        año:año,
        nombre:nombre,
        inicio:inicio,
        fin:fin,
        descrip:descrip,
        esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_año_escolar.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El año acádemico que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Año(id){
    $.ajax({
      "url":"../controller/año_escolar/controlador_eliminar_año.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el año escolar con exito","success").then((value)=>{
            tbl_año_escolar.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Error","No se pudo completar el proceso","error");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_año_escolar').on('click','.delete',function(){
    var data = tbl_año_escolar.row($(this).parents('tr')).data();
  
    if(tbl_año_escolar.row(this).child.isShown()){
        var data = tbl_año_escolar.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el año escolar: '+data.año_escolar+'?',
      text: "Una vez aceptado el año escolar sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Año(data.Id_año_escolar);
      }
    })
  })