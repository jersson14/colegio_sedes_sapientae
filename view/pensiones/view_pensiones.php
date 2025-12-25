<script src="../js/console_pensiones.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE PENSIONES</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">PENSIONES</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Pensiones</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:left">
            <div class="card-body">
                <div class="row">
                    
                    <div class="col-4 form-group">
                        <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                        <select class="form-control" id="select_nivel_buscar" style="width:100%">
                        </select>
                    </div>
                    <div class="col-12 col-md-4" role="document">
                        <label for="">&nbsp;</label><br>
                        <button onclick="listar_pensiones_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar pensiones</button>
                    </div>
                    <div class="col-12 col-md-4" role="document">
                        <label for="">&nbsp;</label><br>
                        <button onclick="listar_pensiones()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                    </div>
                </div>
            </div>
        </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_pensiones" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Nivel Académico</th>
                      <th style="text-align:center">Mes</th>
                      <th style="text-align:center">Fecha de Vencimiento</th>
                      <th style="text-align:center">Precio</th>
                      <th style="text-align:center">Mora</th>
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
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE PENSIONES</b></h5>
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
            <label for="">Mes<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_mes" style="width:100%">
                <option value="ENERO">ENERO</option>
                <option value="FEBRERO">FEBRERO</option>
                <option value="MARZO">MARZO</option>
                <option value="ABRIL">ABRIL</option>
                <option value="MAYO">MAYO</option>
                <option value="JUNIO">JUNIO</option>
                <option value="JULIO">JULIO</option>
                <option value="AGOSTO">AGOSTO</option>
                <option value="SEPTIEMBRE">SEPTIEMBRE</option>
                <option value="OCTUBRE">OCTUBRE</option>
                <option value="NOVIEMBRE">NOVIEMBRE</option>
                <option value="DICIEMBRE">DICIEMBRE</option>
              </select>        
          </div>
          <div class="col-4 form-group">
            <label for="">Fecha de venc.<b style="color:red">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_fecha">        
          </div>
          <div class="col-4 form-group">
            <label for="">Precio<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_precio">        
          </div>
          <div class="col-4 form-group">
            <label for="">Mora<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_mora">        
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_pensiones()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR DATOS DE PENSIÓN</b></h5>
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
            <input type="text" id="txt_id_pension" hidden>
            <select class="form-control" id="select_nivel_editar" style="width:100%">
              </select>       

          </div>
          <div class="col-6 form-group">
            <label for="">Mes<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_mes_editar" style="width:100%">
                <option value="ENERO">ENERO</option>
                <option value="FEBRERO">FEBRERO</option>
                <option value="MARZO">MARZO</option>
                <option value="ABRIL">ABRIL</option>
                <option value="MAYO">MAYO</option>
                <option value="JUNIO">JUNIO</option>
                <option value="JULIO">JULIO</option>
                <option value="AGOSTO">AGOSTO</option>
                <option value="SEPTIEMBRE">SEPTIEMBRE</option>
                <option value="OCTUBRE">OCTUBRE</option>
                <option value="NOVIEMBRE">NOVIEMBRE</option>
                <option value="DICIEMBRE">DICIEMBRE</option>
              </select>        
          </div>
          <div class="col-4 form-group">
            <label for="">Fecha de venc.<b style="color:red">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_fecha_editar">        
          </div>
          <div class="col-4 form-group">
            <label for="">Precio<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_precio_editar">        
          </div>
          <div class="col-4 form-group">
            <label for="">Mora<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_mora_editar">        
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Pensiones()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>


<script>
$(document).ready(function () {
    listar_pensiones();
  $('.js-example-basic-single').select2();
  Cargar_Select_Nivelaca();
});
$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_rol').trigger('focus')
})
</script>