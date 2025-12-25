<script src="../js/console_alumnos.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0"><b>MANTENIMIENTO DE ALUMNOS</b></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">ALUMNOS</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Alumnos</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_alumnos" class="table table-striped table-bordered" style="width:100%">
              <thead style="background-color:#0A5D86;color:#FFFFFF;">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Foto</th>
                      <th style="text-align:center">DNI</th>
                      <th style="text-align:center">Estudiante</th>
                      <th style="text-align:center">Sexo</th>

                      <th style="text-align:center">Fecha de Nacimiento</th>
                      <th style="text-align:center">Edad</th>
                      <th style="text-align:center">Celular</th>


                      <th style="text-align:center">Dirección</th>
                      <th style="text-align:center">Matriculado</th>
                      <th style="text-align:center">Tipo Alumno</th>
                      <th style="text-align:center">Fecha de registro</th>

                      <th style="text-align:center">Datos papá</th>
                      <th style="text-align:center">DNI papá</th>
                      <th style="text-align:center">Celular papá</th>
                      <th style="text-align:center">Datos mamá</th>
                      <th style="text-align:center">DNI mamá</th>
                      <th style="text-align:center">Celular mamá</th>

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

<div class="modal fade" id="modal_registro" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <h5 class="modal-title" id="lb_titulo_datos">Datos Generales</h5>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12">
          <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
              <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true">Datos del Estudiante</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-one-profile-tab" data-toggle="pill" href="#custom-tabs-one-profile" role="tab" aria-controls="custom-tabs-one-profile" aria-selected="false">Datos de los Padres</a>
                </li>
              </ul>
              </div>
            <div class="card-body">
              <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                  <div class="row">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-12 form-group" style="color:red">
                                <h6><b>Campos Obligatorios (*)</b></h6>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">DNI Alumno<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_dni" placeholder="Ingrese el DNI del alumno" onkeypress="return soloNumeros(event)" maxlenght="8">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Nombres<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_nomb" placeholder="Ingrese los nombres" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Apellido Paterno<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_ape_pa" placeholder="Ingrese el Apellido paterno" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Apellido Materno<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_apema" placeholder="Ingrese el Apellido materno" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-4 form-group">
                              <label for="">Sexo<b style="color:red">(*)</b>:</label>
                              <select name="" id="txt_sexo" class="form-control">
                                  <option value="">Seleccione</option>
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
                            <div class="col-8 form-group">
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
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="custom-tabs-one-profile" role="tabpanel" aria-labelledby="custom-tabs-one-profile-tab">
                <div class="row">
                             <div class="col-12 form-group" style="color:red">
                                <h6><b>OJO: Este registro no puede ir vacio, bien llenar datos de la Mamá o del Papá</b></h6>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">DNI Papá:</label>
                                <input type="text" class="form-control" id="txt_dni_pa" placeholder="Ingrese el DNI de Papá" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Nombre y Apellidos Papá:</label>
                                <input type="text" class="form-control" id="txt_nom_pa"  style="background-color:white;" placeholder="Ingrese los nombres y apellidos de Papa" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Teléfono o Celular Papá:</label>
                                <input type="text" class="form-control" id="txt_cel_pa"  style="background-color:white;" placeholder="Ingrese el celular o movil de Papá" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">DNI Mamá:</label>
                                <input type="text" class="form-control" id="txt_dni_ma" placeholder="Ingrese el DNI de Mamá" onkeypress="return soloNumeros(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Nombre y Apellidos Mamá:</label>
                                <input type="text" class="form-control" id="txt_nom_ma"  style="background-color:white;" placeholder="Ingrese los Nombres y apellidos de Mamá" onkeypress="return sololetras(event)">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Teléfono o Celular Mamá:</label>
                                <input type="text" class="form-control" id="txt_cel_ma"  style="background-color:white;" placeholder="Ingrese el celular o movil de Mamá" onkeypress="return soloNumeros(event)"> 
                            </div>
                            
                            
                        </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-arrow-right-from-bracket"></i>Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_alumno()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>


    <!-- Modal Mostrar -->
<div class="modal fade" id="modal_mas" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <h5 class="modal-title" id="lb_titulo_datos">Datos Generales</h5>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12">
          <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
              <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="custom-tabs-one-home-tab2" data-toggle="pill" href="#custom-tabs-one-home2" role="tab" aria-controls="custom-tabs-one-home2" aria-selected="true">Datos del Estudiante</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-one-profile-tab2" data-toggle="pill" href="#custom-tabs-one-profile2" role="tab" aria-controls="custom-tabs-one-profile2" aria-selected="false">Datos de los Padres</a>
                </li>
              </ul>
              </div>
            <div class="card-body">
              <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="custom-tabs-one-home2" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab2">
                  <div class="row">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-12 form-group" style="color:red">
                                <h6><b>Campos Obligatorios (*)</b></h6>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">DNI Alumno:</label>
                                <input type="text" class="form-control" id="txt_dni_mas" placeholder="Ingrese el DNI del alumno" readonly>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Nombres:</label>
                                <input type="text" class="form-control" id="txt_nomb_mas" placeholder="Ingrese los nombres" readonly>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Apellido Paterno:</label>
                                <input type="text" class="form-control" id="txt_ape_pa_mas" placeholder="Ingrese el Apellido paterno" readonly>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Apellido Materno:</label>
                                <input type="text" class="form-control" id="txt_apema_mas" placeholder="Ingrese el Apellido materno" readonly>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Sexo:</label>
                                <select name="" id="txt_sexo_mas" class="form-control" readonly>
                                    <option value="FEMENINO">FEMENINO</option>
                                    <option value="MASCULINO">MASCULINO</option>
                                </select>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Fecha de nacimiento:</label>
                                <input type="date" class="form-control" id="txt_fecha_na_mas" readonly>
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Teléfono o Celular:</label>
                                <input type="text" class="form-control" id="txt_tele_mas" placeholder="Ingrese el teléfono o celular" readonly>
                            </div>
                            <div class="col-8 form-group">
                                <label for="">Dirección:</label>
                                <textarea class="form-control" id="txt_direc_mas" rows="2" style="resize:none" placeholder="Ingrese la dirección" readonly></textarea>
                            </div>
                            <label for="">Foto:</label>
                            <div class="col-12" align="center" style="border: 2px solid black;padding: 10px;display: inline-block;">
                                <img id="preview2" src="#" alt="Preview" style="max-width: 100%; max-height: 150px;">
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="custom-tabs-one-profile2" role="tabpanel" aria-labelledby="custom-tabs-one-profile-tab2">
                <div class="row">
                             <div class="col-12 form-group" style="color:red">
                                <h6><b>OJO: Este registro no puede ir vacio, bien llenar datos de la Mamá o del Papá</b></h6>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">DNI Papá:</label>
                                <input type="text" class="form-control" id="txt_dni_pa_mas" placeholder="Ingrese el DNI de Papá" readonly>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Nombre y Apellidos Papá:</label>
                                <input type="text" class="form-control" id="txt_nom_pa_mas"  style="background-color:white;" placeholder="Ingrese los nombres y apellidos de Papa" readonly>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Teléfono o Celular Papá:</label>
                                <input type="text" class="form-control" id="txt_cel_pa_mas"  style="background-color:white;" placeholder="Ingrese el celular o movil de Papá" readonly>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">DNI Mamá:</label>
                                <input type="text" class="form-control" id="txt_dni_ma_mas" placeholder="Ingrese el DNI de Mamá" readonly>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Nombre y Apellidos Mamá:</label>
                                <input type="text" class="form-control" id="txt_nom_ma_mas"  style="background-color:white;" placeholder="Ingrese los Nombres y apellidos de Mamá" readonly>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Teléfono o Celular Mamá:</label>
                                <input type="text" class="form-control" id="txt_cel_ma_mas"  style="background-color:white;" placeholder="Ingrese el celular o movil de Mamá" readonly>
                            </div>
                        </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-arrow-right-from-bracket"></i>Cerrar</button>
      </div>
    </div>
  </div>
</div>

    <!-- Modal Editar -->
<div class="modal fade" id="modal_editar" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <h5 class="modal-title" id="lb_titulo_datos">Datos Generales</h5>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12">
          <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
              <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="custom-tabs-one-home-tab3" data-toggle="pill" href="#custom-tabs-one-home3" role="tab" aria-controls="custom-tabs-one-home3" aria-selected="true">Datos del Estudiante</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-one-profile-tab3" data-toggle="pill" href="#custom-tabs-one-profile3" role="tab" aria-controls="custom-tabs-one-profile3" aria-selected="false">Datos de los Padres</a>
                </li>
              </ul>
              </div>
            <div class="card-body">
              <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="custom-tabs-one-home3" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab3">
                  <div class="row">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-12 form-group" style="color:red">
                                <h6><b>Campos Obligatorios (*)</b></h6>
                            </div>
                            <div class="col-4 form-group">
                              <input type="text" id="txt_id_alu" hidden>
                                <label for="">DNI Alumno<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control"  id="txt_dni_editar" placeholder="Ingrese el DNI del alumno">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Nombres<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_nomb_editar" placeholder="Ingrese los nombres">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Apellido Paterno<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_ape_pa_editar" placeholder="Ingrese el Apellido paterno">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Apellido Materno<b style="color:red">(*)</b>:</label>
                                <input type="text" class="form-control" id="txt_apema_editar" placeholder="Ingrese el Apellido materno">
                            </div>
                            <div class="col-4 form-group">
                                <label for="">Sexo<b style="color:red">(*)</b>:</label>
                                <select name="" id="txt_sexo_editar" class="form-control">
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
                                <input type="text" class="form-control" id="txt_tele_editar" placeholder="Ingrese el teléfono o celular">
                            </div>
                            <div class="col-8 form-group">
                                <label for="">Dirección<b style="color:red">(*)</b>:</label>
                                <textarea class="form-control" id="txt_direc_editar" rows="2" style="resize:none" placeholder="Ingrese la dirección"></textarea>
                            </div>
                           
                            <div class="col-6">
                                <label for="txt_foto">Editar Foto:</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="txt_foto_editar" accept="image/*" onchange="previewImage3(event)">
                                    <label class="custom-file-label" for="txt_foto_editar">Seleccione Foto...</label>
                                </div>
                            </div>
                            <input type="text" id="foto_actual" hidden>
                            
                            <div class="col-6" align="center" style="border: 2px solid black;padding: 10px;display: inline-block;">
                                <img id="preview3" src="#" alt="Vista previa" style="max-width: 100%; max-height: 150px;">
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="custom-tabs-one-profile3" role="tabpanel" aria-labelledby="custom-tabs-one-profile-tab3">
                <div class="row">
                             <div class="col-12 form-group" style="color:red">
                                <h6><b>OJO: Este registro no puede ir vacio, bien llenar datos de la Mamá o del Papá</b></h6>
                            </div>
                            <div class="col-6 form-group">
                                <label for="">DNI Papá:</label>
                                <input type="text" class="form-control" id="txt_id_papa" hidden>
                                <input type="text" class="form-control" id="txt_dni_pa_editar" placeholder="Ingrese el DNI de Papá">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Nombre y Apellidos Papá:</label>
                                <input type="text" class="form-control" id="txt_nom_pa_editar"  style="background-color:white;" placeholder="Ingrese los nombres y apellidos de Papa">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Teléfono o Celular Papá:</label>
                                <input type="text" class="form-control" id="txt_cel_pa_editar"  style="background-color:white;" placeholder="Ingrese el celular o movil de Papá">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">DNI Mamá:</label>
                                <input type="text" class="form-control" id="txt_dni_ma_editar" placeholder="Ingrese el DNI de Mamá">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Nombre y Apellidos Mamá:</label>
                                <input type="text" class="form-control" id="txt_nom_ma_editar"  style="background-color:white;" placeholder="Ingrese los Nombres y apellidos de Mamá">
                            </div>
                            <div class="col-6 form-group">
                                <label for="">Teléfono o Celular Mamá:</label>
                                <input type="text" class="form-control" id="txt_cel_ma_editar"  style="background-color:white;" placeholder="Ingrese el celular o movil de Mamá">
                            </div>
                        </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-arrow-right-from-bracket"></i>Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_alumno()"><i class="fas fa-edit"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
    listar_alumnos();
  $('.js-example-basic-single').select2();
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
var input=  document.getElementById('txt_dni_pa');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
  this.value = this.value.slice(0,8); 
})
var input=  document.getElementById('txt_dni_ma');
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
var input=  document.getElementById('txt_cel_pa');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})
var input=  document.getElementById('txt_cel_ma');
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
var input=  document.getElementById('txt_dni_pa_editar');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
  this.value = this.value.slice(0,8); 
})
var input=  document.getElementById('txt_dni_ma_editar');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
  this.value = this.value.slice(0,8); 
})
//Celulares editar
var input=  document.getElementById('txt_tele_editar');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})
var input=  document.getElementById('txt_cel_pa_editar');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})
var input=  document.getElementById('txt_cel_ma_editar');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
  this.value = this.value.slice(0,9); 
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