<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/registro_tareas.js?rev=<?php echo time(); ?>"></script>
<style>
  .rt-scroll { overflow: auto; max-height: 70vh; border: 1px solid #dee2e6; }
  .rt-tabla { border-collapse: separate; border-spacing: 0; font-size: 12px; min-width: 100%; }
  .rt-tabla th, .rt-tabla td { border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; text-align: center; padding: 3px 4px; white-space: nowrap; }
  .rt-tabla thead th { position: sticky; top: 0; z-index: 2; background: #f4f6f9; vertical-align: bottom; }
  .rt-tabla .rt-fija { position: sticky; left: 0; z-index: 1; background: #fff; text-align: left; min-width: 230px; }
  .rt-tabla thead .rt-fija { z-index: 3; background: #f4f6f9; }
  .rt-tarea { max-width: 110px; min-width: 70px; white-space: normal !important; cursor: pointer; font-weight: 600; }
  .rt-tarea small { display: block; font-weight: normal; color: #6c757d; }
  .rt-celda { cursor: pointer; min-width: 70px; }
  .rt-celda:hover { background: #f1f7fd; }
  .rt-nota-ok { color: #146c2e; font-weight: bold; }
  .rt-nota-mal { color: #a01818; font-weight: bold; }
  .rt-leyenda span { margin-right: 14px; }
  .rt-tot { font-weight: bold; background: #fafafa; }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8"><h1 class="m-0"><b>REGISTRO DE TAREAS</b></h1></div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">REGISTRO DE TAREAS</li>
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
            <label for="rt_anio">Año escolar</label>
            <select id="rt_anio" class="form-control"></select>
          </div>
          <div class="col-md-5 col-12 form-group">
            <label for="rt_curso">Curso</label>
            <select id="rt_curso" class="form-control"></select>
          </div>
          <div class="col-md-2 col-6 form-group">
            <label for="rt_periodo">Periodo</label>
            <select id="rt_periodo" class="form-control"></select>
          </div>
          <div class="col-md-3 col-12 form-group">
            <label class="d-none d-md-block">&nbsp;</label>
            <div class="btn-group d-flex">
              <button class="btn btn-outline-success" onclick="RegistroTareas.excel()"><i class="fas fa-file-excel"></i> Excel</button>
              <button class="btn btn-outline-secondary" onclick="RegistroTareas.imprimir()"><i class="fas fa-print"></i> Imprimir</button>
            </div>
          </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between mb-2" style="gap:8px">
          <div class="rt-leyenda small">
            <span><b class="rt-nota-ok">15</b> / <b class="rt-nota-mal">08</b> Calificada</span>
            <span><i class="fas fa-inbox text-primary"></i> Entregada, por calificar</span>
            <span><i class="fas fa-hourglass-half text-secondary"></i> Pendiente (aún en plazo)</span>
            <span><i class="fas fa-times text-danger"></i> No entregó</span>
          </div>
          <small class="text-muted"><i class="fas fa-mouse-pointer"></i> Clic en una casilla para ver lo entregado y calificar · Clic en la tarea para ver su material</small>
        </div>
        <div class="rt-scroll"><table class="rt-tabla" id="rt_tabla"></table></div>
      </div>
    </div>
  </div>
</div>
<script>
  RegistroTareas.iniciar();
</script>
