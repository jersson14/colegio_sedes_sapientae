# CLAUDE.md

Guía de trabajo para Claude Code en este repositorio.

## Qué es este proyecto

Sistema web de **gestión académica escolar** para el Colegio Sedes Sapientae (Perú).
PHP "vanilla" con patrón MVC artesanal, sin framework, sin router, sin autoloader
(salvo el vendor de mPDF). Se ejecuta sobre XAMPP (Apache + MySQL/MariaDB).

Cubre: matrícula, alumnos, docentes, personal administrativo, asignaturas, horarios,
asistencia, notas/criterios de evaluación, exámenes, tareas virtuales, pensiones,
ingresos/egresos, enfermería, psicología, comunicados, reportes PDF y landing público
con solicitudes de información.

## Arranque rápido

- URL local: `http://localhost/colegio_sedes_sapientae/`
- Login: [index.php](index.php) → `js/console_usuario.js` → `controller/usuario/controlador_iniciar_sesion.php`
- Panel: [view/index.php](view/index.php) (SPA-like: un solo layout AdminLTE con menús por rol)
- BD: MySQL **puerto 3307**, base `colegio`, usuario `root` sin contraseña (ver [model/model_conexion.php](model/model_conexion.php))
- Dump: `colegio.sql` (36 tablas, 254 procedimientos almacenados) — ignorado por git

## Arquitectura en 30 segundos

```
Navegador (AdminLTE 3.2 + jQuery 3.6 + DataTables)
    │  $.ajax POST a un archivo .php concreto
    ▼
controller/<modulo>/controlador_<accion>.php   ← 263 archivos, 1 endpoint = 1 archivo
    │  require '../../model/model_<x>.php'
    ▼
model/model_<x>.php  (clase Modelo_X extends conexionBD)
    │  PDO prepare("CALL SP_...(?,?)")
    ▼
MySQL — toda la lógica SQL vive en 254 stored procedures
```

- **No hay router ni front controller.** La URL es la ruta física del archivo.
- **No hay capa de servicio ni entidades.** El controlador lee `$_POST`, el modelo llama un SP.
- **Las vistas** (`view/<modulo>/*.php`) son fragmentos HTML incluidos desde `view/index.php`.
- **El JS** (`js/<modulo>.js`) es el que orquesta: dibuja DataTables, arma formularios, llama AJAX.
- **Reportes**: `view/MPDF/` tiene su propio `vendor/` (mPDF 8.1.5) y su **propia conexión MySQLi** separada.

Detalle completo en [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md).

## Convenciones reales del código (respétalas al editar)

| Elemento | Patrón | Ejemplo |
|---|---|---|
| Controlador | `controller/<modulo>/controlador_<accion>.php` | `controlador_registrar_alumno.php` |
| Modelo | `model/model_<entidad>.php`, clase `Modelo_Entidad` | `Modelo_Alumnos` |
| Stored procedure | `SP_<VERBO>_<ENTIDAD>` en MAYÚSCULAS | `SP_LISTAR_ALUMNOS` |
| JS de módulo | `js/console_<modulo>.js` | `js/console_usuario.js` |
| Sesión | `$_SESSION['S_ID']`, `S_ROL`, `S_NOMBRE`, `S_FOTO`, `S_DNI`… | prefijo `S_` |
| Saneado de entrada | `htmlspecialchars($_POST['x'], ENT_QUOTES, 'UTF-8')` | en el controlador |
| Respuesta | `echo json_encode($consulta);` o `echo 1/0;` | sin header JSON |

Nombres de dominio en **español** (alumnos, docentes, matrícula, pensiones). Mantenlo así:
mezclar inglés rompe la coherencia con los 254 SPs.

## Cosas que NO debes asumir

- **No hay tests.** No existe PHPUnit, Jest ni CI. Si añades tests, es trabajo nuevo (ver plan).
- **No hay Composer en la raíz.** Solo `view/MPDF/composer.json` y `package.json` (choices.js).
- **`empresa_id` existe pero no se usa.** Solo en `usuario` y `empresa`; las otras 34 tablas no
  tienen discriminador de tenant. Ver [docs/MULTITENANT.md](docs/MULTITENANT.md).
- **`model_conexion.php` y `view/MPDF/conexion.php` están en `.gitignore`.** Hay `.example.php`
  para ambos. Nunca commitees credenciales.
- **`*.sql` está en `.gitignore`**, pero `colegio.sql` y `tabla_solicitudes.sql` ya están
  versionados desde antes (fueron añadidos antes de la regla).

## Deuda crítica que ya conocemos (no la "descubras" de nuevo)

1. ~~**260 de 263 controladores no verifican sesión.**~~ ✅ Autenticación resuelta (Fase 0.2):
   todo controlador empieza con `require_once __DIR__ . '/../../core/guard.php';` (401 sin sesión).
   Públicos: `iniciar_sesion`, `cerrar_sesion`, `controlador_solicitudes`. Verifica con
   `php tools/verificar_guard.php`. **Pendiente: autorización por rol (`exigir_rol`) y CSRF.**
2. ~~**La sesión se construye desde el cliente.**~~ ✅ Resuelto (Fase 0.1): la sesión la crea
   `controlador_iniciar_sesion.php` con `core/sesion.php` a partir de la BD;
   `controlador_crear_sesion.php` fue eliminado. Usa `sesion_activa()` / `sesion_crear()`.
3. **Subida de archivos sin validación.** El nombre lo elige el cliente (`nombrefoto`) y
   `move_uploaded_file` lo escribe sin comprobar extensión ni MIME → RCE por `.php`.
4. **Credenciales de BD en claro** con `root` sin contraseña.
5. **Sin CSRF, sin rate limiting, sin cabeceras de seguridad, sin HTTPS forzado.**
6. **`phpinfo.php`, `prueba.php`, `test_model.php`, `test_solicitudes.html`** expuestos en raíz.
7. 🚨 **Datos personales publicados en el repositorio público de GitHub**: 118 fotografías
   rastreadas (43 de estudiantes, 43 de docentes) más dumps con DNI, direcciones y
   atenciones de salud recuperables del historial. **No es una vulnerabilidad a explotar:
   ya está público.** Remediación en
   [docs/RUNBOOK-LIMPIEZA-HISTORIAL.md](docs/RUNBOOK-LIMPIEZA-HISTORIAL.md).
   **No hacer push a `origin` hasta completar ese runbook.**

Análisis y remediación en [docs/SEGURIDAD.md](docs/SEGURIDAD.md).
**Regla: cualquier controlador nuevo o tocado debe pasar por el guard de sesión/rol.**

## Documentación del proyecto

| Documento | Contenido |
|---|---|
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, flujo de petición, modelo de datos, inventario de módulos |
| [docs/STACK_VERSIONES.md](docs/STACK_VERSIONES.md) | Versiones exactas, EOL, rutas de actualización |
| [docs/SEGURIDAD.md](docs/SEGURIDAD.md) | Auditoría, severidades, plan de hardening |
| [docs/RUNBOOK-LIMPIEZA-HISTORIAL.md](docs/RUNBOOK-LIMPIEZA-HISTORIAL.md) | 🚨 Remediación de los datos personales ya publicados en GitHub |
| [docs/MULTITENANT.md](docs/MULTITENANT.md) | Viabilidad colegios + institutos, diseño multi-tenant |
| [docs/PLAN_DE_TRABAJO.md](docs/PLAN_DE_TRABAJO.md) | Fases, SOLID/Clean Code, estrategia de testing, empaquetado comercial |
| [docs/DESPLIEGUE.md](docs/DESPLIEGUE.md) | Hosting compartido vs VPS vs AWS, costos, CI/CD |

## Cómo trabajar aquí

- **Antes de tocar SQL**: la lógica está en stored procedures, no en PHP. Busca el SP en
  `colegio.sql` (`grep -n "PROCEDURE \`SP_X\`" colegio.sql`) antes de cambiar un modelo.
- **Antes de tocar una vista**: mira primero `js/console_<modulo>.js`; ahí está el 70% de la lógica.
- **Windows/XAMPP**: la shell por defecto es PowerShell. El puerto MySQL es 3307, no 3306.
- **No refactorices en masa sin tests.** El plan de trabajo define el orden seguro
  (seguridad → infraestructura de pruebas → refactor → multi-tenant → features).
- **Idioma**: comentarios, commits y documentación en español.
