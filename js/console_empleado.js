var tbl_empleado;
function listar_empleado(){
  tbl_empleado = $("#tabla_empleado").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      "destroy":true,
      "async": false ,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "processing": true,
      "ajax":{
          "url":"../controller/empleado/controlador_listar_empleado.php",
          type:'POST'
      },
      dom: 'Bfrtip',       
      buttons:[ 
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE EMPLEADOS"
      },
        title: function() {
          return  "LISTA DE EMPLEADOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE EMPLEADOS"
      },
    title: function() {
      return  "LISTA DE EMPLEADOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE EMPLEADOS"
  
    }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"empl_fotoperfil",
            render: function(data,type,row){
                return '<img src="../'+data+'" class="img img-responsive" style="width:40px">';
            }   
         },
        {"data":"emple_nrodocumento"},
        {"data":"empleado"},
        {"data":"emple_movil"},
        {"data":"emple_email"},
        {"data":"emple_direccion"},
        {"data":"emple_estatus",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos de empleado'><i class='fa fa-edit'></i> Editar</button>&nbsp;<button class='foto btn btn-warning btn-sm' title='Cambiar foto'><i class='fa fa-image'></i> Cambiar foto</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_empleado.on('draw.td',function(){
  var PageInfo = $("#tabla_empleado").DataTable().page.info();
  tbl_empleado.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
$('#tabla_empleado').on('click','.editar',function(){
  var data = tbl_empleado.row($(this).parents('tr')).data();

  if(tbl_empleado.row(this).child.isShown()){
      var data = tbl_empleado.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idempleado').value=data.empleado_id;
  document.getElementById('txt_nro_editar').value=data.emple_nrodocumento;
  document.getElementById('txt_nom_editar').value=data.emple_nombre;
  document.getElementById('txt_apepa_editar').value=data.emple_apepat;
  document.getElementById('txt_apema_editar').value=data.emple_apemat;
  document.getElementById('txt_nac_editar').value=data.emple_fechanacimiento;
  document.getElementById('txt_movil_editar').value=data.emple_movil;
  document.getElementById('txt_dire_editar').value=data.emple_direccion;
  document.getElementById('txt_email_editar').value=data.emple_email;
  document.getElementById('txt_estatus').value=data.emple_estatus;

})

function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

function Registrar_Empleado(){
  let nro = document.getElementById('txt_nro').value;
  let nom = document.getElementById('txt_nom').value;
  let apepa = document.getElementById('txt_apepa').value;
  let apema = document.getElementById('txt_apema').value;
  let fnac = document.getElementById('txt_nac').value;
  let movil = document.getElementById('txt_movil').value;
  let dire = document.getElementById('txt_dire').value;
  let email = document.getElementById('txt_email').value;

  if(nro.length==0 || nom.length==0 || apepa.length==0 || apema.length==0 || fnac.length==0 || movil.length==0 || dire.length==0 || email.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  if(validar_email(email)){

  }else{
    return Swal.fire("Mensaje de Advertencia","El formato de Email es incorrecto","warning");

  }
  $.ajax({
    "url":"../controller/empleado/controlador_registro_empleado.php",
    type:'POST',
    data:{
        nro:nro,
        nom:nom,
        apepa:apepa,
        apema:apema,
        fnac:fnac,
        movil:movil,
        dire:dire,
        email:email
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva Empleado registrado","success").then((value)=>{
          tbl_empleado.ajax.reload();
          document.getElementById('txt_nro').value="";
          document.getElementById('txt_nom').value="";
          document.getElementById('txt_apepa').value="";
          document.getElementById('txt_apema').value="";
          document.getElementById('txt_nac').value="";
          document.getElementById('txt_movil').value="";
          document.getElementById('txt_dire').value="";
          document.getElementById('txt_email').value="";

        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El DNI ingresado ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
function Modificar_Empleado(){
  let id = document.getElementById('txt_idempleado').value;
  let nro = document.getElementById('txt_nro_editar').value;
  let nom = document.getElementById('txt_nom_editar').value;
  let apepa = document.getElementById('txt_apepa_editar').value;
  let apema = document.getElementById('txt_apema_editar').value;
  let fnac = document.getElementById('txt_nac_editar').value;
  let movil = document.getElementById('txt_movil_editar').value;
  let dire = document.getElementById('txt_dire_editar').value;
  let email = document.getElementById('txt_email_editar').value;
  let esta = document.getElementById('txt_estatus').value;

  if(id.length==0 || nro.length==0 || nom.length==0 || apepa.length==0 || apema.length==0 || fnac.length==0 || movil.length==0 || dire.length==0 || email.length==0 || esta.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  if(validar_email(email)){

  }else{
    return Swal.fire("Mensaje de Advertencia","El formato de Email es incorrecto","warning");

  }
  $.ajax({
    "url":"../controller/empleado/controlador_modificar_empleado.php",
    type:'POST',
    data:{
        id:id,
        nro:nro,
        nom:nom,
        apepa:apepa,
        apema:apema,
        fnac:fnac,
        movil:movil,
        dire:dire,
        email:email,
        esta:esta
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos del Empleado Actualizado","success").then((value)=>{
          tbl_empleado.ajax.reload();
        $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El DNI ingresado ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el proceso","error");

    }
  })
}
///////VALIDAR EMAIL
function validar_email(email) {
  var regex = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email) ? true : false;
}
function Total_empleados(){
  $.ajax({
      "url":"../controller/empleado/controlador_total_empleados.php",
      type:'POST'
      }).done(function(resp){
      var data = JSON.parse(resp);
      var cadena="";
      if(data.length>0){
        $("#total_empleados").html(data[0][0]);
      }
      else{
          return Swal.fire("Mensaje de Error","No se pudo traer los empleados","error");
      }
  })
}
$('#tabla_empleado').on('click','.foto',function(){
  var data = tbl_empleado.row($(this).parents('tr')).data();

  if(tbl_empleado.row(this).child.isShown()){
      var data = tbl_empleado.row(this).data();
  }
  $("#modal_editar_foto").modal('show');
  document.getElementById('txt_idmpleado_foto').value=data.empleado_id;
  document.getElementById('lb_usuario').innerHTML=data.emple_nombre;
  document.getElementById('fotoactual').value=data.empl_fotoperfil;

})
function Modificar_Foto_Empleado(){
  let id = document.getElementById("txt_idmpleado_foto").value
  let foto = document.getElementById("txt_foto").value
  let fotoactual = document.getElementById("fotoactual").value

  if(id.length==0 || foto.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
}

    let extension = foto.split('.').pop();
    let nombrefoto="";
    let f = new Date();
    if(foto.length>0){
      nombrefoto="IMG"+f.getDate()+"-"+(f.getMonth()+1)+"-"+f.getFullYear()+"-"+f.getHours()+"-"+f.getMilliseconds()+"."+extension;
    }
    let formData = new FormData();
    let fotoobj = $("#txt_foto")[0].files[0];

    formData.append("id",id);
    formData.append("nombrefoto",nombrefoto);
    formData.append("fotoactual",fotoactual);
    formData.append("foto",fotoobj);
    $.ajax({
      url:"../controller/empleado/controlador_empleado_modificar_foto.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
          Swal.fire("Mensaje de Confirmación","Foto actualizada","success").then((value)=>{
            $("#modal_editar_foto").modal('hide');
            tbl_empleado.ajax.reload();
            document.getElementById('txt_foto').value="";

          });
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo actualizar la foto","warning");
        }
      }
    });
}
