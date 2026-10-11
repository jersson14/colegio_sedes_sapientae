<script src="../js/console_facturacion.js?rev=<?php echo time(); ?>"></script>
<!-- Módulo opcional: facturación electrónica con la cuenta de Nubefact de la institución. -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>FACTURACIÓN ELECTRÓNICA</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">FACTURACIÓN ELECTRÓNICA</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-outline card-secondary collapsed-card" id="fe_tarjeta_configuracion">
      <div class="card-header">
        <h3 class="card-title"><b>Cuenta de Nubefact</b> <span id="fe_estado_cuenta" class="badge badge-secondary ml-2">Sin configurar</span></h3>
        <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button></div>
      </div>
      <div class="card-body">
        <p class="text-muted">En tu cuenta de Nubefact, «API (Integración)», copia la RUTA y el TOKEN. El token se guarda cifrado y no se vuelve a mostrar.</p>
        <div class="row">
          <div class="col-md-6 form-group"><label for="fe_ruta">Ruta</label><input class="form-control" id="fe_ruta" placeholder="https://api.nubefact.com/api/v1/…" autocomplete="off"></div>
          <div class="col-md-6 form-group"><label for="fe_token">Token</label><input class="form-control" id="fe_token" type="password" autocomplete="new-password" placeholder="(se conserva el actual si lo dejas vacío)"></div>
          <div class="col-md-2 form-group"><label for="fe_serie_boleta">Serie boleta</label><input class="form-control" id="fe_serie_boleta" maxlength="4"></div>
          <div class="col-md-2 form-group"><label for="fe_ultimo_boleta">Último N.º boleta</label><input class="form-control" id="fe_ultimo_boleta" type="number" min="0" title="Si ya emitías con esta serie, el último número usado"></div>
          <div class="col-md-2 form-group"><label for="fe_serie_factura">Serie factura</label><input class="form-control" id="fe_serie_factura" maxlength="4"></div>
          <div class="col-md-2 form-group"><label for="fe_ultimo_factura">Último N.º factura</label><input class="form-control" id="fe_ultimo_factura" type="number" min="0"></div>
          <div class="col-md-2 form-group"><label for="fe_afectacion">IGV</label>
            <select class="form-control" id="fe_afectacion"><option value="EXONERADO">Exonerado</option><option value="INAFECTO">Inafecto</option><option value="GRAVADO">Gravado (18 %)</option></select></div>
          <div class="col-md-2 form-group"><label for="fe_enviar_cliente">Enviar por correo</label>
            <select class="form-control" id="fe_enviar_cliente"><option value="0">No</option><option value="1">Sí, al cliente</option></select></div>
        </div>
        <small class="text-muted d-block mb-2">Los servicios educativos suelen estar exonerados del IGV: confírmalo con tu contador.</small>
        <button class="btn btn-primary" id="fe_guardar_configuracion"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><b>Ingresos sin comprobante</b></h3></div>
      <div class="card-body table-responsive">
        <table class="table table-sm table-bordered" id="tabla_fe_pendientes">
          <thead style="background-color:#023D77;color:white;"><tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Cliente sugerido</th><th></th></tr></thead>
          <tbody><tr><td colspan="5" class="text-muted">Cargando…</td></tr></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><b>Comprobantes emitidos</b></h3></div>
      <div class="card-body table-responsive">
        <table class="table table-sm table-bordered" id="tabla_fe_emitidos">
          <thead style="background-color:#023D77;color:white;"><tr><th>Número</th><th>Fecha</th><th>Cliente</th><th>Descripción</th><th>Total</th><th>Estado</th><th></th></tr></thead>
          <tbody><tr><td colspan="7" class="text-muted">Cargando…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_fe_emitir" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="fe_titulo_emitir">Emitir comprobante</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
      <div class="modal-body">
        <p class="mb-2" id="fe_detalle_ingreso"></p>
        <div class="row">
          <div class="col-5 form-group"><label for="fe_tipo_doc">Documento</label>
            <select class="form-control" id="fe_tipo_doc"><option value="1">DNI</option><option value="6">RUC</option><option value="4">Carné de extranjería</option><option value="-">Sin documento</option></select></div>
          <div class="col-7 form-group"><label for="fe_doc">Número</label><input class="form-control" id="fe_doc" maxlength="12"></div>
          <div class="col-12 form-group"><label for="fe_nombre">Nombre o razón social</label><input class="form-control" id="fe_nombre" maxlength="200"></div>
          <div class="col-12 form-group"><label for="fe_direccion">Dirección <small class="text-muted">(obligatoria en factura)</small></label><input class="form-control" id="fe_direccion" maxlength="250"></div>
          <div class="col-12 form-group"><label for="fe_email">Correo <small class="text-muted">(opcional)</small></label><input class="form-control" id="fe_email" type="email" maxlength="250"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="fe_confirmar_emision">Emitir</button>
      </div>
    </div>
  </div>
</div>

<script>
  Facturacion.iniciar();
</script>
