<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/pensiones_nivel.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Pensiones</h1>
        <p class="pagina-ayuda">Cuánto se paga cada mes en cada nivel y cuándo vence.</p>
      </div>
      <div class="col-sm-6 d-flex justify-content-sm-end flex-wrap" style="gap:8px">
        <select class="form-control pe-anio" id="pn_anio" aria-label="Año escolar"></select>
        <button type="button" class="btn btn-outline-primary" id="pn_generar"><i class="fas fa-calendar-alt mr-1"></i>Generar cronograma</button>
        <button type="button" class="btn btn-success" id="pn_nueva"><i class="fas fa-plus mr-1"></i>Un mes</button>
      </div>
    </div>
  </div>
</div>
<div class="content">
  <div class="container-fluid" id="pn_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  Pensiones_Iniciar();
</script>
