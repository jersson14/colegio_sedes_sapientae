<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/cambio_anio.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Cambio de año escolar</h1>
        <p class="pagina-ayuda">Pasa de grado a quienes aprobaron todo y prepara el año nuevo.</p>
      </div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Cambio de año escolar</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        <ol class="mp-pasos" aria-label="Pasos del cambio de año">
          <li data-paso="1"><span>1</span>Años</li>
          <li data-paso="2"><span>2</span>Promoción</li>
          <li data-paso="3"><span>3</span>Copias</li>
          <li data-paso="4"><span>4</span>Resumen</li>
        </ol>

        <section class="ca-paso" data-paso="1">
          <div class="row">
            <div class="col-md-4 form-group">
              <label for="ca_origen">Año que termina</label>
              <select id="ca_origen" class="form-control"></select>
            </div>
            <div class="col-md-4 form-group">
              <label for="ca_destino">Año nuevo</label>
              <select id="ca_destino" class="form-control"></select>
            </div>
          </div>
          <div id="ca_destino_info"></div>
        </section>

        <section class="ca-paso" data-paso="2" hidden>
          <div class="alert alert-light border py-2">
            <i class="fas fa-info-circle text-info mr-1"></i>Solo se propone <b>promover</b> a quien <b>aprobó todos sus cursos</b> (nota final del año en la libreta).
            Con algún curso desaprobado o sin notas, elija usted: <b>repite</b>, <b>promover</b> (por ejemplo, si aprobó la recuperación), <b>no continúa</b> o <b>decidir luego</b>.
            Quien queda en "decidir luego" no se matricula ahora; puede matricularlo después desde Matrícula.
          </div>
          <div id="ca_aulas"></div>
        </section>

        <section class="ca-paso" data-paso="3" hidden>
          <p class="text-muted">Lo que se copia del año que termina, para empezar el año nuevo y corregir después lo que cambie.</p>
          <div id="ca_copias"></div>
          <div class="custom-control custom-switch mt-3">
            <input type="checkbox" class="custom-control-input" id="ca_activar">
            <label class="custom-control-label" for="ca_activar"><b>Activar el año nuevo al terminar</b> (el año que termina se cierra y sus periodos quedan finalizados)</label>
          </div>
        </section>

        <section class="ca-paso" data-paso="4" hidden>
          <div id="ca_resumen"></div>
        </section>

        <div class="d-flex justify-content-end mt-3" style="gap:8px">
          <button type="button" class="btn btn-outline-secondary" id="ca_atras" hidden><i class="fas fa-arrow-left mr-1"></i>Atrás</button>
          <button type="button" class="btn btn-primary" id="ca_siguiente">Siguiente<i class="fas fa-arrow-right ml-1"></i></button>
          <button type="button" class="btn btn-success" id="ca_ejecutar" hidden><i class="fas fa-check mr-1"></i>Hacer el cambio de año</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  CambioAnio.iniciar();
</script>
