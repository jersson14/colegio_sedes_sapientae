<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/permisos.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7">
        <h1 class="m-0 pagina-titulo">Permisos por rol</h1>
        <p class="pagina-ayuda">Qué pantallas ve cada rol. La dirección siempre ve todo.</p>
      </div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Permisos por rol</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <section class="pi-tarjeta">
      <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:8px">
        <p class="text-muted small mb-0">
          Marque o desmarque una casilla y el cambio se guarda al instante; el usuario lo verá al volver a entrar o recargar.
          Las casillas con <span class="pm-cambio px-1">borde</span> son distintas a las de fábrica. Un guion (—) es una pantalla propia de otro rol.
        </p>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="pm_restablecer_todo"><i class="fas fa-undo mr-1"></i>Volver todo a fábrica</button>
      </div>
      <div class="form-group mb-2">
        <input type="search" class="form-control form-control-sm" id="pm_buscar" placeholder="Buscar pantalla…" style="max-width:320px">
      </div>
      <div class="pm-tabla-envoltura">
        <table class="table table-sm table-hover pm-tabla mb-0">
          <thead><tr id="pm_cabecera"><th class="text-left">Pantalla</th></tr></thead>
          <tbody id="pm_cuerpo"><tr><td class="text-muted">Cargando…</td></tr></tbody>
        </table>
      </div>
    </section>
  </div>
</div>
<script>
  Permisos_Iniciar();
</script>
