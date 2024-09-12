<script src="../js/console_año_escolar.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE AÑOS ACADÉMICOS</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">AÑOS ACADÉMICOS</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Años</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_año_escolar" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Año escolar</th>
                      <th style="text-align:center">Nombre</th>
                      <th style="text-align:center">Fecha de Inicio</th>
                      <th style="text-align:center">Fecha de Finalización</th>
                      <th style="text-align:center">Descripción</th>
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
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO AÑO ACADÉMICO</b></h5>
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
            <label for="">Año escolar<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_año" onkeypress="return soloNumeros(event)">
          </div>
          <div class="col-8 form-group">
            <label for="">Nombre de año<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_nombre" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Fecha de inicio<b style="color:red">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_inicio" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Fecha de Finalización<b style="color:red">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_fin" onkeypress="return sololetras(event)">
          </div>
          <div class="col-12 form-group">
            <label for="">Descripción<b style="color:red">(*)</b>:</label>
            <textarea name="" id="txt_descripcion" rows="3" class="form-control" style="resize:none;"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_Año()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR DATOS DEL AÑO ACADÉMICO</b></h5>
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
            <label for="">Año escolar<b style="color:red">(*)</b>:</label>
            <input type="text" id="txt_idaño" hidden>
            <input type="text" class="form-control" id="txt_año_editar" onkeypress="return soloNumeros(event)">
          </div>
          <div class="col-8 form-group">
            <label for="">Nombre de año<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_nombre_editar" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Fecha de inicio<b style="color:red">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_inicio_editar" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Fecha de Finalización<b style="color:red">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_fin_editar" onkeypress="return sololetras(event)">
          </div>
          <div class="col-12 form-group">
            <label for="">Descripción<b style="color:red">(*)</b>:</label>
            <textarea name="" id="txt_descripcion_editar" rows="3" class="form-control" style="resize:none;"></textarea>
          </div>
          <div class="col-12 form-group">
            <label for="">Estado<b style="color:red">(*)</b>:</label>
              <select name="" id="txt_estatus" class="form-control">
                <option value="ACTIVO">ACTIVO</option>
                <option value="INACTIVO">INACTIVO</option>
              </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Año()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>


<script>
$(document).ready(function () {
    listar_año_escolar();
  
});
$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_rol').trigger('focus')
})
</script>