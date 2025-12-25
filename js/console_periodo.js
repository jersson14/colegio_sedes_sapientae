//LISTADO DE NIVEL ACADEMICO
var tbl_periodo;
function listar_periodo(){
    tbl_periodo = $("#tabla_periodo").DataTable({
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
          "url":"../controller/periodos/controlador_listar_periodos.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE PERIODOS"
      },
        title: function() {
            return  "LISTA DE PERIODOS"
        }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE PERIODOS"
      },
    title: function() {
        return  "LISTA DE PERIODOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
        return  "LISTA DE PERIODOS"
  
    }
    }],
      "columns":[
        {"data":"id_año_escolar"},
        {"data":"año_escolar"},
        {"data":"tipo_perido"},

        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostrar periodos'><i class='fa fa-eye'></i> Mostrar Periodos</button>"},

        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar datos'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_periodo.on('draw.td',function(){
  var PageInfo = $("#tabla_periodo").DataTable().page.info();
  tbl_periodo.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

//ENVIANDO DATOS PARA EDITAR
$('#tabla_periodo').on('click','.editar',function(){
  var data = tbl_periodo.row($(this).parents('tr')).data();

  if(tbl_periodo.row(this).child.isShown()){
      var data = tbl_periodo.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('select_año_editar').value=data.id_año_escolar;
  listar_periodo_año(data.id_año_escolar);

})
var tbl_traer_datos;
function listar_periodo_año(id) {
  tbl_traer_datos = $("#tabla_perio_editar").DataTable({
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
      "url": "../controller/periodos/controlador_listar_periodo_año.php",
      "type": 'POST',
      "data": {
        id: id
      },
    },
    "columns": [
      { "data": "id_año_escolar" },
      { "data": "tipo_perido" },
      { "data": "periodos" },
      { "data": "fecha_inicio" },
      { "data": "fecha_fin" },
      {"defaultContent": "<button class='delete btn btn-danger btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"}
    ],
    "language": idioma_espanol,
    "select": true
  });
}


function Eliminar_periodo_año(id){
  $.ajax({
    "url":"../controller/periodos/controlador_eliminar_periodo_unico.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se elimino el periodo con exito","success").then((value)=>{
          tbl_traer_datos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Advetencia","No se puede el periodo por que esta siendo utilizado en las notas, verifique por favor","warning");

    }
  })
}

//ENVIANDO AL BOTON DELETE
$('#tabla_perio_editar').on('click','.delete',function(){
  var data = tbl_traer_datos.row($(this).parents('tr')).data();

  if(tbl_traer_datos.row(this).child.isShown()){
      var data = tbl_traer_datos.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar periodo: '+data.periodos+' del año: '+data.año_escolar+'?',
    text: "Una vez aceptado el periodo sera eliminado!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_periodo_año(data.id_periodo);
    }
  })
})
//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}
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

  function Agregar_componente() {
    var año = $("#select_año").val();
    var tipo_pero = $("#tipo_periodo").val();
    var perio = $("#periodo").val();
    var inicio = $("#txt_fecha_inicio").val();
    var fin = $("#txt_fecha_fin").val();

    // Verificar si los campos no están vacíos
    if (!año || !tipo_pero || !perio || !inicio || !fin) {
        return Swal.fire("Mensaje de Advertencia", "Todos los campos son obligatorios", "warning");
    }

    // Verificar si ya existe la combinación de fechas y periodo en la tabla
    if (verificarFechas(perio, inicio, fin)) {
        return Swal.fire("Mensaje de Advertencia", "El componente con este periodo y estas fechas ya fue agregado a la tabla", "warning");
    }

    // Convertir fechas a formato Date para comparación
    const fechaInicioDate = new Date(inicio);
    const fechaFinDate = new Date(fin);

    // Validar que la fecha de inicio no sea mayor a la fecha de fin
    if (fechaInicioDate > fechaFinDate) {
        Swal.fire(
            "Mensaje de Advertencia",
            "La fecha de inicio no puede ser mayor que la fecha de fin",
            "warning"
        );
        return; // Salir de la función si la fecha de inicio es mayor que la fecha de fin
    }

    // Agregar la nueva fila si las fechas no existen en la tabla
    var datos_agregar = "<tr>";
    datos_agregar += "<td>" + año + "</td>";
    datos_agregar += "<td>" + tipo_pero + "</td>";
    datos_agregar += "<td for='id'>" + perio + "</td>";
    datos_agregar += "<td for='id'>" + inicio + "</td>";
    datos_agregar += "<td for='id'>" + fin + "</td>";
    datos_agregar += "<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button></td>";
    datos_agregar += "</tr>";
    $("#tabla_perio").append(datos_agregar);
}

function verificarFechas(periodo, fecha_inicio, fecha_fin) {
    // Validación para verificar que ninguno de los campos esté vacío
    if (!periodo || !fecha_inicio || !fecha_fin || periodo.trim() === '' || fecha_inicio.trim() === '' || fecha_fin.trim() === '') {
        return false; // Retorna false si las fechas o periodo están vacías o no son válidas
    }

    let filas = document.querySelectorAll('#tabla_perio tr');

    for (let i = 0; i < filas.length; i++) {
        let celdas = filas[i].querySelectorAll('td');

        if (celdas.length > 0) {
            let periodoTabla = celdas[2].textContent.trim(); // columna de periodo
            let fechaInicioTabla = celdas[3].textContent.trim(); // columna de fecha de inicio
            let fechaFinTabla = celdas[4].textContent.trim(); // columna de fecha de fin

            // Verificar si hay coincidencia de periodo, fecha de inicio y fecha de fin
            if (periodo === periodoTabla && fecha_inicio === fechaInicioTabla && fecha_fin === fechaFinTabla) {
                Swal.fire(
                    "Mensaje de Advertencia",
                    "Ya existe un registro con el mismo periodo y rango de fechas",
                    "warning"
                );
                return true; // Retorna true si hay coincidencia de fechas y periodo
            }
        }
    }

    return false; // Retorna false si no hay coincidencia de fechas y periodo
}

  
  
function Agregar_componente_editar() {
  var año = $("#select_año_editar").val();
  var tipo_pero = $("#tipo_periodo_editar").val();
  var perio = $("#periodo_editar").val();
  var inicio = $("#txt_fecha_inicio_editar").val();
  var fin = $("#txt_fecha_fin_editar").val();

  // Verificar si los campos no están vacíos
  if (!año || !tipo_pero || !perio || !inicio || !fin) {
      return Swal.fire("Mensaje de Advertencia", "Todos los campos son obligatorios", "warning");
  }

  // Verificar si ya existe la combinación de fechas y periodo en la tabla
  if (verificarFechasEditar(perio, inicio, fin)) {
      console.log(perio, inicio, fin);
      return Swal.fire("Mensaje de Advertencia", "El componente con este periodo y estas fechas ya fue agregado a la tabla", "warning");
  }

  // Convertir fechas a formato Date para comparación
  const fechaInicioDate = new Date(inicio);
  const fechaFinDate = new Date(fin);

  // Validar que la fecha de inicio no sea mayor a la fecha de fin
  if (fechaInicioDate > fechaFinDate) {
      Swal.fire(
          "Mensaje de Advertencia",
          "La fecha de inicio no puede ser mayor que la fecha de fin",
          "warning"
      );
      return; // Salir de la función si la fecha de inicio es mayor que la fecha de fin
  }

  // Agregar la nueva fila si las fechas no existen en la tabla
  var datos_agregar = "<tr>";
  datos_agregar += "<td>" + año + "</td>";
  datos_agregar += "<td>" + tipo_pero + "</td>";
  datos_agregar += "<td>" + perio + "</td>";
  datos_agregar += "<td>" + inicio + "</td>";
  datos_agregar += "<td>" + fin + "</td>";
  datos_agregar += "<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'></i></button></td>";
  datos_agregar += "</tr>";
  $("#tabla_perio_editar").append(datos_agregar);
}

function verificarFechasEditar(periodo, fecha_inicio, fecha_fin) {
  // Validación para verificar que ninguno de los campos esté vacío
  if (!periodo || !fecha_inicio || !fecha_fin || periodo.trim() === '' || fecha_inicio.trim() === '' || fecha_fin.trim() === '') {
      return false; // Retorna false si las fechas o periodo están vacías o no son válidas
  }

  let filas = document.querySelectorAll('#tabla_perio_editar tr');

  for (let i = 0; i < filas.length; i++) {
      let celdas = filas[i].querySelectorAll('td');

      if (celdas.length > 0) {
          let periodoTabla = celdas[2].textContent.trim(); // columna de periodo
          let fechaInicioTabla = celdas[3].textContent.trim(); // columna de fecha de inicio
          let fechaFinTabla = celdas[4].textContent.trim(); // columna de fecha de fin

          // Verificar si hay coincidencia de periodo, fecha de inicio y fecha de fin
          if (periodo === periodoTabla && fecha_inicio === fechaInicioTabla && fecha_fin === fechaFinTabla) {
              Swal.fire(
                  "Mensaje de Advertencia",
                  "Ya existe un registro con el mismo periodo y rango de fechas",
                  "warning"
              );
              return true; // Retorna true si hay coincidencia de fechas y periodo
          }
      }
  }

  return false; // Retorna false si no hay coincidencia de fechas y periodo
}

  
  
  
  function remove(t){
    var td =t.parentNode;
    var tr=td.parentNode;
    var table =tr.parentNode;
    table.removeChild(tr);
  }
  
//REGISTRANDO NIVEL ACADEMICO
function Registrar_Periodos() {
  let periodos = [];

  // Seleccionar todas las filas de la tabla excepto la fila de encabezado
  $("#tabla_perio tr").each(function() {
    // Obtener los valores de cada celda de la fila
    let año = $(this).find('td').eq(0).text().trim();
    let tipo_pero = $(this).find('td').eq(1).text().trim();
    let perio = $(this).find('td[for="id"]').eq(0).text().trim();
    let inicio = $(this).find('td[for="id"]').eq(1).text().trim();
    let fin = $(this).find('td[for="id"]').eq(2).text().trim();

    // Validar que las fechas no estén vacías
    if (año && tipo_pero && perio && inicio && fin) {
      periodos.push({
        año_escolar: año,
        tipo_periodo: tipo_pero,
        periodo: perio,
        fecha_inicio: inicio,
        fecha_fin: fin
      });
    }
  });

  // Validar si hay datos para registrar
  if (periodos.length === 0) {
    return Swal.fire("Mensaje de Advertencia", "No hay datos válidos en la tabla para registrar", "warning");
  }

  // Enviar datos al servidor mediante AJAX
  $.ajax({
    url: '../controller/periodos/controlador_registro_periodos.php', // Cambia esta URL a la ruta de tu controlador PHP
    type: 'POST',
    data: {
      periodos: JSON.stringify(periodos)  // Enviar los datos como una cadena JSON
    }
  }).done(function(resp) {
    if (resp > 0) {
      if (resp == 1) {
        Swal.fire("Mensaje de Confirmación", "Periodos registrados satisfactoriamente!!!", "success").then(() => {
          tbl_periodo.ajax.reload(); // Recargar la tabla si estás usando DataTables
          $("#modal_registro").modal('hide');
        });
      } else {
        Swal.fire("Mensaje de Advertencia", "Uno o más períodos ya existen en la base de datos, revise por favor", "warning");
      }
    } else {
      Swal.fire("Mensaje de Error", "No se completó el registro de los períodos!!", "error");
    }
  }).fail(function(jqXHR, textStatus, errorThrown) {
    Swal.fire("Mensaje de Error", "Ocurrió un error durante el registro: " + errorThrown, "error");
  });
}

//EDITANDO ROL
function Modificar_Periodos() {
    let periodos = [];
    
    $("#tabla_perio_editar tr").each(function() {
        var año = $(this).find('td').eq(0).text().trim();
        var tipo_pero = $(this).find('td').eq(1).text().trim();
        var perio = $(this).find('td').eq(2).text().trim();
        var inicio = $(this).find('td').eq(3).text().trim();
        var fin = $(this).find('td').eq(4).text().trim();
        
        // Validar que los campos no estén vacíos
        if (año && tipo_pero && perio && inicio && fin) {
            periodos.push({
                año: año,
                tipo_periodo: tipo_pero,
                periodo: perio,
                fecha_inicio: formatDate(inicio),
                fecha_fin: formatDate(fin)
            });
        }
    });

    if (periodos.length === 0) {
        return Swal.fire("Mensaje de Advertencia", "No hay periodos válidos en la tabla para registrar", "warning");
    }

    $.ajax({
        url: '../controller/periodos/controlador_modificar_periodos.php',
        type: 'POST',
        data: {
            periodos: JSON.stringify(periodos)
        }
    }).done(function(resp) {
        if (resp == 1) {
            Swal.fire("Mensaje de Confirmación", "Períodos registrados satisfactoriamente!!!", "success").then(() => {
                tbl_periodo.ajax.reload();
                $("#modal_editar").modal('hide');
            });
        } else if (resp == 2) {
            Swal.fire("Mensaje de Información", "No se registraron nuevos períodos porque ya existen", "info");
        } else {
            Swal.fire("Mensaje de Error", "No se completó el registro de los períodos, tiene un error en el último registro!!", "error");
        }
    });
}

function formatDate(dateStr) {
    // Supongamos que el formato de entrada es DD-MM-YYYY
    var parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`; // Retorna en formato YYYY-MM-DD
    }
    return null; // Retorna null si el formato es incorrecto
}


function formatDate(dateStr) {
  // Supongamos que el formato de entrada es DD-MM-YYYY
  var parts = dateStr.split('-');
  return `${parts[2]}-${parts[1]}-${parts[0]}`; // Retorna en formato YYYY-MM-DD
}


//ELIMINANDO ROL
function Eliminar_Periodo(id){
    $.ajax({
      "url":"../controller/periodos/controlador_eliminar_periodos.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el periodo correctamente!!!","success").then((value)=>{
            tbl_periodo.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advertencia","No se puede eliminar este periodo por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_periodo').on('click','.delete',function(){
    var data = tbl_periodo.row($(this).parents('tr')).data();
  
    if(tbl_periodo.row(this).child.isShown()){
        var data = tbl_periodo.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el periodo: '+data.tipo_perido+' del año : '+data.año_escolar+'?',
      text: "Una vez aceptado el periodo se eliminar conjuntamente con todo sus items que se registraron!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Periodo(data.id_año_escolar);
      }
    })
  })



  var tbl_vistas;
  function listar_perio(id) {
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
            "url": "../controller/periodos/controlador_listar_periodo_año.php",
            type: 'POST',
            data: { id: id },
        },
        "columns": [
            { "data": "id_periodo" },
            { "data": "periodos" },
            { "data": "fecha_formateada" },
            { "data": "fecha_formateada2" },
            {"data":"estado",
              render: function(data,type,row){
                      if(data=='EN CURSO'){
                      return '<span class="badge bg-success">EN CURSO</span>';
                      }else{
                      return '<span class="badge bg-danger">FINALIZADO</span>';
                      }
              }   
          },
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
  
$('#tabla_periodo').on('click','.mostrar',function(){
  var data = tbl_periodo.row($(this).parents('tr')).data();

  if(tbl_periodo.row(this).child.isShown()){
      var data = tbl_periodo.row(this).data();
  }
$("#modal_ver_perio").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>AÑO: "+data.año_escolar+"</b>";
  document.getElementById('lb_titulo2').innerHTML="<b>TIPO DE PERIODO: "+data.tipo_perido+"</b>";
  listar_perio(data.id_año_escolar);

})

