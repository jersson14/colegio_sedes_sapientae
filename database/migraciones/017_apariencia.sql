-- =====================================================================
-- Apariencia configurable del sistema (Configuración > Apariencia)
--   * Colores de la interfaz: principal (barra lateral, encabezados, botones principales) y acento
--     (elemento activo del menú, detalles). Por defecto, los del escudo del colegio: granate y dorado.
--   * Estilo de la barra lateral: COLOR (del color principal), OSCURO o CLARO.
--   * Lema o frase del colegio (opcional), que se muestra junto al nombre en el panel y en el ingreso.
--   El logo y el nombre ya se configuraban en la tabla empresa.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE empresa
  ADD COLUMN color_primario CHAR(7) NOT NULL DEFAULT '#7A1F2B',
  ADD COLUMN color_acento CHAR(7) NOT NULL DEFAULT '#C9A227',
  ADD COLUMN estilo_menu ENUM('COLOR','OSCURO','CLARO') NOT NULL DEFAULT 'COLOR',
  ADD COLUMN lema VARCHAR(150) NULL DEFAULT NULL;
