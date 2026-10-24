<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_planilla.js?rev=<?php echo time();?>"></script>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>PLANILLA DOCENTE</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">PLANILLA DOCENTE</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline card-outline-tabs">
      <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab_planillas" role="tab"><i class="fas fa-file-invoice-dollar"></i> Planillas</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab_contratos" role="tab"><i class="fas fa-file-signature"></i> Contratos</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab_adelantos" role="tab"><i class="fas fa-hand-holding-usd"></i> Adelantos</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab_conceptos" role="tab"><i class="fas fa-list"></i> Conceptos</a></li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">
          <!-- Planillas -->
          <div class="tab-pane fade show active" id="tab_planillas" role="tabpanel">
            <div class="row">
              <div class="col-md-2 form-group"><label>Año:</label><input type="number" class="form-control" id="txt_anio_pla"></div>
              <div class="col-md-3 form-group"><label>Mes a generar:</label>
                <select class="form-control" id="select_mes_pla">
                  <option value="1">ENERO</option><option value="2">FEBRERO</option><option value="3">MARZO</option><option value="4">ABRIL</option>
                  <option value="5">MAYO</option><option value="6">JUNIO</option><option value="7">JULIO</option><option value="8">AGOSTO</option>
                  <option value="9">SEPTIEMBRE</option><option value="10">OCTUBRE</option><option value="11">NOVIEMBRE</option><option value="12">DICIEMBRE</option>
                </select>
              </div>
              <div class="col-md-3 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" onclick="Generar_Planilla()"><i class="fas fa-cogs"></i> Generar planilla del mes</button></div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Listar_Planillas()"><i class="fas fa-search"></i> Ver año</button></div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr><th>Mes</th><th>Docentes</th><th class="text-right">Bruto</th><th class="text-right">Descuentos</th><th class="text-right">Neto</th><th class="text-right">Pagado</th><th>Estado</th><th>Aprobada por</th><th>Acción</th></tr>
                </thead>
                <tbody id="tbody_planillas"></tbody>
              </table>
            </div>
            <div id="panel_detalle" style="display:none">
              <hr>
              <h5><b id="titulo_detalle"></b> <span id="estado_detalle"></span>
                <span class="float-right" id="botones_detalle"></span></h5>
              <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                  <thead style="background-color:#0A5D86;color:#FFFFFF;">
                    <tr><th>Docente</th><th>Contrato</th><th class="text-right">Horas</th><th class="text-right">Base</th><th class="text-right">Ingresos</th><th class="text-right">Descuentos</th><th class="text-right">Adelantos</th><th class="text-right">Neto</th><th class="text-right">Pagado</th><th>Forma de pago</th><th>Acción</th></tr>
                  </thead>
                  <tbody id="tbody_detalle"></tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Contratos -->
          <div class="tab-pane fade" id="tab_contratos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr><th>Docente</th><th>Contrato</th><th>Desde</th><th class="text-right">Sueldo / tarifa</th><th class="text-right">Horas/sem.</th><th>Forma de pago</th><th>Cuenta del docente</th><th class="text-right">Adelantos pendientes</th><th>Acción</th></tr>
                </thead>
                <tbody id="tbody_contratos"></tbody>
              </table>
            </div>
          </div>

          <!-- Adelantos -->
          <div class="tab-pane fade" id="tab_adelantos" role="tabpanel">
            <div class="row">
              <div class="col-md-4 form-group"><label>Docente<b style="color:red">(*)</b>:</label><select class="form-control" id="select_docente_ade"></select></div>
              <div class="col-md-2 form-group"><label>Monto<b style="color:red">(*)</b>:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_monto_ade"></div>
              <div class="col-md-2 form-group"><label>Fecha:</label><input type="date" class="form-control" id="txt_fecha_ade"></div>
              <div class="col-md-2 form-group"><label>Forma de pago:</label><select class="form-control" id="select_metodo_ade"></select></div>
              <div class="col-md-2 form-group"><label>Sale de:</label><select class="form-control" id="select_cuenta_ade"></select></div>
              <div class="col-md-3 form-group"><label>N.° operación:</label><input type="text" class="form-control" id="txt_ref_ade" maxlength="60"></div>
              <div class="col-md-6 form-group"><label>Observación:</label><input type="text" class="form-control" id="txt_obs_ade" maxlength="255"></div>
              <div class="col-md-3 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" onclick="Registrar_Adelanto()"><i class="fas fa-save"></i> Registrar adelanto</button></div>
            </div>
            <hr>
            <div class="row">
              <div class="col-md-3 form-group"><label>Desde:</label><input type="date" class="form-control" id="txt_desde_ade"></div>
              <div class="col-md-3 form-group"><label>Hasta:</label><input type="date" class="form-control" id="txt_hasta_ade"></div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Listar_Adelantos()"><i class="fas fa-search"></i> Ver</button></div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr><th>Fecha</th><th>Docente</th><th class="text-right">Monto</th><th>Forma de pago</th><th>Referencia</th><th>Observación</th><th>Situación</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody id="tbody_adelantos"></tbody>
              </table>
            </div>
          </div>

          <!-- Conceptos -->
          <div class="tab-pane fade" id="tab_conceptos" role="tabpanel">
            <button class="btn btn-success mb-2" onclick="Abrir_Concepto()"><i class="fas fa-plus"></i> Nuevo concepto</button>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr><th>Concepto</th><th>Tipo</th><th>Forma</th><th class="text-right">Valor por defecto</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody id="tbody_conceptos"></tbody>
              </table>
            </div>
            <small class="text-muted">Un concepto con valor por defecto mayor a 0 se aplica a todos los docentes al generar la planilla (p. ej. el porcentaje del aporte de ley de su país).</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Contrato -->
<div class="modal fade" id="modal_contrato" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" style="color:white"><b id="lb_contrato">CONTRATO</b></h5>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="txt_id_contrato"><input type="hidden" id="txt_docente_contrato">
      <div class="alert alert-info" id="aviso_contrato" style="display:none">Al registrar un contrato nuevo, el actual queda finalizado el día anterior a la nueva fecha de inicio.</div>
      <div class="row">
        <div class="col-md-4 form-group"><label>Tipo de contrato<b style="color:red">(*)</b>:</label><select class="form-control" id="select_tipo_contrato"></select></div>
        <div class="col-md-4 form-group"><label>Fecha de inicio<b style="color:red">(*)</b>:</label><input type="date" class="form-control" id="txt_inicio_contrato"></div>
        <div class="col-md-4 form-group grupo-mensual"><label>Sueldo mensual<b style="color:red">(*)</b>:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_sueldo_contrato"></div>
        <div class="col-md-4 form-group grupo-hora"><label>Tarifa por hora<b style="color:red">(*)</b>:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_tarifa_contrato"></div>
        <div class="col-md-4 form-group"><label>Horas semanales:</label><input type="number" step="0.5" min="0" class="form-control" id="txt_horas_contrato"></div>
        <div class="col-md-4 form-group"><label>Forma de pago<b style="color:red">(*)</b>:</label><select class="form-control" id="select_metodo_contrato"></select></div>
        <div class="col-md-4 form-group"><label>Banco del docente:</label><input type="text" class="form-control" id="txt_banco_contrato" maxlength="100"></div>
        <div class="col-md-4 form-group"><label>N.° de cuenta del docente:</label><input type="text" class="form-control" id="txt_cuenta_contrato" maxlength="40"></div>
        <div class="col-md-12 form-group"><label>Observación:</label><input type="text" class="form-control" id="txt_obs_contrato" maxlength="255"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline-danger mr-auto" id="btn_finalizar_contrato" onclick="Finalizar_Contrato()"><i class="fas fa-stop"></i> Finalizar contrato</button>
      <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
      <button type="button" class="btn btn-success" onclick="Guardar_Contrato()"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div></div>
</div>

<!-- Editar detalle (borrador) -->
<div class="modal fade" id="modal_detalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" style="color:white"><b id="lb_detalle"></b></h5>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="txt_id_detalle">
      <div class="row">
        <div class="col-md-4 form-group" id="grupo_horas_det"><label>Horas dictadas en el mes:</label><input type="number" step="0.5" min="0" class="form-control" id="txt_horas_det"></div>
        <div class="col-md-4 form-group" id="grupo_base_det"><label>Sueldo base del mes:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_base_det"></div>
        <div class="col-md-4 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Actualizar_Detalle()"><i class="fas fa-sync"></i> Actualizar</button></div>
      </div>
      <h6><b>Ingresos y descuentos</b></h6>
      <div class="row">
        <div class="col-md-6 form-group"><select class="form-control" id="select_concepto_det"></select></div>
        <div class="col-md-3 form-group"><input type="number" step="0.01" min="0" class="form-control" id="txt_valor_det" placeholder="Monto o %"></div>
        <div class="col-md-3 form-group"><button class="btn btn-success btn-block" onclick="Agregar_Concepto_Detalle()"><i class="fas fa-plus"></i> Agregar</button></div>
      </div>
      <table class="table table-sm table-bordered"><tbody id="tbody_conceptos_det"></tbody></table>
      <p class="text-right" id="resumen_det"></p>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button></div>
  </div></div>
</div>

<!-- Pagar -->
<div class="modal fade" id="modal_pagar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#28a745;"><h5 class="modal-title" style="color:white"><b id="lb_pagar"></b></h5>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="txt_id_pagar">
      <div class="row">
        <div class="col-md-3 form-group"><label>Monto<b style="color:red">(*)</b>:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_monto_pagar_doc"></div>
        <div class="col-md-3 form-group"><label>Fecha:</label><input type="date" class="form-control" id="txt_fecha_pagar_doc"></div>
        <div class="col-md-3 form-group"><label>Forma de pago:</label><select class="form-control" id="select_metodo_pagar_doc"></select></div>
        <div class="col-md-3 form-group"><label>Sale de:</label><select class="form-control" id="select_cuenta_pagar_doc"></select></div>
        <div class="col-md-6 form-group"><label>N.° operación:</label><input type="text" class="form-control" id="txt_ref_pagar_doc" maxlength="60"></div>
        <div class="col-md-6 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" onclick="Pagar_Detalle()"><i class="fas fa-money-bill-wave"></i> Registrar pago</button></div>
      </div>
      <h6><b>Pagos de este mes</b></h6>
      <table class="table table-sm table-bordered"><thead><tr><th>Fecha</th><th class="text-right">Monto</th><th>Forma de pago</th><th>Sale de</th><th>Referencia</th><th>Estado</th><th></th></tr></thead><tbody id="tbody_pagos_det"></tbody></table>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button></div>
  </div></div>
</div>

<!-- Concepto -->
<div class="modal fade" id="modal_concepto" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" style="color:white"><b>CONCEPTO DE PLANILLA</b></h5>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="txt_id_concepto">
      <div class="form-group"><label>Nombre<b style="color:red">(*)</b>:</label><input type="text" class="form-control" id="txt_nombre_concepto" maxlength="80"></div>
      <div class="row">
        <div class="col-6 form-group"><label>Tipo:</label><select class="form-control" id="select_tipo_concepto"><option value="INGRESO">INGRESO</option><option value="DESCUENTO">DESCUENTO</option></select></div>
        <div class="col-6 form-group"><label>Forma:</label><select class="form-control" id="select_forma_concepto"><option value="MONTO">MONTO FIJO</option><option value="PORCENTAJE">PORCENTAJE DEL SUELDO</option></select></div>
        <div class="col-6 form-group"><label>Valor por defecto:</label><input type="number" step="0.01" min="0" class="form-control" id="txt_valor_concepto" value="0"></div>
        <div class="col-6 form-group"><label>Estado:</label><select class="form-control" id="select_estado_concepto"><option value="ACTIVO">ACTIVO</option><option value="INACTIVO">INACTIVO</option></select></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
      <button type="button" class="btn btn-success" onclick="Guardar_Concepto()"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div></div>
</div>

<script>
  $(document).ready(function() {
    Iniciar_Planilla();
  });
</script>
