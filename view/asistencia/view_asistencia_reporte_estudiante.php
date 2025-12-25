<script src="../js/console_asistencia_estudiante.js?rev=<?php echo time(); ?>"></script>
<link rel="stylesheet" href="../plantilla/plugins/icheck-bootstrap/icheck-bootstrap.min.css">

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><b>REPORTE DE ASISTENCIA</b></h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
                    <li class="breadcrumb-item active">ASISTENCIA</li>
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
                        <h3 class="card-title"><i class="nav-icon fas fa-calendar"></i>&nbsp;&nbsp;<b>Asistencia Por mes y Aula en días</b></h3>
                    </div>
                    <div class="table-responsive" style="text-align:left">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">Año académico<b style="color:red">(*)</b>:</label>
                                    <select class="form-control" id="select_año" style="width:100%">
                                    </select>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <div class="form-group">
                                        <label for="txtfechainicio">Mes Académico</label>
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">

                                            </div>
                                            <select class="form-control" id="select_meses_buscar" style="width:100%">
                                                <option value="1">ENERO</option>
                                                <option value="2">FEBRERO</option>
                                                <option value="3">MARZO</option>
                                                <option value="4">ABRIL</option>
                                                <option value="5">MAYO</option>
                                                <option value="6">JUNIO</option>
                                                <option value="7">JULIO</option>
                                                <option value="8">AGOSTO</option>
                                                <option value="9">SETIEMBRE</option>
                                                <option value="10">OCTUBRE</option>
                                                <option value="11">NOVIEMBRE</option>
                                                <option value="12">DICIEMBRE</option>
                                            </select>
                                            <div class="valid-input invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-3" role="document">
                                    <div class="form-group">
                                        <label for="txtfechafin">Grado o Aula:</label>
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">

                                            </div>
                                            <select class="form-control" id="select_aula_buscar_2" style="width:100%">
                                            </select>
                                            <div class="valid-input invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3" role="document">
                                    <label for="">&nbsp;</label><br>
                                    <button onclick="listar_alumnos_totales_dia()" class="btn btn-danger mr-2" style="width:100%"><i class="fas fa-search mr-1"></i>Buscar Asistencia en días</button>
                                </div>
                            </div>

                        </div>

                        <div class="table-responsive" style="text-align:center">
                            <div class="row">
                                <div class="col-lg-12 form-group" style="text-align:center">
                                    <label for="" style="color:#000000">LEYENDA: </label>
                                    <span class="badge bg-success"> P = PRESENTE</span>
                                    <span class="badge bg-warning"> T = TARDE</span>
                                    <span class="badge bg-danger"> A = AUSENTE</span>
                                    <span class="badge bg-dark"> FJ = JUSTIFICADO</span>
                                </div>

                            </div>
                            <div class="card-body">
                                <table id="tabla_asistencia_totales_dia" class="table table-striped table-bordered" style="width:100%">
                                    <thead style="background-color:#0A5D86;color:#FFFFFF;">
                                        <tr>
                                            <th style="text-align:center">Nro.</th>
                                            <th style="text-align:center">DNI</th>
                                            <th style="text-align:center">ESTUDIANTE</th>
                                            <!-- Días del 1 al 31 -->
                                            <th style="text-align:center">1</th>
                                            <th style="text-align:center">2</th>
                                            <th style="text-align:center">3</th>
                                            <th style="text-align:center">4</th>
                                            <th style="text-align:center">5</th>
                                            <th style="text-align:center">6</th>
                                            <th style="text-align:center">7</th>
                                            <th style="text-align:center">8</th>
                                            <th style="text-align:center">9</th>
                                            <th style="text-align:center">10</th>
                                            <th style="text-align:center">11</th>
                                            <th style="text-align:center">12</th>
                                            <th style="text-align:center">13</th>
                                            <th style="text-align:center">14</th>
                                            <th style="text-align:center">15</th>
                                            <th style="text-align:center">16</th>
                                            <th style="text-align:center">17</th>
                                            <th style="text-align:center">18</th>
                                            <th style="text-align:center">19</th>
                                            <th style="text-align:center">20</th>
                                            <th style="text-align:center">21</th>
                                            <th style="text-align:center">22</th>
                                            <th style="text-align:center">23</th>
                                            <th style="text-align:center">24</th>
                                            <th style="text-align:center">25</th>
                                            <th style="text-align:center">26</th>
                                            <th style="text-align:center">27</th>
                                            <th style="text-align:center">28</th>
                                            <th style="text-align:center">29</th>
                                            <th style="text-align:center">30</th>
                                            <th style="text-align:center">31</th>
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


        <div class="content">
            <div class="container-fluid">
                <div class="row">

                    <!-- /.col-md-6 -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="nav-icon fas fa-th"></i>&nbsp;&nbsp;<b>Total de Asistencia Por mes y Aula</b></h3>
                            </div>
                            <div class="table-responsive" style="text-align:left">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12 col-md-3" role="document">
                                            <label for="">Año académico<b style="color:red">(*)</b>:</label>
                                            <select class="form-control" id="select_año_2" style="width:100%">
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3" role="document">
                                            <div class="form-group">
                                                <label for="txtfechainicio">Mes Académico</label>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend"></div>
                                                    <select class="form-control" id="select_meses" style="width:100%">
                                                        <option value="1">ENERO</option>
                                                        <option value="2">FEBRERO</option>
                                                        <option value="3">MARZO</option>
                                                        <option value="4">ABRIL</option>
                                                        <option value="5">MAYO</option>
                                                        <option value="6">JUNIO</option>
                                                        <option value="7">JULIO</option>
                                                        <option value="8">AGOSTO</option>
                                                        <option value="9">SETIEMBRE</option>
                                                        <option value="10">OCTUBRE</option>
                                                        <option value="11">NOVIEMBRE</option>
                                                        <option value="12">DICIEMBRE</option>
                                                    </select>
                                                    <div class="valid-input invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-3" role="document">
                                            <div class="form-group">
                                                <label for="txtfechafin">Grado o Aula:</label>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend"></div>
                                                    <select class="form-control" id="select_aula_buscar" style="width:100%"></select>
                                                    <div class="valid-input invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-3" role="document">
                                            <label for="">&nbsp;</label><br>
                                            <button onclick="listar_alumnos_totales()" class="btn btn-danger mr-2" style="width:100%"><i class="fas fa-search mr-1"></i>Buscar Asistencia</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive" style="text-align:center">
                                    <div class="card-body">
                                        <table id="tabla_asistencia_totales" class="table table-striped table-bordered" style="width:100%">
                                            <thead style="background-color:#0A5D86;color:#FFFFFF;">
                                                <tr>
                                                    <th style="text-align:center">Nro.</th>
                                                    <th style="text-align:center">DNI</th>
                                                    <th style="text-align:center">ESTUDIANTE</th>
                                                    <th style="text-align:center">TOTAL PRESENTE</th>
                                                    <th style="text-align:center">TOTAL TARDE</th>
                                                    <th style="text-align:center">TOTAL FALTAS</th>
                                                    <th style="text-align:center">TOTAL FALTAS JUSTIFICADAS</th>
                                                    <th style="text-align:center">TOTAL GENERAL</th>
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
            </div>
        </div>


        <!-- Modal -->



        <style>
            .hidden {
                display: none;
            }
        </style>

        <script>
            $(document).ready(function() {
                $('.js-example-basic-single').select2();
                Cargar_Año();
                Cargar_Select_Grado();
            });

        </script>