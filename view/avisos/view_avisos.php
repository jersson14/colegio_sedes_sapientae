<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/avisos.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Avisos a las familias</h1>
        <p class="pagina-ayuda">Faltas, tareas, pensiones y comunicados: un toque y el mensaje sale por WhatsApp.</p>
      </div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Avisos a las familias</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-end mb-2" style="gap:10px">
          <div class="av-filtros" id="av_tipos" role="group" aria-label="Tipo de aviso"></div>
          <div class="form-group mb-0" id="av_fecha_grupo">
            <label for="av_fecha" class="mb-0 small">Faltas del día</label>
            <input type="date" id="av_fecha" class="form-control form-control-sm">
          </div>
          <div class="custom-control custom-switch mb-1">
            <input type="checkbox" class="custom-control-input" id="av_enviados">
            <label class="custom-control-label" for="av_enviados">Mostrar también los ya avisados</label>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ml-auto" id="av_configurar" hidden><i class="fas fa-sliders-h mr-1"></i>Configurar avisos</button>
        </div>
        <div id="av_resumen" class="lb-resumen"></div>
        <div id="av_lista"></div>
        <p class="text-muted small mt-2 mb-0">El botón <b>WhatsApp</b> abre el mensaje ya escrito al celular de la madre (o del padre); usted lo envía y el aviso queda registrado para no repetirlo.</p>
      </div>
    </div>
  </div>
</div>

<!-- Configurar avisos (solo la dirección) -->
<div class="modal fade" id="modal_avisos_config" tabindex="-1" role="dialog" aria-labelledby="titulo_av_config" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="titulo_av_config" style="color:white"><b>Configurar avisos</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p class="text-muted">Elija qué avisos se preparan para las familias.</p>
        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="cfg_falta"><label class="custom-control-label" for="cfg_falta">Falta del día</label></div>
        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="cfg_tarea"><label class="custom-control-label" for="cfg_tarea">Tarea vencida sin entregar</label></div>
        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="cfg_pension"><label class="custom-control-label" for="cfg_pension">Pensión vencida o por vencer</label></div>
        <div class="form-group ml-4 mb-2" style="max-width:260px">
          <label for="cfg_dias_pension" class="small mb-0">Avisar las pensiones que vencen en los próximos (días)</label>
          <input type="number" min="0" max="30" class="form-control form-control-sm" id="cfg_dias_pension">
        </div>
        <div class="custom-control custom-switch mb-2"><input type="checkbox" class="custom-control-input" id="cfg_comunicado"><label class="custom-control-label" for="cfg_comunicado">Comunicado nuevo sin leer</label></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Avisos_Guardar_Config()"><i class="fas fa-save mr-1"></i>Guardar</button>
      </div>
    </div>
  </div>
</div>
<script>
  Avisos_Iniciar();
</script>
