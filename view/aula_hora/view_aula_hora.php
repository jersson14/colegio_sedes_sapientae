<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_aulas_hora.js?rev=<?php echo time();?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Horas de clase por aula</h1>
        <p class="pagina-ayuda">Bloques horarios disponibles en cada aula.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Horas de clase por aula</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Aula - Horas</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>

          </div>
          <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-2 form-group">
                                    <label for="">Año académico<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_año_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Grado o Aula<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_aula_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_aula_hora_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar hora</button>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_aula_hora()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_aula_horas" class="table table-striped table-bordered" style="width:100%">
              <thead class="tabla-cabecera">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Año escolar</th>
                      <th style="text-align:center">Aula o Grado</th>
                      <th style="text-align:center">Nivel Académico</th>
                      <th style="text-align:center">Turno</th>
                      <th style="text-align:center">Aula - Hora</th>
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
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>REGISTRO DE HORAS POR AULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group text-danger">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-4 form-group">
            <label for="">Año académico<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_año" style="width:100%">
            </select>              
          </div>
          <div class="col-4 form-group">
            <label for="">Aula o Grado<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_aula" style="width:100%">
            </select>              
          </div>
          <div class="col-4 form-group">
            <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
              <input type="text" class="form-control" id="txt_nivel" disabled>
          </div>
          <div class="col-4 form-group">
                <label for="">Turno<b class="text-danger">(*)</b>:</label>
                <select class="form-control" id="select_turno" style="width:100%">
                    <option value="">Selecciona un turno</option>
                    <option value="MAÑANA">MAÑANA</option>
                    <option value="TARDE">TARDE</option>
                </select>              
            </div>
            <div class="col-4 form-group">
                <label for="">Hora Inicio<b class="text-danger">(*)</b>:</label>
                <input type="time" class="form-control" id="hora_inicio">
            </div>
            <div class="col-4 form-group">
                <label for="">Hora Fin<b class="text-danger">(*)</b>:</label>
                <input type="time" class="form-control" id="hora_fin">
            </div>

            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_componente()">
                  <i class="fas fa-plus"></i> <b>Agregar Hora</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_aula_hora" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th>Año</th>
                <th>Aula</th>
                <th>Turno</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
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
        <button type="button" class="btn btn-success" onclick="Registrar_aula_hora()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_ver_horas" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <div class="modal-header ventana-cabecera">
    <div class="col-12 form-group text-white mb-0">
        <h5 class="modal-title text-white mb-0" id="lb_titulo"></h5>
        <h5 class="modal-title text-white mb-0" id="lb_titulo2"></h5>
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
            <table id="tabla_vistahoras" class="display compact" style="width:100%" style="text-align:center">
                <thead class="tabla-cabecera">
                  <tr style="text-align:center">
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Turno</th>
                      <th style="text-align:center">Hora de inicio</th>
                      <th style="text-align:center">Hora de finalización</th>
                   </tr>
                  </thead>
                </table>     
          </div>
           </div>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>REGISTRO DE HORAS POR AULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group text-danger">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-4 form-group">
            <label for="">Año académico<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_año_editar" style="width:100%" disabled>
            </select>              
          </div>
          <div class="col-4 form-group">
            <label for="">Aula o Grado<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_aula_editar" style="width:100%">
            </select>              
          </div>
          <div class="col-4 form-group">
            <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
              <input type="text" class="form-control" id="txt_nivel_editar" disabled>
          </div>
          <div class="col-4 form-group">
                <label for="">Turno<b class="text-danger">(*)</b>:</label>
                <select class="form-control" id="select_turno_editar" style="width:100%">
                    <option value="">Selecciona un turno</option>
                    <option value="MAÑANA">MAÑANA</option>
                    <option value="TARDE">TARDE</option>
                </select>              
            </div>
            <div class="col-4 form-group">
                <label for="">Hora Inicio<b class="text-danger">(*)</b>:</label>
                <input type="time" class="form-control" id="hora_inicio_editar">
            </div>
            <div class="col-4 form-group">
                <label for="">Hora Fin<b class="text-danger">(*)</b>:</label>
                <input type="time" class="form-control" id="hora_fin_editar">
            </div>

            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_componente_editar()">
                  <i class="fas fa-plus"></i> <b>Agregar Hora</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_aula_hora_editar" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th style="text-align:center">Año</th>
                <th style="text-align:center">Aula</th>
                <th style="text-align:center">Turno</th>
                <th style="text-align:center">Hora Inicio</th>
                <th style="text-align:center">Hora Fin</th>
                <th style="text-align:center">Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody_tabla_aula_hora_editar">
            </tbody>
            </table>  
           
          </div>
          
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_aula_horas()"><i class="fas fa-edit"></i> Modificar</button>
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
    listar_aula_hora();

$(document).ready(function () {
  $('.js-example-basic-single').select2();
  Cargar_Select_Grado();
  Cargar_Año();
  Cargar_Select_Nivelaca();

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
<script>
document.getElementById('select_turno').addEventListener('change', function() {
    const turno = this.value;
    const horaInicio = document.getElementById('hora_inicio');
    const horaFin = document.getElementById('hora_fin');

    if (turno === 'MAÑANA') {
        // Configura el rango para el turno de mañana
        horaInicio.setAttribute('min', '07:00');
        horaInicio.setAttribute('max', '15:00');
        horaFin.setAttribute('min', '07:00');
        horaFin.setAttribute('max', '15:00');
    } else if (turno === 'TARDE') {
        // Configura el rango para el turno de tarde
        horaInicio.setAttribute('min', '15:00');
        horaInicio.setAttribute('max', '20:00');
        horaFin.setAttribute('min', '15:00');
        horaFin.setAttribute('max', '20:00');
    } else {
        // Resetea los valores si no hay turno seleccionado
        horaInicio.removeAttribute('min');
        horaInicio.removeAttribute('max');
        horaFin.removeAttribute('min');
        horaFin.removeAttribute('max');
    }
});

</script>