---
name: nuevo-modulo
description: Crea un módulo CRUD completo (controladores, modelo, vista, JS y stored procedures) siguiendo las convenciones exactas de este sistema escolar en PHP MVC. Úsalo cuando pidan "crear el módulo X", "agregar un CRUD de X", "nuevo mantenimiento de X" o al añadir una entidad nueva al sistema.
---

# Crear un módulo nuevo

Genera un módulo CRUD consistente con los 33 módulos existentes. **No inventes una
estructura nueva**: este sistema tiene convenciones rígidas y romperlas deja el
código inconsistente con los 263 controladores actuales.

## Antes de empezar

1. Lee un módulo existente similar como plantilla de referencia. Los más limpios:
   - `controller/seccion/` + `model/model_seccion.php` + `js/console_seccion.js` (CRUD simple)
   - `controller/alumnos/` (CRUD con foto y entidad relacionada)
2. Confirma con el usuario: nombre de la entidad en singular y plural, campos y tipos,
   qué roles pueden acceder, y si necesita subida de archivos.

## Estructura a generar

```
controller/<modulo>/
  controlador_listar_<modulo>.php
  controlador_registrar_<modulo>.php
  controlador_modificar_<modulo>.php
  controlador_eliminar_<modulo>.php
  controlador_cargar_select_<modulo>.php   (si otras entidades lo referencian)
model/model_<entidad>.php                   clase Modelo_Entidad
view/<modulo>/                              fragmento con tabla + modal
js/console_<modulo>.js                      DataTable + AJAX + SweetAlert2
database/migrations/                        tabla + stored procedures
```

## Reglas obligatorias

### 1. Guard de sesión en TODO controlador nuevo

Es la regla más importante. 260 de los 263 controladores actuales no lo tienen, y eso
es el hallazgo H-02 de `docs/SEGURIDAD.md`. **Todo archivo nuevo debe empezar así:**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');            // ajusta según el módulo
require_once __DIR__ . '/../../model/model_<entidad>.php';
```

Si `core/guard.php` aún no existe (Fase 0 del plan sin completar), créalo según la
plantilla de `docs/SEGURIDAD.md` §H-02 y avisa al usuario de que lo has añadido.

### 2. Nomenclatura (sin excepciones)

| Elemento | Patrón |
|---|---|
| Carpeta de controlador | `controller/<modulo_plural_minuscula>/` |
| Archivo de controlador | `controlador_<verbo>_<entidad>.php` |
| Clase de modelo | `Modelo_<Entidad>` en `model/model_<entidad>.php` |
| Stored procedure | `SP_<VERBO>_<ENTIDAD>` en MAYÚSCULAS |
| Archivo JS | `js/console_<modulo>.js` |
| Función JS | `Listar_<Entidad>()`, `Registrar_<Entidad>()`, `Modificar_<Entidad>()` |

Dominio en **español**. Nada de mezclar inglés.

### 3. Patrón de controlador

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../../core/guard.php';
exigir_rol('ADMINISTRADOR');
require_once __DIR__ . '/../../model/model_seccion.php';

verificar_csrf();                                  // Fase 0
$MS = new Modelo_Seccion();
$nombre = trim((string)($_POST['nombre'] ?? ''));

if ($nombre === '') {
    http_response_code(422);
    exit(json_encode(['error' => 'El nombre es obligatorio']));
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($MS->Registrar_Seccion($nombre));
```

Diferencias respecto al código legado, **todas intencionales**:
- Valida de verdad (no solo `htmlspecialchars`, que es escapado de salida, no validación).
- Usa `?? ''` para no producir notices que corrompan el JSON.
- Emite `Content-Type: application/json`.
- Devuelve códigos HTTP correctos (422, 401, 403), no `echo 0`.

### 4. Patrón de modelo

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/model_conexion.php';

class Modelo_Seccion extends conexionBD
{
    public function Listar_Seccion(): array
    {
        $c = conexionBD::conexionPDO();
        $q = $c->prepare('CALL SP_LISTAR_SECCIONES()');
        $q->execute();
        return ['data' => $q->fetchAll(PDO::FETCH_ASSOC)];
    }
}
```

- **Siempre `PDO::FETCH_ASSOC`.** Sin él, PDO devuelve índices numéricos y asociativos,
  y el JS termina accediendo por número (`data[0][15]`), lo que rompe en silencio cuando
  cambia el `SELECT` del SP. Es el acoplamiento A2 de `docs/ARQUITECTURA.md`.
- **Siempre `prepare` + `bindParam`.** Nunca concatenes variables en el SQL.
- **No copies el `cerrar_conexion()` después del `return`** que aparece en los modelos
  antiguos: es código muerto que nunca se ejecuta.

### 5. Stored procedure

La lógica de negocio va en el SP, igual que los 254 existentes.
Guárdalo en `database/migrations/` para que sea versionable, no solo en el dump.

```sql
DELIMITER $$
CREATE PROCEDURE SP_LISTAR_SECCIONES()
BEGIN
    SELECT s.Id_seccion, s.nombre, s.estado
    FROM seccion s
    WHERE s.estado = 'ACTIVO'
    ORDER BY s.nombre;
END$$
DELIMITER ;
```

Si el módulo se crea después de la Fase 4 (multi-tenant), el SP debe recibir y filtrar
por `p_empresa_id`. Consulta `docs/MULTITENANT.md`.

### 6. Subidas de archivos

Si el módulo sube archivos, **no copies el patrón de `controlador_registrar_alumno.php`**:
es el hallazgo crítico H-03 (ejecución remota de código). Usa siempre:

```php
$ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
    http_response_code(422);
    exit(json_encode(['error' => 'Formato no permitido']));
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
if (!str_starts_with($mime, 'image/')) {
    http_response_code(422);
    exit(json_encode(['error' => 'El archivo no es una imagen']));
}
$nombre = bin2hex(random_bytes(16)) . '.' . $ext;   // nombre del SERVIDOR, no del cliente
move_uploaded_file($_FILES['foto']['tmp_name'], STORAGE_PATH . '/fotos/' . $nombre);
```

### 7. Registrar el módulo en el menú

Añade la entrada en `view/index.php` dentro del bloque del rol correspondiente, y el
`include` de la vista. Ese archivo ronda las 1500 líneas: ubica el bloque correcto antes
de editar y no lo dupliques.

## Al terminar

Verifica y reporta:

- [ ] Todos los controladores nuevos tienen el guard
- [ ] Ninguna consulta concatena variables
- [ ] Todos los `fetchAll` usan `FETCH_ASSOC`
- [ ] El SP está en `database/migrations/`, no solo aplicado a mano
- [ ] El menú de `view/index.php` muestra el módulo solo a los roles previstos
- [ ] Probado manualmente: listar, crear, editar, eliminar
