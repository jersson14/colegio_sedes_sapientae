-- =====================================================================
-- Fase 13 (paso 14 del plan): avisos a las familias
--   avisos_config: interruptor de cada tipo de aviso (y días de anticipación para las pensiones).
--   avisos_enviados: qué aviso se dio a qué familia, por qué canal y quién lo envió, para no repetirlo.
--   Canal hoy: WhatsApp con el mensaje ya escrito (lo envía la persona). Queda listo para un canal automático.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avisos_config (
  clave VARCHAR(40) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO avisos_config(clave, valor) VALUES
  ('falta', '1'), ('tarea', '1'), ('pension', '1'), ('comunicado', '1'), ('dias_pension', '3');

CREATE TABLE IF NOT EXISTS avisos_enviados (
  id_aviso INT(11) NOT NULL AUTO_INCREMENT,
  tipo ENUM('FALTA','TAREA','PENSION','COMUNICADO') NOT NULL,
  referencia VARCHAR(40) NOT NULL,
  id_matricula INT(11) NOT NULL,
  canal ENUM('WHATSAPP','CORREO') NOT NULL DEFAULT 'WHATSAPP',
  destino VARCHAR(100) NOT NULL DEFAULT '',
  usu_id INT(11) NULL,
  enviado_at DATETIME NOT NULL,
  PRIMARY KEY (id_aviso),
  UNIQUE KEY uq_aviso (tipo, referencia, id_matricula),
  KEY idx_aviso_matricula (id_matricula),
  CONSTRAINT fk_aviso_matricula FOREIGN KEY (id_matricula) REFERENCES matricula (id_matricula) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
