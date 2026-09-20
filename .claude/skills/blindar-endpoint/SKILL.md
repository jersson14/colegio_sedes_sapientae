---
name: blindar-endpoint
description: Aplica el guard de autenticación, autorización, CSRF y validación a controladores PHP de este sistema que hoy están expuestos sin protección. Úsalo cuando pidan "asegurar los endpoints", "agregar autenticación", "blindar el controlador X", "aplicar la Fase 0 de seguridad" o al auditar un módulo antes de desplegarlo.
---

# Blindar endpoints

260 de los 263 controladores de este sistema no verifican sesión: cualquiera puede
invocarlos por HTTP sin autenticarse (hallazgo H-02 de `docs/SEGURIDAD.md`).
Esta skill aplica la protección de forma sistemática y verificable.

## Paso 1 — Asegurar que existe el guard

Si `core/guard.php` no existe, créalo:

```php
<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function responder_error(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['error' => $mensaje]));
}

// 1. Autenticación
if (!isset($_SESSION['S_ID'])) {
    responder_error(401, 'No autenticado');
}

// 2. Expiración por inactividad (30 min)
if (isset($_SESSION['ultima_actividad'])
    && (time() - $_SESSION['ultima_actividad']) > 1800) {
    session_unset();
    session_destroy();
    responder_error(401, 'Sesión expirada');
}
$_SESSION['ultima_actividad'] = time();

// 3. Autorización por rol
function exigir_rol(string ...$roles): void
{
    if (!in_array($_SESSION['S_ROL'] ?? '', $roles, true)) {
        responder_error(403, 'Sin permiso para esta acción');
    }
}

// 4. CSRF para todo método que modifique estado
function verificar_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return;
    }
    $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)$recibido)) {
        responder_error(419, 'Token CSRF inválido');
    }
}
```

El token se genera al crear la sesión (`$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`),
se inyecta en `view/index.php` como `<meta name="csrf-token">` y el JS lo envía con:

```js
$.ajaxSetup({ headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') } });
```

## Paso 2 — Clasificar cada endpoint

Antes de aplicar nada, decide el nivel de acceso de cada controlador. Tres categorías:

| Categoría | Ejemplos | Protección |
|---|---|---|
| **Público** | `controlador_iniciar_sesion.php`, solicitudes de la landing, seguimiento de trámite | Sin guard, pero **con rate limiting y validación estricta** |
| **Autenticado** | listados que cualquier usuario logueado consulta (selects de catálogos) | `guard.php` sin `exigir_rol` |
| **Por rol** | todo lo demás | `guard.php` + `exigir_rol(...)` |

**Mapeo de roles por módulo** (según el menú de `view/index.php`):

```
ADMINISTRADOR  → todos los módulos
DOCENTE        → notas, tareas, exámenes, asistencia (solo sus aulas), horarios
ESTUDIANTE     → sus notas, sus tareas, su horario, comunicados
AUXILIAR       → asistencia, comunicados
ENFERMERA      → atencion_enfermeria
PSICOLOGA      → atencion_psicologica
```

> ⚠️ **Rol no es lo mismo que propiedad del dato.** Que un usuario sea DOCENTE no
> significa que pueda ver las notas de *cualquier* aula. Tras aplicar el guard hay
> que añadir la verificación de pertenencia: que el `idaula` solicitado esté entre
> los asignados a ese docente. Lo mismo para ESTUDIANTE con su propio `idalumno`.
> Sin esto sigue habiendo IDOR (referencia directa insegura a objetos).

## Paso 3 — Aplicar

Para cada controlador, insertar al inicio:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
verificar_csrf();                       // solo si modifica datos
require_once __DIR__ . '/../../model/model_x.php';
```

Trabaja **módulo por módulo**, no los 263 de golpe. Tras cada módulo, verifica en el
navegador que la UI sigue funcionando: si el JS empieza a recibir 401 o 403 donde antes
recibía datos, es que el rol asignado es demasiado restrictivo.

## Paso 4 — Verificar

Comprobación de cobertura (ningún controlador sin guard):

```bash
# Debe devolver 0 (salvo los públicos declarados)
for f in controller/*/*.php; do
  grep -Lq "guard.php" "$f" && echo "SIN GUARD: $f"
done
```

Comprobación funcional:

```bash
# Debe devolver 401
curl -s -o /dev/null -w "%{http_code}\n" \
  -X POST http://localhost/colegio_sedes_sapientae/controller/alumnos/controlador_listar_alumnos.php
```

Añade un test automatizado que recorra `controller/` y falle si aparece un archivo
nuevo sin guard. Así la protección no se degrada con el tiempo.

## Errores a evitar

- ❌ Poner el guard **después** del `require` del modelo: el modelo ya abrió la conexión.
- ❌ Usar `session_start()` suelto en vez del guard: autentica pero no autoriza.
- ❌ Confiar en que el menú oculta la opción: ocultar un botón no protege una URL.
- ❌ Aplicar `exigir_rol` y dar por cerrada la autorización: falta la verificación de
  pertenencia del dato (ver Paso 2).
- ❌ Poner guard en `controlador_iniciar_sesion.php`: nadie podría entrar nunca.

## Referencia

Contexto completo, severidades y el resto de hallazgos en `docs/SEGURIDAD.md`.
