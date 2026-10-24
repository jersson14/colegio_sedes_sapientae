-- =====================================================================
-- Usuario de base de datos para la aplicación (Fase 9).
-- La aplicación no debe conectarse como root: este usuario solo puede leer, escribir y ejecutar los
-- procedimientos de la base del colegio (no puede borrar tablas, crear usuarios ni ver otras bases).
--
-- 1. Cambie la clave (y el nombre de la base si no es colegio2) y ejecute este archivo como root, una vez:
--      mysql -u root -p < scripts/usuario_app.sql
-- 2. Ponga el mismo usuario y clave en config/config.local.php ('db' => ['usuario' => ..., 'contrasena' => ...]).
-- 3. Las migraciones (database/migraciones) se siguen aplicando con root.
-- =====================================================================
CREATE USER IF NOT EXISTS 'colegio_app'@'localhost' IDENTIFIED BY 'CAMBIAR_POR_UNA_CLAVE_SEGURA';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, CREATE TEMPORARY TABLES ON `colegio2`.* TO 'colegio_app'@'localhost';
FLUSH PRIVILEGES;
