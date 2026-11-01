<?php
// Panel de inicio de docentes, estudiantes, auxiliar, enfermería y psicología (Fase 13, paso 4).
// Se incluye desde view/index.php; los datos los llena js/panel_rol.js (controller/panel/controlador_panel_rol.php).
if (!function_exists('tema_actual')) { http_response_code(404); exit; }   // no se abre suelto
$TEMA = $TEMA ?? tema_actual();
$rol = $_SESSION['S_ROL'];
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$nombre = mb_convert_case(mb_strtolower(html_entity_decode($_SESSION['S_NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
$foto_ses = html_entity_decode($_SESSION['S_FOTO'] ?? '', ENT_QUOTES, 'UTF-8');
// foto propia solo si el archivo existe (hay rutas por defecto que no existen, p. ej. "Fotos/admin.png")
$foto = $foto_ses !== '' && substr($foto_ses, -1) !== '/' && is_file(__DIR__ . '/../../' . $foto_ses) ? '../' . $foto_ses : '../img/blanco1.jpg';
$titulos = ['CONTADOR' => 'Contabilidad', 'DOCENTE' => 'Docente', 'ESTUDIANTE' => 'Estudiante', 'AUXILIAR' => 'Auxiliar de educación', 'ENFERMERA' => 'Enfermería', 'PSICOLOGA' => 'Psicología'];
// Accesos rápidos de cada rol: [vista o función JS, ícono, texto]
$accesos = [
    'DOCENTE' => [['asistencia/view_registro_asistencia.php', 'fa-clipboard-check', 'Tomar asistencia'], ['tareas/view_tareas_profesor.php', 'fa-tasks', 'Tareas'],
                  ['tareas/view_registro_tareas.php', 'fa-th', 'Registro de tareas'], ['notas/view_notas_profesor.php', 'fa-star-half-alt', 'Notas'],
                  ['examen/view_examen_profesor.php', 'fa-file-signature', 'Exámenes']],
    'ESTUDIANTE' => [['tareas/view_tareas_estudiante.php', 'fa-tasks', 'Mis tareas'], ['notas/view_notas_estudiante.php', 'fa-star-half-alt', 'Mis notas'],
                     ['horario/view_calendario.php', 'fa-clock', 'Mi horario'], ['asistencia/view_asistencia_reporte_estudiante.php', 'fa-calendar-check', 'Mi asistencia'],
                     ['pago_pensiones/view_pago_pensiones_estudiante.php', 'fa-receipt', 'Mis pagos']],
    'AUXILIAR' => [['asistencia/view_registro_asistencia.php', 'fa-clipboard-check', 'Registro de asistencia'], ['asistencia_docente/view_asistencia_docente.php', 'fa-user-check', 'Asistencia docente'],
                   ['horario/view_horario.php', 'fa-clock', 'Horarios'], ['tareas/view_registro_tareas.php', 'fa-th', 'Registro de tareas'],
                   ['reportes/view_reporte_estudiantes.php', 'fa-list-alt', 'Reporte de estudiantes']],
    'ENFERMERA' => [['atenciones/view_atencion_enfermeria.php', 'fa-notes-medical', 'Atenciones de enfermería'], ['planilla/view_mis_boletas.php', 'fa-money-check-alt', 'Mis boletas']],
    'PSICOLOGA' => [['atenciones/view_atencion_psicologia.php', 'fa-brain', 'Atenciones psicológicas'], ['planilla/view_mis_boletas.php', 'fa-money-check-alt', 'Mis boletas']],
    'CONTADOR' => [['pago_pensiones/view_pago_pensiones.php', 'fa-cash-register', 'Cobrar pensión'], ['ingresos_y_egresos/view_egresos.php', 'fa-file-invoice-dollar', 'Registrar gasto'],
                   ['contabilidad/view_cajas_bancos.php', 'fa-university', 'Cajas y bancos'], ['planilla/view_planilla.php', 'fa-users-cog', 'Planilla'],
                   ['reportes/view_reportes_financieros.php', 'fa-chart-line', 'Reportes']],
][$rol] ?? [];
$foto_js = ['DOCENTE' => 'editar_foto_docente()', 'ESTUDIANTE' => 'editar_foto_Estudiante()'][$rol] ?? '';
?>
<div class="content pi">
  <div class="container-fluid">

    <section class="pi-cabecera" aria-label="Bienvenida">
      <div class="pi-identidad">
        <img src="<?php echo $e($foto); ?>" alt="Su foto" class="pi-escudo pi-foto">
        <div>
          <h1 class="pi-colegio">Hola, <?php echo $e($nombre); ?></h1>
          <p class="pi-lema"><?php echo $e($titulos[$rol] ?? $rol); ?> · <?php echo $e($TEMA['nombre']); ?></p>
        </div>
      </div>
      <div class="pi-fecha">
        <span id="pi_fecha"></span>
        <span id="pi_periodo" class="pi-periodo"></span>
      </div>
      <nav class="pi-accesos" aria-label="Accesos rápidos">
        <?php foreach ($accesos as $a) { ?>
          <button type="button" onclick="cargar_contenido('contenido_principal','<?php echo $e($a[0]); ?>')"><i class="fas <?php echo $e($a[1]); ?>"></i> <?php echo $e($a[2]); ?></button>
        <?php } ?>
        <?php if ($foto_js) { ?>
          <button type="button" onclick="<?php echo $foto_js; ?>"><i class="fas fa-camera"></i> Mi foto</button>
        <?php } ?>
      </nav>
    </section>

    <section class="pi-anio" aria-label="Año escolar">
      <div class="pi-anio-titulo">
        <h2 id="pi_anio_nombre">Año escolar</h2>
        <span id="pi_anio_resumen" class="pi-tenue"></span>
      </div>
      <div class="pi-linea" id="pi_linea" role="img" aria-label="Avance del año escolar"></div>
    </section>
<?php if (!empty($SOLO_CABECERA)) { // contadora: debajo sigue su panel contable ?>
  </div>
</div>
<?php return; } ?>

    <?php if ($rol === 'DOCENTE' || $rol === 'ESTUDIANTE') { ?>
    <div class="row">
      <div class="col-lg-7">
        <section class="pi-tarjeta">
          <header><h2 id="pr_clases_titulo">Clases de hoy</h2><span id="pr_aula" class="pi-tenue"></span></header>
          <ul class="pr-clases" id="pr_clases"><li class="pi-tenue">Cargando…</li></ul>
        </section>
      </div>
      <div class="col-lg-5">
        <?php if ($rol === 'DOCENTE') { ?>
        <section class="pi-tarjeta">
          <header><h2>Asistencia de hoy</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'asistencia/view_registro_asistencia.php\');return false;">Registrar <i class="fas fa-arrow-right"></i></a>'; ?></header>
          <ul class="pi-pendientes" id="pr_aulas"><li class="pi-tenue">Cargando…</li></ul>
        </section>
        <?php } else { ?>
        <section class="pi-tarjeta">
          <header><h2>Mi asistencia del mes</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'asistencia/view_asistencia_reporte_estudiante.php\');return false;">Ver detalle <i class="fas fa-arrow-right"></i></a>'; ?></header>
          <div id="pr_asistencia"><p class="pi-tenue mb-0">Cargando…</p></div>
        </section>
        <?php } ?>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-7">
        <section class="pi-tarjeta">
          <?php if ($rol === 'DOCENTE') { ?>
            <header><h2>Tareas por calificar</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'tareas/view_registro_tareas.php\');return false;">Registro de tareas <i class="fas fa-arrow-right"></i></a>'; ?></header>
            <ul class="pi-pendientes" id="pr_tareas"><li class="pi-tenue">Cargando…</li></ul>
          <?php } else { ?>
            <header><h2>Tareas pendientes</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'tareas/view_tareas_estudiante.php\');return false;">Mis tareas <i class="fas fa-arrow-right"></i></a>'; ?></header>
            <ul class="pi-pendientes" id="pr_tareas"><li class="pi-tenue">Cargando…</li></ul>
          <?php } ?>
        </section>
      </div>
      <div class="col-lg-5">
        <section class="pi-tarjeta">
          <header><h2>Próximos exámenes</h2></header>
          <ul class="pi-pendientes" id="pr_examenes"><li class="pi-tenue">Cargando…</li></ul>
        </section>
        <?php if ($rol === 'ESTUDIANTE') { ?>
        <section class="pi-tarjeta">
          <header><h2>Últimas notas de tareas</h2></header>
          <ul class="pi-pendientes" id="pr_notas"><li class="pi-tenue">Cargando…</li></ul>
        </section>
        <section class="pi-tarjeta">
          <header><h2>Pensiones</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'pago_pensiones/view_pago_pensiones_estudiante.php\');return false;">Mis pagos <i class="fas fa-arrow-right"></i></a>'; ?></header>
          <ul class="pi-pendientes" id="pr_pensiones"><li class="pi-tenue">Cargando…</li></ul>
        </section>
        <?php } ?>
      </div>
    </div>
    <?php } elseif ($rol === 'AUXILIAR') { ?>
    <section class="pi-tarjeta">
      <header><h2>Asistencia de hoy por aula</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'asistencia/view_registro_asistencia.php\');return false;">Registro mensual <i class="fas fa-arrow-right"></i></a>'; ?></header>
      <div class="pr-aulas" id="pr_aulas_aux"><p class="pi-tenue mb-0">Cargando…</p></div>
    </section>
    <?php } else { ?>
    <div class="row">
      <div class="col-lg-4">
        <section class="pi-tarjeta">
          <header><h2>Atenciones</h2></header>
          <dl class="pi-caja"><div><dt>Hoy</dt><dd id="pr_salud_hoy">–</dd></div><div><dt>Este mes</dt><dd id="pr_salud_mes">–</dd></div></dl>
        </section>
      </div>
      <div class="col-lg-8">
        <section class="pi-tarjeta">
          <header><h2>Atenciones recientes</h2><?php echo '<a href="#" onclick="cargar_contenido(\'contenido_principal\',\'' . ($rol === 'ENFERMERA' ? 'atenciones/view_atencion_enfermeria.php' : 'atenciones/view_atencion_psicologia.php') . '\');return false;">Nueva atención <i class="fas fa-arrow-right"></i></a>'; ?></header>
          <ul class="pi-pendientes" id="pr_salud"><li class="pi-tenue">Cargando…</li></ul>
        </section>
      </div>
    </div>
    <?php } ?>

    <section class="pi-tarjeta" id="avisos">
      <header><h2>Comunicados</h2></header>
      <ul class="pi-comunicados" id="pi_comunicados"><li class="pi-tenue">Cargando…</li></ul>
    </section>

  </div>
</div>
