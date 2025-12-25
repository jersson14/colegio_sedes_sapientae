<script src="../js/console_boletas.js?rev=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><b>MANTENIMIENTO DE BOLETA DE NOTAS POR AULAS</b></h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
                    <li class="breadcrumb-item active">NOTAS</li>
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
                        <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de boletas por aulas</b></h3>
                    </div>
                    <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3 form-group">
                                    <label for="">Año académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_año" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-3 form-group">
                                    <label for="">Nivel Académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_nivel" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-3 form-group">
                                    <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_aula" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_notas_todos()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar estudiantes</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="text-align:center">
                        <div class="card-body">
                            <table id="tabla_notas" class="table table-striped table-bordered" style="width:100%">
                                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                                    <tr>
                                        <th style="text-align:center">Nro.</th>
                                        <th style="text-align:center">DNI</th>
                                        <th style="text-align:center">Estudiante</th>
                                        <th style="text-align:center">Nivel Académico</th>
                                        <th style="text-align:center">Sección</th>
                                        <th style="text-align:center">Aula o Grado</th>
                                        <th style="text-align:center">Año escolar</th>
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
    <!-- Modal -->
<div class="modal fade" id="modal_imprimir" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE AULA O GRADO ACADÉMICO</b></h5>
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
            <label for="">Estudiante<b style="color:red">(*)</b>:</label>
            <input type="text" id="txt_id_matricula" hidden>
            <input type="text" class="form-control" id="txt_alumno">
          </div>
          <div class="col-12 form-group">
            <label for="">Periodo<b style="color:red">(*)</b>:</label>
              <select class="form-control" id="select_bimestre" style="width:100%">
              </select>          
          </div>
         
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        <button type="button" class="btn btn-success" onclick="Imprimir_notas()"><i class="fas fa-print"></i> Imprimir</button>
      </div>
    </div>
  </div>
</div>

    <!-- /.content -->



    <style>
        .hidden {
            display: none;
        }
    </style>
    <style>
        .toggle-content {
            display: none;
            /* Ocultar por defecto */
            margin-top: 10px;
        }

        .btn-toggle {
            margin: 10px 0;
        }
    </style>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
            Cargar_Select_Nivelaca();
            Cargar_Año();
            Cargar_Bimestre();
        });
        $("#select_nivel").change(function() {
            var id = $("#select_nivel").val();
            Cargar_Select_Aula(id);
        });

        $('#modal_registro').on('shown.bs.modal', function() {
            $('#txt_matricula').trigger('focus')
        })
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
    <script>
        // Función para agregar una nueva fila
        function agregarFila() {
            // Seleccionar el cuerpo de la tabla
            const tbody = document.querySelector("#tabla_vistacomp_padre tbody");

            // Crear una nueva fila
            const nuevaFila = document.createElement("tr");

            // Crear celdas con inputs vacíos
            nuevaFila.innerHTML = `
<td><input type="text" style="width:95%; margin: 5px;"></td>
<td><input type="text" style="width:80%; margin: 5px;"></td>
`;

            // Añadir la nueva fila al final del cuerpo de la tabla
            tbody.appendChild(nuevaFila);
        }

        // Función para eliminar la última fila
        function eliminarFila() {
            // Seleccionar el cuerpo de la tabla
            const tbody = document.querySelector("#tabla_vistacomp_padre tbody");

            // Verificar que haya más de una fila antes de eliminar
            if (tbody.rows.length > 1) {
                tbody.removeChild(tbody.lastChild);
            } else {
                alert("No se puede eliminar la última fila.");
            }
        }

        //EDICION

        function agregarFila_editar() {
            // Seleccionar el cuerpo de la tabla
            const tbody = document.querySelector("#tabla_vistacomp_padre_editar tbody");

            // Crear una nueva fila
            const nuevaFila = document.createElement("tr");

            // Crear celdas con inputs vacíos
            nuevaFila.innerHTML = `
<td><input type="text" class="form-control" style="width:100%; margin: 5px;"></td>
<td><input type="text" class="form-control" style="width:100%; margin: 5px;"></td>

<td><input type="text" class="form-control" style="width:100%; margin: 5px;"></td>
`;

            // Añadir la nueva fila al final del cuerpo de la tabla
            tbody.appendChild(nuevaFila);
        }

        // Función para eliminar la última fila
        function eliminarFila_editar() {
            // Seleccionar el cuerpo de la tabla
            const tbody = document.querySelector("#tabla_vistacomp_padre_editar tbody");

            // Verificar que haya más de una fila antes de eliminar
            if (tbody.rows.length > 1) {
                tbody.removeChild(tbody.lastChild);
            } else {
                alert("No se puede eliminar la última fila.");
            }
        }
    </script>
    <script>
        function toggleTable() {
            var container = document.getElementById('table-container');
            var button = document.querySelector('.btn-toggle');
            if (container.style.display === 'none' || container.style.display === '') {
                container.style.display = 'block';
                button.innerHTML = '<i class="fa fa-chevron-up"></i> Ocultar Tabla notas padre'; // Cambia el texto del botón
            } else {
                container.style.display = 'none';
                button.innerHTML = '<i class="fa fa-chevron-down"></i> Mostrar Tabla notas padre'; // Cambia el texto del botón
            }
        }

        function toggleTable2() {
            var container = document.getElementById('table-container2');
            var button = document.querySelector('.btn-toggle2');
            if (container.style.display === 'none' || container.style.display === '') {
                container.style.display = 'block';
                button.innerHTML = '<i class="fa fa-chevron-up"></i> Ocultar Tabla notas padre'; // Cambia el texto del botón
            } else {
                container.style.display = 'none';
                button.innerHTML = '<i class="fa fa-chevron-down"></i> Mostrar Tabla notas padre'; // Cambia el texto del botón
            }
        }
    </script>