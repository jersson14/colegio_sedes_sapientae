# Runbook: limpieza del historial y remediación de la exposición

> **Estado: PREPARADO, NO EJECUTADO.** Ningún comando de este documento se ha corrido.
> Redactado el 2026-09-20. Requiere revisión y ejecución manual por Jersson.
>
> Repositorio afectado: `https://github.com/jersson14/colegio_sedes_sapientae`
> Visibilidad verificada: **PÚBLICO** (API de GitHub responde 200 sin autenticación).

---

## 1. Qué está expuesto

Verificado sobre el historial de git, ramas `main` y `jersson`, 27 commits.

### 1.1 En el historial (ya no rastreados, pero recuperables)

| Ruta | Contenido |
|---|---|
| `colegio.sql` | Dump completo: ~14 alumnos con DNI, nombre, sexo, fecha de nacimiento, teléfono y dirección · ~14 padres · ~15 docentes · ~29 usuarios con hash bcrypt · **~5 atenciones de salud** |
| `sistema_tramite.sql` | Dump secundario |
| `tabla_solicitudes.sql` | Solicitudes del formulario público |
| `model/model_conexion.php` | Credenciales: `root`, sin contraseña |
| `view/MPDF/conexion.php` | Credenciales MySQLi duplicadas |

Recuperable por cualquiera con: `git show 1b87ee7~1:colegio.sql`

### 1.2 Rastreados AHORA MISMO en el repositorio público

**Esto es lo más grave y no estaba identificado en la auditoría inicial.**

| Carpeta | Archivos |
|---|---|
| `controller/alumnos/fotos/` | **43 fotografías de estudiantes** |
| `controller/docentes/fotos/` | 43 fotografías de docentes |
| `controller/personal_administrativo/fotos/` | 12 |
| `controller/comunicados/fotos/` | 14 |
| `controller/empleado/FOTOS/` | 6 |
| `controller/empresa/FOTOS/` | 5 |
| **Total** | **122 archivos, 118 aún rastreados** |

> **Por qué el `.gitignore` no las detuvo:** la regla `controller/*/fotos/` solo afecta
> a archivos **no rastreados**. Los que ya estaban en el índice siguen versionándose
> indefinidamente. Añadir una regla a `.gitignore` nunca saca del repositorio lo que ya
> está dentro; hace falta `git rm --cached`.

Fotografías de menores identificables, asociadas por el mismo repositorio a sus
nombres, DNI y direcciones del dump. Bajo la **Ley 29733** son datos personales de
menores, y las atenciones de salud son datos sensibles.

### 1.3 Verificación pendiente de Jersson

**¿Son datos reales o de prueba?** Son pocos registros (~14 alumnos), compatible con
un piloto. Pero los nombres, direcciones y fotografías parecen reales. **Confirma esto
antes de decidir el alcance de la notificación** — cambia las obligaciones legales,
no el procedimiento técnico, que debe ejecutarse igual.

---

## 2. Acción inmediata (antes que cualquier otra cosa)

**Poner el repositorio en privado.** Solo puede hacerlo Jersson:

```
GitHub → colegio_sedes_sapientae → Settings → Danger Zone
→ Change repository visibility → Make private
```

Esto detiene la exposición en curso en segundos. No borra lo ya difundido, pero corta
el acceso de nuevos terceros y es condición previa para los pasos siguientes.

> No ejecutes la limpieza de historial con el repositorio aún público: la reescritura
> genera tráfico y actividad que puede atraer atención justo sobre lo que se quiere retirar.

---

## 3. Decisión: reescribir o recrear

Hay dos caminos. Para este repositorio, **el segundo es mejor**.

### Opción A — Reescribir el historial con `git filter-repo`

Conserva los 27 commits y su cronología.

**Problema serio:** GitHub mantiene accesibles los commits por su SHA incluso después
de un `push --force`. Un commit "eliminado" sigue respondiendo en
`github.com/usuario/repo/commit/<sha>` y por la API, a veces durante meses. Para
purgarlos de verdad hay que **abrir un ticket a GitHub Support** y pedir el borrado de
las referencias en caché. Además, cualquier fork existente conserva los objetos.

### Opción B — Recrear el repositorio desde cero ⭐ recomendada

Dado que solo hay 27 commits y el valor histórico es bajo:

1. Borrar el repositorio en GitHub (elimina también sus forks y la caché de SHAs).
2. Crear uno nuevo, **privado**, con el mismo nombre.
3. Subir el estado actual del código limpio, sin fotos ni dumps, como primer commit.

**Ventaja decisiva:** no quedan SHAs en caché que purgar ni tickets que abrir. Es la
única forma de estar razonablemente seguro de que el contenido ya no es recuperable
desde GitHub.

**Coste:** se pierde el historial de commits (queda respaldado en local, ver §4) y se
pierden estrellas, issues y forks — irrelevante aquí.

---

## 4. Respaldo antes de tocar nada

Innegociable. La reescritura de historial no es reversible.

```bash
cd /c/xampp/htdocs
git clone --mirror colegio_sedes_sapientae colegio_BACKUP_$(date +%Y%m%d).git
```

Guarda ese `.git` **fuera del repositorio y fuera de cualquier carpeta sincronizada
con la nube**. Contiene los datos expuestos: trátalo como material sensible, en disco
cifrado, y bórralo cuando ya no sea necesario.

---

## 5. Procedimiento — Opción B (recomendada)

### 5.1 Sacar del rastreo las fotos y los dumps

```bash
cd /c/xampp/htdocs/colegio_sedes_sapientae

# Deja de versionar las 118 fotos, SIN borrarlas del disco
git rm -r --cached controller/alumnos/fotos
git rm -r --cached controller/docentes/fotos
git rm -r --cached controller/personal_administrativo/fotos
git rm -r --cached controller/comunicados/fotos
git rm -r --cached controller/empleado/FOTOS
git rm -r --cached controller/empresa/FOTOS

git commit -m "chore: dejar de versionar fotografias de personas"
```

### 5.2 Corregir el `.gitignore`

Las reglas actuales no cubren todas las carpetas. Reemplazar por:

```gitignore
# Fotografías de personas — NUNCA versionar
controller/*/fotos/
controller/*/FOTOS/
controller/**/fotos/
storage/

# Dumps de base de datos
*.sql

# Conexiones con credenciales
model/model_conexion.php
view/MPDF/conexion.php
.env

# Dependencias
/node_modules/
/vendor/

# Diagnóstico
phpinfo.php
prueba.php
test_*.php
test_*.html
```

### 5.3 Verificar que no queda nada sensible

```bash
# Debe salir vacío
git ls-files | grep -iE "fotos/|FOTOS/|\.sql$|conexion\.php$|\.env$"
```

### 5.4 Recrear el repositorio

```bash
# 1. En GitHub: borrar colegio_sedes_sapientae
#    Settings → Danger Zone → Delete this repository
# 2. En GitHub: crear colegio_sedes_sapientae de nuevo, PRIVADO, vacío

# 3. En local: historial nuevo desde el estado limpio
cd /c/xampp/htdocs/colegio_sedes_sapientae
rm -rf .git
git init -b main
git add -A

# Comprobación final ANTES del primer commit
git status --short | grep -iE "fotos/|FOTOS/|\.sql$|conexion\.php$" && echo "ABORTAR: queda contenido sensible"

git commit -m "chore: estado inicial del repositorio, sin datos personales"
git remote add origin https://github.com/jersson14/colegio_sedes_sapientae.git
git push -u origin main
```

---

## 6. Procedimiento — Opción A (si prefieres conservar el historial)

```bash
pip install git-filter-repo

cd /c/xampp/htdocs
git clone --mirror https://github.com/jersson14/colegio_sedes_sapientae.git limpieza.git
cd limpieza.git

git filter-repo --invert-paths \
  --path colegio.sql \
  --path sistema_tramite.sql \
  --path tabla_solicitudes.sql \
  --path model/model_conexion.php \
  --path view/MPDF/conexion.php \
  --path-glob 'controller/*/fotos/*' \
  --path-glob 'controller/*/FOTOS/*'

# Verificar en TODAS las ramas (main y jersson)
git log --all --pretty=format: --name-only | sort -u | grep -iE "fotos/|\.sql$|conexion\.php$"
# ↑ debe salir vacío

git remote add origin https://github.com/jersson14/colegio_sedes_sapientae.git
git push --force --all
git push --force --tags
```

> ⚠️ La rama `jersson` también contiene los archivos (commits `b2dcfda` y `9f2d20a`).
> `--all` es obligatorio; reescribir solo `main` deja la fuga intacta.
>
> ⚠️ Después: abrir ticket en GitHub Support pidiendo la purga de los SHAs en caché,
> y borrar cualquier fork existente. Sin eso, la limpieza es incompleta.

---

## 7. Rotación de credenciales

La limpieza del historial no invalida lo que ya se filtró. Obligatorio:

- [ ] **Contraseña de los 29 usuarios**: los hashes bcrypt (cost 12) son públicos.
      Bcrypt con cost 12 es resistente, pero una contraseña débil se rompe igual por
      diccionario. Forzar cambio a todos los usuarios.
- [ ] **Usuario de MySQL**: eliminar el uso de `root` sin contraseña. Crear
      `colegio_app` con privilegios mínimos y contraseña fuerte (hallazgo H-04).
- [ ] **Revisar accesos**: si el servidor MySQL estuvo alguna vez accesible desde
      internet con `root` sin contraseña, asumir compromiso y auditar.

---

## 8. Cumplimiento (Ley 29733)

Si se confirma que los datos son reales, evaluar con asesoría legal:

- [ ] Registro interno del incidente: qué se expuso, desde cuándo, alcance estimado.
      La fecha de inicio es el primer commit que incluyó los datos.
- [ ] Notificación a la Autoridad Nacional de Protección de Datos Personales.
- [ ] Notificación a los apoderados de los menores afectados.
- [ ] Documentar las medidas correctivas adoptadas (este runbook sirve de evidencia).

> No soy asesor legal. Esta lista es orientativa; la obligación concreta de notificar
> y sus plazos deben confirmarse con un abogado especializado.

---

## 9. Prevención

Para que no vuelva a ocurrir:

- [ ] `.gitignore` corregido (§5.2) **antes** de volver a subir nada.
- [ ] Hook de pre-commit que rechace `.sql`, `conexion.php`, `.env` y rutas de fotos.
- [ ] Escaneo de secretos en CI (`gitleaks` o `trufflehog`).
- [ ] Mantener el repositorio **privado** mientras existan las vulnerabilidades
      críticas de `docs/SEGURIDAD.md`.
- [ ] Las subidas de usuarios deben ir a `storage/` **fuera del docroot** y fuera del
      repositorio (hito 4.6 del plan y hallazgo H-03).

Ejemplo de hook, en `.git/hooks/pre-commit`:

```bash
#!/bin/sh
if git diff --cached --name-only | grep -iE "fotos/|FOTOS/|\.sql$|conexion\.php$|^\.env$"; then
  echo "BLOQUEADO: intento de commitear datos sensibles."
  exit 1
fi
```

---

## 10. Orden de ejecución

```
1. Repositorio a PRIVADO                    ← Jersson, ahora
2. Confirmar si los datos son reales        ← Jersson
3. Backup espejo (§4)                       ← obligatorio antes de tocar
4. Elegir Opción A o B                      ← recomendada: B
5. Ejecutar el procedimiento
6. Verificar que no queda nada sensible
7. Rotar credenciales (§7)
8. Evaluar notificación legal (§8)
9. Aplicar prevención (§9)
10. Recién entonces: push de la documentación pendiente
```

**Los dos commits de documentación (`bc3836f`, `0b04327`) quedan sin subir hasta
completar este runbook.** `docs/SEGURIDAD.md` contiene el detalle de explotación de
los 4 fallos críticos y no debe publicarse mientras sigan sin corregir.
