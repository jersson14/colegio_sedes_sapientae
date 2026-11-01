<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/aula_virtual.js?rev=<?php echo time(); ?>"></script>
<?php if (rol_actual() === 'ESTUDIANTE') { ?><script src="../js/entrega_tarea.js?rev=<?php echo time(); ?>"></script><?php } else { require __DIR__ . '/../tareas/material_tarea.php'; } ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Aula virtual</h1>
        <p class="pagina-ayuda">Cada curso con sus tareas, su material de apoyo, su horario y sus compañeros.</p>
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
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="material"><i class="fas fa-folder-open mr-1"></i>Material</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="horario"><i class="far fa-clock mr-1"></i>Horario</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="personas"><i class="fas fa-users mr-1"></i>Personas</a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-pestana="notas"><i class="fas fa-star mr-1"></i>Calificaciones</a></li>
      </ul>
      <div id="av_contenido"></div>
    </div>
  </div>
</div>

<?php if (rol_actual() !== 'ESTUDIANTE') { ?>
<!-- Publicar material de apoyo (docente del curso, dirección y auxiliar) -->
<div class="modal fade" id="av_modal_material" tabindex="-1" aria-labelledby="av_material_titulo" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="av_form_material" novalidate>
      <div class="modal-header">
        <h5 class="modal-title" id="av_material_titulo"><i class="fas fa-folder-plus mr-1"></i>Subir material de apoyo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3" id="av_material_curso"></p>
        <div class="form-group">
          <label for="av_mat_titulo">Título <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="av_mat_titulo" maxlength="150" placeholder="Ej.: Separata de fracciones, Lectura semana 3" required>
        </div>
        <div class="form-group">
          <label for="av_mat_desc">Descripción o indicaciones</label>
          <textarea class="form-control" id="av_mat_desc" rows="3" maxlength="3000" placeholder="Opcional: qué es y cómo usarlo"></textarea>
        </div>
        <div class="form-group">
          <label for="av_mat_archivos">Archivos</label>
          <input type="file" class="form-control-file" id="av_mat_archivos" multiple accept="<?php echo ACEPTA_MATERIAL; ?>">
          <small class="form-text text-muted"><?php echo htmlspecialchars(AYUDA_MATERIAL, ENT_QUOTES, 'UTF-8'); ?></small>
          <ul class="list-unstyled small mt-2 mb-0" id="av_mat_lista"></ul>
        </div>
        <div class="form-group mb-0">
          <label for="av_mat_enlaces">Enlaces <small class="text-muted">(uno por línea: YouTube, Google Drive, páginas web…)</small></label>
          <textarea class="form-control" id="av_mat_enlaces" rows="2" placeholder="https://youtu.be/..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" id="av_mat_guardar"><i class="fas fa-upload mr-1"></i>Publicar</button>
      </div>
    </form>
  </div>
</div>
<?php } ?>

<script>
  AulaVirtual_Iniciar();
</script>
