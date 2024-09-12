function Iniciar_Sesion(){
    recuerdame();
    let usu = document.getElementById("txt_usuario").value;
    let con = document.getElementById("txt_contra").value;
    if(usu.length==0 || con.length==0){
       return  Swal.fire({
        icon: 'warning',
        title: 'Mensaje de Advertencia',
        text: 'Llene todo los campos de la sesión',
        heightAuto: false
      });
    }
    $.ajax({
        url:'controller/usuario/controlador_iniciar_sesion.php',
        type: 'POST',
        data:{
            u:usu,
            c:con
        }
    }).done(function(resp){
       let data = JSON.parse(resp)
       if(data.length>0){
            if(data[0][8]=="INACTIVO"){
                return  Swal.fire({
                    icon: 'warning',
                    title: 'Mensaje de Advertencia',
                    text: 'El usuario: '+usu+' se encuentra inactivo',
                    heightAuto: false
                  });  
            }$.ajax({
                url:'controller/usuario/controlador_crear_sesion.php',
                type: 'POST',
                data:{
                    idusuario:data[0][4],
                    usuario:data[0][5],
                    nombres:data[0][0],
                    solonombres:data[0][2],
                    rol:data[0][15],   
                    foto:data[0][3],
                    movil:data[0][16],                     
                    direc:data[0][17],                     
                    fechanac:data[0][18],                     
                    email:data[0][7],                     
                    dni:data[0][19],                     

                }
            }).done(function(resp){
                let timerInterval
                Swal.fire({
                  title: 'Bienvenido al Sistema',
                  html: 'Seras redireccionado en <b></b> milliseconds.',
                  icon: 'success',
                  timer: 1200,
                  timerProgressBar: true,
                  heightAuto: false,
                  didOpen: () => {
                    Swal.showLoading()
                    const b = Swal.getHtmlContainer().querySelector('b')
                    timerInterval = setInterval(() => {
                      b.textContent = Swal.getTimerLeft()
                    }, 100)
                  },
                  willClose: () => {
                    clearInterval(timerInterval)
                  }
                }).then((result) => {
                  /* Read more about handling dismissals below */
                  if (result.dismiss === Swal.DismissReason.timer) {
                    location.reload();
                  }
                })            
            })
       }else{
        Swal.fire({
            icon: 'error',
            title: 'Mensaje de Error',
            text: 'Usuario o Contraseña Incorrectos',
            heightAuto: false
          });

       }
    })
}

function recuerdame(){
    if(rmcheck.checked && usuarioInput.value !="" && passInput.value !=""){
        localStorage.usuario     = usuarioInput.value;
        localStorage.pass        = passInput.value;
        localStorage.checkbox    = rmcheck.value;
    }else{
        localStorage.usuario     = "";
        localStorage.pass        = "";
        localStorage.checkbox    = "";
    }
}

var tbl_usuario;
function listar_usuario(){
  tbl_usuario = $("#tabla_usuario").DataTable({
    pagingType: 'full_numbers',
    scrollCollapse: true,
    responsive: true,
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
          "url":"../controller/usuario/controlador_listar_usuario.php",
          type:'POST'
      },
      dom: 'Bfrtip',       
    buttons:[ 
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE USUARIOS"
    },
      title: function() {
        return  "LISTA DE USUARIOS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE USUARIOS"
    },
  title: function() {
    return  "LISTA DE USUARIOS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE USUARIOS"

  }
  }],
      "columns":[
        {"defaultContent":""},
        {"data":"usu_usuario"},
        {"data":"tipo_rol"},
        {"data":"nombre_completo"},
        {"data":"usu_email"},
        {"data":"fecha_formateada"},

        {"data":"usu_estatus",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"data":"usu_estatus",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return "<button class='editar btn btn-primary btn-sm' title='Editar datos de usuario'><i class='fa fa-edit'></i></button>&nbsp;<button class='contra btn btn-warning btn-sm' title='Cambiar contraseña de usuario'><i class='fas fa-key'></i></button>&nbsp;<button class='btn btn-success btn-sm' disabled title='Activar usuario'><i class='fa fa-check-circle'></i></button>&nbsp;<button class='desactivar btn btn-danger btn-sm' title='Desactivar usuario'><i class='fa fa-times-circle'></i></button>";
                    }else{
                    return "<button class='editar btn btn-primary btn-sm' title='Editar datos de usuario'><i class='fa fa-edit'></i></button>&nbsp;<button class='contra btn btn-warning btn-sm' title='Cambiar contraseña de usuario'><i class='fas fa-key'></i></button>&nbsp;<button class='activar btn btn-success btn-sm' title='Activar usuario'><i class='fa fa-check-circle'></i></button>&nbsp;<button class='btn btn-danger btn-sm' disabled title='Desactivar usuario'><i class='fa fa-times-circle'></i></button>";
                    }
            }   
        }
    ],

    "language":idioma_espanol,
    select: true
});
tbl_usuario.on('draw.td',function(){
  var PageInfo = $("#tabla_usuario").DataTable().page.info();
  tbl_usuario.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}
$('#tabla_usuario').on('click','.editar',function(){
  var data = tbl_usuario.row($(this).parents('tr')).data();

  if(tbl_usuario.row(this).child.isShown()){
      var data = tbl_usuario.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_idusuario').value=data.usu_id;
  document.getElementById('txt_usu_editar').value=data.usu_usuario;
  $("#select_rol_editar").select2().val(data.rol_id).trigger('change.select2');
  document.getElementById('txt_correo_editar').value=data.usu_email;


})


$('#tabla_usuario').on('click','.contra',function(){
  var data = tbl_usuario.row($(this).parents('tr')).data();

  if(tbl_usuario.row(this).child.isShown()){
      var data = tbl_usuario.row(this).data();
  }
  $("#modal_contra").modal('show');
  document.getElementById('txt_idusuario_contra').value=data.usu_id;

})
$('#tabla_usuario').on('click','.desactivar',function(){
  var data = tbl_usuario.row($(this).parents('tr')).data();

  if(tbl_usuario.row(this).child.isShown()){
      var data = tbl_usuario.row(this).data();
  }
    Swal.fire({
      title: 'Desea desactivar al usuario '+data.usu_usuario+'?',
      text: "Una vez desactivado el usuario no tendra acceso al sistema",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Desactivar'
    }).then((result) => {
      if (result.isConfirmed) {
        Modificar_Estatus_Usuario(parseInt(data.usu_id),'INACTIVO',data.usu_usuario);
      }
    })

})


$('#tabla_usuario').on('click','.activar',function(){
  var data = tbl_usuario.row($(this).parents('tr')).data();

  if(tbl_usuario.row(this).child.isShown()){
      var data = tbl_usuario.row(this).data();
  }
    Swal.fire({
      title: 'Desea activar al usuario '+data.usu_usuario+'?',
      text: "Una vez activado el usuario tendra acceso al sistema",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Desactivar'
    }).then((result) => {
      if (result.isConfirmed) {
        Modificar_Estatus_Usuario(parseInt(data.usu_id),'ACTIVO',data.usu_usuario);
      }
    })

})


function Modificar_Estatus_Usuario(id,estatus,user){
  let esta=estatus;
  if(esta==="INACTIVO"){
    esta="Desactivo";
  }
  $.ajax({
    "url":"../controller/usuario/controlador_modificar_usuario_estatus.php",
    type:'POST',
    data:{
      id:id,
      estatus:estatus
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se "+esta+" con exito El Usuario "+user,"success").then((value)=>{
          tbl_usuario.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización","error");

    }
  })
}
function Registrar_Usuario(){
  let usu = document.getElementById('txt_usu').value;
  let con = document.getElementById('txt_con').value;
  let ide = document.getElementById('select_empleado').value;
  let ida = document.getElementById('select_area').value;
  let rol = document.getElementById('select_rol').value;

  if(usu.length==0 || con.length==0 || ide.length==0 || ida.length==0 || rol.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/usuario/controlador_registro_usuario.php",
    type:'POST',
    data:{
      usu:usu,
      con:con,
      ide:ide,
      ida:ida,
      rol:rol
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva Usuario Registrado","success").then((value)=>{
          tbl_usuario.ajax.reload();
          document.getElementById('txt_usu').value="";
          document.getElementById('txt_con').value="";
          document.getElementById('select_empleado').value="";
          document.getElementById('select_area').value="";
          document.getElementById('select_rol').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El Usuario ingresado ya se encuentra en la base de datos","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
function Modificar_Usuario(){
  let id = document.getElementById('txt_idusuario').value;
  let usu = document.getElementById('txt_usu_editar').value;
  let rol = document.getElementById('select_rol_editar').value;
  let correo = document.getElementById('txt_correo_editar').value;

  if(id.length==0 || usu.length==0 || correo.length==0 || rol.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/usuario/controlador_modificar_usuario.php",
    type:'POST',
    data:{
      id:id,
      usu:usu,
      rol:rol,
      correo:correo
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Datos del Usuario Actualizado","success").then((value)=>{
          tbl_usuario.ajax.reload();
        $("#modal_editar").modal('hide');
        });
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización","error");

    }
  })
}


function Modificar_Contra(){
  let id = document.getElementById('txt_idusuario_contra').value;
  let con = document.getElementById('txt_contra_nueva').value;

  if(id.length==0 || con.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/usuario/controlador_modificar_usuario_contra.php",
    type:'POST',
    data:{
      id:id,
      con:con
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Contraseña del Usuario Actualizada","success").then((value)=>{
          tbl_usuario.ajax.reload();
        $("#modal_contra").modal('hide');
        });
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización","error");

    }
  })
}

function Cargar_Select_Rol(){
  $.ajax({
    "url":"../controller/usuario/controlador_cargar_select_rol.php",
    type:'POST',
  }).done(function(resp){
    let data=JSON.parse(resp);
    if(data.length>0){
      let cadena ="";
      for (let i = 0; i < data.length; i++) {
        cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
      }
        document.getElementById('select_rol_editar').innerHTML=cadena;

    }else{
      cadena+="<option value=''>No hay empleado en la base de datos</option>";
      document.getElementById('select_rol_editar').innerHTML=cadena;

    }
  })
}


//SEGUIMIENTO TRAMITE //
function Traer_Datos_Seguimiento(){
  let numero= document.getElementById('txt_numero').value;
  let dni= document.getElementById('txt_dni').value;
  if(numero.length==0 || dni.length==0){
   return Swal.fire("Mensaje de Advertencia","Llene el N° de Documento y DNI para buscar el documento","warning");

  }
  $.ajax({
    "url":"controller/usuario/controlador_traer_seguimiento.php",
    type:'POST',
    data:{
      numero:numero,
      dni:dni
    }
  }).done(function(resp){
    let data=JSON.parse(resp);
    var cadena="";
    if(data.length>0){
      document.getElementById("div_buscador").style.display = "block";
      document.getElementById('lbl_titulo').innerHTML="<b>Seguimiento del Tramite: "+data[0][0]+" - "+data[0][2]+"</b>";
      cadena +='<div class="timeline">'+
      '<div class="time-label">'+
        '<span class="bg-red">'+data[0][4]+'</span>'+
      '</div>';
      //AJAX PARA EL DETALLE DEL SEGUIMIENTO//
      $.ajax({
        "url":"controller/usuario/controlador_traer_seguimiento_detalle.php",
        type:'POST',
        data:{
          codigo:data[0][0]
        }
      }).done(function(resp){
        let datadetalle=JSON.parse(resp);
        if(datadetalle.length>0){
          for (let i = 0; i < datadetalle.length; i++) {
            if(datadetalle[i][7]=="DERIVADO")
            {
            cadena+='<div>'+
            '<i class="fas fa-envelope bg-blue"></i>'+
            '<div class="timeline-item">'+
              '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
              '</span>'+
              '<h3 class="timeline-header" style="color:blue"><a href="#" style="color:BLUE">El documento fue DERIVADO al área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
              '<div class="timeline-body">'+
              datadetalle[i][6]+
              '</div>'+
            '</div>'+
          '</div>';
            }else if(datadetalle[i][7]=="RECHAZADO")
            {
            cadena+='<div>'+
            '<i class="fas fa-envelope bg-red"></i>'+
            '<div class="timeline-item">'+
              '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
              '</span>'+
              '<h3 class="timeline-header" style="color:red"><a href="#" style="color:red">El documento fue RECHAZADO en el área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
              '<div class="timeline-body">'+
              datadetalle[i][6]+
              '</div>'+
            '</div>'+
          '</div>';
            }else if(datadetalle[i][7]=="FINALIZADO")
            {
            cadena+='<div>'+
            '<i class="fas fa-envelope bg-success"></i>'+
            '<div class="timeline-item">'+
              '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
              '</span>'+
              '<h3 class="timeline-header" style="color:green"><a href="#" style="color:green">El documento fue FINALIZADO en el área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
              '<div class="timeline-body">'+
              datadetalle[i][6]+
              '</div>'+
            '</div>'+
          '</div>';
            }else{
              cadena+='<div>'+
            '<i class="fas fa-envelope bg-warning"></i>'+
            '<div class="timeline-item">'+
              '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
              '</span>'+
              '<h3 class="timeline-header" style="color:orange"><a href="#" style="color:orange">El documento se ENCUENTRA en el área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
              '<div class="timeline-body">'+
              datadetalle[i][6]+
              '</div>'+
            '</div>'+
          '</div>';
            }   
          }
          cadena+='</div>';
          document.getElementById("div_seguimiento").innerHTML=cadena;

        }
      })
      ////TERMINA EL AJAX//////////
    }else{
      document.getElementById("div_buscador").style.display = "none";
      return Swal.fire("Mensaje de Advertencia","No se encontraron datos del Documento Buscado","warning");

    }
  })
}

function Traer_Datos_Seguimiento2(){
  let numero= document.getElementById('txt_numero').value;
  let dni= document.getElementById('txt_dni').value;
  if(numero.length==0 || dni.length==0){
   return Swal.fire("Mensaje de Advertencia","Llene el N° de Documento y DNI para buscar el documento","warning");

  }
  $.ajax({
    "url":"../controller/usuario/controlador_traer_seguimiento.php",
    type:'POST',
    data:{
      numero:numero,
      dni:dni
    }
  }).done(function(resp){
    let data=JSON.parse(resp);
    var cadena="";
    if(data.length>0){
      document.getElementById("div_buscador").style.display = "block";
      document.getElementById('lbl_titulo').innerHTML="<b style='color:white'>Seguimiento del Tramite N°: "+data[0][0]+" - Remitente: "+data[0][2]+"</b>";
      cadena +='<div class="timeline">'+
      '<div class="time-label">'+
        '<span class="bg-red">'+data[0][4]+'</span>'+
      '</div>';
      //AJAX PARA EL DETALLE DEL SEGUIMIENTO//
      $.ajax({
        "url":"../controller/usuario/controlador_traer_seguimiento_detalle.php",
        type:'POST',
        data:{
          codigo:data[0][0]
        }
      }).done(function(resp){
        let datadetalle=JSON.parse(resp);
        if(datadetalle.length>0){
          for (let i = 0; i < datadetalle.length; i++) {
            if(datadetalle[i][7]=="DERIVADO")
            {
              cadena+='<div>'+
              '<i class="fas fa-arrow-right bg-blue"></i>'+
              '<div class="timeline-item">'+
                '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
                '</span>'+
                '<h3 class="timeline-header" style="color:blue"><a href="#" style="color:BLUE">El documento fue DERIVADO al área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
                '<div class="timeline-body">'+
                datadetalle[i][6]+
                '</div>'+
              '</div>'+
            '</div>';
              }else if(datadetalle[i][7]=="RECHAZADO")
              {
              cadena+='<div>'+
              '<i class="fas fa-times bg-red"></i>'+
              '<div class="timeline-item">'+
                '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
                '</span>'+
                '<h3 class="timeline-header" style="color:red"><a href="#" style="color:red">El documento fue RECHAZADO en el área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
                '<div class="timeline-body">'+
                datadetalle[i][6]+
                '</div>'+
              '</div>'+
            '</div>';
              }else if(datadetalle[i][7]=="FINALIZADO")
              {
              cadena+='<div>'+
              '<i class="fas fa-check bg-success"></i>'+
              '<div class="timeline-item">'+
                '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
                '</span>'+
                '<h3 class="timeline-header" style="color:green"><a href="#" style="color:green">El documento fue FINALIZADO en el área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
                '<div class="timeline-body">'+
                datadetalle[i][6]+
                '</div>'+
              '</div>'+
            '</div>';
              }else{
                cadena+='<div>'+
              '<i class="fas fa-check bg-warning"></i>'+
              '<div class="timeline-item">'+
                '<span class="time"><i class="fas fa-clock"></i>'+datadetalle[i][4]+
                '</span>'+
                '<h3 class="timeline-header" style="color:orange"><a href="#" style="color:orange">El documento se ENCUENTRA en el área de: '+datadetalle[i][3]+'</a> - <b>ESTADO: '+datadetalle[i][7]+'</b></h3>'+
                '<div class="timeline-body">'+
                datadetalle[i][6]+
                '</div>'+
              '</div>'+
            '</div>';
              }    
          }
          cadena+='</div>';
          document.getElementById("div_seguimiento").innerHTML=cadena;

        }
      })
      ////TERMINA EL AJAX//////////
    }else{
      document.getElementById("div_buscador").style.display = "none";
      return Swal.fire("Mensaje de Advertencia","No se encontraron datos del Documento Buscado","warning");

    }
  })
}



