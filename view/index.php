<?php
require_once __DIR__ . '/../utilitario/sesion.php';
require_once __DIR__ . '/../utilitario/regional.php';
require_once __DIR__ . '/../utilitario/tema.php';
iniciar_sesion_segura();
if (!isset($_SESSION['S_ID'])) {
  header('Location: ../index.php');
  exit;
}
// Contraseña temporal: antes de entrar al sistema, la persona crea la suya
if (!empty($_SESSION['S_DEBE_CAMBIAR'])) {
  include __DIR__ . '/inicio/cambiar_clave.php';
  exit;
}
?>
<?php
if (isset($_SESSION['S_FOTO']) && !empty($_SESSION['S_FOTO'])) {
  // Verificar si la ruta es 'controller/alumnos/fotos/'
  if ($_SESSION['S_FOTO'] === 'controller/alumnos/fotos/') {
    $rutaFoto = '../img/blanco1.jpg';
  } else {
    $rutaFoto = '../' . $_SESSION['S_FOTO'];
  }
} else {
  $rutaFoto = '../img/blanco1.jpg';
}
?>
<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $TEMA = tema_actual(); ?>
  <title><?php echo htmlspecialchars($TEMA['nombre'] ?: 'Sistema escolar', ENT_QUOTES, 'UTF-8'); ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="../plantilla/plugins//fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="icon" href="../<?php echo htmlspecialchars($TEMA['logo'] ?: 'img/icono.jpeg', ENT_QUOTES, 'UTF-8'); ?>">

  <link rel="stylesheet" href="../plantilla/dist//css/adminlte.min.css">
  <link href="../utilitario/DataTables/datatables.min.css" type="text/css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <!-- Apariencia del colegio (Configuración > Apariencia) -->
  <?php echo tema_css_variables($TEMA); ?>
  <link rel="stylesheet" href="../css/tema.css?rev=<?php echo filemtime(__DIR__ . '/../css/tema.css'); ?>">
  <link rel="stylesheet" href="../css/inicio.css?rev=<?php echo filemtime(__DIR__ . '/../css/inicio.css'); ?>">
</head>

<body class="layout-fixed tema-menu-<?php echo strtolower($TEMA['estilo_menu']); ?>">
  <div class="wrapper">
    <?php if ($_SESSION['S_ROL'] == "ADMINISTRADOR") { ?>
      <!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item bg-buscador" role="search">
            <i class="fas fa-search"></i>
            <input type="search" id="buscador_global" class="form-control" placeholder="Buscar estudiante…" title="Nombre o documento del estudiante" aria-label="Buscar estudiante" autocomplete="off" oninput="Buscador_Global(this)" onkeydown="Buscador_Global_Tecla(event)">
            <kbd title="Pulse / para buscar">/</kbd>
            <div id="buscador_global_resultados" class="bg-resultados" role="listbox" hidden></div>
          </li>
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <!-- Notifications Dropdown Menu -->
          <li class="nav-item dropdown" style="text-align:justify;">

            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="text-align:justify">
              <div id="div_cuerpo" style="text-align:justify; border: 1px solid #333333;width: 100%;font-size: 100%;overflow-x: scroll;">
              </div>


              <div class="dropdown-divider"></div>
              <a href="" class="dropdown-item dropdown-footer" onclick="listar_comunicado_dash()"><b><u>Ver Comunicados</u></b></a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              <img src="../<?php echo isset($_SESSION['S_FOTO']) && !empty($_SESSION['S_FOTO']) ? $_SESSION['S_FOTO'] : '../img/blanco1.jpg'; ?>" class="img-circle elevation-1" width="15" height="18">
              <b>Usuario: <?php echo $_SESSION['S_COMPLETO'] ?></b>
              <i class="fas fa-caret-down"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
              <a href="#" class="dropdown-item" onclick="Cuenta.abrir();return false;">
                <i class="fas fa-key mr-2"></i>Cambiar mi contraseña
              </a>
              <div class="dropdown-divider"></div>
              <a href="../controller/usuario/controlador_cerrar_sesion.php" class="dropdown-item">
                <i class="fas fa-power-off mr-2"></i><u><b>Cerrar Sesión</b></u>
              </a>
              <div class="dropdown-divider"></div>
            </div>
          </li>
        </ul>

      </nav>
    <?php
    }
    ?>
    <?php if ($_SESSION['S_ROL'] == "ENFERMERA" || $_SESSION['S_ROL'] == "PSICOLOGA" || $_SESSION['S_ROL'] == "AUXILIAR" || $_SESSION['S_ROL'] == "DOCENTE" || $_SESSION['S_ROL'] == "ESTUDIANTE" || $_SESSION['S_ROL'] == "CONTADOR") { ?>
      <!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
          <?php if ($_SESSION['S_ROL'] == "AUXILIAR" || $_SESSION['S_ROL'] == "CONTADOR") { ?>
            <li class="nav-item bg-buscador" role="search">
              <i class="fas fa-search"></i>
              <input type="search" id="buscador_global" class="form-control" placeholder="Buscar estudiante…" title="Nombre o documento del estudiante" aria-label="Buscar estudiante" autocomplete="off" oninput="Buscador_Global(this)" onkeydown="Buscador_Global_Tecla(event)">
              <kbd title="Pulse / para buscar">/</kbd>
              <div id="buscador_global_resultados" class="bg-resultados" role="listbox" hidden></div>
            </li>
          <?php } ?>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <!-- Notifications Dropdown Menu -->
          <li class="nav-item dropdown" style="text-align:justify;">

            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="text-align:justify">
              <div id="div_cuerpo" style="text-align:justify; border: 1px solid #333333;width: 100%;font-size: 100%;overflow-x: scroll;">
              </div>


              <div class="dropdown-divider"></div>
              <a href="" class="dropdown-item dropdown-footer" onclick="listar_comunicado_dash()"><b><u>Ver Comunicados</u></b></a>
            </div>
          </li>


          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              <img src="../<?php echo isset($_SESSION['S_FOTO']) && !empty($_SESSION['S_FOTO']) ? $_SESSION['S_FOTO'] : '../img/blanco1.jpg'; ?>" class="img-circle elevation-1" width="15" height="18">

              <b>Usuario: <?php echo $_SESSION['S_COMPLETO'] ?></b>
              <i class="fas fa-caret-down"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
              <a href="#" class="dropdown-item" onclick="Cuenta.abrir();return false;">
                <i class="fas fa-key mr-2"></i>Cambiar mi contraseña
              </a>
              <div class="dropdown-divider"></div>
              <a href="../controller/usuario/controlador_cerrar_sesion.php" class="dropdown-item">
                <i class="fas fa-power-off mr-2"></i><u><b>Cerrar Sesión</b></u>
              </a>
              <div class="dropdown-divider"></div>
            </div>
          </li>
        </ul>

      </nav>
    <?php
    }
    ?>
    <!-- /.navbar -->
    <input type="text" id="txtprincipalid" value="<?php echo $_SESSION['S_ID']; ?>" hidden>
    <input type="text" id="txtprincipalfoto" value="<?php echo $_SESSION['S_FOTO']; ?>" hidden>
    <input type="text" id="txtprincipalcompleto" value="<?php echo $_SESSION['S_COMPLETO']; ?>" hidden>
    <input type="text" id="txtprincipaldni" value="<?php echo $_SESSION['S_DNI']; ?>" hidden>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar <?php echo $TEMA['estilo_menu'] === 'CLARO' ? 'sidebar-light-primary' : 'sidebar-dark-primary'; ?> elevation-4">
      <!-- Brand Logo -->
      <a href="index.php" class="brand-link" title="Inicio">
        <img src="../<?php echo htmlspecialchars($TEMA['logo'] ?: 'img/logo.jpeg', ENT_QUOTES, 'UTF-8'); ?>" alt="Escudo del colegio" class="tema-escudo">
        <span class="tema-marca"><b><?php echo htmlspecialchars($TEMA['nombre'], ENT_QUOTES, 'UTF-8'); ?></b><?php if ($TEMA['lema']) { ?><small><?php echo htmlspecialchars($TEMA['lema'], ENT_QUOTES, 'UTF-8'); ?></small><?php } ?></span>
      </a>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-1 pb-3 mb-3 d-flex">
          <div class="image">
            <img src="../<?php echo isset($_SESSION['S_FOTO']) && !empty($_SESSION['S_FOTO']) ? $_SESSION['S_FOTO'] : '../img/blanco1.jpg'; ?>" class="img-circle elevation-2" style="max-width: 100%;height: auto;">

          </div>
          <div class="info">
            <a style="text-align:center;" href="#" class="d-block"><i class="fa fa-circle text-success fa-0x"></i> ¡Hola!<br> <b style="color:white"><?php echo $_SESSION['S_NOMBRE']; ?></b></a>
            <a style="text-align:center;margin:5px;color:white;font-size:15px" href="#" class="d-block">&nbsp;&nbsp;<b style="text-align:center"><i class="fa fa-user text-success fa-0x"></i><em> ROL: <?php echo $_SESSION['S_ROL']; ?></em></b></a>

          </div>
        </div>
        <!-- Sidebar Menu -->
        <nav class="mt-1">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li class="nav-item menu-buscar">
              <div class="input-group input-group-sm">
                <input type="search" id="menu_buscar" class="form-control" placeholder="Buscar en el menú..." aria-label="Buscar en el menú" autocomplete="off">
                <div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
              </div>
              <small id="menu_sin_resultados" class="menu-sin-resultados" hidden>No hay opciones con ese nombre.</small>
            </li>

            <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
            <?php if ($_SESSION['S_ROL'] == "ADMINISTRADOR") { ?>
              <li class="nav-item">
                <a href="index.php" class="nav-link">
                  <i class="nav-icon fas fa-home"></i>
                  <p>Inicio</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">ESTUDIANTES Y MATRÍCULA</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','alumnos/view_alumnos.php')" class="nav-link">
                  <i class="nav-icon fas fa-user-graduate"></i>
                  <p>Estudiantes</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','matricula/view_matricula.php')" class="nav-link">
                  <i class="nav-icon fas fa-id-card"></i>
                  <p>Matrícula</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','reportes/view_reporte_estudiantes.php')" class="nav-link">
                  <i class="nav-icon fas fa-list-alt"></i>
                  <p>Reporte de estudiantes</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">ACADÉMICO</li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-clipboard-check"></i>
                  <p>Notas y evaluación<i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','notas/view_notas.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Notas</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','componentes/view_componentes.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Criterios de evaluación</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','boletas/view_boletas.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Libretas de notas</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','examen/view_examen.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Exámenes</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-book-open"></i>
                  <p>Tareas<i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','tareas/view_tareas.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Tareas</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','tareas/view_registro_tareas.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Registro de tareas</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-chalkboard-teacher"></i>
                  <p>Cursos y horarios<i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','asignaturas/view_asignatura.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Asignaturas</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','docente_asignatura/view_docenteasignatura.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Cursos de cada docente</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','horario/view_horario.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Horarios</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','aula_hora/view_aula_hora.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Horas de clase por aula</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-header menu-seccion">ASISTENCIA</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','asistencia/view_registro_asistencia.php')" class="nav-link">
                  <i class="nav-icon fas fa-table"></i>
                  <p>Registro de asistencia</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','asistencia/view_asistencia_reportes.php')" class="nav-link">
                  <i class="nav-icon fas fa-chart-bar"></i>
                  <p>Reporte de asistencias</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','asistencia_docente/view_asistencia_docente.php')" class="nav-link">
                  <i class="nav-icon fas fa-user-check"></i>
                  <p>Asistencia docente</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">PENSIONES Y CAJA</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','pago_pensiones/view_pago_pensiones.php')" class="nav-link">
                  <i class="nav-icon fas fa-hand-holding-usd"></i>
                  <p>Cobrar pensión</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','pensiones/view_pensiones.php')" class="nav-link">
                  <i class="nav-icon fas fa-file-invoice"></i>
                  <p>Pensiones</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-coins"></i>
                  <p>Ingresos y gastos<i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','ingresos_y_egresos/view_ingresos.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Ingresos</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','ingresos_y_egresos/view_egresos.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Gastos</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="#" onclick="cargar_contenido('contenido_principal','ingresos_y_egresos/view_indicadores.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Categorías</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','contabilidad/view_cajas_bancos.php')" class="nav-link">
                  <i class="nav-icon fas fa-cash-register"></i>
                  <p>Cajas y bancos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','reportes/view_reportes_financieros.php')" class="nav-link">
                  <i class="nav-icon fas fa-chart-line"></i>
                  <p>Reportes financieros</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">PERSONAL</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','docentes/view_docentes.php')" class="nav-link">
                  <i class="nav-icon fas fa-chalkboard-teacher"></i>
                  <p>Docentes</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','personal_admin/view_personal_admin.php')" class="nav-link">
                  <i class="nav-icon fas fa-user-tie"></i>
                  <p>Personal administrativo</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','planilla/view_planilla.php')" class="nav-link">
                  <i class="nav-icon fas fa-file-invoice-dollar"></i>
                  <p>Planilla de personal</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">COMUNICACIÓN Y SALUD</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','comunicado/view_comunicado.php')" class="nav-link">
                  <i class="nav-icon fas fa-bullhorn"></i>
                  <p>Comunicados</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','solicitudes/view_solicitudes.php')" class="nav-link">
                  <i class="nav-icon fas fa-envelope"></i>
                  <p>Solicitudes de información</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','atenciones/view_atencion_enfermeria.php')" class="nav-link">
                  <i class="nav-icon fas fa-briefcase-medical"></i>
                  <p>Enfermería</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','atenciones/view_atencion_psicologia.php')" class="nav-link">
                  <i class="nav-icon fas fa-brain"></i>
                  <p>Psicología</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">ORGANIZACIÓN DEL COLEGIO</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','año/view_año_escolar.php')" class="nav-link">
                  <i class="nav-icon fas fa-calendar-alt"></i>
                  <p>Años escolares</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','periodo/view_periodo.php')" class="nav-link">
                  <i class="nav-icon fas fa-calendar-week"></i>
                  <p>Periodos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','nivel_academico/view_nivel_academico.php')" class="nav-link">
                  <i class="nav-icon fas fa-layer-group"></i>
                  <p>Niveles</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','aulas/view_aulas.php')" class="nav-link">
                  <i class="nav-icon fas fa-school"></i>
                  <p>Grados</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','seccion/view_seccion.php')" class="nav-link">
                  <i class="nav-icon fas fa-th-large"></i>
                  <p>Secciones</p>
                </a>
              </li>
              <li class="nav-header menu-seccion">CONFIGURACIÓN</li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','usuario/view_usuario.php')" class="nav-link">
                  <i class="nav-icon fas fa-users-cog"></i>
                  <p>Usuarios</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','roles/view_roles.php')" class="nav-link">
                  <i class="nav-icon fas fa-sitemap"></i>
                  <p>Roles</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','especialidades/view_especialidades.php')" class="nav-link">
                  <i class="nav-icon fas fa-graduation-cap"></i>
                  <p>Especialidades</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_formas_pago.php')" class="nav-link">
                  <i class="nav-icon fas fa-university"></i>
                  <p>Formas de pago y cuentas</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_regional.php')" class="nav-link">
                  <i class="nav-icon fas fa-globe-americas"></i>
                  <p>Datos regionales</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_apariencia.php')" class="nav-link">
                  <i class="nav-icon fas fa-palette"></i>
                  <p>Apariencia</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_auditoria.php')" class="nav-link">
                  <i class="nav-icon fas fa-history"></i>
                  <p>Auditoría</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_respaldos.php')" class="nav-link">
                  <i class="nav-icon fas fa-database"></i>
                  <p>Respaldos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="https://drive.google.com/file/d/1k5VviNH7amB6DeXcakwoB3wJI031cgcJ/view?usp=drive_link" target="_blank" rel="noopener" class="nav-link">
                  <i class="nav-icon fas fa-film"></i>
                  <p>Video tutorial</p>
                </a>
              </li>
            <?php
            }
            ?>
            <?php if ($_SESSION['S_ROL'] == "CONTADOR") { ?>
              <li class="nav-header menu-seccion">CONTABILIDAD</li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-hand-holding-usd"></i>
                  <p>
                    Cobranzas
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','pago_pensiones/view_pago_pensiones.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Cobrar pensión</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','pensiones/view_pensiones.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Pensiones</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-coins"></i>
                  <p>
                    Ingresos y gastos
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','ingresos_y_egresos/view_ingresos.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Ingresos</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','ingresos_y_egresos/view_egresos.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Gastos</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','ingresos_y_egresos/view_indicadores.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Categorías</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','contabilidad/view_cajas_bancos.php')" class="nav-link">
                  <i class="nav-icon fas fa-university"></i>
                  <p>Cajas y bancos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','planilla/view_planilla.php')" class="nav-link">
                  <i class="nav-icon fas fa-file-invoice-dollar"></i>
                  <p>Planilla de personal</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','reportes/view_reportes_financieros.php')" class="nav-link">
                  <i class="nav-icon fas fa-chart-line"></i>
                  <p>Reportes financieros</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','reportes/view_reporte_estudiantes.php')" class="nav-link">
                  <i class="nav-icon fas fa-list-alt"></i>
                  <p>Reporte de estudiantes</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','configuracion/view_formas_pago.php')" class="nav-link">
                  <i class="nav-icon fas fa-credit-card"></i>
                  <p>Formas de pago y cuentas</p>
                </a>
              </li>
            <?php } ?>
            <?php if ($_SESSION['S_ROL'] == "ENFERMERA") { ?>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-folder-plus"></i>
                  <p>
                    Atenciones de salud
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','atenciones/view_atencion_enfermeria.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Enfermería</p>
                    </a>
                  </li>
                </ul>
              </li>
            <?php
            }
            ?>

            <?php if ($_SESSION['S_ROL'] == "PSICOLOGA") { ?>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-folder-plus"></i>
                  <p>
                    Atenciones de salud
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','atenciones/view_atencion_psicologia.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>
                        Psicología
                      </p>
                    </a>
                  </li>
                </ul>
              </li>
            <?php
            }
            ?>

            <?php if ($_SESSION['S_ROL'] == "AUXILIAR") { ?>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-clock "></i>
                  <p>
                    Horario y asistencia
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','asistencia/view_registro_asistencia.php')" class="nav-link">
                      <i class="nav-icon fas fa-table text-warning"></i>
                      <p>Registro de asistencia</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','asistencia/view_asistencia_reportes.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Reporte de asistencias
                      </p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','asistencia_docente/view_asistencia_docente.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Asistencia docente</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','horario/view_horario.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Horarios</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','aula_hora/view_aula_hora.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Horas de clase por aula</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-clipboard"></i>
                  <p>
                    Revisar tareas
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','tareas/view_tareas.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>
                        Tareas
                      </p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','tareas/view_registro_tareas.php')" class="nav-link">
                      <i class="nav-icon fas fa-th text-info"></i>
                      <p>Registro de tareas</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','reportes/view_reporte_estudiantes.php')" class="nav-link">
                  <i class="nav-icon fas fa-list-alt"></i>
                  <p>Reporte de estudiantes</p>
                </a>
              </li>
            <?php
            }
            ?>
            <?php if ($_SESSION['S_ROL'] == "ENFERMERA" || $_SESSION['S_ROL'] == "PSICOLOGA" || $_SESSION['S_ROL'] == "AUXILIAR" || $_SESSION['S_ROL'] == "CONTADOR") { ?>
              <!-- Personal administrativo: sus boletas de pago (Fase 8B) -->
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','planilla/view_mis_boletas.php')" class="nav-link">
                  <i class="nav-icon fas fa-money-check-alt"></i>
                  <p>Mis boletas de pago</p>
                </a>
              </li>
            <?php } ?>
            <?php if ($_SESSION['S_ROL'] == "DOCENTE") { ?>
              <li class="nav-item">
                <a href="#" onclick="cargar_contenido('contenido_principal','planilla/view_mis_boletas.php')" class="nav-link">
                  <i class="nav-icon fas fa-money-check-alt"></i>
                  <p>Mis boletas de pago</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-clipboard"></i>
                  <p>
                    Notas y tareas
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','notas/view_notas_profesor.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Notas de mis cursos</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','tareas/view_tareas_profesor.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Tareas de mis cursos</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','tareas/view_registro_tareas.php')" class="nav-link">
                      <i class="nav-icon fas fa-th text-info"></i>
                      <p>Registro de tareas</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','asistencia/view_registro_asistencia.php')" class="nav-link">
                      <i class="nav-icon fas fa-table text-warning"></i>
                      <p>Asistencia de mis aulas</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-file"></i>
                  <p>
                    Exámenes
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','examen/view_examen_profesor.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Exámenes de mis cursos</p>
                    </a>
                  </li>
                </ul>
              </li>
            <?php
            }
            ?>
            <?php if ($_SESSION['S_ROL'] == "ESTUDIANTE") { ?>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-clipboard"></i>
                  <p>
                    Notas y tareas
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','notas/view_notas_estudiante.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Mis notas</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','tareas/view_tareas_estudiante.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Mis tareas</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-clock "></i>
                  <p>
                    Horario y asistencia
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','asistencia/view_asistencia_reporte_estudiante.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Mi asistencia</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','horario/view_horario_edtudiante.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Mi horario</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon fas fa-dollar-sign"></i>
                  <p>
                    Pagos
                    <i class="right fas fa-angle-left"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">

                  <li class="nav-item">
                    <a onclick="cargar_contenido('contenido_principal','pago_pensiones/view_pago_pensiones_estudiante.php')" class="nav-link">
                      <i class="nav-icon far fa-circle"></i>
                      <p>Mis pagos</p>
                    </a>
                  </li>

                </ul>
              </li>
            <?php
            }
            ?>
          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>
    <input type="text" id="txtprincipalid" value="<?php echo $_SESSION['S_ID']; ?>" hidden>
    <input type="text" id="txtprincipalusu" value="<?php echo $_SESSION['S_USU']; ?>" hidden>
    <input type="text" id="txtprincipalrol" value="<?php echo $_SESSION['S_ROL']; ?>" hidden>


    <div class="content-wrapper" id="contenido_principal">


      <!-- Content Wrapper. Contains page content -->

      <!-- Content Header (Page header) -->
      <?php if (false) { // cada rol tiene su panel de inicio con su propio encabezado ?>
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0"><i class="fas fa-home"></i>
                <b>BIENVENIDOS AL SISTEMA</b>
              </h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">MENÚ</a></li>
                <li class="breadcrumb-item active">MENÚ PRINCIPAL</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <?php } ?>
      <!-- /.content-header -->
      <?php if ($_SESSION['S_ROL'] == "ADMINISTRADOR") { ?>
        <!-- Panel de inicio del administrador (Fase 12) -->
        <?php include __DIR__ . '/inicio/panel_admin.php'; ?>
        <!-- /.content -->
    </div>

    <div class="modal fade" id="modal_ver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header" style="background-color:#fff; text-align:center; display: flex; justify-content: center; align-items: center;">
            <h5 id="lb_titulo_datos2" style="color:black; margin-bottom: 0; width: 100%; text-align:center;"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 10px;">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12 form-group">
                <label for="">Descripción<b style="color:red">(*)</b>:</label>
                <textarea class="form-control" id="txt_descripcion_ver" rows="3" style="resize:none" readonly></textarea>
              </div>
              <div class="col-12" align="center" style="border: 2px solid black; padding: 10px; display: inline-block; max-width: 100%; box-sizing: border-box;">
                <img id="preview4" src="#" alt="Vista previa" style="max-width: 100%; height: auto; display: block;">
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
          <div class="modal-header" style="background-color:#1FA0E0;">
            <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR DATOS DE LA INSTITUCIÒN</b></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12 form-group" style="color:red">
                <h6><b>Campos Obligatorios (*)</b></h6>
              </div><br>
              <div class="col-6 form-group">
                <input type="text" id="txt_id_empresa" hidden>
                <label for="">Nombre(*):</label>
                <input type="text" class="form-control" id="txt_nombre" maxlenght="8">
              </div>
              <div class="col-6 form-group">
                <label for="">Email(*):</label>
                <input type="text" class="form-control" id="txt_email">
              </div>
              <div class="col-6 form-group">
                <label for="">Código(*):</label>
                <input type="text" class="form-control" id="txt_codigo">
              </div>
              <div class="col-6 form-group">
                <label for="">Teléfono / Celular(*):</label>
                <input type="text" class="form-control" id="txt_telefono" maxlenght="9" onkeypress="return soloNumeros(event)">
              </div>
              <div class="col-12 form-group">
                <label for="">Dirección(*):</label>
                <input type="text" class="form-control" id="txt_direccion">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
            <button type="button" class="btn btn-success" onclick="Modificar_Empleado()"><i class="fas fa-check"></i> Modificar</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal_editar_foto" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header" style="background-color:#1FA0E0;">
            <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR FOTO DE LA INSTITUCIÓN: </b><label for="" id="lb_empresa"></label></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12">
                <input type="text" id="fotoactual" hidden>
                <input type="text" id="txt_idempresa_foto" hidden>
                <label for="checkboxSuccess2" style="align:justify;color:red">
                  OJO: Una vez cambiado el logo, tambien se cambiara el logo en los reportes y ticket.
                </label>
                <label for="">Subir Foto:</label>
                <input class="form-control" type="file" id="txt_foto">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
            <button type="button" class="btn btn-success" onclick="Modificar_Foto_Empresa()"><i class="fas fa-check"></i> Modificar</button>
          </div>
        </div>
      </div>
    </div>


    <!-- /.content-wrapper -->
  <?php
      }
  ?>
  <?php if ($_SESSION['S_ROL'] == "CONTADOR") { ?>
    <?php $SOLO_CABECERA = true; include __DIR__ . '/inicio/panel_rol.php'; ?>
    <!-- Panel del contador (Fase 5) -->
    <div class="content">
      <div class="container-fluid"><br>
        <div class="row">
          <div class="col-lg-2 col-6"><div class="small-box bg-success"><div class="inner"><h4 id="pc_ingresos_hoy">-</h4><p>Ingresos de hoy</p></div><div class="icon"><i class="fas fa-arrow-down"></i></div></div></div>
          <div class="col-lg-2 col-6"><div class="small-box bg-danger"><div class="inner"><h4 id="pc_egresos_hoy">-</h4><p>Egresos de hoy</p></div><div class="icon"><i class="fas fa-arrow-up"></i></div></div></div>
          <div class="col-lg-2 col-6"><div class="small-box bg-info"><div class="inner"><h4 id="pc_ingresos_mes">-</h4><p>Ingresos del mes</p></div><div class="icon"><i class="fas fa-calendar-plus"></i></div></div></div>
          <div class="col-lg-2 col-6"><div class="small-box bg-warning"><div class="inner"><h4 id="pc_egresos_mes">-</h4><p>Egresos del mes</p></div><div class="icon"><i class="fas fa-calendar-minus"></i></div></div></div>
          <div class="col-lg-2 col-6"><div class="small-box bg-primary"><div class="inner"><h4 id="pc_utilidad_mes">-</h4><p>Resultado del mes</p></div><div class="icon"><i class="fas fa-balance-scale"></i></div></div></div>
          <div class="col-lg-2 col-6"><div class="small-box bg-secondary"><div class="inner"><h4 id="pc_recibos_hoy">-</h4><p>Recibos emitidos hoy</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div></div>
        </div>
        <div class="row">
          <div class="col-lg-2 col-6"><div class="small-box bg-danger"><div class="inner"><h4 id="pc_morosidad">-</h4><p>Pensiones vencidas por cobrar</p></div><div class="icon"><i class="fas fa-user-clock"></i></div></div></div>
          <div class="col-lg-2 col-6"><div class="small-box bg-warning"><div class="inner"><h4 id="pc_alumnos_morosos">-</h4><p>Alumnos con deuda</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
          <div class="col-lg-8 col-12">
            <div class="card mb-3">
              <div class="card-header py-2"><b>Ingresos y egresos del año</b>
                <a href="#" class="float-right" onclick="cargar_contenido('contenido_principal','reportes/view_reportes_financieros.php');return false;">Ver reportes financieros <i class="fas fa-arrow-circle-right"></i></a></div>
              <div class="card-body py-2" style="height:170px"><canvas id="pc_grafico"></canvas></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-university"></i> <b>Saldos de cajas y cuentas</b></h3>
            <a href="#" class="float-right" onclick="cargar_contenido('contenido_principal','contabilidad/view_cajas_bancos.php');return false;">Ir a Cajas y bancos <i class="fas fa-arrow-circle-right"></i></a></div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>Caja / cuenta</th><th>Tipo</th><th class="text-right">Entradas hoy</th><th class="text-right">Salidas hoy</th><th class="text-right">Saldo</th><th>Último cierre</th></tr></thead>
              <tbody id="pc_cuentas"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div><!-- cierra el área de contenido, como en los demás roles (el pie de página quedaba dentro) -->
  <?php } ?>
  <?php if ($_SESSION['S_ROL'] == "ENFERMERA" || $_SESSION['S_ROL'] == "PSICOLOGA" || $_SESSION['S_ROL'] == "AUXILIAR") { ?>

    <!-- Main content -->

    
    <!-- Panel de inicio del rol (Fase 13) -->
    <?php include __DIR__ . '/inicio/panel_rol.php'; ?>



    <!-- /.content -->
  </div>
  <div class="modal fade" id="modal_ver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#fff; text-align:center; display: flex; justify-content: center; align-items: center;">
          <h5 class="" id="lb_titulo_datos2" style="color:black; margin-bottom: 0; width: 100%; text-align:center;">
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 10px;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12 form-group">
              <label for="">Descripción<b style="color:red">(*)</b>:</label>
              <textarea class="form-control" id="txt_descripcion_ver" rows="3" style="resize:none" readonly></textarea>
            </div>
            <div class="col-12" align="center" style="border: 2px solid black; padding: 10px; display: inline-block; max-width: 100%; box-sizing: border-box;">
              <img id="preview4" src="#" alt="Vista previa" style="max-width: 100%; height: auto; display: block;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- /.content-wrapper -->
<?php
  }
?>
<?php if ($_SESSION['S_ROL'] == "DOCENTE") { ?>

  <!-- Main content -->

  
    <!-- Panel de inicio del rol (Fase 13) -->
    <?php include __DIR__ . '/inicio/panel_rol.php'; ?>


  <div class="content" hidden>
    <div class="container-fluid">
      <div class="row">
        <!-- /.col-md-6 -->
        <div class="col-lg-12">
          <div class="card-primary"><br>
            <div class="card-header">
              <h5 class="m-0" style="font-family:cooper;text-align:center">
                <i class="fas fa-bullhorn"></i><b> COMUNICADOS</b>
              </h5>
            </div>
            <div class="table-responsive" style="text-align:center">
              <div class="card-body">
                <table id="tabla_comunicados_listar" class="table table-striped table-bordered" style="width:100%">
                  <thead style="background-color:#0A5D86;color:#FFFFFF;">
                    <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Tipo</th>
                      <th style="text-align:center">Grado</th>
                      <th style="text-align:center">Título</th>
                      <th style="text-align:center">Descripción</th>
                      <th style="text-align:center">Vista</th>
                      <th style="text-align:center">Estado</th>
                    </tr>
                  </thead>
                </table>
              </div>
            </div>
          </div>
          <!-- Imagen centrada -->
          <img src="../img/logo.jpeg" style="display:block; margin: 20px auto;" width="auto"><br>
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
  </div>


  <!-- /.content -->
  <div class="modal fade" id="modal_ver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#fff; text-align:center; display: flex; justify-content: center; align-items: center;">
          <h5 class="" id="lb_titulo_datos2" style="color:black; margin-bottom: 0; width: 100%; text-align:center;">
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 10px;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12 form-group">
              <label for="">Descripción<b style="color:red">(*)</b>:</label>
              <textarea class="form-control" id="txt_descripcion_ver" rows="3" style="resize:none" readonly></textarea>
            </div>
            <div class="col-12" align="center" style="border: 2px solid black; padding: 10px; display: inline-block; max-width: 100%; box-sizing: border-box;">
              <img id="preview4" src="#" alt="Vista previa" style="max-width: 100%; height: auto; display: block;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- /.content -->
  </div>

  <div class="modal fade" id="modal_editar_foto_docente" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#1FA0E0;">
          <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR FOTO DEL DOCENTE: </b><label for="" id="lb_docente"></label></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12">
              <input type="text" id="fotoactualdocente" hidden>
              <input type="text" id="txt_iddocente_foto" hidden>
              <label for="checkboxSuccess2" style="align:justify;color:red">
                OJO: Usted puede cambiar su foto de perfil las veces que desee.
              </label>
              <label for="">Subir Foto:</label>
              <input class="form-control" type="file" id="txt_foto_docente">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
          <button type="button" class="btn btn-success" onclick="Modificar_Foto_Docente()"><i class="fas fa-check"></i> Modificar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- /.content-wrapper -->
<?php
}

?>

<?php if ($_SESSION['S_ROL'] == "ESTUDIANTE") { ?>

  <!-- Main content -->



  
    <!-- Panel de inicio del rol (Fase 13) -->
    <?php include __DIR__ . '/inicio/panel_rol.php'; ?>


  <div class="content" hidden>
    <div class="container-fluid">
      <div class="row">
        <!-- /.col-md-6 -->
        <div class="col-lg-12">
          <div class="card-primary"><br>
            <div class="card-header">
              <h5 class="m-0" style="font-family:cooper;text-align:center">
                <i class="fas fa-bullhorn"></i><b> COMUNICADOS</b>
              </h5>
            </div>
            <div class="table-responsive" style="text-align:center">
              <div class="card-body">
                <table id="tabla_comunicados_listar" class="table table-striped table-bordered" style="width:100%">
                  <thead style="background-color:#0A5D86;color:#FFFFFF;">
                    <tr>
                      <th style="text-align:center">Nro.</th>
                      <th style="text-align:center">Tipo</th>
                      <th style="text-align:center">Grado</th>
                      <th style="text-align:center">Título</th>
                      <th style="text-align:center">Descripción</th>
                      <th style="text-align:center">Vista</th>
                      <th style="text-align:center">Estado</th>
                    </tr>
                  </thead>
                </table>
              </div>
            </div>
          </div>
          <!-- Imagen centrada -->
          <img src="../img/logo.jpeg" style="display:block; margin: 20px auto;" width="auto"><br>
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
  </div>


  <!-- /.content -->
  <div class="modal fade" id="modal_ver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#fff; text-align:center; display: flex; justify-content: center; align-items: center;">
          <h5 class="" id="lb_titulo_datos2" style="color:black; margin-bottom: 0; width: 100%; text-align:center;">
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 10px;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12 form-group">
              <label for="">Descripción<b style="color:red">(*)</b>:</label>
              <textarea class="form-control" id="txt_descripcion_ver" rows="3" style="resize:none" readonly></textarea>
            </div>
            <div class="col-12" align="center" style="border: 2px solid black; padding: 10px; display: inline-block; max-width: 100%; box-sizing: border-box;">
              <img id="preview4" src="#" alt="Vista previa" style="max-width: 100%; height: auto; display: block;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- /.content -->
  </div>

  <div class="modal fade" id="modal_editar_foto_estudiante" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#1FA0E0;">
          <h5 class="modal-title" id="exampleModalLabel" style="color:white; text-align:center"><b>EDITAR FOTO DEL ESTUDIANTE: </b><label for="" id="lb_estudiante"></label></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12">
              <input type="text" id="fotoactualestudiante" hidden>
              <input type="text" id="txt_idestudiante_foto" hidden>
              <label for="checkboxSuccess2" style="align:justify;color:red">
                OJO: Usted puede cambiar su foto de perfil las veces que desee.
              </label>
              <label for="">Subir Foto:</label>
              <input class="form-control" type="file" id="txt_foto_estudiante">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times ml-1"></i> Cerrar</button>
          <button type="button" class="btn btn-success" onclick="Modificar_Foto_Estudiante()"><i class="fas fa-check"></i> Modificar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- /.content-wrapper -->
<?php
}

?>
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
  <div class="p-3">
    <h5>Title</h5>
    <p>Sidebar content</p>
  </div>
</aside>
<!-- /.control-sidebar -->

<!-- Main Footer -->
<footer class="main-footer">
  <!-- To the right -->
  <div class="float-right d-none d-sm-inline">
    <em>Versión 1.0.0</em>
  </div>
  <!-- Default to the left -->
  <strong>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($TEMA['nombre'], ENT_QUOTES, 'UTF-8'); ?> · <a href="https://www.facebook.com/jerzhitho.cm/" target="_blank"><em>DESARROLLADO POR JCM (Click)</em></a></strong>
</footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->



<script>

  // Panel de inicio del contador
  function Panel_Contable() {
    $.ajax({ url: "../controller/contabilidad/controlador_panel.php", type: 'POST' }).done(function(r) {
      $("#pc_ingresos_hoy").text(formatoMoneda(r.ingresos_hoy));
      $("#pc_egresos_hoy").text(formatoMoneda(r.egresos_hoy));
      $("#pc_ingresos_mes").text(formatoMoneda(r.ingresos_mes));
      $("#pc_egresos_mes").text(formatoMoneda(r.egresos_mes));
      $("#pc_utilidad_mes").text(formatoMoneda(r.ingresos_mes - r.egresos_mes));
      $("#pc_recibos_hoy").text(r.recibos_hoy);
      $("#pc_morosidad").text(formatoMoneda(r.morosidad));
      $("#pc_alumnos_morosos").text(r.alumnos_morosos);
      let filas = "";
      r.cuentas.forEach(function(c) {
        filas += "<tr><td><b>" + $('<div>').text(c.nombre).html() + "</b></td><td>" + c.tipo + "</td><td class='text-right text-success'>" + formatoMoneda(c.entradas_hoy)
               + "</td><td class='text-right text-danger'>" + formatoMoneda(c.salidas_hoy) + "</td><td class='text-right'><b>" + formatoMoneda(c.saldo)
               + "</b></td><td>" + (c.ultimo_cierre || '<span class="text-muted">sin cierres</span>') + "</td></tr>";
      });
      $("#pc_cuentas").html(filas || "<tr><td colspan='6' class='text-center text-muted'>No hay cajas ni cuentas activas</td></tr>");
    });
    $.ajax({ url: "../controller/reportes/controlador_mensual.php", type: 'POST', data: { anio: new Date().getFullYear() } }).done(function(r) {
      new Chart(document.getElementById('pc_grafico'), {
        type: 'bar',
        data: {
          labels: r.data.map(function(m) { return m.mes_nombre.substr(0, 3); }),
          datasets: [
            { type: 'line', label: 'Utilidad', data: r.data.map(function(m) { return m.utilidad; }), borderColor: '#0A5D86', backgroundColor: 'transparent', lineTension: 0, pointRadius: 2 },
            { label: 'Ingresos', data: r.data.map(function(m) { return m.ingresos; }), backgroundColor: 'rgba(40,167,69,0.75)' },
            { label: 'Egresos', data: r.data.map(function(m) { return m.egresos; }), backgroundColor: 'rgba(220,53,69,0.75)' }
          ]
        },
        options: {
          maintainAspectRatio: false, legend: { position: 'right' },
          tooltips: { mode: 'index', intersect: false, callbacks: { label: function(it, d) { return d.datasets[it.datasetIndex].label + ': ' + formatoMoneda(it.yLabel); } } },
          scales: { yAxes: [{ ticks: { beginAtZero: true, callback: function(v) { return formatoMoneda(v); } } }] }
        }
      });
    });
  }
  function cargar_contenido(id, vista) {
    $("#" + id).load(vista, function() {
      aplicarRegional(this);
    });
  }
  var idioma_espanol = {
    select: {
      rows: "%d fila seleccionada"
    },
    "sProcessing": "Procesando...",
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sEmptyTable": "Ning&uacute;n dato disponible en esta tabla",
    "sInfo": "Registros del (_START_ al _END_) total de _TOTAL_ registros",
    "sInfoEmpty": "Registros del (0 al 0) total de 0 registros",
    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
    "sInfoPostFix": "",
    "sSearch": "Buscar:",
    "sUrl": "",
    "sInfoThousands": ",",
    "sLoadingRecords": "Cargando...",
    "oPaginate": {
      "sFirst": "Primero",
      "sLast": "Último",
      "sNext": "Siguiente",
      "sPrevious": "Atras"
    },
    "oAria": {
      "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
      "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    }
  }

  function sololetras(e) {
    key = e.keyCode || e.which;

    teclado = String.fromCharCode(key).toLowerCase();

    letras = "qwertyuiopasdfghjklñzxcvbnmáéíóúÁÉÍÓÚ ";

    especiales = "8-37-38-46-164";

    teclado_especial = false;

    for (var i in especiales) {
      if (key == especiales[i]) {
        teclado_especial = true;
        break;
      }
    }

    if (letras.indexOf(teclado) == -1 && !teclado_especial) {
      return false;
    }
  }


  function soloNumeros(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla == 8) {
      return true;
    }
    // Patron de entrada, en este caso solo acepta numeros
    patron = /[0-9]/;
    tecla_final = String.fromCharCode(tecla);
    return patron.test(tecla_final);
  }



  ///////VALIDAR EMAIL
  function validar_email(email) {
    var regex = /^[^\s@]+@[^\s@]+\.[^\s@.]{2,}$/;
    return regex.test(email) ? true : false;
  }
</script>
<!-- jQuery -->
<script src="../plantilla/plugins//jquery/jquery.min.js"></script>
<script>
  // Todas las peticiones AJAX envían el token CSRF; si la sesión expiró se vuelve al login
  $.ajaxSetup({
    headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
  });
  window.__avisoGlobal = true;   // js/cuenta.js: los 400/403 los muestra este aviso
  $(document).ajaxError(function(event, xhr) {
    if (xhr.status === 401) {
      window.location.href = '../index.php';
    } else if (xhr.status === 403 && typeof Swal !== 'undefined') {
      var r = xhr.responseJSON || {};
      Swal.fire('Acceso denegado', r.mensaje || 'No tiene permiso para esta acción', 'error');
    } else if (xhr.status === 400 && typeof Swal !== 'undefined') {
      var r400 = xhr.responseJSON || {};
      Swal.fire('Dato inválido', r400.mensaje || 'Revise los datos ingresados', 'warning');
    } else if (xhr.responseJSON && xhr.responseJSON.mensaje && typeof Swal !== 'undefined') {
      // otros errores con mensaje del servidor (404, 409, 500): mostrarlos en vez de no hacer nada
      Swal.fire('Aviso', xhr.responseJSON.mensaje, 'error');
    }
  });
  // País, moneda y documento configurados (Configuración > Datos regionales)
  window.REGIONAL = <?php echo json_encode(config_regional_publica(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
</script>
<script src="../js/regional.js?rev=<?php echo time(); ?>"></script>
<script src="../js/formas_pago_comun.js?rev=<?php echo time(); ?>"></script>
<!-- Bootstrap 4 -->
<script src="../plantilla/plugins//bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../plantilla/dist/js/adminlte.min.js"></script>
<!-- Chart.js (gráficos de reportes y panel del contador) -->
<script src="../plantilla/plugins/chart.js/Chart.min.js"></script>



<script src="../js/console_comunicados.js?rev=<?php echo time(); ?>"></script>
<script src="../js/console_matriculas.js?rev=<?php echo time(); ?>"></script>
<script src="../js/console_usuario.js?rev=<?php echo time(); ?>"></script>
<script src="../js/console_tareas_profesor.js?rev=<?php echo time(); ?>"></script>
<script src="../js/panel_comun.js?rev=<?php echo time(); ?>"></script>
<?php if ($_SESSION['S_ROL'] == "ADMINISTRADOR") { ?><script src="../js/panel_admin.js?rev=<?php echo time(); ?>"></script><?php } ?>
<?php if ($_SESSION['S_ROL'] != "ADMINISTRADOR") { ?><script src="../js/panel_rol.js?rev=<?php echo time(); ?>"></script><?php } ?>
<script src="../js/console_tareas_estudiantes.js?rev=<?php echo time(); ?>"></script>
<script src="../js/console_empresa.js?rev=<?php echo time(); ?>"></script>
<script src="../js/console_examen_profesor.js?rev=<?php echo time(); ?>"></script>
<script src="../utilitario/DataTables/datatables.min.js"></script>
<!-- Escapa los datos al mostrarlos (DataTables por defecto y textoSeguro() para el HTML armado a mano) -->
<script src="../js/texto_seguro.js?rev=<?php echo time(); ?>"></script>
<script src="../js/formulario.js?rev=<?php echo time(); ?>"></script>
<script src="../js/cuenta.js?rev=<?php echo time(); ?>"></script>
<script src="../js/menu.js?rev=<?php echo time(); ?>"></script>
<script src="../js/ficha.js?rev=<?php echo time(); ?>"></script>
<!-- Cambiar mi contraseña (todos los roles) -->
<div class="modal fade" id="modal_mi_clave" tabindex="-1" role="dialog" aria-labelledby="titulo_mi_clave" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1FA0E0;">
        <h5 class="modal-title" id="titulo_mi_clave" style="color:white"><b>Cambiar mi contraseña</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group"><label for="mc_actual">Contraseña actual</label><input type="password" id="mc_actual" class="form-control" autocomplete="current-password"></div>
        <div class="form-group"><label for="mc_nueva">Nueva contraseña</label><input type="password" id="mc_nueva" class="form-control" autocomplete="new-password">
          <small class="text-muted">Al menos 8 caracteres, distinta de su usuario.</small></div>
        <div class="form-group mb-0"><label for="mc_confirmar">Repita la nueva contraseña</label><input type="password" id="mc_confirmar" class="form-control" autocomplete="new-password"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="mc_guardar"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>
<script src="../js/tablas_mejoras.js?rev=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../js/console_usuario.js?rev=<?php echo time(); ?>"></script>


</body>

</html>
<!-- Incluye tu JavaScript personalizado -->
<script>
  $(document).ready(function() {
    aplicarRegional(document.body);
    listar_empresa();
    listar_comunicado_dash();
    <?php if ($_SESSION['S_ROL'] == "ESTUDIANTE") { ?>
    PanelRol.cargar();
    <?php } ?>
    <?php if ($_SESSION['S_ROL'] == "DOCENTE") { ?>
    PanelRol.cargar();
    <?php } ?>
    <?php if ($_SESSION['S_ROL'] == "CONTADOR") { ?>
    Panel_Contable();
    PanelRol.cargar();
    <?php } ?>
    <?php if ($_SESSION['S_ROL'] == "ADMINISTRADOR") { ?>
    PanelAdmin.cargar();
    <?php } ?>
    <?php if ($_SESSION['S_ROL'] == "AUXILIAR" || $_SESSION['S_ROL'] == "ENFERMERA" || $_SESSION['S_ROL'] == "PSICOLOGA") { ?>
    PanelRol.cargar();
    <?php } ?>
    // Maneja los eventos para los botones "Siguiente" y "Anterior"
    $('#btn_modal_siguiente').click(function() {
      mostrarSiguienteModal();
    });

    $('#btn_modal_anterior').click(function() {
      mostrarAnteriorModal();
    });

    // Evento para cerrar el modal y quitar el actual de la lista de activos
    $('#modal_ver').on('hidden.bs.modal', function() {
      // Remover el modal actual de la lista de activos
      if (comunicadosActivos.length > 0) {
        comunicadosActivos.splice(currentModalIndex, 1);
        if (comunicadosActivos.length > 0) {
          currentModalIndex = currentModalIndex % comunicadosActivos.length;
          mostrarSiguienteModal();
        }
      }
    });
  });
</script>