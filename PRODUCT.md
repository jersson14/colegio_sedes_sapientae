# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Dirección / administrador del colegio**: configura el año escolar, aulas, docentes, matrículas, usuarios, comunicados, respaldos; revisa el estado general del colegio al iniciar el día.
- **Auxiliar**: asistencia diaria y mensual, horarios, tareas, apoyo administrativo.
- **Docentes**: tareas (con material y videos), registro de tareas, notas, exámenes y asistencia de sus aulas; suelen usarlo entre clases, a veces desde el celular.
- **Contadora**: cobro de pensiones, ingresos/egresos, caja, planilla, reportes financieros.
- **Enfermera y psicóloga**: atenciones de salud de estudiantes.
- **Estudiantes / familias**: ven notas, tareas, asistencia, horarios, comunicados y sus pagos.

Usuarios con distinta destreza digital: el sistema debe ser fácil de usar para todos.

## Product Purpose

Sistema de gestión escolar de un colegio (Sedes Sapientiae, Perú) que reúne lo académico (matrícula, notas, tareas, exámenes, asistencia, horarios), lo administrativo (comunicados, salud) y lo contable (pensiones, caja, planilla, reportes). Éxito: el personal hace su trabajo diario sin papel ni Excel paralelos y la dirección ve el estado del colegio de un vistazo.

## Positioning

Un solo sistema para todo el colegio, configurable por país (moneda, documento de identidad, escala de notas) y por institución (nombre, logo y, a partir de ahora, colores de la interfaz), con permisos por rol para que cada persona vea solo lo suyo.

## Operating Context

- Navegadores de escritorio en la oficina y celulares de docentes y familias.
- Ritmo diario: asistencia por la mañana, cobros durante el día, cierre de caja; mensual: planilla y reportes; por bimestre: notas.
- Documentos que el sistema produce: recibos, boletas, planilla de asistencia mensual, reportes PDF/Excel.

## Capabilities and Constraints

- PHP + MariaDB, AdminLTE 3 (Bootstrap 4), jQuery, DataTables, SweetAlert2, Chart.js, MPDF. Pantallas cargadas dentro de `view/index.php` con `cargar_contenido()`.
- Roles: ADMINISTRADOR, AUXILIAR, DOCENTE, CONTADOR, ENFERMERA, PSICOLOGA, ESTUDIANTE; menú por rol en `view/index.php`.
- Configuración regional y de la institución (tabla `empresa`): nombre, logo, código modular, teléfono, dirección, país, moneda, escala de notas.
- Idioma: español (Perú).

## Brand Commitments

- **Los colores de la interfaz deben ser configurables** por el colegio (decisión del usuario, 2026-10-25).
- **El logo y los demás datos de identidad deben ser configurables** (ya existe logo y nombre en `empresa`; se amplía).
- Identidad actual del colegio: escudo granate con dorado (`controller/empresa/FOTOS/…`). Nombre: SEDES SAPIENTIAE.

## Evidence on Hand

- Datos reales de prueba en la base `colegio2` (estudiantes, docentes, pagos, comunicados).
- Logo del colegio en `empresa.emp_logo`.
- No hay fotografías del colegio ni testimonios: no inventarlos.

## Product Principles

1. Cada persona entra y ve primero lo que necesita hacer hoy.
2. Fácil para todos: textos claros en español, acciones visibles, sin tecnicismos.
3. El colegio es el protagonista: su nombre, escudo y colores, no los del software.
4. Un mismo lenguaje visual en todas las pantallas.
5. Nada se pierde: acciones destructivas confirmadas y con historial.

## Accessibility & Inclusion

Usuarios de todas las edades y destrezas: contraste suficiente, textos legibles, botones grandes en celular, sin depender solo del color para comunicar estado.
