//LISTADO DE ROLES
var tbl_matricula;
function listar_matriculados(){
    tbl_matricula = $("#tabla_matricula").DataTable({
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
          "url":"../controller/matricula/controlador_listar_matriculas.php",
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
        {"data":"id_matricula"},
        {"data":"Estudiante"},
        {"data":"Grado"},
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
        {"data":"año_escolar"},
        {"data":"MATRICULA",
            render: function(data,type,row){
                    if(data==data){
                    return '<span class="badge bg-success">'+data+'</span>';
                    }
            }   
        },        {"data":"procedencia_colegio"},
        {"data":"provincia"},
        {"data":"departamento"},

        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostras datos'><i class='fa fa-eye'></i> Ver</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos de matrícula'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='imprimir btn btn-warning  btn-sm' title='Imprimir Matrícula'><i class='fa fa-print'></i> Imprimir cédula</button>&nbsp;&nbsp;"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_matricula.on('draw.td',function(){
  var PageInfo = $("#tabla_matricula").DataTable().page.info();
  tbl_matricula.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
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
          cadena+="<option value='"+data[i][0]+"'>"+data[i][1]+"</option>";    
        }
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

        var id =$("#select_aula").val();
        Traernivel(id);

        var id =$("#select_aula_editar").val();
        Traernivel(id);
      }else{
        cadena+="<option value=''>No se encontraron regitros</option>";
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

      }
    })
  }
  function Traernivel(id){
    $.ajax({
      "url":"../controller/matricula/controlador_traernivel.php",
      type:'POST',
          data:{
            id:id
          }
        }).done(function(resp){
          
        var data = JSON.parse(resp);
        var cadena="";
        if(data.length>0){
          $("#txt_nivel").val(data[0][1]);
          $("#txt_nivel_editar").val(data[0][1]);

        }
        else{
          cadena+="<option value=''>No se encontraron regitros</option>";
          $('#txt_nivel').html(cadena);
          $("#txt_nivel_editar").val(data[0][1]);

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
          document.getElementById('select_año_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año_editar').innerHTML=cadena;
      }
    })
  }
//TRAENDO DATOS DE LA NIVEL ACADEMICO

  function Cargar_Select_estudiante(){
    $.ajax({
      "url":"../controller/matricula/controlador_cargar_select_estudiante.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="<option value=''>Seleccionar Estudiante</option>";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>DNI: "+data[i][1]+" - "+data[i][2]+"</option>";    
        }
      $('#select_estudiante').html(cadena);

      var id =$("#select_estudiante").val();
      Traertipo(id);
    }else{
      cadena+="<option value=''>No se encontraron regitros</option>";
      $('#select_estudiante').html(cadena);

    }
    })
  }
  function Traertipo(id){
    $.ajax({
      "url":"../controller/matricula/controlador_traertipo.php",
      type:'POST',
          data:{
            id:id
          }
        }).done(function(resp){
          
        var data = JSON.parse(resp);
        var cadena="";
        if(data.length>0){
          $("#txt_tipo").val(data[0][1]);

        }
        else{
          cadena+="<option value=''>No se encontraron regitros</option>";
          $('#txt_tipo').html(cadena);


      }
    })
  }
//ENVIANDO DATOS PARA EDITAR
$('#tabla_matricula').on('click','.editar',function(){
  var data = tbl_matricula.row($(this).parents('tr')).data();

  if(tbl_matricula.row(this).child.isShown()){
      var data = tbl_matricula.row(this).data();
  }
  $("#modal_editar").modal('show');
  document.getElementById('txt_id_matricula').value=data.id_matricula;
  document.getElementById('txt_id_estudiante').value=data.id_alumno;
  document.getElementById('select_estudiante_editar').value=data.alum_dni+' - '+data.Estudiante;
  document.getElementById('txt_tipo_editar').value=data.tipo_alum;
  $("#select_año_editar").select2().val(data.id_año).trigger('change.select2');
  $("#select_aula_editar").select2().val(data.id_aula).trigger('change.select2');
  document.getElementById('txt_nivel_editar').value=data.Nivel_academico;

  document.getElementById('txt_admision_editar').value=data.pago_admi;
  document.getElementById('txt_alum_nuevo_editar').value=data.pago_alu_nuevo;
  document.getElementById('txt_matricula_editar').value=data.pago_matricula;

  document.getElementById('txt_procedencia_editar').value=data.procedencia_colegio;
  document.getElementById('txt_provincia_editar').value=data.provincia;
  document.getElementById('txt_departamento_editar').value=data.departamento;


})

//ENVIANDO DATOS PARA MOSTRAR
$('#tabla_matricula').on('click','.mostrar',function(){
  var data = tbl_matricula.row($(this).parents('tr')).data();

  if(tbl_matricula.row(this).child.isShown()){
      var data = tbl_matricula.row(this).data();
  }
  $("#modal_mas").modal('show');
  document.getElementById('select_estudiante_mas').value=data.alum_dni+' - '+data.Estudiante;
  document.getElementById('txt_tipo_mas').value=data.tipo_alum;
  document.getElementById('select_año_mas').value=data.año_escolar;
  document.getElementById('select_aula_mas').value=data.Grado;
  document.getElementById('txt_nivel_mas').value=data.Nivel_academico;
  document.getElementById('txt_admision_mas').value=data.pago_admi;
  document.getElementById('txt_alum_nuevo_mas').value=data.pago_alu_nuevo;
  document.getElementById('txt_matricula_mas').value=data.pago_matricula;
  document.getElementById('txt_procedencia_mas').value=data.procedencia_colegio;
  document.getElementById('txt_provincia_mas').value=data.provincia;
  document.getElementById('txt_departamento_mas').value=data.departamento;

})


//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_matriculado(){
  let estu = document.getElementById('select_estudiante').value;
  let año = document.getElementById('select_año').value;
  let aula = document.getElementById('select_aula').value;
  let admi = document.getElementById('txt_admision').value;
  let nuevo = document.getElementById('txt_alum_nuevo').value;
  let matri = document.getElementById('txt_matricula').value;
  let proce = document.getElementById('txt_procedencia').value;
  let pro = document.getElementById('txt_provincia').value;
  let depa = document.getElementById('txt_departamento').value;
  let usu = document.getElementById('txt_usu').value;
  let contra = document.getElementById('txt_contra').value;
  let correo = document.getElementById('txt_correo').value;
  if(estu.length==0||año.length==0||aula.length==0|| matri.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }

  $.ajax({
    "url":"../controller/matricula/controlador_registro_matriculas.php",
    type:'POST',
    data:{
        estu:estu,
        año:año,
        aula:aula,
        admi:admi,
        nuevo:nuevo,
        matri:matri,
        proce:proce,
        pro:pro,
        depa:depa,
        usu:usu,
        contra:contra,
        correo:correo
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Se registro la matricula satisfactoriamente!!!","success").then((value)=>{
          tbl_matricula.ajax.reload();
          document.getElementById('txt_usu').value="";
          document.getElementById('txt_contra').value="";
          document.getElementById('txt_correo').value="";

        $("#modal_registro").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El estudiante que intentas matricular ya se encuentra en la base de datos, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro","error");

    }
  })
}
//EDITANDO ROL
function Modificar_Matricula(){
  let id = document.getElementById('txt_id_matricula').value;
  let estu = document.getElementById('txt_id_estudiante').value;
  let año = document.getElementById('select_año_editar').value;
  let aula = document.getElementById('select_aula_editar').value;
  let admi = document.getElementById('txt_admision_editar').value;
  let nuevo = document.getElementById('txt_alum_nuevo_editar').value;
  let matri = document.getElementById('txt_matricula_editar').value;
  let proce = document.getElementById('txt_procedencia_editar').value;
  let pro = document.getElementById('txt_provincia_editar').value;
  let depa = document.getElementById('txt_departamento_editar').value;

  if(estu.length==0||año.length==0||aula.length==0|| matri.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios","warning");
  }
  $.ajax({
    "url":"../controller/matricula/controlador_modificar_matrícula.php",
    type:'POST',
    data:{
      id:id,
      estu:estu,
      año:año,
      aula:aula,
      admi:admi,
      nuevo:nuevo,
      matri:matri,
      proce:proce,
      pro:pro,
      depa:depa,
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Datos actualizados correctamente","success").then((value)=>{
            tbl_matricula.ajax.reload();
            $("#modal_editar").modal('hide');
        });
      }else{
        Swal.fire("Mensaje de Advertencia","El estudiante y año académico que intentas actualizar ya se encuentra en la base de datos, revise por favor.","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización.","error");

    }
  })
}
//ELIMINANDO ROL
function Eliminar_Aula(id){
    $.ajax({
      "url":"../controller/aulas/controlador_eliminar_rol.php",
      type:'POST',
      data:{
        id:id
      }
    }).done(function(resp){
      if(resp>0){
          Swal.fire("Mensaje de Confirmación","Se elimino el grado académico con exito","success").then((value)=>{
            tbl_matricula.ajax.reload();
          });
      }else{
        return Swal.fire("Mensaje de Advetencia","No se puede eliminar este grado académico por que esta siendo utilizadoe en otro registros, verifique por favor","warning");
  
      }
    })
  }

  //ENVIANDO AL BOTON DELETE
$('#tabla_matricula').on('click','.delete',function(){
    var data = tbl_matricula.row($(this).parents('tr')).data();
  
    if(tbl_matricula.row(this).child.isShown()){
        var data = tbl_matricula.row(this).data();
    }
    Swal.fire({
      title: 'Desea eliminar el grado académico: '+data.Grado+'?',
      text: "Una vez aceptado el grado académico sera eliminado!!!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Si, Eliminar'
    }).then((result) => {
      if (result.isConfirmed) {
        Eliminar_Aula(data.Id_aula);
      }
    })
  })