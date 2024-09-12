<script src="../js/console_empleado.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><b>MANTENIMIENTO DE EMPLEADO</b></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
              <li class="breadcrumb-item active">EMPLEADO</li>
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
                <h3 class="card-title"><i class="fas fa-users"></i>&nbsp;&nbsp;<b>Listado de Empleados</b></h3>
                <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
              </div>
              <div class="table-responsive" style="text-align:center">
              <div class="card-body">
              <table id="tabla_empleado" class="table table-striped table-bordered" style="width:100%">
                  <thead style="background-color:#0A5D86;color:#FFFFFF;">
                      <tr>
                          <th style="text-align:center">Nro.</th>
                          <th style="text-align:center">Foto</th>
                          <th style="text-align:center">DNI</th>
                          <th style="text-align:center">Empleado</th>
                          <th style="text-align:center">Celular</th>
                          <th style="text-align:center">Email</th>
                          <th style="text-align:center">Dirección</th>
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
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE EMPLEADOS</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div><br>
          <div class="col-4 form-group">
            <label for="">DNI(*):</label>
            <input type="text" class="form-control" id="txt_nro" onkeypress="return soloNumeros(event)" maxlenght="8">
          </div>
          <div class="col-8 form-group">
            <label for="">Nombres(*):</label>
            <input type="text" class="form-control" id="txt_nom" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Apellido Paterno(*):</label>
            <input type="text" class="form-control" id="txt_apepa" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Apellido Materno(*):</label>
            <input type="text" class="form-control" id="txt_apema" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Fecha Nacimiento(*):</label>
            <input type="date" class="form-control" id="txt_nac">
          </div>
          <div class="col-6 form-group">
            <label for="">Celular(*):</label>
            <input type="text" class="form-control" id="txt_movil" onkeypress="return soloNumeros(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Dirección(*):</label>
            <input type="text" class="form-control" id="txt_dire">
          </div>
          <div class="col-6 form-group">
            <label for="">Email(*):</label>
            <input type="text" class="form-control" id="txt_email">
          </div>
         
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_Empleado()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR DATOS DEL EMPLEADO</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div><br>
          <div class="col-4 form-group">
            <input type="text" id="txt_idempleado" hidden>
            <label for="">DNI(*):</label>
            <input type="text" class="form-control" id="txt_nro_editar" onkeypress="return soloNumeros(event)" maxlenght="8">
          </div>
          <div class="col-8 form-group">
            <label for="">Nombres(*):</label>
            <input type="text" class="form-control" id="txt_nom_editar" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Apellido Paterno(*):</label>
            <input type="text" class="form-control" id="txt_apepa_editar" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Apellido Materno(*):</label>
            <input type="text" class="form-control" id="txt_apema_editar" onkeypress="return sololetras(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Fecha Nacimiento(*):</label>
            <input type="date" class="form-control" id="txt_nac_editar">
          </div>
          <div class="col-6 form-group">
            <label for="">Celular(*):</label>
            <input type="text" class="form-control" id="txt_movil_editar" onkeypress="return soloNumeros(event)">
          </div>
          <div class="col-6 form-group">
            <label for="">Dirección(*):</label>
            <input type="text" class="form-control" id="txt_dire_editar">
          </div>
          <div class="col-6 form-group">
            <label for="">Email(*):</label>
            <input type="text" class="form-control" id="txt_email_editar">
          </div>
          <div class="col-12 form-group">
            <label for="">Estado(*):</label>
              <select name="" id="txt_estatus" class="form-control">
                <option value="ACTIVO">ACTIVO</option>
                <option value="INACTIVO">INACTIVO</option>
              </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Empleado()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_editar_foto" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR FOTO DEL USUARIO: </b><label for="" id="lb_usuario"></label></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12">
            <input type="text" id="fotoactual" hidden>
            <input type="text" id="txt_idmpleado_foto" hidden>
            <label for="">Subir Foto:</label>
            <input class="form-control" type="file" id="txt_foto">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Foto_Empleado()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>
    <script>
    $(document).ready(function () {
      listar_empleado();
    });
    $('#modal_registro').on('shown.bs.modal', function () {
      $('#txt_nro').trigger('focus')
    })
    var input=  document.getElementById('txt_nro');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
     this.value = this.value.slice(0,8); 
})
var input=  document.getElementById('txt_movil');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})
var input=  document.getElementById('txt_nro_editar');
input.addEventListener('input',function(){
  if (this.value.length > 8) 
     this.value = this.value.slice(0,8); 
})
var input=  document.getElementById('txt_movil_editar');
input.addEventListener('input',function(){
  if (this.value.length > 9) 
     this.value = this.value.slice(0,9); 
})


document.getElementById("txt_foto").addEventListener("change", () =>{
          var fileName = document.getElementById("txt_foto").value;
          var idxDot  = fileName.lastIndexOf(".") + 1;
          var extFile = fileName.substr(idxDot, fileName.length).toLowerCase();
          if(extFile=="jpg" || extFile=="jpeg" || extFile=="png"){
            //TO DO
          }else{
            Swal.fire("Mensaje de Advertencia","Solo se aceptan imagenes - usted subio un archivo con extensión ."+extFile,"warning");
            document.getElementById("txt_foto").value="";

          }
    })
    </script>