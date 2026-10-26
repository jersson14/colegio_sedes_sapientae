# Design

Sistema visual del sistema escolar. La identidad la pone cada colegio desde *Configuración → Apariencia*. Este documento fija cómo se aplica.

## Tema configurable

`utilitario/tema.php` lee de la tabla `empresa` el color principal, el color de acento, el estilo del menú, el lema y el escudo. Con eso emite variables CSS en `<style id="tema-variables">`. Ningún archivo usa colores de marca fijos: todo sale de estas variables.

| Variable | Uso |
|---|---|
| `--tema-primario` / `--tema-primario-texto` | Encabezados (panel, tablas, ventanas), botón principal, elementos activos. El texto se elige blanco u oscuro según el contraste WCAG. |
| `--tema-primario-oscuro` | Menú lateral (estilo "del color"), botón principal al pasar el cursor. Es el principal mezclado con 28 % de negro. |
| `--tema-primario-claro` / `--tema-primario-borde` | Fondos de íconos, tramos del año escolar, filas activas. Es el principal mezclado con 88 % / 70 % de blanco. |
| `--tema-tinta` | Títulos y enlaces sobre blanco. Es el principal, o su versión oscura si no llega a 4.5:1. |
| `--tema-acento` / `--tema-acento-texto` | Marca "Hoy", bimestre en curso, títulos de sección del menú, opción activa del menú, foco del teclado. |
| `--tema-menu`, `--tema-menu-texto`, `--tema-menu-activo` | Barra lateral según el estilo: COLOR, OSCURO (`#1F2226`) o CLARO (`#FFFFFF`). |
| `--tema-fondo` | Fondo de la aplicación: un gris cálido teñido levemente del color principal. |

**Por defecto** (colores del escudo de Sedes Sapientiae): principal `#7A1F2B`, acento `#C9A227`, menú del color principal. Combinaciones sugeridas: `TEMA_PRESETS` en `utilitario/tema.php`.

## Base

- **Tipografía:** Source Sans Pro (AdminLTE). Las cifras usan `font-variant-numeric: tabular-nums`. Títulos de bloque en 1.05rem / 700 y de página en 1.75rem / 700.
- **Componentes:** AdminLTE 3 / Bootstrap 4 estándar (botones, formularios, tablas DataTables, ventanas SweetAlert2). No se inventan controles propios.
- **Superficies:** tarjetas blancas con radio de 14px y sombra `0 1px 3px rgba(20,20,30,.07)`, sobre `--tema-fondo`. Sin tarjetas anidadas.
- **Colores de estado** (fijos, no dependen del tema): presente `#2E8B57`, tardanza `#E0A100`, falta `#C0392B`, justificada `#2D6CDF`. Alerta: fondo `#FBE9E7` y texto `#B23A2A`. Aviso: `#FFF4D6` / `#8A6100`. Correcto: `#E6F4EC` / `#2E7D4F`.

## Firma

La **línea del año escolar** (panel de inicio): los bimestres como tramos proporcionales a sus fechas, el bimestre en curso en el color principal y la marca "Hoy" en el acento. Es el elemento que repiten los paneles de cada rol (ver `docs/plan_interfaz.md`, etapa 1).

## Archivos

- `utilitario/tema.php`: lectura del tema, colores derivados, contraste y combinaciones sugeridas.
- `css/tema.css`: aplica el tema sobre AdminLTE y sobre los colores fijos heredados (`[style*="background-color:#0A5D86"]`, `#1FA0E0`, `#023D77`).
- `css/inicio.css`: panel de inicio (prefijo `pi-`).
- `index.php`: pantalla de ingreso, con sus estilos en línea (prefijo `ing-`).
