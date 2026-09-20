---
name: buscar-sp
description: Localiza, analiza y modifica los 254 stored procedures de MySQL donde vive la lógica de negocio de este sistema escolar. Úsalo cuando haya que entender de dónde salen los datos de un listado, cambiar una regla de negocio, depurar un reporte, o antes de tocar cualquier método de model/.
---

# Trabajar con los stored procedures

En este sistema **la lógica de negocio no está en PHP**. Los modelos son envoltorios
de 10 líneas; los cálculos de notas, las reglas de matrícula, los filtros por rol y
los totales de pensiones viven en **254 procedimientos almacenados** de MySQL.

Consecuencia práctica: si buscas por qué un listado devuelve lo que devuelve, buscar
en `controller/` y `model/` es perder el tiempo. Hay que ir al SP.

## Localizar un SP

El dump `colegio.sql` (330 KB) contiene todas las definiciones.

```bash
# Encontrar la definición de un SP concreto
grep -n "PROCEDURE \`SP_LISTAR_ALUMNOS\`" colegio.sql

# Ver el cuerpo completo (desde la definición hasta el END)
sed -n '/PROCEDURE `SP_LISTAR_ALUMNOS`/,/END \$\$/p' colegio.sql

# Listar todos los SPs de un dominio
grep -o "PROCEDURE \`SP_[A-Z_]*\`" colegio.sql | sort -u | grep NOTA

# Qué modelo llama a un SP
grep -rn "SP_LISTAR_ALUMNOS" model/

# Qué controlador usa ese método del modelo
grep -rn "Listar_Alumnos" controller/
```

En caliente contra la base (puerto **3307**, no 3306):

```bash
mysql -h 127.0.0.1 -P 3307 -u root colegio -e "SHOW PROCEDURE STATUS WHERE Db='colegio'"
mysql -h 127.0.0.1 -P 3307 -u root colegio -e "SHOW CREATE PROCEDURE SP_LISTAR_ALUMNOS\G"
```

## Cadena completa a rastrear

```
js/console_<modulo>.js     → qué URL llama y cómo lee la respuesta
controller/<mod>/*.php     → qué método de modelo invoca
model/model_<ent>.php      → qué SP llama y con qué parámetros
colegio.sql                → el SP: aquí está la lógica real
```

Recórrela siempre en ese orden antes de proponer un cambio.

## Convenciones de los SPs

| Prefijo | Uso | Ejemplos |
|---|---|---|
| `SP_LISTAR_` | Listados para DataTables | 60+ SPs |
| `SP_CARGAR_` | Selects, combos, datos puntuales | 30+ SPs |
| `SP_REGISTRAR_` | Alta | |
| `SP_MODIFICAR_` / `SP_EDITAR_` | Actualización | |
| `SP_ELIMINAR_` / `SP_ANULAR_` | Baja (a veces lógica, a veces física) | |
| `SP_TOTAL_` / `SP_LISTAR_TOTAL_` | Contadores del dashboard | |

Variantes frecuentes del mismo listado: `_FILTRO` (con parámetros de búsqueda),
`_UNICO` (un registro), `_ID` (por clave), `_PROFESOR` / `_ESTUDIANTE` / `_PADRES`
(la misma consulta recortada según el rol que la pide).

> Esa última familia es importante: `SP_LISTAR_CRITERIOS_NOTA_MOSTRAR_PROFESOR`,
> `..._ESTUDIANTE` y `..._PADRES` son tres SPs casi idénticos. Si cambias la regla de
> negocio en uno, **revisa si sus hermanos necesitan el mismo cambio**. Es la fuente
> más común de inconsistencias en este sistema.

## Reglas al modificar un SP

### 1. No cambies el orden ni la cantidad de columnas del SELECT

Varios modelos usan `fetchAll()` sin `PDO::FETCH_ASSOC`, así que el JS lee columnas
por **índice numérico**:

```js
rol: data[0][15],   // en js/console_usuario.js
```

Añadir una columna en medio del `SELECT` desplaza todos los índices y rompe el
frontend **sin lanzar ningún error**. Si necesitas añadir columnas:

- Añádelas **al final** del `SELECT`, o
- Cambia el modelo a `FETCH_ASSOC` y actualiza el JS a acceso por nombre — mejor
  solución, pero verifica **todos** los consumidores de ese SP antes.

Comprueba quién consume el SP antes de tocarlo:

```bash
grep -rn "SP_X" model/ && grep -rn "Metodo_Del_Modelo" controller/ js/
```

### 2. Versiona el cambio

No modifiques el SP solo en la base de datos. Guarda el `CREATE OR REPLACE PROCEDURE`
en `database/migrations/` con fecha, para que sea reproducible en otros entornos.
Hoy la única fuente de verdad es un dump manual, lo cual no es sostenible con
varios entornos (o varios tenants).

### 3. Cuidado con el DEFINER

Los SPs del dump llevan `DEFINER=`root`@`localhost``. Al importar en un servidor donde
ese usuario no existe, la importación falla. Antes de desplegar:

```bash
sed -i 's/DEFINER=`[^`]*`@`[^`]*`//g' colegio.sql
```

Es el motivo más frecuente de fallo al pasar a un hosting compartido
(ver `docs/DESPLIEGUE.md` §1).

### 4. Mantén los parámetros preparados

Los SPs se invocan desde PHP con `prepare('CALL SP_X(?,?)')` + `bindParam`. Nunca
construyas SQL dinámico con `CONCAT` + `PREPARE` dentro del SP sin escapar: reintroduce
inyección SQL por la puerta de atrás, en un sistema que hoy está limpio de ella.

### 5. Multi-tenant

Tras la Fase 4 del plan, todo SP nuevo o modificado debe recibir `p_empresa_id` y
filtrar por él en todos los joins. Un SP sin ese filtro permite que una institución
vea datos de otra. Ver `docs/MULTITENANT.md` §5.

## Probar un SP

Mientras no exista la suite de la Fase 2, prueba a mano en una base de trabajo (**nunca
en producción**):

```sql
START TRANSACTION;
CALL SP_REGISTRAR_ALUMNO('12345678','JUAN','PEREZ', ...);
SELECT * FROM alumnos WHERE dni = '12345678';
ROLLBACK;
```

Cuando exista PHPUnit, cada SP debe tener al menos una prueba de integración
(`tests/Integration/`), que es la meta "254/254" del plan de trabajo.
