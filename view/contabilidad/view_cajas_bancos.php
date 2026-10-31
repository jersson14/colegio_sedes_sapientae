<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_contabilidad.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Cajas y bancos</h1>
        <p class="pagina-ayuda">Saldos y movimientos de cada caja o cuenta.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Cajas y bancos</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row" id="tarjetas_cuentas"></div>

    <div class="card card-primary card-outline card-outline-tabs">
      <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab_movimientos" role="tab"><i class="fas fa-list"></i> Movimientos</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab_transferencias" role="tab"><i class="fas fa-exchange-alt"></i> Depósitos y transferencias</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab_cierre" role="tab"><i class="fas fa-cash-register"></i> Cierre de caja</a></li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">
          <!-- Movimientos -->
          <div class="tab-pane fade show active" id="tab_movimientos" role="tabpanel">
            <div class="row">
              <div class="col-md-4 form-group"><label>Caja / cuenta:</label><select class="form-control" id="select_cuenta_mov"></select></div>
              <div class="col-md-3 form-group"><label>Desde:</label><input type="date" class="form-control" id="txt_desde_mov"></div>
              <div class="col-md-3 form-group"><label>Hasta:</label><input type="date" class="form-control" id="txt_hasta_mov"></div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Listar_Movimientos()"><i class="fas fa-search"></i> Ver</button></div>
            </div>
            <div class="row text-center mb-2" id="resumen_mov"></div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped" style="width:100%">
                <thead class="tabla-cabecera">
                  <tr><th>Fecha</th><th>Origen</th><th>Descripción</th><th>Referencia</th><th class="text-right">Entrada</th><th class="text-right">Salida</th><th class="text-right">Saldo</th><th>Usuario</th><th title="Conciliado con el extracto bancario">Concil.</th></tr>
                </thead>
                <tbody id="tbody_movimientos"></tbody>
              </table>
            </div>
            <small class="text-muted">Marque "Concil." cuando el movimiento aparezca en el extracto del banco. Los anulados se muestran tachados y no suman.</small>
          </div>

          <!-- Depósitos y transferencias -->
          <div class="tab-pane fade" id="tab_transferencias" role="tabpanel">
            <div class="row">
              <div class="col-md-3 form-group"><label>Tipo<b class="text-danger">(*)</b>:</label>
                <select class="form-control" id="select_tipo_tr">
                  <option value="DEPOSITO">DEPÓSITO (caja → banco)</option>
                  <option value="TRANSFERENCIA">TRANSFERENCIA entre cuentas</option>
                  <option value="RETIRO">RETIRO (banco → caja)</option>
                </select>
              </div>
              <div class="col-md-3 form-group"><label>Desde<b class="text-danger">(*)</b>:</label><select class="form-control" id="select_origen_tr"></select></div>
              <div class="col-md-3 form-group"><label>Hacia<b class="text-danger">(*)</b>:</label><select class="form-control" id="select_destino_tr"></select></div>
              <div class="col-md-3 form-group"><label>Monto<b class="text-danger">(*)</b>:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_monto_tr"></div>
              <div class="col-md-3 form-group"><label>Fecha:</label><input type="date" class="form-control" id="txt_fecha_tr"></div>
              <div class="col-md-3 form-group"><label>N.° operación / voucher:</label><input type="text" class="form-control" id="txt_ref_tr" maxlength="60"></div>
              <div class="col-md-4 form-group"><label>Observación:</label><input type="text" class="form-control" id="txt_obs_tr" maxlength="255"></div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" onclick="Registrar_Transferencia()"><i class="fas fa-save"></i> Registrar</button></div>
            </div>
            <hr>
            <div class="row">
              <div class="col-md-3 form-group"><label>Desde:</label><input type="date" class="form-control" id="txt_desde_tr"></div>
              <div class="col-md-3 form-group"><label>Hasta:</label><input type="date" class="form-control" id="txt_hasta_tr"></div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Listar_Transferencias()"><i class="fas fa-search"></i> Ver</button></div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead class="tabla-cabecera">
                  <tr><th>Fecha</th><th>Tipo</th><th>Desde</th><th>Hacia</th><th class="text-right">Monto</th><th>Referencia</th><th>Observación</th><th>Usuario</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody id="tbody_transferencias"></tbody>
              </table>
            </div>
          </div>

          <!-- Cierre de caja -->
          <div class="tab-pane fade" id="tab_cierre" role="tabpanel">
            <div class="row">
              <div class="col-md-4 form-group"><label>Caja:</label><select class="form-control" id="select_caja_cierre"></select></div>
              <div class="col-md-3 form-group"><label>Fecha:</label><input type="date" class="form-control" id="txt_fecha_cierre"></div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Resumen_Caja()"><i class="fas fa-calculator"></i> Calcular</button></div>
            </div>
            <div class="row" id="panel_cierre" style="display:none">
              <div class="col-md-6">
                <table class="table table-sm table-bordered">
                  <tr><th>Saldo al inicio del día</th><td class="text-right" id="ci_inicial"></td></tr>
                  <tr><th>Entradas del día</th><td class="text-right text-success" id="ci_entradas"></td></tr>
                  <tr><th>Salidas del día</th><td class="text-right text-danger" id="ci_salidas"></td></tr>
                  <tr><th>Saldo esperado según el sistema</th><td class="text-right"><b id="ci_esperado"></b></td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <div class="form-group"><label>Efectivo contado<b class="text-danger">(*)</b>:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_contado"></div>
                <p id="ci_diferencia"></p>
                <div class="form-group"><label>Observación (obligatoria si hay diferencia):</label><input type="text" class="form-control" id="txt_obs_cierre" maxlength="255"></div>
                <div class="custom-control custom-checkbox mb-2">
                  <input type="checkbox" class="custom-control-input" id="chk_ajustar">
                  <label class="custom-control-label" for="chk_ajustar">Registrar la diferencia como ajuste (el saldo del sistema queda igual a lo contado)</label>
                </div>
                <button class="btn btn-success" id="btn_cerrar_caja" onclick="Cerrar_Caja()"><i class="fas fa-lock"></i> Cerrar caja</button>
                <span id="ci_cerrada" class="badge badge-secondary" style="display:none">Esta caja ya fue cerrada en esa fecha</span>
              </div>
            </div>
            <h6 class="mt-3"><b>Cierres anteriores</b></h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead class="tabla-cabecera">
                  <tr><th>Fecha</th><th class="text-right">Inicio</th><th class="text-right">Entradas</th><th class="text-right">Salidas</th><th class="text-right">Esperado</th><th class="text-right">Contado</th><th class="text-right">Diferencia</th><th>Observación</th><th>Usuario</th></tr>
                </thead>
                <tbody id="tbody_cierres"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    Iniciar_Cajas_Bancos();
  });
</script>
