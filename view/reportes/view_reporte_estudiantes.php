<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/reporte_estudiantes.js?rev=<?php echo time(); ?>"></script>
<style>
  .re-grupo { font-weight: bold; color: #0A5D86; margin: 6px 0 2px; font-size: 13px; }
  .re-campo { display: block; font-weight: normal; margin: 0; padding: 1px 0; cursor: pointer; font-size: 13px; }
  .re-campos { max-height: 55vh; overflow: auto; }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Reporte de estudiantes</h1>
        <p class="pagina-ayuda">Elige los datos y filtros, y exporta la lista.</p>
      </div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Reporte de estudiantes</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3">
        <div class="card card-primary card-outline">
          <div class="card-header"><h3 class="card-title"><b>1. Campos</b> <small class="text-muted">(en el orden que los marque)</small></h3></div>
          <div class="card-body py-2">
            <div class="mb-2" style="display:flex;gap:4px;flex-wrap:wrap">
              <button class="btn btn-xs btn-outline-secondary" onclick="ReporteEstudiantes.plantilla('basico')">Lista básica</button>
              <button class="btn btn-xs btn-outline-secondary" onclick="ReporteEstudiantes.plantilla('padres')">Contacto de padres</button>
              <button class="btn btn-xs btn-outline-secondary" onclick="ReporteEstudiantes.plantilla('seguimiento')">Seguimiento</button>
              <button class="btn btn-xs btn-outline-secondary" onclick="ReporteEstudiantes.plantilla('cobranza')">Cobranza</button>
              <button class="btn btn-xs btn-outline-danger" onclick="ReporteEstudiantes.plantilla('ninguno')">Ninguno</button>
            </div>
            <div class="re-campos" id="re_campos"></div>
            <div class="mt-2 small">
              <a href="#" onclick="ReporteEstudiantes.guardarSeleccion();return false"><i class="fas fa-star"></i> Guardar como mi reporte</a> ·
              <a href="#" onclick="ReporteEstudiantes.usarGuardada();return false">Usar mi reporte</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-9">
        <div class="card card-primary card-outline">
          <div class="card-header"><h3 class="card-title"><b>2. Filtros</b></h3></div>
          <div class="card-body py-2">
            <div class="row">
              <div class="col-md-2 col-6 form-group mb-2"><label class="mb-0">Año escolar</label><select id="re_anio" class="form-control form-control-sm"></select></div>
              <div class="col-md-2 col-6 form-group mb-2"><label class="mb-0">Nivel</label><select id="re_nivel" class="form-control form-control-sm"></select></div>
              <div class="col-md-3 col-12 form-group mb-2"><label class="mb-0">Aula</label><select id="re_aula" class="form-control form-control-sm"></select></div>
              <div class="col-md-2 col-6 form-group mb-2"><label class="mb-0">Sexo</label>
                <select id="re_sexo" class="form-control form-control-sm"><option value="">Todos</option><option>MASCULINO</option><option>FEMENINO</option></select></div>
              <div class="col-md-3 col-6 form-group mb-2"><label class="mb-0">Nuevo / antiguo</label>
                <select id="re_tipo" class="form-control form-control-sm"><option value="">Todos</option><option>NUEVO</option><option>ANTIGUO</option></select></div>
              <div class="col-md-5 col-12 form-group mb-2"><label class="mb-0">Buscar estudiante</label><input id="re_buscar" class="form-control form-control-sm" placeholder="Nombre o documento"></div>
              <div class="col-md-3 col-12 form-group mb-2 d-flex align-items-end">
                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="re_deuda"><label class="custom-control-label" for="re_deuda">Solo con deuda vencida</label></div>
              </div>
              <div class="col-md-4 col-12 form-group mb-2 d-flex align-items-end">
                <button class="btn btn-primary btn-block" onclick="ReporteEstudiantes.generar()"><i class="fas fa-play"></i> 3. Generar reporte</button>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <div id="re_vacio" class="text-muted">Marque los campos, elija los filtros y pulse <b>Generar reporte</b>. Luego puede descargarlo en Excel, PDF o imprimirlo.</div>
            <div class="table-responsive"><table id="re_tabla" class="table table-striped table-bordered table-sm" style="width:100%"></table></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  ReporteEstudiantes.iniciar();
</script>
