<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/caja.js?rev=<?php echo time(); ?>"></script>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Cobrar pensión</h1>
        <p class="pagina-ayuda">Busca al estudiante, marca lo que paga y elige la forma de pago.</p>
      </div>
      <div class="col-sm-4 d-flex justify-content-sm-end">
        <select class="form-control pe-anio" id="cj_anio" aria-label="Año escolar"></select>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="cj-buscador">
      <i class="fas fa-search"></i>
      <input type="search" class="form-control" id="cj_buscar" placeholder="Nombre, apellido o DNI del estudiante" autocomplete="off" aria-label="Buscar estudiante">
      <div id="cj_resultados" class="cj-resultados d-none"></div>
    </div>
    <div class="cj-cuerpo">
      <div id="cj_estado"><div class="av-vacio"><i class="fas fa-cash-register"></i><p>Busque al estudiante para ver su estado de cuenta.</p></div></div>
      <aside class="cj-cobro" id="cj_cobro"></aside>
    </div>
  </div>
</div>
<script>
  Caja_Iniciar();
</script>
