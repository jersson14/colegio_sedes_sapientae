# src/ — código nuevo (namespace `App\`, PSR-4)

Destino del refactor por estrangulamiento (Fase 3 del plan). Todo código nuevo vive aquí,
con tipos estrictos, PHPStan y pruebas. Lo heredado (`controller/`, `model/`, `view/`,
`core/`) se migra módulo por módulo; no se copia código heredado sin su prueba de caracterización.

Estructura objetivo: `Core/`, `Http/Controllers/`, `Http/Middleware/`, `Domain/`,
`Services/`, `Repositories/`, `Support/` (ver docs/PLAN_DE_TRABAJO.md §1.2).

## Cómo lo usa el código heredado

Mientras no haya front controller, los endpoints siguen en `controller/<modulo>/` (las URLs son rutas
físicas), pero quedan como adaptadores finos: leen `$_POST`, llaman a un servicio y responden con el
mismo contrato de siempre (`0/1/2`, JSON por índice), que es lo que espera el JS.

```php
require_once __DIR__ . '/../../core/guard.php';          // sigue siendo lo primero
exigir_rol('ADMINISTRADOR');
require __DIR__ . '/../../model/model_conexion.php';     // config + core/autoload.php
$cuentas = new GestionarCuentas(new PdoUsuarioRepositorio((new conexionBD())->conexionPDO()));
```

- `core/autoload.php` carga `App\` sin Composer (producción puede no tenerlo).
- `App\Core\Conexion` es la única fábrica del PDO; `conexionBD` delega en ella.
- Los repositorios llaman a los procedimientos existentes: la lógica SQL sigue en la BD. Las
  correcciones de un SP van en una migración (`database/esquema/RecreaProcedimientos.php`).

## Instituciones (Fase 4)

`Tenancy/`: `ModoTenant` (`unico`/`multiple`), `Tenant`, `EstadoTenant`, `ResolverTenant` (host → institución),
`TenantContext` (la de la petición; sin ella `Core\Conexion::crear()` lanza `TenantNoResuelto`) y el registro
`PdoRepositorioTenants` sobre la BD maestra (`Core\Conexion::maestro()`). `AltaInstitucion` + `SolicitudAlta`
dan de alta un colegio (`tools/alta_tenant.php`); `MigradorPhinx` migra una base concreta. `Marca` reúne
nombre, logo, color y página pública (`Domain\Empresa\PaginaPublica`, `ColorInstitucion`), que el
administrador edita con `Services\GestionarPersonalizacion` («Empresa → Personalizar»).

`Comercial/` (Fase 4B): `Plan`, `Condiciones` (estado + plan), `Restricciones` (lo que el guard frena: altas
en MOROSO, todo menos la exportación en SUSPENDIDO, límites del plan), `Consumo`, `ExportacionDatos` y
`PdoRepositorioComercial`; `core/comercial.php` los usa en cada petición.

`Superadmin/`: `CuentasSuperadmin`, `Auditoria` y `PanelInstituciones` (estados, altas, cifras), que usa
`superadmin/index.php` (fuera de `controller/`: tiene su propia autenticación, no el guard de los colegios). El código heredado lo usa a través
de `core/tenant.php` (`tenant_actual()`). `Services\TareasProgramadas` hace el trabajo de los eventos de la BD
para el cron.

## Migrado

| Módulo | Servicios | Endpoints adaptados | Pruebas |
|---|---|---|---|
| usuario (cuentas) | `AutenticarUsuario`, `GestionarCuentas` | `iniciar_sesion`, `modificar_usuario`, `modificar_usuario_contra`, `modificar_usuario_estatus` | `tests/Unit/Usuario`, `tests/Integration/UsuarioRepositorioTest`, flujos §1 y §8 |
| alumnos | `GestionarAlumnos` (`FabricaAlumnos`), `FichaAlumno` | `registrar_alumno`, `modificar_alumno`, `eliminar_alumnos`, `modificar_foto_estudiante` | `tests/Unit/Alumno`, `tests/Integration/AlumnoRepositorioTest`, flujos §9 |
| matrícula | `GestionarMatriculas`, `DatosMatricula`, `Monto`, `CuentaNueva` | `registro_matriculas`, `modificar_matrícula`, `eliminar_matricula` | `tests/Unit/Matricula`, `tests/Integration/MatriculaRepositorioTest`, flujos §4 y §10 |
| notas | `GestionarNotas` (`FabricaNotas`), `ValorNota`, `TextoLibre`, `Lote` | `registro_notas`, `registro_notas_padres`, `editar_notas`, `editar_notas_padre` | `tests/Unit/Nota`, `tests/Integration/NotaRepositorioTest`, flujos §5 y §11 |
| asistencia | `GestionarAsistencia`, `Asistencia`, `EstadoAsistencia` | `registro_asistencias`, `editar_asistencia`, `eliminar_asistencia` | `tests/Unit/Asistencia`, `tests/Integration/AsistenciaRepositorioTest`, flujos §7 y §12 |
| caja | `AnularMovimiento`, `GestionarCaja`, `Domain\Caja\Movimiento`, `MovimientoDiverso` | `ingresos/*` y `egresos/*` (registrar, modificar, anular), `indicadores/eliminar_indicador` | `tests/Unit/Caja`, `tests/Integration/MontosYAnulacionTest`, `PagosYCajaTest`, flujos §13 y §15 |
| pensiones y pagos | `GestionarPensiones`, `Domain\Pension\DatosPension`, `Pago` | `pensiones/*` (registro, modificar, eliminar), `pago_pension/*` (cobrar, modificar, anular) | `tests/Unit/Pension`, `tests/Integration/PagosYCajaTest`, flujos §3 y §15 |
| tareas y exámenes | `GestionarTareas` (`FabricaTareas`), `Domain\Tarea\Actividad`, `Support\DocumentosTarea` | `tareas/*` (publicar, modificar, eliminar, finalizar, entregar, calificar), `examenes/*` | `tests/Unit/Tarea`, `tests/Integration/TareaRepositorioTest`, flujos §6 y §16 |
| enfermería, psicología y comunicados | `GestionarBienestar` (`FabricaBienestar`), `Domain\Salud\Atencion`, `TipoAtencion`, `Domain\Comunicado\Comunicado` | `atencion_enfermeria/*`, `atencion_psicologica/*` (registro, modificar), `comunicados/*` (registro, modificar, eliminar) | `tests/Unit/Bienestar`, `tests/Integration/BienestarRepositorioTest`, flujos §17 |
| reportes PDF | `Reportes\Datos` (PDO preparado), `Reportes\Pdf`, `Reportes\Fecha` | boleta, cédula, horario, kardex, pagos, notas general y por bimestre (`view/MPDF/REPORTE/`) | `tests/Unit/Reportes`, `tools/caracterizar_reportes.php` (HTML de 14 casos), flujos §2 y §3 |
| asignaturas/horarios | `GestionarHorarios` (`FabricaHorarios`), `Domain\Horario\Clase`, `ResultadoClase` | `registro_asignaturas`, `modificar_asignaturas`, `eliminar_asignatura`, `registro_horario_aula`, `modificar_horarios`, `eliminar_horario` | `tests/Unit/Horario`, `tests/Integration/HorarioRepositorioTest`, flujos §14 |

Las lecturas del módulo (listados, totales del panel, combos) siguen en `model/model_usuario.php`,
cubiertas por la caracterización; igual los listados de alumnos y matrícula (`model/model_alumnos.php`,
`model/model_matriculas.php`). `Support\Texto` reúne la normalización heredada de los formularios;
`Support\TextoLibre` (texto escapado, sin mayúsculas) y `Support\Lote` (el JSON «registros») los comparten
notas y asistencia.

Las fotos pasan por `Support\AlmacenFotos` (`FotosSubidas` sobre `core/subidas.php`): se validan antes
de tocar la BD y se guardan, o se borra la anterior, solo si la BD aceptó el cambio.

No se migran los endpoints sin uso desde la interfaz (`docs/MATRIZ_ROLES.md`, «restos de otro
sistema»): su retirada queda pendiente de decisión.

**Compatibilidad que no se debe romper:** `Domain\Usuario\Contrasena` aplica `htmlspecialchars` antes
de hashear y de verificar, como hacía el código heredado; todas las contraseñas guardadas dependen de ello.
