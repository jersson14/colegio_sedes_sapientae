<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_asistencia_docente.js?rev=<?php echo time();?>"></script>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><b>ASISTENCIA DOCENTE</b></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">ASISTENCIA DOCENTE</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline card-outline-tabs">
      <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab_ad_dia" role="tab"><i class="fas fa-user-check"></i> Registrar día</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab_ad_mes" role="tab"><i class="fas fa-calendar-alt"></i> Resumen del mes</a></li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">
          <div class="tab-pane fade show active" id="tab_ad_dia" role="tabpanel">
            <div class="row">
              <div class="col-md-3 form-group"><label>Fecha:</label><input type="date" class="form-control" id="txt_fecha_ad" onchange="Cargar_Asistencia_Dia()"></div>
              <div class="col-md-3 form-group"><label>&nbsp;</label><button class="btn btn-outline-success btn-block" onclick="Marcar_Todos_Presentes()"><i class="fas fa-check-double"></i> Marcar todos presentes</button></div>
              <div class="col-md-3 form-group"><label>&nbsp;</label><button class="btn btn-success btn-block" onclick="Guardar_Asistencia_Docente()"><i class="fas fa-save"></i> Guardar asistencia del día</button></div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr><th>Docente</th><th>Contrato</th><th class="text-right" title="Horas de su horario ese día">Horario</th><th style="width:150px">Estado</th><th style="width:110px">Horas dictadas</th><th style="width:110px">Min. tardanza</th><th>Observación</th></tr>
                </thead>
                <tbody id="tbody_asistencia_docente"></tbody>
              </table>
            </div>
            <small class="text-muted">Las horas dictadas se proponen según el horario del docente. En los contratos <b>por horas</b>, la planilla del mes paga la suma de las horas dictadas. Una falta registra 0 horas. Cuando la planilla docente del mes se aprueba, su asistencia ya no se modifica.</small>
          </div>

          <div class="tab-pane fade" id="tab_ad_mes" role="tabpanel">
            <div class="row">
              <div class="col-md-2 form-group"><label>Año:</label><input type="number" class="form-control" id="txt_anio_ad"></div>
              <div class="col-md-3 form-group"><label>Mes:</label>
                <select class="form-control" id="select_mes_ad">
                  <option value="1">ENERO</option><option value="2">FEBRERO</option><option value="3">MARZO</option><option value="4">ABRIL</option>
                  <option value="5">MAYO</option><option value="6">JUNIO</option><option value="7">JULIO</option><option value="8">AGOSTO</option>
                  <option value="9">SEPTIEMBRE</option><option value="10">OCTUBRE</option><option value="11">NOVIEMBRE</option><option value="12">DICIEMBRE</option>
                </select>
              </div>
              <div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block" onclick="Resumen_Asistencia_Docente()"><i class="fas fa-search"></i> Ver</button></div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped" id="tabla_resumen_ad" style="width:100%">
                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr><th>Docente</th><th class="text-right">Días registrados</th><th class="text-right">Presente</th><th class="text-right">Tardanzas</th><th class="text-right">Min. tardanza</th><th class="text-right">Faltas</th><th class="text-right">Justificadas</th><th class="text-right">Horas de horario</th><th class="text-right">Horas dictadas</th></tr>
                </thead>
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
    Iniciar_Asistencia_Docente();
  });
</script>
