<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/libretas.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Libretas de notas</h1>
        <p class="pagina-ayuda">Notas por curso, promedio y orden de mérito de cada aula.</p>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Libretas de notas</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        <div class="row">
          <div class="col-md-2 col-6 form-group">
            <label for="lb_anio">Año escolar</label>
            <select id="lb_anio" class="form-control"></select>
          </div>
          <div class="col-md-4 col-6 form-group">
            <label for="lb_aula">Aula</label>
            <select id="lb_aula" class="form-control"></select>
          </div>
          <div class="col-md-3 col-12 form-group">
            <label for="lb_periodo">Periodo</label>
            <select id="lb_periodo" class="form-control"></select>
          </div>
          <div class="col-md-3 col-12 form-group">
            <label class="d-none d-md-block">&nbsp;</label>
            <div class="d-flex flex-wrap" style="gap:6px">
              <button type="button" class="btn btn-primary" onclick="Libreta_Pdf()" title="Una libreta por estudiante, en una sola descarga"><i class="fas fa-file-pdf mr-1"></i>Libretas del aula</button>
              <button type="button" class="btn btn-outline-primary" onclick="Libreta_Pdf(null, true)"><i class="fas fa-trophy mr-1"></i>Orden de mérito</button>
            </div>
          </div>
        </div>
        <div id="lb_resumen" class="lb-resumen"></div>
        <div class="table-responsive">
          <table id="lb_tabla" class="table table-striped table-bordered" style="width:100%"></table>
        </div>
        <p class="text-muted small mb-0" id="lb_nota"></p>
      </div>
    </div>
  </div>
</div>
<script>
  Libreta_Iniciar();
</script>
