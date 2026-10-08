# Pruebas

| Capa | Dónde | Qué protege | BD |
|---|---|---|---|
| Unitarias | `tests/Unit/` (PHPUnit) | Código de seguridad: subidas, borrado de fotos, `colegio.env`, límite de intentos | No |
| Integración | `tests/Integration/` (PHPUnit) | Esquema, pertenencia del dato (IDOR) y procedimientos de escritura críticos (pagos, matrícula, notas) | Sí, con transacción revertida |
| Caracterización | `tests/E2E/caracterizacion.mjs` + `tests/Caracterizacion/grabacion.json` | Las 124 respuestas que la interfaz de los 6 roles recibe hoy (columnas por índice incluidas) | Sí, solo lectura |
| E2E | `tests/E2E/flujos.mjs` | Login, autorización, pago + boleta, matrícula, notas, tarea publicada y entregada, asistencia, cuentas de usuario, alumnos (alta, cambios, baja, foto), matrícula (alta, cambios, baja), notas (registro, edición, notas de padres) | Sí, **escribe** |

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
periodo 12 (el §5 usa el 44).

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
| Un monto mayor a 999.99 se recortaba a 999.99 sin aviso (columnas DECIMAL(5,2)) | Se rechaza (0) |
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

## Defectos encontrados al caracterizar (comportamiento congelado, pendiente de corregir)

Las pruebas los marcan con «DEFECTO». Al corregir uno, su prueba cambia en el mismo commit.

| Dónde | Defecto | Prueba |
|---|---|---|
| BD | 4 eventos programados que requieren `event_scheduler=ON` (OFF en el XAMPP local) | `EsquemaTest` |
| `SP_ANULAR_INGRESOS` | Al anular, sobrescribe `id_user` (quién cobró) con quien anula, y ese id llega del formulario | Pendiente (módulo pagos/ingresos) |
| Montos | Columnas `DECIMAL(5,2)`: nada por encima de S/ 999.99 (hoy se rechaza en vez de recortarse). Ampliarlas afecta matrícula, pagos, ingresos y sus SP | Decisión pendiente |
| `SP_MODIFICAR_MATRICULA` | Cambiar los montos de una matrícula no toca los pagos ni los ingresos ya registrados | Decisión pendiente (contable) |
