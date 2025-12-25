<script src="../js/console_usuario.js?rev=<?php echo time();?>"></script>

<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><b>MANTENIMIENTO DE USUARIOS</b></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
              <li class="breadcrumb-item active">USUARIO</li>
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
              <h3 class="card-title"><i class="fas fa-user"></i>&nbsp;&nbsp;<b>Listado de Usuarios</b></h3>
              </div>
              <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4 form-group">
                                    <label for="">Tipo de usuario o rol<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_rol" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-4" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_usuario_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar usuarios</button>
                                </div>
                                <div class="col-12 col-md-4" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_usuario()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="table-responsive" style="text-align:center">
                  <div class="card-body">
                    <table id="tabla_usuario" class="table table-striped table-bordered" style="width:100%">
                        <thead style="background-color:#0A5D86;color:#FFFFFF; ">
                            <tr>
                                <th style="text-align:center">Nro.</th>
                                <th style="text-align:center">Usuario</th>
                                <th style="text-align:center">Rol</th>
                                <th style="text-align:center">Nombre y Apellidos</th>
                                <th style="text-align:center">Email</th>
                                <th style="text-align:center">Fecha de registro</th>
                                <th style="text-align:center">Estado</th>
                                <th style="text-align:center">Acción</th>
                            </tr>
                        </thead>
                    </table>
                  </div>
                </div>           
           </div>
          </div>
          <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->

<div class="modal fade" id="modal_editar" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel"  style="color:white; text-align:center"><b>EDITAR DATOS DE USUARIO</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
              <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-12">
            <input type="text" id="txt_idusuario" hidden>
            <label for="">Usuario(*):</label>
            <input type="text" class="form-control" id="txt_usu_editar" >
          </div>
          <div class="col-6">
            <label for="">Rol(*):</label>
              <select class="js-example-basic-single" id="select_rol_editar" style="width:100%">
              </select>          
          </div>
          <div class="col-6">
            <label for="">Correo Electronico:</label>
            <input type="text" class="form-control" id="txt_correo_editar">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Usuario()"><i class="fas fa-times ml-1"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modal_contra" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>CAMBIAR CONTRASEÑA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group" style="color:red">
              <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-12">
            <input type="text" id="txt_idusuario_contra" hidden>
            <label for="">Contraseña Nueva(*):</label>
            <input type="password" class="form-control" id="txt_contra_nueva">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Contra()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>


    <!-- /.content -->
    <script>
    $(document).ready(function () {
      listar_usuario();

      $('.js-example-basic-single').select2();
      Cargar_Select_Rol();

    });
    $('#modal_registro').on('shown.bs.modal', function () {
      $('#txt_usu').trigger('focus')
    })
    $('#modal_contra').on('shown.bs.modal', function () {
      $('#txt_contra_nueva').trigger('focus')
    })
    
    </script>