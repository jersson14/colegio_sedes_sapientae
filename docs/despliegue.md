# Despliegue y mantenimiento

Para quien instala, actualiza o mantiene el servidor del sistema.

## Requisitos

- PHP 8.1 o superior (probado con 8.2) con PDO MySQL, mysqli, mbstring y GD.
- MariaDB 10.4 o superior (o MySQL 8).
- Apache con `mod_rewrite`, `mod_headers` y `AllowOverride All` (el sistema se protege con archivos `.htaccess`).

## Instalación o actualización

1. **Respaldo** de la base antes de tocar nada (ver *Respaldos*).
2. Copiar el código. **No subir** al servidor: `.git/`, `respaldos/`, `tests/`, `node_modules/`, `config/config.local.php` de otra máquina.
3. **Migraciones**: aplicar en orden, con un usuario administrador de la base, las que falten de `database/migraciones/` (001 a 012):
   ```
   mysql --default-character-set=utf8mb4 -u root -p colegio2 < database/migraciones/0NN_nombre.sql
   ```
   Cada migración se probó primero en la base de prueba `colegio2_test` (ver *Pruebas*). Las migraciones 008 a 012 incluyen datos y triggers: no se deben aplicar dos veces.
4. **Configuración del servidor**: copiar `config/config.local.php.example` como `config/config.local.php` y completar:
   - usuario y clave de la base (el usuario restringido de la aplicación, no root);
   - `'produccion' => true` (oculta los errores de PHP y los guarda en `logs/php_errores.log`).
5. **Videos de las tareas**: el sistema acepta videos de hasta 100 MB (`'video_max_mb'` en `config.php`). El servidor debe aceptar subidas de ese tamaño: con PHP como módulo de Apache lo ajusta `.htaccess`; con PHP-FPM/CGI, `.user.ini`. Si el hosting no permite cambiarlo, el límite se reduce solo al que acepte (se muestra en el formulario). Para videos largos conviene usar enlaces de YouTube (no listado) o Google Drive.
6. Revisar *Configuración → Datos regionales*: país, moneda, zona horaria, documento de identidad y escala de notas.

## Lista para producción

- [ ] **HTTPS** activo (certificado del hosting). Con `'produccion' => true` y HTTPS, la cookie de sesión es segura y el navegador recuerda usar siempre HTTPS.
- [ ] `'produccion' => true` en `config/config.local.php` (en el `php.ini` del servidor, además, `display_errors=Off`).
- [ ] **Usuario de base de datos restringido**: ejecutar `scripts/usuario_app.sql` (cambiando la clave) y usarlo en `config.local.php`. La aplicación no necesita root; solo leer, escribir y ejecutar procedimientos.
- [ ] **Respaldos automáticos diarios** programados y probados (ver abajo), guardados fuera de la carpeta pública o en `respaldos/` (protegida).
- [ ] Comprobar desde un navegador que **no** se descargan: `respaldos/…sql`, `.git/config`, `config/config.php`, `database/…sql`, `tests/lib.sh`, `logs/…log` (deben responder 403 o 404).
- [ ] Si el servidor ya tuvo publicadas las carpetas `respaldos/` o `.git` antes de la Fase 9: cambiar las claves de los usuarios administradores y de la base de datos, porque esos archivos se podían descargar.
- [ ] Usuarios: cambiar las claves por defecto y desactivar los que no se usan.

## Respaldos

- **Windows / XAMPP**: `scripts/respaldo_diario.bat`. Programarlo en el *Programador de tareas* (diario, por ejemplo 23:00). Deja `respaldos/colegio2_AAAAMMDD_HHMM.sql`, anota en `respaldos/respaldos.log` y borra los automáticos de más de 30 días (los `…_antes_0NN.sql` de las migraciones se conservan).
- **Linux / hosting**: `scripts/respaldo_diario.sh` con cron (`0 23 * * * /ruta/scripts/respaldo_diario.sh`). Las credenciales van en `~/.my.cnf`.
- Copiar periódicamente los respaldos **fuera del servidor** (otra máquina o la nube).
- **Restaurar**: crear la base vacía y `mysql --default-character-set=utf8mb4 -u root -p colegio2 < respaldo.sql`.

## Pruebas

Las pruebas usan una copia aparte, `colegio2_test`, y nunca la base real.

1. Crear `config/config.local.php` con `['db' => ['nombre' => 'colegio2_test']]` (solo en la máquina de pruebas).
2. `bash tests/ejecutar_todo.sh`: reconstruye `colegio2_test` desde `respaldos/colegio2_20261023_1545.sql` + todas las migraciones antes de cada suite y corre las pruebas HTTP de todas las fases.
3. En el navegador (requiere Chrome y Node.js):
   - `bash tests/navegador/revisar_roles.sh`: entra con un usuario de cada rol y abre todas las pantallas de su menú.
   - `bash tests/navegador/prueba_escapado.sh`: comprueba que un texto con código no se ejecuta en pantalla.
4. `php tests/herramientas/revisar_permisos.php`: compara `controller/_permisos.php` con lo que realmente usa cada rol según su menú (debe decir 0 sobrantes y 0 faltantes).
5. Al terminar, borrar `config/config.local.php` de pruebas.

## Permisos por rol

`controller/_permisos.php` define qué roles (además del administrador) pueden usar cada controlador y reporte PDF. Un controlador que no figura ahí es **solo del administrador**. Al agregar una pantalla para otro rol, registrar sus controladores y verificar con `tests/herramientas/revisar_permisos.php`.

## Auditoría

Los cambios en las tablas de dinero y de configuración quedan en la tabla `auditoria` (quién, cuándo, IP, antes y después) y se consultan en *Configuración → Auditoría*. Los cambios hechos directamente en la base (fuera del sistema) aparecen como "(base de datos)".
