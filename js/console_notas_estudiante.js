//LISTADO DE ROLES
var tbl_notas;
function listar_notas_todos(){
  let año = document.getElementById('select_año').value;
    let id = document.getElementById('txtprincipalid').value;

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
          "url":"../controller/notas/controlador_listar_matriculas_filtro_alumnos.php",
          type:'POST',
          data:{
            año:año,
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
        




        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostrar notas'><i class='fa fa-eye'></i> Visualizar notas por bimestre</button>"},
        
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
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+" || "+data[i][4]+" || "+data[i][5]+"</option>";    
        }
          document.getElementById('select_año').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
      }
    })
  }


//TRAENDO DATOS DE LA SECCION



function Cargar_Bimestre_cargados(id) {
  if (id === undefined || id === null) {
      console.error("El id es indefinido o nulo.");
      return; // Salir de la función si el id no es válido
  }

  $.ajax({
      "url": "../controller/notas/controlador_cargar_periodos_cargados_estudiante.php",
      type: 'POST',
      data: {
          id: id
      }
  }).done(function(resp) {
      let data = JSON.parse(resp);
      let cadena = "";

      if (data.length > 0) {
          for (let i = 0; i < data.length; i++) {
              cadena += "<option value='" + data[i][0] + "'>" + data[i][2] + "</option>";
          }
          document.getElementById('select_bimestre_ver').innerHTML = cadena;
      } else {
          cadena += "<option value=''>No hay secciones en la base de datos</option>";
          document.getElementById('select_bimestre_ver').innerHTML = cadena;
      }
  });
}

//TRAENDO DATOS DE LA NIVEL ACADEMICO



//REGISTRANDO ROLES









///MODAL VISTAS
/// MODAL VISTAS
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
  Cargar_Bimestre_cargados(data.id_matricula);
});

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
  let id = document.getElementById('txtprincipalid').value;

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
          "url": "../controller/notas/controlador_listar_criterios_notas_mostrar_estudiante.php",
          type: 'POST',
          data: function(d) {
              d.matri = matri;
              d.bime = bime;
              d.id=id;
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
      "language": {
          "emptyTable": "SIN DATOS DISPONIBLES",  // Mensaje cuando no hay datos
          "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
      },
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

