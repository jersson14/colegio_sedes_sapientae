<?php
/**
 * Permisos por controlador (lo usa controller/_seguridad.php).
 *
 * - publico: no requieren sesión.
 * - roles: roles que pueden llamar cada archivo. ADMINISTRADOR siempre puede.
 *   Un archivo que no aparece aquí es SOLO para ADMINISTRADOR.
 * - propio: parámetros que, para roles distintos de ADMINISTRADOR, se fuerzan al id del
 *   usuario en sesión (cada estudiante/docente consulta solo lo suyo).
 * - propio_sesion: como 'propio', pero con otro dato de la sesión (campo => clave de $_SESSION).
 * - usuario_sesion: parámetros que siempre se fuerzan al id del usuario en sesión.
 *
 * Al agregar un controlador nuevo para un rol distinto de ADMINISTRADOR, registrarlo en 'roles'.
 */
return [
    'publico' => [
        'controller/usuario/controlador_iniciar_sesion.php',
        'controller/usuario/controlador_cerrar_sesion.php',
        'controller/controlador_solicitudes.php',
    ],

    'roles' => [
        // alumnos
        'controller/alumnos/controlador_listar_alumnos.php' => ['AUXILIAR'],
        'controller/alumnos/controlador_modificar_foto_estudiante.php' => ['ESTUDIANTE'],
        // asignatura_docente
        'controller/asignatura_docente/controlador_cargar_docente.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/asignatura_docente/controlador_cargar_select_asignatura.php' => ['ESTUDIANTE'],
        'controller/asignatura_docente/controlador_detalle_asignatura_docente.php' => ['ESTUDIANTE'],
        'controller/asignatura_docente/controlador_eliminar_asignatura_unica.php' => ['ESTUDIANTE'],
        'controller/asignatura_docente/controlador_listar_tabla_detalle_curso.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        // asignaturas
        'controller/asignaturas/controlador_cargar_select_grado.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        // asistencias
        'controller/asistencias/controlador_cargar_select_aula_id.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/asistencias/controlador_editar_asistencia.php' => ['AUXILIAR'],
        'controller/asistencias/controlador_eliminar_asistencia.php' => ['AUXILIAR'],
        'controller/asistencias/controlador_listar_alumnos_asistencia.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/asistencias/controlador_listar_alumnos_por_grado.php' => ['AUXILIAR'],
        'controller/asistencias/controlador_listar_alumnos_totales.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/asistencias/controlador_listar_alumnos_totales_dia.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/asistencias/controlador_listar_alumnos_totales_dia_estudiante.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/asistencias/controlador_listar_alumnos_totales_estu.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/asistencias/controlador_listar_asistencias.php' => ['AUXILIAR'],
        'controller/asistencias/controlador_listar_asistencias_fechas.php' => ['AUXILIAR'],
        'controller/asistencias/controlador_registro_asistencias.php' => ['AUXILIAR'],
        // atencion_enfermeria
        'controller/atencion_enfermeria/controlador_listar_atención_enfermeria.php' => ['ENFERMERA'],
        'controller/atencion_enfermeria/controlador_listar_atención_enfermeria_filtros.php' => ['ENFERMERA'],
        'controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php' => ['ENFERMERA', 'PSICOLOGA'],
        'controller/atencion_enfermeria/controlador_registro_enfermeria.php' => ['ENFERMERA', 'PSICOLOGA'],
        // atencion_psicologica
        'controller/atencion_psicologica/controlador_cargar_matriculado_año.php' => ['ENFERMERA', 'PSICOLOGA'],
        'controller/atencion_psicologica/controlador_listar_atención_psicologica.php' => ['PSICOLOGA'],
        'controller/atencion_psicologica/controlador_listar_atención_psicologica_filtros.php' => ['PSICOLOGA'],
        'controller/atencion_psicologica/controlador_modificar_atencion_psico.php' => ['ENFERMERA', 'PSICOLOGA'],
        'controller/atencion_psicologica/controlador_registro_psicologia.php' => ['ENFERMERA', 'PSICOLOGA'],
        // aula_horas
        'controller/aula_horas/controlador_eliminar_aula_horas.php' => ['AUXILIAR'],
        'controller/aula_horas/controlador_eliminar_horas_curso_unico.php' => ['AUXILIAR'],
        'controller/aula_horas/controlador_listar_aula_horas.php' => ['AUXILIAR'],
        'controller/aula_horas/controlador_listar_aula_horas_filtro.php' => ['AUXILIAR'],
        'controller/aula_horas/controlador_listar_hora_aula_id.php' => ['AUXILIAR'],
        'controller/aula_horas/controlador_listar_hora_aula_id2.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/aula_horas/controlador_modificar_aula_horas.php' => ['AUXILIAR'],
        'controller/aula_horas/controlador_registro_aula_horas.php' => ['AUXILIAR'],
        // aulas
        'controller/aulas/controlador_cargar_select_nivel.php' => ['AUXILIAR', 'CONTADOR', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        // componentes
        'controller/componentes/controlador_cargar_curso_id_detalle.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/componentes/controlador_listar_componenetes.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/componentes/controlador_listar_tabla_componentes_curso.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        // comunicados
        'controller/comunicados/controlador_listar_comunicados2.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        // configuracion
        'controller/configuracion/controlador_cuenta_guardar.php' => ['CONTADOR'],
        'controller/configuracion/controlador_formas_pago_listar.php' => ['CONTADOR'],
        'controller/configuracion/controlador_metodo_guardar.php' => ['CONTADOR'],
        // contabilidad
        'controller/contabilidad/controlador_caja_cerrar.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_caja_resumen.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_conciliar.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_movimientos.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_panel.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_saldos.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_transferencia_anular.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_transferencia_registrar.php' => ['CONTADOR'],
        'controller/contabilidad/controlador_transferencias_listar.php' => ['CONTADOR'],
        // docentes
        'controller/docentes/controlador_empresa_modificar_foto_docente.php' => ['DOCENTE'],
        'controller/docentes/controlador_listar_docentes.php' => ['AUXILIAR'],
        // egresos
        'controller/egresos/controlador_anular_egreso.php' => ['CONTADOR'],
        'controller/egresos/controlador_cargar_select_indicadores_egresos.php' => ['CONTADOR'],
        'controller/egresos/controlador_listar_diferencia.php' => ['CONTADOR'],
        'controller/egresos/controlador_listar_diferencia_filtro.php' => ['CONTADOR'],
        'controller/egresos/controlador_listar_egresos_diversos.php' => ['CONTADOR'],
        'controller/egresos/controlador_listar_egresos_diversos_filtros.php' => ['CONTADOR'],
        'controller/egresos/controlador_modificar_egreso.php' => ['CONTADOR'],
        'controller/egresos/controlador_registrar_egreso.php' => ['CONTADOR'],
        'controller/egresos/controlador_ver_comprobante.php' => ['CONTADOR'],
        // empresa
        'controller/empresa/controlador_listar_empresa.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        // examenes
        'controller/examenes/controlador_eliminar_examenes.php' => ['DOCENTE'],
        'controller/examenes/controlador_listar_alumnos_examen.php' => ['DOCENTE'],
        'controller/examenes/controlador_listar_examenes.php' => ['DOCENTE'],
        'controller/examenes/controlador_listar_examenes_estudiante_solo_pendiente.php' => ['ESTUDIANTE'],
        'controller/examenes/controlador_listar_examenes_profesor.php' => ['DOCENTE'],
        'controller/examenes/controlador_listar_examenes_profesor_solo.php' => ['DOCENTE'],
        'controller/examenes/controlador_modificar_estado_examen.php' => ['DOCENTE'],
        'controller/examenes/controlador_modificar_examen.php' => ['DOCENTE'],
        'controller/examenes/controlador_registro_examenes.php' => ['DOCENTE'],
        // horarios
        'controller/horarios/controlador_cargar_curso_id_detalle.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/horarios/controlador_cargar_select_horas.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/horarios/controlador_eliminar_horario.php' => ['AUXILIAR'],
        'controller/horarios/controlador_eliminar_horario_unico.php' => ['AUXILIAR'],
        'controller/horarios/controlador_listar_horarios.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/horarios/controlador_listar_horarios_editar.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/horarios/controlador_listar_horarios_estudiante_id.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/horarios/controlador_listar_horarios_estudiante_id_año.php' => ['ESTUDIANTE'],
        'controller/horarios/controlador_listar_horarios_estudiante_todo.php' => ['ESTUDIANTE'],
        'controller/horarios/controlador_listar_horarios_filtro.php' => ['AUXILIAR'],
        'controller/horarios/controlador_listar_horarios_id.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/horarios/controlador_modificar_horarios.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'controller/horarios/controlador_registro_horario_aula.php' => ['AUXILIAR', 'ESTUDIANTE'],
        // indicadores
        'controller/indicadores/controlador_eliminar_indicador.php' => ['CONTADOR'],
        'controller/indicadores/controlador_listar_indicadores.php' => ['CONTADOR'],
        'controller/indicadores/controlador_modificar_indicador.php' => ['CONTADOR'],
        'controller/indicadores/controlador_registro_indicadores.php' => ['CONTADOR'],
        // ingresos
        'controller/ingresos/controlador_anular_ingreso.php' => ['CONTADOR'],
        'controller/ingresos/controlador_cargar_select_indicadores.php' => ['CONTADOR'],
        'controller/ingresos/controlador_listar_ingresos_diversos.php' => ['CONTADOR'],
        'controller/ingresos/controlador_listar_ingresos_diversos_filtros.php' => ['CONTADOR'],
        'controller/ingresos/controlador_listar_ingresos_pensiones.php' => ['CONTADOR'],
        'controller/ingresos/controlador_listar_ingresos_pensiones_filtros.php' => ['CONTADOR'],
        'controller/ingresos/controlador_modificar_ingreso.php' => ['CONTADOR'],
        'controller/ingresos/controlador_registrar_ingresos.php' => ['CONTADOR'],
        // matricula
        'controller/matricula/controlador_cargar_select_año.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/matricula/controlador_traernivel.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        // nivel_academico
        'controller/nivel_academico/controlador_listar_nivel_academico.php' => ['CONTADOR'],
        // notas
        'controller/notas/controlador_cargar_años_por_estudiante.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_cargar_periodos.php' => ['DOCENTE'],
        'controller/notas/controlador_cargar_periodos2.php' => ['DOCENTE'],
        'controller/notas/controlador_cargar_periodos_cargados.php' => ['DOCENTE'],
        'controller/notas/controlador_cargar_periodos_cargados_estudiante.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_cargar_select_grado_profesor.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_criterios_notas.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/notas/controlador_listar_criterios_notas_mostrar.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_criterios_notas_mostrar_estudiante.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_criterios_notas_mostrar_padres.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_criterios_notas_mostrar_profesor.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_criterios_notas_profesor.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/notas/controlador_listar_matriculas_filtro.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_matriculas_filtro_alumnos.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_listar_matriculas_filtro_profesor.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/notas/controlador_registro_notas.php' => ['DOCENTE'],
        'controller/notas/controlador_registro_notas_padres.php' => ['DOCENTE'],
        // pago_pension
        'controller/pago_pension/controlador_cargar_formas_pago.php' => ['AUXILIAR', 'CONTADOR', 'DOCENTE', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/pago_pension/controlador_cargar_select_pension.php' => ['AUXILIAR', 'CONTADOR', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        'controller/pago_pension/controlador_detalle_pago_pension.php' => ['CONTADOR'],
        'controller/pago_pension/controlador_eliminar_pago_pension.php' => ['CONTADOR'],
        'controller/pago_pension/controlador_listar_matriculas.php' => ['CONTADOR', 'ESTUDIANTE'],
        'controller/pago_pension/controlador_listar_matriculas_año_id.php' => ['ESTUDIANTE'],
        'controller/pago_pension/controlador_listar_matriculas_filtro.php' => ['CONTADOR'],
        'controller/pago_pension/controlador_listar_matriculas_id.php' => ['CONTADOR', 'ESTUDIANTE'],
        'controller/pago_pension/controlador_listar_pension_todo.php' => ['ESTUDIANTE'],
        'controller/pago_pension/controlador_listar_tabla_pagos.php' => ['CONTADOR', 'ESTUDIANTE'],
        'controller/pago_pension/controlador_modificar_pago_solo.php' => ['CONTADOR'],
        'controller/pago_pension/controlador_saldo_pension.php' => ['AUXILIAR', 'CONTADOR', 'ENFERMERA', 'ESTUDIANTE', 'PSICOLOGA'],
        // pensiones
        'controller/pensiones/controlador_eliminar_pensiones.php' => ['CONTADOR'],
        'controller/pensiones/controlador_listar_pensiones.php' => ['CONTADOR'],
        'controller/pensiones/controlador_listar_pensiones_filtro.php' => ['CONTADOR'],
        'controller/pensiones/controlador_modificar_pension.php' => ['CONTADOR'],
        'controller/pensiones/controlador_registro_pensiones.php' => ['CONTADOR'],
        // tareas
        'controller/tareas/controlador_archivos_tarea.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_cargar_curso_id_detalle_estudiante.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_cargar_select_cursos_docente.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_cargar_select_grado_estudiante.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_eliminar_tarea.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_listar_tabla_envio_tareas.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_listar_tareas.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_listar_tareas_estudiante.php' => ['ESTUDIANTE'],
        'controller/tareas/controlador_listar_tareas_estudiante_solo.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_listar_tareas_estudiante_solo_pendiente.php' => ['ESTUDIANTE'],
        'controller/tareas/controlador_listar_tareas_filtro.php' => ['AUXILIAR'],
        'controller/tareas/controlador_listar_tareas_profesor.php' => ['DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_listar_tareas_profesor_solo.php' => ['AUXILIAR', 'DOCENTE', 'ESTUDIANTE'],
        'controller/tareas/controlador_modificar_estado_tarea.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_modificar_tareas.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_modificar_tareas_estudiante.php' => ['ESTUDIANTE'],
        'controller/tareas/controlador_registro_calificacion.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_registro_tareas.php' => ['AUXILIAR', 'DOCENTE'],
        'controller/tareas/controlador_registro_tareas_estudiante.php' => ['ESTUDIANTE'],
        // usuario
        'controller/usuario/controlador_traer_datos.php' => ['ESTUDIANTE'],
        // reportes
        'view/MPDF/REPORTE/boleta_pago.php' => ['CONTADOR', 'ESTUDIANTE'],
        'view/MPDF/REPORTE/horario.php' => ['AUXILIAR', 'ESTUDIANTE'],
        'view/MPDF/REPORTE/kardex.php' => ['CONTADOR', 'ESTUDIANTE'],
        'view/MPDF/REPORTE/recibo.php' => ['CONTADOR', 'ESTUDIANTE'],
    ],

    'propio' => [
        'controller/notas/controlador_cargar_años_por_estudiante.php' => ['id'],
        'controller/tareas/controlador_cargar_select_grado_estudiante.php' => ['id'],
        'controller/asistencias/controlador_listar_alumnos_totales_estu.php' => ['id'],
        'controller/asistencias/controlador_listar_alumnos_totales_dia_estudiante.php' => ['id'],
        'controller/horarios/controlador_listar_horarios_estudiante_id.php' => ['id'],
        'controller/horarios/controlador_listar_horarios_estudiante_id_año.php' => ['id'],
        'controller/horarios/controlador_listar_horarios_estudiante_todo.php' => ['id'],
        'controller/notas/controlador_listar_matriculas_filtro_alumnos.php' => ['id'],
        'controller/notas/controlador_cargar_select_grado_profesor.php' => ['id'],
        'controller/notas/controlador_listar_criterios_notas_profesor.php' => ['id'],
        'controller/pago_pension/controlador_listar_matriculas_id.php' => ['id'],
        'controller/pago_pension/controlador_listar_matriculas_año_id.php' => ['id'],
        'controller/pago_pension/controlador_listar_pension_todo.php' => ['id'],
        'controller/tareas/controlador_listar_tareas_estudiante.php' => ['id'],
        'controller/tareas/controlador_listar_tareas_estudiante_solo.php' => ['id'],
        'controller/tareas/controlador_listar_tareas_estudiante_solo_pendiente.php' => ['id'],
        'controller/examenes/controlador_listar_examenes_estudiante_solo_pendiente.php' => ['id'],
        'controller/tareas/controlador_listar_tareas_profesor.php' => ['id'],
        'controller/tareas/controlador_listar_tareas_profesor_solo.php' => ['id'],
        'controller/examenes/controlador_listar_examenes_profesor.php' => ['id'],
        'controller/examenes/controlador_listar_examenes_profesor_solo.php' => ['id'],
        'controller/usuario/controlador_traer_datos.php' => ['id'],
        'controller/tareas/controlador_cargar_curso_id_detalle_estudiante.php' => ['idestu'],
        'controller/tareas/controlador_cargar_curso_id_detalle_profesor.php' => ['idpro'],
    ],

    'propio_sesion' => [
        'controller/alumnos/controlador_modificar_foto_estudiante.php' => ['id' => 'S_DNI', 'fotoactual' => 'S_FOTO'],
        'controller/docentes/controlador_empresa_modificar_foto_docente.php' => ['id' => 'S_DNI', 'fotoactual' => 'S_FOTO'],
    ],

    'usuario_sesion' => [
        'controller/egresos/controlador_registrar_egreso.php' => ['usu'],
        'controller/egresos/controlador_modificar_egreso.php' => ['usu'],
        'controller/egresos/controlador_anular_egreso.php' => ['usu'],
        'controller/ingresos/controlador_registrar_ingresos.php' => ['usu'],
        'controller/ingresos/controlador_modificar_ingreso.php' => ['usu'],
        'controller/ingresos/controlador_anular_ingreso.php' => ['usu'],
        'controller/comunicados/controlador_registro_comunicados.php' => ['usu'],
        'controller/comunicados/controlador_modificar_comunicados.php' => ['usu'],
        'controller/atencion_enfermeria/controlador_registro_enfermeria.php' => ['idusu'],
        'controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php' => ['idusu'],
        'controller/atencion_psicologica/controlador_registro_psicologia.php' => ['idusu'],
        'controller/atencion_psicologica/controlador_modificar_atencion_psico.php' => ['idusu'],
    ],
];
