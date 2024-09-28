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
        
     
      


        {"defaultContent":"<button class='print_bimestre btn btn-warning  btn-sm' title='Imprimir notas por bimestre'><i class='fa fa-print'></i> Imprimir notas por bimestre</button>&nbsp;&nbsp;<button class='print_total btn btn-success  btn-sm' title='Imprimir notas general'><i class='fa fa-print'></i> Imprimir notas general</button>"},
        
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
      "url":"../controller/notas/controlador_cargar_periodos2.php",
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


$('#tabla_notas').on('click','.print_bimestre',function(){
    var data = tbl_notas.row($(this).parents('tr')).data();
  
    if(tbl_notas.row(this).child.isShown()){
        var data = tbl_notas.row(this).data();
    }
    $("#modal_imprimir").modal('show');
    document.getElementById('txt_id_matricula').value=data.id_matricula;
    document.getElementById('txt_alumno').value=data.Estudiante;

  })



  function Imprimir_notas() {

    var id = document.getElementById('txt_id_matricula').value.toString();
    var bime = document.getElementById('select_bimestre').value.toString();
    
    var url = "../view/MPDF/REPORTE/notas_por_bimestre.php?"
    + "id_matricula=" + encodeURIComponent(id)
    + "&id_bimestre=" + encodeURIComponent(bime)
    + "#zoom=100%";
    
    var width = screen.width;
    var height = screen.height;
    
    window.open(url, "Boleta de notas por bimestre", "scrollbars=NO,width=" + width + ",height=" + height + ",top=0,left=0");
  
  }


  $('#tabla_notas').on('click','.print_total',function(){
    var data = tbl_notas.row($(this).parents('tr')).data();
  
    if(tbl_notas.row(this).child.isShown()){
        var data = tbl_notas.row(this).data();
    }
    var url = "../view/MPDF/REPORTE/notas_general.php?id_matricula=" + encodeURIComponent(data.id_matricula)+ "#zoom=100%";
  
    // Abrir una nueva ventana con la URL construida
    var newWindow = window.open(url, "BOLETA GENERAL", "scrollbars=NO");
    
    // Asegurarse de que la ventana se abre en tamaño máximo
    if (newWindow) {
        newWindow.moveTo(0, 0);
        newWindow.resizeTo(screen.width, screen.height);
    }
  
  })