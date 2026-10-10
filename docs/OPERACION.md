# Operación de la plataforma (modo múltiple)

> Para quien opera el SaaS: altas, cobros, respaldos, bajas e incidencias. Despliegue en
> [DESPLIEGUE.md](DESPLIEGUE.md) §7; instancias dedicadas en [RUNBOOK-INSTANCIA-DEDICADA.md](RUNBOOK-INSTANCIA-DEDICADA.md).

## Panel de superadministrador

`https://SUPERADMIN_HOST/superadmin/` — cuentas con `php tools/crear_superadmin.php --usuario=… --nombre="…"`
(`--desactivar=…` para quitarlas). Todo lo que se hace ahí queda en la auditoría (quién, qué, colegio, cuándo, IP).

| Tarea | Dónde |
|---|---|
| Ver colegios, su uso y su plan | Tabla «Instituciones» |
| Crear o cambiar un plan (límites y precios) | Tarjeta «Planes» (mismo código = modificar) |
| Asignar plan / fin de prueba | Columna «Plan» del colegio |
| Suspender, reactivar, cancelar | Columna «Cambiar estado» |
| Registrar un pago o anular un cobro | Tarjeta «Cobros» |
| Dar de alta un colegio (o una demo) | Tarjeta «Dar de alta una institución» |
| Convertir una demo en cliente | Etiqueta «DEMO» → «Convertir en cliente» |
| Tipo de institución (colegio, instituto, CETPRO) | Columna «Tipo»: cambia sus reglas académicas por defecto |

## Ciclo de vida de un colegio

```
demo (datos de ejemplo) ─convertir─▶ PRUEBA ─▶ ACTIVO ⇄ MOROSO ─▶ SUSPENDIDO ─▶ CANCELADO ─▶ borrado
```

| Estado | Qué ve el colegio |
|---|---|
| PRUEBA | Todo, con aviso «Versión de prueba hasta…» y marca de agua en los PDF; límites del plan PRUEBA |
| ACTIVO | Todo, con los límites de su plan |
| MOROSO | Aviso de pago pendiente: consulta e imprime, pero no registra altas ni matrículas |
| SUSPENDIDO | Solo una página para que su administrador descargue sus datos, `SUSPENSION_DIAS_EXPORTACION` días |
| CANCELADO | 404. Sus datos se conservan hasta `retencion_hasta` |
| Borrado | Ya no existen su base, sus archivos ni sus respaldos; queda la constancia |

## Tareas programadas (cron del servidor)

```cron
* * * * *  php /var/www/colegio/tools/tareas_programadas.php    # vencimientos, cierre de año, consumo diario
0 2 * * *  php /var/www/colegio/tools/respaldo_tenant.php respaldar --todos
0 6 * * *  php /var/www/colegio/tools/facturacion.php           # emite cobros y marca morosos
```

Cualquier salida de esos comandos es un aviso: configura `MAILTO` en el crontab.

## Procedimientos

**Alta de un colegio:** panel, o `php tools/alta_tenant.php --slug=… --razon=… --email=… --admin-dni=… --admin-nombres=… --admin-apellidos=…`
(`--demo` para una demo). La contraseña inicial se muestra una vez: entrégala por un canal seguro.

**Actualizar la plataforma:** respaldo de todo → `git pull` → `composer install --no-dev` → `php tools/migrar_tenants.php`
(maestra y cada colegio, en orden; se detiene en el primer fallo y dice cuáles quedaron sin migrar).

**Cobros:** cada día `tools/facturacion.php` emite el cobro del periodo de cada suscripción con precio (no en
PRUEBA). Vencido `FACTURA_DIAS_PAGO` + `FACTURA_DIAS_GRACIA` sin pago → MOROSO. El pago se registra en el
panel y lo devuelve a ACTIVO. **Son cobros internos:** el comprobante electrónico (factura/boleta SUNAT) se
emite con tu OSE/PSE.

**Restaurar un colegio:** `php tools/respaldo_tenant.php restaurar --desde=<carpeta>` crea una base nueva y
comprueba fila por fila; revisa y, si es correcta, repite con `--activar` (lo anterior se conserva aparte).

**Baja:** CANCELADO en el panel → `php tools/baja_tenant.php exportar --tenant=…` (entrega) → vencida la
retención, `purgar --tenant=… --confirmar=…`. Sin vuelta atrás; la constancia queda en `RESPALDO_DIR/bajas/`.

## Incidencias

| Síntoma | Primero mirar |
|---|---|
| Un colegio ve «Institución no encontrada» | Su estado y `suspendido_desde`/`borrado_en` en el panel; el DNS del subdominio |
| «Se alcanzó el límite…» | Su plan en el panel (uso frente a límite) |
| Un colegio pasó a MOROSO sin deber | Tarjeta «Cobros»: anular el cobro equivocado lo reactiva |
| Fallo de una migración | Salida de `migrar_tenants.php`: corregir y relanzar (lo ya migrado no se repite) |
| Sospecha de acceso indebido | Auditoría del panel y log de PHP; cerrar sesiones cambiando la contraseña de la cuenta |

Pruebas que deben seguir en verde antes de cualquier cambio: `tests/E2E/aislamiento.php`, `comercial.php`,
`superadmin.php`, `demo.php` y `baja.php` (trabajo «Modo multiple» del CI).
