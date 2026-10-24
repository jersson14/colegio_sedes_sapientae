<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_regional.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>DATOS REGIONALES</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">DATOS REGIONALES</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-7">
        <div class="card card-primary card-outline">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-globe-americas"></i>&nbsp;&nbsp;<b>País, moneda y documento de identidad</b></h3>
          </div>
          <div class="card-body">
            <div class="alert alert-info" style="text-align:justify">
              El colegio trabaja en <b>un solo país y una sola moneda</b>. Con esta configuración el sistema muestra los montos,
              valida el documento de identidad y usa la hora local en todos los módulos y reportes.
            </div>
            <div class="row">
              <div class="col-md-6 form-group">
                <label for="select_pais">País<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_pais" style="width:100%"></select>
              </div>
              <div class="col-md-6 form-group">
                <label for="select_moneda">Moneda<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_moneda" style="width:100%"></select>
              </div>
              <div class="col-md-6 form-group">
                <label for="select_zona">Zona horaria<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="select_zona" style="width:100%"></select>
              </div>
              <div class="col-md-6 form-group">
                <label for="txt_id_tributario" id="lbl_id_tributario">Identificación tributaria del colegio:</label>
                <input type="text" class="form-control" id="txt_id_tributario" maxlength="20">
              </div>
              <div class="col-12 form-group">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_doc_extranjero">
                  <label class="custom-control-label" for="chk_doc_extranjero">
                    Aceptar también pasaporte o carné de extranjería (alumnos, padres o personal extranjeros)
                  </label>
                </div>
              </div>
            </div>
            <h5 class="mt-2"><b>Escala de notas</b> <small class="text-muted">(se propone la del país; ajústela si su colegio usa otra)</small></h5>
            <div class="row">
              <div class="col-md-3 col-6 form-group"><label>Nota mínima:</label><input type="text" inputmode="decimal" class="form-control" id="txt_nota_min"></div>
              <div class="col-md-3 col-6 form-group"><label>Nota máxima:</label><input type="text" inputmode="decimal" class="form-control" id="txt_nota_max"></div>
              <div class="col-md-3 col-6 form-group"><label>Aprueba con:</label><input type="text" inputmode="decimal" class="form-control" id="txt_nota_aprobatoria"></div>
              <div class="col-md-3 col-6 form-group"><label>Decimales:</label>
                <select class="form-control" id="select_nota_decimales"><option value="0">0</option><option value="1">1</option><option value="2">2</option></select>
              </div>
              <div class="col-md-6 form-group"><label>Calificaciones con letras (opcional):</label><input type="text" class="form-control" id="txt_notas_literales" maxlength="60" placeholder="p. ej. AD,A,B,C"></div>
              <div class="col-md-6 form-group"><label>Letras que aprueban:</label><input type="text" class="form-control" id="txt_literales_aprobatorios" maxlength="60" placeholder="p. ej. AD,A,B"></div>
            </div>
            <h5 class="mt-2"><b>Vista previa</b></h5>
            <table class="table table-sm table-bordered">
              <tr><th style="width:45%">Monto de ejemplo</th><td id="prev_monto"></td></tr>
              <tr><th>Documento de identidad</th><td id="prev_documento"></td></tr>
              <tr><th>Prefijo telefónico</th><td id="prev_prefijo"></td></tr>
              <tr><th>Notas aceptadas</th><td id="prev_escala"></td></tr>
            </table>
          </div>
          <div class="card-footer">
            <button class="btn btn-success float-right" onclick="Guardar_Regional()"><i class="fas fa-save"></i> <b>Guardar</b></button>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history"></i>&nbsp;&nbsp;<b>Últimos cambios</b></h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm table-striped">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                <tr><th>Fecha</th><th>Campo</th><th>Antes</th><th>Ahora</th><th>Usuario</th></tr>
              </thead>
              <tbody id="tbody_historial"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    Cargar_Regional();
  });
</script>
