# Pruebas

| Capa | Dónde | Qué protege | BD |
|---|---|---|---|
| Unitarias | `tests/Unit/` (PHPUnit) | Código de seguridad: subidas, borrado de fotos, `colegio.env`, límite de intentos | No |
| Integración | `tests/Integration/` (PHPUnit) | Esquema, pertenencia del dato (IDOR) y procedimientos de escritura críticos (pagos, matrícula, notas) | Sí, con transacción revertida |
| Caracterización | `tests/E2E/caracterizacion.mjs` + `tests/Caracterizacion/grabacion.json` | Las 124 respuestas que la interfaz de los 6 roles recibe hoy (columnas por índice incluidas) | Sí, solo lectura |
| Aislamiento | `tests/E2E/aislamiento.php` | Modo múltiple: dos colegios con los mismos usuarios; cada uno ve solo su base, la sesión de uno no vale en otro, host desconocido o suspendido → 404 idéntico, límite de login por colegio | Sí, dos bases + la maestra |
| E2E | `tests/E2E/flujos.mjs` | Login, autorización, pago + boleta, matrícula, notas, tarea publicada y entregada, asistencia, cuentas de usuario, alumnos (alta, cambios, baja, foto), matrícula (alta, cambios, baja), notas (registro, edición, notas de padres), asistencia (días pasados, edición), anulación de ingresos, asignaturas y horarios, pensiones, pagos e ingresos diversos, tareas y exámenes, atenciones de salud y comunicados | Sí, **escribe** |

Todo corre en el CI (`.github/workflows/calidad.yml`). Sin CI en verde no se mergea.

## Datos de prueba

`database/seeders/datos_prueba.sql`: el dataset de piloto **anonimizado** (507 filas, 36 tablas),
generado con `tools/generar_datos_prueba.php`. El generador verifica que ningún valor personal
original (386: DNI, nombres, teléfonos, correos, direcciones, salud…) aparezca en la salida;
si aparece, no escribe nada.

- Usuarios `usuarioN` (N = `usu_id`), contraseña `Prueba.2026`. `usuario33` está INACTIVO.
- Fecha congelada: con `APP_ENTORNO=prueba` y `DB_FECHA_PRUEBA=2025-12-26 12:00:00`, la conexión hace
  `SET SESSION timestamp`, así que `NOW()`/`YEAR(NOW())` de los 254 procedimientos son deterministas.

Escenario de los flujos: docente `usuario10` (curso 20, aula 5, criterio 1, periodo 44), alumno
`usuario62` (matrícula 40, aula 5), administrador `usuario9`, auxiliar `usuario22`; la cuenta que se renombra y desactiva es la del docente
`usuario11` (id 11). Los flujos cuentan para el límite de intentos de login: al repetirlos en local,
vacía `LOGIN_LIMITE_DIR` además de recargar los datos. Alumnos: 70000014 (id 20, sin matrícula) se modifica;
70000001 tiene matrícula; los DNI 79999991/2 los crea el propio flujo. Matrícula: el alumno 20 se
matricula en los años 5 y 2 con el usuario `nuevo20e2e` y se dan de baja ambas. Notas §11: matrícula 40,
periodo 12 (el §5 usa el 44). Asistencia §12: matrícula 40 el 2025-12-01 (el §7 usa el 26). Horarios §14: el aula 5 tiene la semana
completa; la hora 41 del lunes es del curso 20. Pagos §15: edita y anula el pago del §3 (matrícula 31,
pensión 36); por eso el §13 anula un ingreso que no es de pensión. Tareas §16: el docente publica una
tarea vigente (2025-12-31) y otra vencida (2025-12-20); exámenes con el administrador. Salud §17:
psicóloga `usuario32` y enfermera `usuario50`.

## Ejecutar en local

```bash
# 1. BD de prueba (el nombre debe contener prueba/test/ci) con esquema y datos
export DB_HOST=127.0.0.1 DB_PORT=3307 DB_NAME=colegio_prueba DB_MIGRACION_USER=root DB_MIGRACION_PASS=
vendor/bin/phinx migrate && PERMITIR_DATOS_PRUEBA=1 vendor/bin/phinx seed:run -s DatosPrueba

# 2. Unitarias e integración
composer test
DB_USER=root DB_PASS= vendor/bin/phpunit --testsuite=Integration

# 3. App contra esa BD (colegio.env de prueba con APP_ENTORNO=prueba y DB_FECHA_PRUEBA)
COLEGIO_ENV=/ruta/prueba.env php -S 127.0.0.1:8099 -t .

# 4. Caracterización y flujos (Chrome instalado; flujos escribe: recargar datos antes de repetir)
cd tests/E2E && npm ci
BASE_URL=http://127.0.0.1:8099/ node caracterizacion.mjs verificar
BASE_URL=http://127.0.0.1:8099/ node flujos.mjs
```

## Reportes PDF

`tools/caracterizar_reportes.php` ejecuta cada reporte de `view/MPDF/REPORTE/` por línea de comandos con
una sesión de prueba y un sustituto de mPDF (`tests/Caracterizacion/reporte_html.php`), y compara el
**HTML** que el reporte entrega a mPDF con `tests/Caracterizacion/reportes.json` (el PDF lleva fechas de
generación y compresión). Con los datos de prueba recién cargados:

```bash
COLEGIO_ENV=/ruta/prueba.env php tools/caracterizar_reportes.php verificar   # o «grabar» tras un cambio intencionado
```

## Aislamiento entre colegios (modo múltiple)

Servidor con `MODO_TENANT=multiple` y `TENANT_DOMINIO=prueba.test` (el trabajo «Modo multiple» del CI lo
monta entero: maestra, dos bases migradas con `tools/migrar_tenants.php` y los mismos datos de prueba):

```bash
BASE_URL=http://127.0.0.1:8098/ DB_HOST=127.0.0.1 DB_PORT=3307 DB_USER=root DB_PASS= \
  MAESTRO_DB_NAME=sge_maestro_prueba DB_NAME_A=prueba_tenant_a DB_NAME_B=prueba_tenant_b php tests/E2E/aislamiento.php
```

Las peticiones van a `colegio-a.prueba.test` / `colegio-b.prueba.test` resueltos a 127.0.0.1 (sin tocar
el archivo hosts). Comprobado que falla si se quita la marca `S_TENANT` de la sesión.

## Regrabar la caracterización

Solo cuando un cambio de comportamiento es **intencionado**: `node caracterizacion.mjs grabar`
(con datos recién cargados) y revisar el diff de `grabacion.json` en el PR. Las filas se comparan
sin importar su orden y se guardan en orden canónico, de modo que regrabar sin cambios no produce diff.

## Defectos corregidos (Fase 3)

Migración `20261008000000_corregir_cuentas_e_ingresos`:

| Defecto | Ahora |
|---|---|
| `USU VARCHAR(8)` en matrícula y personal: usuarios largos truncados sin aviso | `VARCHAR(250)`, como la columna |
| Login con `BINARY` mientras la matrícula guarda en MAYÚSCULAS | El login no distingue mayúsculas (ningún usuario existente colisionaba) |
| Los 3 ingresos de la matrícula apuntaban al último pago o a NULL | Cada ingreso apunta a su pago (`LAST_INSERT_ID`) |
| Ingresos siempre a nombre del usuario 9 | A nombre de quien cobra (usuario de la sesión) |

Los ingresos **históricos** mal enlazados se reparan aparte y a decisión del responsable con
`tools/reparar_ingresos.php` (simula por defecto; `--aplicar` solo toca lo inequívoco). Quién cobró
en el pasado no es recuperable.

Migración `20261009000000_corregir_notas_conceptos_y_orden`:

| Defecto | Ahora |
|---|---|
| Un concepto de pago fuera del ENUM se guardaba vacío sin error | `SP_REGISTRAR_DETALLE_PENSION_PAGO` lo rechaza («Concepto de pago no válido») |
| `SP_REGISTRAR_NOTAS`: la validación «matrícula no existe» nunca se disparaba (variable que sombreaba la columna) | Variables con prefijo `v_`; la validación se lanza con su mensaje |
| `SP_REGISTRAR_NOTAS` devolvía el conteo del último registro | Devuelve el total insertado; el controlador responde 1 (todas), 2 (parcial: alguna ya existía) o 0 (error) |
| 4 listados (asistencias, componentes, matrículas, pagos) con `ORDER BY` sobre columnas con empates | Desempate por clave: el orden es estable entre peticiones |

Migración `20261010000000_corregir_modificar_usuario` (módulo usuario, ya en `src/`):

| Defecto | Ahora |
|---|---|
| Editar una cuenta truncaba el usuario a 20 caracteres (`SP_MODIFICAR_USUARIO`) | Hasta 250, como la columna |
| Se podía renombrar una cuenta al nombre de otra (dos cuentas con el mismo usuario) | Responde 2 sin tocar nada; el panel lo avisa |
| Un estado distinto de ACTIVO/INACTIVO se guardaba vacío, y el login lo trataba como activo | Se rechaza (0) en el servicio y en el SP |

Migración `20261011000000_corregir_alumnos` (módulo alumnos, ya en `src/`):

| Defecto | Ahora |
|---|---|
| Modificar un alumno actualizaba los padres por el `idpa` del formulario: con uno ajeno, los de OTRO alumno | Por `id_alu` (1:1); `idpa` ya no se usa |
| Un DNI de más de 8 caracteres (o celular de más de 9) se truncaba sin aviso; sexo o fecha inválidos se guardaban vacíos | `FichaAlumno` lo rechaza: responde 0 y el panel lo avisa |
| Eliminar un alumno con matrícula daba 500 (clave foránea): el aviso del panel nunca aparecía | Responde 0 |
| `SP_ELIMINAR_ALUMNO` comparaba el DNI como número | Como texto |
| Los padres se enlazaban con `MAX(Id_alumno)` (carrera entre altas simultáneas) | `LAST_INSERT_ID()` |
| La foto actual y la que se borraba venían del formulario; al eliminar, la foto quedaba en el disco | Se leen de la BD; al eliminar se borra |

Migración `20261012000000_corregir_matricula` (módulo matrícula, ya en `src/`):

| Defecto | Ahora |
|---|---|
| Eliminar una matrícula con 3 pagos o menos borraba en cascada sus **ingresos cobrados**, notas, asistencias y tareas | Responde 2 si hay pensiones, ingresos válidos con monto, notas, asistencias, tareas o atenciones; los ingresos se anulan antes |
| Al matricular a un alumno NUEVO se creaba la cuenta aunque el usuario ya existiera (cuentas duplicadas) | Responde 3 sin tocar nada; el panel lo avisa |
| Un monto mayor a 999.99 se recortaba a 999.99 sin aviso (columnas DECIMAL(5,2)) | Columnas ampliadas a DECIMAL(10,2) (migración `20261015000000_ampliar_montos`); lo que no cabe se rechaza |
| Modificar permitía mover una matrícula a un año en el que el alumno ya estaba | Regla única (alumno, año), como el registro |
| Modificar podía cambiar el alumno de la matrícula, que seguía unida a la cuenta del anterior | El alumno no cambia |
| Tras eliminar su única matrícula, el alumno quedaba ANTIGUO: al volver a matricularlo quedaba sin cuenta | Vuelve a NUEVO y su cuenta sin uso se borra |
| El registro (cuenta, matrícula, 3 pagos, 3 ingresos) no era atómico | En una transacción |

Migración `20261013000000_corregir_notas_padres` y módulo notas en `src/`:

| Defecto | Ahora |
|---|---|
| **XSS almacenado**: el docente registraba conclusiones sin escapar y el panel las pinta como HTML | Se guardan escapadas (`Domain\Nota\TextoLibre`) |
| Una nota podía ser cualquier texto de 5 caracteres | Escala 0–20 (un decimal) o AD/A/B/C; si no, el lote entero se rechaza |
| Conclusiones de más de 255 caracteres se truncaban (los SP declaraban 1000) | Se rechazan |
| Guardar otra vez las notas de los padres las **duplicaba** (`ON DUPLICATE KEY` sin clave única) | Se actualizan por (matrícula, periodo, competencia) |
| Editar devolvía al navegador los mensajes de la BD; usaba `FILTER_SANITIZE_STRING` (obsoleto) | Mensajes propios; mismo escapado que el registro |

Migración `20261014000000_corregir_asistencia` y módulo asistencia en `src/`:

| Defecto | Ahora |
|---|---|
| El duplicado se buscaba por la fecha de **registro**: registrar dos veces un día pasado lo duplicaba | Por (matrícula, fecha de la asistencia) |
| `mes` se guardaba con el mes del registro, no el de la asistencia (reportes mensuales) | `MONTH(fecha)` |
| **XSS almacenado** en la observación (el panel la pinta como HTML) | Se guarda escapada |
| Un estado fuera del ENUM se guardaba vacío | Se rechaza (0) |
| Editar cambiaba la fecha (deshabilitada en el panel) y respondía éxito aunque el registro no existiera | No toca la fecha; 2 si no existe |
| El registro de un aula era fila a fila: un error a mitad la dejaba incompleta | En una transacción |

Decisiones del responsable (migraciones `20261015000000` a `20261017000000`):

| Antes | Ahora |
|---|---|
| Montos en `DECIMAL(5,2)`: nada por encima de S/ 999.99 | `DECIMAL(10,2)` en las 8 columnas de montos y en los 16 parámetros de los SP que los reciben |
| Editar los montos de una matrícula no tocaba sus pagos ni sus ingresos | Los pagos de ADMISION, ALUMNO NUEVO y MATRICULA y sus ingresos **válidos** siguen a los montos; un ingreso anulado conserva su monto |
| Anular un ingreso o egreso sobrescribía quién cobró/pagó con un id que llegaba del formulario, y se podía volver a anular | `id_user` no cambia; quién anula (de la sesión) va en `id_usuario_anulacion`; solo se anula lo VALIDO y el motivo es obligatorio |

Migración `20261018000000_corregir_horarios` y módulo asignaturas/horarios en `src/`:

| Defecto | Ahora |
|---|---|
| Dos cursos distintos podían ocupar la misma hora y día de un aula | Responde 3 y no registra nada del lote |
| Un docente podía tener dos clases a la vez en aulas distintas | Responde 4 (solapamiento de horas, mismo día y año escolar) |
| Eliminar el horario de un aula borraba el de **todos los años escolares** | Solo el del año de la fila |
| Eliminar una asignatura con docente asignado daba 500 (el aviso del panel no aparecía) | Responde 0 |
| Un día fuera de lunes a viernes se guardaba vacío | Se rechaza (0) |
| El registro del horario era fila a fila | Todo el lote o nada (con punto de guardado si hay una transacción abierta) |

Migración `20261019000000_corregir_pagos_y_caja` y módulo pensiones/pagos/caja en `src/`:

| Defecto | Ahora |
|---|---|
| Ingresos y egresos diversos se registraban a nombre del `usu` del formulario y editarlos reescribía quién cobró/pagó | Responsable de la sesión; editar no lo cambia |
| Un ingreso podía llevar un indicador de gastos (y un egreso uno de ingresos) | Se rechaza (0) |
| Se podía editar un movimiento anulado, o desde caja el ingreso de un pago de pensión (se descuadraba del pago) | Solo lo VALIDO; el de un pago se edita desde el pago |
| Una sola pensión de cada mes por nivel **para siempre** (`AND fecha_vencimiento` siempre verdadero): la del año siguiente respondía «ya existe» | Regla (nivel, mes, año del vencimiento) |
| Eliminar una pensión con pagos o un indicador en uso daba 500 | Responde 0 |
| Editar el monto de un pago no tocaba su ingreso | El ingreso válido lo sigue |
| «Anular pago» **borraba el ingreso cobrado** | Lo anula (motivo, fecha, quién), lo conserva en caja y libera la pensión |
| El cobro de varias filas se cortaba en el primer duplicado dejando cobradas las anteriores | Todo o nada |

Migración `20261020000000_corregir_tareas_y_examenes` y módulo tareas/exámenes en `src/`:

| Defecto | Ahora |
|---|---|
| La carpeta de cada entrega se nombraba con `time()`: dos entregas del mismo segundo compartían carpeta y una reentrega **borraba los archivos de otro alumno** | Nombre único (marca de tiempo + sufijo aleatorio) |
| Al reemplazar archivos se borraba la carpeta anterior **antes** de actualizar la BD | Se guarda lo nuevo, se actualiza la BD y solo entonces se borra lo anterior |
| Se podía entregar una tarea vencida o finalizada, y reemplazar una entrega ya calificada | 0 (el panel lo avisa; antes «enviado correctamente» con cualquier respuesta) |
| Cualquier estado de tarea calificaba con 5 a los pendientes | Solo se finaliza |
| Eliminar una tarea borraba en cascada las entregas enviadas y calificadas (y dejaba los archivos en el disco) | 0 si hay entregas; si se elimina, se borran sus archivos |
| Una calificación fuera de 0–20 se guardaba | Se rechaza |
| Editar un examen con hora sin cambiar la fecha respondía «ya existe» (DATE contra DATETIME) | Regla (curso, fecha) sin contar el propio examen |
| El primer examen recibía el código «D0000001» | «E0000001» (el ya existente se conserva) |
| Estado de examen sin validar | Solo los del ENUM |

Migración `20261021000000_corregir_salud_y_comunicados` y módulo bienestar en `src/`:

| Defecto | Ahora |
|---|---|
| **La enfermera podía reescribir una atención psicológica** (confidencial) con solo enviar su id, y la psicóloga una de enfermería | Cada uno solo modifica las de su tipo |
| El profesional que atendió y el autor de un comunicado llegaban del formulario, y editar los reemplazaba (y una atención con un usuario que no es personal desaparecía del listado) | Salen de la sesión y no cambian al editar |
| Al eliminar un comunicado su imagen quedaba en el disco; la imagen actual al editar venía del formulario | Se borra; se lee de la BD |

Reportes PDF (`src/Reportes`, sin migración: los defectos estaban en el PHP de cada reporte):

| Defecto | Ahora |
|---|---|
| `utf8_encode`/`utf8_decode` sobre datos que ya eran UTF-8: «MUÑOZ» salía «MUÃOZ» en la boleta | Se quitaron (además, obsoletas en PHP 8.2) |
| Fechas con `strftime()` (obsoleta): el mes salía en el idioma del servidor («25 de december del 2025» en el reporte de pagos) | `App\Reportes\Fecha` con `IntlDateFormatter` |
| Las matrículas sin cuenta de alumno (2 de 11 en la BD de trabajo) daban boleta, kardex, pagos, cédula y notas **en blanco** | `LEFT JOIN` a la cuenta; la empresa por defecto si no hay |
| El informe general omitía el **primer periodo** de asistencia y mezclaba periodos de otros años | Todos los periodos del año de la matrícula |
| El informe por bimestre sumaba la asistencia de **todo el año** | Solo la del bimestre |
| Periodo de otro año o matrícula inexistente: avisos de PHP y un PDF vacío | 404 «No hay datos para este reporte» |
| SQL armado con `real_escape_string` y una conexión MySQLi aparte | Consultas preparadas sobre el PDO común (`App\Reportes\Datos`) |

## Defectos encontrados al caracterizar (comportamiento congelado, pendiente de corregir)

Las pruebas los marcan con «DEFECTO». Al corregir uno, su prueba cambia en el mismo commit.

| Dónde | Defecto | Prueba |
|---|---|---|
| Reporte de horario | La consulta no filtra por año escolar: con horarios de dos años los mezclaría | Pendiente: en los datos el «año escolar 2025» transcurre en 2026 y la regla no es obvia |
| BD | 4 eventos programados que requieren `event_scheduler=ON` (OFF en el XAMPP local) | `EsquemaTest` |
