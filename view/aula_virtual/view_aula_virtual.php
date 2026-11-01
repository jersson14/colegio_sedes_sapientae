<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/aula_virtual.js?rev=<?php echo time(); ?>"></script>
<?php if (rol_actual() === 'ESTUDIANTE') { ?><script src="../js/entrega_tarea.js?rev=<?php echo time(); ?>"></script><?php } ?>
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

<script>
  AulaVirtual_Iniciar();
</script>
