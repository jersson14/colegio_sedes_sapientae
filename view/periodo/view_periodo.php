<script src="../js/console_periodo.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE PERIODOS</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">PERIODOS</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Periodos</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_periodo" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Año escolar</th>
                      <th style="text-align:center">Tipo Periodo</th>
                      <th style="text-align:center">Mostrar Periodos</th>

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
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE PERIODO ACADÉMICO</b></h5>
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
            <label for="">Año escolar<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_año" style="width:100%">
                </select>           
            </div>
            <div class="col-6 form-group">
                <label for="">Tipo de Periodo<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="tipo_periodo" style="width:100%">
                    <option value="BIMESTRE">BIMESTRE</option>
                    <option value="TRIMESTRE">TRIMESTRE</option>
                    <option value="CUATRIMESTRE">CUATRIMESTRE</option>
                    <option value="SEMESTRE">SEMESTRE</option>
                </select>            
            </div>
            <div class="col-4 form-group">
                <label for="">Periodo<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="periodo" style="width:100%">
                    <!-- Las opciones se generarán dinámicamente con JavaScript -->
                </select>            
            </div>
            <div class="col-4 form-group">
                <label for="">Fecha de inicio<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_inicio">
            </div>
            <div class="col-4 form-group">
                <label for="">Fecha de finalización<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_fin">
            </div>
            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_componente()">
                  <i class="fas fa-plus"></i> <b>Agregar Componente</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_perio" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th>Año.</th>
                <th>Tipo Periodo</th>
                <th>Periodo</th>
                <th>F. Inicio</th>
                <th>F. Fin</th>

                <th>Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody_tabla_periodo">
            </tbody>
            </table>  
           
          
      </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_Periodos()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR PERIODO ACADÉMICO</b></h5>
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
            <label for="">Año escolar<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_año_editar" style="width:100%" disabled>
                </select>           
            </div>
            <div class="col-6 form-group">
                <label for="">Tipo de Periodo<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="tipo_periodo_editar" style="width:100%" disabled>
                    <option value="BIMESTRE">BIMESTRE</option>
                    <option value="TRIMESTRE">TRIMESTRE</option>
                    <option value="CUATRIMESTRE">CUATRIMESTRE</option>
                    <option value="SEMESTRE">SEMESTRE</option>
                </select>            
            </div>
            <div class="col-4 form-group">
                <label for="">Periodo<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="periodo_editar" style="width:100%" >
                    <!-- Las opciones se generarán dinámicamente con JavaScript -->
                </select>            
            </div>
            <div class="col-4 form-group">
                <label for="">Fecha de inicio<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_inicio_editar">
            </div>
            <div class="col-4 form-group">
                <label for="">Fecha de finalización<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_fin_editar">
            </div>
            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_componente_editar()">
                  <i class="fas fa-plus"></i> <b>Agregar Componente</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_perio_editar" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th style="text-align:center">Año.</th>
                <th style="text-align:center">Tipo Periodo</th>
                <th style="text-align:center">Periodo</th>
                <th style="text-align:center">F. Inicio</th>
                <th style="text-align:center">F. Fin</th>
                <th style="text-align:center">Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody_tabla_periodo_editar">
            </tbody>
            </table>  
           
          
      </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Periodos()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_ver_perio" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
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
            <table id="tabla_vistacomp" class="display compact" style="width:100%" style="text-align:center">
                <thead style="background-color:#0A5D86;color:#FFFFFF; ">
                  <tr style="text-align:center">
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Periodo</th>
                      <th style="text-align:center">Fecha Inicio</th>
                      <th style="text-align:center">Fecha Finalización</th>
                      <th style="text-align:center">Estado</th>
                  </tr>
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

<script>
$(document).ready(function () {
    listar_periodo();
    Cargar_Año();

});
$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_nivel_academico').trigger('focus')
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

document.getElementById('txt_fecha_inicio').value = firstDay;
document.getElementById('txt_fecha_fin').value = lastDayFormatted;

document.getElementById('txt_fecha_inicio_editar').value = firstDay;
document.getElementById('txt_fecha_fin_editar').value = lastDayFormatted;
</script>
<script>
$(document).ready(function() {
    const opcionesPorTipo = {
      BIMESTRE: ["I BIMESTRE", "II BIMESTRE", "III BIMESTRE", "IV BIMESTRE"],
        TRIMESTRE: ["I TRIMESTRE", "II TRIMESTRE", "III TRIMESTRE"],
        CUATRIMESTRE: ["I CUATRIMESTRE", "II CUATRIMESTRE", "III CUATRIMESTRE", "IV CUATRIMESTRE"],
        SEMESTRE: ["I SEMESTRE", "II SEMESTRE"]
    };

    function actualizarOpcionesPeriodo(tipoPeriodo) {
        $('#periodo').empty(); // Limpiar las opciones actuales
        opcionesPorTipo[tipoPeriodo].forEach(function(opcion) {
            $('#periodo').append(new Option(opcion, opcion)); // Agregar cada nueva opción
        });
    }

    $('#tipo_periodo').change(function() {
        actualizarOpcionesPeriodo($(this).val());
    });

    // Inicializar con las opciones correspondientes al valor inicial del select de "Tipo de Periodo"
    actualizarOpcionesPeriodo($('#tipo_periodo').val());
});
</script>
<script>
$(document).ready(function() {
    const opcionesPorTipo = {
        BIMESTRE: ["I BIMESTRE", "II BIMESTRE", "III BIMESTRE", "IV BIMESTRE"],
        TRIMESTRE: ["I TRIMESTRE", "II TRIMESTRE", "III TRIMESTRE"],
        CUATRIMESTRE: ["I CUATRIMESTRE", "II CUATRIMESTRE", "III CUATRIMESTRE", "IV CUATRIMESTRE"],
        SEMESTRE: ["I SEMESTRE", "II SEMESTRE"]
    };

    function actualizarOpcionesPeriodo(tipoPeriodo) {
        $('#periodo_editar').empty(); // Limpiar las opciones actuales
        opcionesPorTipo[tipoPeriodo].forEach(function(opcion) {
            $('#periodo_editar').append(new Option(opcion, opcion)); // Agregar cada nueva opción
        });
    }

    $('#tipo_periodo_editar').change(function() {
        actualizarOpcionesPeriodo($(this).val());
    });

    // Inicializar con las opciones correspondientes al valor inicial del select de "Tipo de Periodo"
    actualizarOpcionesPeriodo($('#tipo_periodo_editar').val());
});
</script>