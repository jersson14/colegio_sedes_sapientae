//LISTADO DE ROLES
var tb_alumnos;
function listar_alumnos(){
    tb_alumnos = $("#tabla_alumnos").DataTable({
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
          "url":"../controller/alumnos/controlador_listar_alumnos.php",
          type:'POST'
      },
      dom: 'Bfrtip', 
     
      buttons:[ 
        
    {
      extend:    'excelHtml5',
      text:      '<i class="fas fa-file-excel"></i> <b>Excel</b>',
      titleAttr: 'Exportar a Excel',
      
      filename: function() {
        return  "LISTA DE ALUMNOS"
      },
      title: function() {
        return  "LISTA DE ALUMNOS" },
        exportOptions: {
          columns: [2, 3, 4, 5, 6,7,8,9,10,11,12,13,14,15,16] // Excluye la columna de fotos (1) y acciones (7)
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
        return  "LISTA DE ALUMNOS"
      },
      title: function() {
        return  "LISTA DE ALUMNOS"
      },
      
      orientation: 'landscape',
      exportOptions: {
        columns: [2, 3, 4, 5, 6,7,8,9,10] // Excluye la columna de fotos (1) y acciones (7)
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
        return  "LISTA DE ALUMNOS"
    
      },
      exportOptions: {
        columns: [2, 3, 4, 5, 6,7,8,9,10,11,12,13,14,15,16] // Excluye la columna de fotos (1) y acciones (7)
      }
    }],
      "columns":[
        {"defaultContent":""},
        {"data":"alum_fotoperfil",
          render: function(data,type,row){
                  if(data=='controller/alumnos/fotos/'){
                    return '<img src="../controller/alumnos/fotos/VACIO.png" class="img img-responsive" style="width:40px">';
                  }else{
                    return '<img src="../'+data+'" class="img img-responsive" style="width:40px">';
                  }
              }   
        }, 
        
        {"data":"alum_dni"},
        {"data":"Estudiante"},
        {"data":"alum_sexo",
            render: function(data,type,row){
                    if(data=='FEMENINO'){
                    return '<span class="badge bg-warning">FEMENINO</span>';
                    }else{
                    return '<span class="badge bg-primary">MASCULINO</span>';
                    }
            }   
        },
        {"data":"fecha_formateada2","visible": false}, 
        {"data":"Edad","visible": false},        
        {"data":"alum_movil","visible": false},      

        {"data":"alum_direccion"},        
        {"data":"alum_estatus",
            render: function(data,type,row){
                    if(data=='SI'){
                    return '<span class="badge bg-success">SI</span>';
                    }else{
                    return '<span class="badge bg-danger">NO</span>';
                    }
            }   
        },
        {"data":"tipo_alum",
          render: function(data,type,row){
                  if(data=='NUEVO'){
                  return '<span class="badge bg-success">NUEVO</span>';
                  }else{
                  return '<span class="badge bg-danger">ANTIGUO</span>';
                  }
          }   
      },
        {"data":"fecha_formateada","visible": false},

        {"data":"Datos_papa","visible": false},        
        {"data":"Dni_papa","visible": false},        
        {"data":"Celular_papa","visible": false},        
        {"data":"Datos_mama","visible": false},        
        {"data":"Dni_mama","visible": false},        
        {"data":"Celular_mama","visible": false},        
        
        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Mostrar</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos del estudiante'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp;<button class='delete btn btn-danger  btn-sm' title='Eliminar registro'><i class='fa fa-trash'></i> Eliminar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tb_alumnos.on('draw.td',function(){
  var PageInfo = $("#tabla_alumnos").DataTable().page.info();
  tb_alumnos.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}

//TRAENDO DATOS DE LA NIVEL ACADEMICO




$('#tabla_alumnos').on('click','.mostrar',function(){
  var data = tb_alumnos.row($(this).parents('tr')).data();

  if(tb_alumnos.row(this).child.isShown()){
      var data = tb_alumnos.row(this).data();
  }
  $("#modal_mas").modal('show');

  document.getElementById('txt_dni_mas').value=data.alum_dni;
  document.getElementById('txt_nomb_mas').value=data.alum_nombre;
  document.getElementById('txt_ape_pa_mas').value=data.alum_apepat;
  document.getElementById('txt_apema_mas').value=data.alum_apemat;
  document.getElementById('txt_sexo_mas').value=data.alum_sexo;
  document.getElementById('txt_fecha_na_mas').value=data.alum_fechanacimiento;
  document.getElementById('txt_tele_mas').value=data.alum_movil;
  document.getElementById('txt_direc_mas').value=data.alum_direccion;
  var imgElement = document.getElementById('preview2');
  console.log('Data:', data);  // Verifica que los datos sean correctos
  console.log('Image URL:', data.alum_fotoperfil);  // Verifica la URL de la imagen

  if (imgElement) {
    if (data.alum_fotoperfil && data.alum_fotoperfil.trim() !== '') {
      imgElement.src = "../" + data.alum_fotoperfil;
    } else {
      imgElement.src = '../controller/alumnos/fotos/VACIO.png';
    }
  
    // Manejar errores de carga de la imagen
    imgElement.onerror = function() {
      console.error("Error al cargar la imagen.");
      imgElement.src = '../controller/alumnos/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
    };
  } else {
    console.error('Elemento img con id preview2 no encontrado');
  }
  document.getElementById('txt_dni_pa_mas').value=data.Dni_papa;
  document.getElementById('txt_nom_pa_mas').value=data.Datos_papa;
  document.getElementById('txt_cel_pa_mas').value=data.Celular_papa;
  document.getElementById('txt_dni_ma_mas').value=data.Dni_mama;
  document.getElementById('txt_nom_ma_mas').value=data.Datos_mama;
  document.getElementById('txt_cel_ma_mas').value=data.Celular_mama;
})


$('#tabla_alumnos').on('click','.editar',function(){
  var data = tb_alumnos.row($(this).parents('tr')).data();

  if(tb_alumnos.row(this).child.isShown()){
      var data = tb_alumnos.row(this).data();
  }
  $("#modal_editar").modal('show');

  document.getElementById('txt_id_alu').value=data.Id_alumno;

  document.getElementById('txt_dni_editar').value=data.alum_dni;
  document.getElementById('txt_nomb_editar').value=data.alum_nombre;
  document.getElementById('txt_ape_pa_editar').value=data.alum_apepat;
  document.getElementById('txt_apema_editar').value=data.alum_apemat;
  document.getElementById('txt_sexo_editar').value=data.alum_sexo;
  document.getElementById('txt_fecha_na_editar').value=data.alum_fechanacimiento;
  document.getElementById('txt_tele_editar').value=data.alum_movil;
  document.getElementById('txt_direc_editar').value=data.alum_direccion;
  document.getElementById('foto_actual').value=data.alum_fotoperfil;

  var imgElement = document.getElementById('preview3');
  console.log('Data:', data);  // Verifica que los datos sean correctos
  console.log('Image URL:', data.alum_fotoperfil);  // Verifica la URL de la imagen

  if (imgElement) {
    if (data.alum_fotoperfil && data.alum_fotoperfil.trim() !== '') {
      imgElement.src = "../" + data.alum_fotoperfil;
    } else {
      imgElement.src = '../controller/alumnos/fotos/VACIO.png';
    }
  
    // Manejar errores de carga de la imagen
    imgElement.onerror = function() {
      console.error("Error al cargar la imagen.");
      imgElement.src = '../controller/alumnos/fotos/VACIO.png';  // Cargar imagen predeterminada en caso de error
    };
  } else {
    console.error('Elemento img con id preview3 no encontrado');
  }
  document.getElementById('txt_id_papa').value=data.id_papas;
  document.getElementById('txt_dni_pa_editar').value=data.Dni_papa;
  document.getElementById('txt_nom_pa_editar').value=data.Datos_papa;
  document.getElementById('txt_cel_pa_editar').value=data.Celular_papa;
  document.getElementById('txt_dni_ma_editar').value=data.Dni_mama;
  document.getElementById('txt_nom_ma_editar').value=data.Datos_mama;
  document.getElementById('txt_cel_ma_editar').value=data.Celular_mama;
})

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO AULMONOS
function Registrar_alumno(){

  //DATOS DEL ALUMNO
  let dni = document.getElementById('txt_dni').value;
  let nombre = document.getElementById('txt_nomb').value;
  let apepa = document.getElementById('txt_ape_pa').value;
  let apema = document.getElementById('txt_apema').value;
  let sexo = document.getElementById('txt_sexo').value;
  let fechanaci = document.getElementById('txt_fecha_na').value;
  let telf = document.getElementById('txt_tele').value;
  let direc = document.getElementById('txt_direc').value;
  let foto = document.getElementById('txt_foto').value;


  //DATOS DEL PADRE DE FAMILIA
  let dnipa = document.getElementById('txt_dni_pa').value;
  let nompa = document.getElementById('txt_nom_pa').value;
  let celpa = document.getElementById('txt_cel_pa').value;
  let dnima = document.getElementById('txt_dni_ma').value;
  let nomma = document.getElementById('txt_nom_ma').value;
  let celma = document.getElementById('txt_cel_ma').value;

  
  if(dni.length==0|| apepa.length==0||apema.length==0||sexo.length==0||fechanaci.length==0||direc.length==0||nombre.length==0 ){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios en el registro del alumno","warning");
  }
  if(dnima.length==0||nomma.length==0||celma.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios en el registro de los papas","warning");
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
    formData.append("apepa",apepa);
    formData.append("apema",apema);
    formData.append("sexo",sexo);
    formData.append("fechanaci",fechanaci);
    formData.append("telf",telf);
    formData.append("direc",direc);
    formData.append("nombrefoto",nombrefoto);
    formData.append("foto",fotoobj);

    formData.append("dnipa",dnipa);
    formData.append("nompa",nompa);
    formData.append("celpa",celpa);
    formData.append("dnima",dnima);
    formData.append("nomma",nomma);
    formData.append("celma",celma);
    $.ajax({
      url:"../controller/alumnos/controlador_registrar_alumno.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
          if(resp==1){

          Swal.fire("Mensaje de Confirmación","Se registro correctamente al estudiante con el DNI N° <b>"+dni+"</b>","success").then((value)=>{
            $("#modal_registro").modal('hide');
            tb_alumnos.ajax.reload();
            document.getElementById('txt_dni').value="";
            document.getElementById('txt_nomb').value="";
            document.getElementById('txt_ape_pa').value="";
            document.getElementById('txt_apema').value="";
            document.getElementById('txt_fecha_na').value="";
            document.getElementById('txt_tele').value="";
            document.getElementById('txt_direc').value="";
            document.getElementById('txt_direc').value = "";
            document.getElementById('txt_foto').value = '';  // Limpiar el valor del input de archivo

            // Limpiar la vista previa de la imagen
            document.getElementById('preview').src = '#';
            document.getElementById('preview').alt = 'Vista previa';
            document.getElementById('txt_dni_pa').value="";
            document.getElementById('txt_nom_pa').value="";
            document.getElementById('txt_cel_pa').value="";
            document.getElementById('txt_dni_ma').value="";
            document.getElementById('txt_nom_ma').value="";
            document.getElementById('txt_cel_ma').value="";
          });
        }else{
        Swal.fire("Mensaje de Advertencia","El DNI del alumno que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
        }
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo registrar al alumno","warning");
        }
      }
    });
}

//EDITANDO DATOS ALUMNOS
function Modificar_alumno(){

  //DATOS DEL ALUMNO
  let id = document.getElementById('txt_id_alu').value;
  let dni = document.getElementById('txt_dni_editar').value;
  let nombre = document.getElementById('txt_nomb_editar').value;
  let apepa = document.getElementById('txt_ape_pa_editar').value;
  let apema = document.getElementById('txt_apema_editar').value;
  let sexo = document.getElementById('txt_sexo_editar').value;
  let fechanaci = document.getElementById('txt_fecha_na_editar').value;
  let telf = document.getElementById('txt_tele_editar').value;
  let direc = document.getElementById('txt_direc_editar').value;
  let fotoactual = document.getElementById('foto_actual').value;
  let foto = document.getElementById('txt_foto_editar').value;
  
  //DATOS DEL PADRE DE FAMILIA
  let idpa = document.getElementById('txt_id_papa').value;
  let dnipa = document.getElementById('txt_dni_pa_editar').value;
  let nompa = document.getElementById('txt_nom_pa_editar').value;
  let celpa = document.getElementById('txt_cel_pa_editar').value;
  let dnima = document.getElementById('txt_dni_ma_editar').value;
  let nomma = document.getElementById('txt_nom_ma_editar').value;
  let celma = document.getElementById('txt_cel_ma_editar').value;

  
  if(dni.length==0|| apepa.length==0||apema.length==0||sexo.length==0||fechanaci.length==0||direc.length==0||nombre.length==0 ){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios en el registro del alumno","warning");
  }
  if(dnima.length==0||nomma.length==0||celma.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios en el registro de los papas","warning");
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
    formData.append("apepa",apepa);
    formData.append("apema",apema);
    formData.append("sexo",sexo);
    formData.append("fechanaci",fechanaci);
    formData.append("telf",telf);
    formData.append("direc",direc);
    formData.append("nombrefoto",nombrefoto);
    formData.append("fotoactual",fotoactual);
    formData.append("foto",fotoobj);
    
    formData.append("idpa",idpa);
    formData.append("dnipa",dnipa);
    formData.append("nompa",nompa);
    formData.append("celpa",celpa);
    formData.append("dnima",dnima);
    formData.append("nomma",nomma);
    formData.append("celma",celma);
    $.ajax({
      url:"../controller/alumnos/controlador_modificar_alumno.php",
      type:'POST',
      data:formData,
      contentType:false,
      processData:false,
      success:function(resp){
        if(resp.length>0){
          if(resp==1){
          Swal.fire("Mensaje de Confirmación","Se actualizo correctamente al estudiante con el DNI N° <b>"+dni+"</b>","success").then((value)=>{
            $("#modal_editar").modal('hide');
            tb_alumnos.ajax.reload();
            document.getElementById('txt_foto_editar').value="";

          });
        }else{
        Swal.fire("Mensaje de Advertencia","El DNI del alumno que intentas registrar ya se encuentra en la base de datos, revise por favor","warning");
        }
        }else{
          Swal.fire("Mensaje de Advertencia","No se pudo actualizar la foto","warning");
        }
      }
    });
}
//ELIMINANDO ROL
function Eliminar_Alumno(id){
    $.ajax({
      "url":"../controller/alumnos/controlador_eliminar_alumnos.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el alumno con exito","success").then((value)=>{
            tb_alumnos.ajax.reload();

          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este estudiante por que esta siendo utilizado en la matricula, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_alumnos').on('click','.delete',function(){
    var data = tb_alumnos.row($(this).parents('tr')).data();
  
    if(tb_alumnos.row(this).child.isShown()){
        var data = tb_alumnos.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar a el estudiante: '+data.Estudiante+'?',
      text: "Una vez aceptado el estudiante sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Alumno(data.alum_dni);
      }
    })
  })