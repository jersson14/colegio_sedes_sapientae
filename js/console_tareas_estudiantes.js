//LISTADO DE ROLES
var tbl_tareas;
function listar_tareas_id(){
  let año = document.getElementById('select_año').value;
  let grado = document.getElementById('select_grado').value;
  let curso = document.getElementById('select_curso').value;

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
        "url":"../controller/tareas/controlador_listar_tareas_estudiante.php",
        type:'POST',
        data:{
          año:año,
          grado:grado,
          curso:curso,
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
        {"data":"archivo_evnio_tarea",
          render: function(data,type,row){
                  if(data==''){
                      return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
                  }else{
                    return "<a class='btn btn-primary btn-sm' href='../controller/tareas/"+data+"' target='_blank' title='Ver archivo'><i class='fas fa-file'></i> Ver tarea enviada</a>";
                  }
              }   
          },  
        {"data":"ESTADO",
          render: function(data,type,row){
                  if(data=='PENDIENTE'){
                  return '<span class="badge bg-warning">PENDIENTE</span>';
                  }else if (data=='ENVIADO'){
                  return '<span class="badge bg-success">ENVIADO</span>';
                  }else{
                    return '<span class="badge bg-primary">CALIFICADO</span>';

                  }
          }   
      },
      {"data":"ESTADO",
        render: function (data, type, row ) {
          if(data=='PENDIENTE'){
            return "<button  class='agregar btn btn-secondary btn-sm'  style='margin-right: 10px;' title='Subir tarea'><i class='fa fa-upload'></i> Subir tarea</button><button class='editar btn btn-warning btn-sm' style='margin-right: 10px;' hidden title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
          }else if(data=='ENVIADO'){
            return "<button  class='agregar btn btn-secondary btn-sm' hidden style='margin-right: 10px;' title='Subir tarea'><i class='fa fa-upload'></i> Subir tarea</button><button class='editar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
          }else{
            return "<button  class='ver btn btn-success btn-sm' style='margin-right: 10px;' title='Ver nota de tarea'><i class='fa fa-eye'></i> Ver calificación</button>";             

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
          "url":"../controller/tareas/controlador_listar_tareas_estudiante_solo.php",
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
          {"data":"archivo_evnio_tarea",
            render: function(data,type,row){
                    if(data==''){
                        return "<button class='btn btn-danger btn-sm' disabled title='Ver archivo'><i class='fa fa-file-pdf'></i></button>";
                    }else{
                      return "<a class='btn btn-primary btn-sm' href='../controller/tareas/"+data+"' target='_blank' title='Ver archivo'><i class='fas fa-file'></i> Ver tarea enviada</a>";
                    }
                }   
            },  
        {"data":"ESTADO",
            render: function(data,type,row){
                    if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                    }else if (data=='ENVIADO'){
                    return '<span class="badge bg-success">ENVIADO</span>';
                    }else{
                      return '<span class="badge bg-primary">CALIFICADO</span>';

                    }
            }   
        },
        {"data":"ESTADO",
          render: function (data, type, row ) {
            if(data=='PENDIENTE'){
              return "<button  class='agregar btn btn-secondary btn-sm'  style='margin-right: 10px;' title='Subir tarea'><i class='fa fa-upload'></i> Subir tarea</button><button class='editar btn btn-warning btn-sm' style='margin-right: 10px;' hidden title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
            }else if(data=='ENVIADO'){
              return "<button  class='agregar btn btn-secondary btn-sm' hidden style='margin-right: 10px;' title='Subir tarea'><i class='fa fa-upload'></i> Subir tarea</button><button class='editar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>";             
            }else{
              return "<button  class='ver btn btn-success btn-sm' style='margin-right: 10px;' title='Ver nota de tarea'><i class='fa fa-eye'></i> Ver calificación</button>";             

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




var tbl_taras_menu
function listar_tareas_menu_estudiante(){
  let id = document.getElementById('txtprincipalid').value;

  tbl_taras_menu = $("#tabla_tarea_menu_estudiante").DataTable({
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
          "url":"../controller/tareas/controlador_listar_tareas_estudiante_solo_pendiente.php",
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
        {"data":"Docente"},
        {"data":"tema"},
        {"data":"descripcion"},
        {"data":"fecha_entrega2"},
        {"data":"ESTADO",
            render: function(data,type,row){
                    if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                    }else if (data=='ENVIADO'){
                    return '<span class="badge bg-success">ENVIADO</span>';
                    }else{
                      return '<span class="badge bg-primary">CALIFICADO</span>';

                    }
            }   
        },
        
    ],

    "language":idioma_espanol,
    select: true
});

}


var tbl_taras_menu
function listar_examenes_menu_estudiante(){
  let id = document.getElementById('txtprincipalid').value;

  tbl_taras_menu = $("#tabla_examenes_menu_estudiante").DataTable({
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
          "url":"../controller/examenes/controlador_listar_examenes_estudiante_solo_pendiente.php",
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
        {"data":"Docente"},
        {"data":"tema_examen"},
        {"data":"descripcion"},
        {"data":"FECHA_EXAMEN"},
        {"data":"ESTADO",
            render: function(data,type,row){
                    if(data=='PENDIENTE'){
                    return '<span class="badge bg-warning">PENDIENTE</span>';
                    }else if (data=='ENVIADO'){
                    return '<span class="badge bg-success">ENVIADO</span>';
                    }else{
                      return '<span class="badge bg-primary">CALIFICADO</span>';

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
    "url": "../controller/tareas/controlador_cargar_select_grado_estudiante.php",
    type: 'POST',
    data: { id: id }
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "";
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + " - " + data[i][2] + "</option>";
      }
      $('#select_aula, #select_aula_editar,#select_grado_ver, #select_grado, #select_grado_editar,#select_grado_envio').html(cadena);
      
      // Cargar los cursos correspondientes al aula seleccionada por defecto
      var id_grado = $("#select_grado").val();
      Cargar_Select_curso(id_grado, 'select_curso');

      var id_grado2 = $("#select_grado_envio").val();
      Cargar_Select_curso(id_grado2, 'select_curso_en');
      
      var id_grado3 = $("#select_grado_ver").val();
      Cargar_Select_curso(id_grado3, 'select_curso_ver');
      
      var id_grado_editar = $("#select_grado_editar").val();
      Cargar_Select_curso(id_grado_editar, 'select_curso_editar');
    } else {
      $('#select_aula, #select_aula_editar,#select_grado_ver, #select_grado, #select_grado_editar,#select_grado_envio').html(cadena);
    }
  });
}

// Función para cargar el select de cursos
function Cargar_Select_curso(id, select_id) {
  let idestu = document.getElementById('txtprincipalid').value;
  return $.ajax({
    url: "../controller/tareas/controlador_cargar_curso_id_detalle_estudiante.php",
    type: 'POST',
    data: {
      id: id,
      idestu: idestu
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
  document.getElementById('txt_id_detalle_tarea_editar').value = data.id_detalle_tarea;

  $("#select_grado_editar").select2().val(data.Id_aula).trigger('change.select2');
  $("#select_curso_editar").select2().val(data.Id_detalle_asig_docente).trigger('change.select2');
  document.getElementById('archivo_actual_editar').value = data.archivo_evnio_tarea;

  // Llenar campos con los datos seleccionados
  document.getElementById('txt_tema_editar').value = data.tema;
  document.getElementById('txt_fecha_entre_envio_editar').value = data.fecha_entrega;
  document.getElementById('txt_descripcion_editar').value = data.descripcion;

  // Cargar select de docente

});

$(document).ready(function() {
  Cargar_Select_Grado();
});
//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}



//ENVIAR TAREA
$('#tabla_tarea').on('click', '.agregar', function() {
  var data = tbl_tareas.row($(this).parents('tr')).data();

  if (tbl_tareas.row(this).child.isShown()) {
    data = tbl_tareas.row(this).data();
  }

  // Mostrar modal
  $("#modal_envio").modal('show');
  document.getElementById('txt_id_detalle_tarea').value = data.id_detalle_tarea;

  $("#select_grado_envio").select2().val(data.Id_aula).trigger('change.select2');
  $("#select_curso_en").select2().val(data.Id_detalle_asig_docente).trigger('change.select2');
  document.getElementById('archivo_actual').value = data.archivo_evnio_tarea;

  // Llenar campos con los datos seleccionados
  document.getElementById('txt_tema').value = data.tema;
  document.getElementById('txt_fecha_entre_envio').value = data.fecha_entrega;
  document.getElementById('txt_descripcion').value = data.descripcion;

  // Cargar select de docente

});


//REGISTRANDO ROLES
//EDITANDO ROL
function Registrar_Tarea_envio() {
  // Datos del formulario
  let iddetalle = document.getElementById('txt_id_detalle_tarea').value;
  let tema = document.getElementById('txt_tema').value;

  let archivoactual = document.getElementById('archivo_actual').value;
  let archivos = $("#txt_archivo")[0].files;

  // Validaciones

  if (archivos.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "Seleccione el archivo de la tarea que desea enviar", "warning");
  }

  let formData = new FormData();

  // Añadir datos al FormData
  formData.append("iddetalle", iddetalle);

  formData.append("archivoactual", archivoactual);

  // Añadir nuevos archivos al formData
  for (let i = 0; i < archivos.length; i++) {
    formData.append("archivos[]", archivos[i]);
  }

  $.ajax({
    url: "../controller/tareas/controlador_registro_tareas_estudiante.php",
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(resp) {
      if (resp.length > 0) {
       
          Swal.fire("Mensaje de Confirmación", "Se envio correctamente la tarea con el TEMA: " + tema, "success").then((value) => {
            tbl_tareas.ajax.reload();
            $("#modal_envio").modal('hide');
            // Resetear campos del formulario
            document.getElementById('iddetalle').value = "";

            document.getElementById('archivo_actual').value = "";
            $("#txt_archivo").val('');
          });
       
      } else {
        Swal.fire("Mensaje de Advertencia", "No se pudo realizar la actualización, verifique por favor", "warning");
      }
    }
  });

  return false;
}

//EDITAR TAREA
function Modificar_Tarea_envio() {
  // Datos del formulario
  let iddetalle = document.getElementById('txt_id_detalle_tarea_editar').value;
  let tema = document.getElementById('txt_tema_editar').value;

  let archivoactual = document.getElementById('archivo_actual').value;
  let archivos = $("#txt_archivo_editar")[0].files;

  // Validaciones

  if (archivos.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "Seleccione el archivo de la tarea que desea enviar", "warning");
  }

  let formData = new FormData();

  // Añadir datos al FormData
  formData.append("iddetalle", iddetalle);

  formData.append("archivoactual", archivoactual);

  // Añadir nuevos archivos al formData
  for (let i = 0; i < archivos.length; i++) {
    formData.append("archivos[]", archivos[i]);
  }

  $.ajax({
    url: "../controller/tareas/controlador_modificar_tareas_estudiante.php",
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(resp) {
      if (resp.length > 0) {
       
          Swal.fire("Mensaje de Confirmación", "Se actualizo correctamente el archivo de la tarea con el TEMA: " + tema, "success").then((value) => {
            tbl_tareas.ajax.reload();
            $("#modal_editar").modal('hide');
            // Resetear campos del formulario
            document.getElementById('iddetalle').value = "";

            document.getElementById('archivo_actual').value = "";
            $("#txt_archivo").val('');
          });
       
      } else {
        Swal.fire("Mensaje de Advertencia", "No se pudo realizar la actualización, verifique por favor", "warning");
      }
    }
  });

  return false;
}


$('#tabla_tarea').on('click', '.ver', function() {
  var data = tbl_tareas.row($(this).parents('tr')).data();

  if (tbl_tareas.row(this).child.isShown()) {
    data = tbl_tareas.row(this).data();
  }

  // Mostrar modal
  $("#modal_nota").modal('show');

  $("#select_grado_ver").select2().val(data.Id_aula).trigger('change.select2');
  $("#select_curso_ver").select2().val(data.Id_detalle_asig_docente).trigger('change.select2');

  // Llenar campos con los datos seleccionados
  document.getElementById('txt_tema_ver').value = data.tema;
  document.getElementById('txt_fecha_entre_envi_ver').value = data.fecha_entrega;
  document.getElementById('txt_descripcion_ver').value = data.descripcion;
  document.getElementById('txt_observacion').value = data.observacion;

  document.getElementById('titulo_h1').innerHTML = '<b>NOTA DE LA TAREA CON EL TEMA: <span style="color:blue">' + data.tema+'</span></b>';

 // Asignar el valor de la calificación al h2
  var nota = document.getElementById('titulo_h2').innerHTML = data.calificacion;

// Verificar la calificación y asignar el texto correspondiente con el color
if (nota >= 11) {
  // Colocar la nota en verde y mostrar "FELICITACIONES" con un emoji de trofeo
  document.getElementById('titulo_h2').innerHTML = '<b><span style="color: green;">' + data.calificacion + '</span></b>';
  document.getElementById('subtitulo_h3').innerHTML = '<b><span style="color: green;">🏆 FELICITACIONES 🏆</span></b>';
} else {
  // Colocar la nota en rojo y mostrar "TIENES QUE MEJORAR" con un emoji de advertencia
  document.getElementById('titulo_h2').innerHTML = '<b><span style="color: red;">' + data.calificacion + '</span></b>';
  document.getElementById('subtitulo_h3').innerHTML = '<b><span style="color: red;">⚠️ TIENES QUE MEJORAR ⚠️</span></b>';
}


  // Cargar select de docente

});
