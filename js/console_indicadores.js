//LISTADO DE NIVEL ACADEMICO
var tbl_indicadores;
function listar_nivel_academico(){
    tbl_indicadores = $("#tabla_indicadores").DataTable({
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
          "url":"../controller/indicadores/controlador_listar_indicadores.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE INDICADORES"
      },
        title: function() {
          return  "LISTA DE INDICADORES" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE INDICADORES"
      },
    title: function() {
      return  "LISTA DE INDICADORES"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE INDICADORES"
  
    }
    }],
      "columns":[
        {"data":"id_indicadores"},
        {"data":"tipo_indicador",
            render: function(data,type,row){
                if(data=='GASTOS'){
                return '<span class="badge bg-danger">GASTOS</span>';
                }else{
                return '<span class="badge bg-success">INGRESOS</span>';
                }
        }   
        },
        {"data":"nombre"},
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
tbl_indicadores.on('draw.td',function(){
  var PageInfo = $("#tabla_indicadores").DataTable().page.info();
  tbl_indicadores.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

//ENVIANDO DATOS PARA EDITAR
$('#tabla_indicadores').on('click','.editar',function(){
  var data = tbl_indicadores.row($(this).parents('tr')).data();

  if(tbl_indicadores.row(this).child.isShown()){
      var data = tbl_indicadores.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('id_indiciador').value=data.id_indicadores;
  document.getElementById('txt_tipo_editar').value=data.tipo_indicador;
  document.getElementById('txt_nombre_editar').value=data.nombre;
  document.getElementById('txt_descripcion_editar').value=data.descripcion;
  document.getElementById('txt_estatus').value=data.estado;

})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO NIVEL ACADEMICO
function Registrar_indicador(){
  let tipo = document.getElementById('txt_tipo').value;
  let nombre = document.getElementById('txt_nombre').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(tipo.length==0||nombre.length==0){
      return Swal.fire("Mensaje de Advertencia","El tipo y nombre son obligatorio llenar","warning");
  }
  $.ajax({
    "url":"../controller/indicadores/controlador_registro_indicadores.php",
    type:'POST',
    data:{
        tipo:tipo,
        nombre:nombre,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nuevo indicador registrado satisfactoriamente!!!","success").then((value)=>{
        tbl_indicadores.ajax.reload();
          document.getElementById('txt_nombre').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El indicador que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO INDICADOR
function Modificar_Indicador(){
  let id = document.getElementById('id_indiciador').value;
  let tipo = document.getElementById('txt_tipo_editar').value;
  let nombre = document.getElementById('txt_nombre_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(tipo.length==0 || id.length==0||nombre.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/indicadores/controlador_modificar_indicador.php",
    type:'POST',
    data:{
      id:id,
      tipo:tipo,
      nombre:nombre,
      descrip:descrip,
      esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_indicadores.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El indicador que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Indicador(id){
    $.ajax({
      "url":"../controller/indicadores/controlador_eliminar_indicador.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el indicador con éxito","success").then((value)=>{
            tbl_indicadores.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advertencia","No se puede eliminar este indicador por que esta siendo utilizado en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_indicadores').on('click','.delete',function(){
    var data = tbl_indicadores.row($(this).parents('tr')).data();
  
    if(tbl_indicadores.row(this).child.isShown()){
        var data = tbl_indicadores.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el indicador: '+data.nombre+'?',
      text: "Una vez aceptado el indicador sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Indicador(data.id_indicadores);
      }
    })
  })