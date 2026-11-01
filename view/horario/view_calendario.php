<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/horario_calendario.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Calendario de horarios</h1>
        <p class="pagina-ayuda">Las clases de cada grado por semana o por mes.</p>
      </div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Calendario de horarios</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <section class="pi-tarjeta">
      <div class="row">
        <div class="col-sm-4 col-lg-3 form-group">
          <label for="hc_anio">Año escolar</label>
          <select class="form-control" id="hc_anio"></select>
        </div>
        <div class="col-sm-8 col-lg-5 form-group" id="hc_aula_grupo">
          <label for="hc_aula">Grado y sección</label>
          <select class="form-control" id="hc_aula"></select>
        </div>
        <div class="col-lg-4 form-group d-flex align-items-end justify-content-lg-end">
          <button type="button" class="btn btn-outline-secondary" id="hc_imprimir"><i class="fas fa-print mr-1"></i>Imprimir</button>
        </div>
      </div>
      <div id="hc_vacio" class="alert alert-light border d-none mb-3"></div>
      <div id="hc_calendario" class="hc-calendario"></div>
      <div id="hc_leyenda" class="hc-leyenda mt-3"></div>
    </section>
  </div>
</div>
<script>
  Calendario_Iniciar();
</script>
