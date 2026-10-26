-- =====================================================================
-- Fase 13 (paso 3 del plan): contraseñas temporales
--   La contraseña que pone el administrador (usuario nuevo o restablecido) es temporal: la persona debe
--   cambiarla al ingresar. Los usuarios existentes no quedan obligados.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE usuario ADD COLUMN debe_cambiar_clave TINYINT(1) NOT NULL DEFAULT 0 AFTER usu_contra;
