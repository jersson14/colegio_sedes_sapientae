//LISTADO DE ROLES
var tbl_tareas;
function listar_tareas_id(){
    let año = document.getElementById('select_año').value;
    let grado = document.getElementById('select_aula').value;
    let id = document.getElementById('txtprincipalid').value;


    tbl_tareas = $("#tabla_tarea").DataTable({
        
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
          "url":"../controller/tareas/controlador_listar_tareas_profesor.php",
          type:'POST',
          data:{
            año:año,
            grado:grado,
            id:id
        }
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE TAREAS"
      },
        title: function() {
          return  "LISTA DE TAREAS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE TAREAS"
      },
    title: function() {
      return  "LISTA DE TAREAS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE TAREAS"
  
    }
    }],
      "columns":[
        {"data":"id_tarea"},
        {"data":"Grado"},
        {"data":"Docente"},
        {"data":"tema"},
        {"data":"descripcion"},
        {"data":"fecha_publicacion"},
        {"data":"fecha_entrega2"},
        {"data":"archivo_tarea",
          render: function(data,type,row){
                  if(data==''){
                      return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
                  }else{
                    return "<a class='btn btn-success btn-sm' href='../controller/tareas/"+data+"' target='_blank' title='Ver archivo'><i class='fas fa-file-download'></i> Descargar tarea</a>";
                  }
              }   
          },    
        {
            "defaultContent": "<button class='mostrar btn btn-primary btn-sm' title='Ver tarea realizada'><i class='fa fa-check'></i> Calificar tarea</button>"
        },

        {"data":"ESTADO",
            render: function(data,type,row){
                    if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                    }else{
                    return '<span class="badge bg-success">FINALIZADO</span>';
                    }
            }   
        },
        {"data":"ESTADO",
            render: function (data, type, row ) {
              if(data=='PENDIENTE'){
                  return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button class='editar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
              }else if(data=='FINALIZADO'){
                return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;' disabled  title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button disabled class='editar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
            }
            }
          },        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_tareas.on('draw.td',function(){
  var PageInfo = $("#tabla_tarea").DataTable().page.info();
  tbl_tareas.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}


function listar_tareas(){
    let id = document.getElementById('txtprincipalid').value;

    tbl_tareas = $("#tabla_tarea").DataTable({
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
          "url":"../controller/tareas/controlador_listar_tareas_profesor_solo.php",
          type:'POST',
          data:{
            id:id
        }
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE TAREAS"
      },
        title: function() {
          return  "LISTA DE TAREAS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE TAREAS"
      },
    title: function() {
      return  "LISTA DE TAREAS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE TAREAS"
  
    }
    }],
      "columns":[
        {"data":"id_tarea"},
        {"data":"Grado"},
        {"data":"Docente"},
        {"data":"tema"},
        {"data":"descripcion"},
        {"data":"fecha_publicacion"},
        {"data":"fecha_entrega2"},
        {"data":"archivo_tarea",
          render: function(data,type,row){
                  if(data==''){
                      return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
                  }else{
                    return "<a class='btn btn-success btn-sm' href='../controller/tareas/"+data+"' target='_blank' title='Ver archivo'><i class='fas fa-file-download'></i> Descargar tarea</a>";
                  }
              }   
          },    
        {
            "defaultContent": "<button class='mostrar btn btn-primary btn-sm' title='Ver tarea realizada'><i class='fa fa-check'></i> Calificar tarea</button>"
        },

        {"data":"ESTADO",
            render: function(data,type,row){
                    if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                    }else{
                    return '<span class="badge bg-success">FINALIZADO</span>';
                    }
            }   
        },
        {"data":"ESTADO",
            render: function (data, type, row ) {
              if(data=='PENDIENTE'){
                return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;'   title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button class='editar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
            }else if(data=='FINALIZADO'){
              return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;' disabled  title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button disabled class='editar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
          }
            }
          },        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_tareas.on('draw.td',function(){
  var PageInfo = $("#tabla_tarea").DataTable().page.info();
  tbl_tareas.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function Modificar_Estatus_tarea(id,estatus,temita){
  let esta=estatus;
  $.ajax({
    "url":"../controller/tareas/controlador_modificar_estado_tarea.php",
    type:'POST',
    data:{
      id:id,
      estatus:estatus
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se ah "+esta+" con éxito la tarea con el tema: "+temita,"success").then((value)=>{
          tbl_tareas.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Error","No se completo el cambio","error");

    }
  })
}

$('#tabla_tarea').on('click','.activar',function(){
  var data = tbl_tareas.row($(this).parents('tr')).data();

  if(tbl_tareas.row(this).child.isShown()){
      var data = tbl_tareas.row(this).data();
  }
    Swal.fire({
      title: 'Desea dejar como finalizado la tarea con el tema: <b style="color:blue">'+data.tema+'</b>?',
      text: "Una vez finalizado ningún estudiante podra subir su tarea.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, dar como Finalizado'
      
    }).then((result) => {
      if (result.isConfirmed) {
        Modificar_Estatus_tarea(data.id_tarea,'FINALIZADO',data.tema);
      }
    })

})

var tbl_taras_menu
function listar_tareas_menu(){
  let id = document.getElementById('txtprincipalid').value;

  tbl_taras_menu = $("#tabla_tarea_menu").DataTable({
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
        "url":"../controller/tareas/controlador_listar_tareas_profesor_solo.php",
        type:'POST',
        data:{
          id:id
      }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE TAREAS"
    },
      title: function() {
        return  "LISTA DE TAREAS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE TAREAS"
    },
  title: function() {
    return  "LISTA DE TAREAS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE TAREAS"

  }
  }],
    "columns":[
      {"data":"id_tarea"},
      {"data":"Grado"},
      {"data":"Docente"},
      {"data":"tema"},
      {"data":"descripcion"},
      {"data":"fecha_publicacion"},
      {"data":"fecha_entrega2"},
      {"data":"archivo_tarea",
        render: function(data,type,row){
                if(data==''){
                    return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
                }else{
                  return "<a class='btn btn-success btn-sm' href='../controller/tareas/"+data+"' target='_blank' title='Ver archivo'><i class='fas fa-file-download'></i> Descargar tarea</a>";
                }
            }   
        },    


      {"data":"ESTADO",
          render: function(data,type,row){
                  if(data=='PENDIENTE'){
                  return '<span class="badge bg-warning">PENDIENTE</span>';
                  }else{
                  return '<span class="badge bg-success">FINALIZADO</span>';
                  }
          }   
      },
        
  ],

  "language":idioma_espanol,
  select: true
});

}



//TRAENDO DATOS DE LA SECCION
function Cargar_Año(){
    $.ajax({
      "url":"../controller/matricula/controlador_cargar_select_año.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
          document.getElementById('select_año').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
      }
    })
  }
//TRAENDO DATOS DE LA SECCION




function Cargar_Select_Grado() {
  let id = document.getElementById('txtprincipalid').value;
  $.ajax({
    "url": "../controller/notas/controlador_cargar_select_grado_profesor.php",
    type: 'POST',
    data: { id: id }
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + " - " + data[i][2] + "</option>";
      }
      $('#select_aula, #select_aula_editar, #select_grado, #select_grado_editar').html(cadena);
      
      // Cargar los cursos correspondientes al aula seleccionada por defecto
      var id_grado = $("#select_grado").val();
      Cargar_Select_curso(id_grado, 'select_curso');
      
      var id_grado_editar = $("#select_grado_editar").val();
      Cargar_Select_curso(id_grado_editar, 'select_curso_editar');
    } else {
      $('#select_aula, #select_aula_editar, #select_grado, #select_grado_editar').html(cadena);
    }
  });
}

// Función para cargar el select de cursos
function Cargar_Select_curso(id, select_id) {
  let idpro = document.getElementById('txtprincipalid').value;
  return $.ajax({
    url: "../controller/tareas/controlador_cargar_curso_id_detalle_profesor.php",
    type: 'POST',
    data: {
      id: id,
      idpro: idpro
    },
    dataType: 'json'  // Especificamos que esperamos JSON como respuesta
  }).then(function(data) {
    let cadena = "<option value='' disabled selected>--SELECCIONE--</option>";
    
    if (data && data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][2] + "'>" + data[i][1] + "</option>";
      }
    } else {
      cadena = "<option value='' disabled selected>Sin datos disponibles</option>";
    }
    
    $('#' + select_id).html(cadena);
  }).catch(function(error) {
    console.error('Error al cargar los cursos:', error);
    $('#' + select_id).html("<option value='' disabled selected>Error al cargar datos</option>");
  });
}


// Evento para cargar los cursos cuando cambia el aula seleccionada
$('#select_grado, #select_grado_editar').on('change', function() {
  var id = $(this).val();
  var target_select = (this.id === 'select_grado') ? 'select_curso' : 'select_curso_editar';
  Cargar_Select_curso(id, target_select);
});

// Evento para abrir el modal y cargar los datos en el formulario
$('#tabla_tarea').on('click', '.editar', function() {
  var data = tbl_tareas.row($(this).parents('tr')).data();
  
  if (tbl_tareas.row(this).child.isShown()) {
    data = tbl_tareas.row(this).data();
  }
  
  // Mostrar modal
  $("#modal_editar").modal('show');
  
  // Llenar campos con los datos seleccionados
  document.getElementById('txt_id_tarea').value = data.id_tarea;
  document.getElementById('txt_tema_editar').value = data.tema;
  document.getElementById('txt_fecha_entre_editar').value = data.fecha_entrega;
  document.getElementById('txt_descripcion_editar').value = data.descripcion;
  document.getElementById('archivo_actual').value = data.archivo_tarea;
  
  // Cargar select de grado y esperar a que se complete
  $("#select_grado_editar").val(data.Id_aula).trigger('change');
  
  // Esperar a que se carguen los cursos antes de seleccionar el curso
  $('#select_grado_editar').one('change', function() {
    setTimeout(function() {
      $("#select_curso_editar").val(data.Id_detalle_asig_docente).trigger('change');
    }, 500); // Ajusta este tiempo si es necesario
  });
});
$(document).ready(function() {
  Cargar_Select_Grado();
});
//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_Tarea() {
  // DATOS DEL REMITENTE
  let asig = document.getElementById('select_curso').value;
  let tema = document.getElementById('txt_tema').value;
  let fecha = document.getElementById('txt_fecha_entre').value;
  let descrip = document.getElementById('txt_descripcion').value;
  let archivos = $("#txt_archivo")[0].files;

  if (archivos.length == 0) {
      return Swal.fire("Mensaje de Advertencia", "Seleccione algún tipo de documento", "warning");
  }

  if (asig.length == 0 || tema.length == 0 || fecha.length == 0 || descrip.length == 0) {
      return Swal.fire("Mensaje de Advertencia", "Llene todo los campos de la tarea", "warning");
  }

  let formData = new FormData();
  //////DATOS DEL REMITENTE/////
  formData.append("asig", asig);
  formData.append("tema", tema);
  formData.append("fecha", fecha);
  formData.append("descrip", descrip);

  // Añadir múltiples archivos al formData
  for (let i = 0; i < archivos.length; i++) {
      formData.append("archivos[]", archivos[i]);
  }

  $.ajax({
      url: "../controller/tareas/controlador_registro_tareas.php",
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function(resp) {
          if (resp.length > 0) {
              if (resp == 1) {
                  Swal.fire("Mensaje de Confirmación", "Nueva Tarea asignada Correctamente!!!", "success").then((value) => {
                      tbl_tareas.ajax.reload();
                      $("#modal_registro").modal('hide');
                      document.getElementById('tema').value = "";
                      document.getElementById('fecha').value = "";
                      document.getElementById('descrip').value = "";

                  });
              } else {
                  Swal.fire("Mensaje de Advertencia", "La tarea que esta intentando registrar ya se encuentra en la base de datos, revise por favor.", "warning");
              }
          } else {
              Swal.fire("Mensaje de Advertencia", "No se pudo realizar el registro verifique por favor", "warning");
          }
      }
  });
  return false;
}
//EDITANDO ROL
function Modificar_Tarea() {
  // Datos del formulario
  let id = document.getElementById('txt_id_tarea').value;
  let asig = document.getElementById('select_curso_editar').value;
  let tema = document.getElementById('txt_tema_editar').value;
  let fecha = document.getElementById('txt_fecha_entre_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let archivoactual = document.getElementById('archivo_actual').value;
  let archivos = $("#txt_archivo_editar")[0].files;

  // Validaciones
  if (id.length === 0 || asig.length === 0 || tema.length === 0 || fecha.length === 0 || descrip.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "Tiene campos vacíos en el registro, revise por favor", "warning");
  }

  if (archivos.length === 0 && archivoactual.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "Seleccione algún tipo de documento", "warning");
  }

  let formData = new FormData();

  // Añadir datos al FormData
  formData.append("id", id);
  formData.append("asig", asig);
  formData.append("tema", tema);
  formData.append("fecha", fecha);
  formData.append("descrip", descrip);
  formData.append("archivoactual", archivoactual);

  // Añadir nuevos archivos al formData
  for (let i = 0; i < archivos.length; i++) {
    formData.append("archivos[]", archivos[i]);
  }

  $.ajax({
    url: "../controller/tareas/controlador_modificar_tareas.php",
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(resp) {
      if (resp.length > 0) {
        if (resp == 1) {
          Swal.fire("Mensaje de Confirmación", "Se actualizó de forma correcta la tarea con el TEMA: " + tema, "success").then((value) => {
            tbl_tareas.ajax.reload();
            $("#modal_editar").modal('hide');
            // Resetear campos del formulario
            document.getElementById('txt_id_tarea').value = "";
            document.getElementById('select_curso_editar').value = "";
            document.getElementById('txt_tema_editar').value = "";
            document.getElementById('txt_fecha_entre_editar').value = "";
            document.getElementById('txt_descripcion_editar').value = "";
            document.getElementById('archivo_actual').value = "";
            $("#txt_archivo_editar").val('');
          });
        } else {
          Swal.fire("Mensaje de Advertencia", "El registro que intenta actualizar ya se encuentra registrado en la base de datos, revise por favor", "warning");
        }
      } else {
        Swal.fire("Mensaje de Advertencia", "No se pudo realizar la actualización, verifique por favor", "warning");
      }
    }
  });

  return false;
}

//ELIMINANDO ROL
function Eliminar_Tarea(id){
    $.ajax({
      "url":"../controller/tareas/controlador_eliminar_tarea.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la tarea asignada con exito","success").then((value)=>{
            tbl_tareas.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta tarea por que ya tiene datos registrados, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_tarea').on('click','.delete',function(){
    var data = tbl_tareas.row($(this).parents('tr')).data();
  
    if(tbl_tareas.row(this).child.isShown()){
        var data = tbl_tareas.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar la tarea asignada con el tema: '+data.tema+'?',
      text: "Una vez aceptado la tarea sera eliminada!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Tarea(data.id_tarea);
      }
    })
  })



  var tbl_tareas_enviadas;
  function listar_tareas_enviadas(id) {
    tbl_tareas_enviadas = $("#tabla_ver").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": { "regex": false },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 10,
        "destroy": true,
        pagingType: 'full_numbers',
        scrollCollapse: true,
        responsive: true,
        "async": false,
        "processing": true,
        "ajax": {
            "url": "../controller/tareas/controlador_listar_tabla_envio_tareas.php",
            type: 'POST',
            data: { id: id },
        },
        "columns": [
            { "data": "id_detalle_tarea" },
            { "data": "Estudiante" },
            {
                "data": "archivo_evnio_tarea",
                render: function (data, type, row) {
                    if (data == '') {
                        return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
                    } else {
                        return "<a class='btn btn-success btn-sm' href='../controller/tareas/" + data + "' target='_blank' title='Ver archivo'><i class='fas fa-file-download'></i> Descargar tarea</a>";
                    }
                }
            },
            {
              "data": "calificacion",
              render: function (data, type, row) {
                  if (row.estado === 'PENDIENTE') {
                      return ''; // No mostrar nada si está pendiente
                  } else if (row.estado === 'ENVIADO'){
                      return "<input type='number' id='txt_cali' class='form-control' value='" + (data || '') + "' />";
                  } else {
                    if (data >11) {
                      return '<span class="badge bg-primary">'+data+'</span>';
                    } else {
                        return '<span class="badge bg-danger">'+data+'</span>';
                    }
                  }
              }
          },
          {
              "data": "observacion",
              render: function (data, type, row) {
                  if (row.estado === 'PENDIENTE') {
                      return ''; // No mostrar nada si está pendiente
                  } else if (row.estado === 'ENVIADO') {
                      return "<input type='text' hidden id='txt_id_tarea_deta' class='form-control' value='" + row.id_detalle_tarea  + "' /><input type='text' id='txt_observa' class='form-control' value='" + (data || '') + "' />";
                  } else {
                      return data;
                  }
              }
          },
            {
                "data": "estado",
                render: function (data, type, row) {
                    if (data === 'PENDIENTE') {
                        return '<span class="badge bg-warning">PENDIENTE</span>';
                    } else {
                        return '<span class="badge bg-success">ENVIADO</span>';
                    }
                }
            },
            { "data": "fecha_formateada2" },
            {
                "data": "estado",
                render: function (data, type, row) {
                    if (data === 'PENDIENTE') {
                        return "<button hidden class='calificar btn btn-primary btn-sm' style='margin-right: 10px;' title='Guardar calificación de tarea'><i class='fa fa-save'></i> Guardar</button>";
                    } else if (data === 'ENVIADO') {
                        return "<button class='calificar btn btn-primary btn-sm' style='margin-right: 10px;' title='Guardar calificación de tarea'><i class='fa fa-save'></i> Guardar</button>";
                    } else {
                        return "<button hidden class='calificar btn btn-primary btn-sm' style='margin-right: 10px;' title='Guardar calificación de tarea'><i class='fa fa-save'></i> Guardar</button>";
                    }
                }
            },
        ],
        "language": idioma_espanol,
        select: true
    });

    tbl_tareas_enviadas.on('draw.td', function () {
        var PageInfo = $("#tabla_ver").DataTable().page.info();
        tbl_tareas_enviadas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = i + 1 + PageInfo.start;
        });
    });
}
  
$('#tabla_tarea').on('click','.mostrar',function(){
  var data = tbl_tareas.row($(this).parents('tr')).data();

  if(tbl_tareas.row(this).child.isShown()){
      var data = tbl_tareas.row(this).data();
  }
$("#modal_ver_tareas").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>CURSO - DOCENTE: "+data.Docente+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>TEMA / TAREA: "+data.tema+"</b>";
  listar_tareas_enviadas(data.id_tarea);

})



//SE PONDRA FUNCIONALIDAD DEL BOTON GUARDAR CALIFICACION
$('#tabla_ver').on('click','.calificar',function(){

  
  let id = document.getElementById('txt_id_tarea_deta').value;
  let nota = document.getElementById('txt_cali').value;
  let obser = document.getElementById('txt_observa').value;

  if(nota>20){
      return Swal.fire("Mensaje de Advertencia","La nota no debe ser mayor a 20.","warning");
  }
  if(id.length==0||nota.length==0){
    return Swal.fire("Mensaje de Advertencia","Debe llenar la nota es un campo obligatorio.","warning");
}
  $.ajax({
    "url":"../controller/tareas/controlador_registro_calificacion.php",
    type:'POST',
    data:{
        id:id,
        nota:nota,
        obser:obser
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se guardo correctamente la nota de la tarea!!!","success").then((value)=>{
          tbl_tareas_enviadas.ajax.reload();

        });
     
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
})