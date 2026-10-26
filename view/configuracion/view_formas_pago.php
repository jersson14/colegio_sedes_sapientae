<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_formas_pago.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Formas de pago y cuentas</h1>
        <p class="pagina-ayuda">Medios de pago y cuentas donde ingresa el dinero.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Formas de pago y cuentas</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-university"></i>&nbsp;&nbsp;<b>Cajas y cuentas del colegio</b></h3>
            <button class="btn btn-success float-right" onclick="Abrir_Cuenta()"><i class="fas fa-plus"></i> Nueva caja / cuenta</button>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-striped table-bordered table-sm" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                <tr><th>Tipo</th><th>Nombre</th><th>Banco</th><th>N.° de cuenta</th><th>CCI / CBU</th><th>Saldo inicial</th><th>Cobrado</th><th>Estado</th><th>Acción</th></tr>
              </thead>
              <tbody id="tbody_cuentas"></tbody>
            </table>
            <small class="text-muted">"Cobrado" es lo recibido en pagos de pensiones y matrícula. Los movimientos completos (egresos, depósitos y saldo) se ven en el módulo de contabilidad.</small>
          </div>
        </div>
      </div>
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-credit-card"></i>&nbsp;&nbsp;<b>Métodos de pago</b></h3>
            <button class="btn btn-success float-right" onclick="Abrir_Metodo()"><i class="fas fa-plus"></i> Nuevo método</button>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-striped table-bordered table-sm" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                <tr><th>Nombre</th><th>Tipo</th><th>Pide n.° de operación</th><th>Pide caja/cuenta</th><th>Comisión %</th><th>Usos</th><th>Estado</th><th>Acción</th></tr>
              </thead>
              <tbody id="tbody_metodos"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_cuenta" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="lb_titulo_cuenta" style="color:white; text-align:center"><b>CAJA / CUENTA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="txt_id_cuenta">
        <div class="row">
          <div class="col-md-4 form-group">
            <label>Tipo<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_tipo_cuenta">
              <option value="CAJA">CAJA (efectivo)</option>
              <option value="BANCO">CUENTA BANCARIA</option>
              <option value="BILLETERA">BILLETERA DIGITAL</option>
            </select>
          </div>
          <div class="col-md-8 form-group">
            <label>Nombre<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_nombre_cuenta" maxlength="100" placeholder="Ej. BANCO X CUENTA CORRIENTE">
          </div>
          <div class="col-md-4 form-group">
            <label>Banco / entidad:</label>
            <input type="text" class="form-control" id="txt_banco_cuenta" maxlength="100">
          </div>
          <div class="col-md-4 form-group">
            <label>N.° de cuenta / celular:</label>
            <input type="text" class="form-control" id="txt_numero_cuenta" maxlength="40">
          </div>
          <div class="col-md-4 form-group">
            <label>Código interbancario (CCI / CBU / IBAN):</label>
            <input type="text" class="form-control" id="txt_cci_cuenta" maxlength="40">
          </div>
          <div class="col-md-6 form-group">
            <label>Titular:</label>
            <input type="text" class="form-control" id="txt_titular_cuenta" maxlength="150">
          </div>
          <div class="col-md-3 form-group">
            <label>Saldo inicial:</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_saldo_cuenta" value="0">
          </div>
          <div class="col-md-3 form-group">
            <label>Fecha del saldo:</label>
            <input type="date" class="form-control" id="txt_fecha_saldo_cuenta">
          </div>
          <div class="col-md-4 form-group">
            <label>Estado:</label>
            <select class="form-control" id="select_estado_cuenta"><option value="ACTIVO">ACTIVO</option><option value="INACTIVO">INACTIVO</option></select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Guardar_Cuenta()"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_metodo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="lb_titulo_metodo" style="color:white; text-align:center"><b>MÉTODO DE PAGO</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="txt_id_metodo">
        <div class="row">
          <div class="col-12 form-group">
            <label>Nombre<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_nombre_metodo" maxlength="60" placeholder="Ej. YAPE, PLIN, NEQUI, MERCADO PAGO">
          </div>
          <div class="col-md-6 form-group">
            <label>Tipo<b style="color:red">(*)</b>:</label>
            <select class="form-control" id="select_tipo_metodo">
              <option>EFECTIVO</option><option>TARJETA</option><option>TRANSFERENCIA</option><option>DEPOSITO</option>
              <option>BILLETERA</option><option>CHEQUE</option><option>OTRO</option>
            </select>
          </div>
          <div class="col-md-6 form-group">
            <label>Comisión (%):</label>
            <input type="text" inputmode="decimal" class="form-control" id="txt_comision_metodo" value="0">
          </div>
          <div class="col-12 form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="chk_ref_metodo">
              <label class="custom-control-label" for="chk_ref_metodo">Pedir n.° de operación / referencia</label>
            </div>
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="chk_cta_metodo" checked>
              <label class="custom-control-label" for="chk_cta_metodo">Pedir la caja o cuenta donde ingresa el dinero</label>
            </div>
          </div>
          <div class="col-md-6 form-group">
            <label>Estado:</label>
            <select class="form-control" id="select_estado_metodo"><option value="ACTIVO">ACTIVO</option><option value="INACTIVO">INACTIVO</option></select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Guardar_Metodo()"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    Listar_Formas_Pago();
  });
</script>
