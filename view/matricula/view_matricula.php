<script src="../js/console_matriculas.js?rev=<?php echo time();?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE MATRICULA DE ALUMNOS</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">MATRICULA</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Matriculados</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_matricula" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Estudiante</th>
                      <th style="text-align:center">Aula o Grado</th>
                      <th style="text-align:center">Nivel Académico</th>
                      <th style="text-align:center">Año escolar</th>
                      <th style="text-align:center">Matrícula</th>
                      <th style="text-align:center">Procedencia Colegio</th>
                      <th style="text-align:center">Provincia</th>
                      <th style="text-align:center">Departamento</th>
                      <th style="text-align:center">Acción</th>
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
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE MATRICULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
            </div>
            <div class="col-5 form-group">
                <label for="">DNI - Estudiante<b style="color:red">(*)</b>:</label>
                <select class="js-example-basic-single" id="select_estudiante" style="width:100%">
                </select>            
            </div>
            <div class="col-2 form-group">
                <label for="">Tipo<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_tipo" disabled>
          
            </div>
            <div class="col-2 form-group">
                <label for="">Año Acad.<b style="color:red">(*)</b>:</label>
                <select class="js-example-basic-single" id="select_año" style="width:100%">
                </select>             
            </div>
            <div class="col-3 form-group">
                <label for="">Aula o Grado:</label>
                <select class="js-example-basic-single" id="select_aula" style="width:100%">
                </select>                
            </div>
            <div class="col-12">
                <div class="form-group clearfix">
                    <div class="icheck-success d-inline">
                        <input type="checkbox"  id="checkboxSuccess1" onclick="Validar_Informacion()">
                        <label for="checkboxSuccess1" style="align:justify">
                            Si el alumno es nuevo marque el casillero.
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-3 form-group">
                <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nivel" disabled >
            </div>
          
            <div class="col-3 form-group">
                <label for="txt_foto">Pago por Admisión <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_admision" value="0" placeholder="Ingrese el monto" disabled   onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-3 form-group">
                <label for="txt_foto">Pago Alumno Nuevo <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_alum_nuevo" value="0" placeholder="Ingrese el monto"  disabled onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-3 form-group">
            <label for="txt_foto">Pago de Matrícula <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_matricula" value="0" placeholder="Ingrese el monto" onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group hidden" id="campo_procedencia">
                <label for="txt_foto">Colegio de Procedencia:</label>
                <input type="text" class="form-control" id="txt_procedencia" placeholder="Ingrese el coledio de procedencia">
            </div>
            <div class="col-4 form-group hidden" id="campo_provincia">
            <label for="txt_foto">Provincia:</label>
                <input type="text" class="form-control" id="txt_provincia" placeholder="Ingrese la provincia" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group hidden" id="campo_departamento">
            <label for="txt_foto">Departamento:</label>
                <input type="text" class="form-control" id="txt_departamento" placeholder="Ingrese el Departamento" onkeypress="return sololetras(event)">
            </div>
          
            <div class="col-4 form-group hidden" id="campo_usu">
            <label for="">Usuario<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_usu"  placeholder="Ingrese el usuario">
            </div>
            <div class="col-4 form-group hidden" id="campo_contra">
            <label for="">Contraseña<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_contra" placeholder="Ingrese la contraseña">
            </div>
            <div class="col-4 form-group hidden" id="campo_correo">
            <label for="">Correo electronico<b style="color:red">(*)</b>:</label>
                <input type="email" class="form-control" id="txt_correo" placeholder="Ingrese el correo electronico">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_matriculado()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_mas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>DATOS DE LA MATRICULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-5 form-group">
                <label for="">DNI - Estudiante<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="select_estudiante_mas" disabled>
            </div>
            <div class="col-2 form-group">
                <label for="">Tipo:</label>
                <input type="text" class="form-control" id="txt_tipo_mas" disabled>
          
            </div>
            <div class="col-2 form-group">
                <label for="">Año Académico:</label>        
                <input type="text" class="form-control" id="select_año_mas" disabled >

            </div>
            <div class="col-3 form-group">
                <label for="">Aula o Grado:</label>
                <input type="text" class="form-control" id="select_aula_mas" disabled >

                </select>                
            </div>
            <div class="col-3 form-group">
                <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nivel_mas" disabled >
            </div>
          
            <div class="col-3 form-group">
                <label for="txt_foto">Pago por Admisión <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_admision_mas" placeholder="Ingrese el monto" disabled  >
            </div>
            <div class="col-3 form-group">
                <label for="txt_foto">Pago Alumno Nuevo <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_alum_nuevo_mas" placeholder="Ingrese el monto" disabled>
            </div>
            <div class="col-3 form-group">
            <label for="txt_foto">Pago de Matrícula <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_matricula_mas" placeholder="Ingrese el monto" disabled>
            </div>


            <div class="col-4 form-group">
                <label for="txt_foto">Colegio de Procedencia:</label>
                <input type="text" class="form-control" id="txt_procedencia_mas" placeholder="Ingrese el coledio de procedencia" disabled>
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Provincia:</label>
                <input type="text" class="form-control" id="txt_provincia_mas" placeholder="Ingrese la provincia" disabled>
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Departamento:</label>
                <input type="text" class="form-control" id="txt_departamento_mas" placeholder="Ingrese el Departamento" disabled>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE MATRICULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
            </div>
            <div class="col-5 form-group">
                <label for="">DNI - Estudiante<b style="color:red">(*)</b>:</label>
                <input type="text" id="txt_id_matricula" hidden>
                <input type="text" id="txt_id_estudiante" hidden>
                <input type="text" class="form-control" id="select_estudiante_editar" disabled>
         
            </div>
            <div class="col-2 form-group">
                <label for="">Tipo:</label>
                <input type="text" class="form-control" id="txt_tipo_editar" disabled>
          
            </div>
            <div class="col-2 form-group">
                <label for="">Año Académico:</label>
                <select class="form-control" id="select_año_editar" style="width:100%" disabled>
                </select>             
            </div>
            <div class="col-3 form-group">
                <label for="">Aula o Grado:</label>
                <select class="form-control" id="select_aula_editar"  style="width:100%">
                </select>                
            </div>
           
            <div class="col-3 form-group">
                <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nivel_editar">
            </div>
          
            <div class="col-3 form-group">
                <label for="txt_foto">Pago por Admisión <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_admision_editar" value="0" placeholder="Ingrese el monto">
            </div>
            <div class="col-3 form-group">
                <label for="txt_foto">Pago Alumno Nuevo <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_alum_nuevo_editar" value="0" placeholder="Ingrese el monto">
            </div>
            <div class="col-3 form-group">
            <label for="txt_foto">Pago de Matrícula <b style="color:red">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_matricula_editar" value="0" placeholder="Ingrese el monto">
            </div>
            <div class="col-4 form-group">
                <label for="txt_foto">Colegio de Procedencia:</label>
                <input type="text" class="form-control" id="txt_procedencia_editar" placeholder="Ingrese el coledio de procedencia">
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Provincia:</label>
                <input type="text" class="form-control" id="txt_provincia_editar" placeholder="Ingrese la provincia">
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Departamento:</label>
                <input type="text" class="form-control" id="txt_departamento_editar" placeholder="Ingrese el Departamento">
            </div>
          
           
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Matricula()"><i class="fas fa-save"></i> Registrar</button>
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
$(document).ready(function () {
    listar_matriculados();
  $('.js-example-basic-single').select2();
  Cargar_Select_estudiante();
  Cargar_Select_Grado();
  Cargar_Año();
});

$("#select_aula").change(function(){
var id=$("#select_aula").val();
Traernivel(id);
Validar_Informacion();
});
$("#select_aula_editar").change(function(){
var id=$("#select_aula_editar").val();
Traernivel(id);
Validar_Informacion();
});

$("#select_estudiante").change(function(){
var id=$("#select_estudiante").val();
Traertipo(id);
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