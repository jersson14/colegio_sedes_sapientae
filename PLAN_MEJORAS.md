# Plan de mejoras – Sistema Colegio

> Base de datos: `colegio2` (MySQL/MariaDB, puerto 3307)
> Arquitectura actual: PHP (MVC simple) + procedimientos almacenados + jQuery/DataTables + MPDF
> Fecha de análisis: 2026-10-23

Este documento sirve para avanzar **por fases**. Cada fase es entregable por sí sola: se termina, se prueba, se hace commit y recién se pasa a la siguiente. Marcar `[x]` cada tarea al completarla.

## Decisiones confirmadas

| Tema | Decisión |
|------|----------|
| País | **Un solo país por colegio.** Se configura una vez en los datos de la empresa. |
| Moneda | **Una sola moneda**, configurable desde el sistema. No se cobra en dos monedas a la vez. |
| Roles financieros | **Solo CONTADOR** (no habrá rol CAJERO). El contador cobra pensiones, registra ingresos y egresos, hace la planilla y ve los reportes. |

---

## Índice

- [Resumen del diagnóstico](#resumen-del-diagnóstico)
- [Fase 0 – Preparación y respaldo](#fase-0--preparación-y-respaldo--2026-10-23) ✅
- [Fase 1 – Seguridad crítica](#fase-1--seguridad-crítica--2026-10-23) ✅
- [Fase 2 – Corrección de lógica en módulos existentes](#fase-2--corrección-de-lógica-en-módulos-existentes--2026-10-23) ✅
- [Fase 3 – Configuración regional (Latinoamérica)](#fase-3--configuración-regional-latinoamérica--2026-10-23) ✅
- [Fase 4 – Métodos de pago, cuentas y recibos](#fase-4--métodos-de-pago-cuentas-y-recibos--2026-10-23) ✅
- [Fase 5 – Módulo de contabilidad (rol Contador/a)](#fase-5--módulo-de-contabilidad-rol-contadora--2026-10-23) ✅
- [Fase 6 – Pago a docentes (planilla)](#fase-6--pago-a-docentes-planilla--2026-10-23) ✅
- [Fase 7 – Reportes financieros y utilidades](#fase-7--reportes-financieros-y-utilidades--2026-10-23) ✅
- [Fase 8 – Mejoras generales por módulo](#fase-8--mejoras-generales-por-módulo--2026-10-24) ✅
- [Fase 9 – Pruebas, documentación y despliegue](#fase-9--pruebas-documentación-y-despliegue--2026-10-24) ✅
- [Anexo A – Monedas y países](#anexo-a--monedas-y-países)
- [Anexo B – Script SQL de referencia](#anexo-b--script-sql-de-referencia)
- [Pendientes por confirmar](#pendientes-por-confirmar)

---

## Resumen del diagnóstico

Problemas encontrados al revisar el código y la base de datos real (`colegio2`):

| # | Severidad | Problema | Dónde |
|---|-----------|----------|-------|
| 1 | 🔴 Crítico | **El rol de la sesión lo envía el navegador.** `controlador_crear_sesion.php` toma `rol`, `idusuario`, etc. de `$_POST`; cualquiera puede llamar ese endpoint con `rol=ADMINISTRADOR` y entrar como administrador sin contraseña. | `controller/usuario/controlador_crear_sesion.php` |
| 2 | 🔴 Crítico | **255 de 257 controladores no verifican sesión ni rol.** Cualquier persona sin iniciar sesión puede listar, registrar, modificar o eliminar datos (alumnos, pagos, egresos, usuarios). | `controller/*/*.php` |
| 3 | 🔴 Crítico | **Subida de archivos sin validar.** El nombre de archivo viene del cliente (`nombrefoto`) y no se valida extensión ni tipo MIME → se puede subir un `.php` y ejecutarlo en el servidor. | `controller/alumnos`, `docentes`, `empleado`, `empresa`, `comunicados`, `tareas` |
| 4 | 🔴 Crítico | **El login devuelve el hash de la contraseña** (`usu_contra`) al navegador en el JSON. | `model/model_usuario.php` → `Verificar_Usuario` |
| 5 | 🔴 Crítico | **El usuario que registra egresos/ingresos viene del formulario** (`$_POST['usu']`) en vez de la sesión → se puede falsificar quién registró un movimiento. En pagos de pensión el usuario está **fijo en 9** dentro del SP. | `controller/egresos`, `controller/ingresos`, `SP_REGISTRAR_DETALLE_PENSION_PAGO` |
| 6 | 🟠 Alto | **Montos en `DECIMAL(5,2)`** → máximo **999.99**. Una pensión de 1 200 o cualquier monto en pesos argentinos/colombianos/chilenos/guaraníes se corta o falla. | `pensiones`, `pago_pensiones`, `ingresos`, `egresos`, `matricula` |
| 7 | 🟠 Alto | **Registro de pagos sin transacción.** El controlador recorre un bucle y llama al SP por cada concepto; si falla a la mitad quedan pagos parciales registrados. | `controller/pago_pension/controlador_detalle_pago_pension.php` |
| 8 | 🟠 Alto | El SP obtiene el último id con `SELECT MAX(id_pago_pension)` → con dos cajeros a la vez se asocia el ingreso al pago equivocado. Debe usarse `LAST_INSERT_ID()`. | `SP_REGISTRAR_DETALLE_PENSION_PAGO` |
| 9 | 🟠 Alto | La validación de duplicados es `id_matri + id_pension`; para conceptos ADMISIÓN / ALUMNO NUEVO / MATRÍCULA (sin pensión) puede bloquear o permitir duplicados incorrectamente. | mismo SP |
| 10 | 🟠 Alto | **La mora no se calcula.** La pantalla solo pinta "VENCIDO" en rojo y le pide al usuario que sume la mora a mano. | `js/console_pago_pension.js`, `view/pago_pensiones` |
| 11 | 🟠 Alto | Moneda **"S/." fija** en ~20 archivos PHP/JS y en procedimientos almacenados (`SP_LISTAR_DIFERENCIA`, etc.). Zona horaria fija `America/Lima` / `-05:00`. | `js/`, `view/`, `view/MPDF/REPORTE/`, SPs |
| 12 | 🟡 Medio | Credenciales de BD (`root` sin contraseña) escritas en **dos** archivos distintos (PDO y mysqli). | `model/model_conexion.php`, `view/MPDF/conexion.php` |
| 13 | 🟡 Medio | `conexionBD::cerrar_conexion()` está **después del `return`** → nunca se ejecuta (código muerto en casi todos los modelos). Errores de conexión se imprimen con `echo` y exponen detalles. | `model/*.php` |
| 14 | 🟡 Medio | Documentos y teléfonos con longitud fija peruana (`CHAR(8)` DNI, `CHAR(9)` celular) → no sirve para cédula de Ecuador (10), CI de Bolivia, RUT de Chile, etc. | `docentes`, `alumnos`, `personal_admi`, `padres` |
| 15 | 🟡 Medio | Archivos de prueba expuestos en producción: `phpinfo.php`, `prueba.php`. | raíz |
| 16 | 🟡 Medio | Ruta de subida de tareas duplicada: `controller/tareas/controller/tareas/documentos/...`; fotos y documentos subidos se mezclan con el código en git. | `controller/tareas` |
| 17 | 🟢 Bajo | Uso de `htmlspecialchars` como "validación" de entrada (es para salida, no para entrada); no hay validación de tipos (números, fechas). | todos los controladores |
| 18 | 🟢 Bajo | No existen: método de pago, cuentas bancarias, rol contador, pago a docentes, tipo de contrato docente, reportes de utilidad. | — |

---

## Fase 0 – Preparación y respaldo ✅ (2026-10-23)

**Objetivo:** poder avanzar sin riesgo de perder datos.

- [x] Respaldo completo de `colegio2` (estructura + datos + rutinas) en `respaldos/colegio2_20261023_1545.sql`:
  `mysqldump -uroot -P3307 --routines --triggers --events colegio2 > respaldos/colegio2_YYYYMMDD.sql`
- [x] Rama de trabajo `mejoras-fase-1` (una rama por fase).
- [x] Carpeta `database/migraciones/` con scripts numerados (`001_...sql`, `002_...sql`). **Todo cambio de BD se hace por script**, nunca a mano en phpMyAdmin, para poder repetirlo en producción.
- [x] `.gitignore` para no versionar fotos/documentos subidos, `config/config.local.php`, `respaldos/` y `logs/*.log`.
  > Los 181 archivos subidos que ya estaban en git siguen versionados; para dejar de versionarlos hay que ejecutar `git rm --cached` sobre ellos (se decide aparte, porque en el servidor se borrarían al hacer pull).
  > `node_modules/` **no** se ignora: `choices.js` se sirve desde ahí.
- [x] Esquema actual (sin datos, con rutinas) en `database/esquema_base.sql`.

**Criterio de cierre:** ✅ respaldo restaurado en `colegio2_test` (36 tablas, 254 rutinas, mismos conteos de datos).

---

## Fase 1 – Seguridad crítica ✅ (2026-10-23)

**Objetivo:** cerrar los huecos que permiten entrar o modificar datos sin permiso. **No avanzar a funcionalidades nuevas sin terminar esta fase.**

### 1.1 Configuración centralizada
- [x] `config/config.php` (bloqueado por `.htaccess`) con BD, zona horaria, modo producción y parámetros de login. Cada servidor sobreescribe lo suyo en `config/config.local.php` (no versionado; ver `config.local.php.example`).
- [x] `model/model_conexion.php` y `view/MPDF/conexion.php` leen de ese archivo (una sola fuente). La zona horaria de MySQL sigue a la de PHP.
- [x] En error de conexión se registra en el log de PHP y se responde un mensaje genérico (antes se mostraba `$e->getMessage()`).
- [ ] **Pendiente en producción:** crear un usuario MySQL propio para la app (no `root`) con permisos solo sobre `colegio2` y ponerlo en `config.local.php`.
- [ ] Pasar la conexión a `utf8mb4` → se hace en la Fase 2 junto con la conversión de tablas (hoy hay tablas `utf8` y `utf8mb4` mezcladas y los SP usan `CONVERT(... USING utf8)`).
  > `PDO::ATTR_EMULATE_PREPARES` se deja como está: todas las consultas ya usan parámetros enlazados y cambiarlo afecta cómo se leen los resultados de los `CALL`.

### 1.2 Inicio de sesión seguro
- [x] `controlador_iniciar_sesion.php` valida usuario/contraseña y **él mismo** llena `$_SESSION` con los datos de la BD. Se eliminó `controlador_crear_sesion.php` (recibía el rol por POST).
- [x] El hash de la contraseña (`usu_contra`) ya no sale del servidor.
- [x] `session_regenerate_id(true)` al iniciar sesión; cookie `HttpOnly`, `SameSite=Lax`, `Secure` con HTTPS (`utilitario/sesion.php`).
- [x] Bloqueo temporal tras 5 intentos fallidos por 15 minutos (tabla `login_intentos`, migración `001_login_intentos.sql`, aplicada en `colegio2`).
- [x] `usu_estatus = 'ACTIVO'` se valida en el servidor.
- [x] "Recuérdame" ya no guarda la contraseña en `localStorage` (solo el usuario) y borra la que hubiera quedado guardada.
- [x] `view/index.php` e `index.php`: la redirección por falta de sesión ahora termina con `exit` (antes la página se seguía generando).

### 1.3 Control de acceso en todos los controladores
- [x] `controller/_seguridad.php` incluido en la primera línea de **los 261 controladores**: 401 sin sesión, 403 si el rol no está permitido o falta el token CSRF en un POST.
- [x] Permisos por archivo en `controller/_permisos.php`, generados analizando qué rol llega a cada controlador (menú por rol → vista → JS → controlador). **Un controlador que no aparece ahí es solo para ADMINISTRADOR.** Al crear un controlador para otro rol hay que registrarlo.
- [x] `propio`: en 24 controladores de consulta, para roles distintos de ADMINISTRADOR el `id` se reemplaza por el de la sesión → un estudiante/docente solo ve lo suyo aunque cambie el `id` en el navegador.
- [x] `usuario_sesion`: en ingresos, egresos, comunicados, enfermería y psicología el usuario que registra sale de la sesión (antes venía de `$_POST['usu']` / `idusu`).
- [x] Token CSRF en `view/index.php` (meta tag) enviado por `$.ajaxSetup` en todas las peticiones; si la sesión expira (401) se vuelve al login.
- [x] Las 42 sub-vistas (`view/*/view_*.php`) exigen sesión (`utilitario/vista_segura.php`).
- [x] Reportes MPDF con sesión y rol (`utilitario/seguridad_reporte.php`); un estudiante solo abre la boleta/kardex de su matrícula y el horario de su aula.
- [x] El panel solo pide al servidor lo de cada rol (los totales de ingresos/egresos ya no se consultan desde la sesión de un estudiante).
- [x] Accesos rechazados registrados en `logs/accesos_denegados.log` (bloqueado por web) para detectar permisos mal configurados.
- [ ] `SP_REGISTRAR_DETALLE_PENSION_PAGO` sigue con `id_user = 9` fijo → se corrige en la Fase 2 (requiere cambiar el SP).

| Módulo | Roles permitidos |
|--------|------------------|
| Usuarios, roles, empresa, configuración | ADMINISTRADOR |
| Alumnos, matrícula, aulas, secciones, horarios | ADMINISTRADOR |
| Pensiones, pago de pensiones | ADMINISTRADOR, CONTADOR |
| Ingresos, egresos, contabilidad, planilla docente, reportes financieros | ADMINISTRADOR, CONTADOR |
| Notas, tareas, exámenes, asistencia | DOCENTE, AUXILIAR, ADMINISTRADOR |
| Enfermería / Psicología | ENFERMERA / PSICOLOGA, ADMINISTRADOR |
| Vistas del estudiante | ESTUDIANTE (solo sus propios datos) |

> El rol CONTADOR se agrega a `_permisos.php` en la Fase 5, cuando exista.

### 1.4 Subida de archivos
- [x] `utilitario/archivos.php`:
  - fotos: nombre generado en el servidor, MIME real (`finfo`) + `getimagesize`, solo JPG/PNG/GIF/WEBP hasta 5 MB (12 controladores);
  - documentos de tareas: lista blanca de extensiones (pdf, doc(x), xls(x), ppt(x), txt, jpg, png, zip, rar), hasta 20 MB, nombre saneado (4 controladores).
- [x] **Borrado arbitrario de archivos corregido:** `unlink('../../'.$fotoactual)` borraba la ruta que mandara el navegador (p. ej. `config/config.php`). Ahora `borrar_archivo_seguro()` solo borra dentro de la carpeta de fotos y nunca las imágenes por defecto. También se corrigió que la foto anterior se borraba aunque fallara la subida, y que al modificar alumno/docente/personal/comunicado nunca se borraba.
- [x] Al reemplazar una tarea, la carpeta anterior solo se borra si tiene el formato que genera el sistema (antes `archivoactual=.` borraba todos los archivos de `documentos`). El estudiante solo puede enviar/reemplazar su propia tarea.
- [x] `.htaccess` en cada carpeta de fotos: no ejecuta scripts ni lista contenido.
- [x] **Archivos de tareas privados:** la carpeta `documentos` tenía `Options +Indexes` y cualquiera podía listar y descargar las tareas de todos. Ahora está bloqueada y los archivos se ven con `controller/tareas/controlador_archivos_tarea.php` (con sesión; el estudiante solo los de sus tareas). Se actualizaron los 11 botones "Descargar/Ver tarea".
- [x] ~~Corregir la ruta duplicada `controller/tareas/controller/tareas/documentos`~~ → **se mantiene**: es la ruta real donde están todos los archivos y lo que guarda la BD; moverla no aporta seguridad y arriesga perder archivos. El nuevo controlador la resuelve.

### 1.5 Limpieza
- [x] Eliminados `phpinfo.php` y `prueba.php`.
- [ ] Escapar salida en vistas y en DataTables (`render` con texto) → se hace módulo por módulo en la Fase 8 (son cientos de columnas).
- [ ] Revisar `registrar.php`, `seguimiento.php`, `default.php` y los módulos `area`, `empleado`, `tipo_documento`, `tramite` (restos de otro sistema "DIRESA"; ya quedan solo para ADMINISTRADOR) → decidir si se eliminan.

### Pruebas realizadas (BD `colegio2_test`, 43/43 OK)
Sin sesión → 401 en controladores, vistas y PDF · el endpoint `crear_sesion` ya no existe · login por rol (admin, auxiliar, docente, estudiante, enfermera) · clave incorrecta no devuelve hash · estudiante/docente/auxiliar/enfermera reciben 403 fuera de su módulo · POST sin token o con token falso → 403 · estudiante que pide datos de otro `id` recibe los suyos (admin sí puede consultar otros) · kardex/cedula/tareas ajenas → 403 · foto falsa (PHP) y documento `.php` → 400 · `config.php` intacto tras intentar borrarlo · logout invalida la sesión · bloqueo tras 5 intentos aunque luego la clave sea correcta · panel y las 46 vistas del menú de los 5 roles cargan sin errores PHP.

**Criterio de cierre:** ✅ sin sesión cualquier controlador responde 401; un ESTUDIANTE no puede llamar controladores de egresos; no se puede subir un `.php`.

> **Para validar en el navegador:** usar el sistema normalmente con cada rol durante unos días y revisar `logs/accesos_denegados.log`. Si aparece una acción legítima rechazada (`ROL_NO_PERMITIDO`), agregar ese controlador y rol en `controller/_permisos.php`.

### Hallazgos para la Fase 2 (encontrados al probar)
- Hay usuarios con nombre de usuario **duplicado** (`JOAQUIN2`, `ALEXANDE`) y **vacío** → agregar índice único en `usuario.usu_usuario`.
- Un estudiante sin matrícula vinculada a su usuario (`matricula.usu_id`) no puede iniciar sesión (`SP_VERIFICAR_USUARIO` hace `INNER JOIN matricula`).
- `SP_LISTAR_PAGO_PENSION_ID` solo muestra el año actual: en enero un estudiante no ve sus pagos del año anterior.
- Docentes: no se valida que la tarea/examen/nota que modifica sea de un curso suyo (pendiente de propiedad, igual que se hizo con estudiantes).

---

## Fase 2 – Corrección de lógica en módulos existentes ✅ (2026-10-23)

Migración `database/migraciones/002_correcciones_logica.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_20261023_1626_antes_002.sql`). Total de ingresos válidos antes y después: **9 490.00** (sin cambios); egresos 1 751.99.

### 2.1 Base de datos
- [x] Montos a `DECIMAL(14,2)`: `pensiones.precio/mora`, `pago_pensiones.sub_total`, `ingresos.monto`, `egresos.monto`, `matricula.pago_admi/pago_alu_nuevo/pago_matricula` y todos los parámetros `DECIMAL(5,2)` de los SP.
  > Hay un egreso de exactamente **999.99** (luz, 25-12-2025): probablemente era un monto mayor recortado por el límite anterior. Revisarlo a mano.
- [x] **Collation única `utf8mb4_unicode_ci`.** Las 36 tablas ya estaban en `unicode_ci`, pero la BD y sus 254 procedimientos en `general_ci` → *"Illegal mix of collations"* al comparar parámetros de texto con columnas. **En producción, registrar una pensión fallaba.** Se cambió la collation de la BD y se recrearon todos los procedimientos.
- [x] `ingresos → pago_pensiones` pasó de `ON DELETE CASCADE` a `SET NULL`: borrar un pago o una matrícula ya no borra ingresos (historia contable).
- [x] Índice `(id_matri, id_pension, estado)` en `pago_pensiones`.
- [ ] Ampliar documentos/teléfonos (`*_dni` → `VARCHAR(20)` + `tipo_documento_id`, `*_movil` → `VARCHAR(20)`) → **se hace en la Fase 3**, junto con la validación por país.
- [ ] Reemplazar fechas `''`/`0000-00-00` por `NULL` en datos existentes → pendiente menor (los SP nuevos ya guardan `NULL`).

### 2.2 Pago de pensiones
- [x] `SP_REGISTRAR_PAGO_PENSION` + transacción en PHP (`Modelo_Pago_Pension::Registrar_Pago`): **si un concepto de la tabla falla, no se registra ninguno** (antes quedaban pagos a medias).
- [x] `LAST_INSERT_ID()` en lugar de `MAX(id)` → dos cajas simultáneas ya no cruzan ingresos.
- [x] El **monto lo valida el servidor** contra el saldo de la pensión (antes se aceptaba lo que se escribiera). Duplicados: pensión ya pagada → rechazada; MATRÍCULA/ADMISIÓN/ALUMNO NUEVO: un pago válido por matrícula.
- [x] La pensión debe ser del **nivel de la matrícula**; el selector muestra el **año** y si está PAGADA/PARCIAL ("ABRIL 2025 (PARCIAL)").
- [x] **Mora automática** (`SP_CALCULAR_SALDO_PENSION`): se cobra si la pensión no quedó pagada completa hasta su vencimiento; va como fila `MORA` aparte. **Exonerar** requiere motivo y queda registrado (fila `MORA` en 0 con `mora_exonerada = 1`, usuario y fecha). Las pensiones antiguas pagadas tarde sin mora no generan deuda retroactiva.
- [x] **Pagos parciales**: se puede pagar menos que el saldo; el pago se aplica primero a la mora y luego a la pensión. La pantalla sugiere el saldo pendiente y muestra estado y mora.
- [x] **Anular en vez de eliminar** (`SP_ANULAR_PAGO_PENSION`): estado `ANULADO`, motivo obligatorio, usuario y fecha; el ingreso también queda ANULADO. Los anulados no salen en listados, kardex, boletas ni en "mes que toca pagar".
- [x] Modificar un pago actualiza también su ingreso, exige motivo y no permite pagar una pensión/mora por encima de su precio.
- [x] El usuario que registra/anula es el de la sesión (antes `id_user = 9` fijo).
- [ ] Número de recibo correlativo por serie (`B001-000123`) → **Fase 4**, junto con la cabecera de pago y los métodos de pago.

### 2.3 Matrícula y pensiones
- [x] `SP_REGISTRAR_MATRICULA`: el usuario se cortaba a **8 caracteres** (`ALEXANDER` → `ALEXANDE`, por eso hay duplicados); ahora 250, y se rechaza si ya existe (código 3). Cada ingreso se enlaza a **su** pago (antes los 3 al último, o a un id de otra sesión en alumnos antiguos); no se crean ingresos en 0; todo en una transacción.
- [x] **Datos reparados:** 26 ingresos mal enlazados y 2 sin pago vueltos a enlazar con el pago de su concepto (21 pagos que figuraban "sin ingreso"). Sin cambio en los totales.
- [x] Modificar matrícula actualiza los pagos e ingresos de admisión/alumno nuevo/matrícula (antes la matrícula decía un monto y el ingreso otro). El duplicado se valida por alumno + **año** (antes por aula).
- [x] Eliminar matrícula: no se permite si tiene pensiones pagadas (2) o notas/asistencias (4) — antes se borraban en cascada. Sus ingresos quedan ANULADOS.
- [x] Pensiones: duplicado = nivel + mes + **año** (antes nivel + mes, lo que **impedía crear las pensiones del año siguiente** y explica fechas como "MARZO 2026" dentro de la serie 2025). No se elimina una pensión con pagos (antes daba error).
- [ ] Revisar a mano las pensiones con fechas fuera de su serie: MARZO 2026 (niveles 1 y 3), DICIEMBRE 2024 (nivel 1), DICIEMBRE 2026 (nivel 2).
- [ ] Usuarios duplicados existentes (`JOAQUIN2` ×2, `ALEXANDE` ×2, 2 vacíos) → corregirlos a mano y luego agregar índice único en `usuario.usu_usuario`.
- [ ] El registro de docentes y personal no valida usuario duplicado; `SP_REGISTRAR_USUARIO` usa columnas que no existen (resto del sistema anterior) → revisar en la Fase 8 (módulo Usuarios).

### 2.4 Ingresos / Egresos
- [x] En el servidor: indicador y cantidad enteros ≥ 1, monto > 0 con 2 decimales, observación ≤ 255; anular exige motivo.
- [x] `monto` es el **total** del movimiento (así lo suman todos los reportes); `cantidad` es informativa.
- [x] Los totales del día ya no suman ingresos/egresos anulados.
- [x] El listado filtrado de ingresos de pensiones ahora incluye admisión/matrícula (antes los excluía).
- [x] Un ingreso de pensión/matrícula no se puede modificar ni anular desde "Ingresos" (se hace desde Pago de pensiones, para que coincida con el pago).
- [x] Filtros por fecha: `created_at` es `DATE`, así que `BETWEEN ini AND fin` ya incluye el último día (verificado, sin cambio).
- [ ] `SP_LISTAR_DIFERENCIA` devuelve el texto "S/." → **Fase 3** (formato de moneda configurable).

### 2.5 Modelos PHP
- [x] 26 funciones de modelo devolvían `null` cuando no había filas (`$arreglo` sin inicializar) → corregido.
- [x] `utilitario/validacion.php`: `entero_post`, `monto_post`, `texto_post`, `fecha_post`; aplicado en pagos, pensiones, matrícula, ingresos y egresos. Un dato inválido responde 400 con el motivo (se muestra en pantalla).
- [x] Respuestas JSON `{ok, codigo, mensaje}` en los endpoints de pago nuevos/modificados. El resto se uniforma al tocar cada módulo (Fase 8).
- [x] Matrícula con año/estudiante/aula vacíos → 400 (antes error fatal mostrando la traza al navegador).
- [ ] `cerrar_conexion()` después del `return`: no tiene efecto práctico (la conexión se cierra al terminar la función); se limpia al tocar cada modelo.

### Pruebas (BD `colegio2_test`)
- SQL directo: 27 casos del cálculo de saldo/mora/parciales/exoneración/anulación/modificación.
- HTTP: **41/41** (pago parcial y exonerado, transacción que revierte todo si una fila falla, montos inválidos, anulación con motivo, modificación con tope, pensiones del año siguiente, montos > 999.99, egresos con usuario de la sesión, matrícula con usuario repetido, eliminar matrícula con pensiones, kardex PDF).
- Regresión Fase 1: **43/43**. Panel y 46 vistas de 5 roles sin errores PHP.

**Criterio de cierre:** ✅ se registra una pensión de 1 500.50; los ingresos se enlazan con `LAST_INSERT_ID()` dentro de una transacción; un pago vencido suma la mora automáticamente.

> **Para probar en el navegador (Pago de pensiones):** elegir una pensión vencida y ver que el monto sugerido incluye la mora; marcar "Exonerar mora"; agregar dos pensiones y registrar; anular un pago (pide motivo); editar un pago.

---

## Fase 3 – Configuración regional (Latinoamérica) ✅ (2026-10-23)

**Objetivo:** que el colegio elija su país y todo el sistema (montos, zona horaria, documentos) se adapte. Un solo país y una sola moneda por colegio.

Migración `database/migraciones/003_configuracion_regional.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_003.sql`). El colegio quedó en **Perú / Sol / America/Lima**, igual que antes; los totales no cambian.

### 3.1 Base de datos
- [x] Catálogos: `monedas` (18, con símbolo, decimales y separadores), `paises` (los 20 de Latinoamérica, con locale, zona horaria, prefijo telefónico y nombre del identificador tributario) y `tipos_documento_identidad` (documento principal de cada país con largo y expresión regular). Ver [Anexo A](#anexo-a--monedas-y-países).
- [x] `empresa`: `pais_id`, `moneda_id`, `zona_horaria`, `identificacion_tributaria`, `permite_doc_extranjero` (aceptar pasaporte / carné de extranjería).
- [x] La moneda es **única para todo el colegio**; los movimientos no guardan moneda propia.
- [x] Cambiar la moneda con pagos/egresos registrados **pide confirmación** (los montos no se convierten) y queda en `historial_configuracion` (campo, antes, después, usuario, fecha).
- [x] Documentos `CHAR(8)` → `VARCHAR(20)` y teléfonos `CHAR(9)` → `VARCHAR(20)` en alumnos, padres, docentes, personal y auxiliar, y en los 8 SP que los reciben. *(De paso se corrigió `SP_REGISTRAR_DOCENTES`, que también cortaba el usuario a 8 caracteres.)*
- [x] Los 14 SP que devolvían `'S/ '` o `'S/.'` concatenado ahora devuelven el número.
- [ ] `tipo_documento_id` por persona → no hace falta con un solo país: se valida contra el documento del país o, si está permitido, pasaporte/carné. Se agrega si algún día se necesita distinguir el tipo en reportes.
- [ ] `serie_recibo` → Fase 4 (recibos).

### 3.2 Backend
- [x] `utilitario/regional.php`: `config_regional()` (se lee una vez y se guarda en la sesión), `formato_moneda()`, `nombre_documento()`, `documento_valido()`, `telefono_valido()`.
- [x] Conexiones (PDO y MPDF) usan la zona horaria del país configurado (PHP y `SET time_zone` de MySQL); se quitó `America/Lima` fijo de los 9 reportes.
- [x] Reportes MPDF (boleta, pago, kardex, cédula de matrícula, notas): montos con `formato_moneda()` y etiqueta del documento del país (DNI, Cédula, CI, RUT…).
- [x] Validación en el servidor (`documento_post`, `telefono_post`) al registrar/modificar alumnos (y padres), docentes y personal. Los datos existentes cumplen las reglas de Perú (verificado).
- [x] El formulario público de la landing acepta teléfonos de cualquier país (6 a 15 dígitos; antes exactamente 9).

### 3.3 Frontend
- [x] `js/regional.js`: `formatoMoneda()`, `documentoValido()` (mismas reglas que PHP) con la configuración impresa en `window.REGIONAL` por `view/index.php`.
  > Se usa formato propio con los separadores de la tabla `monedas` (no `Intl.NumberFormat`) para que pantalla y PDF muestren exactamente lo mismo.
- [x] Se reemplazaron todas las apariciones de `S/.` en `js/` y `view/` (ingresos, egresos, diferencia, pensiones, matrícula, pago de pensiones, totales del panel).
- [x] Al cargar cada vista, "DNI" se cambia por el documento del país en etiquetas, encabezados y placeholders; los campos de documento dejan de aceptar "solo números" si el documento lleva letras o se permiten extranjeros, y se marcan en rojo si el formato no es válido.
- [x] Pantalla **Datos regionales** (menú del ADMINISTRADOR): país (propone su moneda y zona), moneda, zona horaria, identificación tributaria (RUC/NIT/CUIT…), extranjeros, vista previa y últimos cambios.
- [ ] Formato de fecha por país → no se cambió: todo el sistema usa `dd-mm-aaaa`, que es el formato de toda Latinoamérica.

### Pruebas (BD `colegio2_test`)
- Unitarias PHP: **101/102** — el ejemplo de cada uno de los 20 documentos pasa su regla, con espacios/puntos/minúsculas; vacíos y textos se rechazan; formato de moneda en 10 países (`S/ 1,500.50`, `$ 1.500,50`, `$ 1.501` en Chile, `₡ 1 500,50`…); DNI con un dígito de menos no pasa como extranjero; teléfonos. La única "falla" es esperada: Puerto Rico no tiene documento nacional y su regla es genérica.
- Unitarias JS: **13/13** (mismos resultados que PHP).
- HTTP: **26/26** — cambio a Ecuador/USD con confirmación e historial, la sesión y otros usuarios ven `$` y "Cédula", DNI peruano rechazado en Ecuador, cédula y pasaporte aceptados, PDF con la nueva configuración, docente no puede cambiar la configuración, vuelta a Perú.
- Regresión: Fase 1 **43/43**, Fase 2 **41/41**; panel y 47 vistas de 5 roles sin errores PHP.

**Criterio de cierre:** ✅ cambiando el país a Ecuador se ve `$ 1,500.50` y se exige cédula de 10 dígitos; Argentina `$ 1.500,50`; Bolivia `Bs 1.500,50`; Perú `S/ 1,500.50`.

> **Para probar en el navegador:** *Datos regionales* → elegir Ecuador → Guardar (pedirá confirmar el cambio de moneda) → revisar Pago de pensiones, Ingresos y un kardex PDF; en Alumnos la etiqueta debe decir "Cédula". Luego volver a Perú.

---

## Fase 4 – Métodos de pago, cuentas y recibos ✅ (2026-10-23)

Migración `database/migraciones/004_metodos_pago_recibos.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_004.sql`). Ingresos válidos sin cambio (9 490.00).

### 4.1 Base de datos
- [x] `metodos_pago`: EFECTIVO, TARJETA DE DÉBITO, TARJETA DE CRÉDITO, TRANSFERENCIA BANCARIA, DEPÓSITO EN CUENTA, BILLETERA DIGITAL, CHEQUE; cada uno indica si pide n.° de operación y caja/cuenta, y su comisión %. Se agregan más desde la pantalla (Yape, Plin, Nequi, Mercado Pago…).
- [x] `cuentas_financieras`: cajas, cuentas bancarias y billeteras del colegio (banco, número, CCI/CBU, titular, saldo inicial). Arranca con **CAJA PRINCIPAL**; **se pueden agregar todas las cuentas que se necesiten**.
- [x] `pagos` (recibo): serie + número **correlativo** (`R001-000123`, serie en `empresa.serie_recibo`), matrícula, fecha, total, observación, voucher, usuario, estado y anulación.
  - El número sale de `correlativos` dentro de la transacción del cobro: **sin repetidos ni saltos** (si el cobro falla no se consume; probado con 5 cobros simultáneos).
- [x] `pagos_metodos`: un recibo puede pagarse con **varias formas** (efectivo + tarjeta…), cada una con su caja/cuenta, monto, referencia y comisión.
- [x] `pago_pensiones.id_pago`: cada concepto es una línea de su recibo.
- [x] **Pagos anteriores:** se agruparon en 16 recibos históricos (uno por alumno y día, R001-000001 a 000016) con el método **"NO REGISTRADO (HISTÓRICO)"**, que no se puede elegir ni editar. No se inventó el método real.

### 4.2 Funcionalidad
- [x] Pantalla de cobro: sección **Forma de pago** con método, caja/cuenta (se sugiere: efectivo → caja, billetera → billetera, resto → banco), monto y n.° de operación; "Agregar otra forma de pago"; aviso de lo que falta asignar. Con una sola forma, el monto se ajusta solo al total.
- [x] El servidor exige que las formas de pago **sumen exactamente** el total y valida operación/cuenta según el método.
- [x] **Voucher opcional** (imagen o PDF ≤ 5 MB) y observación por recibo. Los vouchers se guardan en una carpeta bloqueada y se ven con `controlador_ver_voucher.php` (con sesión).
- [x] **Recibo PDF** (`view/MPDF/REPORTE/recibo.php`): número, datos del colegio e identificación tributaria, conceptos (mes y año, mora exonerada), formas de pago con caja/cuenta y referencia, usuario que lo registró; marca de agua **ANULADO** si corresponde. Se ofrece imprimirlo al terminar el cobro.
- [x] **Matrícula**: el formulario pide forma de pago, caja/cuenta y referencia, y genera su recibo (admisión + alumno nuevo + matrícula).
- [x] Historial de pagos (administrador y estudiante): columnas **Recibo** y **Forma de pago**; botón "Recibo".
- [x] **Anular** un concepto anula **el recibo completo** (todas sus líneas e ingresos; el recibo queda visible como ANULADO).
- [x] **Editar** un pago solo si su recibo tiene una forma de pago (se ajustan la forma de pago y el total); con varias formas hay que anular y registrar de nuevo.
- [x] Pantalla **Formas de pago y cuentas** (menú del ADMINISTRADOR): alta/edición/desactivación de métodos y cuentas; muestra lo cobrado en cada cuenta.
- [x] Corrección de seguridad: un estudiante podía ver la tabla de pagos de **cualquier** matrícula cambiando el id (se verificaba el rol, no la matrícula). Ahora solo la suya; también sus recibos.
- [x] Al abrir el cobro se limpia la tabla: antes quedaban las filas del alumno anterior y podían cobrarse al siguiente.
- [ ] Cada forma de pago genera el **movimiento en su cuenta** → **Fase 5** (la información ya queda en `pagos_metodos`: método, cuenta y monto).

### Pruebas (BD `colegio2_test`)
- HTTP Fase 4: **45/45** — catálogos y permisos, pago mixto efectivo + transferencia, número correlativo, validaciones que no consumen número, Yape sin cuenta, voucher (guardado, protegido, PHP rechazado), recibo PDF y acceso del estudiante (propio sí, ajeno no), editar/anular por recibo, matrícula con forma de pago (300.00, 3 conceptos), 5 cobros simultáneos sin números repetidos.
- Integridad tras todas las pruebas: suma de conceptos = suma de formas de pago = suma de recibos (9 333.00).
- Regresión: Fase 1 **43/43**, Fase 2 **41/41**, Fase 3 **26/26**; panel y vistas de 5 roles sin errores.

**Criterio de cierre:** ✅ un pago mixto efectivo + transferencia se registra en la caja y en la cuenta bancaria, con su número de recibo, y el recibo PDF lo refleja.

> **Para probar en el navegador:** *Formas de pago y cuentas* → crear la cuenta del banco y, si se usa, "YAPE". Luego *Pago de pensiones* → Pagar → agregar una pensión → dividir entre Efectivo y Transferencia (con n.° de operación) → Registrar → imprimir el recibo.

---

## Fase 5 – Módulo de contabilidad (rol Contador/a) ✅ (2026-10-23)

**Objetivo:** que la contadora maneje todo el dinero del colegio desde un solo módulo.

Migración `database/migraciones/005_contabilidad.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_005.sql`). Totales sin cambio (ingresos 9 490.00, egresos 1 751.99).

### 5.1 Rol y menú
- [x] Rol **CONTADOR** creado. Se registra como cualquier personal administrativo eligiendo el tipo "CONTADOR" (el usuario ya no se corta a 20 caracteres).
- [x] Menú propio: **Cobranzas** (Pago de pensiones, Pensiones), **Ingresos y egresos** (Ingresos, Egresos, Categorías), **Cajas y bancos**, **Formas de pago y cuentas**.
- [x] Panel de inicio: ingresos y egresos de hoy y del mes, resultado del mes, recibos emitidos hoy y saldo de cada caja/cuenta con su último cierre.
- [x] El CONTADOR **no** accede a alumnos, usuarios, notas, tareas, enfermería ni psicología (permisos generados desde su menú; probado).
- [x] El ADMINISTRADOR también tiene "Cajas y bancos".
- [ ] Planilla docente → Fase 6. Reportes de utilidades → Fase 7.

### 5.2 Categorías contables
- [x] `indicadores` con `codigo`, `padre_id` y `es_grupo`: **plan de cuentas simplificado** con grupos 4.1 Ingresos académicos, 4.2 Otros ingresos, 5.1 Remuneraciones (docentes / administrativas), 5.2 Servicios básicos, 5.3 Alquiler y mantenimiento, 5.4 Materiales, 5.5 Impuestos y comisiones (comisiones bancarias), 5.9 Otros egresos.
- [x] Las categorías existentes se ubicaron en su grupo (pensiones → académicos, uniformes → otros ingresos, luz y agua → servicios). Los grupos no se pueden elegir en un movimiento; los selectores muestran "GRUPO › categoría".
- [ ] Elegir el grupo al crear una categoría desde la pantalla Categorías (hoy queda sin grupo) → Fase 8.

### 5.3 Movimientos de caja y bancos
- [x] `movimientos_cuenta`: **libro único** de entradas y salidas de cada caja/cuenta, con origen (PAGO, INGRESO, EGRESO, TRANSFERENCIA, AJUSTE; PLANILLA para la Fase 6). Lo alimentan automáticamente:
  - cada forma de pago de un recibo (pensiones y matrícula); al anular el recibo, sale de la caja;
  - ingresos manuales y egresos (al modificarlos se actualiza el movimiento; al anularlos se anula);
  - depósitos/transferencias y ajustes de cierre.
- [x] **Saldo** de cada cuenta = saldo inicial (con su fecha) + movimientos válidos. No se puede gastar ni depositar más efectivo del que hay en una **caja**; un banco sí puede quedar en negativo (sobregiro o depósitos aún no registrados).
- [x] **Depósitos** (caja → banco), **transferencias** entre cuentas y **retiros** (banco → caja), con n.° de operación; se pueden anular (revierte ambas cuentas).
- [x] **Cierre de caja diario**: saldo al inicio, entradas, salidas, esperado vs. contado y diferencia; si hay diferencia exige explicación y, opcionalmente, registra un **ajuste** para que el saldo quede igual a lo contado. Un cierre por caja y día.
- [x] **Conciliación bancaria**: en los movimientos de bancos/billeteras se marca cada uno como verificado contra el extracto.
- [x] Pantalla **Cajas y bancos**: tarjetas con el saldo de cada cuenta, extracto por fechas con saldo acumulado, depósitos/transferencias y cierre de caja con historial.

### 5.4 Egresos e ingresos mejorados
- [x] Egreso: fecha (no futura), categoría, forma de pago, **caja/cuenta de salida**, n.° de operación, **beneficiario/proveedor**, **n.° de comprobante** y **comprobante escaneado** opcional (carpeta protegida; se abre con sesión).
- [x] Ingreso manual: fecha, forma de pago, caja/cuenta de entrada y referencia.
- [x] Listados con columna "Forma de pago" (método · cuenta, beneficiario, comprobante y enlace al adjunto).
- [x] **Corregido:** el filtro de ingresos/egresos combinaba categoría **O** fechas (filtrar "luz en octubre" traía toda la luz de cualquier fecha y todo octubre); ahora es categoría **Y** fechas.
- [x] **Corregido:** "Ingresos diversos" mostraba también los ingresos de pensiones del día.
- [x] **Corregido:** el PHP del servidor usa `Europe/Berlin` en `php.ini`; las fechas de PHP iban 6–7 horas adelantadas a las de MySQL (por la noche un egreso "de hoy" se rechazaba como futuro). Ahora la zona horaria configurada se aplica al iniciar cada petición.
- [ ] Los egresos de la planilla docente se generarán solos en la Fase 6.

### Pruebas (BD `colegio2_test`)
- HTTP Fase 5: **53/53** — menú y panel del contador, permisos (sí egresos/cajas; no alumnos/usuarios; docente no entra a contabilidad), saldo inicial, cobro en efectivo suma a la caja, egresos (grupo y fecha futura rechazados, saldo insuficiente en caja, banco en negativo, operación obligatoria, comprobante PDF), modificar/anular egreso ajusta la caja, filtro categoría Y fecha, ingreso al banco, depósito (con y sin saldo, misma cuenta, anulación), recibo anulado sale de la caja, extracto = saldo, conciliación, cierre con faltante y ajuste, matrícula en efectivo entra a la caja, integridad (cada forma de pago y cada egreso con cuenta tiene su movimiento).
- Regresión: Fase 1 **43/43**, Fase 2 **41/41**, Fase 3 **26/26**, Fase 4 **45/45**; panel y vistas de 6 roles (incluido CONTADOR) sin errores.

**Criterio de cierre:** ✅ la contadora inicia sesión, ve solo su menú, registra un egreso con cuenta y comprobante, deposita el efectivo del día al banco y el saldo de ambas cuentas cuadra.

> **Antes de usarlo en el colegio:** en *Formas de pago y cuentas* poner el **saldo inicial** real de la caja y de cada banco con la fecha desde la que se lleva el control; luego crear el usuario de la contadora en *Personal administrativo* con tipo **CONTADOR**.

---

## Fase 6 – Pago a docentes (planilla) ✅ (2026-10-23)

Migración `database/migraciones/006_planilla_docente.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_006.sql`). Totales sin cambio.

### 6.1 Base de datos
- [x] `tipos_contrato`: **TIEMPO COMPLETO** y **TIEMPO PARCIAL** (sueldo mensual) y **POR HORAS** (tarifa × horas).
- [x] `contratos_docente`: tipo, fecha de inicio/fin, sueldo o tarifa, horas semanales, **forma de pago del docente** (efectivo, transferencia, depósito, billetera…), banco y cuenta del docente. Historial: un contrato nuevo (aumento, cambio de tipo) **finaliza el anterior** el día previo.
- [x] `conceptos_planilla`: ingresos (BONO, HORAS EXTRA, MOVILIDAD) y descuentos (TARDANZAS, FALTAS, **APORTE DE LEY**), en monto fijo o **porcentaje del sueldo**; con valor por defecto se aplican a todos al generar.
- [x] `planillas` (BORRADOR → APROBADA → PAGADA / ANULADA), `planilla_detalle` (copia del contrato del mes: horas, base, ingresos, descuentos, adelantos, neto, pagado) y `planilla_detalle_conceptos`.
- [x] `pagos_docentes`: pagos de planilla y **adelantos**, cada uno con su forma de pago, caja/cuenta, referencia y **egreso** asociado.

### 6.2 Reglas de cálculo
- [x] Tiempo completo / parcial: sueldo mensual del contrato (editable en el borrador, p. ej. proporcional si ingresó a mitad de mes).
- [x] Por horas: se proponen horas semanales × 4 y se ajustan con las horas dictadas; base = horas × tarifa.
- [x] Neto = base + ingresos − descuentos − adelantos. No se aprueba con netos negativos.
- [ ] Tomar las horas dictadas desde `horarios`/asistencia docente → requiere registrar asistencia docente (Fase 8).
- [ ] Retenciones de ley con cálculo automático por país → hoy es un concepto en % configurable (ver Pendientes).

### 6.3 Flujo
1. [x] El contador registra los **contratos** y genera la planilla del mes (todos los docentes con contrato vigente; descuenta los adelantos pendientes).
2. [x] Ajusta horas, sueldos, bonos y descuentos (BORRADOR).
3. [x] El **ADMINISTRADOR aprueba** (el contador no puede; también puede volver a borrador si aún no hay pagos).
4. [x] Se paga a cada docente (total o en partes) con la forma de pago propuesta de su contrato → **egreso** en "Remuneraciones docentes" y **salida** de la caja/cuenta elegida. Al pagar todo, la planilla queda PAGADA.
5. [x] **Adelantos**: egreso inmediato; se descuentan en la próxima planilla (o en la del mes si está en borrador). No se anula un adelanto ya descontado en una planilla aprobada.
6. [x] Anular un pago devuelve el dinero a la caja/cuenta y reabre lo pendiente. Los egresos de planilla no se modifican ni anulan desde la pantalla Egresos.
7. [x] **Boleta de pago PDF** (ingresos, descuentos, adelantos, neto, pagos realizados y firmas); el docente ve las suyas en **Mis boletas de pago**.

### 6.4 Pantallas
- [x] **Planilla docente** (ADMINISTRADOR y CONTADOR): pestañas Planillas (generar, detalle, ajustar, aprobar, pagar, boletas), Contratos, Adelantos y Conceptos.
- [x] Docente → **Mis boletas de pago** (solo las suyas y de planillas aprobadas/pagadas).
- [ ] Contrato desde el formulario de Docentes → se dejó en Planilla > Contratos (un solo lugar para lo económico).
- [ ] Planilla del personal administrativo con la misma estructura → Fase 8.

### Pruebas (BD `colegio2_test`)
- SQL directo: 22 casos del flujo completo.
- HTTP Fase 6: **46/46** — contratos de los 3 tipos y validaciones, adelanto, generar (y no repetir el mes), horas, aporte 10 %, bono, netos 1500/900/1100, no pagar sin aprobar, **el contador no aprueba** y el administrador sí, pagos total/parcial y con transferencia (operación obligatoria), caja 10 000 → 7 700, egresos de planilla protegidos, anulaciones, planilla PAGADA, boletas (el docente solo las suyas; el estudiante ninguna), contrato nuevo finaliza el anterior, integridad pagos = egresos.
- Regresión: Fases 1–5 **208/208**; vistas de 6 roles sin errores.

**Criterio de cierre:** ✅ se genera la planilla con un docente a tiempo completo (pago en efectivo) y otros por horas y a tiempo parcial (pago por transferencia); los pagos aparecen en egresos y salen de sus cuentas.

> **Para empezar a usarlo:** *Planilla docente → Contratos* → registrar el contrato de cada docente; en *Conceptos* poner el % del aporte de ley de su país (si corresponde); luego *Planillas* → Generar.

---

## Fase 7 – Reportes financieros y utilidades ✅ (2026-10-23)

Migración `database/migraciones/007_reportes_financieros.sql` (solo lectura; aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_007.sql`). Totales sin cambio.

Pantalla **Reportes financieros** (ADMINISTRADOR y CONTADOR) con filtros por período (con atajos: hoy, este mes, mes anterior, este año, año escolar), año o año escolar/nivel/grado según la pestaña. Cada tabla se exporta a **Excel** (montos como número) e **imprime**; utilidad, flujo y morosidad también en **PDF** con el encabezado del colegio.

- [x] **Estado de resultados / utilidad** por período: ingresos y egresos por grupo del plan de cuentas › categoría; la cobranza se desglosa en matrícula, admisión, alumno nuevo, pensiones y mora. Utilidad (o pérdida) y **margen %**. PDF con firmas.
- [x] **Flujo de caja** por caja/cuenta: saldo inicial, cobros, transferencias recibidas, ajustes, pagos (incluida la planilla), transferencias enviadas y saldo final (= saldo del libro). Los cobros/pagos históricos sin caja/cuenta se informan aparte para que cuadre con la utilidad. PDF.
- [x] **Comparativo mensual**: ingresos, egresos y utilidad de cada mes contra el año anterior, con gráfico (barras + líneas) y variación %.
- [x] **Morosidad**: pensiones vencidas con saldo a hoy, por alumno (meses, días de atraso, pensión, mora, deuda y teléfonos de los padres), por pensión y por grado; filtros por año escolar, nivel y grado/sección; PDF para cobranza agrupado por grado. Usa la **misma regla de mora** que la pantalla de cobro (vista `vw_estado_pensiones`, verificada contra `SP_SALDO_PENSION` para cada matrícula × pensión).
- [x] **Proyección de pensiones** del año escolar por mes: esperado, cobrado, vencido sin cobrar, mora pendiente, por vencer y % de avance.
- [x] **Costo de planilla** por mes y tipo de contrato (sueldo/horas, bonos, costo bruto, descuentos, adelantos, neto, pagado) y resumen por tipo con % del costo.
- [x] **Formas de pago**: cobros y pagos por forma de pago y caja/cuenta, con comisiones; cuadra con el estado de resultados.
- [x] **Cierres de caja** del período con faltantes y sobrantes.
- [x] **Panel del contador**: además de ingresos/egresos/resultado, **pensiones vencidas por cobrar**, **alumnos con deuda** y gráfico de ingresos y egresos del año.
- [x] **Corregido:** al entrar, el contador (y enfermería/psicología) veía "Acceso denegado": los JS de tareas, cargados para todos los roles, pedían los grados del docente al iniciar. Ahora solo los pide la pantalla de tareas.
- [x] **Aviso de datos:** la tabla `pensiones` no tiene año; el colegio la usa como calendario por nivel y **27 pensiones tienen vencimiento 2024/2025 para el año escolar 2026**. La morosidad las cuenta vencidas desde esa fecha (como la pantalla de cobro) y muestra un aviso para corregirlas en Pensiones. Vincular pensiones al año escolar → Fase 8.

### Pruebas (BD `colegio2_test`)
- HTTP Fase 7: **75/75** — accesos (contador y administrador sí; docente y estudiante 403, también en PDF), validación de fechas/rango/año, **utilidad del mes = suma manual de ingresos y egresos válidos** (420 − 280 = 140, margen 33.3 %; el egreso anulado no suma), historial completo = SQL, desglose de cobranza, comparativo (mes y año = SQL), flujo (saldo final = saldo del libro de cada cuenta; depósito sale de caja y entra al banco sin cambiar el total), formas de pago = estado de resultados, morosidad (vista = pantalla de cobro en todas las pensiones; total = panel; filtros por nivel y aula; al cobrar baja 110), proyección (esperado = cobrado + vencido + por vencer), costo de planilla 3 000 (2 000 + 25 × 40 h), cierre con faltante 10, PDF de los tres reportes.
- Navegador (Chrome headless): las 8 pestañas y el panel cargan sin errores de JavaScript.
- Regresión: Fases 1–6 **254/254**; vistas de 7 roles sin avisos de PHP.

**Criterio de cierre:** ✅ el reporte de utilidad de un mes coincide con la suma manual de ingresos y egresos válidos (y en producción, el historial completo da 9 490.00 − 1 751.99).

---

## Fase 8 – Mejoras generales por módulo ✅ (2026-10-24)

Aplicar en cada módulo una vez cerradas las fases 1–3 (dependen de la seguridad y del formato regional). Se avanza por bloques:

| Bloque | Contenido | Estado |
|--------|-----------|--------|
| **8A** | Integridad de datos: pensiones por año escolar, usuarios únicos, categorías con grupo | ✅ 2026-10-24 |
| **8B** | Planilla del personal administrativo (misma estructura que la docente) y asistencia docente para las horas de la planilla | ✅ 2026-10-24 |
| **8C** | Seguridad en pantallas: escapar los datos que muestran las tablas (DataTables) en todos los módulos; validación de notas según la escala del país | ✅ 2026-10-24 |
| **8D** | Matrícula (vacantes por aula, becas/descuentos), choque de horarios, confidencialidad de enfermería/psicología, anti-spam del landing, auditoría de tablas financieras; retiro de módulos de otro proyecto | ✅ 2026-10-24 |

### Bloque 8A ✅ (2026-10-24)

Migración `database/migraciones/008_integridad_pensiones_usuarios_categorias.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_008.sql`). Totales sin cambio.

**Pensiones por año escolar**
- [x] `pensiones.id_anio` (obligatorio, con llave foránea) y **único año + nivel + mes**. Las existentes se asignaron al año de las matrículas que las pagaron (o al año activo): las 30 quedaron en el año escolar activo.
- [x] El cobro solo ofrece y acepta pensiones del **nivel y del año escolar de la matrícula**; la morosidad, la proyección y el panel también. Antes, al crear las pensiones del año siguiente, cada alumno habría aparecido debiendo las de ambos años.
- [x] Pantalla Pensiones: filtro por año escolar y nivel, columna de pagos, **Generar cronograma** (un nivel, meses desde/hasta —puede cruzar el fin de año, p. ej. septiembre → junio—, día de vencimiento o último día, precio y mora; no toca los meses existentes).
- [x] Validación: el vencimiento debe caer dentro del calendario del año escolar (±2 meses). Con pagos no se cambia año, nivel ni mes (sí vencimiento, precio y mora) y no se elimina.
- [x] **Ajustar vencimientos al año escolar**: aviso en Pensiones con vista previa y confirmación; mantiene el día y pone el año que corresponde al mes; queda historial en `pensiones_ajustes`. **No se aplicó solo**: en producción hay 27 pensiones con vencimiento 2024/2025 en el año escolar 2026 → usar el botón *Revisar y ajustar* en Pensiones.
- [x] **Corregido:** los procedimientos de pensiones comparaban `mes = MES` (el parámetro tapaba a la columna), así que la validación de "mes repetido" fallaba; ahora los parámetros llevan prefijo `P_`.

**Usuarios únicos**
- [x] Índice único en `usuario.usu_usuario`. Había 3 nombres repetidos/vacíos entre estudiantes (el login no podía distinguirlos). Se conservó el primero; los demás usan **el documento del alumno** como usuario; el que no pertenecía a nadie quedó inactivo. Registro en `usuarios_renombrados`:
  - JOAQUIN2 (2.º, Joaquín Jorge Peña) → `22626969`; usuario vacío (Alexander Peralta) → `55516555`; usuario vacío (Mary Carmen Farfán) → `15515115`; ALEXANDE sin alumno → `ALEXANDE_65` (inactivo).
- [x] Alta de docentes y personal: rechaza usuario vacío o ya usado (antes fallaba con error de BD o creaba repetidos) y une a cada persona con **su** usuario (antes `MAX(usu_id)`, que con dos altas simultáneas cruzaba cuentas). Modificar usuario no permite tomar el nombre de otra cuenta.
- [x] `SP_REGISTRAR_USUARIO` usaba columnas que no existen; corregido.

**Categorías (plan de cuentas)**
- [x] Al crear o editar una categoría se elige su **grupo** (4.x ingresos, 5.x egresos) y recibe código (p. ej. 5.2.3). Las existentes recibieron código.
- [x] **Corregido:** editar una categoría siempre validaba mal (variable mal escrita) y el borrado eliminaba categorías con movimientos (en reportes quedaban "SIN CATEGORÍA"). Ahora no se borran las que tienen ingresos/egresos (se desactivan), los grupos ni las que usa el sistema (pensiones, planilla, comisiones); estas tampoco cambian de tipo ni de grupo.
- [x] Nombres validados en el servidor y escapados al mostrarlos.

**Pruebas (BD `colegio2_test`)**: Bloque 8A **68/68**; regresión Fases 1–7 **329/329** (se actualizó la prueba de pensiones de la Fase 2 al nuevo formato con año escolar); Pensiones y Categorías revisadas en Chrome headless sin errores de JavaScript.

### Bloque 8B ✅ (2026-10-24)

Migración `database/migraciones/009_planilla_administrativa_asistencia.sql` (aplicada en `colegio2` tras el respaldo `respaldos/colegio2_*_antes_009.sql`). Totales sin cambio; la planilla docente de octubre ya aprobada se conserva.

**Planilla del personal administrativo**
- [x] Contratos, planilla mensual, adelantos, pagos y boletas sirven ahora para **docentes o personal administrativo** (auxiliar, enfermera, psicóloga, contadora…). Las tablas de la Fase 6 tienen `docente_id` **o** `personal_id` (restricción que exige uno solo) y la vista `vw_personal_planilla` une a ambos.
- [x] Pantalla **Planilla de personal** (antes "Planilla docente") con selector *Docentes / Personal administrativo*: cada tipo tiene su propia planilla del mes, aprobación (solo ADMINISTRADOR), adelantos y pagos.
- [x] Los pagos y adelantos del personal administrativo van a **5.1.2 REMUNERACIONES ADMINISTRATIVAS** (los docentes siguen en 5.1.1); salen de la caja/banco elegido igual que en la Fase 6.
- [x] **Mis boletas de pago** también para auxiliar, enfermera, psicóloga y contadora; cada uno ve solo las suyas (la boleta PDF lo controla).
- [x] El reporte *Costo de planilla* separa "DOCENTES · …" y "ADMINISTRATIVOS · …".

**Asistencia docente**
- [x] Tabla `asistencia_docente` (un registro por docente y día): PRESENTE, TARDANZA (con minutos), FALTA (0 horas) o JUSTIFICADA, horas programadas y **horas dictadas**.
- [x] Pantalla **Asistencia docente** (ADMINISTRADOR y AUXILIAR): registrar el día con las horas de su **horario** propuestas (desde `horarios` del año escolar), "marcar todos presentes", y resumen del mes exportable a Excel.
- [x] Al **generar la planilla docente**, los contratos **por horas** cobran la suma de horas dictadas del mes (antes: horas semanales × 4, que queda solo si no hay asistencia registrada). El detalle muestra de dónde salieron las horas (asistencia / estimado / ajustado) y el resumen de faltas y tardanzas para decidir descuentos.
- [x] Con la planilla docente del mes aprobada, su asistencia ya no se modifica.
- [ ] Descuento automático por faltas/tardanzas de los contratos mensuales → depende de la política de cada colegio/país; hoy se ve el resumen y se agrega el concepto FALTAS/TARDANZAS a mano.

**Pruebas (BD `colegio2_test`)**: Bloque 8B **51/51** (contratos y planillas por tipo sin mezclarse, asistencia → 9 h × 25 = 225, adelanto y pago del auxiliar en 5.1.2, caja 10 000 → 8 500, bloqueo de asistencia tras aprobar, boletas propias y ajenas, reporte); regresión Fases 1–8A **397/397**; Planilla y Asistencia docente revisadas en Chrome headless sin errores.

### Bloque 8C ✅ (2026-10-24)

Migración `database/migraciones/010_escala_notas.sql`.

**Datos mostrados como texto, nunca como HTML (XSS)**
- [x] `js/texto_seguro.js`: `textoSeguro()` escapa `& < > " '` y el acento grave; antes decodifica las entidades de los registros antiguos que se guardaron con `htmlspecialchars` (se ve "O'Brien" y no "O&#039;Brien").
- [x] **DataTables**: toda columna sin `render` propio (517 columnas en 47 tablas) se muestra escapada por defecto; antes cualquier nombre, tema, título o comentario con `<script>`/`onerror` se ejecutaba en el navegador de quien lo viera (incluido el aviso de comunicados del inicio, que ven todos los roles).
- [x] HTML armado a mano: 168 concatenaciones (listas desplegables, títulos de SweetAlert, `innerHTML`) y 18 `render` con datos (fotos, archivos, recibos, calificaciones, campos editables de notas y asistencia) usan `textoSeguro()`.
- [x] Prueba en Chrome headless con textos `<img onerror>` en especialidad, alumno, sección, comunicado y categoría: **0 ejecuciones** en las 7 pantallas (sin la protección: 11 a 14 por pantalla). Todas las vistas (47) cargan sin errores de JavaScript.
- [x] **Corregido de paso:** 56 listas desplegables lanzaban un error de JavaScript cuando no había datos (variable `cadena` sin declarar en la rama "no hay registros"), p. ej. *Reporte de asistencia del estudiante*.

**Escala de notas según el país**
- [x] Cada país trae su escala: Perú 0–20 (aprueba 11; también AD/A/B/C, aprueban AD/A/B), Ecuador 0–10 (7), Bolivia 0–100 (51), Argentina 1–10 (6), Chile 1.0–7.0 (4.0), Colombia y Panamá 1.0–5.0 (3.0), México 5–10 (6), Venezuela 1–20 (10), Paraguay 1–5 (2), Uruguay 1–12 (6), Brasil/El Salvador 0–10 (6), Costa Rica 0–100 (65), Cuba/Guatemala/Nicaragua/Puerto Rico 0–100 (60), Honduras/R. Dominicana 0–100 (70).
- [x] *Configuración > Datos regionales*: escala del colegio (mínimo, máximo, nota aprobatoria, decimales, letras y letras que aprueban); se propone la del país y queda en el historial.
- [x] Notas del bimestre, notas de padres y calificaciones de tareas se validan con la escala en el servidor y en la pantalla (antes: solo "no mayor a 20" en el navegador). Los colores de aprobado/desaprobado usan la nota aprobatoria configurada (antes 11 fijo).
- [x] La calificación de tareas era entero (6.5 se guardaba 6, no admitía letras): ahora texto como las notas.
- [x] Quitado `FILTER_SANITIZE_STRING` (obsoleto en PHP 8.1+) de la edición de notas.

**Pruebas**: Bloque 8C 26/26 + prueba unitaria de la escala; regresión de todas las fases **474/474**.

> **Herramientas de prueba en el repositorio (`tests/`).** Hasta la Fase 8B las suites vivían en una carpeta temporal de la sesión, que se borró el 2026-10-24. Se recrearon en `tests/` las de las Fases 7, 8A, 8B y 8C, la prueba de escapado en el navegador, el reinicio de la base de prueba y la revisión de todas las vistas (`tests/ejecutar_todo.sh`). Las suites de las Fases 1–6 y el generador de `controller/_permisos.php` se perdieron: rehacerlas es parte de la Fase 9. Mientras tanto, un controlador nuevo para un rol distinto de ADMINISTRADOR se agrega a mano en `controller/_permisos.php`.

### Bloque 8D ✅ (2026-10-24)

Migración `database/migraciones/011_matricula_horarios_salud_auditoria.sql`, generada con `database/generadores/generar_011.php` (toma los procedimientos vigentes y las columnas reales de cada tabla).

**Limpieza**
- [x] Retirados los módulos de otro proyecto (empleado, tipo de documento, áreas y trámites: 39 archivos). No estaban en el menú y sus tablas no existen; respondían con errores fatales de PHP que mostraban la ruta del servidor.

**Permisos corregidos** (errores del mapeo automático por nombres de función)
- [x] El **estudiante podía registrar y modificar horarios** y eliminar asignaciones de cursos de docentes: ahora solo el auxiliar (y el administrador).
- [x] **Confidencialidad de salud**: la enfermera podía registrar y modificar atenciones psicológicas y la psicóloga las de enfermería. Ahora cada una solo las suyas, y los procedimientos de modificación filtran por tipo (con el id de otro tipo no se modifica nada).

**Matrícula: becas y vacantes**
- [x] **Beca o descuento** por matrícula (botón *Beca* en Matrículas): porcentaje o monto por pensión, con motivo y fecha desde (se aplica a las pensiones que vencen desde esa fecha; lo cobrado no cambia). El precio con beca lo usan el cobro, la corrección de pagos, la morosidad, la proyección y el panel (`FN_PRECIO_PENSION`).
- [x] **Capacidad por aula** (opcional; vacía = sin límite) y columna *Matriculados / capacidad* en Aulas. No se matricula (ni se cambia de aula) a un aula llena; la capacidad no puede bajar de los matriculados del año activo.

**Horarios sin cruces**
- [x] Al registrar o editar el horario se rechaza el bloque si **el aula ya tiene otro curso** a esa hora o si **el docente ya dicta en otra aula** en un horario que se superpone (se explica cuál). Todas las filas se guardan en una transacción: si una cruza, no se guarda ninguna (antes quedaban horarios a medias).

**Formulario público (landing)**
- [x] Campo trampa oculto, tiempo mínimo de 3 s con el formulario abierto, máximo 3 solicitudes por hora por IP y largo máximo de los campos. Se quitó `Access-Control-Allow-Origin: *` y el detalle de errores internos en la respuesta.

**Auditoría**
- [x] Tabla `auditoria` llenada por triggers en 19 tablas (recibos, formas de pago, pagos de pensión, ingresos, egresos, pensiones, matrículas y becas, cajas, transferencias, cierres, formas de pago, planillas, contratos, conceptos, categorías, configuración del colegio y usuarios): quién, cuándo, desde qué IP, antes y después. La contraseña solo se informa como "cambiada". Los cambios sin efecto real no se registran.
- [x] Pantalla **Configuración → Auditoría** (solo administrador) con filtros por tabla, fechas y número de registro; muestra campo por campo qué cambió; exportable a Excel.

**Ya resuelto en fases anteriores:** imágenes de comunicados validadas (Fase 1).

**Pruebas**: Bloque 8D 44/44 (`tests/pruebas_fase8d.sh`).

### Mejoras por módulo (pendientes para después de la Fase 8)

| Módulo | Mejoras |
|--------|---------|
| **Usuarios / Roles** | Permisos por módulo configurables (tabla `rol_permisos`) en lugar de `if ($_SESSION['S_ROL'] == ...)` repetidos en `view/index.php`; cambio de contraseña obligatorio en primer ingreso; recuperar contraseña por email. |
| **Empresa** | Datos regionales (Fase 3), logo validado, datos tributarios (RUC/NIT/CUIT/RUT). |
| **Alumnos** | ~~Validación de documento según país~~ (Fase 3); evitar duplicados por documento; buscador con paginación del servidor (DataTables server-side) para colegios grandes; ficha de apoderado con más de 2 responsables. |
| **Matrícula** | Validar vacantes por aula; impedir doble matrícula del mismo alumno en el mismo año; generar automáticamente el cronograma de pensiones del alumno al matricular; descuentos/becas (% o monto) por alumno. |
| **Pensiones** | ~~Vincular al año escolar~~ y ~~generar el cronograma en un clic~~ (8A ✅); mora fija o % por día/mes; becas y descuentos por hermanos. |
| **Docentes** | Contrato y modalidad de pago (Fase 6); validación de documento; historial. |
| **Personal administrativo** | ~~Reutilizar la planilla de la Fase 6~~ (8B ✅). |
| **Asistencia** | ~~Registro de asistencia docente~~ (8B ✅); alertas a padres por inasistencias. |
| **Notas / Exámenes / Tareas** | Validar rangos de nota según la escala del país (0–20 Perú, 1–10 Ecuador, 1–7 Chile…) desde configuración; bloquear edición de periodos cerrados. |
| **Horarios / Aulas** | Validar choque de horario de docente y aula. |
| **Comunicados / Solicitudes** | Validar archivos adjuntos; notificaciones; estado leído. |
| **Enfermería / Psicología** | Confidencialidad: solo el rol correspondiente y ADMINISTRADOR. |
| **Reportes MPDF** | Unificar conexión con PDO; moneda y documento regional; usar en los reportes antiguos el encabezado común `utilitario/reporte_pdf.php` (Fase 7). |
| **Landing (`landing.html`)** | Formulario de solicitudes con validación y protección anti-spam (honeypot / captcha). |
| **General** | Mensajes de error claros con SweetAlert; registro de auditoría (`auditoria`: quién, qué, cuándo, valor anterior/nuevo) para tablas financieras; paginación server-side en listados grandes. |

---

## Fase 9 – Pruebas, documentación y despliegue ✅ (2026-10-24)

**Seguridad encontrada al preparar el despliegue**
- [x] **Los respaldos completos de la base (`respaldos/*.sql`) y la carpeta `.git` se podían descargar desde el navegador.** El `.htaccess` ahora bloquea `.sql`, `.md`, `.sh`, logs, `package.json` y `.git`; `respaldos/` y `scripts/` tienen su propio `.htaccess`. Si el servidor del colegio ya tenía esas carpetas publicadas, cambiar las claves (ver `docs/despliegue.md`).
- [x] Retiradas páginas de otro proyecto que seguían publicadas (`registrar.php` y `seguimiento.php` de trámites DIRESA), la página por defecto del hosting y un manual ajeno.

**Permisos por rol revisados**
- [x] Nueva herramienta `tests/herramientas/revisar_permisos.php`: sigue el menú de cada rol hasta los controladores que realmente usa (sin unir funciones por nombre entre archivos, que causó los errores del generador anterior).
- [x] Se quitaron **89 permisos** que ningún rol usaba (p. ej. notas de otros alumnos para enfermería, psicología o la contadora; saldos de pensiones y listas de matrícula para el estudiante) y se agregaron 2 que faltaban. Resultado: 0 sobrantes, 0 faltantes.
- [x] `tests/navegador/revisar_roles.sh`: con un usuario de cada rol abre todas las pantallas de su menú en Chrome headless; los 7 roles sin errores ni "Acceso denegado".

**Pruebas**
- [x] BD de prueba `colegio2_test` reconstruida desde el respaldo + todas las migraciones (`tests/reset_test_db.sh`); cada suite corre sobre una base recién reiniciada (`tests/ejecutar_todo.sh`).
- [x] Suites: Fases 1–6 (33, rehecha: login y bloqueo, 401/403, CSRF, subida de archivos, mora, cobro duplicado, pago mixto, recibo y anulación, egresos y caja, planilla), Fase 7 (26), 8A (37), 8B (26), 8C (23), 8D (44): **189 comprobaciones, 0 fallas**; también con el usuario restringido de la base.
- [x] Navegador: todas las vistas sin errores de JavaScript; prueba de escapado (XSS).

**Producción**
- [x] `'produccion' => true` en `config.local.php` oculta los errores de PHP (van a `logs/php_errores.log`) y con HTTPS envía `Strict-Transport-Security`.
- [x] `scripts/usuario_app.sql`: usuario de base de datos solo con SELECT, INSERT, UPDATE, DELETE y EXECUTE (probado: el sistema completo funciona con él).
- [x] `scripts/respaldo_diario.bat` (Windows/XAMPP, probado) y `scripts/respaldo_diario.sh` (Linux/cron): respaldo diario con rutinas y triggers, 30 días.
- [ ] HTTPS y programar el respaldo en el servidor del colegio: los configura quien administra el hosting (pasos en `docs/despliegue.md`).

**Documentación**
- [x] `docs/manual_contadora.md`: flujo diario (cobrar → registrar gastos → cerrar caja → depositar) y mensual (planilla → reportes).
- [x] `docs/despliegue.md`: instalación, migraciones, lista para producción, respaldos, pruebas y permisos.
- [ ] `manual_usuario.pdf`: sigue siendo el manual anterior; el contenido nuevo está en `docs/`. Generar un PDF actualizado queda pendiente.

---

## Fase 10 – Videos en las tareas y revisión de estudiantes/docentes ✅ (2026-10-25)

**Videos en las tareas (docente y administrador)** — migración `012_videos_tareas.sql`
- [x] Se pueden **subir videos** (MP4, WebM, MOV) junto con los documentos; hasta 100 MB por video (`config.php` → `video_max_mb`, limitado por lo que acepte el servidor: `.htaccess` / `.user.ini`). Se revisa que el contenido sea realmente video.
- [x] **Enlaces de video** (uno por línea, hasta 10): YouTube, Google Drive y Vimeo se reproducen **dentro de la plataforma**; otros sitios quedan como enlace. Recomendado para videos largos (no ocupan espacio ni ancho de banda del hosting).
- [x] Página "Ver material" para docente, administrador y estudiante: reproductor para videos subidos (se transmiten por partes, se puede adelantar), videos incrustados y lista de documentos.
- [x] Al **editar** una tarea, los archivos nuevos se suman a los actuales y se marcan los que se quieren quitar (antes subir un archivo borraba todo el material).
- [x] Barra de avance al subir; todos los archivos elegidos se validan (antes solo el primero, y rechazaba .doc/.xls/.ppt que el servidor sí acepta).
- [x] Corregido: si el envío superaba el límite del servidor, la tarea se guardaba sin archivos y sin aviso.

**Revisión de los módulos de estudiantes y docentes**
- [x] **Cada docente solo maneja tareas de sus cursos**: antes, con una petición armada, podía publicar, modificar, eliminar o finalizar tareas de otro docente, ver sus envíos y calificarlos.
- [x] Envíos de estudiantes: la carpeta se nombraba con la hora en segundos y **dos envíos en el mismo segundo compartían carpeta** (cada uno veía el archivo del otro). Ahora cada carpeta es única.
- [x] Las listas de docentes, personal administrativo y usuarios enviaban al navegador la **clave cifrada** (`usu_contra`) de cada usuario; ya no se envía.
- [x] Eliminar un estudiante con matrícula o un docente con cursos/contratos/pagos mostraba un error de PHP (y en producción nada): ahora avisa por qué no se puede y que el historial se conserva.
- [x] Pruebas: `tests/pruebas_fase10_videos.sh` (33). Total con las fases anteriores: **222 comprobaciones, 0 fallas**; 7 roles sin errores en el navegador.

**Recomendado a continuación** → hecho en el cierre de la Fase 11 (ver abajo).

---

## Fase 11 – Asistencia y tareas en planilla, filtros y reportes a medida ✅ (2026-10-25)

Referencia: hoja de asistencia mensual en papel (estudiantes × días, semanas de colores, códigos P/T/F/J y totales) y los sistemas escolares que permiten marcar a todos como presentes y corregir solo las excepciones.

**Registro mensual de asistencia** — migración `013_asistencia_mensual.sql`
- [x] Planilla por aula y mes (lunes a viernes por semana): clic o teclado para marcar P/T/F/J, "Hoy: todos presentes", marcar o limpiar un día completo, totales y % por estudiante, asistentes por día. Se guarda todo junto.
- [x] PDF con el formato de la hoja de asistencia (también **en blanco** para llenar a mano) y Excel.
- [x] Administrador y auxiliar: todas las aulas. **Docente**: solo las aulas donde enseña (nuevo en su menú).
- [x] Corregido: `SP_REGISTRAR_ASISTENCIA` comparaba con la fecha de creación (no detectaba duplicados al registrar días anteriores) y guardaba el mes actual. Índice único estudiante + día.

**Registro de tareas** — migración `014_tareas_asignacion.sql`
- [x] Cuadrícula estudiantes × tareas por curso y periodo: nota (verde/roja), entregada por calificar, pendiente o no entregó; entregadas y promedio. Calificar desde la casilla; Excel e imprimir.
- [x] **Corregido: las tareas nuevas no llegaban a ningún estudiante** si el año escolar no se llamaba igual que el año del calendario (el actual se llama "2025" con fechas de 2026). Ahora se usa el año escolar del curso del docente; las tareas pendientes reciben a los estudiantes que faltaban (también los matriculados después). La calificación inicial queda vacía en vez de 0.

**Filtros y columnas en todas las tablas** (`js/tablas_mejoras.js`, sin tocar cada pantalla)
- [x] Botón **Filtros**: un filtro por columna (lista si hay pocos valores, búsqueda si no) y **Limpiar**.
- [x] Botón **Columnas**: elegir columnas visibles (se recuerda por navegador). Excel/PDF/Imprimir exportan solo lo visible y filtrado, sin la columna de acciones.

**Reporte de estudiantes a medida**
- [x] 28 campos en 5 grupos (estudiante, padres, matrícula, seguimiento, pensiones) en el orden elegido; filtros por año, nivel, aula, sexo, nuevo/antiguo, búsqueda y "solo con deuda vencida" (coincide con Morosidad). Listas rápidas y "mi reporte" guardado. Campos solo del catálogo del servidor.
- [x] Administrador, auxiliar y contadora.

**Respaldos desde el sistema** (*Configuración → Respaldos*, solo administrador)
- [x] Generar respaldo (`.sql.gz` con tablas, datos, vistas, funciones, procedimientos y triggers, sin depender de `mysqldump`), lista con tipo y tamaño, descargar y eliminar. Lectura consistente, un respaldo a la vez, triggers al final para que restaurar no duplique la auditoría.
- [x] Probado restaurando en una base aparte: 60 tablas idénticas (checksum), mismas vistas, rutinas y triggers.
- [x] Con el usuario restringido avisa que el respaldo es incompleto; `scripts/usuario_respaldo.sql` + `'db_respaldo'` para respaldos completos.

**Cierre (2026-10-25)** — migraciones `015_anio_escolar_actual.sql` y `016_corregir_anio_escolar.sql`
- [x] **Año escolar actual = el año escolar ACTIVO** (`FN_ANIO_ESCOLAR_ACTUAL`): 15 procedimientos usaban el año del calendario (horarios, alumnos por grado de la asistencia diaria, tareas y exámenes pendientes del estudiante, sus pagos, periodos…) y salían vacíos porque el año en curso se llamaba "2025". Generada por `database/generadores/generar_015.php`.
- [x] Datos corregidos (autorizado): el año escolar en curso se llama 2026 y su IV bimestre va del 19/10 al 11/12/2026. Revisar su nombre oficial en *Año escolar*.
- [x] Edad del estudiante calculada siempre desde la fecha de nacimiento (listados de estudiantes y atenciones).
- [x] **Notas y exámenes**: cada docente solo en sus cursos y con los estudiantes de esas aulas (registrar notas; crear, modificar, eliminar, cerrar exámenes y ver sus alumnos).
- [x] **Activar / Desactivar** estudiantes (acceso al sistema) y docentes (docente + usuario), conservando el historial.
- [x] **Reenvío de tareas**: se puede enviar y reenviar hasta que el docente califique o cierre la tarea; lo enviado después de la fecha de entrega se marca **Fuera de plazo** (lista de envíos y registro de tareas).

**Documentación y pruebas**
- [x] `docs/guia_rapida.md` para docentes, auxiliares y dirección (incluye respaldos, activar/desactivar y reenvío de tareas).
- [x] `tests/pruebas_fase11_cierre.sh` (28).
- [x] `tests/pruebas_fase11.sh` (36) y `tests/pruebas_fase11_respaldos.sh` (17). Total **303 comprobaciones, 0 fallas**; 7 roles sin errores en el navegador (incluye detectar tablas que se quedan en "Procesando").

---

## Anexo A – Monedas y países

| País | Código | Moneda | Símbolo | Decimales | Formato ejemplo | Zona horaria | Documento |
|------|--------|--------|---------|-----------|-----------------|--------------|-----------|
| Perú | PE | PEN – Sol | S/ | 2 | S/ 1,500.00 | America/Lima | DNI (8) |
| Ecuador | EC | USD – Dólar | $ | 2 | $ 1,500.00 | America/Guayaquil | Cédula (10) |
| Bolivia | BO | BOB – Boliviano | Bs | 2 | Bs 1.500,00 | America/La_Paz | CI (5–10) |
| Argentina | AR | ARS – Peso | $ | 2 | $ 1.500,00 | America/Argentina/Buenos_Aires | DNI (7–8) |
| Chile | CL | CLP – Peso | $ | 0 | $ 1.500 | America/Santiago | RUT |
| Colombia | CO | COP – Peso | $ | 0–2 | $ 1.500 | America/Bogota | CC (6–10) |
| México | MX | MXN – Peso | $ | 2 | $1,500.00 | America/Mexico_City | CURP (18) |
| Venezuela | VE | VES – Bolívar | Bs. | 2 | Bs. 1.500,00 | America/Caracas | CI |
| Paraguay | PY | PYG – Guaraní | ₲ | 0 | ₲ 1.500 | America/Asuncion | CI |
| Uruguay | UY | UYU – Peso | $U | 2 | $U 1.500,00 | America/Montevideo | CI (8) |
| Brasil | BR | BRL – Real | R$ | 2 | R$ 1.500,00 | America/Sao_Paulo | CPF (11) |
| Costa Rica | CR | CRC – Colón | ₡ | 2 | ₡1 500,00 | America/Costa_Rica | Cédula (9) |
| Panamá | PA | PAB/USD | B/. | 2 | B/. 1,500.00 | America/Panama | Cédula |
| Guatemala | GT | GTQ – Quetzal | Q | 2 | Q1,500.00 | America/Guatemala | DPI (13) |
| Honduras | HN | HNL – Lempira | L | 2 | L 1,500.00 | America/Tegucigalpa | DNI (13) |
| El Salvador | SV | USD – Dólar | $ | 2 | $1,500.00 | America/El_Salvador | DUI (9) |
| Nicaragua | NI | NIO – Córdoba | C$ | 2 | C$ 1,500.00 | America/Managua | Cédula (14) |
| Rep. Dominicana | DO | DOP – Peso | RD$ | 2 | RD$1,500.00 | America/Santo_Domingo | Cédula (11) |
| Cuba | CU | CUP – Peso | $ | 2 | $1 500,00 | America/Havana | CI (11) |
| Puerto Rico | PR | USD – Dólar | $ | 2 | $1,500.00 | America/Puerto_Rico | — |

> El formato exacto (separadores) se resuelve con `Intl.NumberFormat('es-XX', {currency})` en JS y con la configuración de la tabla `monedas` en PHP/MPDF.

---

## Anexo B – Script SQL de referencia

Esquema propuesto (se ajusta al implementar cada fase; nombres en español para seguir la convención actual).

```sql
-- ===== Fase 2: ampliar montos =====
ALTER TABLE pensiones       MODIFY precio DECIMAL(14,2), MODIFY mora DECIMAL(14,2);
ALTER TABLE pago_pensiones  MODIFY sub_total DECIMAL(14,2);
ALTER TABLE ingresos        MODIFY monto DECIMAL(14,2);
ALTER TABLE egresos         MODIFY monto DECIMAL(14,2);
ALTER TABLE matricula       MODIFY pago_admi DECIMAL(14,2),
                            MODIFY pago_alu_nuevo DECIMAL(14,2),
                            MODIFY pago_matricula DECIMAL(14,2);

-- ===== Fase 3: regional =====
CREATE TABLE monedas (
  id_moneda      INT AUTO_INCREMENT PRIMARY KEY,
  codigo         CHAR(3) NOT NULL UNIQUE,          -- PEN, USD, BOB, ARS...
  nombre         VARCHAR(60) NOT NULL,
  simbolo        VARCHAR(5)  NOT NULL,
  decimales      TINYINT NOT NULL DEFAULT 2,
  sep_miles      CHAR(1) NOT NULL DEFAULT ',',
  sep_decimal    CHAR(1) NOT NULL DEFAULT '.',
  simbolo_antes  TINYINT(1) NOT NULL DEFAULT 1,
  estado         ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE paises (
  id_pais        INT AUTO_INCREMENT PRIMARY KEY,
  codigo_iso     CHAR(2) NOT NULL UNIQUE,
  nombre         VARCHAR(80) NOT NULL,
  locale         VARCHAR(10) NOT NULL,             -- es-PE, es-EC, es-BO...
  moneda_id      INT NOT NULL,
  zona_horaria   VARCHAR(60) NOT NULL,
  prefijo_tel    VARCHAR(6),
  FOREIGN KEY (moneda_id) REFERENCES monedas(id_moneda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tipos_documento_identidad (
  id_tipo_doc    INT AUTO_INCREMENT PRIMARY KEY,
  pais_id        INT NOT NULL,
  nombre         VARCHAR(40) NOT NULL,             -- DNI, CÉDULA, CI, RUT...
  long_min       TINYINT NOT NULL,
  long_max       TINYINT NOT NULL,
  regex          VARCHAR(120),
  FOREIGN KEY (pais_id) REFERENCES paises(id_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE empresa
  ADD pais_id INT NULL, ADD moneda_id INT NULL,
  ADD zona_horaria VARCHAR(60) NULL, ADD identificacion_tributaria VARCHAR(20) NULL;

-- ===== Fase 4: métodos de pago y cuentas =====
CREATE TABLE metodos_pago (
  id_metodo            INT AUTO_INCREMENT PRIMARY KEY,
  nombre               VARCHAR(60) NOT NULL,       -- EFECTIVO, TARJETA, TRANSFERENCIA...
  requiere_referencia  TINYINT(1) DEFAULT 0,
  requiere_cuenta      TINYINT(1) DEFAULT 0,
  estado               ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cuentas_financieras (
  id_cuenta      INT AUTO_INCREMENT PRIMARY KEY,
  tipo           ENUM('CAJA','BANCO','BILLETERA') NOT NULL,
  nombre         VARCHAR(100) NOT NULL,            -- "Caja principal", "BCP Soles"...
  banco          VARCHAR(100),
  numero_cuenta  VARCHAR(40),
  codigo_interbancario VARCHAR(40),                -- CCI / CBU / IBAN
  saldo_inicial  DECIMAL(14,2) DEFAULT 0,
  estado         ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos (
  id_pago        INT AUTO_INCREMENT PRIMARY KEY,
  serie          VARCHAR(5)  NOT NULL,
  numero         INT NOT NULL,
  id_matricula   INT NOT NULL,
  fecha          DATETIME NOT NULL,
  total          DECIMAL(14,2) NOT NULL,
  usuario_id     INT NOT NULL,
  estado         ENUM('VALIDO','ANULADO') DEFAULT 'VALIDO',
  motivo_anulacion VARCHAR(255),
  UNIQUE KEY (serie, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos_metodos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  id_pago        INT NOT NULL,
  metodo_pago_id INT NOT NULL,
  cuenta_id      INT NOT NULL,
  monto          DECIMAL(14,2) NOT NULL,
  comision       DECIMAL(14,2) DEFAULT 0,
  referencia     VARCHAR(60),
  voucher        VARCHAR(255),
  FOREIGN KEY (id_pago) REFERENCES pagos(id_pago),
  FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo),
  FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pago_pensiones ADD id_pago INT NULL, ADD estado ENUM('VALIDO','ANULADO') DEFAULT 'VALIDO';

-- ===== Fase 5: contabilidad =====
INSERT INTO roles (tipo_rol, descripcion, estado, created_at)
VALUES ('CONTADOR','GESTIONA INGRESOS, EGRESOS, PLANILLA Y REPORTES FINANCIEROS','ACTIVO',NOW());

CREATE TABLE movimientos_cuenta (
  id_movimiento  INT AUTO_INCREMENT PRIMARY KEY,
  cuenta_id      INT NOT NULL,
  fecha          DATETIME NOT NULL,
  tipo           ENUM('INGRESO','EGRESO','TRANSF_ENTRADA','TRANSF_SALIDA','AJUSTE') NOT NULL,
  monto          DECIMAL(14,2) NOT NULL,
  origen         ENUM('PAGO_PENSION','INGRESO','EGRESO','PAGO_DOCENTE','DEPOSITO','MANUAL') NOT NULL,
  origen_id      INT,
  referencia     VARCHAR(60),
  usuario_id     INT NOT NULL,
  conciliado     TINYINT(1) DEFAULT 0,
  estado         ENUM('VALIDO','ANULADO') DEFAULT 'VALIDO',
  FOREIGN KEY (cuenta_id) REFERENCES cuentas_financieras(id_cuenta),
  INDEX (cuenta_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cierres_caja (
  id_cierre      INT AUTO_INCREMENT PRIMARY KEY,
  cuenta_id      INT NOT NULL,
  fecha          DATE NOT NULL,
  saldo_inicial  DECIMAL(14,2), ingresos DECIMAL(14,2), egresos DECIMAL(14,2),
  saldo_esperado DECIMAL(14,2), saldo_contado DECIMAL(14,2), diferencia DECIMAL(14,2),
  observacion    VARCHAR(255),
  usuario_id     INT NOT NULL,
  UNIQUE KEY (cuenta_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE egresos
  ADD metodo_pago_id INT NULL, ADD cuenta_id INT NULL,
  ADD beneficiario VARCHAR(150) NULL, ADD nro_comprobante VARCHAR(30) NULL,
  ADD adjunto VARCHAR(255) NULL;

-- ===== Fase 6: planilla docente =====
CREATE TABLE tipos_contrato (
  id_tipo_contrato INT AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(40) NOT NULL,             -- TIEMPO COMPLETO, TIEMPO PARCIAL, POR HORAS
  calculo        ENUM('MENSUAL','POR_HORA') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contratos_docente (
  id_contrato    INT AUTO_INCREMENT PRIMARY KEY,
  docente_id     INT NOT NULL,
  tipo_contrato_id INT NOT NULL,
  fecha_inicio   DATE NOT NULL,
  fecha_fin      DATE NULL,
  sueldo_base    DECIMAL(14,2) DEFAULT 0,
  tarifa_hora    DECIMAL(14,2) DEFAULT 0,
  horas_semanales DECIMAL(5,2) DEFAULT 0,
  metodo_pago_id INT NOT NULL,                     -- efectivo, transferencia, depósito...
  banco_docente  VARCHAR(100),
  cuenta_docente VARCHAR(40),
  estado         ENUM('ACTIVO','FINALIZADO') DEFAULT 'ACTIVO',
  FOREIGN KEY (tipo_contrato_id) REFERENCES tipos_contrato(id_tipo_contrato),
  FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id_metodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE conceptos_planilla (
  id_concepto    INT AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(80) NOT NULL,
  tipo           ENUM('INGRESO','DESCUENTO') NOT NULL,
  forma          ENUM('MONTO','PORCENTAJE') NOT NULL,
  valor_defecto  DECIMAL(14,2) DEFAULT 0,
  estado         ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE planillas (
  id_planilla    INT AUTO_INCREMENT PRIMARY KEY,
  anio           SMALLINT NOT NULL,
  mes            TINYINT NOT NULL,
  estado         ENUM('BORRADOR','APROBADA','PAGADA','ANULADA') DEFAULT 'BORRADOR',
  total_bruto    DECIMAL(14,2) DEFAULT 0,
  total_descuentos DECIMAL(14,2) DEFAULT 0,
  total_neto     DECIMAL(14,2) DEFAULT 0,
  usuario_id     INT NOT NULL,
  aprobado_por   INT NULL,
  UNIQUE KEY (anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE planilla_detalle (
  id_detalle     INT AUTO_INCREMENT PRIMARY KEY,
  id_planilla    INT NOT NULL,
  contrato_id    INT NOT NULL,
  horas_dictadas DECIMAL(6,2) DEFAULT 0,
  base           DECIMAL(14,2) NOT NULL,
  ingresos       DECIMAL(14,2) DEFAULT 0,
  descuentos     DECIMAL(14,2) DEFAULT 0,
  adelantos      DECIMAL(14,2) DEFAULT 0,
  neto           DECIMAL(14,2) NOT NULL,
  pagado         DECIMAL(14,2) DEFAULT 0,
  FOREIGN KEY (id_planilla) REFERENCES planillas(id_planilla),
  FOREIGN KEY (contrato_id) REFERENCES contratos_docente(id_contrato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE planilla_detalle_conceptos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  id_detalle     INT NOT NULL,
  concepto_id    INT NOT NULL,
  monto          DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (id_detalle) REFERENCES planilla_detalle(id_detalle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos_docentes (
  id_pago_docente INT AUTO_INCREMENT PRIMARY KEY,
  id_detalle     INT NULL,                         -- NULL = adelanto fuera de planilla
  docente_id     INT NOT NULL,
  fecha          DATETIME NOT NULL,
  monto          DECIMAL(14,2) NOT NULL,
  metodo_pago_id INT NOT NULL,
  cuenta_id      INT NOT NULL,                     -- cuenta/caja del colegio de donde sale
  referencia     VARCHAR(60),
  voucher        VARCHAR(255),
  egreso_id      INT NULL,                         -- egreso generado automáticamente
  usuario_id     INT NOT NULL,
  estado         ENUM('VALIDO','ANULADO') DEFAULT 'VALIDO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Orden recomendado y dependencias

```
Fase 0 ─► Fase 1 ─► Fase 2 ─► Fase 3 ─┬─► Fase 4 ─► Fase 5 ─► Fase 6 ─► Fase 7
                                      └─► Fase 8 (en paralelo, módulo por módulo)
                                                                         ─► Fase 9
```

- Fase 1 va primero porque hoy cualquiera puede entrar como administrador.
- Fase 2 (ampliar `DECIMAL`) va antes de multimoneda: sin ella, los montos en pesos/guaraníes no caben.
- Fase 4 (cuentas y métodos) es la base de la Fase 5 (movimientos) y la Fase 6 (pago a docentes).

---

## Pendientes por confirmar

Ya resueltos: un solo país, una sola moneda configurable, solo rol CONTADOR (ver [Decisiones confirmadas](#decisiones-confirmadas)).

1. **Retenciones de ley en planilla** (aportes de pensión, seguro, impuesto a la renta) cambian por país; ¿se manejan como conceptos configurables (propuesto) o se necesita cálculo automático por país?
2. **¿Facturación electrónica?** (SUNAT, SRI, SIN, AFIP…) — fuera del alcance de este plan; sería una fase aparte.
3. ~~El mensaje original terminaba en "a su vez debe poder…"~~ → confirmado el 2026-10-24: no faltaba ningún requerimiento.
