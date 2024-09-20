<script src="../js/console_tareas_estudiantes.js?rev=<?php echo time(); ?>"></script>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><b>MANTENIMIENTO DE TAREAS</b></h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
                    <li class="breadcrumb-item active">TAREAS</li>
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
                        <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Listado de Tareas</b></h3>
                    </div>
                    <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3 form-group">
                                    <label for="">Año académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_año" style="width:100%" disabled>
                                    </select>
                                </div>
                                <div class="col-3 form-group">
                                    <label for="">Grado o Aula<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_grado" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-3 form-group">
                                    <label for="">Curso<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_curso" style="width:100%" >
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_tareas_id()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar estudiantes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="text-align:center">
                        <div class="card-body">
                            <table id="tabla_tarea" class="table table-striped table-bordered" style="width:100%">
                                <thead style="background-color:#0A5D86;color:#FFFFFF;">
                                    <tr>
                                        <th style="text-align:center">Nro.</th>
                                        <th style="text-align:center">Grado</th>
                                        <th style="text-align:center">Curso - Docente</th>
                                        <th style="text-align:center">Tema o Tarea</th>
                                        <th style="text-align:center">Descripción</th>
                                        <th style="text-align:center">Fecha de publicación</th>
                                        <th style="text-align:center">Fecha de entrega</th>
                                        <th style="text-align:center">Archivo de Tarea</th>
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
    <div class="modal fade" id="modal_envio" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#1FA0E0;">
                    <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>REGISTRO DE TAREA</b></h5>
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
                            <label for="">Grado<b style="color:red">(*)</b>:</label>
                            <select class="form-control" id="select_grado_envio" style="width:100%">
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Curso<b style="color:red">(*)</b>:</label>
                            <select class="form-control" id="select_curso_en" style="width:100%">
                            </select>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Tema<b style="color:red">(*)</b>:</label>
                            <input type="text" class="form-control" id="txt_tema">
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Fecha entrega<b style="color:red">(*)</b>:</label>
                            <input type="datetime-local" class="form-control" id="txt_fecha_entre">
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Descripción<b style="color:red">(*)</b>:</label>
                            <textarea name="" id="txt_descripcion" rows="3" class="form-control" style="resize:none;"></textarea>
                        </div>
                        <div class="col-12 form-group">
                            <label for="txt_archivo">Adjuntar Archivo<b style="color:red">(*)</b>:</label>
                            <form id="form_tramite" method="post" enctype="multipart/form-data">
                                <input class="form-control" type="file" id="txt_archivo" name="archivos[]" multiple><br>
                                <ul id="fileList" class="file-list"></ul>
                                <label for="txt_archivo" style="font-size:16px;color:red">La carga de archivos solo se permiten pdf, docs, excel e imagenes.</label>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="Registrar_Tarea()"><i class="fas fa-save"></i> Registrar</button>
                </div>
            </div>
        </div>
    </div>





    <script>
        $(document).ready(function() {
            listar_tareas();

            $('.js-example-basic-single').select2();
            Cargar_Año();

            Cargar_Select_Grado();

        });

        $("#select_grado").change(function() {
            var id = $("#select_grado").val();
            Cargar_Select_curso(id);
        });


        $("#select_grado_editar").change(function() {
            var id = $("#select_grado_editar").val();
            Cargar_Select_curso(id);
        });


        $('#modal_registro').on('shown.bs.modal', function() {
            $('#txt_tema').trigger('focus')
        })

        $('input[type="file"]').on('change', function() {
            var ext = $(this).val().split('.').pop();
            console.log($(this).val());
            if ($(this).val() != '') {
                if (ext == "PDF" || ext == "pdf" || ext == "xlsx" || ext == "xlsm" || ext == "docx" || ext == "png" || ext == "jpg") {
                    if ($(this)[0].files[0].size > 31457280) { //----- 30 MB
                        //if($(this)[0].files[0].size> 1048576){ ------- 1 MB
                        //if($(this)[0].files[0].size> 10485760){ ------- 10 MB
                        Swal.fire("El archivo seleccionado es demasiado pesado",
                            "<label style='color:#9B0000;'>Seleccionar un archivo mas liviano</label>", "waning");
                        $("#txt_archivo").val("");
                        return;
                        //$("#btn_subir").prop("disabled",true);
                    } else {
                        //$("#btn_subir").attr("disabled",false);
                    }
                    $("#txtformato").val(ext);
                } else {
                    $("#txt_archivo").val("");
                    Swal.fire("Mensaje de Error", "Extensión no permitida: " + ext,
                        "error");
                }
            }
        });

        document.getElementById('txt_archivo').addEventListener('change', function() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';

            for (const file of this.files) {
                const listItem = document.createElement('li');
                listItem.textContent = file.name;
                fileList.appendChild(listItem);
            }


        });

        document.getElementById('txt_archivo_editar').addEventListener('change', function() {
            const fileList = document.getElementById('fileList2');
            fileList.innerHTML = '';

            for (const file of this.files) {
                const listItem = document.createElement('li');
                listItem.textContent = file.name;
                fileList.appendChild(listItem);
            }


        });

        var n = new Date();
        var y = n.getFullYear();
        var m = n.getMonth() + 1;
        var d = n.getDate();
        var h = n.getHours();
        var min = n.getMinutes();
        var s = n.getSeconds();

        // Asegurar que el día tenga dos dígitos
        if (d < 10) {
            d = '0' + d;
        }

        // Asegurar que el mes tenga dos dígitos
        if (m < 10) {
            m = '0' + m;
        }

        // Asegurar que la hora tenga dos dígitos
        if (h < 10) {
            h = '0' + h;
        }

        // Asegurar que los minutos tengan dos dígitos
        if (min < 10) {
            min = '0' + min;
        }

        // Asegurar que los segundos tengan dos dígitos
        if (s < 10) {
            s = '0' + s;
        }

        document.getElementById('txt_fecha_entre').value = y + "-" + m + "-" + d + " " + h + ":" + min + ":" + s;
    </script>
    <style>
        .file-list {
            list-style-type: none;
            padding: 0;
        }

        .file-list li {
            font-size: 14px;
        }
    </style>


    <!-- Incluye las librerías de DataTables