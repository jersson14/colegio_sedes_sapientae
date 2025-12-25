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
          "url":"../controller/notas/controlador_listar_matriculas_filtro_profesor.php",
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
        

      {"defaultContent":"<button class='insert btn btn-success btn-sm' title='Insertar notas'><i class='fas fa-pen'></i> Insertar notas estudiante</button>"},



        {"defaultContent":"<button class='mostrar btn btn-warning  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Mostrar notas por bimestre</button>"},
        
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

function Cargar_Select_Grado(){
    let id = document.getElementById('txtprincipalid').value;
    $.ajax({
      "url":"../controller/notas/controlador_cargar_select_grado_profesor.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+" - "+ data[i][2]+"</option>";    
        }
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

      }else{
        cadena+="<option value=''>No se encontraron regitros</option>";
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

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


  function Cargar_Bimestre_cargados(id){
    $.ajax({
      "url":"../controller/notas/controlador_cargar_periodos_cargados_estudiante.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      let data=JSON.parse(resp);

      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][2]+"</option>";    
        }
          document.getElementById('select_bimestre_ver').innerHTML=cadena;

      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_bimestre_ver').innerHTML=cadena;

      }
    })
  }
//TRAENDO DATOS DE LA NIVEL ACADEMICO



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
  let id = document.getElementById('txtprincipalid').value;

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
          "url": "../controller/notas/controlador_listar_criterios_notas_profesor.php",
          type: 'POST',
          data: { nivel: nivel, aula: aula, id: id },
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
      "language": {
          "emptyTable": "No se encontraron datos",
          "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"  // Incluye la traducción al español para otros elementos si es necesario
      },
      "select": true
  });
}



//MOSTRAR

// Maneja el clic en el botón de mostrar


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
                Swal.fire("Mensaje de Confirmación", `Notas de alumno registradas satisfactoriamente!!!.`, "success").then(() => {
                });
                tbl_notas.ajax.reload();
                $("#modal_notas").modal('hide');
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
          "url": "../controller/notas/controlador_listar_criterios_notas_mostrar_profesor.php",
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







