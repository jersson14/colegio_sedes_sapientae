//LISTADO DE ROLES
var tbl_horarios;
function listar_horarios(){
    tbl_horario = $("#tabla_horario").DataTable({
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
          "url":"../controller/horarios/controlador_listar_horarios.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE HORARIOS"
      },
        title: function() {
            return  "LISTA DE HORARIOS"
        }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE HORARIOS"
      },
    title: function() {
        return  "LISTA DE HORARIOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
        return  "LISTA DE HORARIOS"
  
    }
    }],
      "columns":[
        {"data":"id_año_academico"},
        {"data":"año_escolar"},
        {"data":"GRADO"},
        {"data":"Nivel_academico"},
        
        {           
             "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver horario'><i class='fa fa-eye'></i> Horario por aula</button>"
        },
        {"data":"HORARIO",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },

        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar componentes'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar componentes'><i class='fa fa-trash'></i> Eliminar</button>&nbsp;&nbsp; <button class='print btn btn-warning  btn-sm' title='Imprimir horario'><i class='fa fa-print'></i> Imprimir horario</button>"},

    ],

    "language":idioma_espanol,
    select: true
});
tbl_horario.on('draw.td',function(){
  var PageInfo = $("#tabla_horario").DataTable().page.info();
  tbl_horario.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}


// Función para cargar las aulas en el selector
// Función para cargar las aulas en el selector
// Función para cargar las aulas en el selector
function Cargar_Select_Grado() {
    $.ajax({
      url: "../controller/asignaturas/controlador_cargar_select_grado.php",
      type: 'POST',
    }).done(function(resp) {
      let data = JSON.parse(resp);
      let cadena = ""; // Inicializa la cadena vacía
      
      if (data.length > 0) {
        for (let i = 0; i < data.length; i++) {
          cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + " - " + data[i][2] + "</option>";
        }
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);
    
        // Llama a las funciones para cargar cursos y horas con el ID del aula seleccionado por defecto
        var id = $("#select_aula").val();
        Cargar_Select_curso(id, 'select_curso');
        Cargar_Select_horas(id);
  
        var id_editar = $("#select_aula_editar").val();
        Cargar_Select_curso(id_editar, 'select_curso_editar');
        Cargar_Select_horas(id_editar);
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
          cadena += "<option value='" + data[i][2] + "'>" + data[i][1] + "</option>";
        }
      } else {
        // Si no hay cursos disponibles
        cadena += "<option value='' disabled selected>Sin datos disponibles</option>";
      }
  
      $('#' + select_id).html(cadena); // Actualizar el contenido del selector
    });
  }
  
  // Función para cargar los horarios de la aula seleccionada
  function Cargar_Select_horas(id) {
    $.ajax({
      url: "../controller/horarios/controlador_cargar_select_horas.php",
      type: 'POST',
      data: {
        id: id
      }
    }).done(function(resp) {
      let data = JSON.parse(resp);
      let cadena = "<option value='' disabled selected>--SELECCIONE UN RANGO DE HORA--</option>";
  
      if (data.length > 0) {
        for (let i = 0; i < data.length; i++) {
          cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
        }
      } else {
        cadena += "<option value='' disabled>No se encontraron registros</option>";
      }
  
      $('#select_horas').html(cadena); // Actualizar el contenido del selector
      $('#select_horas_editar').html(cadena); // Actualizar el contenido del selector

    });
  }
  
  // Evento para cargar los cursos y horas cuando cambia el aula seleccionada
  $('#select_aula').on('change', function() {
    var id = $(this).val();
    Cargar_Select_curso(id, 'select_curso');
    Cargar_Select_horas(id);
  });
  
  $('#select_aula_editar').on('change', function() {
    var id = $(this).val();
    Cargar_Select_curso(id, 'select_curso_editar');
    Cargar_Select_horas(id, 'select_horas_editar');
  });
  

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
          document.getElementById('select_año_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año_editar').innerHTML=cadena;
      }
    })
  }
// Evento para abrir el modal de edición y cargar los datos correspondientes
$('#tabla_horario').on('click', '.editar', function() {
  var data = tbl_horario.row($(this).parents('tr')).data();

  if (tbl_horario.row(this).child.isShown()) {
    data = tbl_horario.row(this).data();
  }

  $("#modal_editar").modal('show');


  document.getElementById('select_año_editar').value = data.id_año_academico;

  // Cambiar el valor del aula y cargar cursos correspondientes
  $("#select_aula_editar").val(data.id_aula).trigger('change');
  listar_horario_editar(data.id_aula);

});

var tbl_traer_datos;
function listar_horario_editar(id) {

  tbl_traer_datos = $("#tabla_horario_aula_editar").DataTable({
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
    "url": "../controller/horarios/controlador_listar_horarios_editar.php",
    "type": 'POST',
    "data": { id: id },
   
    },
    "columns": [
      {"data": "id_hora"},
      {"data": "HORA"},
      {"data": "Id_detalle_asig_docente"},
      {"data": "nombre_asig"},
      {"data": "dia"},
      {"defaultContent": "<button class='delete btn btn-danger btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"}
    ],
    "language": idioma_espanol,
    "select": true
  });
}


function Eliminar_horario_unico(id){
  $.ajax({
    "url":"../controller/horarios/controlador_eliminar_horario_unico.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se elimino el curso con su hora con exito","success").then((value)=>{
          tbl_traer_datos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta hora por que esta siendo utilizado en otros formularios, verifique por favor","warning");

    }
  })
}

//ENVIANDO AL BOTON DELETE
$('#tabla_horario_aula_editar').on('click','.delete',function(){
  var data = tbl_traer_datos.row($(this).parents('tr')).data();

  if(tbl_traer_datos.row(this).child.isShown()){
      var data = tbl_traer_datos.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar la hora del curso: '+data.nombre_asig+' seleccionado?',
    text: "Una vez aceptado la hora sera eliminada!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_horario_unico(data.id_horario);
    }
  })
})




//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}



function Agregar_componente() {
    var idhora = $("#select_horas").val(); // ID de la hora
    var hora = $("#select_horas option:selected").text(); // Texto visible de la opción seleccionada
    var idasig = $("#select_curso").val(); // ID de la asignatura
    var asig = $("#select_curso option:selected").text(); // Texto visible de la opción seleccionada
    var dia = $("#select_dia").val(); // Día seleccionado

    // Validación de entradas vacías
    if (!idhora || !idasig || !dia) {
        return Swal.fire("Mensaje de Advertencia", "Todos los campos son obligatorios", "warning");
    }

    // Validación para duplicados
    if (verificarid(idhora, idasig, dia)) {
        return Swal.fire("Mensaje de Advertencia", "El horario para ese curso ya fue agregado a la tabla", "warning");
    }

    // Agregar la fila a la tabla
    var datos_agregar = "<tr>";
    datos_agregar += "<td for='id'>" + idhora + "</td>"; // ID de la hora
    datos_agregar += "<td>" + hora + "</td>"; // Texto de la hora
    datos_agregar += "<td for='id'>" + idasig + "</td>"; // ID de la asignatura
    datos_agregar += "<td>" + asig + "</td>"; // Texto de la asignatura
    datos_agregar += "<td for='id'>" + dia + "</td>"; // Día
    datos_agregar += "<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button></td>";
    datos_agregar += "</tr>";
    $("#tabla_horario_aula").append(datos_agregar);
}

function verificarid(idhora, idasig, dia) {
  if (!idhora || idhora.trim() === '' || !idasig || idasig.trim() === '' || !dia || dia.trim() === '') {
    return false; // Retorna false si alguno de los parámetros es null, undefined, o una cadena vacía
  }

  // Obtener todas las filas de la tabla
  let filas = document.querySelectorAll('#tabla_horario_aula tr');

  // Verificar si alguna fila ya tiene la misma combinación de aula, inicio y fin
  for (let fila of filas) {
    let celdas = fila.querySelectorAll('td');
    if (celdas.length > 3) { // Asegurarse de que no es una fila de cabecera o vacía
      let idhoraExistente = celdas[0].textContent.trim();
      let idasigExistente = celdas[2].textContent.trim();
      let fdiaExistente = celdas[4].textContent.trim();

      if (idhoraExistente === idhora.trim() && idasigExistente === idasig.trim() && fdiaExistente === dia.trim()) {
        return true; // Ya existe una fila con la misma combinación de aula, inicio y fin
      }
    }
  }
  return false;
}


function Agregar_componente_editar() {
  var idhora = $("#select_horas_editar").val(); // ID de la hora
  var hora = $("#select_horas_editar option:selected").text(); // Texto visible de la opción seleccionada
  var idasig = $("#select_curso_editar").val(); // ID de la asignatura
  var asig = $("#select_curso_editar option:selected").text(); // Texto visible de la opción seleccionada
  var dia = $("#select_dia_editar").val(); // Día seleccionado

  // Validación de entradas vacías
  if (!idhora || !idasig || !dia) {
      return Swal.fire("Mensaje de Advertencia", "Todos los campos son obligatorios", "warning");
  }

  // Validación para duplicados
  if (verificarid2(idhora, idasig, dia)) {
      return Swal.fire("Mensaje de Advertencia", "El horario para ese curso ya fue agregado a la tabla", "warning");
  }

  // Agregar la fila a la tabla
  var datos_agregar = "<tr>";
  datos_agregar += "<td for='id'>" + idhora + "</td>"; // ID de la hora
  datos_agregar += "<td>" + hora + "</td>"; // Texto de la hora
  datos_agregar += "<td for='id'>" + idasig + "</td>"; // ID de la asignatura
  datos_agregar += "<td>" + asig + "</td>"; // Texto de la asignatura
  datos_agregar += "<td for='id'>" + dia + "</td>"; // Día
  datos_agregar += "<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button></td>";
  datos_agregar += "</tr>";
  $("#tabla_horario_aula_editar").append(datos_agregar);
}

function verificarid2(idhora, idasig, dia) {
  if (!idhora || idhora.trim() === '' || !idasig || idasig.trim() === '' || !dia || dia.trim() === '') {
    return false; // Retorna false si alguno de los parámetros es null, undefined, o una cadena vacía
  }

  // Obtener todas las filas de la tabla
  let filas = document.querySelectorAll('#tabla_horario_aula_editar tr');

  // Verificar si alguna fila ya tiene la misma combinación de aula, inicio y fin
  for (let fila of filas) {
    let celdas = fila.querySelectorAll('td');
    if (celdas.length > 3) { // Asegurarse de que no es una fila de cabecera o vacía
      let idhoraExistente = celdas[0].textContent.trim();
      let idasigExistente = celdas[2].textContent.trim();
      let fdiaExistente = celdas[4].textContent.trim();

      if (idhoraExistente === idhora.trim() && idasigExistente === idasig.trim() && fdiaExistente === dia.trim()) {
        return true; // Ya existe una fila con la misma combinación de aula, inicio y fin
      }
    }
  }
  return false;
}


function remove(t){
  var td =t.parentNode;
  var tr=td.parentNode;
  var table =tr.parentNode;
  table.removeChild(tr);
}

//REGISTRANDO HORA AULA
function Registrar_horario_aula() {
    let componentes = [];

    // Recorrer cada fila de la tabla para obtener los datos
    $("#tabla_horario_aula tr").each(function() {
        var idhora = $(this).find('td[for="id"]').eq(0).text().trim(); // Obtener el id de la hora
        var idasig = $(this).find('td[for="id"]').eq(1).text().trim(); // Obtener el id de la asignatura
        var dia = $(this).find('td[for="id"]').eq(2).text().trim(); // Obtener el día

        // Validar que todos los campos estén presentes y correctos
        if (idhora && idasig  && dia) {
            componentes.push({
                idhora: idhora,
                idasig: idasig,
                dia: dia
            });
        }
    });

    // Validar si hay componentes para registrar
    if (componentes.length === 0) {
        return Swal.fire("Mensaje de Advertencia", "No hay componentes válidos en la tabla para registrar", "warning");
    }

    // Realizar la solicitud AJAX para registrar los componentes
    $.ajax({
        url: '../controller/horarios/controlador_registro_horario_aula.php',
        type: 'POST',
        data: {
            componentes: JSON.stringify(componentes)
        }
    }).done(function(resp) {
        if (resp > 0) {
            if (resp == 1) {
                Swal.fire("Mensaje de Confirmación", "Horario registrado satisfactoriamente!!!", "success").then(() => {
                    $("#tabla_horario_aula").empty(); // Limpia la tabla después de registrar
                    tbl_horario.ajax.reload(); // Recarga la tabla de datos
                    $("#modal_registro").modal('hide'); // Cierra el modal de registro
                });
            } else {
                Swal.fire("Mensaje de Advertencia", "El horario que estás intentando registrar ya existe en la base de datos, revise por favor", "warning");
            }
        } else {
            Swal.fire("Mensaje de Error", "No se completó el registro del horario!!", "error");
        }
    });
}

//EDITANDO ROL
function Modificar_horarios_aula() {
  let componentes = [];  // Se inicializa el array vacío cada vez que se ejecuta la función

  // Recorre cada fila de la tabla de edición para extraer los datos
  $("#tabla_horario_aula_editar tr").each(function() {
    var idhora = $(this).find('td').eq(0).text().trim();  // ID de la hora
    var idasig = $(this).find('td').eq(2).text().trim();  // ID de la asignatura
    var dia = $(this).find('td').eq(4).text().trim();     // Día

    // Validar que todos los campos estén llenos y no sean valores vacíos
    if (idhora && idasig && dia) {
      componentes.push({
        idhora: idhora,
        idasig: idasig,
        dia: dia
      });
    }
  });

  // Verificar si hay componentes válidos para enviar
  if (componentes.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "No hay componentes válidos en la tabla para modificar", "warning");
  }

  // Enviar la solicitud AJAX al servidor para modificar los componentes
  $.ajax({
    url: '../controller/horarios/controlador_modificar_horarios.php',
    type: 'POST',
    data: {
      componentes: JSON.stringify(componentes)  // Enviar solo los datos de la tabla actual
    }
  }).done(function(resp) {
    console.log("Respuesta del servidor:", resp);  // Verificar la respuesta del servidor
    if (resp == 1) {
      Swal.fire("Mensaje de Confirmación", "Componentes modificados satisfactoriamente!!!", "success").then(() => {
        $("#tabla_horario_aula_editar").empty();  // Vaciar la tabla después de la confirmación
        tbl_horario.ajax.reload();  // Recargar la tabla original (si es necesario)
        $("#modal_editar").modal('hide');  // Ocultar el modal de edición
      });
    } else {
      Swal.fire("Mensaje de Información", "No se modificaron componentes porque ya existen", "warning");
    }
  });
}











//ELIMINANDO HORARIO
function Eliminar_horario(id){
    $.ajax({
      "url":"../controller/horarios/controlador_eliminar_horario.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se eliminaron toda las asignaturas asignadas con un horario","success").then((value)=>{
            tbl_horario.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este horario por que esta siendo utilizado, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_horario').on('click','.delete',function(){
    var data = tbl_horario.row($(this).parents('tr')).data();
  
    if(tbl_horario.row(this).child.isShown()){
        var data = tbl_horario.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el horario de: '+data.Grado+'?',
      text: "Una vez aceptado, toda las horas asignadas a cada curso serán eliminados!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_horario(data.id_aula);
      }
    })
  })


//MOSTRAR
  var tbl_vistas;
  function listar_horas(id) {
    tbl_vistas = $("#tabla_vista_horario").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": { "regex": false },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 12,
        "destroy": true,
        pagingType: 'full_numbers',
        scrollCollapse: true,
        responsive: true,
        "async": false,
        "processing": true,
        "ajax": {
            "url": "../controller/horarios/controlador_listar_horarios_id.php",
            type: 'POST',
            data: { id: id },
        },
       "columns": [
    { 
        "data": "hora",
        "render": function(data, type, row) {
            return '<strong>' + data + '</strong>';
        }
    },
    { 
        "data": "Lunes",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Martes",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Miercoles",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Jueves",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    },
    { 
        "data": "Viernes",
        "render": function(data, type, row) {
            // Aplica el estilo en línea si el valor es "RECREO"
            return data === 'RECREO' 
                ? '<div style="background-color: yellow; text-align: center;">' + data + '</div>' 
                : data;
        }
    }
],

        "language": idioma_espanol,
        select: true
    });

    tbl_vistas.on('draw.td', function () {
        var PageInfo = $("#tabla_vista_horario").DataTable().page.info();
        tbl_vistas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
        });
    });
}
  
$('#tabla_horario').on('click','.mostrar',function(){
  var data = tbl_horario.row($(this).parents('tr')).data();

  if(tbl_horario.row(this).child.isShown()){
      var data = tbl_horario.row(this).data();
  }
$("#modal_ver_horario").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>AÑO ACADEMICO: "+data.año_escolar+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>AULA O GRADO: "+data.Grado+"</b>";
  listar_horas(data.id_aula);

})



