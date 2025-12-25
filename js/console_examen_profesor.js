//LISTADO DE ROLES
var tbl_examen;
function listar_examenes_id(){
    let año = document.getElementById('select_año').value;
    let grado = document.getElementById('select_aula').value;
    let id = document.getElementById('txtprincipalid').value;
    tbl_examen = $("#tabla_examen").DataTable({
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
          "url":"../controller/examenes/controlador_listar_examenes_profesor.php",
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
        return  "LISTA DE EXAMENES"
      },
        title: function() {
          return  "LISTA DE EXAMENES" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE EXAMENES"
      },
    title: function() {
      return  "LISTA DE EXAMENES"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE EXAMENES"
  
    }
    }],
      "columns":[
        {"data":"id_examen"},
        {"data":"Nivel_academico"},
        {"data":"Grado"},
        {"data":"Docente"},
        {"data":"tema_examen"},
        {"data":"descripcion"},
        {"data":"fecha_publicacion"},
        {"data":"FECHA_EXAMEN"},
         
        {
            "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver tarea realizada'><i class='fa fa-check'></i> Alumnos para examen</button>"
        },
        {"data":"ESTADO",
          render: function(data,type,row){
                  if(data=='PENDIENTE'){
                  return '<span class="badge bg-danger">PENDIENTE</span>';
                  }else{
                  return '<span class="badge bg-success">REALIZADO</span>';
                  }
          }   
      },
      {"data":"ESTADO",
          render: function (data, type, row ) {
            if(data=='PENDIENTE'){
                return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button class='editar btn btn-primary btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>&nbsp;<button style='margin-right: 10px;' class='delete btn btn-danger btn-sm' title='Eliminar datos'><i class='fa fa-trash'></i> Eliminar</button>";             
            }else if(data=='REALIZADO'){
                  return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;' hidden title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button class='editar  btn btn-primary btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>&nbsp;<button style='margin-right: 10px;' class='delete btn btn-danger btn-sm' hidden title='Eliminar datos'><i class='fa fa-trash'></i> Eliminar</button>";             
          }
          }
        },       
    ],

    "language":idioma_espanol,
    select: true
});
tbl_examen.on('draw.td',function(){
  var PageInfo = $("#tabla_examen").DataTable().page.info();
  tbl_examen.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function listar_examenes(){
    let id = document.getElementById('txtprincipalid').value;

  tbl_examen = $("#tabla_examen").DataTable({
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
        "url":"../controller/examenes/controlador_listar_examenes_profesor_solo.php",
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
      return  "LISTA DE EXAMENES"
    },
      title: function() {
        return  "LISTA DE EXAMENES" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE EXAMENES"
    },
  title: function() {
    return  "LISTA DE EXAMENES"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE EXAMENES"

  }
  }],
    "columns":[
      {"data":"id_examen"},
      {"data":"Nivel_academico"},
      {"data":"Grado"},
      {"data":"Docente"},
      {"data":"tema_examen"},
      {"data":"descripcion"},
      {"data":"fecha_publicacion"},
      {"data":"FECHA_EXAMEN"},
       
      {
          "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver tarea realizada'><i class='fa fa-check'></i> Alumnos para examen</button>"
      },

      {"data":"ESTADO",
          render: function(data,type,row){
                  if(data=='PENDIENTE'){
                  return '<span class="badge bg-danger">PENDIENTE</span>';
                  }else{
                  return '<span class="badge bg-success">REALIZADO</span>';
                  }
          }   
      },
      {"data":"ESTADO",
          render: function (data, type, row ) {
            if(data=='PENDIENTE'){
                return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;'  title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button class='editar btn btn-primary btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>&nbsp;<button style='margin-right: 10px;' class='delete btn btn-danger btn-sm' title='Eliminar datos'><i class='fa fa-trash'></i> Eliminar</button>";             
            }else if(data=='REALIZADO'){
                  return "<button class='activar btn btn-warning btn-sm' style='margin-right: 10px;' hidden title='Finalizar examen'><i class='fa fa-thumbs-up'></i> Finalizar</button>&nbsp;<button class='editar  btn btn-primary btn-sm' style='margin-right: 10px;'  title='Editar datos'><i class='fa fa-edit'></i> Editar</button>&nbsp;<button style='margin-right: 10px;' class='delete btn btn-danger btn-sm' hidden title='Eliminar datos'><i class='fa fa-trash'></i> Eliminar</button>";             
          }
          }
        },        
  ],

  "language":idioma_espanol,
  select: true
});
tbl_examen.on('draw.td',function(){
var PageInfo = $("#tabla_examen").DataTable().page.info();
tbl_examen.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}


function listar_examenes_menu(){
    let id = document.getElementById('txtprincipalid').value;

  tbl_examen = $("#tabla_examenes_menu").DataTable({
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
        "url":"../controller/examenes/controlador_listar_examenes_profesor_solo.php",
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
      return  "LISTA DE EXAMENES"
    },
      title: function() {
        return  "LISTA DE EXAMENES" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE EXAMENES"
    },
  title: function() {
    return  "LISTA DE EXAMENES"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE EXAMENES"

  }
  }],
    "columns":[
      {"data":"id_examen"},
      {"data":"Nivel_academico"},
      {"data":"Grado"},
      {"data":"Docente"},
      {"data":"tema_examen"},
      {"data":"descripcion"},
      {"data":"fecha_publicacion"},
      {"data":"FECHA_EXAMEN"},
       


      {"data":"ESTADO",
          render: function(data,type,row){
                  if(data=='PENDIENTE'){
                  return '<span class="badge bg-danger">PENDIENTE</span>';
                  }else{
                  return '<span class="badge bg-success">REALIZADO</span>';
                  }
          }   
      },
    
  ],

  "language":idioma_espanol,
  select: true
});
tbl_examen.on('draw.td',function(){
var PageInfo = $("#tabla_examenes_menu").DataTable().page.info();
tbl_examen.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
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
function Modificar_Estatus_examen(id,estatus,temita){
    let esta=estatus;
    $.ajax({
      "url":"../controller/examenes/controlador_modificar_estado_examen.php",
      type:'POST',
      data:{
        id:id,
        estatus:estatus
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se ah "+esta+" con éxito el examen con el tema: "+temita,"success").then((value)=>{
            tbl_examen.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Error","No se completo el cambio","error");
  
      }
    })
  }

  $('#tabla_examen').on('click','.activar',function(){
    var data = tbl_examen.row($(this).parents('tr')).data();
  
    if(tbl_examen.row(this).child.isShown()){
        var data = tbl_examen.row(this).data();
    }
      Swal.fire({
        title: 'Desea dejar como realizado el examen con el tema: <b style="color:blue">'+data.tema_examen+'</b>?',
        text: "Una vez realizado se deshabilitara el boton eliminar ya que el examen de desarrollo con éxito.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si, dar como Realizado'
        
      }).then((result) => {
        if (result.isConfirmed) {
            Modificar_Estatus_examen(data.id_examen,'REALIZADO',data.tema_examen);
        }
      })
  
  })
  
//TRAENDO DATOS DE LA SECCION
function Cargar_Select_docente() {
  $.ajax({
    url: "../controller/asignatura_docente/controlador_cargar_docente.php",
    type: 'POST',
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let placeholder = "<option value='' disabled selected>--SELECCIONE--</option>";
    let cadena = placeholder;

    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + " - " + data[i][4] + "</option>";
      }
    } else {
      cadena += "<option value='' disabled>No hay docentes en la base de datos</option>";
    }

    $('#select_docente').html(cadena);
    $('#select_docente_editar').html(cadena);
  });
}

function Cargar_Select_Curso(id, selectedCursoId = null) {
  $.ajax({
    url: "../controller/tareas/controlador_cargar_select_cursos_docente.php",
    type: 'POST',
    data: {
      id: id
    },
  }).done(function(resp) {
    let data = JSON.parse(resp);

    // Limpiar el selector de cursos
    let cadena = "<option value='' disabled selected>No hay cursos disponibles</option>";

    // Solo agregar opciones si hay datos disponibles
    if (data.length > 0) {
      cadena = "<option value='' disabled selected>--SELECCIONE--</option>"; // Reinicia el placeholder si hay datos
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][2] + "'>" + data[i][1] + "</option>";
      }
    }

    // Asignar las opciones al select de cursos
    $('#select_curso').html(cadena);
    $('#select_curso_editar').html(cadena);

    // Establecer el curso seleccionado si se proporciona
    if (selectedCursoId) {
      $('#select_curso_editar').val(selectedCursoId).trigger('change');
    }
  }).fail(function() {
    // En caso de fallo en la solicitud, limpiar el selector de cursos
    $('#select_curso').html("<option value='' disabled selected>No hay cursos disponibles</option>");
    $('#select_curso_editar').html("<option value='' disabled selected>No hay cursos disponibles</option>");
  });
}

// Manejadores de eventos para cambios en los selects de docente
$('#select_docente').change(function() {
  var id = $(this).val();
  if (id) {
    Cargar_Select_Curso(id);
  } else {
    $('#select_curso').html("<option value='' disabled selected>No hay cursos disponibles</option>");
  }
});

$('#select_docente_editar').change(function() {
  var id = $(this).val();
  if (id) {
    Cargar_Select_Curso(id);
  } else {
    $('#select_curso_editar').html("<option value='' disabled selected>No hay cursos disponibles</option>");
  }
});
// Enviando datos para editar
$('#tabla_examen').on('click', '.editar', function() {
  var data = tbl_examen.row($(this).parents('tr')).data();

  if (tbl_examen.row(this).child.isShown()) {
    data = tbl_examen.row(this).data();
  }

  $("#modal_editar").modal('show');
  document.getElementById('txt_id_examen').value = data.id_examen;
  $("#select_docente_editar").select2().val(data.Id_docente).trigger('change.select2');
  
  // Cargar cursos para el docente seleccionado al abrir el modal
  // y pasar el id_detalle_asignatura para seleccionar el curso correcto
  Cargar_Select_Curso(data.Id_docente, data.id_detalle_asignatura);

  document.getElementById('txt_tema_editar').value = data.tema_examen;
  document.getElementById('txt_fecha_examen_editar').value = data.fecha_examen;
  document.getElementById('txt_descripcion_editar').value = data.descripcion;
});
//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_Examen() {
  // DATOS DEL REMITENTE
  let asig = document.getElementById('select_curso').value;
  let tema = document.getElementById('txt_tema').value;
  let fecha = document.getElementById('txt_fecha_exa').value;
  let descrip = document.getElementById('txt_descripcion').value;


  if(asig.length==0||tema.length==0||fecha.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios revise por favor","warning");
}
$.ajax({
  "url":"../controller/examenes/controlador_registro_examenes.php",
  type:'POST',
  data:{
      asig:asig,
      tema:tema,
      fecha:fecha,
      descrip:descrip

  }
}).done(function(resp){
  if(resp>0){
    if(resp==1){
      Swal.fire("Mensaje de Confirmación","Nueva examen programado satisfactoriamente!!!","success").then((value)=>{
        tbl_examen.ajax.reload();
        document.getElementById('txt_tema').value="";
        document.getElementById('txt_descripcion').value="";
      $("#modal_registro").modal('hide');
      });
    }else{
      Swal.fire("Mensaje de Advertencia","El examen con la fecha que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
    }
  }else{
    return Swal.fire("Mensaje de Error","No se completo el registro","error");

  }
})
}
//EDITANDO ROL
function Modificar_Examen() {
  // Datos del formulario
  let id = document.getElementById('txt_id_examen').value;
  let asig = document.getElementById('select_curso_editar').value;
  let tema = document.getElementById('txt_tema_editar').value;
  let fecha = document.getElementById('txt_fecha_examen_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  if(id.length==0||asig.length==0||tema.length==0||fecha.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios revise por favor","warning");
  }
  $.ajax({
    "url":"../controller/examenes/controlador_modificar_examen.php",
    type:'POST',
    data:{
      id:id,
      asig:asig,
      tema:tema,
      fecha:fecha,
      descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_examen.ajax.reload();
            $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El examen con la fecha que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}

//ELIMINANDO ROL
function Eliminar_Examen(id){
    $.ajax({
      "url":"../controller/examenes/controlador_eliminar_examenes.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la programación del examen con exito","success").then((value)=>{
            tbl_examen.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta programación por que ya tiene datos registrados, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_examen').on('click','.delete',function(){
    var data = tbl_examen.row($(this).parents('tr')).data();
  
    if(tbl_examen.row(this).child.isShown()){
        var data = tbl_examen.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el examen programado con el tema: '+data.tema_examen+'?',
      text: "Una vez aceptado la programación del examen sera eliminada!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Examen(data.id_examen);
      }
    })
  })



  var tbl_vistas;
  function listar_tareas_enviadas(id) {
    tbl_vistas = $("#tabla_ver").DataTable({
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
            "url": "../controller/examenes/controlador_listar_alumnos_examen.php",
            type: 'POST',
            data: { id: id },
        },
        "columns": [
            { "data": "Id_alumno" },
            { "data": "alum_dni" },
            { "data": "Estudiante" },

            {"data":"alum_sexo",
                render: function(data,type,row){
                        if(data=='FEMENINO'){
                        return '<span class="badge bg-warning">FEMENINO</span>';
                        }else{
                        return '<span class="badge bg-primary">MASCULINO</span>';
                        }
                }   
            },
        ],
        "language": idioma_espanol,
        select: true
    });

    tbl_vistas.on('draw.td', function () {
        var PageInfo = $("#tabla_ver").DataTable().page.info();
        tbl_vistas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = i + 1 + PageInfo.start;
        });
    });
}
  
$('#tabla_examen').on('click','.mostrar',function(){
  var data = tbl_examen.row($(this).parents('tr')).data();

  if(tbl_examen.row(this).child.isShown()){
      var data = tbl_examen.row(this).data();
  }
$("#modal_ver_alumnos").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>CURSO - DOCENTE: "+data.Docente+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>TEMA DE EXAMEN: "+data.tema_examen+"</b>";
  listar_tareas_enviadas(data.Id_detalle_asig_docente);

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
          tbl_vistas.ajax.reload();

        });
     
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
})