# Plan de mejora de la interfaz

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

### Etapa 4: formularios y ventanas (impacto medio, esfuerzo medio)

- **118 ventanas emergentes.** Las largas (matrícula, registro de estudiante, docente) deberían ser pantallas o paneles laterales, con pasos ("Datos del estudiante → Padres → Aula y pagos").
- Validaciones junto al campo, no en un mensaje aparte. Obligatorios marcados de una sola forma. Botones "Guardar" y "Cancelar" siempre en el mismo orden y lugar.
- Usar un aviso breve en una esquina para las confirmaciones simples ("Guardado"), y dejar las ventanas de SweetAlert para las decisiones (eliminar, anular).

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
- **Primer ingreso:** pedir el cambio de la contraseña inicial.

---

## Cómo se mide

- Cada rol completa su tarea diaria principal desde el inicio en **2 clics o menos**: tomar asistencia, calificar, cobrar o ver las tareas.
- Ninguna pantalla con textos en inglés, colores fijos o títulos "MANTENIMIENTO DE".
- Revisión en celular (390 px) de cada pantalla de docentes y familias.
- Las pruebas del navegador (`tests/navegador/revisar_roles.sh`) siguen en 0 errores.
