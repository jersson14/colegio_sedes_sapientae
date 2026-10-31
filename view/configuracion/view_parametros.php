<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/parametros.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Parámetros del sistema</h1>
        <p class="pagina-ayuda">Ingreso, videos, días de clase y nombres de la asistencia.</p>
      </div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Parámetros del sistema</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-6">
        <section class="pi-tarjeta">
          <header><h2><i class="fas fa-user-lock mr-2"></i>Ingreso al sistema</h2></header>
          <div class="form-group">
            <label for="pa_login_max_intentos">Intentos fallidos antes de bloquear el usuario</label>
            <input type="number" min="0" max="50" class="form-control" id="pa_login_max_intentos">
            <small class="text-muted">0 = sin bloqueo (no recomendado: deja probar contraseñas sin límite).</small>
          </div>
          <div class="form-group mb-0">
            <label for="pa_login_minutos_bloqueo">Minutos de bloqueo</label>
            <input type="number" min="1" max="1440" class="form-control" id="pa_login_minutos_bloqueo">
          </div>
        </section>
        <section class="pi-tarjeta">
          <header><h2><i class="fas fa-video mr-2"></i>Videos de las tareas</h2></header>
          <div class="form-group mb-0">
            <label for="pa_video_max_mb">Tamaño máximo de cada video (MB)</label>
            <input type="number" min="1" max="2048" class="form-control" id="pa_video_max_mb">
            <small class="text-muted" id="pa_limite"></small>
          </div>
        </section>
      </div>
      <div class="col-lg-6">
        <section class="pi-tarjeta">
          <header><h2><i class="fas fa-calendar-week mr-2"></i>Días de clase</h2></header>
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="pa_clases_sabado">
            <label class="custom-control-label" for="pa_clases_sabado">El colegio tiene clases los <b>sábados</b> (se registra asistencia de lunes a sábado)</label>
          </div>
        </section>
        <section class="pi-tarjeta">
          <header><h2><i class="fas fa-clipboard-check mr-2"></i>Nombres de las marcas de asistencia</h2></header>
          <p class="text-muted small">Las letras P, T, F y J no cambian; aquí se elige cómo se llama cada una en el registro y en la planilla impresa.</p>
          <div class="row">
            <div class="col-6 form-group"><label for="pa_asistencia_P"><span class="ra-letra ra-P">P</span></label><input type="text" maxlength="20" class="form-control" id="pa_asistencia_P"></div>
            <div class="col-6 form-group"><label for="pa_asistencia_T"><span class="ra-letra ra-T">T</span></label><input type="text" maxlength="20" class="form-control" id="pa_asistencia_T"></div>
            <div class="col-6 form-group mb-0"><label for="pa_asistencia_F"><span class="ra-letra ra-F">F</span></label><input type="text" maxlength="20" class="form-control" id="pa_asistencia_F"></div>
            <div class="col-6 form-group mb-0"><label for="pa_asistencia_J"><span class="ra-letra ra-J">J</span></label><input type="text" maxlength="20" class="form-control" id="pa_asistencia_J"></div>
          </div>
        </section>
      </div>
    </div>
    <div class="d-flex justify-content-end" style="gap:8px">
      <button type="button" class="btn btn-outline-secondary" id="pa_defecto"><i class="fas fa-undo mr-1"></i>Valores de fábrica</button>
      <button type="button" class="btn btn-success" id="pa_guardar"><i class="fas fa-save mr-1"></i>Guardar</button>
    </div>
  </div>
</div>
<script>
  Parametros_Iniciar();
</script>
