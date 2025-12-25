<script src="../js/console_notas_estudiante.js?rev=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><b>MANTENIMIENTO DE NOTAS POR ESTUDIANTE</b></h1>
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
                        <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de notas</b></h3>
                    </div>
                    <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 form-group">
                                    <label for="">Año académico || Grado  || Nivel Académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_año" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-6" role="document">
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


    <!-- /.content -->







    <div class="modal fade" id="modal_notas_ver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#1FA0E0;">
                    <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>VISUALIZAR NOTAS</b></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
         
                <div class="modal-body">
                    <div class="alert alert-warning alert-dismissible" style=" text-align: justify;">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Aviso Importante!</h5>
                        Si no logras visualizar las notas del bimestre correspondiente a la fecha, es posible que no estés al día con tus pagos. Por favor, revisa tu historial de pagos y verifica si hay algún saldo pendiente. En caso de no estar al día se le pide que pague su deuda para poder visualizar sus notas, y si fuera el caso que usted esta al día con su pago y no le filtra el bimestre que corresponde, te recomendamos ponerte en contacto con el área de soporte para más información.                   
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12" style="text-align:center">
                            <div class="table-responsive" style="text-align:left">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5 form-group">
                                            <label for="">Estudiante<b style="color:red">(*)</b>:</label>
                                            <input type="text" id="id_matri_ver" hidden>
                                            <input type="text" class="form-control" id="txt_estudiante_ver" disabled>
                                        </div>
                                        <div class="col-2 form-group">
                                            <label for="txt_nivel_ver">Nivel Académico<b style="color:red">(*)</b>:</label>
                                            <input type="text" class="form-control" id="txt_nivel_ver" disabled>
                                        </div>
                                        <div class="col-2 form-group">
                                            <label for="txt_aula_ver">Grado<b style="color:red">(*)</b>:</label>
                                            <input type="text" class="form-control" id="txt_aula_ver" disabled>
                                        </div>
                                        <div class="col-3 form-group">
                                            <label for="select_bimestre_ver">Periodo<b style="color:red">(*)</b>:</label>
                                            <select class="form-control" id="select_bimestre_ver" style="width:100%">
                                                <!-- Opciones se pueden agregar dinámicamente -->
                                            </select>
                                        </div>
                                        <div class="col-12 form-group">
                                            <button type="button" class="btn btn-success btn-block" onclick="listar_notas_ver()">
                                                <i class="fas fa-search"></i> <b>Listar notas</b>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive" style="text-align:center">
                                <div class="card-body">
                                    <table id="tabla_vistacomp_ver" class="display compact" style="width:100%; text-align:center; border-collapse: separate; border-spacing: 0 15px;">
                                        <thead style="background-color:#0A5D86;color:#FFFFFF;">
                                            <tr>
                                                <th style="text-align:center; padding: 10px;">Área</th>
                                                <th style="text-align:center; padding: 10px;">Competencias</th>
                                                <th style="text-align:center; padding: 10px;">Nota</th>
                                                <th style="text-align:center; padding: 10px;">Conclusión Descriptiva</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-toggle2" onclick="toggleTable2()">
                                <i class="fa fa-chevron-down"></i> Agregar notas padres/Ocultar notas padres
                            </button>

                            <div id="table-container2" class="toggle-content2" style="display: none;">
                                <div class="table-responsive" style="text-align:center">

                                    <div class="card-body">
                                        <table id="tabla_vistacomp_padre_ver" class="display compact" style="width:100%;">
                                            <thead>
                                                <tr style="background-color:#0A5D86; color:#FFFFFF;">
                                                    <th colspan="2" style="padding: 20px 20px 30px 20px; text-align: center; font-size: 1.2em;">
                                                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                                            <span style="flex-grow: 1; text-align: left;">EVALUACIÓN PADRES DE FAMILIA</span>
                                                        </div>
                                                    </th>
                                                </tr>
                                                <tr style="background-color:#0A5D86; color:#FFFFFF;">
                                                    <th style="text-align:center; padding: 15px; border-top: 2px solid #FFFFFF;">Competencias</th>
                                                    <th style="text-align:center; padding: 15px; border-top: 2px solid #FFFFFF;">Nota</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Las filas se llenarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
                </div>
            </div>
        </div>
    </div>




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
            Cargar_Select_Grado();
            Cargar_Año();
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