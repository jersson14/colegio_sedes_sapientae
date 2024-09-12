var tbl_tipodocumento;
function listar_tipodocumento(){
  tbl_tipodocumento = $("#tabla_tipo").DataTable({
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
          "url":"../controller/tipo_documento/controlador_listar_tipo.php",
          type:'POST'
      },
      dom: 'Bfrtip',       
      buttons:[ 
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE TIPO DE DOCUMENTOS"
      },
        title: function() {
          return  "LISTA DE TIPO DE DOCUMENTOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE TIPO DE DOCUMENTOS"
      },
    title: function() {
      return  "LISTA DE TIPO DE DOCUMENTOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE TIPO DE DOCUMENTOS"
  
    }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"tipodo_descripcion"},
        {"data":"requisitos"},
        {"data":"fecha_tipo"},
        {"data":"tipodo_estado",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"defaultContent":"<button class='editar btn btn-primary btn-sm' title='Editar datos de tipo de documento'><i class='fa fa-edit'></i> Editar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_tipodocumento.on('draw.td',function(){
  var PageInfo = $("#tabla_tipo").DataTable().page.info();
  tbl_tipodocumento.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
$('#tabla_tipo').on('click','.editar',function(){
  var data = tbl_tipodocumento.row($(this).parents('tr')).data();

  if(tbl_tipodocumento.row(this).child.isShown()){
      var data = tbl_tipodocumento.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_tipo_editar').value=data.tipodo_descripcion;
  document.getElementById('txt_idtipo').value=data.tipodocumento_id;
  document.getElementById('txt_estatus').value=data.tipodo_estado;
  document.getElementById('txt_requisitos_editar').value=data.requisitos;

})

function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

function Registrar_Tipo_Documento(){
  let tipodoc = document.getElementById('txt_tipo').value;
  let requisi = document.getElementById('txt_requisitos').value;
  if(tipodoc.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/tipo_documento/controlador_registro_tipo_documento.php",
    type:'POST',
    data:{
      tipodoc:tipodoc,
      requisi:requisi
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva Tipo de Documento registrado","success").then((value)=>{
          tbl_tipodocumento.ajax.reload();
          document.getElementById('txt_tipo').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El tipo de documento ingresado ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
function Modificar_Tipo_Documento(){
  let id = document.getElementById('txt_idtipo').value;
  let tipo = document.getElementById('txt_tipo_editar').value;
  let esta = document.getElementById('txt_estatus').value;
  let requisi = document.getElementById('txt_requisitos_editar').value;

  if(tipo.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/tipo_documento/controlador_modificar_tipo.php",
    type:'POST',
    data:{
      id:id,
      tipo:tipo,
      esta:esta,
      requisi:requisi
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados","success").then((value)=>{
          tbl_tipodocumento.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El tipo de documento ingresado ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización","error");

    }
  })
}