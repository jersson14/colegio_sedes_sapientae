<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/respaldos.js?rev=<?php echo time(); ?>"></script>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8"><h1 class="m-0"><b>RESPALDOS DE LA BASE DE DATOS</b></h1></div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">RESPALDOS</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-4">
        <div class="card card-primary card-outline">
          <div class="card-body">
            <p>Un respaldo es una copia completa de la información del colegio: estudiantes, matrículas, notas, asistencia, pagos, contabilidad y configuración.</p>
            <button class="btn btn-primary btn-lg btn-block" id="btn_generar" onclick="Respaldos.generar()">
              <i class="fas fa-database"></i> Generar respaldo ahora
            </button>
            <div id="resultado_respaldo" class="mt-3"></div>
            <hr>
            <p class="small text-muted mb-1"><i class="fas fa-lightbulb text-warning"></i> <b>Recomendaciones</b></p>
            <ul class="small text-muted pl-3 mb-0">
              <li>Genere un respaldo antes de cambios importantes (fin de año, cierre de caja mensual, actualizaciones).</li>
              <li><b>Descárguelo y guárdelo fuera del servidor</b> (otra computadora, USB o la nube): si el servidor falla, los respaldos que están en él también se pierden.</li>
              <li>Además de estos, el servidor puede hacer un respaldo automático cada día (ver <i>docs/despliegue.md</i>).</li>
              <li>Para restaurar un respaldo se necesita al encargado técnico (también está explicado en <i>docs/despliegue.md</i>).</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-history"></i> <b>Respaldos guardados</b></h3></div>
          <div class="card-body">
            <table id="tabla_respaldos" class="table table-striped table-bordered table-sm" style="width:100%">
              <thead><tr><th>Fecha</th><th>Archivo</th><th>Tipo</th><th>Tamaño</th><th>Acción</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  Respaldos.iniciar();
</script>
