-- =====================================================================
-- Fase 13 (paso 15 del plan): permisos y parámetros desde una pantalla
--   permisos_pantalla: cambios de la dirección sobre los permisos de fábrica (controller/_permisos.php):
--     permitido = 1 da una pantalla a un rol; permitido = 0 se la quita. Sin fila: lo de fábrica.
--   parametros: valores que antes estaban en config/config.php (intentos de ingreso, videos, sábados,
--     nombres de las marcas de asistencia). Sin fila: el valor de config/config.php o el de fábrica.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisos_pantalla (
  rol VARCHAR(30) NOT NULL,
  vista VARCHAR(150) NOT NULL,
  permitido TINYINT(1) NOT NULL,
  usu_id INT(11) NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (rol, vista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS parametros (
  clave VARCHAR(40) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
