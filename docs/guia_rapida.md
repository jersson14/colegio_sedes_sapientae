# Guía rápida: asistencia, tareas, filtros y reportes

Para docentes, auxiliares y la dirección.

## Tomar asistencia: Registro mensual

Menú: **Horario y asistencia → Registro mensual**. Los docentes lo encuentran en **Notas y tareas → Asistencia de mis aulas**.

1. Elija el **aula** y el **mes**. Las flechas ‹ › cambian de mes.
2. Cada fila es un estudiante y cada columna un día de clase (lunes a viernes), agrupados por semana.
3. Haga **clic en la casilla** para marcar. Cada clic cambia la marca: **P** presente → **T** tardanza → **F** falta → **J** falta justificada → vacío.
4. **Más rápido:**
   - **Hoy: todos presentes** marca a todos en el día de hoy. Después cambie solo a los que faltaron o llegaron tarde.
   - Un clic en el **número del día** marca como presentes a todos los que no tienen marca ese día, o limpia el día.
   - Con el **teclado**: las flechas mueven la casilla, y las letras P, T, F o J marcan y bajan al siguiente estudiante. Supr borra la marca.
5. Pulse **Guardar**. El número naranja indica cuántos cambios faltan guardar.
6. **PDF** imprime la planilla del mes con el formato de la hoja de asistencia. **En blanco** imprime la planilla vacía para llenarla a mano. **Excel** la descarga para editarla.

El resumen de la derecha muestra, por estudiante, los P, T, F y J del mes y el % de asistencia. La fila final muestra cuántos asistieron cada día.

Los docentes solo ven las aulas donde enseñan. No se puede marcar días futuros.

## Revisar tareas: Registro de tareas

Menú: **Notas y tareas → Registro de tareas**.

- Elija el **curso** y el **periodo** (bimestre o todo el año). Cada columna es una tarea y cada fila un estudiante.
- Qué significa cada casilla:
  - Una **nota** en verde está aprobada; en rojo, desaprobada.
  - El icono de **bandeja** significa que el estudiante entregó y falta calificar.
  - El **reloj de arena** significa que la tarea sigue pendiente dentro del plazo.
  - La **X roja** significa que no entregó y el plazo ya venció.
- **Clic en la casilla** para ver lo que entregó el estudiante y poner la nota con una observación.
- **Clic en el nombre de la tarea** para ver su material (archivos y videos).
- Al final de cada fila están las tareas **entregadas** y el **promedio**. Se puede descargar en Excel o imprimir.

## Filtros y columnas en todas las listas

Todas las tablas del sistema (estudiantes, matrículas, pagos, tareas, etc.) tienen arriba a la derecha:

- **Filtros**: abre un filtro por columna. Las columnas con pocos valores (estado, grado, sexo) muestran una lista y las demás un cuadro para buscar. **Limpiar** quita todos los filtros.
- **Columnas**: elija qué columnas ver. El sistema lo recuerda en esa computadora.

**Excel, PDF e Imprimir** sacan **solo las columnas visibles y las filas filtradas**. Así cualquier lista se convierte en un reporte con los campos que usted necesita.

## Reporte de estudiantes a medida

Menú: **Reporte de estudiantes**. Lo usan la dirección, el auxiliar y la contadora.

1. **Campos**: marque los datos que quiere, en el orden en que deben aparecer: datos del estudiante, padres, matrícula, asistencia, tareas, pensiones y deuda. Las listas rápidas (*Lista básica*, *Contacto de padres*, *Seguimiento*, *Cobranza*) marcan varios de una vez.
2. **Filtros**: año, nivel, aula, sexo, nuevo o antiguo, búsqueda por nombre, o **solo con deuda vencida**.
3. **Generar reporte**, y luego Excel, PDF o Imprimir.

Con **Guardar como mi reporte** el sistema recuerda los campos elegidos en esa computadora; **Usar mi reporte** los vuelve a marcar.

## Respaldos (solo administrador)

Menú: **Configuración → Respaldos**.

1. **Generar respaldo ahora** crea una copia completa de la información del colegio en pocos segundos.
2. **Descargar** guarda esa copia en su computadora. Guárdela también **fuera del servidor**: en un USB, otra computadora o la nube.
3. La lista muestra todos los respaldos: los generados desde el sistema, los automáticos diarios y los hechos antes de cada actualización.

Genere un respaldo antes de cerrar el año, antes de una actualización y una vez al mes como mínimo. Para restaurar un respaldo llame al encargado técnico; los pasos están en `docs/despliegue.md`.

## Estudiantes y docentes: activar o desactivar

En **Estudiantes** y **Docentes**, el botón **Desactivar** impide que la persona ingrese al sistema (por ejemplo, un estudiante retirado o un docente que dejó el colegio). Sus notas, asistencia, pagos, cursos y contratos se conservan, y **Activar** le devuelve el acceso. Use **Eliminar** solo para registros creados por error: el sistema no deja eliminar a quien ya tiene historial.

## Envío de tareas: cuándo se puede reenviar

- El estudiante puede **enviar y volver a enviar** su tarea mientras el docente no la haya calificado.
- Una vez **calificada**, o si el docente **cerró** (finalizó) la tarea, ya no se aceptan envíos.
- Lo entregado después de la fecha de entrega se acepta, pero el docente lo ve marcado **Fuera de plazo**, tanto en la lista de envíos como en el registro de tareas.
- Cada docente solo puede ver y calificar las tareas, notas y exámenes de **sus** cursos.
