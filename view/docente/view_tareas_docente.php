<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<?php include __DIR__ . '/../tareas/material_tarea.php'; ?>
<script src="../js/trabajos_docente.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Tareas de mis cursos</h1>
        <p class="pagina-ayuda">Publica tareas, mira quién entregó y califica.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end" style="gap:8px">
        <select class="form-control pe-anio" id="td_anio" aria-label="Año escolar"></select>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid" id="td_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  TareasDocente_Iniciar();
</script>
