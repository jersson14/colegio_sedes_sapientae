var tbl_comunicados;
function listar_comunicado(){
  tbl_comunicados = $("#tabla_comunicados").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "destroy":true,
      "async": false ,
      "processing": true,
      "ajax":{
          "url":"../controller/comunicados/controlador_listar_comunicados.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> ',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE COMUNICADOS"
      },
        title: function() {
          return  "LISTA DE COMUNICADOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      orientation: 'landscape',
      pageSize: 'LEGAL',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE COMUNICADOS"
      },
    title: function() {
      return  "LISTA DE COMUNICADOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE COMUNICADOS"
  
    }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"tipo",
          render: function(data,type,row){
            if(data=='GENERAL'){
            return '<span class="badge bg-success">GENERAL</span>';
            }else{
            return '<span class="badge bg-warning">POR GRADO</span>';
            }
    }   
        },
        {"data":"Grado"},

        {"data":"titulo"},
        {"data":"descripcion"},
        {"defaultContent":"<button class='mostrar btn btn-warning  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Ver</button>"},

        {"data":"estado",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"data":"fecha_formateada"},

        {"data":"estado",
          render: function(data,type,row){
                  if(data=='ACTIVO'){
                  return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de comunicado'><i class='fa fa-edit'>Editar</i></button>&nbsp;&nbsp;<button class='delete btn btn-danger disabled btn-sm' title='Eliminar registro'><i class='fa fa-trash'></i> Eliminar</button>";
                  }else{
                  return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de comunicado'><i class='fa fa-edit'>Editar</i></button>&nbsp;&nbsp;<button class='delete btn btn-danger  btn-sm' title='Eliminar registro'><i class='fa fa-trash'></i> Eliminar</button>";
                  }
          }   
      },
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_comunicados.on('draw.td',function(){
  var PageInfo = $("#tabla_comunicados").DataTable().page.info();
  tbl_comunicados.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

function listar_comunicado_filtro(){
  let fechaini = document.getElementById('txtfechainicio').value;
  let fechafin = document.getElementById('txtfechafin').value;
  tbl_comunicados = $("#tabla_comunicados").DataTable({
      "ordering":false,   
      "bLengthChange":true,
      "searching": { "regex": false },
      "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
      "pageLength": 10,
      pagingType: 'full_numbers',
      scrollCollapse: true,
      responsive: true,
      "destroy":true,
      "async": false ,
      "processing": true,
      "ajax":{
          "url":"../controller/comunicados/controlador_listar_comunicados_filtro.php",
          type:'POST',
          data:{
            fechaini:fechaini,
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
        return  "LISTA DE COMUNICADOS"
      },
        title: function() {
          return  "LISTA DE COMUNICADOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      orientation: 'landscape',
      pageSize: 'LEGAL',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE COMUNICADOS"
      },
    title: function() {
      return  "LISTA DE COMUNICADOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE COMUNICADOS"
  
    }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"tipo",
          render: function(data,type,row){
            if(data=='GENERAL'){
            return '<span class="badge bg-success">GENERAL</span>';
            }else{
            return '<span class="badge bg-warning">POR GRADO</span>';
            }
    }   
        },
        {"data":"Grado"},

        {"data":"titulo"},
        {"data":"descripcion"},
        {"defaultContent":"<button class='mostrar btn btn-warning  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Ver</button>"},

        {"data":"estado",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"data":"fecha_formateada"},

        {"data":"estado",
          render: function(data,type,row){
                  if(data=='ACTIVO'){
                  return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de comunicado'><i class='fa fa-edit'>Editar</i></button>&nbsp;&nbsp;<button class='delete btn btn-danger disabled btn-sm' title='Eliminar registro'><i class='fa fa-trash'></i> Eliminar</button>";
                  }else{
                  return "<button class='editar btn btn-primary  btn-sm' title='Editar datos de comunicado'><i class='fa fa-edit'>Editar</i></button>&nbsp;&nbsp;<button class='delete btn btn-danger  btn-sm' title='Eliminar registro'><i class='fa fa-trash'></i> Eliminar</button>";
                  }
          }   
      },
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_comunicados.on('draw.td',function(){
  var PageInfo = $("#tabla_comunicados").DataTable().page.info();
  tbl_comunicados.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

$('#tabla_comunicados').on('click','.editar',function(){
  var data = tbl_comunicados.row($(this).parents('tr')).data();

  if(tbl_comunicados.row(this).child.isShown()){
      var data = tbl_comunicados.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_id_comu').value=data.id_comunicado;
  document.getElementById('select_tipo_editar').value=data.tipo;
  $("#select_grado_editar").select2().val(data.id_aula).trigger('change.select2');
  document.getElementById('txt_titulo_editar').value=data.titulo;
  document.getElementById('txt_descripcion_editar').value=data.descripcion;
  document.getElementById('txt_estado').value=data.estado;
  document.getElementById('txt_foto_actual').value=data.imagen;

  var imgElement = document.getElementById('preview3');
console.log('Data:', data);  // Verifica que los datos sean correctos
console.log('Image URL:', data.imagen);  // Verifica la URL de la imagen

if (imgElement) {
  if (data.imagen && data.imagen.trim() !== '') {
    imgElement.src = "../" + data.imagen;
  } else {
    imgElement.src = '../controller/comunicados/fotos/VACIO.png';
  }

  // Manejar errores de carga de la imagen
  imgElement.onerror = function() {
    console.error("Error al cargar la imagen.");
    imgElement.src = '../controller/comunicados/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
  };
} else {
  console.error('Elemento img con id preview3 no encontrado');
}
$(document).ready(function() {
  // Inicializa Select2
  $('#select_grado_editar').select2();

  // Maneja el cambio de select_tipo
  $('#select_tipo_editar').on('change', function() {
      if (this.value === 'POR GRADO') {
          $('#select_grado_editar').prop('disabled', false);
      } else {
          $('#select_grado_editar').prop('disabled', true);
          // Reset Select2 to apply the disabled state correctly
          $('#select_grado_editar').select2();
      }
  });

  // Inicializa el select_grado como deshabilitado si está en "GENERAL"
  if ($('#select_tipo_editar').val() === 'GENERAL') {
      $('#select_grado_editar').prop('disabled', true);
      $('#select_grado_editar').select2(); // Reaplicar Select2 para reflejar el estado deshabilitado
  }
});
})


$('#tabla_comunicados').on('click','.mostrar',function(){
  var data = tbl_comunicados.row($(this).parents('tr')).data();

  if(tbl_comunicados.row(this).child.isShown()){
      var data = tbl_comunicados.row(this).data();
  }
  $("#modal_ver").modal('show');
  document.getElementById('lb_titulo_datos2').innerHTML="<u><b>"+data.titulo+"</b></u>";

  document.getElementById('txt_descripcion_ver').value=data.descripcion;

  var imgElement = document.getElementById('preview4');
console.log('Data:', data);  // Verifica que los datos sean correctos
console.log('Image URL:', data.imagen);  // Verifica la URL de la imagen

if (imgElement) {
  if (data.imagen && data.imagen.trim() !== '') {
    imgElement.src = "../" + data.imagen;
  } else {
    imgElement.src = '../controller/comunicados/fotos/VACIO.png';
  }

  // Manejar errores de carga de la imagen
  imgElement.onerror = function() {
    console.error("Error al cargar la imagen.");
    imgElement.src = '../controller/comunicados/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
  };
} else {
  console.error('Elemento img con id preview4 no encontrado');
}
})
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}


function AbrirComunicado(data) {
  // Verificar si el estado es ACTIVO
  if (data.estado === 'ACTIVO') {
    $("#modal_ver").modal({backdrop:'static', keyboard:false});
    $("#modal_ver").modal('show');
    
    document.getElementById('lb_titulo_datos2').innerHTML = "<u><b>" + data.titulo + "</b></u>";
    document.getElementById('txt_descripcion_ver').value = data.descripcion;
    
    var imgElement = document.getElementById('preview4');
    console.log('Data:', data);
    console.log('Image URL:', data.imagen);
    
    if (imgElement) {
      if (data.imagen && data.imagen.trim() !== '') {
        imgElement.src = "../" + data.imagen;
      } else {
        imgElement.src = '../controller/comunicados/fotos/VACIO.png';
      }
      
      imgElement.onerror = function() {
        console.error("Error al cargar la imagen.");
        imgElement.src = '../controller/comunicados/fotos/VACIO.png';
      };
    } else {
      console.error('Elemento img con id preview4 no encontrado');
    }
  } else {
    console.log('El comunicado está INACTIVO. No se abrirá el modal.');
  }
}
function Cargar_Select_Grado(){
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
        document.getElementById('select_grado').innerHTML=cadena;
        document.getElementById('select_grado_editar').innerHTML=cadena;
    }else{
      cadena+="<option value=''>No hay secciones en la base de datos</option>";
      document.getElementById('select_grado').innerHTML=cadena;
      document.getElementById('select_grado_editar').innerHTML=cadena;

    }
  })
}

function Registrar_Comunicado(){

  //DATOS DEL ALUMNO
  let tipo = document.getElementById('select_tipo').value;
  let grado = document.getElementById('select_grado').value;
  let titulo = document.getElementById('txt_titulo').value;
  let descripcion = document.getElementById('txt_descripcion').value;
  let foto = document.getElementById('txt_foto').value;
  let usu = document.getElementById('txtprincipalid').value;

  if(tipo.length==0|| grado.length==0||titulo.length==0||descripcion.length==0||foto.length==0||usu.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios en el registro del comunicado","warning");
  }


    let extension = foto.split('.').pop();
    let nombrefoto="";
    let f = new Date();
    if(foto.length>0){
      nombrefoto="IMG"+f.getDate()+"-"+(f.getMonth()+1)+"-"+f.getFullYear()+"-"+f.getHours()+"-"+f.getMilliseconds()+"."+extension;
    }
    //CONDICIONANDO LOS CAMPOS VACIOS


    let formData = new FormData();
    let fotoobj = $("#txt_foto")[0].files[0];

    formData.append("tipo",tipo);
    formData.append("grado",grado);
    formData.append("titulo",titulo);
    formData.append("descripcion",descripcion);
    formData.append("nombrefoto",nombrefoto);
    formData.append("foto",fotoobj);
    formData.append("usu",usu);

    $.ajax({
      url:"../controller/comunicados/controlador_registro_comunicados.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
        if(resp==1){
          Swal.fire("Mensaje de Confirmación","Se registro correctamente al Comunicado con el Título: <b>"+titulo+"</b>","success").then((value)=>{
            $("#modal_registro").modal('hide');
            tbl_comunicados.ajax.reload();
            document.getElementById('txt_titulo').value="";
            document.getElementById('txt_descripcion').value="";
            document.getElementById('txt_foto').value="";


          });
            }else{
            Swal.fire("Mensaje de Advertencia","El comunicado con la fecha de hoy día que intentas ingresar ya se encuentra en la base de datos, revise por favor","warning");
            }
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo registrar el comunicado","warning");
        }
      }
    });
}
function Modificar_comunicado(){

  //DATOS DEL DOCENTE
  let id = document.getElementById('txt_id_comu').value;
  let tipo = document.getElementById('select_tipo_editar').value;
  let grado = document.getElementById('select_grado_editar').value;
  let titulo = document.getElementById('txt_titulo_editar').value;
  let descripcion = document.getElementById('txt_descripcion_editar').value;
  let esta = document.getElementById('txt_estado').value;
  let fotoactual = document.getElementById('txt_foto_actual').value;
  let foto = document.getElementById('txt_foto_editar').value;
  let usu = document.getElementById('txtprincipalid').value;

  
  if(id.length==0|| tipo.length==0||grado.length==0||titulo.length==0||descripcion.length==0||esta.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios en la edición del comunicado","warning");
  }


    let extension = foto.split('.').pop();
    let nombrefoto="";
    let f = new Date();
    if(foto.length>0){
      nombrefoto="IMG"+f.getDate()+"-"+(f.getMonth()+1)+"-"+f.getFullYear()+"-"+f.getHours()+"-"+f.getMilliseconds()+"."+extension;
    }
    //CONDICIONANDO LOS CAMPOS VACIOS


    let formData = new FormData();
    let fotoobj = $("#txt_foto_editar")[0].files[0];
   
;
    formData.append("id",id);
    formData.append("tipo",tipo);
    formData.append("grado",grado);
    formData.append("titulo",titulo);
    formData.append("descripcion",descripcion);
    formData.append("esta",esta);
    formData.append("fotoactual",fotoactual);
    formData.append("nombrefoto",nombrefoto);
    formData.append("foto",fotoobj);
    formData.append("usu",usu);

    $.ajax({
      url:"../controller/comunicados/controlador_modificar_comunicados.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp>0){
          if(resp==1){
            Swal.fire("Mensaje de Confirmación","Se actualizo correctamente el Comunicado con el Título de: <b>"+titulo+"</b>","success").then((value)=>{
              $("#modal_editar").modal('hide');
              tbl_comunicados.ajax.reload();
              document.getElementById('txt_foto_editar').value="";
            });
              }else{
              Swal.fire("Mensaje de Advertencia","El Comunicado que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
              }
          }else{
            Swal.fire("Mensaje de Advertencia","No se pudo actualizar el comunicado","warning");
          }
      }
    });
}
/////
function Eliminar_Comunicado(id){
  $.ajax({
    "url":"../controller/comunicados/controlador_eliminar_comunicado.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Se elimino el comunicado con exito","success").then((value)=>{
          tbl_comunicados.ajax.reload();

        });
    }else{
      return Swal.fire("Mensaje de Advetencia","No se puede eliminar este comunicado por que esta siendo utilizado, verifique por favor","warning");

    }
  })
}

//ENVIANDO AL BOTON DELETE
$('#tabla_comunicados').on('click','.delete',function(){
  var data = tbl_comunicados.row($(this).parents('tr')).data();

  if(tbl_comunicados.row(this).child.isShown()){
      var data = tbl_comunicados.row(this).data();
  }
  Swal.fire({
    title: 'Desea eliminar el comunicado con título: '+data.titulo+' con fecha '+data.fecha_formateada+'?',
    text: "Una vez aceptado el comunicado sera eliminado sin opción a recuperar!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      Eliminar_Comunicado(data.id_comunicado);
    }
  })
});



var tbl_comunicados_dash;
var comunicadosActivos = [];
var currentModalIndex = 0;

function listar_comunicado_dash() {
  tbl_comunicados_dash = $("#tabla_comunicados_listar").DataTable({
    "ordering": false,
    "processing": true,
    responsive: true,
    "searching": false,
    "bPaginate": false,
    "ajax": {
      "url": "../controller/comunicados/controlador_listar_comunicados2.php",
      type: 'POST'
    },
    "columns": [
      { "data": "id_comunicado" },
      {
        "data": "tipo",
        render: function (data) {
          return data === 'GENERAL'
            ? '<span class="badge bg-success">GENERAL</span>'
            : '<span class="badge bg-warning">POR GRADO</span>';
        }
      },
      { "data": "Grado" },
      { "data": "titulo" },
      { "data": "descripcion" },
      {
        "defaultContent": "<button class='mostrar btn btn-warning btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Ver</button>"
      },
      {
        "data": "estado",
        render: function (data) {
          return data === 'ACTIVO'
            ? '<span class="badge bg-success">ACTIVO</span>'
            : '<span class="badge bg-danger">INACTIVO</span>';
        }
      }
    ],
    "language": idioma_espanol,
    select: false,
    "drawCallback": function () {
      if (tbl_comunicados_dash) {
        var gradoSeleccionado = $('#txt_grado').val();  // Obtén el valor del input
        comunicadosActivos = [];
        tbl_comunicados_dash.rows().every(function () {
          var data = this.data();
          // Filtrar por estado ACTIVO y Grado que coincida con el seleccionado o sea "TODOS"
          if (data.estado === 'ACTIVO' && (data.Grado === gradoSeleccionado || data.Grado === 'TODOS')) {
            comunicadosActivos.push(data);
          }
        });
        // Mostrar el primer modal activo si hay alguno
        if (comunicadosActivos.length > 0) {
          currentModalIndex = 0;
          mostrarDatosModal(comunicadosActivos[currentModalIndex]);
        }
      }
    }
  });

  $('#tabla_comunicados_listar').on('click', '.mostrar', function () {
    var data = tbl_comunicados_dash.row($(this).parents('tr')).data();
    mostrarDatosModal(data);
  });
}


function mostrarDatosModal(data) {
  $("#modal_ver").modal({ backdrop: 'static', keyboard: false });
  $("#modal_ver").modal('show');

  document.getElementById('lb_titulo_datos2').innerHTML = "<u><b>" + data.titulo + "</b></u>";
  document.getElementById('txt_descripcion_ver').value = data.descripcion;

  var imgElement = document.getElementById('preview4');
  console.log('Data:', data);  // Verifica que los datos sean correctos
  console.log('Image URL:', data.imagen);  // Verifica la URL de la imagen

  if (imgElement) {
    if (data.imagen && data.imagen.trim() !== '') {
      imgElement.src = "../" + data.imagen;
    } else {
      imgElement.src = '../controller/comunicados/fotos/VACIO.png';
    }

    // Manejar errores de carga de la imagen
    imgElement.onerror = function () {
      console.error("Error al cargar la imagen.");
      imgElement.src = '../controller/comunicados/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
    };
  } else {
    console.error('Elemento img con id preview4 no encontrado');
  }
}

function mostrarSiguienteModal() {
  if (comunicadosActivos.length > 0) {
    currentModalIndex = (currentModalIndex + 1) % comunicadosActivos.length;
    mostrarDatosModal(comunicadosActivos[currentModalIndex]);
  }
}

function mostrarAnteriorModal() {
  if (comunicadosActivos.length > 0) {
    currentModalIndex = (currentModalIndex - 1 + comunicadosActivos.length) % comunicadosActivos.length;
    mostrarDatosModal(comunicadosActivos[currentModalIndex]);
  }
}

