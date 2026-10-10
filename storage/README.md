# storage/ — subidas y logs

Fuera de git (solo este README). No se sirve por HTTP: el `.htaccess` raíz responde 404 a `storage/`.

- `tenants/<slug>/` — almacén de archivos de cada institución (Fase 4.6), con la misma ruta relativa que
  guarda la BD: `tenants/<slug>/controller/alumnos/fotos/IMG….jpg`,
  `tenants/<slug>/controller/tareas/documentos/<carpeta>/…`. Las fotos se sirven con sesión por
  `controller/archivo/controlador_ver_archivo.php`; los documentos de tareas, por
  `controller/tareas/controlador_descargar_tarea.php`. En modo único el slug es `TENANT_SLUG`
  (por defecto `principal`). Otra ubicación, fuera del proyecto: `ALMACEN_DIR` en `colegio.env`.
- Los archivos subidos antes de la Fase 4 siguen en `controller/*/fotos/` y
  `controller/tareas/controller/tareas/documentos/`, y se siguen encontrando en modo único.

Copia de seguridad de una institución: su base de datos + `tenants/<slug>/`, las dos con
`php tools/respaldo_tenant.php respaldar --tenant=<slug>`. Al restaurar con `--activar` quedan aquí
`<slug>.anterior-<fecha>/` (lo que había) hasta que se retire a mano.
