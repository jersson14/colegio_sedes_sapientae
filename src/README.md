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

## Migrado

| Módulo | Servicios | Endpoints adaptados | Pruebas |
|---|---|---|---|
| usuario (cuentas) | `AutenticarUsuario`, `GestionarCuentas` | `iniciar_sesion`, `modificar_usuario`, `modificar_usuario_contra`, `modificar_usuario_estatus` | `tests/Unit/Usuario`, `tests/Integration/UsuarioRepositorioTest`, flujos §1 y §8 |

Las lecturas del módulo (listados, totales del panel, combos) siguen en `model/model_usuario.php`,
cubiertas por la caracterización. No se migran los endpoints sin uso desde la interfaz
(`docs/MATRIZ_ROLES.md`, «restos de otro sistema»): su retirada queda pendiente de decisión.

**Compatibilidad que no se debe romper:** `Domain\Usuario\Contrasena` aplica `htmlspecialchars` antes
de hashear y de verificar, como hacía el código heredado; todas las contraseñas guardadas dependen de ello.
