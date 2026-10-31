<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_pago_pension.js?rev=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Cobrar pensión</h1>
        <p class="pagina-ayuda">Registra los pagos de pensión y emite el comprobante.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Cobrar pensión</li>
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
      <!-- /.col-md-6 -->
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Pago de Pensiones</b></h3>
          </div>
          <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-2 form-group">
                                    <label for="">Año académico<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_año_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-2 form-group">
                                    <label for="">Grado o Aula<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_aula_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_pago_pension_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar pagos</button>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_pago_pension()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
            <div class="card-body">
              <table id="tabla_pago_pension" class="table table-striped table-bordered" style="width:100%">
                <thead class="tabla-cabecera">
                  <tr>
                    <th style="text-align:center">Nro.</th>
                    <th style="text-align:center">DNI</th>
                    <th style="text-align:center">Estudiante</th>
                    <th style="text-align:center">Aula o Grado</th>
                    <th style="text-align:center">Nivel Académico</th>
                    <th style="text-align:center">Año escolar</th>
                    <th style="text-align:center">Ultimo pago</th>
                    <th style="text-align:center">Acciones</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

        </div>
        <!-- /.col-md-6 -->
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->

  <!-- Modal -->
  <div class="modal fade" id="modal_registro" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header ventana-cabecera">
          <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>PAGO DE PENSIONES</b></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning alert-dismissible" style="text-align:justify">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Aviso Importante!</h5>
            El <b>monto sugerido</b> es el saldo pendiente de la pensión e <b>incluye la mora</b> si se pagó fuera de la fecha de vencimiento.
            Puede registrar un <b>pago parcial</b> con un monto menor (se aplica primero a la mora y luego a la pensión).
            Para no cobrar la mora marque <b>Exonerar mora</b> e indique el motivo.
          </div>
          <div class="row">
            <div class="col-12 form-group text-danger">
              <h6><b>Campos Obligatorios (*)</b></h6>
            </div>
            <div class="col-6 form-group">
              <label for="">Concepto<b class="text-danger">(*)</b>:</label>
              <input type="date" id="fecha_pago" hidden>
              <input type="text" class="form-control" id="txt_id_matricula" hidden>
              <select class="form-control" id="select_concepto" style="width:100%">
                <option value="PENSION">PENSION</option>
                <option value="MATRICULA">MATRICULA</option>
                <option value="ALUMNO NUEVO">ALUMNO NUEVO</option>
                <option value="ADMISION">ADMISION</option>
              </select>
            </div>
            <div class="col-6 form-group">
              <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_nivel" style="width:100%">
              </select>
            </div>
            <div class="col-6 form-group">
              <label for="">Pensión<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_pension" style="width:100%">
              </select>
            </div>
            <div class="col-6 form-group">
              <label for="">Monto a Pagar<b class="text-danger">(*)</b>:</label>
              <input type="text" class="form-control" id="txt_monto_pagar">
            </div>
            <div class="col-6 form-group">
                <label for="">Fecha vencimiento<b class="text-danger">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_ven" disabled>
            </div>
            <div class="col-6 form-group">
                <label for="">Estado<b class="text-danger">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_estado" disabled>
            </div>
            <div class="col-6 form-group">
                <label for="">Mora pendiente:</label>
                <input type="text" class="form-control" id="txt_mora" disabled>
            </div>
            <div class="col-6 form-group" id="div_exonerar_mora" style="display:none">
                <label>&nbsp;</label>
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="chk_exonerar_mora">
                  <label class="custom-control-label" for="chk_exonerar_mora"><b>Exonerar mora</b></label>
                </div>
            </div>
            <div class="col-12 form-group" id="div_motivo_mora" style="display:none">
                <label for="">Motivo de la exoneración<b class="text-danger">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_motivo_mora" maxlength="200">
            </div>
            <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_pago()">
                <i class="fas fa-plus"></i> <b>Agregar Pago</b>
              </button>
            </div>
            <div class="col-12 table-responsive" style="text-align:center">
              <table id="tabla_pago" style="width:100%" class="table">
                <thead class="thead-dark">
                  <tr>
                    <th>Id.</th>
                    <th>Concepto</th>
                    <th>Id pen</th>
                    <th>Pensión</th>
                    <th>Subtotal</th>
                    <th>Acci&oacute;n</th>
                  </tr>
                </thead>
                <tbody id="tbody_tabla_pago">
                </tbody>
              </table>
              <div class="col-9">
              </div>
              <div class="col-3">
                <h3 for="" id="lbl_totalneto"></h3>
              </div>
            </div>
            <div class="col-12">
              <h6><b><i class="fas fa-money-check-alt"></i> Forma de pago</b> <small class="text-muted">(puede dividirse, por ejemplo una parte en efectivo y otra con tarjeta)</small></h6>
            </div>
            <div class="col-12 table-responsive">
              <table class="table table-sm table-bordered" id="tabla_formas_pago">
                <thead class="tabla-cabecera">
                  <tr>
                    <th style="width:28%">Método</th>
                    <th style="width:28%">Caja / cuenta</th>
                    <th style="width:16%">Monto</th>
                    <th>N.° operación / referencia</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="tbody_formas_pago"></tbody>
              </table>
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="Agregar_forma_pago()"><i class="fas fa-plus"></i> Agregar otra forma de pago</button>
              <span class="float-right" id="lbl_diferencia_pago"></span>
            </div>
            <div class="col-md-6 form-group mt-2">
              <label for="file_voucher">Voucher (opcional, imagen o PDF):</label>
              <input type="file" class="form-control" id="file_voucher" accept="image/jpeg,image/png,image/webp,application/pdf">
            </div>
            <div class="col-md-6 form-group mt-2">
              <label for="txt_observacion_pago">Observación (opcional):</label>
              <input type="text" class="form-control" id="txt_observacion_pago" maxlength="255">
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
          <button type="button" class="btn btn-success" onclick="Registrar_Pago()"><i class="fas fa-save"></i> Registrar</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modal_ver_pagos" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lb_titulo"></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12" style="text-align:center">
              <div class="table-responsive" style="text-align:center">
                <div class="card-body">
                  <table id="tabla_pagos2" class="display compact" style="width:100%; text-align:center;">
                    <thead class="tabla-cabecera">
                      <tr style="text-align:center;">
                        <th style="text-align:center;">Nro.</th>
                        <th style="text-align:center;">Recibo</th>
                        <th style="text-align:center;">Concepto</th>
                        <th style="text-align:center;">Mes</th>
                        <th style="text-align:center;">Fecha de Pago</th>
                        <th style="text-align:center;">Forma de pago</th>
                        <th style="text-align:center;">Subtotal</th>
                        <th style="text-align:center;">Acción</th>
                      </tr>
                    </thead>
                    <tfoot>
                      <tr>
                        <th colspan="6" style="text-align:right;">Total:</th>
                        <th style="text-align:center;" id="total_sub_total"><?php echo formato_moneda(0); ?></th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modal_editar2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>DATOS DE MODIFICACIÓN</b></h5>

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-12 form-group">
        <h5 class="modal-title text-danger" id="lb_titulo4"></h5>

          </div>
          <div class="col-12 form-group">
            <label for="">Monto pago<b class="text-danger">(*)</b>:</label>
            <input class="form-control" type="text" id="txt_monto_edita" onkeypress="return soloNumeros(event)">
            <input type="text" id="txt_id_pagopension" hidden>
          </div>
          <div class="col-12 form-group">
            <label for="">Motivo de edición<b class="text-danger">(*)</b>:</label>
            <textarea name="" id="txt_observación_motivo" rows="3" class="form-control" style="resize:none;"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Editar_pago()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>

  <style>
    .hidden {
      display: none;
    }
  </style>

  <script>
    $(document).ready(function() {
      listar_pago_pension();
      $('.js-example-basic-single').select2();
      Cargar_Select_Nivelaca();
      Cargar_Select_pensiones();
      Cargar_Select_Nivelaca2();
      Cargar_Año();
    });


    $("#select_nivel").change(function() {
      var id = $("#select_nivel").val();
      Cargar_Select_pensiones(id);
    });

    $("#select_pension").change(function() {
      var id = $("#select_pension").val();
      Traerpension(id);
    });
    $("#select_nivel_buscar").change(function() {
      var id = $("#select_nivel_buscar").val();
      Cargar_Select_Aula(id);
    });
    $('#modal_registro').on('shown.bs.modal', function() {
      $('#txt_matricula').trigger('focus')
    })
    var n = new Date();
var y = n.getFullYear();
var m = n.getMonth() + 1;  // Los meses empiezan desde 0, por eso se suma 1
var d = n.getDate();

// Si el día o el mes es menor a 10, se le agrega un '0' al inicio
if (d < 10) {
    d = '0' + d;
}
if (m < 10) {
    m = '0' + m;
}

// Establece el valor del campo de fecha con el formato YYYY-MM-DD
document.getElementById('fecha_pago').value = y + "-" + m + "-" + d;
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var camposAdicionales = [
        'txt_procedencia',
        'txt_provincia',
        'txt_departamento',
        'txt_usu',
        'txt_contra',
        'txt_correo'
      ];

      camposAdicionales.forEach(function(id) {
        var element = document.getElementById(id);
        if (element) {
          element.parentElement.style.display = 'none';
        }
      });
    });

    function Validar_Informacion() {
      var isChecked = document.getElementById('checkboxSuccess1').checked;

      // Habilitar o deshabilitar campos de pago
      document.getElementById('txt_admision').disabled = !isChecked;
      document.getElementById('txt_alum_nuevo').disabled = !isChecked;

      // Mostrar u ocultar campos adicionales
      var camposAdicionales = [
        'txt_procedencia',
        'txt_provincia',
        'txt_departamento',
        'txt_usu',
        'txt_contra',
        'txt_correo'
      ];

      camposAdicionales.forEach(function(id) {
        var element = document.getElementById(id);
        if (element) {
          element.parentElement.style.display = isChecked ? 'block' : 'none';
        }
      });
    }



  </script>