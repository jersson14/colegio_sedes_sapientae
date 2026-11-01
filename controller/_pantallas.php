<?php
// GENERADO por tests/herramientas/revisar_permisos.php --generar (no editar a mano).
// Pantallas del menú con los controladores que usan, y las pantallas e inicio de fábrica de cada rol.
return array (
  'pantallas' => 
  array (
    'alumnos/view_alumnos.php' => 
    array (
      'titulo' => 'Estudiantes',
      'seccion' => 'ESTUDIANTES Y MATRÍCULA',
      'controladores' => 
      array (
        0 => 'controller/alumnos/controlador_buscar_padre.php',
        1 => 'controller/alumnos/controlador_cambiar_acceso.php',
        2 => 'controller/alumnos/controlador_eliminar_alumnos.php',
        3 => 'controller/alumnos/controlador_ficha.php',
        4 => 'controller/alumnos/controlador_importar.php',
        5 => 'controller/alumnos/controlador_listar_alumnos.php',
        6 => 'controller/alumnos/controlador_modificar_alumno.php',
        7 => 'controller/alumnos/controlador_registrar_alumno.php',
        8 => 'controller/ayuda/controlador_ayuda.php',
      ),
    ),
    'matricula/view_matricula.php' => 
    array (
      'titulo' => 'Matrícula',
      'seccion' => 'ESTUDIANTES Y MATRÍCULA',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        1 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        2 => 'controller/aulas/controlador_cargar_select_nivel.php',
        3 => 'controller/aulas/controlador_eliminar_rol.php',
        4 => 'controller/ayuda/controlador_ayuda.php',
        5 => 'controller/matricula/controlador_beca_guardar.php',
        6 => 'controller/matricula/controlador_beca_traer.php',
        7 => 'controller/matricula/controlador_cargar_select_año.php',
        8 => 'controller/matricula/controlador_cargar_select_estudiante.php',
        9 => 'controller/matricula/controlador_contexto.php',
        10 => 'controller/matricula/controlador_eliminar_matricula.php',
        11 => 'controller/matricula/controlador_listar_matriculas.php',
        12 => 'controller/matricula/controlador_listar_matriculas_filtro.php',
        13 => 'controller/matricula/controlador_modificar_matrícula.php',
        14 => 'controller/matricula/controlador_registro_matriculas.php',
        15 => 'controller/matricula/controlador_traernivel.php',
        16 => 'controller/matricula/controlador_traertipo.php',
        17 => 'controller/notas/controlador_cargar_select_grado_profesor.php',
        18 => 'controller/pago_pension/controlador_cargar_formas_pago.php',
        19 => 'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php',
        20 => 'view/MPDF/REPORTE/cedula.php',
      ),
    ),
    'reportes/view_reporte_estudiantes.php' => 
    array (
      'titulo' => 'Reporte de estudiantes',
      'seccion' => 'ESTUDIANTES Y MATRÍCULA',
      'controladores' => 
      array (
        0 => 'controller/reportes/controlador_reporte_estudiantes.php',
      ),
    ),
    'notas/view_notas.php' => 
    array (
      'titulo' => 'Notas',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        1 => 'controller/aulas/controlador_cargar_select_nivel.php',
        2 => 'controller/matricula/controlador_cargar_select_año.php',
        3 => 'controller/notas/controlador_cargar_periodos.php',
        4 => 'controller/notas/controlador_cargar_periodos_cargados.php',
        5 => 'controller/notas/controlador_editar_notas.php',
        6 => 'controller/notas/controlador_editar_notas_padre.php',
        7 => 'controller/notas/controlador_listar_criterios_notas.php',
        8 => 'controller/notas/controlador_listar_criterios_notas_mostrar.php',
        9 => 'controller/notas/controlador_listar_criterios_notas_mostrar_padres.php',
        10 => 'controller/notas/controlador_listar_matriculas_filtro.php',
        11 => 'controller/notas/controlador_registro_notas.php',
        12 => 'controller/notas/controlador_registro_notas_padres.php',
      ),
    ),
    'componentes/view_componentes.php' => 
    array (
      'titulo' => 'Criterios de evaluación',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        1 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        2 => 'controller/aulas/controlador_cargar_select_nivel.php',
        3 => 'controller/componentes/controlador_cargar_curso_id_detalle.php',
        4 => 'controller/componentes/controlador_eliminar_componente_curso_unico.php',
        5 => 'controller/componentes/controlador_eliminar_componentes.php',
        6 => 'controller/componentes/controlador_listar_componenetes.php',
        7 => 'controller/componentes/controlador_listar_componenetes_filtro.php',
        8 => 'controller/componentes/controlador_listar_componentes_curso.php',
        9 => 'controller/componentes/controlador_listar_tabla_componentes_curso.php',
        10 => 'controller/componentes/controlador_modificar_componentes.php',
        11 => 'controller/componentes/controlador_registro_componentes.php',
        12 => 'controller/matricula/controlador_traernivel.php',
        13 => 'controller/notas/controlador_cargar_select_grado_profesor.php',
        14 => 'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php',
      ),
    ),
    'boletas/view_boletas.php' => 
    array (
      'titulo' => 'Libretas de notas',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/boletas/controlador_libreta.php',
        2 => 'view/MPDF/REPORTE/libreta.php',
      ),
    ),
    'examen/view_examen.php' => 
    array (
      'titulo' => 'Exámenes',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignatura_docente/controlador_cargar_docente.php',
        1 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        2 => 'controller/aulas/controlador_cargar_select_nivel.php',
        3 => 'controller/examenes/controlador_eliminar_examenes.php',
        4 => 'controller/examenes/controlador_listar_alumnos_examen.php',
        5 => 'controller/examenes/controlador_listar_examenes.php',
        6 => 'controller/examenes/controlador_listar_examenes_filtro.php',
        7 => 'controller/examenes/controlador_listar_examenes_profesor_solo.php',
        8 => 'controller/examenes/controlador_modificar_estado_examen.php',
        9 => 'controller/examenes/controlador_modificar_examen.php',
        10 => 'controller/examenes/controlador_registro_examenes.php',
        11 => 'controller/tareas/controlador_archivos_tarea.php',
        12 => 'controller/tareas/controlador_cargar_select_cursos_docente.php',
        13 => 'controller/tareas/controlador_listar_tabla_envio_tareas.php',
        14 => 'controller/tareas/controlador_registro_calificacion.php',
      ),
    ),
    'aula_virtual/view_aula_virtual.php' => 
    array (
      'titulo' => 'Aula virtual',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/aula_virtual/controlador_aula_virtual.php',
        1 => 'controller/ayuda/controlador_ayuda.php',
        2 => 'controller/tareas/controlador_archivos_tarea.php',
        3 => 'controller/tareas/controlador_registro_tareas_estudiante.php',
      ),
    ),
    'tareas/view_tareas.php' => 
    array (
      'titulo' => 'Tareas',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignatura_docente/controlador_cargar_docente.php',
        1 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        2 => 'controller/aulas/controlador_cargar_select_nivel.php',
        3 => 'controller/examenes/controlador_listar_alumnos_examen.php',
        4 => 'controller/tareas/controlador_archivos_tarea.php',
        5 => 'controller/tareas/controlador_cargar_select_cursos_docente.php',
        6 => 'controller/tareas/controlador_eliminar_tarea.php',
        7 => 'controller/tareas/controlador_listar_tabla_envio_tareas.php',
        8 => 'controller/tareas/controlador_listar_tareas.php',
        9 => 'controller/tareas/controlador_listar_tareas_filtro.php',
        10 => 'controller/tareas/controlador_listar_tareas_profesor_solo.php',
        11 => 'controller/tareas/controlador_modificar_estado_tarea.php',
        12 => 'controller/tareas/controlador_modificar_tareas.php',
        13 => 'controller/tareas/controlador_registro_calificacion.php',
        14 => 'controller/tareas/controlador_registro_tareas.php',
      ),
    ),
    'tareas/view_registro_tareas.php' => 
    array (
      'titulo' => 'Registro de tareas',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/comunicados/controlador_ver_comunicado.php',
        2 => 'controller/panel/controlador_panel_rol.php',
        3 => 'controller/registro_tareas/controlador_registro.php',
        4 => 'controller/tareas/controlador_archivos_tarea.php',
        5 => 'controller/tareas/controlador_registro_calificacion.php',
      ),
    ),
    'asignaturas/view_asignatura.php' => 
    array (
      'titulo' => 'Asignaturas',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_eliminar_asignatura.php',
        1 => 'controller/asignaturas/controlador_listar_asignaturas.php',
        2 => 'controller/asignaturas/controlador_listar_asignaturas_filtro.php',
        3 => 'controller/asignaturas/controlador_modificar_asignaturas.php',
        4 => 'controller/asignaturas/controlador_registro_asignaturas.php',
        5 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        6 => 'controller/aulas/controlador_cargar_select_nivel.php',
        7 => 'controller/aulas/controlador_eliminar_rol.php',
        8 => 'view/MPDF/REPORTE/cedula.php',
      ),
    ),
    'docente_asignatura/view_docenteasignatura.php' => 
    array (
      'titulo' => 'Cursos de cada docente',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignatura_docente/controlador_asig_docente.php',
        1 => 'controller/asignatura_docente/controlador_cargar_docente.php',
        2 => 'controller/asignatura_docente/controlador_cargar_select_asignatura.php',
        3 => 'controller/asignatura_docente/controlador_detalle_asignatura_docente.php',
        4 => 'controller/asignatura_docente/controlador_eliminar_asignatura_docente.php',
        5 => 'controller/asignatura_docente/controlador_eliminar_asignatura_unica.php',
        6 => 'controller/asignatura_docente/controlador_listar_asignatura_docentes.php',
        7 => 'controller/asignatura_docente/controlador_listar_asignatura_docentes_filtro.php',
        8 => 'controller/asignatura_docente/controlador_listar_tabla_detalle_curso.php',
        9 => 'controller/asignatura_docente/controlador_modificar_asignatura_docente_unico.php',
        10 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        11 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        12 => 'controller/aulas/controlador_cargar_select_nivel.php',
        13 => 'controller/aulas/controlador_registro_aulas.php',
        14 => 'controller/matricula/controlador_cargar_select_año.php',
      ),
    ),
    'horario/view_horario.php' => 
    array (
      'titulo' => 'Horarios',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        1 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        2 => 'controller/aulas/controlador_cargar_select_nivel.php',
        3 => 'controller/ayuda/controlador_ayuda.php',
        4 => 'controller/horarios/controlador_cargar_curso_id_detalle.php',
        5 => 'controller/horarios/controlador_cargar_select_horas.php',
        6 => 'controller/horarios/controlador_eliminar_horario.php',
        7 => 'controller/horarios/controlador_eliminar_horario_unico.php',
        8 => 'controller/horarios/controlador_listar_horarios.php',
        9 => 'controller/horarios/controlador_listar_horarios_editar.php',
        10 => 'controller/horarios/controlador_listar_horarios_filtro.php',
        11 => 'controller/horarios/controlador_listar_horarios_id.php',
        12 => 'controller/horarios/controlador_modificar_horarios.php',
        13 => 'controller/horarios/controlador_registro_horario_aula.php',
        14 => 'controller/matricula/controlador_cargar_select_año.php',
        15 => 'controller/matricula/controlador_traernivel.php',
        16 => 'controller/notas/controlador_cargar_select_grado_profesor.php',
        17 => 'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php',
        18 => 'view/MPDF/REPORTE/horario.php',
      ),
    ),
    'horario/view_calendario.php' => 
    array (
      'titulo' => 'Calendario de horarios',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/horarios/controlador_calendario.php',
      ),
    ),
    'aula_hora/view_aula_hora.php' => 
    array (
      'titulo' => 'Horas de clase por aula',
      'seccion' => 'ACADÉMICO',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        1 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        2 => 'controller/aula_horas/controlador_eliminar_aula_horas.php',
        3 => 'controller/aula_horas/controlador_eliminar_horas_curso_unico.php',
        4 => 'controller/aula_horas/controlador_listar_aula_horas.php',
        5 => 'controller/aula_horas/controlador_listar_aula_horas_filtro.php',
        6 => 'controller/aula_horas/controlador_listar_hora_aula_id.php',
        7 => 'controller/aula_horas/controlador_listar_hora_aula_id2.php',
        8 => 'controller/aula_horas/controlador_modificar_aula_horas.php',
        9 => 'controller/aula_horas/controlador_registro_aula_horas.php',
        10 => 'controller/aulas/controlador_cargar_select_nivel.php',
        11 => 'controller/matricula/controlador_cargar_select_año.php',
        12 => 'controller/matricula/controlador_traernivel.php',
        13 => 'controller/notas/controlador_cargar_select_grado_profesor.php',
        14 => 'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php',
      ),
    ),
    'asistencia/view_registro_asistencia.php' => 
    array (
      'titulo' => 'Registro de asistencia',
      'seccion' => 'ASISTENCIA',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/comunicados/controlador_ver_comunicado.php',
        2 => 'controller/panel/controlador_panel_rol.php',
        3 => 'controller/registro_asistencia/controlador_guardar.php',
        4 => 'controller/registro_asistencia/controlador_planilla.php',
        5 => 'view/MPDF/REPORTE/asistencia_mensual.php',
      ),
    ),
    'asistencia/view_asistencia_reportes.php' => 
    array (
      'titulo' => 'Reporte de asistencias',
      'seccion' => 'ASISTENCIA',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        1 => 'controller/asistencias/controlador_listar_alumnos_totales.php',
        2 => 'controller/asistencias/controlador_listar_alumnos_totales_dia.php',
        3 => 'controller/matricula/controlador_cargar_select_año.php',
      ),
    ),
    'asistencia_docente/view_asistencia_docente.php' => 
    array (
      'titulo' => 'Asistencia docente',
      'seccion' => 'ASISTENCIA',
      'controladores' => 
      array (
        0 => 'controller/asistencia_docente/controlador_dia.php',
        1 => 'controller/asistencia_docente/controlador_guardar.php',
        2 => 'controller/asistencia_docente/controlador_resumen.php',
        3 => 'controller/ayuda/controlador_ayuda.php',
      ),
    ),
    'pago_pensiones/view_caja.php' => 
    array (
      'titulo' => 'Cobrar pensión',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/pago_pension/controlador_caja.php',
        1 => 'controller/pago_pension/controlador_cargar_formas_pago.php',
        2 => 'controller/pago_pension/controlador_detalle_pago_pension.php',
        3 => 'controller/pago_pension/controlador_eliminar_pago_pension.php',
        4 => 'view/MPDF/REPORTE/boleta_pago.php',
        5 => 'view/MPDF/REPORTE/kardex.php',
        6 => 'view/MPDF/REPORTE/recibo.php',
      ),
    ),
    'pensiones/view_pensiones_nivel.php' => 
    array (
      'titulo' => 'Pensiones',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/aulas/controlador_cargar_select_nivel.php',
        1 => 'controller/pensiones/controlador_ajustar_vencimientos.php',
        2 => 'controller/pensiones/controlador_anios_escolares.php',
        3 => 'controller/pensiones/controlador_eliminar_pensiones.php',
        4 => 'controller/pensiones/controlador_generar_pensiones.php',
        5 => 'controller/pensiones/controlador_listar_pensiones.php',
        6 => 'controller/pensiones/controlador_modificar_pension.php',
        7 => 'controller/pensiones/controlador_registro_pensiones.php',
      ),
    ),
    'ingresos_y_egresos/view_movimientos_ingresos.php' => 
    array (
      'titulo' => 'Ingresos',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/egresos/controlador_listar_diferencia_filtro.php',
        1 => 'controller/ingresos/controlador_anular_ingreso.php',
        2 => 'controller/ingresos/controlador_cargar_select_indicadores.php',
        3 => 'controller/ingresos/controlador_listar_ingresos_diversos_filtros.php',
        4 => 'controller/ingresos/controlador_listar_ingresos_pensiones_filtros.php',
        5 => 'controller/ingresos/controlador_modificar_ingreso.php',
        6 => 'controller/ingresos/controlador_registrar_ingresos.php',
        7 => 'controller/pago_pension/controlador_cargar_formas_pago.php',
      ),
    ),
    'ingresos_y_egresos/view_movimientos_gastos.php' => 
    array (
      'titulo' => 'Gastos',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/egresos/controlador_anular_egreso.php',
        1 => 'controller/egresos/controlador_cargar_select_indicadores_egresos.php',
        2 => 'controller/egresos/controlador_listar_diferencia_filtro.php',
        3 => 'controller/egresos/controlador_listar_egresos_diversos_filtros.php',
        4 => 'controller/egresos/controlador_modificar_egreso.php',
        5 => 'controller/egresos/controlador_registrar_egreso.php',
        6 => 'controller/egresos/controlador_ver_comprobante.php',
        7 => 'controller/pago_pension/controlador_cargar_formas_pago.php',
      ),
    ),
    'ingresos_y_egresos/view_indicadores.php' => 
    array (
      'titulo' => 'Categorías',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/indicadores/controlador_eliminar_indicador.php',
        1 => 'controller/indicadores/controlador_listar_grupos.php',
        2 => 'controller/indicadores/controlador_listar_indicadores.php',
        3 => 'controller/indicadores/controlador_modificar_indicador.php',
        4 => 'controller/indicadores/controlador_registro_indicadores.php',
      ),
    ),
    'contabilidad/view_cuentas.php' => 
    array (
      'titulo' => 'Cajas y bancos',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/contabilidad/controlador_caja_cerrar.php',
        1 => 'controller/contabilidad/controlador_caja_resumen.php',
        2 => 'controller/contabilidad/controlador_conciliar.php',
        3 => 'controller/contabilidad/controlador_movimientos.php',
        4 => 'controller/contabilidad/controlador_saldos.php',
        5 => 'controller/contabilidad/controlador_transferencia_anular.php',
        6 => 'controller/contabilidad/controlador_transferencia_registrar.php',
        7 => 'controller/contabilidad/controlador_transferencias_listar.php',
      ),
    ),
    'reportes/view_reportes_financieros.php' => 
    array (
      'titulo' => 'Reportes financieros',
      'seccion' => 'PENSIONES Y CAJA',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/reportes/controlador_cierres.php',
        2 => 'controller/reportes/controlador_filtros.php',
        3 => 'controller/reportes/controlador_flujo_caja.php',
        4 => 'controller/reportes/controlador_mensual.php',
        5 => 'controller/reportes/controlador_morosidad.php',
        6 => 'controller/reportes/controlador_planilla.php',
        7 => 'controller/reportes/controlador_por_metodo.php',
        8 => 'controller/reportes/controlador_proyeccion.php',
        9 => 'controller/reportes/controlador_resultados.php',
        10 => 'view/MPDF/REPORTE/estado_resultados.php',
        11 => 'view/MPDF/REPORTE/flujo_caja.php',
        12 => 'view/MPDF/REPORTE/morosidad.php',
      ),
    ),
    'docentes/view_docentes.php' => 
    array (
      'titulo' => 'Docentes',
      'seccion' => 'PERSONAL',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/docentes/controlador_cambiar_estado.php',
        2 => 'controller/docentes/controlador_cargar_select_especialidad.php',
        3 => 'controller/docentes/controlador_eliminar_docente.php',
        4 => 'controller/docentes/controlador_listar_docentes.php',
        5 => 'controller/docentes/controlador_modificar_docente.php',
        6 => 'controller/docentes/controlador_registrar_docente.php',
      ),
    ),
    'personal_admin/view_personal_admin.php' => 
    array (
      'titulo' => 'Personal administrativo',
      'seccion' => 'PERSONAL',
      'controladores' => 
      array (
        0 => 'controller/personal_administrativo/controlador_cargar_select_roles.php',
        1 => 'controller/personal_administrativo/controlador_eliminar_personal.php',
        2 => 'controller/personal_administrativo/controlador_listar_personaladmin.php',
        3 => 'controller/personal_administrativo/controlador_modificar_personal.php',
        4 => 'controller/personal_administrativo/controlador_registrar_personal_administrativo.php',
      ),
    ),
    'planilla/view_planilla.php' => 
    array (
      'titulo' => 'Planilla de personal',
      'seccion' => 'PERSONAL',
      'controladores' => 
      array (
        0 => 'controller/pago_pension/controlador_cargar_formas_pago.php',
        1 => 'controller/planilla/controlador_adelanto_registrar.php',
        2 => 'controller/planilla/controlador_adelantos_listar.php',
        3 => 'controller/planilla/controlador_concepto_agregar.php',
        4 => 'controller/planilla/controlador_concepto_guardar.php',
        5 => 'controller/planilla/controlador_concepto_quitar.php',
        6 => 'controller/planilla/controlador_conceptos_listar.php',
        7 => 'controller/planilla/controlador_contrato_finalizar.php',
        8 => 'controller/planilla/controlador_contrato_guardar.php',
        9 => 'controller/planilla/controlador_contratos_listar.php',
        10 => 'controller/planilla/controlador_detalle_actualizar.php',
        11 => 'controller/planilla/controlador_detalle_conceptos.php',
        12 => 'controller/planilla/controlador_pagar.php',
        13 => 'controller/planilla/controlador_pago_anular.php',
        14 => 'controller/planilla/controlador_planilla_anular.php',
        15 => 'controller/planilla/controlador_planilla_detalle.php',
        16 => 'controller/planilla/controlador_planilla_generar.php',
        17 => 'controller/planilla/controlador_planillas_listar.php',
        18 => 'view/MPDF/REPORTE/boleta_docente.php',
      ),
    ),
    'comunicado/view_comunicado.php' => 
    array (
      'titulo' => 'Comunicados',
      'seccion' => 'COMUNICACIÓN Y SALUD',
      'controladores' => 
      array (
        0 => 'controller/asignaturas/controlador_cargar_select_grado.php',
        1 => 'controller/comunicados/controlador_eliminar_comunicado.php',
        2 => 'controller/comunicados/controlador_lectura_comunicado.php',
        3 => 'controller/comunicados/controlador_listar_comunicados.php',
        4 => 'controller/comunicados/controlador_listar_comunicados2.php',
        5 => 'controller/comunicados/controlador_listar_comunicados_filtro.php',
        6 => 'controller/comunicados/controlador_modificar_comunicados.php',
        7 => 'controller/comunicados/controlador_registro_comunicados.php',
        8 => 'controller/matricula/controlador_traernivel.php',
        9 => 'controller/notas/controlador_cargar_select_grado_profesor.php',
        10 => 'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php',
      ),
    ),
    'avisos/view_avisos.php' => 
    array (
      'titulo' => 'Avisos a las familias',
      'seccion' => 'COMUNICACIÓN Y SALUD',
      'controladores' => 
      array (
        0 => 'controller/avisos/controlador_avisos.php',
      ),
    ),
    'solicitudes/view_solicitudes.php' => 
    array (
      'titulo' => 'Solicitudes de información',
      'seccion' => 'COMUNICACIÓN Y SALUD',
      'controladores' => 
      array (
        0 => 'controller/controlador_actualizar_estado_solicitud.php',
        1 => 'controller/controlador_estadisticas_solicitudes.php',
        2 => 'controller/controlador_listar_solicitudes.php',
      ),
    ),
    'salud/view_enfermeria.php' => 
    array (
      'titulo' => 'Enfermería',
      'seccion' => 'COMUNICACIÓN Y SALUD',
      'controladores' => 
      array (
        0 => 'controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php',
        1 => 'controller/atencion_enfermeria/controlador_registro_enfermeria.php',
        2 => 'controller/ayuda/controlador_ayuda.php',
        3 => 'controller/salud/controlador_atenciones.php',
      ),
    ),
    'salud/view_psicologia.php' => 
    array (
      'titulo' => 'Psicología',
      'seccion' => 'COMUNICACIÓN Y SALUD',
      'controladores' => 
      array (
        0 => 'controller/atencion_psicologica/controlador_modificar_atencion_psico.php',
        1 => 'controller/atencion_psicologica/controlador_registro_psicologia.php',
        2 => 'controller/ayuda/controlador_ayuda.php',
        3 => 'controller/salud/controlador_atenciones.php',
      ),
    ),
    'año/view_año_escolar.php' => 
    array (
      'titulo' => 'Años escolares',
      'seccion' => 'ORGANIZACIÓN DEL COLEGIO',
      'controladores' => 
      array (
        0 => 'controller/año_escolar/controlador_eliminar_año.php',
        1 => 'controller/año_escolar/controlador_listar_año_escolar.php',
        2 => 'controller/año_escolar/controlador_modificar_año_escolar.php',
        3 => 'controller/año_escolar/controlador_registro_año_escolar.php',
      ),
    ),
    'periodo/view_periodo.php' => 
    array (
      'titulo' => 'Periodos',
      'seccion' => 'ORGANIZACIÓN DEL COLEGIO',
      'controladores' => 
      array (
        0 => 'controller/matricula/controlador_cargar_select_año.php',
        1 => 'controller/periodos/controlador_eliminar_periodo_unico.php',
        2 => 'controller/periodos/controlador_eliminar_periodos.php',
        3 => 'controller/periodos/controlador_listar_periodo_año.php',
        4 => 'controller/periodos/controlador_listar_periodos.php',
        5 => 'controller/periodos/controlador_modificar_periodos.php',
        6 => 'controller/periodos/controlador_registro_periodos.php',
      ),
    ),
    'nivel_academico/view_nivel_academico.php' => 
    array (
      'titulo' => 'Niveles',
      'seccion' => 'ORGANIZACIÓN DEL COLEGIO',
      'controladores' => 
      array (
        0 => 'controller/nivel_academico/controlador_eliminar_nivel_academico.php',
        1 => 'controller/nivel_academico/controlador_listar_nivel_academico.php',
        2 => 'controller/nivel_academico/controlador_modificar_niveaca.php',
        3 => 'controller/nivel_academico/controlador_registro_nivel_aca.php',
      ),
    ),
    'aulas/view_aulas.php' => 
    array (
      'titulo' => 'Grados',
      'seccion' => 'ORGANIZACIÓN DEL COLEGIO',
      'controladores' => 
      array (
        0 => 'controller/asistencias/controlador_cargar_select_aula_id.php',
        1 => 'controller/aulas/controlador_cargar_select_nivel.php',
        2 => 'controller/aulas/controlador_cargar_select_seccion.php',
        3 => 'controller/aulas/controlador_eliminar_rol.php',
        4 => 'controller/aulas/controlador_listar_aulas.php',
        5 => 'controller/aulas/controlador_listar_aulas_filtro.php',
        6 => 'controller/aulas/controlador_modificar_aula.php',
        7 => 'controller/aulas/controlador_registro_aulas.php',
        8 => 'view/MPDF/REPORTE/cedula.php',
      ),
    ),
    'seccion/view_seccion.php' => 
    array (
      'titulo' => 'Secciones',
      'seccion' => 'ORGANIZACIÓN DEL COLEGIO',
      'controladores' => 
      array (
        0 => 'controller/seccion/controlador_eliminar_seccion.php',
        1 => 'controller/seccion/controlador_listar_seccion.php',
        2 => 'controller/seccion/controlador_modificar_seccion.php',
        3 => 'controller/seccion/controlador_registro_seccion.php',
      ),
    ),
    'configuracion/view_cambio_anio.php' => 
    array (
      'titulo' => 'Cambio de año escolar',
      'seccion' => 'ORGANIZACIÓN DEL COLEGIO',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/cambio_anio/controlador_ejecutar.php',
        2 => 'controller/cambio_anio/controlador_propuesta.php',
        3 => 'controller/comunicados/controlador_ver_comunicado.php',
        4 => 'controller/panel/controlador_panel_rol.php',
      ),
    ),
    'usuario/view_usuario.php' => 
    array (
      'titulo' => 'Usuarios',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/alumnos/controlador_modificar_foto_estudiante.php',
        1 => 'controller/docentes/controlador_empresa_modificar_foto_docente.php',
        2 => 'controller/usuario/controlador_cargar_select_rol.php',
        3 => 'controller/usuario/controlador_listar_usuario.php',
        4 => 'controller/usuario/controlador_listar_usuario_filtro.php',
        5 => 'controller/usuario/controlador_modificar_usuario.php',
        6 => 'controller/usuario/controlador_modificar_usuario_contra.php',
        7 => 'controller/usuario/controlador_modificar_usuario_estatus.php',
        8 => 'controller/usuario/controlador_registro_usuario.php',
        9 => 'controller/usuario/controlador_total_administrativos.php',
        10 => 'controller/usuario/controlador_total_docentes.php',
        11 => 'controller/usuario/controlador_total_egresos.php',
        12 => 'controller/usuario/controlador_total_enfermeria.php',
        13 => 'controller/usuario/controlador_total_estudiantes.php',
        14 => 'controller/usuario/controlador_total_ingresos.php',
        15 => 'controller/usuario/controlador_total_psicologia.php',
        16 => 'controller/usuario/controlador_total_usuarios.php',
        17 => 'controller/usuario/controlador_traer_datos.php',
      ),
    ),
    'roles/view_roles.php' => 
    array (
      'titulo' => 'Roles',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/roles/controlador_eliminar_rol.php',
        1 => 'controller/roles/controlador_listar_roles.php',
        2 => 'controller/roles/controlador_modificar_rol.php',
        3 => 'controller/roles/controlador_registro_roles.php',
      ),
    ),
    'especialidades/view_especialidades.php' => 
    array (
      'titulo' => 'Especialidades',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/especialidad/controlador_eliminar_especialidad.php',
        1 => 'controller/especialidad/controlador_listar_especialidad.php',
        2 => 'controller/especialidad/controlador_modificar_especialidad.php',
        3 => 'controller/especialidad/controlador_registro_especialidad.php',
      ),
    ),
    'configuracion/view_formas_pago.php' => 
    array (
      'titulo' => 'Formas de pago y cuentas',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/configuracion/controlador_cuenta_guardar.php',
        1 => 'controller/configuracion/controlador_formas_pago_listar.php',
        2 => 'controller/configuracion/controlador_metodo_guardar.php',
      ),
    ),
    'configuracion/view_regional.php' => 
    array (
      'titulo' => 'Datos regionales',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/configuracion/controlador_regional_cargar.php',
        1 => 'controller/configuracion/controlador_regional_guardar.php',
      ),
    ),
    'configuracion/view_apariencia.php' => 
    array (
      'titulo' => 'Apariencia',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/configuracion/controlador_apariencia.php',
        1 => 'controller/empresa/controlador_listar_empresa.php',
      ),
    ),
    'configuracion/view_permisos.php' => 
    array (
      'titulo' => 'Permisos por rol',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/configuracion/controlador_permisos.php',
      ),
    ),
    'configuracion/view_parametros.php' => 
    array (
      'titulo' => 'Parámetros del sistema',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/configuracion/controlador_parametros.php',
      ),
    ),
    'configuracion/view_auditoria.php' => 
    array (
      'titulo' => 'Auditoría',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/configuracion/controlador_auditoria_listar.php',
      ),
    ),
    'configuracion/view_respaldos.php' => 
    array (
      'titulo' => 'Respaldos',
      'seccion' => 'CONFIGURACIÓN',
      'controladores' => 
      array (
        0 => 'controller/respaldos/controlador_respaldos.php',
      ),
    ),
    'planilla/view_mis_boletas.php' => 
    array (
      'titulo' => 'Mis boletas de pago',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/planilla/controlador_mis_boletas.php',
        1 => 'view/MPDF/REPORTE/boleta_docente.php',
      ),
    ),
    'notas/view_registro_notas.php' => 
    array (
      'titulo' => 'Notas de mis cursos',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/notas/controlador_registro_notas_curso.php',
      ),
    ),
    'docente/view_tareas_docente.php' => 
    array (
      'titulo' => 'Tareas de mis cursos',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/docente/controlador_trabajos.php',
        2 => 'controller/examenes/controlador_eliminar_examenes.php',
        3 => 'controller/examenes/controlador_modificar_estado_examen.php',
        4 => 'controller/examenes/controlador_modificar_examen.php',
        5 => 'controller/examenes/controlador_registro_examenes.php',
        6 => 'controller/tareas/controlador_archivos_tarea.php',
        7 => 'controller/tareas/controlador_eliminar_tarea.php',
        8 => 'controller/tareas/controlador_modificar_estado_tarea.php',
        9 => 'controller/tareas/controlador_modificar_tareas.php',
        10 => 'controller/tareas/controlador_registro_tareas.php',
      ),
    ),
    'docente/view_examenes_docente.php' => 
    array (
      'titulo' => 'Exámenes de mis cursos',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/docente/controlador_trabajos.php',
        2 => 'controller/examenes/controlador_eliminar_examenes.php',
        3 => 'controller/examenes/controlador_modificar_estado_examen.php',
        4 => 'controller/examenes/controlador_modificar_examen.php',
        5 => 'controller/examenes/controlador_registro_examenes.php',
        6 => 'controller/tareas/controlador_archivos_tarea.php',
        7 => 'controller/tareas/controlador_eliminar_tarea.php',
        8 => 'controller/tareas/controlador_modificar_estado_tarea.php',
        9 => 'controller/tareas/controlador_modificar_tareas.php',
        10 => 'controller/tareas/controlador_registro_tareas.php',
      ),
    ),
    'estudiante/view_mis_notas.php' => 
    array (
      'titulo' => 'Mis notas',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/estudiante/controlador_mi_asistencia.php',
        1 => 'controller/estudiante/controlador_mis_notas.php',
        2 => 'controller/estudiante/controlador_mis_pagos.php',
        3 => 'controller/estudiante/controlador_mis_tareas.php',
        4 => 'controller/tareas/controlador_archivos_tarea.php',
        5 => 'view/MPDF/REPORTE/boleta_pago.php',
        6 => 'view/MPDF/REPORTE/kardex.php',
        7 => 'view/MPDF/REPORTE/libreta.php',
        8 => 'view/MPDF/REPORTE/recibo.php',
      ),
    ),
    'estudiante/view_mis_tareas.php' => 
    array (
      'titulo' => 'Mis tareas',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/estudiante/controlador_mi_asistencia.php',
        2 => 'controller/estudiante/controlador_mis_notas.php',
        3 => 'controller/estudiante/controlador_mis_pagos.php',
        4 => 'controller/estudiante/controlador_mis_tareas.php',
        5 => 'controller/tareas/controlador_archivos_tarea.php',
        6 => 'controller/tareas/controlador_registro_tareas_estudiante.php',
        7 => 'view/MPDF/REPORTE/boleta_pago.php',
        8 => 'view/MPDF/REPORTE/kardex.php',
        9 => 'view/MPDF/REPORTE/libreta.php',
        10 => 'view/MPDF/REPORTE/recibo.php',
      ),
    ),
    'estudiante/view_mi_asistencia.php' => 
    array (
      'titulo' => 'Mi asistencia',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/estudiante/controlador_mi_asistencia.php',
        1 => 'controller/estudiante/controlador_mis_notas.php',
        2 => 'controller/estudiante/controlador_mis_pagos.php',
        3 => 'controller/estudiante/controlador_mis_tareas.php',
        4 => 'controller/tareas/controlador_archivos_tarea.php',
        5 => 'view/MPDF/REPORTE/boleta_pago.php',
        6 => 'view/MPDF/REPORTE/kardex.php',
        7 => 'view/MPDF/REPORTE/libreta.php',
        8 => 'view/MPDF/REPORTE/recibo.php',
      ),
    ),
    'estudiante/view_mis_pagos.php' => 
    array (
      'titulo' => 'Mis pagos',
      'seccion' => 'CONTABILIDAD',
      'controladores' => 
      array (
        0 => 'controller/estudiante/controlador_mi_asistencia.php',
        1 => 'controller/estudiante/controlador_mis_notas.php',
        2 => 'controller/estudiante/controlador_mis_pagos.php',
        3 => 'controller/estudiante/controlador_mis_tareas.php',
        4 => 'controller/tareas/controlador_archivos_tarea.php',
        5 => 'view/MPDF/REPORTE/boleta_pago.php',
        6 => 'view/MPDF/REPORTE/kardex.php',
        7 => 'view/MPDF/REPORTE/libreta.php',
        8 => 'view/MPDF/REPORTE/recibo.php',
      ),
    ),
  ),
  'roles' => 
  array (
    'ADMINISTRADOR' => 
    array (
      'vistas' => 
      array (
        0 => 'alumnos/view_alumnos.php',
        1 => 'matricula/view_matricula.php',
        2 => 'reportes/view_reporte_estudiantes.php',
        3 => 'notas/view_notas.php',
        4 => 'componentes/view_componentes.php',
        5 => 'boletas/view_boletas.php',
        6 => 'examen/view_examen.php',
        7 => 'aula_virtual/view_aula_virtual.php',
        8 => 'tareas/view_tareas.php',
        9 => 'tareas/view_registro_tareas.php',
        10 => 'asignaturas/view_asignatura.php',
        11 => 'docente_asignatura/view_docenteasignatura.php',
        12 => 'horario/view_horario.php',
        13 => 'horario/view_calendario.php',
        14 => 'aula_hora/view_aula_hora.php',
        15 => 'asistencia/view_registro_asistencia.php',
        16 => 'asistencia/view_asistencia_reportes.php',
        17 => 'asistencia_docente/view_asistencia_docente.php',
        18 => 'pago_pensiones/view_caja.php',
        19 => 'pensiones/view_pensiones_nivel.php',
        20 => 'ingresos_y_egresos/view_movimientos_ingresos.php',
        21 => 'ingresos_y_egresos/view_movimientos_gastos.php',
        22 => 'ingresos_y_egresos/view_indicadores.php',
        23 => 'contabilidad/view_cuentas.php',
        24 => 'reportes/view_reportes_financieros.php',
        25 => 'docentes/view_docentes.php',
        26 => 'personal_admin/view_personal_admin.php',
        27 => 'planilla/view_planilla.php',
        28 => 'comunicado/view_comunicado.php',
        29 => 'avisos/view_avisos.php',
        30 => 'solicitudes/view_solicitudes.php',
        31 => 'salud/view_enfermeria.php',
        32 => 'salud/view_psicologia.php',
        33 => 'año/view_año_escolar.php',
        34 => 'periodo/view_periodo.php',
        35 => 'nivel_academico/view_nivel_academico.php',
        36 => 'aulas/view_aulas.php',
        37 => 'seccion/view_seccion.php',
        38 => 'configuracion/view_cambio_anio.php',
        39 => 'usuario/view_usuario.php',
        40 => 'roles/view_roles.php',
        41 => 'especialidades/view_especialidades.php',
        42 => 'configuracion/view_formas_pago.php',
        43 => 'configuracion/view_regional.php',
        44 => 'configuracion/view_apariencia.php',
        45 => 'configuracion/view_permisos.php',
        46 => 'configuracion/view_parametros.php',
        47 => 'configuracion/view_auditoria.php',
        48 => 'configuracion/view_respaldos.php',
      ),
      'indice' => 
      array (
        0 => 'controller/alumnos/controlador_buscar_global.php',
        1 => 'controller/alumnos/controlador_ficha.php',
        2 => 'controller/ayuda/controlador_ayuda.php',
        3 => 'controller/comunicados/controlador_lectura_comunicado.php',
        4 => 'controller/comunicados/controlador_listar_comunicados2.php',
        5 => 'controller/comunicados/controlador_ver_comunicado.php',
        6 => 'controller/empresa/controlador_empresa_modificar_foto.php',
        7 => 'controller/empresa/controlador_listar_empresa.php',
        8 => 'controller/empresa/controlador_modificar_empresa.php',
        9 => 'controller/panel/controlador_panel_rol.php',
        10 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        11 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
    'CONTADOR' => 
    array (
      'vistas' => 
      array (
        0 => 'pago_pensiones/view_caja.php',
        1 => 'pensiones/view_pensiones_nivel.php',
        2 => 'ingresos_y_egresos/view_movimientos_ingresos.php',
        3 => 'ingresos_y_egresos/view_movimientos_gastos.php',
        4 => 'ingresos_y_egresos/view_indicadores.php',
        5 => 'contabilidad/view_cuentas.php',
        6 => 'planilla/view_planilla.php',
        7 => 'reportes/view_reportes_financieros.php',
        8 => 'reportes/view_reporte_estudiantes.php',
        9 => 'avisos/view_avisos.php',
        10 => 'configuracion/view_formas_pago.php',
        11 => 'planilla/view_mis_boletas.php',
      ),
      'indice' => 
      array (
        0 => 'controller/alumnos/controlador_buscar_global.php',
        1 => 'controller/alumnos/controlador_ficha.php',
        2 => 'controller/ayuda/controlador_ayuda.php',
        3 => 'controller/comunicados/controlador_listar_comunicados2.php',
        4 => 'controller/comunicados/controlador_ver_comunicado.php',
        5 => 'controller/contabilidad/controlador_panel.php',
        6 => 'controller/panel/controlador_panel_rol.php',
        7 => 'controller/reportes/controlador_mensual.php',
        8 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        9 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
    'ENFERMERA' => 
    array (
      'vistas' => 
      array (
        0 => 'salud/view_enfermeria.php',
        1 => 'planilla/view_mis_boletas.php',
      ),
      'indice' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/comunicados/controlador_listar_comunicados2.php',
        2 => 'controller/comunicados/controlador_ver_comunicado.php',
        3 => 'controller/panel/controlador_panel_rol.php',
        4 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        5 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
    'PSICOLOGA' => 
    array (
      'vistas' => 
      array (
        0 => 'salud/view_psicologia.php',
        1 => 'planilla/view_mis_boletas.php',
      ),
      'indice' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/comunicados/controlador_listar_comunicados2.php',
        2 => 'controller/comunicados/controlador_ver_comunicado.php',
        3 => 'controller/panel/controlador_panel_rol.php',
        4 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        5 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
    'AUXILIAR' => 
    array (
      'vistas' => 
      array (
        0 => 'asistencia/view_registro_asistencia.php',
        1 => 'asistencia/view_asistencia_reportes.php',
        2 => 'asistencia_docente/view_asistencia_docente.php',
        3 => 'horario/view_horario.php',
        4 => 'horario/view_calendario.php',
        5 => 'aula_hora/view_aula_hora.php',
        6 => 'aula_virtual/view_aula_virtual.php',
        7 => 'tareas/view_tareas.php',
        8 => 'tareas/view_registro_tareas.php',
        9 => 'reportes/view_reporte_estudiantes.php',
        10 => 'avisos/view_avisos.php',
        11 => 'planilla/view_mis_boletas.php',
      ),
      'indice' => 
      array (
        0 => 'controller/alumnos/controlador_buscar_global.php',
        1 => 'controller/alumnos/controlador_ficha.php',
        2 => 'controller/ayuda/controlador_ayuda.php',
        3 => 'controller/comunicados/controlador_listar_comunicados2.php',
        4 => 'controller/comunicados/controlador_ver_comunicado.php',
        5 => 'controller/panel/controlador_panel_rol.php',
        6 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        7 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
    'DOCENTE' => 
    array (
      'vistas' => 
      array (
        0 => 'planilla/view_mis_boletas.php',
        1 => 'notas/view_registro_notas.php',
        2 => 'aula_virtual/view_aula_virtual.php',
        3 => 'horario/view_calendario.php',
        4 => 'docente/view_tareas_docente.php',
        5 => 'tareas/view_registro_tareas.php',
        6 => 'asistencia/view_registro_asistencia.php',
        7 => 'docente/view_examenes_docente.php',
      ),
      'indice' => 
      array (
        0 => 'controller/ayuda/controlador_ayuda.php',
        1 => 'controller/comunicados/controlador_listar_comunicados2.php',
        2 => 'controller/comunicados/controlador_ver_comunicado.php',
        3 => 'controller/docentes/controlador_empresa_modificar_foto_docente.php',
        4 => 'controller/panel/controlador_panel_rol.php',
        5 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        6 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
    'ESTUDIANTE' => 
    array (
      'vistas' => 
      array (
        0 => 'estudiante/view_mis_notas.php',
        1 => 'aula_virtual/view_aula_virtual.php',
        2 => 'estudiante/view_mis_tareas.php',
        3 => 'estudiante/view_mi_asistencia.php',
        4 => 'horario/view_calendario.php',
        5 => 'estudiante/view_mis_pagos.php',
      ),
      'indice' => 
      array (
        0 => 'controller/alumnos/controlador_modificar_foto_estudiante.php',
        1 => 'controller/ayuda/controlador_ayuda.php',
        2 => 'controller/comunicados/controlador_listar_comunicados2.php',
        3 => 'controller/comunicados/controlador_ver_comunicado.php',
        4 => 'controller/panel/controlador_panel_rol.php',
        5 => 'controller/usuario/controlador_cambiar_mi_clave.php',
        6 => 'controller/usuario/controlador_cerrar_sesion.php',
      ),
    ),
  ),
);
