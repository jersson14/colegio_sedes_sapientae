<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/mi_portal.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Mis notas</h1>
        <p class="pagina-ayuda">Tu nota de cada curso por bimestre y del año.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <select class="form-control pe-anio d-none" id="mp_anio" aria-label="Año escolar"></select>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid" id="mp_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  MisNotas_Iniciar();
</script>
