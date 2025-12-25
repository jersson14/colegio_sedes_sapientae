<script src="../js/console_atencion_psicologica.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE ATENCIÓN PSICOLÓGICA</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">PSICOLOGÍA</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Atenciones Psicológicas</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-2 form-group">
                                    <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_aula" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Fecha inicio</label><b style="color:red">(*)</b>:</label>
                                    <input type="date" class="form-control" id="txtfechainicio">

                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Fecha final<b style="color:red">(*)</b>:</label>
                                    <input type="date" class="form-control" id="txtfechafin">

                                </div>
                                <div class="col-12 col-md-2" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_atencion_psicologica_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar estudiantes</button>
                                </div>
                                <div class="col-12 col-md-2" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_atencion_psicologica()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_psicologia" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">DNI</th>
                      <th style="text-align:center">Estudiante</th>
                      <th style="text-align:center">Sexo</th>
                      <th style="text-align:center">Aula</th>
                      <th style="text-align:center">Nivel Academico</th>
                      <th style="text-align:center">Motivo</th>
                      <th style="text-align:center">Fecha de Registro</th>
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
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE ATENCIÓN PSICOLÓGICA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-12 form-group">
            <label for="">Estudiante<b style="color:red">(*)</b>:</label>
            <select class="js-example-basic-single" id="txt_estudiante" style="width:100%">
            </select>           
        </div>
          <div class="col-12 form-group">
            <label for="">Motivo de consulta<b style="color:red">(*)</b>:</label>
            <textarea name="" id="txt_motivo" rows="3" class="form-control" style="resize:none;"></textarea>
      
          </div>
          <div class="col-12 form-group">
            <label for="">Diagnostico (opcional):</label>
            <textarea name="" id="txt_diagnostico" rows="3" class="form-control" style="resize:none;"></textarea>
          
          </div>
          <div class="col-12 form-group">
            <label for="">Observaciones (opcional):</b>:</label>
            <textarea name="" id="txt_observacion" rows="3" class="form-control" style="resize:none;"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_atencion()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>MODIFICAR LA ATENCIÓN PSICOLÓGICA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-12 form-group">
            <label for="">Estudiante<b style="color:red">(*)</b>:</label>
            <input type="text" id="txt_id_atencion" hidden>
            <select class="js-example-basic-single" id="txt_estudiante_editar" style="width:100%">
            </select>         
         </div>
          <div class="col-12 form-group">
            <label for="">Motivo de consulta<b style="color:red">(*)</b>:</label>
            <textarea name="" id="txt_motivo_editar" rows="3" class="form-control" style="resize:none;" rows="2" style="resize:none" ></textarea>
          </div>
          <div class="col-12 form-group">
            <label for="">Diagnostico (Opcional):</label>
            <textarea name="" id="txt_diagnostico_editar" rows="3" class="form-control" style="resize:none;" rows="2" style="resize:none" ></textarea>
          
          </div>
          <div class="col-12 form-group">
            <label for="">Observaciones (Opcional):</b>:</label>
            <textarea name="" id="txt_observacion_editar" rows="3" class="form-control" style="resize:none;" rows="2" style="resize:none" ></textarea>
          </div>
          
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Atencion()"><i class="fas fa-save"></i> Registrar</button>

      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="modal_mas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>DATOS DE ATENCIÓN PSICOLÓGICA</b></h5>
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
            <label for="">Estudiante:</label>
            <input type="text" class="form-control" id="txt_estudiante_mas" disabled>
       
        </div>
        <div class="col-6 form-group">
            <label for="">Psicologa que atendio:</label>
            <input type="text" class="form-control" id="txt_psicologa_mas" disabled>
          </div>
          <div class="col-12 form-group">
            <label for="">Motivo de consulta:</label>
            <textarea name="" id="txt_motivo_mas" rows="3" class="form-control" style="resize:none;" rows="2" style="resize:none" disabled></textarea>
          </div>
          <div class="col-12 form-group">
            <label for="">Diagnostico:</label>
            <textarea name="" id="txt_diagnostico_mas" rows="3" class="form-control" style="resize:none;" rows="2" style="resize:none" disabled></textarea>
          
          </div>
          <div class="col-12 form-group">
            <label for="">Observaciones:</b>:</label>
            <textarea name="" id="txt_observacion_mas" rows="3" class="form-control" style="resize:none;" rows="2" style="resize:none" disabled></textarea>
          </div>
          <div class="col-12 form-group">
            <label for="">Fecha de ultima actualización:</label>
            <input type="datetime-local" class="form-control" id="txt_fecha_actua_mas" disabled>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
  function initializeSelect2(selector) {
  $(selector).select2({
    dropdownParent: $('#modal_registro'),
    width: '100%',
    language: {
      noResults: function() {
        return "No se encontraron resultados";
      },
      searching: function() {
        return "Buscando...";
      }
    },
    debug: true, // Habilita el modo de depuración de Select2
    matcher: function(params, data) {
      // Implementa tu propia lógica de búsqueda si es necesario
      if ($.trim(params.term) === '') {
        return data;
      }

      if (typeof data.text === 'undefined') {
        return null;
      }

      if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        return data;
      }

      return null;
    }
  }).on('select2:open', function(e) {
    console.log('Select2 abierto:', e.target.id);
  }).on('select2:closing', function(e) {
    console.log('Select2 cerrándose:', e.target.id);
  }).on('select2:select', function(e) {
    console.log('Opción seleccionada:', e.params.data);
  });
}
$(document).ready(function () {
    listar_atencion_psicologica();
  $('.js-example-basic-single').select2();
  Cargar_Select_Matriculados();
  Cargar_Select_Nivelaca();
});
$("#select_nivel").change(function() {
      var id = $("#select_nivel").val();
      Cargar_Select_Aula(id);
    });
$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_motivo').trigger('focus')
})

var n = new Date();
var y= n.getFullYear();
var m= n.getMonth()+1;
var d= n.getDate();
if(d<10){
    d='0' + d;
}
if(m<10){
    m='0' + m;

}
document.getElementById('txtfechainicio').value = y + "-" + m + "-" + d;
document.getElementById('txtfechafin').value = y + "-" + m + "-" + d;
</script>