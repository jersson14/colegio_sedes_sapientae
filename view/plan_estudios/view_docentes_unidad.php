<script src="../js/console_docentes_unidad.js?rev=<?php echo time(); ?>"></script>
<!-- Fase 5.9: docente de cada unidad didáctica por periodo (institutos). -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>DOCENTES POR UNIDAD DIDÁCTICA</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">DOCENTES POR UNIDAD</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-md-7 form-group"><label for="du_programa">Programa de estudios</label><select class="form-control" id="du_programa"></select></div>
          <div class="col-md-5 form-group"><label for="du_periodo">Periodo</label><select class="form-control" id="du_periodo"></select></div>
        </div>
        <p class="text-muted mb-2">El docente asignado ve la unidad en «Mis unidades didácticas» y registra las notas de sus matriculados en ese periodo.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="tabla_docentes_unidad">
            <thead style="background-color:#023D77;color:white;"><tr><th>Periodo</th><th>Código</th><th>Unidad didáctica</th><th>Módulo</th><th style="width:40%">Docente</th></tr></thead>
            <tbody><tr><td colspan="5" class="text-muted">Elige un programa y un periodo.</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  DocentesUnidad.iniciar();
</script>
