<script src="../js/console_matricula_unidades.js?rev=<?php echo time(); ?>"></script>
<!-- Fase 5.4: matrícula por unidad didáctica (institutos). -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>MATRÍCULA POR UNIDADES DIDÁCTICAS</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">MATRÍCULA POR UNIDADES</li>
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
          <div class="col-md-5 form-group"><label for="mu_alumno">Alumno</label><select class="form-control" id="mu_alumno" style="width:100%"></select></div>
          <div class="col-md-4 form-group"><label for="mu_programa">Programa de estudios</label><select class="form-control" id="mu_programa"></select></div>
          <div class="col-md-3 form-group"><label for="mu_periodo">Periodo</label><select class="form-control" id="mu_periodo"></select></div>
        </div>
        <p class="text-muted mb-2">Solo se puede matricular en una unidad si el alumno tiene aprobados sus prerrequisitos; una unidad aprobada no se vuelve a llevar.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="tabla_matricula_unidades">
            <thead style="background-color:#023D77;color:white;"><tr><th>Periodo</th><th>Código</th><th>Unidad didáctica</th><th>Módulo</th><th>Créditos</th><th>Situación</th><th></th></tr></thead>
            <tbody><tr><td colspan="7" class="text-muted">Elige un alumno, un programa y un periodo.</td></tr></tbody>
          </table>
        </div>
        <p class="mb-0"><b>Créditos matriculados en el periodo: <span id="mu_creditos">0</span></b></p>
      </div>
    </div>
  </div>
</div>

<script>
  MatriculaUnidades.iniciar();
</script>
