<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_auditoria.js?rev=<?php echo time();?>"></script>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Auditoría</h1>
        <p class="pagina-ayuda">Quién cambió qué y cuándo.</p>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Auditoría</li>
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
          <div class="col-md-3 form-group"><label>Qué:</label><select class="form-control" id="select_tabla_aud"><option value="">Todo</option></select></div>
          <div class="col-md-2 form-group"><label>Desde:</label><input type="date" class="form-control" id="txt_desde_aud"></div>
          <div class="col-md-2 form-group"><label>Hasta:</label><input type="date" class="form-control" id="txt_hasta_aud"></div>
          <div class="col-md-2 form-group"><label>N.° de registro:</label><input type="text" class="form-control" id="txt_registro_aud" maxlength="20" placeholder="Todos"></div>
          <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Listar_Auditoria()"><i class="fas fa-search"></i> Buscar</button></div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped" id="tabla_auditoria" style="width:100%">
            <thead style="background-color:#0A5D86;color:#FFFFFF;">
              <tr><th>Fecha</th><th>Usuario</th><th>Qué</th><th>N.°</th><th>Acción</th><th>Cambios</th><th>IP</th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <small class="text-muted">Registra automáticamente cada alta, cambio y baja en recibos, pagos, ingresos, egresos, pensiones, matrículas y becas, cajas, planillas, contratos, formas de pago, categorías, usuarios y la configuración del colegio. "(base de datos)" indica un cambio hecho fuera del sistema. Se muestran hasta 1000 cambios por búsqueda.</small>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    Iniciar_Auditoria();
  });
</script>
