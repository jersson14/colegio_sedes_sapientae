<script src="../js/console_solicitudes.js?rev=<?php echo time(); ?>"></script>

<section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-envelope"></i> Solicitudes de Información</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../view/index.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Solicitudes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="stat_total">0</h3>
                            <p>Total Solicitudes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="stat_pendientes">0</h3>
                            <p>Pendientes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="stat_atendidos">0</h3>
                            <p>Atendidos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3 id="stat_hoy">0</h3>
                            <p>Hoy</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de solicitudes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Listado de Solicitudes</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Filtrar por Estado:</label>
                            <select class="form-control" id="select_filtro_estado" onchange="Filtrar_Por_Estado()">
                                <option value="">Todos</option>
                                <option value="PENDIENTE">Pendientes</option>
                                <option value="CONTACTADO">Contactados</option>
                                <option value="ATENDIDO">Atendidos</option>
                                <option value="CANCELADO">Cancelados</option>
                            </select>
                        </div>
                        <div class="col-md-9 mb-3">
                            <label>Buscar:</label>
                            <input type="text" class="global_filter form-control" id="global_filter" placeholder="Buscar por nombre, email, teléfono...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tabla_solicitudes" class="table table-bordered table-striped table-hover" style="width:100%">
                            <thead class="bg-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Nivel</th>
                                    <th>Mensaje</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Modal Ver Solicitud -->
<div class="modal fade" id="modal_ver_solicitud" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-eye"></i> Detalles de la Solicitud</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="txt_id_solicitud" hidden>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nombre Completo:</label>
                            <input type="text" class="form-control" id="txt_nombre_ver" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email:</label>
                            <input type="text" class="form-control" id="txt_email_ver" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Teléfono:</label>
                            <input type="text" class="form-control" id="txt_telefono_ver" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Nivel de Interés:</label>
                            <input type="text" class="form-control" id="txt_nivel_ver" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Fecha de Registro:</label>
                            <input type="text" class="form-control" id="txt_fecha_ver" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-info-circle"></i> Estado:</label>
                            <input type="text" class="form-control" id="txt_estado_ver" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Mensaje:</label>
                    <textarea class="form-control" id="txt_mensaje_ver" rows="4" readonly></textarea>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sticky-note"></i> Observaciones:</label>
                    <textarea class="form-control" id="txt_observaciones_ver" rows="3" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Estado -->
<div class="modal fade" id="modal_editar_solicitud" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Actualizar Estado de Solicitud</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="txt_id_solicitud_editar" hidden>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Solicitante:</label>
                    <p class="form-control-static"><strong id="txt_nombre_editar"></strong></p>
                </div>
                <div class="form-group">
                    <label for="select_estado"><i class="fas fa-info-circle"></i> Estado: <span class="text-danger">*</span></label>
                    <select class="form-control" id="select_estado">
                        <option value="">Seleccione...</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="CONTACTADO">CONTACTADO</option>
                        <option value="ATENDIDO">ATENDIDO</option>
                        <option value="CANCELADO">CANCELADO</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="txt_observaciones_editar"><i class="fas fa-sticky-note"></i> Observaciones:</label>
                    <textarea class="form-control" id="txt_observaciones_editar" rows="4" placeholder="Agregar notas sobre el seguimiento..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button>
                <button type="button" class="btn btn-success" onclick="Actualizar_Estado_Solicitud()"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    listar_solicitudes();
    Cargar_Estadisticas();
    
    // Recargar estadísticas cada 30 segundos
    setInterval(function() {
        Cargar_Estadisticas();
    }, 30000);
});
</script>
