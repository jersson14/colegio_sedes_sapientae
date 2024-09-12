//LISTADO DE ROLES
var tbl_aula_horas;
function listar_aula_hora(){
    tbl_aula_horas = $("#tabla_aula_horas").DataTable({
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
          "url":"../controller/aula_horas/controlador_listar_aula_horas.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE HORAS POR AULA"
      },
        title: function() {
            return  "LISTA DE HORAS POR AULA"
        }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE HORAS POR AULA"
      },
    title: function() {
        return  "LISTA DE HORAS POR AULA"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
        return  "LISTA DE HORAS POR AULA"
  
    }
    }],
      "columns":[
        {"data":"id_año_academico"},
        {"data":"año_escolar"},
        {"data":"Grado"},
        {"data":"Nivel_academico"},
        {"data":"turno"},
        {           
             "defaultContent": "<button class='mostrar btn btn-success btn-sm' title='Ver horas aula'><i class='fa fa-eye'></i> Horas por aula</button>"
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
tbl_aula_horas.on('draw.td',function(){
  var PageInfo = $("#tabla_aula_horas").DataTable().page.info();
  tbl_aula_horas.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}


// Función para cargar las aulas en el selector
// Función para cargar las aulas en el selector
// Función para cargar las aulas en el selector
function Cargar_Select_Grado(){
    $.ajax({
      "url":"../controller/asignaturas/controlador_cargar_select_grado.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

        var id =$("#select_aula").val();
        Traernivel(id);

        var id =$("#select_aula_editar").val();
        Traernivel(id);
      }else{
        cadena+="<option value=''>No se encontraron regitros</option>";
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

      }
    })
  }
  function Traernivel(id){
    $.ajax({
      "url":"../controller/matricula/controlador_traernivel.php",
      type:'POST',
          data:{
            id:id
          }
        }).done(function(resp){
          
        var data = JSON.parse(resp);
        var cadena="";
        if(data.length>0){
          $("#txt_nivel").val(data[0][1]);
          $("#txt_nivel_editar").val(data[0][1]);

        }
        else{
          cadena+="<option value=''>No se encontraron regitros</option>";
          $('#txt_nivel').html(cadena);
          $("#txt_nivel_editar").val(data[0][1]);

      }
    })
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
          document.getElementById('select_año_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año_editar').innerHTML=cadena;
      }
    })
  }
// Evento para abrir el modal de edición y cargar los datos correspondientes
$('#tabla_aula_horas').on('click', '.editar', function() {
  var data = tbl_aula_horas.row($(this).parents('tr')).data();

  if (tbl_aula_horas.row(this).child.isShown()) {
    data = tbl_aula_horas.row(this).data();
  }

  $("#modal_editar").modal('show');
  listar_horas_aula_editar(data.id_aula);


  document.getElementById('select_año_editar').value = data.id_año_academico;

  // Cambiar el valor del aula y cargar cursos correspondientes
  $("#select_aula_editar").val(data.id_aula).trigger('change');

  // Esperar a que el cambio en select_aula_editar se aplique antes de establecer el curso
  $("#select_aula_editar").on('change', function() {
    setTimeout(function() {
      $("#txt_nivel_editar").val(data.Nivel_academico).trigger('change.select2');
    }, 100); // Reducido el tiempo de espera
  });
  // Cargar los datos en la tabla
});

var tbl_traer_datos;
function listar_horas_aula_editar(id) {

  tbl_traer_datos = $("#tabla_aula_hora_editar").DataTable({
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
    "url": "../controller/aula_horas/controlador_listar_hora_aula_id2.php",
    "type": 'POST',
    "data": { id: id },
    "dataSrc": function (json) {
        console.log("Respuesta del servidor:", json); // Añade esta línea para depuración
        return json.aaData;
        }
    },
    "columns": [
      {"data": "id_año_academico"},
      {"data": "id_aula"},
      {"data": "turno"},
      {"data": "hora_inicio"},
      {"data": "hora_fin"},
      {"defaultContent": "<button class='delete btn btn-danger btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"}
    ],
    "language": idioma_espanol,
    "select": true
  });
}


function Eliminar_horas_curso_unico(id){
  $.ajax({
    "url":"../controller/aula_horas/controlador_eliminar_horas_curso_unico.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se elimino la hora con exito","success").then((value)=>{
          tbl_traer_datos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta hora por que esta siendo utilizado en las notas, verifique por favor","warning");

    }
  })
}

//ENVIANDO AL BOTON DELETE
$('#tabla_aula_hora_editar').on('click','.delete',function(){
  var data = tbl_traer_datos.row($(this).parents('tr')).data();

  if(tbl_traer_datos.row(this).child.isShown()){
      var data = tbl_traer_datos.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar la hora seleccionada?',
    text: "Una vez aceptado la hora sera eliminada!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_horas_curso_unico(data.id_hora);
    }
  })
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}



function Agregar_componente() {
  var año = $("#select_año").val();
  var aula = $("#select_aula").val(); // Cambiado para obtener el id en lugar del texto
  var turno = $("#select_turno").val();
  var inicio = $("#hora_inicio").val();
  var fin = $("#hora_fin").val();

  // Validación de entradas vacías
  if (!año || !aula || !turno || !inicio || !fin) {
    return Swal.fire("Mensaje de Advertencia", "Todos los campos son obligatorios", "warning");
  }

  // Conversión de horas a formato numérico para comparaciones
  var horaInicio = parseInt(inicio.split(':')[0], 10);
  var horaFin = parseInt(fin.split(':')[0], 10);

  // Validación para turno "mañana"
  if (turno === "mañana" && horaInicio >= 15) {
    return Swal.fire("Mensaje de Advertencia", "No se puede agregar una hora de inicio después de las 15:00 para el turno de mañana", "warning");
  }

  // Validación para turno "tarde"
  if (turno === "tarde" && (horaInicio < 15 || horaFin < 15)) {
    return Swal.fire("Mensaje de Advertencia", "No se pueden agregar horas de inicio o fin antes de las 15:00 para el turno de tarde", "warning");
  }

  // Validación de rango de tiempo
  if (inicio >= fin) {
    return Swal.fire("Mensaje de Advertencia", "La hora de inicio debe ser menor que la hora de fin", "warning");
  }

  // Validación para duplicados
  if (verificarid(aula, inicio, fin)) {
    return Swal.fire("Mensaje de Advertencia", "La hora ya fue agregado a la tabla", "warning");
  }

  var datos_agregar = "<tr>";
  datos_agregar += "<td>" + año + "</td>";
  datos_agregar += "<td for='id'>" + aula + "</td>";
  datos_agregar += "<td>" + turno + "</td>";
  datos_agregar += "<td for='id'>" + inicio + "</td>";
  datos_agregar += "<td for='id'>" + fin + "</td>";
  datos_agregar += "<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button></td>";
  datos_agregar += "</tr>";
  $("#tabla_aula_hora").append(datos_agregar);
}

function verificarid(aula, inicio, fin) {
  if (!aula || aula.trim() === '' || !inicio || inicio.trim() === '' || !fin || fin.trim() === '') {
    return false; // Retorna false si alguno de los parámetros es null, undefined, o una cadena vacía
  }

  // Obtener todas las filas de la tabla
  let filas = document.querySelectorAll('#tabla_aula_hora tr');

  // Verificar si alguna fila ya tiene la misma combinación de aula, inicio y fin
  for (let fila of filas) {
    let celdas = fila.querySelectorAll('td');
    if (celdas.length > 0) { // Asegurarse de que no es una fila de cabecera o vacía
      let aulaExistente = celdas[1].textContent.trim();
      let inicioExistente = celdas[3].textContent.trim();
      let finExistente = celdas[4].textContent.trim();

      if (aulaExistente === aula.trim() && inicioExistente === inicio.trim() && finExistente === fin.trim()) {
        return true; // Ya existe una fila con la misma combinación de aula, inicio y fin
      }
    }
  }
  return false;
}


function Agregar_componente_editar() {
  var año = $("#select_año_editar").val();
  var aula = $("#select_aula_editar").val(); // Cambiado para obtener el id en lugar del texto
  var turno = $("#select_turno_editar").val();
  var inicio = $("#hora_inicio_editar").val();
  var fin = $("#hora_fin_editar").val();

  // Validación de entradas vacías
  if (!año || !aula || !turno || !inicio || !fin) {
    return Swal.fire("Mensaje de Advertencia", "Todos los campos son obligatorios", "warning");
  }

  // Conversión de horas a formato numérico para comparaciones
  var horaInicio = parseInt(inicio.split(':')[0], 10);
  var horaFin = parseInt(fin.split(':')[0], 10);

  // Validación para turno "mañana"
  if (turno === "mañana" && horaInicio >= 15) {
    return Swal.fire("Mensaje de Advertencia", "No se puede agregar una hora de inicio después de las 15:00 para el turno de mañana", "warning");
  }

  // Validación para turno "tarde"
  if (turno === "tarde" && (horaInicio < 15 || horaFin < 15)) {
    return Swal.fire("Mensaje de Advertencia", "No se pueden agregar horas de inicio o fin antes de las 15:00 para el turno de tarde", "warning");
  }

  // Validación de rango de tiempo
  if (inicio >= fin) {
    return Swal.fire("Mensaje de Advertencia", "La hora de inicio debe ser menor que la hora de fin", "warning");
  }

  // Validación para duplicados
  if (verificarid2(aula, inicio, fin)) {
    return Swal.fire("Mensaje de Advertencia", "La hora ya fue agregado a la tabla", "warning");
  }

  var datos_agregar = "<tr>";
  datos_agregar += "<td>" + año + "</td>";
  datos_agregar += "<td for='id'>" + aula + "</td>";
  datos_agregar += "<td>" + turno + "</td>";
  datos_agregar += "<td for='id'>" + inicio + "</td>";
  datos_agregar += "<td for='id'>" + fin + "</td>";
  datos_agregar += "<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button></td>";
  datos_agregar += "</tr>";
  $("#tabla_aula_hora_editar").append(datos_agregar);
}

function verificarid2(aula, inicio, fin) {
  if (!aula || aula.trim() === '' || !inicio || inicio.trim() === '' || !fin || fin.trim() === '') {
    return false; // Retorna false si alguno de los parámetros es null, undefined, o una cadena vacía
  }

  // Obtener todas las filas de la tabla
  let filas = document.querySelectorAll('#tabla_aula_hora_editar tr');

  // Verificar si alguna fila ya tiene la misma combinación de aula, inicio y fin
  for (let fila of filas) {
    let celdas = fila.querySelectorAll('td');
    if (celdas.length > 0) { // Asegurarse de que no es una fila de cabecera o vacía
      let aulaExistente = celdas[1].textContent.trim();
      let inicioExistente = celdas[3].textContent.trim();
      let finExistente = celdas[4].textContent.trim();

      if (aulaExistente === aula.trim() && inicioExistente === inicio.trim() && finExistente === fin.trim()) {
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
function Registrar_aula_hora() {
  let componentes = [];
  
  $("#tabla_aula_hora tr").each(function() {
    var año = $(this).find('td').eq(0).text().trim(); // Obtener el año
    var aula = $(this).find('td[for="id"]').eq(0).text().trim(); // Obtener el id del aula
    var turno = $(this).find('td').eq(2).text().trim();
    var inicio = $(this).find('td[for="id"]').eq(1).text().trim(); // Obtener hora de inicio
    var fin = $(this).find('td[for="id"]').eq(2).text().trim(); // Obtener hora de fin

    // Validar que todos los campos estén presentes y correctos
    if (año && aula && turno && inicio && fin) {
      componentes.push({
        año: año,
        aula: aula,
        turno: turno,
        inicio: inicio,
        fin: fin
      });
    }
  });

  // Validar si hay componentes para registrar
  if (componentes.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "No hay componentes válidos en la tabla para registrar", "warning");
  }

  $.ajax({
    url: '../controller/aula_horas/controlador_registro_aula_horas.php',
    type: 'POST',
    data: {
      componentes: JSON.stringify(componentes)
    }
  }).done(function(resp) {
    if (resp > 0) {
      if (resp == 1) {
        Swal.fire("Mensaje de Confirmación", "horas registradas satisfactoriamente!!!", "success").then(() => {
          $("#tabla_aula_hora").empty(); // Limpia la tabla después de registrar
          tbl_aula_horas.ajax.reload(); // Recarga la tabla de datos
          $("#modal_registro").modal('hide'); // Cierra el modal de registro
        });
      } else {
        Swal.fire("Mensaje de Advertencia", "Las horas que estás intentando registrar ya existe en la base de datos, revise por favor", "warning");
      }
    } else {
      Swal.fire("Mensaje de Error", "No se completó el registro de los componentes!!", "error");
    }
  });
}

//EDITANDO ROL
function Modificar_aula_horas() {
  let componentes = [];
  
  // Recorre cada fila de la tabla de edición para extraer los datos
  $("#tabla_aula_hora_editar tr").each(function() {
    var año = $(this).find('td').eq(0).text().trim();
    var aula = $(this).find('td[for="id"]').eq(0).text().trim();
    var turno = $(this).find('td').eq(2).text().trim();
    var inicio = $(this).find('td[for="id"]').eq(1).text().trim();
    var fin = $(this).find('td[for="id"]').eq(2).text().trim();

    // Validar que todos los campos estén llenos y no sean valores por defecto
    if (año && aula && turno && inicio && fin && aula !== "--SELECCIONE--") {
      componentes.push({
        año: año,
        aula: aula,
        turno: turno,
        inicio: inicio,
        fin: fin
      });
    }
  });

  // Verificar si hay componentes válidos para enviar
  if (componentes.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "No hay componentes válidos en la tabla para registrar", "warning");
  }

  // Enviar la solicitud AJAX al servidor para modificar los componentes
  $.ajax({
    url: '../controller/aula_horas/controlador_modificar_aula_horas.php',
    type: 'POST',
    data: {
      componentes: JSON.stringify(componentes)
    }
  }).done(function(resp) {
    if (resp == 1) {
      Swal.fire("Mensaje de Confirmación", "Componentes modificados satisfactoriamente!!!", "success").then(() => {
        $("#tabla_aula_hora_editar").empty();  // Vaciar la tabla después de la confirmación
        tbl_aula_horas.ajax.reload();  // Recargar la tabla original (si es necesario)
        $("#modal_editar").modal('hide');  // Ocultar el modal de edición
      });
    } else {
      Swal.fire("Mensaje de Información", "No se modificaron componentes porque ya existen", "warning");
    }
  });
}









//ELIMINANDO ROL
function Eliminar_horas_aula(id){
    $.ajax({
      "url":"../controller/aula_horas/controlador_eliminar_aula_horas.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se eliminaron toda las horas asignadas del aula seleccionada","success").then((value)=>{
            tbl_aula_horas.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar estas horas por que ya tiene horarios registrados, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_aula_horas').on('click','.delete',function(){
    var data = tbl_aula_horas.row($(this).parents('tr')).data();
  
    if(tbl_aula_horas.row(this).child.isShown()){
        var data = tbl_aula_horas.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar las horas del aula de: '+data.Grado+'?',
      text: "Una vez aceptado, toda las horas serán eliminados!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_horas_aula(data.id_aula);
      }
    })
  })


//MOSTRAR
  var tbl_vistas;
  function listar_horas(id) {
    tbl_vistas = $("#tabla_vistahoras").DataTable({
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
            "url": "../controller/aula_horas/controlador_listar_hora_aula_id.php",
            type: 'POST',
            data: { id: id },
        },
        "columns": [
            { "data": "id_hora" },
            { "data": "turno" },
            { "data": "hora_inicio" },
            { "data": "hora_fin" },
        ],
        "language": idioma_espanol,
        select: true
    });

    tbl_vistas.on('draw.td', function () {
        var PageInfo = $("#tabla_vistahoras").DataTable().page.info();
        tbl_vistas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = i + 1 + PageInfo.start;
        });
    });
}
  
$('#tabla_aula_horas').on('click','.mostrar',function(){
  var data = tbl_aula_horas.row($(this).parents('tr')).data();

  if(tbl_aula_horas.row(this).child.isShown()){
      var data = tbl_aula_horas.row(this).data();
  }
$("#modal_ver_horas").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>AÑO ACADEMICO: "+data.año_escolar+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>AULA O GRADO: "+data.Grado+"</b>";
  listar_horas(data.id_aula);

})



