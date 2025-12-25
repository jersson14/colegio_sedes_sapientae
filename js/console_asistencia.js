//LISTADO DE ROLES
var tbl_asistencia;
function listar_asistencia(){
    tbl_asistencia = $("#tabla_asistencia").DataTable({
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
          "url":"../controller/asistencias/controlador_listar_asistencias.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE ASISTENCIA"
      },
        title: function() {
          return  "LISTA DE ASISTENCIA" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE ASISTENCIA"
      },
    title: function() {
      return  "LISTA DE ASISTENCIA"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE ASISTENCIA"
  
    }
    }],
      "columns":[
        {"data":"id_año"},
        {"data":"año_escolar"},
        {"data":"Grado"},
        {"data":"seccion_nombre"},
        {"data":"Nivel_academico",
            render: function(data,type,row){
                if(data=='INICIAL'){
                return '<span class="badge bg-warning">INICIAL</span>';
                }else if(data=='PRIMARIA'){
                return '<span class="badge bg-success">PRIMARIA</span>';
                }else if(data=='SECUNDARIA'){
                  return '<span class="badge bg-primary">SECUNDARIA</span>';
                  }else{
                return '<span class="badge bg-dark">TODOS</span>';
                }
        }
        },
        {"data":"fecha_formateada"},
        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Ver asistencia'><i class='fa fa-eye'></i> Ver asistencia de alumnos</button>"},
        {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar asistencia'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar asistencia'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_asistencia.on('draw.td',function(){
  var PageInfo = $("#tabla_asistencia").DataTable().page.info();
  tbl_asistencia.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}


function listar_asistencia_fechas(){
  let fechainicio = document.getElementById('txtfechainicio').value;
  let fechafin = document.getElementById('txtfechafin').value;

  tbl_asistencia = $("#tabla_asistencia").DataTable({
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
        "url":"../controller/asistencias/controlador_listar_asistencias_fechas.php",
        type:'POST',
        data:{
          fechainicio:fechainicio,
          fechafin:fechafin
        }
    },
    dom: 'Bfrtip', 
   
    buttons:[ 
      
  {
    extend:    'excelHtml5',
    text:      '<i class="fas fa-file-excel"></i> ',
    titleAttr: 'Exportar a Excel',
    
    filename: function() {
      return  "LISTA DE ASISTENCIA"
    },
      title: function() {
        return  "LISTA DE ASISTENCIA" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE ASISTENCIA"
    },
  title: function() {
    return  "LISTA DE ASISTENCIA"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE ASISTENCIA"

  }
  }],
    "columns":[
      {"data":"id_año"},
      {"data":"año_escolar"},
      {"data":"Grado"},
      {"data":"seccion_nombre"},
      {"data":"Nivel_academico",
          render: function(data,type,row){
              if(data=='INICIAL'){
              return '<span class="badge bg-warning">INICIAL</span>';
              }else if(data=='PRIMARIA'){
              return '<span class="badge bg-success">PRIMARIA</span>';
              }else if(data=='SECUNDARIA'){
                return '<span class="badge bg-primary">SECUNDARIA</span>';
              }else{
              return '<span class="badge bg-dark">TODOS</span>';
              }
      }
      },
      {"data":"fecha_formateada"},
      {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Ver asistencia'><i class='fa fa-eye'></i> Ver asistencia de alumnos</button>"},
      {"defaultContent":"<button class='editar btn btn-primary  btn-sm' title='Editar asistencia'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar asistencia'><i class='fa fa-trash'></i> Eliminar</button>"},
      
  ],

  "language":idioma_espanol,
  select: true
});
tbl_asistencia.on('draw.td',function(){
var PageInfo = $("#tabla_asistencia").DataTable().page.info();
tbl_asistencia.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}





//TRAEN ESTUDIANTES:
function listar_alumnos(){
    let grado = document.getElementById('select_aula').value;

    tbl_alumnos_asistencia = $("#tabla_alumnos").DataTable({
        "ordering":false,   
        "bLengthChange":true,
        "searching": { "regex": false },
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
        "pageLength": 10,
        "destroy":true,
        "pagingType": 'full_numbers',
        "scrollCollapse": true,
        "responsive": true,
        "async": false,
        "processing": true,
        "ajax":{
            "url":"../controller/asistencias/controlador_listar_alumnos_por_grado.php",
            type:'POST',
            data:{
                grado:grado
            }
        },
        dom: 'Bfrtip', 
        buttons:[ 
            {
                extend:    'excelHtml5',
                text:      '<i class="fas fa-file-excel"></i> ',
                titleAttr: 'Exportar a Excel',
                filename: function() {
                    return  "LISTA DE ALUMNOS - ASISTENCIA";
                },
                title: function() {
                    return  "LISTA DE ALUMNOS - ASISTENCIA";
                }
            },
            {
                extend:    'pdfHtml5',
                text:      '<i class="fas fa-file-pdf"></i> ',
                titleAttr: 'Exportar a PDF',
                filename: function() {
                    return  "LISTA DE ALUMNOS - ASISTENCIA";
                },
                title: function() {
                    return  "LISTA DE ALUMNOS - ASISTENCIA";
                }
            },
            {
                extend:    'print',
                text:      '<i class="fa fa-print"></i> ',
                titleAttr: 'Imprimir',
                title: function() {
                    return  "LISTA DE ALUMNOS - ASISTENCIA";
                }
            }
        ],
        "columns":[
            {
                "data":"id_matricula",
                "render": function(data, type, row, meta){
                    return "<input type='number' disabled style='width: 70px;text-align:center' id='id_matri' class='form-control input-small' value='" + (data || '') + "' />";
                }
            },
            {"data":"alum_dni"},
            {"data":"Estudiante"},
            {
                "data": null, 
                "render": function(data, type, row, meta){
                    // Obtener la fecha y hora actual de la PC y formatearla para datetime-local
                    let now = new Date();
                    let year = now.getFullYear();
                    let month = ('0' + (now.getMonth() + 1)).slice(-2);
                    let day = ('0' + now.getDate()).slice(-2);

                    let formattedDateTime = `${year}-${month}-${day}`;
                    return `<input disabled style="text-align:center" type="date" class="form-control" id="txt_fecha_asis" value="${formattedDateTime}">`;
                }
            },
            {
                "data": null,  // No asociada a ningún dato
                "render": function(data, type, row, meta){
                    return `
                        <select class="form-control" id="select_estado">
                            <option value="PRESENTE">PRESENTE</option>
                            <option value="TARDE">TARDE</option>
                            <option value="AUSENTE">AUSENTE</option>
                            <option value="JUSTIFICADO">JUSTIFICADO</option>
                        </select>`;
                }
            },
            {
                "data": null,  // No asociada a ningún dato
                "render": function(data, type, row, meta){
                    return `<textarea class="form-control" id="txt_observa" rows="2"></textarea>`;
                }
            }
        ],
        "language":idioma_espanol,
        select: true
    });

    tbl_alumnos_asistencia.on('draw.td', function(){
        var PageInfo = $("#tabla_alumnos").DataTable().page.info();
        tbl_alumnos_asistencia.column(0, {page: 'current'}).nodes().each(function(cell, i){
            cell.tbl_alumnos_asistencia = i + 1 + PageInfo.start;
        });
    });
}





//EDITAR

function listar_alumnos_editar(){
  let fecha = document.getElementById('txt_fecha_editar').value;
  let aula = document.getElementById('select_aula_editar').value;
  console.log(fecha, aula); // Verifica los valores

  tbl_alumnos_asistencia = $("#tabla_alumnos_editar").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      "destroy":true,
      "pagingType": 'full_numbers',
      "scrollCollapse": true,
      "responsive": true,
      "async": false,
      "processing": true,
      "ajax":{
        "url": "../controller/asistencias/controlador_listar_alumnos_asistencia.php",
        type:'POST',
        data: { fecha: fecha,aula,aula },
        dataSrc: function (json) {
          console.log(json); // Verifica la estructura de la respuesta
          return json.data;  // Asegúrate de que 'data' es la clave correcta
      }
      },
      dom: 'Bfrtip', 
      buttons:[ 
          {
              extend:    'excelHtml5',
              text:      '<i class="fas fa-file-excel"></i> ',
              titleAttr: 'Exportar a Excel',
              filename: function() {
                  return  "LISTA DE ALUMNOS - ASISTENCIA";
              },
              title: function() {
                  return  "LISTA DE ALUMNOS - ASISTENCIA";
              }
          },
          {
              extend:    'pdfHtml5',
              text:      '<i class="fas fa-file-pdf"></i> ',
              titleAttr: 'Exportar a PDF',
              filename: function() {
                  return  "LISTA DE ALUMNOS - ASISTENCIA";
              },
              title: function() {
                  return  "LISTA DE ALUMNOS - ASISTENCIA";
              }
          },
          {
              extend:    'print',
              text:      '<i class="fa fa-print"></i> ',
              titleAttr: 'Imprimir',
              title: function() {
                  return  "LISTA DE ALUMNOS - ASISTENCIA";
              }
          }
      ],
      "columns":[
          {
              "data":"id_asistencia",
              "render": function(data, type, row, meta){
                  return "<input type='number' disabled style='width: 70px;text-align:center' id='id_asis_editar' class='form-control input-small' value='" + (data || '') + "' />";
              }
          },
          {"data":"alum_dni"},
          {"data":"Estudiante"},
          {
              "data": "fecha", 
              "render": function(data, type, row, meta){
                  // Obtener la fecha y hora actual de la PC y formatearla para datetime-local                  return "<input type='number' disabled style='width: 70px;text-align:center' id='id_asis_editar' class='form-control input-small' value='" + (data || '') + "' />";
                  return "<input disabled type='date' style='text-align:center' id='txt_fecha_asis_editar' class='form-control input-small' value='" + (data || '') + "' />";

              }
          },
          {
            "data": "estado",  // Cambiar de null a la propiedad 'estado' de tu objeto de datos
            "render": function(data, type, row, meta){
                return `
                    <select class="form-control" id="select_estado_editar">
                        <option value="PRESENTE" ${data === 'PRESENTE' ? 'selected' : ''}>PRESENTE</option>
                        <option value="TARDE" ${data === 'TARDE' ? 'selected' : ''}>TARDE</option>
                        <option value="AUSENTE" ${data === 'AUSENTE' ? 'selected' : ''}>AUSENTE</option>
                        <option value="JUSTIFICADO" ${data === 'JUSTIFICADO' ? 'selected' : ''}>JUSTIFICADO</option>
                    </select>`;
            }
          },
          {
              "data": null,  // No asociada a ningún dato
              "render": function(data, type, row, meta){
                  return `<textarea class="form-control" id="txt_observa_editar" rows="2"></textarea>`;
              }
          }
      ],
      "language":idioma_espanol,
      select: true
  });

  tbl_alumnos_asistencia.on('draw.td', function(){
      var PageInfo = $("#tabla_alumnos_editar").DataTable().page.info();
      tbl_alumnos_asistencia.column(0, {page: 'current'}).nodes().each(function(cell, i){
          cell.tbl_alumnos_asistencia = i + 1 + PageInfo.start;
      });
  });
}




function listar_alumnos_totales() {
  let año = document.getElementById('select_año2').value;

  let mes = document.getElementById('select_meses').value;
  let aula = document.getElementById('select_aula_buscar').value;
  console.log(mes, aula); // Verifica los valores

  // Inicializa o reinicializa el DataTable
  tbl_alumnos_asistencia2 = $("#tabla_asistencia_totales").DataTable({
    "ordering": false,
    "bLengthChange": true,
    "searching": { "regex": false },
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "pageLength": 10,
    "destroy": true, // Asegúrate de destruir cualquier instancia previa
    "pagingType": 'full_numbers',
    "scrollCollapse": true,
    "responsive": true,
    "processing": true,
    "ajax": {
      "url": "../controller/asistencias/controlador_listar_alumnos_totales.php",
      "type": 'POST',
      "data": { año:año,mes: mes, aula: aula },
      "dataSrc": function (json) {
        console.log(json); // Verifica la estructura de la respuesta
        // Asegúrate de que 'data' es la clave correcta
        return json.data || []; // Devuelve un array vacío si 'data' es indefinido
      }
    },
    "dom": 'Bfrtip',
    "buttons": [
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel"></i> ',
        titleAttr: 'Exportar a Excel',
        filename: function () {
          return "LISTA DE ASISTENCIAS POR MES";
        },
        title: function () {
          return "LISTA DE ASISTENCIAS POR MES";
        }
      },
      {
        extend: 'pdfHtml5',
        text: '<i class="fas fa-file-pdf"></i> ',
        titleAttr: 'Exportar a PDF',
        filename: function () {
          return "LISTA DE ASISTENCIAS POR MES";
        },
        title: function () {
          return "LISTA DE ASISTENCIAS POR MES";
        }
      },
      {
        extend: 'print',
        text: '<i class="fa fa-print"></i> ',
        titleAttr: 'Imprimir',
        title: function () {
          return "LISTA DE ASISTENCIAS POR MES";
        }
      }
    ],
    "columns": [
      {"data": "Id_alumno"},
      {"data": "DNI"},
      {"data": "Estudiante"},
      {"data": "total_asistencia_presente"},
      {"data": "total_asistencia_tarde"},
      {"data": "total_asistencia_ausente"},
      {"data": "total_asistencia_justificado"},
      {
        "data": "total_general",
        "render": function (data, type, row) {
          return '<span class="resaltado" style="font-size: 1.2em; font-weight: bold;text-align: center;">' + data + '</span>';
        }
      }
    ],
    "language": idioma_espanol,
    "select": true
  });

  // Si el evento 'draw.td' no es necesario, elimínalo
  // Si quieres numerar las filas, usa el siguiente código
  tbl_alumnos_asistencia2.on('draw', function () {
    var PageInfo = $("#tabla_asistencia_totales").DataTable().page.info();
    tbl_alumnos_asistencia2.column(0, { page: 'current' }).nodes().each(function (cell, i) {
      $(cell).html(i + 1 + PageInfo.start); // Actualiza el contenido de la celda
    });
  });
}


function listar_alumnos_totales_dia() {
  let año = document.getElementById('select_año').value;
  let mes = document.getElementById('select_meses_buscar').value;
  let aula = document.getElementById('select_aula_buscar_2').value;
  console.log(mes, aula); // Verifica los valores

  // Inicializa o reinicializa el DataTable
  tbl_alumnos_asistencia2_dia = $("#tabla_asistencia_totales_dia").DataTable({
    "ordering": false,
    "bLengthChange": true,
    "searching": { "regex": false },
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "pageLength": 10,
    "destroy": true, // Asegúrate de destruir cualquier instancia previa
    "pagingType": 'full_numbers',
    "scrollCollapse": true,
    "responsive": true,
    "processing": true,
    "ajax": {
      "url": "../controller/asistencias/controlador_listar_alumnos_totales_dia.php",
      "type": 'POST',
      "data": { año:año,mes: mes, aula: aula },
      "dataSrc": function (json) {
        console.log(json); // Verifica la estructura de la respuesta
        // Asegúrate de que 'data' es la clave correcta
        return json.data || []; // Devuelve un array vacío si 'data' es indefinido
      }
    },
    "dom": 'Bfrtip',
    "buttons": [
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel"></i> ',
        titleAttr: 'Exportar a Excel',
        filename: function () {
          return "LISTA DE ASISTENCIAS POR DIA";
        },
        title: function () {
          return "LISTA DE ASISTENCIAS POR DIA";
        }
      },
      {
        extend: 'pdfHtml5',
        text: '<i class="fas fa-file-pdf"></i> ',
        titleAttr: 'Exportar a PDF',
        filename: function () {
          return "LISTA DE ASISTENCIAS POR DIA";
        },
        title: function () {
          return "LISTA DE ASISTENCIAS POR DIA";
        }
      },
      {
        extend: 'print',
        text: '<i class="fa fa-print"></i> ',
        titleAttr: 'Imprimir',
        title: function () {
          return "LISTA DE ASISTENCIAS POR DIA";
        }
      }
    ],
    "columns": [
      { "data": "Id_alumno" },
      { "data": "DNI" },
      { "data": "Estudiante" },
      { "data": "dia_1",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_2",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_3",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_4",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_5",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_6",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_7",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_8",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_9",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_10",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_11",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_12",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_13",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      {
        "data": "dia_14",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
    },      
    { "data": "dia_15",
      "render": function (data, type, row, meta) {
        if (data === 'PRESENTE') {
          return '<span class="badge bg-success">P</span>';
        } else if (data === 'TARDE') {
          return '<span class="badge bg-warning">T</span>';
        } else if (data === 'AUSENTE') {
          return '<span class="badge bg-danger">A</span>';
        } else if (data === 'JUSTIFICADO') {
          return '<span class="badge bg-dark">FJ</span>';
        } else {
            return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
        }
    }
    },
      { "data": "dia_16",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_17",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_18",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_19",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_20",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_21",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_22",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_23",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_24",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_25",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_26",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_27",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_28",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_29",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_30",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       },
      { "data": "dia_31",
        "render": function (data, type, row, meta) {
          if (data === 'PRESENTE') {
            return '<span class="badge bg-success">P</span>';
          } else if (data === 'TARDE') {
            return '<span class="badge bg-warning">T</span>';
          } else if (data === 'AUSENTE') {
            return '<span class="badge bg-danger">A</span>';
          } else if (data === 'JUSTIFICADO') {
            return '<span class="badge bg-dark">FJ</span>';
          } else {
              return data;  // Devuelve el valor tal cual si no coincide con ninguna condición
          }
      }
       }
    ],
    "language": idioma_espanol,
    "select": true
  });

  // Si el evento 'draw.td' no es necesario, elimínalo
  // Si quieres numerar las filas, usa el siguiente código
  tbl_alumnos_asistencia2_dia.on('draw', function () {
    var PageInfo = $("#tabla_asistencia_totales_dia").DataTable().page.info();
    tbl_alumnos_asistencia2_dia.column(0, { page: 'current' }).nodes().each(function (cell, i) {
      $(cell).html(i + 1 + PageInfo.start); // Actualiza el contenido de la celda
    });
  });
}

//TRAENDO DATOS DE LA NIVEL ACADEMICO

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
      data:{
        id:id
      }
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][1]+"'>"+data[i][2]+"</option>";    
        }
          document.getElementById('select_aula').innerHTML=cadena;
          document.getElementById('select_aula_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_aula').innerHTML=cadena;
        document.getElementById('select_aula_editar').innerHTML=cadena;

     }
})

}
function Cargar_Select_Grado_buscar(){
  $.ajax({
    "url":"../controller/asignaturas/controlador_cargar_select_grado.php",
    type:'POST',
  }).done(function(resp){
    let data=JSON.parse(resp);
    if(data.length>0){
      let cadena ="";
      for (let i = 0; i < data.length; i++) {
        cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+" - "+data[i][2]+"</option>";    
      }
      $('#select_aula_buscar').html(cadena);
      $('#select_aula_buscar_2').html(cadena);

    }else{
      cadena+="<option value=''>No se encontraron regitros</option>";
      $('#select_aula_buscar').html(cadena);
      $('#select_aula_buscar_2').html(cadena);


    }
  })
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
        document.getElementById('select_año2').innerHTML=cadena;
    }else{
      cadena+="<option value=''>No hay secciones en la base de datos</option>";
      document.getElementById('select_año').innerHTML=cadena;
      document.getElementById('select_año2').innerHTML=cadena;
    }
  })
}
//ENVIANDO DATOS PARA EDITAR
$('#tabla_asistencia').on('click', '.editar', function() {
  var data = tbl_asistencia.row($(this).parents('tr')).data();

  if (tbl_asistencia.row(this).child.isShown()) {
      data = tbl_asistencia.row(this).data();
  }

  console.log("Datos obtenidos:", data);  // Verifica que el valor de 'estado' es el esperado.

  $("#modal_editar").modal('show');
  document.getElementById('txt_fecha_editar').value = data.fecha;

  $("#select_nivel_editar").select2().val(data.Id_nivel).trigger('change.select2');
  $("#select_aula_editar").select2().val(data.Id_aula).trigger('change.select2');

  listar_alumnos_editar();

});


//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_asistencia() {
    let table = $('#tabla_alumnos').DataTable();
    let registros = [];

    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
        let data = this.data();

        // Extraer valores de cada fila
        let id_matri = $(this.node()).find('#id_matri').val();
        let fecha = $(this.node()).find('#txt_fecha_asis').val();
        let esta = $(this.node()).find('#select_estado').val();
        let obse = $(this.node()).find('#txt_observa').val();

        // Agregar el registro a la lista de registros
        registros.push({
            id_matri: id_matri,
            fecha: fecha,
            esta: esta,
            obse: obse
        });
    });

    if (registros.length === 0) {
        return Swal.fire("Mensaje de Advertencia", "No hay registros para enviar", "warning");
    }

    $.ajax({
        url: "../controller/asistencias/controlador_registro_asistencias.php",
        type: 'POST',
        data: {
            registros: JSON.stringify(registros)  // Convertir el array de objetos a JSON
        }
    }).done(function(resp) {
        if (resp == 1) {
            Swal.fire("Mensaje de Confirmación", "Asistencia registrada satisfactoriamente!!!", "success").then((value) => {
                tbl_asistencia.ajax.reload();
                $("#modal_registro").modal('hide');

            });
        } else if (resp == 2) {
            Swal.fire("Mensaje de Advertencia", "Algunos registros ya existen en la base de datos, revise por favor", "warning");
        } else {
            Swal.fire("Mensaje de Error", "No se completó el registro", "error");
        }
    });
}


//EDITANDO ROL
function Editar_asistencia() {
  let table = $('#tabla_alumnos_editar').DataTable();
  let registros = [];

  table.rows().every(function(rowIdx, tableLoop, rowLoop) {
      let data = this.data();

      // Asegúrate de que el ID de asistencia esté disponible
      let id_asis = data.id_asistencia || $(this.node()).find('#id_asis_editar').val();
      let fecha = $(this.node()).find('#txt_fecha_asis_editar').val();
      let esta = $(this.node()).find('#select_estado_editar').val();
      let obse = $(this.node()).find('#txt_observa_editar').val();

      // Verifica si id_asis está definido y si la fecha también está disponible
      if (id_asis && fecha) {
          // Agregar el registro a la lista de registros
          registros.push({
              id_asis: id_asis,
              fecha: fecha,
              esta: esta,
              obse: obse
          });
      }
  });

  if (registros.length === 0) {
      return Swal.fire("Mensaje de Advertencia", "No hay registros para enviar", "warning");
  }

  $.ajax({
      url: "../controller/asistencias/controlador_editar_asistencia.php",
      type: 'POST',
      data: {
          registros: JSON.stringify(registros)  // Convertir el array de objetos a JSON
      }
  }).done(function(resp) {
      if (resp == 1) {
          Swal.fire("Mensaje de Confirmación", "Asistencia editada satisfactoriamente!!!", "success").then(() => {
              $('#tabla_asistencia').DataTable().ajax.reload(); // Recarga la tabla de edición si es necesario
              $("#modal_editar").modal('hide'); // Oculta el modal
          });
      } else if (resp == 2) {
          Swal.fire("Mensaje de Advertencia", "El registro no existe en la base de datos, revise por favor", "warning");
      } else {
          Swal.fire("Mensaje de Error", "No se completó la edición", "error");
      }
  }).fail(function(jqXHR, textStatus, errorThrown) {
      Swal.fire("Mensaje de Error", "No se pudo conectar con el servidor", "error");
  });
}


//ELIMINANDO ROL
function Eliminar_Asistencia(fecha,aula){
    $.ajax({
      "url":"../controller/asistencias/controlador_eliminar_asistencia.php",
      type:'POST',
      data:{
        fecha:fecha,
        aula:aula
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la asistencia con fecha "+fecha+" con éxito","success").then((value)=>{
            tbl_asistencia.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advertencia","No se puede eliminar este grado académico por que esta siendo utilizado en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_asistencia').on('click','.delete',function(){
    var data = tbl_asistencia.row($(this).parents('tr')).data();
  
    if(tbl_asistencia.row(this).child.isShown()){
        var data = tbl_asistencia.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar la asistencia de fecha: '+data.fecha_formateada+' del aula: '+data.Grado+'?',
      text: "Una vez aceptado se eliminara la asistencia de la fecha que se mostro así como el registro de cada alumno!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Asistencia(data.fecha,data.Id_aula);
      }
    })
  })






  $('#tabla_asistencia').on('click','.mostrar',function(){
    var data = tbl_asistencia.row($(this).parents('tr')).data();
  
    if(tbl_asistencia.row(this).child.isShown()){
        var data = tbl_asistencia.row(this).data();
    }
  $("#modal_ver_asistencia").modal('show');
    document.getElementById('lb_titulo').innerHTML="<b>NIVEL ACADEMICO - GRADO: </b>"+data.Nivel_academico+" - "+data.Grado+"";
    document.getElementById('lb_titulo2').innerHTML="<b>FECHA: </b>"+data.fecha_formateada+"";
    listar_asistencia_alumnos(data.fecha,data.Id_aula);
  
  })

  var tbl_tareas_enviadas;
  function listar_asistencia_alumnos(fecha, aula) {
    tbl_tareas_enviadas = $("#tabla_ver").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": { "regex": false },
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 10,
        "destroy": true,
        "pagingType": 'full_numbers',
        "scrollCollapse": true,
        "responsive": true,
        "async": false,
        "processing": true,
        "info": true, // Activamos el listado de cantidad de registros en la parte inferior
        "ajax": {
            "url": "../controller/asistencias/controlador_listar_alumnos_asistencia.php",
            type: 'POST',
            data: { fecha: fecha, aula: aula },
        },
       
        "columns": [
            { "data": "id_matricula" },
            { "data": "alum_dni" },
            { "data": "Estudiante" },
            { "data": "fecha_formateada2" },
            {
                "data": "estado",
                render: function(data, type, row) {
                    if (data == 'PRESENTE') {
                        return '<span class="badge bg-success">PRESENTE</span>';
                    } else if (data == 'TARDE') {
                        return '<span class="badge bg-warning">TARDE</span>';
                    } else if (data == 'AUSENTE') {
                        return '<span class="badge bg-danger">AUSENTE</span>';
                    } else {
                        return '<span class="badge bg-dark">JUSTIFICADO</span>';
                    }
                },
            },
            { "data": "observacion" },
        ],
         dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> ',
                titleAttr: 'Exportar a Excel',
                filename: function() {
                    return "LISTA DE ALUMNOS - ASISTENCIA";
                },
                title: function() {
                    return "LISTA DE ALUMNOS - ASISTENCIA";
                }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> ',
                titleAttr: 'Exportar a PDF',
                filename: function() {
                    return "LISTA DE ALUMNOS - ASISTENCIA";
                },
                title: function() {
                    return "LISTA DE ALUMNOS - ASISTENCIA";
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> ',
                titleAttr: 'Imprimir',
                title: function() {
                    return "LISTA DE ALUMNOS - ASISTENCIA";
                }
            }
        ],
        "language": idioma_espanol,
        "select": true
    });

    tbl_tareas_enviadas.on('draw.td', function () {
        var PageInfo = $("#tabla_ver").DataTable().page.info();
        tbl_tareas_enviadas.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.innerHTML = i + 1 + PageInfo.start;
        });
    });
}


