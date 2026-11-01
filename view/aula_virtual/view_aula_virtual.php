<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/aula_virtual.js?rev=<?php echo time(); ?>"></script>
<?php if (rol_actual() === 'ESTUDIANTE') { ?><script src="../js/aula_virtual_entrega.js?rev=<?php echo time(); ?>"></script><?php } ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Aula virtual</h1>
        <p class="pagina-ayuda">Cada curso con sus tareas, su horario y sus compañeros.</p>
      </div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Aula virtual</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <!-- Lista de cursos -->
    <div id="av_lista">
      <div class="av-filtros row">
        <div class="col-sm-4 col-lg-3 form-group">
          <label for="av_anio">Año escolar</label>
          <select class="form-control" id="av_anio"></select>
        </div>
        <div class="col-sm-8 col-lg-5 form-group d-none" id="av_aula_grupo">
          <label for="av_aula">Grado y sección</label>
          <select class="form-control" id="av_aula"></select>
        </div>
      </div>
      <div id="av_cursos" class="av-cursos"><p class="text-muted">Cargando…</p></div>
    </div>

    <!-- Un curso -->
    <div id="av_curso" class="d-none">
      <button type="button" class="btn btn-link px-0 mb-2" id="av_volver"><i class="fas fa-arrow-left mr-1"></i>Todos los cursos</button>
      <header class="av-portada" id="av_portada"></header>
      <ul class="nav av-pestanas" role="tablist">
        <li class="nav-item"><a class="nav-link active" href="#" data-pestana="tablon"><i class="fas fa-stream mr-1"></i>Tablón</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="horario"><i class="far fa-clock mr-1"></i>Horario</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="personas"><i class="fas fa-users mr-1"></i>Personas</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="notas"><i class="fas fa-star mr-1"></i>Calificaciones</a></li>
      </ul>
      <div id="av_contenido"></div>
    </div>
  </div>
</div>

<!-- Entregar una tarea (estudiante) -->
<div class="modal fade" id="av_modal_entrega" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white" id="av_entrega_titulo">Entregar tarea</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="av_entrega_id">
        <p class="text-muted small mb-2" id="av_entrega_aviso"></p>
        <div class="form-group mb-0">
          <label for="av_entrega_archivos">Archivos de su trabajo <b class="text-danger">(*)</b></label>
          <input type="file" class="form-control-file" id="av_entrega_archivos" multiple>
          <small class="text-muted">Puede elegir varios archivos (fotos, PDF, Word…). Si vuelve a entregar, reemplazan a los anteriores.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
        <button type="button" class="btn btn-success" id="av_entrega_enviar"><i class="fas fa-upload mr-1"></i>Entregar</button>
      </div>
    </div>
  </div>
</div>
<script>
  AulaVirtual_Iniciar();
</script>
