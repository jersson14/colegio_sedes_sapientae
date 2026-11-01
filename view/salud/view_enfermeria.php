<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/atenciones_salud.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Enfermería</h1>
        <p class="pagina-ayuda">Atenciones de salud de los estudiantes.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <button type="button" class="btn btn-success" id="sa_nueva"><i class="fas fa-plus mr-1"></i>Nueva atención</button>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid" id="sa_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  Salud_Iniciar('ENFERMERIA', { nuevo: '../controller/atencion_enfermeria/controlador_registro_enfermeria.php', editar: '../controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php' });
</script>
