<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/movimientos.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Gastos</h1>
        <p class="pagina-ayuda">Lo que sale: servicios, compras, remuneraciones y otros gastos.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <button type="button" class="btn btn-success" id="mv_nuevo"><i class="fas fa-plus mr-1"></i>Nuevo gasto</button>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid" id="mv_contenido"><p class="text-muted">Cargando…</p></div>
</div>
<script>
  Movimientos_Iniciar('GASTO', {
    listar: '../controller/egresos/controlador_listar_egresos_diversos_filtros.php',
    diferencia: '../controller/egresos/controlador_listar_diferencia_filtro.php',
    categorias: '../controller/egresos/controlador_cargar_select_indicadores_egresos.php',
    formas: '../controller/pago_pension/controlador_cargar_formas_pago.php',
    nuevo: '../controller/egresos/controlador_registrar_egreso.php',
    editar: '../controller/egresos/controlador_modificar_egreso.php',
    anular: '../controller/egresos/controlador_anular_egreso.php',
    comprobante: '../controller/egresos/controlador_ver_comprobante.php'
  });
</script>
