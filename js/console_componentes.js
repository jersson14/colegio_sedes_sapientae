//LISTADO DE ROLES
var tbl_componentes;
function listar_componentes(){
    tbl_componentes = $("#tabla_componentes").DataTable({
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
          "url":"../controller/componentes/controlador_listar_componenetes.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE CRITERIOS"
      },
        title: function() {
          return  "LISTA DE CRITERIOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE CRITERIOS"
      },
    title: function() {
      return  "LISTA DE CRITERIOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE CRITERIOS"
  
    }
    }],
      "columns":[
        {"data":"Id_detalle_asig_docente"},
        {"data":"Grado"},
        {"data":"seccion_nombre"},
        {"data":"Nivel_academico",
          render: function(data,type,row){
              if(data=='INICIAL'){
              return '<span class="badge bg-warning">INICIAL</span>';
              }else if(data=='PRIMARIA'){
              return '<span class="badge bg-success">PRIMARIA</span>';
              }else{
              return '<span class="badge bg-primary">SECUNDARIA</span>';
              }
      }
      },        {"data":"nombre_asig"},
        {           
             "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver componentes o criterios'><i class='fa fa-check'></i> Componentes de curso</button>"
        },
        {"data":"estado",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },

        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar componentes'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar componentes'><i class='fa fa-trash'></i> Eliminar</button>"},

    ],

    "language":idioma_espanol,
    select: true
});
tbl_componentes.on('draw.td',function(){
  var PageInfo = $("#tabla_componentes").DataTable().page.info();
  tbl_componentes.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function listar_componentes_filtro(){
  let aula = document.getElementById('select_aula_buscar').value;
  tbl_componentes = $("#tabla_componentes").DataTable({
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
        "url":"../controller/componentes/controlador_listar_componenetes_filtro.php",
        type:'POST',
        data:{
          aula:aula
        }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE CRITERIOS"
    },
      title: function() {
        return  "LISTA DE CRITERIOS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE CRITERIOS"
    },
  title: function() {
    return  "LISTA DE CRITERIOS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE CRITERIOS"

  }
  }],
    "columns":[
      {"data":"Id_detalle_asig_docente"},
      {"data":"Grado"},
      {"data":"seccion_nombre"},
      {"data":"Nivel_academico",
        render: function(data,type,row){
            if(data=='INICIAL'){
            return '<span class="badge bg-warning">INICIAL</span>';
            }else if(data=='PRIMARIA'){
            return '<span class="badge bg-success">PRIMARIA</span>';
            }else{
            return '<span class="badge bg-primary">SECUNDARIA</span>';
            }
    }
    },      {"data":"nombre_asig"},
      {           
           "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver componentes o criterios'><i class='fa fa-check'></i> Componentes de curso</button>"
      },
      {"data":"estado",
          render: function(data,type,row){
                  if(data=='ACTIVO'){
                  return '<span class="badge bg-success">ACTIVO</span>';
                  }else{
                  return '<span class="badge bg-danger">INACTIVO</span>';
                  }
          }   
      },

      {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar componentes'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar componentes'><i class='fa fa-trash'></i> Eliminar</button>"},

  ],

  "language":idioma_espanol,
  select: true
});
tbl_componentes.on('draw.td',function(){
var PageInfo = $("#tabla_componentes").DataTable().page.info();
tbl_componentes.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}

// Función para cargar las aulas en el selector
// Función para cargar las aulas en el selector
// Función para cargar las aulas en el selector

function Cargar_Select_Nivelaca(){
  $.ajax({
    "url":"../controller/aulas/controlador_cargar_select_nivel.php",
    type:'POST',
  }).done(function(resp){
    let data=JSON.parse(resp);
    if(data.length>0){
      let cadena ="";
      for (let i = 0; i < data.length; i++) {
        cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
      }
      $('#select_nivel').html(cadena);
      $('#select_nivel_editar').html(cadena);

      var id =$("#select_nivel").val();
      Cargar_Select_Aula(id);
      var id =$("#select_nivel_editar").val();
      Cargar_Select_Aula(id);
    }else{
      cadena+="<option value=''>No se encontraron regitros</option>";
      $('#select_nivel_editar').html(cadena);

    }
  })
}

//TRAENDO DATOS DE LA AULAS
function Cargar_Select_Aula(id){
  $.ajax({
      "url":"../controller/asistencias/controlador_cargar_select_aula_id.php",
      type:'POST',
      data: {
          id: id  // Ensure this matches the parameter name expected by the PHP script
      },
      dataType: 'json',  // Expect JSON response
      success: function(data){
          if(data.length > 0){
              let cadena = "";
              for (let i = 0; i < data.length; i++) {
                  cadena += "<option value='" + data[i][1] + "'>" + data[i][2] + "</option>";    
              }
              $('#select_aula_buscar').html(cadena);
          } else {
              $('#select_aula_buscar').html("<option value=''>No hay secciones en la base de datos</option>");
          }
      },
      error: function(xhr, status, error) {
          console.error("AJAX Error: " + status + " - " + error);
          $('#select_aula_buscar').html("<option value=''>Error al cargar las secciones</option>");
      }
  });
}

function Cargar_Select_Grado() {
  $.ajax({
    url: "../controller/asignaturas/controlador_cargar_select_grado.php",
    type: 'POST',
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = ""; // Inicializa la cadena vacía
    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + " - "+ data[i][2] + "</option>";
      }
      $('#select_aula').html(cadena);
      $('#select_aula_editar').html(cadena);

      // Cargar los cursos correspondientes al aula seleccionada por defecto
      var id = $("#select_aula").val();
      Cargar_Select_curso(id, 'select_curso');

      var id_editar = $("#select_aula_editar").val();
      Cargar_Select_curso(id_editar, 'select_curso_editar');
    } else {
      // Si no hay aulas, limpiar los selectores
      $('#select_aula').html('');
      $('#select_aula_editar').html('');
    }
  });
}

// Función para cargar cursos basados en el aula seleccionada
function Cargar_Select_curso(id, select_id) {
  // Limpia el selector antes de cargar nuevos datos
  $('#' + select_id).empty().append("<option value='' disabled selected>Sin datos disponibles</option>");

  $.ajax({
    url: "../controller/componentes/controlador_cargar_curso_id_detalle.php",
    type: 'POST',
    data: {
      id: id
    }
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = ""; // Reinicia la cadena para evitar acumulación de datos

    if (data.length > 0) {
      cadena += "<option value='' disabled selected>--SELECCIONE--</option>";
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][2] + "'>" + data[i][1] +"</option>";
      }
    } else {
      // Si no hay cursos disponibles
      cadena += "<option value='' disabled selected>Sin datos disponibles</option>";
    }

    $('#' + select_id).html(cadena); // Actualizar el contenido del selector
  });
}

// Evento para cargar los cursos cuando cambia el aula seleccionada
$('#select_aula').on('change', function() {
  var id = $(this).val();
  Cargar_Select_curso(id, 'select_curso');
});

$('#select_aula_editar').on('change', function() {
  var id = $(this).val();
  Cargar_Select_curso(id, 'select_curso_editar');
});

// Evento para abrir el modal de edición y cargar los datos correspondientes
$('#tabla_componentes').on('click', '.editar', function() {
  var data = tbl_componentes.row($(this).parents('tr')).data();

  if (tbl_componentes.row(this).child.isShown()) {
    data = tbl_componentes.row(this).data();
  }

  $("#modal_editar").modal('show');
  document.getElementById('txt_id_asig_docente').value = data.Id_detalle_asig_docente;

  // Cambiar el valor del aula y cargar cursos correspondientes
  $("#select_aula_editar").val(data.Id_aula).trigger('change');

  // Esperar a que el cambio en select_aula_editar se aplique antes de establecer el curso
  $("#select_aula_editar").on('change', function() {
    setTimeout(function() {
      $("#select_curso_editar").val(data.Id_asignatura).trigger('change.select2');
    }, 100); // Reducido el tiempo de espera
  });

  // Cargar los datos en la tabla
  listar_componentes_curso(data.Id_detalle_asig_docente);
});

var tbl_traer_datos;
function listar_componentes_curso(id) {
  tbl_traer_datos = $("#tabla_criterio_editar").DataTable({
    "ordering": false,
    "bLengthChange": false,
    "searching": false,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "pageLength": 10,
    "destroy": true,
    "pagingType": 'full_numbers',
    "scrollCollapse": false,
    "responsive": true,
    "processing": true,
    "ajax": {
      "url": "../controller/componentes/controlador_listar_tabla_componentes_curso.php",
      "type": 'POST',
      "data": {
        id: id
      },
    },
    "columns": [
      {"data": "id_detalle_asignatura"},
      {"data": "nombre_asig"},
      {"data": "competencias"},
      {"data": "descripción_observa"},
      {"defaultContent": "<button class='delete btn btn-danger btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"}
    ],
    "language": idioma_espanol,
    "select": true
  });
}


function Eliminar_compo_curso(id){
  $.ajax({
    "url":"../controller/componentes/controlador_eliminar_componente_curso_unico.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se elimino el componente con exito","success").then((value)=>{
          tbl_traer_datos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Advetencia","No se puede eliminar este componente por que esta siendo utilizado en las notas, verifique por favor","warning");

    }
  })
}

//ENVIANDO AL BOTON DELETE
$('#tabla_criterio_editar').on('click','.delete',function(){
  var data = tbl_traer_datos.row($(this).parents('tr')).data();

  if(tbl_traer_datos.row(this).child.isShown()){
      var data = tbl_traer_datos.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar el componente: '+data.competencias+' del curso de: '+data.nombre_asig+'?',
    text: "Una vez aceptado el componente sera eliminado!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_compo_curso(data.id_criterio);
    }
  })
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}



function Agregar_componente(){
  var id_asignatura=$("#select_curso").val();
  var asig=$("#select_curso option:selected").text();
  var compon=$("#txt_criterio").val();
  var obser=$("#txt_observa").val();


  if(verificarid(compon)){
   return Swal.fire("Mensaje de Advertencia","El componente ya fue agregado a la tabla","warning");
  }

  var datos_agregar ="<tr>";
  datos_agregar+="<td >"+id_asignatura+"</td>";
  datos_agregar+="<td>"+asig+"</td>";
  datos_agregar+="<td for='id'>"+compon+"</td>";
  datos_agregar+="<td>"+obser+"</td>";
  datos_agregar+="<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'><i></button></td>";
  datos_agregar+="</tr>";
  $("#tabla_criterio").append(datos_agregar);
 
}

function Agregar_componente_editar(){
  var id_asignatura=$("#select_curso_editar").val();
  var asig=$("#select_curso_editar option:selected").text();
  var compon=$("#txt_criterio_editar").val();
  var obser=$("#txt_observa_editar").val();


  if(verificarid2(compon)){
   return Swal.fire("Mensaje de Advertencia","El componente ya fue agregado a la tabla","warning");
  }

  var datos_agregar ="<tr>";
  datos_agregar+="<td >"+id_asignatura+"</td>";
  datos_agregar+="<td>"+asig+"</td>";
  datos_agregar+="<td for='id'>"+compon+"</td>";
  datos_agregar+="<td>"+obser+"</td>";
  datos_agregar+="<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'><i></button></td>";
  datos_agregar+="</tr>";
  $("#tabla_criterio_editar").append(datos_agregar);
 
}

function verificarid(id) {
  if (!id || id.trim() === '') {
    // Retorna false si el id es null, undefined, o una cadena vacía
    return false;
  }

  let compon = document.querySelectorAll('#tabla_criterio td[for="id"]');
  return [].filter.call(compon, td => td.textContent.trim() === id.trim()).length === 1;
}


function verificarid2(id) {
  if (!id || id.trim() === '') {
    // Retorna false si el id es null, undefined, o una cadena vacía
    return false;
  }

  let compon = document.querySelectorAll('#tabla_criterio_editar td[for="id"]');
  return [].filter.call(compon, td => td.textContent.trim() === id.trim()).length === 1;
}

function remove(t){
  var td =t.parentNode;
  var tr=td.parentNode;
  var table =tr.parentNode;
  table.removeChild(tr);
}

//REGISTRANDO ROLES
function Registrar_Componentes() {
  let componentes = [];
  
  $("#tabla_criterio tr").each(function() {
    var id_asignatura = $(this).find('td').eq(0).text().trim(); // Obtener el ID y eliminar espacios
    var compon = $(this).find('td[for="id"]').text().trim();
    var obser = $(this).find('td').eq(3).text().trim();
    
    // Validar que id_asignatura no esté vacío y que el componente no sea vacío o "--SELECCIONE--"
    if (id_asignatura && compon && compon !== "--SELECCIONE--") {
      componentes.push({
        id_asignatura: id_asignatura,
        componente: compon,
        observacion: obser
      });
    }
  });

  // Validar si hay componentes para registrar
  if (componentes.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "No hay componentes válidos en la tabla para registrar", "warning");
  }

  $.ajax({
    url: '../controller/componentes/controlador_registro_componentes.php',
    type: 'POST',
    data: {
      componentes: JSON.stringify(componentes)
    }
  }).done(function(resp) {
    if (resp > 0) {
      if (resp == 1) {
        Swal.fire("Mensaje de Confirmación", "Componentes registrados satisfactoriamente!!!", "success").then(() => {
          tbl_componentes.ajax.reload();
          $("#modal_registro").modal('hide');
        });
      } else {
        Swal.fire("Mensaje de Advertencia", "El Componente que está intentando registrar ya existe en la base de datos, revise por favor", "warning");
      }
    } else {
      Swal.fire("Mensaje de Error", "No se completó el registro de los componentes!!", "error");
    }
  });
}

//EDITANDO ROL
function Modificar_Componentes() {
  let componentes = [];
  
  $("#tabla_criterio_editar tr").each(function() {
    var id_asignatura = $(this).find('td').eq(0).text().trim(); // Obtener el ID y eliminar espacios
    var compon = $(this).find('td[for="id"]').text().trim();
    var obser = $(this).find('td').eq(3).text().trim();
    
    // Validar que id_asignatura no esté vacío y que el componente no sea vacío o "--SELECCIONE--"
    if (id_asignatura && compon && compon !== "--SELECCIONE--") {
      componentes.push({
        id_asignatura: id_asignatura,
        componente: compon,
        observacion: obser
      });
    }
  });

  // Validar si hay componentes para registrar
  if (componentes.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "No hay componentes válidos en la tabla para registrar", "warning");
  }

  $.ajax({
    url: '../controller/componentes/controlador_modificar_componentes.php',
    type: 'POST',
    data: {
      componentes: JSON.stringify(componentes)
    }
  }).done(function(resp) {
    if (resp == 1) {
      Swal.fire("Mensaje de Confirmación", "Componentes registrados satisfactoriamente!!!", "success").then(() => {
        tbl_componentes.ajax.reload();
        $("#modal_editar").modal('hide');
      });
    } else if (resp == 2) {
      Swal.fire("Mensaje de Información", "No se registraron nuevos componentes porque ya existen", "info");
    } else {
      Swal.fire("Mensaje de Error", "No se completó el registro de los componentes, tiene un error en el último registro!!", "error");
    }
  });
}








//ELIMINANDO ROL
function Eliminar_Componente(id){
    $.ajax({
      "url":"../controller/componentes/controlador_eliminar_componentes.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se eliminaron todo los componentes del curso con éxito","success").then((value)=>{
            tbl_componentes.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este componente por que ya tiene nota registradas, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_componentes').on('click','.delete',function(){
    var data = tbl_componentes.row($(this).parents('tr')).data();
  
    if(tbl_componentes.row(this).child.isShown()){
        var data = tbl_componentes.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar los componentes del curso de: '+data.nombre_asig+'?',
      text: "Una vez aceptado, todo los componentes serán eliminados!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Componente(data.id_detalle_asignatura);
      }
    })
  })



  var tbl_vistas;
  function listar_compo2(id) {
    tbl_vistas = $("#tabla_vistacomp").DataTable({
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
            "url": "../controller/componentes/controlador_listar_componentes_curso.php",
            type: 'POST',
            data: { id: id },
        },
        "columns": [
            { "data": "id_detalle_asignatura" },
            { "data": "competencias" },
            { "data": "fecha_formateada" },
            { "data": "descripción_observa" },
        ],
        "language": idioma_espanol,
        select: true
    });

    tbl_vistas.on('draw.td', function () {
        var PageInfo = $("#tabla_vistacomp").DataTable().page.info();
        tbl_vistas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = i + 1 + PageInfo.start;
        });
    });
}
  
$('#tabla_componentes').on('click','.mostrar',function(){
  var data = tbl_componentes.row($(this).parents('tr')).data();

  if(tbl_componentes.row(this).child.isShown()){
      var data = tbl_componentes.row(this).data();
  }
$("#modal_ver_compo").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>AULA O GRADO: "+data.Grado+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>CURSO: "+data.nombre_asig+"</b>";
  listar_compo2(data.id_detalle_asignatura);

})



