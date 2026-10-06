-- Usuario de BD con privilegios mínimos para la aplicación (Fase 0.4).
-- Ejecutar como administrador DESPUÉS de importar colegio.sql
-- (el GRANT sobre solicitudes_informacion falla si la tabla aún no existe).
-- Cambia la contraseña y cópiala en DB_PASS del colegio.env.
--
-- Por qué estos permisos:
--   EXECUTE  → toda la lógica está en los 254 procedimientos, que corren con los
--              permisos de su DEFINER (quien importó el dump), no con los de este usuario.
--   SELECT   → los reportes de view/MPDF/REPORTE/ hacen SELECT directos.
--   INSERT, UPDATE en solicitudes_informacion → model/model_solicitudes.php usa SQL directo.
-- Sin DDL, sin DELETE directo, sin FILE, sin GRANT, sin acceso a otras bases.

CREATE USER IF NOT EXISTS 'colegio_app'@'localhost' IDENTIFIED BY 'cambia_esta_contraseña';
GRANT EXECUTE, SELECT ON colegio.* TO 'colegio_app'@'localhost';
GRANT INSERT, UPDATE ON colegio.solicitudes_informacion TO 'colegio_app'@'localhost';
FLUSH PRIVILEGES;

-- Comprobación:
-- SHOW GRANTS FOR 'colegio_app'@'localhost';
