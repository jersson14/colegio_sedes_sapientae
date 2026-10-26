-- Usuario de SOLO LECTURA para los respaldos generados desde el sistema (Configuración > Respaldos).
-- El usuario de la aplicación (usuario_app.sql) no puede leer vistas, procedimientos ni triggers; sin este
-- usuario el respaldo incluye todas las tablas y datos, pero esos objetos se recrean con las migraciones.
-- 1) Cambie la clave. 2) Ejecútelo como administrador de la base. 3) En config/config.local.php agregue:
--      'db_respaldo' => ['usuario' => 'colegio_respaldo', 'contrasena' => 'LA_CLAVE'],
CREATE USER IF NOT EXISTS 'colegio_respaldo'@'localhost' IDENTIFIED BY 'CAMBIAR_POR_UNA_CLAVE_SEGURA';
GRANT SELECT, SHOW VIEW, TRIGGER, LOCK TABLES ON `colegio2`.* TO 'colegio_respaldo'@'localhost';
-- Leer el código de procedimientos y funciones (MariaDB 10.4 / MySQL 5.7)
GRANT SELECT ON `mysql`.`proc` TO 'colegio_respaldo'@'localhost';
FLUSH PRIVILEGES;
