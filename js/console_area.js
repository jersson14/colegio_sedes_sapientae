var tbl_area;
function listar_area(){
  tbl_area = $("#tabla_area").DataTable({
      "ordering":true,   
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
          "url":"../controller/area/controlador_listar_area.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE ÁREAS"
      },
        title: function() {
          return  "LISTA DE ÁREAS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE ÁREAS"
      },
    title: function() {
      return  "LISTA DE ÁREAS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE ÁREAS"
  
    }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"area_nombre"},
        {"data":"fecha_formateada"},
        {"data":"area_estado",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos de área'><i class='fa fa-edit'></i> Editar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_area.on('draw.td',function(){
  var PageInfo = $("#tabla_area").DataTable().page.info();
  tbl_area.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
$('#tabla_area').on('click','.editar',function(){
  var data = tbl_area.row($(this).parents('tr')).data();

  if(tbl_area.row(this).child.isShown()){
      var data = tbl_area.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_area_editar').value=data.area_nombre;
  document.getElementById('txt_idarea').value=data.area_cod;
  document.getElementById('txt_estatus').value=data.area_estado;
})

function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

function Registrar_Area(){
  let area = document.getElementById('txt_area').value;
  if(area.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/area/controlador_registro_area.php",
    type:'POST',
    data:{
      a:area
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva Área registrada","success").then((value)=>{
          tbl_area.ajax.reload();
          document.getElementById('txt_area').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El área ingresada ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
function Modificar_Area(){
  let id = document.getElementById('txt_idarea').value;
  let area = document.getElementById('txt_area_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(area.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/area/controlador_modificar_area.php",
    type:'POST',
    data:{
      id:id,
      are:area,
      esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados","success").then((value)=>{
          tbl_area.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El área ingresada ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización","error");

    }
  })
}