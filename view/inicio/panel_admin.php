<?php
// Panel de inicio del administrador (se incluye desde view/index.php). Los datos los llena js/panel_admin.js.
if (!function_exists('tema_actual')) { http_response_code(404); exit; }   // no se abre suelto
$TEMA = $TEMA ?? tema_actual();
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<div class="content pi">
  <div class="container-fluid">

    <section class="pi-cabecera" aria-label="Colegio">
      <div class="pi-identidad">
        <img src="../<?php echo $e($TEMA['logo'] ?: 'img/logo.jpeg'); ?>" alt="Escudo de <?php echo $e($TEMA['nombre']); ?>" class="pi-escudo">
        <div>
          <h1 class="pi-colegio"><?php echo $e($TEMA['nombre']); ?></h1>
          <p class="pi-lema"><?php echo $e($TEMA['lema'] ?: 'Panel de dirección'); ?></p>
        </div>
      </div>
      <div class="pi-fecha">
        <span id="pi_fecha"></span>
        <span id="pi_periodo" class="pi-periodo"></span>
      </div>
      <nav class="pi-accesos" aria-label="Accesos rápidos">
        <button type="button" onclick="cargar_contenido('contenido_principal','asistencia/view_registro_asistencia.php')"><i class="fas fa-clipboard-check"></i> Tomar asistencia</button>
        <button type="button" onclick="cargar_contenido('contenido_principal','matricula/view_matricula.php')"><i class="fas fa-user-plus"></i> Matricular</button>
        <button type="button" onclick="cargar_contenido('contenido_principal','pago_pensiones/view_pago_pensiones.php')"><i class="fas fa-cash-register"></i> Cobrar pensión</button>
        <button type="button" onclick="cargar_contenido('contenido_principal','comunicado/view_comunicado.php')"><i class="fas fa-bullhorn"></i> Nuevo comunicado</button>
        <button type="button" onclick="cargar_contenido('contenido_principal','reportes/view_reporte_estudiantes.php')"><i class="fas fa-list-alt"></i> Reportes</button>
      </nav>
    </section>

    <section class="pi-anio" aria-label="Año escolar">
      <div class="pi-anio-titulo">
        <h2 id="pi_anio_nombre">Año escolar</h2>
        <span id="pi_anio_resumen" class="pi-tenue"></span>
      </div>
      <div class="pi-linea" id="pi_linea" role="img" aria-label="Avance del año escolar"></div>
    </section>

    <section class="pi-comunidad" aria-label="Comunidad educativa">
      <a href="#" onclick="cargar_contenido('contenido_principal','matricula/view_matricula.php');return false;" class="pi-dato">
        <span class="pi-dato-icono"><i class="fas fa-user-graduate"></i></span>
        <span><b id="pi_estudiantes">–</b><span>Estudiantes matriculados</span><small id="pi_estudiantes_det"></small></span>
      </a>
      <a href="#" onclick="cargar_contenido('contenido_principal','docentes/view_docentes.php');return false;" class="pi-dato">
        <span class="pi-dato-icono"><i class="fas fa-chalkboard-teacher"></i></span>
        <span><b id="pi_docentes">–</b><span>Docentes activos</span><small id="pi_docentes_det"></small></span>
      </a>
      <a href="#" onclick="cargar_contenido('contenido_principal','personal_admin/view_personal_admin.php');return false;" class="pi-dato">
        <span class="pi-dato-icono"><i class="fas fa-user-tie"></i></span>
        <span><b id="pi_administrativos">–</b><span>Personal administrativo</span><small>activo</small></span>
      </a>
      <a href="#" onclick="cargar_contenido('contenido_principal','usuario/view_usuario.php');return false;" class="pi-dato">
        <span class="pi-dato-icono"><i class="fas fa-id-badge"></i></span>
        <span><b id="pi_usuarios">–</b><span>Usuarios del sistema</span><small id="pi_usuarios_det"></small></span>
      </a>
    </section>

    <div class="row">
      <div class="col-lg-7">
        <section class="pi-tarjeta">
          <header>
            <h2 id="pi_asis_titulo">Asistencia de hoy</h2>
            <a href="#" onclick="cargar_contenido('contenido_principal','asistencia/view_registro_asistencia.php');return false;">Registro de asistencia <i class="fas fa-arrow-right"></i></a>
          </header>
          <div id="pi_asistencia"><p class="pi-tenue mb-0">Cargando…</p></div>
        </section>
      </div>
      <div class="col-lg-5">
        <section class="pi-tarjeta">
          <header>
            <h2>Caja</h2>
            <a href="#" onclick="cargar_contenido('contenido_principal','reportes/view_reportes_financieros.php');return false;">Reportes financieros <i class="fas fa-arrow-right"></i></a>
          </header>
          <dl class="pi-caja">
            <div><dt>Ingresos de hoy</dt><dd id="pi_ing_hoy">–</dd></div>
            <div><dt>Gastos de hoy</dt><dd id="pi_egr_hoy">–</dd></div>
            <div><dt>Ingresos del mes</dt><dd id="pi_ing_mes">–</dd></div>
            <div><dt>Recibos emitidos hoy</dt><dd id="pi_recibos">–</dd></div>
          </dl>
        </section>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-5">
        <section class="pi-tarjeta">
          <header><h2>Pendientes</h2></header>
          <ul class="pi-pendientes" id="pi_pendientes"><li class="pi-tenue">Cargando…</li></ul>
        </section>
      </div>
      <div class="col-lg-7">
        <section class="pi-tarjeta">
          <header>
            <h2>Comunicados recientes</h2>
            <a href="#" onclick="cargar_contenido('contenido_principal','comunicado/view_comunicado.php');return false;">Ver todos <i class="fas fa-arrow-right"></i></a>
          </header>
          <ul class="pi-comunicados" id="pi_comunicados"><li class="pi-tenue">Cargando…</li></ul>
        </section>
      </div>
    </div>

    <section class="pi-tarjeta pi-institucion" aria-label="Datos de la institución">
      <img id="pi_inst_logo" src="../<?php echo $e($TEMA['logo'] ?: 'img/logo.jpeg'); ?>" alt="Escudo" class="pi-inst-logo">
      <div class="pi-inst-datos">
        <h2 id="pi_inst_nombre"><?php echo $e($TEMA['nombre']); ?></h2>
        <dl>
          <div><dt>Código modular</dt><dd id="pi_inst_cod">–</dd></div>
          <div><dt>Teléfono</dt><dd id="pi_inst_tel">–</dd></div>
          <div><dt>Correo</dt><dd id="pi_inst_email">–</dd></div>
          <div><dt>Dirección</dt><dd id="pi_inst_dir">–</dd></div>
        </dl>
      </div>
      <div class="pi-inst-acciones">
        <button type="button" class="btn btn-primary" onclick="PanelAdmin.editarInstitucion()"><i class="fas fa-edit"></i> Editar datos</button>
        <button type="button" class="btn btn-outline-primary" onclick="PanelAdmin.cambiarLogo()"><i class="fas fa-image"></i> Cambiar escudo</button>
        <button type="button" class="btn btn-outline-primary" onclick="cargar_contenido('contenido_principal','configuracion/view_apariencia.php')"><i class="fas fa-palette"></i> Colores y apariencia</button>
      </div>
    </section>

  </div>
</div>
