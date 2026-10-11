<script src="../js/console_auditoria.js?rev=<?php echo time(); ?>"></script>
<!-- H-15: auditoría de la institución (quién hizo qué y cuándo). -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>AUDITORÍA</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">AUDITORÍA</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-md-2 form-group"><label for="au_desde">Desde</label><input type="date" class="form-control" id="au_desde"></div>
          <div class="col-md-2 form-group"><label for="au_hasta">Hasta</label><input type="date" class="form-control" id="au_hasta"></div>
          <div class="col-md-3 form-group"><label for="au_usuario">Usuario</label><input type="text" class="form-control" id="au_usuario" maxlength="100"></div>
          <div class="col-md-3 form-group"><label for="au_modulo">Módulo</label><select class="form-control" id="au_modulo"><option value="">— Todos —</option></select></div>
          <div class="col-md-2 form-group d-flex align-items-end"><button class="btn btn-primary btn-block" id="au_buscar"><i class="fas fa-search"></i> Buscar</button></div>
        </div>
        <p class="text-muted mb-2">Se registra toda acción que guarda, modifica o elimina datos (también las rechazadas), y los inicios y cierres de sesión. Las contraseñas nunca se guardan. Se muestran hasta 500 registros: acota con los filtros.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="tabla_auditoria">
            <thead style="background-color:#023D77;color:white;"><tr><th>Fecha</th><th>Usuario</th><th>Rol</th><th>Módulo</th><th>Acción</th><th>Resultado</th><th>IP</th><th>Datos</th></tr></thead>
            <tbody><tr><td colspan="8" class="text-muted">Cargando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  Auditoria.iniciar();
</script>
