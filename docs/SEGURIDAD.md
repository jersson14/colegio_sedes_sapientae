# Auditoría de seguridad y plan de hardening

> Revisión estática del código en `main` (commit `5f180bb`), 2026-09-20.
> Clasificación: 🔴 Crítica · 🟠 Alta · 🟡 Media · 🔵 Baja.
> **Este sistema no debe exponerse a internet en su estado actual.**

---

## 1. Resumen ejecutivo

| Severidad | Hallazgos |
|---|---|
| 🔴 Crítica | 4 |
| 🟠 Alta | 5 |
| 🟡 Media | 6 |
| 🔵 Baja | 4 |

Los cuatro hallazgos críticos permiten, **sin credenciales válidas**, leer y modificar
todos los datos del sistema y ejecutar código en el servidor. Cualquier despliegue
público debe ir precedido de la Fase 0 del [plan de trabajo](PLAN_DE_TRABAJO.md).

Datos en juego: nombres, DNI, fechas de nacimiento, direcciones y teléfonos de **menores
de edad**, registros de atención psicológica y de enfermería, y pagos. En Perú esto cae
bajo la **Ley 29733 de Protección de Datos Personales** (datos sensibles: salud, menores),
lo que convierte estos hallazgos en un riesgo legal, no solo técnico.

---

## 2. Hallazgos críticos

### 🔴 H-01 — Escalada de privilegios: la sesión la construye el cliente

**Dónde:** `controller/usuario/controlador_crear_sesion.php`, `js/console_usuario.js:29`

Tras validar la contraseña, el servidor **no crea la sesión**. Devuelve los datos al
navegador, y el navegador los reenvía por POST a un segundo endpoint que los escribe
en `$_SESSION` sin verificar nada:

```php
$rol = htmlspecialchars($_POST['rol'], ENT_QUOTES, 'UTF-8');
$_SESSION['S_ROL'] = $rol;   // confía en el navegador
```

**Explotación:** un POST directo a `controlador_crear_sesion.php` con
`idusuario=1&rol=ADMINISTRADOR` otorga una sesión de administrador **sin conocer
ninguna contraseña**. El endpoint tampoco exige haber pasado por el login.

**Corrección:** eliminar `controlador_crear_sesion.php`. El login debe abrir la sesión
en el servidor, en la misma petición, con los datos que vinieron de la base:

```php
session_start();
session_regenerate_id(true);                 // previene fijación de sesión
$_SESSION['S_ID']  = $usuario['usu_id'];
$_SESSION['S_ROL'] = $usuario['tipo_rol'];   // desde la BD, nunca desde $_POST
```

---

### 🔴 H-02 — 260 de 263 endpoints sin autenticación

**Dónde:** todo `controller/`. Solo 3 archivos llaman `session_start()`.

Ningún controlador de negocio comprueba si hay sesión ni qué rol tiene. Una petición
anónima a `controller/alumnos/controlador_listar_alumnos.php` devuelve el padrón
completo de estudiantes con DNI y dirección. `controller/*/controlador_eliminar_*.php`
borra registros sin autenticación.

**Explotación:** trivial. `curl -X POST http://host/controller/alumnos/controlador_listar_alumnos.php`

**Corrección:** un guard incluido en la primera línea de cada controlador:

```php
// core/guard.php
<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['S_ID'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'No autenticado']));
}
function exigir_rol(string ...$roles): void {
    if (!in_array($_SESSION['S_ROL'], $roles, true)) {
        http_response_code(403);
        exit(json_encode(['error' => 'Sin permiso']));
    }
}
```

Y en cada endpoint: `require_once __DIR__ . '/../../core/guard.php';`
más `exigir_rol('ADMINISTRADOR');` donde corresponda.

> El paso a un modelo de **permisos granulares** (no strings de rol) está en la Fase 3
> del plan; el guard es el parche inmediato.

---

### 🔴 H-03 — Subida de archivos sin validación → ejecución remota de código

**Dónde:** 16 controladores. Ejemplo `controller/alumnos/controlador_registrar_alumno.php:29`

```php
$nombrefoto = htmlspecialchars($_POST['nombrefoto'], ENT_QUOTES, 'UTF-8');
move_uploaded_file($_FILES['foto']['tmp_name'], "fotos/" . $nombrefoto);
```

El nombre y la extensión los decide el cliente. No se valida extensión, tipo MIME,
tamaño ni contenido. El destino (`controller/alumnos/fotos/`) está **dentro del docroot
y es servido por Apache**.

**Explotación:** subir `shell.php` como "foto" y abrir
`/controller/alumnos/fotos/shell.php` → ejecución de PHP arbitrario en el servidor.
`$nombrefoto` tampoco se limpia de `../`, así que además permite escritura fuera de la carpeta.

**Corrección (las cuatro capas, todas necesarias):**

1. Nombre generado por el servidor: `bin2hex(random_bytes(16)) . '.' . $ext`.
2. Lista blanca de extensiones (`jpg`, `jpeg`, `png`, `webp`, `pdf`) **y** verificación
   real con `finfo_file()` / `getimagesize()`.
3. Límite de tamaño explícito, independiente de `upload_max_filesize`.
4. Almacenar **fuera del docroot** y servir mediante un script que valide la sesión,
   o como mínimo `php_flag engine off` en un `.htaccess` dentro de la carpeta de subidas.

---

### 🔴 H-04 — Credenciales de base de datos en el código, con `root` sin contraseña

**Dónde:** `model/model_conexion.php`, `view/MPDF/conexion.php`

```php
$usuario = "root";
$contrasena = "";
```

Aunque los archivos están en `.gitignore`, viven dentro del docroot. Cualquier fallo de
configuración de Apache que deje de interpretar PHP los expone en texto plano. Y usar
`root` significa que una inyección SQL (o el RCE de H-03) tiene privilegios totales
sobre todo el servidor MySQL, no solo sobre la base `colegio`.

**Corrección:**
- Usuario dedicado `colegio_app` con `GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE` únicamente sobre `colegio`.
- Contraseña fuerte, en `.env` **fuera del docroot**, leída con `vlucas/phpdotenv`.
- Unificar las dos conexiones (PDO y MySQLi) en una sola configuración.

---

## 3. Hallazgos de severidad alta

### 🟠 H-05 — Sin protección CSRF
Ninguna petición lleva token. Un usuario autenticado que visite una página maliciosa
puede ejecutar cualquier acción del sistema (borrar alumnos, cambiar notas, registrar pagos).
**Corrección:** token por sesión, inyectado en el layout y enviado por `$.ajaxSetup` en
la cabecera `X-CSRF-Token`; validación en el guard para todo método no-GET.

### 🟠 H-06 — Contraseña del usuario guardada en `localStorage` en claro
`index.php:110` — el "Recuérdame" hace `localStorage.pass = <contraseña>`. Persiste
indefinidamente en el navegador y es legible por cualquier XSS.
**Corrección:** eliminar. Si se quiere "recordar", guardar solo el nombre de usuario,
o usar un token de sesión persistente en cookie `HttpOnly` + `Secure` + `SameSite=Strict`.

### 🟠 H-07 — Sin límite de intentos de login
`controlador_iniciar_sesion.php` no cuenta intentos fallidos ni aplica retardo. Fuerza
bruta y *credential stuffing* sin obstáculo.
**Corrección:** contador por usuario e IP con bloqueo temporal exponencial, y registro
de intentos en tabla de auditoría.

### 🟠 H-08 — Cookies de sesión sin banderas de seguridad
No se configura `session.cookie_httponly`, `cookie_secure`, `cookie_samesite`, ni
`session_regenerate_id()` tras el login (fijación de sesión). Sin expiración por inactividad.

### 🟠 H-09 — Archivos de diagnóstico y prueba expuestos en la raíz
`phpinfo.php` (revela versión de PHP, rutas absolutas, extensiones, variables de entorno),
`prueba.php`, `test_model.php`, `test_solicitudes.html`, `default.php`.
**Corrección:** eliminarlos del repositorio.

---

## 4. Hallazgos de severidad media

| ID | Hallazgo | Detalle |
|---|---|---|
| 🟡 H-10 | Errores de BD mostrados al cliente | `model_conexion.php` hace `echo 'Falló la conexión: ' . $e->getMessage()` → revela host, puerto, nombre de base y estructura |
| 🟡 H-11 | Sin cabeceras de seguridad HTTP | Faltan `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security` |
| 🟡 H-12 | Sin HTTPS forzado | El `.htaccess` no redirige a HTTPS; las credenciales viajan en claro si se despliega sin TLS |
| 🟡 H-13 | Recursos de CDN sin SRI | SweetAlert2 (`@11`, sin versión fija) y Select2 cargados sin `integrity` — un CDN comprometido ejecuta código en la sesión del administrador |
| 🟡 H-14 | Endpoints públicos sin rate limiting | El formulario de la landing (`registrar.php`, solicitudes) permite inundar la BD; sin CAPTCHA |
| 🟡 H-15 | Sin registro de auditoría | Existe `log_eventos_alumnos`, pero no hay traza de quién cambió notas, pagos o usuarios. Imprescindible para datos de menores |

---

## 5. Hallazgos de severidad baja

| ID | Hallazgo |
|---|---|
| 🔵 H-16 | `htmlspecialchars` usado como validación; los datos se guardan ya escapados (`&amp;`) y se corrompen en los PDF |
| 🔵 H-17 | Sin `Content-Type: application/json` en las respuestas; el navegador las interpreta por olfateo |
| 🔵 H-18 | Sin política de contraseñas (longitud, complejidad, caducidad) |
| 🔵 H-19 | `colegio.sql` con datos reales versionado en el repositorio pese a la regla `*.sql` del `.gitignore` (fue añadido antes de la regla; sigue en el historial de git) |

> **Sobre H-19:** quitarlo del árbol de trabajo no lo borra del historial. Si el repositorio
> es público y el dump contiene datos reales de menores, hay que reescribir el historial
> (`git filter-repo`) y rotar todo lo que allí aparezca.

---

## 6. Lo que ya está bien hecho

No todo es deuda. Conviene preservar estas decisiones:

- ✅ **Contraseñas con `password_hash` + `PASSWORD_DEFAULT` y `cost => 12`**, verificadas
  con `password_verify`. Es la práctica correcta.
- ✅ **Consultas 100% parametrizadas.** Los 39 modelos usan `prepare()` + `bindParam()`
  sobre stored procedures. **No se encontró ni una sola concatenación de `$_POST`/`$_GET`
  en SQL** — el sistema es sólido frente a inyección SQL.
- ✅ `Options -Indexes` en `.htaccess` evita el listado de directorios.
- ✅ `FilesMatch` fuerza la descarga de PDF/DOC en lugar de renderizarlos inline.
- ✅ El `.gitignore` ya excluye conexiones, dumps y fotos de usuarios, con `.example.php`
  publicados — la intención de manejo de secretos es correcta.

---

## 7. Plan de hardening por fases

### Fase 0 — Bloqueante antes de cualquier exposición pública (1–2 semanas)

| # | Acción | Hallazgo |
|---|---|---|
| 1 | Sesión creada en servidor; eliminar `controlador_crear_sesion.php` | H-01 |
| 2 | `core/guard.php` en los 263 controladores | H-02 |
| 3 | Validación completa de subidas + almacenamiento fuera del docroot | H-03 |
| 4 | Usuario de BD con privilegios mínimos + `.env` fuera del docroot | H-04 |
| 5 | Token CSRF global | H-05 |
| 6 | Quitar contraseña de `localStorage` | H-06 |
| 7 | Borrar `phpinfo.php`, `prueba.php`, `test_*` | H-09 |
| 8 | `session_regenerate_id` + banderas de cookie + expiración | H-08 |

### Fase 1 — Endurecimiento (2–3 semanas)

Rate limiting y bloqueo de fuerza bruta (H-07) · cabeceras de seguridad y CSP (H-11) ·
HTTPS forzado con HSTS (H-12) · assets locales o SRI (H-13) · CAPTCHA en formularios
públicos (H-14) · manejo de errores centralizado con Monolog, sin filtrar detalles (H-10).

### Fase 2 — Cumplimiento y observabilidad (continuo)

Tabla de auditoría inmutable para notas, pagos, usuarios y atenciones de salud (H-15) ·
política de contraseñas (H-18) · cifrado en reposo de las atenciones psicológicas ·
retención y anonimización conforme a la Ley 29733 · backups cifrados con restauración probada ·
consentimiento informado de los apoderados.

### Verificación

- `composer audit` y `npm audit` en CI.
- PHPStan nivel 6+ con `phpstan-strict-rules`.
- Escaneo OWASP ZAP contra el entorno de staging antes de cada release.
- Revisión manual del guard: ningún archivo nuevo en `controller/` sin `require_once guard`
  (verificable con un test automatizado que recorra el directorio).
