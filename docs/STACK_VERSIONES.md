# Stack tecnológico y versiones

> Inventario verificado sobre el repositorio el 2026-09-20.
> Fuente: `composer.lock`, cabeceras de los bundles minificados, `php -v`, `package.json`.

---

## 1. Runtime y servidor

| Componente | Versión actual | Última estable (2026) | Estado | Notas |
|---|---|---|---|---|
| PHP | **8.2.12** (build XAMPP, ZTS VC++2019) | 8.4.x / 8.5.x | ⚠️ Soporte de seguridad de 8.2 termina **31-dic-2026** | El código ya es compatible 8.x; no usa APIs eliminadas |
| Apache | El de XAMPP (2.4.x) | 2.4.x | ✅ | Solo se usa `.htaccess` con `mod_rewrite` y `Options -Indexes` |
| MySQL / MariaDB | XAMPP, **puerto 3307** | MySQL 8.4 LTS / MariaDB 11.4 LTS | ⚠️ | 254 stored procedures; migrar requiere validar sintaxis de SPs |
| Sistema | Windows 11 + XAMPP | — | ⚠️ Solo desarrollo | Producción debe ser Linux |

**Sobre PHP 8.2 → 8.3/8.4**: el riesgo es bajo. El código no usa `${}` en strings,
ni `utf8_encode`, ni constantes dinámicas de clase. Los puntos a revisar son:
- Propiedades dinámicas (deprecadas en 8.2, error en 9.0): los modelos no las usan.
- `mysqli` en `view/MPDF/` — sigue soportado.
- mPDF 8.1.5 **no declara compatibilidad con PHP 8.3+**; hay que subir a mPDF 8.2.x.

---

## 2. Backend / PHP

| Librería | Versión instalada | Última | Criticidad de actualizar |
|---|---|---|---|
| mpdf/mpdf | **8.1.5** | 8.2.x | 🔴 Alta — 8.1.x tiene CVEs conocidos de path traversal en anotaciones e imágenes remotas, y es la que bloquea PHP 8.3 |
| mpdf/qrcode | ^1.2 | 1.2.x | 🟢 Baja |
| Composer | Solo en `view/MPDF/` | — | 🟠 Media — falta `composer.json` en la raíz |

> **No hay ninguna otra dependencia PHP.** Ni logger, ni validador, ni librería JWT,
> ni cliente de correo, ni testing. Todo es código propio o nada.

---

## 3. Frontend

| Librería | Versión | Última | Estado |
|---|---|---|---|
| AdminLTE | **3.2.0** | 4.0.x (Bootstrap 5) | 🟠 3.2.0 es la última de la rama 3. AdminLTE 4 es reescritura completa: migración costosa |
| Bootstrap | **4.6.1** | 5.3.x | 🔴 Bootstrap 4 está **EOL desde 2023**, sin parches de seguridad |
| jQuery | **3.6.0** | 3.7.1 | 🟠 3.6.0 tiene vulnerabilidad de XSS conocida vía `.html()` con HTML no confiable; 3.7.1 la corrige |
| DataTables | **1.13.4** | 2.x | 🟠 Funciona, pero 1.x ya no recibe features |
| SweetAlert2 | **11 (vía CDN `@11`)** | 11.x | ⚠️ Se carga sin *pin* de versión desde `cdn.jsdelivr.net` → cambios silenciosos y dependencia de terceros |
| Choices.js | **^11.0.2** (npm) | 11.x | 🟢 |
| Select2 | **4.1.0-rc.0** (CDN) | 4.1.0-rc.0 | 🟠 Es una *release candidate* en producción |
| Font Awesome | Free (bundle AdminLTE) | 6.x | 🟢 |

### Riesgo de CDN sin integridad

`view/index.php` e `index.php` cargan SweetAlert2 y Select2 desde CDN **sin atributo
`integrity` ni `crossorigin`**. Si el CDN se compromete, se ejecuta código arbitrario
en la sesión de todos los usuarios, incluido el administrador. Además el sistema
**no funciona sin internet**, lo cual importa en colegios con conectividad intermitente.

**Acción recomendada:** descargar y servir localmente, o añadir SRI con versión fijada.

---

## 4. Ruta de actualización recomendada

### Etapa 1 — Sin romper nada (1–2 días)

```
mPDF        8.1.5  → 8.2.x      (composer update en view/MPDF/)
jQuery      3.6.0  → 3.7.1      (reemplazo de archivo; API compatible)
SweetAlert2 @11    → 11.x fijo, servido localmente
Select2     RC     → 4.0.13 estable, servido localmente
DataTables  1.13.4 → 1.13.11    (parche dentro de la misma rama)
```

Riesgo: **bajo**. Ninguno rompe API. Verificar manualmente: login, DataTables de
alumnos, generación de una boleta PDF.

### Etapa 2 — Con plan de pruebas (1–2 semanas)

```
PHP         8.2 → 8.3           (tras subir mPDF)
MySQL       → 8.4 LTS o MariaDB 11.4 LTS
Charset     unificar utf8 → utf8mb4 en las 7 tablas rezagadas
Composer    añadir composer.json en la raíz + autoload PSR-4
```

### Etapa 3 — Con refactor de UI (1–2 meses, opcional)

```
Bootstrap 4.6 → 5.3  +  AdminLTE 3.2 → 4.x
DataTables 1.x → 2.x
```

Esto **toca las 33 carpetas de vistas y los 45 archivos JS** (cambian clases de
utilidad, `data-toggle` → `data-bs-toggle`, se elimina la dependencia de jQuery).
Solo tiene sentido hacerlo *después* de tener pruebas E2E. No es urgente por seguridad
si el panel está tras autenticación correcta, pero sí por mantenibilidad a largo plazo.

---

## 5. Lo que falta en el stack

| Necesidad | Hoy | Propuesta |
|---|---|---|
| Gestor de dependencias raíz | ❌ | Composer + PSR-4 (`App\`) |
| Pruebas unitarias | ❌ | PHPUnit 11 |
| Pruebas E2E | ❌ | Playwright (o Cypress) |
| Análisis estático | ❌ | PHPStan nivel 5→8, incremental |
| Estilo de código | ❌ | PHP-CS-Fixer con preset PSR-12 |
| Logging | ❌ (`echo` del error) | Monolog, a archivo rotado |
| Variables de entorno | ❌ (hardcoded) | `vlucas/phpdotenv` + `.env` fuera del docroot |
| Migraciones de BD | ❌ (dump manual) | Phinx o Doctrine Migrations |
| CI | ❌ | GitHub Actions (lint + PHPStan + PHPUnit) |
| Gestión de assets | Parcial (npm con 1 paquete) | Vite o mantener assets locales versionados |

---

## 6. Tabla resumen de decisiones

| Decisión | Recomendación | Por qué |
|---|---|---|
| ¿Reescribir en Laravel? | **No de entrada** | 254 SPs + 263 endpoints + 0 tests = reescritura de 6–9 meses con alto riesgo de regresión funcional. Ver estrategia de estrangulamiento en el plan. |
| ¿Subir a PHP 8.3? | Sí, tras mPDF 8.2 | Amplía la ventana de soporte de seguridad hasta 2027 |
| ¿Bootstrap 5 ya? | No todavía | Bloqueante: requiere suite E2E previa |
| ¿Quitar los stored procedures? | Gradualmente | Son el activo de lógica de negocio; migrarlos a PHP permite testearlos, pero es trabajo de meses |
| ¿MySQL o PostgreSQL? | Quedarse en MySQL | Los 254 SPs son sintaxis MySQL; migrar a PG los reescribe todos |
