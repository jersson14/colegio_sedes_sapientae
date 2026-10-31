-- =====================================================================
-- Fase 13 (paso 14 del plan): comunicados con lectura
--   Se registra cuándo cada usuario abre un comunicado, para saber qué familias lo leyeron y volver a avisar
--   a las que no. Una fila por comunicado y usuario (la primera lectura).
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comunicado_lecturas (
  id_comunicado INT(11) NOT NULL,
  usu_id INT(11) NOT NULL,
  leido_at DATETIME NOT NULL,
  PRIMARY KEY (id_comunicado, usu_id),
  KEY idx_lectura_usuario (usu_id),
  CONSTRAINT fk_lectura_comunicado FOREIGN KEY (id_comunicado) REFERENCES comunicados (id_comunicado) ON DELETE CASCADE,
  CONSTRAINT fk_lectura_usuario FOREIGN KEY (usu_id) REFERENCES usuario (usu_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
