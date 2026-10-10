# Runbook: instancia dedicada (paquete P2)

> Para quien opera la plataforma. Un colegio que quiere «su propio servidor» recibe un VPS solo para él,
> **con el mismo código y la misma rama** que el SaaS. No es una versión aparte: es otro despliegue.

## Cuándo se usa

- El colegio exige que sus datos no compartan servidor con otros (contrato, auditoría, percepción).
- Volumen alto (cientos de alumnos y mucho tráfico) que conviene aislar.
- No es la licencia on-premise (P3): el servidor lo administras tú y las actualizaciones las aplicas tú.

## Decisión de modo

| Opción | Cuándo |
|---|---|
| `MODO_TENANT=unico` (recomendada) | Un colegio, sin panel ni facturación automática: el cobro va por contrato. Lo más simple. |
| `MODO_TENANT=multiple` con un solo colegio | Si quieres el panel de superadministrador, los planes, los cobros o la auditoría también en esa instancia. |

El resto de este runbook asume modo único; las diferencias del múltiple están en
[DESPLIEGUE.md](DESPLIEGUE.md) §7.2.

## 1. Servidor (día 1)

1. VPS Ubuntu 24.04 LTS (2 vCPU / 4 GB para hasta ~1000 alumnos), región cercana a Perú.
2. Paquetes: Apache 2.4 con `mod_rewrite` y `mod_headers`, PHP 8.2+ (`pdo_mysql`, `mysqli`, `mbstring`, `intl`,
   `gd`, `fileinfo`, `zip`, `curl`), MariaDB 10.4+ y su cliente (`mysqldump`/`mysql` **de MariaDB**, no los de MySQL 8:
   estropean los nombres con «ñ»), Certbot, UFW (solo 22, 80, 443) y fail2ban.
3. Usuario de despliegue sin root; SSH solo con llave.

## 2. Base de datos

```sql
CREATE DATABASE sge_<colegio> CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- La aplicación: solo EXECUTE y SELECT (config/usuario_bd.sql).
-- Las migraciones y los respaldos: un usuario con DDL, aparte (DB_MIGRACION_USER).
```

## 3. Código y configuración

1. `git clone` en `/var/www/colegio` y `composer install --no-dev --optimize-autoloader`.
2. VirtualHost con `DocumentRoot /var/www/colegio` y `AllowOverride All` (toda la protección de carpetas está en
   el `.htaccess`).
3. `/var/www/colegio_config/colegio.env` (permisos 640, dueño `root:www-data`), desde `config/colegio.env.example`:
   `MODO_TENANT=unico`, `DB_*`, `DB_MIGRACION_*`, `APP_ZONA_HORARIA=America/Lima`, `ALMACEN_DIR=/var/www/colegio_almacen`,
   `RESPALDO_DIR=/var/backups/colegio`, `APP_DEBUG=false`.
4. `vendor/bin/phinx migrate` crea el esquema completo.
5. La institución: nombre, logo y colores en «Empresa» (y «Personalizar»); las imágenes de `img/` son las de la
   instalación (cámbialas si el colegio no tiene logo propio aún). El primer administrador se crea con
   «Personal administrativo» desde una cuenta inicial que entregas y luego se desactiva.

## 4. DNS y TLS

- Registro A del dominio del colegio (o `colegio.tudominio.pe`) al VPS.
- `certbot --apache -d <dominio>`; redirección a HTTPS y HSTS.
- Comprobar: `curl -I https://<dominio>/.git/config` → 404, `/tools/` → 403/404, `/colegio.sql` → 403.

## 5. Tareas programadas

```cron
* * * * *  php /var/www/colegio/tools/tareas_programadas.php      # tareas y exámenes vencidos, cierre de año
0 2 * * *  php /var/www/colegio/tools/respaldo_tenant.php respaldar
30 2 * * * rclone sync /var/backups/colegio remoto:colegio-<slug>  # copia FUERA del VPS (B2, S3, Spaces…)
```

`event_scheduler` puede quedar apagado: el cron hace lo mismo.

## 6. Respaldos y restauración (probar antes de entregar)

1. `php tools/respaldo_tenant.php respaldar` y `verificar --desde=<carpeta>`.
2. Ensayo: `restaurar --desde=<carpeta>` (crea una base nueva y comprueba fila por fila); revisa y bórrala.
3. Anota el resultado en la ficha del cliente. Repetir el ensayo cada trimestre.

## 7. Actualizaciones

```bash
cd /var/www/colegio && git pull && composer install --no-dev --optimize-autoloader
vendor/bin/phinx migrate          # solo aplica lo nuevo; el código y sus migraciones van juntos
```

Con un respaldo hecho justo antes. Si algo falla: volver al commit anterior y `restaurar` el respaldo.
Las correcciones de seguridad se aplican en todas las instancias dedicadas el mismo día que en el SaaS.

## 8. Monitoreo mínimo

- Disponibilidad de `https://<dominio>/` cada 5 minutos (UptimeRobot o similar).
- Espacio en disco < 80 %, y que el respaldo de anoche exista (`find /var/backups/colegio -mtime -1`).
- Log de PHP (`/var/log/apache2/error.log`) sin errores nuevos tras cada actualización.

## 9. Fin del contrato

Exportación con «Empresa → Exportar datos» (o `tools/respaldo_tenant.php`), entrega por canal seguro, y borrado
del VPS y de sus copias según lo pactado, dejando constancia escrita.
