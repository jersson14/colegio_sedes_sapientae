<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/registro_notas.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo"><?php echo rol_actual() === 'DOCENTE' ? 'Notas de mis cursos' : 'Notas'; ?></h1>
        <p class="pagina-ayuda">Elige el curso y el bimestre, escribe las notas y guarda.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <select class="form-control pe-anio" id="rn_anio" aria-label="Año escolar"></select>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div id="rn_cursos" class="rn-cursos"></div>
    <div id="rn_periodos" class="pe-filtros pe-periodos"></div>
    <div id="rn_contenido"><p class="text-muted">Cargando…</p></div>
  </div>
</div>
<script>
  RegistroNotas_Iniciar();
</script>
