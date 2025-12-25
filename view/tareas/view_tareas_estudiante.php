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
                                <div class="col-2 form-group">
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
                                    <select class="form-control" id="select_curso" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-2" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_tareas_id()" class="btn btn-danger mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Buscar tareas</button>
                                </div>
                                <div class="col-12 col-md-2" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_tareas()" class="btn btn-success mr-2" style="width:100%" onclick><i class="fas fa-search mr-1"></i>Listar todo</button>
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
                                        <th style="text-align:center">Archivo enviado</th>
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
                    <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>ENVÍO DE TAREA</b></h5>
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
                            <input type="text" id="txt_id_detalle_tarea" hidden>
                            <select class="form-control" id="select_grado_envio" style="width:100%" disabled>
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Curso<b style="color:red">(*)</b>:</label>
                            <select class="form-control" id="select_curso_en" style="width:100%" disabled>
                            </select>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Tema<b style="color:red">(*)</b>:</label>
                            <input type="text" class="form-control" id="txt_tema" disabled>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Fecha entrega<b style="color:red">(*)</b>:</label>
                            <input type="datetime-local" class="form-control" id="txt_fecha_entre_envio" disabled>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Descripción<b style="color:red">(*)</b>:</label>
                            <textarea name="" id="txt_descripcion" rows="3" class="form-control" style="resize:none;" disabled></textarea>
                        </div>
                        <div class="col-12 form-group">
                            <label for="txt_archivo">Adjuntar Archivo de tarea realizada<b style="color:red">(*)</b>:</label>
                            <form id="form_tramite" method="post" enctype="multipart/form-data">
                                <input type="text" id="archivo_actual" hidden>
                                <input class="form-control" type="file" id="txt_archivo" name="archivos[]" multiple><br>
                                <ul id="fileList" class="file-list"></ul>
                                <label for="txt_archivo" style="font-size:16px;color:red">La carga de archivos solo se permiten pdf, docs, excel e imágenes.</label>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="Registrar_Tarea_envio()"><i class="fas fa-upload"></i> Enviar tarea</button>
                </div>
            </div>
        </div>
    </div>




    <div class="modal fade" id="modal_editar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#1FA0E0;">
                    <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>MODIFICAR ARCHIVOS DE TAREA</b></h5>
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
                            <input type="text" id="txt_id_detalle_tarea_editar" hidden>
                            <select class="form-control" id="select_grado_editar" style="width:100%" disabled>
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Curso<b style="color:red">(*)</b>:</label>
                            <select class="form-control" id="select_curso_editar" style="width:100%" disabled>
                            </select>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Tema<b style="color:red">(*)</b>:</label>
                            <input type="text" class="form-control" id="txt_tema_editar" disabled>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Fecha entrega<b style="color:red">(*)</b>:</label>
                            <input type="datetime-local" class="form-control" id="txt_fecha_entre_envio_editar" disabled>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Descripción<b style="color:red">(*)</b>:</label>
                            <textarea name="" id="txt_descripcion_editar" rows="3" class="form-control" style="resize:none;" disabled></textarea>
                        </div>
                        <div class="col-12 form-group">
                            <label for="txt_archivo_editar">Adjuntar Archivo de tarea realizada<b style="color:red">(*)</b>:</label>
                            <form id="form_tramite" method="post" enctype="multipart/form-data">
                                <input type="text" id="archivo_actual_editar" hidden>
                                <input class="form-control" type="file" id="txt_archivo_editar" name="archivos[]" multiple><br>
                                <ul id="fileList2" class="file-list2"></ul>
                                <label for="txt_archivo" style="font-size:16px;color:red">La carga de archivos solo se permiten pdf, docs, excel e imágenes.</label>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="Modificar_Tarea_envio()"><i class="fas fa-upload"></i> Enviar tarea</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_nota" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#1FA0E0;">
                    <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>CALIFICACIÓN</b></h5>
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
                            <select class="form-control" id="select_grado_ver" style="width:100%" disabled>
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Curso<b style="color:red">(*)</b>:</label>
                            <select class="form-control" id="select_curso_ver" style="width:100%" disabled>
                            </select>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Tema<b style="color:red">(*)</b>:</label>
                            <input type="text" class="form-control" id="txt_tema_ver" disabled>
                        </div>
                        <div class="col-6 form-group">
                            <label for="">Fecha entrega<b style="color:red">(*)</b>:</label>
                            <input type="datetime-local" class="form-control" id="txt_fecha_entre_envi_ver" disabled>
                        </div>
                        <div class="col-12 form-group">
                            <label for="">Descripción<b style="color:red">(*)</b>:</label>
                            <textarea name="" id="txt_descripcion_ver" rows="2" class="form-control" style="resize:none;" disabled></textarea>
                        </div>
                        <div class="col-12 form-group">
                            <label style="color:red">Observaciones del profesor <b >(*)</b>:</label>
                            <textarea name="" id="txt_observacion" rows="2" class="form-control" style="resize:none;" disabled></textarea>
                        </div>
                        <!-- Agregando el h1 y h3 con sus respectivos IDs -->
                        <div class="col-12 form-group" style="text-align:center; border: 1px solid #000;">
                        <h4 id="titulo_h1"></h4>
                            <h1 id="titulo_h2" style="font-size:70px;family-font:Arial"></h1>
                            <h3 id="subtitulo_h3"></h3>
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

        $("#select_grado_envio").change(function() {
            var id = $("#select_grado_envio").val();
            Cargar_Select_curso(id);
        });

        $("#select_grado_ver").change(function() {
            var id = $("#select_grado_ver").val();
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