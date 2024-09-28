//LISTADO DE ROLES
var tbl_notas;
function listar_notas_todos(){
    let año = document.getElementById('select_año').value;
    let grado = document.getElementById('select_aula').value;

    tbl_notas = $("#tabla_notas").DataTable({
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
          "url":"../controller/notas/controlador_listar_matriculas_filtro.php",
          type:'POST',
          data:{
            año:año,
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
        return  "LISTA DE NOTAS"
      },
        title: function() {
          return  "LISTA DE NOTAS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE NOTAS"
      },
    title: function() {
      return  "LISTA DE NOTAS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE NOTAS"
  
    }
    }],
      "columns":[
        {"data":"id_matricula"},
        {"data":"alum_dni"},
        {"data":"Estudiante"},
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
        },
        {"data":"seccion_nombre"},
        {"data":"Grado"},

        {"data":"año_escolar"},
        
        {
          "data": "contar",
          "render": function(data, type, row) {
              if (data > 0) {
                return '<button class="insert btn btn-success btn-sm" title="Insertar notas"><i class="fas fa-pen"></i> Insertar notas estudiante</button>';

              } else {
                return '<button class="insert btn btn-success btn-sm" title="Insertar notas"><i class="fas fa-pen"></i> Insertar notas estudiante</button>';
              }
          }
      },
      


        {"defaultContent":"<button class='mostrar btn btn-warning  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Mostrar notas por bimestre</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos'><i class='fa fa-edit'></i> Editar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_notas.on('draw.td',function(){
  var PageInfo = $("#tabla_notas").DataTable().page.info();
  tbl_notas.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
$(document).on('click', '.insert', function(event) {
  // Verificar si el botón tiene el atributo 'disabled'
  if ($(this).is(':disabled')) {
      event.preventDefault(); // Evitar la acción del clic
      return false;
  }

  // Aquí va la lógica para manejar el clic cuando el botón no está deshabilitado
  console.log('Botón clickeado');
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
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
      }
    })
  }

  function Cargar_Bimestre(){
    $.ajax({
      "url":"../controller/notas/controlador_cargar_periodos.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
          document.getElementById('select_bimestre').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_bimestre').innerHTML=cadena;
      }
    })
  }

  function Cargar_Bimestre_cargados(){
    $.ajax({
      "url":"../controller/notas/controlador_cargar_periodos_cargados.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][1]+"'>"+data[i][2]+"</option>";    
        }
          document.getElementById('select_bimestre_ver').innerHTML=cadena;
          document.getElementById('select_bimestre_editar').innerHTML=cadena;

      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_bimestre_ver').innerHTML=cadena;
        document.getElementById('select_bimestre_editar').innerHTML=cadena;

      }
    })
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
                $('#select_aula').html(cadena);
            } else {
                $('#select_aula').html("<option value=''>No hay secciones en la base de datos</option>");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + " - " + error);
            $('#select_aula').html("<option value=''>Error al cargar las secciones</option>");
        }
    });
}
//ENVIANDO DATOS PARA EDITAR
$('#tabla_notas').on('click','.insert',function(){
  var data = tbl_notas.row($(this).parents('tr')).data();

  if(tbl_notas.row(this).child.isShown()){
      var data = tbl_notas.row(this).data();
  }
  $("#modal_notas").modal('show');
  document.getElementById('id_matri').value=data.id_matricula;
  document.getElementById('txt_estudiante').value=data.alum_dni+" - "+data.Estudiante;
  document.getElementById('txt_nivel').value=data.Nivel_academico;
  document.getElementById('txt_aula').value=data.Grado;
  listar_componentes();


})
var tbl_tareas_enviadas;

function listar_componentes() {
    let nivel = document.getElementById('txt_nivel').value;
    let aula = document.getElementById('txt_aula').value;

    tbl_tareas_enviadas = $("#tabla_vistacomp").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 3,
        "destroy": true,
        "pagingType": 'full_numbers',
        "scrollCollapse": true,
        "responsive": true,
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "../controller/notas/controlador_listar_criterios_notas.php",
            type: 'POST',
            data: { nivel: nivel, aula: aula },
        },
        "columns": [
            { "data": "nombre_asig", "visible": false },
            {
                "data": "id_criterio",
                "title": "ID",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        return '<input class="form-control criterio" type="text" value="' + (data || '') + '" readonly />';
                    }
                    return data;
                }
            },
            { "data": "competencias", "title": "Competencias" },
            {
               "data": null,  // No asociada a ningún dato
                "render": function(data, type, row, meta){
                    if (type === 'display') {
                        return '<input class="form-control txt_nota" type="text" min="0" step="1"/>';
                    }
                    return data;
                }
            },
            {
               "data": null,  // No asociada a ningún dato
                "render": function(data, type, row, meta){
                    if (type === 'display') {
                        return '<input class="form-control txt_conclusion" type="text" />';
                    }
                    return data;
                }
            }
        ],
        "drawCallback": function(settings) {
            var api = this.api();
            var rows = api.rows({page: 'current'}).nodes();
            var last = null;

            api.column(0, {page: 'current'}).data().each(function(group, i) {
                if (last !== group) {
                    $(rows).eq(i).before(
                        '<tr class="group"><td colspan="4"><strong>Área: ' + group + '</strong></td></tr>'
                    );
                    last = group;
                }
            });
        },
        "language": idioma_espanol,
        "select": true
    });


}



//MOSTRAR

// Maneja el clic en el botón de mostrar
$('#tabla_notas').on('click', '.mostrar', function() {
  var data = tbl_notas.row($(this).parents('tr')).data();
  
  // Verifica si la fila está desplegada
  if (tbl_notas.row(this).child.isShown()) {
      data = tbl_notas.row(this).data();
  }

  // Muestra el modal y llena los campos
  $("#modal_notas_ver").modal('show');
  document.getElementById('id_matri_ver').value = data.id_matricula;
  document.getElementById('txt_estudiante_ver').value = data.alum_dni + " - " + data.Estudiante;
  document.getElementById('txt_nivel_ver').value = data.Nivel_academico;
  document.getElementById('txt_aula_ver').value = data.Grado;
  // Llena el select si es necesario
  // Ej: $('#select_bimestre_ver').val(data.bimestre); // Asegúrate de que 'data.bimestre' sea la clave correcta
});

// Función para listar notas
var tbl_notas_ver;

var tbl_notas_ver_padres;
var tablaCargada = false; // Bandera para verificar si la tabla ya ha sido cargada

function inicializar_modal_notas_padres() {
    // Limpiar la tabla si existe
    if ($.fn.DataTable.isDataTable('#tabla_vistacomp_padre_ver')) {
        $('#tabla_vistacomp_padre_ver').DataTable().clear().destroy();
    }

    // Resetear el contenido de la tabla
    $('#tabla_vistacomp_padre_ver').empty();

    // Reinicializar la estructura de la tabla con los estilos correctos
    $('#tabla_vistacomp_padre_ver').html(`
        <thead>
            <tr>
                <th>Competencias</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody></tbody>
    `);

    tablaCargada = false; // Reiniciar la bandera cuando se inicializa el modal
}

function inicializar_modal_notas() {
    // Limpiar la tabla si existe
    if ($.fn.DataTable.isDataTable('#tabla_vistacomp_ver')) {
        $('#tabla_vistacomp_ver').DataTable().clear().destroy();
    }
    
    // Resetear el contenido de la tabla
    $('#tabla_vistacomp_ver').empty();
    
    // Reinicializar la estructura de la tabla con los estilos correctos
    $('#tabla_vistacomp_ver').html(`
        <thead style="background-color:#0A5D86;color:#FFFFFF;">
            <tr>
                <th style="text-align:center; padding: 10px;">Área</th>
                <th style="text-align:center; padding: 10px;">Competencias</th>
                <th style="text-align:center; padding: 10px;">Nota</th>
                <th style="text-align:center; padding: 10px;">Conclusión Descriptiva</th>
            </tr>
        </thead>
        <tbody></tbody>
    `);
}

function listar_notas_ver() {
  listar_notas_ver_padres();

    let matri = document.getElementById('id_matri_ver').value;
    let bime = document.getElementById('select_bimestre_ver').value;

    // Configura la tabla de notas
    tbl_notas_ver = $("#tabla_vistacomp_ver").DataTable({
        "ordering": false,
        "bLengthChange": true,
        "searching": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "pageLength": 3,
        "destroy": true,
        "pagingType": 'full_numbers',
        "scrollCollapse": true,
        "responsive": true,
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "../controller/notas/controlador_listar_criterios_notas_mostrar.php",
            type: 'POST',
            data: function(d) {
                d.matri = matri;
                d.bime = bime;
            },
            dataSrc: function(json) {
                return json.data;
            }
        },
        "columns": [
            { "data": "nombre_asig", "visible": false },
            { "data": "competencias", "title": "Competencias" },
            { "data": "nota",
                "title": "Nota", },

          
            { "data": "conclusiones", "title": "Conclusión Descriptiva" }
        ],
        "drawCallback": function(settings) {
            var api = this.api();
            var rows = api.rows({ page: 'current' }).nodes();
            var last = null;

            api.column(0, { page: 'current' }).data().each(function(group, i) {
                if (last !== group) {
                    $(rows).eq(i).before(
                        '<tr class="group"><td colspan="4" style="background-color:#f0f0f0;"><strong>Área: ' + group + '</strong></td></tr>'
                    );
                    last = group;
                }
            });

            // Reaplica los estilos a la cabecera
            $('#tabla_vistacomp_ver thead').css({
                'background-color': '#0A5D86',
                'color': '#FFFFFF'
            });
            $('#tabla_vistacomp_ver thead th').css({
                'text-align': 'center',
                'padding': '10px'
            });
        },
        "language": idioma_espanol,
        "select": true
    });
}

// Evento para el botón que lista las notas
$(document).on('click', '#btn_listar_notas', function() {
    listar_notas_ver();

});

// Evento para cuando se abre el modal
$('#modal_notas_ver').on('show.bs.modal', function (e) {
    inicializar_modal_notas();
});


function listar_notas_ver_padres() {
  let matri = document.getElementById('id_matri_ver').value;
  let bime = document.getElementById('select_bimestre_ver').value;

  tbl_notas_ver_padres = $("#tabla_vistacomp_padre_ver").DataTable({
      "ordering": false,
      "bLengthChange": true,
      "searching": true,
      "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
      "pageLength": 3,
      "destroy": true,
      "pagingType": 'full_numbers',
      "scrollCollapse": true,
      "responsive": true,
      "processing": true,
      "serverSide": false,
      "ajax": {
          "url": "../controller/notas/controlador_listar_criterios_notas_mostrar_padres.php",
          type: 'POST',
          data: function(d) {
              d.matri = matri;
              d.bime = bime;
          }
      },
      "columns": [
          { 
              "data": "criterio", 
              "title": "Competencias",
              "width": "70%"
          },
          { 
              "data": "nota", 
              "title": "Nota",
              "width": "30%"
          }
      ],
      "drawCallback": function(settings) {
        $('#tabla_vistacomp_padre_ver tbody td').css({
            'white-space': 'normal',
            'word-wrap': 'break-word',
            'padding': '12px'
        });
        
        // Asegurar que el color de fondo del título se mantenga y ajustar espaciado
        $('#tabla_vistacomp_padre_ver thead tr:first-child th').css({
            'background-color': '#0A5D86',
            'color': '#FFFFFF',
            'padding-bottom': '30px'
        });

        // Añadir línea separadora y ajustar espaciado para las cabeceras de columna
        $('#tabla_vistacomp_padre_ver thead tr:nth-child(2) th').css({
            'border-top': '20px solid #FFFFFF',
            'padding-top': '15px'
        });
    },
    // ... (resto del código permanece igual)
});

tablaCargadaPadres = true;
}


// Evento para el botón que lista las notas

// Evento para cuando se abre el modal
$('#modal_notas_ver_padres').on('show.bs.modal', function (e) {
    // Solo listar las notas si la tabla no ha sido cargada
    if (!tablaCargada) {
        listar_notas_ver_padres();
    }
});

// No es necesario el evento 'page.dt' aquí ya que no hay inputs para mantener
//ENVIANDO DATOS PARA MOSTRAR
//REGISTRO

$(document).ready(function() {
  var tabla = $('#tabla_vistacomp_padre').DataTable({
      "ordering": false,
      "bLengthChange": true,
      "searching": true,
      "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
      "pageLength": 10,
      "destroy": true,
      "pagingType": 'full_numbers',
      "scrollCollapse": true,
      "responsive": true,
      "processing": true,
      "serverSide": false,
      "language": idioma_espanol,

      // Configuración de columnas para los inputs
      "columns": [
          {
              "data": null,
              "title": "Competencias",
              "render": function(data, type, row, meta) {
                  return '<input type="text" class="form-control competencia_padre" value="' + (data ? data[0] : '') + '" style="width:95%; margin: 5px;">';
              }
          },
          {
              "data": null,
              "title": "Nota",
              "render": function(data, type, row, meta) {
                  return '<input type="text" class="form-control notapadre" value="' + (data ? data[1] : '') + '" style="width:80%; margin: 5px;">';
              }
          }
      ],

      // Datos predeterminados para las filas iniciales
      "data": [
          ["Asiste y participa activamente en actividades y reuniones del Aula", ""],
          ["Acude regularmente al colegio en el horario de atencion a padres de familia, para dialogar con los docentes sobre el rendimiento academico y la conducta de su hijo. (a)", ""],
          ["Cumple con las cuotas de pension.", ""]
      ]
  });

  // Función para agregar una nueva fila con inputs vacíos
  function agregarFila() {
      tabla.row.add(['', '']).draw(false);
  }

  // Función para eliminar la última fila
  function eliminarFila() {
      var rowCount = tabla.rows().count();
      if (rowCount > 0) {
          tabla.row(rowCount - 1).remove().draw(false);
      } else {
          alert("No se puede eliminar la última fila.");
      }
  }

  // Asignar las funciones a botones globalmente
  window.agregarFila = agregarFila;
  window.eliminarFila = eliminarFila;
});

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_notas() {
  let table = $('#tabla_vistacomp').DataTable();
  let registros = [];

  // Obtener los valores de los inputs que están fuera de la tabla
  let id_matri = $('#id_matri').val();
  let perio = $('#select_bimestre').val();

  // Validar que los campos fuera de la tabla no estén vacíos
  if (!id_matri || !perio) {
      return Swal.fire("Mensaje de Advertencia", "Por favor complete todos los campos antes de enviar.", "warning");
  }

  // Recorrer todas las filas de la tabla para obtener los datos de cada input
  table.rows().every(function(rowIdx, tableLoop, rowLoop) {
      let cri = $(this.node()).find('.criterio').val();
      let nota = $(this.node()).find('.txt_nota').val();
      let conclu = $(this.node()).find('.txt_conclusion').val();

      // Verificar si los campos dentro de la tabla no están vacíos
      if (cri && nota) {
          // Agregar el registro a la lista de registros
          registros.push({
              id_matri: id_matri,
              perio: perio,
              cri: cri,
              nota: nota,
              conclu: conclu || ''  // Asegurarse de que 'conclu' no sea undefined
          });
      }
  });

  // Verificar si hay registros para enviar
  if (registros.length === 0) {
      return Swal.fire("Mensaje de Advertencia", "No hay registros para enviar", "warning");
  }

  // Enviar los registros al servidor mediante AJAX
  $.ajax({
      url: "../controller/notas/controlador_registro_notas.php",
      type: 'POST',
      data: {
          registros: JSON.stringify(registros)  // Convertir el array de objetos a JSON
      },
      success: function(resp) {
          try {
              const response = JSON.parse(resp);
              if (response.status === 1) {
                Registrar_notas_padre();
              } else {
                Swal.fire("Mensaje de Advertencia", "Este alumno ya tiene notas registradas en el periodo que intentas ingresar", "warning");
              }
          } catch (e) {
              Swal.fire("Mensaje de Error", "Error al procesar la respuesta del servidor.", "error");
          }
      },
      error: function(xhr, status, error) {
          Swal.fire("Mensaje de Error", "Error en la solicitud AJAX: " + error, "error");
      }
  });
}





function Registrar_notas_padre() {
  let table = $('#tabla_vistacomp_padre').DataTable();
  let registros = [];

  // Obtener los valores de los inputs que están fuera de la tabla
  let id_matri = $('#id_matri').val();
  let perio = $('#select_bimestre').val();

  // Validar que los campos fuera de la tabla no estén vacíos
  if (!id_matri || !perio) {
      return Swal.fire("Mensaje de Advertencia", "Por favor complete todos los campos antes de enviar.", "warning");
  }

  // Recorrer todas las filas de la tabla para obtener los datos de cada input
  table.rows().every(function(rowIdx, tableLoop, rowLoop) {
      let cri = $(this.node()).find('.competencia_padre').val();
      let nota = $(this.node()).find('.notapadre').val();

      // Verificar si los campos dentro de la tabla no están vacíos
      if (cri && nota) {
          // Agregar el registro a la lista de registros
          registros.push({
              id_matri: id_matri,
              perio: perio,
              competencia: cri,
              nota: nota
          });
      }
  });

  // Verificar si hay registros para enviar
  if (registros.length === 0) {
      return Swal.fire("Mensaje de Advertencia", "No hay registros para enviar", "warning");
  }

  // Enviar los registros al servidor mediante AJAX
  $.ajax({
      url: "../controller/notas/controlador_registro_notas_padres.php",
      type: 'POST',
      data: {
          registros: JSON.stringify(registros)
      },
      dataType: 'json',
      success: function(response) {
          if (response.status === 1) {
              Swal.fire("Mensaje de Confirmación", `Notas de padres procesadas satisfactoriamente: ${response.inserted_count} registros.`, "success").then(() => {
              });
              tbl_tareas_enviadas.ajax.reload();
              $("#modal_notas").modal('hide');
          } else {
              Swal.fire("Mensaje de Error", response.message, "error");
          }
      },
      error: function(xhr, status, error) {
          console.error("Error en la solicitud AJAX:", error);
          Swal.fire("Mensaje de Error", "Error en la solicitud AJAX: " + error, "error");
      }
  });
}



///MODAL VISTAS

function inicializar_modal_notas() {
  // Limpiar la tabla si existe
  if ($.fn.DataTable.isDataTable('#tabla_vistacomp_ver')) {
      $('#tabla_vistacomp_ver').DataTable().clear().destroy();
  }
  
  // Resetear el contenido de la tabla
  $('#tabla_vistacomp_ver').empty();
  
  // Reinicializar la estructura de la tabla con los estilos correctos
  $('#tabla_vistacomp_ver').html(`
      <thead style="background-color:#0A5D86;color:#FFFFFF;">
          <tr>
              <th style="text-align:center; padding: 10px;">Área</th>
              <th style="text-align:center; padding: 10px;">Competencias</th>
              <th style="text-align:center; padding: 10px;">Nota</th>
              <th style="text-align:center; padding: 10px;">Conclusión Descriptiva</th>
          </tr>
      </thead>
      <tbody></tbody>
  `);
}

function listar_notas_ver() {
listar_notas_ver_padres();

  let matri = document.getElementById('id_matri_ver').value;
  let bime = document.getElementById('select_bimestre_ver').value;

  // Configura la tabla de notas
  tbl_notas_ver = $("#tabla_vistacomp_ver").DataTable({
      "ordering": false,
      "bLengthChange": true,
      "searching": true,
      "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
      "pageLength": 3,
      "destroy": true,
      "pagingType": 'full_numbers',
      "scrollCollapse": true,
      "responsive": true,
      "processing": true,
      "serverSide": false,
      "ajax": {
          "url": "../controller/notas/controlador_listar_criterios_notas_mostrar.php",
          type: 'POST',
          data: function(d) {
              d.matri = matri;
              d.bime = bime;
          },
          dataSrc: function(json) {
              return json.data;
          }
      },
      "columns": [
          { "data": "nombre_asig", "visible": false },
          { "data": "competencias", "title": "Competencias" },
          {
              "data": "nota",
              "title": "Nota",
           
          },
          { "data": "conclusiones", "title": "Conclusión Descriptiva" }
      ],
      "drawCallback": function(settings) {
          var api = this.api();
          var rows = api.rows({ page: 'current' }).nodes();
          var last = null;

          api.column(0, { page: 'current' }).data().each(function(group, i) {
              if (last !== group) {
                  $(rows).eq(i).before(
                      '<tr class="group"><td colspan="4" style="background-color:#f0f0f0;"><strong>Área: ' + group + '</strong></td></tr>'
                  );
                  last = group;
              }
          });

          // Reaplica los estilos a la cabecera
          $('#tabla_vistacomp_ver thead').css({
              'background-color': '#0A5D86',
              'color': '#FFFFFF'
          });
          $('#tabla_vistacomp_ver thead th').css({
              'text-align': 'center',
              'padding': '10px'
          });
      },
      "language": idioma_espanol,
      "select": true
  });
}

// Evento para el botón que lista las notas
$(document).on('click', '#btn_listar_notas', function() {
  listar_notas_ver();

});

// Evento para cuando se abre el modal
$('#modal_notas_ver').on('show.bs.modal', function (e) {
  inicializar_modal_notas();
});

//MODALES PARA EDITAR

var tbl_notas_ver_padres_editar;
function listar_notas_ver_padres_editar() {
let matri = document.getElementById('id_matri_editar').value;
let bime = document.getElementById('select_bimestre_editar').value;

// Configura la tabla de notas
tbl_notas_ver_padres_editar = $("#tabla_vistacomp_padre_editar").DataTable({
    "ordering": false,
    "bLengthChange": true,
    "searching": true,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "pageLength": 3,
    "destroy": true,
    "pagingType": 'full_numbers',
    "scrollCollapse": true,
    "responsive": true,
    "processing": true,
    "serverSide": false,
    "ajax": {
        "url": "../controller/notas/controlador_listar_criterios_notas_mostrar_padres.php",
        type: 'POST',
        data: function(d) {
            d.matri = matri;
            d.bime = bime;
        },
        dataSrc: function(json) {
            return json.data;
        }
    },
    "columns": [
      { 
        "data": "id_nota_papa", 
        "title": "ID Nota padres",
        "render": function(data, type, row) {
            if (type === 'display') {
                // Renderiza un input de texto para "Competencias" con una clase específica
                return '<input type="text" class="idpapas form-control" readonly value="' + data + '" />';
            }
            return data;
        }
    },
          { 
              "data": "criterio", 
              "title": "Competencias",
              "width": "800px", // Aumenta el ancho de la columna según sea necesario
              "render": function(data, type, row) {
                  if (type === 'display') {
                      // Renderiza un input de texto para "Competencias" con una clase específica
                      return '<input type="text"  class="competencias_papa form-control" value="' + data + '" />';
                  }
                  return data;
              }
          },

          { 
              "data": "nota", 
              "title": "Nota",
              "render": function(data, type, row) {
                  if (type === 'display') {
                      // Renderiza un input de número para "Nota" con una clase específica
                      return '<input type="text" class="nota_papa form-control" value="' + data + '" />';
                  }
                  return data;
              }
          }
      ],

    "drawCallback": function(settings) {
        // Reaplica los estilos a la cabecera
        $('#tabla_vistacomp_padre_editar thead').css({
            'background-color': '#0A5D86',
            'color': '#FFFFFF'
        });
        $('#tabla_vistacomp_padre_editar thead th').css({
            'text-align': 'center',
            'padding': '5px'
        });

        // Aplicar estilos adicionales a la columna 'Competencias'
        $('#tabla_vistacomp_padre_editar th:nth-child(1), #tabla_vistacomp_padre_ver td:nth-child(1)').css({
            'max-width': '900px', // Ajusta el ancho máximo según sea necesario
            'overflow': 'visible', // Permite que el contenido sea visible
            'text-overflow': 'clip', // Muestra el texto completo sin puntos suspensivos
            'white-space': 'normal' // Permite el ajuste de línea
        });
    },
    "language": idioma_espanol,
    "select": true
});

}

  
  // Evento para cuando se abre el modal


$('#tabla_notas').on('click','.editar',function(){
  var data = tbl_notas.row($(this).parents('tr')).data();

  if(tbl_notas.row(this).child.isShown()){
      var data = tbl_notas.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('id_matri_editar').value = data.id_matricula;
  document.getElementById('txt_estudiante_editar').value = data.alum_dni + " - " + data.Estudiante;
  document.getElementById('txt_nivel_editar').value = data.Nivel_academico;
  document.getElementById('txt_aula_editar').value = data.Grado;
})



function listar_notas_ver_editar() {
  listar_notas_ver_padres_editar();

  let matri = document.getElementById('id_matri_editar').value;
  let bime = document.getElementById('select_bimestre_editar').value;

  // Configura la tabla de notas
  tbl_notas_ver = $("#tabla_vistacomp_editar").DataTable({
    "ordering": false,
    "bLengthChange": true,
    "searching": true,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "pageLength": 3,
    "destroy": true,
    "pagingType": 'full_numbers',
    "scrollCollapse": true,
    "responsive": true,
    "processing": true,
    "serverSide": false,
    "ajax": {
      "url": "../controller/notas/controlador_listar_criterios_notas_mostrar.php",
      type: 'POST',
      data: function(d) {
        d.matri = matri;
        d.bime = bime;
      },
      dataSrc: function(json) {
        return json.data;
      }
    },
    "columns": [
      { "data": "nombre_asig", "visible": false },
      {
        "data": "id_nota_bole",
        "title": " ID Nota",
        "render": function(data, type, row) {
          if (type === 'display') {
            return '<input type="number" readonly class="IDnota form-control" value="' + data + '" />';
          }
          return data;
        }
      },
      { "data": "competencias", "title": "Competencias" },
      {
        "data": "nota",
        "title": "Nota",
        "render": function(data, type, row) {
          if (type === 'display') {
            return '<input type="text" class="nota_editar form-control" value="' + data + '" />';
          }
          return data;
        }
      },
      {
        "data": "conclusiones",
        "title": "Conclusión Descriptiva",
        "render": function(data, type, row) {
          if (type === 'display') {
            return '<input type="text" class="conclusiones_editar form-control" value="' + data + '" />';
          }
          return data;
        }
      }
    ],
    "drawCallback": function(settings) {
      var api = this.api();
      var rows = api.rows({ page: 'current' }).nodes();
      var last = null;

      api.column(0, { page: 'current' }).data().each(function(group, i) {
        if (last !== group) {
          $(rows).eq(i).before(
            '<tr class="group"><td colspan="4" style="background-color:#f0f0f0;"><strong>Área: ' + group + '</strong></td></tr>'
          );
          last = group;
        }
      });

      // Reaplica los estilos a la cabecera
      $('#tabla_vistacomp_editar thead').css({
        'background-color': '#0A5D86',
        'color': '#FFFFFF'
      });
      $('#tabla_vistacomp_editar thead th').css({
        'text-align': 'center',
        'padding': '10px'
      });
    },
    "language": idioma_espanol,
    "select": true
  });
}




function Editar_notas() {
  let table = $('#tabla_vistacomp_editar').DataTable();
  let registros = [];

  table.rows().every(function(rowIdx, tableLoop, rowLoop) {
      let $row = $(this.node());
      let id_nota_bole = $row.find('.IDnota').val();
      let nota = $row.find('.nota_editar').val();
      let conclusiones = $row.find('.conclusiones_editar').val();

      if (id_nota_bole && nota) {
          registros.push({
              id_nota_bole: id_nota_bole,
              nota: nota,
              conclusiones: conclusiones
          });
      }
  });

  if (registros.length === 0) {
      return Swal.fire("Mensaje de Advertencia", "No hay registros para enviar", "warning");
  }

  $.ajax({
      url: "../controller/notas/controlador_editar_notas.php",
      type: 'POST',
      data: {
          registros: JSON.stringify(registros)
      },
      dataType: 'json'  // Esperamos una respuesta JSON del servidor
  }).done(function(response) {
      console.log("Respuesta del servidor:", response);  // Para debugging

      if (response.status === 1) {

            Editar_notas_padre();
      } else if (response.status === 2) {
          let errorMessage = "Hubo errores al actualizar algunas notas:\n";
          response.errores.forEach(error => {
              errorMessage += "- " + error + "\n";
          });
          Swal.fire("Mensaje de Advertencia", errorMessage, "warning");
      } else {
          Swal.fire("Mensaje de Error", "No se completó la edición: " + response.message, "error");
      }
  }).fail(function(jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud AJAX:", textStatus, errorThrown);  // Para debugging
      Swal.fire("Mensaje de Error", "No se pudo conectar con el servidor: " + errorThrown, "error");
  });
}


function Editar_notas_padre() {
  let table = $('#tabla_vistacomp_padre_editar').DataTable();
  let registros = [];

  table.rows().every(function(rowIdx, tableLoop, rowLoop) {
      let $row = $(this.node());
      let id_nota_papa = $row.find('.idpapas').val();
      let criterio = $row.find('.competencias_papa').val();
      let nota = $row.find('.nota_papa').val();

      if (id_nota_papa && criterio && nota) {
          registros.push({
              id_nota_papa: id_nota_papa,
              criterio: criterio,
              nota: nota
          });
      }
  });
  console.log(registros);
  if (registros.length === 0) {
      return Swal.fire("Mensaje de Advertencia", "No hay registros para enviar", "warning");
  }

  $.ajax({
      url: "../controller/notas/controlador_editar_notas_padre.php",
      type: 'POST',
      data: {
          registros: JSON.stringify(registros)
      },
      dataType: 'json'
  }).done(function(response) {
      console.log("Respuesta del servidor:", response);  // Para debugging

      if (response.status === 1) {
          Swal.fire("Mensaje de Confirmación", response.message, "success")
          .then(() => {
              table.ajax.reload();
              $("#modal_editar").modal('hide');
          });
      } else if (response.status === 2) {
          let errorMessage = "Hubo errores al actualizar algunas notas de padres:\n";
          response.errores.forEach(error => {
              errorMessage += "- " + error + "\n";
          });
          Swal.fire("Mensaje de Advertencia", errorMessage, "warning");
      } else {
          Swal.fire("Mensaje de Error", "No se completó la edición: " + response.message, "error");
      }
  }).fail(function(jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud AJAX:", textStatus, errorThrown);  // Para debugging
      Swal.fire("Mensaje de Error", "No se pudo conectar con el servidor: " + errorThrown, "error");
  });
}