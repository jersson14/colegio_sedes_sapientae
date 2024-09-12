//LISTADO DE ROLES
var tbl_asigdocente;
function listar_asignatura_docente(){
    tbl_asigdocente = $("#tabla_asigdocente").DataTable({
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
          "url":"../controller/asignatura_docente/controlador_listar_asignatura_docentes.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE ASIGNATURA DOCENTE"
      },
        title: function() {
          return  "LISTA DE ASIGNATURA DOCENTE" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE ASIGNATURA DOCENTE"
      },
    title: function() {
      return  "LISTA DE ASIGNATURA DOCENTE"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE ASIGNATURA DOCENTE"
  
    }
    }],
      "columns":[
        {"data":"Id_asigdocente"},
        {"data":"docente_dni"},
        {"data":"Docente"},
        {"data":"Grado"},
        {"data":"Nivel_academico"},
        {"data":"Total_cursos"},
        {"data":"fecha_formateada"},
        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Ver cursos asignados'><i class='fa fa-eye'></i> Ver Cursos</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_asigdocente.on('draw.td',function(){
  var PageInfo = $("#tabla_asigdocente").DataTable().page.info();
  tbl_asigdocente.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
//TRAENDO DATOS DEL DOCENTE
function Cargar_Select_docente(){
    $.ajax({
      "url":"../controller/asignatura_docente/controlador_cargar_docente.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"-"+data[i][4]+"</option>";    
        }
          document.getElementById('select_docente2').innerHTML=cadena;
          document.getElementById('select_docente2_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay docentes en la base de datos</option>";
        document.getElementById('select_docente2').innerHTML=cadena;
        document.getElementById('select_docente2_editar').innerHTML=cadena;

      }
    })
  }

  function Cargar_Select_aulas() {
    $.ajax({
      url: "../controller/asignaturas/controlador_cargar_select_grado.php",
      type: 'POST',
    }).done(function(resp) {
      let data = JSON.parse(resp);
      let cadena = "";
  
      if (data.length > 0) {
        for (let i = 0; i < data.length; i++) {
          cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + " - " + data[i][2] + "</option>";
        }
        $('#select_grado').html(cadena);
        $('#select_grado_editar').html(cadena);
  
        // Cargar asignaturas para el grado seleccionado por defecto
        var idCreacion = $("#select_grado").val();
        var idEdicion = $("#select_grado_editar").val();
        
        if (idCreacion) {
          Cargar_Select_asignatura(idCreacion, 'creacion');
        }
        
        if (idEdicion) {
          Cargar_Select_asignatura(idEdicion, 'edicion');
        }
      } else {
        // No se encontraron registros, mostrar mensaje adecuado
        $('#select_grado').html("<option value=''>No se encontraron grados</option>");
        $('#select_grado_editar').html("<option value=''>No se encontraron grados</option>");
  
        // Limpiar el select de cursos y mostrar mensaje
        $('#select_curso').html("<option value=''>No hay cursos disponibles</option>");
        $('#select_curso_editar').html("<option value=''>No hay cursos disponibles</option>");
      }
    });
  }
  
  function Cargar_Select_asignatura(id, modo) {
    $.ajax({
      url: "../controller/asignatura_docente/controlador_cargar_select_asignatura.php",
      type: 'POST',
      data: {
        id: id
      }
    }).done(function(resp) {
      let data = JSON.parse(resp);
      let cadena = "";
  
      if (data.length > 0) {
        for (let i = 0; i < data.length; i++) {
          cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
        }
        if (modo === 'creacion') {
          $('#select_curso').html(cadena);
        } else if (modo === 'edicion') {
          $('#select_curso_editar').html(cadena);
        }
      } else {
        // No se encontraron cursos, limpiar el select y mostrar mensaje
        if (modo === 'creacion') {
          $('#select_curso').html("<option value=''>No hay cursos disponibles</option>");
        } else if (modo === 'edicion') {
          $('#select_curso_editar').html("<option value=''>No hay cursos disponibles</option>");
        }
      }
    });
  }
  
  // Manejadores de eventos para cambios en los selects de grados
  $('#select_grado').on('change', function() {
    var id = $(this).val();
    if (id) {
      Cargar_Select_asignatura(id, 'creacion');
    } else {
      // Si no se selecciona un grado, limpiar los cursos
      $('#select_curso').html("<option value=''>Seleccione un grado</option>");
    }
  });
  
  $('#select_grado_editar').on('change', function() {
    var id = $(this).val();
    if (id) {
      Cargar_Select_asignatura(id, 'edicion');
    } else {
      // Si no se selecciona un grado, limpiar los cursos
      $('#select_curso_editar').html("<option value=''>Seleccione un grado</option>");
    }
  });
  $('#tabla_asigdocente').on('click', '.editar', function() {
    var data = tbl_asigdocente.row($(this).parents('tr')).data();
    if (tbl_asigdocente.row(this).child.isShown()) {
        var data = tbl_asigdocente.row(this).data();
    }
    // Mostrar el modal de edición
    $("#modal_editar").modal('show');
  
    // Rellenar los campos del modal
    document.getElementById('txt_id_asig').value = data.Id_asigdocente;
    $("#select_docente2_editar").select2().val(data.Id_docente).trigger('change.select2');
    $("#select_grado_editar").select2().val(data.Id_grado).trigger('change.select2');
  
    listar_cursos_docente2(data.Id_asigdocente);
  
  });
  
  
//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}
$('#tabla_asigdocente').on('click','.mostrar',function(){
  var data = tbl_asigdocente.row($(this).parents('tr')).data();

  if(tbl_asigdocente.row(this).child.isShown()){
      var data = tbl_asigdocente.row(this).data();
  }
$("#modal_ver_cursos").modal('show');
  document.getElementById('lb_titulo').innerHTML="<b>CURSOS DEL DOCENTE: "+data.Docente+"</b>";
  listar_cursos_docente(data.Id_asigdocente);
})
//REGISTRANDO ROLES
function Registrar_aula(){
  let grado = document.getElementById('txt_grado').value;
  let seccion = document.getElementById('select_seccion').value;
  let nivel = document.getElementById('select_nivel_aca').value;
  let descrip = document.getElementById('txt_descripcion').value;

  if(grado.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/aulas/controlador_registro_aulas.php",
    type:'POST',
    data:{
        grado:grado,
        seccion:seccion,
        nivel:nivel,
        descrip:descrip
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva grado académico registrado satisfactoriamente!!!","success").then((value)=>{
          tbl_asigdocente.ajax.reload();
          document.getElementById('txt_grado').value="";
          document.getElementById('txt_descripcion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El grado académico que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Rol(){
  let id = document.getElementById('txt_id_grado').value;
  let grado = document.getElementById('txt_grado_editar').value;
  let seccion = document.getElementById('select_seccion_editar').value;
  let nivel = document.getElementById('select_nivel_aca_editar').value;
  let descrip = document.getElementById('txt_descripcion_editar').value;
  let estatus = document.getElementById('txt_estatus').value;

  if(grado.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/aulas/controlador_modificar_aula.php",
    type:'POST',
    data:{
      id:id,
      grado:grado,
      seccion:seccion,
      nivel:nivel,
      descrip:descrip,
      estatus:estatus
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_asigdocente.ajax.reload();
            $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El grado académico que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Asigdocente(id){
    $.ajax({
      "url":"../controller/asignatura_docente/controlador_eliminar_asignatura_docente.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la asignación de cursos del docente","success").then((value)=>{
            tbl_asigdocente.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta asignación por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_asigdocente').on('click','.delete',function(){
    var data = tbl_asigdocente.row($(this).parents('tr')).data();
  
    if(tbl_asigdocente.row(this).child.isShown()){
        var data = tbl_asigdocente.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el la asignación de cursos del docente: '+data.Docente+'?',
      text: "Una vez aceptado la asignación sera borrada y los cursos volveran a estar sin asignación!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Asigdocente(data.Id_asigdocente);
      }
    })
  })


  function Agregar_curso(){
    var id_asignatura=$("#select_curso").val();
    var asig=$("#select_curso option:selected").text();


    if(verificarid(id_asignatura)){
     return Swal.fire("Mensaje de Advertencia","La asignatura ya fue agregado a la tabla","warning");
    }
  
    var datos_agregar ="<tr>";
    datos_agregar+="<td for='id'>"+id_asignatura+"</td>";
    datos_agregar+="<td>"+asig+"</td>";
    datos_agregar+="<td><button class='btn btn-danger' onclick='remove(this)'><i class='fas fa-trash'><i></button></td>";
    datos_agregar+="</tr>";
    $("#tabla_asignacion").append(datos_agregar);
   
  }
  
  function Agregar_curso_editar(){
    var id_asignatura2=$("#select_curso_editar").val();
    var asig=$("#select_curso_editar option:selected").text();


    if(verificarid2(id_asignatura2)){
     return Swal.fire("Mensaje de Advertencia","La asignatura ya fue agregado a la tabla","warning");
    }
  
    var datos_agregar ="<tr>";
    datos_agregar+="<td for='id'>"+id_asignatura2+"</td>";
    datos_agregar+="<td>"+asig+"</td>";
    datos_agregar+="<td><button class='btn btn-danger btn-sm' onclick='remove(this)' title='Eliminar datos de especialidad'><i class='fas fa-trash'><i> Eliminar</button></td>";
    datos_agregar+="</tr>";
    $("#tabla_asignacion_editar").append(datos_agregar);
   
  }
 

  
  function verificarid(id){
    let idverificar=document.querySelectorAll('#tabla_asignacion td[for="id"]');
    return [].filter.call(idverificar, td=>td.textContent ===id).length===1;
  }
  function verificarid2(id){
    let idverificar=document.querySelectorAll('#tabla_asignacion_editar td[for="id"]');
    return [].filter.call(idverificar, td=>td.textContent ===id).length===1;
  }
  function remove(t){
    var td =t.parentNode;
    var tr=td.parentNode;
    var table =tr.parentNode;
    table.removeChild(tr);
  }
  

 function Registrar_ASIGDOCENTE(){
    let count = 0;
    $("#tabla_asignacion tbody#tbody_tabla_asignacion tr").each(function(){
        count++;
    });
    if(count == 0){
        return Swal.fire("Mensaje de Advertencia","La tabla de asignación debe tener al menos un registro","warning");
    }
   
    var docen = $("#select_docente2").val();
    var curso = $("#select_curso").val();
    if(docen.length == 0 || curso.length == 0){
        return Swal.fire("Mensaje De Advertencia","Debe llenar los datos de la consulta primero para guardar","warning");
    }

    let id_docente = document.getElementById('select_docente2').value;

    $.ajax({
        url: "../controller/asignatura_docente/controlador_asig_docente.php",
        type: 'POST',
        data: {
            id_docente: id_docente
        }
    }).done(function(resp){
        if(resp > 0){
            Registrar_Detalle_asigdocente(parseInt(resp));
            Swal.fire("Mensaje de Confirmación", "Datos registrados correctamente", "success").then((value) => {
                // Recargar la tabla de asignaturas
                tbl_asignatura_docente.ajax.reload();
                
                // Limpiar la tabla de asignación
                $('#tabla_cursos').DataTable().clear().draw();

                // Limpiar los campos de entrada
                document.getElementById('select_curso').value = "";
                $("#modal_registro").modal('hide');
                table.removeChild(tr);

                // Actualizar el select de asignaturas
                Cargar_Select_asignatura();
            });
        } else {
            return Swal.fire("Mensaje De Advertencia","Lo sentimos, la hora que selecciono ya ha sido ocupada por otro paciente en el mismo día, revise","warning");
        }
    });
}
  function Registrar_Detalle_asigdocente(id){
    let count=0;
    let arreglo_asignatura=new Array();
    
    $("#tabla_asignacion tbody#tbody_tabla_asignacion tr").each(function(){
      
      arreglo_asignatura.push($(this).find('td').eq(0).text());


      count++;
    })
    if(count==0){
      return Swal.fire("Mensaje de Advertencia","El detalle de los cursos debe tener al menos un registro","warning");
  
    }
    let asignatura= arreglo_asignatura.toString();

  
    $.ajax({
      url:"../controller/asignatura_docente/controlador_detalle_asignatura_docente.php",
      type:'POST',
      data:{
        id:id,
        asignatura:asignatura
      }
  }).done(function(resp){
    if(resp>0){
      tbl_asigdocente.ajax.reload();
      tbl_asigdocente.clear().draw();
      document.getElementById('select_curso').value="";

      $("#modal_registro").modal('hide');
      Cargar_Select_asignatura();
      table.removeChild(tr);


    }
    else{
      return Swal.fire("Mensaje De Advertencia","Lo sentimos, la hora que selecciono ya ah sido ocupada por otro curso","warning");
    }
      
    });
  }
  //LISTADO DE CURSOS DOCENTE
var tbl_asignatura_docente;
function listar_cursos_docente(id){
  tbl_asignatura_docente = $("#tabla_cursos").DataTable({
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
        "url":"../controller/asignatura_docente/controlador_listar_tabla_detalle_curso.php",
        type:'POST',
        data:{
          id:id
        }
    },
     
      "columns":[
        {"data":"Id_detalle_asig_docente"},
        {"data":"nombre_asig"},
        {"data":"Grado"},
        {"data":"Nivel_academico"},
        {"data":"observaciones"},

    ],

    "language":idioma_espanol,
    select: true
});
tbl_asignatura_docente.on('draw.td',function(){
  var PageInfo = $("#tabla_cursos").DataTable().page.info();
  tbl_asignatura_docente.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}


var tbl_traer_datos;
function listar_cursos_docente2(id){
  tbl_traer_datos = $("#tabla_asignacion_editar").DataTable({
      "ordering":false,   
      "bLengthChange":false,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      "destroy":true,
      pagingType: 'full_numbers',
      scrollCollapse: false,
      responsive: false,
      "async": false ,
      "processing": true,
      "ajax":{
        "url":"../controller/asignatura_docente/controlador_listar_tabla_detalle_curso.php",
        type:'POST',
        data:{
          id:id
        }
    },
     
      "columns":[
        {"data":"Id_asignatura"},
        {"data":"nombre_asig"},
        {"defaultContent":"<button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>"},

    ],

    "language":idioma_espanol,
    select: true
});

}
$('#tabla_asignacion_editar').on('click', '.delete', function() {
  var data = tbl_traer_datos.row($(this).parents('tr')).data();
  if (tbl_traer_datos.row(this).child.isShown()) {
      var data = tbl_traer_datos.row(this).data();
  }
  // Mostrar el modal de edición
  $("#modal_editar").modal('show');
  Swal.fire({
    title: 'Esta seguro que desea eliminar el curso asignado: '+data.nombre_asig+'?',
    text: "Una vez aceptado el curso asignado sera eliminado y se pondra con el estado de SIN DOCENTE!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_Asignatura_detalle(parseInt(data.Id_detalle_asig_docente))
    }
  })
});

function Eliminar_Asignatura_detalle(id){
  $.ajax({
    "url":"../controller/asignatura_docente/controlador_eliminar_asignatura_unica.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se elimino la asignatura con exito!!!","success").then((value)=>{
          tbl_traer_datos.ajax.reload();
        });
    }else{
      return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta asignatura por que esta siendo utilizado en otro registros, verifique por favor","warning");

    }
  })
}

function Modificar_Detalle_Asignar_docente(){
  let id = document.getElementById('txt_id_asig').value;
  let curso = document.getElementById('select_curso_editar').value;



  if(id.length==0 ||id.curso==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/asignatura_docente/controlador_modificar_asignatura_docente_unico.php",
    type:'POST',
    data:{
      id:id,
      curso:curso

    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
          tbl_asigdocente.ajax.reload();
            $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La asignatura y grado que intentas que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}