-- Tabla para almacenar las solicitudes de información de la landing page
CREATE TABLE IF NOT EXISTS `solicitudes_informacion` (
  `id_solicitud` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `nivel_interes` ENUM('inicial','primaria','secundaria') NOT NULL,
  `mensaje` TEXT NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` ENUM('PENDIENTE','CONTACTADO','ATENDIDO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `ip_registro` VARCHAR(45) DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_atencion` DATETIME DEFAULT NULL,
  `atendido_por` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_registro`),
  KEY `idx_nivel` (`nivel_interes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitudes de información desde la landing page';
