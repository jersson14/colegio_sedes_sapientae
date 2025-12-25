<script src="../js/console_docentes.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE DOCENTES</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">DOCENTES</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Docentes</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_docentes" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Foto</th>
                      <th style="text-align:center">DNI</th>
                      <th style="text-align:center">Docente</th>
                      <th style="text-align:center">Especialidad</th>
                      <th style="text-align:center">Sexo</th>

                      <th style="text-align:center">Fecha de Nacimiento</th>
                      <th style="text-align:center">Celular</th>
                      <th style="text-align:center">Celular alterno</th>

                      <th style="text-align:center">Dirección</th>
                      <th style="text-align:center">Estado</th>
                      <th style="text-align:center">Fecha de registro</th>

                      <th style="text-align:center">Usuario</th>
                      <th style="text-align:center">Estado usuario</th>
                      <th style="text-align:center">Rol</th>

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


    <!-- Modal Registro -->
<div class="modal fade" id="modal_registro" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE DOCENTES</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
            </div>
            <div class="col-4 form-group">
                <label for="">DNI<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_dni" placeholder="Ingrese el DNI del docente" onkeypress="return soloNumeros(event)" maxlenght="8">
            </div>
            <div class="col-4 form-group">
                <label for="">Nombres<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nomb" placeholder="Ingrese los nombres" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Apellidos<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_apelli" placeholder="Ingrese los apellidos" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Especialidad<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="txt_especialidad" style="width:100%">
              </select>   
            </div>
            <div class="col-4 form-group">
                <label for="">Sexo<b style="color:red">(*)</b>:</label>
                <select name="" id="txt_sexo" class="form-control">
                <option value="">Seleccione</option> <!-- Opción 'Seleccione' añadida -->

                    <option value="FEMENINO">FEMENINO</option>
                    <option value="MASCULINO">MASCULINO</option>
                </select>
            </div>
            <div class="col-4 form-group">
                <label for="">Fecha de nacimiento<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_na">
            </div>
            <div class="col-4 form-group">
                <label for="">Teléfono o Celular:</label>
                <input type="text" class="form-control" id="txt_tele" placeholder="Ingrese el teléfono o celular" onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Teléfono o Celular alterno:</label>
                <input type="text" class="form-control" id="txt_tele_alte" placeholder="Ingrese el teléfono o celular" onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Dirección<b style="color:red">(*)</b>:</label>
                <textarea class="form-control" id="txt_direc" rows="2" style="resize:none" placeholder="Ingrese la dirección"></textarea>
            </div>

            <div class="col-6">
                <label for="txt_foto">Subir Foto <b style="color:red">*</b>:</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="txt_foto" accept="image/*" onchange="previewImage(event)">
                    <label class="custom-file-label" for="txt_foto">Seleccione Foto...</label>
                </div>
            </div>

            <div class="col-6" align="center" style="border: 2px solid black;padding: 10px;display: inline-block;">
                <img id="preview" src="#" alt="Vista previa" style="max-width: 100%; max-height: 150px;">
            </div><br>
            <div class="col-12"><br>

                <li class="header text-center" style="color:#FFFFFF;background-color:Black;"><b>DATOS DEL USUARIO</b></li>  
            </div><br>
            <div class="col-4 form-group"><br>
                <label for="">Usuario<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_usu"  placeholder="Ingrese el usuario">
            </div>
            <div class="col-4 form-group"><br>
                <label for="">Contraseña<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_contra" placeholder="Ingrese la contraseña">
            </div>
            <div class="col-4 form-group"><br>
                <label for="">Correo electronico<b style="color:red">(*)</b>:</label>
                <input type="email" class="form-control" id="txt_correo" placeholder="Ingrese el correo electronico">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_Docente()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>


    <!-- Modal Mostrar -->
<div class="modal fade" id="modal_mas" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>DATOS DEL DOCENTE</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-4 form-group">
                <label for="">DNI<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_dni_mas" readonly placeholder="Ingrese el DNI del alumno" onkeypress="return soloNumeros(event)" maxlenght="8">
            </div>
            <div class="col-4 form-group">
                <label for="">Nombres<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nomb_mas" readonly placeholder="Ingrese los nombres" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Apellidos<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_apelli_mas" readonly placeholder="Ingrese el Apellido paterno" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Especialidad<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="txt_especialidad_mas" style="width:100%" readonly>
              </select>   
            </div>
            <div class="col-4 form-group">
                <label for="">Sexo<b style="color:red">(*)</b>:</label>
                <select name="" id="txt_sexo_mas" class="form-control">
                    <option value="">Seleccione</option> <!-- Opción 'Seleccione' añadida -->
                    <option value="FEMENINO">FEMENINO</option>
                    <option value="MASCULINO">MASCULINO</option>
                </select>
            </div>

            <div class="col-4 form-group">
                <label for="">Fecha de nacimiento<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_na_mas" readonly>
            </div>
            <div class="col-4 form-group">
                <label for="">Teléfono o Celular:</label>
                <input type="text" class="form-control" id="txt_tele_mas" placeholder="Ingrese el teléfono o celular" readonly onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Teléfono o Celular alterno:</label>
                <input type="text" class="form-control" id="txt_tele_alte_mas" placeholder="Ingrese el teléfono o celular" readonly onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Dirección<b style="color:red">(*)</b>:</label>
                <textarea class="form-control" id="txt_direc_mas" rows="2" style="resize:none" placeholder="Ingrese la dirección" readonly></textarea>
            </div>

            <div class="col-12" align="center" style="border: 2px solid black;padding: 10px;display: inline-block;">
                <img id="preview2" src="#" alt="Vista previa" style="max-width: 100%; max-height: 150px;">
            </div><br>
            <div class="col-12"><br>

                <li class="header text-center" style="color:#FFFFFF;background-color:Black;"><b>DATOS DEL USUARIO</b></li>  
            </div><br>
            <div class="col-6 form-group"><br>
                <label for="">Usuario<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_usu_mas"  placeholder="Ingrese el usuario" readonly>
            </div>
            <div class="col-6 form-group"><br>
                <label for="">Correo electronico<b style="color:red">(*)</b>:</label>
                <input type="email" class="form-control" id="txt_correo_mas" placeholder="Ingrese el correo electronico" readonly>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>

    <!-- Modal Editar -->
<div class="modal fade" id="modal_editar" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR DATOS DEL DOCENTE</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
            </div>
            <div class="col-4 form-group">
                <label for="">DNI<b style="color:red">(*)</b>:</label>
                <input type="text" id="id_docente" hidden>
                <input type="text" class="form-control"  id="txt_dni_editar" placeholder="Ingrese el DNI del docente" onkeypress="return soloNumeros(event)" maxlenght="8">
            </div>
            <div class="col-4 form-group">
                <label for="">Nombres<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nomb_editar" placeholder="Ingrese los nombres" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Apellidos<b style="color:red">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_apelli_editar" placeholder="Ingrese los apellidos" onkeypress="return sololetras(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Especialidad<b style="color:red">(*)</b>:</label>
                <select class="form-control" id="txt_especialidad_editar" style="width:100%">
              </select>   
            </div>
            <div class="col-4 form-group">
                <label for="">Sexo<b style="color:red">(*)</b>:</label>
                <select name="" id="txt_sexo_editar" class="form-control">
                <option value="">Seleccione</option> <!-- Opción 'Seleccione' añadida -->

                    <option value="FEMENINO">FEMENINO</option>
                    <option value="MASCULINO">MASCULINO</option>
                </select>
            </div>
            <div class="col-4 form-group">
                <label for="">Fecha de nacimiento<b style="color:red">(*)</b>:</label>
                <input type="date" class="form-control" id="txt_fecha_na_editar">
            </div>
            <div class="col-4 form-group">
                <label for="">Teléfono o Celular:</label>
                <input type="text" class="form-control" id="txt_tele_editar" placeholder="Ingrese el teléfono o celular" onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Teléfono o Celular alterno:</label>
                <input type="text" class="form-control" id="txt_tele_alte_editar" placeholder="Ingrese el teléfono o celular" onkeypress="return soloNumeros(event)">
            </div>
            <div class="col-4 form-group">
                <label for="">Dirección<b style="color:red">(*)</b>:</label>
                <textarea class="form-control" id="txt_direc_editar" rows="2" style="resize:none" placeholder="Ingrese la dirección"></textarea>
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
        <button type="button" class="btn btn-success" onclick="Modificar_docente()"><i class="fas fa-edit"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
    listar_alumnos();
  $('.js-example-basic-single').select2();
  Cargar_Select_Especialidad();
});
$('#modal_registro').on('shown.bs.modal', function () {
  $('#txt_rol').trigger('focus')
})
//DNI registro
var input=  document.getElementById('txt_dni');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
  this.value = this.value.slice(0,8); 
})

//Celulares registro
var input=  document.getElementById('txt_tele');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})
var input=  document.getElementById('txt_tele_alte');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})


//DNI editar
var input=  document.getElementById('txt_dni_editar');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
  this.value = this.value.slice(0,8); 
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
    function previewImage2(event) {
        var input = event.target;
        var preview2 = document.getElementById('preview2');

        var reader = new FileReader();
        reader.onload = function(){
            preview2.src = reader.result;
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