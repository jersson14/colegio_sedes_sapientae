<script src="../js/console_asistencia.js?rev=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE ASISTENCIA</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">ASISTENCIA</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado Asistencia</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:left">
                  <div class="card-body">
                  <div class="row">
                <div class="col-12 col-md-3" role="document">
                    <div class="form-group">
                    <label for="txtfechainicio">Fecha Desde:</label>
                        <div class="input-group mb-2">
                         <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                        <input type="date" class="form-control" id="txtfechainicio" name="txtfechainicio" required>
                        <div class="valid-input invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-md-3" role="document">
                    <div class="form-group">
                    <label for="txtfechafin">Fecha Hasta:</label>
                        <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                        <input type="date" class="form-control" id="txtfechafin" name="txtfechafin" required>
                        <div class="valid-input invalid-feedback"></div>
                    </div>
                    </div>
                </div>
                <div class="col-12 col-md-3" role="document">
                    <label for="">&nbsp;</label><br>
                    <button onclick="listar_asistencia_fechas()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar asistencias</button>
                </div>
                <div class="col-12 col-md-3" role="document">
                    <label for="">&nbsp;</label><br>
                    <button onclick="listar_asistencia()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todos</button>
                </div>
                </div>
                </div>
          <div class="table-responsive" style="text-align:center">
            <div class="card-body">
              <table id="tabla_asistencia" class="table table-striped table-bordered" style="width:100%">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                    <th style="text-align:center">Nro.</th>
                    <th style="text-align:center">Año Académico</th>
                    <th style="text-align:center">Aula o Grado</th>
                    <th style="text-align:center">Sección</th>
                    <th style="text-align:center">Nivel Académico</th>
                    <th style="text-align:center">Fecha</th>
                    <th style="text-align:center">Ver Asistencias</th>
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
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content ">
        <div class="modal-header" style="background-color:#1FA0E0;">
          <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE ASISTENCIA</b></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12 form-group" style="color:red">
              <h6><b>Campos Obligatorios (*)</b></h6>
            </div>

            <div class="col-6 form-group">
              <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_nivel" style="width:100%">
              </select>
            </div>
            <div class="col-6 form-group">
              <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_aula" style="width:100%">
              </select>
            </div>

            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="listar_alumnos()">
                <i class="fas fa-search"></i> <b>Buscar estudiantes</b>
              </button>
            </div>
            <div class="col-12 table-responsive" style="text-align:center">
              <table id="tabla_alumnos" style="width:100%" class="table">
                <thead class="thead-dark">
                  <tr>
                    <th style="text-align:center">ID</th>
                    <th style="text-align:center">DNI</th>
                    <th style="text-align:center">Estudiante</th>
                    <th style="text-align:center">Fecha</th>
                    <th style="text-align:center">Asistencia</th>
                    <th style="text-align:center">Observación</th>
                  </tr>
                </thead>
              </table>

            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
          <button type="button" class="btn btn-success" onclick="Registrar_asistencia()"><i class="fas fa-save"></i> Registrar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modal_ver_asistencia" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                  <table id="tabla_ver" style="width:100%; text-align:center;">
                    <thead style="background-color:#0A5D86;color:#FFFFFF;">
                      <tr>
                        <th style="text-align:center">ID</th>
                        <th style="text-align:center">DNI</th>
                        <th style="text-align:center">Estudiante</th>
                        <th style="text-align:center">Fecha</th>
                        <th style="text-align:center">Asistencia</th>
                        <th style="text-align:center">Observación</th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Aquí van los datos de la tabla -->
                    </tbody>
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
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content ">
        <div class="modal-header" style="background-color:#1FA0E0;">
          <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE ASISTENCIA</b></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12 form-group" style="color:red">
              <h6><b>Campos Obligatorios (*)</b></h6>
            </div>

            <div class="col-6 form-group">
              <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
              <input type="date" id="txt_fecha_editar" hidden>
              <select class="form-control" id="select_nivel_editar" style="width:100%">
              </select>
            </div>
            <div class="col-6 form-group">
              <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_aula_editar" style="width:100%">
              </select>
            </div>

            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="listar_alumnos()">
                <i class="fas fa-search"></i> <b>Buscar estudiantes</b>
              </button>
            </div>
            <div class="col-12 table-responsive" style="text-align:center">
              <table id="tabla_alumnos_editar" style="width:100%" class="table">
                <thead class="thead-dark">
                  <tr>
                    <th style="text-align:center">ID</th>
                    <th style="text-align:center">DNI</th>
                    <th style="text-align:center">Estudiante</th>
                    <th style="text-align:center">Fecha</th>
                    <th style="text-align:center">Asistencia</th>
                    <th style="text-align:center">Observación</th>
                  </tr>
                </thead>
              </table>

            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
          <button type="button" class="btn btn-success" onclick="Editar_asistencia()"><i class="fas fa-edit"></i> Editar</button>
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
    $(document).ready(function() {
      listar_asistencia();
      $('.js-example-basic-single').select2();
      Cargar_Select_Nivelaca();
      Cargar_Select_Grado_buscar();
    });


    $("#select_nivel").change(function() {
      var id = $("#select_nivel").val();
      Cargar_Select_Aula(id);
    });

    $("#select_nivel_editar").change(function() {
      var id = $("#select_nivel_editar").val();
      Cargar_Select_Aula(id);
    });



    $('#modal_registro').on('shown.bs.modal', function() {
      $('#txt_matricula').trigger('focus')
    })


    var n = new Date();
var y = n.getFullYear();
var m = n.getMonth() + 1;

// Formatear el mes con un cero inicial si es necesario
if (m < 10) {
    m = '0' + m;
}

// Establecer el primer día del mes
var firstDay = y + "-" + m + "-01";

// Calcular el último día del mes actual
var lastDay = new Date(y, n.getMonth() + 1, 0).getDate();

// Formatear el último día del mes con un cero inicial si es necesario
if (lastDay < 10) {
    lastDay = '0' + lastDay;
}

var lastDayFormatted = y + "-" + m + "-" + lastDay;

document.getElementById('txtfechainicio').value = firstDay;
document.getElementById('txtfechafin').value = lastDayFormatted;

  </script>