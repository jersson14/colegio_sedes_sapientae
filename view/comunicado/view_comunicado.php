<script src="../js/console_comunicados.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><b>MANTENIMIENTO DE COMUNICADOS</b></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
              <li class="breadcrumb-item active">COMUNICADOS</li>
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
                <h3 class="card-title"><i class="nav-icon fas fa-bullhorn"></i>&nbsp;&nbsp;<b>Listado de Comunicados</b></h3>
                <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
              </div>
              <div class="table-responsive" style="text-align:center">
              <div class="card-body">
              <table id="tabla_comunicados" class="table table-striped table-bordered" style="width:100%">
                  <thead style="background-color:#0A5D86;color:#FFFFFF;">
                      <tr>
                          <th style="text-align:center">Nro.</th>
                          <th style="text-align:center">Tipo</th>
                          <th style="text-align:center">Grado</th>
                          <th style="text-align:center">Título</th>
                          <th style="text-align:center">Descripción</th>
                          <th style="text-align:center">Vista</th>
                          <th style="text-align:center">Estado</th>
                          <th style="text-align:center">Acción</th>
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
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE COMUNICADOS</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-12 form-group">
              <label for="">Tipo comunicado<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_tipo" style="width:100%">
                  <option value="GENERAL">GENERAL</option>
                  <option value="POR GRADO">POR GRADO</option>
              </select>          
          </div>
          <div class="col-12 form-group" id="grado_container" >
              <label for="">Grado académico<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_grado" style="width:100%">
                  <!-- Opciones del grado académico -->
              </select>             
          </div>

          <div class="col-12 form-group">
            <label for="">Título<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_titulo">
          </div>
          <div class="col-12 form-group">
            <label for="">Decripción<b style="color:red">(*)</b>:</label>
            <textarea class="form-control" id="txt_descripcion" rows="3" style="resize:none"></textarea>
          </div>
          <div class="col-12">
              <label for="txt_foto">Subir Foto <b style="color:red">*</b>:</label>
              <div class="custom-file">
                  <input type="file" class="custom-file-input" id="txt_foto" accept="image/*" onchange="previewImage(event)">
                  <label class="custom-file-label" for="txt_foto">Seleccione Foto...</label>
              </div>
          </div>
          <div class="col-12" align="center" style="border: 2px solid black;padding: 10px;display: inline-block;">
              <br><img id="preview" src="#" alt="Vista previa" style="max-width: 100%; max-height: 150px;">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_Comunicado()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE COMUNICADOS</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-12 form-group">
              <label for="">Tipo comunicado<b style="color:red">(*)</b>:</label>
              <input type="text" id="txt_id_comu" hidden>
              <select class="form-control" id="select_tipo_editar" style="width:100%">
                  <option value="GENERAL">GENERAL</option>
                  <option value="POR GRADO">POR GRADO</option>
              </select>          
          </div>
          <div class="col-12 form-group" id="grado_container" >
              <label for="">Grado académico<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_grado_editar" style="width:100%">
                  <!-- Opciones del grado académico -->
              </select>             
          </div>

          <div class="col-12 form-group">
            <label for="">Título<b style="color:red">(*)</b>:</label>
            <input type="text" class="form-control" id="txt_titulo_editar">
          </div>
          <div class="col-12 form-group">
            <label for="">Decripción<b style="color:red">(*)</b>:</label>
            <textarea class="form-control" id="txt_descripcion_editar" rows="3" style="resize:none"></textarea>
          </div>
          <div class="col-6 form-group">
              <label for="">Estado<b style="color:red">(*)</b>:</label>
              <select name="" id="txt_estado" class="form-control">
                  <option value="ACTIVO">ACTIVO</option>
                  <option value="INACTIVO">INACTIVO</option>
              </select>                            
            </div>
          <div class="col-6">
            <input type="text" id="txt_foto_actual" hidden>

                <label for="txt_foto">Subir Foto <b style="color:red">*</b>:</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="txt_foto_editar" accept="image/*" onchange="previewImage3(event)">
                    <label class="custom-file-label" for="txt_foto_editar">Seleccione Foto...</label>
                </div>
            </div>

            <div class="col-12" align="center" style="border: 2px solid black;padding: 10px;display: inline-block;">
                <img id="preview3" src="#" alt="Vista previa" style="max-width: 100%; max-height: 150px;">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_comunicado()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_ver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <div class="modal-header" style="background-color:#fff; text-align:center; display: flex; justify-content: center; align-items: center;">
      <h5 class="" id="lb_titulo_datos2" style="color:black; margin-bottom: 0; width: 100%; text-align:center;">
      </h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 10px;">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
      <div class="modal-body">
        <div class="row">
          
          <div class="col-12 form-group">
            <label for="">Decripción<b style="color:red">(*)</b>:</label>
            <textarea class="form-control" id="txt_descripcion_ver" rows="3" style="resize:none" readonly></textarea>
          </div>

          <div class="col-12" align="center" style="border: 2px solid black; padding: 10px; display: inline-block; max-width: 100%; box-sizing: border-box;">
    <img id="preview4" src="#" alt="Vista previa" style="max-width: 100%; height: auto; display: block;">
</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>
    <script>
    $(document).ready(function () {
      listar_comunicado();
      $('.js-example-basic-single').select2();

      Cargar_Select_Grado();
    });
    $('#modal_registro').on('shown.bs.modal', function () {
      $('#txt_titulo').trigger('focus')
    })
    </script>
    <style>
.custom-file {
    position: relative;
    display: inline-block;
    width: 100%;
    height: calc(1.5em + .75rem + 2px);
    margin-bottom: 0;
}

.custom-file-input {
    position: relative;
    z-index: 2;
    width: 100%;
    height: calc(1.5em + .75rem + 2px);
    margin: 0;
    opacity: 0;
}

.custom-file-label {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    z-index: 1;
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: .25rem;
    box-shadow: inset 0 0 0 transparent;
}

.custom-file-input:focus ~ .custom-file-label {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.custom-file-label::after {
    content: "Subir Foto";
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    z-index: 3;
    display: block;
    height: calc(1.5em + .75rem);
    padding: .375rem .75rem;
    line-height: 1.5;
    color: #fff;
    background-color: #007bff;
    border-left: 1px solid #ced4da;
    border-radius: 0 .25rem .25rem 0;
}
</style>

<script>
    $(document).ready(function() {
        // Inicializa Select2
        $('#select_grado').select2();

        // Maneja el cambio de select_tipo
        $('#select_tipo').on('change', function() {
            if (this.value === 'POR GRADO') {
                $('#select_grado').prop('disabled', false);
            } else {
                $('#select_grado').prop('disabled', true);
                // Reset Select2 to apply the disabled state correctly
                $('#select_grado').select2();
            }
        });

        // Inicializa el select_grado como deshabilitado si está en "GENERAL"
        if ($('#select_tipo').val() === 'GENERAL') {
            $('#select_grado').prop('disabled', true);
            $('#select_grado').select2(); // Reaplicar Select2 para reflejar el estado deshabilitado
        }
    });
</script>

<script>
  
</script>
<script>
    function previewImage(event) {
        var input = event.target;
        var preview = document.getElementById('preview');

        var reader = new FileReader();
        reader.onload = function(){
            preview.src = reader.result;
        };
        reader.readAsDataURL(input.files[0]);

        // Actualizar la etiqueta del input file con el nombre del archivo seleccionado
        var fileName = input.files[0].name;
        var label = input.nextElementSibling;
        label.innerHTML = "Subir Foto (" + fileName + ")";
    }
</script>

<script>
    function previewImage3(event) {
        var input = event.target;
        var preview3 = document.getElementById('preview3');

        var reader = new FileReader();
        reader.onload = function(){
            preview3.src = reader.result;
        };
        reader.readAsDataURL(input.files[0]);

        // Actualizar la etiqueta del input file con el nombre del archivo seleccionado
        var fileName = input.files[0].name;
        var label = input.nextElementSibling;
        label.innerHTML = "Subir Foto (" + fileName + ")";
    }
</script>
<script>
    function previewImage4(event) {
        var input = event.target;
        var preview3 = document.getElementById('preview4');

        var reader = new FileReader();
        reader.onload = function(){
            preview3.src = reader.result;
        };
        reader.readAsDataURL(input.files[0]);

        // Actualizar la etiqueta del input file con el nombre del archivo seleccionado
        var fileName = input.files[0].name;
        var label = input.nextElementSibling;
    }
</script>