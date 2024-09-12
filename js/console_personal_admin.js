//LISTADO DE ROLES
var tb_personal_administrativo;
function listar_personal_administrativo(){
    tb_personal_administrativo = $("#tabla_personal_administrativo").DataTable({
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
          "url":"../controller/personal_administrativo/controlador_listar_personaladmin.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> <b>Excel</b>',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE PERSONAL ADMINISTRATIVO"
      },
      title: function() {
        return  "LISTA DE PERSONAL ADMINISTRATIVO" },
        exportOptions: {
            columns: [2, 3, 4, 5, 6,7,8,9,10,11,12,13,14] // Excluye la columna de fotos (1) y acciones (7)
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
        return  "LISTA DE PERSONAL ADMINISTRATIVO"
      },
      title: function() {
        return  "LISTA DE PERSONAL ADMINISTRATIVO"
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
        return  "LISTA DE PERSONAL ADMINISTRATIVO"
    
      },
      exportOptions: {
        columns: [2, 3, 4, 5, 6,7,8,9,10,11,12,13,14] // Excluye la columna de fotos (1) y acciones (7)
      }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"personal_adm_fotoperfil",
          render: function(data,type,row){
                  if(data=='controller/personal_administrativo/fotos/'){
                    return '<img src="../controller/personal_administrativo/fotos/VACIO.png" class="img img-responsive" style="width:40px">';
                  }else{
                    return '<img src="../'+data+'" class="img img-responsive" style="width:40px">';
                  }
              }   
        }, 
        
        {"data":"personal_adm_dni"},
        {"data":"Personal"},
        {"data":"personal_adm_tipo"},
        {"data":"personal_adm_sexo",
            render: function(data,type,row){
                    if(data=='FEMENINO'){
                    return '<span class="badge bg-warning">FEMENINO</span>';
                    }else{
                    return '<span class="badge bg-primary">MASCULINO</span>';
                    }
            }   
        },
        {"data":"fecha_formateada"}, 
        {"data":"personal_adm_movil"},        
        {"data":"personal_adm_nro_alterno","visible": false},      
        {"data":"personal_adm_direccion"},    
        {"data":"personal_adm_estatus",
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
tb_personal_administrativo.on('draw.td',function(){
  var PageInfo = $("#tabla_personal_administrativo").DataTable().page.info();
  tb_personal_administrativo.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

//TRAENDO DATOS DE LOS ROLES

function Cargar_Select_Roles() {
    $.ajax({
      url: "../controller/personal_administrativo/controlador_cargar_select_roles.php",
      type: 'POST',
    }).done(function(resp){
      let data = JSON.parse(resp);
      
      if (data.length > 0) {
        let optionsHtml1 = "";
        let optionsHtml2 = "";
        
        // Construir opciones para ambos selects
        for (let i = 0; i < data.length; i++) {
          optionsHtml1 += "<option value='" + data[i][1] + "'>" + data[i][1] + "</option>";
          optionsHtml2 += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
        }
        
        // Asignar opciones iniciales a los selects de creación
        document.getElementById('txt_tipo').innerHTML = optionsHtml1;
        document.getElementById('id_rol').innerHTML = optionsHtml2;
        
        // Asignar opciones iniciales a los selects de edición
        document.getElementById('txt_tipo_editar').innerHTML = optionsHtml1;
  
        // Función para actualizar el segundo select basado en el primero
        function updateSelects(selectedValue, idRolId) {
          let filteredOptionsHtml = "";
          for (let i = 0; i < data.length; i++) {
            if (data[i][1] === selectedValue) {
              filteredOptionsHtml += "<option value='" + data[i][0] + "'>" + data[i][1] + "</option>";
            }
          }
          document.getElementById(idRolId).innerHTML = filteredOptionsHtml;
        }
  
        // Agregar evento change al primer select de creación
        document.getElementById('txt_tipo').addEventListener('change', function() {
          let selectedValue = this.value;
          updateSelects(selectedValue, 'id_rol');
        });

      } else {
        let noDataHtml = "<option value=''>No hay secciones en la base de datos</option>";
        
        // Asignar mensaje de error a ambos sets de selects si no hay datos
        document.getElementById('txt_tipo').innerHTML = noDataHtml;
        document.getElementById('id_rol').innerHTML = noDataHtml;
  
        document.getElementById('txt_tipo_editar').innerHTML = noDataHtml;
      }
    }).fail(function(jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
    });
  }
  
  
  //MOSTRAR MAS
$('#tabla_personal_administrativo').on('click','.mostrar',function(){
  var data = tb_personal_administrativo.row($(this).parents('tr')).data();

  if(tb_personal_administrativo.row(this).child.isShown()){
      var data = tb_personal_administrativo.row(this).data();
  }
  $("#modal_mas").modal('show');

  document.getElementById('txt_dni_mas').value=data.personal_adm_dni;
  document.getElementById('txt_nomb_mas').value=data.personal_adm_nombre;
  document.getElementById('txt_apelli_mas').value=data.personal_adm_apellido;
  document.getElementById('txt_tipo_mas').value=data.personal_adm_tipo;

  document.getElementById('txt_sexo_mas').value=data.personal_adm_sexo;
  document.getElementById('txt_fecha_na_mas').value=data.personal_adm_fechanacimiento;
  document.getElementById('txt_tele_mas').value=data.personal_adm_movil;
  document.getElementById('txt_tele_alte_mas').value=data.personal_adm_nro_alterno;
  document.getElementById('txt_direc_mas').value=data.personal_adm_direccion;
  
  var imgElement = document.getElementById('preview2');
  console.log('Data:', data);  // Verifica que los datos sean correctos
  console.log('Image URL:', data.personal_adm_fotoperfil);  // Verifica la URL de la imagen
  
  if (imgElement) {
    if (data.personal_adm_fotoperfil && data.personal_adm_fotoperfil.trim() !== '') {
      imgElement.src = "../" + data.personal_adm_fotoperfil;
    } else {
      imgElement.src = '../controller/docepersonal_administrativontes/fotos/VACIO.png';
    }
  
    // Manejar errores de carga de la imagen
    imgElement.onerror = function() {
      console.error("Error al cargar la imagen.");
      imgElement.src = '../controller/personal_administrativo/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
    };
  } else {
    console.error('Elemento img con id preview2 no encontrado');
  }
  document.getElementById('txt_usu_mas').value=data.usu_usuario;
  document.getElementById('txt_correo_mas').value=data.usu_email;

})


$('#tabla_personal_administrativo').on('click','.editar',function(){
  var data = tb_personal_administrativo.row($(this).parents('tr')).data();

  if(tb_personal_administrativo.row(this).child.isShown()){
      var data = tb_personal_administrativo.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('id_personal').value=data.personal_adm_id;
  document.getElementById('txt_dni_editar').value=data.personal_adm_dni;
  document.getElementById('txt_nomb_editar').value=data.personal_adm_nombre;
  document.getElementById('txt_apelli_editar').value=data.personal_adm_apellido;
  $("#txt_tipo_editar").select2().val(data.personal_adm_tipo).trigger('change.select2');
  $("#id_rol_editar").select2().val(data.rol_id).trigger('change.select2');

  document.getElementById('txt_sexo_editar').value=data.personal_adm_sexo;
  document.getElementById('txt_fecha_na_editar').value=data.personal_adm_fechanacimiento;
  document.getElementById('txt_tele_editar').value=data.personal_adm_movil;
  document.getElementById('txt_tele_alte_editar').value=data.personal_adm_nro_alterno;
  document.getElementById('txt_direc_editar').value=data.personal_adm_direccion;
  document.getElementById('txt_estado').value=data.personal_adm_estatus;
  document.getElementById('txt_foto_actual').value=data.personal_adm_fotoperfil;

  var imgElement = document.getElementById('preview3');
console.log('Data:', data);  // Verifica que los datos sean correctos
console.log('Image URL:', data.personal_adm_fotoperfil);  // Verifica la URL de la imagen

if (imgElement) {
  if (data.personal_adm_fotoperfil && data.personal_adm_fotoperfil.trim() !== '') {
    imgElement.src = "../" + data.personal_adm_fotoperfil;
  } else {
    imgElement.src = '../controller/personal_administrativo/fotos/VACIO.png';
  }

  // Manejar errores de carga de la imagen
  imgElement.onerror = function() {
    console.error("Error al cargar la imagen.");
    imgElement.src = '../controller/personal_administrativo/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
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
function Registrar_Personal(){

  //DATOS DEL ALUMNO
  let dni = document.getElementById('txt_dni').value;
  let nombre = document.getElementById('txt_nomb').value;
  let apelli = document.getElementById('txt_apelli').value;
  let tipo = document.getElementById('txt_tipo').value;
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
  let rol = document.getElementById('id_rol').value;


  
  if(dni.length==0|| apelli.length==0||tipo.length==0||sexo.length==0||fechanaci.length==0||direc.length==0||nombre.length==0||telf.length==0  ){
    return Swal.fire("Mensaje de Advertencia","Tiene campos en el registro del docente","warning");
  }
  if(usu.length==0||contra.length==0||email.length==0||rol.length==0){
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
    formData.append("tipo",tipo);
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
    formData.append("rol",rol);

    $.ajax({
      url:"../controller/personal_administrativo/controlador_registrar_personal_administrativo.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
        if(resp==1){
          Swal.fire("Mensaje de Confirmación","Se registro correctamente al Personal Administrativo con el DNI N° <b>"+dni+"</b>","success").then((value)=>{
            $("#modal_registro").modal('hide');
            tb_personal_administrativo.ajax.reload();
            document.getElementById('txt_dni').value="";
            document.getElementById('txt_nomb').value="";
            document.getElementById('txt_apelli').value="";
            document.getElementById('txt_fecha_na').value="";
            document.getElementById('txt_tele').value="";
            document.getElementById('txt_tele_alte').value="";
            document.getElementById('txt_direc').value="";
            document.getElementById('txt_foto').value="";

            document.getElementById('txt_usu').value="";
            document.getElementById('txt_contra').value="";
            document.getElementById('txt_correo').value="";

          });
            }else{
            Swal.fire("Mensaje de Advertencia","El DNI del personal administrativo que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
            }
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo registrar al personal administrativo","warning");
        }
      }
    });
}

//EDITANDO DATOS ALUMNOS
function Modificar_personal(){

  //DATOS DEL DOCENTE
  let id = document.getElementById('id_personal').value;
  let dni = document.getElementById('txt_dni_editar').value;
  let nombre = document.getElementById('txt_nomb_editar').value;
  let apelli = document.getElementById('txt_apelli_editar').value;
  let tipo = document.getElementById('txt_tipo_editar').value;
  let sexo = document.getElementById('txt_sexo_editar').value;
  let fechanaci = document.getElementById('txt_fecha_na_editar').value;
  let telf = document.getElementById('txt_tele_editar').value;
  let telfal = document.getElementById('txt_tele_alte_editar').value;
  let direc = document.getElementById('txt_direc_editar').value;
  let esta = document.getElementById('txt_estado').value;
  let fotoactual = document.getElementById('txt_foto_actual').value;
  let foto = document.getElementById('txt_foto_editar').value;

  
  if(dni.length==0|| apelli.length==0||tipo.length==0||sexo.length==0||fechanaci.length==0||direc.length==0||nombre.length==0||telf.length==0  ){
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
    formData.append("tipo",tipo);
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
      url:"../controller/personal_administrativo/controlador_modificar_personal.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp>0){
          if(resp==1){
            Swal.fire("Mensaje de Confirmación","Se actualizo correctamente al Personal Administrativo con el DNI N° <b>"+dni+"</b>","success").then((value)=>{
              $("#modal_editar").modal('hide');
              tb_personal_administrativo.ajax.reload();
              document.getElementById('txt_foto_editar').value="";
            });
              }else{
              Swal.fire("Mensaje de Advertencia","El DNI del Personal administrativo que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
              }
          }else{
            Swal.fire("Mensaje de Advertencia","No se pudo registrar al personal administrativo","warning");
          }
      }
    });
}
//ELIMINANDO ROL
function Eliminar_Personal(id){
    $.ajax({
      "url":"../controller/personal_administrativo/controlador_eliminar_personal.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el personal administrativo con exito","success").then((value)=>{
            tb_personal_administrativo.ajax.reload();

          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este personal administrativo por que esta siendo utilizado en la matricula, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_personal_administrativo').on('click','.delete',function(){
    var data = tb_personal_administrativo.row($(this).parents('tr')).data();
  
    if(tb_personal_administrativo.row(this).child.isShown()){
        var data = tb_personal_administrativo.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar al personal administrativo: '+data.Personal+'?',
      text: "Una vez aceptado el personal administrativo sera eliminado y a su vez se eliminara el acceso al sistema!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Personal(data.usu_id);
      }
    })
  })