<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_asignatura_docente.js?rev=<?php echo time();?>"></script>


<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Cursos de cada docente</h1>
        <p class="pagina-ayuda">Asigna a cada docente los cursos y aulas que dicta.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Cursos de cada docente</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Asignatura - Docente</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
          </div>
          <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3 form-group">
                                    <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-3 form-group">
                                    <label for="">Grado o Aula<b class="text-danger">(*)</b>:</label>
                                    <select class="form-control" id="select_aula_buscar" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_asignatura_docente_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar cursos</button>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_asignatura_docente()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_asigdocente" class="table table-striped table-bordered" style="width:100%">
              <thead class="tabla-cabecera">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">DNI Docente</th>
                      <th style="text-align:center">Docente</th>
                      <th style="text-align:center">Grado</th>
                      <th style="text-align:center">Nivel Academico</th>
                      <th style="text-align:center">Total cursos</th>
                      <th style="text-align:center">Fecha Registro</th>
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
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>ASIGNAR CURSO A DOCENTE</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12 form-group text-danger">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-6 form-group">
            <label for="">Año Académico<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_año" style="width:100%">
            </select>              
          </div>
          <div class="col-6 form-group">
            <label for="">Docente<b class="text-danger">(*)</b>:</label>
            <select class="form-control" id="select_docente2" style="width:100%">
            </select>              
          </div>
          <div class="col-12 form-group">
            <label for="">Grado<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_grado" style="width:100%">
              </select>          
          </div>
          <div class="col-12 form-group">
            <label for="">Curso<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_curso" style="width:100%">
              </select>          
          </div>
          
          <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_curso()">
                  <i class="fas fa-plus"></i> <b>Agregar Asignatura</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_asignacion" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th>Id.</th>
                <th>Asignatura</th>
                <th>Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody_tabla_asignacion">
            </tbody>
            </table>   
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Registrar_ASIGDOCENTE()"><i class="fas fa-save"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg" role="document">
<div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>EDITAR ASIGNATURAS DEL DOCENTE</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <div class="row">
          <div class="col-12 form-group text-danger">
            <h6><b>Campos Obligatorios (*)</b></h6>
          </div>
          <div class="col-6 form-group">
            <label for="">Año Académico<b class="text-danger">(*)</b>:</label>
            <input type="text" class="form-control" id="select_año_editar" disabled>
       
          </div>
          <div class="col-6 form-group">
            <label for="">Docente<b class="text-danger">(*)</b>:</label>
            <input type="text" id="txt_id_asig" hidden>

            <input type="text" class="form-control" id="select_docente2_editar" disabled>
              
          </div>
          <div class="col-12 form-group">
            <label for="">Grado<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_grado_editar" style="width:100%">
              </select>          
          </div>
          <div class="col-12 form-group">
            <label for="">Curso y Grado<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_curso_editar" style="width:100%">
              </select>          
          </div>
          
          <div class="col-12 form-group">
              <button type="button" class="btn btn-success btn-block" onclick="Agregar_curso_editar()">
                  <i class="fas fa-plus"></i> <b>Agregar Asignatura</b>
              </button>
          </div>
          <div class="col-12 table-responsive"style="text-align:center">
            <table id="tabla_asignacion_editar" style="width:100%" class="table">
            <thead class="thead-dark">
                <tr>
                <th>Id.</th>
                <th>Asignatura</th>
                <th>Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody id="tbody_tabla_asignacion">
            </tbody>
            </table>   
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Detalle_Asignar_docente()"><i class="fas fa-check"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="modal_ver_cursos" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <div class="modal-header ventana-cabecera">
    <h5 class="modal-title text-white mb-0" id="lb_titulo"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-12" style="text-align:center"> 
          <div class="table-responsive" style="text-align:center">
            <div class="card-body">
            <table id="tabla_cursos" class="display compact" style="width:100%" style="text-align:center">
                <thead class="tabla-cabecera">
                  <tr style="text-align:center">
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Asignatura</th>
                      <th style="text-align:center">Grado o Sección</th>
                      <th style="text-align:center">Nivel Académico</th>
                      <th style="text-align:center">Observaciones</th>
                   </tr>
                  </thead>
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

<script>
    listar_asignatura_docente();
    $(document).ready(function(){
    $('.js-example-basic-single').select2();
    Cargar_Select_docente();
    Cargar_Select_aulas();
    Cargar_Anio();
    Cargar_Select_Nivelaca();


});
  $("#select_grado").change(function() {
      var id = $("#select_grado").val();
      Cargar_Select_asignatura(id);
    });

    $("#select_grado_editar").change(function() {
      var id = $("#select_grado_editar").val();
      Cargar_Select_asignatura(id);
    });
    $("#select_nivel_buscar").change(function() {
      var id = $("#select_nivel_buscar").val();
      Cargar_Select_Aula(id);
    });

</script>
