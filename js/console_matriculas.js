//LISTADO DE MATRICULAS
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
        return  "LISTA DE MATRICULADOS"
      },
        title: function() {
          return  "LISTA DE MATRICULADOS" }
  
    },
    {
      extend:    'pdfHtml5',
      text:      '<i class="fas fa-file-pdf"></i> ',
      titleAttr: 'Exportar a PDF',
      filename: function() {
        return  "LISTA DE MATRICULADOS"
      },
    title: function() {
      return  "LISTA DE MATRICULADOS"
    }
  },
    {
      extend:    'print',
      text:      '<i class="fa fa-print"></i> ',
      titleAttr: 'Imprimir',
      
    title: function() {
      return  "LISTA DE MATRICULADOS"
  
    }
    }],
      "columns":[
        {"data":"id_matricula"},
        {"data":"alum_dni"},
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
                    return '<span class="badge bg-success">'+formatoMoneda(data)+'</span>';
                    }
            }   
        },        {"data":"procedencia_colegio"},
        {"data":"provincia"},
        {"data":"departamento"},

        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostras datos'><i class='fa fa-eye'></i> Ver</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos de matrícula'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='cedula btn btn-warning  btn-sm' title='Imprimir Matrícula'><i class='fa fa-print'></i> Imprimir cédula</button>&nbsp;&nbsp;<button class='beca btn btn-info btn-sm' title='Beca o descuento en las pensiones'><i class='fa fa-percent'></i> Beca</button>&nbsp;&nbsp;<button class='delete btn btn-danger btn-sm' title='Eliminar matriculado'><i class='fa fa-trash'></i> Eliminar</button>"},
        
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


function listar_matriculados_filtro(){
  let año = document.getElementById('select_año_buscar').value;
    let grado = document.getElementById('select_aula_buscar').value;
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
        "url":"../controller/matricula/controlador_listar_matriculas_filtro.php",
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
      return  "LISTA DE MATRICULADOS"
    },
      title: function() {
        return  "LISTA DE MATRICULADOS" }

  },
  {
    extend:    'pdfHtml5',
    text:      '<i class="fas fa-file-pdf"></i> ',
    titleAttr: 'Exportar a PDF',
    filename: function() {
      return  "LISTA DE MATRICULADOS"
    },
  title: function() {
    return  "LISTA DE MATRICULADOS"
  }
},
  {
    extend:    'print',
    text:      '<i class="fa fa-print"></i> ',
    titleAttr: 'Imprimir',
    
  title: function() {
    return  "LISTA DE MATRICULADOS"

  }
  }],
    "columns":[
      {"data":"id_matricula"},
      {"data":"alum_dni"},
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
                  return '<span class="badge bg-success">'+formatoMoneda(data)+'</span>';
                  }
          }   
      },        {"data":"procedencia_colegio"},
      {"data":"provincia"},
      {"data":"departamento"},

      {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostras datos'><i class='fa fa-eye'></i> Ver</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos de matrícula'><i class='fa fa-edit'></i> Editar</button>&nbsp;&nbsp; <button class='cedula btn btn-warning  btn-sm' title='Imprimir Matrícula'><i class='fa fa-print'></i> Imprimir cédula</button>&nbsp;&nbsp;<button class='beca btn btn-info btn-sm' title='Beca o descuento en las pensiones'><i class='fa fa-percent'></i> Beca</button>&nbsp;&nbsp;<button class='delete btn btn-danger btn-sm' title='Eliminar matriculado'><i class='fa fa-trash'></i> Eliminar</button>"},
      
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
function Cargar_Select_estudiante() {
  $.ajax({
    url: "../controller/matricula/controlador_cargar_select_estudiante.php",
    type: 'POST',
    dataType: 'json'
  }).done(function(data) {
    console.log("Datos recibidos:", data);
    let cadena = "<option value=''>Seleccionar Estudiante</option>";
    if (Array.isArray(data) && data.length > 0) {
      data.forEach(function(item) {
        cadena += `<option value='${textoSeguro(item[0])}'>${REGIONAL.doc_nombre}: ${textoSeguro(item[1])} - ${textoSeguro(item[2])}</option>`;
      });
    } else {
      cadena = "<option value=''>No se encontraron registros</option>";
    }
    $('#select_estudiante').html(cadena);

    // Inicializa Select2 para el select de estudiante
    $('#select_estudiante').select2({
      dropdownParent: $('#modal_registro') // Si aplicas esto en un modal, puedes personalizarlo según el caso
    });

    // Recuperar el valor seleccionado (id del estudiante) y llamar a la función Traertipo si es necesario
    var id = $("#select_estudiante").val();
    if (id) {
      Traertipo(id); // Asegúrate de que esta función existe y está bien implementada
    }
  }).fail(function(jqXHR, textStatus, errorThrown) {
    console.error("Error en la petición AJAX de estudiantes:", textStatus, errorThrown);
    console.log("Respuesta del servidor:", jqXHR.responseText);
  });
}


function debugSelect2() {
  console.log("Select2 para estudiantes:", $('#select_estudiante').select2('data'));
  console.log("¿Select2 está abierto?", $('.select2-container--open').length > 0);
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
          cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+" - "+textoSeguro(data[i][2])+"</option>";    
        }
        $('#select_aula').html(cadena);
        $('#select_aula_editar').html(cadena);

        var id =$("#select_aula").val();
        Traernivel(id);

        var id2 =$("#select_aula_editar").val();
        Traernivel(id2);
      }else{
        let cadena = "<option value=''>No se encontraron regitros</option>";
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
          let cadena = "<option value=''>No se encontraron regitros</option>";
          $('#txt_nivel').html(cadena);
          $("#txt_nivel_editar").val(data[0][1]);

      }
    })
  }


  
function Cargar_Select_Nivelaca(){
  $.ajax({
    "url":"../controller/aulas/controlador_cargar_select_nivel.php",
    type:'POST',
  }).done(function(resp){
    let data=JSON.parse(resp);
    if(data.length>0){
      let cadena ="";
      for (let i = 0; i < data.length; i++) {
        cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+"</option>";    
      }
      $('#select_nivel_buscar').html(cadena);

      var id =$("#select_nivel_buscar").val();
      Cargar_Select_Aula(id);

    }else{
      let cadena = "<option value=''>No se encontraron regitros</option>";
      $('#select_nivel_buscar').html(cadena);

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
                  cadena += "<option value='" + textoSeguro(data[i][1]) + "'>" + textoSeguro(data[i][2]) + "</option>";    
              }
              $('#select_aula_buscar').html(cadena);
          } else {
              $('#select_aula_buscar').html("<option value=''>No hay secciones en la base de datos</option>");
          }
      },
      error: function(xhr, status, error) {
          console.error("AJAX Error: " + status + " - " + error);
          $('#select_aula_buscar').html("<option value=''>Error al cargar las secciones</option>");
      }
  });
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
          cadena+="<option value='"+textoSeguro(data[i][0])+"'>"+textoSeguro(data[i][1])+"</option>";    
        }
          document.getElementById('select_año').innerHTML=cadena;
          document.getElementById('select_año_editar').innerHTML=cadena;
          document.getElementById('select_año_buscar').innerHTML=cadena;

      }else{
        let cadena = "<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('select_año').innerHTML=cadena;
        document.getElementById('select_año_editar').innerHTML=cadena;
        document.getElementById('select_año_buscar').innerHTML=cadena;

      }
    })
  }
//TRAENDO DATOS DE LA NIVEL ACADEMICO


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
          let cadena = "<option value=''>No se encontraron regitros</option>";
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
  document.getElementById('estudiante').value=data.alum_dni+' - '+data.Estudiante;
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
  let metodo = document.getElementById('select_metodo_matricula').value;
  let cuenta = document.getElementById('select_cuenta_matricula').value;
  let referencia = document.getElementById('txt_ref_matricula').value;
  if(estu.length==0||año.length==0||aula.length==0|| matri.length==0){
      return Swal.fire("Mensaje de Advertencia","Complete el estudiante, año, aula y pago de matrícula","warning");
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
        correo:correo,
        metodo:metodo,
        cuenta:cuenta,
        referencia:referencia
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Se registro la matricula satisfactoriamente!!!","success").then((value)=>{
          tbl_matricula.ajax.reload();
          document.getElementById('txt_usu').value="";
          document.getElementById('txt_contra').value="";
          document.getElementById('txt_correo').value="";
          Cargar_Select_estudiante();
        $("#modal_registro").modal('hide');
        });
      }else if(resp==3){
        Swal.fire("Mensaje de Advertencia","El nombre de usuario ya existe, ingrese otro","warning");
      }else if(resp==4){
        Swal.fire("Mensaje de Advertencia","Ingrese el usuario y contraseña del estudiante (es su primera matrícula)","warning");
      }else if(resp==5){
        Swal.fire("Mensaje de Advertencia","Revise la forma de pago: seleccione la caja o cuenta e indique el n.° de operación si corresponde","warning");
      }else if(resp==6){
        Swal.fire("Mensaje de Advertencia","El aula elegida ya no tiene vacantes en ese año escolar (revise su capacidad en Aulas)","warning");
      }else{
        Swal.fire("Mensaje de Advertencia","El estudiante ya está matriculado en ese año académico, revise por favor","warning");
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
      }else if(resp==3){
        Swal.fire("Mensaje de Advertencia","El aula elegida ya no tiene vacantes en ese año escolar (revise su capacidad en Aulas)","warning");
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
      title: 'Desea eliminar el grado académico: '+textoSeguro(data.Grado)+'?',
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


  $('#tabla_matricula').on('click','.cedula',function(){
    var data = tbl_matricula.row($(this).parents('tr')).data();
  
    if(tbl_matricula.row(this).child.isShown()){
        var data = tbl_matricula.row(this).data();
    }
    var url = "../view/MPDF/REPORTE/cedula.php?codigo=" + encodeURIComponent(data.id_matricula)+ "#zoom=100%";
  
    // Abrir una nueva ventana con la URL construida
    var newWindow = window.open(url, "CEDULA DE MATRICULA", "scrollbars=NO");
    
    // Asegurarse de que la ventana se abre en tamaño máximo
    if (newWindow) {
        newWindow.moveTo(0, 0);
        newWindow.resizeTo(screen.width, screen.height);
    }
  
  })
  
  



  
function eliminar_matricula(id){
  $.ajax({
    "url":"../controller/matricula/controlador_eliminar_matricula.php",
    type:'POST',
    data:{
      id:id
    }
  }).done(function(resp){
    if(resp>0){
      if(resp==1){
        Swal.fire("Mensaje de Confirmación","Se eliminó la matrícula satisfactoriamente","success").then((value)=>{
          tbl_matricula.ajax.reload();
        });
      }else{
        Swal.fire("Mensaje de Advertencia","La matricula del alumno no podra ser eliminada ya que cuenta con pagos de pensión y tiene registros en otros formularios, revise por favor","warning");
      }
    }else{
      return Swal.fire("Mensaje de Error","No se pudo completar el proceso","error");

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
    title: 'Desea eliminar la matricula del alumno: '+textoSeguro(data.Estudiante)+'?',
    text: "Una vez aceptado la matricula del alumno sera eliminada de esta lista!!!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Si, Eliminar'
  }).then((result) => {
    if (result.isConfirmed) {
      eliminar_matricula(data.id_matricula);
    }
  })
})
// Forma de pago del recibo de matrícula (Fase 4)
var formas_pago_matricula = {metodos: [], cuentas: []};
function Cargar_Formas_Pago_Matricula(){
  $.ajax({
    url: "../controller/pago_pension/controlador_cargar_formas_pago.php",
    type: 'POST'
  }).done(function(resp){
    formas_pago_matricula = resp;
    let metodos = "", cuentas = "";
    resp.metodos.forEach(function(m){ metodos += "<option value='" + m.id_metodo + "'>" + $('<div>').text(m.nombre).html() + "</option>"; });
    resp.cuentas.forEach(function(c){ cuentas += "<option value='" + c.id_cuenta + "' data-tipo='" + c.tipo + "'>" + $('<div>').text(c.nombre).html() + "</option>"; });
    $("#select_metodo_matricula").html(metodos);
    $("#select_cuenta_matricula").html(cuentas);
    Sugerir_Cuenta_Matricula();
  });
}
function Sugerir_Cuenta_Matricula(){
  let m = formas_pago_matricula.metodos.find(x => x.id_metodo == $("#select_metodo_matricula").val());
  if (!m) { return; }
  let tipo = m.tipo == 'EFECTIVO' ? 'CAJA' : (m.tipo == 'BILLETERA' ? 'BILLETERA' : 'BANCO');
  let op = $("#select_cuenta_matricula option[data-tipo='" + tipo + "']").first();
  if (op.length) { $("#select_cuenta_matricula").val(op.val()); }
  $("#txt_ref_matricula").attr('placeholder', m.requiere_referencia == 1 ? 'Obligatorio' : 'Opcional');
}
$(document).off('change.fpm', '#select_metodo_matricula').on('change.fpm', '#select_metodo_matricula', Sugerir_Cuenta_Matricula);


// ---------- Beca o descuento en las pensiones (Fase 8D) ----------
$('#tabla_matricula').on('click', '.beca', function() {
  var data = tbl_matricula.row($(this).parents('tr')).data();
  if (tbl_matricula.row(this).child.isShown()) { data = tbl_matricula.row(this).data(); }
  $.ajax({ url: "../controller/matricula/controlador_beca_traer.php", type: 'POST', data: { id: data.id_matricula } }).done(function(r) {
    let b = r.data;
    $("#txt_id_matricula_beca").val(data.id_matricula);
    $("#lb_beca_alumno").text(textoPlano(data.Estudiante) + " – " + textoPlano(data.Grado) + " (" + textoPlano(data["año_escolar"]) + ")");
    $("#select_beca_tipo").val(b.beca_tipo);
    $("#txt_beca_valor").val(b.beca_tipo == 'NINGUNA' ? '' : b.beca_valor);
    $("#txt_beca_desde").val(b.beca_desde || '');
    $("#txt_beca_motivo").val(b.beca_motivo || '');
    Mostrar_Campos_Beca();
    $("#modal_beca").modal('show');
  });
});
$(document).off('change.beca', '#select_beca_tipo').on('change.beca', '#select_beca_tipo', Mostrar_Campos_Beca);
function Mostrar_Campos_Beca() {
  let tipo = $("#select_beca_tipo").val();
  $(".beca-campos").toggle(tipo != 'NINGUNA');
  $("#lb_beca_valor").text(tipo == 'PORCENTAJE' ? 'Porcentaje de descuento (%):' : 'Monto de descuento por pensión:');
}
function Guardar_Beca() {
  $.ajax({ url: "../controller/matricula/controlador_beca_guardar.php", type: 'POST',
           data: { id: $("#txt_id_matricula_beca").val(), tipo: $("#select_beca_tipo").val(), valor: $("#txt_beca_valor").val() || '0',
                   desde: $("#txt_beca_desde").val(), motivo: $("#txt_beca_motivo").val() } }).done(function(r) {
    if (r.codigo == 1) {
      Swal.fire("Mensaje de Confirmación", textoSeguro(r.mensaje) + ". Las pensiones que vencen desde la fecha indicada ya muestran el precio con descuento.", "success");
      $("#modal_beca").modal('hide');
    } else {
      Swal.fire("Mensaje de Advertencia", textoSeguro(r.mensaje), "warning");
    }
  });
}
