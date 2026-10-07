# Matriz de autorización por rol

> Generada en la Fase 0.2 y revisada con `tools/analizar_roles.py` (resuelve funciones JS como el
> navegador: los scripts de cada vista ganan a los globales). Análisis estático de alcanzabilidad:
> menú por rol en `view/index.php` → vistas → `<script>` → funciones JS → URL del controlador.
> Un manejador `$('#x').on(...)` solo cuenta si `#x` existe en el documento que carga el script.
> **Fuente de verdad:** la línea `exigir_rol(...)` de cada controlador. Este archivo es la foto de revisión.

Leyenda: **A** administrador · **D** docente · **E** estudiante · **X** auxiliar · **N** enfermera · **P** psicóloga.
"Todos" = cualquier usuario autenticado (sin `exigir_rol`).

**Pertenencia (IDOR):** las filas marcadas usan `core/pertenencia.php`: para el rol ESTUDIANTE
el id propio sale de la sesión y matrícula/aula/pago/envío/carpeta se verifican contra
`matricula.usu_id`. Pendiente: restringir al DOCENTE a sus aulas.

## (raíz)

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_actualizar_estado_solicitud.php` | A |  |
| `controlador_estadisticas_solicitudes.php` | A |  |
| `controlador_listar_solicitudes.php` | A |  |

## alumnos

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_alumnos.php` | A |  |
| `controlador_listar_alumnos.php` | A |  |
| `controlador_modificar_alumno.php` | A |  |
| `controlador_modificar_foto_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_registrar_alumno.php` | A |  |

## area

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_listar_area.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_modificar_area.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_registro_area.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |

## asignatura_docente

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_asig_docente.php` | A |  |
| `controlador_cargar_docente.php` | A X |  |
| `controlador_cargar_select_asignatura.php` | A |  |
| `controlador_detalle_asignatura_docente.php` | A |  |
| `controlador_eliminar_asignatura_docente.php` | A |  |
| `controlador_eliminar_asignatura_unica.php` | A |  |
| `controlador_listar_asignatura_docentes.php` | A |  |
| `controlador_listar_asignatura_docentes_filtro.php` | A |  |
| `controlador_listar_tabla_detalle_curso.php` | A |  |
| `controlador_modificar_asignatura_docente_unico.php` | A |  |

## asignaturas

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_grado.php` | A X |  |
| `controlador_eliminar_asignatura.php` | A |  |
| `controlador_listar_asignaturas.php` | A |  |
| `controlador_listar_asignaturas_filtro.php` | A |  |
| `controlador_modificar_asignaturas.php` | A |  |
| `controlador_registro_asignaturas.php` | A |  |

## asistencias

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_aula_id.php` | Todos |  |
| `controlador_editar_asistencia.php` | A X |  |
| `controlador_eliminar_asistencia.php` | A X |  |
| `controlador_listar_alumnos_asistencia.php` | A X |  |
| `controlador_listar_alumnos_por_grado.php` | A X |  |
| `controlador_listar_alumnos_totales.php` | A X |  |
| `controlador_listar_alumnos_totales_dia.php` | A X |  |
| `controlador_listar_alumnos_totales_dia_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_alumnos_totales_estu.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_asistencias.php` | A X |  |
| `controlador_listar_asistencias_fechas.php` | A X |  |
| `controlador_registro_asistencias.php` | A X |  |

## atencion_enfermeria

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_listar_atención_enfermeria.php` | A N | funciones JS homónimas entre módulos: datos de salud separados |
| `controlador_listar_atención_enfermeria_filtros.php` | A N | funciones JS homónimas entre módulos: datos de salud separados |
| `controlador_modificar_atencion_enferme.php` | A N | funciones JS homónimas entre módulos: datos de salud separados |
| `controlador_registro_enfermeria.php` | A N | funciones JS homónimas entre módulos: datos de salud separados |

## atencion_psicologica

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_matriculado_año.php` | A N P |  |
| `controlador_listar_atención_psicologica.php` | A P | funciones JS homónimas entre módulos: datos de salud separados |
| `controlador_listar_atención_psicologica_filtros.php` | A P | funciones JS homónimas entre módulos: datos de salud separados |
| `controlador_modificar_atencion_psico.php` | A P | funciones JS homónimas entre módulos: datos de salud separados |
| `controlador_registro_psicologia.php` | A P | funciones JS homónimas entre módulos: datos de salud separados |

## aula_horas

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_aula_horas.php` | A X |  |
| `controlador_eliminar_horas_curso_unico.php` | A X |  |
| `controlador_listar_aula_horas.php` | A X |  |
| `controlador_listar_aula_horas_filtro.php` | A X |  |
| `controlador_listar_hora_aula_id.php` | A X |  |
| `controlador_listar_hora_aula_id2.php` | A X |  |
| `controlador_modificar_aula_horas.php` | A X |  |
| `controlador_registro_aula_horas.php` | A X |  |

## aulas

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_nivel.php` | A X N P |  |
| `controlador_cargar_select_seccion.php` | A |  |
| `controlador_eliminar_rol.php` | A |  |
| `controlador_listar_aulas.php` | A |  |
| `controlador_listar_aulas_filtro.php` | A |  |
| `controlador_modificar_aula.php` | A |  |
| `controlador_registro_aulas.php` | A |  |

## año_escolar

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_año.php` | A |  |
| `controlador_listar_año_escolar.php` | A |  |
| `controlador_modificar_año_escolar.php` | A |  |
| `controlador_registro_año_escolar.php` | A |  |

## componentes

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_curso_id_detalle.php` | A |  |
| `controlador_eliminar_componente_curso_unico.php` | A |  |
| `controlador_eliminar_componentes.php` | A |  |
| `controlador_listar_componenetes.php` | A |  |
| `controlador_listar_componenetes_filtro.php` | A |  |
| `controlador_listar_componentes_curso.php` | A |  |
| `controlador_listar_tabla_componentes_curso.php` | A |  |
| `controlador_modificar_componentes.php` | A |  |
| `controlador_registro_componentes.php` | A |  |

## comunicados

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_comunicado.php` | A |  |
| `controlador_listar_comunicados.php` | A |  |
| `controlador_listar_comunicados2.php` | Todos |  |
| `controlador_listar_comunicados_filtro.php` | A |  |
| `controlador_modificar_comunicados.php` | A |  |
| `controlador_registro_comunicados.php` | A |  |

## docentes

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_especialidad.php` | A |  |
| `controlador_eliminar_docente.php` | A |  |
| `controlador_empresa_modificar_foto_docente.php` | A D | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_docentes.php` | A |  |
| `controlador_modificar_docente.php` | A |  |
| `controlador_registrar_docente.php` | A |  |

## egresos

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_anular_egreso.php` | A |  |
| `controlador_cargar_select_indicadores_egresos.php` | A |  |
| `controlador_listar_diferencia.php` | A |  |
| `controlador_listar_diferencia_filtro.php` | A |  |
| `controlador_listar_egresos_diversos.php` | A |  |
| `controlador_listar_egresos_diversos_filtros.php` | A |  |
| `controlador_modificar_egreso.php` | A |  |
| `controlador_registrar_egreso.php` | A |  |

## empleado

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_empleado_modificar_foto.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_listar_empleado.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_modificar_empleado.php` | A |  |
| `controlador_registro_empleado.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_total_empleados.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |

## empresa

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_empresa_modificar_foto.php` | A |  |
| `controlador_listar_empresa.php` | Todos |  |
| `controlador_modificar_empresa.php` | A |  |

## especialidad

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_especialidad.php` | A |  |
| `controlador_listar_especialidad.php` | A |  |
| `controlador_modificar_especialidad.php` | A |  |
| `controlador_registro_especialidad.php` | A |  |

## examenes

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_examenes.php` | A D |  |
| `controlador_listar_alumnos_examen.php` | A D |  |
| `controlador_listar_examenes.php` | A |  |
| `controlador_listar_examenes_estudiante_solo_pendiente.php` | Todos | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_examenes_filtro.php` | A |  |
| `controlador_listar_examenes_profesor.php` | A D |  |
| `controlador_listar_examenes_profesor_solo.php` | Todos |  |
| `controlador_modificar_estado_examen.php` | A D |  |
| `controlador_modificar_examen.php` | A D |  |
| `controlador_registro_examenes.php` | A D |  |

## horarios

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_curso_id_detalle.php` | A X |  |
| `controlador_cargar_select_horas.php` | A X |  |
| `controlador_eliminar_horario.php` | A X |  |
| `controlador_eliminar_horario_unico.php` | A X |  |
| `controlador_listar_horarios.php` | A X |  |
| `controlador_listar_horarios_editar.php` | A X |  |
| `controlador_listar_horarios_estudiante_id.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_horarios_estudiante_id_año.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_horarios_estudiante_todo.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_horarios_filtro.php` | A X |  |
| `controlador_listar_horarios_id.php` | A E X | IDOR: verifica pertenencia (estudiante) |
| `controlador_modificar_horarios.php` | A X | modal copiado en la vista del estudiante: no debe escribir horarios |
| `controlador_registro_horario_aula.php` | A X | modal copiado en la vista del estudiante: no debe escribir horarios |

## indicadores

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_indicador.php` | A |  |
| `controlador_listar_indicadores.php` | A |  |
| `controlador_modificar_indicador.php` | A |  |
| `controlador_registro_indicadores.php` | A |  |

## ingresos

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_anular_ingreso.php` | A |  |
| `controlador_cargar_select_indicadores.php` | A |  |
| `controlador_listar_ingresos_diversos.php` | A |  |
| `controlador_listar_ingresos_diversos_filtros.php` | A |  |
| `controlador_listar_ingresos_pensiones.php` | A |  |
| `controlador_listar_ingresos_pensiones_filtros.php` | A |  |
| `controlador_modificar_ingreso.php` | A |  |
| `controlador_registrar_ingresos.php` | A |  |

## matricula

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_año.php` | A D E X |  |
| `controlador_cargar_select_estudiante.php` | A |  |
| `controlador_eliminar_matricula.php` | A |  |
| `controlador_listar_matriculas.php` | A |  |
| `controlador_listar_matriculas_filtro.php` | A |  |
| `controlador_modificar_matrícula.php` | A |  |
| `controlador_registro_matriculas.php` | A |  |
| `controlador_traernivel.php` | A X |  |
| `controlador_traertipo.php` | A |  |

## nivel_academico

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_nivel_academico.php` | A |  |
| `controlador_listar_nivel_academico.php` | A |  |
| `controlador_modificar_niveaca.php` | A |  |
| `controlador_registro_nivel_aca.php` | A |  |

## notas

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_años_por_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_cargar_periodos.php` | A D |  |
| `controlador_cargar_periodos2.php` | A |  |
| `controlador_cargar_periodos_cargados.php` | A |  |
| `controlador_cargar_periodos_cargados_estudiante.php` | A D E | IDOR: verifica pertenencia (estudiante) |
| `controlador_cargar_periodos_cargados_profesor.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_cargar_select_grado_profesor.php` | Todos |  |
| `controlador_editar_notas.php` | A |  |
| `controlador_editar_notas_padre.php` | A |  |
| `controlador_listar_criterios_notas.php` | A |  |
| `controlador_listar_criterios_notas_mostrar.php` | A |  |
| `controlador_listar_criterios_notas_mostrar_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_criterios_notas_mostrar_padres.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_criterios_notas_mostrar_profesor.php` | A D |  |
| `controlador_listar_criterios_notas_profesor.php` | A D |  |
| `controlador_listar_matriculas_filtro.php` | A |  |
| `controlador_listar_matriculas_filtro_alumnos.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_matriculas_filtro_profesor.php` | A D |  |
| `controlador_registro_notas.php` | A D |  |
| `controlador_registro_notas_padres.php` | A |  |

## pago_pension

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_pension.php` | A |  |
| `controlador_detalle_pago_pension.php` | A |  |
| `controlador_eliminar_pago_pension.php` | A |  |
| `controlador_listar_matriculas.php` | A |  |
| `controlador_listar_matriculas_año_id.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_matriculas_filtro.php` | A |  |
| `controlador_listar_matriculas_id.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_pension_todo.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_tabla_pagos.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_modificar_pago_solo.php` | A |  |
| `controlador_traermonto.php` | A |  |

## pensiones

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_pensiones.php` | A |  |
| `controlador_listar_pensiones.php` | A |  |
| `controlador_listar_pensiones_filtro.php` | A |  |
| `controlador_modificar_pension.php` | A |  |
| `controlador_registro_pensiones.php` | A |  |

## periodos

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_periodo_unico.php` | A |  |
| `controlador_eliminar_periodos.php` | A |  |
| `controlador_listar_periodo_año.php` | A |  |
| `controlador_listar_periodos.php` | A |  |
| `controlador_modificar_periodos.php` | A |  |
| `controlador_registro_periodos.php` | A |  |

## personal_administrativo

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_roles.php` | A |  |
| `controlador_eliminar_personal.php` | A |  |
| `controlador_listar_personaladmin.php` | A |  |
| `controlador_modificar_personal.php` | A |  |
| `controlador_registrar_personal_administrativo.php` | A |  |

## roles

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_roles.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_eliminar_rol.php` | A |  |
| `controlador_listar_roles.php` | A |  |
| `controlador_modificar_rol.php` | A |  |
| `controlador_registro_roles.php` | A |  |

## seccion

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_eliminar_seccion.php` | A |  |
| `controlador_listar_seccion.php` | A |  |
| `controlador_modificar_seccion.php` | A |  |
| `controlador_registro_seccion.php` | A |  |

## tareas

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_curso_id_detalle_estudiante.php` | Todos | IDOR: verifica pertenencia (estudiante) |
| `controlador_cargar_curso_id_detalle_profesor.php` | Todos |  |
| `controlador_cargar_select_cursos_docente.php` | A D X |  |
| `controlador_cargar_select_grado_estudiante.php` | Todos | IDOR: verifica pertenencia (estudiante) |
| `controlador_descargar_tarea.php` | A D E X | Fase 0.3-B: sustituye al listado público de la carpeta · IDOR: verifica pertenencia (estudiante) |
| `controlador_eliminar_tarea.php` | A D X |  |
| `controlador_listar_tabla_envio_tareas.php` | A D X |  |
| `controlador_listar_tareas.php` | A X |  |
| `controlador_listar_tareas_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_tareas_estudiante_solo.php` | A D E | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_tareas_estudiante_solo_pendiente.php` | Todos | IDOR: verifica pertenencia (estudiante) |
| `controlador_listar_tareas_filtro.php` | A X |  |
| `controlador_listar_tareas_profesor.php` | A |  |
| `controlador_listar_tareas_profesor_solo.php` | Todos |  |
| `controlador_modificar_estado_tarea.php` | A D X |  |
| `controlador_modificar_tareas.php` | A D X |  |
| `controlador_modificar_tareas_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `controlador_registro_calificacion.php` | A D X |  |
| `controlador_registro_tareas.php` | A D X |  |
| `controlador_registro_tareas_estudiante.php` | A E | IDOR: verifica pertenencia (estudiante) |

## tipo_documento

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_listar_tipo.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_modificar_tipo.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_registro_tipo_documento.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |

## usuario

| Controlador | Roles | Nota |
|---|---|---|
| `controlador_cargar_select_area.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_cargar_select_area_solo.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_cargar_select_rol.php` | A |  |
| `controlador_listar_usuario.php` | A |  |
| `controlador_listar_usuario_filtro.php` | A |  |
| `controlador_modificar_usuario.php` | A |  |
| `controlador_modificar_usuario_contra.php` | A |  |
| `controlador_modificar_usuario_estatus.php` | A |  |
| `controlador_registro_usuario.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_total_administrativos.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_docentes.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_egresos.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_enfermeria.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_estudiantes.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_ingresos.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_psicologia.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_total_usuarios.php` | A | totales del panel: las tarjetas solo existen para el administrador |
| `controlador_traer_datos.php` | Todos | IDOR: verifica pertenencia (estudiante) |
| `controlador_traer_notificacion_tramite.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_traer_seguimiento.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `controlador_traer_seguimiento_detalle.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |

## view/MPDF/REPORTE (reportes PDF, GET)

| Reporte | Roles | Nota |
|---|---|---|
| `boleta_pago.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `cedula.php` | A |  |
| `ficha_seguimiento.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `ficha_seguimiento_automatico.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |
| `horario.php` | A E X | IDOR: verifica pertenencia (estudiante) |
| `kardex.php` | A E | IDOR: verifica pertenencia (estudiante) |
| `notas_general.php` | A |  |
| `notas_por_bimestre.php` | A |  |
| `pago.php` | A |  |
| `ticket_tramite.php` | A | sin uso desde la UI (restos de otro sistema): solo administrador |

## Públicos (sin guard)

- `controlador_solicitudes.php`
- `usuario/controlador_cerrar_sesion.php`
- `usuario/controlador_iniciar_sesion.php`
