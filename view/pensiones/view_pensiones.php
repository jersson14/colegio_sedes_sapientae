<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_pensiones.js?rev=<?php echo time();?>"></script>
<?php
$meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
$opciones_mes = '';
$opciones_num = '';
foreach ($meses as $i => $m) {
    $opciones_mes .= "<option value='$m'>$m</option>";
    $opciones_num .= "<option value='" . ($i + 1) . "'>$m</option>";
}
?>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Pensiones</h1>
        <p class="pagina-ayuda">Montos y vencimientos de las pensiones de cada nivel.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Pensiones</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Pensiones por año escolar y nivel</b></h3>
        <div class="float-right">
          <button class="btn btn-primary" onclick="AbrirGenerar()"><i class="fas fa-calendar-alt"></i> Generar cronograma</button>
          <button class="btn btn-success" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nueva pensión</button>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 form-group">
            <label>Año escolar:</label>
            <select class="form-control" id="select_anio_buscar" onchange="listar_pensiones()"></select>
          </div>
          <div class="col-md-4 form-group">
            <label>Nivel académico:</label>
            <select class="form-control" id="select_nivel_buscar" onchange="listar_pensiones()"></select>
          </div>
        </div>
        <div id="aviso_vencimientos"></div>
        <div class="table-responsive">
          <table id="tabla_pensiones" class="table table-striped table-bordered" style="width:100%">
            <thead class="tabla-cabecera">
              <tr>
                <th style="text-align:center">Nro.</th>
                <th style="text-align:center">Año escolar</th>
                <th style="text-align:center">Nivel Académico</th>
                <th style="text-align:center">Mes</th>
                <th style="text-align:center">Fecha de Vencimiento</th>
                <th style="text-align:center">Precio</th>
                <th style="text-align:center">Mora</th>
                <th style="text-align:center">Pagos</th>
                <th style="text-align:center">Acción</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</div>
<!-- /.content -->

<!-- Registro -->
<div class="modal fade" id="modal_registro" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center"><b>REGISTRO DE PENSIÓN</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group text-danger"><h6><b>Campos Obligatorios (*)</b></h6></div>
          <div class="col-12 form-group">
            <label>Año escolar<b class="text-danger">(*)</b>:</label>
            <select class="form-control select-anio" id="select_anio"></select>
          </div>
          <div class="col-6 form-group">
            <label>Nivel Académico<b class="text-danger">(*)</b>:</label>
            <select class="form-control select-nivel" id="select_nivel" style="width:100%"></select>
          </div>
          <div class="col-6 form-group">
            <label>Mes<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_mes" style="width:100%"><?php echo $opciones_mes; ?></select>
          </div>
          <div class="col-4 form-group">
            <label>Fecha de venc.<b class="text-danger">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_fecha">
          </div>
          <div class="col-4 form-group">
            <label>Precio<b class="text-danger">(*)</b>:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_precio">
          </div>
          <div class="col-4 form-group">
            <label>Mora:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_mora">
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

<!-- Edición -->
<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center"><b>EDITAR DATOS DE PENSIÓN</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group text-danger"><h6><b>Campos Obligatorios (*)</b></h6></div>
          <input type="text" id="txt_id_pension" hidden>
          <div class="col-12 form-group">
            <label>Año escolar<b class="text-danger">(*)</b>:</label>
            <select class="form-control select-anio" id="select_anio_editar"></select>
          </div>
          <div class="col-6 form-group">
            <label>Nivel Académico<b class="text-danger">(*)</b>:</label>
            <select class="form-control select-nivel" id="select_nivel_editar" style="width:100%"></select>
          </div>
          <div class="col-6 form-group">
            <label>Mes<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_mes_editar" style="width:100%"><?php echo $opciones_mes; ?></select>
          </div>
          <div class="col-4 form-group">
            <label>Fecha de venc.<b class="text-danger">(*)</b>:</label>
            <input type="date" class="form-control" id="txt_fecha_editar">
          </div>
          <div class="col-4 form-group">
            <label>Precio<b class="text-danger">(*)</b>:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_precio_editar">
          </div>
          <div class="col-4 form-group">
            <label>Mora:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_mora_editar">
          </div>
          <div class="col-12"><small class="text-muted" id="txt_aviso_pagos"></small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Pensiones()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>

<!-- Generar cronograma -->
<div class="modal fade" id="modal_generar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header tabla-cabecera">
        <h5 class="modal-title text-white"><b>GENERAR CRONOGRAMA DE PENSIONES</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group">
            <label>Año escolar<b class="text-danger">(*)</b>:</label>
            <select class="form-control select-anio" id="select_anio_gen"></select>
          </div>
          <div class="col-12 form-group">
            <label>Nivel Académico<b class="text-danger">(*)</b>:</label>
            <select class="form-control select-nivel" id="select_nivel_gen"></select>
          </div>
          <div class="col-6 form-group">
            <label>Desde el mes<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_desde_gen"><?php echo $opciones_num; ?></select>
          </div>
          <div class="col-6 form-group">
            <label>Hasta el mes<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_hasta_gen"><?php echo $opciones_num; ?></select>
          </div>
          <div class="col-4 form-group">
            <label>Vence el día:</label>
            <select class="form-control" id="select_dia_gen">
              <option value="0">Último día</option>
              <?php for ($d = 1; $d <= 31; $d++) { echo "<option value='$d'>$d</option>"; } ?>
            </select>
          </div>
          <div class="col-4 form-group">
            <label>Precio<b class="text-danger">(*)</b>:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_precio_gen">
          </div>
          <div class="col-4 form-group">
            <label>Mora:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_mora_gen">
          </div>
          <div class="col-12"><small class="text-muted">Crea una pensión por mes en el rango (puede cruzar el fin de año, p. ej. septiembre → junio). Los meses que ya existen no se modifican.</small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-primary" onclick="Generar_Pensiones()"><i class="fas fa-calendar-check"></i> Generar</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
  Iniciar_Pensiones();
});
</script>
