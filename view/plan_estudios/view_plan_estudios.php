<script src="../js/console_plan_estudios.js?rev=<?php echo time(); ?>"></script>
<!-- Fase 5.3: plan de estudios de un instituto (programas → módulos formativos → unidades didácticas). -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>PLAN DE ESTUDIOS</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">PLAN DE ESTUDIOS</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sitemap"></i>&nbsp;<b>Programas de estudio</b></h3>
        <button class="btn btn-success btn-sm float-right" id="btn_nuevo_programa" onclick="Plan.abrirPrograma()"><i class="fas fa-plus"></i> Nuevo programa</button>
      </div>
      <div class="card-body">
        <p class="text-muted mb-2">Cada programa tiene módulos formativos y cada módulo sus unidades didácticas, con créditos, horas,
          periodo académico y prerrequisitos. Lo que ya tiene historial no se borra: se desactiva.</p>
        <div id="plan_contenido"><p class="text-muted">Cargando…</p></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_programa" tabindex="-1" role="dialog" aria-labelledby="titulo_programa" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" id="titulo_programa" style="color:white"><b>PROGRAMA DE ESTUDIOS</b></h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="programa_id">
      <div class="form-group"><label for="programa_codigo">Código</label><input class="form-control" id="programa_codigo" maxlength="20" placeholder="CI"></div>
      <div class="form-group"><label for="programa_nombre">Nombre</label><input class="form-control" id="programa_nombre" maxlength="200" placeholder="Computación e Informática"></div>
      <div class="form-group"><label for="programa_estado">Estado</label><select class="form-control" id="programa_estado"><option>ACTIVO</option><option>INACTIVO</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
      <button type="button" class="btn btn-success" id="guardar_programa" onclick="Plan.guardarPrograma()">Guardar</button></div>
  </div></div>
</div>

<div class="modal fade" id="modal_modulo" tabindex="-1" role="dialog" aria-labelledby="titulo_modulo" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" id="titulo_modulo" style="color:white"><b>MÓDULO FORMATIVO</b></h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="modulo_id"><input type="hidden" id="modulo_programa">
      <div class="form-group"><label for="modulo_nombre">Nombre</label><input class="form-control" id="modulo_nombre" maxlength="200"></div>
      <div class="form-group"><label for="modulo_orden">Orden</label><input type="number" min="1" max="50" class="form-control" id="modulo_orden" value="1"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
      <button type="button" class="btn btn-success" id="guardar_modulo" onclick="Plan.guardarModulo()">Guardar</button></div>
  </div></div>
</div>

<div class="modal fade" id="modal_unidad" tabindex="-1" role="dialog" aria-labelledby="titulo_unidad" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" id="titulo_unidad" style="color:white"><b>UNIDAD DIDÁCTICA</b></h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body"><div class="row">
      <input type="hidden" id="unidad_id"><input type="hidden" id="unidad_modulo">
      <div class="col-4 form-group"><label for="unidad_codigo">Código</label><input class="form-control" id="unidad_codigo" maxlength="20" placeholder="UD-101"></div>
      <div class="col-8 form-group"><label for="unidad_nombre">Nombre</label><input class="form-control" id="unidad_nombre" maxlength="200"></div>
      <div class="col-3 form-group"><label for="unidad_periodo">Periodo académico</label>
        <select class="form-control" id="unidad_periodo"><option value="1">I</option><option value="2">II</option><option value="3">III</option><option value="4">IV</option><option value="5">V</option><option value="6">VI</option><option value="7">VII</option><option value="8">VIII</option><option value="9">IX</option><option value="10">X</option></select></div>
      <div class="col-3 form-group"><label for="unidad_creditos">Créditos</label><input class="form-control" id="unidad_creditos" inputmode="decimal" placeholder="3"></div>
      <div class="col-3 form-group"><label for="unidad_ht">Horas teóricas</label><input type="number" min="0" max="999" class="form-control" id="unidad_ht" value="0"></div>
      <div class="col-3 form-group"><label for="unidad_hp">Horas prácticas</label><input type="number" min="0" max="999" class="form-control" id="unidad_hp" value="0"></div>
      <div class="col-4 form-group"><label for="unidad_estado">Estado</label><select class="form-control" id="unidad_estado"><option>ACTIVO</option><option>INACTIVO</option></select></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
      <button type="button" class="btn btn-success" id="guardar_unidad" onclick="Plan.guardarUnidad()">Guardar</button></div>
  </div></div>
</div>

<div class="modal fade" id="modal_requisitos" tabindex="-1" role="dialog" aria-labelledby="titulo_requisitos" aria-hidden="true">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header" style="background-color:#1FA0E0;"><h5 class="modal-title" id="titulo_requisitos" style="color:white"><b>PRERREQUISITOS DE <span id="requisitos_de"></span></b></h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body">
      <input type="hidden" id="requisitos_unidad">
      <p class="text-muted">Para matricularse en esta unidad, el alumno debe tener aprobadas:</p>
      <ul id="requisitos_lista" class="mb-3"></ul>
      <div class="input-group">
        <select class="form-control" id="requisitos_nuevo" aria-label="Unidad requerida"></select>
        <div class="input-group-append"><button class="btn btn-success" id="agregar_requisito" onclick="Plan.agregarRequisito()">Agregar</button></div>
      </div>
    </div>
  </div></div>
</div>

<script>
  Plan.cargar();
</script>
