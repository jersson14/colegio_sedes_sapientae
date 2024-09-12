var tbl_tramite;
function listar_tramite(){
  tbl_tramite = $("#tabla_tramite").DataTable({
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
          "url":"../controller/tramite/controlador_listar_tramite.php",
          type:'POST'
      },
      dom: 'Bfrtip',       
      buttons:[ 
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
      },
        title: function() {
          return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      orientation: 'landscape',
      pageSize: 'LEGAL',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
      },
    title: function() {
      return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
  
    }
    }],
    "columns":[
      {"data":"documento_id"},
      {"data":"doc_nrodocumento"},
      {"data":"tipodo_descripcion"},
      {"data":"doc_dniremitente"},
      {"data":"REMITENTE"},
      {"data":"fecha_formateada"},
      {"data":"doc_asunto"},
      {"data":"origen"},
      {"data":"destino"},
      {"data":"doc_estatus",
      render: function(data,type,row){
              if(data=='PENDIENTE'){
                  return '<span class="badge bg-warning">PENDIENTE</span>';
              }else if(data=='RECHAZADO'){
                  return '<span class="badge bg-danger">RECHAZADO</span>';
              }else if(data=='ACEPTADO'){
                  return '<span class="badge bg-success">ACEPTADO</span>';
              }else if(data=='FINALIZADO'){
                return '<span class="badge bg-primary">FINALIZADO</span>';
            }
          }
           
      },
        
   
  ],

    "language":idioma_espanol,
    select: true
});
tbl_tramite.on('draw.td',function(){
  var PageInfo = $("#tabla_tramite").DataTable().page.info();
  tbl_tramite.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

function listar_fechas_busqueda(){
    let fechainicio = document.getElementById('txtfechainicio').value;
    let fechafin = document.getElementById('txtfechafin').value;
    let tipodoc = document.getElementById('select_tipo').value;

  tbl_tramite = $("#tabla_tramite").DataTable({
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
          "url":"../controller/tramite/controlador_listar_tramite_fecha_tipodoc.php",
          type:'POST',
          data:{
            fechainicio:fechainicio,
            fechafin:fechafin,
            tipodoc:tipodoc
          }
      },
      dom: 'Bfrtip',       
      buttons:[ 
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
      },
        title: function() {
          return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      orientation: 'landscape',
      pageSize: 'LEGAL',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
      },
    title: function() {
      return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE DOCUMENTOS POR FECHA Y  TIPO DE DOCUMENTO"
  
    }
    }],
      "columns":[
        {"data":"documento_id"},
        {"data":"doc_nrodocumento"},
        {"data":"tipodo_descripcion"},
        {"data":"doc_dniremitente"},
        {"data":"REMITENTE"},
        {"data":"fecha_formateada"},
        {"data":"doc_asunto"},
        {"data":"origen"},
        {"data":"destino"},
        {"data":"doc_estatus",
        render: function(data,type,row){
                if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                }else if(data=='RECHAZADO'){
                    return '<span class="badge bg-danger">RECHAZADO</span>';
                }else if(data=='ACEPTADO'){
                    return '<span class="badge bg-success">ACEPTADO</span>';
                }else if(data=='FINALIZADO'){
                  return '<span class="badge bg-primary">FINALIZADO</span>';
              }
            }
             
        },
           
     
    ],

    "language":idioma_espanol,
    select: true
});
tbl_tramite.on('draw.td',function(){
  var PageInfo = $("#tabla_tramite").DataTable().page.info();
  tbl_tramite.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
$('#tabla_tramite').on('click','.delete',function(){
  var data = tbl_tramite.row($(this).parents('tr')).data();

  if(tbl_tramite.row(this).child.isShown()){
      var data = tbl_tramite.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar el tramite con código '+data.documento_id+'?',
    text: "Una vez aceptado el tramite sera eliminado!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_Tramite(data.documento_id);
    }
  })
})
$('#tabla_tramite').on('click','.editar',function(){
  var data = tbl_tramite.row($(this).parents('tr')).data();

  if(tbl_tramite.row(this).child.isShown()){
      var data = tbl_tramite.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_area_editar').value=data.area_nombre;
  document.getElementById('txt_idarea').value=data.area_cod;
  document.getElementById('txt_estatus').value=data.area_estado;
})
$('#tabla_tramite').on('click','.seguimiento',function(){
  var data = tbl_tramite.row($(this).parents('tr')).data();

  if(tbl_tramite.row(this).child.isShown()){
      var data = tbl_tramite.row(this).data();
  }
$("#modal_seguimiento").modal('show');
  document.getElementById('lb_titulo').innerHTML="SEGUIMIENTO DE TRAMITE Nº: "+data.documento_id;
  listar_seguimiento_tramite(data.documento_id);
})
$('#tabla_tramite').on('click','.mas',function(){
  var data = tbl_tramite.row($(this).parents('tr')).data();

  if(tbl_tramite.row(this).child.isShown()){
      var data = tbl_tramite.row(this).data();
  }
$("#modal_mas").modal('show');
document.getElementById('txt_ndocumento').value=data.doc_nrodocumento;
document.getElementById('txt_folio').value=data.doc_folio;
document.getElementById('txt_asunto').value=data.doc_asunto;
document.getElementById('lb_titulo_datos').innerHTML="DATOS DEL EXPEDIENTE Nº: "+data.doc_nrodocumento;
$("#select_area_p").select2().val(data.area_origen).trigger('change.select2');
$("#select_area_d").select2().val(data.area_destino).trigger('change.select2');
$("#select_tipo").select2().val(data.tipodocumento_id).trigger('change.select2');


document.getElementById('txt_dni').value=data.doc_dniremitente;
document.getElementById('txt_nom').value=data.doc_nombreremitente;
document.getElementById('txt_apepat').value=data.doc_apepatremitente;
document.getElementById('txt_apemat').value=data.doc_apematremitente;
document.getElementById('txt_celular').value=data.doc_celularremitente;
document.getElementById('txt_email').value=data.doc_emailremitente;
document.getElementById('txt_dire').value=data.doc_direccionremitente;
document.getElementById('txt_dire').value=data.doc_direccionremitente;
if(data.doc_representacion=="A Nombre Propio"){
  $("#rad_presentacion1").prop('checked',true);
}
if(data.doc_representacion=="A Otra Persona Natural"){
  $("#rad_presentacion2").prop('checked',true);
}
if(data.doc_representacion=="Persona Jurídica")
  $("#rad_presentacion3").prop('checked',true);
}
)
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
          tbl_tramite.ajax.reload();
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
          tbl_tramite.ajax.reload();
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
function Cargar_Select_Area(){
    $.ajax({
      "url":"../controller/usuario/controlador_cargar_select_area.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="<option value=''>Seleccionar Área</option>";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
          document.getElementById('select_area_p').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay áreas disponibles</option>";
        document.getElementById('select_area_p').innerHTML=cadena;
        
      }
    })
  }

  function Cargar_Select_Tipo(){
    $.ajax({
      "url":"../controller/tramite/controlador_cargar_select_tipo.php",
          type:'POST'
    }).done(function(resp){
        var data = JSON.parse(resp);
        var cadena="";
        if(data.length>0){
            for(var i=0; i < data.length; i++){
                cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";
            }
            $('#select_tipo').html(cadena);
            var id =$("#select_tipo").val();
  
            Traerrequisitotipodoc(id);
            
        }
        else{
            cadena+="<option value=''>No se encontraron regitros</option>";
            $('#select_tipo').html(cadena);
        }
    })
  }
  function Traerrequisitotipodoc(idrequisito){
    $.ajax({
      "url":"../controller/tramite/controlador_traerrequisito.php",
      type:'POST',
          data:{
            id:idrequisito
          }
        }).done(function(resp){
        var data = JSON.parse(resp);
        var cadena="";
        if(data.length>0){
          $("#txt_requisitos").val(data[0][1]);
        }
        else{
            return Swal.fire("Mensaje de Error","No se pudo traer el requisito","error");
        }
    })
  }

function Registrar_Tramite(){
  //DATOS DEL REMITENTE
    let dni = document.getElementById('txt_dni').value;
    let nom = document.getElementById('txt_nom').value;
    let apt = document.getElementById('txt_apepat').value;
    let apm = document.getElementById('txt_apemat').value;
    let cel = document.getElementById('txt_celular').value;
    let ema = document.getElementById('txt_email').value;
    let dir = document.getElementById('txt_dire').value;
    let idusu = document.getElementById('txtprincipalid').value;

    let presentacion = document.getElementsByName("r1");
    let vpresentacion ="";
    for (let i = 0; i < presentacion.length; i++) {
      if(presentacion[i].checked){
        vpresentacion = presentacion[i].value;
      }
      
    }
    let ruc = document.getElementById('txt_ruc').value;
    let raz = document.getElementById('txt_razon').value;

  //DATOS DEL DOCUMENTO
    let arp = document.getElementById('select_area_p').value;
    let ard = document.getElementById('select_area_d').value;
    let tip = document.getElementById('select_tipo').value;
    let ndo = document.getElementById('txt_ndocumento').value;
    let asu = document.getElementById('txt_asunto').value;
    let arc = document.getElementById('txt_archivo').value;
    let fol = document.getElementById('txt_folio').value;
    if(arc.length==0){
      return Swal.fire("Mensaje de Advertencia","Seleccione algún tipo de documento","warning")
    }

    let extension = arc.split('.').pop();//DOCUMENTO.PPT
    let nombrearchivo="";
    let f = new Date();
    
    if(arc.length>0){
      nombrearchivo="ARCH"+f.getDate()+""+(f.getMonth()+1)+""+f.getFullYear()+""+f.getHours()+""+f.getMilliseconds()+"."+extension;
    }
    if(dni.length==0 || nom.length==0 ||apt.length==0 ||  apm.length==0 || cel.length==0 || ema.length==0 ||
      dir.length==0 ){
        return Swal.fire("Mensaje de Advertencia","Llene todo los campos del remitente","warning")
      }
    if(arp.length==0 || ard.length==0 ||tip.length==0 ||  ndo.length==0 || asu.length==0 || fol.length==0){
        return Swal.fire("Mensaje de Advertencia","Llene todo los campos del documento","warning")
    }

    let formData = new FormData();
    let achivoobj = $("#txt_archivo")[0].files[0];//El objeto del archivo adjuntado

    //////DATOS DEL REMITENTE/////
    formData.append("dni",dni);
    formData.append("nom",nom);
    formData.append("apt",apt);
    formData.append("apm",apm);
    formData.append("cel",cel);
    formData.append("ema",ema);
    formData.append("dir",dir);
    formData.append("vpresentacion",vpresentacion);
    formData.append("ruc",ruc);
    formData.append("raz",raz);
    ///////DATOS DEL DOCUMENTO//////
    formData.append("arp",arp);
    formData.append("ard",ard);
    formData.append("tip",tip);
    formData.append("ndo",ndo);
    formData.append("asu",asu);
    formData.append("nombrearchivo",nombrearchivo);
    formData.append("fol",fol);
    formData.append("achivoobj",achivoobj);
    formData.append("idusu",idusu);

    $.ajax({
      url:"../controller/tramite/controlador_registro_tramite.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
          Swal.fire("Mensaje de Confirmación","Nueva Tramite Registrado código: "+resp,"success").then((value)=>{
            window.open("MPDF/REPORTE/ticket_tramite.php?codigo="+resp+"#zomm=100");
            $("#contenido_principal").load("tramite/view_tramite.php");
            document.getElementById('txt_dni').value="";
            document.getElementById('txt_nom').value="";
            document.getElementById('txt_apepat').value="";
            document.getElementById('txt_apemat').value="";
            ocument.getElementById('txt_celular').value="";
            ldocument.getElementById('txt_email').value="";
            document.getElementById('txt_dire').value="";
            ruc = document.getElementById('txt_ruc').value;
            document.getElementById('txt_razon').value="";
  //DATOS DEL REMITENTE
            document.getElementById('select_area_p').value="";
            document.getElementById('select_area_d').value="";
            document.getElementById('select_tipo').value="";
            document.getElementById('txt_ndocumento').value="";
            document.getElementById('txt_asunto').value="";
            document.getElementById('txt_folio').value="";
          });
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo realizar el","warning");
        }
      }
    });
    return false;
}

//SEGUIMIENTO TRAMITE
var tbl_seguimiento;
function listar_seguimiento_tramite(id){
  tbl_seguimiento = $("#tabla_seguimiento").DataTable({
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
          "url":"../controller/tramite/controlador_listar_tabla_seguimiento.php",
          type:'POST',
          data:{
            id:id
          }
      },
      "columns":[
        {"data":"area_nombre"},
        {"data":"fecha_formateada"},
        {"data":"mov_descripcion"},
        {"data":"mov_estatus",
        render: function(data,type,row){
                if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                }else if(data=='RECHAZADO'){
                    return '<span class="badge bg-danger">RECHAZADO</span>';
                }else if(data=='ACEPTADO'){
                    return '<span class="badge bg-success">ACEPTADO</span>';
                }else if(data=='FINALIZADO'){
                  return '<span class="badge bg-primary">FINALIZADO</span>';
                }else if(data=='DERIVADO'){
                  return '<span class="badge bg-dark">DERIVADO</span>';
                }
            
            }
             
        },
        {"data":"mov_archivo",
        render: function(data,type,row){
          if(data==''){
            return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
        }else{
          return "<a class='btn btn-primary btn-sm' href='../"+data+"' target='_blank' title='Ver archivo'><i class='fas fa-file-download'></i></a>";
        }
            }   
        },         
    ],

    "language":idioma_espanol,
    select: true
});
}
