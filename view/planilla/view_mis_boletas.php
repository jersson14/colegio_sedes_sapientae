<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/mis_boletas.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Mis boletas de pago</h1>
        <p class="pagina-ayuda">Lo que recibes cada mes y tu boleta para imprimir.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <select class="form-control pe-anio d-none" id="mb_anio" aria-label="Año"></select>
      </div>
    </div>
  </div>
</div>
<div class="content">
  <div class="container-fluid" id="mb_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  MisBoletas_Iniciar();
</script>
