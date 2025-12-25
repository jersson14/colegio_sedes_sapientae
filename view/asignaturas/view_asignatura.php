<script src="../js/console_asignaturas.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE ASIGNATURAS</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">ASIGNATURAS</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Asignaturas</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3 form-group">
                                    <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-3 form-group">
                                    <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_aula_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_asignaturas_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar asignatura</button>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_asignaturas()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_asignaturas" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Asignatura</th>
                      <th style="text-align:center">Grado y Sección</th>
                      <th style="text-align:center">Nivel Academico</th>
                      <th style="text-align:center">Observaciones</th>
                      <th style="text-align:center">Fecha de Registro</th>
                      <th style="text-align:center">Estado</th>
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
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE ASIGNATURA POR GRADO</b></h5>
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
            <label for="">Nombre de Asignatura<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_asignatura">
          </div>
          <div class="col-12 form-group">
              <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_nivel" style="width:100%">
              </select>
            </div>
          <div class="col-12 form-group">
            <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_grado" style="width:100%">
              </select>          
          </div>
          <div class="col-12 form-group">
          <label for="">Observaciones (Opcional):</label>
          <textarea class="form-control" id="txt_observacion" rows="3" style="resize:none" placeholder="Ingrese la dirección"></textarea>
      
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_asignatura()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR DATOS DE LA ASIGNATURA</b></h5>
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
            <label for="">Nombre de Asignatura<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_id_asig" hidden>
            <input type="text" class="form-control" id="txt_asignatura_editar">
          </div>
          <div class="col-12 form-group">
              <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_nivel_editar" style="width:100%">
              </select>
            </div>
          <div class="col-12 form-group">
            <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_grado_editar" style="width:100%">
              </select>          
          </div>
          <div class="col-12 form-group">
            <label for="">Observaciones (Opcional):</label>
            <textarea class="form-control" id="txt_observacion_editar" rows="3" style="resize:none" placeholder="Ingrese la dirección"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Asignatura()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>


<script>
$(document).ready(function () {
    listar_asignaturas();
  $('.js-example-basic-single').select2();
  Cargar_Select_Nivelaca();
  Cargar_Select_Grado();
});


    $("#select_nivel").change(function() {
      var id = $("#select_nivel").val();
      Cargar_Select_Aula(id);
    });

    $("#select_nivel_editar").change(function() {
      var id = $("#select_nivel_editar").val();
      Cargar_Select_Aula(id);
    });
    $("#select_nivel_buscar").change(function() {
      var id = $("#select_nivel_buscar").val();
      Cargar_Select_Aula(id);
    });
$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_rol').trigger('focus')
})
</script>