<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/cuentas.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Cajas y bancos</h1>
        <p class="pagina-ayuda">El saldo de cada caja y cuenta, sus movimientos, el cierre de caja y las transferencias.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <button type="button" class="btn btn-outline-primary" id="cb_transferir"><i class="fas fa-exchange-alt mr-1"></i>Depositar o mover dinero</button>
      </div>
    </div>
  </div>
</div>
<div class="content">
  <div class="container-fluid">
    <div id="cb_cuentas" class="cb-cuentas"></div>
    <div id="cb_detalle"></div>
  </div>
</div>
<script>
  Cuentas_Iniciar();
</script>
