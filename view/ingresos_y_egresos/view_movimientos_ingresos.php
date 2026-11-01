<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/movimientos.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Ingresos</h1>
        <p class="pagina-ayuda">Lo que entra al colegio: cobros de pensiones y otros ingresos.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <button type="button" class="btn btn-success" id="mv_nuevo"><i class="fas fa-plus mr-1"></i>Nuevo ingreso</button>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid" id="mv_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  Movimientos_Iniciar('INGRESO', {
    listar: '../controller/ingresos/controlador_listar_ingresos_diversos_filtros.php',
    cobros: '../controller/ingresos/controlador_listar_ingresos_pensiones_filtros.php',
    diferencia: '../controller/egresos/controlador_listar_diferencia_filtro.php',
    categorias: '../controller/ingresos/controlador_cargar_select_indicadores.php',
    formas: '../controller/pago_pension/controlador_cargar_formas_pago.php',
    nuevo: '../controller/ingresos/controlador_registrar_ingresos.php',
    editar: '../controller/ingresos/controlador_modificar_ingreso.php',
    anular: '../controller/ingresos/controlador_anular_ingreso.php'
  });
</script>
