<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_reportes.js?rev=<?php echo time();?>"></script>
<style>
  #rf_resultados .fila-grupo td { background-color: #e9f1f6; font-weight: bold; }
  #rf_resultados .fila-detalle td:first-child { padding-left: 2.5rem; color: #6c757d; }
  #rf_resultados .fila-categoria td:first-child { padding-left: 1.2rem; }
  #rf_resultados .fila-total td { font-weight: bold; border-top: 2px solid #0A5D86; }
  .rf-tarjeta h4 { margin-bottom: 0; }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Reportes financieros</h1>
        <p class="pagina-ayuda">Ingresos, gastos y pensiones por periodo.</p>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Reportes financieros</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <!-- Filtros: cada pestaña usa los que le corresponden -->
    <div class="card">
      <div class="card-body pb-1">
        <div class="row">
          <div class="col-md-2 form-group rf-filtro" data-usa="periodo"><label>Desde:</label><input type="date" class="form-control" id="rf_desde"></div>
          <div class="col-md-2 form-group rf-filtro" data-usa="periodo"><label>Hasta:</label><input type="date" class="form-control" id="rf_hasta"></div>
          <div class="col-md-3 form-group rf-filtro" data-usa="periodo"><label>Período rápido:</label>
            <select class="form-control" id="rf_rapido">
              <option value="">— elegir —</option>
              <option value="hoy">Hoy</option>
              <option value="mes">Este mes</option>
              <option value="mes_anterior">Mes anterior</option>
              <option value="anio">Este año</option>
              <option value="anio_escolar">Año escolar activo</option>
            </select>
          </div>
          <div class="col-md-2 form-group rf-filtro" data-usa="anio"><label>Año:</label><input type="number" class="form-control" id="rf_anio" min="2000" max="2100"></div>
          <div class="col-md-3 form-group rf-filtro" data-usa="escolar"><label>Año escolar:</label><select class="form-control" id="rf_anio_escolar"></select></div>
          <div class="col-md-2 form-group rf-filtro" data-usa="aula"><label>Nivel:</label><select class="form-control" id="rf_nivel"><option value="0">TODOS</option></select></div>
          <div class="col-md-2 form-group rf-filtro" data-usa="aula"><label>Grado / sección:</label><select class="form-control" id="rf_aula"><option value="0">TODOS</option></select></div>
          <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Cargar_Reporte()"><i class="fas fa-search"></i> Ver reporte</button></div>
        </div>
      </div>
    </div>

    <div class="card card-primary card-outline card-outline-tabs">
      <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist" id="rf_tabs">
          <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#rf_tab_resultados" data-reporte="resultados" data-filtros="periodo"><i class="fas fa-balance-scale"></i> Utilidad</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_mensual" data-reporte="mensual" data-filtros="anio"><i class="fas fa-chart-bar"></i> Comparativo mensual</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_flujo" data-reporte="flujo" data-filtros="periodo"><i class="fas fa-water"></i> Flujo de caja</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_metodos" data-reporte="metodos" data-filtros="periodo"><i class="fas fa-credit-card"></i> Formas de pago</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_morosidad" data-reporte="morosidad" data-filtros="escolar,aula"><i class="fas fa-user-clock"></i> Morosidad</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_proyeccion" data-reporte="proyeccion" data-filtros="escolar"><i class="fas fa-bullseye"></i> Proyección de pensiones</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_planilla" data-reporte="planilla" data-filtros="anio"><i class="fas fa-file-invoice-dollar"></i> Costo de planilla</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#rf_tab_cierres" data-reporte="cierres" data-filtros="periodo"><i class="fas fa-cash-register"></i> Cierres de caja</a></li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">

          <!-- Estado de resultados -->
          <div class="tab-pane fade show active" id="rf_tab_resultados">
            <div class="row text-center" id="rf_resultados_tarjetas"></div>
            <div class="mb-2 text-right"><button class="btn btn-danger btn-sm" onclick="Abrir_PDF('estado_resultados')"><i class="fas fa-file-pdf"></i> PDF</button></div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered" id="rf_resultados" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Concepto</th><th class="text-right">Operaciones</th><th class="text-right">Monto</th><th class="text-right">% del total</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
            <small class="text-muted">Incluye los ingresos y egresos válidos con fecha dentro del período (los anulados no suman). Los pagos de la planilla docente están en Remuneraciones.</small>
          </div>

          <!-- Comparativo mensual -->
          <div class="tab-pane fade" id="rf_tab_mensual">
            <div class="row text-center" id="rf_mensual_tarjetas"></div>
            <div style="height:320px"><canvas id="rf_mensual_grafico"></canvas></div>
            <div class="table-responsive mt-3">
              <table class="table table-sm table-bordered table-striped" id="rf_mensual" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Mes</th><th class="text-right">Ingresos</th><th class="text-right">Egresos</th><th class="text-right">Utilidad</th><th class="text-right">Ingresos año anterior</th><th class="text-right">Egresos año anterior</th><th class="text-right">Utilidad año anterior</th></tr></thead>
                <tbody></tbody>
                <tfoot></tfoot>
              </table>
            </div>
          </div>

          <!-- Flujo de caja -->
          <div class="tab-pane fade" id="rf_tab_flujo">
            <div class="mb-2 text-right"><button class="btn btn-danger btn-sm" onclick="Abrir_PDF('flujo_caja')"><i class="fas fa-file-pdf"></i> PDF</button></div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped" id="rf_flujo" style="width:100%">
                <thead class="tabla-cabecera">
                  <tr><th>Caja / cuenta</th><th>Tipo</th><th class="text-right">Saldo inicial</th><th class="text-right">Cobros</th><th class="text-right">Transf. recibidas</th><th class="text-right">Ajustes (+)</th><th class="text-right">Pagos</th><th class="text-right">Transf. enviadas</th><th class="text-right">Ajustes (−)</th><th class="text-right">Saldo final</th></tr>
                </thead>
                <tbody></tbody>
                <tfoot></tfoot>
              </table>
            </div>
            <div id="rf_flujo_sin_cuenta"></div>
            <small class="text-muted">Cobros = recibos de pensiones/matrícula e ingresos; Pagos = egresos (incluida la planilla). Las transferencias mueven dinero entre cuentas y no cambian el total.</small>
          </div>

          <!-- Formas de pago -->
          <div class="tab-pane fade" id="rf_tab_metodos">
            <div class="row">
              <div class="col-lg-6">
                <h5 class="text-success"><b>Cobros por forma de pago</b></h5>
                <table class="table table-sm table-bordered table-striped" id="rf_metodos_ingreso" style="width:100%">
                  <thead class="tabla-cabecera"><tr><th>Forma de pago</th><th>Caja / cuenta</th><th class="text-right">Operaciones</th><th class="text-right">Monto</th><th class="text-right">Comisión</th><th class="text-right">%</th></tr></thead>
                  <tbody></tbody><tfoot></tfoot>
                </table>
              </div>
              <div class="col-lg-6">
                <h5 class="text-danger"><b>Pagos por forma de pago</b></h5>
                <table class="table table-sm table-bordered table-striped" id="rf_metodos_egreso" style="width:100%">
                  <thead class="tabla-cabecera"><tr><th>Forma de pago</th><th>Caja / cuenta</th><th class="text-right">Operaciones</th><th class="text-right">Monto</th><th class="text-right">Comisión</th><th class="text-right">%</th></tr></thead>
                  <tbody></tbody><tfoot></tfoot>
                </table>
              </div>
            </div>
            <small class="text-muted">"No registrado (histórico)" son cobros anteriores a la Fase 4, cuando aún no se guardaba la forma de pago.</small>
          </div>

          <!-- Morosidad -->
          <div class="tab-pane fade" id="rf_tab_morosidad">
            <div class="row text-center" id="rf_morosidad_tarjetas"></div>
            <div id="rf_morosidad_avisos"></div>
            <div class="mb-2 text-right">
              <div class="btn-group btn-group-sm mr-2">
                <button class="btn btn-outline-primary active" id="rf_moro_ver_alumnos" onclick="Vista_Morosidad('alumnos')">Por alumno</button>
                <button class="btn btn-outline-primary" id="rf_moro_ver_detalle" onclick="Vista_Morosidad('detalle')">Por pensión</button>
                <button class="btn btn-outline-primary" id="rf_moro_ver_aulas" onclick="Vista_Morosidad('aulas')">Por grado</button>
              </div>
              <button class="btn btn-danger btn-sm" onclick="Abrir_PDF('morosidad')"><i class="fas fa-file-pdf"></i> PDF para cobranza</button>
            </div>
            <div class="table-responsive rf-moro" id="rf_moro_alumnos">
              <table class="table table-sm table-bordered table-striped" id="rf_morosidad_alumnos" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Alumno</th><th>Documento</th><th>Nivel</th><th>Grado</th><th>Teléfonos</th><th>Meses vencidos</th><th class="text-right">Máx. días</th><th class="text-right">Pensión</th><th class="text-right">Mora</th><th class="text-right">Deuda</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="table-responsive rf-moro" id="rf_moro_detalle" style="display:none">
              <table class="table table-sm table-bordered table-striped" id="rf_morosidad_detalle" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Alumno</th><th>Nivel</th><th>Grado</th><th>Mes</th><th>Vencimiento</th><th class="text-right">Días de atraso</th><th class="text-right">Precio</th><th class="text-right">Pagado</th><th class="text-right">Saldo pensión</th><th class="text-right">Mora</th><th class="text-right">Deuda</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="table-responsive rf-moro" id="rf_moro_aulas" style="display:none">
              <table class="table table-sm table-bordered table-striped" id="rf_morosidad_aulas" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Nivel › grado</th><th class="text-right">Alumnos con deuda</th><th class="text-right">Deuda</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
            <small class="text-muted">Pensiones vencidas (no pagadas completas hasta su vencimiento) con saldo pendiente a hoy. La mora sigue la misma regla que la pantalla de cobro.</small>
          </div>

          <!-- Proyección -->
          <div class="tab-pane fade" id="rf_tab_proyeccion">
            <div class="row text-center" id="rf_proyeccion_tarjetas"></div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped" id="rf_proyeccion" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Mes</th><th>Vencimiento</th><th class="text-right">Matrículas</th><th class="text-right">Esperado</th><th class="text-right">Cobrado</th><th class="text-right">Vencido sin cobrar</th><th class="text-right">Mora pendiente</th><th class="text-right">Por vencer</th><th style="width:16%">Avance</th></tr></thead>
                <tbody></tbody><tfoot></tfoot>
              </table>
            </div>
            <small class="text-muted">Esperado = precio de la pensión del nivel × alumnos matriculados en ese nivel. No incluye matrícula ni admisión.</small>
          </div>

          <!-- Planilla -->
          <div class="tab-pane fade" id="rf_tab_planilla">
            <div class="row" id="rf_planilla_tipos"></div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped" id="rf_planilla" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Mes</th><th>Estado</th><th>Tipo de contrato</th><th class="text-right">Docentes</th><th class="text-right">Horas</th><th class="text-right">Sueldo / horas</th><th class="text-right">Bonos y otros</th><th class="text-right">Costo bruto</th><th class="text-right">Descuentos</th><th class="text-right">Adelantos</th><th class="text-right">Neto</th><th class="text-right">Pagado</th></tr></thead>
                <tbody></tbody><tfoot></tfoot>
              </table>
            </div>
            <small class="text-muted">Costo bruto = sueldo u horas + bonos y otros ingresos (lo que cuesta al colegio). No incluye planillas anuladas.</small>
          </div>

          <!-- Cierres de caja -->
          <div class="tab-pane fade" id="rf_tab_cierres">
            <div class="row text-center" id="rf_cierres_tarjetas"></div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped" id="rf_cierres" style="width:100%">
                <thead class="tabla-cabecera"><tr><th>Fecha</th><th>Caja</th><th class="text-right">Saldo inicial</th><th class="text-right">Entradas</th><th class="text-right">Salidas</th><th class="text-right">Esperado</th><th class="text-right">Contado</th><th class="text-right">Diferencia</th><th>Ajustado</th><th>Observación</th><th>Usuario</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    Iniciar_Reportes();
  });
</script>
