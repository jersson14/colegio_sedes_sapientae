-- Fase 1: registro de intentos fallidos de inicio de sesión (bloqueo temporal por usuario)
CREATE TABLE IF NOT EXISTS login_intentos (
  id_intento  INT AUTO_INCREMENT PRIMARY KEY,
  usuario     VARCHAR(250) NOT NULL,
  ip          VARCHAR(45)  NOT NULL,
  fecha       DATETIME     NOT NULL,
  INDEX idx_usuario_fecha (usuario, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
