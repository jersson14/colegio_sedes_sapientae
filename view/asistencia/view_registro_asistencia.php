<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?>
<script src="../js/registro_asistencia.js?rev=<?php echo time(); ?>"></script>
<style>
  .ra-leyenda span { display: inline-block; min-width: 26px; text-align: center; border-radius: 4px; font-weight: bold; margin-right: 4px; padding: 1px 6px; }
  .ra-P { background: #c8f0cf; color: #146c2e; }
  .ra-T { background: #ffe6a6; color: #8a5a00; }
  .ra-F { background: #f8c9c9; color: #a01818; }
  .ra-J { background: #cfe2ff; color: #0b4aa2; }
  .ra-scroll { overflow: auto; max-height: 70vh; border: 1px solid #dee2e6; }
  .ra-tabla { border-collapse: separate; border-spacing: 0; font-size: 12px; min-width: 100%; }
  .ra-tabla th, .ra-tabla td { border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; padding: 0; text-align: center; white-space: nowrap; }
  .ra-tabla thead th { position: sticky; z-index: 2; background: #f4f6f9; }
  .ra-tabla thead tr:first-child th { top: 0; height: 26px; }
  .ra-tabla thead tr:nth-child(2) th { top: 26px; }
  .ra-tabla .ra-fija { position: sticky; left: 0; z-index: 1; background: #fff; text-align: left; padding: 3px 8px; }
  .ra-tabla .ra-num { position: sticky; left: 0; z-index: 1; background: #fff; min-width: 34px; }
  .ra-tabla .ra-nombre { left: 34px; min-width: 230px; max-width: 280px; overflow: hidden; text-overflow: ellipsis; }
  .ra-tabla thead .ra-fija, .ra-tabla thead .ra-num { z-index: 3; background: #f4f6f9; }
  .ra-dia { min-width: 30px; cursor: pointer; line-height: 1.1; padding: 2px !important; }
  .ra-dia:hover { filter: brightness(.92); }
  .ra-dia.ra-hoy { outline: 2px solid #1FA0E0; outline-offset: -2px; }
  .ra-dia.ra-fuera { opacity: .55; }
  .ra-celda { width: 30px; height: 26px; cursor: pointer; font-weight: bold; user-select: none; }
  .ra-celda:focus { outline: 2px solid #1FA0E0; outline-offset: -2px; }
  .ra-celda.ra-bloq { background: #f1f1f1; cursor: not-allowed; }
  .ra-celda.ra-cambio { box-shadow: inset 0 0 0 2px #ff9800; }
  .ra-celda.ra-col-hoy:not(.ra-P):not(.ra-T):not(.ra-F):not(.ra-J) { background: #eef8fd; }
  .ra-tot { min-width: 34px; font-weight: bold; padding: 3px !important; }
  .ra-sem1 { background: #d9d4f5 !important; } .ra-sem2 { background: #c9eeee !important; } .ra-sem3 { background: #fdeeb5 !important; }
  .ra-sem4 { background: #f5d2ee !important; } .ra-sem5 { background: #d4efd2 !important; } .ra-sem6 { background: #e6e6e6 !important; }
  .ra-tabla tfoot td { background: #f4f6f9; font-weight: bold; position: sticky; bottom: 0; }
  #ra_pendientes { display: none; }
  .ra-celda.ra-obs { background-image: linear-gradient(225deg, #6c5ce7 6px, transparent 6px); }
  /* Modo Día: un día a la vez, botones grandes para el celular */
  .ra-modo .btn.active { background: var(--tema-primario); border-color: var(--tema-primario); color: var(--tema-primario-texto); }
  #ra_dia { max-width: 860px; margin: 0 auto; }
  .ra-dia-nav { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
  .ra-dia-fecha { flex: 1; text-align: center; font-size: 1.05rem; }
  .ra-hoy-chip { margin-left: 8px; background: var(--tema-acento); color: var(--tema-acento-texto); border-radius: 999px; padding: 1px 9px; font-size: .75rem; font-weight: 700; }
  .ra-dia-resumen { text-align: center; color: #555; margin-bottom: 10px; }
  .ra-dia-resumen span { border-radius: 4px; padding: 0 5px; font-weight: 600; }
  .ra-lista { list-style: none; margin: 0; padding: 0; }
  .ra-fila { display: grid; grid-template-columns: 30px 1fr auto; grid-template-areas: "num nombre botones" ". obs obs"; gap: 6px 10px; align-items: center;
             padding: 8px 6px; border-bottom: 1px solid #eee; }
  .ra-fila.ra-cambio { background: #fff8e6; }
  .ra-fila-num { grid-area: num; color: #888; text-align: right; font-variant-numeric: tabular-nums; }
  .ra-fila-nombre { grid-area: nombre; font-weight: 600; }
  .ra-botones { grid-area: botones; display: flex; gap: 6px; }
  .ra-btn { width: 44px; height: 40px; border: 1px solid #d5d5d5; border-radius: 8px; background: #fff; font-weight: 700; color: #666; }
  .ra-btn.activo { border-color: currentColor; box-shadow: 0 0 0 1px currentColor inset; }
  .ra-btn.activo.ra-P { background: #c8f0cf; } .ra-btn.activo.ra-T { background: #ffe6a6; }
  .ra-btn.activo.ra-F { background: #f8c9c9; } .ra-btn.activo.ra-J { background: #cfe2ff; }
  .ra-btn:focus-visible { outline: 2px solid var(--tema-acento); outline-offset: 2px; }
  .ra-obs-input { grid-area: obs; }
  @media (max-width: 575.98px) {
    .ra-fila { grid-template-columns: 26px 1fr; grid-template-areas: "num nombre" ". botones" ". obs"; }
    .ra-btn { flex: 1; width: auto; height: 44px; }
    .ra-botones { width: 100%; }
  }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1 class="m-0 pagina-titulo">Registro de asistencia</h1>
        <p class="pagina-ayuda">Día por día o el mes completo de un aula.</p>
      </div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Registro de asistencia</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-body">
        <div class="btn-group ra-modo mb-3" role="group" aria-label="Forma de registrar">
          <button type="button" class="btn btn-outline-secondary" data-modo="dia" aria-pressed="false"><i class="fas fa-calendar-day mr-1"></i>Por día</button>
          <button type="button" class="btn btn-outline-secondary" data-modo="mes" aria-pressed="false"><i class="fas fa-table mr-1"></i>Mes completo</button>
        </div>
        <div class="row">
          <div class="col-md-2 col-6 form-group">
            <label for="ra_anio">Año escolar</label>
            <select id="ra_anio" class="form-control"></select>
          </div>
          <div class="col-md-3 col-6 form-group">
            <label for="ra_aula">Aula</label>
            <select id="ra_aula" class="form-control"></select>
          </div>
          <div class="col-md-3 col-12 form-group ra-solo-mes">
            <label for="ra_mes">Mes</label>
            <div class="input-group">
              <div class="input-group-prepend"><button class="btn btn-outline-secondary" type="button" onclick="RegistroAsistencia.moverMes(-1)" title="Mes anterior"><i class="fas fa-chevron-left"></i></button></div>
              <input type="month" id="ra_mes" class="form-control px-1">
              <div class="input-group-append"><button class="btn btn-outline-secondary" type="button" onclick="RegistroAsistencia.moverMes(1)" title="Mes siguiente"><i class="fas fa-chevron-right"></i></button></div>
            </div>
          </div>
          <div class="col-md-4 col-12 form-group">
            <label class="d-none d-md-block">&nbsp;</label>
            <div class="d-flex flex-wrap" style="gap:6px">
              <button class="btn btn-success" id="ra_btn_hoy" onclick="RegistroAsistencia.todosPresentesHoy()"><i class="fas fa-check-double"></i> Hoy: todos presentes</button>
              <button class="btn btn-primary" onclick="RegistroAsistencia.guardar()"><i class="fas fa-save"></i> Guardar <span id="ra_pendientes" class="badge badge-warning"></span></button>
              <div class="btn-group">
                <button class="btn btn-outline-danger" onclick="RegistroAsistencia.pdf()" title="Planilla para imprimir"><i class="fas fa-file-pdf"></i> PDF</button>
                <button class="btn btn-outline-success" onclick="RegistroAsistencia.excel()" title="Descargar en Excel"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn btn-outline-secondary" onclick="RegistroAsistencia.pdf(true)" title="Planilla sin marcas para llenar a mano"><i class="fas fa-print"></i> En blanco</button>
              </div>
            </div>
          </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2" style="gap:8px">
          <div class="ra-leyenda">
            <span class="ra-P">P</span>Presente &nbsp; <span class="ra-T">T</span>Tardanza &nbsp; <span class="ra-F">F</span>Falta &nbsp; <span class="ra-J">J</span>Justificada
          </div>
          <small class="text-muted ra-solo-mes"><i class="fas fa-mouse-pointer"></i> Clic en la casilla: P → T → F → J → vacío ·
            <i class="fas fa-keyboard"></i> Flechas para moverse y teclas P, T, F, J (Supr borra) ·
            Clic en el día para marcar a todos</small>
        </div>
        <div id="ra_aviso"></div>
        <div class="ra-scroll ra-solo-mes"><table class="ra-tabla" id="ra_tabla"></table></div>
        <div id="ra_dia" style="display:none"></div>
      </div>
    </div>
  </div>
</div>
<script>
  RegistroAsistencia.iniciar();
</script>
