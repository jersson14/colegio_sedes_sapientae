<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; ?><script src="../js/console_matriculas.js?rev=<?php echo time();?>"></script><script src="../js/matricula_pasos.js?rev=<?php echo time();?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 pagina-titulo">Matrícula</h1>
        <p class="pagina-ayuda">Matricula a los estudiantes en un aula del año escolar.</p>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
          <li class="breadcrumb-item active">Matrícula</li>
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
            <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Matriculados</b></h3>
            <button class="btn btn-success float-right" onclick="AbrirRegistro()"><i class="fas fa-plus"></i> Nuevo Registro</button>
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
                                    <button onclick="listar_matriculados_filtro()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar matriculados</button>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_matriculados()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
                                </div>
                            </div>
                        </div>
                    </div>
          <div class="table-responsive" style="text-align:center">
          <div class="card-body">
          <table id="tabla_matricula" class="table table-striped table-bordered" style="width:100%">
              <thead class="tabla-cabecera">
                  <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">DNI</th>
                      <th style="text-align:center">Estudiante</th>
                      <th style="text-align:center">Aula o Grado</th>
                      <th style="text-align:center">Nivel Académico</th>
                      <th style="text-align:center">Año escolar</th>
                      <th style="text-align:center">Matrícula</th>
                      <th style="text-align:center">Procedencia Colegio</th>
                      <th style="text-align:center">Provincia</th>
                      <th style="text-align:center">Departamento</th>
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
<div class="modal fade" id="modal_registro" tabindex="-1" role="dialog" aria-labelledby="titulo_matricula" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white" id="titulo_matricula"><b>Nueva matrícula</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Matrícula paso a paso (Fase 13): js/matricula_pasos.js -->
        <ol class="mp-pasos" aria-label="Pasos de la matrícula">
          <li data-paso="1"><span>1</span>Estudiante</li>
          <li data-paso="2"><span>2</span>Padres</li>
          <li data-paso="3"><span>3</span>Aula</li>
          <li data-paso="4"><span>4</span>Pagos</li>
          <li data-paso="5"><span>5</span>Resumen</li>
        </ol>

        <section class="mp-paso" data-paso="1">
          <div class="row">
            <div class="col-md-8 form-group">
              <label for="select_estudiante">Estudiante<b class="text-danger">(*)</b>:</label>
              <select class="js-example-basic-single" id="select_estudiante" style="width:100%"></select>
              <small class="text-muted">¿No está en la lista? <a href="#" onclick="$('#modal_registro').data('fm-salir', true).modal('hide'); cargar_contenido('contenido_principal','alumnos/view_alumnos.php'); return false;">Regístrelo primero en Estudiantes</a>.</small>
            </div>
            <div class="col-md-4 form-group">
              <label for="select_año">Año escolar<b class="text-danger">(*)</b>:</label>
              <select class="form-control" id="select_año" style="width:100%"></select>
            </div>
          </div>
          <div id="mp_estudiante" aria-live="polite"></div>
        </section>

        <section class="mp-paso" data-paso="2" hidden>
          <p class="text-muted mb-2">Revise que los datos de los padres o apoderado estén completos: son el contacto del colegio con la familia.</p>
          <div id="mp_padres"></div>
        </section>

        <section class="mp-paso" data-paso="3" hidden>
          <input type="hidden" id="select_aula">
          <p class="text-muted mb-2" id="mp_aula_ayuda">Elija el aula. Las que ya no tienen vacantes no se pueden elegir.</p>
          <div id="mp_aulas"></div>
        </section>

        <section class="mp-paso" data-paso="4" hidden>
          <div class="row">
            <div class="col-12 form-group mb-2">
              <div class="icheck-success d-inline">
                <input type="checkbox" id="checkboxSuccess1">
                <label for="checkboxSuccess1">Es estudiante nuevo en el colegio (cobra admisión y alumno nuevo)</label>
              </div>
            </div>
            <div class="col-md-4 form-group mp-nuevo">
              <label for="txt_admision">Pago por admisión:</label>
              <input type="text" inputmode="decimal" class="form-control" id="txt_admision" value="0">
            </div>
            <div class="col-md-4 form-group mp-nuevo">
              <label for="txt_alum_nuevo">Pago alumno nuevo:</label>
              <input type="text" inputmode="decimal" class="form-control" id="txt_alum_nuevo" value="0">
            </div>
            <div class="col-md-4 form-group">
              <label for="txt_matricula">Pago de matrícula<b class="text-danger">(*)</b>:</label>
              <input type="text" inputmode="decimal" class="form-control" id="txt_matricula" value="0">
            </div>
            <div class="col-12"><small class="text-muted" id="mp_montos_ayuda"></small></div>
            <div class="col-md-4 form-group mp-nuevo">
              <label for="txt_procedencia">Colegio de procedencia:</label>
              <input type="text" class="form-control" id="txt_procedencia" maxlength="150">
            </div>
            <div class="col-md-4 form-group mp-nuevo">
              <label for="txt_provincia">Provincia:</label>
              <input type="text" class="form-control" id="txt_provincia" maxlength="100">
            </div>
            <div class="col-md-4 form-group mp-nuevo">
              <label for="txt_departamento">Departamento:</label>
              <input type="text" class="form-control" id="txt_departamento" maxlength="100">
            </div>
          </div>

          <fieldset class="mp-bloque mp-cuenta">
            <legend>Acceso del estudiante al sistema</legend>
            <div class="row">
              <div class="col-md-4 form-group">
                <label for="txt_usu">Usuario<b class="text-danger">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_usu" maxlength="50" autocomplete="off">
              </div>
              <div class="col-md-4 form-group">
                <label for="txt_contra">Contraseña inicial<b class="text-danger">(*)</b>:</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="txt_contra" maxlength="50" autocomplete="off">
                  <div class="input-group-append"><button type="button" class="btn btn-outline-secondary" id="mp_generar" title="Crear una contraseña fácil de dictar">Generar</button></div>
                </div>
                <small class="text-muted">Al menos 8 caracteres. La cambiará en su primer ingreso.</small>
              </div>
              <div class="col-md-4 form-group">
                <label for="txt_correo">Correo electrónico (opcional):</label>
                <input type="email" class="form-control" id="txt_correo" maxlength="100">
              </div>
            </div>
          </fieldset>

          <fieldset class="mp-bloque mp-cobro">
            <legend>Forma de pago de lo que se cobra hoy</legend>
            <div class="row">
              <div class="col-md-4 form-group">
                <label for="select_metodo_matricula">Forma de pago<b class="text-danger">(*)</b>:</label>
                <select class="form-control" id="select_metodo_matricula"></select>
              </div>
              <div class="col-md-4 form-group">
                <label for="select_cuenta_matricula">Caja / cuenta<b class="text-danger">(*)</b>:</label>
                <select class="form-control" id="select_cuenta_matricula"></select>
              </div>
              <div class="col-md-4 form-group">
                <label for="txt_ref_matricula">N.° operación / referencia:</label>
                <input type="text" class="form-control" id="txt_ref_matricula" maxlength="60">
              </div>
            </div>
          </fieldset>

          <fieldset class="mp-bloque">
            <legend>Beca o descuento en las pensiones (opcional)</legend>
            <div class="row">
              <div class="col-md-4 form-group">
                <label for="mp_beca_tipo">Tipo:</label>
                <select class="form-control" id="mp_beca_tipo">
                  <option value="NINGUNA">Sin beca</option>
                  <option value="PORCENTAJE">Porcentaje</option>
                  <option value="MONTO">Monto fijo por pensión</option>
                </select>
              </div>
              <div class="col-md-3 form-group mp-beca">
                <label for="mp_beca_valor" id="mp_beca_valor_lb">Porcentaje (%):</label>
                <input type="text" inputmode="decimal" class="form-control" id="mp_beca_valor">
              </div>
              <div class="col-md-5 form-group mp-beca">
                <label for="mp_beca_motivo">Motivo:</label>
                <input type="text" class="form-control" id="mp_beca_motivo" maxlength="255" placeholder="Hermanos, convenio, rendimiento…">
              </div>
            </div>
          </fieldset>
        </section>

        <section class="mp-paso" data-paso="5" hidden>
          <div id="mp_resumen"></div>
        </section>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger mr-auto" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-outline-secondary" id="mp_atras"><i class="fas fa-arrow-left mr-1"></i>Atrás</button>
        <button type="button" class="btn btn-primary" id="mp_siguiente">Siguiente<i class="fas fa-arrow-right ml-1"></i></button>
        <button type="button" class="btn btn-success" id="mp_registrar" onclick="Matricula_Registrar()" hidden><i class="fas fa-check mr-1"></i>Matricular</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal_mas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>DATOS DE LA MATRICULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-5 form-group">
                <label for="">DNI - Estudiante<b class="text-danger">(*)</b>:</label>
                <input type="text" class="form-control" id="estudiante" disabled>
            </div>
            <div class="col-2 form-group">
                <label for="">Tipo:</label>
                <input type="text" class="form-control" id="txt_tipo_mas" disabled>
          
            </div>
            <div class="col-2 form-group">
                <label for="">Año Académico:</label>        
                <input type="text" class="form-control" id="select_año_mas" disabled >

            </div>
            <div class="col-3 form-group">
                <label for="">Aula o Grado:</label>
                <input type="text" class="form-control" id="select_aula_mas" disabled >

                </select>                
            </div>
            <div class="col-3 form-group">
                <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nivel_mas" disabled >
            </div>
          
            <div class="col-3 form-group">
                <label for="txt_foto">Pago por Admisión <b class="text-danger">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_admision_mas" placeholder="Ingrese el monto" disabled  >
            </div>
            <div class="col-3 form-group">
                <label for="txt_foto">Pago Alumno Nuevo <b class="text-danger">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_alum_nuevo_mas" placeholder="Ingrese el monto" disabled>
            </div>
            <div class="col-3 form-group">
            <label for="txt_foto">Pago de Matrícula <b class="text-danger">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_matricula_mas" placeholder="Ingrese el monto" disabled>
            </div>


            <div class="col-4 form-group">
                <label for="txt_foto">Colegio de Procedencia:</label>
                <input type="text" class="form-control" id="txt_procedencia_mas" placeholder="Ingrese el coledio de procedencia" disabled>
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Provincia:</label>
                <input type="text" class="form-control" id="txt_provincia_mas" placeholder="Ingrese la provincia" disabled>
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Departamento:</label>
                <input type="text" class="form-control" id="txt_departamento_mas" placeholder="Ingrese el Departamento" disabled>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header ventana-cabecera">
        <h5 class="modal-title text-white text-center" id="exampleModalLabel"><b>EDITAR DATOS DE MATRICULA</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
        <div class="col-12 form-group text-danger">
            <h6><b>Campos Obligatorios (*)</b></h6>
            </div>
            <div class="col-5 form-group">
                <label for="">DNI - Estudiante<b class="text-danger">(*)</b>:</label>
                <input type="text" id="txt_id_matricula" hidden>
                <input type="text" id="txt_id_estudiante" hidden>
                <input type="text" class="form-control" id="select_estudiante_editar" disabled>
         
            </div>
            <div class="col-2 form-group">
                <label for="">Tipo:</label>
                <input type="text" class="form-control" id="txt_tipo_editar" disabled>
          
            </div>
            <div class="col-2 form-group">
                <label for="">Año Académico:</label>
                <select class="form-control" id="select_año_editar" style="width:100%">
                </select>             
            </div>
            <div class="col-3 form-group">
                <label for="">Aula o Grado:</label>
                <select class="form-control" id="select_aula_editar"  style="width:100%">
                </select>                
            </div>
           
            <div class="col-3 form-group">
                <label for="">Nivel Académico<b class="text-danger">(*)</b>:</label>
                <input type="text" class="form-control" id="txt_nivel_editar">
            </div>
          
            <div class="col-3 form-group">
                <label for="txt_foto">Pago por Admisión <b class="text-danger">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_admision_editar" value="0" placeholder="Ingrese el monto">
            </div>
            <div class="col-3 form-group">
                <label for="txt_foto">Pago Alumno Nuevo <b class="text-danger">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_alum_nuevo_editar" value="0" placeholder="Ingrese el monto">
            </div>
            <div class="col-3 form-group">
            <label for="txt_foto">Pago de Matrícula <b class="text-danger">(*)</b>:</label>
                <input type="number" class="form-control" id="txt_matricula_editar" value="0" placeholder="Ingrese el monto">
            </div>
            <div class="col-4 form-group">
                <label for="txt_foto">Colegio de Procedencia:</label>
                <input type="text" class="form-control" id="txt_procedencia_editar" placeholder="Ingrese el coledio de procedencia">
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Provincia:</label>
                <input type="text" class="form-control" id="txt_provincia_editar" placeholder="Ingrese la provincia">
            </div>
            <div class="col-4 form-group">
            <label for="txt_foto">Departamento:</label>
                <input type="text" class="form-control" id="txt_departamento_editar" placeholder="Ingrese el Departamento">
            </div>
          
           
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Modificar_Matricula()"><i class="fas fa-edit"></i> Modificar</button>
      </div>
    </div>
  </div>
</div>


<!-- Beca o descuento en las pensiones (Fase 8D) -->
<div class="modal fade" id="modal_beca" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info">
        <h5 class="modal-title text-white"><b>BECA O DESCUENTO</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="txt_id_matricula_beca">
        <p><b id="lb_beca_alumno"></b></p>
        <div class="row">
          <div class="col-12 form-group">
            <label>Tipo:</label>
            <select class="form-control" id="select_beca_tipo">
              <option value="NINGUNA">Sin beca ni descuento</option>
              <option value="PORCENTAJE">Porcentaje de la pensión</option>
              <option value="MONTO">Monto fijo por pensión</option>
            </select>
          </div>
          <div class="col-6 form-group beca-campos"><label id="lb_beca_valor">Valor:</label><input type="text" inputmode="decimal" class="form-control" id="txt_beca_valor"></div>
          <div class="col-6 form-group beca-campos"><label>Desde las pensiones que vencen el:</label><input type="date" class="form-control" id="txt_beca_desde"></div>
          <div class="col-12 form-group beca-campos"><label>Motivo<b class="text-danger">(*)</b>:</label><input type="text" class="form-control" id="txt_beca_motivo" maxlength="255" placeholder="p. ej. beca por rendimiento, descuento por hermanos"></div>
          <div class="col-12"><small class="text-muted">Se aplica a las pensiones (no a la mora ni a la matrícula). Si deja la fecha vacía, se aplica a todas las del año. Lo ya pagado no cambia; el cambio queda en la auditoría.</small></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Guardar_Beca()"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Inicialización de Select2 con la función personalizada
function initializeSelect2(selector) {
  $(selector).select2({
    dropdownParent: $('#modal_registro'),
    width: '100%',
    language: {
      noResults: function() {
        return "No se encontraron resultados";
      },
      searching: function() {
        return "Buscando...";
      }
    },
    debug: true, // Habilita el modo de depuración de Select2
    matcher: function(params, data) {
      // Implementa tu propia lógica de búsqueda si es necesario
      if ($.trim(params.term) === '') {
        return data;
      }

      if (typeof data.text === 'undefined') {
        return null;
      }

      if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        return data;
      }

      return null;
    }
  }).on('select2:open', function(e) {
    console.log('Select2 abierto:', e.target.id);
  }).on('select2:closing', function(e) {
    console.log('Select2 cerrándose:', e.target.id);
  }).on('select2:select', function(e) {
    console.log('Opción seleccionada:', e.params.data);
  });
}

$(document).ready(function () {
  listar_matriculados();
  
  // Inicializa Select2 para los selects con la clase 'js-example-basic-single'
  $('.js-example-basic-single').select2();

  // Cargar los selectores necesarios
  Cargar_Select_estudiante();
  Cargar_Select_Grado();
  Cargar_Año();
  Cargar_Select_Nivelaca();
});

$("#select_aula_editar").change(function(){
var id=$("#select_aula_editar").val();
Traernivel(id);
});


$("#select_nivel_buscar").change(function() {
      var id = $("#select_nivel_buscar").val();
      Cargar_Select_Aula(id);
    });

</script>
<style>
    .hidden {
        display: none;
    }
</style>