// Reporte de asistencias por aula y mes (lo que quedó de la antigua pantalla de asistencia diaria; registrar se hace
// solo en el Registro de asistencia, js/registro_asistencia.js).




function listar_alumnos_totales() {
  let año = document.getElementById('select_año2').value;

  let mes = document.getElementById('select_meses').value;
  let aula = document.getElementById('select_aula_buscar').value;

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
        text: '<i class="fas fa-print"></i> ',
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
        text: '<i class="fas fa-print"></i> ',
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
            return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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
              return textoSeguro(data);  // Devuelve el valor (escapado) si no coincide con ninguna condición
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

function Cargar_Select_Grado_buscar(){
  $.ajax({
    "url":"../controller/asignaturas/controlador_cargar_select_grado.php",
    type:'POST',
  }).done(function(resp){
    let data=JSON.parse(resp);
    if(data.length>0){
      let cadena ="";
      for (let i = 0; i < data.length; i++) {
        cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+" - "+textoSeguro(data[i][2])+"</option>";    
      }
      $('#select_aula_buscar').html(cadena);
      $('#select_aula_buscar_2').html(cadena);

    }else{
      let cadena = "<option value=''>No se encontraron regitros</option>";
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
        cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+"</option>";    
      }
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año2').innerHTML=cadena;
    }else{
      let cadena = "<option value=''>No hay secciones en la base de datos</option>";
      document.getElementById('select_año').innerHTML=cadena;
      document.getElementById('select_año2').innerHTML=cadena;
    }
  })
}
