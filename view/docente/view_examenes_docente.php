<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/trabajos_docente.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo"><?php echo rol_actual() === 'DOCENTE' ? 'Exámenes de mis cursos' : 'Exámenes'; ?></h1>
        <p class="pagina-ayuda">Programa los exámenes de tus aulas.</p>
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
  ExamenesDocente_Iniciar({
    nuevo: '../controller/examenes/controlador_registro_examenes.php',
    editar: '../controller/examenes/controlador_modificar_examen.php',
    estado: '../controller/examenes/controlador_modificar_estado_examen.php',
    eliminar: '../controller/examenes/controlador_eliminar_examenes.php'
  });
</script>
