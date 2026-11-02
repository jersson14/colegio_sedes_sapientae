-- Fase 13: la página pública del colegio (landing.php) se administra desde Configuración → Página web.
-- Cada dato editable es una fila (clave → valor JSON). Lo que no está aquí se muestra con el texto de fábrica
-- (utilitario/pagina_web.php), así la página se ve igual hasta que el colegio la cambie.
CREATE TABLE IF NOT EXISTS pagina_web (
  clave VARCHAR(60) NOT NULL,
  valor MEDIUMTEXT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
