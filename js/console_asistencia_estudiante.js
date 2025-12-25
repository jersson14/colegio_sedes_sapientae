//LISTADO DE ROLES
function Cargar_Año(){
let id = document.getElementById('txtprincipalid').value;

    $.ajax({
    "url":"../controller/notas/controlador_cargar_años_por_estudiante.php",
    type:'POST',
    data:{
        id:id
    }
    }).done(function(resp){
    let data=JSON.parse(resp);
    if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
        cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año_2').innerHTML=cadena;

    }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año_2').innerHTML=cadena;

    }
    })
}
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
    
        document.getElementById('select_aula_buscar').innerHTML=cadena;
        document.getElementById('select_aula_buscar_2').innerHTML=cadena;      
        
      } else {
        document.getElementById('select_aula_buscar').innerHTML=cadena;
        document.getElementById('select_aula_buscar_2').innerHTML=cadena;      
    
    }
    });
  }




var tbl_alumnos_asistencia2;




function listar_alumnos_totales() {
    let id = document.getElementById('txtprincipalid').value;
  let año = document.getElementById('select_año_2').value;
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
      "url": "../controller/asistencias/controlador_listar_alumnos_totales_estu.php",
      "type": 'POST',
      "data": {id:id,año:año, mes: mes, aula: aula },
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

var tbl_alumnos_asistencia2_dia;
function listar_alumnos_totales_dia() {
    let id = document.getElementById('txtprincipalid').value;
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
      "url": "../controller/asistencias/controlador_listar_alumnos_totales_dia_estudiante.php",
      "type": 'POST',
      "data": { id:id,año:año,mes: mes, aula: aula },
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

