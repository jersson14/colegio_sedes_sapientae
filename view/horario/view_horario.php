<script src="../js/console_horarios.js?rev=<?php echo time();?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE HORARIOS</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">AULA - HORAS</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <!-- /.col-md-6 -->
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Horarios</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>

          </div>
          <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-2 form-group">
                                    <label for="">Año académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_año_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_aula_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_horarios_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar horario</button>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_horarios()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_horario" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Año escolar</th>
                      <th style="text-align:center">Aula - Sección</th>
                      <th style="text-align:center">Nivel Académico</th>
                      <th style="text-align:center">Horario</th>
                      <th style="text-align:center">Estado</th>
                      <th style="text-align:center">Acciones</th>
                  </tr>
              </thead>
          </table>
          </div>
        </div>

      </div>
      <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content -->

    <!-- Modal -->
<div class="modal fade" id="modal_registro" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE HORARIOS POR AULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-4 form-group">
            <label for="">Año académico<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_año" style="width:100%">
            </select>              
          </div>
          <div class="col-4 form-group">
            <label for="">Aula - Nivel Académico<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_aula" style="width:100%">
            </select>              
          </div>
          <div class="col-4 form-group">
                <label for="">Día<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_dia" style="width:100%">
                    <option value="">--SELECCIONE UN DÍA--</option>
                    <option value="LUNES">LUNES</option>
                    <option value="MARTES">MARTES</option>
                    <option value="MIERCOLES">MIERCOLES</option>
                    <option value="JUEVES">JUEVES</option>
                    <option value="VIERNES">VIERNES</option>
                </select>              
            </div>
          <div class="col-6 form-group">
            <label for="">Horas<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_horas" style="width:100%">
            </select>          
        </div>
          
        <div class="col-6 form-group">
            <label for="">Asignatura<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_curso" style="width:100%">
            </select>          
        </div>

            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_componente()">
                  <i class="fas fa-plus"></i> <b>Agregar Asignatura</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_horario_aula" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th>ID Hora</th>
                <th>Hora</th>
                <th>Id Asignatura</th>
                <th>Asignatura</th>
                <th>Día</th>
                <th>Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody_tabla_aula_hora">
            </tbody>
            </table>  
           
          </div>
          
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_horario_aula()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_ver_horario" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0">
    <div class="col-12 form-group" style="color:white; margin-bottom: 0;">
        <h5 class="modal-title" id="lb_titulo" style="color:white; margin-bottom: 0;"></h5>
        <h5 class="modal-title" id="lb_titulo2" style="color:white; margin-bottom: 0;"></h5>
        </div>        
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12" style="text-align:center"> 
          <div class="table-responsive" style="text-align:center">
            <div class="card-body">
            <table id="tabla_vista_horario" class="display compact" style="width:100%" style="text-align:center">
                <thead style="background-color:#0A5D86;color:#FFFFFF; ">
                  <tr style="text-align:center">
                      <th style="text-align:center">Hora</th>
                      <th style="text-align:center">Lunes</th>
                      <th style="text-align:center">Martes</th>
                      <th style="text-align:center">Miercoles</th>
                      <th style="text-align:center">Jueves</th>
                      <th style="text-align:center">Viernes</th>
                   </tr>
                  </thead>
                </table>     
          </div>
           </div>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-arrow-right-from-bracket"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE HORARIOS POR AULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-4 form-group">
            <label for="">Año académico<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_año_editar" style="width:100%" disabled>
            </select>              
          </div>
          <div class="col-4 form-group">
            <label for="">Aula - Nivel Académico<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_aula_editar" style="width:100%">
            </select>              
          </div>
          <div class="col-4 form-group">
                <label for="">Día<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_dia_editar" style="width:100%">
                    <option value="">--SELECCIONE UN DÍA--</option>
                    <option value="LUNES">LUNES</option>
                    <option value="MARTES">MARTES</option>
                    <option value="MIERCOLES">MIERCOLES</option>
                    <option value="JUEVES">JUEVES</option>
                    <option value="VIERNES">VIERNES</option>
                </select>              
            </div>
          <div class="col-6 form-group">
            <label for="">Horas<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_horas_editar" style="width:100%">
            </select>          
        </div>
          
        <div class="col-6 form-group">
            <label for="">Asignatura<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_curso_editar" style="width:100%">
            </select>          
        </div>

            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_componente_editar()">
                  <i class="fas fa-plus"></i> <b>Agregar Asignatura</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_horario_aula_editar" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th>ID Hora</th>
                <th>Hora</th>
                <th>Id Asignatura</th>
                <th>Asignatura</th>
                <th>Día</th>
                <th>Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody tabla_aula_hora_editar">
            </tbody>
            </table>  
           
          </div>
          
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_horarios_aula()"><i class="fas fa-edit"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>
<style>
    .hidden {
        display: none;
    }
</style>

<script>
    listar_horarios();

$(document).ready(function () {
  $('.js-example-basic-single').select2();
  Cargar_Select_Grado();
  Cargar_Año();
  Cargar_Select_Nivelaca();
});



$("#select_aula").change(function(){
var id=$("#select_aula").val();
Cargar_Select_horas(id);
});

$("#select_aula_editar").change(function(){
var id=$("#select_aula_editar").val();
Cargar_Select_horas(id);
});
      
$("#select_nivel_buscar").change(function() {
      var id = $("#select_nivel_buscar").val();
      Cargar_Select_Aula(id);
    });

$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_matricula').trigger('focus')
})



</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var camposAdicionales = [
            'txt_procedencia',
            'txt_provincia',
            'txt_departamento',
            'txt_usu',
            'txt_contra',
            'txt_correo'
        ];

        camposAdicionales.forEach(function(id) {
            var element = document.getElementById(id);
            if (element) {
                element.parentElement.style.display = 'none';
            }
        });
    });

    function Validar_Informacion() {
        var isChecked = document.getElementById('checkboxSuccess1').checked;
        
        // Habilitar o deshabilitar campos de pago
        document.getElementById('txt_admision').disabled = !isChecked;
        document.getElementById('txt_alum_nuevo').disabled = !isChecked;

        // Mostrar u ocultar campos adicionales
        var camposAdicionales = [
            'txt_procedencia',
            'txt_provincia',
            'txt_departamento',
            'txt_usu',
            'txt_contra',
            'txt_correo'
        ];

        camposAdicionales.forEach(function(id) {
            var element = document.getElementById(id);
            if (element) {
                element.parentElement.style.display = isChecked ? 'block' : 'none';
            }
        });
    }
</script>
