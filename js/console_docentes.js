//LISTADO DE ROLES
var tb_docentes;
function listar_alumnos(){
    tb_docentes = $("#tabla_docentes").DataTable({
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
          "url":"../controller/docentes/controlador_listar_docentes.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> <b>Excel</b>',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE DOCENTES"
      },
      title: function() {
        return  "LISTA DE DOCENTES" },
        exportOptions: {
            columns: [2, 3, 4, 5, 6,7,8,9,10,11,12,13,14,15] // Excluye la columna de fotos (1) y acciones (7)
        },
          // exportOptions: {
          //     columns: ':not(:last-child)' // Excluye la última columna (Acción)
          // }
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> <b>PDF</b>',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE DOCENTES"
      },
      title: function() {
        return  "LISTA DE DOCENTES"
      },
      
      orientation: 'landscape',
      exportOptions: {
        columns: [2, 3, 4, 5, 6,7,8,9,10,11,12] // Excluye la columna de fotos (1) y acciones (7)
      },
      customize: function(doc) {
        doc.content[1].table.body.forEach(function(row) {
            row.forEach(function(cell) {
                cell.alignment = 'center'; // Centra el contenido
            });
        });
      }
    
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> <b>Imprimir</b>',
      titleAttr: 'Imprimir',
      
      title: function() {
        return  "LISTA DE DOCENTES"
    
      },
      exportOptions: {
        columns: [2, 3, 4, 5, 6,7,8,9,10,11,12,13,14,15] // Excluye la columna de fotos (1) y acciones (7)
      }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"docente_fotoperfil",
          render: function(data,type,row){
                  if(data=='controller/docentes/fotos/'){
                    return '<img src="../controller/docentes/fotos/VACIO.png" class="img img-responsive" style="width:40px">';
                  }else{
                    return '<img src="../'+data+'" class="img img-responsive" style="width:40px">';
                  }
              }   
        }, 
        
        {"data":"docente_dni"},
        {"data":"Docente"},
        {"data":"Especialidad"},
        {"data":"docente_sexo",
            render: function(data,type,row){
                    if(data=='FEMENINO'){
                    return '<span class="badge bg-warning">FEMENINO</span>';
                    }else{
                    return '<span class="badge bg-primary">MASCULINO</span>';
                    }
            }   
        },
        {"data":"fecha_formateada"}, 
        {"data":"docente_movil"},        
        {"data":"docente_nro_alterno","visible": false},      
        {"data":"docente_direccion"},    
        {"data":"docente_estatus",
            render: function(data,type,row){
                    if(data=='ACTIVO'){
                    return '<span class="badge bg-success">ACTIVO</span>';
                    }else{
                    return '<span class="badge bg-danger">INACTIVO</span>';
                    }
            }   
        },
        {"data":"fecha_formateada2","visible": false},
        {"data":"usu_usuario","visible": false},        
        {"data":"usu_estatus","visible": false},        
        {"data":"tipo_rol","visible": false},        
      
        
        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Mostrar</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos del estudiante'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp;<button class='delete btn btn-danger  btn-sm' title='Eliminar registro'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tb_docentes.on('draw.td',function(){
  var PageInfo = $("#tabla_docentes").DataTable().page.info();
  tb_docentes.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

//TRAENDO DATOS DE LA NIVEL ACADEMICO

function Cargar_Select_Especialidad(){
  $.ajax({
    "url":"../controller/docentes/controlador_cargar_select_especialidad.php",
    type:'POST',
  }).done(function(resp){
    let data = JSON.parse(resp);
    let cadena = "<option value=''>Seleccione</option>"; // Agregar opción 'Seleccione'
    if(data.length > 0){
      for (let i = 0; i < data.length; i++) {
        cadena += "<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
      }
    } else {
      cadena += "<option value=''>No hay secciones en la base de datos</option>";
    }
    document.getElementById('txt_especialidad').innerHTML = cadena;
    document.getElementById('txt_especialidad_mas').innerHTML = cadena;
    document.getElementById('txt_especialidad_editar').innerHTML = cadena;
  })
}

  


$('#tabla_docentes').on('click','.mostrar',function(){
  var data = tb_docentes.row($(this).parents('tr')).data();

  if(tb_docentes.row(this).child.isShown()){
      var data = tb_docentes.row(this).data();
  }
  $("#modal_mas").modal('show');

  document.getElementById('txt_dni_mas').value=data.docente_dni;
  document.getElementById('txt_nomb_mas').value=data.docente_nombre;
  document.getElementById('txt_apelli_mas').value=data.docente_apelli;
  $("#txt_especialidad_mas").select2().val(data.especialidad_id).trigger('change.select2');

  document.getElementById('txt_sexo_mas').value=data.docente_sexo;
  document.getElementById('txt_fecha_na_mas').value=data.docente_fechanacimiento;
  document.getElementById('txt_tele_mas').value=data.docente_movil;
  document.getElementById('txt_tele_alte_mas').value=data.docente_nro_alterno;
  document.getElementById('txt_direc_mas').value=data.docente_direccion;
  
  var imgElement = document.getElementById('preview2');
  console.log('Data:', data);  // Verifica que los datos sean correctos
  console.log('Image URL:', data.docente_fotoperfil);  // Verifica la URL de la imagen
  
  if (imgElement) {
    if (data.docente_fotoperfil && data.docente_fotoperfil.trim() !== '') {
      imgElement.src = "../" + data.docente_fotoperfil;
    } else {
      imgElement.src = '../controller/docentes/fotos/VACIO.png';
    }
  
    // Manejar errores de carga de la imagen
    imgElement.onerror = function() {
      console.error("Error al cargar la imagen.");
      imgElement.src = '../controller/docentes/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
    };
  } else {
    console.error('Elemento img con id preview2 no encontrado');
  }
  document.getElementById('txt_usu_mas').value=data.usu_usuario;
  document.getElementById('txt_correo_mas').value=data.usu_email;

})


$('#tabla_docentes').on('click','.editar',function(){
  var data = tb_docentes.row($(this).parents('tr')).data();

  if(tb_docentes.row(this).child.isShown()){
      var data = tb_docentes.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('id_docente').value=data.Id_docente;
  document.getElementById('txt_dni_editar').value=data.docente_dni;
  document.getElementById('txt_nomb_editar').value=data.docente_nombre;
  document.getElementById('txt_apelli_editar').value=data.docente_apelli;
  $("#txt_especialidad_editar").select2().val(data.especialidad_id).trigger('change.select2');

  document.getElementById('txt_sexo_editar').value=data.docente_sexo;
  document.getElementById('txt_fecha_na_editar').value=data.docente_fechanacimiento;
  document.getElementById('txt_tele_editar').value=data.docente_movil;
  document.getElementById('txt_tele_alte_editar').value=data.docente_nro_alterno;
  document.getElementById('txt_direc_editar').value=data.docente_direccion;
  document.getElementById('txt_direc_editar').value=data.docente_direccion;
  document.getElementById('txt_estado').value=data.docente_estatus;
  document.getElementById('txt_foto_actual').value=data.docente_fotoperfil;

  var imgElement = document.getElementById('preview3');
console.log('Data:', data);  // Verifica que los datos sean correctos
console.log('Image URL:', data.docente_fotoperfil);  // Verifica la URL de la imagen

if (imgElement) {
  if (data.docente_fotoperfil && data.docente_fotoperfil.trim() !== '') {
    imgElement.src = "../" + data.docente_fotoperfil;
  } else {
    imgElement.src = '../controller/docentes/fotos/VACIO.png';
  }

  // Manejar errores de carga de la imagen
  imgElement.onerror = function() {
    console.error("Error al cargar la imagen.");
    imgElement.src = '../controller/docentes/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
  };
} else {
  console.error('Elemento img con id preview3 no encontrado');
}
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO AULMONOS
function Registrar_Docente(){

  //DATOS DEL DOCENTE
  let dni = document.getElementById('txt_dni').value;
  let nombre = document.getElementById('txt_nomb').value;
  let apelli = document.getElementById('txt_apelli').value;
  let espe = document.getElementById('txt_especialidad').value;
  let sexo = document.getElementById('txt_sexo').value;
  let fechanaci = document.getElementById('txt_fecha_na').value;
  let telf = document.getElementById('txt_tele').value;
  let telfal = document.getElementById('txt_tele_alte').value;
  let direc = document.getElementById('txt_direc').value;
  let foto = document.getElementById('txt_foto').value;


  //DATOS DEL USUARIO
  let usu = document.getElementById('txt_usu').value;
  let contra = document.getElementById('txt_contra').value;
  let email = document.getElementById('txt_correo').value;


  
  if(dni.length==0|| apelli.length==0||espe.length==0||sexo.length==0||fechanaci.length==0||direc.length==0||nombre.length==0||telf.length==0  ){
    return Swal.fire("Mensaje de Advertencia","Tiene campos en el registro del docente","warning");
  }
  if(usu.length==0||contra.length==0||email.length==0){
    return Swal.fire("Mensaje de Advertencia","Los datos del usuario son oblgatorios","warning");
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

    formData.append("dni",dni);
    formData.append("nombre",nombre);
    formData.append("apelli",apelli);
    formData.append("espe",espe);
    formData.append("sexo",sexo);
    formData.append("fechanaci",fechanaci);
    formData.append("telf",telf);
    formData.append("telfal",telfal);
    formData.append("direc",direc);
    formData.append("nombrefoto",nombrefoto);
    formData.append("foto",fotoobj);

    formData.append("usu",usu);
    formData.append("contra",contra);
    formData.append("email",email);
    $.ajax({
      url:"../controller/docentes/controlador_registrar_docente.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
        if(resp==1){
          Swal.fire("Mensaje de Confirmación","Se registro correctamente al Docente con el DNI N° <b>"+dni+"</b>","success").then((value)=>{
            // Limpiar todos los campos
            document.getElementById('txt_dni').value = "";
            document.getElementById('txt_nomb').value = "";
            document.getElementById('txt_apelli').value = "";
            document.getElementById('txt_fecha_na').value = "";
            document.getElementById('txt_tele').value = "";
            document.getElementById('txt_tele_alte').value = "";
            document.getElementById('txt_direc').value = "";
            document.getElementById('txt_foto').value = '';  // Limpiar el valor del input de archivo

            // Limpiar la vista previa de la imagen
            document.getElementById('preview').src = '#';
            document.getElementById('preview').alt = 'Vista previa';

            document.getElementById('txt_usu').value = "";
            document.getElementById('txt_contra').value = "";
            document.getElementById('txt_correo').value = "";

            // Cerrar el modal
            $("#modal_registro").modal('hide');
            tb_docentes.ajax.reload();

          });
            }else{
            Swal.fire("Mensaje de Advertencia","El DNI del docente que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
            }
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo registrar al docente","warning");
        }
      }
    });
}

//EDITANDO DATOS ALUMNOS
function Modificar_docente(){

  //DATOS DEL DOCENTE
  let id = document.getElementById('id_docente').value;
  let dni = document.getElementById('txt_dni_editar').value;
  let nombre = document.getElementById('txt_nomb_editar').value;
  let apelli = document.getElementById('txt_apelli_editar').value;
  let espe = document.getElementById('txt_especialidad_editar').value;
  let sexo = document.getElementById('txt_sexo_editar').value;
  let fechanaci = document.getElementById('txt_fecha_na_editar').value;
  let telf = document.getElementById('txt_tele_editar').value;
  let telfal = document.getElementById('txt_tele_alte_editar').value;
  let direc = document.getElementById('txt_direc_editar').value;
  let esta = document.getElementById('txt_estado').value;
  let fotoactual = document.getElementById('txt_foto_actual').value;
  let foto = document.getElementById('txt_foto_editar').value;

  
  if(dni.length==0|| apelli.length==0||espe.length==0||sexo.length==0||fechanaci.length==0||direc.length==0||nombre.length==0||telf.length==0  ){
    return Swal.fire("Mensaje de Advertencia","Tiene campos en el registro del docente","warning");
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
   
    formData.append("id",id);
    formData.append("dni",dni);
    formData.append("nombre",nombre);
    formData.append("apelli",apelli);
    formData.append("espe",espe);
    formData.append("sexo",sexo);
    formData.append("fechanaci",fechanaci);
    formData.append("telf",telf);
    formData.append("telfal",telfal);esta
    formData.append("direc",direc);
    formData.append("esta",esta);
    formData.append("fotoactual",fotoactual);
    formData.append("nombrefoto",nombrefoto);
    formData.append("foto",fotoobj);

    $.ajax({
      url:"../controller/docentes/controlador_modificar_docente.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp>0){
          if(resp==1){
            Swal.fire("Mensaje de Confirmación","Se actualizo correctamente al Docente con el DNI N° <b>"+dni+"</b>","success").then((value)=>{
              $("#modal_editar").modal('hide');
              tb_docentes.ajax.reload();
              document.getElementById('txt_foto_editar').value="";
            });
              }else{
              Swal.fire("Mensaje de Advertencia","El DNI del docente que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
              }
          }else{
            Swal.fire("Mensaje de Advertencia","No se pudo registrar al docente","warning");
          }
      }
    });
}
//ELIMINANDO ROL
function Eliminar_Docente(id){
    $.ajax({
      "url":"../controller/docentes/controlador_eliminar_docente.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el docente con exito","success").then((value)=>{
            tb_docentes.ajax.reload();

          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este docente por que esta siendo utilizado en la matricula, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_docentes').on('click','.delete',function(){
    var data = tb_docentes.row($(this).parents('tr')).data();
  
    if(tb_docentes.row(this).child.isShown()){
        var data = tb_docentes.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar al docente: '+data.Docente+'?',
      text: "Una vez aceptado el docente sera eliminado y a su vez se eliminara el acceso al sistema!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Docente(data.usu_id);
      }
    })
  })