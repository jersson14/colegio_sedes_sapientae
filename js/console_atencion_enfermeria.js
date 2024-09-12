//LISTADO DE ROLES
var tbl_atencion_enfermeria;
function listar_atencion_enfermeria(){
    tbl_atencion_enfermeria = $("#tabla_enfermeria").DataTable({
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
          "url":"../controller/atencion_enfermeria/controlador_listar_atención_enfermeria.php",
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
        {"data":"id_atencion"},
        {"data":"alum_dni"},
        {"data":"Estudiante"},
        {"data":"alum_sexo",
            render: function(data,type,row){
                if(data=='FEMENINO'){
                return '<span class="badge bg-success">FEMENINO</span>';
                }else{
                return '<span class="badge bg-primary">MASCULINO</span>';
                }
        }
        },
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
        {"data":"motivo_consulta"},
        {"data":"fecha_formateada2"},
        
        {"defaultContent":"<button class='mostrar btn btn-success  btn-sm' title='Mostrar datos'><i class='fa fa-eye'></i> Mostrar</button>&nbsp;&nbsp;<button class='editar btn btn-primary  btn-sm' title='Editar datos del estudiante'><i class='fa fa-edit'></i> Editar</button>"},
        
    ],

    "language":idioma_espanol,
    select: true
});
tbl_atencion_enfermeria.on('draw.td',function(){
  var PageInfo = $("#tabla_enfermeria").DataTable().page.info();
  tbl_atencion_enfermeria.column(0, {page: 'current'}).nodes().each(function(cell, i){
    cell.innerHTML = i + 1 + PageInfo.start;
  });
});
}
//TRAENDO DATOS DEL MATRICULADO
function Cargar_Select_Matriculados(){
    $.ajax({
      "url":"../controller/atencion_psicologica/controlador_cargar_matriculado_año.php",
      type:'POST',
    }).done(function(resp){
      let data=JSON.parse(resp);
      if(data.length>0){
        let cadena ="";
        for (let i = 0; i < data.length; i++) {
          cadena+="<option value='"+data[i][0]+"'>"+data[i][5]+" - "+data[i][1]+"</option>";    
        }
          document.getElementById('txt_estudiante').innerHTML=cadena;
          document.getElementById('txt_estudiante_editar').innerHTML=cadena;
      }else{
        cadena+="<option value=''>No hay secciones en la base de datos</option>";
        document.getElementById('txt_estudiante').innerHTML=cadena;
        document.getElementById('txt_estudiante_editar').innerHTML=cadena;
      }
    })
  }
  
//ENVIANDO DATOS PARA EDITAR
$('#tabla_enfermeria').on('click','.editar',function(){
  var data = tbl_atencion_enfermeria.row($(this).parents('tr')).data();

  if(tbl_atencion_enfermeria.row(this).child.isShown()){
      var data = tbl_atencion_enfermeria.row(this).data();
  }
  $("#modal_editar").modal('show');

  document.getElementById('txt_id_atencion').value=data.id_atencion;

  $("#txt_estudiante_editar").select2().val(data.id_matricula).trigger('change.select2');
  document.getElementById('txt_motivo_editar').value=data.motivo_consulta;
  document.getElementById('txt_diagnostico_editar').value=data.diagnostico;
  document.getElementById('txt_observacion_editar').value=data.observaciones;
})




$('#tabla_enfermeria').on('click','.mostrar',function(){
    var data = tbl_atencion_enfermeria.row($(this).parents('tr')).data();
  
    if(tbl_atencion_enfermeria.row(this).child.isShown()){
        var data = tbl_atencion_enfermeria.row(this).data();
    }
    $("#modal_mas").modal('show');
    document.getElementById('txt_estudiante_mas').value=data.Estudiante;
    document.getElementById('txt_psicologa_mas').value=data.Personal;
    document.getElementById('txt_motivo_mas').value=data.motivo_consulta;
    document.getElementById('txt_diagnostico_mas').value=data.diagnostico;
    document.getElementById('txt_observacion_mas').value=data.observaciones;
    document.getElementById('txt_fecha_actua_mas').value=data.updated_at;

  })
  

//ABRIENDO MODAL REGISTRO
function AbrirRegistro(){
  $("#modal_registro").modal({backdrop:'static',keyboard:false})
  $("#modal_registro").modal('show');
}

//REGISTRANDO ROLES
function Registrar_atencion(){
  let estu = document.getElementById('txt_estudiante').value;
  let motivo = document.getElementById('txt_motivo').value;
  let diagno = document.getElementById('txt_diagnostico').value;
  let observa = document.getElementById('txt_observacion').value;
  let idusu = document.getElementById('txtprincipalid').value;

  if(estu.length==0 || motivo.length==0 || idusu.length==0){
      return Swal.fire("Mensaje de Advertencia","El nombre del rol debe esta completo","warning");
  }
  $.ajax({
    "url":"../controller/atencion_enfermeria/controlador_registro_enfermeria.php",
    type:'POST',
    data:{
        estu:estu,
        motivo:motivo,
        diagno:diagno,
        observa:observa,
        idusu:idusu
    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Nueva atención registrada correctamente!!!","success").then((value)=>{
          tbl_atencion_enfermeria.ajax.reload();
          document.getElementById('txt_motivo').value="";
          document.getElementById('txt_diagnostico').value="";
          document.getElementById('txt_observacion').value="";

        $("#modal_registro").modal('hide');
        });
   
    }else{
      return Swal.fire("Mensaje de Error","No se completo el registro, hubo un error verifique","error");

    }
  })
}
//EDITANDO ATENCION
function Modificar_Atencion(){
  let id = document.getElementById('txt_id_atencion').value;
  let estu = document.getElementById('txt_estudiante_editar').value;
  let motivo = document.getElementById('txt_motivo_editar').value;
  let diagno = document.getElementById('txt_diagnostico_editar').value;
  let observa = document.getElementById('txt_observacion_editar').value;
  let idusu = document.getElementById('txtprincipalid').value;


  if(id.length==0||estu.length==0 || motivo.length==0 || idusu.length==0){
    return Swal.fire("Mensaje de Advertencia","Tiene campos vacios.","warning");
  }
  $.ajax({
    "url":"../controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php",
    type:'POST',
    data:{
      id:id,
      estu:estu,
      motivo:motivo,
      diagno:diagno,
      observa:observa,
      idusu:idusu,

    }
  }).done(function(resp){
    if(resp>0){
        Swal.fire("Mensaje de Confirmación","Datos de la tención actualizados correctamente","success").then((value)=>{
            tbl_atencion_enfermeria.ajax.reload();
            $("#modal_editar").modal('hide');
        });
     
    }else{
      return Swal.fire("Mensaje de Error","No se completo la actualización, revise por favor.","error");

    }
  })
}
