# Pruebas

| Capa | Dónde | Qué protege | BD |
|---|---|---|---|
| Unitarias | `tests/Unit/` (PHPUnit) | Código de seguridad: subidas, borrado de fotos, `colegio.env`, límite de intentos | No |
| Integración | `tests/Integration/` (PHPUnit) | Esquema, pertenencia del dato (IDOR) y procedimientos de escritura críticos (pagos, matrícula, notas) | Sí, con transacción revertida |
| Caracterización | `tests/E2E/caracterizacion.mjs` + `tests/Caracterizacion/grabacion.json` | Las 124 respuestas que la interfaz de los 6 roles recibe hoy (columnas por índice incluidas) | Sí, solo lectura |
| E2E | `tests/E2E/flujos.mjs` | Login, autorización, pago + boleta, matrícula, notas, tarea publicada y entregada, asistencia | Sí, **escribe** |

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
`usuario62` (matrícula 40, aula 5), administrador `usuario9`, auxiliar `usuario22`.

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
sin importar su orden porque varios procedimientos ordenan por columnas con empates
(`ORDER BY created_at` con fecha sin hora) y la app no garantiza ese orden.

## Defectos corregidos (Fase 3, migración `20261008000000_corregir_cuentas_e_ingresos`)

| Defecto | Ahora |
|---|---|
| `USU VARCHAR(8)` en matrícula y personal: usuarios largos truncados sin aviso | `VARCHAR(250)`, como la columna |
| Login con `BINARY` mientras la matrícula guarda en MAYÚSCULAS | El login no distingue mayúsculas (ningún usuario existente colisionaba) |
| Los 3 ingresos de la matrícula apuntaban al último pago o a NULL | Cada ingreso apunta a su pago (`LAST_INSERT_ID`) |
| Ingresos siempre a nombre del usuario 9 | A nombre de quien cobra (usuario de la sesión) |

Los ingresos **históricos** mal enlazados se reparan aparte y a decisión del responsable con
`tools/reparar_ingresos.php` (simula por defecto; `--aplicar` solo toca lo inequívoco). Quién cobró
en el pasado no es recuperable.

## Defectos encontrados al caracterizar (comportamiento congelado, pendiente de corregir)

Las pruebas los marcan con «DEFECTO». Al corregir uno, su prueba cambia en el mismo commit.

| Dónde | Defecto | Prueba |
|---|---|---|
| `SP_REGISTRAR_DETALLE_PENSION_PAGO` | Un concepto fuera del ENUM se guarda vacío sin error | `ProcedimientosCriticosTest` |
| `SP_REGISTRAR_NOTAS` | La validación «matrícula no existe» nunca se dispara (variable que sombrea la columna) y el conteo devuelto es solo del último registro | `ProcedimientosCriticosTest` |
| 4 listados (asistencias, componentes, matrículas, pagos) | `ORDER BY` sobre columnas con empates: el orden de las filas varía entre peticiones | `caracterizacion.mjs` |
| BD | 4 eventos programados que requieren `event_scheduler=ON` (OFF en el XAMPP local) | `EsquemaTest` |
