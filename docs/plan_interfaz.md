# Plan de mejora: interfaz, formularios, funcionalidad y lógica

**Objetivo:** que el sistema se sienta como *el sistema del colegio*: con su escudo y sus colores, fácil para todos (dirección, docentes, personal, estudiantes y familias) y consistente en todas las pantallas.

**Principios** (ver `PRODUCT.md`):

1. Cada persona entra y ve primero lo que tiene que hacer hoy.
2. Textos claros en español, sin tecnicismos.
3. El colegio es el protagonista.
4. Un mismo lenguaje visual en todo el sistema.
5. Nada se pierde sin confirmación.

---

## Ya hecho (Fase 12, 2026-10-25)

| Mejora | Dónde |
|---|---|
| **Apariencia configurable** | *Configuración → Apariencia*: color principal, color de acento, estilo del menú (del color, oscuro o claro), lema y escudo. Hay 5 combinaciones sugeridas. Vista previa en vivo en todo el sistema y guardado para todos. El texto sobre cada color se elige solo para que siempre se lea. |
| **Tema aplicado a todo el sistema** sin tocar las 51 pantallas | `css/tema.css` recolorea el menú, los botones, las tarjetas y la paginación, más los colores fijos heredados: 109 encabezados de tabla y 103 encabezados de ventana. |
| **Identidad del colegio** | El escudo y el nombre configurados aparecen en el menú lateral (antes era una imagen fija), en el título de la pestaña, en el ícono del navegador y en el pie de página (con el año actual). |
| **Panel de inicio del administrador**, rediseñado | Encabezado con el escudo, la fecha y el bimestre en curso. Accesos rápidos. **Línea del año escolar** con los bimestres y la marca "Hoy". Comunidad educativa. Asistencia del día con las aulas que aún no registran. Caja. Pendientes: deuda vencida, tareas por calificar, salud y último respaldo. Comunicados recientes. Ficha de la institución. Se adapta al celular. |
| **Pantalla de ingreso** | Escudo, nombre y lema con los colores del colegio, y textos de ayuda. **Se quitó el cuadro que mostraba los usuarios y contraseñas de todos los roles.** |

---

## Por hacer, en orden de impacto

### Etapa 1: paneles de inicio por rol (alto impacto, esfuerzo medio)

Cada rol debería abrir el sistema en "lo de hoy", igual que el administrador:

- **Docente:**
  - las clases de hoy según su horario;
  - las aulas con asistencia pendiente, con un botón directo a su planilla;
  - las tareas entregadas por calificar;
  - el próximo examen;
  - los comunicados.
- **Estudiante y familia:**
  - el horario de hoy;
  - las tareas pendientes ordenadas por fecha de entrega, con la marca de las vencidas;
  - las últimas notas;
  - las pensiones pendientes y su vencimiento;
  - los comunicados.
- **Auxiliar:** la asistencia del día por aula (las que faltan) y los horarios.
- **Enfermería y psicología:** las atenciones recientes y un botón "Nueva atención".
- **Contadora:** su panel ya existe (Fase 7). Solo falta pasarlo al mismo lenguaje visual del panel nuevo.

Se reutilizan los componentes del panel nuevo (`css/inicio.css`): encabezado del colegio, línea del año escolar, tarjetas de trabajo y listas de pendientes.

### Etapa 2: menú y encabezados de pantalla (alto impacto, esfuerzo bajo)

- **Menú organizado por tareas**: Inicio · Estudiantes y matrícula · Académico (notas, tareas, exámenes, horarios) · Asistencia · Pensiones y caja · Comunicación · Configuración.
- Corregir los nombres poco claros: "Academico" (sin tilde), "Asignaturas y examen", "Sección y grado".
- **Buscador en el menú**, porque el administrador tiene 43 pantallas.
- **Encabezados uniformes:** 36 pantallas se titulan "MANTENIMIENTO DE …". Deben decir lo que la persona hace ("Estudiantes", "Matrículas", "Pagos de pensión"), en tipo oración y no todo en mayúsculas, con el botón principal ("Nueva matrícula") siempre en el mismo lugar.

### Etapa 3: legibilidad de los datos (alto impacto, esfuerzo medio)

- Los nombres se guardan en MAYÚSCULAS (95 controladores usan `strtoupper`). **Mostrarlos en formato título** ("Ana Quispe Rojas") en pantallas y documentos, sin cambiar lo guardado.
- Fechas siempre en el formato `dd/mm/aaaa` y montos con `formatoMoneda()`. Revisar las tablas que aún muestran `2025-12-31 10:40:50`.
- Estados como **etiquetas consistentes**, el mismo color para el mismo significado en todo el sistema: activo / inactivo, pendiente / enviado / calificado, vigente / vencido.
- Tablas vacías con un mensaje útil y la acción siguiente ("Aún no hay tareas. Crear la primera").

### Etapa 4: formularios (impacto alto, esfuerzo medio)

Situación actual:

- Hay 477 campos en las pantallas y solo 82 tienen marca de obligatorio. Casi toda la validación se hace a mano, campo por campo, con un mensaje general ("Llene todos los campos").
- 80 campos de fecha no tienen límites, así que se acepta un nacimiento en el futuro o una entrega en el pasado.
- 118 ventanas emergentes, algunas muy largas.

**Un solo componente de formulario para todo el sistema** (`js/formulario.js`):

- Marca de obligatorio (*) y mensaje **junto al campo que falla**: "Falta el documento del padre", no "Llene todos los campos".
- Validación mientras se escribe: documento según el país, celular, correo, montos y notas según la escala. Hoy ya existen `documentoValido()`, `notaValida()` y `formatoMoneda()`; falta conectarlas a todos los formularios.
- El botón Guardar se bloquea mientras se envía, para no registrar dos veces con un doble clic.
- Aviso al cerrar con cambios sin guardar: "¿Descartar los cambios?".
- La tecla Enter guarda y Esc cancela. El cursor empieza en el primer campo.
- Los errores del servidor (400 o 403) se muestran sobre el campo correspondiente cuando se puede.

**Límites con sentido en las fechas:**

- Nacimiento: entre 2 y 25 años atrás para estudiantes y entre 18 y 80 para personal.
- Entrega de tareas y fecha de examen: desde hoy.
- Pagos: no más adelante que hoy.
- Periodos: dentro de las fechas del año escolar.

**Formularios largos convertidos en pantallas paso a paso:**

- **Matrícula:** Estudiante → Padres o apoderado → Aula y vacantes → Pagos y beca → Resumen con lo que se cobrará.
- **Nuevo estudiante y nuevo docente:** primero los datos personales y luego el usuario; la contraseña inicial la genera el sistema y se puede imprimir.

**Ayudas para escribir menos:**

- Al escribir el documento del padre o la madre, si ya existe por un hermano matriculado, ofrecer sus datos para no repetirlos.
- Listas con buscador (Select2) en todos los selectores largos: estudiantes, docentes, cursos.
- Valores por defecto inteligentes: el año escolar activo, el bimestre en curso, la fecha de hoy y el aula usada la última vez.

**Mensajes:** un aviso breve en la esquina para "Guardado", y las ventanas de confirmación solo para decisiones (eliminar, anular, desactivar).

### Etapa 4b: funcionalidad nueva (impacto alto, esfuerzo medio a alto)

- **Cambio de año escolar asistido:**
  - cerrar el año;
  - pasar a los estudiantes al grado siguiente (los que aprobaron), marcándolos como "antiguos";
  - crear la matrícula del año nuevo con un clic por aula;
  - generar las pensiones y copiar horarios y cursos de docentes como borrador.

  Hoy todo esto se hace a mano.
- **Libreta de notas por bimestre:** promedio por curso con la escala configurada, orden de mérito por aula, y PDF de la libreta con el escudo para entregar a las familias.
- **Avisos a las familias** por correo, y por WhatsApp más adelante:
  - falta del día;
  - tarea vencida sin entregar;
  - pensión por vencer o vencida;
  - nuevo comunicado.

  Cada aviso con su interruptor en Configuración.
- **Importar estudiantes desde Excel**: plantilla descargable, vista previa con errores marcados antes de grabar.
- **Buscador general** en la barra superior: escribir un nombre o documento y llegar a la ficha del estudiante.
- **Ficha única del estudiante:** en una sola pantalla, sus datos, padres, matrículas, asistencia, notas, tareas, pagos y atenciones de salud, con un botón "Imprimir ficha".
- **Cuentas:**
  - "Olvidé mi contraseña": la dirección la restablece y el sistema genera una nueva.
  - Cambio obligatorio de la contraseña inicial en el primer ingreso.
  - Mínimo de 8 caracteres. Hoy no hay un largo mínimo.
- **Una sola pantalla de asistencia:** hoy conviven la asistencia diaria antigua y el registro mensual. Dejar el registro mensual como la única forma de registrar, con un modo "solo hoy" para el celular.
- **Comunicados con lectura:** saber qué familias lo vieron y volver a avisar a las que no.

### Etapa 4c: lógica y datos (impacto alto, esfuerzo bajo a medio)

Se encontró en la base de datos:

- **Registro de estudiantes y de cursos del docente:** `SP_REGISTRAR_ALUMNOS` y `SP_REGISTRAR_ASIGNATURA_DOCENTE` toman el último código con `SELECT MAX(id)` después de insertar, y sin transacción. Si dos personas registran al mismo tiempo, los padres o los cursos pueden quedar unidos al registro equivocado. Corregir con `LAST_INSERT_ID()` y una transacción. Solo 6 procedimientos usan transacción hoy.
- **Correos:** el servidor no validaba el correo y el navegador rechazaba correos válidos con `+` (como `ana+padres@gmail.com`).
- **Datos duplicados:** avisar si ya existe un estudiante o docente con el mismo nombre y fecha de nacimiento, aunque el documento sea distinto (suele ser un error de tipeo).
- **Consistencia por año escolar:** periodos sin solaparse y dentro del año, y tareas y exámenes con fechas dentro del bimestre.
- **Integridad al eliminar:** revisar cada "Eliminar" que queda (aulas, asignaturas, periodos, comunicados) para que avise cuando hay datos relacionados, como ya hacen estudiantes y docentes, y ofrecer Desactivar.
- **Auditoría ampliada:** hoy registra las tablas de dinero y configuración. Sumar notas y asistencia, para saber quién cambió una nota o una falta y cuándo.
- **Pruebas:** cada corrección de lógica con su prueba en `tests/` (hoy hay 320), como en las fases anteriores.

### Etapa 4d: lo que aún no es configurable

- Permisos por rol desde una pantalla, con casillas por rol, en vez del archivo `controller/_permisos.php`.
- En una pantalla de Configuración: tamaño máximo de los videos, intentos de ingreso antes del bloqueo, días de clase (incluir sábado) y códigos o nombres de asistencia.
- Orden y nombres del menú.
- PDF con el escudo y los colores del colegio (ver Etapa 6).

### Etapa 5: celular (impacto alto para docentes y familias, esfuerzo medio)

- En el celular, las tablas largas se muestran como **tarjetas**: nombre, estado y acción.
- Para el estudiante y la familia, una **barra inferior** con Inicio · Tareas · Notas · Pagos · Avisos.
- Botones de al menos 44 px de alto. Los botones con solo ícono llevan texto o etiqueta: hoy hay 17 sin descripción.

### Etapa 6: documentos con la identidad del colegio (impacto medio, esfuerzo bajo)

- Recibos, boletas, libreta de notas, planilla de asistencia y reportes PDF con el **escudo y el color principal configurados**. Hoy usan colores fijos (`#0A5D86`) y varias fuentes distintas (freeserif, dejavusanscondensed, aegean, Arial).
- Un encabezado común para todos los PDF (`utilitario/reporte_pdf.php` ya lo tiene para los reportes financieros).

### Etapa 7: consistencia fina (impacto medio, esfuerzo bajo)

- Quitar los estilos en línea heredados (`font-family:cooper`, `arial black`, colores sueltos) y llevarlos a clases del tema.
- Un solo juego de íconos (Font Awesome 5) con el mismo peso.
- Ayuda contextual corta en las pantallas complejas, enlazando a `docs/guia_rapida.md`.

---

## Hoja de ruta sugerida

**Avance:** paso 1 hecho el 2026-10-26 (migración 018, `tests/pruebas_fase13.sh`):

- Estudiante y padres se guardan en una transacción con `LAST_INSERT_ID()`; la asignación de cursos usa el mismo método.
- Auditoría de notas, asistencia, tareas, estudiantes, docentes, periodos y año escolar (24 triggers).
- Correos validados en el servidor.
- Aviso "¿Es otra persona?" ante posibles duplicados.
- Periodos dentro del año y sin cruces.
- Un manejador común de errores: eliminar algo con datos relacionados explica el motivo en lugar de dar un error de PHP. Cubre las 24 funciones de eliminar que no estaban protegidas.

Paso 2 hecho el 2026-10-26 (`js/formulario.js`, `tests/navegador/prueba_formularios.sh`). Funciona en **todas las ventanas** sin tocar cada pantalla:

- Cada obligatorio (*) vacío se marca junto a su campo, también en pestañas ocultas, que se abren solas.
- Fechas con límites: nacimiento entre 2 y 100 años; tareas y exámenes nuevos desde hoy; pagos, adelantos, depósitos y cierres no a futuro. Escribirla a mano fuera de rango también se marca.
- Un solo envío aunque se pulse varias veces; el botón muestra que está enviando.
- Al cerrar una ventana con cambios sin guardar, se pregunta.
- Enter guarda y el cursor empieza en el primer campo.
- Las ventanas que arman listas con "Agregar" mantienen su propia validación.

Paso 3 hecho el 2026-10-26 (migración 019, `tests/pruebas_fase13_cuentas.sh`):

- Contraseñas de al menos 8 caracteres y distintas del usuario, en todos los lugares donde se crean: docentes, personal, usuarios, matrícula de un estudiante nuevo y restablecer.
- La contraseña que pone el administrador es **temporal**. En el primer ingreso solo se muestra la pantalla "Cree su contraseña", y el servidor bloquea todo lo demás hasta cambiarla. Los usuarios existentes no quedan obligados.
- **"Cambiar mi contraseña"** en el menú del usuario, para todos los roles.
- En Usuarios, botón **Generar**: crea una contraseña temporal fácil de dictar y la muestra al guardar para entregarla.
- Corregido de paso: la contadora no tenía barra superior, así que no tenía en el menú de usuario la opción "Cerrar Sesión" (y ahora tampoco tendría "Cambiar mi contraseña").

Paso 4 hecho el 2026-10-26 (`view/inicio/panel_rol.php`, `js/panel_rol.js`, `tests/pruebas_fase13_paneles.sh`). Cada rol entra a "lo de hoy", con el encabezado del colegio, la línea del año escolar y sus accesos rápidos:

- **Docente:** clases de hoy según el horario (la que está en curso, resaltada); sus aulas y si ya registró la asistencia; tareas con entregas por calificar; próximos exámenes; comunicados.
- **Estudiante:** clases de hoy; asistencia del mes; tareas pendientes (las vencidas en rojo); próximos exámenes; últimas notas de tareas; pensiones por pagar; comunicados de su aula y generales.
- **Auxiliar:** asistencia de hoy por aula, con cuántos estudiantes se registraron en cada una.
- **Enfermería y psicología:** atenciones de hoy y del mes, y las más recientes.
- **Contadora:** el mismo encabezado y línea del año escolar sobre su panel contable.
- Corregido de paso: el panel de la contadora no cerraba el área de contenido y el pie de página quedaba corrido.

Paso 5 hecho el 2026-10-26 (`js/menu.js`, `tests/pruebas_fase13_menu.sh`):

- El menú de la dirección quedó ordenado por tareas en 8 secciones: Estudiantes y matrícula, Académico, Asistencia, Pensiones y caja, Personal, Comunicación y salud, Organización del colegio y Configuración. Siguen las 44 pantallas; **Usuarios** estaba oculto y ahora se ve.
- **Buscador en el menú** para todos los roles. No importan las tildes, Enter abre la primera coincidencia y Esc limpia. La opción abierta queda marcada y en el celular el menú se cierra al elegir.
- Las 53 pantallas tienen título en tipo oración, una línea que explica para qué sirven y la ruta "Inicio › pantalla". Antes la ruta decía "MENU" y apuntaba fuera del sistema. Ya no queda ningún "MANTENIMIENTO DE …".
- Para el estudiante y el docente las opciones dicen de quién son: "Mis notas", "Mi horario", "Mis pagos", "Notas de mis cursos".
- Corregido de paso: en Solicitudes, el refresco de cada 30 segundos seguía activo al salir de la pantalla y se sumaba uno nuevo en cada visita. Además, Asignaturas llamaba a una función de otra pantalla.

Paso 6 hecho el 2026-10-26 (`js/ficha.js`, `model/model_ficha.php`, `tests/pruebas_fase13_ficha.sh`):

- **Buscador general** en la barra superior para la dirección, el auxiliar y la contadora. Busca por nombre, apellidos (en cualquier orden y sin importar tildes) o el inicio del documento. Se abre con la tecla **/**; las flechas eligen y Enter abre la ficha.
- **Ficha del estudiante** en una sola pantalla: identidad, datos personales, padres, todas sus matrículas, asistencia del año, notas por curso y periodo (promedio de los criterios, lo desaprobado en rojo), tareas, pensiones mes a mes con la deuda vencida, pagos y atenciones de salud. Si estudió varios años, un selector cambia de año.
- Cada rol ve solo lo de su trabajo, y lo decide el servidor: la contadora ve datos, padres, pensiones y pagos; el auxiliar ve datos, padres, asistencia, notas y tareas; las atenciones de salud solo las ve la dirección.
- **Imprimir ficha** saca solo la ficha, con el escudo y el nombre del colegio.
- En la lista de Estudiantes, el botón "Mostrar" (una ventana de solo lectura) pasa a ser **Ficha**.

Paso 7 hecho el 2026-10-26 (`js/registro_asistencia.js`, `tests/pruebas_fase13_asistencia.sh`):

- El **Registro de asistencia** es la única pantalla para registrar. Tiene dos modos: **Por día** (botones grandes P, T, F, J por estudiante, pensado para el celular, donde se abre así) y **Mes completo** (la hoja de siempre). Recuerda el último modo en cada equipo.
- **Observación** por estudiante y día ("cita médica", "llegó con su mamá"), que antes solo tenía la pantalla antigua. En la hoja del mes se ve como una esquina de color en la casilla.
- Desde los paneles de inicio, tocar un aula abre su asistencia del día directamente.
- Se retiró la antigua "Asistencia del día" con sus 7 controladores de registrar, editar y eliminar: el auxiliar ya no tiene permiso para ellos y no quedan dos formas de hacer lo mismo. El **Reporte de asistencias** sigue igual, con su propio script (`js/asistencia_reportes.js`) y sin las ventanas que había copiado de la pantalla antigua.

Paso 8 hecho el 2026-10-26 (`js/matricula_pasos.js`, `controller/matricula/controlador_contexto.php`, `tests/pruebas_fase13_matricula.sh`):

- **Matrícula en 5 pasos:** Estudiante → Padres → Aula y vacantes → Pagos y beca → Resumen. Cada paso se valida antes de seguir y el resumen muestra lo que se cobra hoy, las pensiones del año con la beca aplicada y el usuario que se entregará.
- Valores sugeridos: el año escolar activo y los montos de la última matrícula del mismo nivel. La beca se registra en el mismo flujo.
- **Corregido:** la lista de estudiantes solo mostraba a los que nunca se habían matriculado (`alum_estatus = 'NO'`), así que un estudiante antiguo no podía volver a matricularse el año siguiente. Ahora la lista depende del año: todos los que aún no tienen matrícula en ese año.
- **Padres ya registrados:** al escribir el documento del padre o la madre en Estudiantes, si ya existe por un hermano se ofrecen sus datos y los del otro padre de la familia, tomando el dato no vacío más reciente.
- De paso: Enter en una ventana solo pulsa un botón de guardar **visible**.

Paso 9 hecho el 2026-10-31 (`utilitario/libreta.php`, `js/libretas.js`, `view/MPDF/REPORTE/libreta.php`, `tests/pruebas_fase13_libreta.sh`):

- **Libretas de notas** reemplaza a "Boletas de notas": por aula y periodo (o todo el año), con la nota de cada curso, el promedio, los cursos desaprobados y el **orden de mérito** (los empates comparten el puesto; quien aún no tiene notas va al final y sin puesto).
- Los cálculos están en un solo lugar del servidor, así la pantalla y el PDF muestran siempre lo mismo. Siguen la escala configurada, sea numérica o con letras.
- **PDF de la libreta** con el escudo, el nombre, el lema y el color del colegio: uno por estudiante, todos los del aula en un solo archivo y el orden de mérito. El estudiante descarga la suya desde Mis notas y no puede abrir la de otro.
- Se retiraron los dos PDF antiguos de boletas, que tenían escritos a mano "DRE: APURÍMAC" y "UGEL Abancay".

Paso 10 hecho el 2026-10-31 (`js/legibilidad.js`, `tests/pruebas_fase13_legibilidad.sh`). Se aplica a **todas las tablas** del sistema sin tocar cada pantalla, y solo cambia lo que se ve:

- Nombres, direcciones, cursos y grados guardados en mayúsculas se muestran en formato título. Los conectores van en minúscula, los números romanos en mayúscula ("IV Bimestre") y también la sección del aula ("Primer Grado A").
- Fechas `2025-12-31 10:40:50` o `28-12-2025` se muestran como `31/12/2025 10:40` y `28/12/2025`; la fecha vacía de MySQL (`0000-00-00`), como un guion.
- Estados con el mismo color para el mismo significado: verde, ámbar, azul, rojo y gris. Las columnas "Estado" en texto plano pasan a ser etiquetas.
- Tablas vacías: "Aún no hay registros aquí" y el botón de crear de la pantalla.
- El orden, los filtros y las exportaciones siguen usando los datos originales.

Paso 11 hecho el 2026-10-31 (`utilitario/reporte_pdf.php`, `tests/pruebas_fase13_pdf.sh`):

- **Los 11 reportes PDF** usan la misma identidad: escudo, nombre, lema y color del colegio en un encabezado común, una sola fuente (DejaVu Sans, con tildes y ñ) y tablas con el color configurado. Los tickets angostos (recibo, constancia) usan la versión compacta.
- Fuera los colores fijos (`#0A5D86`, gris, amarillo, magenta) y las regiones escritas a mano ("Abancay-Apurímac-Perú"). Se retiró `pago.php`, que nadie usaba y tenía la región fija.
- La constancia de pago (`boleta_pago.php`) se reescribió con consultas parametrizadas y sin `utf8_encode`, que estropeaba las tildes.
- Nombres, cursos y grados en formato título, y fechas en dd/mm/aaaa, también en los documentos.
- **Corregido:** la cédula de matrícula y el kardex de pagos salían vacíos para los estudiantes sin usuario del sistema, porque los datos del colegio se buscaban a través de ese usuario.

Paso 12 hecho el 2026-10-31 (`js/legibilidad.js`, `css/tema.css`, `tests/navegador/prueba_celular.sh`):

- **Tablas como tarjetas** en pantallas angostas, en todas las tablas: el título es la columna más descriptiva (nombre, tema, curso o grado), cada dato lleva su etiqueta y las acciones van al pie. La fila desplegable de la extensión *responsive* se reemplaza por la tarjeta completa. Una tabla puede excluirse con `sin-tarjetas`.
- **Barra inferior** para el estudiante (Inicio · Tareas · Notas · Pagos · Avisos) y para el docente (Inicio · Asistencia · Tareas · Notas · Exámenes). Solo en el celular.
- Botones de **44 px** (40 px los pequeños) y **etiqueta automática** para los botones que solo tienen ícono (Eliminar, Editar, Agregar, Quitar, Anular…), también en ventanas y pantallas que se cargan después.
- Las columnas fijas angostas de filtros y formularios se apilan en el celular, y la barra superior muestra solo la foto del usuario, con una imagen de respaldo si el archivo no existe.
- **Corregido:** en el celular la capa de legibilidad no se aplicaba, porque el selector de columnas visibles de DataTables excluye las que esconde la extensión *responsive*.

Paso 13 hecho el 2026-10-31 (`model/model_cambio_anio.php`, `js/cambio_anio.js`, `tests/pruebas_fase13_cambio_anio.sh`):

- **Regla de promoción:** solo se propone promover a quien **aprobó todos sus cursos** (nota final del año según la libreta del paso 9). Con desaprobados o sin notas, decide la dirección estudiante por estudiante: repite, promover (por ejemplo, tras la recuperación), no continúa o decidir luego. Promover a alguien que no aprobó todo se marca y se advierte en el resumen.
- **Aula siguiente** deducida del nombre del grado (con cambio de nivel: 5 años → primer grado de primaria; sexto → primero de secundaria), con la misma sección si existe y editable por aula. El último grado egresa; el nivel comodín "TODOS" no entra en la progresión.
- **Copias** al año nuevo, solo si aún no las tiene: periodos (fechas corridas al año nuevo), pensiones, cursos de cada docente con sus criterios, y horas y horarios.
- Todo en **una transacción**. Las matrículas se crean sin cobro, como el registro normal con montos en cero. Repetir el proceso no duplica nada. Opcional: activar el año nuevo y cerrar los periodos del anterior.

El orden busca primero lo que evita errores en los datos, luego lo que más tiempo ahorra cada día y al final lo estético.

| # | Qué | Etapa | Esfuerzo | Por qué en este orden |
|---|---|---|---|---|
| 1 ✅ | Correcciones de lógica: registro sin `MAX(id)`, transacciones, correos, duplicados, periodos, auditoría, errores claros | 4c | Bajo | Evita datos cruzados entre registros |
| 2 ✅ | Componente de formulario y límites de fechas | 4 | Medio | Menos errores al cargar datos en todo el sistema |
| 3 ✅ | Cuentas: contraseña inicial, mínimo de 8 caracteres, restablecer | 4b | Bajo | Seguridad antes de abrir el sistema a las familias |
| 4 ✅ | Paneles de inicio por rol | 1 | Medio | Cada persona ve lo suyo al entrar |
| 5 ✅ | Menú por tareas y encabezados claros | 2 | Bajo | Encontrar todo más rápido |
| 6 ✅ | Ficha única del estudiante y buscador general | 4b | Medio | La consulta más frecuente de la dirección |
| 7 ✅ | Una sola pantalla de asistencia | 4b | Bajo | Hoy hay dos formas de hacer lo mismo |
| 8 ✅ | Matrícula paso a paso y reutilizar los datos de los padres | 4 | Medio | El formulario más largo y más usado en marzo |
| 9 ✅ | Libreta de notas por bimestre | 4b | Medio | Documento clave para las familias |
| 10 ✅ | Legibilidad: nombres en formato título, fechas y estados | 3 | Medio | Se lee mejor en todas las pantallas |
| 11 ✅ | PDF con la identidad del colegio | 6 | Bajo | Recibos y libretas con el escudo |
| 12 ✅ | Celular: tarjetas y barra inferior | 5 | Medio | Docentes y familias usan el teléfono |
| 13 ✅ | Cambio de año escolar asistido | 4b | Alto | Se usa una vez al año, pero ahorra días de trabajo |
| 14 | Importar desde Excel, avisos a las familias y comunicados con lectura | 4b | Alto | Necesitan definir el correo o WhatsApp del colegio |
| 15 | Permisos y parámetros desde una pantalla | 4d | Medio | Configurable sin el técnico |
| 16 | Consistencia fina | 7 | Bajo | Pulido final |

---

## Cómo se mide

- Cada rol completa su tarea diaria principal desde el inicio en **2 clics o menos**: tomar asistencia, calificar, cobrar o ver las tareas.
- Ninguna pantalla con textos en inglés, colores fijos o títulos "MANTENIMIENTO DE".
- Revisión en celular (390 px) de cada pantalla de docentes y familias.
- Las pruebas del navegador (`tests/navegador/revisar_roles.sh`) siguen en 0 errores.
- Formularios: ningún "Llene todos los campos"; cada error se muestra junto a su campo. Ninguna fecha imposible aceptada. Ningún registro doble por doble clic (prueba automática).
- Lógica: ningún procedimiento usa `SELECT MAX(id)` para obtener el registro recién creado. Toda corrección queda con su prueba en `tests/`.
- Funcionalidad: la matrícula de un estudiante nuevo, con padres, aula y pagos, se completa en menos de 3 minutos.
