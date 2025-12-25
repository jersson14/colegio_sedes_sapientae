//LISTADO DE ROLES
var tbl_asignaturas;
function listar_asignaturas(){
    tbl_asignaturas = $("#tabla_asignaturas").DataTable({
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
          "url":"../controller/asignaturas/controlador_listar_asignaturas.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE AULAS"
      },
        title: function() {
          return  "LISTA DE AULAS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE AULAS"
      },
    title: function() {
      return  "LISTA DE AULAS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE AULAS"
  
    }
    }],
      "columns":[
        {"data":"Id_asignatura"},
        {"data":"nombre_asig"},
        {"data":"GradoSECCION"},
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
        {"data":"observaciones"},
        {"data":"fecha_formateada"},
        {"data":"estado",
            render: function(data,type,row){
                    if(data=='CON DOCENTE'){
                    return '<span class="badge bg-success">CON DOCENTE</span>';
                    }else{
                    return '<span class="badge bg-danger">SIN DOCENTE</span>';
                    }
            }   
        },
        {"data":"estado",
            render: function(data,type,row){
                    if(data=='CON DOCENTE'){
                    return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>";
                    }else{
                    return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>";
                    }
            }   
        },
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_asignaturas.on('draw.td',function(){
  var PageInfo = $("#tabla_asignaturas").DataTable().page.info();
  tbl_asignaturas.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
function listar_asignaturas_filtro(){
  let grado = document.getElementById('select_aula_buscar').value;

  tbl_asignaturas = $("#tabla_asignaturas").DataTable({
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
        "url":"../controller/asignaturas/controlador_listar_asignaturas_filtro.php",
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
      return  "LISTA DE AULAS"
    },
      title: function() {
        return  "LISTA DE AULAS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE AULAS"
    },
  title: function() {
    return  "LISTA DE AULAS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE AULAS"

  }
  }],
    "columns":[
      {"data":"Id_asignatura"},
      {"data":"nombre_asig"},
      {"data":"GradoSECCION"},
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
      {"data":"observaciones"},
      {"data":"fecha_formateada"},
      {"data":"estado",
          render: function(data,type,row){
                  if(data=='CON DOCENTE'){
                  return '<span class="badge bg-success">CON DOCENTE</span>';
                  }else{
                  return '<span class="badge bg-danger">SIN DOCENTE</span>';
                  }
          }   
      },
      {"data":"estado",
          render: function(data,type,row){
                  if(data=='CON DOCENTE'){
                  return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>";
                  }else{
                  return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de especialidad'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='delete btn btn-danger  btn-sm' title='Eliminar datos de especialidad'><i class='fa fa-trash'></i> Eliminar</button>";
                  }
          }   
      },
      
  ],

  "language":idioma_espanol,
  select: true
});
tbl_asignaturas.on('draw.td',function(){
var PageInfo = $("#tabla_asignaturas").DataTable().page.info();
tbl_asignaturas.column(0, {page: 'current'}).nodes().each(function(cell, i){
  cell.innerHTML = i + 1 + PageInfo.start;
});
});
}
//TRAENDO DATOS DE LA SECCION
// Función para cargar los niveles en los selectores
function Cargar_Select_Nivelaca() {
  $.ajax({
    url: "../controller/aulas/controlador_cargar_select_nivel.php",
    type: 'POST',
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "";

    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
      }
      $('#select_nivel').html(cadena);
      $('#select_nivel_editar').html(cadena);
      $('#select_nivel_buscar').html(cadena);

      // Cargar secciones basadas en el nivel seleccionado al iniciar
      var id = $("#select_nivel").val();
      Cargar_Select_Aula(id);

      // Verificar si se necesita cargar las secciones para la edición
      var idEditar = $("#select_nivel_editar").val();
      Cargar_Select_Aula(idEditar);

      var idEditar2 = $("#select_nivel_buscar").val();
      Cargar_Select_Aula(idEditar2);
    } else {
      cadena += "<option value=''>No se encontraron registros</option>";
      $('#select_nivel_editar').html(cadena);
    }
  });
}

// Función para cargar las secciones basadas en el nivel seleccionado
function Cargar_Select_Aula(id) {
  $.ajax({
    url: "../controller/asistencias/controlador_cargar_select_aula_id.php",
    type: 'POST',
    data: { id: id }
  }).done(function(resp) {
    let data = JSON.parse(resp);
    let cadena = "";

    if (data.length > 0) {
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='" + data[i][1] + "'>" + data[i][2] + "</option>";
      }
      document.getElementById('select_grado').innerHTML = cadena;
      document.getElementById('select_grado_editar').innerHTML = cadena;
      document.getElementById('select_aula_buscar').innerHTML = cadena;

    } else {
      cadena += "<option value=''>No hay secciones en la base de datos</option>";
      document.getElementById('select_grado').innerHTML = cadena;
      document.getElementById('select_grado_editar').innerHTML = cadena;
      document.getElementById('select_aula_buscar').innerHTML = cadena;

    }
  });
}

// Al hacer clic en el botón de edición
$('#tabla_asignaturas').on('click', '.editar', function() {
  var data = tbl_asignaturas.row($(this).parents('tr')).data();

  if (tbl_asignaturas.row(this).child.isShown()) {
    data = tbl_asignaturas.row(this).data();
  }

  // Mostrar el modal de edición
  $("#modal_editar").modal('show');

  // Rellenar los campos del modal con los datos correspondientes
  document.getElementById('txt_id_asig').value = data.Id_asignatura;
  document.getElementById('txt_asignatura_editar').value = data.nombre_asig;
  
  // Actualizar select2 y seleccionar el valor correcto
  $("#select_nivel_editar").val(data.Id_nivel).trigger('change.select2');
  $("#select_grado_editar").val(data.Id_grado).trigger('change.select2');

  // Rellenar el campo de observaciones
  document.getElementById('txt_observacion_editar').value = data.observaciones;
});

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_asignatura(){
  let asigna = document.getElementById('txt_asignatura').value;
  let grado = document.getElementById('select_grado').value;
  let obse = document.getElementById('txt_observacion').value;

  if(asigna.length==0||grado.length==0){
      return Swal.fire("Mensaje de Advertencia","Llene la asignatura este campo no puede ir vacio.","warning");
  }
  $.ajax({
    "url":"../controller/asignaturas/controlador_registro_asignaturas.php",
    type:'POST',
    data:{
        asigna:asigna,
        grado:grado,
        obse:obse
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Nueva asignatura registrada satisfactoriamente!!!","success").then((value)=>{
          tbl_asignaturas.ajax.reload();
          document.getElementById('txt_asignatura').value="";
          document.getElementById('txt_observacion').value="";
        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La asignatura que intentas registrar al respectivo grado ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Asignatura(){
  let id = document.getElementById('txt_id_asig').value;
  let asigna = document.getElementById('txt_asignatura_editar').value;
  let grado = document.getElementById('select_grado_editar').value;
  let observa = document.getElementById('txt_observacion_editar').value;


  if(grado.length==0 ||asigna.length==0 || id.length==0){
      return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/asignaturas/controlador_modificar_asignaturas.php",
    type:'POST',
    data:{
      id:id,
      asigna:asigna,
      grado:grado,
      observa:observa

    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_asignaturas.ajax.reload();
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
//ELIMINANDO ROL
function Eliminar_Aula(id){
    $.ajax({
      "url":"../controller/asignaturas/controlador_eliminar_asignatura.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino la asignatura con exito","success").then((value)=>{
            tbl_asignaturas.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar esta asignatura por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_asignaturas').on('click','.delete',function(){
    var data = tbl_asignaturas.row($(this).parents('tr')).data();
  
    if(tbl_asignaturas.row(this).child.isShown()){
        var data = tbl_asignaturas.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar la asignatura: '+data.nombre_asig+'?',
      text: "Una vez aceptado la asignatura sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Aula(data.Id_asignatura);
      }
    })
  })