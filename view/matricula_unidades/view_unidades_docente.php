<script src="../js/console_unidades_docente.js?rev=<?php echo time(); ?>"></script>
<!-- Fase 5.9: las unidades didácticas del docente y las notas de sus matriculados (institutos). -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>MIS UNIDADES DIDÁCTICAS</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">MIS UNIDADES</li>
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
          <div class="col-md-4 form-group"><label for="ud_periodo">Periodo</label><select class="form-control" id="ud_periodo"></select></div>
          <div class="col-md-8 form-group"><label for="ud_unidad">Unidad didáctica</label><select class="form-control" id="ud_unidad"></select></div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="tabla_unidades_docente">
            <thead style="background-color:#023D77;color:white;"><tr><th>DNI</th><th>Alumno</th><th>Situación</th><th>Nota</th><th></th></tr></thead>
            <tbody><tr><td colspan="5" class="text-muted">Elige un periodo y una de tus unidades.</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  UnidadesDocente.iniciar();
</script>
